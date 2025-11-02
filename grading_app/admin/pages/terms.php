<?php
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

$stmt = $pdo->query("SELECT * FROM terms ORDER BY start_date DESC, id DESC");
$result = $stmt->fetchAll();
?>
<!doctype html><html><head>
  <meta charset="utf-8"><title>Database Management - Terms</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include __DIR__.'/../includes/header.php'; ?>
<div class="layout">
  <?php include __DIR__.'/../includes/sidebar.php'; ?>
  <main class="content">
    <?php show_flash(); ?>

    <div class="page-header">
      <h2>Manage Terms / Semesters</h2>
    </div>

    <?php if (isset($_GET['msg'])): ?>
      <div class="alert alert-success">
        <?= htmlspecialchars($_GET['msg']) ?>
      </div>
    <?php endif; ?>

    <div class="card" id="student-tabs">
      <div class="card-header tabs">
        <button type="button" class="tab-link active" data-tab="add">Add Term</button>
        <button type="button" class="tab-link" data-tab="edit">Edit Term</button>
      </div>
      <div class="card-body">
        <div class="tab-pane active" data-pane="add">
          <form action="../includes/term_process.php" method="POST">
            <input type="hidden" name="action" value="create">
            <div class="form-box">
              <div class="row-grid cols-2">
                <div class="form-group">
                  <label>Semester *</label>
                  <select name="semester" class="form-control" required>
                    <option value="1">1st Semester</option>
                    <option value="2">2nd Semester</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>School Year *</label>
                  <input type="text" name="school_year" class="form-control" placeholder="2025-2026" required>
                </div>
              </div>
              <div class="row-grid cols-3">
                <div class="form-group">
                  <label>Start Date</label>
                  <input type="date" name="start_date" class="form-control">
                </div>
                <div class="form-group">
                  <label>End Date</label>
                  <input type="date" name="end_date" class="form-control">
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
                <button class="btn btn-primary" type="submit">Save Term</button>
              </div>
            </div>
          </form>
        </div>
        <div class="tab-pane" data-pane="edit">
          <form action="../includes/term_process.php" method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit-id">
            <div class="form-box">
              <div class="row-grid cols-2">
                <div class="form-group">
                  <label>Semester *</label>
                  <select name="semester" id="edit-semester" class="form-control" required>
                    <option value="1">1st Semester</option>
                    <option value="2">2nd Semester</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>School Year *</label>
                  <input type="text" name="school_year" id="edit-school_year" class="form-control" placeholder="2025-2026" required>
                </div>
              </div>
              <div class="row-grid cols-3">
                <div class="form-group">
                  <label>Start Date</label>
                  <input type="date" name="start_date" id="edit-start_date" class="form-control">
                </div>
                <div class="form-group">
                  <label>End Date</label>
                  <input type="date" name="end_date" id="edit-end_date" class="form-control">
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
                <button class="btn btn-primary" type="submit">Update Term</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

  <div class="page-header">
    <h2>Terms / Semesters Table</h2>
  </div>

    <div class="card">
      <div class="card-body">
        <table class="table table-striped table-bordered">
          <thead>
            <tr>
              <th>#</th>
              <th>Term Name</th>
              <th>School Year</th>
              <th>Start</th>
              <th>End</th>
              <th>Status</th>
              <th width="140">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php $i=1; foreach ($result as $row): ?>
            <tr>
              <td><?= $i++; ?></td>
              <td><?= htmlspecialchars($row['term_name']); ?></td>
              <td><?= htmlspecialchars($row['school_year']); ?></td>
              <td><?= htmlspecialchars($row['start_date']); ?></td>
              <td><?= htmlspecialchars($row['end_date']); ?></td>
              <td><?= ($row['is_active'] ? 'Active' : 'Inactive'); ?></td>
              <td class="actions">
                <button
                  class="btn btn-sm btn-warning btn-edit"
                  data-id="<?= $row['id']; ?>"
                  data-term_name="<?= htmlspecialchars($row['term_name']); ?>"
                  data-semester="<?= htmlspecialchars($row['semester']); ?>"
                  data-school_year="<?= htmlspecialchars($row['school_year']); ?>"
                  data-start_date="<?= htmlspecialchars($row['start_date']); ?>"
                  data-end_date="<?= htmlspecialchars($row['end_date']); ?>"
                  data-is_active="<?= htmlspecialchars($row['is_active']); ?>"
                >Edit</button>

                <form action="../includes/term_process.php" method="POST" onsubmit="return confirm('Delete this term?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $row['id']; ?>">
                  <button class="btn btn-sm btn-danger" type="submit">Delete</button>
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
</div>
</body>
</html>
