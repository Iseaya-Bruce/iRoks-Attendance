<?php
require_once __DIR__ . '/../includes/auth.php';
require_login('admin');
require_once __DIR__ . '/../includes/config.php';

// Fetch all employees (for admin selection)
$employees = [];
$empStmt = $pdo->query("SELECT id, fullname, hourly_pay, overtime_applicable FROM employees ORDER BY fullname ASC");
$employees = $empStmt->fetchAll(PDO::FETCH_ASSOC);

// Determine selected employee (from GET id or default to first)
$selectedId = isset($_GET['id']) ? (int)$_GET['id'] : null;
if (!$selectedId && !empty($employees)) {
    $selectedId = (int)$employees[0]['id'];
}

// If still no employee (empty list)
if (!$selectedId) {
    die('No employees found.');
}

// Load selected employee info
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id=?");
$stmt->execute([$selectedId]);
$emp = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$emp) {
    die("Employee not found");
}

// Month filter (like employee/attendance.php)
$selMonth = isset($_GET['month']) ? max(1, min(12, (int)$_GET['month'])) : (int)date('m');
$selYear  = isset($_GET['year'])  ? (int)$_GET['year'] : (int)date('Y');
$baseTs = strtotime(sprintf('%04d-%02d-01', $selYear, $selMonth));
$monthStart = date('Y-m-01', $baseTs);
$monthEnd   = date('Y-m-t', $baseTs);

