<<<<<<< Updated upstream
=======
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

$profileErrors = [];
$passwordErrors = [];
$profileNotice = '';
$passwordNotice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($firstName === '') {
            $profileErrors[] = 'First name is required.';
        }
        if ($lastName === '') {
            $profileErrors[] = 'Last name is required.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $profileErrors[] = 'A valid email address is required.';
        } else {
            $emailStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
            $emailStmt->execute([$email, $userId]);
            if ($emailStmt->fetch()) {
                $profileErrors[] = 'Email address is already in use.';
            }
        }

        if (empty($profileErrors)) {
            $pdo->beginTransaction();
            try {
                $updateUser = $pdo->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?');
                $updateUser->execute([$firstName, $lastName, $email, $userId]);

                $updateProfessor = $pdo->prepare('UPDATE professors SET ptc_email = ? WHERE id = ?');
                $updateProfessor->execute([$email, $professorId]);

                $pdo->commit();
                $profile['first_name'] = $firstName;
                $profile['last_name'] = $lastName;
                $profile['email'] = $email;
                $profile['ptc_email'] = $email;
                $_SESSION['user']['name'] = $firstName . ' ' . $lastName;
                $profileNotice = 'Profile updated successfully.';
            } catch (Throwable $e) {
                $pdo->rollBack();
                $profileErrors[] = 'Unable to update profile. Please try again.';
            }
        }
    } elseif (isset($_POST['change_password'])) {
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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <link rel="stylesheet" href="../assets/css/professor.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="content">
        <h1>Profile Settings</h1>

        <section class="card">
            <h2>Personal Information</h2>
            <?php if ($profileNotice): ?>
                <div class="alert alert-success"><?= htmlspecialchars($profileNotice); ?></div>
            <?php endif; ?>
            <?php if ($profileErrors): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($profileErrors as $error): ?>
                            <li><?= htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <form method="post">
                <label for="professor_id">Professor ID</label>
                <input id="professor_id" type="text" value="<?= htmlspecialchars($profile['professor_id']); ?>" readonly>

                <label for="first_name">First name</label>
                <input id="first_name" type="text" name="first_name" value="<?= htmlspecialchars($profile['first_name']); ?>" required>

                <label for="last_name">Last name</label>
                <input id="last_name" type="text" name="last_name" value="<?= htmlspecialchars($profile['last_name']); ?>" required>

                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="<?= htmlspecialchars($profile['email']); ?>" required>

                <button type="submit" name="update_profile">Save Profile</button>
            </form>
        </section>

        <section class="card">
            <h2>Change Password</h2>
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
            <form method="post">
                <label for="current_password">Current password</label>
                <input id="current_password" type="password" name="current_password" required>

                <label for="new_password">New password</label>
                <input id="new_password" type="password" name="new_password" required>

                <label for="confirm_password">Confirm new password</label>
                <input id="confirm_password" type="password" name="confirm_password" required>

                <button type="submit" name="change_password">Update Password</button>
            </form>
        </section>
    </main>
</div>
<script src="../assets/js/professor.js"></script>
</body>
<?php include '../includes/footer.php'; ?>
</html>
>>>>>>> Stashed changes
