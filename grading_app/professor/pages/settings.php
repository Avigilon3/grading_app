<?php
session_start();

// --- Database connection ---
$host = 'http://localhost/grading_app/';
$dbname = 'professor_portal';
$user = 'root';  // teacher1
$pass = '';      // password

$mysqli = new mysqli($host, $user, $pass, $dbname);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Simulate logged-in professor id; replace with your auth/session system
$professor_id = 1;

// Fetch professor data
$stmt = $mysqli->prepare("SELECT id, full_name, email, role, prof_id FROM professors WHERE id = ?");
$stmt->bind_param('i', $professor_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    die("Professor not found.");
}
$professor = $res->fetch_assoc();
$stmt->close();

$profile_msg = $password_msg = '';
$errors = ['profile' => [], 'password' => []];

// Handle Profile Info update
if (isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // Validate inputs
    if ($full_name === '') {
        $errors['profile'][] = 'Full Name is required.';
    }
    if ($email === '') {
        $errors['profile'][] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['profile'][] = 'Invalid email address format.';
    } else {
        // Check for duplicate email
        $stmt = $mysqli->prepare("SELECT id FROM professors WHERE email = ? AND id != ?");
        $stmt->bind_param('si', $email, $professor_id);
        $stmt->execute();
        $stmt_res = $stmt->get_result();
        if ($stmt_res->num_rows > 0) {
            $errors['profile'][] = 'Email address already in use by another account.';
        }
        $stmt->close();
    }

    if (empty($errors['profile'])) {
        // Update database
        $stmt = $mysqli->prepare("UPDATE professors SET full_name=?, email=? WHERE id=?");
        $stmt->bind_param('ssi', $full_name, $email, $professor_id);

        if ($stmt->execute()) {
            $profile_msg = "Profile information updated successfully.";
            // Update local variable to show updated values
            $professor['full_name'] = $full_name;
            $professor['email'] = $email;
        } else {
            $errors['profile'][] = "Failed to update profile. Please try again.";
        }
        $stmt->close();
    }
}

// Handle Change Password
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Fetch current password hash
    $stmt = $mysqli->prepare("SELECT password_hash FROM professors WHERE id = ?");
    $stmt->bind_param('i', $professor_id);
    $stmt->execute();
    $stmt_res = $stmt->get_result();
    $row = $stmt_res->fetch_assoc();
    $current_hash = $row['password_hash'] ?? '';
    $stmt->close();

    // Validate inputs
    if ($current_password === '') {
        $errors['password'][] = 'Please enter your current password.';
    } elseif (!password_verify($current_password, $current_hash)) {
        $errors['password'][] = 'Current password is incorrect.';
    }

    if ($new_password === '') {
        $errors['password'][] = 'Please enter a new password.';
    } elseif (strlen($new_password) < 6) {
        $errors['password'][] = 'New password must be at least 6 characters.';
    }

    if ($confirm_password === '') {
        $errors['password'][] = 'Please confirm your new password.';
    } elseif ($confirm_password !== $new_password) {
        $errors['password'][] = 'New password and confirmation do not match.';
    }

    if (empty($errors['password'])) {
        // Update password hash
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("UPDATE professors SET password_hash=? WHERE id=?");
        $stmt->bind_param('si', $new_hash, $professor_id);
        if ($stmt->execute()) {
            $password_msg = "Password changed successfully.";
        } else {
            $errors['password'][] = "Failed to change password. Please try again.";
        }
        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Professor Portal - Account Settings</title>
<style>
    /* Layout */
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0; background-color: #f9fafb; color: #212529;
    }
    .sidebar {
        width: 220px;
        background-color: #4f744e;
        color: white;
        height: 100vh;
        position: fixed;
        padding: 1.5em 1em;
        box-sizing: border-box;
    }
    .sidebar h1 {
        font-size: 1.3rem;
        margin-bottom: 2em;
    }
    .sidebar a {
        display: block;
        margin: 1em 0;
        color: white;
        text-decoration: none;
        font-weight: 600;
        padding: 0.3em 0.5em;
        border-radius: 6px;
    }
    .sidebar a.active, .sidebar a:hover {
        background-color: #6f9568;
    }

    .main {
        margin-left: 220px;
        padding: 2.5em 3em;
        max-width: 1000px;
    }
    
    h2 {
        color: #1c3d14;
        margin-top: 0;
    }
    p.subheading {
        color: #4a4a4a;
        margin-top: -0.5rem;
        margin-bottom: 1.7rem;
    }

    /* Card style */
    .card {
        background: white;
        border-radius: 1em;
        padding: 2em;
        margin-bottom: 3em;
        box-shadow: 0 3px 10px rgb(0 0 0 / 0.1);
    }

    label {
        font-weight: 600;
        display: block;
        margin-bottom: 0.4em;
        margin-top: 1.3em;
        color: #32492f;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"] {
        width: 100%;
        padding: 10px 14px;
        font-size: 1rem;
        border: 1px solid #cfd4d4;
        border-radius: 8px;
        box-sizing: border-box;
        color: #414141;
    }
    input[readonly] {
        background: #e7f0db;
        color: #6a7a49;
        cursor: not-allowed;
    }

    /* Button */
    button {
        background-color: #5a7a4f;
        color: white;
        padding: 0.65em 1.4em;
        margin-top: 1.8em;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 700;
        font-size: 1rem;
        transition: background-color 0.3s ease;
    }
    button:hover:not(:disabled) {
        background-color: #4b6b3f;
    }
    button:disabled {
        background-color: #a4b39a;
        cursor: not-allowed;
    }

    /* Messages */
    .message {
        margin-bottom: 1em;
        padding: 0.85em 1em;
        border-radius: 6px;
        font-weight: 600;
    }
    .message.success {
        background-color: #daf2d4;
        color: #2f612b;
        border: 1.5px solid #5a7a4f;
    }
    .message.error {
        background-color: #fddede;
        color: #b6141d;
        border: 1.5px solid #d94a49;
    }

    /* Password toggle button */
    .pw-toggle {
        position: relative;
        width: 100%;
        margin-top: 0.4em;
    }
    .pw-toggle-input {
        width: 100%;
        padding-right: 40px;
    }
    .pw-toggle-btn {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        border: none;
        background: none;
        cursor: pointer;
        color: #6a7a49;
        font-size: 1.1rem;
    }
