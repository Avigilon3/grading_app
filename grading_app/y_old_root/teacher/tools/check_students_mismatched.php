<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    die('Unauthorized');
}
// Students with no matching user
$stmt = $pdo->query("SELECT s.id, s.student_number, s.user_id FROM students s LEFT JOIN users u ON s.user_id = u.id WHERE u.id IS NULL OR u.role != 'student'");
$students_mismatch = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Grades with no matching student
$stmt2 = $pdo->query("SELECT g.id, g.student_id FROM grades g LEFT JOIN students s ON g.student_id = s.id WHERE s.id IS NULL");
$grades_mismatch = $stmt2->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html><head><title>Mismatched Student Records</title>
<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
<style>
body { background: #f9fafb; min-height: 100vh; }
.center-card { max-width: 480px; margin: 56px auto; background: #f5f6fa; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.03); padding: 2.2rem 1.5rem; }
h2, h3 { color: #444; font-weight: 500; text-align: center; margin-bottom: 1.1rem; }
h2 { font-size: 1.2rem; margin-bottom: 1.5rem; }
h3 { font-size: 1.05rem; margin-top: 2.1rem; }
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
    <h2>Mismatched Student Records</h2>
    <h3>Students with No Matching User</h3>
    <table>
        <thead><tr><th>Student ID</th><th>Student #</th><th>User ID</th></tr></thead>
        <tbody>
        <?php foreach ($students_mismatch as $s): ?>
        <tr><td><?= htmlspecialchars($s['id']) ?></td><td><?= htmlspecialchars($s['student_number']) ?></td><td><?= htmlspecialchars($s['user_id']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (empty($students_mismatch)) echo '<div class="alert alert-success">All students have valid user accounts.</div>'; ?>
    <h3>Grades with No Matching Student</h3>
    <table>
        <thead><tr><th>Grade ID</th><th>Student ID</th></tr></thead>
        <tbody>
        <?php foreach ($grades_mismatch as $g): ?>
        <tr><td><?= htmlspecialchars($g['id']) ?></td><td><?= htmlspecialchars($g['student_id']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (empty($grades_mismatch)) echo '<div class="alert alert-success">All grades are linked to valid students.</div>'; ?>
</div>
</body></html> 