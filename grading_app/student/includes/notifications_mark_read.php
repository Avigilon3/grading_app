<?php
require_once '../includes/init.php';
requireStudent();

$currentUserId = $_SESSION['user']['id'] ?? null;

if ($currentUserId) {
    try {
        $stmt = $pdo->prepare(
            "UPDATE notifications
                SET is_read = 1,
                    read_at = CASE WHEN read_at IS NULL THEN NOW() ELSE read_at END
              WHERE user_id = :uid
                AND is_read = 0"
        );
        $stmt->execute([':uid' => $currentUserId]);
    } catch (Throwable $e) {
        // ignore failure; user will just see notifications again
    }
}

$redirect = $_SERVER['HTTP_REFERER'] ?? '../pages/dashboard.php';
header('Location: ' . $redirect);
exit;
