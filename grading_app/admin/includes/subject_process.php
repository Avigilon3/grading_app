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
        $subject_code  = trim($_POST['subject_code']);
        $subject_title = trim($_POST['subject_title']);
        $units         = trim($_POST['units']);
        $description   = trim($_POST['description']);
        $course_id     = isset($_POST['course_id']) && $_POST['course_id'] !== '' ? (int)$_POST['course_id'] : null;
        $year_level    = isset($_POST['year_level']) && $_POST['year_level'] !== '' ? trim($_POST['year_level']) : null;
        $term_id       = isset($_POST['term_id']) && $_POST['term_id'] !== '' ? (int)$_POST['term_id'] : null;
        $is_active     = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        $check = $pdo->prepare("SELECT id FROM subjects WHERE subject_code = ? LIMIT 1");
        $check->execute([$subject_code]);
        if ($check->fetch()) {
            header('Location: ../pages/subjects.php?msg=' . urlencode('Subject code already exists.'));
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO subjects (subject_code, subject_title, units, description, course_id, year_level, term_id, is_active) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$subject_code, $subject_title, $units, $description, $course_id, $year_level, $term_id, $is_active]);

        $userId = $_SESSION['user']['id'] ?? null;
        add_activity_log($pdo, $userId, 'ADD_SUBJECT', 'Added subject: ' . $subject_code);

        header('Location: ../pages/subjects.php?msg=' . urlencode('Subject added successfully.'));
        exit;
    }

    if ($action === 'update') {
        $id            = (int)$_POST['id'];
        $subject_code  = trim($_POST['subject_code']);
        $subject_title = trim($_POST['subject_title']);
        $units         = trim($_POST['units']);
        $description   = trim($_POST['description']);
        $course_id     = isset($_POST['course_id']) && $_POST['course_id'] !== '' ? (int)$_POST['course_id'] : null;
        $year_level    = isset($_POST['year_level']) && $_POST['year_level'] !== '' ? trim($_POST['year_level']) : null;
        $term_id       = isset($_POST['term_id']) && $_POST['term_id'] !== '' ? (int)$_POST['term_id'] : null;
        $is_active     = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        $check = $pdo->prepare("SELECT id FROM subjects WHERE subject_code = ? AND id != ? LIMIT 1");
        $check->execute([$subject_code, $id]);
        if ($check->fetch()) {
            header('Location: ../pages/subjects.php?msg=' . urlencode('Another subject with same code exists.'));
            exit;
        }

        $stmt = $pdo->prepare("UPDATE subjects SET subject_code=?, subject_title=?, units=?, description=?, course_id=?, year_level=?, term_id=?, is_active=? WHERE id=?");
        $stmt->execute([
            $subject_code, 
            $subject_title, 
            $units, 
            $description, 
            $course_id, 
            $year_level, 
            $term_id, 
            $is_active, 
            $id]);

        $userId = $_SESSION['user']['id'] ?? null;
        add_activity_log($pdo, $userId, 'UPDATE_SUBJECT', 'Updated subject: ' . $subject_code);

        header('Location: ../pages/subjects.php?msg=' . urlencode('Subject updated successfully.'));
        exit;
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
        $stmt->execute([$id]);

        $userId = $_SESSION['user']['id'] ?? null;
        add_activity_log($pdo, $userId, 'DELETE_SUBJECT', 'Deleted subject id: ' . $id);

        header('Location: ../pages/subjects.php?msg=' . urlencode('Subject deleted successfully.'));
        exit;
    }

    header('Location: ../pages/subjects.php');
    exit;
} catch (Exception $e) {
    header('Location: ../pages/subjects.php?msg=' . urlencode('Error: ' . $e->getMessage()));
    exit;
}

