<?php
require_once '../includes/init.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add_default_component') {
            $name = trim((string)($_POST['name'] ?? ''));
            $weightRaw = trim((string)($_POST['weight'] ?? ''));
            if ($name === '') {
                throw new RuntimeException('Component name is required.');
            }
            if (strlen($name) > 100) {
                throw new RuntimeException('Component name must be 100 characters or fewer.');
            }
            if (!is_numeric($weightRaw)) {
                throw new RuntimeException('Weight must be a numeric value.');
            }
            $weight = (float)$weightRaw;
            if ($weight <= 0) {
                throw new RuntimeException('Weight must be greater than zero.');
            }

            ensureDefaultGradeTemplateStorage($pdo);
            $sortOrder = (int)$pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM default_grade_components')->fetchColumn();
            $insert = $pdo->prepare('INSERT INTO default_grade_components (name, weight, sort_order) VALUES (?, ?, ?)');
            $insert->execute([$name, $weight, $sortOrder]);
            set_flash('success', 'New grade component added to the default template.');
        } elseif ($action === 'update_default_component') {
            $componentId = (int)($_POST['component_id'] ?? 0);
            $name = trim((string)($_POST['name'] ?? ''));
            $weightRaw = trim((string)($_POST['weight'] ?? ''));
            if ($componentId <= 0) {
                throw new RuntimeException('Invalid grade component selected.');
            }
            if ($name === '') {
                throw new RuntimeException('Component name is required.');
            }
            if (strlen($name) > 100) {
                throw new RuntimeException('Component name must be 100 characters or fewer.');
            }
            if (!is_numeric($weightRaw)) {
                throw new RuntimeException('Weight must be a numeric value.');
            }
            $weight = (float)$weightRaw;
            if ($weight <= 0) {
                throw new RuntimeException('Weight must be greater than zero.');
            }

            $update = $pdo->prepare('UPDATE default_grade_components SET name = ?, weight = ? WHERE id = ?');
            $update->execute([$name, $weight, $componentId]);
            set_flash('success', 'Grade component updated.');
        } elseif ($action === 'delete_default_component') {
            $componentId = (int)($_POST['component_id'] ?? 0);
            if ($componentId <= 0) {
                throw new RuntimeException('Invalid grade component selected.');
            }
            $delete = $pdo->prepare('DELETE FROM default_grade_components WHERE id = ?');
            $delete->execute([$componentId]);
            set_flash('success', 'Grade component removed from the default template.');
        } elseif ($action === 'apply_default_template') {
            $template = defaultGradeComponentsTemplate($pdo);
            if (!$template) {
                throw new RuntimeException('No default template found.');
            }
            $total = 0.0;
            foreach ($template as $tempComponent) {
                $total += (float)$tempComponent['weight'];
            }
            if (abs($total - 100.0) > 0.01) {
                throw new RuntimeException('Total component weight must equal 100% before applying the template.');
            }

            $scope = $_POST['apply_scope'] ?? 'single';
            if ($scope === 'all') {
                $sheetIds = $pdo->query('SELECT id FROM grading_sheets')->fetchAll(PDO::FETCH_COLUMN);
                foreach ($sheetIds as $sheetId) {
                    syncDefaultGradeComponentsForSheet($pdo, (int)$sheetId, $template, true);
                }
                set_flash('success', 'Default template applied to all grading sheets.');
            } else {
                $targetSheetId = (int)($_POST['target_sheet_id'] ?? 0);
                if ($targetSheetId <= 0) {
                    throw new RuntimeException('Select a grading sheet to apply the template.');
                }
                syncDefaultGradeComponentsForSheet($pdo, $targetSheetId, $template, true);
                set_flash('success', 'Default template applied to the selected grading sheet.');
            }
        }
    } catch (Throwable $e) {
        set_flash('error', $e->getMessage());
    }

    $redirectQuery = $_SERVER['QUERY_STRING'] ?? '';
    $redirectUrl = './grading_management.php' . ($redirectQuery ? '?' . $redirectQuery : '');
    header('Location: ' . $redirectUrl);
    exit;
}

