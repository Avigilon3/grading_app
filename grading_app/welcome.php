<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome | Online Grading System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-yellow: #ffd43b;
            --accent-yellow: #f7b733;
            --deep-green: #11593c;
            --teal-border: #1bb8d1;
            --text-base: #f7f8f3;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Poppins', Arial, sans-serif;
            color: var(--text-base);
            background: linear-gradient(120deg, rgba(6, 64, 42, 0.92), rgba(6, 98, 80, 0.7)),
                url('admin/assets/images/ptc.jpg') center/cover no-repeat fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px;
            position: relative;
            text-align: center;
        }
        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background: linear-gradient(180deg, rgba(0, 56, 32, 0.75), rgba(10, 104, 72, 0.65));
            mix-blend-mode: multiply;
            z-index: 0;
        }
        .welcome-card {
            position: relative;
            z-index: 1;
            width: min(100%, 980px);
            min-height: calc(100vh - 96px);
            border: 4px solid var(--teal-border);
            border-radius: 22px;
            padding: 56px 48px 112px;
            backdrop-filter: blur(3px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 18px;
        }
        .school-brand img {
            width: 110px;
            height: 110px;
            object-fit: contain;
            margin-bottom: 8px;
            filter: drop-shadow(0 8px 18px rgba(0, 0, 0, 0.4));
        }
        .school-name {
            font-size: clamp(1.3rem, 4vw, 1.85rem);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2.5px;
            margin: 0;
        }
        .tagline {
            margin: 6px 0 32px;
            font-weight: 400;
            color: rgba(247, 248, 243, 0.85);
            letter-spacing: 0.6px;
        }
        .system-intro h1 {
            font-size: clamp(2rem, 5vw, 3rem);
            color: var(--primary-yellow);
            margin: 0;
            text-shadow: 0 6px 18px rgba(0, 0, 0, 0.3);
        }
        .system-intro p {
            max-width: 640px;
            margin: 12px auto 28px;
            font-size: 1.05rem;
            line-height: 1.6;
            color: rgba(247, 248, 243, 0.88);
        }
        .cta-button {
            display: inline-flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            font-weight: 600;
            color: var(--text-base);
            letter-spacing: 1.2px;
            text-transform: uppercase;
        }
        .cta-button .cta-icon {
            width: 52px;
            height: 52px;
            border: 2px solid rgba(255, 255, 255, 0.85);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            transition: 0.3s ease;
        }
        .cta-button:hover .cta-icon {
            background: var(--primary-yellow);
            color: #0a5035;
            border-color: transparent;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.25);
        }
        .cta-button span:last-child {
            font-size: 1rem;
        }
        .status-panel {
            position: absolute;
            left: 38px;
            right: 38px;
            bottom: 32px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        .status-stripes {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .status-stripes .stripe {
            height: 12px;
            border-radius: 999px;
            background: linear-gradient(90deg, #fdd835, #f6b93b 75%, #f9e651);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        }
        .status-stripes .stripe.accent {
            height: 9px;
            background: linear-gradient(90deg, #0aa34f, #0e8a63 65%, #0f704f);
        }
        .status-pill {
            background: #d92027;
            border-radius: 50px;
            padding: 4px 20px;
            font-size: 0.95rem;
            font-weight: 600;
            letter-spacing: 1.1px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.35);
        }
        @media (max-width: 768px) {
            body {
                padding: 16px;
            }
            .welcome-card {
                padding: 48px 28px 96px;
                min-height: calc(100vh - 48px);
            }
            .cta-button {
                flex-direction: column;
                gap: 10px;
            }
            .cta-button span:last-child {
                font-size: 0.9rem;
            }
            .status-panel {
                left: 24px;
                right: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="welcome-card">
        <div class="school-brand">
            <img src="admin/assets/images/logo-ptc.png" alt="PTC Logo">
            <p class="school-name">Pateros Technological College</p>
            <p class="tagline">Gearing the way to your future!</p>
        </div>
        <div class="system-intro">
            <h1>Online Grading System</h1>
            <p>Access your grades, monitor progress, and stay connected with your academic journey whenever and wherever you are.</p>
            <a href="login.php" class="cta-button">
                <span class="cta-icon">&#x21E9;</span>
                <span>Get Started</span>
            </a>
        </div>
        <div class="status-panel">
            <div class="status-stripes">
                <div class="stripe"></div>
                <div class="stripe accent"></div>
            </div>
            <div class="status-pill">26</div>
        </div>
    </div>
</body>
</html>
