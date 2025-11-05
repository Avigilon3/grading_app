<?php
<<<<<<< HEAD
// Database Management Page
require_once __DIR__ . '/../../core/config/config.php';
require_once __DIR__ . '/../../core/auth/session.php';
require_once __DIR__ . '/../../core/auth/guards.php';
// Require admin (or registrar) login
requireLogin();
if (!in_array($_SESSION['user']['role'] ?? '', ['admin', 'registrar'])) {
  http_response_code(403);
  echo 'Unauthorized.';
  exit;
}
require_once __DIR__ . '/../includes/init.php';
// Sample database management actions
$actions = [
    'Backup Database' => BASE_URL . '/admin/actions/backup_database.php',
    'Optimize Database' => BASE_URL . '/admin/actions/optimize_database.php',
    'Repair Database' => BASE_URL . '/admin/actions/repair_database.php',
    ];
?>
<!doctype html>
<html>      
<head>
=======
require_once __DIR__ . '/../includes/init.php';
requireAdmin();


$students = $pdo->query("SELECT id, student_id, ptc_email, first_name, middle_name, last_name, year_level, section, status FROM students ORDER BY last_name, first_name LIMIT 10")->fetchAll();

$professors = $pdo->query("SELECT id, professor_id, ptc_email, first_name, middle_name, last_name, is_active FROM professors ORDER BY last_name, first_name LIMIT 10")->fetchAll();

$subjects = $pdo->query("SELECT id, subject_code, subject_title, units, is_active FROM subjects ORDER BY subject_code LIMIT 10")->fetchAll();

$terms = $pdo->query("SELECT id, term_name, school_year, start_date, end_date, is_active FROM terms ORDER BY start_date DESC, id DESC LIMIT 10")->fetchAll();

$sections = $pdo->query(
  "SELECT s.id, s.section_name, s.schedule, s.is_active,
          t.term_name,
          sub.subject_code, sub.subject_title,
          p.first_name, p.middle_name, p.last_name
     FROM sections s
LEFT JOIN terms t ON t.id = s.term_id
LEFT JOIN subjects sub ON sub.id = s.subject_id
LEFT JOIN professors p ON p.id = s.assigned_professor_id
 ORDER BY s.section_name
 LIMIT 10"
)->fetchAll();
?>
<!doctype html>
<html>
  <head>
>>>>>>> main
  <meta charset="utf-8">
  <title>Database Management</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<<<<<<< HEAD
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="content">
        <?php show_flash(); ?>
        <h1>Database Management</h1>
        <p class="flash info">Perform database maintenance tasks below.</p>
        <ul>
            <?php foreach ($actions as $action => $url): ?>
                <li><a href="<?= htmlspecialchars($url) ?>"><?= htmlspecialchars($action) ?></a></li>
            <?php endforeach; ?>
        </ul> 
    </main>
</div>
<script src="../assets/js/admin.js"></script>

