<?php
require_once '../includes/init.php';
requireAdmin();

$stmt = $pdo->query("SELECT * FROM subjects ORDER BY subject_code");
$result = $stmt->fetchAll();
?>
<!doctype html><html><head>
  <meta charset="utf-8"><title>Database Management - Subjects</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="layout">
  <?php include '../includes/sidebar.php'; ?>
  <main class="content">
    <?php show_flash(); ?>

    <div class="page-header">
      <h1>Manage Subjects Information</h1>
      <p>Add, edit, and manage course/subject records</p>
    </div>

    <?php if (isset($_GET['msg'])): ?>
      <div class="alert alert-success">
        <?= htmlspecialchars($_GET['msg']) ?>
      </div>
    <?php endif; ?>

    <div class="card" id="student-tabs">
      <div class="card-header tabs">
        <button type="button" class="tab-link active" data-tab="add">Add Subject</button>
        <button type="button" class="tab-link" data-tab="edit">Edit Subject</button>
      </div>
      <div class="card-body">
        <div class="tab-pane active" data-pane="add">
          <form action="../includes/subject_process.php" method="POST">
            <input type="hidden" name="action" value="create">
            <div class="form-box">
              <div class="row-grid cols-3">
                <div class="form-group">
                  <label>Subject Code *</label>
                  <input type="text" name="subject_code" class="form-control" required>
                </div>
                <div class="form-group">
                  <label>Title *</label>
                  <input type="text" name="subject_title" class="form-control" required>
                </div>
                <div class="form-group">
                  <label>Units</label>
                  <input type="number" step="0.5" name="units" class="form-control" value="3">
                </div>
              </div>
              <div class="row-grid cols-1">
                <div class="form-group">
                  <label>Description</label>
                  <textarea name="description" class="form-control" rows="2"></textarea>
                </div>
              </div>
              <div class="row-grid cols-2">
                <div class="form-group">
                  <label>Status</label>
                  <select name="is_active" class="form-control">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                  </select>
                </div>
              </div>
              <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save Subject</button>
              </div>
            </div>
          </form>
        </div>
        <div class="tab-pane" data-pane="edit">
          <form action="../includes/subject_process.php" method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit-id">
            <div class="form-box">
              <div class="row-grid cols-3">
                <div class="form-group">
                  <label>Subject Code *</label>
                  <input type="text" name="subject_code" id="edit-subject_code" class="form-control" required>
                </div>
                <div class="form-group">
                  <label>Title *</label>
                  <input type="text" name="subject_title" id="edit-subject_title" class="form-control" required>
                </div>
                <div class="form-group">
                  <label>Units</label>
                  <input type="number" step="0.5" name="units" id="edit-units" class="form-control" value="3">
                </div>
              </div>
              <div class="row-grid cols-1">
                <div class="form-group">
                  <label>Description</label>
                  <textarea name="description" id="edit-description" class="form-control" rows="2"></textarea>
                </div>
              </div>
              <div class="row-grid cols-2">
                <div class="form-group">
                  <label>Status</label>
                  <select name="is_active" id="edit-is_active" class="form-control">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                  </select>
                </div>
              </div>
              <div class="form-actions">
                <button class="btn btn-primary" type="submit">Update Subject</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

  <div class="page-header">
    <h2>Subjects Information Table</h2>
  </div>

    <div class="card">
      <div class="card-body">
        <table class="table table-striped table-bordered">
          <thead>
            <tr>
              <th>#</th>
              <th>Code</th>
              <th>Title</th>
              <th>Units</th>
              <th>Status</th>
              <th width="140">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php $i=1; foreach ($result as $row): ?>
            <tr>
              <td><?= $i++; ?></td>
              <td><?= htmlspecialchars($row['subject_code']); ?></td>
              <td><?= htmlspecialchars($row['subject_title']); ?></td>
              <td><?= htmlspecialchars($row['units']); ?></td>
              <td><?= ($row['is_active'] ? 'Active' : 'Inactive'); ?></td>
              <td class="actions">
                <button
                  class="btn btn-sm btn-warning btn-edit"
                  data-id="<?= $row['id']; ?>"
                  data-subject_code="<?= htmlspecialchars($row['subject_code']); ?>"
                  data-subject_title="<?= htmlspecialchars($row['subject_title']); ?>"
                  data-units="<?= htmlspecialchars($row['units']); ?>"
                  data-description="<?= htmlspecialchars($row['description']); ?>"
                  data-is_active="<?= htmlspecialchars($row['is_active']); ?>"
                >Edit</button>

                <form action="../includes/subject_process.php" method="POST" onsubmit="return confirm('Delete this subject?');">
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

