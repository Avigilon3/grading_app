<?php
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

// Fetch lookup data for selects
$termsStmt = $pdo->query("SELECT id, term_name FROM terms ORDER BY start_date DESC, id DESC");
$terms = $termsStmt->fetchAll();

$subjectsStmt = $pdo->query("SELECT id, subject_code, subject_title FROM subjects ORDER BY subject_code, subject_title");
$subjects = $subjectsStmt->fetchAll();

$profStmt = $pdo->query("SELECT id, professor_id, first_name, middle_name, last_name FROM professors ORDER BY last_name, first_name");
$professors = $profStmt->fetchAll();

// Fetch sections with joined display fields
$stmt = $pdo->query(
    "SELECT s.*, 
            t.term_name AS term_name,
            sub.subject_title AS subject_title, sub.subject_code AS subject_code,
            p.first_name, p.middle_name, p.last_name, p.professor_id AS professor_code
       FROM sections s
  LEFT JOIN terms t ON t.id = s.term_id
  LEFT JOIN subjects sub ON sub.id = s.subject_id
  LEFT JOIN professors p ON p.id = s.assigned_professor_id
   ORDER BY s.section_name"
);
$result = $stmt->fetchAll();
?>
<!doctype html><html><head>
  <meta charset="utf-8"><title>Database Management - Sections</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include __DIR__.'/../includes/header.php'; ?>
