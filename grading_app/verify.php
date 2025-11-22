<?php
require_once 'core/config/config.php';
require_once 'core/auth/session.php';
require_once 'core/db/connection.php';

if($_SERVER['REQUEST_METHOD']!=='POST' || empty($_SESSION['pending_reg'])){ header('Location: register.php'); exit; }
$code = trim($_POST['code'] ?? '');
if($code !== ($_SESSION['pending_reg']['code'] ?? '')){ set_flash('error','Invalid code.'); header('Location: register.php'); exit; }

$email = $_SESSION['pending_reg']['email'];
$pass  = $_SESSION['pending_reg']['password'];
unset($_SESSION['pending_reg']);

$hash = password_hash($pass, PASSWORD_BCRYPT);

// Expect user row exists (MIS preload). If not, fallback to student.
$stmt = $pdo->prepare('SELECT id,role,name_first,name_last FROM users WHERE email=? LIMIT 1');
$stmt->execute([$email]);
$u = $stmt->fetch();
if(!$u){
  $ins = $pdo->prepare('INSERT INTO users(email,password_hash,role) VALUES(?,?,?)');
  $ins->execute([$email,$hash,'student']);
  $u = ['id'=>$pdo->lastInsertId(),'role'=>'student','name_first'=>'','name_last'=>''];
} else {
  $upd = $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?');
  $upd->execute([$hash,$u['id']]);
}

$_SESSION['user'] = [
  'id'=>$u['id'],'email'=>$email,'role'=>$u['role'],
  'name'=>trim(($u['name_first']??'').' '.($u['name_last']??'')),
];

if($u['role']==='admin' || $u['role']==='registrar'){ header('Location: admin/index.php'); exit; }
if($u['role']==='professor'){ header('Location: professor/index.php'); exit; }
header('Location: students/index.php'); exit;
