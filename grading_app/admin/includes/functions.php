<?php
function flash(string $msg, string $type='info') {
  $_SESSION['flash'] = ['msg'=>$msg, 'type'=>$type];
}
function show_flash() {
  if (!empty($_SESSION['flash'])) {
    $f = $_SESSION['flash'];
    echo '<div class="flash '.$f['type'].'">'.htmlspecialchars($f['msg']).'</div>';
    unset($_SESSION['flash']);
  }
}
function audit(PDO $pdo, int $adminId, string $action, array $meta = []) {
  $stmt = $pdo->prepare("INSERT INTO activity_logs (admin_id, action, meta) VALUES (?,?,?)");
  $stmt->execute([$adminId, $action, $meta ? json_encode($meta) : null]);
}
function csrf_token() {
  if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
  return $_SESSION['csrf'];
}
function csrf_check() {
  if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '')) {
    http_response_code(400);
    echo "Invalid CSRF token.";
    exit;
  }
}
