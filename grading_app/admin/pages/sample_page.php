

//nuto? -bethel
<?php
require_once __DIR__ . '/../../core/config/config.php';
require_once __DIR__ . '/../../core/auth/session.php';
require_once __DIR__ . '/../../core/auth/guards.php';

requireLogin();
if (!in_array($_SESSION['user']['role'] ?? '', ['admin', 'registrar'])) {
  http_response_code(403);
  echo 'Unauthorized.';
  exit;
}

require_once __DIR__ . '/../includes/init.php';

$stats = ['sections' => 12, 'professors' => 4, 'students' => 128, 'edit_requests' => 3];
?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Sample Admin Page</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
  </head>
  <body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <div class="layout">
      <?php include __DIR__ . '/../includes/sidebar.php'; ?>
      <main class="content">
        <?php show_flash(); ?>
        <h1>Sample Admin Page</h1>
        <p>This sample page demonstrates wiring to the existing admin assets (CSS/JS) and includes.</p>

        <div class="flash info">Example counts (server-side):</div>
        <ul>
          <li>Sections: <strong><?= (int)$stats['sections'] ?></strong></li>
          <li>Professors: <strong><?= (int)$stats['professors'] ?></strong></li>
          <li>Students: <strong><?= (int)$stats['students'] ?></strong></li>
          <li>Pending Edit Requests: <strong><?= (int)$stats['edit_requests'] ?></strong></li>
        </ul>

        <p>Below badges are updated via the JS helper exposed at <code>window.admin.updateCounts()</code>.</p>
      </main>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script>
      // Example: push server-side counts to the client-side badge updater
      (function () {
        if (window.admin && typeof window.admin.updateCounts === 'function') {
          window.admin.updateCounts({ edit_requests: <?= (int)$stats['edit_requests'] ?>, submissions: 5 });
        }
      })();
    </script>
  </body>
</html>
