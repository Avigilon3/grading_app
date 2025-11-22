<?php
require_once '../includes/init.php';
requireAdmin();

if (!function_exists('assignProfessorToSubjectSections')) {
    function assignProfessorToSubjectSections(PDO $pdo, int $professorId, ?int $subjectId): void
    {
        if (!$professorId || !$subjectId) {
            return;
        }

        $stmt = $pdo->prepare(
            'UPDATE section_subjects
                SET professor_id = :professor_id
              WHERE subject_id = :subject_id
                AND professor_id IS NULL'
        );
        $stmt->execute([
            ':professor_id' => $professorId,
            ':subject_id' => $subjectId,
        ]);

        $ssStmt = $pdo->prepare(
            'SELECT id
               FROM section_subjects
              WHERE subject_id = :subject_id
                AND professor_id = :professor_id'
        );
        $ssStmt->execute([
            ':subject_id' => $subjectId,
            ':professor_id' => $professorId,
        ]);
        $sectionSubjectIds = $ssStmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($sectionSubjectIds as $sectionSubjectId) {
            ensureGradingSheetForSectionSubject($pdo, (int)$sectionSubjectId);
        }
    }
}

if (!function_exists('releaseProfessorFromSubjectSections')) {
    function releaseProfessorFromSubjectSections(PDO $pdo, int $professorId, ?int $subjectId): void
    {
        if (!$professorId || !$subjectId) {
            return;
        }

        $sectionsStmt = $pdo->prepare(
            'SELECT section_id
               FROM section_subjects
              WHERE subject_id = :subject_id
                AND professor_id = :professor_id'
        );
        $sectionsStmt->execute([
            ':subject_id' => $subjectId,
            ':professor_id' => $professorId,
        ]);
        $sectionIds = $sectionsStmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $pdo->prepare(
            'UPDATE section_subjects
                SET professor_id = NULL
              WHERE subject_id = :subject_id
                AND professor_id = :professor_id'
        );
        $stmt->execute([
            ':subject_id' => $subjectId,
            ':professor_id' => $professorId,
        ]);

        foreach ($sectionIds as $sectionId) {
            removeProfessorFromGradingSheet($pdo, (int)$sectionId, $professorId);
        }
    }
}

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
        $subject_id   = isset($_POST['subject_id']) && $_POST['subject_id'] !== '' ? (int)$_POST['subject_id'] : null;
        $schedule     = isset($_POST['schedule']) ? trim($_POST['schedule']) : null;
        if ($schedule === '') {
            $schedule = null;
        }
        $is_active    = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        $check = $pdo->prepare("SELECT id FROM professors WHERE professor_id = ? OR ptc_email = ? LIMIT 1");
        $check->execute([$professor_id, $ptc_email]);
        if ($check->fetch()) {
            header('Location: ../pages/professors.php?msg=' . urlencode('Professor ID or email already exists.'));
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO professors (professor_id, ptc_email, first_name, middle_name, last_name, subject_id, schedule, is_active) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$professor_id, $ptc_email, $first_name, $middle_name, $last_name, $subject_id, $schedule, $is_active]);
        $newProfessorId = (int)$pdo->lastInsertId();
        assignProfessorToSubjectSections($pdo, $newProfessorId, $subject_id);

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
        $subject_id   = isset($_POST['subject_id']) && $_POST['subject_id'] !== '' ? (int)$_POST['subject_id'] : null;
        $schedule     = isset($_POST['schedule']) ? trim($_POST['schedule']) : null;
        if ($schedule === '') {
            $schedule = null;
        }
        $is_active    = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        $check = $pdo->prepare("SELECT id FROM professors WHERE (professor_id = ? OR ptc_email = ?) AND id != ? LIMIT 1");
        $check->execute([$professor_id, $ptc_email, $id]);
        if ($check->fetch()) {
            header('Location: ../pages/professors.php?msg=' . urlencode('Another professor with same ID/email exists.'));
            exit;
        }

        $currentSubjectStmt = $pdo->prepare('SELECT subject_id FROM professors WHERE id = ?');
        $currentSubjectStmt->execute([$id]);
        $previousSubjectId = $currentSubjectStmt->fetchColumn();

        $stmt = $pdo->prepare("UPDATE professors SET professor_id=?, ptc_email=?, first_name=?, middle_name=?, last_name=?, subject_id=?, schedule=?, is_active=? WHERE id=?");
        $stmt->execute([$professor_id, $ptc_email, $first_name, $middle_name, $last_name, $subject_id, $schedule, $is_active, $id]);

        if ((int)$previousSubjectId !== (int)$subject_id) {
            releaseProfessorFromSubjectSections($pdo, $id, $previousSubjectId ? (int)$previousSubjectId : null);
        }
        assignProfessorToSubjectSections($pdo, $id, $subject_id);

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
