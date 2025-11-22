<?php
require_once '../includes/init.php';
requireProfessor();

$professor = requireProfessorRecord($pdo);
$professorId = (int)$professor['id'];

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
?>

<!DOCTYPE html>
<html lang="en">
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