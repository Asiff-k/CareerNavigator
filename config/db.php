<?php
// ===== Database Connection =====
$host = 'localhost';
$dbname = 'careernavigator';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Keep the details in the error log instead of showing them on the page
    error_log('CareerNavigator database connection failed: ' . $e->getMessage());
    die('Database connection failed. Please make sure MySQL is running in XAMPP.');
}
