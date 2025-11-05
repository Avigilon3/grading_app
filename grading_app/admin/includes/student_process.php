<?php
require_once '../includes/init.php';
requireAdminLogin();



if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/students.php');
    exit;
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'create') {
        $student_id  = trim($_POST['student_id']);
        $ptc_email   = trim($_POST['ptc_email']);
        $first_name  = trim($_POST['first_name']);
        $middle_name = trim($_POST['middle_name']);
        $last_name   = trim($_POST['last_name']);
        $year_level  = trim($_POST['year_level']);
        $section     = trim($_POST['section']);
        $status      = trim($_POST['status']);

        // Check duplicate
        $check = $pdo->prepare("SELECT id FROM students WHERE student_id = ? OR ptc_email = ? LIMIT 1");
        $check->execute([$student_id, $ptc_email]);
        if ($check->fetch()) {
            header('Location: ../pages/students.php?msg=' . urlencode('Student ID or email already exists.'));
            exit;
        }

        // Insert
        $stmt = $pdo->prepare("INSERT INTO students (student_id, ptc_email, first_name, middle_name, last_name, year_level, section, status)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $ptc_email, $first_name, $middle_name, $last_name, $year_level, $section, $status]);


        //activity log add student
        $userId = $_SESSION['user']['id'] ?? null;
        add_activity_log($pdo, $userId, 'ADD_STUDENT', 'Added student: ' . $student_id);


        header('Location: ../pages/students.php?msg=' . urlencode('Student added successfully.'));
        exit;
    }

    if ($action === 'update') {
        $id          = (int)$_POST['id'];
        $student_id  = trim($_POST['student_id']);
        $ptc_email   = trim($_POST['ptc_email']);
        $first_name  = trim($_POST['first_name']);
        $middle_name = trim($_POST['middle_name']);
        $last_name   = trim($_POST['last_name']);
        $year_level  = trim($_POST['year_level']);
        $section     = trim($_POST['section']);
        $status      = trim($_POST['status']);

        // Check duplicates (excluding self)
        $check = $pdo->prepare("SELECT id FROM students WHERE (student_id = ? OR ptc_email = ?) AND id != ? LIMIT 1");
        $check->execute([$student_id, $ptc_email, $id]);
        if ($check->fetch()) {
            header('Location: ../pages/students.php?msg=' . urlencode('Another student with same ID/email exists.'));
            exit;
        }

        $stmt = $pdo->prepare("UPDATE students SET student_id=?, ptc_email=?, first_name=?, middle_name=?, last_name=?, year_level=?, section=?, status=? WHERE id=?");
        $stmt->execute([$student_id, $ptc_email, $first_name, $middle_name, $last_name, $year_level, $section, $status, $id]);

        //activity log edit or update student
        $userId = $_SESSION['user']['id'] ?? null;
        add_activity_log($pdo, $userId, 'ADD_STUDENT', 'Added student: ' . $student_id);

        header('Location: ../pages/students.php?msg=' . urlencode('Student updated successfully.'));
        exit;
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([$id]);

        //activity log delete student
        $userId = $_SESSION['user']['id'] ?? null;
        add_activity_log($pdo, $userId, 'ADD_STUDENT', 'Added student: ' . $student_id);

        header('Location: ../pages/students.php?msg=' . urlencode('Student deleted successfully.'));
        exit;
    }

    header('Location: ../pages/students.php');
    exit;
} catch (Exception $e) {
    header('Location: ../pages/students.php?msg=' . urlencode('Error: ' . $e->getMessage()));
    exit;
}
