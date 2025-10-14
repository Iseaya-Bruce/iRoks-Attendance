<?php
require_once '../includes/auth.php';
require_login('employee');
require_once '../includes/config.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO leave_requests (employee_id, start_date, end_date, reason, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([
        $_SESSION['user']['id'],
        $_POST['start_date'],
        $_POST['end_date'],
        $_POST['reason']
    ]);
    $success = "✅ Leave request submitted successfully!";
}

// Fetch leave requests for this employee
$stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE employee_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user']['id']]);
$leave_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Leave</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: #111;
            color: #fff;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 600px;
            margin: 60px auto;
            background: #1a1a1a;
            padding: 25px;
            border-radius: 12px;
            border: 2px solid #00ff7f;
            box-shadow: 0 0 20px #00ff7f;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #32CD32;
        }
        label {
            display: block;
            margin-top: 12px;
            margin-bottom: 6px;
            font-weight: bold;
        }
        input, textarea, button {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: none;
            margin-bottom: 12px;
            font-size: 14px;
        }
        input, textarea {
            background: #222;
            color: #fff;
        }
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        button {
            background: #32CD32;
            color: #111;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover {
            background: #28a428;
        }
        .success {
            text-align: center;
            margin-bottom: 15px;
            padding: 10px;
            background: #28a745;
            color: #fff;
            border-radius: 6px;
        }
        .requests {
            margin-top: 30px;
        }
        .requests h2 {
            text-align: center;
            margin-bottom: 15px;
            color: #f1faee;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #222;
            border-radius: 8px;
            overflow: hidden;
        }
        table th, table td {
            padding: 10px;
            border-bottom: 1px solid #444;
            text-align: center;
        }
        table th {
            background: #333;
            color: #32CD32;
        }
        .status {
            font-weight: bold;
            padding: 6px 10px;
            border-radius: 6px;
        }
        .status.pending { background: #444; color: #f1faee; }
        .status.accepted { background: #28a745; color: #fff; }
        .status.declined { background: #e63946; color: #fff; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="container">
        <h1>Request Leave</h1>

        <?php if (!empty($success)): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>

        <!-- Leave Request Form -->
        <form method="post">
            <label>Start Date</label>
            <input type="date" name="start_date" required>

            <label>End Date</label>
            <input type="date" name="end_date" required>

            <label>Reason</label>
            <textarea name="reason" placeholder="Enter reason..."></textarea>

            <button type="submit">Submit Request</button>
        </form>

        <!-- Past Requests -->
        <?php if ($leave_requests): ?>
        <div class="requests">
            <h2>Your Leave Requests</h2>
            <table>
                <tr>
                    <th>Start</th>
                    <th>End</th>
                    <th>Reason</th>
                    <th>Status</th>
                </tr>
                <?php foreach ($leave_requests as $request): ?>
                <tr>
                    <td><?= htmlspecialchars($request['start_date']) ?></td>
                    <td><?= htmlspecialchars($request['end_date']) ?></td>
                    <td><?= htmlspecialchars($request['reason']) ?></td>
                    <td>
                        <span class="status <?= htmlspecialchars($request['status']) ?>">
                            <?= ucfirst($request['status']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