</style>
<script>
function togglePasswordVisibility(id, btn) {
    const input = document.getElementById(id);
    if (input.type === "password") {
        input.type = "text";
        btn.textContent = "🙈";
    } else {
        input.type = "password";
        btn.textContent = "👁️";
    }
}
</script>
</head>
<body>

<div class="sidebar">
    <h1>Professor Portal</h1>
    <a href="dashboard.php">Dashboard</a>
    <a href="classes.php">Classes</a>
    <a href="grading_sheet.php">Grading Sheets</a>
    <a href="requests.php">Requests</a>
    <a href="prof_settings.php" class="active">Account Settings</a>
</div>

<div class="main">
    <h2>Account Settings</h2>
    <p class="subheading">Manage your account settings and security preferences</p>

    <!-- Profile Information -->
    <div class="card" aria-label="Profile Information">
        <h3 style="margin-top:0; font-weight:700; color:#536a45;">
            Profile Information
        </h3>

        <?php if (!empty($profile_msg)): ?>
            <div class="message success"><?= htmlspecialchars($profile_msg) ?></div>
        <?php endif; ?>
        <?php if (!empty($errors['profile'])): ?>
            <div class="message error">
                <ul style="margin:0; padding-left: 1.2em;">
                    <?php foreach ($errors['profile'] as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="prof_settings.php" novalidate>
            <label for="full_name">Full Name</label>
            <input 
                type="text" 
                id="full_name" 
                name="full_name" 
                value="<?= htmlspecialchars($professor['full_name']) ?>" 
                required
            />

            <label for="role">Role</label>
            <input 
                type="text" 
                id="role" 
                name="role" 
                value="<?= htmlspecialchars($professor['role']) ?>" 
                readonly
                tabindex="-1"
            />

            <label for="email">Email</label>
            <input 
                type="email" 
                id="email" 
                name="email" 
                value="<?= htmlspecialchars($professor['email']) ?>" 
                required
            />

            <label for="prof_id">ID number</label>
            <input 
                type="text" 
                id="prof_id" 
                name="prof_id" 
                value="<?= htmlspecialchars($professor['prof_id']) ?>" 
                readonly 
                tabindex="-1"
            />

            <button type="submit" name="update_profile" aria-label="Update Profile">Update Profile</button>
        </form>
    </div>

    <!-- Change Password -->
    <div class="card" aria-label="Change Password">
        <h3 style="margin-top:0; font-weight:700; color:#536a45;">
            Change Password
        </h3>

        <?php if (!empty($password_msg)): ?>
            <div class="message success"><?= htmlspecialchars($password_msg) ?></div>
        <?php endif; ?>
        <?php if (!empty($errors['password'])): ?>
            <div class="message error">
                <ul style="margin:0; padding-left: 1.2em;">
                    <?php foreach ($errors['password'] as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="prof_settings.php" novalidate>
            <label for="current_password">Current Password</label>
            <div class="pw-toggle">
                <input 
                    type="password" 
                    name="current_password" 
                    id="current_password" 
                    class="pw-toggle-input"
                    placeholder="Enter current password" 
                    required 
                    aria-required="true"
                    autocomplete="current-password"
                />
                <button 
                    type="button" 
                    class="pw-toggle-btn" 
                    aria-label="Toggle Current Password Visibility" 
                    tabindex="-1"
                    onclick="togglePasswordVisibility('current_password', this)">👁️</button>
            </div>

            <label for="new_password">New Password</label>
            <div class="pw-toggle">
                <input 
                    type="password" 
                    name="new_password" 
                    id="new_password" 
                    class="pw-toggle-input"
                    placeholder="Enter new password" 
                    required 
                    aria-required="true"
                    autocomplete="new-password"
                />
                <button 
                    type="button" 
                    class="pw-toggle-btn" 
                    aria-label="Toggle New Password Visibility" 
                    tabindex="-1"
                    onclick="togglePasswordVisibility('new_password', this)">👁️</button>
            </div>

            <label for="confirm_password">Confirm New Password</label>
            <div class="pw-toggle">
                <input 
                    type="password" 
                    name="confirm_password" 
                    id="confirm_password" 
                    class="pw-toggle-input"
                    placeholder="Confirm new password" 
                    required 
                    aria-required="true"
                    autocomplete="new-password"
                />
                <button 
                    type="button" 
                    class="pw-toggle-btn" 
                    aria-label="Toggle Confirm Password Visibility" 
                    tabindex="-1"
                    onclick="togglePasswordVisibility('confirm_password', this)">👁️</button>
            </div>

            <button type="submit" name="change_password" aria-label="Change Password">Change Password</button>
        </form>
    </div>
</div>

</body>
</html>
