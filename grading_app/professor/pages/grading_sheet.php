<?php
require_once '../includes/init.php';
requireProfessor();

$professor = requireProfessorRecord($pdo);
$professorId = (int)$professor['id'];
$sheetId = isset($_GET['sheet_id']) ? (int)$_GET['sheet_id'] : 0;

$redirectWithMessage = function (string $message): void {
    set_flash('error', $message);
    header('Location: ./grading_sheets.php');
    exit;
};

if ($sheetId <= 0) {
    $firstSheetStmt = $pdo->prepare('SELECT id FROM grading_sheets WHERE professor_id = ? ORDER BY deadline_at IS NULL, deadline_at, id LIMIT 1');
    $firstSheetStmt->execute([$professorId]);
    $sheetId = (int)($firstSheetStmt->fetchColumn() ?: 0);
    if ($sheetId > 0) {
        header('Location: ./grading_sheet.php?sheet_id=' . $sheetId);
        exit;
    }

    $redirectWithMessage('No grading sheets have been assigned to you yet.');
}

$sheetStmt = $pdo->prepare(
    'SELECT gs.id,
            gs.status,
            gs.deadline_at,
            gs.submitted_at,
            gs.section_id,
            sec.section_name
       FROM grading_sheets gs
       JOIN sections sec ON sec.id = gs.section_id
      WHERE gs.id = ?
        AND gs.professor_id = ?
      LIMIT 1'
);
$sheetStmt->execute([$sheetId, $professorId]);
$sheet = $sheetStmt->fetch(PDO::FETCH_ASSOC);

if (!$sheet) {
    $redirectWithMessage('Selected grading sheet is not available.');
}

$sheetStatus = $sheet['status'] ?? 'draft';
$isEditable = in_array($sheetStatus, ['draft', 'reopened'], true);
$submittedAt = $sheet['submitted_at'] ? new DateTimeImmutable($sheet['submitted_at']) : null;

$sectionId = (int)$sheet['section_id'];

$componentsStmt = $pdo->prepare('SELECT id, name, weight FROM grade_components WHERE section_id = ? ORDER BY id');
$componentsStmt->execute([$sectionId]);
$components = $componentsStmt->fetchAll(PDO::FETCH_ASSOC);

$componentIds = array_column($components, 'id');
$items = [];
if ($componentIds) {
    $placeholders = implode(',', array_fill(0, count($componentIds), '?'));
    $itemStmt = $pdo->prepare("SELECT id, component_id, title, total_points FROM grade_items WHERE component_id IN ($placeholders) ORDER BY component_id, id");
    $itemStmt->execute($componentIds);
    while ($row = $itemStmt->fetch(PDO::FETCH_ASSOC)) {
        $items[$row['component_id']][] = $row;
    }
}
$hasGradeItems = false;
foreach ($items as $componentItems) {
    if (!empty($componentItems)) {
        $hasGradeItems = true;
        break;
    }
}

$studentsStmt = $pdo->prepare(
    'SELECT st.id,
            st.student_id,
            st.first_name,
            st.last_name
       FROM section_students ss
       JOIN students st ON st.id = ss.student_id
      WHERE ss.section_id = ?
   ORDER BY st.last_name, st.first_name'
);
$studentsStmt->execute([$sectionId]);
$students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

$studentIds = array_column($students, 'id');
$gradeMap = [];
if ($studentIds && $componentIds) {
    $gradeItemIds = [];
    foreach ($items as $rows) {
        foreach ($rows as $item) {
            $gradeItemIds[] = (int)$item['id'];
        }
    }
    if ($gradeItemIds) {
        $studentPlaceholders = implode(',', array_fill(0, count($studentIds), '?'));
        $itemPlaceholders = implode(',', array_fill(0, count($gradeItemIds), '?'));
        $gradeSql = "SELECT id, student_id, grade_item_id, score
                       FROM grades
                      WHERE student_id IN ($studentPlaceholders)
                        AND grade_item_id IN ($itemPlaceholders)";
        $gradeStmt = $pdo->prepare($gradeSql);
        $gradeStmt->execute([...$studentIds, ...$gradeItemIds]);
        while ($row = $gradeStmt->fetch(PDO::FETCH_ASSOC)) {
            $gradeMap[$row['student_id']][$row['grade_item_id']] = [
                'id' => (int)$row['id'],
                'score' => $row['score'],
            ];
        }
    }
}

