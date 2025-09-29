<?php
require_once __DIR__ . '/../includes/auth.php';
require_login('admin');
require_once __DIR__ . '/../includes/config.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: dashboard.php");
    exit;
}

// Get employee
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id=?");
$stmt->execute([$id]);
$emp = $stmt->fetch();

if (!$emp) {
    die("Employee not found");
}

// Fetch attendance records
$stmt = $pdo->prepare("
    SELECT id, employee_id, DATE(clockin_time) AS work_date, clockin_time, clockout_time,
           TIMESTAMPDIFF(MINUTE, clockin_time, clockout_time) AS minutes_worked
    FROM attendance
    WHERE employee_id=?
    ORDER BY work_date DESC
");
$stmt->execute([$id]);
$attendance = $stmt->fetchAll();

// Calculate totals
$total_hours = 0;
$total_days = count($attendance);
foreach ($attendance as $row) {
    $total_hours += $row['minutes_worked'] / 60;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - iRoks</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #111; color: #fff; font-family: Arial, sans-serif; }
        .container { max-width: 900px; margin: 30px auto; padding: 20px; background: #1a1a1a; border-radius: 12px; }
        h1, h2 { color: #32CD32; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border-bottom: 1px solid #333; text-align: center; }
        th { background: #222; }
        tr:hover { background: #222; }
        .summary { margin-top: 20px; background: #222; padding: 15px; border-radius: 8px; }
        a.back { color: #32CD32; text-decoration: none; display: inline-block; margin-bottom: 15px; }
        .export { margin-top: 20px; display: inline-block; background: #32CD32; color: #111; padding: 10px 15px; border-radius: 6px; text-decoration: none; font-weight: bold; }
        .export:hover { background: #28a428; }
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
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
<div class="container">
    <a href="dashboard.php" class="back">← Back to Dashboard</a>
    <h1>Attendance - <?= htmlspecialchars($emp['fullname']) ?></h1>
    <h2>Overview</h2>

    <div class="summary">
        <p><strong>Total Days Worked:</strong> <?= $total_days ?></p>
        <p><strong>Total Hours Worked:</strong> <?= round($total_hours, 2) ?> h</p>
        <p><strong>Expected Clock-in:</strong> <?= $emp['expected_clockin'] ?></p>
        <p><strong>Expected Clock-out:</strong> <?= $emp['expected_clockout'] ?></p>
    </div>

    <h2>Daily Records</h2>
    <div class="table-wrapper">
        <table>
            <tr>
                <th>Date</th>
                <th>Clock-in</th>
                <th>Clock-out</th>
                <th>Worked Hours</th>
                <th>Comment</th>
            </tr>
            <?php foreach ($attendance as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['work_date']) ?></td>
                    <td><?= $row['clockin_time'] ? date('H:i', strtotime($row['clockin_time'])) : '-' ?></td>
                    <td><?= $row['clockout_time'] ? date('H:i', strtotime($row['clockout_time'])) : '-' ?></td>
                    <td><?= round($row['minutes_worked']/60, 2) ?> h</td>
                    <td><?= htmlspecialchars($row['comment'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <a href="../pdf/generate.php?id=<?= $emp['id'] ?>" class="export">👁️ View Timesheet</a>
    <a href="../pdf/export_timesheet.php?id=<?= $emp['id'] ?>" class="export">📄 Export as PDF</a>

</div>
</body>
</html>
