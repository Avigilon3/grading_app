<?php
function isLoggedIn(): bool { return !empty($_SESSION['professor']); }
function requireLogin() {
  if (!isLoggedIn()) {
    header("Location: ./pages/login.php");
    exit;
  }
}
