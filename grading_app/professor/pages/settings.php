<?php
require_once '../includes/init.php';
requireProfessor();

$professor = requireProfessorRecord($pdo);
$userId = (int)$_SESSION['user']['id'];
$professorId = (int)$professor['id'];

$stmt = $pdo->prepare(
    'SELECT u.first_name,
            u.last_name,
            u.email,
            u.password_hash,
            p.professor_id,
            p.ptc_email
       FROM users u
       JOIN professors p ON p.user_id = u.id
      WHERE u.id = ?
      LIMIT 1'
);
$stmt->execute([$userId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    http_response_code(404);
    echo 'User profile not found.';
    exit;
}

$passwordErrors = [];
$passwordNotice = '';
$fullName = trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
$professorNumber = $profile['professor_id'] ?? '';
$emailAddress = $profile['ptc_email'] ?? $profile['email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($currentPassword === '' || !password_verify($currentPassword, $profile['password_hash'] ?? '')) {
        $passwordErrors[] = 'Current password is incorrect.';
    }
    if (strlen($newPassword) < 8) {
        $passwordErrors[] = 'New password must be at least 8 characters.';
    }
    if ($newPassword !== $confirmPassword) {
        $passwordErrors[] = 'New password and confirmation do not match.';
    }

    if (empty($passwordErrors)) {
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $updatePassword = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $updatePassword->execute([$newHash, $userId]);
        $profile['password_hash'] = $newHash;
        $passwordNotice = 'Password updated.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <link rel="stylesheet" href="../assets/css/professor.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="content">
        <div class="page-header settings-header">
            <div>
                <h1>Settings</h1>
                <p class="text-muted">Manage your account settings and security preferences.</p>
            </div>
        </div>

        <section class="form-box">
            <div class="page-header icon">
                <span class="material-symbols-rounded">account_circle</span>
                <div>
                    <h3>Profile Information</h3>
                </div>
            </div>
            <div class="row-grid cols-2">
                <div>
                    <label>Full Name</label>
                    <input class="form-control" type="text" value="<?= htmlspecialchars($fullName); ?>" readonly>
                </div>
                <div>
                    <label>Professor ID</label>
                    <input class="form-control" type="text" value="<?= htmlspecialchars($professorNumber); ?>" readonly>
                </div>
                <div>
                    <label>Email Address</label>
                    <input class="form-control" type="text" value="<?= htmlspecialchars($emailAddress); ?>" readonly>
                </div>
            </div>
        </section>

        <section class="form-box">
            <div class="page-header icon">
                <span class="material-symbols-rounded">lock</span>
                <div>
                    <h3>Change Password</h3>
                </div>
            </div>
            <?php if ($passwordNotice): ?>
                <div class="alert alert-success"><?= htmlspecialchars($passwordNotice); ?></div>
            <?php endif; ?>
            <?php if ($passwordErrors): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($passwordErrors as $error): ?>
                            <li><?= htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <form method="post" class="change-password-form">
                <input type="hidden" name="action" value="change_password">
                <label for="current_password">Current password</label>
                <input id="current_password" class="form-control" type="password" name="current_password" required>

                <label for="new_password">New password</label>
                <input id="new_password" class="form-control" type="password" name="new_password" minlength="8" required>

                <label for="confirm_password">Confirm new password</label>
                <input id="confirm_password" class="form-control" type="password" name="confirm_password" minlength="8" required>

                <div class="form-actions">
                    <button type="submit" class="btn primary">Update Password</button>
                </div>
            </form>
        </section>
    </main>
</div>
<script src="../assets/js/professor.js"></script>
</body>
<?php include '../includes/footer.php'; ?>
</html>
