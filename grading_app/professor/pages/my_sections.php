<?php
require_once '../includes/init.php';
requireProfessor();

$professor = requireProfessorRecord($pdo);
$professorId = (int)$professor['id'];

$termId = isset($_GET['term_id']) ? (int)$_GET['term_id'] : 0;
$sectionFilterId = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;
$search = trim($_GET['search'] ?? '');

$termsStmt = $pdo->query('SELECT id, term_name FROM terms ORDER BY start_date DESC, id DESC');
$terms = $termsStmt->fetchAll(PDO::FETCH_ASSOC);

$sectionsStmt = $pdo->prepare(
    'SELECT DISTINCT sec.id, sec.section_name
       FROM section_subjects ss
       JOIN sections sec ON sec.id = ss.section_id
      WHERE ss.professor_id = ?
   ORDER BY sec.section_name'
);
$sectionsStmt->execute([$professorId]);
$sectionOptions = $sectionsStmt->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT ss.id AS section_subject_id,
               ss.section_id,
               ss.term_id,
               sec.section_name,
               sec.year_level,
               c.code AS course_code,
               c.title AS course_title,
               sub.subject_code,
               sub.subject_title,
               t.term_name,
               gs.id AS grading_sheet_id
          FROM section_subjects ss
          JOIN sections sec ON sec.id = ss.section_id
     LEFT JOIN courses c ON c.id = sec.course_id
          JOIN subjects sub ON sub.id = ss.subject_id
     LEFT JOIN terms t ON t.id = ss.term_id
     LEFT JOIN grading_sheets gs ON gs.section_subject_id = ss.id
         WHERE ss.professor_id = :professor_id";
$params = [
    ':professor_id' => $professorId,
];

if ($termId > 0) {
    $sql .= ' AND ss.term_id = :term_id';
    $params[':term_id'] = $termId;
}

if ($sectionFilterId > 0) {
    $sql .= ' AND ss.section_id = :section_id';
    $params[':section_id'] = $sectionFilterId;
}

