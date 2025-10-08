<?php
// pdf/generate.php
ob_start(); // capture accidental output so FPDF can send headers
require_once __DIR__ . '/../includes/auth.php';
require_login('any'); // allow admin or employee
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/fpdf.php';

$user = $_SESSION['user'];

// Determine employee
if ($user['type'] === 'admin' && isset($_GET['id'])) {
    $employeeId = (int) $_GET['id'];
} else {
    if ($user['type'] !== 'employee') {
        ob_end_clean();
        die("Only employees or admins may generate timesheets.");
    }
    $employeeId = (int) $user['id'];
}

// 📅 Date range selection
if (!empty($_GET['start']) && !empty($_GET['end'])) {
    $periodStart = date('Y-m-d', strtotime($_GET['start']));
    $periodEnd   = date('Y-m-d', strtotime($_GET['end']));
    
    // Extract year and month for filename
    $y = date('Y', strtotime($periodStart));
    $m = date('m', strtotime($periodStart));
} else {
    $m = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('m');
    $y = isset($_GET['year'])  ? (int) $_GET['year']  : (int) date('Y');
    $periodStart = sprintf('%04d-%02d-01', $y, $m);
    $periodEnd   = date('Y-m-t', strtotime($periodStart));
}

$year = $y;
$month = $m;

// Fetch employee (including expected shift times if present)
$empStmt = $pdo->prepare("SELECT fullname, role, hourly_pay, overtime_applicable, late_fee_applicable, expected_clockin, expected_clockout FROM employees WHERE id = ?");
$empStmt->execute([$employeeId]);
$employee = $empStmt->fetch(PDO::FETCH_ASSOC);
if (!$employee) {
    if (ob_get_length()) ob_end_clean();
    die("Employee not found.");
}

// ---- HTML Form for date selection ----
if (!isset($_GET['download'])) {
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Generate Timesheet</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f5f6f8;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                height: 100vh;
                margin: 0;
            }
            .form-container {
                background: #fff;
                padding: 30px;
                border-radius: 12px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                width: 350px;
                text-align: center;
            }
            h2 {
                color: #333;
                margin-bottom: 20px;
            }
            label {
                font-weight: bold;
                display: block;
                margin-top: 10px;
            }
            input[type="date"] {
                width: 90%;
                padding: 8px;
                border: 1px solid #ccc;
                border-radius: 6px;
                margin-top: 5px;
            }
            button {
                margin-top: 20px;
                padding: 10px 20px;
                border: none;
                border-radius: 6px;
                background: #007bff;
                color: #fff;
                cursor: pointer;
                font-size: 14px;
            }
            button:hover {
                background: #0056b3;
            }
        </style>
    </head>
    <body>
        <div class="form-container">
            <h2>Generate Timesheet</h2>
            <form method="get" action="">
                <input type="hidden" name="id" value="' . htmlspecialchars($employeeId) . '">
                <label>Start Date:</label>
                <input type="date" name="start" required value="' . date('Y-m-01') . '">

                <label>End Date:</label>
                <input type="date" name="end" required value="' . date('Y-m-t') . '">

                <input type="hidden" name="download" value="1">
                <button type="submit">📄 Generate Report</button>
            </form>
        </div>
    </body>
    </html>';
    exit;
}

// Determine expected hours (use employee expected times if set, else default to 8)
$expectedHours = 8.0;
$expectedClockInDefault = '07:45';
if (!empty($employee['expected_clockin']) && !empty($employee['expected_clockout'])) {
    $t1 = strtotime($employee['expected_clockin']);
    $t2 = strtotime($employee['expected_clockout']);
    if ($t2 > $t1) {
        $expectedHours = ($t2 - $t1) / 3600;
    } else {
        // overnight shift
        $expectedHours = (($t2 + 24*3600) - $t1) / 3600;
    }
}

