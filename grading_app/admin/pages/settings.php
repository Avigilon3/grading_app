<?php
require_once '../includes/init.php';
requireAdmin();

// Handle Settings form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'set_active_term') {
            $termId = (int)($_POST['active_term_id'] ?? 0);
            $pdo->beginTransaction();
            $pdo->exec("UPDATE terms SET is_active = 0");
            if ($termId > 0) {
                $stmt = $pdo->prepare("UPDATE terms SET is_active = 1 WHERE id = ?");
                $stmt->execute([$termId]);
            }
            $pdo->commit();
            $userId = $_SESSION['user']['id'] ?? null;
            add_activity_log($pdo, $userId, 'SET_ACTIVE_TERM', 'Active term id: ' . $termId);
            set_flash('success', 'Active term updated.');
            header('Location: ./settings.php');
            exit;
        }

        if ($action === 'update_profile') {
            $first = trim($_POST['first_name'] ?? '');
            $last  = trim($_POST['last_name'] ?? '');
            $uid   = (int)($_SESSION['user']['id'] ?? 0);
            if ($uid > 0) {
                $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ? WHERE id = ?");
                $stmt->execute([$first ?: null, $last ?: null, $uid]);
                $_SESSION['user']['first_name'] = $first;
                $_SESSION['user']['last_name']  = $last;
                add_activity_log($pdo, $uid, 'UPDATE_PROFILE', 'Updated name');
                set_flash('success', 'Profile updated.');
            }
            header('Location: ./settings.php');
            exit;
        }

        if ($action === 'change_password') {
            $uid = (int)($_SESSION['user']['id'] ?? 0);
            $current = $_POST['current_password'] ?? '';
            $new     = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            if ($uid <= 0) { throw new Exception('No user session.'); }
            if ($new !== $confirm) { throw new Exception('New passwords do not match.'); }
            if (strlen($new) < 8) { throw new Exception('Password must be at least 8 characters.'); }
            $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
            $stmt->execute([$uid]);
            $hash = (string)$stmt->fetchColumn();
            if ($hash && !password_verify($current, $hash)) {
                throw new Exception('Current password is incorrect.');
            }
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $upd = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $upd->execute([$newHash, $uid]);
            add_activity_log($pdo, $uid, 'CHANGE_PASSWORD', 'Password updated');
            set_flash('success', 'Password changed successfully.');
            header('Location: ./settings.php');
            exit;
        }
    } catch (Exception $e) {
        set_flash('error', $e->getMessage());
        header('Location: ./settings.php');
        exit;
    }
}

// Load data for forms
$terms = [];
try {
    $q = $pdo->query('SELECT id, term_name, school_year, is_active FROM terms ORDER BY start_date DESC, id DESC');
    $terms = $q->fetchAll();
} catch (Exception $e) { /* ignore */ }

$me = $_SESSION['user'] ?? [];
$firstName = $me['first_name'] ?? '';
$lastName  = $me['last_name'] ?? '';
$email     = $me['email'] ?? '';

try {
    $uid = (int)($me['id'] ?? 0);
    if ($uid > 0) {
        $stmt = $pdo->prepare('SELECT first_name, last_name, email FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$uid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            if (!empty($row['first_name'])) {
                $firstName = $row['first_name'];
            }
            if (!empty($row['last_name'])) {
                $lastName = $row['last_name'];
            }
            if (!empty($row['email'])) {
                $email = $row['email'];
            }
        }
    }
} catch (Exception $e) {
  
}

$fullName = trim($firstName . ' ' . $lastName);
if ($fullName === '') {
    $fullName = $me['name'] ?? ($email ?: 'Admin');
}
?>

<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Settings</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
  </head>
  <body>
    <?php include '../includes/header.php'; ?>
    <div class="layout">
      <?php include '../includes/sidebar.php'; ?>
      <main class="content">
        <?php show_flash(); ?>

        <div class="page-header">
          <h2>Settings</h2>
          <p class="text-muted">Manage portal preferences and your account.</p>
        </div>

        <div class="row-grid cols-1">
          <div class="form-box">
            <h3>General</h3>
            <p class="muted">Select the active term used across the portal.</p>
            <form method="post">
              <input type="hidden" name="action" value="set_active_term">
              <label>Active Term</label>
              <select class="form-control" name="active_term_id" required>
                <?php foreach ($terms as $t): ?>
                  <option value="<?= (int)$t['id']; ?>" <?= ((int)$t['is_active'] === 1 ? 'selected' : '') ?>>
                    <?= htmlspecialchars($t['term_name'] ?: ('SY ' . ($t['school_year'] ?? ''))) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="form-actions" style="margin-top:12px">
                <button type="submit">Save</button>
              </div>
            </form>
          </div>

          <div class="form-box">
            <div class="page-header icon">
              <span class="material-symbols-rounded">account_circle</span>
              <h3>Profile Information</h3>
            </div>
            <div class="row-grid cols-2">
              <div>
                <label>Full Name</label>
                <input class="form-control" type="text" value="<?= htmlspecialchars($fullName ?: 'Not set'); ?>" readonly>
              </div>
              <div>
                <label>Email Address</label>
                <input class="form-control" type="email" value="<?= htmlspecialchars($email ?: ''); ?>" readonly>
              </div>
            </div>
          </div>
        </div>

        <div class="row-grid cols-1">
          <div class="form-box">
            <div class="page-header icon">
              <span class="material-symbols-rounded">settings</span>
              <h3>Change Password</h3>
            </div>
            <form method="post">
              <input type="hidden" name="action" value="change_password">
              <label>Current Password</label>
              <input class="form-control" type="password" name="current_password" required>
              <label>New Password</label>
              <input class="form-control" type="password" name="new_password" minlength="8" required>
              <label>Confirm New Password</label>
              <input class="form-control" type="password" name="confirm_password" minlength="8" required>
              <div class="form-actions" style="margin-top:12px">
                <button type="submit">Change Password</button>
              </div>
            </form>
          </div>
        </div>
      </main>
    </div>
  </body>
  <script src="../assets/js/admin.js"></script>
</html>
