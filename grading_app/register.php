<?php
require_once 'core/config/config.php';
require_once 'core/auth/session.php';
require_once 'core/helpers/mailer.php';
$flashErr = get_flash('error');
$msg = $err = null;
if($_SERVER['REQUEST_METHOD']==='POST'){
  $email = trim($_POST['email'] ?? '');
  $pass  = trim($_POST['password'] ?? '');
  $allowedDomain = '@paterostechnologicalcollege.edu.ph';

  if(!$email || !$pass){
    $err='Please fill in all fields.';
  } elseif (!preg_match('/@paterostechnologicalcollege\.edu\.ph$/i', $email)) {
    $err='Please use your institutional email ('.$allowedDomain.').';
  } else {
    require_once 'core/db/connection.php';

    // Must already exist from MIS preload and be a student
    $check = $pdo->prepare('SELECT id, email, first_name, last_name, role, status, email_verified_at FROM users WHERE email=? LIMIT 1');
    $check->execute([$email]);
    $user = $check->fetch(PDO::FETCH_ASSOC);

    if(!$user){
      $err='No record found for this PTC email. Please contact MIS/Registrar.';
    } elseif (strtolower($user['role'] ?? '') !== 'student') {
      $err='Only student accounts can be registered here.';
    } elseif (strtoupper($user['status'] ?? '') === 'INACTIVE') {
      $err='Your account is inactive. Please contact MIS/Registrar.';
    } elseif (!empty($user['email_verified_at'])) {
      $err='This email is already verified. You can log in.';
    } else {
      // Clear any previous pending codes for this user/purpose
      $pdo->prepare('DELETE FROM user_verification_codes WHERE user_id = ? AND purpose = ?')->execute([$user['id'], 'register']);

      $code = strval(random_int(100000, 999999));
      $codeHash = password_hash($code, PASSWORD_DEFAULT);
      $newPassHash = password_hash($pass, PASSWORD_BCRYPT);

      $ins = $pdo->prepare('INSERT INTO user_verification_codes (user_id, code_hash, new_password_hash, purpose, attempts, expires_at, created_at) VALUES (?,?,?,?,0, DATE_ADD(NOW(), INTERVAL 15 MINUTE), NOW())');
      $ins->execute([$user['id'], $codeHash, $newPassHash, 'register']);
      $codeId = $pdo->lastInsertId();

      $_SESSION['pending_reg'] = [
        'user_id'=>$user['id'],
        'code_id'=>$codeId,
        'email'=>$email,
      ];

      $sent = send_verification_code_email($email, $code, 'Student registration');
      $msg = 'We sent a verification code to your institutional email.';
      // In local/dev we log the code; surface it only for local to help QA
      if ((getenv('APP_ENV') ?: '') === 'local') {
        $msg .= ' (Dev code: '.$code.')';
      } elseif(!$sent) {
        $msg .= ' If you do not receive it, please contact MIS.';
      }
    }
  }
}
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Register</title></head><body>
<h2>Create an account</h2>
<?php if($err): ?><div style="color:#b00020;"><?= htmlspecialchars($err) ?></div><?php endif; ?>
<?php if($flashErr): ?><div style="color:#b00020;"><?= htmlspecialchars($flashErr) ?></div><?php endif; ?>
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
