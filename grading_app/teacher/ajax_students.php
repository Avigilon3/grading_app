<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';

$stmt = $pdo->prepare("
    SELECT s.id AS student_id, s.student_number, u.username, u.email, u.id AS user_id
    FROM students s
    JOIN users u ON u.id = s.user_id
    ORDER BY u.username ASC
");
$stmt->execute();
$students = $stmt->fetchAll();

echo '<table class="table table-striped">';
echo '<thead><tr><th>ID</th><th>Student Number</th><th>Username</th><th>Email</th>';
if ($_SESSION['role'] === 'admin') {
    echo '<th>Action</th>';
}
echo '</tr></thead><tbody>';
foreach ($students as $student) {
    echo '<tr>';
    echo '<td>' . $student['user_id'] . '</td>';
    echo '<td>' . htmlspecialchars($student['student_number']) . '</td>';
    echo '<td>' . htmlspecialchars($student['username']) . '</td>';
    echo '<td>' . htmlspecialchars($student['email']) . '</td>';
    if ($_SESSION['role'] === 'admin') {
        echo '<td><button class="btn btn-danger btn-sm delete-student" data-id="' . $student['user_id'] . '">Delete</button></td>';
    }
    echo '</tr>';
}
echo '</tbody></table>'; 