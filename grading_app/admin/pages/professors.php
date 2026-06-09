<?php
require_once '../includes/init.php';
requireAdmin();

$statusFilter = $_GET['status_filter'] ?? 'active';
$subjectFilter = $_GET['subject_filter'] ?? 'all';
$validStatusFilters = ['active', 'inactive', 'all'];
if (!in_array($statusFilter, $validStatusFilters, true)) {
  $statusFilter = 'active';
}

$subjects = [];
$subjectsById = [];
try {
  $subjectsStmt = $pdo->query("SELECT id,
                                      subject_code,
                                      subject_title
                                FROM subjects
                                ORDER BY subject_code");
  $subjects = $subjectsStmt->fetchAll();
  foreach ($subjects as $subject) {
    $code = $subject['subject_code'] ?? '';
    $title = $subject['subject_title'] ?? '';
    $label = $code;
    if ($title) {
      $label = ($label ? $label . ' - ' : '') . $title;
    } elseif (!$label) {
      $label = 'Subject #' . (int)$subject['id'];
    }
    $subjectsById[$subject['id']] = $label;
  }
  if ($subjectFilter !== 'all' && !array_key_exists((int)$subjectFilter, $subjectsById)) {
    $subjectFilter = 'all';
  }
} catch (Exception $e) {
  $subjects = [];
  $subjectsById = [];
  $subjectFilter = 'all';
}

$professorFilters = [];
$professorParams = [];
if ($statusFilter === 'active') {
  $professorFilters[] = 'is_active = 1';
} elseif ($statusFilter === 'inactive') {
  $professorFilters[] = 'is_active = 0';
}
if ($subjectFilter !== 'all') {
  $professorFilters[] = 'subject_id = :subject_id';
  $professorParams[':subject_id'] = (int)$subjectFilter;
}
$professorWhereSql = $professorFilters ? 'WHERE ' . implode(' AND ', $professorFilters) : '';
$stmt = $pdo->prepare("SELECT * FROM professors $professorWhereSql ORDER BY last_name, first_name");
$stmt->execute($professorParams);
$result = $stmt->fetchAll();
?>
<!doctype html><html><head>
  <meta charset="utf-8"><title> Database Management - Professors</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="layout">
<?php include '../includes/sidebar.php'; ?>
  <main class="content">
    <?php show_flash(); ?>

    <div class="page-header">
      <h1>Manage Professor Information</h1>
      <p class="text-muted">Add, edit, and manage professor records</p>
    </div>

    <?php if (isset($_GET['msg'])): ?>
      <div class="feedback-toast feedback-toast-success" role="status" aria-live="polite">
        <?= htmlspecialchars($_GET['msg']) ?>
      </div>
    <?php endif; ?>

    <div class="card" id="student-tabs">
      <div class="card-header admin-tabs" role="tablist" aria-label="Professor management tabs">
        <button type="button" class="admin-tab tab-link active" data-tab="add">
          <span class="material-symbols-rounded" aria-hidden="true">add_circle</span>
          Add Professor
        </button>
        <button type="button" class="admin-tab tab-link" data-tab="edit">
          <span class="material-symbols-rounded" aria-hidden="true">edit</span>
          Edit Professor
        </button>
      </div>
      <div class="card-body">
        <div class="tab-pane active" data-pane="add">
          <form action="../includes/professor_process.php" method="POST">
            <input type="hidden" name="action" value="create">
            <div class="form-box">
              <div class="row-grid cols-2">
                <div class="form-group">
                  <label>Professor ID *</label>
                  <input type="text" name="professor_id" class="form-control" required>
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
                  <label>Subject</label>
                  <select name="subject_id" class="form-control">
                    <option value="">-- Select Subject --</option>
                    <?php foreach ($subjects as $subject): ?>
                      <?php
                        $label = $subjectsById[$subject['id']] ?? ('Subject #' . (int)$subject['id']);
                      ?>
                      <option value="<?= (int)$subject['id']; ?>"><?= htmlspecialchars($label); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label>Schedule</label>
                  <input type="text" name="schedule" class="form-control" placeholder="e.g. Mon/Wed 1:00-2:00 PM">
                </div>
                <div class="form-group">
                  <label>Status</label>
                  <select name="is_active" class="form-control">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                  </select>
                </div>
              </div>
              <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save Professor</button>
              </div>
            </div>
          </form>
        </div>
        <div class="tab-pane" data-pane="edit">
          <form action="../includes/professor_process.php" method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit-id">
            <div class="form-box">
              <div class="row-grid cols-2">
                <div class="form-group">
                  <label>Professor ID *</label>
                  <input type="text" name="professor_id" id="edit-professor_id" class="form-control" required>
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
                  <label>Subject</label>
                  <select name="subject_id" id="edit-subject_id" class="form-control">
                    <option value="">-- Select Subject --</option>
                    <?php foreach ($subjects as $subject): ?>
                      <?php
                        $label = $subjectsById[$subject['id']] ?? ('Subject #' . (int)$subject['id']);
                      ?>
                      <option value="<?= (int)$subject['id']; ?>"><?= htmlspecialchars($label); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label>Schedule</label>
                  <input type="text" name="schedule" id="edit-schedule" class="form-control" placeholder="e.g. Mon/Wed 1:00-2:00 PM">
                </div>
                <div class="form-group">
                  <label>Status</label>
                  <select name="is_active" id="edit-is_active" class="form-control">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                  </select>
                </div>
              </div>
              <div class="form-actions">
                <button class="btn btn-primary" type="submit">Update Professor</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="page-header">
      <h2>Professor Information Table</h2>
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
            <label>Subject</label>
            <select name="subject_filter" class="form-control">
              <option value="all" <?= $subjectFilter === 'all' ? 'selected' : ''; ?>>All Subjects</option>
              <?php foreach ($subjects as $subject): ?>
                <?php $label = $subjectsById[$subject['id']] ?? ('Subject #' . (int)$subject['id']); ?>
                <option value="<?= (int)$subject['id']; ?>" <?= (string)$subjectFilter === (string)$subject['id'] ? 'selected' : ''; ?>>
                  <?= htmlspecialchars($label); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-actions filter-actions">
            <button class="btn btn-primary" type="submit">Apply Filters</button>
            <a class="btn btn-sm btn-secondary" href="./professors.php">Reset</a>
          </div>
        </form>
        <table class="table table-striped table-bordered">
          <thead>
            <tr>
              <th>#</th>
              <th>Professor ID</th>
              <th>PTC Email</th>
              <th>Name</th>
              <th>Subject</th>
              <th>Schedule</th>
              <th>Status</th>
              <th width="140">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php $i=1; foreach ($result as $row): ?>
            <tr>
              <td><?= $i++; ?></td>
              <td><?= htmlspecialchars($row['professor_id']); ?></td>
              <td><?= htmlspecialchars($row['ptc_email']); ?></td>
              <td><?= htmlspecialchars($row['last_name'] . ', ' . $row['first_name'] . ' ' . $row['middle_name']); ?></td>
              <td><?= htmlspecialchars($subjectsById[$row['subject_id']] ?? '--'); ?></td>
              <td><?= htmlspecialchars($row['schedule'] ?: '--'); ?></td>
              <td><?= ($row['is_active'] ? 'Active' : 'Inactive'); ?></td>
              <td class="actions">
                <button
                  class="btn btn-sm btn-warning btn-edit"
                  data-id="<?= $row['id']; ?>"
                  data-professor_id="<?= htmlspecialchars($row['professor_id']); ?>"
                  data-ptc_email="<?= htmlspecialchars($row['ptc_email']); ?>"
                  data-first_name="<?= htmlspecialchars($row['first_name']); ?>"
                  data-middle_name="<?= htmlspecialchars($row['middle_name']); ?>"
                  data-last_name="<?= htmlspecialchars($row['last_name']); ?>"
                  data-is_active="<?= htmlspecialchars($row['is_active']); ?>"
                  data-subject_id="<?= htmlspecialchars((string)($row['subject_id'] ?? '')); ?>"
                  data-schedule="<?= htmlspecialchars((string)($row['schedule'] ?? '')); ?>"
                >Edit</button>

                <form action="../includes/professor_process.php" method="POST" onsubmit="return confirm('Deactivate this professor?');">
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
    </div>
    <?php if (function_exists('renderCrudTabsScript')) { renderCrudTabsScript(); } ?>
    <script src="../assets/js/admin.js"></script>

  </main>
  <?php include '../includes/footer.php'; ?>
</div>
</body>
</html>

