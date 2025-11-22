<?php
require_once '../includes/init.php';
requireAdminLogin();

if (!function_exists('lookupUserIdByEmail')) {
    function lookupUserIdByEmail(PDO $pdo, string $email): ?int
    {
        $email = trim(strtolower($email));
        if ($email === '') {
            return null;
        }
        $stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = ? LIMIT 1");
        $stmt->execute([$email]);
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
    }
}

if (!function_exists('findSectionIdByName')) {
    function findSectionIdByName(PDO $pdo, ?string $sectionName): ?int
    {
        $sectionName = trim((string)$sectionName);
        if ($sectionName === '') {
            return null;
        }
        $stmt = $pdo->prepare("SELECT id FROM sections WHERE LOWER(section_name) = LOWER(?) LIMIT 1");
        $stmt->execute([$sectionName]);
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
    }
}

if (!function_exists('ensureSectionStudentLink')) {
    function ensureSectionStudentLink(PDO $pdo, int $studentId, ?int $sectionId): void
    {
        if (!$studentId || !$sectionId) {
            return;
        }
        $stmt = $pdo->prepare(
            "INSERT INTO section_students (section_id, student_id) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE section_id = section_id"
        );
        $stmt->execute([$sectionId, $studentId]);
    }
}

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

        $linkedUserId = lookupUserIdByEmail($pdo, $ptc_email);

        // Insert
        $stmt = $pdo->prepare("INSERT INTO students (user_id, student_id, ptc_email, first_name, middle_name, last_name, year_level, section, status)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$linkedUserId, $student_id, $ptc_email, $first_name, $middle_name, $last_name, $year_level, $section, $status]);
        $newStudentId = (int)$pdo->lastInsertId();
        $sectionId = findSectionIdByName($pdo, $section);
        ensureSectionStudentLink($pdo, $newStudentId, $sectionId);

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

        $linkedUserId = lookupUserIdByEmail($pdo, $ptc_email);

        $stmt = $pdo->prepare("UPDATE students SET user_id=?, student_id=?, ptc_email=?, first_name=?, middle_name=?, last_name=?, year_level=?, section=?, status=? WHERE id=?");
        $stmt->execute([$linkedUserId, $student_id, $ptc_email, $first_name, $middle_name, $last_name, $year_level, $section, $status, $id]);

        $sectionId = findSectionIdByName($pdo, $section);
        ensureSectionStudentLink($pdo, $id, $sectionId);

        $userId = $_SESSION['user']['id'] ?? null;
        add_activity_log($pdo, $userId, 'UPDATE_STUDENT', 'Updated student: ' . $student_id);

        header('Location: ../pages/students.php?msg=' . urlencode('Student updated successfully.'));
        exit;
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([$id]);

        $userId = $_SESSION['user']['id'] ?? null;
        add_activity_log($pdo, $userId, 'DELETE_STUDENT', 'Deleted student id: ' . $id);

        header('Location: ../pages/students.php?msg=' . urlencode('Student deleted successfully.'));
        exit;
    }

    header('Location: ../pages/students.php');
    exit;
} catch (Exception $e) {
    header('Location: ../pages/students.php?msg=' . urlencode('Error: ' . $e->getMessage()));
    exit;
}
