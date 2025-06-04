<?php
// db.php - Database connection file

$host = 'localhost';        // usually localhost
$dbname = 'grading_app';    // your database name
$username = 'root';         // your MySQL/MariaDB username
$password = '';             // your password, usually empty in XAMPP

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Set PDO error mode to exception for debugging
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>

