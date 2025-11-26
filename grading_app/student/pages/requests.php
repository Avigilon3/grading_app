<?php
require_once '../includes/init.php';
requireStudent();

$currentUserId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
$studentId = null;
$requests = [];
$pageAlert = '';

if ($currentUserId) {
    $studentStmt = $pdo->prepare('SELECT id FROM students WHERE user_id = :uid LIMIT 1');
    $studentStmt->execute([':uid' => $currentUserId]);
    $studentId = $studentStmt->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_type'])) {
    if ($studentId) {
        $requestType = $_POST['request_type'] === 'certificate' ? 'certificate' : 'report';
        $insert = $pdo->prepare('INSERT INTO document_requests (student_id, type, status) VALUES (:sid, :type, :status)');
        $insert->execute([
            ':sid' => $studentId,
            ':type' => $requestType,
            ':status' => 'pending',
        ]);
        header('Location: requests.php?submitted=1');
        exit;
    } else {
        $pageAlert = 'We could not find your student record.';
    }
}

if ($studentId) {
    $requestStmt = $pdo->prepare('SELECT * FROM document_requests WHERE student_id = :sid ORDER BY id DESC');
    $requestStmt->execute([':sid' => $studentId]);
    $requests = $requestStmt->fetchAll(PDO::FETCH_ASSOC);
}

$statusCounts = [
    'pending' => 0,
    'scheduled' => 0,
    'ready' => 0,
    'released' => 0,
];
$nextPickup = null;

foreach ($requests as $row) {
    $status = $row['status'] ?? 'pending';
    if (!array_key_exists($status, $statusCounts)) {
        $status = 'pending';
    }
    $statusCounts[$status]++;

    if (($status === 'scheduled' || $status === 'ready') && !empty($row['scheduled_at'])) {
        $ts = strtotime($row['scheduled_at']);
        if ($ts && (!$nextPickup || $ts < ($nextPickup['ts'] ?? PHP_INT_MAX))) {
            $nextPickup = [
                'ts' => $ts,
                'status' => $status,
                'type' => $row['type'] ?? '',
            ];
        }
    } elseif ($status === 'ready' && !$nextPickup) {
        $nextPickup = [
            'ts' => null,
            'status' => 'ready',
            'type' => $row['type'] ?? '',
        ];
    }
}

