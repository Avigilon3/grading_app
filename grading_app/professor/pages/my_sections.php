<?php
require_once '../includes/init.php';
requireProfessor();

$professor = requireProfessorRecord($pdo);
$professorId = (int)$professor['id'];

$termId = isset($_GET['term_id']) ? (int)$_GET['term_id'] : 0;
$search = trim($_GET['search'] ?? '');

$termsStmt = $pdo->query('SELECT id, term_name FROM terms ORDER BY start_date DESC, id DESC');
$terms = $termsStmt->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT ss.id,
               sec.section_name,
               sec.year_level,
               c.code AS course_code,
               c.title AS course_title,
               sub.subject_code,
               sub.subject_title,
               t.term_name
          FROM section_subjects ss
          JOIN sections sec ON sec.id = ss.section_id
     LEFT JOIN courses c ON c.id = sec.course_id
          JOIN subjects sub ON sub.id = ss.subject_id
     LEFT JOIN terms t ON t.id = ss.term_id
         WHERE ss.professor_id = :professor_id";
$params = [
    ':professor_id' => $professorId,
];

if ($termId > 0) {
    $sql .= ' AND ss.term_id = :term_id';
    $params[':term_id'] = $termId;
}

if ($search !== '') {
    $sql .= ' AND (sec.section_name LIKE :search OR sub.subject_code LIKE :search OR sub.subject_title LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

$sql .= ' ORDER BY sec.section_name, sub.subject_code';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Sections</title>
    <link rel="stylesheet" href="../assets/css/professor.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="content">
        <h1>My Sections</h1>

        <form method="get" class="filters">
            <label>
                <span>Term</span>
                <select name="term_id">
                    <option value="0">All terms</option>
                    <?php foreach ($terms as $term): ?>
                        <option value="<?= (int)$term['id']; ?>" <?= $termId === (int)$term['id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($term['term_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Search</span>
                <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" placeholder="Section or subject">
            </label>
            <button type="submit">Apply</button>
        </form>

        <?php if (empty($assignments)): ?>
            <p>No sections found for the selected filters.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                    <tr>
                        <th>Section</th>
                        <th>Subject</th>
                        <th>Course</th>
                        <th>Year</th>
                        <th>Term</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($assignments as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['section_name']); ?></td>
                            <td>
                                <strong><?= htmlspecialchars($row['subject_code']); ?></strong><br>
                                <small><?= htmlspecialchars($row['subject_title']); ?></small>
                            </td>
                            <td><?= htmlspecialchars($row['course_code'] ?? $row['course_title'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($row['year_level']); ?></td>
                            <td><?= htmlspecialchars($row['term_name'] ?? 'Not set'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</div>
<script src="../assets/js/professor.js"></script>
</body>
<?php include '../includes/footer.php'; ?>
</html>
