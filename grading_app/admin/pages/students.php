
<?php
require_once '../includes/init.php';
requireAdmin();


// add later siguro wag na? check if user is MIS or Registrar
// if (!is_mis($_SESSION['admin']['role'])) { die('Unauthorized'); }

$statusFilter = $_GET['status_filter'] ?? 'active';
$yearFilter = $_GET['year_level_filter'] ?? 'all';
$sectionFilter = $_GET['section_filter'] ?? 'all';
$validStatusFilters = ['active', 'inactive', 'all'];
$validYearFilters = ['all', '1', '2', '3', '4'];
if (!in_array($statusFilter, $validStatusFilters, true)) {
    $statusFilter = 'active';
}
if (!in_array($yearFilter, $validYearFilters, true)) {
    $yearFilter = 'all';
}

$sectionStmt = $pdo->query("SELECT s.id, s.section_name, s.term_id, t.school_year
                              FROM sections s
                         LEFT JOIN terms t ON s.term_id = t.id
                             WHERE s.is_active = 1
                          ORDER BY s.section_name");
$sections = $sectionStmt->fetchAll(PDO::FETCH_ASSOC);

$sectionOptions = [];
foreach ($sections as $section) {
    $sectionName = trim($section['section_name'] ?? '');
    if ($sectionName === '') {
        continue;
    }
    $label = $sectionName;
    $schoolYear = trim($section['school_year'] ?? '');
    if ($schoolYear !== '') {
        $label .= ' - ' . $schoolYear;
    }
    $sectionOptions[] = [
        'value' => $sectionName,
        'label' => $label
    ];
}
if ($sectionFilter !== 'all') {
    $validSections = array_column($sectionOptions, 'value');
    if (!in_array($sectionFilter, $validSections, true)) {
        $sectionFilter = 'all';
    }
}

$studentFilters = [];
$studentParams = [];
if ($statusFilter === 'active') {
    $studentFilters[] = "status <> 'Inactive'";
} elseif ($statusFilter === 'inactive') {
    $studentFilters[] = "status = 'Inactive'";
}
if ($yearFilter !== 'all') {
    $studentFilters[] = "year_level = :year_level";
    $studentParams[':year_level'] = $yearFilter;
}
if ($sectionFilter !== 'all') {
    $studentFilters[] = "section = :section";
    $studentParams[':section'] = $sectionFilter;
}
$studentWhereSql = $studentFilters ? 'WHERE ' . implode(' AND ', $studentFilters) : '';
$stmt = $pdo->prepare("SELECT * FROM students $studentWhereSql ORDER BY last_name, first_name");
$stmt->execute($studentParams);
$result = $stmt->fetchAll();

?>
<!doctype html><html><head>
  <meta charset="utf-8"><title>Database Management - Students</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="layout">
  <?php include '../includes/sidebar.php'; ?>
  <main class="content">
    <?php show_flash(); ?>


    <div class="page-header">
      <h1>Manage Student Information</h1>
      <p class="text-muted">Add, edit, and manage student records</p>
    </div>

<!-- Feedback (optional) -->
<?php if (isset($_GET['msg'])): ?>
    <div class="feedback-toast feedback-toast-success" role="status" aria-live="polite">
        <?= htmlspecialchars($_GET['msg']) ?>
    </div>
<?php endif; ?>

<!-- Tabs: Add / Edit Student -->
<div class="card" id="student-tabs">
  <div class="card-header admin-tabs" role="tablist" aria-label="Student management tabs">
    <button type="button" class="admin-tab tab-link active" data-tab="add">
      <span class="material-symbols-rounded" aria-hidden="true">add_circle</span>
      Add Student
    </button>
    <button type="button" class="admin-tab tab-link" data-tab="edit">
      <span class="material-symbols-rounded" aria-hidden="true">edit</span>
      Edit Student
    </button>
  </div>

  <div class="card-body">
    <div class="tab-pane active" data-pane="add">
      <form action="../includes/student_process.php" method="POST">
        <input type="hidden" name="action" value="create">
        <div class="form-box">
          <div class="row-grid cols-2">
            <div class="form-group">
              <label>Student ID *</label>
              <input type="text" name="student_id" class="form-control" required>
            </div>
            <div class="form-group">
              <label>PTC Email *</label>
              <input type="email" name="ptc_email" class="form-control" required>
            </div>
          </div>
          <div class="row-grid cols-3">
            <div class="form-group">
              <label>First Name *</label>
              <input type="text" name="first_name" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Middle Name</label>
              <input type="text" name="middle_name" class="form-control">
            </div>
            <div class="form-group">
              <label>Last Name *</label>
              <input type="text" name="last_name" class="form-control" required>
            </div>
          </div>
          <div class="row-grid cols-3">
            <div class="form-group">
              <label>Year Level</label>
              <select name="year_level" class="form-control">
                <option value="">-- Select --</option>
                <option value="1">1st Year</option>
                <option value="2">2nd Year</option>
                <option value="3">3rd Year</option>
                <option value="4">4th Year</option>
              </select>
            </div>


            <div class="form-group">
              <label>Section</label>
              <select name="section" class="form-control">
                <option value="">-- Select Section --</option>
                <?php foreach ($sectionOptions as $sectionOption): ?>
                  <option value="<?= htmlspecialchars($sectionOption['value']); ?>">
                    <?= htmlspecialchars($sectionOption['label']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>




            <div class="form-group">
              <label>Status *</label>
              <select name="status" class="form-control" required>
                <option value="Regular">Regular</option>
                <option value="Irregular">Irregular</option>
                <option value="Inactive">Inactive</option>
              </select>
            </div>
          </div>
            <div class="form-actions">
                <button type="submit">Save Student</button>
            </div>
        </div>
      </form>
    </div>


    <div class="tab-pane" data-pane="edit">
      <form action="../includes/student_process.php" method="POST">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" id="edit-id">
        <div class="form-box">
          <div class="row-grid cols-2">
            <div class="form-group">
              <label>Student ID *</label>
              <input type="text" name="student_id" id="edit-student_id" class="form-control" required>
            </div>
            <div class="form-group">
              <label>PTC Email *</label>
              <input type="email" name="ptc_email" id="edit-ptc_email" class="form-control" required>
            </div>
          </div>
          <div class="row-grid cols-3">
            <div class="form-group">
              <label>First Name *</label>
              <input type="text" name="first_name" id="edit-first_name" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Middle Name</label>
              <input type="text" name="middle_name" id="edit-middle_name" class="form-control">
            </div>
            <div class="form-group">
              <label>Last Name *</label>
              <input type="text" name="last_name" id="edit-last_name" class="form-control" required>
            </div>
          </div>
          <div class="row-grid cols-3">
            <div class="form-group">
              <label>Year Level</label>
              <select name="year_level" id="edit-year_level" class="form-control">
                <option value="">-- Select --</option>
                <option value="1">1st Year</option>
                <option value="2">2nd Year</option>
                <option value="3">3rd Year</option>
                <option value="4">4th Year</option>
              </select>
            </div>
            <div class="form-group">
              <label>Section</label>
              <select name="section" id="edit-section" class="form-control">
                <option value="">-- Select Section --</option>
                <?php foreach ($sectionOptions as $sectionOption): ?>
                  <option value="<?= htmlspecialchars($sectionOption['value']); ?>">
                    <?= htmlspecialchars($sectionOption['label']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Status *</label>
              <select name="status" id="edit-status" class="form-control" required>
                <option value="Regular">Regular</option>
                <option value="Irregular">Irregular</option>
                <option value="Inactive">Inactive</option>
              </select>
            </div>
          </div>
            <div class="form-actions">
                <button type="submit">Update Student</button>
            </div>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="page-header">
    <h2>Student Information Table</h2>
</div>

<div class="card">
    <div class="card-body">
        <form method="get" class="form-box filters-grid table-filter-form">
            <div class="form-group">
                <label>Status</label>
                <select name="status_filter" class="form-control">
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : ''; ?>>All</option>
                </select>
            </div>
            <div class="form-group">
                <label>Year Level</label>
                <select name="year_level_filter" class="form-control">
                    <option value="all" <?= $yearFilter === 'all' ? 'selected' : ''; ?>>All Year Levels</option>
                    <option value="1" <?= $yearFilter === '1' ? 'selected' : ''; ?>>1st Year</option>
                    <option value="2" <?= $yearFilter === '2' ? 'selected' : ''; ?>>2nd Year</option>
                    <option value="3" <?= $yearFilter === '3' ? 'selected' : ''; ?>>3rd Year</option>
                    <option value="4" <?= $yearFilter === '4' ? 'selected' : ''; ?>>4th Year</option>
                </select>
            </div>
            <div class="form-group">
                <label>Section</label>
                <select name="section_filter" class="form-control">
                    <option value="all" <?= $sectionFilter === 'all' ? 'selected' : ''; ?>>All Sections</option>
                    <?php foreach ($sectionOptions as $sectionOption): ?>
                        <option value="<?= htmlspecialchars($sectionOption['value']); ?>" <?= $sectionFilter === $sectionOption['value'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($sectionOption['label']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-actions filter-actions">
                <button class="btn btn-primary" type="submit">Apply Filters</button>
                <a class="btn btn-sm btn-secondary" href="./students.php">Reset</a>
            </div>
        </form>
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student ID</th>
                    <th>PTC Email</th>
                    <th>Name</th>
                    <th>Year Level</th>
                    <th>Section</th>
                    <th>Status</th>
                    <th width="140">Actions</th>
                </tr>
            </thead>
           <tbody>
                <?php
                $i = 1;
                foreach ($result as $row):
                ?>
                <tr>
                    <td><?= $i++; ?></td>
                    <td><?= htmlspecialchars($row['student_id']); ?></td>
                    <td><?= htmlspecialchars($row['ptc_email']); ?></td>
                    <td><?= htmlspecialchars($row['last_name'] . ', ' . $row['first_name'] . ' ' . $row['middle_name']); ?></td>
                    <td>
                        <?php
                        $yl = $row['year_level'];
                        $labels = ['1' => '1st Year', '2' => '2nd Year', '3' => '3rd Year', '4' => '4th Year'];
                        echo htmlspecialchars($labels[$yl] ?? $yl);
                        ?>
                    </td>
                    <td><?= htmlspecialchars($row['section']); ?></td>
                    <td><?= htmlspecialchars($row['status']); ?></td>
                    <td class="actions">
                        <button 
                            class="btn btn-sm btn-warning btn-edit"
                            data-id="<?= $row['id']; ?>"
                            data-student_id="<?= htmlspecialchars($row['student_id']); ?>"
                            data-ptc_email="<?= htmlspecialchars($row['ptc_email']); ?>"
                            data-first_name="<?= htmlspecialchars($row['first_name']); ?>"
                            data-middle_name="<?= htmlspecialchars($row['middle_name']); ?>"
                            data-last_name="<?= htmlspecialchars($row['last_name']); ?>"
                            data-year_level="<?= htmlspecialchars($row['year_level']); ?>"
                            data-section="<?= htmlspecialchars($row['section']); ?>"
                            data-status="<?= htmlspecialchars($row['status']); ?>"
                        >Edit</button>

                        <form action="../includes/student_process.php" method="POST" onsubmit="return confirm('Deactivate this student?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $row['id']; ?>">
                            <button class="btn btn-sm btn-danger btn-deactivate" type="submit">Deactivate</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>


<?php if (function_exists('renderCrudTabsScript')) { renderCrudTabsScript(); } ?>
<script src="../assets/js/admin.js"></script>

  </main>
<?php include '../includes/footer.php'; ?>
</body>
</html>
