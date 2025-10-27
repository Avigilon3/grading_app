<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure a CSRF token exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Determine theme (default to light)
$theme = $_SESSION['theme_preference'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= htmlspecialchars($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grading System</title>
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <link 
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" 
      rel="stylesheet" 
      integrity="sha384-..." 
      crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/chart.js" integrity="sha384-..." crossorigin="anonymous"></script>
    <style>
      :root {
        --bg-color: #f8f9fa;
        --text-color: #212529;
        --card-bg: #fff;
      }
      [data-theme="dark"] {
        --bg-color: #121212;
        --text-color: #f8f9fa;
        --card-bg: #1e1e1e;
      }
      body {
        background: var(--bg-color);
        color: var(--text-color);
        padding-top: 70px; /* space for navbar */
      }
      .card {
        background: var(--card-bg);
      }
      .dark-mode-toggle {
        position: fixed;
        top: 1rem;
        right: 1rem;
      }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
  <div class="container">
    <a class="navbar-brand" href="/grading_app/index.php">Grading System</a>
    <div class="d-flex align-items-center">
      <button id="themeToggle" class="btn btn-outline-light me-3">
        <?= $theme === 'dark' ? '☀️ Light' : '🌙 Dark' ?>
      </button>
      <span class="navbar-text me-3">
        Hello, <?= htmlspecialchars($_SESSION['username'] ?? 'Guest') ?>
      </span>
      <a href="/grading_app/includes/logout.php" class="btn btn-outline-light">Logout</a>
    </div>
  </div>
</nav>

<script>
// Dark mode toggle logic
const toggle = document.getElementById('themeToggle');
toggle.addEventListener('click', () => {
  const current = document.documentElement.getAttribute('data-theme');
  const next = current === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', next);

  // Persist choice via AJAX to a PHP endpoint
  fetch('/grading_app/includes/session.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({ theme: next })
  }).then(() => {
    toggle.textContent = next === 'dark' ? '☀️ Light' : '🌙 Dark';
  });
});
</script>
