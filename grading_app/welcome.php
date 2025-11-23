<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome | Online Grading System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Arimo:ital,wght@0,400..700;1,400..700&family=Bayon&family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        :root {
            --primary-yellow: #FFD700;
            --accent-yellow: #f7b733;
            --deep-green: #11593c;
            --teal-border: #1bb8d1;
            --text-base: #F9F9F9;
            
        }
        * {
            box-sizing: border-box;
        
            
        /* FONTS */
        .space-grotesk {
          font-family: "Space Grotesk", sans-serif;
          font-optical-sizing: auto;
          font-weight: <weight>;
          font-style: normal;
        }
        .bayon-regular {
          font-family: "Bayon", sans-serif;
          font-weight: 400;
          font-style: normal;
        }
        .arimo {
          font-family: "Arimo", sans-serif;
          font-optical-sizing: auto;
          font-weight: 400;
          font-style: normal;
        }

        }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Arimo', Arial, sans-serif;
            color: var(--text-base);
            background: linear-gradient(120deg, rgba(6, 64, 42, 0.92), rgba(6, 98, 80, 0.7)),
                url('admin/assets/images/background.jpg') center/cover no-repeat fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            /* padding: 32px; */
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
            width: 100%;
            min-height: 100vh;
            /* border: 4px solid var(--teal-border);
            border-radius: 22px; */
            /* padding: 56px 48px 112px; */
            backdrop-filter: blur(3px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
        }
        .school-brand img {
            /* width: 110px;
            height: 110px;
            object-fit: contain;
            margin-bottom: 8px;
            filter: drop-shadow(0 8px 18px rgba(0, 0, 0, 0.4)); */
            margin-top: 150px;
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
        .school-name {
            /* font-size: clamp(1.3rem, 4vw, 1.85rem);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2.5px;
            margin: 0; */

            color: var(--text-base);
            text-align: center;
            font-family: Bayon;
            font-size: 35px;
            font-style: normal;
            font-weight: 400;
            line-height: 35px; /* 100% */
        }
        .tagline {
            /* margin: 6px 0 32px;
            font-weight: 400;
            color: rgba(247, 248, 243, 0.85);
            letter-spacing: 0.6px; */

          color: var(--text-base);
          font-family: Arimo;
          font-size: 18px;
          font-style: normal;
          font-weight: 400;
          line-height: 28px;

        }
        .system-intro h1 {
            font-size: clamp(2rem, 5vw, 3rem);
            font-family: "Space Grotesk", sans-serif;
            font-weight: 700;
            text-align: center;
            color: var(--primary-yellow);
            margin: 0;
            text-shadow: 0 6px 18px rgba(0, 0, 0, 0.3);

        }
        .system-intro p {
            margin: 12px auto 28px;
            text-align: center;
            font-family: Arimo;
            font-size: 22px;
            font-weight: 400;
            font-style: normal;
            line-height: 28px;
            color: var(--text-base);

        }
        .cta-button {
            display: inline-flex;
            align-items: center;
            gap: 20px;
            padding: 0 248px;
            text-decoration: none;
            font-weight: 600;
            color: var(--text-base);
            letter-spacing: 1.2px;
            font-family: Arimo;
            /* text-transform: uppercase; */
        }
        .cta-button .material-symbols-rounded {
            width: 52px;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            transition: 0.3s ease;
        }
        .cta-button:hover {
            background: rgba(255, 255, 255, 0.20);;
            /* color: #0a5035; */
            border-color: transparent;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.25);
        }
        .cta-button span:last-child {
            font-size: 1rem;
        }
        /* .status-panel {
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
        } */
        .divider {
          width: 100%;
          height: 47px;
          background: linear-gradient(180deg, #FFD700 25.96%, #998100 99.98%, #A08700 99.99%);
          align-items: bottom;
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
        </div>
        <div class="start">
            <a href="login.php" class="cta-button">
                <span class="material-symbols-rounded">arrow_circle_down</span>
                <span>Get Started</span>
            </a>
        </div>
        <div class="divider">
            <div class="divide"></div>
          </div>
        <!-- <div class="status-panel">
            <div class="status-stripes">
                <div class="stripe"></div>
                <div class="stripe accent"></div>
            </div>
        </div> -->
    </div>
    
</body>
</html>
