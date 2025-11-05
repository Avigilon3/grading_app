<?php
<<<<<<< HEAD
// Grading Management Page
require_once __DIR__ . '/../../core/config/config.php';
require_once __DIR__ . '/../../core/auth/session.php';
require_once __DIR__ . '/../../core/auth/guards.php';
// Require admin (or registrar) login
requireLogin();
if (!in_array($_SESSION['user']['role'] ?? '', ['admin', 'registrar
'])) {
  http_response_code(403);
  echo 'Unauthorized.';
  exit;
}
require_once __DIR__ . '/../includes/init.php';
// Sample grading management data
$grading_data = [
    ['section' => 'BSIT-1A', 'subject' => 'Programming 1', 'status' => 'Submitted'],
    ['section' => 'BSIT-2B', 'subject' => 'Data Structures', 'status' => 'Pending'],
    ['section' => 'BSCS-3C', 'subject' => 'Algorithms', 'status' => 'Submitted'],
]; 
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Grading Management</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="content">
        <?php show_flash(); ?>
        <h1>Grading Management</h1>
        <p class="flash info">Manage grading submissions for different sections below.</p>
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="text-align:left;">
                    <th>Section</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grading_data as $data): ?>
                    <tr>
                        <td><?= htmlspecialchars($data['section']) ?></td>
                        <td><?= htmlspecialchars($data['subject']) ?></td>
                        <td><?= htmlspecialchars($data['status']) ?></td>
                        <td><a class="nav-item" href="grading_management_view.php?section=<?= urlencode($data['section']) ?>">View</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table> 
    </main>
</div>
<script src="../assets/js/admin.js"></script>

</body>

</html>
=======
require_once __DIR__ . '/../includes/init.php';
requireAdmin();


        //dito maglagay if may need ifetch sa database

?>

<!DOCTYPE html>
<html lang="en">
<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
      <title>Grading Management</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<?php include __DIR__.'/../includes/header.php'; ?>
<body>
    <div class="layout">
        <?php include __DIR__.'/../includes/sidebar.php'; ?>
        <main class="content">

            //lagay content here



        </main>
    </div>
</body>
</html>
>>>>>>> main
