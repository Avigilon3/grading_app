<?php
require_once '../includes/init.php';
requireAdmin();

// Optional: CSV download for the submissions chart
function outputSubmissionsCsv(PDO $pdo): void {
  try {
    $today  = new DateTimeImmutable('today');
    $start  = $today->sub(new DateInterval('P6D'));
    $end    = $today->add(new DateInterval('P1D'));

    $stmt = $pdo->prepare(
      "SELECT DATE(submitted_at) d, COUNT(*) c
       FROM grading_sheets
       WHERE status='submitted' AND submitted_at >= :start AND submitted_at < :end
       GROUP BY DATE(submitted_at)"
    );
    $stmt->execute([
      ':start' => $start->format('Y-m-d 00:00:00'),
      ':end'   => $end->format('Y-m-d 00:00:00'),
    ]);
    $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // ['Y-m-d' => count]

    // Build 7-day data
    $data = [];
    for ($i = 0; $i < 7; $i++) {
      $d = $start->add(new DateInterval('P' . $i . 'D'));
      $key = $d->format('Y-m-d');
      $data[] = [$key, (int)($rows[$key] ?? 0)];
    }

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="grading_submissions_last7days.csv"');
    echo "Date,Count\n";
    foreach ($data as $row) {
      echo $row[0] . ',' . $row[1] . "\n";
    }
  } catch (Throwable $e) {
    header('Content-Type: text/plain', true, 500);
    echo 'Failed to generate CSV.';
  }
  exit;
}

if (isset($_GET['download']) && $_GET['download'] === 'submissions_csv') {
  outputSubmissionsCsv($pdo);
}

// KPI stats for dashboard widgets
try {
  $stats = [
    'students'        => (int)$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn(),
    'professors'      => (int)$pdo->query('SELECT COUNT(*) FROM professors')->fetchColumn(),
    'sections'        => (int)$pdo->query('SELECT COUNT(*) FROM sections')->fetchColumn(),
    'submittedSheets' => (int)$pdo->query("SELECT COUNT(*) FROM grading_sheets WHERE status='submitted'")->fetchColumn(),
    'pendingRequests' => (int)$pdo->query("SELECT COUNT(*) FROM edit_requests WHERE status='pending'")->fetchColumn(),
  ];
} catch (Exception $e) {
  $stats = [
    'students' => 0,
    'professors' => 0,
    'sections' => 0,
    'submittedSheets' => 0,
    'pendingRequests' => 0,
  ];
}

// Build last-7-days submission stats and WoW delta
$chart = [
  'labels' => [],
  'submitted' => [],
  'pending' => [],
  'total'  => 0,
  'deltaPct' => 0.0,
  'max' => 1,
];

try {
  $today = new DateTimeImmutable('today');
  $start = $today->sub(new DateInterval('P6D'));
  $end   = $today->add(new DateInterval('P1D')); // exclusive upper bound

  // Current 7 days grouped by date
  $stmt = $pdo->prepare(
    "SELECT DATE(submitted_at) d, COUNT(*) c
     FROM grading_sheets
     WHERE status='submitted' AND submitted_at >= :start AND submitted_at < :end
     GROUP BY DATE(submitted_at)"
  );
  $stmt->execute([
    ':start' => $start->format('Y-m-d 00:00:00'),
    ':end'   => $end->format('Y-m-d 00:00:00'),
  ]);
  $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // ['Y-m-d' => count]

  $pendingStmt = $pdo->prepare(
    "SELECT DATE(deadline_at) d, COUNT(*) c
       FROM grading_sheets
      WHERE status IN ('draft','reopened')
        AND deadline_at IS NOT NULL
        AND deadline_at >= :start
        AND deadline_at < :end
      GROUP BY DATE(deadline_at)"
  );
  $pendingStmt->execute([
    ':start' => $start->format('Y-m-d 00:00:00'),
    ':end'   => $end->format('Y-m-d 00:00:00'),
  ]);
  $pendingRows = $pendingStmt->fetchAll(PDO::FETCH_KEY_PAIR);

  $maxCount = 0;
  for ($i = 0; $i < 7; $i++) {
    $d = $start->add(new DateInterval('P' . $i . 'D'));
    $key = $d->format('Y-m-d');
    $submittedCnt = (int)($rows[$key] ?? 0);
    $pendingCnt = (int)($pendingRows[$key] ?? 0);
    $chart['labels'][] = $d->format('D');
    $chart['submitted'][] = $submittedCnt;
    $chart['pending'][] = $pendingCnt;
    $chart['total'] += $submittedCnt;
    $maxCount = max($maxCount, $submittedCnt, $pendingCnt);
  }
  $chart['max'] = max(1, $maxCount);

  // Previous 7 days (for delta)
  $prevStart = $start->sub(new DateInterval('P7D'));
  $prevEnd   = $start; // exclusive
  $stmt2 = $pdo->prepare(
    "SELECT COUNT(*) FROM grading_sheets
     WHERE status='submitted' AND submitted_at >= :start AND submitted_at < :end"
  );
  $stmt2->execute([
    ':start' => $prevStart->format('Y-m-d 00:00:00'),
    ':end'   => $prevEnd->format('Y-m-d 00:00:00'),
  ]);
  $prevTotal = (int)$stmt2->fetchColumn();
  if ($prevTotal > 0) {
    $chart['deltaPct'] = (($chart['total'] - $prevTotal) / $prevTotal) * 100.0;
  } else {
    $chart['deltaPct'] = $chart['total'] > 0 ? 100.0 : 0.0;
  }
} catch (Exception $e) {
  // leave defaults
}
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
      <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
  </head>
