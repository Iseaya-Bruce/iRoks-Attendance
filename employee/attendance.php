<?php
// employee/attendance.php
require_once __DIR__ . '/../includes/auth.php';
require_login('employee');
require_once __DIR__ . '/../includes/config.php';

$user = $_SESSION['user'];

// Assuming you already have employee_id from session or URL
$employee_id = $_SESSION['employee_id'] ?? $user['id'];

if ($employee_id) {
    $stmt = $pdo->prepare("SELECT fullname, hourly_pay, overtime_applicable, expected_clockin, expected_clockout FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Month filter (from GET), default to current month
$selMonth = isset($_GET['month']) ? max(1, min(12, (int)$_GET['month'])) : (int)date('m');
$selYear  = isset($_GET['year'])  ? (int)$_GET['year'] : (int)date('Y');
$baseTs = strtotime(sprintf('%04d-%02d-01', $selYear, $selMonth));
$monthStart = date('Y-m-01', $baseTs);
$monthEnd   = date('Y-m-t', $baseTs);

$recStmt = $pdo->prepare("
    SELECT *,
           TIMESTAMPDIFF(MINUTE, clockin_time, clockout_time) AS minutes_worked
    FROM attendance
    WHERE employee_id = ?
      AND work_date BETWEEN ? AND ?
    ORDER BY work_date DESC
");
$recStmt->execute([$employee_id, $monthStart, $monthEnd]);
$records = $recStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate expected hours
$expectedHours = 8.0;
if (!empty($employee['expected_clockin']) && !empty($employee['expected_clockout'])) {
    $t1 = strtotime($employee['expected_clockin']);
    $t2 = strtotime($employee['expected_clockout']);
    if ($t2 > $t1) {
        $expectedHours = ($t2 - $t1) / 3600;
    } else {
        $expectedHours = (($t2 + 24*3600) - $t1) / 3600;
    }
}

// Calculate totals
$totalHours = 0;
$totalRegularPaidHours = 0;
$totalOvertime = 0;
$otApplicable = (int)($employee['overtime_applicable'] ?? 0);

foreach ($records as $r) {
    if ($r['clockin_time'] && $r['clockout_time']) {
        $hours = floor((strtotime($r['clockout_time']) - strtotime($r['clockin_time'])) / 3600);
        $totalHours += $hours;
        if ($otApplicable) {
            $regularPaid = min($expectedHours, $hours);
            $overtime = max(0, $hours - $expectedHours);
        } else {
            $regularPaid = $hours;
            $overtime = 0;
        }
        $totalRegularPaidHours += $regularPaid;
        $totalOvertime += $overtime;
    }
}

// Salary totals for selected month
$hourly = (float)($employee['hourly_pay'] ?? 0);
$ot_applicable = (int)($employee['overtime_applicable'] ?? 0);
$regularPayTotal = $totalRegularPaidHours * $hourly;
$overtimePayTotal = $ot_applicable ? $totalOvertime * $hourly * 1.5 : 0;
$totalPayTotal = $regularPayTotal + $overtimePayTotal;
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance - iRoks</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .container { 
            max-width: 1000px; 
            margin: 30px auto; 
            padding: 20px; 
            background: #1a1a1a; 
            border-radius: 12px; 
            border: 2px solid #00ff7f;
            box-shadow: 0 0 20px #00ff7f;
        }

        .summary-cards {
            display: flex;
            gap: 20px;
            margin: 20px 0;
            border: 2px solid #00ff7f;
            box-shadow: 0 0 20px #00ff7f;
        }
        .card {
            flex: 1;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            border: 2px solid #00ff7f;
            box-shadow: 0 0 20px #00ff7f;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table th, table td {
            padding: 10px;
            box-shadow: 0 0 15px rgba(0, 255, 100, 0.25); border: 1px solid rgba(0,255,100,0.2); transition: transform 0.3s ease, box-shadow 0.3s ease; text-align: center; animation: fadeInUp 0.7s ease;
        }
        table th {
            background: #333;
            color: #fff;
        }
        /* ✅ Responsive Design */
@media (max-width: 768px) {
    .container {
        margin: 15px;
        padding: 15px;
    }

    .profile {
        flex-direction: column;
        text-align: center;
    }

    .profile img {
        width: 70px;
        height: 70px;
    }

    .actions {
        flex-direction: column;
        gap: 10px;
    }

    .btn {
        width: 30px;
    }

    .btn, .btn-clock, button {
        width: 100%;
        text-align: center;
    }

    /* 🔹 Make tables scrollable */
    .table-wrapper {
        overflow-x: auto;
    }

    table {
        min-width: 600px; /* so it scrolls instead of shrinking too much */
    }

    table, th, td {
        font-size: 14px;
        padding: 8px;
    }
}

@media (max-width: 480px) {
    h1, h2 {
        font-size: 20px;
    }

    .container {
        padding: 10px;
    }

    .profile img {
        width: 60px;
        height: 60px;
    }

    table, th, td {
        font-size: 12px;
        padding: 6px;
    }
}
    /* Month scroll styles */
    .month-scroll { display:flex; gap:10px; overflow-x:auto; padding:8px 4px 2px; margin: 10px 0 18px; scrollbar-width: thin; scrollbar-color: #32CD32 #222; }
    .month-scroll::-webkit-scrollbar { height: 8px; }
    .month-scroll::-webkit-scrollbar-track { background: #222; border-radius: 999px; }
    .month-scroll::-webkit-scrollbar-thumb { background: #32CD32; border-radius: 999px; }
    .month-chip { flex:0 0 auto; padding:8px 12px; border-radius:999px; background: #222; color: #fff; border:1px solid rgba(255,255,255,0.1); text-decoration:none; font-weight:700; font-size:14px; box-shadow: 0 0 6px rgba(50,205,50,0.25) inset; }
    .month-chip:hover { background:#2a2a2a; box-shadow: 0 0 8px rgba(50,205,50,0.45) inset; }
    .month-chip.active { background:#32CD32; color:#111; box-shadow: 0 0 12px rgba(50,205,50,0.65); }
    .summary-cards { flex-direction: row; gap: 10px; }
    .salary-summary .card { background:#222; color:#fff; }
    </style>
</head>
<body class="dashboard employee">
<?php include '../includes/header.php'; ?>
<main class="container">
    <h1>Attendance History</h1>

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
            echo '<a class="month-chip' . $active . '" href="?month=' . $m . '&year=' . $y . '">' . $label . '</a>';
        }
    ?>
    </div>

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

    <div class="salary-summary summary-cards">
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
                    <th>Hours</th>
                    <th>Overtime Hours</th>
                    <th>Regular Pay (SRD)</th>
                    <th>Overtime Pay (SRD)</th>
                    <th>Total Pay (SRD)</th>
                    <th>Comment</th>
                </tr>
                <?php foreach ($records as $r):
                    $workedFloat = ($r['clockin_time'] && $r['clockout_time'])
                        ? floor((strtotime($r['clockout_time'])-strtotime($r['clockin_time']))/3600)
                        : 0;

                    if ($otApplicable) {
                        if ($workedFloat > $expectedHours) {
                            $overtime = floor($workedFloat - $expectedHours);
                        } else {
                            $overtime = 0;
                        }
                        $regularPaidHours = min($expectedHours, floor($workedFloat));
                    } else {
                        $overtime = 0;
                        $regularPaidHours = floor($workedFloat);
                    }

                    $hourly = (float)($employee['hourly_pay'] ?? 0);
                    $ot_applicable = (int)($employee['overtime_applicable'] ?? 0);

                    $regularPay = $regularPaidHours * $hourly;
                    $overtimePay = $ot_applicable ? $overtime * $hourly * 1.5 : 0;
                    $totalPay = $regularPay + $overtimePay;
                ?>
                    <tr>
                        <td><?=date("Y-m-d", strtotime($r['clockin_time']))?></td>
                        <td><?=date("H:i", strtotime($r['clockin_time']))?></td>
                        <td><?=$r['clockout_time'] ? date("H:i", strtotime($r['clockout_time'])) : "-" ?></td>
                        <td><?=$regularPaidHours?></td>
                        <td><?=$overtime?></td>
                        <td><?=number_format($regularPay,2)?></td>
                        <td><?=number_format($overtimePay,2)?></td>
                        <td><?=number_format($totalPay,2)?></td>
                        <td><?=htmlspecialchars($r['comment'])?></td>
                    </tr>
                <?php endforeach; ?>

                <!-- Totals Row -->
                <tr style="font-weight:bold;background:#222;color:#fff;">
                    <td colspan="5">Totals</td>
                    <td><?=number_format($totalRegularPaidHours * $employee['hourly_pay'], 2)?></td>
                    <td><?= $employee['overtime_applicable'] ? number_format($totalOvertime * $employee['hourly_pay'] * 1.5, 2) : '0.00' ?></td>
                    <td><?=number_format($totalPayTotal, 2)?></td>
                    <td>-</td>
                </tr>
            </table>
        </div>
    </section>

    <a href="../pdf/generate.php?month=<?=sprintf('%02d',$selMonth)?>&year=<?=$selYear?>" class="btn">
        View PDF Report
    </a>
</main>
</body>
</html>