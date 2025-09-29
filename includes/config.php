<?php
// includes/config.php
session_start();

// Set application timezone (adjust if your company uses a different local time)
date_default_timezone_set('America/Paramaribo');

$DB_HOST = '127.0.0.1';
$DB_NAME = 'iroks';
$DB_USER = 'root';
$DB_PASS = ''; // <- set your MySQL password

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    // Align MySQL session time zone with PHP time zone to keep NOW()/CURDATE() consistent
    try {
        $pdo->exec("SET time_zone = '" . date('P') . "'");
    } catch (Exception $e2) {
        // Ignore if not supported
    }
} catch (PDOException $e) {
    // In production, do not echo DB details
    die("Database connection failed: " . $e->getMessage());
}
?>
