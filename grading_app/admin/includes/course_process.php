<?php
require_once '../includes/init.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/subjects.php');
    exit;
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'create') {
        $code        = trim($_POST['code']);
        $title       = trim($_POST['title']);
        $description = trim($_POST['description']);
        $is_active   = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        if ($code === '' || $title === '') {
            header('Location: ../pages/subjects.php?msg=' . urlencode('Course code and name are required.'));
            exit;
        }

        $check = $pdo->prepare("SELECT id FROM courses WHERE code = ? LIMIT 1");
        $check->execute([$code]);
        if ($check->fetch()) {
            header('Location: ../pages/subjects.php?msg=' . urlencode('Course code already exists.'));
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO courses (code, title, description, is_active) VALUES (?,?,?,?)");
        $stmt->execute([$code, $title, $description, $is_active]);

        $userId = $_SESSION['user']['id'] ?? null;
        add_activity_log($pdo, $userId, 'ADD_COURSE', 'Added course: ' . $code);

        header('Location: ../pages/subjects.php?msg=' . urlencode('Course added successfully.'));
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE courses SET is_active = 0 WHERE id = ?");
            $stmt->execute([$id]);

            $userId = $_SESSION['user']['id'] ?? null;
            add_activity_log($pdo, $userId, 'DEACTIVATE_COURSE', 'Deactivated course id: ' . $id);
        }
        header('Location: ../pages/subjects.php?msg=' . urlencode('Course deactivated.'));
        exit;
    }

    header('Location: ../pages/subjects.php');
    exit;
} catch (Exception $e) {
    header('Location: ../pages/subjects.php?msg=' . urlencode('Error: ' . $e->getMessage()));
    exit;
}

