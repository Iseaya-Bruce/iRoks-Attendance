<?php
// pdf/generate.php
require_once __DIR__ . '/../includes/auth.php';
require_login('any'); // allow admin or employee
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/fpdf.php';

$user = $_SESSION['user'];

// If admin and an employee id is provided, generate for that employee.
if ($user['type'] === 'admin' && isset($_GET['id'])) {
    $employeeId = (int) $_GET['id'];
} else {
    if ($user['type'] !== 'employee') {
        die("Only employees or admins may generate timesheets.");
    }
    $employeeId = (int) $user['id'];
}

// month/year selection
$month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('m');
$year  = isset($_GET['year'])  ? (int) $_GET['year']  : (int) date('Y');
if ($month < 1 || $month > 12) $month = (int)date('m');
if ($year < 2000 || $year > 3000) $year = (int)date('Y');

$periodStart = sprintf('%04d-%02d-01', $year, $month);
$periodEnd   = date('Y-m-t', strtotime($periodStart));

// fetch employee
$empStmt = $pdo->prepare("SELECT fullname, role, hourly_pay, overtime_applicable FROM employees WHERE id = ?");
$empStmt->execute([$employeeId]);
$employee = $empStmt->fetch();
if (!$employee) die("Employee not found.");

// fetch attendance
$attStmt = $pdo->prepare("
    SELECT * 
    FROM attendance
    WHERE employee_id = ? 
      AND work_date BETWEEN ? AND ?
    ORDER BY work_date ASC
");
$attStmt->execute([$employeeId, $periodStart, $periodEnd]);
$rows = $attStmt->fetchAll();

// totals
$totalHours = 0.0;
$totalOvertime = 0.0;
$daysWorked = 0;
$totalRegularPay = 0.0;
$totalOvertimePay = 0.0;

$hourlyRate = (float)($employee['hourly_pay'] ?? 0);
$otApplicable = (int)($employee['overtime_applicable'] ?? 0);

foreach ($rows as $r) {
    $worked = 0;
    if (!empty($r['clockin_time']) && !empty($r['clockout_time'])) {
        $in = strtotime($r['clockin_time']);
        $out = strtotime($r['clockout_time']);
        if ($out > $in) {
            $worked = ($out - $in) / 3600;
            $totalHours += $worked;
            $daysWorked++;
        }
    }
    $overtime = isset($r['overtime_hours']) ? (float)$r['overtime_hours'] : 0.0;
    $totalOvertime += $overtime;

    // pay
    $regularPay = $worked * $hourlyRate;
    $overtimePay = $otApplicable ? $overtime * $hourlyRate * 1.5 : 0;
    $totalRegularPay += $regularPay;
    $totalOvertimePay += $overtimePay;
}

// compute working days in period (Mon-Sat)
$startDT = new DateTime($periodStart);
$endDT = new DateTime($periodEnd);
$endDT->modify('+1 day');
$interval = new DateInterval('P1D');
$period = new DatePeriod($startDT, $interval, $endDT);

$totalWorkingDays = 0;
foreach ($period as $d) {
    if ((int)$d->format('N') <= 6) $totalWorkingDays++;
}

// PDF
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
$pdf->Cell(0, 6, 'Period: ' . date('F Y', strtotime($periodStart)), 0, 1);
$pdf->Ln(4);

// Table header
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(22, 8, 'Date', 1, 0, 'C');
$pdf->Cell(22, 8, 'Clock In', 1, 0, 'C');
$pdf->Cell(22, 8, 'Clock Out', 1, 0, 'C');
$pdf->Cell(20, 8, 'Hours', 1, 0, 'C');
$pdf->Cell(20, 8, 'OT (h)', 1, 0, 'C');
$pdf->Cell(28, 8, 'Reg Pay', 1, 0, 'C');
$pdf->Cell(28, 8, 'OT Pay', 1, 0, 'C');
$pdf->Cell(36, 8, 'Comment', 1, 1, 'C');

// Rows
$pdf->SetFont('Arial', '', 9);
foreach ($rows as $r) {
    $date = $r['work_date'] ?? '-';
    $in = !empty($r['clockin_time']) ? date('H:i', strtotime($r['clockin_time'])) : '-';
    $out = !empty($r['clockout_time']) ? date('H:i', strtotime($r['clockout_time'])) : '-';

    $worked = 0;
    if (!empty($r['clockin_time']) && !empty($r['clockout_time'])) {
        $inT = strtotime($r['clockin_time']);
        $outT = strtotime($r['clockout_time']);
        if ($outT > $inT) $worked = ($outT - $inT) / 3600;
    }

    $overtime = isset($r['overtime_hours']) ? (float)$r['overtime_hours'] : 0.0;
    $regularPay = $worked * $hourlyRate;
    $overtimePay = $otApplicable ? $overtime * $hourlyRate * 1.5 : 0;

    $comment = isset($r['comment']) ? preg_replace("/\s+/", " ", trim($r['comment'])) : '';
    $commentShort = (strlen($comment) > 30) ? substr($comment, 0, 27) . '...' : $comment;

    $pdf->Cell(22, 7, $date, 1, 0, 'C');
    $pdf->Cell(22, 7, $in, 1, 0, 'C');
    $pdf->Cell(22, 7, $out, 1, 0, 'C');
    $pdf->Cell(20, 7, number_format($worked, 2), 1, 0, 'C');
    $pdf->Cell(20, 7, number_format($overtime, 2), 1, 0, 'C');
    $pdf->Cell(28, 7, 'SRD ' . number_format($regularPay, 2), 1, 0, 'R');
    $pdf->Cell(28, 7, 'SRD ' . number_format($overtimePay, 2), 1, 0, 'R');
    $pdf->Cell(36, 7, $commentShort, 1, 1, 'L');
}

// Summary
$pdf->Ln(6);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, 'Summary', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(60, 6, 'Total worked hours:', 0, 0);
$pdf->Cell(0, 6, number_format($totalHours, 2) . ' h', 0, 1);
$pdf->Cell(60, 6, 'Total overtime hours:', 0, 0);
$pdf->Cell(0, 6, number_format($totalOvertime, 2) . ' h', 0, 1);
$pdf->Cell(60, 6, 'Total Regular Pay:', 0, 0);
$pdf->Cell(0, 6, 'SRD ' . number_format($totalRegularPay, 2), 0, 1);
$pdf->Cell(60, 6, 'Total Overtime Pay:', 0, 0);
$pdf->Cell(0, 6, 'SRD ' . number_format($totalOvertimePay, 2), 0, 1);
$pdf->Cell(60, 6, 'Grand Total Pay:', 0, 0);
$pdf->Cell(0, 6, 'SRD ' . number_format($totalRegularPay + $totalOvertimePay, 2), 0, 1);
$pdf->Cell(60, 6, 'Working days (Mon-Sat):', 0, 0);
$pdf->Cell(0, 6, $totalWorkingDays . ' days', 0, 1);
$pdf->Cell(60, 6, 'Days worked (distinct):', 0, 0);
$pdf->Cell(0, 6, $daysWorked . ' days', 0, 1);

$pdf->Ln(8);
$pdf->SetFont('Arial', 'I', 9);
$pdf->Cell(0, 6, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1);

// Show PDF inline
$cleanName = preg_replace('/[^A-Za-z0-9_\-]/', '_', ($employee['fullname'] ?? 'employee'));
$filename = "iRoks_Timesheet_{$cleanName}_{$year}_{$month}.pdf";
$pdf->Output('I', $filename);
exit;
