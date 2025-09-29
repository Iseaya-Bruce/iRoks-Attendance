<?php
ob_clean();
require_once __DIR__ . '/../includes/auth.php';
require_login('any');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/fpdf.php';

$user = $_SESSION['user'];

// Determine which employee to export
if ($user['type'] === 'employee') {
    $employeeId = (int)$user['id'];
} elseif ($user['type'] === 'admin' && isset($_POST['id'])) {
    $employeeId = (int)$_POST['id'];
} else {
    header("Location: ../login.php");
    exit;
}

// Employee info
$stmt = $pdo->prepare("SELECT id, fullname, role, hourly_pay, overtime_applicable FROM employees WHERE id=?");
$stmt->execute([$employeeId]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    die("Employee not found.");
}

// Attendance records (whole period or filter by month/year if needed)
$stmt = $pdo->prepare("
    SELECT work_date, clockin_time, clockout_time, overtime_hours, comment
    FROM attendance
    WHERE employee_id=?
    ORDER BY work_date ASC
");
$stmt->execute([$employeeId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Totals
$totalHours = 0;
$totalOvertime = 0;
$daysWorked = 0;

foreach ($rows as $r) {
    if (!empty($r['clockin_time']) && !empty($r['clockout_time'])) {
        $in = strtotime($r['clockin_time']);
        $out = strtotime($r['clockout_time']);
        if ($out > $in) {
            $hours = ($out - $in) / 3600; // ✅ seconds precision
            $totalHours += $hours;
            $daysWorked++;
        }
    }
    $totalOvertime += isset($r['overtime_hours']) ? (float)$r['overtime_hours'] : 0;
}

$hourly = (float)($employee['hourly_pay'] ?? 0);
$ot_applicable = (int)($employee['overtime_applicable'] ?? 0);

$regularPay = $totalHours * $hourly;
$overtimePay = $ot_applicable ? $totalOvertime * $hourly * 1.5 : 0;
$grandTotal = $regularPay + $overtimePay;

// PDF start
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
$pdf->Cell(0, 6, 'Period: ' . date('F Y'), 0, 1);
$pdf->Ln(4);

// Table header
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(30, 8, 'Date', 1, 0, 'C');
$pdf->Cell(35, 8, 'Clock In', 1, 0, 'C');
$pdf->Cell(35, 8, 'Clock Out', 1, 0, 'C');
$pdf->Cell(25, 8, 'Hours', 1, 0, 'C');
$pdf->Cell(25, 8, 'OT (h)', 1, 0, 'C');
$pdf->Cell(40, 8, 'Comment', 1, 1, 'C');

// Rows
$pdf->SetFont('Arial', '', 10);
foreach ($rows as $r) {
    $date = $r['work_date'] ?? '-';
    $in = !empty($r['clockin_time']) ? date('H:i', strtotime($r['clockin_time'])) : '-';
    $out = !empty($r['clockout_time']) ? date('H:i', strtotime($r['clockout_time'])) : '-';
    $hours = '-';
    if (!empty($r['clockin_time']) && !empty($r['clockout_time'])) {
        $inT = strtotime($r['clockin_time']);
        $outT = strtotime($r['clockout_time']);
        if ($outT > $inT) $hours = number_format(($outT - $inT) / 3600, 2);
        else $hours = '0.00';
    }
    $ot = isset($r['overtime_hours']) ? number_format((float)$r['overtime_hours'], 2) : '0.00';
    $comment = isset($r['comment']) ? preg_replace("/\s+/", " ", trim($r['comment'])) : '';
    $commentShort = (strlen($comment) > 60) ? substr($comment, 0, 57) . '...' : $comment;

    $pdf->Cell(30, 7, $date, 1, 0, 'C');
    $pdf->Cell(35, 7, $in, 1, 0, 'C');
    $pdf->Cell(35, 7, $out, 1, 0, 'C');
    $pdf->Cell(25, 7, (is_numeric($hours) ? $hours : $hours), 1, 0, 'C');
    $pdf->Cell(25, 7, $ot, 1, 0, 'C');
    $pdf->Cell(40, 7, $commentShort, 1, 1, 'L');
}

// ✅ Unified Summary
$pdf->Ln(6);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, 'Summary', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(60, 6, 'Total worked hours:', 0, 0);
$pdf->Cell(0, 6, number_format($totalHours, 2) . ' h', 0, 1);
$pdf->Cell(60, 6, 'Total overtime hours:', 0, 0);
$pdf->Cell(0, 6, number_format($totalOvertime, 2) . ' h', 0, 1);
$pdf->Cell(60, 6, 'Total Regular Pay:', 0, 0);
$pdf->Cell(0, 6, 'SRD' . number_format($regularPay, 2), 0, 1);
$pdf->Cell(60, 6, 'Total Overtime Pay:', 0, 0);
$pdf->Cell(0, 6, 'SRD' . number_format($overtimePay, 2), 0, 1);
$pdf->Cell(60, 6, 'Grand Total Pay:', 0, 0);
$pdf->Cell(0, 6, 'SRD' . number_format($grandTotal, 2), 0, 1);

$pdf->Ln(8);
$pdf->SetFont('Arial', 'I', 9);
$pdf->Cell(0, 6, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1);

// Preview instead of forcing download
$cleanName = preg_replace('/[^A-Za-z0-9_\-]/', '_', ($employee['fullname'] ?? 'employee'));
$filename = "iRoks_Timesheet_{$cleanName}_" . date('Y_m') . ".pdf";
$pdf->Output('D', $filename);
exit;
