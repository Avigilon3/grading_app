<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $student_id = $role === 'student' ? trim($_POST['student_id']) : null;

    // Validate input
    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required";
    } else {
        try {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->rowCount() > 0) {
                $error = "Username or email already exists";
            } else {
                // Begin transaction
                $pdo->beginTransaction();

                // Insert user
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $email, $role]);
                $user_id = $pdo->lastInsertId();

                // Insert role-specific data
                if ($role === 'student' && $student_id) {
                    $stmt = $pdo->prepare("INSERT INTO students (user_id, student_number) VALUES (?, ?)");
                    $stmt->execute([$user_id, $student_id]);
                }

                $pdo->commit();
                $success = "Registration successful! You can now login.";
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Registration failed. Please try again.";
        }
    }
}

// Redirect logged-in users to their dashboards
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: teacher/grading_system.php');
            break;
        case 'student':
            header('Location: student/dashboard.php');
            break;
        default:
            // Unknown role: log out user
            session_destroy();
            header('Location: login.php');
            break;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Pateros Technological Grading System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body { background: #f9fafb; min-height: 100vh; }
    .landing-card { max-width: 520px; margin: 80px auto; background: #f5f6fa; border-radius: 22px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); padding: 3.5rem 2.5rem; text-align: center; }
    .landing-card .icon { font-size: 4.2em; margin-bottom: 0.7rem; }
    .landing-card h1 { font-size: 2.2rem; font-weight: 700; color: #444; margin-bottom: 0.7rem; letter-spacing: -1px; }
    .landing-card .subtitle { color: #666; font-size: 1.18rem; margin-bottom: 2.2rem; font-weight: 400; }
    .features { list-style: none; padding: 0; margin: 2rem 0 2.2rem 0; text-align: left; color: #6a6a6a; font-size: 1.08rem; }
    .features li { display: flex; align-items: center; margin-bottom: 1rem; }
    .features li .check { color: #7f9cf5; margin-right: 0.75rem; font-size: 1.3rem; flex-shrink: 0; }
    .btn-landing-main { background: #e9ecf3; color: #3a3a3a; border-radius: 18px; border: none; padding: 1.1rem 1.2rem; font-size: 1.18rem; margin-bottom: 1.1rem; transition: background 0.2s; width: 100%; font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
    .btn-landing-main:hover { background: #dde2ea; color: #222; }
    .btn-landing-outline { background: none; color: #555; border: 2px solid #e0e0e0; border-radius: 18px; padding: 1.1rem 1.2rem; font-size: 1.18rem; width: 100%; transition: background 0.2s, color 0.2s; font-weight: 600; }
    .btn-landing-outline:hover { background: #f0f1f4; color: #222; }
    .theme-toggle { position: fixed; top: 1.5rem; right: 1.5rem; background: #f0f1f4; color: #888; border: none; border-radius: 50%; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; box-shadow: 0 1px 4px rgba(0,0,0,0.03); transition: background 0.2s; }
    .theme-toggle:hover { background: #e4e5e9; color: #444; }
  </style>
</head>
<body>
  <button class="theme-toggle" id="themeToggle" title="Toggle dark mode">
    <span id="themeIcon">🌙</span>
  </button>
  <div class="container d-flex align-items-center justify-content-center min-vh-100">
    <div class="landing-card">
      <div class="icon">🎓</div>
      <h1>Student Grading System</h1>
      <div class="subtitle">Welcome! Manage academic performance with ease and confidence.</div>
      <ul class="features">
        <li><span class="check">✔️</span> Secure login and role-based dashboards</li>
        <li><span class="check">✔️</span> Real-time grade tracking</li>
        <li><span class="check">✔️</span> Easy-to-use interface for teachers and students</li>
        <li><span class="check">✔️</span> Admin tools for managing users and data</li>
      </ul>
      <a href="login.php" class="btn btn-landing-main mb-2">Login</a>
      <a href="register.php" class="btn btn-landing-outline">Register</a>
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

