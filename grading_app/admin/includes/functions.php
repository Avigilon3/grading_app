<?php

if (!function_exists('isLoggedIn')) {
  function isLoggedIn(): bool {
    return !empty($_SESSION['user']) && in_array($_SESSION['user']['role'] ?? '', ['admin','registrar'], true);
  }
}

if (!function_exists('currentUserName')) {
  function currentUserName(): string {
    if (!empty($_SESSION['user']['name'])) return $_SESSION['user']['name'];
    if (!empty($_SESSION['user']['email'])) return $_SESSION['user']['email'];
    return 'User';
  }
}

if (!function_exists('show_flash')) {
  function show_flash(): void {
    if (!empty($_SESSION['flash']) && is_array($_SESSION['flash'])) {
      foreach ($_SESSION['flash'] as $type => $msg) {
        $t = htmlspecialchars((string)$type);
        $m = htmlspecialchars((string)$msg);
        echo "<div class=\"flash {$t}\">{$m}</div>";
      }
      unset($_SESSION['flash']);
    }
  }
}
