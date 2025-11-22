<?php
// core/config/config.php

// --- Option A (manual, simplest) ---
// Set this to the folder path you see in your browser after http://localhost
// Example: if your login URL is http://localhost/grading_app/grading_app/login.php
// then set BASE_URL to '/grading_app/grading_app'
define('BASE_URL', '/grading_app/grading_app');

// --- Option B (automatic, safer if paths move) ---
// If you prefer auto-detect, uncomment the block below and comment out Option A above.
// $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
// $scriptDir = $scriptDir === '/' ? '' : $scriptDir;
// define('BASE_URL', $scriptDir);

// DB settings (adjust if needed)
$DB_HOST = '127.0.0.1';
$DB_PORT = '3306';
$DB_NAME = 'grading_app';
$DB_USER = 'root';
$DB_PASS = '';
