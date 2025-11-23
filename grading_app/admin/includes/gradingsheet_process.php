<?php
require_once '../includes/init.php';
requireAdmin();

$redirect = $_POST['redirect'] ?? '../pages/grading_management.php';
if (!is_string($redirect) || strpos($redirect, '../pages/') !== 0) {
    $redirect = '../pages/grading_management.php';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirect);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'set_deadline') {
        $id = (int)($_POST['id'] ?? 0);
        $deadlineAt = trim($_POST['deadline_at'] ?? '');
        if (!$id) {
            throw new Exception('Invalid grading sheet.');
        }
        $stmt = $pdo->prepare('UPDATE grading_sheets SET deadline_at = ? WHERE id = ?');
        $stmt->execute([$deadlineAt ?: null, $id]);
        add_activity_log($pdo, $_SESSION['user']['id'] ?? null, 'SET_DEADLINE', "Grading sheet #{$id} deadline updated.");
        $msg = 'Deadline updated.';
    } elseif ($action === 'change_status') {
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $allowed = ['draft', 'submitted', 'locked', 'reopened'];
        if (!$id || !in_array($status, $allowed, true)) {
            throw new Exception('Invalid status.');
        }
        $stmt = $pdo->prepare('UPDATE grading_sheets SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
        add_activity_log($pdo, $_SESSION['user']['id'] ?? null, 'CHANGE_STATUS', "Grading sheet #{$id} set to {$status}.");
        $msg = 'Status updated.';
    } else {
        throw new Exception('Unknown action.');
    }

    $separator = (strpos($redirect, '?') === false) ? '?' : '&';
    header('Location: ' . $redirect . $separator . 'msg=' . urlencode($msg));
    exit;
} catch (Exception $e) {
    $separator = (strpos($redirect, '?') === false) ? '?' : '&';
    header('Location: ' . $redirect . $separator . 'err=' . urlencode($e->getMessage()));
    exit;
}
?>
