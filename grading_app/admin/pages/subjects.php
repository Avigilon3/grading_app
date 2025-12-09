<?php
require_once '../includes/init.php';
requireAdmin();

$stmt = $pdo->query("SELECT s.*,
                             t.term_name,
                             t.is_active AS term_is_active
                      FROM subjects s
                      LEFT JOIN terms t
                        ON t.id = s.term_id
                      ORDER BY s.subject_code");
$result = $stmt->fetchAll();

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

$termsRecords = $pdo->query("SELECT * FROM terms ORDER BY start_date DESC, id DESC")->fetchAll();

$yearLevels = [
  '1' => '1st Year',
  '2' => '2nd Year',
  '3' => '3rd Year',
  '4' => '4th Year',
];
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
      <p class="text-muted">Add, edit, and manage course/subject records</p>
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
        <button type="button" class="tab-link" data-tab="courses">Manage Courses</button>
        <button type="button" class="tab-link" data-tab="terms">Manage Terms</button>
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
              <div class="row-grid cols-4">
                <div class="form-group">
                  <label>Course</label>
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
                    <?php foreach ($termsRecords as $term): ?>
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
                <button class="btn btn-primary" type="submit">Save Subject</button>
              </div>
            </div>
          </form>
        </div>

        <!-- Edit subjects -->
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
              <div class="row-grid cols-4">
                <div class="form-group">
                  <label>Course</label>
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
                    <?php foreach ($termsRecords as $term): ?>
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
                <button class="btn btn-primary" type="submit">Update Subject</button>
              </div>
            </div>
          </form>
        </div>

        <!-- Manage Courses -->
        <div class="tab-pane" data-pane="courses">
          <div class="form-box">
            <form action="../includes/course_process.php" method="POST">
              <input type="hidden" name="action" value="create">
              <div class="row-grid cols-2">
                <div class="form-group">
                  <label>Course Code *</label>
                  <input type="text" name="code" class="form-control" required>
                </div>
                <div class="form-group">
                  <label>Course Name *</label>
                  <input type="text" name="title" class="form-control" required>
                </div>
              </div>
              <div class="row-grid cols-1">
                <div class="form-group">
                  <label>Description</label>
                  <textarea name="description" class="form-control" rows="2"></textarea>
                </div>
              </div>
              <div class="row-grid cols-4">
                <div class="form-group">
                  <label>Status</label>
                  <select name="is_active" class="form-control">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                  </select>
                </div>
              </div>
              <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save Course</button>
              </div>
            </form>
          </div>
          <div class="card">
            <div class="card-body">
              <h3>Courses Table</h3>
              <table class="table table-striped table-bordered">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th width="120">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!$courses): ?>
                    <tr><td colspan="6">No courses found.</td></tr>
                  <?php else: $i = 1; foreach ($courses as $course): ?>
                    <tr>
                      <td><?= $i++; ?></td>
                      <td><?= htmlspecialchars($course['code'] ?? ''); ?></td>
                      <td><?= htmlspecialchars($course['title'] ?? ''); ?></td>
                      <td><?= htmlspecialchars($course['description'] ?? ''); ?></td>
                      <td><?= (!isset($course['is_active']) || $course['is_active']) ? 'Active' : 'Inactive'; ?></td>
                      <td class="actions">
                        <form action="../includes/course_process.php" method="POST" onsubmit="return confirm('Delete this course?');">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="<?= (int)$course['id']; ?>">
                          <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Manage terms -->
        <div class="tab-pane" data-pane="terms">
          <div class="form-box">
            <form action="../includes/term_process.php" method="POST">
              <input type="hidden" name="action" value="create">
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
            </form>
          </div>
          <div class="form-box" style="margin-top:16px;">
            <form action="../includes/term_process.php" method="POST">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" id="term-edit-id">
              <div class="row-grid cols-2">
                <div class="form-group">
                  <label>Semester *</label>
                  <select name="semester" id="term-edit-semester" class="form-control" required>
                    <option value="1">1st Semester</option>
                    <option value="2">2nd Semester</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>School Year *</label>
                  <input type="text" name="school_year" id="term-edit-school_year" class="form-control" placeholder="2025-2026" required>
                </div>
              </div>
              <div class="row-grid cols-3">
                <div class="form-group">
                  <label>Start Date</label>
                  <input type="date" name="start_date" id="term-edit-start_date" class="form-control">
                </div>
                <div class="form-group">
                  <label>End Date</label>
                  <input type="date" name="end_date" id="term-edit-end_date" class="form-control">
                </div>
                <div class="form-group">
                  <label>Status</label>
                  <select name="is_active" id="term-edit-is_active" class="form-control">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                  </select>
                </div>
              </div>
              <div class="form-actions">
                <button class="btn btn-primary" type="submit">Update Term</button>
              </div>
            </form>
          </div>
          <div class="card" style="margin-top:16px;">
            <div class="card-body">
              <h3>Terms Table</h3>
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
                  <?php if (!$termsRecords): ?>
                    <tr><td colspan="7">No terms found.</td></tr>
                  <?php else: $i = 1; foreach ($termsRecords as $termRow): ?>
                    <tr>
                      <td><?= $i++; ?></td>
                      <td><?= htmlspecialchars($termRow['term_name']); ?></td>
                      <td><?= htmlspecialchars($termRow['school_year']); ?></td>
                      <td><?= htmlspecialchars($termRow['start_date']); ?></td>
                      <td><?= htmlspecialchars($termRow['end_date']); ?></td>
                      <td><?= ($termRow['is_active'] ? 'Active' : 'Inactive'); ?></td>
                      <td class="actions">
                        <button
                          type="button"
                          class="btn btn-sm btn-warning term-btn-edit"
                          data-id="<?= $termRow['id']; ?>"
                          data-semester="<?= htmlspecialchars($termRow['semester']); ?>"
                          data-school_year="<?= htmlspecialchars($termRow['school_year']); ?>"
                          data-start_date="<?= htmlspecialchars($termRow['start_date']); ?>"
                          data-end_date="<?= htmlspecialchars($termRow['end_date']); ?>"
                          data-is_active="<?= htmlspecialchars($termRow['is_active']); ?>"
                        >Edit</button>
                        <form action="../includes/term_process.php" method="POST" onsubmit="return confirm('Delete this term?');">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="<?= $termRow['id']; ?>">
                          <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Subjects Information Table -->

    
    <div id="subjects-table-section">
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
                <th>Course</th>
                <th>Year Level</th>
                <th>Term</th>
                <th>Units</th>
                <th>Status</th>
                <th width="140">Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php $i=1; foreach ($result as $row): ?>
              <?php
                $termIsActive = null;
                if (!empty($row['term_id'])) {
                  if ($row['term_is_active'] === null) {
                    $termIsActive = null;
                  } else {
                    $termIsActive = (int)$row['term_is_active'];
                  }
                }
                $derivedActive = null;
                if ($row['term_id']) {
                  $derivedActive = ($termIsActive === null) ? null : ($termIsActive === 1);
                }
                if ($derivedActive === null) {
                  $derivedActive = (int)$row['is_active'] === 1;
                }
                $derivedActiveInt = $derivedActive ? 1 : 0;
              ?>
              <tr>
                <td><?= $i++; ?></td>
                <td><?= htmlspecialchars($row['subject_code']); ?></td>
                <td><?= htmlspecialchars($row['subject_title']); ?></td>
                <td><?= htmlspecialchars($courseLabels[$row['course_id']] ?? '--'); ?></td>
                <td><?= htmlspecialchars($yearLevels[$row['year_level']] ?? $row['year_level'] ?? '--'); ?></td>
                <td><?= htmlspecialchars($row['term_name'] ?? '--'); ?></td>
                <td><?= htmlspecialchars($row['units']); ?></td>
                <td><?= $derivedActive ? 'Active' : 'Inactive'; ?></td>
                <td class="actions">
                  <button
                    class="btn btn-sm btn-warning btn-edit"
                    data-id="<?= $row['id']; ?>"
                    data-subject_code="<?= htmlspecialchars($row['subject_code']); ?>"
                    data-subject_title="<?= htmlspecialchars($row['subject_title']); ?>"
                    data-units="<?= htmlspecialchars($row['units']); ?>"
                    data-description="<?= htmlspecialchars($row['description']); ?>"
                    data-course_id="<?= htmlspecialchars($row['course_id'] ?? ''); ?>"
                    data-year_level="<?= htmlspecialchars($row['year_level'] ?? ''); ?>"
                    data-term_id="<?= htmlspecialchars($row['term_id'] ?? ''); ?>"
                    data-is_active="<?= $derivedActiveInt; ?>"
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
    </div>

    <?php if (function_exists('renderCrudTabsScript')) { renderCrudTabsScript(); } ?>
    <script>
      (function(){
        function fillTermForm(btn) {
          var idEl = document.getElementById('term-edit-id');
          if (idEl) idEl.value = btn.dataset.id || '';
          var mappings = ['semester','school_year','start_date','end_date','is_active'];
          mappings.forEach(function(key){
            var field = document.getElementById('term-edit-' + key);
            if (!field) return;
            var value = btn.dataset[key] ?? '';
            if (field.tagName === 'SELECT' || field.tagName === 'INPUT') {
              field.value = value;
            }
          });
        }

        function initTermEditors() {
          var buttons = document.querySelectorAll('.term-btn-edit');
          buttons.forEach(function(btn){
            btn.addEventListener('click', function(){
              fillTermForm(btn);
              var pane = btn.closest('.tab-pane');
              if (pane) {
                window.scrollTo({ top: pane.offsetTop || 0, behavior: 'smooth' });
              }
            });
          });
        }

        function updateSubjectTableVisibility() {
          var section = document.getElementById('subjects-table-section');
          if (!section) return;
          var active = document.querySelector('#student-tabs .tab-link.active');
          var show = active && (active.dataset.tab === 'add' || active.dataset.tab === 'edit');
          section.style.display = show ? '' : 'none';
        }

        function initTabVisibility() {
          var tabs = document.querySelectorAll('#student-tabs .tab-link');
          tabs.forEach(function(tab){
            tab.addEventListener('click', function(){
              setTimeout(updateSubjectTableVisibility, 0);
            });
          });
          updateSubjectTableVisibility();
        }

        function init() {
          initTermEditors();
          initTabVisibility();
        }

        if (document.readyState === 'loading') {
          document.addEventListener('DOMContentLoaded', init);
        } else {
          init();
        }
      })();
    </script>
    <script src="../assets/js/admin.js"></script>

  </main>
  <?php include '../includes/footer.php'; ?>
</div>
</body>
</html>
