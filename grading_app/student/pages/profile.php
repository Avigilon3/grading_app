<?php
require_once '../includes/init.php';
requireStudent();
session_start();


// Function to safely prepare and execute SQL statements
function safeQuery($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        die("An error occurred. Please try again later.");
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // First get user data without joins to verify user exists
    $user = safeQuery($pdo, "SELECT * FROM users WHERE id = ?", [$user_id])->fetch();
    
    if (!$user) {
        die("User not found. Please log in again.");
    }
    
    // Then get the full user data with student info
    $sql = "SELECT 
                u.*,
                s.name as student_name,
                s.email as student_email
            FROM users u 
            LEFT JOIN students s ON s.user_id = u.id 
            WHERE u.id = ?";
    
    $userData = safeQuery($pdo, $sql, [$user_id])->fetch();
    
    // Set display values
    $user['display_name'] = $userData['student_name'] ?? $user['username'];
    $user['email'] = $userData['student_email'] ?? '';
    
    $errors = [];
    
    // Validate current password if trying to change password
    if (!empty($new_password)) {
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect";
        }
        if ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Update or create student record
            $stmt = safeQuery($pdo, "SELECT id FROM students WHERE user_id = ?", [$user_id]);
            $student = $stmt->fetch();
            
            if ($student) {
                safeQuery($pdo, 
                    "UPDATE students SET name = ?, email = ? WHERE user_id = ?",
                    [$name, $email, $user_id]
                );
            } else {
                safeQuery($pdo,
                    "INSERT INTO students (user_id, name, email) VALUES (?, ?, ?)",
                    [$user_id, $name, $email]
                );
            }
            
            // Update password if provided
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                safeQuery($pdo,
                    "UPDATE users SET password = ? WHERE id = ?",
                    [$hashed_password, $user_id]
                );
            }
            
            $pdo->commit();
            $success = "Profile updated successfully!";
            
            // Refresh user data
            $userData = safeQuery($pdo, $sql, [$user_id])->fetch();
            $user['display_name'] = $userData['student_name'] ?? $user['username'];
            $user['email'] = $userData['student_email'] ?? '';
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Transaction failed: " . $e->getMessage());
            die("An error occurred while updating your profile. Please try again later.");
        }
    }
}

// Get current user data
$user = safeQuery($pdo, "SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']])->fetch();

if (!$user) {
    header("Location: login.php");
    exit;
}

// Get extended user data with student info
$sql = "SELECT 
            u.*,
            s.name as student_name,
            s.email as student_email
        FROM users u 
        LEFT JOIN students s ON s.user_id = u.id 
        WHERE u.id = ?";

$userData = safeQuery($pdo, $sql, [$_SESSION['user_id']])->fetch();

// Set display values
$user['display_name'] = $userData['student_name'] ?? $user['username'];
$user['email'] = $userData['student_email'] ?? '';
?>

<!DOCTYPE html>
<html lang="en" data-theme="<?= isset($_SESSION['theme_preference']) ? htmlspecialchars($_SESSION['theme_preference']) : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Grading System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f8f9fa;
            --text-color: #212529;
            --card-bg: #ffffff;
            --border-color: #dee2e6;
            --input-bg: #ffffff;
        }

        [data-theme="dark"] {
            --bg-color: #121212;
            --text-color: #f8f9fa;
            --card-bg: #1e1e1e;
            --border-color: #2d2d2d;
            --input-bg: #2d2d2d;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            min-height: 100vh;
        }

        .profile-card {
            background-color: var(--card-bg);
            border-color: var(--border-color);
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-top: 2rem;
        }

        .form-control {
            background-color: var(--input-bg);
            border-color: var(--border-color);
            color: var(--text-color);
        }

        .form-control:focus {
            background-color: var(--input-bg);
            color: var(--text-color);
        }

        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: #0d6efd;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            margin: 0 auto 1rem;
        }

        .btn-profile {
            padding: 0.5rem 2rem;
            border-radius: 25px;
        }

        .section-title {
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <h2><?= htmlspecialchars($user['display_name']) ?></h2>
                    <p class="text-muted"><?= htmlspecialchars($user['role']) ?></p>
                </div>

                <?php if (isset($errors)): ?>
                    <?php foreach ($errors as $error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <h4 class="section-title">
                        <i class="fas fa-user-edit"></i> Basic Information
                    </h4>
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-user"></i>
                            </span>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['display_name']) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-user-tag"></i>
                            </span>
                            <input type="text" class="form-control" value="<?= htmlspecialchars(ucfirst($user['role'])) ?>" disabled>
                        </div>
                    </div>

                    <h4 class="section-title mt-4">
                        <i class="fas fa-lock"></i> Change Password
                    </h4>
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" name="current_password" class="form-control" placeholder="Enter current password">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-key"></i>
                            </span>
                            <input type="password" name="new_password" class="form-control" placeholder="Enter new password">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-key"></i>
                            </span>
                            <input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="dashboard.php" class="btn btn-secondary btn-profile">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                        <button type="submit" class="btn btn-primary btn-profile">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
