<?php

require_once 'init.php';

header('Content-Type: application/json');

try {
    requireProfessor();
    $professor = requireProfessorRecord($pdo);
    $professorId = (int)($professor['id'] ?? 0);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(405, ['success' => false, 'message' => 'Method not allowed.']);
    }

    $action = $_POST['action'] ?? '';
    if (!in_array($action, ['create', 'update', 'delete'], true)) {
        throw new RuntimeException('Unsupported action.');
    }

    switch ($action) {
        case 'create':
            handleCreateGradeItem($pdo, $professorId);
            break;
        case 'update':
            handleUpdateGradeItem($pdo, $professorId);
            break;
        case 'delete':
            handleDeleteGradeItem($pdo, $professorId);
            break;
    }
} catch (RuntimeException $e) {
    jsonResponse(400, ['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    jsonResponse(500, ['success' => false, 'message' => 'Unexpected server error.']);
}

function handleCreateGradeItem(PDO $pdo, int $professorId): void
{
    $componentId = isset($_POST['component_id']) ? (int)$_POST['component_id'] : 0;
    if ($componentId <= 0) {
        throw new RuntimeException('Invalid grade component selected.');
    }

    $component = fetchComponent($pdo, $componentId, $professorId);
    ensureSheetEditable($component['status'] ?? 'draft');

    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM grade_items WHERE component_id = ?');
    $countStmt->execute([$componentId]);
    $nextNumber = (int)$countStmt->fetchColumn() + 1;

    $baseName = trim($component['name'] ?? '');
    if ($baseName === '') {
        $baseName = 'Grade Item';
    }
    $defaultTitle = trim($baseName . ' ' . $nextNumber);

    $defaultPoints = 10;
    $pdo->beginTransaction();
    try {
        $insertStmt = $pdo->prepare('INSERT INTO grade_items (component_id, title, total_points) VALUES (?, ?, ?)');
        $insertStmt->execute([$componentId, $defaultTitle, $defaultPoints]);
        $newItemId = (int)$pdo->lastInsertId();

        seedDefaultStudentScores(
            $pdo,
            (int)($component['section_id'] ?? 0),
            $newItemId,
            $defaultPoints
        );

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    jsonResponse(200, [
        'success' => true,
        'message' => 'Grade item added.',
        'item' => [
            'id' => isset($newItemId) ? $newItemId : (int)$pdo->lastInsertId(),
            'title' => $defaultTitle,
            'total_points' => $defaultPoints,
            'component_id' => $componentId,
        ],
    ]);
}

function handleUpdateGradeItem(PDO $pdo, int $professorId): void
{
    $gradeItemId = isset($_POST['grade_item_id']) ? (int)$_POST['grade_item_id'] : 0;
    if ($gradeItemId <= 0) {
        throw new RuntimeException('Invalid grade item selected.');
    }

    $item = fetchGradeItem($pdo, $gradeItemId, $professorId);
    ensureSheetEditable($item['status'] ?? 'draft');

    $title = trim((string)($_POST['title'] ?? ''));
    if ($title === '') {
        throw new RuntimeException('Grade item name is required.');
    }

    $length = function_exists('mb_strlen') ? mb_strlen($title) : strlen($title);
    if ($length > 100) {
        throw new RuntimeException('Grade item name must be 100 characters or fewer.');
    }

    $pointsRaw = trim((string)($_POST['total_points'] ?? ''));
    if (!is_numeric($pointsRaw)) {
        throw new RuntimeException('Total points must be a numeric value.');
    }
    $totalPoints = (int)round((float)$pointsRaw);
    if ($totalPoints <= 0) {
        throw new RuntimeException('Total points must be greater than zero.');
    }

    $updateStmt = $pdo->prepare('UPDATE grade_items SET title = ?, total_points = ? WHERE id = ?');
    $updateStmt->execute([$title, $totalPoints, $gradeItemId]);

    jsonResponse(200, [
        'success' => true,
        'message' => 'Grade item updated.',
    ]);
}

function handleDeleteGradeItem(PDO $pdo, int $professorId): void
{
    $gradeItemId = isset($_POST['grade_item_id']) ? (int)$_POST['grade_item_id'] : 0;
    if ($gradeItemId <= 0) {
        throw new RuntimeException('Invalid grade item selected.');
    }

    $item = fetchGradeItem($pdo, $gradeItemId, $professorId);
    ensureSheetEditable($item['status'] ?? 'draft');

    $deleteStmt = $pdo->prepare('DELETE FROM grade_items WHERE id = ?');
    $deleteStmt->execute([$gradeItemId]);

    jsonResponse(200, [
        'success' => true,
        'message' => 'Grade item deleted.',
    ]);
}

function fetchComponent(PDO $pdo, int $componentId, int $professorId): array
{
    $stmt = $pdo->prepare(
        'SELECT gc.id,
                gc.name,
                gc.grading_sheet_id,
                gs.status,
                gs.professor_id,
                gs.section_id
           FROM grade_components gc
           JOIN grading_sheets gs ON gs.id = gc.grading_sheet_id
          WHERE gc.id = ?
          LIMIT 1'
    );
    $stmt->execute([$componentId]);
    $component = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$component || (int)$component['professor_id'] !== $professorId) {
        throw new RuntimeException('Grade component is not available.');
    }

    return $component;
}

function fetchGradeItem(PDO $pdo, int $gradeItemId, int $professorId): array
{
    $stmt = $pdo->prepare(
        'SELECT gi.id,
                gi.title,
                gi.total_points,
                gc.name AS component_name,
                gs.status,
                gs.professor_id
           FROM grade_items gi
           JOIN grade_components gc ON gc.id = gi.component_id
           JOIN grading_sheets gs ON gs.id = gc.grading_sheet_id
          WHERE gi.id = ?
          LIMIT 1'
    );
    $stmt->execute([$gradeItemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$item || (int)$item['professor_id'] !== $professorId) {
        throw new RuntimeException('Grade item is not available.');
    }

    return $item;
}

function ensureSheetEditable(string $status): void
{
    if (!in_array($status, ['draft', 'reopened'], true)) {
        throw new RuntimeException('This grading sheet is locked and cannot be modified.');
    }
}

function jsonResponse(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function seedDefaultStudentScores(PDO $pdo, int $sectionId, int $gradeItemId, float $defaultScore): void
{
    if ($sectionId <= 0 || $gradeItemId <= 0) {
        return;
    }

    $studentStmt = $pdo->prepare(
        'SELECT st.id
           FROM section_students ss
           JOIN students st ON st.id = ss.student_id
          WHERE ss.section_id = ?'
    );
    $studentStmt->execute([$sectionId]);
    $students = $studentStmt->fetchAll(PDO::FETCH_COLUMN);
    if (!$students) {
        return;
    }

    $insertStmt = $pdo->prepare('INSERT INTO grades (grade_item_id, student_id, score) VALUES (?, ?, ?)');
    foreach ($students as $studentId) {
        $insertStmt->execute([$gradeItemId, (int)$studentId, $defaultScore]);
    }
}
