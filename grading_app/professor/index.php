<?php
// Redirect helper for professor module.
// Load config if available (defines BASE_URL). If BASE_URL is not defined,
// fall back to a relative path computed from the current script location.
$cfg = __DIR__ . '/../core/config/config.php';
if (file_exists($cfg)) {
	require_once $cfg;
}

$relative = '/professor/pages/dashboard.php';
if (defined('BASE_URL') && BASE_URL !== '') {
	$target = rtrim(BASE_URL, '/') . $relative;
} else {
	// Attempt to detect base path (keeps behavior safe if config not set)
	$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
	$scriptDir = $scriptDir === '/' ? '' : $scriptDir;
	$target = $scriptDir . $relative;
}

header('Location: ' . $target);
exit;