$defaultGradeTemplate = defaultGradeComponentsTemplate($pdo);
$defaultTemplateTotalWeight = 0.0;
foreach ($defaultGradeTemplate as $component) {
    $defaultTemplateTotalWeight += (float)($component['weight'] ?? 0);
}
$defaultTemplateTotalWeight = round($defaultTemplateTotalWeight, 2);
$defaultTemplateNeedsWarning = abs($defaultTemplateTotalWeight - 100.0) > 0.01;
$defaultWeightsDisplay = implode(', ', array_map(function ($component) {
    $weight = rtrim(rtrim(number_format((float)$component['weight'], 2), '0'), '.');
    return sprintf('%s: %s%%', $component['name'], $weight);
}, $defaultGradeTemplate));
$defaultTemplateStudents = [
    ['student_id' => '2025-0001', 'name' => 'Garcia, Ana'],
    ['student_id' => '2025-0002', 'name' => 'Santos, Mark'],
];

$err = $_GET['err'] ?? null;
$msg = $_GET['msg'] ?? null;

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

$applySheetsStmt = $pdo->query(
    "SELECT gs.id,
            sec.section_name,
            sub.subject_code,
            sub.subject_title
       FROM grading_sheets gs
  LEFT JOIN sections sec ON sec.id = gs.section_id
  LEFT JOIN section_subjects ss ON ss.id = gs.section_subject_id
  LEFT JOIN subjects sub ON sub.id = ss.subject_id
   ORDER BY sec.section_name, sub.subject_title"
);
$applyTemplateOptions = $applySheetsStmt->fetchAll(PDO::FETCH_ASSOC);