// Fetch attendance rows for the period
$attStmt = $pdo->prepare("
    SELECT * 
    FROM attendance
    WHERE employee_id = ? 
      AND work_date BETWEEN ? AND ?
    ORDER BY work_date ASC
");
$attStmt->execute([$employeeId, $periodStart, $periodEnd]);
$rows = $attStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch holiday dates into array
$holidayDates = [];
try {
    $holidayStmt = $pdo->query("SELECT holiday_date FROM holidays");
    $holidayRows = $holidayStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($holidayRows as $h) {
        $holidayDates[] = $h['holiday_date'];
    }
} catch (\Throwable $e) {
    $holidayDates = [];
}

// Totals initialization
$totalHours = 0.0;          // actual worked hours (float)
$totalOvertime = 0.0;       // overtime hours (float)
$totalRegularPay = 0.0;     // money
$totalOvertimePay = 0.0;    // money
$totalLateFee = 0.0;        // money
$daysWorked = 0;

$hourlyRate = (float)($employee['hourly_pay'] ?? 0.0);
$otApplicable = (int)($employee['overtime_applicable'] ?? 0);
$lateFeeApplicable = isset($employee['late_fee_applicable']) ? (int)$employee['late_fee_applicable'] : 1; // default to apply late fee

// Prepare PDF
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();

// Logo
$logoPath = __DIR__ . '/../assets/img/IROKS.jpg';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 10, 8, 12);
}

$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'iRoks - Timesheet Report', 0, 1, 'C');

$pdf->Ln(2);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, 'Employee: ' . ($employee['fullname'] ?? '-'), 0, 1);
$pdf->Cell(0, 6, 'Role: ' . ($employee['role'] ?? '-'), 0, 1);
$pdf->Cell(0, 6, 'Period: ' . date('d M Y', strtotime($periodStart)) . ' - ' . date('d M Y', strtotime($periodEnd)), 0, 1);
$pdf->Ln(4);

// Table header (adjusted widths so they fit A4)
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(20, 8, 'Date', 1, 0, 'C');
$pdf->Cell(18, 8, 'Clock In', 1, 0, 'C');
$pdf->Cell(18, 8, 'Clock Out', 1, 0, 'C');
$pdf->Cell(16, 8, 'Hours', 1, 0, 'C');
$pdf->Cell(16, 8, 'OT (h)', 1, 0, 'C');
$pdf->Cell(26, 8, 'Reg Pay', 1, 0, 'C');
$pdf->Cell(26, 8, 'OT Pay', 1, 0, 'C');
$pdf->Cell(16, 8, 'Late', 1, 0, 'C');
$pdf->Cell(34, 8, 'Comment', 1, 1, 'C');

$pdf->SetFont('Arial', '', 9);

