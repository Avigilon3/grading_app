<?php
require_once '../includes/init.php';
requireAdminLogin();


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
        if (!$studentId) {
            return;
        }

        if ($sectionId) {
            $cleanup = $pdo->prepare("DELETE FROM section_students WHERE student_id = ? AND section_id <> ?");
            $cleanup->execute([$studentId, $sectionId]);

            $stmt = $pdo->prepare(
                "INSERT INTO section_students (section_id, student_id) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE section_id = VALUES(section_id)"
            );
            $stmt->execute([$sectionId, $studentId]);
        } else {
            $cleanup = $pdo->prepare("DELETE FROM section_students WHERE student_id = ?");
            $cleanup->execute([$studentId]);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/students.php');
    exit;
}

$action = $_POST['action'] ?? '';

try {
    require_csrf_token();

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

        $newStudentId = (int)$pdo->lastInsertId();
        $sectionId = findSectionIdByName($pdo, $section);
        ensureSectionStudentLink($pdo, $newStudentId, $sectionId);

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

        $sectionId = findSectionIdByName($pdo, $section);
        ensureSectionStudentLink($pdo, $id, $sectionId);

        //activity log edit or update student
        $userId = $_SESSION['user']['id'] ?? null;
        add_activity_log($pdo, $userId, 'UPDATE_STUDENT', 'Updated student: ' . $student_id);

        header('Location: ../pages/students.php?msg=' . urlencode('Student updated successfully.'));
        exit;
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([$id]);

        //activity log delete student
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
