<?php
require_once '../includes/auth.php';
require_login('employee');
require_once '../includes/config.php';

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
            max-width: 500px;
            margin: 60px auto;
            background: #1a1a1a;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0px 4px 15px rgba(0,0,0,0.4);
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
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="container">
        <h1>Request Leave</h1>

        <?php if (!empty($success)): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>

        <form method="post">
            <label>Start Date</label>
            <input type="date" name="start_date" required>

            <label>End Date</label>
            <input type="date" name="end_date" required>

            <label>Reason</label>
            <textarea name="reason" placeholder="Enter reason..."></textarea>

            <button type="submit">Submit Request</button>
        </form>
    </div>
</body>
</html>