$templateSections = $sections;
$templateSectionId = $templateSectionId ?: ($templateSections[0]['id'] ?? 0);
$templateComponents = [];
$templateItems = [];
$templateStudents = [];
if ($templateSectionId) {
    $templateSheetStmt = $pdo->prepare('SELECT id FROM grading_sheets WHERE section_id = ? ORDER BY deadline_at IS NULL, deadline_at, id LIMIT 1');
    $templateSheetStmt->execute([$templateSectionId]);
    $templateSheetId = (int)($templateSheetStmt->fetchColumn() ?: 0);

    if ($templateSheetId) {
        $componentStmt = $pdo->prepare('SELECT id, name, weight FROM grade_components WHERE grading_sheet_id = ? ORDER BY id');
        $componentStmt->execute([$templateSheetId]);
        $templateComponents = $componentStmt->fetchAll(PDO::FETCH_ASSOC);
    }

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
$currentQuery = $_SERVER['QUERY_STRING'] ?? '';
$redirectUrl = '../pages/grading_management.php' . ($currentQuery ? '?' . $currentQuery : '');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Grading Sheets Management</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .default-template-editor {
            border: 1px solid #e1e6ef;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
            background: #fdfefe;
        }
        .default-template-editor .editor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .component-add-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 1px solid #c0cadb;
            background: #fff;
            font-size: 20px;
            cursor: pointer;
        }
        .component-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .component-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: flex-end;
            border: 1px solid #e6e9f0;
            border-radius: 10px;
            padding: 12px;
            background: #fff;
        }
        .component-row .form-group {
            flex: 1 1 220px;
            margin: 0;
        }
        .component-row .form-group label {
            font-size: 13px;
            color: #4d5565;
        }
        .component-row .form-group input {
            width: 100%;
        }
        .component-row .form-group.narrow {
            flex: 0 0 140px;
        }
        .component-row .row-actions {
            display: flex;
            gap: 8px;
        }
        .component-row .row-actions button {
            padding: 8px 14px;
            border-radius: 8px;
            border: 1px solid #ccd5e0;
            background: #f5f7fb;
            cursor: pointer;
        }
        .component-row .row-actions button.danger {
            border-color: #e88a8a;
            background: #ffecec;
            color: #b42318;
        }
        .component-row.add-row {
            border-style: dashed;
        }
        .apply-template-form {
            margin-top: 20px;
            border: 1px solid #e6e9f0;
            border-radius: 12px;
            padding: 16px;
            background: #fbfcff;
        }
        .apply-template-form .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 10px;
        }
        .apply-template-form .form-actions button {
            border-radius: 8px;
            padding: 10px 16px;
            border: 1px solid #ccd5e0;
            background: #fff;
            cursor: pointer;
        }
        .apply-template-form .form-actions button.danger {
            border-color: #f5b3b3;
            background: #ffe9e9;
            color: #91261f;
        }
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

        <div class="card" id="grading-tabs">
            <div class="card-header tabs">
                <button class="tab-link active" type="button" data-tab="view">View Grading Sheets</button>
                <button class="tab-link" type="button" data-tab="template">Manage Template</button>
                <button class="tab-link" type="button" data-tab="deadlines">Submission Deadlines</button>
            </div>
            <div class="card-body">
                <section class="tab-pane active" data-pane="view">
                    <form method="get" class="form-box filters-grid">
                        <div class="form-group">
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
                        <div class="form-group">
                            <label>Year Level</label>
                            <select name="year_level" class="form-control">
                                <option value="">-- Select Year --</option>
                                <?php foreach (['1' => '1st', '2' => '2nd', '3' => '3rd', '4' => '4th'] as $val => $label): ?>
                                    <option value="<?= $val; ?>" <?= $yearLevel === $val ? 'selected' : ''; ?>><?= $label; ?> Year</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
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
                        <div class="form-group">
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
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status_filter" class="form-control">
                                <option value="all" <?= $statusFilter === 'all' ? 'selected' : ''; ?>>All</option>
                                <option value="submitted" <?= $statusFilter === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                                <option value="locked" <?= $statusFilter === 'locked' ? 'selected' : ''; ?>>Locked</option>
                                <option value="draft" <?= $statusFilter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="reopened" <?= $statusFilter === 'reopened' ? 'selected' : ''; ?>>Reopened</option>
                            </select>
                        </div>
                        <div class="form-actions filter-actions">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                        </div>
                    </form>

                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-label">Submitted</div>
                            <div class="stat-value"><?= (int)$stats['submitted']; ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Pending</div>
                            <div class="stat-value"><?= (int)$stats['pending']; ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Total Sheets</div>
                            <div class="stat-value"><?= (int)$stats['total']; ?></div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="card-section-header">
                                <h3>Grading Sheets</h3>
                            </div>
                            <table class="table table-striped table-bordered">
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
                                        <td><?= htmlspecialchars($row['section_name'] ?? '--'); ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($row['subject_code'] ?? 'N/A'); ?></strong><br>
                                            <small><?= htmlspecialchars($row['subject_title'] ?? '--'); ?></small>
                                        </td>
                                        <td><?= htmlspecialchars(trim($row['professor_name'] ?? 'Unassigned')); ?></td>
                                        <td><?= $row['deadline_at'] ? htmlspecialchars(date('Y-m-d', strtotime($row['deadline_at']))) : '--'; ?></td>
                                        <td>
                                            <?php
                                            $status = $row['status'] ?? 'draft';
                                            $badgeClass = in_array($status, ['submitted', 'locked'], true) ? 'status-good' : 'status-warn';
                                            ?>
                                            <span class="status-badge <?= $badgeClass; ?>"><?= htmlspecialchars(ucfirst($status)); ?></span>
                                        </td>
                                        <td><?= $row['submitted_at'] ? htmlspecialchars(date('Y-m-d', strtotime($row['submitted_at']))) : '--'; ?></td>
                                        <td>
                                            <form method="post" class="inline-form" action="../includes/gradingsheet_process.php">
                                                <input type="hidden" name="action" value="change_status">
                                                <input type="hidden" name="id" value="<?= (int)$row['id']; ?>">
                                                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectUrl); ?>">
                                                <select name="status" class="form-control inline-select">
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

                <section class="tab-pane" data-pane="template">
                    <div class="card default-template-card">
                        <div class="card-body">
                            <h3>Default Grading Sheet Template</h3>
                            <p class="text-muted">Current weights: <?= htmlspecialchars($defaultWeightsDisplay); ?></p>
                            <div class="default-template-editor">
                                <div class="editor-header">
                                    <h4>Grade Components</h4>
                                    <button type="button" class="component-add-btn" data-toggle-default-component-form title="Add grade component">+</button>
                                </div>
                                <?php if ($defaultTemplateNeedsWarning): ?>
                                    <div class="alert alert-warning">
                                        Component weights currently total <?= htmlspecialchars(number_format($defaultTemplateTotalWeight, 2)); ?>%. Please adjust to reach exactly 100%.
                                    </div>
                                <?php endif; ?>
                                <div class="component-list">
                                    <?php foreach ($defaultGradeTemplate as $component): ?>
                                        <form method="post" class="component-row">
                                            <input type="hidden" name="component_id" value="<?= (int)$component['id']; ?>">
                                            <div class="form-group">
                                                <label>Component Name</label>
                                                <input type="text" name="name" value="<?= htmlspecialchars($component['name']); ?>" maxlength="100" required>
                                            </div>
                                            <div class="form-group narrow">
                                                <label>Weight (%)</label>
                                                <input type="number" name="weight" step="0.01" min="0.01" value="<?= htmlspecialchars(number_format((float)$component['weight'], 2, '.', '')); ?>" required>
                                            </div>
                                            <div class="row-actions">
                                                <button type="submit" name="action" value="update_default_component">Save</button>
                                                <button type="submit" name="action" value="delete_default_component" class="danger" onclick="return confirm('Remove <?= htmlspecialchars($component['name'], ENT_QUOTES); ?> from the default template?');">Delete</button>
                                            </div>
                                        </form>
                                    <?php endforeach; ?>
                                </div>
                                <form method="post" class="component-row add-row" data-default-component-add-form hidden>
                                    <input type="hidden" name="action" value="add_default_component">
                                    <div class="form-group">
                                        <label>Component Name</label>
                                        <input type="text" name="name" maxlength="100" placeholder="e.g. Projects" required>
                                    </div>
                                    <div class="form-group narrow">
                                        <label>Weight (%)</label>
                                        <input type="number" name="weight" min="0.01" step="0.01" placeholder="e.g. 10" required>
                                    </div>
                                    <div class="row-actions">
                                        <button type="submit">Add Component</button>
                                    </div>
                                </form>
                            </div>
                            <table class="table table-bordered template-table">
                                <thead>
                                <tr>
                                    <th class="template-head">Student ID</th>
                                    <th class="template-head">Student Name</th>
                                    <?php foreach ($defaultGradeTemplate as $component): ?>
                                        <th class="template-head">
                                            <?= htmlspecialchars($component['name']); ?>
                                            <br><small><?= rtrim(rtrim(number_format((float)$component['weight'], 2), '0'), '.'); ?>%</small>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                                <tr>
                                    <th></th>
                                    <th></th>
                                    <?php foreach ($defaultGradeTemplate as $component): ?>
                                        <th>Score</th>
                                    <?php endforeach; ?>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($defaultTemplateStudents as $student): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($student['student_id']); ?></td>
                                        <td><?= htmlspecialchars($student['name']); ?></td>
                                        <?php foreach ($defaultGradeTemplate as $component): ?>
                                            <td>--</td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                            <form method="post" class="apply-template-form">
                                <input type="hidden" name="action" value="apply_default_template">
                                <div class="form-group">
                                    <label>Apply template to specific grading sheet</label>
                                    <select name="target_sheet_id" class="form-control">
                                        <option value="0">-- Select grading sheet --</option>
                                        <?php foreach ($applyTemplateOptions as $sheet): ?>
                                            <option value="<?= (int)$sheet['id']; ?>">
                                                <?= htmlspecialchars(trim(($sheet['section_name'] ?? 'Unnamed Section') . ' - ' . ($sheet['subject_code'] ?? ($sheet['subject_title'] ?? 'Subject')))); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="alert alert-warning info-note">
                                    Applying the template will overwrite the grade components of the selected grading sheet. Use the button below to copy the template to all sheets if needed.
                                </div>
                                <div class="form-actions">
                                    <button type="submit" name="apply_scope" value="single">Apply to Selected Sheet</button>
                                    <button type="submit" name="apply_scope" value="all" class="danger" onclick="return confirm('Apply the default template to ALL grading sheets? This will replace their existing grade components.');">Apply to All Sheets</button>
                                </div>
                            </form>
                            <div class="alert alert-info info-note">
                                This template is automatically applied to every grading sheet that is created, ensuring the same Activity, Exam, Quizzes, and Attendance weights.
                            </div>
                        </div>
                    </div>
                    <div class="form-box form-box-inline">
                        <h3>Manage Template</h3>
                        <div class="form-group narrow">
                            <label>Select Section</label>
                            <select name="template_section_id" class="form-control" onchange="this.form.submit()" form="template-section-form">
                                <?php foreach ($templateSections as $sec): ?>
                                    <option value="<?= (int)$sec['id']; ?>" <?= $templateSectionId === (int)$sec['id'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($sec['section_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <form id="template-section-form" method="get"></form>
                    </div>

                    <?php if (!$templateSectionId): ?>
                        <p>No sections available.</p>
                    <?php else: ?>
                        <div class="card">
                            <div class="card-body">
                                <h3>Section Template Preview</h3>
                                <?php if (!$templateComponents): ?>
                                    <p>No grading template configured for this section.</p>
                                <?php else: ?>
                                    <table class="table table-bordered template-table">
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
                                                        <td>--</td>
                                                    <?php else: foreach ($items as $item): ?>
                                                        <td>--</td>
                                                    <?php endforeach; endif; ?>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                                <div class="alert alert-info info-note">
                                    Sections inherit the default template above. Adjust academic settings only if a section requires additional customization.
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="tab-pane" data-pane="deadlines">
                    <div class="card">
                        <div class="card-body">
                            <h3>Submission Deadlines</h3>
                            <table class="table table-striped table-bordered">
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
                                        <td><?= htmlspecialchars($sheet['section_name'] ?? '--'); ?></td>
                                        <td><?= htmlspecialchars($sheet['professor_name'] ?? 'Unassigned'); ?></td>
                                        <td>
                                            <form method="post" class="inline-form" action="../includes/gradingsheet_process.php">
                                                <input type="hidden" name="action" value="set_deadline">
                                                <input type="hidden" name="id" value="<?= (int)$sheet['id']; ?>">
                                                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectUrl); ?>">
                                                <input type="date" name="deadline_at" value="<?= $sheet['deadline_at'] ? htmlspecialchars(date('Y-m-d', strtotime($sheet['deadline_at']))) : ''; ?>" class="form-control inline-select">
                                                <button type="submit" class="btn btn-link">Save</button>
                                            </form>
                                        </td>
                                        <td><?= htmlspecialchars(ucfirst($sheet['status'] ?? 'draft')); ?></td>
                                        <td>
                                            <form method="post" class="inline-form" action="../includes/gradingsheet_process.php">
                                                <input type="hidden" name="action" value="change_status">
                                                <input type="hidden" name="id" value="<?= (int)$sheet['id']; ?>">
                                                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectUrl); ?>">
                                                <select name="status" class="form-control inline-select">
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
            </div>
        </div>
    </main>
</div>
<script>
    (function(){
        function activateTab(which) {
            document.querySelectorAll('#grading-tabs .tab-link').forEach(function(btn){
                btn.classList.toggle('active', btn.dataset.tab === which);
            });
            document.querySelectorAll('#grading-tabs .tab-pane').forEach(function(pane){
                pane.classList.toggle('active', pane.dataset.pane === which);
            });
        }

        function initDefaultComponentFormToggle() {
            var toggle = document.querySelector('[data-toggle-default-component-form]');
            var form = document.querySelector('[data-default-component-add-form]');
            if (!toggle || !form) return;
            toggle.addEventListener('click', function(){
                var hidden = form.hasAttribute('hidden');
                if (hidden) {
                    form.removeAttribute('hidden');
                    var input = form.querySelector('input[name="name"]');
                    if (input) {
                        input.focus();
                        input.select();
                    }
                } else {
                    form.setAttribute('hidden', 'hidden');
                }
            });
        }

        function init() {
            document.querySelectorAll('#grading-tabs .tab-link').forEach(function(btn){
                btn.addEventListener('click', function(){
                    activateTab(btn.dataset.tab);
                });
            });
            initDefaultComponentFormToggle();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
</script>
<script src="../assets/js/admin.js"></script>
</body>
</html>
