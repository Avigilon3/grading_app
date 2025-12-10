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
            sec.year_level,
            sub.subject_code,
            sub.subject_title,
            t.term_name,
            gs.id AS grading_sheet_id
       FROM section_subjects ss
       JOIN sections sec ON sec.id = ss.section_id
       JOIN subjects sub ON sub.id = ss.subject_id
  LEFT JOIN terms t ON t.id = ss.term_id
  LEFT JOIN grading_sheets gs ON gs.section_subject_id = ss.id
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
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    </head>
<body>
  <?php include '../includes/header.php'; ?>
<div class="layout"> 
  <?php include '../includes/sidebar.php'; ?>
  <main class="content">
    <?php show_flash(); ?>
    <div class="page-header">
      <h1>Dashboard Overview</h1>
      <p>Welcome back! Here's your grading summary for this term.</p>
    </div>

      <section class="dashboard-stats">
          <?php
              $statCards = [
                  ['label' => 'Total Classes', 'value' => $totalClasses, 'icon' => 'menu_book'],
                  ['label' => 'Total Students', 'value' => $totalStudents, 'icon' => 'groups_2'],
                  ['label' => 'Pending Grades', 'value' => $pendingGrades, 'icon' => 'schedule'],
                  ['label' => 'Submitted Sheets', 'value' => $submittedSheets, 'icon' => 'task_alt'],
              ];
          ?>
          <?php foreach ($statCards as $card): ?>
              <article class="stat-card">

                  <div class="stat-text">
                  <p class="stat-label"><?= htmlspecialchars($card['label']); ?></p>
                  <p class="stat-value"><?= (int)$card['value']; ?></p>
                  </div>

                  <div class="stat-icon">
                      <span class="material-symbols-rounded"><?= htmlspecialchars($card['icon']); ?></span>
                  </div>
              </article>
          <?php endforeach; ?>
      </section>

      <section class="dashboard-section">
          <div class="section-header">
              <h3>My Classes</h3>
              <a class="link-muted" href="./my_sections.php">View all</a>
          </div>
          <?php if (empty($classes)): ?>
              <div class="empty-state-card">
                  <p>No active teaching assignments yet.</p>
              </div>
          <?php else: ?>
              <div class="dashboard-classes">
                  <?php foreach (array_slice($classes, 0, 4) as $class): ?>
                      <?php
                          $title = trim(($class['subject_code'] ?? '') . ' - ' . ($class['section_name'] ?? ''));
                          $subtitle = $class['subject_title'] ?? '';
                          $meta = $class['term_name'] ?? '';
                          $sheetId = $class['grading_sheet_id'] ?? null;
                          $viewUrl = $sheetId ? './grading_sheet.php?sheet_id=' . (int)$sheetId : './grading_sheet.php';
                      ?>
                      <article class="class-pill">
                          <div>
                              <p class="class-pill__title"><?= htmlspecialchars($title ?: 'Assigned Class'); ?></p>
                              <?php if ($subtitle): ?>
                                  <p class="class-pill__subtitle"><?= htmlspecialchars($subtitle); ?></p>
                              <?php endif; ?>
                              <?php if ($meta): ?>
                                  <p class="class-pill__meta"><?= htmlspecialchars($meta); ?></p>
                              <?php endif; ?>
                          </div>
                          <a class="btn view" href="<?= htmlspecialchars($viewUrl); ?>">View</a>
                      </article>
                  <?php endforeach; ?>
              </div>
          <?php endif; ?>
      </section>

      <section class="dashboard-section">
          <div class="section-header">
              <h3>Upcoming Deadlines</h3>
          </div>
          <?php if (empty($deadlines)): ?>
              <div class="empty-state-card">
                  <p>No upcoming deadlines.</p>
              </div>
          <?php else: ?>

                  <?php foreach ($deadlines as $d): ?>
                      <?php
                          $deadlineAt = $d['deadline_at'] ?? null;
                          $isOverdue = $deadlineAt ? (new DateTimeImmutable($deadlineAt) < new DateTimeImmutable()) : false;
                          $dueText = dueText($deadlineAt);
                          $status = ucfirst($d['status'] ?? 'draft');
                      ?>
                      <article class="deadline-card <?= $isOverdue ? 'deadline-card--warning' : 'deadline-card--info'; ?>">
                          <div>
                              <p class="deadline-card__title"><?= htmlspecialchars($d['section_name'] ?? 'Section'); ?></p>
                              <p class="deadline-card__meta">Status: <?= htmlspecialchars($status); ?></p>
                          </div>
                          <p class="deadline-card__due"><?= htmlspecialchars($dueText); ?></p>
                      </article>
                  <?php endforeach; ?>

          <?php endif; ?>
      </section>
  </main>
</div>
<script src="../assets/js/professor.js"></script>
</body>
<?php include '../includes/footer.php'; ?>
</html>
