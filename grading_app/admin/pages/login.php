<?php
require_once __DIR__ . '/../includes/init.php';

// Simple logout handler
if (isset($_GET['logout'])) {
  if (!empty($_SESSION['admin'])) audit($pdo, $_SESSION['admin']['id'], 'logout');
  session_destroy();

  // Build a path like /.../admin/pages/login.php (relative to the current script)
  $dir = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
  header("Location: {$dir}/login.php");
  exit;
}

// Handle login POST
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $email = trim($_POST['email'] ?? '');
  $pass  = $_POST['password'] ?? '';

  $stmt = $pdo->prepare("SELECT * FROM admins WHERE email=? AND status='active' LIMIT 1");
  $stmt->execute([$email]);
  $admin = $stmt->fetch();
  if ($admin && password_verify($pass, $admin['password_hash'])) {
    $_SESSION['admin'] = ['id'=>$admin['id'], 'name'=>$admin['name'], 'role'=>$admin['role']];
    audit($pdo, $admin['id'], 'login');

    // Same trick for redirect after login
    $dir = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    header("Location: {$dir}/dashboard.php");
    exit;
  } else {
    flash('Invalid credentials','error');
  }
}
?>
<!doctype html><html><head>
  <meta charset="utf-8"><title>Admin Login</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head><body>
<?php include __DIR__.'/../includes/header.php'; ?>
<div class="layout">
  <?php /* no sidebar on login */ ?>
  <main class="content" style="grid-column: 1 / span 2; max-width:560px; margin:40px auto;">
    <?php show_flash(); ?>
    <h1>Login</h1>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <label>Email <input type="email" name="email" required></label>
      <label>Password <input type="password" name="password" required></label>
      <button type="submit">Login</button>
    </form>
    <p>No account? <a href="register.php">Create one</a></p>
  </main>
</div>
<script src="../assets/js/admin.js"></script>
</body></html>
