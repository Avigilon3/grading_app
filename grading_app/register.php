<?php
require_once 'core/config/config.php';
require_once 'core/auth/session.php';
$msg = $err = null;
if($_SERVER['REQUEST_METHOD']==='POST'){
  $email = trim($_POST['email'] ?? '');
  $pass  = trim($_POST['password'] ?? '');
  if(!$email || !$pass){ $err='Please fill in all fields.'; }
  else {
    require_once 'core/db/connection.php';
    // Must already exist from MIS preload
    $check = $pdo->prepare('SELECT email, first_name, last_name, role FROM users WHERE email=? LIMIT 1');
    $check->execute([$email]);
    $user = $check->fetch(PDO::FETCH_ASSOC);
    if(!$user){
      $err='No record found for this PTC email. Please contact MIS/Registrar.';
    } else {
      $_SESSION['pending_reg'] = [
        'email'=>$email,
        'password'=>$pass,
        'code'=>strval(rand(100000,999999)),
        'first_name'=>$user['first_name'] ?? '',
        'last_name'=>$user['last_name'] ?? '',
        'role'=>$user['role'] ?? 'student',
      ];
      $msg='We sent a verification code to your email. (Demo code: '.$_SESSION['pending_reg']['code'].')';
    }
  }
}
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Register</title></head><body>
<h2>Create an account</h2>
<?php if($err): ?><div style="color:#b00020;"><?= htmlspecialchars($err) ?></div><?php endif; ?>
<?php if($msg): ?>
  <div style="color:green;"><?= $msg ?></div>
  <form method="post" action="verify.php">
    <label>Enter Code</label><br><input type="text" name="code" required><br>
    <button type="submit">Verify</button>
  </form>
<?php else: ?>
  <form method="post">
    <label>PTC Email</label><br><input type="email" name="email" required><br>
    <label>Password</label><br><input type="password" name="password" required><br>
    <button type="submit">Register</button>
  </form>
<?php endif; ?>
<div><a href="login.php">Back to login</a></div>
</body></html>
