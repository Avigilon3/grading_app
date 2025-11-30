<?php
require_once '../includes/init.php';
requireStudent();

$currentUserId = $_SESSION['user']['id'] ?? null;
$studentFullName = '';
$studentEmail = '';
$studentNumber = '';

if ($currentUserId) {
    try {
        $stmt = $pdo->prepare(
            "SELECT student_id, ptc_email, first_name, middle_name, last_name
               FROM students
              WHERE user_id = :uid
              LIMIT 1"
        );
        $stmt->execute([':uid' => $currentUserId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $parts = [];
            foreach (['first_name', 'middle_name', 'last_name'] as $key) {
                $val = trim((string)($row[$key] ?? ''));
                if ($val !== '') {
                    $parts[] = $val;
                }
            }
            $studentFullName = $parts ? implode(' ', $parts) : '';
            $studentEmail = trim((string)($row['ptc_email'] ?? ''));
            $studentNumber = trim((string)($row['student_id'] ?? ''));
        }
    } catch (Throwable $e) {
        // fall back to session-based values below
    }
}

if ($studentFullName === '' && function_exists('currentUserName')) {
    $studentFullName = currentUserName();
}
if ($studentEmail === '' && !empty($_SESSION['user']['email'])) {
    $studentEmail = $_SESSION['user']['email'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <link rel="stylesheet" href="../assets/css/student.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <div class="layout">
      <?php include '../includes/sidebar.php'; ?>
      <main class="content">
          <div class="page-header">
            <h2>Settings</h2>
            <p class="text-muted">Manage your account settings and security preferences</p>
          </div>

          <div class="form-box">
            <div class="page-header icon">
              <span class="material-symbols-rounded">account_circle</span>
              <h3>Profile Information</h3>
            </div>
            <div class="row-grid cols-2">
                <div>
                  <label>Full Name</label>
                  <input class="form-control" type="text" value="<?= htmlspecialchars($studentFullName); ?>" readonly>
                </div>
                <div>
                  <label>Email Address</label>
                  <input class="form-control" type="email" value="<?= htmlspecialchars($studentEmail); ?>" readonly>
                </div>
                <div>
                  <label>Student ID Number</label>
                  <input class="form-control" type="text" value="<?= htmlspecialchars($studentNumber); ?>" readonly>
                </div>
            </div>
          </div>

          <div class="row-grid cols-1">
            <div class="form-box">
              <div class="page-header icon">
                <span class="material-symbols-rounded">settings</span>
                <h3>Change Password</h3>
              </div>
              <form method="post">
                <input type="hidden" name="action" value="change_password">
                <label>Current Password</label>
                <input class="form-control" type="password" name="current_password" required>
                <label>New Password</label>
                <input class="form-control" type="password" name="new_password" minlength="8" required>
                <label>Confirm New Password</label>
                <input class="form-control" type="password" name="confirm_password" minlength="8" required>
                <div class="form-actions" style="margin-top:12px">
                  <button type="submit">Change Password</button>
                </div>
              </form>
            </div>
          </div>

      </main>
    </div>
</body>
<script src="../assets/js/student.js"></script>
</html>