$isLocked = !$isEditable;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isEditable) {
        set_flash('error', 'This grading sheet is already submitted or locked. Please request a reopen to make changes.');
        header('Location: ./grading_sheet.php?sheet_id=' . $sheetId);
        exit;
    }

    $gradesInput = $_POST['grades'] ?? [];
    $action = $_POST['action'] ?? 'save';
    $newStatus = $action === 'submit'
        ? 'submitted'
        : ($sheetStatus === 'reopened' ? 'reopened' : 'draft');

    $insertStmt = $pdo->prepare('INSERT INTO grades (grade_item_id, student_id, score) VALUES (?, ?, ?)');
    $updateStmt = $pdo->prepare('UPDATE grades SET score = ? WHERE id = ?');
    $deleteStmt = $pdo->prepare('DELETE FROM grades WHERE id = ?');

    $pdo->beginTransaction();
    try {
        foreach ($students as $student) {
            $studentId = (int)$student['id'];
            $studentGrades = $gradesInput[$studentId] ?? [];

            foreach ($items as $componentItems) {
                foreach ($componentItems as $item) {
                    $itemId = (int)$item['id'];
                    $scoreRaw = trim($studentGrades[$itemId] ?? '');
                    $existing = $gradeMap[$studentId][$itemId] ?? null;

                    if ($scoreRaw === '') {
                        if ($existing) {
                            $deleteStmt->execute([$existing['id']]);
                        }
                        continue;
                    }

                    if (!is_numeric($scoreRaw)) {
                        throw new RuntimeException('Scores must be numeric values.');
                    }

                    $score = (float)$scoreRaw;
                    if ($existing) {
                        $updateStmt->execute([$score, $existing['id']]);
                    } else {
                        $insertStmt->execute([$itemId, $studentId, $score]);
                    }
                }
            }
        }

        if ($newStatus === 'submitted') {
            $statusStmt = $pdo->prepare('UPDATE grading_sheets SET status = ?, submitted_at = CURRENT_TIMESTAMP WHERE id = ?');
            $statusStmt->execute([$newStatus, $sheetId]);
        } else {
            $statusStmt = $pdo->prepare('UPDATE grading_sheets SET status = ?, submitted_at = NULL WHERE id = ?');
            $statusStmt->execute([$newStatus, $sheetId]);
        }

        $pdo->commit();
        set_flash('success', $newStatus === 'submitted' ? 'Grading sheet submitted.' : 'Draft saved.');
    } catch (Throwable $e) {
        $pdo->rollBack();
        set_flash('error', 'Unable to save grades: ' . $e->getMessage());
    }

    header('Location: ./grading_sheet.php?sheet_id=' . $sheetId);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grading Sheet</title>
    <link rel="stylesheet" href="../assets/css/professor.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="content">
        <h1><?= htmlspecialchars($sheet['section_name']); ?> &mdash; Grading Sheet</h1>
        <?php show_flash(); ?>

        <?php if ($isLocked): ?>
            <div class="alert alert-warning">
                <?php if ($sheetStatus === 'submitted'): ?>
                    This grading sheet was submitted<?= $submittedAt ? ' on ' . htmlspecialchars($submittedAt->format('M j, Y g:i A')) : ''; ?> and can no longer be edited. Submit an edit request if you need changes.
                <?php else: ?>
                    This grading sheet is locked by the registrar. You can view grades but cannot make changes.
                <?php endif; ?>
            </div>
        <?php elseif ($sheetStatus === 'reopened'): ?>
            <div class="alert alert-info">This grading sheet has been reopened. Please make changes and resubmit.</div>
        <?php endif; ?>

        <?php if (empty($components) || empty($students) || !$hasGradeItems): ?>
            <p>No grading components, grade items, or students were found for this section.</p>
        <?php else: ?>
            <form method="post">
                <div class="table-responsive">
                    <table>
                        <thead>
                        <tr>
                            <th>Student</th>
                            <?php foreach ($components as $component): ?>
                                <?php foreach ($items[$component['id']] ?? [] as $item): ?>
                                    <th>
                                        <?= htmlspecialchars($component['name']); ?><br>
                                        <small><?= htmlspecialchars($item['title']); ?></small>
                                    </th>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($student['student_id']); ?></strong><br>
                                    <small><?= htmlspecialchars($student['last_name'] . ', ' . $student['first_name']); ?></small>
                                </td>
                                <?php foreach ($components as $component): ?>
                                    <?php foreach ($items[$component['id']] ?? [] as $item): ?>
                                        <?php
                                            $grade = $gradeMap[$student['id']][$item['id']]['score'] ?? '';
                                        ?>
                                        <td>
                                            <input
                                                type="number"
                                                name="grades[<?= (int)$student['id']; ?>][<?= (int)$item['id']; ?>]"
                                                value="<?= htmlspecialchars($grade); ?>"
                                                step="0.01"
                                                <?= $isEditable ? '' : 'readonly'; ?>
                                            >
                                        </td>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($isEditable): ?>
                    <div class="form-actions">
                        <button type="submit" name="action" value="save">Save Draft</button>
                        <button type="submit" name="action" value="submit">Submit</button>
                    </div>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </main>
</div>
<script src="../assets/js/professor.js"></script>
</body>
<?php include '../includes/footer.php'; ?>
</html>
