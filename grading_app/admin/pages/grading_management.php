<?php
require_once '../includes/init.php';
requireAdmin();

$err = $msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'set_deadline') {
            $id = (int)($_POST['id'] ?? 0);
            $deadlineAt = trim($_POST['deadline_at'] ?? '');
            if (!$id) {
                throw new Exception('Invalid grading sheet.');
            }
            $stmt = $pdo->prepare('UPDATE grading_sheets SET deadline_at = ? WHERE id = ?');
            $stmt->execute([$deadlineAt ?: null, $id]);
            add_activity_log($pdo, $_SESSION['user']['id'] ?? null, 'SET_DEADLINE', "Grading sheet #{$id} deadline updated.");
            $msg = 'Deadline updated.';
        }

        if ($action === 'change_status') {
            $id = (int)($_POST['id'] ?? 0);
            $status = $_POST['status'] ?? '';
            $allowed = ['draft', 'submitted', 'locked', 'reopened'];
            if (!$id || !in_array($status, $allowed, true)) {
                throw new Exception('Invalid status.');
            }
            $stmt = $pdo->prepare('UPDATE grading_sheets SET status = ? WHERE id = ?');
            $stmt->execute([$status, $id]);
            add_activity_log($pdo, $_SESSION['user']['id'] ?? null, 'CHANGE_STATUS', "Grading sheet #{$id} set to {$status}.");
            $msg = 'Status updated.';
        }
    } catch (Exception $e) {
        $err = $e->getMessage();
    }
}

$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$yearLevel = isset($_GET['year_level']) ? trim($_GET['year_level']) : '';
$sectionId = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;
$termId = isset($_GET['term_id']) ? (int)$_GET['term_id'] : 0;
$statusFilter = $_GET['status_filter'] ?? 'all';
$templateSectionId = isset($_GET['template_section_id']) ? (int)$_GET['template_section_id'] : 0;

$courses = $pdo->query('SELECT id, code, title FROM courses ORDER BY title')->fetchAll(PDO::FETCH_ASSOC);
$sections = $pdo->query('SELECT id, section_name, year_level FROM sections ORDER BY section_name')->fetchAll(PDO::FETCH_ASSOC);
$terms = $pdo->query('SELECT id, term_name FROM terms ORDER BY start_date DESC')->fetchAll(PDO::FETCH_ASSOC);

$filters = [];
$params = [];
if ($courseId) {
    $filters[] = 'sec.course_id = :course_id';
    $params[':course_id'] = $courseId;
}
if ($yearLevel !== '') {
    $filters[] = 'sec.year_level = :year_level';
    $params[':year_level'] = $yearLevel;
}
if ($sectionId) {
    $filters[] = 'sec.id = :section_id';
    $params[':section_id'] = $sectionId;
}
if ($termId) {
    $filters[] = 'sec.term_id = :term_id';
    $params[':term_id'] = $termId;
}
if ($statusFilter !== 'all') {
    $filters[] = 'gs.status = :status_filter';
    $params[':status_filter'] = $statusFilter;
}
$whereSql = $filters ? ('WHERE ' . implode(' AND ', $filters)) : '';

$statsStmt = $pdo->prepare(
    "SELECT COUNT(*) AS total,
            SUM(CASE WHEN gs.status IN ('submitted','locked') THEN 1 ELSE 0 END) AS submitted,
            SUM(CASE WHEN gs.status IN ('draft','reopened') THEN 1 ELSE 0 END) AS pending
       FROM grading_sheets gs
  LEFT JOIN sections sec ON sec.id = gs.section_id
       {$whereSql}"
);
$statsStmt->execute($params);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'submitted' => 0, 'pending' => 0];

$sheetsStmt = $pdo->prepare(
    "SELECT gs.id,
            gs.status,
            gs.deadline_at,
            gs.submitted_at,
            sec.section_name,
            sec.year_level,
            c.code AS course_code,
            sub.subject_code,
            sub.subject_title,
            CONCAT(COALESCE(prof.last_name,''), ', ', COALESCE(prof.first_name,'')) AS professor_name,
            t.term_name
       FROM grading_sheets gs
  LEFT JOIN sections sec ON sec.id = gs.section_id
  LEFT JOIN courses c ON c.id = sec.course_id
  LEFT JOIN terms t ON t.id = sec.term_id
  LEFT JOIN professors prof ON prof.id = gs.professor_id
  LEFT JOIN section_subjects ss ON ss.section_id = gs.section_id AND (ss.professor_id = gs.professor_id OR gs.professor_id IS NULL)
  LEFT JOIN subjects sub ON sub.id = ss.subject_id
       {$whereSql}
   ORDER BY sec.section_name, sub.subject_title"
);
$sheetsStmt->execute($params);
$gradingSheets = $sheetsStmt->fetchAll(PDO::FETCH_ASSOC);

