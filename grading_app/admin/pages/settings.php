<?php
<<<<<<< HEAD
// Settings Page
require_once __DIR__ . '/../../core/config/config.php';
require_once __DIR__ . '/../../core/auth/session.php';
?>
<div class="layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="content">
        <?php show_flash(); ?>
        <h1>Settings</h1>
        <p class="flash info">Manage application settings below.</p>
        <form method="post" action="settings.php">
            <label for="site_name">Site Name:</label>
            <input type="text" id="site_name" name="site_name" value="<?= htmlspecialchars($site_name ?? '') ?>">
            <br>
            <label for="admin_email">Admin Email:</label>
            <input type="email" id="admin_email" name="admin_email" value="<?= htmlspecialchars($admin_email ?? '') ?>">
            <br>
            <input type="submit" value="Save Settings">
        </form>
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
