
<?php
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
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
=======
require_once '../includes/init.php';
requireProfessor();

$professor = requireProfessorRecord($pdo);
$professorId = (int)$professor['id'];

=======
require_once '../includes/init.php';
requireProfessor();

$professor = requireProfessorRecord($pdo);
$professorId = (int)$professor['id'];

>>>>>>> Stashed changes
=======
require_once '../includes/init.php';
requireProfessor();

$professor = requireProfessorRecord($pdo);
$professorId = (int)$professor['id'];

>>>>>>> Stashed changes
=======
require_once '../includes/init.php';
requireProfessor();

$professor = requireProfessorRecord($pdo);
$professorId = (int)$professor['id'];

>>>>>>> Stashed changes
=======
require_once '../includes/init.php';
requireProfessor();

$professor = requireProfessorRecord($pdo);
$professorId = (int)$professor['id'];

>>>>>>> Stashed changes
=======
require_once '../includes/init.php';
requireProfessor();

$professor = requireProfessorRecord($pdo);
$professorId = (int)$professor['id'];

>>>>>>> Stashed changes
$totalClasses = 0;
$totalStudents = 0;
$pendingGrades = 0;
$submittedSheets = 0;

// Total assigned section-subject pairs for this professor
$stmt = $pdo->prepare('SELECT COUNT(*) AS cnt FROM section_subjects WHERE professor_id = ?');
$stmt->execute([$professorId]);
$totalClasses = (int)($stmt->fetchColumn() ?: 0);

// Distinct students across the professor's sections
$stmt = $pdo->prepare(
    'SELECT COUNT(DISTINCT ss.student_id) AS cnt
       FROM section_students ss
       JOIN section_subjects subj ON subj.section_id = ss.section_id
      WHERE subj.professor_id = ?'
);
$stmt->execute([$professorId]);
$totalStudents = (int)($stmt->fetchColumn() ?: 0);

// Pending grading sheets (not yet submitted)
$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS cnt
       FROM grading_sheets
      WHERE professor_id = ?
        AND status IN ('draft','reopened')"
);
$stmt->execute([$professorId]);
$pendingGrades = (int)($stmt->fetchColumn() ?: 0);

// Submitted / locked sheets
$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS cnt
       FROM grading_sheets
      WHERE professor_id = ?
        AND status IN ('submitted','locked')"
);
$stmt->execute([$professorId]);
$submittedSheets = (int)($stmt->fetchColumn() ?: 0);

