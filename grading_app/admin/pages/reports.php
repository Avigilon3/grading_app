<?php
require_once '../includes/init.php';
requireAdmin();

// Simple CSV export handler (students, professors, sections, subjects, activity_logs)
function csv_out(array $rows, array $headers, string $filename) {
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="'.$filename.'"');
  $out = fopen('php://output', 'w');
  fputcsv($out, $headers);
  foreach ($rows as $r) {
    $line = [];
    foreach ($headers as $h) { $line[] = $r[$h] ?? ''; }
    fputcsv($out, $line);
  }
  fclose($out);
  exit;
}

$export = $_GET['export'] ?? '';
if ($export) {
  if ($export === 'students') {
    $rows = $pdo->query('SELECT student_id, ptc_email, last_name, first_name, middle_name, year_level, section, status FROM students ORDER BY last_name, first_name')->fetchAll();
    csv_out($rows, ['student_id','ptc_email','last_name','first_name','middle_name','year_level','section','status'], 'students.csv');
  }
  if ($export === 'professors') {
    $rows = $pdo->query('SELECT professor_id, ptc_email, last_name, first_name, middle_name, is_active FROM professors ORDER BY last_name, first_name')->fetchAll();
    csv_out($rows, ['professor_id','ptc_email','last_name','first_name','middle_name','is_active'], 'professors.csv');
  }
  if ($export === 'sections') {
    $rows = $pdo->query('SELECT id, section_name, schedule, is_active FROM sections ORDER BY section_name')->fetchAll();
    csv_out($rows, ['id','section_name','schedule','is_active'], 'sections.csv');
  }
  if ($export === 'subjects') {
    $rows = $pdo->query('SELECT subject_code, subject_title, units, is_active FROM subjects ORDER BY subject_code')->fetchAll();
    csv_out($rows, ['subject_code','subject_title','units','is_active'], 'subjects.csv');
  }
  if ($export === 'activity') {
    $rows = $pdo->query('SELECT id, user_id, action, details, ip, created_at FROM activity_logs ORDER BY id DESC LIMIT 500')->fetchAll();
    csv_out($rows, ['id','user_id','action','details','ip','created_at'], 'activity_logs.csv');
  }
}

// Summary widgets
$summary = [
  'students'   => (int)$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn(),
  'professors' => (int)$pdo->query('SELECT COUNT(*) FROM professors')->fetchColumn(),
  'sections'   => (int)$pdo->query('SELECT COUNT(*) FROM sections')->fetchColumn(),
  'subjects'   => (int)$pdo->query('SELECT COUNT(*) FROM subjects')->fetchColumn(),
];

// Recent activities
$logs = $pdo->query('SELECT a.id, a.action, a.details, a.ip, a.created_at, u.email AS user_email
                      FROM activity_logs a
                 LEFT JOIN users u ON u.id = a.user_id
                  ORDER BY a.id DESC LIMIT 20')->fetchAll();
?>

<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Reports</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
  </head>
  <body>
    <?php include '../includes/header.php'; ?>
    <div class="layout">
      <?php include '../includes/sidebar.php'; ?>
      <main class="content">
        <?php show_flash(); ?>

        <div class="page-header">
          <h2>Reports</h2>
        </div>

        <div class="row-grid cols-4">
          <div class="card"><div class="card-body"><strong>Students</strong><div><?= (int)$summary['students'] ?></div></div></div>
          <div class="card"><div class="card-body"><strong>Professors</strong><div><?= (int)$summary['professors'] ?></div></div></div>
          <div class="card"><div class="card-body"><strong>Sections</strong><div><?= (int)$summary['sections'] ?></div></div></div>
          <div class="card"><div class="card-body"><strong>Subjects</strong><div><?= (int)$summary['subjects'] ?></div></div></div>
        </div>

        <div class="card">
          <div class="card-body">
            <div class="page-header compact"><h2>Quick Exports</h2></div>
            <div class="row-grid cols-1">
              <a class="button" href="?export=students">Export Students CSV</a>
              <a class="button" href="?export=professors">Export Professors CSV</a>
              <a class="button" href="?export=sections">Export Sections CSV</a>
              <a class="button" href="?export=subjects">Export Subjects CSV</a>
              <a class="button" href="?export=activity">Export Activity Logs CSV</a>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-body">
            <div class="page-header compact"><h2>Recent Activity</h2></div>
            <table class="table table-striped table-bordered">
              <thead>
                <tr>
                  <th>#</th>
                  <th>User</th>
                  <th>Action</th>
                  <th>Details</th>
                  <th>IP</th>
                  <th>When</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$logs): ?>
                  <tr><td colspan="6">No activity yet.</td></tr>
                <?php else: foreach ($logs as $row): ?>
                  <tr>
                    <td><?= (int)$row['id']; ?></td>
                    <td><?= htmlspecialchars($row['user_email'] ?: 'N/A'); ?></td>
                    <td><?= htmlspecialchars($row['action']); ?></td>
                    <td><?= htmlspecialchars((string)$row['details']); ?></td>
                    <td><?= htmlspecialchars((string)$row['ip']); ?></td>
                    <td><?= htmlspecialchars($row['created_at']); ?></td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <script src="../assets/js/admin.js"></script>
      </main>
    </div>
  </body>
  <?php include '../includes/footer.php'; ?>
</html>
