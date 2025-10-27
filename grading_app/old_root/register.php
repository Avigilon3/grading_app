<?php
session_start();
require_once 'includes/config.php'; // your PDO connection

// CSRF token setup
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token check
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Invalid CSRF token.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $role = $_POST['role'] ?? '';
        $student_id = trim($_POST['student_id'] ?? '');

        // Basic validation
        if (!$username) {
            $errors[] = "Username is required.";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Valid email is required.";
        }
        if (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters.";
        }
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }

        // Validate role
        $allowed_roles = ['student', 'admin'];
        if (!in_array($role, $allowed_roles)) {
            $errors[] = "Please select a valid role.";
        }

        // If role is student, student_id is required and must be alphanumeric (adjust pattern as needed)
        if ($role === 'student') {
            if (!$student_id) {
                $errors[] = "Student ID is required for students.";
            } elseif (!preg_match('/^[a-zA-Z0-9\-]+$/', $student_id)) {
                $errors[] = "Student ID can only contain letters, numbers, and hyphens.";
            }
        } else {
            // Clear student_id if role isn't student
            $student_id = null;
        }

        if (empty($errors)) {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Username or email already taken.";
            } else {
                // Hash password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // Insert new user with role and optional student_id
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, student_id) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$username, $email, $password_hash, $role, $student_id])) {
                    $success = "Registration successful! You can now <a href='login.php'>login</a>.";
                    // Clear form inputs after success
                    $_POST = [];
                } else {
                    $errors[] = "Registration failed. Please try again.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Register - Student Grading System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body { background: #f9fafb; min-height: 100vh; }
    .center-card { max-width: 520px; margin: 80px auto; background: #f5f6fa; border-radius: 22px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); padding: 3.5rem 2.5rem; text-align: center; }
    .center-card h1 { font-size: 2rem; font-weight: 700; color: #444; margin-bottom: 2rem; letter-spacing: -1px; }
    .form-label { color: #666; font-weight: 500; font-size: 1.1rem; }
    .form-control { border-radius: 14px; background: #f8f9fb; border: 1px solid #e0e0e0; color: #444; font-size: 1.13rem; padding: 0.9rem 1rem; }
    .form-control:focus { background: #f5f6fa; color: #222; border-color: #bfc4c9; box-shadow: none; }
    .btn-soft { background: #f0f1f4; color: #444; border-radius: 18px; border: none; padding: 1rem 1.2rem; font-size: 1.18rem; margin-bottom: 1.1rem; transition: background 0.2s; width: 100%; font-weight: 500; }
    .btn-soft:hover { background: #e4e5e9; color: #222; }
    .btn-outline-soft { background: none; color: #555; border: 1px solid #e0e0e0; border-radius: 18px; padding: 1rem 1.2rem; font-size: 1.18rem; width: 100%; transition: background 0.2s, color 0.2s; font-weight: 500; }
    .btn-outline-soft:hover { background: #f0f1f4; color: #222; }
    .alert-soft { background: #f6fff6; color: #3a5c3a; border: none; border-radius: 12px; font-size: 1.08rem; margin-bottom: 1.3rem; }
    .divider { border-bottom: 1px solid #ececec; margin: 2.2rem 0; }
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
      <h1>Register</h1>
      <?php if ($errors): ?>
        <div class="alert alert-soft" role="alert">
          <ul class="mb-0" style="text-align:left;">
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-soft" role="alert">
          <?= $success ?>
        </div>
      <?php endif; ?>
      <form method="post" action="register.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>" />
        <div class="mb-4 text-start">
          <label for="username" class="form-label">Username</label>
          <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
        </div>
        <div class="mb-4 text-start">
          <label for="email" class="form-label">Email</label>
          <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="divider"></div>
        <div class="mb-4 text-start">
          <label for="password" class="form-label">Password</label>
          <input type="password" id="password" name="password" class="form-control" required>
        </div>
        <div class="mb-4 text-start">
          <label for="confirm_password" class="form-label">Confirm Password</label>
          <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
        </div>
        <div class="divider"></div>
        <div class="mb-4 text-start">
          <label for="role" class="form-label">Role</label>
          <select id="role" name="role" class="form-control" required>
            <option value="">Select Role</option>
            <option value="student" <?= (($_POST['role'] ?? '') === 'student') ? 'selected' : '' ?>>Student</option>
            <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
          </select>
        </div>
        <div class="mb-4 text-start" id="studentIdField" style="display: none;">
          <label for="student_id" class="form-label">Student ID</label>
          <input type="text" id="student_id" name="student_id" class="form-control" value="<?= htmlspecialchars($_POST['student_id'] ?? '') ?>">
        </div>
        <div class="d-grid gap-2 mt-4">
          <button type="submit" class="btn btn-soft">Register</button>
          <a href="login.php" class="btn btn-outline-soft">Already have an account? Login</a>
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
    // Show/hide student ID field
    document.getElementById('role').addEventListener('change', function() {
      document.getElementById('studentIdField').style.display = this.value === 'student' ? '' : 'none';
    });
    // On load, show if student selected
    if (document.getElementById('role').value === 'student') {
      document.getElementById('studentIdField').style.display = '';
    }
  </script>
</body>
</html>
