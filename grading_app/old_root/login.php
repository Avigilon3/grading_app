<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/db.php';

// Redirect logged in users
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT u.*, 
                CASE 
                    WHEN u.role = 'student' THEN s.student_number
                    ELSE NULL
                END as role_id
                FROM users u
                LEFT JOIN students s ON u.id = s.user_id
                WHERE u.username = ? AND u.is_active = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['role_id'] = $user['role_id'];

                // Update last login
                $stmt = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$user['id']]);

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: teacher/grading_system.php");
                } elseif ($user['role'] === 'student') {
                    header("Location: student/dashboard.php");
                } else {
                    header("Location: login.php");
                }
                exit;
            } else {
                $error = "Invalid username or password";
            }
        } catch (PDOException $e) {
            $error = "Login failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Grading System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    body { background: #f9fafb; min-height: 100vh; }
    .center-card { max-width: 520px; margin: 80px auto; background: #f5f6fa; border-radius: 22px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); padding: 3.5rem 2.5rem; text-align: center; }
    .center-card h2 { font-size: 2rem; font-weight: 700; color: #444; margin-bottom: 2rem; letter-spacing: -1px; }
    .form-label { color: #666; font-weight: 500; font-size: 1.1rem; }
    .form-control { border-radius: 14px; background: #f8f9fb; border: 1px solid #e0e0e0; color: #444; font-size: 1.13rem; padding: 0.9rem 1rem; }
    .form-control:focus { background: #f5f6fa; color: #222; border-color: #bfc4c9; box-shadow: none; }
    .btn-soft { background: #f0f1f4; color: #444; border-radius: 18px; border: none; padding: 1rem 1.2rem; font-size: 1.18rem; margin-bottom: 1.1rem; transition: background 0.2s; width: 100%; font-weight: 500; }
    .btn-soft:hover { background: #e4e5e9; color: #222; }
    .btn-outline-soft { background: none; color: #555; border: 1px solid #e0e0e0; border-radius: 18px; padding: 1rem 1.2rem; font-size: 1.18rem; width: 100%; transition: background 0.2s, color 0.2s; font-weight: 500; }
    .btn-outline-soft:hover { background: #f0f1f4; color: #222; }
    .alert-soft { background: #f6fff6; color: #3a5c3a; border: none; border-radius: 12px; font-size: 1.08rem; margin-bottom: 1.3rem; }
    .theme-toggle { position: fixed; top: 1.5rem; right: 1.5rem; background: #f0f1f4; color: #888; border: none; border-radius: 50%; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; box-shadow: 0 1px 4px rgba(0,0,0,0.03); transition: background 0.2s; }
    .theme-toggle:hover { background: #e4e5e9; color: #444; }
    </style>
</head>
<body>
    <button class="theme-toggle" id="themeToggle" title="Toggle dark mode">
        <span id="themeIcon">🌙</span>
    </button>
    <div class="container d-flex align-items-center justify-content-center min-vh-100">
        <div class="center-card">
            <h2>Login</h2>
            <?php if ($error): ?>
                <div class="alert alert-soft"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" action="" class="needs-validation" novalidate>
                <div class="mb-4 text-start">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus>
                </div>
                <div class="mb-4 text-start">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-soft">Login</button>
                    <a href="register.php" class="btn btn-outline-soft">Don't have an account? Register</a>
                </div>
            </form>
        </div>
    </div>
    <script>
    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        document.getElementById('themeIcon').textContent = theme === 'dark' ? '☀️' : '🌙';
    }
    function toggleTheme() {
        const current = localStorage.getItem('theme') || 'light';
        setTheme(current === 'dark' ? 'light' : 'dark');
    }
    document.getElementById('themeToggle').onclick = toggleTheme;
    setTheme(localStorage.getItem('theme') || 'light');
    </script>
</body>
</html>
