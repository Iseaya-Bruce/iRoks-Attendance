<?php
require_once __DIR__ . '/../includes/auth.php';
require_login('employee'); // only employees should submit their own comments
require_once __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = $_SESSION['user']['id'];
    $comment = trim($_POST['comment'] ?? '');

    if (empty($comment)) {
        header("Location: attendance.php?error=empty_comment");
        exit;
    }

    // Find today's attendance record for this employee
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT id FROM attendance WHERE employee_id = ? AND work_date = ?");
    $stmt->execute([$employeeId, $today]);
    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($attendance) {
        // Update existing row
        $stmt = $pdo->prepare("UPDATE attendance SET comment = ? WHERE id = ?");
        $stmt->execute([$comment, $attendance['id']]);
    } else {
        // Insert a new row if no record exists yet
        $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, work_date, comment) VALUES (?, ?, ?)");
        $stmt->execute([$employeeId, $today, $comment]);
    }

    header("Location: attendance.php?success=comment_saved");
    exit;
}

header("Location: attendance.php?error=invalid_request");
exit;
