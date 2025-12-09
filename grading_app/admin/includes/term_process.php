<?php
require_once '../includes/init.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/terms.php');
    exit;
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'create') {
        $semester    = isset($_POST['semester']) ? trim((string)$_POST['semester']) : '1';
        if ($semester !== '1' && $semester !== '2') { $semester = '1'; }
        $school_year = trim($_POST['school_year'] ?? '');
        $start_date  = trim($_POST['start_date'] ?? '');
        $end_date    = trim($_POST['end_date'] ?? '');
        $is_active   = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        if ($school_year === '') {
            header('Location: ../pages/terms.php?msg=' . urlencode('Please enter a School Year.'));
            exit;
        }

        
        $dupe = $pdo->prepare("SELECT id FROM terms WHERE semester = ? AND school_year = ? LIMIT 1");
        $dupe->execute([$semester, $school_year]);
        if ($dupe->fetch()) {
            header('Location: ../pages/terms.php?msg=' . urlencode('A term for this semester and school year already exists.'));
            exit;
        }

        $term_name = ($semester === '1' ? '1st Semester ' : '2nd Semester ') . $school_year;

        $stmt = $pdo->prepare("INSERT INTO terms (semester, term_name, school_year, start_date, end_date, is_active) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$semester, $term_name, $school_year, $start_date ?: null, $end_date ?: null, $is_active]);
        $newTermId = (int)$pdo->lastInsertId();
        syncSubjectStatusesWithTerms($pdo, $newTermId);

        $userId = $_SESSION['user']['id'] ?? null;
        add_activity_log($pdo, $userId, 'ADD_TERM', 'Added term: ' . $term_name);

        header('Location: ../pages/terms.php?msg=' . urlencode('Term added successfully.'));
        exit;
    }

    if ($action === 'update') {
        $id          = (int)($_POST['id'] ?? 0);
        $semester    = isset($_POST['semester']) ? trim((string)$_POST['semester']) : '1';
        if ($semester !== '1' && $semester !== '2') { $semester = '1'; }
        $school_year = trim($_POST['school_year'] ?? '');
        $start_date  = trim($_POST['start_date'] ?? '');
        $end_date    = trim($_POST['end_date'] ?? '');
        $is_active   = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        if ($school_year === '') {
            header('Location: ../pages/terms.php?msg=' . urlencode('Please enter a School Year.'));
            exit;
        }

        
        $dupe = $pdo->prepare("SELECT id FROM terms WHERE semester = ? AND school_year = ? AND id <> ? LIMIT 1");
        $dupe->execute([$semester, $school_year, $id]);
        if ($dupe->fetch()) {
            header('Location: ../pages/terms.php?msg=' . urlencode('A term for this semester and school year already exists.'));
            exit;
        }

        $term_name = ($semester === '1' ? '1st Semester ' : '2nd Semester ') . $school_year;

        $stmt = $pdo->prepare("UPDATE terms SET semester=?, term_name=?, school_year=?, start_date=?, end_date=?, is_active=? WHERE id=?");
        $stmt->execute([$semester, $term_name, $school_year, $start_date ?: null, $end_date ?: null, $is_active, $id]);
        syncSubjectStatusesWithTerms($pdo, $id);

        $userId = $_SESSION['user']['id'] ?? null;
        add_activity_log($pdo, $userId, 'UPDATE_TERM', 'Updated term: ' . $term_name);

        header('Location: ../pages/terms.php?msg=' . urlencode('Term updated successfully.'));
        exit;
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $subjectReset = $pdo->prepare("UPDATE subjects SET term_id = NULL, is_active = 0 WHERE term_id = ?");
        $subjectReset->execute([$id]);
        $stmt = $pdo->prepare("DELETE FROM terms WHERE id = ?");
        $stmt->execute([$id]);

        $userId = $_SESSION['user']['id'] ?? null;
        add_activity_log($pdo, $userId, 'DELETE_TERM', 'Deleted term id: ' . $id);

        header('Location: ../pages/terms.php?msg=' . urlencode('Term deleted successfully.'));
        exit;
    }

    header('Location: ../pages/terms.php');
    exit;
} catch (Exception $e) {
    header('Location: ../pages/terms.php?msg=' . urlencode('Error: ' . $e->getMessage()));
    exit;
}
