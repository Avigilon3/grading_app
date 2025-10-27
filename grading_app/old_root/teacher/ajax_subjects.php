<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';

$stmt = $pdo->prepare("SELECT * FROM subjects ORDER BY name ASC");
$stmt->execute();
$subjects = $stmt->fetchAll();

echo '<table class="table table-striped">';
echo '<thead><tr><th>ID</th><th>Subject Name</th><th>Code</th>';
if ($_SESSION['role'] === 'admin') {
    echo '<th>Action</th>';
}
echo '</tr></thead><tbody>';
foreach ($subjects as $subject) {
    echo '<tr>';
    echo '<td>' . $subject['id'] . '</td>';
    echo '<td>' . htmlspecialchars($subject['name']) . '</td>';
    echo '<td>' . htmlspecialchars($subject['code']) . '</td>';
    if ($_SESSION['role'] === 'admin') {
        echo '<td><button class="btn btn-danger btn-sm delete-subject" data-id="' . $subject['id'] . '">Delete</button></td>';
    }
    echo '</tr>';
}
echo '</tbody></table>'; 