$templateSections = $sections;
$templateSectionId = $templateSectionId ?: ($templateSections[0]['id'] ?? 0);
$templateComponents = [];
$templateItems = [];
$templateStudents = [];
if ($templateSectionId) {
    $componentStmt = $pdo->prepare('SELECT id, name, weight FROM grade_components WHERE section_id = ? ORDER BY id');
    $componentStmt->execute([$templateSectionId]);
    $templateComponents = $componentStmt->fetchAll(PDO::FETCH_ASSOC);

    if ($templateComponents) {
        $componentIds = array_column($templateComponents, 'id');
        if ($componentIds) {
            $placeholders = implode(',', array_fill(0, count($componentIds), '?'));
            $itemStmt = $pdo->prepare("SELECT id, component_id, title, total_points FROM grade_items WHERE component_id IN ({$placeholders}) ORDER BY component_id, id");
            $itemStmt->execute($componentIds);
            while ($row = $itemStmt->fetch(PDO::FETCH_ASSOC)) {
                $templateItems[$row['component_id']][] = $row;
            }
        }
    }

    $studentStmt = $pdo->prepare(
        'SELECT st.id, st.student_id, st.first_name, st.last_name
           FROM section_students ss
           JOIN students st ON st.id = ss.student_id
          WHERE ss.section_id = ?
       ORDER BY st.last_name, st.first_name
          LIMIT 5'
    );
    $studentStmt->execute([$templateSectionId]);
    $templateStudents = $studentStmt->fetchAll(PDO::FETCH_ASSOC);
}

