<?php
require_once __DIR__ . '/core/config/config.php';
require_once __DIR__ . '/core/auth/session.php';

$err = null;
if($_SERVER['REQUEST_METHOD']==='POST'){
  $email = trim($_POST['email'] ?? '');
  $pass  = trim($_POST['password'] ?? '');
  if($email && $pass){
    require_once __DIR__ . '/core/db/connection.php';
    $stmt = $pdo->prepare('SELECT id,email,password_hash,role,name_first,name_last FROM users WHERE email=? LIMIT 1');
    $stmt->execute([$email]);
    $u = $stmt->fetch();
    if($u && password_verify($pass, $u['password_hash'] ?? '')){
      $_SESSION['user'] = [
        'id'=>$u['id'],'email'=>$u['email'],'role'=>$u['role'],
        'name'=>trim(($u['name_first']??'').' '.($u['name_last']??'')),
      ];
      if($u['role']==='admin' || $u['role']==='registrar'){ header('Location: ' . BASE_URL . '/admin/index.php'); exit; }
      if($u['role']==='professor'){ header('Location: ' . BASE_URL . '/professor/index.php'); exit; }
      if($u['role']==='student'){ header('Location: ' . BASE_URL . '/student/index.php'); exit; }

      header('Location: login.php'); exit;
    } else { $err = 'Invalid email or password.'; }
  } else { $err = 'Please fill in all fields.'; }
}
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Login</title></head><body>
<h2>Login</h2>
<?php if($err): ?><div style="color:#b00020;"><?= htmlspecialchars($err) ?></div><?php endif; ?>
<form method="post">
  <label>Email</label><br><input type="email" name="email" required><br>
  <label>Password</label><br><input type="password" name="password" required><br>
  <button type="submit">Sign in</button>
</form>
<div><a href="register.php">Create an account</a> · <a href="forgot_password.php">Forgot password?</a></div>
</body></html>
