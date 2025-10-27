<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';

// Handle search (optional, can be extended)
$search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';

// Modify query based on user role
if ($_SESSION['role'] === 'student') {
    $stmt = $pdo->prepare("
        SELECT s.id AS student_id, s.student_number, u.username, u.id AS user_id
        FROM students s
        JOIN users u ON u.id = s.user_id
        WHERE u.id = ? AND (u.username LIKE ? OR s.student_number LIKE ?)
        ORDER BY u.username ASC
    ");
    $stmt->execute([$_SESSION['user_id'], $search, $search]);
} else {
    $stmt = $pdo->prepare("
        SELECT s.id AS student_id, s.student_number, u.username, u.id AS user_id
        FROM students s
        JOIN users u ON u.id = s.user_id
        WHERE u.username LIKE ? OR s.student_number LIKE ?
        ORDER BY u.username ASC
    ");
    $stmt->execute([$search, $search]);
}
$students = $stmt->fetchAll();

function convertRawToGrade($raw) {
    if ($raw >= 97) return 1.00;
    if ($raw >= 94) return 1.25;
    if ($raw >= 91) return 1.50;
    if ($raw >= 88) return 1.75;
    if ($raw >= 85) return 2.00;
    if ($raw >= 82) return 2.25;
    if ($raw >= 79) return 2.50;
    if ($raw >= 76) return 2.75;
    if ($raw >= 75) return 3.00;
    return 5.00;
}

foreach ($students as $student):
    $stmt = $pdo->prepare("
        SELECT g.id, sub.name AS subject, g.raw_grade, g.grade, g.comments
        FROM grades g
        JOIN subjects sub ON g.subject_id = sub.id
        WHERE g.student_id = ?
    ");
    $stmt->execute([$student['student_id']]);
    $grades = $stmt->fetchAll();
    echo '<div class="card mb-3">';
    echo '<div class="card-header bg-primary text-white">' . htmlspecialchars($student['username']) . ' (' . htmlspecialchars($student['student_number']) . ")</div>";
    echo '<div class="card-body">';
    echo '<table class="table table-striped">';
    echo '<thead><tr><th>Subject</th><th>Raw</th><th>Equivalent</th><th>Comments</th>';
    if ($_SESSION['role'] === 'admin') {
        echo '<th>Action</th>';
    }
    echo '</tr></thead><tbody>';
    $total = 0; $count = 0;
    foreach ($grades as $g) {
        $total += $g['grade']; $count++;
        echo '<tr>';
        echo '<td>' . htmlspecialchars($g['subject']) . '</td>';
        echo '<td>' . $g['raw_grade'] . '</td>';
        echo '<td>' . $g['grade'] . '</td>';
        echo '<td>' . htmlspecialchars($g['comments']) . '</td>';
        if ($_SESSION['role'] === 'admin') {
            echo '<td>';
            echo '<a href="?edit_grade=' . $g['id'] . '" class="btn btn-primary btn-sm">Edit</a> ';
            echo '<a href="?delete_grade=' . $g['id'] . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Delete this grade?\');">Delete</a>';
            echo '</td>';
        }
        echo '</tr>';
    }
    if ($count) {
        echo '<tr><td colspan="' . (($_SESSION['role'] === 'admin') ? '5' : '4') . '"><strong>Average Grade:</strong> ' . number_format($total / $count, 2) . '</td></tr>';
    }
    echo '</tbody></table>';
    echo '</div></div>';
endforeach; 