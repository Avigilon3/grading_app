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
$openLogin = ($_SERVER['REQUEST_METHOD'] === 'POST') || isset($_GET['open']) || $flashError || $flashSuccess || $err;
$heroAttributes = $openLogin ? '' : ' role="button" tabindex="0" aria-label="Open login form"';
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
            overflow-x: hidden;
        }
        .hero {
            position: relative;
            height: 100vh;
            min-height: 100vh;
            padding: 72px 16px 96px;
            backdrop-filter: blur(3px);
            background: 
            linear-gradient(120deg, rgba(6, 64, 42, 0.92), rgba(6, 98, 80, 0.7)),
            linear-gradient(180deg, rgba(0, 56, 32, 0.75), rgba(10, 104, 72, 0.65)),
                url('admin/assets/images/background.jpg') center/cover no-repeat;
            color: #fff;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            overflow: hidden;
            transform-origin: top center;
            will-change: height, min-height, padding;
        }
        .hero::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            height: 27px;
            background: linear-gradient(180deg, #FFD700 25.96%, #998100 99.98%, #A08700 99.99%);
        }
        .hero-inner {
            position: relative;
            z-index: 1;
            max-width: 720px;
            margin: 0 auto;
            transition: transform 0.7s ease, max-width 0.7s ease;
        }
        .hero-logo {
            width: 110px;
            height: 110px;
            object-fit: contain;
            margin-bottom: 10px;
            aspect-ratio: 54/55;
            border: 10px solid rgba(255, 255, 255, 0.20);
            border-radius: 50%;
            transition: width 0.7s ease, height 0.7s ease, border-width 0.7s ease;
        }
        .hero h1 {
            color: var(--text-base);
            text-align: center;
            font-family: Bayon;
            font-size: clamp(1.8rem, 4vw, 2.2rem);
            font-style: normal;
            font-weight: 400;
            line-height: 35px;
            margin: 0;
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
        .hero-description {
            max-width: 670px;
            margin: 14px auto 34px;
            font-size: clamp(1rem, 2.4vw, 1.35rem);
            transition: opacity 0.35s ease, transform 0.35s ease, max-height 0.5s ease, margin 0.5s ease;
        }
        .hero-hint {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            color: var(--text-base);
            font-weight: 600;
            letter-spacing: 1px;
            transition: opacity 0.35s ease, transform 0.35s ease, max-height 0.5s ease;
        }
        .hero-hint .material-symbols-rounded {
            font-size: 2.1rem;
        }
        .auth-area {
            flex: 1;
            background:
              linear-gradient(180deg, rgba(255, 255, 255, 0.47) 0%, rgba(255, 255, 255, 0.95) 50.96%),
              url('admin/assets/images/ptcfront.png') center/cover no-repeat;
            padding: 48px 16px 56px;
            display: flex;
            justify-content: center;
            opacity: 0;
            transform: translateY(36px);
            pointer-events: none;
            transition: opacity 0.55s ease 0.48s, transform 0.55s ease 0.48s;
        }
        body.hero-compressed .hero {
            height: clamp(430px, 42vh, 460px);
            min-height: clamp(430px, 42vh, 460px);
            padding: 44px 16px 72px;
            cursor: default;
            animation: curtainCompress 0.92s cubic-bezier(0.2, 0.85, 0.25, 1) both;
        }
        body.hero-compressed.hero-skip-animation .hero {
            animation: none;
        }
        body.hero-compressed .hero-inner {
            max-width: 620px;
            transform: translateY(-4px);
        }
        body.hero-compressed .hero-logo {
            width: 86px;
            height: 86px;
            border-width: 8px;
        }
        body.hero-compressed .hero-description,
        body.hero-compressed .hero-hint {
            opacity: 0;
            transform: translateY(-12px);
            max-height: 0;
            margin: 0 auto;
            overflow: hidden;
        }
        body.hero-compressed .auth-area {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }
        @keyframes curtainCompress {
            0% {
                height: 100vh;
                min-height: 100vh;
                padding-top: 72px;
                padding-bottom: 96px;
            }
            28% {
                height: 104vh;
                min-height: 104vh;
                padding-top: 76px;
                padding-bottom: 112px;
            }
            100% {
                height: clamp(430px, 42vh, 460px);
                min-height: clamp(430px, 42vh, 460px);
                padding-top: 44px;
                padding-bottom: 72px;
            }
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
        .toggle-visibility {
            border: none;
            background: transparent;
            cursor: pointer;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #7b8a94;
        }
        .toggle-visibility:focus-visible {
            outline: 2px solid #0f6b43;
            outline-offset: 2px;
        }
        .toggle-visibility [data-icon-hide] {
            display: none;
        }
        .toggle-visibility.is-visible [data-icon-show] {
            display: none;
        }
        .toggle-visibility.is-visible [data-icon-hide] {
            display: inline-flex;
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
        @media (prefers-reduced-motion: reduce) {
            .hero,
            .hero-inner,
            .hero-logo,
            .hero-description,
            .hero-hint,
            .auth-area,
            body.hero-compressed .hero {
                animation: none;
                transition: none;
            }
        }
        @media (max-width: 760px) {
            .hero {
                padding: 54px 20px 84px;
            }
            body.hero-compressed .hero {
                height: 380px;
                min-height: 380px;
                padding: 34px 20px 62px;
            }
            @keyframes curtainCompress {
                0% {
                    height: 100vh;
                    min-height: 100vh;
                    padding-top: 54px;
                    padding-bottom: 84px;
                }
                28% {
                    height: 103vh;
                    min-height: 103vh;
                    padding-top: 58px;
                    padding-bottom: 96px;
                }
                100% {
                    height: 380px;
                    min-height: 380px;
                    padding-top: 34px;
                    padding-bottom: 62px;
                }
            }
        }
        @media (max-width: 520px) {
            .hero-logo {
                width: 94px;
                height: 94px;
            }
            .hero h1 {
                font-size: 1.7rem;
                line-height: 1.05;
            }
            .hero h2 {
                font-size: 2rem;
            }
            .hero p {
                font-size: 1rem;
                line-height: 1.5;
            }
            body.hero-compressed .hero {
                height: 350px;
                min-height: 350px;
            }
            @keyframes curtainCompress {
                0% {
                    height: 100vh;
                    min-height: 100vh;
                    padding-top: 54px;
                    padding-bottom: 84px;
                }
                28% {
                    height: 103vh;
                    min-height: 103vh;
                    padding-top: 58px;
                    padding-bottom: 96px;
                }
                100% {
                    height: 350px;
                    min-height: 350px;
                    padding-top: 34px;
                    padding-bottom: 62px;
                }
            }
            body.hero-compressed .hero-logo {
                width: 76px;
                height: 76px;
            }
            .auth-card {
                padding: 24px 20px 30px;
                border-radius: 22px;
            }
        }
    </style>
</head>
<body class="<?= $openLogin ? 'hero-compressed hero-skip-animation' : '' ?>">
    <div class="page-shell">
        <section class="hero"<?= $heroAttributes ?>>
            <div class="hero-inner">
                <img src="admin/assets/images/logo-ptc.png" alt="PTC Logo" class="hero-logo">
                <h1>PATEROS TECHNOLOGICAL COLLEGE</h1>
                <p>Gearing the way to your future!</p>
                <h2>Online Grading System</h2>
                <p class="hero-description">Access your grades, monitor progress, and stay connected with your academic journey whenever and wherever you are.</p>
                <div class="hero-hint" aria-hidden="true">
                    <span class="material-symbols-rounded">arrow_circle_down</span>
                    <span>Click anywhere to get started</span>
                </div>
            </div>
        </section>

        <section class="auth-area">
            <div class="auth-card">
                <div class="auth-tabs">
                    <a href="login.php?open=1" class="active">Login</a>
                    <a href="register.php?open=1">Register</a>
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
                            <input type="password" id="password" name="password" placeholder="********" required>
                          <button
                            type="button"
                            class="toggle-visibility"
                            data-password-toggle="#password"
                            data-hidden-type="password"
                            aria-pressed="false"
                            aria-label="Show password"
                          >
                            <span class="material-symbols-rounded" data-icon-show>visibility</span>
                            <span class="material-symbols-rounded" data-icon-hide>visibility_off</span>
                          </button>
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
<script>
(function () {
    var hero = document.querySelector('.hero');
    var hasOpened = document.body.classList.contains('hero-compressed');

    function openLogin() {
        if (hasOpened) return;
        hasOpened = true;
        document.body.classList.add('hero-compressed');
        if (hero) {
            hero.removeAttribute('role');
            hero.removeAttribute('tabindex');
            hero.removeAttribute('aria-label');
        }
    }

    if (hero && !hasOpened) {
        hero.addEventListener('click', openLogin, { once: true });
        hero.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openLogin();
            }
        });
    }

    var toggles = document.querySelectorAll('[data-password-toggle]');
    if (!toggles.length) return;

    toggles.forEach(function (toggle) {
        var targetSelector = toggle.getAttribute('data-password-toggle');
        var hiddenType = toggle.getAttribute('data-hidden-type') || 'password';
        var visibleType = toggle.getAttribute('data-visible-type') || 'text';
        if (!targetSelector) return;
        var input = document.querySelector(targetSelector);
        if (!input) return;

        function setVisibility(show) {
            input.setAttribute('type', show ? visibleType : hiddenType);
            toggle.classList.toggle('is-visible', show);
            toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
            toggle.setAttribute('aria-label', show ? 'Hide value' : 'Show value');
        }

        toggle.addEventListener('click', function () {
            var shouldShow = input.getAttribute('type') === hiddenType;
            setVisibility(shouldShow);
        });
    });
})();
</script>
</body>
</html>