// Loop rows and compute pay (dynamic overtime calculation + late fee + hourly-only regular pay)
foreach ($rows as $r) {
    $date = $r['work_date'] ?? '-';
    $in = !empty($r['clockin_time']) ? date('H:i', strtotime($r['clockin_time'])) : '-';
    $out = !empty($r['clockout_time']) ? date('H:i', strtotime($r['clockout_time'])) : '-';

    $workedFloat = 0.0;
    $overtime = 0.0;
    $lateFee = 0.0;
    $regularPaidHours = 0.0;
    $regularPay = 0.0;
    $overtimePay = 0.0;

    if (!empty($r['clockin_time']) && !empty($r['clockout_time'])) {
        $inT = strtotime($r['clockin_time']);
        $outT = strtotime($r['clockout_time']);

        if ($outT > $inT) {
            $workedFloat = ($outT - $inT) / 3600.0;
        } else {
            $workedFloat = 0.0;
        }

        // compute overtime relative to expectedHours
        if ($workedFloat > $expectedHours) {
            $overtime = round($workedFloat - $expectedHours, 2);
        } else {
            $overtime = 0.0;
        }

        // regular paid hours: up to expected (whole hours)
        $regularPaidHours = min($expectedHours, round($workedFloat));


        // Day identification
        $isSunday = (date('N', strtotime($date)) == 7);
        $isHoliday = in_array($date, $holidayDates);

        // multipliers
        $regMultiplier = ($isHoliday || $isSunday) ? 2.0 : 1.0;
        $otMultiplier  = ($isHoliday || $isSunday) ? 2.0 : 1.5;

        // pay calculations
        $regularPay = $regularPaidHours * $hourlyRate * $regMultiplier;
        $overtimePay = ($otApplicable && $overtime > 0) ? ($overtime * $hourlyRate * $otMultiplier) : 0.0;

        // Late fee calculation only if applicable to employee
        if ($lateFeeApplicable) {
            $expectedClockIn = !empty($employee['expected_clockin']) ? $employee['expected_clockin'] : $expectedClockInDefault;
            $expectedStartTs = strtotime($date . ' ' . $expectedClockIn);

            $threshold10 = strtotime('+5 minutes', $expectedStartTs);
            $threshold15 = strtotime('+15 minutes', $expectedStartTs);

            if ($inT > $threshold10 && $inT < $threshold15) {
                $lateFee = 50.0;
            } elseif ($inT >= $threshold15) {
                $lateFee = $hourlyRate;
            } else {
                $lateFee = 0.0;
            }
        }
    }

    // accumulate totals
    $totalHours += $workedFloat;
    $totalOvertime += $overtime;
    $totalRegularPay += $regularPay;
    $totalOvertimePay += $overtimePay;
    $totalLateFee += $lateFee;
    if ($workedFloat > 0) $daysWorked++;

    // comment
    $comment = isset($r['comment']) ? preg_replace("/\s+/", " ", trim($r['comment'])) : '';
    $commentShort = (strlen($comment) > 30) ? substr($comment, 0, 27) . '...' : $comment;

    // mark day note
    $dayNote = '';
    if ($isHoliday) $dayNote = ' (Holiday)';
    elseif ($isSunday) $dayNote = ' (Sunday)';

    // Row output
    $pdf->Cell(20, 7, $date . $dayNote, 1, 0, 'C');
    $pdf->Cell(18, 7, $in, 1, 0, 'C');
    $pdf->Cell(18, 7, $out, 1, 0, 'C');
    $pdf->Cell(16, 7, number_format($regularPaidHours, 2), 1, 0, 'C');
    $pdf->Cell(16, 7, number_format($overtime, 2), 1, 0, 'C');
    $pdf->Cell(26, 7, 'SRD ' . number_format($regularPay, 2), 1, 0, 'R');
    $pdf->Cell(26, 7, 'SRD ' . number_format($overtimePay, 2), 1, 0, 'R');
    $pdf->Cell(16, 7, ($lateFee > 0 ? 'SRD ' . number_format($lateFee, 2) : '-'), 1, 0, 'C');
    $pdf->Cell(34, 7, $commentShort, 1, 1, 'L');
}

// Summary
$pdf->Ln(6);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, 'Summary', 0, 1);

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(70, 6, 'Total actual worked hours:', 0, 0);
$pdf->Cell(0, 6, number_format($totalHours, 2) . ' h', 0, 1);

$pdf->Cell(70, 6, 'Total paid regular hours:', 0, 0);
$pdf->Cell(0, 6, 'SRD ' . number_format($totalRegularPay, 2), 0, 1);

$pdf->Cell(70, 6, 'Total overtime hours:', 0, 0);
$pdf->Cell(0, 6, number_format($totalOvertime, 2) . ' h', 0, 1);

$pdf->Cell(70, 6, 'Total Regular Pay:', 0, 0);
$pdf->Cell(0, 6, 'SRD ' . number_format($totalRegularPay, 2), 0, 1);

$pdf->Cell(70, 6, 'Total Overtime Pay:', 0, 0);
$pdf->Cell(0, 6, 'SRD ' . number_format($totalOvertimePay, 2), 0, 1);

$pdf->Cell(70, 6, 'Late Fee Deduction:', 0, 0);
$pdf->Cell(0, 6, '- SRD ' . number_format($totalLateFee, 2), 0, 1);

$grandTotal = ($totalRegularPay + $totalOvertimePay) - $totalLateFee;
$pdf->Ln(4);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(70, 8, 'Grand Total Pay:', 0, 0);
$pdf->Cell(0, 8, 'SRD ' . number_format($grandTotal, 2), 0, 1);

$pdf->SetFont('Arial', '', 9);
$pdf->Cell(70, 6, 'Days worked (distinct):', 0, 0);
$pdf->Cell(0, 6, $daysWorked . ' days', 0, 1);

$pdf->Ln(6);
$pdf->SetFont('Arial', 'I', 9);
$pdf->Cell(0, 6, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1);

// Clean any accidental buffered output before sending PDF
if (ob_get_length()) ob_end_clean();

// Output PDF inline
$cleanName = preg_replace('/[^A-Za-z0-9_\-]/', '_', ($employee['fullname'] ?? 'employee'));
$filename = "iRoks_Timesheet_{$cleanName}_{$year}_{$month}.pdf";
$pdf->Output('I', $filename);
exit;
