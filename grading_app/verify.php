<?php
require_once 'core/config/config.php';
require_once 'core/auth/session.php';
require_once 'core/db/connection.php';

$pending = $_SESSION['pending_reg'] ?? null;
if (!$pending) {
    set_flash('error', 'Please start your registration first.');
    header('Location: ' . BASE_URL . '/register.php');
    exit;
}

$err = get_flash('error');
$msg = get_flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    $email = $pending['email'];

    if (!$code) {
        set_flash('error', 'Please enter the verification code.');
        header('Location: ' . BASE_URL . '/verify.php');
        exit;
    }

    $stmt = $pdo->prepare('SELECT id, email, role, first_name, last_name, verify_code FROM users WHERE email=? LIMIT 1');
    $stmt->execute([$email]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$u || ($u['verify_code'] ?? '') !== $code) {
        set_flash('error', 'Invalid or expired verification code.');
        header('Location: ' . BASE_URL . '/verify.php');
        exit;
    }

    $hash = password_hash($pending['password'], PASSWORD_BCRYPT);
    $first = $pending['first_name'] ?? ($u['first_name'] ?? '');
    $last  = $pending['last_name'] ?? ($u['last_name'] ?? '');
    $role  = $pending['role'] ?? ($u['role'] ?? 'student');

    $upd = $pdo->prepare('UPDATE users SET password_hash=?, verify_code=NULL, first_name=?, last_name=?, role=? WHERE id=?');
    $upd->execute([$hash, $first, $last, $role, $u['id']]);

    unset($_SESSION['pending_reg']);

    $_SESSION['user'] = [
        'id'    => $u['id'],
        'email' => $email,
        'role'  => $role,
        'name'  => trim($first . ' ' . $last),
    ];

    // Notify admins about the new verified user
    try {
        $adminStmt = $pdo->query("SELECT id FROM users WHERE role IN ('admin','super_admin','registrar','mis')");
        $adminIds = $adminStmt->fetchAll(PDO::FETCH_COLUMN);
        if ($adminIds) {
            $notif = $pdo->prepare('INSERT INTO notifications (user_id, type, message, is_read) VALUES (?, ?, ?, 0)');
            foreach ($adminIds as $adminId) {
                $notif->execute([
                    $adminId,
                    'new_user',
                    'New user registered: ' . $email . ' (' . ucfirst($role) . ')'
                ]);
            }
        }
    } catch (Exception $e) {
        // do not block login on notification failure
    }

    if ($role === 'admin' || $role === 'registrar' || $role === 'mis' || $role === 'super_admin') {
        header('Location: ' . BASE_URL . '/admin/index.php');
        exit;
    }
    if ($role === 'professor') {
        header('Location: ' . BASE_URL . '/professor/index.php');
        exit;
    }
    header('Location: ' . BASE_URL . '/student/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Online Grading System</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Arimo:ital,wght@0,400..700;1,400..700&family=Bayon&family=Space+Grotesk:wght@300..700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        :root {
            --primary-green: #145a32;
            --primary-yellow: #FFD700;
            --hero-green: #0e5130;
            --accent-blue: #1bb8d1;
            --highlight-yellow: #ffd43b;
            --border-muted: #dfe6eb;
            --text-dark: #162125;
            --text-muted: #5f6b74;
            --text-base: #F9F9F9;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Arimo', Arial, sans-serif;
            background: #f0f3f6;
            color: var(--text-dark);
        }
        .page-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .hero {
            position: relative;
            padding: 56px 16px 90px;
            backdrop-filter: blur(3px);
            background: 
            linear-gradient(120deg, rgba(6, 64, 42, 0.92), rgba(6, 98, 80, 0.7)),
            linear-gradient(180deg, rgba(0, 56, 32, 0.75), rgba(10, 104, 72, 0.65)),
                url('admin/assets/images/background.jpg') center/cover no-repeat;
            mix-blend-mode: multiply;
            
            color: #fff;
            text-align: center;
        }
        .hero::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 27px;
            background: linear-gradient(180deg, #FFD700 25.96%, #998100 99.98%, #A08700 99.99%);
        }
        .hero-inner {
            position: relative;
            z-index: 1;
            max-width: 720px;
            margin: 0 auto;
        }
        .hero-logo {
            width: 110px;
            height: 110px;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            flex-shrink: 0;
            aspect-ratio: 54/55;
            border: 10px solid rgba(255, 255, 255, 0.20);
            border-radius: 50%;
        }
        .hero h1 {
            color: var(--text-base);
            text-align: center;
            font-family: Bayon;
            font-size: 35px;
            font-weight: 400;
            line-height: 35px;
        }
        .hero h2 {
            font-size: clamp(2rem, 5vw, 3rem);
            font-family: "Space Grotesk", sans-serif;
            font-weight: 700;
            text-align: center;
            color: var(--primary-yellow);
            margin: 0;
            text-shadow: 0 6px 18px rgba(0, 0, 0, 0.3);
        }
        .hero p {
          color: var(--text-base);
          font-family: Arimo;
          font-size: 18px;
          font-weight: 400;
          line-height: 28px;
        }
        .auth-area {
            flex: 1;
            background:
              linear-gradient(180deg, rgba(255, 255, 255, 0.47) 0%, rgba(255, 255, 255, 0.95) 50.96%),
              url('admin/assets/images/ptcfront.png') center/cover no-repeat;
            padding: 48px 16px 56px;
            display: flex;
            justify-content: center;
        }
        .auth-card {
            width: min(440px, 100%);
            background: #fff;
            border-radius: 28px;
            box-shadow: 0 30px 70px rgba(17, 48, 59, 0.18);
            padding: 28px 32px 36px;
        }
        .auth-tabs {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            padding: 4px;
            border-radius: 999px;
            background: #eef3f6;
            margin-bottom: 24px;
            position: relative;
        }
        .auth-tabs a {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            padding: 10px 0;
            border-radius: 999px;
            font-weight: 600;
            text-decoration: none;
            color: var(--text-muted);
            transition: 0.3s ease;
        }
        .auth-tabs a.active {
            background: linear-gradient(90deg, #0e7a4c, #0c613c);
            color: #fff;
        }
        .welcome-text h3 {
            color: #618A61;
            margin: 0;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .welcome-text p {
            margin: 4px 0 18px;
            color: var(--text-muted);
            display: flex;
            text-align: center;
            align-items: center;
            justify-content: center;
        }
        .alert {
            background: #fdecea;
            color: #a12622;
            border: 1px solid #fac2bc;
            border-radius: 16px;
            padding: 12px 16px;
            font-size: 0.95rem;
            margin-bottom: 16px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        label {
            font-weight: 500;
            font-size: 0.95rem;
            display: block;
            margin-bottom: 6px;
        }
        .input-field {
            display: flex;
            flex-direction: column;
        }
        .field-box {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border-radius: 14px;
            border: 1px solid var(--border-muted);
            background: #f9fbfd;
        }
        .field-box input {
            border: none;
            background: transparent;
            outline: none;
            flex: 1;
            font-size: 1rem;
            color: var(--text-dark);
            font-family: inherit;
        }
        .page-footer {
            text-align: center;
            margin-top: 28px;
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        @media (max-width: 520px) {
            .auth-card {
                padding: 24px 20px 30px;
                border-radius: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <section class="hero">
            <div class="hero-inner">
                <img src="admin/assets/images/logo-ptc.png" alt="PTC Logo" class="hero-logo">
                <h1>PATEROS TECHNOLOGICAL COLLEGE</h1>
                <p>Gearing the way to your future!</p>
                <h2>Online Grading System</h2>
            </div>
        </section>

        <section class="auth-area">
            <div class="auth-card">
                <div class="auth-tabs">
                    <a href="login.php">Login</a>
                    <a href="register.php" class="active">Register</a>
                </div>

                <div class="welcome-text">
                    <h3>Hi PTCian!</h3>
                    <p>Sign up using your PTC email address to access your portal.</p>
                </div>

                    <h2>Email Verification</h2>
                      <p>We sent a 6-digit code to <strong><?= htmlspecialchars($pending['email']) ?></strong>.</p>

                      <?php if ($err): ?>
                          <div class="alert"><?= htmlspecialchars($err) ?></div>
                      <?php endif; ?>
                      <?php if ($msg && !$err): ?>
                          <div class="alert" style="background:#e8f6ed;border-color:#b6e2c3;color:#1c6b34;">
                              <?= htmlspecialchars($msg) ?>
                          </div>
                      <?php endif; ?>

                      <form method="post">
                          <label>Verification Code</label><br>
                          <input type="text" name="code" maxlength="6" required><br><br>
                          <button type="submit">Verify</button>
                      </form>

                      <div>
                          <a href="register.php">Back to registration</a>
                      </div>
            </div>
        </section>
    </div>

  
</body>
</html>
