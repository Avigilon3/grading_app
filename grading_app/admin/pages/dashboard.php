<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
// Demo stats — replace with real queries later
$stats = ['sections'=>0,'professors'=>0,'students'=>0,'edit_requests'=>0];
?>
<!doctype html><html><head>
  <meta charset="utf-8"><title>Dashboard</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head><body>
<?php include __DIR__.'/../includes/header.php'; ?>
<div class="layout">
  <?php include __DIR__.'/../includes/sidebar.php'; ?>
  <main class="content">
    <?php show_flash(); ?>
    <h1>Dashboard</h1>
    <div class="flash info">This is a starter dashboard. We’ll wire real counts later.</div>
    <ul>
      <li>Sections: <strong><?= (int)$stats['sections'] ?></strong></li>
      <li>Professors: <strong><?= (int)$stats['professors'] ?></strong></li>
      <li>Students: <strong><?= (int)$stats['students'] ?></strong></li>
      <li>Pending Edit Requests: <strong><?= (int)$stats['edit_requests'] ?></strong></li>
    </ul>
  </main>
</div>
<script src="../assets/js/admin.js"></script>
</body></html>
