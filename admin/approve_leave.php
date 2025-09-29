<?php
require_once '../includes/auth.php';
require_login('admin');
require_once '../includes/config.php';

// Handle approve/deny
if (isset($_GET['action'], $_GET['id'])) {
    $status = $_GET['action'] === 'approve' ? 'approved' : 'denied';
    $stmt = $pdo->prepare("UPDATE leave_requests SET status=? WHERE id=?");
    $stmt->execute([$status, $_GET['id']]);
}

// Fetch leave requests
$stmt = $pdo->query("SELECT lr.id, e.fullname, lr.start_date, lr.end_date, lr.reason, lr.status
                     FROM leave_requests lr
                     JOIN employees e ON lr.employee_id = e.id
                     ORDER BY lr.id DESC");
$requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Leave</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #111; color: #fff; font-family: Arial, sans-serif; }
        .container { max-width: 1000px; margin: 40px auto; background: #1a1a1a; padding: 20px; border-radius: 12px; }
        h1 { color: #32CD32; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; border-bottom: 1px solid #333; text-align: center; }
        th { background: #222; }
        tr:hover { background: #2a2a2a; }
        .btn {
            padding: 1px 1px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            margin: 0 0px;
        }
        .approve { background: #28a745; color: #fff; }
        .deny { background: #dc3545; color: #fff; }
        .pending { color: #ffc107; font-weight: bold; }
        .approved { color: #28a745; font-weight: bold; }
        .denied { color: #dc3545; font-weight: bold; }
       /* Wrapper to make table scrollable on mobile */
.table-wrapper {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch; /* smooth scrolling on iOS */
}

.table-wrapper table {
    min-width: 600px; /* prevent columns from being too cramped */
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .container {
        margin: 20px 10px;
        padding: 15px;
    }

    table th, table td {
        padding: 8px;
        font-size: 13px;
    }

    .btn {
        padding: 6px 10px;
        font-size: 13px;
        display: inline-block;
        margin: 3px 0;
    }

    h1 {
        font-size: 20px;
        text-align: center;
    }
}

/* Extra small devices */
@media (max-width: 480px) {
    .btn {
        display: block;       /* stack buttons vertically */
        width: 100%;          /* full width */
        text-align: center;
        margin-bottom: 6px;
    }

    table th, table td {
        font-size: 12px;
        padding: 6px;
    }

    h1 {
        font-size: 18px;
    }
}

    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="container">
        <h1>Approve Leave Requests</h1>
        <div class="table-wrapper">
            <table>
                <tr>
                    <th>Employee</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($requests as $req): ?>
                    <tr>
                        <td><?= htmlspecialchars($req['fullname']) ?></td>
                        <td><?= htmlspecialchars($req['start_date']) ?></td>
                        <td><?= htmlspecialchars($req['end_date']) ?></td>
                        <td><?= htmlspecialchars($req['reason']) ?></td>
                        <td class="<?= $req['status'] ?>"><?= ucfirst($req['status']) ?></td>
                        <td>
                            <?php if ($req['status'] === 'pending'): ?>
                                <a href="?action=approve&id=<?= $req['id'] ?>" class="btn approve">Approve</a>
                                <a href="?action=deny&id=<?= $req['id'] ?>" class="btn deny">Deny</a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>
