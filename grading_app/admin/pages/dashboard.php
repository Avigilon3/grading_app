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
  'counts' => [],
  'total'  => 0,
  'deltaPct' => 0.0,
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

  $maxCount = 0;
  for ($i = 0; $i < 7; $i++) {
    $d = $start->add(new DateInterval('P' . $i . 'D'));
    $key = $d->format('Y-m-d');
    $cnt = (int)($rows[$key] ?? 0);
    $chart['labels'][] = $d->format('D');
    $chart['counts'][] = $cnt;
    $chart['total'] += $cnt;
    if ($cnt > $maxCount) $maxCount = $cnt;
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
<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
      <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="layout">
  <?php include '../includes/sidebar.php'; ?>
  <main class="content">
    <?php show_flash(); ?>
    <div class="page-header">
      <h1>Dashboard</h1>
      <p class="muted">Welcome, <?= htmlspecialchars(adminCurrentName()); ?>. Here's what's happening with your grading system.</p>
    </div>

    <!-- KPI cards -->
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-label">Total Students</div>
        <div class="kpi-value"><?= (int)$stats['students'] ?></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-label">Total Professors</div>
        <div class="kpi-value"><?= (int)$stats['professors'] ?></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-label">Total Sections</div>
        <div class="kpi-value"><?= (int)$stats['sections'] ?></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-label">Submitted Sheets</div>
        <div class="kpi-value"><?= (int)$stats['submittedSheets'] ?></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-label">Pending Requests</div>
        <div class="kpi-value"><?= (int)$stats['pendingRequests'] ?></div>
      </div>
    </div>

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
      <div class="bar-chart">
        <?php
          $todayLabel = (new DateTimeImmutable('today'))->format('D');
          foreach ($chart['labels'] as $idx => $lbl):
            $cnt = (int)$chart['counts'][$idx];
            $h = (int)round(($cnt / $chart['max']) * 100);
            $isToday = ($lbl === $todayLabel);
        ?>
          <div class="bar-wrap">
            <div class="bar <?= $isToday ? 'today' : '' ?>" style="height: <?= $h ?>%" title="<?= htmlspecialchars($lbl . ': ' . $cnt) ?>"></div>
            <div class="bar-label"><?= htmlspecialchars($lbl) ?></div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="chart-actions">
        <a class="button btn-outline" href="./reports.php">View Full Report</a>
        <a class="button" href="?download=submissions_csv">Download CSV</a>
      </div>
    </div>

    <?php
      $recent = [];
      try {
        $q = $pdo->query('SELECT a.id, a.action, a.details, a.created_at, u.email AS user_email
                           FROM activity_logs a LEFT JOIN users u ON u.id = a.user_id
                           ORDER BY a.id DESC LIMIT 10');
        $recent = $q->fetchAll();
      } catch (Exception $e) { /* ignore */ }
    ?>

    <div class="card">
      <div class="card-body">
        <div class="page-header compact"><h2>Recent Activity</h2></div>
        <table class="table table-striped table-bordered">
          <thead>
            <tr>
              <th>#</th>
              <th>User</th>
              <th>Action</th>
              <th>Details</th>
              <th>When</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$recent): ?>
              <tr><td colspan="5">No recent activity.</td></tr>
            <?php else: foreach ($recent as $r): ?>
              <tr>
                <td><?= (int)$r['id']; ?></td>
                <td><?= htmlspecialchars($r['user_email'] ?: 'N/A'); ?></td>
                <td><?= htmlspecialchars($r['action']); ?></td>
                <td><?= htmlspecialchars((string)$r['details']); ?></td>
                <td><?= htmlspecialchars($r['created_at']); ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>
<script src="../assets/js/admin.js"></script>
</body>
</html>
