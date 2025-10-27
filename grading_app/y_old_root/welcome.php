<?php 
session_start();
require_once 'includes/config.php';

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: teacher/grading_system.php');
            break;
        case 'student':
            header('Location: student/dashboard.php');
            break;
        default:
            header('Location: login.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to PTC GRADING SYSTEM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    body { background: #f9fafb; min-height: 100vh; }
    .welcome-card { max-width: 520px; margin: 80px auto; background: #f5f6fa; border-radius: 22px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); padding: 3.5rem 2.5rem; text-align: center; }
    .welcome-card .icon { font-size: 4.2em; margin-bottom: 0.7rem; }
    .welcome-card h1 { font-size: 2.2rem; font-weight: 700; color: #444; margin-bottom: 0.7rem; letter-spacing: -1px; }
    .welcome-card .subtitle { color: #666; font-size: 1.18rem; margin-bottom: 2.2rem; font-weight: 400; }
    .btn-welcome-main { background: #e9ecf3; color: #3a3a3a; border-radius: 18px; border: none; padding: 1.1rem 1.2rem; font-size: 1.18rem; margin-bottom: 1.1rem; transition: background 0.2s; width: 100%; font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
    .btn-welcome-main:hover { background: #dde2ea; color: #222; }
    .btn-welcome-outline { background: none; color: #555; border: 2px solid #e0e0e0; border-radius: 18px; padding: 1.1rem 1.2rem; font-size: 1.18rem; width: 100%; transition: background 0.2s, color 0.2s; font-weight: 600; }
    .btn-welcome-outline:hover { background: #f0f1f4; color: #222; }
    .theme-toggle { position: fixed; top: 1.5rem; right: 1.5rem; background: #f0f1f4; color: #888; border: none; border-radius: 50%; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; box-shadow: 0 1px 4px rgba(0,0,0,0.03); transition: background 0.2s; }
    .theme-toggle:hover { background: #e4e5e9; color: #444; }
    </style>
</head>
<body>
    <button class="theme-toggle" id="themeToggle" title="Toggle dark mode">
        <span id="themeIcon">🌙</span>
    </button>
    <div class="container d-flex align-items-center justify-content-center min-vh-100">
        <div class="welcome-card">
            <div class="icon">🎓</div>
            <h1>Welcome</h1>
            <div class="subtitle">A modern, friendly, and efficient way to manage student grades.<br>Get started below!</div>
            <a href="register.php" class="btn btn-welcome-main mb-2">Register</a>
            <a href="login.php" class="btn btn-welcome-outline">Login</a>
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

