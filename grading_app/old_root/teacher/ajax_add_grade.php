<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/db.php';

function convertRawToGrade($raw) {
    if ($raw >= 97) return 1.00;
    if ($raw >= 94) return 1.25;
    if ($raw >= 91) return 1.50;
    if ($raw >= 88) return 1.75;
    if ($raw >= 85) return 2.00;
    if ($raw >= 82) return 2.25;
    if ($raw >= 79) return 2.50;
    if ($raw >= 76) return 2.75;
    if ($raw >= 75) return 3.00;
    return 5.00;
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$student_id = $_POST['student_id'] ?? '';
$subject_id = $_POST['subject_id'] ?? '';
$raw_grade = $_POST['raw_grade'] ?? '';
$comments = $_POST['comments'] ?? '';

if (!$student_id || !$subject_id || $raw_grade === '') {
    echo json_encode(['success' => false, 'error' => 'All fields are required.']);
    exit;
}

$grade = convertRawToGrade($raw_grade);

try {
    $stmt = $pdo->prepare("INSERT INTO grades (student_id, subject_id, teacher_id, raw_grade, grade, comments)
        VALUES (?, ?, NULL, ?, ?, ?) ON DUPLICATE KEY UPDATE raw_grade=?, grade=?, comments=?");
    $stmt->execute([$student_id, $subject_id, $raw_grade, $grade, $comments, $raw_grade, $grade, $comments]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} 