<body>
<?php include '../includes/header.php'; ?>
<div class="layout">
  <?php include '../includes/sidebar.php'; ?>
  <main class="content">
    <?php show_flash(); ?>
    <div class="page-header">
      <h1>Dashboard</h1>
      <p class="text-muted">Welcome, <?= htmlspecialchars(adminCurrentName()); ?>. Here's what's happening with your grading system.</p>
    </div>

    <?php
      $dashboardCards = [
        ['label' => 'Total Students', 'value' => (int)$stats['students'], 'icon' => 'school'],
        ['label' => 'Total Professors', 'value' => (int)$stats['professors'], 'icon' => 'group'],
        ['label' => 'Total Sections', 'value' => (int)$stats['sections'], 'icon' => 'class'],
        ['label' => 'Submitted Sheets', 'value' => (int)$stats['submittedSheets'], 'icon' => 'task_alt'],
        ['label' => 'Pending Requests', 'value' => (int)$stats['pendingRequests'], 'icon' => 'pending_actions'],
      ];
    ?>
    <section class="dashboard-stats">
      <?php foreach ($dashboardCards as $card): ?>
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

    <section class="pending-card">
      <div class="pending-card__info">
        <div class="pending-card__icon">
          <span class="material-symbols-rounded">schedule</span>
        </div>
        <div>
          <p class="pending-card__label">Pending Requests</p>
          <p class="pending-card__value"><?= (int)$stats['pendingRequests']; ?></p>
          <p class="pending-card__muted">Awaiting approval</p>
        </div>
      </div>
      <a class="btn primary" href="./report_management.php">View All</a>
    </section>

    <!-- Submission statistics chart -->
    <div class="chart-card">
      <p class="chart-title">Grading Sheet Submission Statistics</p>
      <p class="chart-subtitle">Number of submissions in the last 7 days.</p>
      <div class="chart-header">
        <div class="chart-total"><?= (int)$chart['total'] ?></div>
        <?php
          $delta = round($chart['deltaPct'], 1);
          $deltaSign = $delta > 0 ? '+' : ($delta < 0 ? '' : '');
          $deltaClass = $delta >= 0 ? 'chart-delta' : 'chart-delta' ; // keep same color; tweak if needed
        ?>
        <div class="<?= $deltaClass ?>"><?= $deltaSign . $delta ?>%</div>
      </div>
      <div class="chart-legend">
        <span><span class="legend-dot legend-dot--submitted"></span>Submitted</span>
        <span><span class="legend-dot legend-dot--pending"></span>Pending</span>
      </div>
      <div class="bar-chart">
        <?php
          $todayLabel = (new DateTimeImmutable('today'))->format('D');
          foreach ($chart['labels'] as $idx => $lbl):
            $submittedCnt = (int)($chart['submitted'][$idx] ?? 0);
            $pendingCnt = (int)($chart['pending'][$idx] ?? 0);
            $submittedHeight = $chart['max'] > 0 ? (int)round(($submittedCnt / $chart['max']) * 100) : 0;
            $pendingHeight = $chart['max'] > 0 ? (int)round(($pendingCnt / $chart['max']) * 100) : 0;
            $isToday = ($lbl === $todayLabel);
        ?>
          <div class="bar-wrap">
            <div class="bar-track">
              <div class="bar submitted <?= $isToday ? 'today' : '' ?>" style="height: <?= $submittedHeight ?>%" title="<?= htmlspecialchars($lbl . ' submitted: ' . $submittedCnt) ?>"></div>
              <div class="bar pending" style="height: <?= $pendingHeight ?>%" title="<?= htmlspecialchars($lbl . ' pending: ' . $pendingCnt) ?>"></div>
            </div>
            <div class="bar-label"><?= htmlspecialchars($lbl) ?></div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="chart-actions">
        <a class="button btn-outline" href="./reports.php">View Full Report</a>
        <a class="button" href="?download=submissions_csv">Download CSV</a>
      </div>
    </div>


  </main>
</div>
<script src="../assets/js/admin.js"></script>
</body>
<?php include '../includes/footer.php'; ?>
</html>
