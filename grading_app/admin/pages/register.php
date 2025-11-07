<?php
require_once '../includes/init.php';
requireAdmin();

$msg = $err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $role  = trim($_POST['role'] ?? '');
    $first = trim($_POST['name_first'] ?? '');
    $last  = trim($_POST['name_last'] ?? '');
    $status = strtoupper(trim($_POST['status'] ?? 'ACTIVE'));

    if (!$email || !$role) {
        $err = 'Email and role are required.';
    } else {
        try {
            // Check if user exists
            $chk = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $chk->execute([$email]);
            $exists = $chk->fetchColumn();

            if ($exists) {
                // Update basic fields; do not overwrite password here
                $upd = $pdo->prepare('UPDATE users SET role = ?, name_first = ?, name_last = ?, status = ? WHERE id = ?');
                $upd->execute([$role, $first, $last, $status, $exists]);

                add_activity_log($pdo, $_SESSION['user']['id'] ?? null, 'UPDATE_USER', 'Updated user '.$email);
                $msg = 'User updated successfully.';
            } else {
                // Insert placeholder; password will be set by public verification flow
                $ins = $pdo->prepare('INSERT INTO users (email, role, name_first, name_last, status) VALUES (?, ?, ?, ?, ?)');
                $ins->execute([$email, $role, $first, $last, $status]);

                add_activity_log($pdo, $_SESSION['user']['id'] ?? null, 'ADD_USER', 'Added user '.$email);
                $msg = 'User added successfully.';
            }
        } catch (Exception $e) {
            $err = 'Error: '.$e->getMessage();
        }
    }
}
?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Register User</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
  </head>
  <body>
    <?php include '../includes/header.php'; ?>
    <div class="layout">
      <?php include '../includes/sidebar.php'; ?>
      <main class="content">
        <?php show_flash(); ?>
        <?php if ($err): ?>
          <div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>
        <?php if ($msg): ?>
          <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <div class="page-header">
          <h2>Register / Preload User</h2>
          <p class="muted">Create a user record so they can complete account setup via the public registration flow.</p>
        </div>

        <div class="card">
          <div class="card-body">
            <form method="post">
              <div class="row-grid cols-2">
                <div class="form-group">
                  <label>PTC Email *</label>
                  <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                  <label>Role *</label>
                  <select name="role" class="form-control" required>
                    <option value="">-- Select --</option>
                    <option value="student">Student</option>
                    <option value="professor">Professor</option>
                    <option value="registrar">Registrar</option>
                    <option value="mis">MIS</option>
                    <option value="admin">Admin</option>
                    <option value="super_admin">Super Admin</option>
                  </select>
                </div>
              </div>

              <div class="row-grid cols-2">
                <div class="form-group">
                  <label>First Name</label>
                  <input type="text" name="name_first" class="form-control">
                </div>
                <div class="form-group">
                  <label>Last Name</label>
                  <input type="text" name="name_last" class="form-control">
                </div>
              </div>

              <div class="row-grid cols-2">
                <div class="form-group">
                  <label>Status</label>
                  <select name="status" class="form-control">
                    <option value="ACTIVE" selected>ACTIVE</option>
                    <option value="INACTIVE">INACTIVE</option>
                  </select>
                </div>
              </div>

              <div class="form-actions">
                <button type="submit">Save</button>
                <a class="btn-link" href="./dashboard.php">Cancel</a>
              </div>
            </form>

            <div class="help-text" style="margin-top:1rem;">
              <small>
                Note: No password is set here. The user must visit
                <code><?= BASE_URL; ?>/register.php</code> to verify and set their password.
              </small>
            </div>
          </div>
        </div>
      </main>
    </div>
    <script src="../assets/js/admin.js"></script>
  </body>
</html>