=======
<?php include __DIR__.'/../includes/header.php'; ?>
<div class="layout">
  <?php include __DIR__.'/../includes/sidebar.php'; ?>
  <main class="content">
    <?php show_flash(); ?>

    <div class="page-header">
      <h2>Database Management Overview</h2>
    </div>

    <?php if (isset($_GET['msg'])): ?>
      <div class="alert alert-success">
        <?= htmlspecialchars($_GET['msg']) ?>
      </div>
    <?php endif; ?>

    <div class="row-grid cols-1 mb-16">
      <div>
        <a class="button" href="students.php">Manage Students</a>
        <a class="button" href="professors.php">Manage Professors</a>
        <a class="button" href="subjects.php">Manage Subjects</a>
        <a class="button" href="sections.php">Manage Sections</a>
        <a class="button" href="terms.php">Manage Semesters</a>
      </div>
    </div>

    <div class="row-grid cols-2">
      <div class="card">
        <div class="card-body">
          <div class="page-header compact">
            <h2>Students (latest)</h2>
          </div>
          <table class="table table-striped table-bordered">
            <thead>
              <tr>
                <th>#</th>
                <th>Student ID</th>
                <th>Name</th>
                <th>Year</th>
                <th>Section</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
            <?php if (!$students): ?>
              <tr><td colspan="6">No students found.</td></tr>
            <?php else: $i=1; foreach ($students as $s): ?>
              <tr>
                <td><?= $i++; ?></td>
                <td><?= htmlspecialchars($s['student_id']); ?></td>
                <td><?= htmlspecialchars($s['last_name'] . ', ' . $s['first_name'] . ' ' . ($s['middle_name'] ?? '')); ?></td>
                <td><?= htmlspecialchars($s['year_level']); ?></td>
                <td><?= htmlspecialchars($s['section']); ?></td>
                <td><?= htmlspecialchars($s['status']); ?></td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <div class="page-header compact">
            <h2>Professors (latest)</h2>
          </div>
          <table class="table table-striped table-bordered">
            <thead>
              <tr>
                <th>#</th>
                <th>Professor ID</th>
                <th>Name</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
            <?php if (!$professors): ?>
              <tr><td colspan="4">No professors found.</td></tr>
            <?php else: $i=1; foreach ($professors as $p): ?>
              <tr>
                <td><?= $i++; ?></td>
                <td><?= htmlspecialchars($p['professor_id']); ?></td>
                <td><?= htmlspecialchars($p['last_name'] . ', ' . $p['first_name'] . ' ' . ($p['middle_name'] ?? '')); ?></td>
                <td><?= ($p['is_active'] ? 'Active' : 'Inactive'); ?></td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="row-grid cols-2">
      <div class="card">
        <div class="card-body">
          <div class="page-header compact">
            <h2>Subjects (latest)</h2>
          </div>
          <table class="table table-striped table-bordered">
            <thead>
              <tr>
                <th>#</th>
                <th>Code</th>
                <th>Title</th>
                <th>Units</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
            <?php if (!$subjects): ?>
              <tr><td colspan="5">No subjects found.</td></tr>
            <?php else: $i=1; foreach ($subjects as $sub): ?>
              <tr>
                <td><?= $i++; ?></td>
                <td><?= htmlspecialchars($sub['subject_code']); ?></td>
                <td><?= htmlspecialchars($sub['subject_title']); ?></td>
                <td><?= htmlspecialchars($sub['units']); ?></td>
                <td><?= ($sub['is_active'] ? 'Active' : 'Inactive'); ?></td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <div class="page-header compact">
            <h2>Semesters (latest)</h2>
          </div>
          <table class="table table-striped table-bordered">
            <thead>
              <tr>
                <th>#</th>
                <th>Term Name</th>
                <th>School Year</th>
                <th>Start</th>
                <th>End</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
            <?php if (!$terms): ?>
              <tr><td colspan="6">No semesters found.</td></tr>
            <?php else: $i=1; foreach ($terms as $t): ?>
              <tr>
                <td><?= $i++; ?></td>
                <td><?= htmlspecialchars($t['term_name']); ?></td>
                <td><?= htmlspecialchars($t['school_year']); ?></td>
                <td><?= htmlspecialchars($t['start_date']); ?></td>
                <td><?= htmlspecialchars($t['end_date']); ?></td>
                <td><?= ($t['is_active'] ? 'Active' : 'Inactive'); ?></td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="row-grid cols-1">
      <div class="card">
        <div class="card-body">
          <div class="page-header compact">
            <h2>Sections (latest)</h2>
          </div>
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
              </tr>
            </thead>
            <tbody>
            <?php if (!$sections): ?>
              <tr><td colspan="7">No sections found.</td></tr>
            <?php else: $i=1; foreach ($sections as $sec): ?>
              <tr>
                <td><?= $i++; ?></td>
                <td><?= htmlspecialchars($sec['section_name']); ?></td>
                <td><?= htmlspecialchars($sec['term_name'] ?? ''); ?></td>
                <td><?= htmlspecialchars(($sec['subject_code'] ? $sec['subject_code'].' - ' : '') . ($sec['subject_title'] ?? '')); ?></td>
                <td><?= htmlspecialchars($sec['schedule']); ?></td>
                <td><?= htmlspecialchars(trim(($sec['last_name'] ?? '') . ', ' . ($sec['first_name'] ?? '') . ' ' . ($sec['middle_name'] ?? ''))); ?></td>
                <td><?= ($sec['is_active'] ? 'Active' : 'Inactive'); ?></td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <script src="../assets/js/admin.js"></script>
  </main>
  <?php include '../includes/footer.php'; ?>
</div>
>>>>>>> main
</body>
</html>
