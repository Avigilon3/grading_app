<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Academic Portal | Pateros Technological College</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Arimo:wght@400;500;600;700&family=Bayon&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
	<style>
		:root {
			--green-950: #08251a;
			--green-900: #0d3d28;
			--green-700: #17613e;
			--green-500: #4f9468;
			--gold: #f5c84c;
			--gold-soft: #ffe69a;
			--paper: #f5f7f1;
			--line: rgba(255, 255, 255, 0.2);
		}

		* { box-sizing: border-box; }

		html, body { min-height: 100%; }

		body {
			margin: 0;
			color: #fff;
			background: var(--green-950);
			font-family: 'Arimo', sans-serif;
		}

		.welcome-page {
			min-height: 100vh;
			display: grid;
			place-items: center;
			padding: 28px;
			position: relative;
			isolation: isolate;
			overflow: hidden;
			background:
				linear-gradient(115deg, rgba(5, 30, 20, 0.94) 8%, rgba(9, 61, 38, 0.72) 52%, rgba(12, 68, 48, 0.5)),
				url('admin/assets/images/ptc_bg_people.jpg') center / cover no-repeat;
		}

		.welcome-page::before {
			content: '';
			position: absolute;
			inset: 0;
			z-index: -1;
			opacity: 0.35;
			background-image: linear-gradient(rgba(255,255,255,.08) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.08) 1px, transparent 1px);
			background-size: 56px 56px;
			mask-image: linear-gradient(90deg, #000, transparent 78%);
		}

		.welcome-page::after {
			content: '';
			position: absolute;
			z-index: -1;
			width: 52vw;
			height: 52vw;
			right: -23vw;
			bottom: -30vw;
			border: 1px solid rgba(245, 200, 76, 0.3);
			transform: rotate(28deg);
			box-shadow: 0 0 0 28px rgba(245, 200, 76, 0.04), 0 0 0 56px rgba(245, 200, 76, 0.03);
		}

		.welcome-shell {
			width: min(1120px, 100%);
			display: grid;
			grid-template-columns: minmax(0, 1.1fr) minmax(300px, .9fr);
			gap: clamp(36px, 7vw, 100px);
			align-items: center;
		}

		.brand-lockup {
			display: flex;
			align-items: center;
			gap: 14px;
			color: var(--gold-soft);
			font-size: .76rem;
			font-weight: 700;
			letter-spacing: .15em;
			text-transform: uppercase;
		}

		.brand-lockup img {
			width: 44px;
			height: 44px;
			object-fit: contain;
		}

		.welcome-copy { animation: reveal .8s ease both; }

		.eyebrow {
			margin: 48px 0 16px;
			color: var(--gold);
			font-size: .78rem;
			font-weight: 700;
			letter-spacing: .2em;
			text-transform: uppercase;
		}

		h1 {
			max-width: 680px;
			margin: 0;
			font-family: 'Space Grotesk', sans-serif;
			font-size: clamp(3rem, 7vw, 6.7rem);
			line-height: .94;
			letter-spacing: -.04em;
		}

		h1 span { color: var(--gold); }

		.lead {
			max-width: 560px;
			margin: 28px 0 34px;
			color: rgba(255,255,255,.76);
			font-size: clamp(1rem, 2vw, 1.2rem);
			line-height: 1.7;
		}

		.welcome-actions { display: flex; flex-wrap: wrap; gap: 12px; }

		.button {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			min-height: 52px;
			padding: 0 24px;
			border: 1px solid transparent;
			border-radius: 10px;
			font: 700 .92rem 'Arimo', sans-serif;
			text-decoration: none;
			transition: transform .2s ease, background .2s ease, border-color .2s ease;
		}

		.button:hover { transform: translateY(-3px); }
		.button-primary { color: var(--green-950); background: var(--gold); box-shadow: 0 14px 30px rgba(0,0,0,.22); }
		.button-primary:hover { background: #ffda6c; }
		.button-secondary { color: #fff; background: rgba(255,255,255,.06); border-color: var(--line); }
		.button-secondary:hover { background: rgba(255,255,255,.13); border-color: rgba(255,255,255,.4); }

		.welcome-panel {
			padding: 34px;
			border: 1px solid var(--line);
			border-radius: 18px;
			background: linear-gradient(145deg, rgba(255,255,255,.15), rgba(255,255,255,.04));
			box-shadow: 0 28px 70px rgba(0,0,0,.3);
			backdrop-filter: blur(16px);
			animation: reveal .8s .14s ease both;
		}

		.panel-label { margin: 0 0 28px; color: var(--gold-soft); font-size: .75rem; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; }
		.panel-title { margin: 0; font-family: 'Space Grotesk', sans-serif; font-size: 1.8rem; line-height: 1.1; }
		.panel-rule { height: 1px; margin: 28px 0; background: var(--line); }
		.panel-item { display: grid; grid-template-columns: 34px 1fr; gap: 14px; align-items: start; margin-top: 22px; }
		.panel-number { color: var(--gold); font: 700 1.1rem 'Space Grotesk', sans-serif; }
		.panel-item strong { display: block; margin-bottom: 5px; font-size: .98rem; }
		.panel-item span { color: rgba(255,255,255,.65); font-size: .88rem; line-height: 1.5; }

		.footer-note { margin-top: 52px; color: rgba(255,255,255,.48); font-size: .76rem; }

		@keyframes reveal { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: translateY(0); } }

		@media (max-width: 760px) {
			.welcome-page { padding: 22px; }
			.welcome-shell { grid-template-columns: 1fr; gap: 42px; }
			.eyebrow { margin-top: 42px; }
			.welcome-panel { padding: 26px; }
			.footer-note { margin-top: 34px; }
		}

		@media (prefers-reduced-motion: reduce) {
			*, *::before, *::after { animation-duration: .01ms !important; transition-duration: .01ms !important; }
		}
	</style>
</head>
<body>
	<main class="welcome-page">
		<div class="welcome-shell">
			<section class="welcome-copy" aria-labelledby="welcome-title">
				<div class="brand-lockup">
					<img src="admin/assets/images/logo-ptc.png" alt="Pateros Technological College logo">
					<span>Pateros Technological College</span>
				</div>
				<p class="eyebrow">Academic progress, made visible</p>
				<h1 id="welcome-title">Your grades.<br><span>Your next step.</span></h1>
				<p class="lead">A focused space for students, professors, and academic teams to keep every milestone clear and every decision moving forward.</p>
				<div class="welcome-actions">
					<a class="button button-primary" href="login.php?open=1">Sign in to your portal</a>
					<a class="button button-secondary" href="register.php?open=1">Create an account</a>
				</div>
				<p class="footer-note">Gearing the way to your future. &copy; <?= date('Y'); ?> Pateros Technological College.</p>
			</section>

			<aside class="welcome-panel" aria-label="Portal highlights">
				<p class="panel-label">Academic Portal</p>
				<h2 class="panel-title">One clear view of the work ahead.</h2>
				<div class="panel-rule"></div>
				<div class="panel-item">
					<span class="panel-number">01</span>
					<div><strong>Check progress</strong><span>Review academic performance wherever your day takes you.</span></div>
				</div>
				<div class="panel-item">
					<span class="panel-number">02</span>
					<div><strong>Stay aligned</strong><span>Keep course updates, submissions, and requests within reach.</span></div>
				</div>
				<div class="panel-item">
					<span class="panel-number">03</span>
					<div><strong>Move forward</strong><span>Use timely information to plan your next academic step.</span></div>
				</div>
			</aside>
		</div>
	</main>
</body>
</html>