<div class="layout">
  <?php include __DIR__.'/../includes/sidebar.php'; ?>
  <main class="content">
    <?php show_flash(); ?>

    <div class="page-header">
      <h2>Manage Sections Information</h2>
    </div>

    <?php if (isset($_GET['msg'])): ?>
      <div class="alert alert-success">
        <?= htmlspecialchars($_GET['msg']) ?>
      </div>
    <?php endif; ?>

    <div class="card" id="student-tabs">
      <div class="card-header tabs">
        <button type="button" class="tab-link active" data-tab="add">Add Section</button>
        <button type="button" class="tab-link" data-tab="edit">Edit Section</button>
      </div>
      <div class="card-body">
        <div class="tab-pane active" data-pane="add">
          <form action="../includes/section_process.php" method="POST">
            <input type="hidden" name="action" value="create">
            <div class="form-box">
              <div class="row-grid cols-3">
                <div class="form-group">
                  <label>Section Name *</label>
                  <input type="text" name="section_name" class="form-control" required>
                </div>
                <div class="form-group">
                  <label>Term</label>
                  <select name="term_id" class="form-control">
                    <option value="">-- Select Term --</option>
                    <?php foreach ($terms as $t): ?>
                      <option value="<?= (int)$t['id']; ?>"><?= htmlspecialchars($t['term_name']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label>Subject</label>
                  <select name="subject_id" class="form-control">
                    <option value="">-- Select Subject --</option>
                    <?php foreach ($subjects as $s): ?>
                      <option value="<?= (int)$s['id']; ?>"><?= htmlspecialchars(($s['subject_code'] ? $s['subject_code'].' - ' : '') . $s['subject_title']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="row-grid cols-3">
                <div class="form-group">
                  <label>Schedule</label>
                  <input type="text" name="schedule" class="form-control" placeholder="MWF 1:00-2:00">
                </div>
                <div class="form-group">
                  <label>Assigned Professor</label>
                  <select name="assigned_professor_id" class="form-control">
                    <option value="">-- Select Professor --</option>
                    <?php foreach ($professors as $p): ?>
                      <?php $name = trim($p['last_name'] . ', ' . $p['first_name'] . ' ' . ($p['middle_name'] ?? '')); ?>
                      <option value="<?= (int)$p['id']; ?>"><?= htmlspecialchars($name . ($p['professor_id'] ? ' ('.$p['professor_id'].')' : '')); ?></option>
                    <?php endforeach; ?>
                  </select>
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
                <button class="btn btn-primary" type="submit">Save Section</button>
              </div>
            </div>
          </form>
        </div>
        <div class="tab-pane" data-pane="edit">
          <form action="../includes/section_process.php" method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit-id">
            <div class="form-box">
              <div class="row-grid cols-3">
                <div class="form-group">
                  <label>Section Name *</label>
                  <input type="text" name="section_name" id="edit-section_name" class="form-control" required>
                </div>
                <div class="form-group">
                  <label>Term</label>
                  <select name="term_id" id="edit-term_id" class="form-control">
                    <option value="">-- Select Term --</option>
                    <?php foreach ($terms as $t): ?>
                      <option value="<?= (int)$t['id']; ?>"><?= htmlspecialchars($t['term_name']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label>Subject</label>
                  <select name="subject_id" id="edit-subject_id" class="form-control">
                    <option value="">-- Select Subject --</option>
                    <?php foreach ($subjects as $s): ?>
                      <option value="<?= (int)$s['id']; ?>"><?= htmlspecialchars(($s['subject_code'] ? $s['subject_code'].' - ' : '') . $s['subject_title']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="row-grid cols-3">
                <div class="form-group">
                  <label>Schedule</label>
                  <input type="text" name="schedule" id="edit-schedule" class="form-control" placeholder="MWF 1:00-2:00">
                </div>
                <div class="form-group">
                  <label>Assigned Professor</label>
                  <select name="assigned_professor_id" id="edit-assigned_professor_id" class="form-control">
                    <option value="">-- Select Professor --</option>
                    <?php foreach ($professors as $p): ?>
                      <?php $name = trim($p['last_name'] . ', ' . $p['first_name'] . ' ' . ($p['middle_name'] ?? '')); ?>
                      <option value="<?= (int)$p['id']; ?>"><?= htmlspecialchars($name . ($p['professor_id'] ? ' ('.$p['professor_id'].')' : '')); ?></option>
                    <?php endforeach; ?>
                  </select>
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
                <button class="btn btn-primary" type="submit">Update Section</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="page-header">
      <h2>Sections Information Table</h2>
    </div>
    <div class="card">
      <div class="card-body">
        <table class="table table-striped table-bordered">
          <thead>
            <tr>
              <th>#</th>
              <th>Section Name</th>
              <th>Term</th>
              <th>Subject</th>
              <th>Schedule</th>
              <th>Professor</th>
              <th>Status</th>
              <th width="140">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php $i=1; foreach ($result as $row): ?>
            <tr>
              <td><?= $i++; ?></td>
              <td><?= htmlspecialchars($row['section_name']); ?></td>
              <td><?= htmlspecialchars($row['term_name'] ?? ('ID #'.$row['term_id'])); ?></td>
              <td><?= htmlspecialchars(($row['subject_code'] ? $row['subject_code'].' - ' : '') . ($row['subject_title'] ?? '')); ?></td>
              <td><?= htmlspecialchars($row['schedule']); ?></td>
              <td><?= htmlspecialchars(trim(($row['last_name'] ?? '') . ', ' . ($row['first_name'] ?? '') . ' ' . ($row['middle_name'] ?? '')) . (isset($row['professor_code']) && $row['professor_code'] ? ' ('.$row['professor_code'].')' : '')); ?></td>
              <td><?= ($row['is_active'] ? 'Active' : 'Inactive'); ?></td>
              <td class="actions">
                <button
                  class="btn btn-sm btn-warning btn-edit"
                  data-id="<?= $row['id']; ?>"
                  data-section_name="<?= htmlspecialchars($row['section_name']); ?>"
                  data-term_id="<?= htmlspecialchars($row['term_id']); ?>"
                  data-subject_id="<?= htmlspecialchars($row['subject_id']); ?>"
                  data-schedule="<?= htmlspecialchars($row['schedule']); ?>"
                  data-assigned_professor_id="<?= htmlspecialchars($row['assigned_professor_id']); ?>"
                  data-is_active="<?= htmlspecialchars($row['is_active']); ?>"
                >Edit</button>

                <form action="../includes/section_process.php" method="POST" onsubmit="return confirm('Delete this section?');">
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