$totalRequests = count($requests);
$nextScheduleDate = $nextPickup && $nextPickup['ts'] ? date('M j, Y', $nextPickup['ts']) : null;
$nextScheduleTime = $nextPickup && $nextPickup['ts'] ? date('g:i A', $nextPickup['ts']) : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requests</title>
    <link rel="stylesheet" href="../assets/css/student.css">
    <style>
      .req-page { display: flex; flex-direction: column; gap: 18px; }
      .req-hero { position: relative; overflow: hidden; background: linear-gradient(135deg, #0f2d52, #0f3d2d); border-radius: 16px; padding: 24px; color: #ecf4ff; box-shadow: 0 20px 50px rgba(10, 22, 40, 0.35); }
      .req-hero::after { content: ''; position: absolute; inset: -40%; background: radial-gradient(circle at 30% 30%, rgba(255,255,255,0.08), transparent 38%), radial-gradient(circle at 80% 20%, rgba(80,245,171,0.14), transparent 30%), radial-gradient(circle at 70% 80%, rgba(255,199,95,0.18), transparent 32%); transform: rotate(5deg); z-index: 0; }
      .req-hero-grid { position: relative; display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(240px, 1fr); gap: 20px; z-index: 1; align-items: stretch; }
      .req-eyebrow { text-transform: uppercase; letter-spacing: 0.08em; font-size: 12px; color: #cde6ff; margin: 0; }
      .req-hero h1 { margin: 4px 0 8px; font-size: 28px; letter-spacing: -0.02em; }
      .req-hero p { max-width: 520px; color: rgba(236, 244, 255, 0.9); }
      .req-hero-actions { display: flex; gap: 10px; margin-top: 14px; flex-wrap: wrap; }
      .req-hero-btn { padding: 10px 14px; border-radius: 10px; text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; }
      .req-hero-btn.primary { background: #7fd5a2; color: #0b1f20; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.22); }
      .req-hero-btn.ghost { border: 1px solid rgba(255,255,255,0.35); color: #ecf4ff; }
      .req-hero-meta { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px; }
      .req-hero-pill { padding: 8px 12px; border-radius: 999px; background: rgba(255,255,255,0.12); color: #ecf4ff; font-weight: 600; }
      .req-hero-panel { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 14px; padding: 16px; backdrop-filter: blur(6px); display: flex; flex-direction: column; gap: 6px; }
      .req-panel-label { font-size: 12px; letter-spacing: 0.04em; color: #cde6ff; text-transform: uppercase; margin: 0; }
      .req-panel-value { font-size: 18px; font-weight: 700; margin: 2px 0; }
      .req-panel-sub { color: rgba(236,244,255,0.78); margin: 0; }
      .req-shell { display: flex; flex-direction: column; gap: 16px; }
      .req-stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
      .req-stat-card { background: #fff; border: 1px solid #e6e9f0; border-radius: 12px; padding: 14px; display: flex; align-items: center; gap: 12px; box-shadow: 0 14px 40px rgba(12, 23, 43, 0.08); }
      .req-stat-icon { width: 38px; height: 38px; border-radius: 10px; display: grid; place-items: center; color: #1d3e4c; background: #e8f3ff; }
      .req-stat-card.ready .req-stat-icon { background: #e9f9ee; color: #176d3f; }
      .req-stat-card.scheduled .req-stat-icon { background: #fff4e3; color: #b55f00; }
      .req-stat-card.released .req-stat-icon { background: #f0f3ff; color: #303f9f; }
      .req-stat-label { color: #4b5563; font-size: 13px; margin: 0; }
      .req-stat-value { margin: 2px 0 0; font-size: 24px; font-weight: 800; color: #111827; letter-spacing: -0.01em; }
      .req-columns { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr); gap: 16px; align-items: start; }
      .req-card { background: #fff; border: 1px solid #e6e9f0; border-radius: 14px; padding: 18px; box-shadow: 0 14px 40px rgba(12, 23, 43, 0.08); }
      .req-heading { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 12px; }
      .req-heading h2 { margin: 0; font-size: 18px; }
      .req-stack { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px; }
      .req-tile { border: 1px solid #e6e9f0; border-radius: 12px; padding: 14px; display: flex; flex-direction: column; gap: 8px; background: linear-gradient(180deg, #fdfefe 0%, #f6f9ff 100%); box-shadow: inset 0 1px 0 rgba(255,255,255,0.8); }
      .req-tile-heading { display: flex; gap: 10px; align-items: flex-start; }
      .req-tile-icon { width: 40px; height: 40px; border-radius: 10px; background: #e8f3ff; display: grid; place-items: center; color: #1f4b68; }
      .req-tile-title { margin: 0; font-size: 16px; font-weight: 700; color: #0f172a; }
      .req-tile-sub { margin: 2px 0 0; color: #4b5563; font-size: 13px; }
      .req-tile-points { margin: 4px 0 0 18px; color: #4b5563; font-size: 13px; line-height: 1.5; }
      .req-tile-actions { display: flex; justify-content: flex-end; margin-top: auto; }
      .req-btn { border: none; background: #0f3d2d; color: #fff; padding: 10px 12px; border-radius: 10px; font-weight: 700; cursor: pointer; box-shadow: 0 10px 25px rgba(15, 61, 45, 0.18); display: inline-flex; gap: 8px; align-items: center; }
      .req-btn.secondary { background: #1f4b68; }
      .req-info { margin-top: 12px; padding: 12px; border-radius: 12px; background: #f3f7ff; border: 1px solid #dce6ff; color: #1f2a44; line-height: 1.6; }
      .req-info ul { margin: 6px 0 0 18px; padding: 0; }
      .req-empty { padding: 14px; border: 1px dashed #d1d9e6; border-radius: 12px; background: #f7f9fc; color: #4b5563; }
      .req-history-list { display: flex; flex-direction: column; gap: 12px; }
      .req-history-item { display: grid; grid-template-columns: auto 1fr; gap: 12px; align-items: start; }
      .req-timeline-dot { width: 12px; height: 12px; border-radius: 50%; background: #0f3d2d; margin-top: 8px; box-shadow: 0 0 0 6px rgba(15, 61, 45, 0.08); }
      .req-history-card { background: #fff; border: 1px solid #e6e9f0; border-radius: 12px; padding: 14px; box-shadow: 0 14px 40px rgba(12, 23, 43, 0.08); }
      .req-history-top { display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; align-items: center; }
      .req-chip { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 999px; font-weight: 700; font-size: 12px; background: #e8f3ff; color: #0f3d2d; }
      .req-status { display: inline-flex; gap: 6px; align-items: center; padding: 6px 10px; border-radius: 10px; font-weight: 700; font-size: 12px; }
      .req-status.pending { background: #fff4e3; color: #b55f00; }
      .req-status.scheduled { background: #fff4e3; color: #b55f00; }
      .req-status.ready { background: #e9f9ee; color: #176d3f; }
      .req-status.released { background: #e8edff; color: #263583; }
      .req-history-title { margin: 8px 0 4px; font-size: 16px; color: #0f172a; }
      .req-history-sub { margin: 0 0 8px; color: #4b5563; }
      .req-meta-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px; }
      .req-meta { background: #f7f9fc; border: 1px solid #e6e9f0; border-radius: 10px; padding: 10px; }
      .req-meta-label { margin: 0; text-transform: uppercase; letter-spacing: 0.06em; font-size: 11px; color: #6b7280; }
      .req-meta-value { margin: 4px 0 0; font-weight: 700; color: #0f172a; }
      .req-note { margin-top: 10px; padding: 10px 12px; border-radius: 10px; background: #f3f7ff; color: #1f2a44; border: 1px solid #dce6ff; }
      .req-note.ready { background: #e9f9ee; border-color: #bfead0; color: #0f3d2d; }
      .req-note.pending { background: #fff7ed; border-color: #f4d9ae; color: #8a5200; }
      .req-note.released { background: #f1f5ff; border-color: #d5ddff; color: #1f2a44; }
      @media (max-width: 980px) {
        .req-hero-grid { grid-template-columns: 1fr; }
        .req-columns { grid-template-columns: 1fr; }
      }
      @media (max-width: 640px) {
        .req-stat-grid { grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); }
        .req-history-item { grid-template-columns: 1fr; }
        .req-timeline-dot { display: none; }
      }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <div class="layout">
        <?php include '../includes/sidebar.php'; ?>
        <main class="content">
          <div class="req-page">
            <div class="req-hero">
              <div class="req-hero-grid">
                <div>
                  <p class="req-eyebrow">Registrar Services</p>
                  <h1>Document Requests</h1>
                  <p>Request grade documents, track pickup schedules, and see what is ready at a glance.</p>
                  <div class="req-hero-actions">
                    <a class="req-hero-btn primary" href="#new-request">Request document</a>
                    <a class="req-hero-btn ghost" href="#history">View history</a>
                  </div>
                  <div class="req-hero-meta">
                    <span class="req-hero-pill"><?php echo (int)$totalRequests; ?> total submissions</span>
                    <span class="req-hero-pill"><?php echo (int)($statusCounts['ready'] + $statusCounts['released']); ?> completed</span>
                  </div>
                </div>
                <div class="req-hero-panel">
                  <p class="req-panel-label">Next step</p>
                  <?php if ($nextPickup): ?>
                    <?php $nextLabel = ($nextPickup['type'] ?? '') === 'certificate' ? 'Certificate of Grades' : 'Report of Grades'; ?>
                    <p class="req-panel-value"><?php echo htmlspecialchars($nextLabel); ?></p>
                    <?php if ($nextScheduleDate): ?>
                      <p class="req-panel-sub">Pickup <?php echo htmlspecialchars($nextScheduleDate); ?><?php echo $nextScheduleTime ? ' at ' . htmlspecialchars($nextScheduleTime) : ''; ?>, Registrar's Office</p>
                    <?php else: ?>
                      <p class="req-panel-sub">Ready for pickup. Please bring a valid ID.</p>
                    <?php endif; ?>
                  <?php else: ?>
                    <p class="req-panel-value">No pickup scheduled yet</p>
                    <p class="req-panel-sub">Submit a request to get your schedule.</p>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <?php if (isset($_GET['submitted'])): ?>
              <div class="alert-simple alert-success">Your request was submitted.</div>
            <?php endif; ?>
            <?php if ($pageAlert): ?>
              <div class="alert-simple alert-warning"><?php echo htmlspecialchars($pageAlert); ?></div>
            <?php endif; ?>

            <div class="req-shell">
              <div class="req-stat-grid">
                <div class="req-stat-card">
                  <div class="req-stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="12" cy="12" r="9" />
                      <path d="M12 7v5l3 2" />
                    </svg>
                  </div>
                  <div>
                    <p class="req-stat-label">Pending</p>
                    <p class="req-stat-value"><?php echo (int)$statusCounts['pending']; ?></p>
                  </div>
                </div>
                <div class="req-stat-card scheduled">
                  <div class="req-stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="12" cy="12" r="9" />
                      <path d="M12 7v5l3 2" />
                      <path d="M7 12h10" />
                    </svg>
                  </div>
                  <div>
                    <p class="req-stat-label">Scheduled</p>
                    <p class="req-stat-value"><?php echo (int)$statusCounts['scheduled']; ?></p>
                  </div>
                </div>
                <div class="req-stat-card ready">
                  <div class="req-stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M9 12l2 2 4-4" />
                      <circle cx="12" cy="12" r="9" />
                    </svg>
                  </div>
                  <div>
                    <p class="req-stat-label">Ready for pickup</p>
                    <p class="req-stat-value"><?php echo (int)$statusCounts['ready']; ?></p>
                  </div>
                </div>
                <div class="req-stat-card released">
                  <div class="req-stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M5 12l5 5L19 7" />
                    </svg>
                  </div>
                  <div>
                    <p class="req-stat-label">Released</p>
                    <p class="req-stat-value"><?php echo (int)$statusCounts['released']; ?></p>
                  </div>
                </div>
              </div>

              <div class="req-columns">
                <section class="req-card" id="new-request">
                  <div class="req-heading">
                    <h2>Submit a request</h2>
                    <span class="req-chip">Official dry seal guaranteed</span>
                  </div>
                  <div class="req-stack">
                    <form class="req-tile" method="post">
                      <input type="hidden" name="request_type" value="report">
                      <div class="req-tile-heading">
                        <div class="req-tile-icon" aria-hidden="true">
                          <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke="currentColor">
                            <path d="M7 3h10l3 3v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" />
                            <path d="M17 3v4h4" />
                            <path d="M12 9v6M9 12h6" />
                          </svg>
                        </div>
                        <div>
                          <p class="req-tile-title">Report of Grades</p>
                          <p class="req-tile-sub">Official grade report with the school dry seal.</p>
                        </div>
                      </div>
                      <ul class="req-tile-points">
                        <li>Processing window: 3-5 business days</li>
                        <li>Pick up at Registrar's Office with valid ID</li>
                      </ul>
                      <div class="req-tile-actions">
                        <button type="submit" class="req-btn secondary">
                          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 5v14M5 12h14" />
                          </svg>
                          Request report
                        </button>
                      </div>
                    </form>

                    <form class="req-tile" method="post">
                      <input type="hidden" name="request_type" value="certificate">
                      <div class="req-tile-heading">
                        <div class="req-tile-icon" aria-hidden="true">
                          <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke="currentColor">
                            <path d="M7 3h10l3 3v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" />
                            <path d="M17 3v4h4" />
                            <path d="M9 13h6M9 17h3M9 9h6" />
                          </svg>
                        </div>
                        <div>
                          <p class="req-tile-title">Certificate of Grades</p>
                          <p class="req-tile-sub">For official authentication with the school dry seal.</p>
                        </div>
                      </div>
                      <ul class="req-tile-points">
                        <li>Use for scholarship, employment, and verification</li>
                        <li>Pickup schedule sent once approved</li>
                      </ul>
                      <div class="req-tile-actions">
                        <button type="submit" class="req-btn">
                          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 5v14M5 12h14" />
                          </svg>
                          Request certificate
                        </button>
                      </div>
                    </form>
                  </div>
                  <div class="req-info">
                    <strong>Before you go</strong>
                    <ul>
                      <li>All documents include the official school dry seal.</li>
                      <li>Processing takes 3-5 business days from submission.</li>
                      <li>Bring a valid ID for pickup at the Registrar's Office.</li>
                      <li>Office hours: Monday to Friday, 8:00 AM - 5:00 PM.</li>
                    </ul>
                  </div>
                </section>

                <section class="req-card" id="history">
                  <div class="req-heading">
                    <h2>Recent requests</h2>
                    <span class="req-chip"><?php echo (int)$totalRequests; ?> total</span>
                  </div>
                  <?php if (empty($requests)): ?>
                    <div class="req-empty">You haven't requested any grade-related documents yet.</div>
                  <?php else: ?>
                    <div class="req-history-list">
                      <?php foreach ($requests as $row): ?>
                        <?php
                          $typeLabel = ($row['type'] === 'certificate') ? 'Certificate of Grades' : 'Report of Grades';
                          $typeSub = ($row['type'] === 'certificate') ? 'Official certificate with school dry seal' : 'Official grade report with school dry seal';

                          $statusClass = 'pending';
                          $statusText = 'Pending';
                          $noteText = 'Your request is being processed. You will be notified of the pickup date and time.';

                          if ($row['status'] === 'scheduled') {
                              $statusClass = 'scheduled';
                              $statusText = 'Scheduled';
                              $noteText = 'Pickup schedule set.';
                          } elseif ($row['status'] === 'ready') {
                              $statusClass = 'ready';
                              $statusText = 'Ready';
                              $noteText = 'Ready for pickup at the Registrar\'s Office.';
                          } elseif ($row['status'] === 'released') {
                              $statusClass = 'released';
                              $statusText = 'Released';
                              $noteText = 'Released to you.';
                          }

                          $requestedText = 'Requested date not recorded';
                          if (!empty($row['created_at'])) {
                              $requestedText = date('M j, Y', strtotime($row['created_at']));
                          } elseif (!empty($row['requested_at'])) {
                              $requestedText = date('M j, Y', strtotime($row['requested_at']));
                          }

                          $scheduleDate = '';
                          $scheduleTime = '';
                          if (!empty($row['scheduled_at'])) {
                              $timestamp = strtotime($row['scheduled_at']);
                              if ($timestamp) {
                                  $scheduleDate = date('M j, Y', $timestamp);
                                  $scheduleTime = date('g:i A', $timestamp);
                              }
                          }
                          $releaseText = '';
                          if (!empty($row['released_at'])) {
                              $releaseTime = strtotime($row['released_at']);
                              if ($releaseTime) {
                                  $releaseText = date('M j, Y', $releaseTime);
                              }
                          }
                        ?>
                        <div class="req-history-item">
                          <div class="req-timeline-dot" aria-hidden="true"></div>
                          <div class="req-history-card">
                            <div class="req-history-top">
                              <span class="req-chip"><?php echo htmlspecialchars($typeLabel); ?></span>
                              <span class="req-status <?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusText); ?></span>
                            </div>
                            <p class="req-history-title"><?php echo htmlspecialchars($typeLabel); ?></p>
                            <p class="req-history-sub"><?php echo htmlspecialchars($typeSub); ?></p>
                            <div class="req-meta-grid">
                              <div class="req-meta">
                                <p class="req-meta-label">Requested</p>
                                <p class="req-meta-value"><?php echo htmlspecialchars($requestedText); ?></p>
                              </div>
                              <div class="req-meta">
                                <p class="req-meta-label">Status</p>
                                <p class="req-meta-value"><?php echo htmlspecialchars($statusText); ?></p>
                              </div>
                              <div class="req-meta">
                                <p class="req-meta-label">Pickup</p>
                                <p class="req-meta-value">
                                  <?php if ($scheduleDate): ?>
                                    <?php echo htmlspecialchars($scheduleDate . ($scheduleTime ? ' - ' . $scheduleTime : '')); ?>
                                  <?php elseif ($releaseText): ?>
                                    Released <?php echo htmlspecialchars($releaseText); ?>
                                  <?php else: ?>
                                    To be scheduled
                                  <?php endif; ?>
                                </p>
                              </div>
                            </div>
                            <div class="req-note <?php echo $statusClass; ?>">
                              <?php if ($row['status'] === 'ready' || $row['status'] === 'scheduled'): ?>
                                <strong><?php echo ($row['status'] === 'ready') ? 'Ready for pickup' : 'Pickup schedule'; ?></strong><br>
                                <?php if ($scheduleDate): ?>
                                  Date: <?php echo htmlspecialchars($scheduleDate); ?><br>
                                <?php endif; ?>
                                <?php if ($scheduleTime): ?>
                                  Time: <?php echo htmlspecialchars($scheduleTime); ?><br>
                                <?php endif; ?>
                                Location: Registrar's Office
                              <?php elseif ($row['status'] === 'released' && $releaseText): ?>
                                Released on <?php echo htmlspecialchars($releaseText); ?>.
                              <?php else: ?>
                                <?php echo htmlspecialchars($noteText); ?>
                              <?php endif; ?>
                            </div>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                </section>
              </div>
            </div>
          </div>
        </main>

    </div>

    
</body>
<script src="../assets/js/student.js"></script>
</html>
