<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bootstrap config first to set include_path
require_once '../../core/config/config.php';

require_once 'core/auth/session.php';
require_once 'core/db/connection.php';
require_once 'core/config/functions.php'; 
require_once 'admin/includes/functions.php';
require_once 'admin/includes/auth.php';
