<?php   
// Report Management Page
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
// Sample report management data
$report_data = [
    ['report' => 'Student Performance Report', 'generated_on' => '2024-06-01', 'status' => 'Available'],
    ['report' => 'Section Attendance Report', 'generated_on' => '2024-05-28', 'status' => 'Processing'],
    ['report' => 'Grade Distribution Report', 'generated_on' => '2024-05-25', 'status' => 'Available'],
];
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Report Management</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="content">
        <?php show_flash(); ?>
        <h1>Report Management</h1>
        <p class="flash info">Manage generated reports below.</p>
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="text-align:left;">
                    <th>Report</th>
                    <th>Generated On</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report_data as $data): ?>
                    <tr>
                        <td><?= htmlspecialchars($data['report']) ?></td>
                        <td><?= htmlspecialchars($data['generated_on']) ?></td>
                        <td><?= htmlspecialchars($data['status']) ?></td>
                        <td><a class="nav-item" href="report_management_view.php?report=<?= urlencode($data['report']) ?>">View</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table> 
    </main>
</div>
<script src="../assets/js/admin.js"></script>
</body>
</html>