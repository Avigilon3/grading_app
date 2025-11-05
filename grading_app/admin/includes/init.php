<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config and dependencies using only relative paths
require_once '../../core/config/config.php';

require_once '../../core/auth/session.php';
require_once '../../core/auth/guards.php';
require_once '../../core/db/connection.php';
require_once '../../core/config/functions.php'; 
require_once '../includes/functions.php';
require_once '../includes/auth.php';
