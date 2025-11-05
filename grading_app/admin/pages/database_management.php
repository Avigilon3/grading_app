<?php
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
  <meta charset="utf-8">
  <title>Database Management</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
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

</body>
</html>
