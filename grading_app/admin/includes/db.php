<?php
// Simple PDO connection. Adjust to match your current DB.
$DB_HOST = '127.0.0.1';
$DB_PORT = 3306;
$DB_NAME = 'grading_app';
$DB_USER = 'root';
$DB_PASS = '';

try {
  $pdo = new PDO(
    "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4",
    $DB_USER,
    $DB_PASS,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
  );
} catch (PDOException $e) {
  http_response_code(500);
  echo "Database connection error.";
  exit;
}
