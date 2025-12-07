<?php
require_once 'core/config/config.php';
require_once 'core/auth/session.php';
require_once 'core/db/connection.php';

if (empty($_SESSION['pending_reg'])) {
  header('Location: register.php');
  exit;
}

$pending = $_SESSION['pending_reg'];
$codeInput = '';
$error = null;

if($_SERVER['REQUEST_METHOD']==='POST'){
  $codeInput = trim($_POST['code'] ?? '');

  $stmt = $pdo->prepare('SELECT * FROM user_verification_codes WHERE id = ? AND user_id = ? AND purpose = ? LIMIT 1');
  $stmt->execute([$pending['code_id'] ?? 0, $pending['user_id'] ?? 0, 'register']);
  $row = $stmt->fetch();

  $now = date('Y-m-d H:i:s');
  $maxAttempts = 5;
  $isExpired = !$row || $row['expires_at'] < $now || $row['attempts'] >= $maxAttempts;
  $isValid = $row && !$isExpired && password_verify($codeInput, $row['code_hash']);

  if (!$isValid) {
    if ($row) {
      $pdo->prepare('UPDATE user_verification_codes SET attempts = attempts + 1 WHERE id = ?')->execute([$row['id']]);
    }
    set_flash('error','Invalid or expired code. Please request a new one.');
    header('Location: register.php');
    exit;
  }

  // Apply the pending password and mark email verified
  $upd = $pdo->prepare('UPDATE users SET password_hash = ?, email_verified_at = NOW() WHERE id = ?');
  $upd->execute([$row['new_password_hash'], $pending['user_id']]);

  // Clean up used code
  $pdo->prepare('DELETE FROM user_verification_codes WHERE id = ?')->execute([$row['id']]);
  unset($_SESSION['pending_reg']);

  // Reload user for session data
  $userStmt = $pdo->prepare('SELECT id, email, role, first_name, last_name FROM users WHERE id = ? LIMIT 1');
  $userStmt->execute([$row['user_id']]);
  $u = $userStmt->fetch();

  $_SESSION['user'] = [
    'id'=>$u['id'],
    'email'=>$u['email'],
    'role'=>$u['role'],
    'name'=>trim(($u['first_name']??'').' '.($u['last_name']??'')),
  ];

  if($u['role']==='admin' || $u['role']==='registrar'){ header('Location: admin/index.php'); exit; }
  if($u['role']==='professor'){ header('Location: professor/index.php'); exit; }
  header('Location: student/index.php'); exit;
}
