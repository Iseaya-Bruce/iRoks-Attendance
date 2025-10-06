<?php
require_once __DIR__ . '/../includes/auth.php';
require_login('admin');
require_once __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) $_POST['id'];

    try {
        $stmt = $pdo->prepare("UPDATE employees SET status='active' WHERE id=? AND status='pending'");
        $stmt->execute([$id]);
    } catch (Exception $e) {
        die("Error approving request: " . $e->getMessage());
    }
}

header("Location: dashboard.php");
exit;