if ($search !== '') {
    $sql .= ' AND (sec.section_name LIKE :search OR sub.subject_code LIKE :search OR sub.subject_title LIKE :search OR t.term_name LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

$sql .= ' ORDER BY sec.section_name, sub.subject_code';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$studentsBySection = [];
if ($assignments) {
    $sectionIds = array_values(array_unique(array_map(static function ($row) {
        return (int)$row['section_id'];
    }, $assignments)));

    if ($sectionIds) {
        $placeholders = implode(',', array_fill(0, count($sectionIds), '?'));
        $studentsSql = "SELECT ss.section_id,
                               st.student_id AS student_number,
                               st.first_name,
                               st.last_name
                          FROM section_students ss
                          JOIN students st ON st.id = ss.student_id
                         WHERE ss.section_id IN ($placeholders)
                      ORDER BY st.last_name, st.first_name";
        $studentsStmt = $pdo->prepare($studentsSql);
        $studentsStmt->execute($sectionIds);
        while ($row = $studentsStmt->fetch(PDO::FETCH_ASSOC)) {
            $sectionId = (int)$row['section_id'];
            $studentsBySection[$sectionId][] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Classes</title>
    <link rel="stylesheet" href="../assets/css/professor.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="content">
    <?php show_flash(); ?>

    <div class="page-header">
      <h1>My Classes</h1>
      <p>Manage and view all your assigned sections for this semester.</p>
    </div>

        <form method="get" class="classes-filters">
            <div class="filter-group">
                <label class="filter-field">
                    <span>Section</span>
                    <select name="section_id">
                        <option value="0">All sections</option>
                        <?php foreach ($sectionOptions as $option): ?>
                            <?php $optionId = (int)$option['id']; ?>
                            <option value="<?= $optionId; ?>" <?= $sectionFilterId === $optionId ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($option['section_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="filter-field">
                    <span>Term</span>
                    <select name="term_id">
                        <option value="0">All terms</option>
                        <?php foreach ($terms as $term): ?>
                            <?php $termValue = (int)$term['id']; ?>
                            <option value="<?= $termValue; ?>" <?= $termId === $termValue ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($term['term_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <div class="filter-group filter-group--actions">
                <label class="filter-field">
                    <span class="sr-only">Search classes</span>
                    <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" placeholder="Search classes...">
                </label>
                <button type="submit">Apply</button>
            </div>
        </form>

        <?php if (empty($assignments)): ?>
            <div class="empty-state-card">
                <p>No sections found for the selected filters.</p>
            </div>
        <?php else: ?>
            <div class="classes-grid">
                <?php foreach ($assignments as $assignment): ?>
                    <?php
                        $sectionId = (int)$assignment['section_id'];
                        $sectionSubjectId = (int)$assignment['section_subject_id'];
                        $students = $studentsBySection[$sectionId] ?? [];
                        $sheetId = isset($assignment['grading_sheet_id']) ? (int)$assignment['grading_sheet_id'] : 0;
                        $courseDisplay = $assignment['course_code'] ?: ($assignment['course_title'] ?? '');
                        $termDisplay = $assignment['term_name'] ?? 'Term not set';
                        $subjectCode = trim((string)($assignment['subject_code'] ?? ''));
                        $sectionLabel = trim((string)($assignment['section_name'] ?? ''));
                        $subjectTitle = trim((string)($assignment['subject_title'] ?? ''));
                        $metaParts = [];
                        if ($subjectTitle !== '') {
                            $metaParts[] = $subjectTitle;
                        }
                        if ($courseDisplay) {
                            $metaParts[] = $courseDisplay;
                        }
                        if ($termDisplay) {
                            $metaParts[] = $termDisplay;
                        }
                        if ($subjectCode && $sectionLabel) {
                            $cardTitle = $subjectCode . ' - ' . $sectionLabel;
                        } else {
                            $cardTitle = $subjectCode ?: $sectionLabel ?: 'Assigned Class';
                        }
                    ?>
                    <section class="class-card" data-section-id="<?= $sectionId; ?>" data-term-id="<?= (int)$assignment['term_id']; ?>">
                        <div class="class-card__header">
                            <div>
                                <p class="class-card__title"><?= htmlspecialchars($cardTitle); ?></p>
                                <?php if ($metaParts): ?>
                                    <p class="class-card__meta">
                                        <?php foreach ($metaParts as $metaIndex => $metaValue): ?>
                                            <?php if ($metaIndex > 0): ?>&middot; <?php endif; ?>
                                            <?= htmlspecialchars($metaValue); ?>
                                        <?php endforeach; ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <?php if ($sheetId): ?>
                                <a class="btn btn-go-sheet" href="./grading_sheet.php?sheet_id=<?= $sheetId; ?>">Go to Grading Sheet</a>
                            <?php else: ?>
                                <span class="badge badge-muted">No grading sheet</span>
                            <?php endif; ?>
                        </div>
                        <div class="class-card__search">
                            <?php $searchId = 'student-search-' . $sectionSubjectId; ?>
                            <label for="<?= $searchId; ?>" class="sr-only">Search students in <?= htmlspecialchars($cardTitle); ?></label>
                            <input
                                type="text"
                                id="<?= $searchId; ?>"
                                placeholder="Search students by ID or name..."
                                data-student-search-input="<?= $sectionSubjectId; ?>"
                            >
                        </div>
                        <div class="class-card__table">
                            <table data-students-table="<?= $sectionSubjectId; ?>">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if ($students): ?>
                                    <?php foreach ($students as $index => $student): ?>
                                        <?php
                                            $studentName = trim($student['last_name'] . ', ' . $student['first_name']);
                                        ?>
                                        <tr>
                                            <td><?= $index + 1; ?></td>
                                            <td><?= htmlspecialchars($student['student_number']); ?></td>
                                            <td><?= htmlspecialchars($studentName); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr class="empty-row">
                                        <td colspan="3">No enrolled students yet.</td>
                                    </tr>
                                <?php endif; ?>
                                <tr class="no-results-row" hidden>
                                    <td colspan="3">No students match that search.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>
<script src="../assets/js/professor.js"></script>
</body>
<?php include '../includes/footer.php'; ?>
</html>