// Fetch attendance records for selected month and employee
$recStmt = $pdo->prepare("
    SELECT *,
           TIMESTAMPDIFF(MINUTE, clockin_time, clockout_time) AS minutes_worked
    FROM attendance
    WHERE employee_id = ?
      AND work_date BETWEEN ? AND ?
    ORDER BY work_date DESC
");
$recStmt->execute([$selectedId, $monthStart, $monthEnd]);
$records = $recStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals (hours + overtime)
$totalHours = 0.0;
$totalOvertime = 0.0;
foreach ($records as $r) {
    if ($r['clockin_time'] && $r['clockout_time']) {
        $hours = (strtotime($r['clockout_time']) - strtotime($r['clockin_time'])) / 3600;
        $totalHours += $hours;
        $totalOvertime += (float)($r['overtime_hours'] ?? 0);
    }
}

$hourly = (float)($emp['hourly_pay'] ?? 0);
$ot_applicable = (int)($emp['overtime_applicable'] ?? 0);
$regularPayTotal = $totalHours * $hourly;
$overtimePayTotal = $ot_applicable ? $totalOvertime * $hourly * 1.5 : 0;
$totalPayTotal = $regularPayTotal + $overtimePayTotal;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - iRoks (Admin)</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #111; color: #fff; font-family: Arial, sans-serif; }
        .container { max-width: 1100px; margin: 30px auto; padding: 20px; background: #1a1a1a; border-radius: 12px; }
        h1, h2 { color: #32CD32; }
        a.back { color: #32CD32; text-decoration: none; display: inline-block; margin-bottom: 15px; }
        .summary { margin-top: 20px; background: #222; padding: 15px; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table th, table td { padding: 10px; box-shadow: 0 0 15px rgba(0, 255, 100, 0.25); border: 1px solid rgba(0,255,100,0.2); transition: transform 0.3s ease, box-shadow 0.3s ease; text-align: center; animation: fadeInUp 0.7s ease; }
        table th { background: #222; color: #fff; }
        .summary-cards { display: flex; gap: 20px; margin: 20px 0; }
        .card { flex: 1; box-shadow: 0 0 15px rgba(0, 255, 100, 0.25); border: 1px solid rgba(0,255,100,0.2); transition: transform 0.3s ease, box-shadow 0.3s ease; text-align: center; animation: fadeInUp 0.7s ease; }
        .export { margin-top: 20px; display: inline-block; background: #32CD32; color: #111; padding: 10px 15px; border-radius: 8px; text-decoration: none; font-weight: bold; }
        .export:hover { background: #28a428; }

        /* Scroll bars like month-scroll from employee/attendance */
        .selector-wrap { display: flex; flex-direction: column; gap: 8px; margin-bottom: 12px; }
        .selector-label { font-weight: 700; color: #cfcfcf; }
        .month-scroll { display:flex; gap:10px; overflow-x:auto; padding:8px 4px 2px; margin: 4px 0 6px; scrollbar-width: thin; scrollbar-color: #32CD32 #222; }
        .month-scroll::-webkit-scrollbar { height: 8px; }
        .month-scroll::-webkit-scrollbar-track { background: #222; border-radius: 999px; }
        .month-scroll::-webkit-scrollbar-thumb { background: #32CD32; border-radius: 999px; }
        .month-chip { flex:0 0 auto; padding:8px 12px; border-radius:999px; background:#222; color:#fff; border:1px solid rgba(255,255,255,0.1); text-decoration:none; font-weight:700; font-size:14px; box-shadow: 0 0 6px rgba(50,205,50,0.25) inset; white-space: nowrap; }
        .month-chip:hover { background:#2a2a2a; box-shadow: 0 0 8px rgba(50,205,50,0.45) inset; }
        .month-chip.active { background:#32CD32; color:#111; box-shadow: 0 0 12px rgba(50,205,50,0.65); }
        .emp-scroll { display:flex; gap:10px; overflow-x:auto; padding:8px 4px 2px; margin: 4px 0 6px; scrollbar-width: thin; scrollbar-color: #32CD32 #222; }
        .emp-scroll::-webkit-scrollbar { height: 8px; }
        .emp-scroll::-webkit-scrollbar-track { background: #222; border-radius: 999px; }
        .emp-scroll::-webkit-scrollbar-thumb { background: #32CD32; border-radius: 999px; }
        .emp-chip { flex:0 0 auto; padding:8px 12px; border-radius:999px; background:#222; color:#fff; border:1px solid rgba(255,255,255,0.1); text-decoration:none; font-weight:700; font-size:14px; box-shadow: 0 0 6px rgba(50,205,50,0.25) inset; white-space: nowrap; }
        .emp-chip:hover { background:#2a2a2a; box-shadow: 0 0 8px rgba(50,205,50,0.45) inset; }
        .emp-chip.active { background:#32CD32; color:#111; box-shadow: 0 0 12px rgba(50,205,50,0.65); }

        /* Responsive */
        @media (max-width: 768px) {
            .container { margin: 15px; padding: 15px; }
            .summary-cards { flex-direction: column; gap: 10px; }
            .table-wrapper { overflow-x: auto; }
            table { min-width: 700px; }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
<div class="container">
    <a href="dashboard.php" class="back">← Back to Dashboard</a>
    <h1>Attendance - <?= htmlspecialchars($emp['fullname']) ?></h1>

    <!-- Employee selector (scrollable chips) -->
    <div class="selector-wrap">
        <div class="selector-label">Employees</div>
        <div class="emp-scroll">
            <?php foreach ($employees as $e): $active = ((int)$e['id'] === (int)$selectedId) ? ' active' : ''; ?>
                <a class="emp-chip<?= $active ?>" href="?id=<?= (int)$e['id'] ?>&month=<?= sprintf('%02d',$selMonth) ?>&year=<?= $selYear ?>">
                    <?= htmlspecialchars($e['fullname']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Month selector (scrollable chips, last 24 months) -->
    <div class="selector-wrap">
        <div class="selector-label">Months</div>
        <div class="month-scroll">
            <?php
                $selectedKey = date('Y-m', $baseTs);
                $curTs = strtotime(date('Y-m-01'));
                for ($i = 0; $i < 24; $i++) {
                    $ts = strtotime("-{$i} month", $curTs);
                    $m = (int)date('m', $ts);
                    $y = (int)date('Y', $ts);
                    $key = date('Y-m', $ts);
                    $label = date('M Y', $ts);
                    $active = ($key === $selectedKey) ? ' active' : '';
                    echo '<a class="month-chip' . $active . '" href="?id=' . (int)$selectedId . '&month=' . $m . '&year=' . $y . '">' . $label . '</a>';
                }
            ?>
        </div>
    </div>

    <!-- Summary cards (hours, overtime, shifts) -->
    <div class="summary-cards">
        <div class="card">
            <h3>Total Hours</h3>
            <p><?=number_format($totalHours,2)?> hrs</p>
        </div>
        <div class="card">
            <h3>Overtime</h3>
            <p><?=number_format($totalOvertime,2)?> hrs</p>
        </div>
        <div class="card">
            <h3>Shifts Worked</h3>
            <p><?=count($records)?></p>
        </div>
    </div>

    <!-- Salary summary -->
    <div class="summary-cards">
        <div class="card">
            <h3>Regular Pay</h3>
            <p><?=number_format($regularPayTotal,2)?></p>
        </div>
        <div class="card">
            <h3>Overtime Pay</h3>
            <p><?=number_format($overtimePayTotal,2)?></p>
        </div>
        <div class="card">
            <h3>Total Salary</h3>
            <p><?=number_format($totalPayTotal,2)?></p>
        </div>
    </div>

    <section>
        <h2>Selected Month (<?=date('F Y', $baseTs)?>)</h2>
        <div class="table-wrapper">
            <table>
                <tr>
                    <th>Date</th>
                    <th>Clock In</th>
                    <th>Clock Out</th>
                    <th>Worked Hours</th>
                    <th>Overtime Hours</th>
                    <th>Regular Pay (SRD)</th>
                    <th>Overtime Pay (SRD)</th>
                    <th>Total Pay (SRD)</th>
                    <th>Comment</th>
                </tr>
                <?php foreach ($records as $r): 
                    $worked = ($r['clockin_time'] && $r['clockout_time'])
                        ? round((strtotime($r['clockout_time'])-strtotime($r['clockin_time']))/3600,2)
                        : 0;
                    $overtime = (float)($r['overtime_hours'] ?? 0);
                    $regularPay = $worked * $hourly;
                    $overtimePay = $ot_applicable ? $overtime * $hourly * 1.5 : 0;
                    $totalPay = $regularPay + $overtimePay;
                ?>
                    <tr>
                        <td><?=date("Y-m-d", strtotime($r['clockin_time']))?></td>
                        <td><?=date("H:i", strtotime($r['clockin_time']))?></td>
                        <td><?=$r['clockout_time'] ? date("H:i", strtotime($r['clockout_time'])) : "-" ?></td>
                        <td><?=$worked?></td>
                        <td><?=$overtime?></td>
                        <td><?=number_format($regularPay,2)?></td>
                        <td><?=number_format($overtimePay,2)?></td>
                        <td><?=number_format($totalPay,2)?></td>
                        <td><?=htmlspecialchars($r['comment'] ?? '')?></td>
                    </tr>
                <?php endforeach; ?>

                <tr style="font-weight:bold;background:#222;color:#fff;">
                    <td colspan="5">Totals</td>
                    <td><?=number_format($totalHours * $hourly, 2)?></td>
                    <td><?= $ot_applicable ? number_format($totalOvertime * $hourly * 1.5, 2) : '0.00' ?></td>
                    <td><?=number_format(($totalHours * $hourly) + ($ot_applicable ? $totalOvertime * $hourly * 1.5 : 0), 2)?></td>
                    <td>-</td>
                </tr>
            </table>
        </div>
    </section>

    <!-- PDF/Timesheet links (note: export_timesheet currently ignores month filter) -->
    <div style="margin-top:12px; display:flex; gap:10px; flex-wrap: wrap;">
        <a href="../pdf/generate.php?id=<?= (int)$selectedId ?>" class="export">👁️ View Timesheet</a>
        <!--form method="post" action="../pdf/export_timesheet.php" style="display:inline;">
            <input type="hidden" name="id" value="<?= (int)$selectedId ?>">
            <button type="submit" class="export">📄 Export as PDF</button-->
        </form>
    </div>

</div>
</body>
</html>
