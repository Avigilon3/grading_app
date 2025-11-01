<?php

if (!function_exists('isLoggedIn')) {
  function isLoggedIn(): bool {
    if (!empty($_SESSION['admin']) && !empty($_SESSION['admin']['id'])) {
      return true;
    }

    if (!empty($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
      // but make sure it's an admin-type user
      $role = $_SESSION['user']['role'] ?? '';
      if (in_array($role, ['admin','mis','registrar','super_admin'], true)) {
        return true;
      }
    }

    return false;
  }
}

if (!function_exists('currentUserName')) {
  function currentUserName(): string {

    if (!empty($_SESSION['admin']['name'])) {
      return $_SESSION['admin']['name'];
    }

    if (!empty($_SESSION['admin']['first_name']) || !empty($_SESSION['admin']['last_name'])) {
      return trim(($_SESSION['admin']['first_name'] ?? '') . ' ' . ($_SESSION['admin']['last_name'] ?? ''));
    }

    if (!empty($_SESSION['user']['name'])) {
      return $_SESSION['user']['name'];
    }
    if (!empty($_SESSION['user']['first_name']) || !empty($_SESSION['user']['last_name'])) {
      return trim(($_SESSION['user']['first_name'] ?? '') . ' ' . ($_SESSION['user']['last_name'] ?? ''));
    }

    if (!empty($_SESSION['admin']['email'])) {
      return $_SESSION['admin']['email'];
    }
    if (!empty($_SESSION['user']['email'])) {
      return $_SESSION['user']['email'];
    }

    return 'Admin';
  }
}

function requireAdminLogin() {
  if (!isLoggedIn()) {
    header('Location: ../../login.php?session=expired');
    exit;
  }
}
