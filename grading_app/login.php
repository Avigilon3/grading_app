<?php
require_once 'core/config/config.php';
require_once 'core/auth/session.php';

$err = null;
$flashError = get_flash('error');
$flashSuccess = get_flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = trim($_POST['password'] ?? '');

    if ($email && $pass) {
        require_once 'core/db/connection.php';

        $stmt = $pdo->prepare('SELECT id, email, password_hash, role, first_name, last_name, status FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if ($u && !empty($u['password_hash']) && password_verify($pass, $u['password_hash'])) {

            // combine names for display
            $name = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
            if ($name === '') {
                $name = $u['email']; // fallback
            }

            // block inactive accounts
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

                // role-based redirects
                switch ($u['role']) {
                    case 'admin':
                    case 'registrar':
                    case 'mis':
                    case 'super_admin': //temporary for super_admin
                        header('Location: ' . BASE_URL . '/admin/index.php');
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
            height: 18px;
            width: 100%;
            height: 27px;
            background: linear-gradient(180deg, #FFD700 25.96%, #998100 99.98%, #A08700 99.99%);
            align-items: bottom;


        }
        .hero-inner {
            position: relative;
            z-index: 1;
            max-width: 720px;
            margin: 0 auto;
        }
        .hero-logo {
            /* width: 105px;
            height: 105px;
            object-fit: contain;
            margin-bottom: 12px;
            filter: drop-shadow(0 10px 18px rgba(0, 0, 0, 0.35)); */
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
            /* text-transform: uppercase;
            letter-spacing: 2.5px;
            font-size: clamp(1.3rem, 4vw, 1.8rem);
            margin: 0; */
            color: var(--text-base);
            text-align: center;
            font-family: Bayon;
            font-size: 35px;
            font-style: normal;
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
          font-style: normal;
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
        .field-box svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            fill: #7b8a94;
        }
        .form-links {
            display: flex;
            justify-content: flex-end;
            margin-top: -8px;
            font-size: 0.9rem;
        }
        .form-links a {
            color: var(--primary-green);
            text-decoration: none;
            font-weight: 500;
        }
        .form-links a:hover {
            text-decoration: underline;
        }
        .btn-submit {
            margin-top: 6px;
            border: none;
            border-radius: 16px;
            padding: 13px 18px;
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(90deg, #0f6b43, #0c5133);
            cursor: pointer;
            transition: 0.25s ease;
        }
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 15px 30px rgba(12, 81, 51, 0.25);
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
                    <a href="login.php" class="active">Login</a>
                    <a href="register.php">Register</a>
                </div>

                <div class="welcome-text">
                    <h3>Welcome Back!</h3>
                    <p>Sign in to access your portal.</p>
                </div>

                <?php if ($flashError): ?>
                    <div class="alert"><?= htmlspecialchars($flashError) ?></div>
                <?php endif; ?>
                <?php if ($flashSuccess): ?>
                    <div class="alert" style="background:#e8f6ed;border-color:#b6e2c3;color:#1c6b34;">
                        <?= htmlspecialchars($flashSuccess) ?>
                    </div>
                <?php endif; ?>
                <?php if ($err): ?>
                    <div class="alert"><?= htmlspecialchars($err) ?></div>
                <?php endif; ?>

                <form method="post" autocomplete="on">
                    <div class="input-field">
                        <label for="email">Email Address</label>
                        <div class="field-box">
                            <span class="material-symbols-rounded">mail</span>
                            <input type="email" id="email" name="email" placeholder="you@paterostechnologicalcollege.edu.ph" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="input-field">
                        <label for="password">Password</label>
                        <div class="field-box">
                          <span class="material-symbols-rounded">lock</span>
                            <input type="password" id="password" name="password" placeholder="••••••••" required>
                          <span class="material-symbols-rounded">visibility</span>
                        </div>
                    </div>

                    <div class="form-links">
                        <a href="forgot_password.php">Forgot Password?</a>
                    </div>

                    <button type="submit" class="btn-submit">Sign In</button>
                </form>

                <div class="page-footer">
                    Developed by BSIT 3OL Students<br>
                    © <?= date('Y') ?> Pateros Technological College. All Rights Reserved.
                </div>
            </div>
        </section>
    </div>
</body>
</html>
