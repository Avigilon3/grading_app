<?php require_once __DIR__ . '/../../core/db/connection.php';
// Make a PDO connection available via admin_db(); reuse core connection when present.
if (!function_exists('admin_db')) {
    function admin_db() {
        global $pdo;
        return $pdo ?? null;
    }
}
?>

