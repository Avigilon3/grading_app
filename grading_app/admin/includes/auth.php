<?php
function isLoggedIn(): bool {
  return !empty($_SESSION['admin']);
}
function requireLogin() {
  if (!isLoggedIn()) {
    header("Location: /grading_app/admin/pages/login.php");
    exit;
  }
}
