<?php
require_once '../includes/init.php';
require_once '../includes/grading_sheet_process.php';
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

$componentsStmt = $pdo->prepare('SELECT id, name, weight FROM grade_components WHERE grading_sheet_id = ? ORDER BY id');
$componentsStmt->execute([$sheetId]);
$components = $componentsStmt->fetchAll(PDO::FETCH_ASSOC);
$componentStyles = [];
if ($components) {
    $palette = [
        ['bg' => '#FFE8B5', 'border' => '#F3D487', 'text' => '#7A4B00', 'muted' => '#FFF6DC'],
        ['bg' => '#FFD6D6', 'border' => '#F1AAAA', 'text' => '#8B1A1A', 'muted' => '#FFECEC'],
        ['bg' => '#E0D7FF', 'border' => '#C3B4FF', 'text' => '#412678', 'muted' => '#F0EDFF'],
        ['bg' => '#CFE9FF', 'border' => '#A1D4FF', 'text' => '#0D3A61', 'muted' => '#E7F4FF'],
        ['bg' => '#DDF5CF', 'border' => '#B7E5A0', 'text' => '#34541E', 'muted' => '#F3FCEB'],
    ];
    foreach ($components as $idx => $component) {
        $componentStyles[(int)$component['id']] = $palette[$idx % count($palette)];
    }
}

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
$componentTotals = [];
foreach ($components as $component) {
    $componentId = (int)$component['id'];
    $totalPoints = 0.0;
    foreach ($items[$componentId] ?? [] as $itemRow) {
        $totalPoints += (float)$itemRow['total_points'];
    }
    $componentTotals[$componentId] = $totalPoints;
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

$studentSummaries = [];
foreach ($students as $student) {
    $studentId = (int)$student['id'];
    $finalGradeTotal = 0.0;
    $weightAccumulated = 0.0;
    $finalGradeDisplayTotal = 0.0;
    $studentSummaries[$studentId] = [
        'components' => [],
        'final_grade' => null,
        'final_grade_display' => null,
        'equivalent' => null,
    ];

    foreach ($components as $component) {
        $componentId = (int)$component['id'];
        $componentWeight = (float)$component['weight'];
        $componentItems = $items[$componentId] ?? [];
        $earned = 0.0;
        foreach ($componentItems as $item) {
            $score = $gradeMap[$studentId][$item['id']]['score'] ?? null;
            if ($score !== null && $score !== '') {
                $earned += (float)$score;
            }
        }
        $possible = $componentTotals[$componentId] ?? 0.0;
        $percent = $possible > 0 ? ($earned / $possible) : null;
        $finalPercent = $percent !== null ? $percent * $componentWeight : null;
        $finalPercentDisplay = $finalPercent;
        if ($finalPercentDisplay === null && empty($componentItems)) {
            $finalPercentDisplay = (float)$componentWeight;
        }

        if ($finalPercent !== null) {
            $finalGradeTotal += $finalPercent;
            $weightAccumulated += $componentWeight;
        }
        if ($finalPercentDisplay !== null) {
            $finalGradeDisplayTotal += $finalPercentDisplay;
        }

        $studentSummaries[$studentId]['components'][$componentId] = [
            'earned' => $earned,
            'possible' => $possible,
            'final_percent' => $finalPercent,
            'final_percent_display' => $finalPercentDisplay,
        ];
    }

    if ($finalGradeDisplayTotal > 0) {
        $studentSummaries[$studentId]['final_grade_display'] = round($finalGradeDisplayTotal, 2);
    }

    if ($weightAccumulated > 0) {
        $finalGrade = round($finalGradeTotal, 2);
        $studentSummaries[$studentId]['final_grade'] = $finalGrade;
        $studentSummaries[$studentId]['equivalent'] = convertRawGradeToEquivalent($finalGrade);
    } elseif ($finalGradeDisplayTotal > 0) {
        $studentSummaries[$studentId]['final_grade'] = round($finalGradeDisplayTotal, 2);
        $studentSummaries[$studentId]['equivalent'] = convertRawGradeToEquivalent($studentSummaries[$studentId]['final_grade']);
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
<body data-sheet-id="<?= $sheetId; ?>" data-sheet-editable="<?= $isEditable ? '1' : '0'; ?>">
<?php include '../includes/header.php'; ?>
<div class="layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="content">
        <div class="content-header">
            <h1><?= htmlspecialchars($sheet['section_name']); ?> &mdash; Grading Sheet</h1>
            <?php if ($isEditable): ?>
                <div class="header-actions">
                    <a class="btn secondary" href="./grading_sheet_export.php?sheet_id=<?= $sheetId; ?>">Export</a>
                    <button type="submit" form="grading-sheet-form" name="action" value="save">Save Draft</button>
                    <button type="submit" form="grading-sheet-form" name="action" value="submit">Submit</button>
                </div>
            <?php else: ?>
                <div class="header-actions">
                    <a class="btn secondary" href="./grading_sheet_export.php?sheet_id=<?= $sheetId; ?>">Export</a>
                </div>
            <?php endif; ?>
        </div>
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

        <?php if (empty($components) || empty($students)): ?>
            <p>No grading components or students were found for this section.</p>
        <?php else: ?>
            <?php if (!$hasGradeItems): ?>
                <div class="alert alert-info">
                    No grade items have been configured for the current grading components yet. Add activities/exams for each component from the academic settings so professors can record scores.
                </div>
            <?php endif; ?>
            <form method="post" id="grading-sheet-form">
                <div class="table-responsive">
                    <table class="grading-sheet-table">
                        <thead>
                        <tr>
                            <th rowspan="2">Student ID</th>
                            <th rowspan="2">Student Name</th>
                            <?php foreach ($components as $component): ?>
                                <?php
                                    $componentId = (int)$component['id'];
                                    $componentItems = $items[$componentId] ?? [];
                                    $weightLabel = rtrim(rtrim(number_format((float)$component['weight'], 2), '0'), '.');
                                    $colspan = max(1, count($componentItems)) + 1;
                                    $palette = $componentStyles[$componentId] ?? null;
                                    $styleAttr = $palette
                                        ? 'style="background:' . htmlspecialchars($palette['bg']) . ';border-color:' . htmlspecialchars($palette['border']) . ';color:' . htmlspecialchars($palette['text']) . ';"'
                                        : '';
                                ?>
                                <th colspan="<?= $colspan; ?>" class="component-header" <?= $styleAttr; ?>>
                                    <span class="component-title"><?= htmlspecialchars($component['name']); ?></span>
                                    <span class="component-weight"><?= htmlspecialchars($weightLabel ?: '0'); ?>%</span>
                                    <?php if ($isEditable): ?>
                                        <button
                                            type="button"
                                            class="component-actions"
                                            title="Add grade item"
                                            data-add-grade-item
                                            data-component-id="<?= $componentId; ?>"
                                            data-component-name="<?= htmlspecialchars($component['name']); ?>"
                                        >+</button>
                                    <?php endif; ?>
                                </th>
                            <?php endforeach; ?>
                            <th rowspan="2">Final Grade</th>
                            <th rowspan="2">Equivalent</th>
                        </tr>
                        <tr>
                            <?php foreach ($components as $component): ?>
                                <?php
                                    $componentId = (int)$component['id'];
                                    $componentItems = $items[$componentId] ?? [];
                                ?>
                                <?php if ($componentItems): ?>
                                    <?php foreach ($componentItems as $item): ?>
                                        <th class="component-item-head">
                                            <span class="item-title"><?= htmlspecialchars($item['title']); ?></span>
                                            <small><?= htmlspecialchars(number_format((float)$item['total_points'], 2)); ?> pts</small>
                                            <?php if ($isEditable): ?>
                                                <div class="item-actions">
                                                    <button
                                                        type="button"
                                                        class="item-action edit"
                                                        title="Edit grade item"
                                                        data-edit-grade-item
                                                        data-grade-item-id="<?= (int)$item['id']; ?>"
                                                        data-component-id="<?= $componentId; ?>"
                                                        data-component-name="<?= htmlspecialchars($component['name']); ?>"
                                                        data-title="<?= htmlspecialchars($item['title']); ?>"
                                                        data-total-points="<?= htmlspecialchars(number_format((float)$item['total_points'], 2, '.', '')); ?>"
                                                    >&#9998;</button>
                                                    <button
                                                        type="button"
                                                        class="item-action delete"
                                                        title="Delete grade item"
                                                        data-delete-grade-item
                                                        data-grade-item-id="<?= (int)$item['id']; ?>"
                                                        data-component-name="<?= htmlspecialchars($component['name']); ?>"
                                                        data-title="<?= htmlspecialchars($item['title']); ?>"
                                                    >&times;</button>
                                                </div>
                                            <?php endif; ?>
                                        </th>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <th class="component-item-head">No Grade Items</th>
                                <?php endif; ?>
                                <?php
                                    $palette = $componentStyles[$componentId] ?? null;
                                    $styleAttr = $palette
                                        ? 'style="background:' . htmlspecialchars($palette['muted']) . ';border-color:' . htmlspecialchars($palette['border']) . ';color:' . htmlspecialchars($palette['text']) . ';"'
                                        : '';
                                ?>
                                <th class="component-final-head" <?= $styleAttr; ?>>Final %</th>
                            <?php endforeach; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($students as $student): ?>
                            <?php $summary = $studentSummaries[$student['id']] ?? null; ?>
                            <tr>
                                <td><?= htmlspecialchars($student['student_id']); ?></td>
                                <td><?= htmlspecialchars($student['last_name'] . ', ' . $student['first_name']); ?></td>
                                <?php foreach ($components as $component): ?>
                                    <?php
                                        $componentId = (int)$component['id'];
                                        $componentItems = $items[$componentId] ?? [];
                                    ?>
                                    <?php if ($componentItems): ?>
                                        <?php foreach ($componentItems as $item): ?>
                                            <?php
                                                $grade = $gradeMap[$student['id']][$item['id']]['score'] ?? '';
                                            ?>
                                            <td class="component-cell">
                                                <input
                                                    type="number"
                                                    name="grades[<?= (int)$student['id']; ?>][<?= (int)$item['id']; ?>]"
                                                    value="<?= htmlspecialchars($grade); ?>"
                                                    step="0.01"
                                                    <?= $isEditable ? '' : 'readonly'; ?>
                                                    class="grade-input"
                                                >
                                            </td>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <td class="component-cell muted">&mdash;</td>
                                    <?php endif; ?>
                                    <?php
                                        $componentDisplay = $summary['components'][$componentId]['final_percent_display'] ?? null;
                                        $palette = $componentStyles[$componentId] ?? null;
                                        $styleAttr = $palette
                                            ? 'style="background:' . htmlspecialchars($palette['muted']) . ';border-color:' . htmlspecialchars($palette['border']) . ';color:' . htmlspecialchars($palette['text']) . ';"'
                                            : '';
                                    ?>
                                    <td class="final-percent" <?= $styleAttr; ?>>
                                        <?= $componentDisplay !== null ? htmlspecialchars(number_format($componentDisplay, 2)) . '<span>%</span>' : '&mdash;'; ?>
                                    </td>
                                <?php endforeach; ?>
                                <?php
                                    $finalGradeValue = $summary['final_grade'] ?? null;
                                    $finalGradeDisplay = $summary['final_grade_display'] ?? $finalGradeValue;
                                    $equivalentValue = $summary['equivalent'] ?? null;
                                ?>
                                <td class="final-grade-column">
                                    <div class="grade-value">
                                        <?= $finalGradeDisplay !== null ? htmlspecialchars(number_format($finalGradeDisplay, 2)) : '&mdash;'; ?>
                                    </div>
                                </td>
                                <td class="equivalent-grade-column">
                                    <div class="grade-value">
                                        <?= $equivalentValue !== null ? htmlspecialchars($equivalentValue) : '&mdash;'; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </form>
            <div class="grading-info-card">
                <h3>Grading Information</h3>
                <ul>
                    <li>Use the plus button beside each component to add new grade items, then refine their details with the pencil icon.</li>
                    <li>Scores are automatically computed based on each component&apos;s weight.</li>
                    <li>Save your work frequently as a draft to avoid losing progress before the final submission.</li>
                    <li>After submitting or once the deadline passes, edits will require an official reopen request.</li>
                    <li>
                        Current component weights:
                        <?php
                            $weightBreakdown = array_map(function ($component) {
                                return htmlspecialchars($component['name']) . ': ' . rtrim(rtrim(number_format((float)$component['weight'], 2), '0'), '.') . '%';
                            }, $components);
                            echo $weightBreakdown ? implode(', ', $weightBreakdown) : 'Not configured';
                        ?>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </main>
</div>
    <div class="grade-item-modal" data-grade-item-modal hidden>
        <div class="grade-item-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="grade-item-modal-title">
            <div class="grade-item-modal__header">
                <div>
                    <h3 id="grade-item-modal-title">Edit Grade Item</h3>
                    <p class="grade-item-modal__component" data-grade-item-modal-component></p>
                </div>
                <button type="button" class="grade-item-modal__close" data-grade-item-close aria-label="Close grade item editor">&times;</button>
            </div>
            <form data-grade-item-form>
                <input type="hidden" name="grade_item_id">
                <div class="form-group">
                    <label for="grade-item-name">Grade Item Name</label>
                    <input type="text" id="grade-item-name" name="title" maxlength="100" required>
                </div>
                <div class="form-group">
                    <label for="grade-item-points">Total Points</label>
                    <input type="number" id="grade-item-points" name="total_points" min="0.01" step="0.01" required>
                </div>
                <div class="grade-item-modal__actions">
                    <button type="button" data-grade-item-cancel>Cancel</button>
                    <button type="submit" data-grade-item-submit>Save Changes</button>
                </div>
            </form>
        </div>
    </div>
<script src="../assets/js/professor.js"></script>

</body>
<?php include '../includes/footer.php'; ?>
</html>
