<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function set_flash($k, $v) {
    $_SESSION['flash'][$k] = $v;
}

function get_flash($k) {
    if (isset($_SESSION['flash'][$k])) {
        $v = $_SESSION['flash'][$k];
        unset($_SESSION['flash'][$k]);
        return $v;
    }
    return null;
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

function currentUserName(): string
{

    if (!empty($_SESSION['user']['name'])) {
        return $_SESSION['user']['name'];
    }

    $first = $_SESSION['user']['first_name'] ?? '';
    $last  = $_SESSION['user']['last_name'] ?? '';
    $full  = trim($first . ' ' . $last);
    if ($full !== '') {
        return $full;
    }

    if (!empty($_SESSION['user']['email'])) {
        return $_SESSION['user']['email'];
    }

    return 'User';
}
