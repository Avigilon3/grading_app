<?php

if (!function_exists('professorIsLoggedIn')) {
    function professorIsLoggedIn(): bool {
        return isLoggedIn() && (($_SESSION['user']['role'] ?? '') === 'professor');
    }
}

if (!function_exists('professorCurrentName')) {
    function professorCurrentName(): string {
        return currentUserName();
    }
}
