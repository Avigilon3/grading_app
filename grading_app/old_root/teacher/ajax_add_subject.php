<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$subject_name = trim($_POST['subject_name'] ?? '');
$subject_code = trim($_POST['subject_code'] ?? '');

if (!$subject_name || !$subject_code) {
    echo json_encode(['success' => false, 'error' => 'All fields are required.']);
    exit;
}

// Check for duplicate subject code
$stmt = $pdo->prepare("SELECT id FROM subjects WHERE code = ?");
$stmt->execute([$subject_code]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Subject code already exists.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO subjects (name, code) VALUES (?, ?)");
    $stmt->execute([$subject_name, $subject_code]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} 