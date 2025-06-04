<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    die('Unauthorized');
}
$stmt = $pdo->query("SELECT s.id, u.username, s.student_number, u.email FROM students s JOIN users u ON s.user_id = u.id");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
$students_no_grades = [];
foreach ($students as $student) {
    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM grades WHERE student_id = ?");
    $stmt2->execute([$student['id']]);
    if ($stmt2->fetchColumn() == 0) {
        $students_no_grades[] = $student;
    }
}
?>
<!DOCTYPE html>
<html><head><title>Students with No Grades</title>
<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
<style>
body { background: #f9fafb; min-height: 100vh; }
.center-card { max-width: 420px; margin: 56px auto; background: #f5f6fa; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.03); padding: 2.2rem 1.5rem; }
h2 { color: #444; font-weight: 500; margin-bottom: 1.2rem; text-align: center; font-size: 1.3rem; }
table { width: 100%; background: transparent; border-radius: 10px; margin-bottom: 1.2rem; }
th, td { font-size: 0.95rem; color: #444; font-weight: 400; padding: 0.45rem 0.3rem; text-align: center; }
th { background: none; font-weight: 500; border-bottom: 1px solid #ececec; }
tr:not(:last-child) td { border-bottom: 1px solid #f0f0f0; }
tr:last-child td { border-bottom: none; }
.btn-back { background: #f0f1f4; color: #555; border-radius: 14px; border: none; padding: 0.4rem 1.1rem; font-size: 0.95rem; margin-bottom: 1.2rem; transition: background 0.2s; display: block; margin-left: auto; margin-right: auto; }
.btn-back:hover { background: #e4e5e9; color: #222; }
.alert-success { border-radius: 10px; text-align: center; font-size: 0.98rem; background: #f6fff6; color: #3a5c3a; border: none; }
</style>
</head><body>
<div class="center-card">
    <a href="../grading_system.php" class="btn btn-back mb-3">&larr; Back</a>
    <h2>Students with No Grades</h2>
    <table>
        <thead><tr><th>Username</th><th>Student #</th><th>Email</th></tr></thead>
        <tbody>
        <?php foreach ($students_no_grades as $s): ?>
        <tr><td><?= htmlspecialchars($s['username']) ?></td><td><?= htmlspecialchars($s['student_number']) ?></td><td><?= htmlspecialchars($s['email']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (empty($students_no_grades)) echo '<div class="alert alert-success">All students have at least one grade.</div>'; ?>
</div>
</body></html> 