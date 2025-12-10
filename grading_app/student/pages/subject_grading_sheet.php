<?php
require_once '../includes/init.php';
requireStudent();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
    exit;
}

$sectionId = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;
$sectionSubjectId = isset($_GET['section_subject_id']) ? (int)$_GET['section_subject_id'] : 0;

if ($sectionId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Missing section identifier.']);
    exit;
}

$userId = $_SESSION['user']['id'] ?? null;
if (!$userId) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'You must be signed in as a student to view grades.']);
    exit;
}

$studentStmt = $pdo->prepare('SELECT id FROM students WHERE user_id = ? LIMIT 1');
$studentStmt->execute([$userId]);
$studentId = (int)($studentStmt->fetchColumn() ?: 0);
if ($studentId <= 0) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'Student record not found.']);
    exit;
}

try {
    $breakdown = getStudentGradingSheetBreakdown($pdo, $studentId, $sectionId, $sectionSubjectId ?: null);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Unable to load grading sheet data.']);
    exit;
}

if (!$breakdown) {
    echo json_encode(['ok' => false, 'message' => 'No grading sheet for this subject has been submitted yet.']);
    exit;
}

echo json_encode([
    'ok' => true,
    'data' => $breakdown,
]);