// Fetch the current teaching assignments
$stmt = $pdo->prepare(
    'SELECT ss.id AS assignment_id,
            sec.section_name,
            sub.subject_code,
            sub.subject_title,
            t.term_name
       FROM section_subjects ss
       JOIN sections sec ON sec.id = ss.section_id
       JOIN subjects sub ON sub.id = ss.subject_id
  LEFT JOIN terms t ON t.id = ss.term_id
      WHERE ss.professor_id = ?
   ORDER BY sec.section_name, sub.subject_title'
);
$stmt->execute([$professorId]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch grading sheet deadlines (if any)
$stmt = $pdo->prepare(
    'SELECT gs.id,
            gs.deadline_at,
            gs.status,
            sec.section_name
       FROM grading_sheets gs
       JOIN sections sec ON sec.id = gs.section_id
      WHERE gs.professor_id = ?
   ORDER BY (gs.deadline_at IS NULL), gs.deadline_at ASC
      LIMIT 5'
);
$stmt->execute([$professorId]);
$deadlines = $stmt->fetchAll(PDO::FETCH_ASSOC);

function dueText(?string $date): string
{
    if (!$date) {
        return 'No deadline set';
    }

    $now = new DateTimeImmutable('now');
    $due = new DateTimeImmutable($date);

    if ($due < $now) {
        return 'Past due';
    }

    $diff = $now->diff($due);
    if ($diff->days === 0) {
        return 'Due today';
    }

    if ($diff->days <= 7) {
        return 'Due in ' . $diff->days . ' days';
    }

    return 'Due ' . $due->format('M j, Y');
}
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
?>
<!DOCTYPE html>
<html lang="en">
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
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
=======
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
            <title>Professor Portal Dashboard</title>
        <link rel="stylesheet" href="../assets/css/professor.css">
    </head>
<body>
=======
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
            <title>Professor Portal Dashboard</title>
        <link rel="stylesheet" href="../assets/css/professor.css">
    </head>
<body>
>>>>>>> Stashed changes
=======
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
            <title>Professor Portal Dashboard</title>
        <link rel="stylesheet" href="../assets/css/professor.css">
    </head>
<body>
>>>>>>> Stashed changes
=======
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
            <title>Professor Portal Dashboard</title>
        <link rel="stylesheet" href="../assets/css/professor.css">
    </head>
<body>
>>>>>>> Stashed changes
=======
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
            <title>Professor Portal Dashboard</title>
        <link rel="stylesheet" href="../assets/css/professor.css">
    </head>
<body>
>>>>>>> Stashed changes
  <?php include '../includes/header.php'; ?>
<div class="layout"> 
  <?php include '../includes/sidebar.php'; ?>
  <main class="content">
    <?php show_flash(); ?>
    <div class="page-header">
      <h2>Dashboard Overview</h2>
      <p>Welcome back! Here's your grading summary for this term.</p>
    </div>

      <div class="stats">
          <div class="stat"><small>Total Classes</small> <span><?= $totalClasses ?></span></div>
          <div class="stat"><small>Total Students</small> <span><?= $totalStudents ?></span></div>
          <div class="stat"><small>Pending Grades</small> <span><?= $pendingGrades ?></span></div>
          <div class="stat"><small>Submitted Sheets</small> <span><?= $submittedSheets ?></span></div>
      </div>

      <h3>My Classes</h3>
      <div class="classes">
          <?php if (empty($classes)): ?>
            <p>No active teaching assignments yet.</p>
          <?php else: ?>
            <?php foreach ($classes as $class): ?>
              <div class="class-card">
                <strong><?= htmlspecialchars($class['subject_code'] ?? 'Subject') ?></strong><br>
                <small><?= htmlspecialchars($class['subject_title'] ?? 'Untitled subject') ?></small><br>
                <small><?= htmlspecialchars($class['section_name'] ?? '') ?></small><br>
                <?php if(!empty($class['term_name'])): ?>
                <small><?= htmlspecialchars($class['term_name']) ?></small><br>
                <?php endif; ?>
                <button type="button" onclick="window.location.href='./grading_sheet.php'">Open Sheet</button>
              </div>
              <?php endforeach; ?>
          <?php endif; ?>
        </div>

      <div class="deadline-list">
          <h3>Upcoming Deadlines</h3>
          <?php if(empty($deadlines)): ?>
              <p>No upcoming deadlines.</p>
          <?php endif; ?>
          <?php foreach($deadlines as $d): ?>
              <?php 
                  $deadlineAt = $d['deadline_at'] ?? null;
                  $now = new DateTimeImmutable();
                  $dueClass = ($deadlineAt && new DateTimeImmutable($deadlineAt) < $now) ? 'deadline-upcoming' : 'deadline-later'; 
                  $dueText = dueText($deadlineAt);
              ?>
              <div class="deadline-item <?= $dueClass ?>">
                  <strong><?= htmlspecialchars($d['section_name'] ?? 'Section') ?></strong><br>
                  <small>Status: <?= htmlspecialchars(ucfirst($d['status'] ?? 'draft')) ?></small>
                  <span style="float: right;"><?= $dueText ?></span>
              </div>
          <?php endforeach; ?>
        </div>
  </main>
</div>
<script src="../assets/js/professor.js"></script>
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
</body>
<?php include '../includes/footer.php'; ?>
</html>
=======
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
            <title>Professor Portal Dashboard</title>
        <link rel="stylesheet" href="../assets/css/professor.css">
    </head>
<body>
  <?php include '../includes/header.php'; ?>
<div class="layout"> 
  <?php include '../includes/sidebar.php'; ?>
  <main class="content">
    <?php show_flash(); ?>
    <div class="page-header">
      <h2>Dashboard Overview</h2>
      <p>Welcome back! Here's your grading summary for this term.</p>
    </div>

      <div class="stats">
          <div class="stat"><small>Total Classes</small> <span><?= $totalClasses ?></span></div>
          <div class="stat"><small>Total Students</small> <span><?= $totalStudents ?></span></div>
          <div class="stat"><small>Pending Grades</small> <span><?= $pendingGrades ?></span></div>
          <div class="stat"><small>Submitted Sheets</small> <span><?= $submittedSheets ?></span></div>
      </div>

      <h3>My Classes</h3>
      <div class="classes">
          <?php if (empty($classes)): ?>
            <p>No active teaching assignments yet.</p>
          <?php else: ?>
            <?php foreach ($classes as $class): ?>
              <div class="class-card">
                <strong><?= htmlspecialchars($class['subject_code'] ?? 'Subject') ?></strong><br>
                <small><?= htmlspecialchars($class['subject_title'] ?? 'Untitled subject') ?></small><br>
                <small><?= htmlspecialchars($class['section_name'] ?? '') ?></small><br>
                <?php if(!empty($class['term_name'])): ?>
                <small><?= htmlspecialchars($class['term_name']) ?></small><br>
                <?php endif; ?>
                <button type="button" onclick="window.location.href='./grading_sheet.php'">Open Sheet</button>
              </div>
              <?php endforeach; ?>
          <?php endif; ?>
        </div>

      <div class="deadline-list">
          <h3>Upcoming Deadlines</h3>
          <?php if(empty($deadlines)): ?>
              <p>No upcoming deadlines.</p>
          <?php endif; ?>
          <?php foreach($deadlines as $d): ?>
              <?php 
                  $deadlineAt = $d['deadline_at'] ?? null;
                  $now = new DateTimeImmutable();
                  $dueClass = ($deadlineAt && new DateTimeImmutable($deadlineAt) < $now) ? 'deadline-upcoming' : 'deadline-later'; 
                  $dueText = dueText($deadlineAt);
              ?>
              <div class="deadline-item <?= $dueClass ?>">
                  <strong><?= htmlspecialchars($d['section_name'] ?? 'Section') ?></strong><br>
                  <small>Status: <?= htmlspecialchars(ucfirst($d['status'] ?? 'draft')) ?></small>
                  <span style="float: right;"><?= $dueText ?></span>
              </div>
          <?php endforeach; ?>
        </div>
  </main>
</div>
<script src="../assets/js/professor.js"></script>
</body>
<?php include '../includes/footer.php'; ?>
</html>
>>>>>>> Stashed changes
