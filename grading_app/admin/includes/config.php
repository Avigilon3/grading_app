<?php
// Admin includes config: central helper for admin module
// Provides BASE paths, asset/url helpers, and DB access via core connection.

// Load core configuration (defines BASE_URL and DB_* defaults)
if (file_exists(__DIR__ . '/../../core/config/config.php')) {
	require_once __DIR__ . '/../../core/config/config.php';
}

// Ensure BASE_URL is defined (fallback to script detection)
if (!defined('BASE_URL')) {
	$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
	$scriptDir = $scriptDir === '/' ? '' : $scriptDir;
	define('BASE_URL', $scriptDir);
}

// Admin base url and asset root
if (!defined('ADMIN_BASE_URL')) {
	define('ADMIN_BASE_URL', rtrim(BASE_URL, '/') . '/admin');
}

if (!defined('ADMIN_ASSETS_URL')) {
	define('ADMIN_ASSETS_URL', ADMIN_BASE_URL . '/assets');
}

// Expose a helper to resolve admin asset URLs (CSS/JS/images)
if (!function_exists('admin_asset')) {
	function admin_asset($path = '') {
		$p = ltrim($path, '/');
		return ADMIN_ASSETS_URL . '/' . $p;
	}
}

// Helper to build admin internal links (pages/...)
if (!function_exists('admin_url')) {
	function admin_url($path = '') {
		$p = ltrim($path, '/');
		return ADMIN_BASE_URL . '/' . $p;
	}
}

// Make a PDO connection available via admin_db(); reuse core connection when present.
if (file_exists(__DIR__ . '/../../core/db/connection.php')) {
	require_once __DIR__ . '/../../core/db/connection.php'; // provides $pdo
}

if (!function_exists('admin_db')) {
	function admin_db() {
		global $pdo;
		return $pdo ?? null;
	}
}

// Optional: small helper to echo an asset tag
if (!function_exists('admin_css')) {
	function admin_css($file) {
		echo '<link rel="stylesheet" href="' . htmlspecialchars(admin_asset('css/' . ltrim($file, '/'))) . '">';
	}
}

if (!function_exists('admin_js')) {
	function admin_js($file) {
		echo '<script src="' . htmlspecialchars(admin_asset('js/' . ltrim($file, '/'))) . '"></script>';
	}
}

// Ready: other admin includes can require this file to get helpers above.

