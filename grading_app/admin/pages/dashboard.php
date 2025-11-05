<?php
require_once '../includes/init.php';
requireAdmin();

// pang admin (or registrar) access to
requireLogin();
if (!in_array($_SESSION['user']['role'] ?? '', ['admin', 'registrar'])) {
  http_response_code(403);
  echo 'Unauthorized.';
  exit;
}

$stats = ['sections'=>0,'professors'=>0,'students'=>0,'edit_requests'=>0];
?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
      <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<?php include '../includes/header.php'; ?>

<body>
<div class="layout">
  <?php include '../includes/sidebar.php'; ?>
  <main class="content">
    <?php show_flash(); ?>
    <h1>Dashboard</h1>
    <div class="flash info">counts here</div>
    <ul>
      <li>Sections: <strong><?= (int)$stats['sections'] ?></strong></li>
      <li>Professors: <strong><?= (int)$stats['professors'] ?></strong></li>
      <li>Students: <strong><?= (int)$stats['students'] ?></strong></li>
      <li>Pending Edit Requests: <strong><?= (int)$stats['edit_requests'] ?></strong></li>
    </ul>
  </main>
</div>
<script src="../assets/js/admin.js"></script>
</body>
</html>