$deadlineSheets = $gradingSheets;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Grading Sheets Management</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .tabs { display: flex; gap: 1rem; border-bottom: 1px solid #e4e7eb; margin-bottom: 1.5rem; }
        .tabs button { background: none; border: none; padding: 0.75rem 1rem; font-size: 1rem; cursor: pointer; border-bottom: 3px solid transparent; }
        .tabs button.active { border-bottom-color: #2f855a; color: #2f855a; font-weight: 600; }
        .filters { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem; margin-bottom: 1rem; }
        .stat-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { padding: 1rem; border-radius: 12px; background: #f8fafc; border: 1px solid #e4e7eb; }
        .table-like { width: 100%; border-collapse: collapse; }
        .table-like th, .table-like td { padding: 0.75rem; border-bottom: 1px solid #edf2f7; text-align: left; }
        .badge { padding: 0.2rem 0.5rem; border-radius: 999px; font-size: 0.85rem; }
        .badge.submitted { background: #e6fffa; color: #276749; }
        .badge.pending { background: #fffaf0; color: #b7791f; }
        .template-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .template-table th, .template-table td { border: 1px solid #e2e8f0; padding: 0.5rem; text-align: center; }
        .template-head { background: #f7fafc; font-weight: 600; }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="content">
        <?php show_flash(); ?>
        <?php if ($err): ?><div class="alert alert-error"><?= htmlspecialchars($err); ?></div><?php endif; ?>
        <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg); ?></div><?php endif; ?>

        <div class="page-header">
            <h1>Grading Sheets Management</h1>
            <p class="text-muted">Manage grading sheet templates, track submissions, and set deadlines</p>
        </div>

        <div class="tabs">
            <button class="tab-trigger active" data-tab="view">View Grading Sheets</button>
            <button class="tab-trigger" data-tab="template">Manage Template</button>
            <button class="tab-trigger" data-tab="deadlines">Submission Deadlines</button>
        </div>

        <section class="tab-pane active" id="tab-view">
            <form method="get" class="filters card" style="padding:1rem;">
                <div>
                    <label>Course</label>
                    <select name="course_id" class="form-control">
                        <option value="0">-- Select Course --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= (int)$c['id']; ?>" <?= $courseId === (int)$c['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($c['code'] . ' - ' . $c['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Year Level</label>
                    <select name="year_level" class="form-control">
                        <option value="">-- Select Year --</option>
                        <?php foreach (['1' => '1st', '2' => '2nd', '3' => '3rd', '4' => '4th'] as $val => $label): ?>
                            <option value="<?= $val; ?>" <?= $yearLevel === $val ? 'selected' : ''; ?>><?= $label; ?> Year</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Section</label>
                    <select name="section_id" class="form-control">
                        <option value="0">-- Select Section --</option>
                        <?php foreach ($sections as $sec): ?>
                            <option value="<?= (int)$sec['id']; ?>" <?= $sectionId === (int)$sec['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($sec['section_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Term/Semester</label>
                    <select name="term_id" class="form-control">
                        <option value="0">-- Select Term --</option>
                        <?php foreach ($terms as $t): ?>
                            <option value="<?= (int)$t['id']; ?>" <?= $termId === (int)$t['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($t['term_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Status</label>
                    <select name="status_filter" class="form-control">
                        <option value="all" <?= $statusFilter === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="submitted" <?= $statusFilter === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                        <option value="locked" <?= $statusFilter === 'locked' ? 'selected' : ''; ?>>Locked</option>
                        <option value="draft" <?= $statusFilter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="reopened" <?= $statusFilter === 'reopened' ? 'selected' : ''; ?>>Reopened</option>
                    </select>
                </div>
                <div style="align-self:end;">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </form>

            <div class="stat-cards">
                <div class="stat-card">
                    <div class="text-muted">Submitted</div>
                    <div style="font-size:1.5rem;font-weight:700;"><?= (int)$stats['submitted']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="text-muted">Pending</div>
                    <div style="font-size:1.5rem;font-weight:700;"><?= (int)$stats['pending']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="text-muted">Total Sheets</div>
                    <div style="font-size:1.5rem;font-weight:700;"><?= (int)$stats['total']; ?></div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h3>Grading Sheets</h3>
                    <table class="table-like">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Section</th>
                            <th>Subject</th>
                            <th>Professor</th>
                            <th>Deadline</th>
                            <th>Status</th>
                            <th>Submitted On</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$gradingSheets): ?>
                            <tr><td colspan="8">No grading sheets found.</td></tr>
                        <?php else: foreach ($gradingSheets as $row): ?>
                            <tr>
                                <td><?= (int)$row['id']; ?></td>
                                <td><?= htmlspecialchars($row['section_name'] ?? ''); ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($row['subject_code'] ?? 'N/A'); ?></strong><br>
                                    <small><?= htmlspecialchars($row['subject_title'] ?? ''); ?></small>
                                </td>
                                <td><?= htmlspecialchars(trim($row['professor_name'] ?? 'Unassigned')); ?></td>
                                <td><?= $row['deadline_at'] ? htmlspecialchars(date('Y-m-d', strtotime($row['deadline_at']))) : '—'; ?></td>
                                <td>
                                    <?php
                                    $status = $row['status'] ?? 'draft';
                                    $badgeClass = in_array($status, ['submitted','locked'], true) ? 'submitted' : 'pending';
                                    ?>
                                    <span class="badge <?= $badgeClass; ?>"><?= htmlspecialchars(ucfirst($status)); ?></span>
                                </td>
                                <td><?= $row['submitted_at'] ? htmlspecialchars(date('Y-m-d', strtotime($row['submitted_at']))) : '—'; ?></td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="action" value="change_status">
                                        <input type="hidden" name="id" value="<?= (int)$row['id']; ?>">
                                        <select name="status" class="form-control" style="width:120px;display:inline-block;">
                                            <?php foreach (['draft','submitted','locked','reopened'] as $st): ?>
                                                <option value="<?= $st; ?>" <?= $status === $st ? 'selected' : ''; ?>><?= ucfirst($st); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-link">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="tab-pane" id="tab-template">
            <form method="get" class="card" style="padding:1rem; margin-bottom:1rem;">
                <h3>Manage Template</h3>
                <div style="max-width:300px;">
                    <label>Select Section</label>
                    <select name="template_section_id" class="form-control" onchange="this.form.submit()">
                        <?php foreach ($templateSections as $sec): ?>
                            <option value="<?= (int)$sec['id']; ?>" <?= $templateSectionId === (int)$sec['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($sec['section_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <?php if (!$templateSectionId): ?>
                <p>No sections available.</p>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <h3>Default Grading Sheet</h3>
                        <?php if (!$templateComponents): ?>
                            <p>No grading template configured for this section.</p>
                        <?php else: ?>
                            <table class="template-table">
                                <thead>
                                <tr>
                                    <th class="template-head">Student ID</th>
                                    <th class="template-head">Student Name</th>
                                    <?php foreach ($templateComponents as $component): ?>
                                        <?php $items = $templateItems[$component['id']] ?? []; ?>
                                        <th class="template-head" colspan="<?= max(count($items), 1); ?>">
                                            <?= htmlspecialchars($component['name']); ?>
                                            <br><small><?= (float)$component['weight']; ?>%</small>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                                <tr>
                                    <th></th>
                                    <th></th>
                                    <?php foreach ($templateComponents as $component): ?>
                                        <?php $items = $templateItems[$component['id']] ?? []; ?>
                                        <?php if (!$items): ?>
                                            <th>Score</th>
                                        <?php else: foreach ($items as $item): ?>
                                            <th><?= htmlspecialchars($item['title']); ?><br><small><?= (float)$item['total_points']; ?> pts</small></th>
                                        <?php endforeach; endif; ?>
                                    <?php endforeach; ?>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (!$templateStudents): ?>
                                    <tr><td colspan="20">No students assigned yet.</td></tr>
                                <?php else: foreach ($templateStudents as $student): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($student['student_id']); ?></td>
                                        <td><?= htmlspecialchars($student['last_name'] . ', ' . $student['first_name']); ?></td>
                                        <?php foreach ($templateComponents as $component): ?>
                                            <?php $items = $templateItems[$component['id']] ?? []; ?>
                                            <?php if (!$items): ?>
                                                <td>—</td>
                                            <?php else: foreach ($items as $item): ?>
                                                <td>—</td>
                                            <?php endforeach; endif; ?>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                        <div class="alert alert-info" style="margin-top:1rem;">
                            Adjust grade components and items from the academic settings to update this template for all assigned professors.
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <section class="tab-pane" id="tab-deadlines">
            <div class="card">
                <div class="card-body">
                    <h3>Submission Deadlines</h3>
                    <table class="table-like">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Section</th>
                            <th>Professor</th>
                            <th>Deadline</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$deadlineSheets): ?>
                            <tr><td colspan="6">No grading sheets found.</td></tr>
                        <?php else: foreach ($deadlineSheets as $sheet): ?>
                            <tr>
                                <td><?= (int)$sheet['id']; ?></td>
                                <td><?= htmlspecialchars($sheet['section_name'] ?? ''); ?></td>
                                <td><?= htmlspecialchars($sheet['professor_name'] ?? 'Unassigned'); ?></td>
                                <td>
                                    <form method="post" class="form-inline">
                                        <input type="hidden" name="action" value="set_deadline">
                                        <input type="hidden" name="id" value="<?= (int)$sheet['id']; ?>">
                                        <input type="date" name="deadline_at" value="<?= $sheet['deadline_at'] ? htmlspecialchars(date('Y-m-d', strtotime($sheet['deadline_at']))) : ''; ?>" class="form-control">
                                        <button type="submit" class="btn btn-link">Save</button>
                                    </form>
                                </td>
                                <td><?= htmlspecialchars(ucfirst($sheet['status'] ?? 'draft')); ?></td>
                                <td>
                                    <form method="post" class="form-inline">
                                        <input type="hidden" name="action" value="change_status">
                                        <input type="hidden" name="id" value="<?= (int)$sheet['id']; ?>">
                                        <select name="status" class="form-control">
                                            <?php foreach (['draft','submitted','locked','reopened'] as $st): ?>
                                                <option value="<?= $st; ?>" <?= ($sheet['status'] ?? '') === $st ? 'selected' : ''; ?>><?= ucfirst($st); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-link">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</div>
<script>
    document.querySelectorAll('.tab-trigger').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-trigger').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
            btn.classList.add('active');
            const target = document.getElementById('tab-' + btn.dataset.tab);
            if (target) target.classList.add('active');
        });
    });
</script>
</body>
</html>
