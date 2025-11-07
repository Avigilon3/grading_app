<?php
require_once '../includes/init.php';
requireAdmin();

// Basic stats for dashboard widgets
try {
  $stats = [
    'sections'       => (int)$pdo->query('SELECT COUNT(*) FROM sections')->fetchColumn(),
    'professors'     => (int)$pdo->query('SELECT COUNT(*) FROM professors')->fetchColumn(),
    'students'       => (int)$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn(),
    'edit_requests'  => (int)$pdo->query("SELECT COUNT(*) FROM edit_requests WHERE status='pending'")->fetchColumn(),
  ];
} catch (Exception $e) {
  $stats = ['sections'=>0,'professors'=>0,'students'=>0,'edit_requests'=>0];
}
?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
      <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="layout">
  <?php include '../includes/sidebar.php'; ?>
  <main class="content">
    <?php show_flash(); ?>
    <div class="page-header">
      <h2>Dashboard</h2>
      <p class="muted">Welcome, <?= htmlspecialchars(adminCurrentName()); ?>.</p>
    </div>

    <div class="row-grid cols-4">
      <div class="card"><div class="card-body"><strong>Sections</strong><div><?= (int)$stats['sections'] ?></div></div></div>
      <div class="card"><div class="card-body"><strong>Professors</strong><div><?= (int)$stats['professors'] ?></div></div></div>
      <div class="card"><div class="card-body"><strong>Students</strong><div><?= (int)$stats['students'] ?></div></div></div>
      <div class="card"><div class="card-body"><strong>Pending Edit Requests</strong><div><?= (int)$stats['edit_requests'] ?></div></div></div>
    </div>

    <?php
      $recent = [];
      try {
        $q = $pdo->query('SELECT a.id, a.action, a.details, a.created_at, u.email AS user_email
                           FROM activity_logs a LEFT JOIN users u ON u.id = a.user_id
                           ORDER BY a.id DESC LIMIT 10');
        $recent = $q->fetchAll();
      } catch (Exception $e) { /* ignore */ }
    ?>

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
              <th>When</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$recent): ?>
              <tr><td colspan="5">No recent activity.</td></tr>
            <?php else: foreach ($recent as $r): ?>
              <tr>
                <td><?= (int)$r['id']; ?></td>
                <td><?= htmlspecialchars($r['user_email'] ?: 'N/A'); ?></td>
                <td><?= htmlspecialchars($r['action']); ?></td>
                <td><?= htmlspecialchars((string)$r['details']); ?></td>
                <td><?= htmlspecialchars($r['created_at']); ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>
<script src="../assets/js/admin.js"></script>
</body>
</html>
