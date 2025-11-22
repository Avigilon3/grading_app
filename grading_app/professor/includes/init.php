<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load shared core modules to align with admin
require_once '../../core/config/config.php';
require_once '../../core/auth/session.php';
require_once '../../core/auth/guards.php';
require_once '../../core/db/connection.php';
require_once '../../core/config/functions.php';

// Module-specific helpers (keep for future use)
require_once 'functions.php';
require_once 'auth.php';
