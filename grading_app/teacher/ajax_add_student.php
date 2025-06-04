<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$student_number = trim($_POST['student_number'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = password_hash('password', PASSWORD_DEFAULT);

if (!$student_number || !$username || !$email) {
    echo json_encode(['success' => false, 'error' => 'All fields are required.']);
    exit;
}

// Check for duplicate username or email
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$stmt->execute([$username, $email]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Username or email already exists. Please use a different one or assign grades to the existing student.']);
    exit;
}

// Check for duplicate student number
$stmt = $pdo->prepare("SELECT id FROM students WHERE student_number = ?");
$stmt->execute([$student_number]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Student number already exists. Please use a different one or assign grades to the existing student.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, 'student', ?)");
    $stmt->execute([$username, $password, $email]);
    $userId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO students (user_id, student_number) VALUES (?, ?)");
    $stmt->execute([$userId, $student_number]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    // Show a friendly error message, not the raw SQL error
    echo json_encode(['success' => false, 'error' => 'An unexpected error occurred. Please try again or contact support.']);
} 