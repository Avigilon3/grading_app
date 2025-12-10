<?php
require_once 'core/config/config.php';
require_once 'core/auth/session.php';
require_once 'core/db/connection.php';
require_once 'core/config/functions.php';

$notFoundMessage = "Invalid email address. Your email address doesn't exist in the database. Contact Registrar or MIS for assistance.";
$prefilledFirst = '';
$prefilledLast = '';
$detectedRole = '';

$err = get_flash('error');
$msg = get_flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['retype_password'] ?? '');
    $directoryProfile = null;
    $first = '';
    $last = '';
    $roleChoice = '';

    if (!$email) {
        $err = 'Please enter your PTC email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Please enter a valid email address.';
    }

    if (!$err) {
        $directoryProfile = lookupDirectoryProfileByEmail($pdo, $email);
        if (!$directoryProfile) {
            $err = $notFoundMessage;
        } else {
            $first = $directoryProfile['first_name'];
            $last = $directoryProfile['last_name'];
            $roleChoice = $directoryProfile['role'];
            $prefilledFirst = $first;
            $prefilledLast = $last;
            $detectedRole = ucfirst($roleChoice);
        }
    }

    if (!$err && (!$pass || !$confirm)) {
        $err = 'Please fill in all required fields.';
    } elseif (!$err && strlen($pass) < 8) {
        $err = 'Password must be at least 8 characters.';
    } elseif (!$err && $pass !== $confirm) {
        $err = 'Passwords do not match.';
    }

    if (!$err) {
        $check = $pdo->prepare('SELECT id, email, password_hash, role, first_name, last_name, status FROM users WHERE email=? LIMIT 1');
        $check->execute([$email]);
        $user = $check->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $insert = $pdo->prepare('INSERT INTO users (email, role, first_name, last_name, status) VALUES (?, ?, ?, ?, ?)');
            $insert->execute([$email, $roleChoice, $first, $last, 'INACTIVE']);
            $userId = (int)$pdo->lastInsertId();
            $user = [
                'id' => $userId,
                'password_hash' => null,
                'role' => $roleChoice,
                'first_name' => $first,
                'last_name' => $last,
                'status' => 'INACTIVE',
            ];
        }

        if (!empty($user['password_hash'])) {
            $err = 'This account is already registered. Please sign in instead.';
        }
    }

    if (!$err) {
        $code = strval(random_int(100000, 999999));
        $userId = (int)$user['id'];

        $upd = $pdo->prepare('UPDATE users SET verify_code = ?, first_name = ?, last_name = ?, role = ? WHERE id = ?');
        $upd->execute([$code, $first, $last, $roleChoice, $userId]);

        if ($directoryProfile['table'] === 'students') {
            $link = $pdo->prepare('UPDATE students SET user_id = ? WHERE id = ?');
            $link->execute([$userId, $directoryProfile['id']]);
        } elseif ($directoryProfile['table'] === 'professors') {
            $link = $pdo->prepare('UPDATE professors SET user_id = ? WHERE id = ?');
            $link->execute([$userId, $directoryProfile['id']]);
        }

        $_SESSION['pending_reg'] = [
            'email'      => $email,
            'password'   => $pass,
            'code'       => $code,
            'first_name' => $first,
            'last_name'  => $last,
            'role'       => $roleChoice,
        ];

        try {
            $subject = 'Your verification code';
            $body = "Your verification code is: {$code}\n\nIf you did not request this, please ignore.";
            @mail($email, $subject, $body);
        } catch (Exception $e) {
            // ignore mail failures
        }

        set_flash('success', 'We sent a 6-digit code to your email. (If not received, check spam or contact MIS.)');
        header('Location: ' . BASE_URL . '/verify.php');
        exit;
    }
}
$canSetCredentials = $prefilledFirst !== '' && $prefilledLast !== '';
$roleDisplayText = $detectedRole ? 'Detected role: ' . $detectedRole : '';
$emailHelperMessage = '';
if ($detectedRole) {
    $emailHelperMessage = 'Email verified as ' . strtolower($detectedRole) . ' account.';
} elseif ($err === $notFoundMessage) {
    $emailHelperMessage = $notFoundMessage;
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
        .field-with-button {
            gap: 6px;
        }
        .btn-check {
            border: none;
            border-radius: 10px;
            padding: 10px 16px;
            font-weight: 600;
            background: #e3f2e9;
            color: #0f6b43;
            cursor: pointer;
            transition: background 0.2s ease, color 0.2s ease;
        }
        .btn-check:hover:not(:disabled) {
            background: #d6eadf;
        }
        .btn-check:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .helper-text {
            margin: 6px 0 0;
            font-size: 0.85rem;
            color: var(--text-muted);
            min-height: 1.2rem;
        }
        .helper-text.error {
            color: #b00020;
        }
        .helper-text.success {
            color: #0f6b43;
        }
        .role-note {
            margin: 8px 0 0;
            font-size: 0.9rem;
            color: #0f6b43;
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
                    <a href="login.php">Login</a>
                    <a href="register.php" class="active">Register</a>
                </div>

                <div class="welcome-text">
                    <h3>Hi PTCian!</h3>
                    <p>Sign up using your PTC email address to access your portal.</p>
                </div>

                <?php if ($err): ?>
                    <div class="alert"><?= htmlspecialchars($err) ?></div>
                <?php endif; ?>
                <?php if ($msg && !$err): ?>
                    <div class="alert" style="background:#e8f6ed;border-color:#b6e2c3;color:#1c6b34;">
                        <?= htmlspecialchars($msg) ?>
                    </div>
                <?php endif; ?>

                <form method="post" autocomplete="on" id="registration-form" data-email-verified="<?= ($prefilledFirst && $prefilledLast) ? '1' : '0'; ?>">
                    <div class="input-field">
                        <label for="email">Email Address</label>
                        <div class="field-box field-with-button">
                            <span class="material-symbols-rounded">mail</span>
                            <input type="email" id="email" name="email" placeholder="you@paterostechnologicalcollege.edu.ph" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                            <button type="button" class="btn-check" id="check-email">Check</button>
                        </div>
                        <p class="helper-text" data-email-helper><?= htmlspecialchars($emailHelperMessage); ?></p>
                    </div>

                    <input type="hidden" name="role" id="detected_role" value="<?= htmlspecialchars(strtolower($detectedRole)); ?>">

                    <div class="input-field">
                        <label for="firstname">First Name</label>
                        <div class="field-box">
                            <span class="material-symbols-rounded">person</span>
                            <input type="text" id="firstname" name="firstname" placeholder="Juan" value="<?= htmlspecialchars($prefilledFirst); ?>" readonly>
                        </div>
                    </div>

                    <div class="input-field">
                        <label for="lastname">Last Name</label>
                        <div class="field-box">
                            <span class="material-symbols-rounded">person</span>
                            <input type="text" id="lastname" name="lastname" placeholder="Dela Cruz" value="<?= htmlspecialchars($prefilledLast); ?>" readonly>
                        </div>
                        <p class="role-note" data-role-display><?= htmlspecialchars($roleDisplayText); ?></p>
                    </div>

                    <div class="input-field">
                        <label for="password">Password</label>
                        <div class="field-box">
                            <span class="material-symbols-rounded">lock</span>
                            <input type="password" id="password" name="password" placeholder="********" <?= ($prefilledFirst && $prefilledLast) ? '' : 'disabled'; ?> data-credential-input>
                            <span class="material-symbols-rounded">visibility</span>
                        </div>
                    </div>

                    <div class="input-field">
                        <label for="retype-password">Reenter Password</label>
                        <div class="field-box">
                            <span class="material-symbols-rounded">lock</span>
                            <input type="password" id="retype_password" name="retype_password" placeholder="********" <?= ($prefilledFirst && $prefilledLast) ? '' : 'disabled'; ?> data-credential-input>
                            <span class="material-symbols-rounded">visibility</span>
                        </div>
                    </div>

                    <div class="form-links">
                        <a href="login.php">I have an existing account.</a>
                    </div>

                    <button type="submit" class="btn-submit" <?= ($prefilledFirst && $prefilledLast) ? '' : 'disabled'; ?> data-submit-button>Sign Up</button>
                </form>

                <div class="page-footer">
                    Developed by BSIT 3OL Students<br>
                    © <?= date('Y') ?> Pateros Technological College. All Rights Reserved.
                </div>
            </div>
        </section>
    </div>

    <!-- <h2>Create an account</h2>
      <?php if($err): ?><div style="color:#b00020;"><?= htmlspecialchars($err) ?></div><?php endif; ?>
      <?php if($msg): ?>
      <div style="color:green;"><?= $msg ?>
      </div>
      <form method="post" action="verify.php">
        <label>Enter Code</label><br><input type="text" name="code" required><br>
        <button type="submit">Verify</button>
      </form>
    <?php else: ?>
      <form method="post">
        <label>PTC Email</label><br><input type="email" name="email" required><br>
        <label>Password</label><br><input type="password" name="password" required><br>
        <button type="submit">Register</button>
      </form>
    <?php endif; ?>
    <div><a href="login.php">Back to login</a></div> -->
</body>
<script>
(function () {
    const form = document.getElementById('registration-form');
    if (!form) return;

    const emailInput = document.getElementById('email');
    const checkButton = document.getElementById('check-email');
    const firstInput = document.getElementById('firstname');
    const lastInput = document.getElementById('lastname');
    const helper = document.querySelector('[data-email-helper]');
    const roleDisplay = document.querySelector('[data-role-display]');
    const hiddenRole = document.getElementById('detected_role');
    const credentialInputs = form.querySelectorAll('[data-credential-input]');
    const submitButton = form.querySelector('[data-submit-button]');

    const notFoundMessage = "Invalid email address. Your email address doesn't exist in the database. Contact Registrar or MIS for assistance.";

    const capitalize = (value) => value ? value.charAt(0).toUpperCase() + value.slice(1) : '';

    const setHelper = (message, state) => {
        if (!helper) return;
        helper.textContent = message || '';
        helper.classList.remove('error', 'success');
        if (state === 'error') {
            helper.classList.add('error');
        } else if (state === 'success') {
            helper.classList.add('success');
        }
    };

    const updateRoleDisplay = (role) => {
        if (!roleDisplay) return;
        roleDisplay.textContent = role ? `Detected role: ${capitalize(role)}` : '';
    };

    const setCredentialState = (enabled) => {
        credentialInputs.forEach((input) => {
            input.disabled = !enabled;
        });
        if (submitButton) {
            submitButton.disabled = !enabled;
        }
    };

    const fillNames = (first, last, role) => {
        if (firstInput) {
            firstInput.value = first || '';
        }
        if (lastInput) {
            lastInput.value = last || '';
        }
        if (hiddenRole) {
            hiddenRole.value = role || '';
        }
        updateRoleDisplay(role || '');
    };

    const initialVerified = form.dataset.emailVerified === '1';
    if (initialVerified) {
        setCredentialState(true);
        updateRoleDisplay(hiddenRole.value || '');
        if (helper && helper.textContent.trim() !== '') {
            helper.classList.add('success');
        }
    } else {
        setCredentialState(false);
    }
    if (!initialVerified && helper && helper.textContent.trim() !== '') {
        if (helper.textContent.trim() === notFoundMessage) {
            helper.classList.add('error');
        }
    }

    const handleError = (message) => {
        fillNames('', '', '');
        setCredentialState(false);
        setHelper(message || notFoundMessage, 'error');
        form.dataset.emailVerified = '0';
    };

    if (checkButton) {
        checkButton.addEventListener('click', () => {
            const email = emailInput ? emailInput.value.trim() : '';
            if (!email) {
                handleError('Please enter your PTC email address first.');
                return;
            }

            setHelper('Checking email...', null);
            checkButton.disabled = true;

            fetch('ajax/check_ptc_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email })
            })
                .then((response) => response.json())
                .then((payload) => {
                    if (payload.redirect) {
                        window.location.href = payload.redirect;
                        return;
                    }
                    if (!payload.ok) {
                        handleError(payload.message || notFoundMessage);
                        return;
                    }
                    const data = payload.data || {};
                    const detectedRole = (data.role || '').toLowerCase();
                    fillNames(data.first_name || '', data.last_name || '', detectedRole);
                    setCredentialState(true);
                    setHelper('Email verified as ' + (detectedRole || 'student') + ' account.', 'success');
                    form.dataset.emailVerified = '1';
                })
                .catch(() => {
                    handleError('Unable to verify this email right now. Please try again.');
                })
                .finally(() => {
                    checkButton.disabled = false;
                });
        });
    }
})();
</script>
</html>
