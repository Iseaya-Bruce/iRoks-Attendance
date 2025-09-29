<?php
// employee/clock.php
require_once __DIR__ . '/../includes/auth.php';
require_login('employee');
require_once __DIR__ . '/../includes/config.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// ensure logged in
$user_id = $_SESSION['user']['id'] ?? null;
if (!$user_id) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success'=>false, 'error'=>'Not logged in']);
        exit;
    }
    header('Location: ../login.php');
    exit;
}

try {
    // Handle POST (either explicit action or toggle)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // prefer explicit action if provided
        $action = $_POST['action'] ?? null;

        // Find today's latest attendance (if any)
        $stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? AND work_date = CURDATE() ORDER BY id DESC LIMIT 1");
        $stmt->execute([$user_id]);
        $today = $stmt->fetch(PDO::FETCH_ASSOC);

        // If no action provided, decide toggle based on whether an open record exists
        if (!$action) {
            if (!$today || $today['clockout_time'] !== null) {
                $action = 'clockin';
            } else {
                $action = 'clockout';
            }
        }

        if ($action === 'clockin') {
            // Insert a new row for today
            $ins = $pdo->prepare("INSERT INTO attendance (employee_id, work_date, clockin_time) VALUES (?, CURDATE(), NOW())");
            $ins->execute([$user_id]);

            // fetch row
            $lastId = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT * FROM attendance WHERE id = ?");
            $stmt->execute([$lastId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'action' => 'clockin',
                    'clockin_time' => $row['clockin_time'],
                    'message' => 'Clocked in'
                ]);
                exit;
            } else {
                header("Location: clock.php");
                exit;
            }
        }

        if ($action === 'clockout') {
            // Update the latest open shift for today
            $stmt = $pdo->prepare("SELECT id, clockin_time FROM attendance 
                                WHERE employee_id = ? AND work_date = CURDATE() 
                                AND clockout_time IS NULL ORDER BY id DESC LIMIT 1");
            $stmt->execute([$user_id]);
            $open = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($open) {
                // get employee overtime rules
                $empStmt = $pdo->prepare("SELECT expected_clockout, overtime_applicable 
                                        FROM employees WHERE id=?");
                $empStmt->execute([$user_id]);
                $emp = $empStmt->fetch(PDO::FETCH_ASSOC);

                $clockin = $open['clockin_time'];
                $clockout = date('Y-m-d H:i:s'); // now
                $worked_hours = null;
                $overtime_hours = 0;

                if (!empty($clockin)) {
                    $worked_hours = round((strtotime($clockout) - strtotime($clockin)) / 3600, 2);

                    // calculate overtime only if enabled
                    if ($emp && $emp['overtime_applicable']) {
                        if (!empty($emp['expected_clockout'])) {
                            $expectedOut = strtotime(date('Y-m-d') . ' ' . $emp['expected_clockout']);
                            $actualOut = strtotime($clockout);

                            if ($actualOut > $expectedOut) {
                                $overtime_hours = round(($actualOut - $expectedOut) / 3600, 2);
                            }
                        }
                    }
                }

                // update attendance row
                $upd = $pdo->prepare("UPDATE attendance 
                                    SET clockout_time = ?, overtime_hours=? 
                                    WHERE id = ?");
                $upd->execute([$clockout, $overtime_hours, $open['id']]);

                // fetch updated row
                $stmt = $pdo->prepare("SELECT * FROM attendance WHERE id = ?");
                $stmt->execute([$open['id']]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'action' => 'clockout',
                        'clockout_time' => $row['clockout_time'],
                        'worked_hours' => $worked_hours,
                        'overtime_hours' => $overtime_hours,
                        'message' => 'Clocked out'
                    ]);
                    exit;
                } else {
                    header("Location: clock.php");
                    exit;
                }
            } else {
                // nothing to clock out
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success'=>false,'error'=>'No open shift to clock out.']);
                    exit;
                } else {
                    header("Location: clock.php");
                    exit;
                }
            }
        }

        // unknown action
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success'=>false,'error'=>'Invalid action']);
            exit;
        } else {
            header("Location: clock.php");
            exit;
        }
    }

    // If GET: fetch today's attendance and render the page (original behavior)
    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id=? AND work_date=CURDATE() ORDER BY id DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $today = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
        exit;
    }
    // For normal page, show error
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Clock In / Out</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container">
    <h2>Clock In / Out</h2>

    <?php if (!$today): ?>
        <form method="post">
            <button type="submit" name="action" value="clockin" class="btn btn-success">Clock In</button>
        </form>
    <?php elseif ($today && $today['clockout_time'] === null): ?>
        <p>Clocked in at: <?= htmlspecialchars($today['clockin_time']) ?></p>
        <form method="post">
            <button type="submit" name="action" value="clockout" class="btn btn-danger">Clock Out</button>
        </form>
    <?php else: ?>
        <p>You worked today from <?= htmlspecialchars($today['clockin_time']) ?> to <?= htmlspecialchars($today['clockout_time']) ?></p>
    <?php endif; ?>

    <a href="dashboard.php">← Back to Dashboard</a>
</div>
</body>
</html>
