<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_POST['user_id'] ?? '';
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Missing user ID.']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log('DB error in ajax_delete_student.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error.']);
} 