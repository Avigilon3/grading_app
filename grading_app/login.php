<?php
require_once __DIR__ . '/core/config/config.php';
require_once __DIR__ . '/core/auth/session.php';

$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = trim($_POST['password'] ?? '');

    if ($email && $pass) {
        require_once __DIR__ . '/core/db/connection.php';

        // ✅ matches your actual columns (first_name, last_name, password_hash)
        $stmt = $pdo->prepare('SELECT id, email, password_hash, role, first_name, last_name, status FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if ($u && !empty($u['password_hash']) && password_verify($pass, $u['password_hash'])) {

            // combine names for display
            $name = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
            if ($name === '') {
                $name = $u['email']; // fallback
            }

            // optional: block inactive accounts
            if (isset($u['status']) && strtoupper($u['status']) !== 'ACTIVE') {
                $err = 'Your account is inactive.';
            } else {
                // unified session for all roles
                $_SESSION['user'] = [
                    'id'    => $u['id'],
                    'email' => $u['email'],
                    'role'  => $u['role'],
                    'name'  => $name,
                ];

                // backward support for admin session
                if (in_array($u['role'], ['admin','registrar','mis','super_admin'], true)) {
                    $_SESSION['admin'] = $_SESSION['user'];
                }

                // role-based redirects
                switch ($u['role']) {
                    case 'admin':
                    case 'registrar':
                    case 'mis':
                    case 'super_admin':
                        header('Location: ' . BASE_URL . '/admin/pages/dashboard.php');
                        break;
                    case 'professor':
                        header('Location: ' . BASE_URL . '/professor/index.php');
                        break;
                    case 'student':
                        header('Location: ' . BASE_URL . '/student/index.php');
                        break;
                    default:
                        header('Location: ' . BASE_URL . '/login.php');
                        break;
                }
                exit;
            }

        } else {
            $err = 'Invalid email or password.';
        }

    } else {
        $err = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Login</title>
</head>
<body>
<h2>Login</h2>
<?php if ($err): ?>
  <div style="color:#b00020;"><?= htmlspecialchars($err) ?></div>
<?php endif; ?>
<form method="post">
  <label>Email</label><br>
  <input type="email" name="email" required><br>
  <label>Password</label><br>
  <input type="password" name="password" required><br>
  <button type="submit">Sign in</button>
</form>
<div>
  <a href="register.php">Create an account</a> ·
  <a href="forgot_password.php">Forgot password?</a>
</div>
</body>
</html>
