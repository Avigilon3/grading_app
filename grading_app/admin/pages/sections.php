<?php
require_once '../includes/init.php';
requireAdmin();

// Fetch sections with joined display fields
$stmt = $pdo->query(
    "SELECT s.*, t.term_name 
     FROM sections s
     LEFT JOIN terms t ON t.id = s.term_id
     ORDER BY s.section_name DESC, s.id DESC"
);
$result = $stmt->fetchAll();

// Fetch lookup data for selects
$termsStmt = $pdo->query("SELECT id, term_name FROM terms ORDER BY start_date DESC, id DESC");
$terms = $termsStmt->fetchAll();

$coursesStmt = $pdo->query("SELECT * FROM courses ORDER BY code, title");
$courses = $coursesStmt->fetchAll();
$courseLabels = [];
foreach ($courses as $course) {
  $label = trim(($course['code'] ?? '') . ' ' . ($course['title'] ?? ''));
  if ($label === '') {
    $label = 'Course #' . (int)$course['id'];
  }
  $courseLabels[$course['id']] = $label;
}

$yearLevels = [
  '1' => '1st Year',
  '2' => '2nd Year',
  '3' => '3rd Year',
  '4' => '4th Year',
];

?>
<!doctype html><html><head>
  <meta charset="utf-8"><title>Database Management - Sections</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="layout">
<?php include '../includes/sidebar.php'; ?>
  <main class="content">
    <?php show_flash(); ?>

    <div class="page-header">
      <h1>Manage Sections Information</h1>
      <p>Add, edit, and manage course/subject records</p>
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
              <div class="row-grid cols-4">
                <div class="form-group">
                  <label>Section Name *</label>
                  <input type="text" name="section_name" class="form-control" required>
                </div>
                <div class="form-group">
                  <label>Course *</label>
                  <select name="course_id" class="form-control">
                    <option value="">-- Select Course --</option>
                    <?php foreach ($courses as $course): ?>
                      <?php
                        $label = trim(($course['code'] ?? '') . ' ' . ($course['title'] ?? '')) ?: 'Course #' . (int)$course['id'];
                      ?>
                      <option value="<?= (int)$course['id']; ?>"><?= htmlspecialchars($label); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label>Year Level</label>
                  <select name="year_level" class="form-control">
                    <option value="">-- Select Year Level --</option>
                    <?php foreach ($yearLevels as $value => $label): ?>
                      <option value="<?= htmlspecialchars($value); ?>"><?= htmlspecialchars($label); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label>Term</label>
                  <select name="term_id" class="form-control">
                    <option value="">-- Select Term --</option>
                    <?php foreach ($terms as $term): ?>
                      <option value="<?= (int)$term['id']; ?>"><?= htmlspecialchars($term['term_name']); ?></option>
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
              <div class="row-grid cols-4">
                <div class="form-group">
                  <label>Section Name *</label>
                  <input type="text" name="section_name" id="edit-section_name" class="form-control" required>
                </div>
                <div class="form-group">
                  <label>Course *</label>
                  <select name="course_id" id="edit-course_id" class="form-control">
                    <option value="">-- Select Course --</option>
                    <?php foreach ($courses as $course): ?>
                      <?php
                        $label = trim(($course['code'] ?? '') . ' ' . ($course['title'] ?? '')) ?: 'Course #' . (int)$course['id'];
                      ?>
                      <option value="<?= (int)$course['id']; ?>"><?= htmlspecialchars($label); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label>Year Level</label>
                  <select name="year_level" id="edit-year_level" class="form-control">
                    <option value="">-- Select Year Level --</option>
                    <?php foreach ($yearLevels as $value => $label): ?>
                      <option value="<?= htmlspecialchars($value); ?>"><?= htmlspecialchars($label); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label>Term</label>
                  <select name="term_id" id="edit-term_id" class="form-control">
                    <option value="">-- Select Term --</option>
                    <?php foreach ($terms as $term): ?>
                      <option value="<?= (int)$term['id']; ?>"><?= htmlspecialchars($term['term_name']); ?></option>
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
              <th>Course</th>
              <th>Year Level</th>
              <th>Term</th>
              <th>Status</th>
              <th width="140">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php $i=1; foreach ($result as $row): ?>
            <tr>
              <td><?= $i++; ?></td>
              <td><?= htmlspecialchars($row['section_name']); ?></td>
              <td><?= htmlspecialchars($courseLabels[$row['course_id']] ?? '--'); ?></td>
              <td><?= htmlspecialchars($yearLevels[$row['year_level']] ?? $row['year_level'] ?? '--'); ?></td>
              <td><?= htmlspecialchars($row['term_name'] ?? '--'); ?></td>
              <td><?= ($row['is_active'] ? 'Active' : 'Inactive'); ?></td>
              <td class="actions">
                <a class="btn btn-sm" href="./masterlist.php?section_id=<?= $row['id']; ?>">Masterlist</a>
                <button
                  class="btn btn-sm btn-warning btn-edit"
                  data-id="<?= $row['id']; ?>"
                  data-section_name="<?= htmlspecialchars($row['section_name']); ?>"
                  data-course_id="<?= htmlspecialchars($row['course_id']); ?>"
                  data-year_level="<?= htmlspecialchars($row['year_level']); ?>"
                  data-term_id="<?= htmlspecialchars($row['term_id'] ?? ''); ?>"
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
