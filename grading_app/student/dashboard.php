<?php

require_once '../includes/config.php';
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit;
}

// Ensure CSRF token is set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$course_count = 0; // Always define before try
try {
    // Get student info
    $stmt = $pdo->prepare("
        SELECT s.*, u.username, u.email
        FROM students s
        JOIN users u ON s.user_id = u.id
        WHERE s.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        throw new Exception('Student record not found');
    }

    // Get student's grades and subjects
    $stmt = $pdo->prepare("
        SELECT sub.name AS subject, g.raw_grade, g.grade, g.comments
        FROM grades g
        JOIN subjects sub ON g.subject_id = sub.id
        WHERE g.student_id = ?
        ORDER BY sub.name
    ");
    $stmt->execute([$student['id']]);
    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate GPA (average of equivalent grades)
    $total_grade = 0;
    $course_count = 0;
    foreach ($grades as $grade) {
        if (is_numeric($grade['grade'])) {
            $grade_value = floatval($grade['grade']);
            $total_grade += $grade_value;
            $course_count++;
        }
    }
    $gpa = $course_count > 0 ? round($total_grade / $course_count, 2) : 0;
    $passed = $gpa > 0 && $gpa <= 3.00 ? 'PASSED' : ($course_count > 0 ? 'FAILED' : 'N/A');

    // Analytics: grade distribution and subject performance
    $grade_bins = ['1.00','1.25','1.50','1.75','2.00','2.25','2.50','2.75','3.00','5.00'];
    $grade_counts = [];
    foreach ($grade_bins as $bin) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM grades WHERE student_id = ? AND grade = ?");
        $stmt->execute([$student['id'], $bin]);
        $grade_counts[] = (int)$stmt->fetchColumn();
    }
    $subject_names = [];
    $subject_grades = [];
    foreach ($grades as $g) {
        $subject_names[] = $g['subject'];
        $subject_grades[] = $g['grade'];
    }

} catch (Exception $e) {
    error_log("Error in student dashboard: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while loading your information. Please try again later.";
    $student = [];
    $grades = [];
    $gpa = 0;
    $course_count = 0;
    $passed = 'N/A';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= isset($_SESSION['theme_preference']) ? htmlspecialchars($_SESSION['theme_preference']) : 'light' ?>">
<head>
    <meta charset="UTF-8" />
    <title>Student Dashboard | Grading System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg-color: #f3f6fd;
            --text-color: #22223b;
            --card-bg: #fff;
            --border-color: #d1d5db;
            --primary: #5f2eea;
            --primary-hover: #3a1fa9;
            --accent: #ffb300;
        }
        [data-theme="dark"] {
            --bg-color: #181a1b;
            --text-color: #f8f9fa;
            --card-bg: #23272b;
            --border-color: #444;
            --primary: #a385ff;
            --primary-hover: #6c4eea;
            --accent: #ffd166;
        }
        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            min-height: 100vh;
            margin: 0;
            padding: 2rem;
        }
        .dashboard-card {
            background-color: var(--card-bg);
            padding: 2.5rem 2rem;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(95,46,234,0.10);
            margin: 2rem auto;
            border: 1px solid var(--border-color);
            max-width: 1100px;
        }
        .card {
            background-color: var(--card-bg);
            border-color: var(--border-color);
        }
        .alert {
            margin-bottom: 1rem;
        }
        .dark-mode-toggle {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 9999;
        }
        h1, h2 {
            color: var(--primary);
            font-weight: 700;
        }
        .btn-danger {
            border-radius: 18px;
            font-weight: 600;
        }
        .table thead th {
            background: linear-gradient(90deg, var(--primary) 60%, var(--accent) 100%);
            color: #fff;
            border: none;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(95,46,234,0.08);
        }
        .card-title {
            color: var(--primary);
            font-weight: 600;
        }
        .average-status {
            font-size: 1.2rem;
            font-weight: bold;
        }
        .average-status .text-success {
            color: #28a745 !important;
        }
        .average-status .text-danger {
            color: #dc3545 !important;
        }
        @media (max-width: 900px) {
            .dashboard-card { padding: 1.2rem 0.5rem; }
        }
    </style>
</head>
<body>

<div class="dark-mode-toggle form-check form-switch">
    <input class="form-check-input" type="checkbox" id="darkModeToggle" />
    <label class="form-check-label" for="darkModeToggle">Dark Mode</label>
</div>

<div class="container">
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="dashboard-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-graduation-cap"></i> Student Dashboard</h1>
            <a href="../logout.php" class="btn btn-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-user"></i> Student Information</h5>
                        <?php if (!empty($student)): ?>
                            <p class="card-text">
                                <strong>Name:</strong> <?= htmlspecialchars($student['name'] ?? 'N/A') ?><br />
                                <strong>Student Number:</strong> <?= htmlspecialchars($student['student_number'] ?? 'N/A') ?><br />
                                <strong>Email:</strong> <?= htmlspecialchars($student['email'] ?? 'N/A') ?>
                            </p>
                        <?php else: ?>
                            <p class="text-muted">Student information not available</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-chart-line"></i> Academic Summary</h5>
                        <p class="card-text">
                            <strong>GPA (Average Equivalent Grade):</strong> <?= number_format($gpa, 2) ?><br />
                            <strong>Total Subjects:</strong> <?= $course_count ?><br />
                            <span class="average-status">
                                <strong>Status:</strong>
                                <?php if ($passed === 'PASSED'): ?>
                                    <span class="text-success"><?= $passed ?></span>
                                <?php elseif ($passed === 'FAILED'): ?>
                                    <span class="text-danger"><?= $passed ?></span>
                                <?php else: ?>
                                    <span class="text-secondary"><?= $passed ?></span>
                                <?php endif; ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="mb-3"><i class="fas fa-book"></i> Subject Grades</h2>
        <?php if (!empty($grades)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Raw Grade</th>
                            <th>Equivalent</th>
                            <th>Comments</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grades as $grade): ?>
                            <tr>
                                <td><?= htmlspecialchars($grade['subject']) ?></td>
                                <td><?= $grade['raw_grade'] ?></td>
                                <td><?= $grade['grade'] ?></td>
                                <td><?= htmlspecialchars($grade['comments'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No grades available yet.
            </div>
        <?php endif; ?>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body">
                    <h5 class="card-title">Your Grade Distribution</h5>
                    <canvas id="gradeDistribution"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body">
                    <h5 class="card-title">Subject Performance</h5>
                    <canvas id="subjectPerformance"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Initialize theme from localStorage or fallback to light
    const toggle = document.getElementById('darkModeToggle');
    const theme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', theme);
    toggle.checked = theme === 'dark';

    // Theme toggle event
    toggle.addEventListener('change', () => {
        const newTheme = toggle.checked ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);

        // Optionally notify server of theme preference (if implemented)
        fetch('../update_theme.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ theme: newTheme })
        }).catch(console.error);
    });

    // Grade Distribution Chart
    new Chart(document.getElementById('gradeDistribution').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($grade_bins) ?>,
            datasets: [{
                label: 'Number of Grades',
                data: <?= json_encode($grade_counts) ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });

    // Subject Performance Chart
    new Chart(document.getElementById('subjectPerformance').getContext('2d'), {
        type: 'line',
        data: {
            labels: <?= json_encode($subject_names) ?>,
            datasets: [{
                label: 'Your Grade',
                data: <?= json_encode($subject_grades) ?>,
                fill: false,
                borderColor: 'rgba(75, 192, 192, 1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true, reverse: true } }
        }
    });
</script>

</body>
</html>