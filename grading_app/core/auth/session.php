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
    if (!empty($_SESSION['user']['id'])) {
        return true;
    }
    if (!empty($_SESSION['admin']['id'])) {
        return true;
    }
    return false;
}

function currentUserName(): string
{
    if (!empty($_SESSION['user']['name'])) return $_SESSION['user']['name'];
    if (!empty($_SESSION['admin']['name'])) return $_SESSION['admin']['name'];
    if (!empty($_SESSION['user']['email'])) return $_SESSION['user']['email'];
    if (!empty($_SESSION['admin']['email'])) return $_SESSION['admin']['email'];
    return 'Admin';
}
