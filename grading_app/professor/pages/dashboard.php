
<?php
require_once __DIR__ . '/../../core/config/config.php';
require_once __DIR__ . '/../../core/auth/session.php';
require_once __DIR__ . '/../../core/auth/guards.php';
requireLogin();
if (($_SESSION['user']['role'] ?? '') !== 'professor') {
    http_response_code(403);
    echo 'Unauthorized.';
    exit;
}
require_once __DIR__ . '/../includes/init.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Professor Dashboard</title>
        <link rel="stylesheet" href="../assets/css/professor.css">
        <style>
            .stat-cards { display: flex; gap: 18px; margin-bottom: 24px; }
            .stat-card {
                background: #fff; color: #222; border-radius: 16px; box-shadow: 0 2px 8px #0001;
                padding: 18px 24px; min-width: 140px; flex: 1; display: flex; flex-direction: column; align-items: flex-start;
            }
            .stat-card .stat-label { font-size: 15px; color: #888; margin-bottom: 6px; }
            .stat-card .stat-value { font-size: 2em; font-weight: 700; }
            .stat-card .stat-icon { font-size: 1.5em; margin-left: 8px; }
            .dashboard-chart {
                background: #fff; border-radius: 16px; box-shadow: 0 2px 8px #0001;
                padding: 24px; margin-bottom: 24px;
            }
            .chart-bars { display: flex; align-items: flex-end; gap: 18px; height: 120px; margin: 24px 0 12px; }
            .chart-bar {
                flex: 1; display: flex; flex-direction: column; align-items: center;
            }
            .bar {
                width: 32px; border-radius: 8px 8px 0 0; background: #6ea8fe; opacity: 0.3;
                transition: background 0.2s, opacity 0.2s;
            }
            .bar.active { background: #2ecc40; opacity: 1; }
            .chart-label { font-size: 13px; color: #888; margin-top: 6px; }
            .chart-value { font-size: 1.1em; font-weight: 600; color: #222; }
            .chart-actions { display: flex; gap: 12px; margin-top: 18px; }
            .btn { padding: 8px 18px; border-radius: 8px; border: 1px solid #bbb; background: #fff; color: #222; font-weight: 500; cursor: pointer; }
            .btn.primary { background: #2ecc40; color: #fff; border-color: #2ecc40; }
        </style>
</head>
<body>
        <?php include __DIR__ . '/../includes/header.php'; ?>
        <div class="layout">
            <?php include __DIR__ . '/../includes/sidebar.php'; ?>
            <main class="content">
                <h1>Dashboard</h1>
                <div class="stat-cards">
                    <div class="stat-card">
                        <div class="stat-label">Total Students</div>
                        <div class="stat-value">1,234</div>
                        <div class="stat-icon">👥</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Total Professors</div>
                        <div class="stat-value">56</div>
                        <div class="stat-icon">🎓</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Total Sections</div>
                        <div class="stat-value">78</div>
                        <div class="stat-icon">📄</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Submitted Sheets</div>
                        <div class="stat-value">90</div>
                        <div class="stat-icon">📝</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Pending Requests</div>
                        <div class="stat-value">12</div>
                        <div class="stat-icon">📋</div>
                    </div>
                </div>
                <div class="dashboard-chart">
                    <div style="font-size:1.2em;font-weight:600;margin-bottom:8px;">Grading Sheet Submission Statistics</div>
                    <div style="color:#888;font-size:14px;margin-bottom:8px;">Number of submissions in the last 7 days.</div>
                    <div class="chart-value">1,234 <span style="color:#2ecc40;font-size:0.9em;font-weight:500;">↑ 12.5%</span></div>
                    <div class="chart-bars">
                        <div class="chart-bar"><div class="bar" style="height:60px;"></div><div class="chart-label">Mon</div></div>
                        <div class="chart-bar"><div class="bar" style="height:40px;"></div><div class="chart-label">Tue</div></div>
                        <div class="chart-bar"><div class="bar" style="height:32px;"></div><div class="chart-label">Wed</div></div>
                        <div class="chart-bar"><div class="bar" style="height:80px;"></div><div class="chart-label">Thu</div></div>
                        <div class="chart-bar"><div class="bar active" style="height:110px;"></div><div class="chart-label" style="color:#2ecc40;">Fri</div></div>
                        <div class="chart-bar"><div class="bar" style="height:22px;"></div><div class="chart-label">Sat</div></div>
                        <div class="chart-bar"><div class="bar" style="height:28px;"></div><div class="chart-label">Sun</div></div>
                    </div>
                    <div class="chart-actions">
                        <button class="btn">View Full Report</button>
                        <button class="btn primary">Download CSV</button>
                    </div>
                </div>
            </main>
        </div>
        <script src="../assets/js/professor.js"></script>
</body>
</html>