<?php
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
