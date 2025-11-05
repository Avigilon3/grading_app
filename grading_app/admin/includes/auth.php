<?php
require_once __DIR__ . '/../../core/auth/session.php';
require_once __DIR__ . '/../../core/auth/guards.php';

// Require admin login
requireLogin();
if (($_SESSION['user']['role'] ?? '') !== 'admin') {
  http_response_code(403);
  echo 'Unauthorized.';
  exit;
}
?>
