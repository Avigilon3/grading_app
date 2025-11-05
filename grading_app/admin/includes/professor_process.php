<?php
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/professors.php');
    exit;
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'create') {
        $professor_id = trim($_POST['professor_id']);
        $ptc_email    = trim($_POST['ptc_email']);
        $first_name   = trim($_POST['first_name']);
        $middle_name  = trim($_POST['middle_name']);
        $last_name    = trim($_POST['last_name']);
        $is_active    = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        $check = $pdo->prepare("SELECT id FROM professors WHERE professor_id = ? OR ptc_email = ? LIMIT 1");
        $check->execute([$professor_id, $ptc_email]);
        if ($check->fetch()) {
            header('Location: ../pages/professors.php?msg=' . urlencode('Professor ID or email already exists.'));
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO professors (professor_id, ptc_email, first_name, middle_name, last_name, is_active) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$professor_id, $ptc_email, $first_name, $middle_name, $last_name, $is_active]);

        $userId = $_SESSION['user']['id'] ?? null;
        add_activity_log($pdo, $userId, 'ADD_PROFESSOR', 'Added professor: ' . $professor_id);

        header('Location: ../pages/professors.php?msg=' . urlencode('Professor added successfully.'));
        exit;
    }

    if ($action === 'update') {
        $id           = (int)$_POST['id'];
        $professor_id = trim($_POST['professor_id']);
        $ptc_email    = trim($_POST['ptc_email']);
        $first_name   = trim($_POST['first_name']);
        $middle_name  = trim($_POST['middle_name']);
        $last_name    = trim($_POST['last_name']);
        $is_active    = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        $check = $pdo->prepare("SELECT id FROM professors WHERE (professor_id = ? OR ptc_email = ?) AND id != ? LIMIT 1");
        $check->execute([$professor_id, $ptc_email, $id]);
        if ($check->fetch()) {
            header('Location: ../pages/professors.php?msg=' . urlencode('Another professor with same ID/email exists.'));
            exit;
        }

        $stmt = $pdo->prepare("UPDATE professors SET professor_id=?, ptc_email=?, first_name=?, middle_name=?, last_name=?, is_active=? WHERE id=?");
        $stmt->execute([$professor_id, $ptc_email, $first_name, $middle_name, $last_name, $is_active, $id]);

        $userId = $_SESSION['user']['id'] ?? null;
        add_activity_log($pdo, $userId, 'UPDATE_PROFESSOR', 'Updated professor: ' . $professor_id);

        header('Location: ../pages/professors.php?msg=' . urlencode('Professor updated successfully.'));
        exit;
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM professors WHERE id = ?");
        $stmt->execute([$id]);

        $userId = $_SESSION['user']['id'] ?? null;
        add_activity_log($pdo, $userId, 'DELETE_PROFESSOR', 'Deleted professor id: ' . $id);

        header('Location: ../pages/professors.php?msg=' . urlencode('Professor deleted successfully.'));
        exit;
    }

    header('Location: ../pages/professors.php');
    exit;
} catch (Exception $e) {
    header('Location: ../pages/professors.php?msg=' . urlencode('Error: ' . $e->getMessage()));
    exit;
}

