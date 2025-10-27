<?php
define('ALLOW_ACCESS', true);

// Secure session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_lifetime', 3600);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID periodically
if (!isset($_SESSION['last_regeneration']) || time() - $_SESSION['last_regeneration'] > 300) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Handle AJAX request to set theme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
        exit;
    }

    if (isset($input['theme']) && in_array($input['theme'], ['light', 'dark'])) {
        $_SESSION['theme_preference'] = $input['theme'];
        echo json_encode(['status' => 'success', 'theme' => $input['theme']]);
        exit;
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid theme value']);
        exit;
    }
}

// Flash message setter
function set_flash_message($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function get_flash_message() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        set_flash_message('error', 'Please log in to access this page');
        header('Location: login.php');
        exit;
    }
}

function has_role($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function require_role($role) {
    if (!has_role($role)) {
        set_flash_message('error', 'You do not have permission to access this page');
        header('Location: dashboard.php');
        exit;
    }
}
