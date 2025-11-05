<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/session.php';


function requireLogin() {
    if (empty($_SESSION['user'])) {
        header('Location: ' . BASE_URL . '/login.php?session=expired');
        exit;
    }
}

//role based
function requireRole(array $roles) {
    requireLogin();
    $role = $_SESSION['user']['role'] ?? '';
    if (!in_array($role, $roles, true)) {
        http_response_code(403);
        echo 'Unauthorized.';
        exit;
    }
}

function requireAdmin() {
    requireRole(['admin', 'registrar', 'mis', 'super_admin']);
} //wala pa yung super_admin temporary lang

function requireProfessor() {
    requireRole(['professor']);
}

function requireStudent() {
    requireRole(['student']);
}
