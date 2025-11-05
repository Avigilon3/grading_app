<?php
require_once '../includes/init.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/sections.php');
    exit;
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'create') {
        $section_name          = trim($_POST['section_name']);
        $term_id               = trim($_POST['term_id']);
        $subject_id            = trim($_POST['subject_id']);
        $schedule              = trim($_POST['schedule']);
        $assigned_professor_id = trim($_POST['assigned_professor_id']);
        $is_active             = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        $stmt = $pdo->prepare("INSERT INTO sections (section_name, term_id, subject_id, schedule, assigned_professor_id, is_active) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$section_name, $term_id ?: null, $subject_id ?: null, $schedule, $assigned_professor_id ?: null, $is_active]);

        $userId = $_SESSION['user']['id'] ?? null;
        add_activity_log($pdo, $userId, 'ADD_SECTION', 'Added section: ' . $section_name);

        header('Location: ../pages/sections.php?msg=' . urlencode('Section added successfully.'));
        exit;
    }

    if ($action === 'update') {
        $id                    = (int)$_POST['id'];
        $section_name          = trim($_POST['section_name']);
        $term_id               = trim($_POST['term_id']);
        $subject_id            = trim($_POST['subject_id']);
        $schedule              = trim($_POST['schedule']);
        $assigned_professor_id = trim($_POST['assigned_professor_id']);
        $is_active             = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        $stmt = $pdo->prepare("UPDATE sections SET section_name=?, term_id=?, subject_id=?, schedule=?, assigned_professor_id=?, is_active=? WHERE id=?");
        $stmt->execute([$section_name, $term_id ?: null, $subject_id ?: null, $schedule, $assigned_professor_id ?: null, $is_active, $id]);

        $userId = $_SESSION['user']['id'] ?? null;
        add_activity_log($pdo, $userId, 'UPDATE_SECTION', 'Updated section: ' . $section_name);

        header('Location: ../pages/sections.php?msg=' . urlencode('Section updated successfully.'));
        exit;
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM sections WHERE id = ?");
        $stmt->execute([$id]);

        $userId = $_SESSION['user']['id'] ?? null;
        add_activity_log($pdo, $userId, 'DELETE_SECTION', 'Deleted section id: ' . $id);

        header('Location: ../pages/sections.php?msg=' . urlencode('Section deleted successfully.'));
        exit;
    }

    header('Location: ../pages/sections.php');
    exit;
} catch (Exception $e) {
    header('Location: ../pages/sections.php?msg=' . urlencode('Error: ' . $e->getMessage()));
    exit;
}

