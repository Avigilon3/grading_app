<?php
require_once '../includes/init.php';
requireAdmin();


if (($_GET['action'] ?? '') === 'lookup_student') {
    header('Content-Type: application/json');
    $code = trim($_GET['student_code'] ?? '');
    if ($code === '') {
        echo json_encode(['success' => false, 'error' => 'Missing student_code']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT id, student_id, first_name, middle_name, last_name, year_level FROM students WHERE student_id = ?");
    $stmt->execute([$code]);
    $row = $stmt->fetch();
    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'Not found']);
        exit;
    }
    echo json_encode(['success' => true, 'data' => $row]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/masterlist.php');
    exit;
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'add') {
        $section_id = (int)($_POST['section_id'] ?? 0);
        $student_code = trim($_POST['student_code'] ?? '');
        if (!$section_id || $student_code === '') {
            $_SESSION['flash']['error'] = 'Missing section or student ID.';
            header('Location: ../pages/masterlist.php?section_id=' . $section_id);
            exit;
        }

        // Find student
        $s = $pdo->prepare("SELECT id, first_name, middle_name, last_name FROM students WHERE student_id = ?");
        $s->execute([$student_code]);
        $student = $s->fetch();
        if (!$student) {
            $_SESSION['flash']['error'] = 'Student not found in MIS records.';
            header('Location: ../pages/masterlist.php?section_id=' . $section_id);
            exit;
        }

        // Check duplicate
        $chk = $pdo->prepare("SELECT id FROM section_students WHERE section_id = ? AND student_id = ?");
        $chk->execute([$section_id, $student['id']]);
        if ($chk->fetch()) {
            $_SESSION['flash']['info'] = 'Student is already in this masterlist.';
            header('Location: ../pages/masterlist.php?section_id=' . $section_id);
            exit;
        }

        // Insert enrollment
        $ins = $pdo->prepare("INSERT INTO section_students (section_id, student_id) VALUES (?, ?)");
        $ins->execute([$section_id, $student['id']]);

        $userId = $_SESSION['user']['id'] ?? null;
        $fullName = trim(($student['last_name'] ?? '') . ', ' . ($student['first_name'] ?? '') . ' ' . ($student['middle_name'] ?? ''));
        add_activity_log($pdo, $userId, 'ADD_TO_MASTERLIST', 'Added ' . $fullName . ' to section ID ' . $section_id);

        $_SESSION['flash']['success'] = 'Student added to masterlist.';
        header('Location: ../pages/masterlist.php?section_id=' . $section_id);
        exit;
    }

    if ($action === 'remove') {
        $section_id = (int)($_POST['section_id'] ?? 0);
        $student_pk = (int)($_POST['student_pk'] ?? 0); // students.id
        if (!$section_id || !$student_pk) {
            $_SESSION['flash']['error'] = 'Missing parameters.';
            header('Location: ../pages/masterlist.php?section_id=' . $section_id);
            exit;
        }

        $del = $pdo->prepare("DELETE FROM section_students WHERE section_id = ? AND student_id = ?");
        $del->execute([$section_id, $student_pk]);

        $userId = $_SESSION['user']['id'] ?? null;
        add_activity_log($pdo, $userId, 'REMOVE_FROM_MASTERLIST', 'Removed student ID ' . $student_pk . ' from section ID ' . $section_id);

        $_SESSION['flash']['success'] = 'Student removed from masterlist.';
        header('Location: ../pages/masterlist.php?section_id=' . $section_id);
        exit;
    }

    header('Location: ../pages/masterlist.php');
    exit;
} catch (Exception $e) {
    $_SESSION['flash']['error'] = 'Error: ' . $e->getMessage();
    $redir = '../pages/masterlist.php';
    if (!empty($_POST['section_id'])) { $redir .= '?section_id='.(int)$_POST['section_id']; }
    header('Location: ' . $redir);
    exit;
}

