<?php
require_once 'core/config/config.php';
require_once 'core/auth/session.php';

$err = null;

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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-green: #145a32;
            --hero-green: #0e5130;
            --accent-blue: #1bb8d1;
            --highlight-yellow: #ffd43b;
            --border-muted: #dfe6eb;
            --text-dark: #162125;
            --text-muted: #5f6b74;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Poppins', Arial, sans-serif;
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
            background: linear-gradient(115deg, rgba(10, 84, 58, 0.96), rgba(6, 94, 83, 0.82)),
                url('admin/assets/images/ptc.jpg') center/cover no-repeat;
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
            background:
                linear-gradient(180deg, rgba(0,0,0,0.18), rgba(0,0,0,0.18)),
                linear-gradient(90deg, #ffd43b 0%, #f9c440 60%, #ffd43b 100%);
            border-top: 4px solid var(--accent-blue);
        }
        .hero-inner {
            position: relative;
            z-index: 1;
            max-width: 720px;
            margin: 0 auto;
        }
        .hero-logo {
            width: 105px;
            height: 105px;
            object-fit: contain;
            margin-bottom: 12px;
            filter: drop-shadow(0 10px 18px rgba(0, 0, 0, 0.35));
        }
        .hero h1 {
            text-transform: uppercase;
            letter-spacing: 2.5px;
            font-size: clamp(1.3rem, 4vw, 1.8rem);
            margin: 0;
        }
        .hero h2 {
            margin: 12px 0 0;
            font-size: clamp(2rem, 4.8vw, 3rem);
            color: var(--highlight-yellow);
        }
        .hero p {
            margin: 8px 0 0;
            color: rgba(255, 255, 255, 0.85);
        }
        .auth-area {
            flex: 1;
            background:
                linear-gradient(180deg, rgba(255,255,255,0.94), rgba(255,255,255,0.98)),
                url('admin/assets/images/background4.jpg') center/cover fixed;
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
            margin: 0;
            font-size: 1.5rem;
        }
        .welcome-text p {
            margin: 4px 0 18px;
            color: var(--text-muted);
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
                <p class="hero-kicker">Pateros Technological College</p>
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

                <?php if ($err): ?>
                    <div class="alert"><?= htmlspecialchars($err) ?></div>
                <?php endif; ?>

                <form method="post" autocomplete="on">
                    <div class="input-field">
                        <label for="email">Email Address</label>
                        <div class="field-box">
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path d="M2 5c0-1.1.9-2 2-2h16c1.1 0 2 .9 2 2v14c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V5zm2 0v.01L12 11l8-5.99V5H4zm16 4.24-7.44 5.57c-.98.73-2.14.73-3.12 0L2 9.24V19h18V9.24z" />
                            </svg>
                            <input type="email" id="email" name="email" placeholder="you@paterostechnologicalcollege.edu.ph" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="input-field">
                        <label for="password">Password</label>
                        <div class="field-box">
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path d="M17 8V7a5 5 0 10-10 0v1H5v14h14V8h-2zm-8 0V7a3 3 0 016 0v1H9zm3 5c.83 0 1.5.67 1.5 1.5 0 .59-.34 1.1-.84 1.35V18h-1.32v-2.15A1.49 1.49 0 0110.5 14.5c0-.83.67-1.5 1.5-1.5z" />
                            </svg>
                            <input type="password" id="password" name="password" placeholder="••••••••" required>
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path d="M12 5c-5 0-9.27 3.11-11 7 1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7zm0 12a5 5 0 110-10 5 5 0 010 10zm0-2a3 3 0 100-6 3 3 0 000 6z" />
                            </svg>
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
