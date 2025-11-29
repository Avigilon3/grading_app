<?php
require_once '../includes/init.php';
requireAdmin();

// Handle actions for requests management
$err = $msg = null;

$err = $msg = null;

        //dito maglagay if may need ifetch sa database
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  try {
    if ($action === 'docreq_update') {
      $id = (int)($_POST['id'] ?? 0);
      $status = $_POST['status'] ?? '';
      $scheduled_at = trim($_POST['scheduled_at'] ?? '');
      $released_at  = trim($_POST['released_at'] ?? '');
      $allowed = ['pending','scheduled','ready','released','completed'];
      if (!$id || !in_array($status, $allowed, true)) {
        throw new Exception('Invalid document request update.');
      }
      $stmt = $pdo->prepare('UPDATE document_requests SET status=?, scheduled_at=?, released_at=? WHERE id=?');
      $stmt->execute([$status, ($scheduled_at ?: null), ($released_at ?: null), $id]);
      add_activity_log($pdo, $_SESSION['user']['id'] ?? null, 'UPDATE_DOC_REQUEST', 'Updated document request id: '.$id.' -> '.$status);
      $msg = 'Document request updated.';
    }

    if ($action === 'editreq_decide') {
      $id = (int)($_POST['id'] ?? 0);
      $decision = $_POST['decision'] ?? '';
      if (!$id || !in_array($decision, ['approved','denied'], true)) {
        throw new Exception('Invalid decision.');
      }
      $stmt = $pdo->prepare('UPDATE edit_requests SET status=?, decided_by=?, decided_at=NOW() WHERE id=?');
      $stmt->execute([$decision, ($_SESSION['user']['id'] ?? null), $id]);
      add_activity_log($pdo, $_SESSION['user']['id'] ?? null, 'DECIDE_EDIT_REQUEST', strtoupper($decision).' edit request id: '.$id);
      $msg = 'Edit request '.$decision.'.';
    }
  } catch (Exception $e) {
    $err = 'Error: '.$e->getMessage();
  }
}

// Load requests
$docRequests = $pdo->query(
  "SELECT dr.id, dr.type, dr.purpose, dr.status, dr.scheduled_at, dr.released_at,
          COALESCE(dr.scheduled_at, dr.released_at) AS request_date,
          s.student_id, s.first_name, s.middle_name, s.last_name, s.year_level
     FROM document_requests dr
LEFT JOIN students s ON s.id = dr.student_id
 ORDER BY dr.id DESC LIMIT 200"
)->fetchAll();

$editRequests = $pdo->query(
  "SELECT er.id, er.grading_sheet_id, er.professor_id, er.reason, er.status, er.decided_by, er.decided_at,
          p.first_name, p.middle_name, p.last_name
     FROM edit_requests er
LEFT JOIN professors p ON p.id = er.professor_id
 ORDER BY er.id DESC LIMIT 100"
)->fetchAll();

$search = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';

$docFiltered = array_values(array_filter($docRequests, static function ($r) use ($search, $statusFilter) {
  $matchesStatus = ($statusFilter === 'all') || ($r['status'] === $statusFilter);
  if (!$matchesStatus) {
    return false;
  }
  if ($search === '') {
    return true;
  }
  $haystack = strtolower(
    ($r['student_id'] ?? '') . ' ' .
    ($r['first_name'] ?? '') . ' ' .
    ($r['middle_name'] ?? '') . ' ' .
    ($r['last_name'] ?? '') . ' ' .
    ($r['id'] ?? '')
  );
  return strpos($haystack, strtolower($search)) !== false;
}));

$counts = [
  'pendingDocs' => 0,
  'scheduledDocs' => 0,
  'pendingProf' => 0,
];
foreach ($docRequests as $r) {
  if (($r['status'] ?? '') === 'pending') {
    $counts['pendingDocs']++;
  }
  if (($r['status'] ?? '') === 'scheduled') {
    $counts['scheduledDocs']++;
  }
}
foreach ($editRequests as $r) {
  if (($r['status'] ?? '') === 'pending') {
    $counts['pendingProf']++;
  }
}
?>

<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Requests</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
      :root {
        --accent: #4b7250;
        --warn: #c06500;
        --blue: #1d4ed8;
        --green: #15803d;
        --border: #e5e7eb;
        --panel: #ffffff;
        --muted: #4b5563;
      }
      .requests-hero { display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px; }
      .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px; margin-bottom: 14px; }
      .stat-card { background: #fff7e6; border: 1px solid #f5deb5; border-radius: 12px; padding: 14px; display: flex; justify-content: space-between; align-items: center; }
      .stat-card.blue { background: #eef4ff; border-color: #d8e4ff; }
      .stat-card.red { background: #ffecec; border-color: #f8c7c7; }
      .stat-value { font-size: 28px; font-weight: 700; color: #111827; }
      .tabs { display: flex; gap: 12px; margin: 14px 0 6px; }
      .tab-btn { background: none; border: none; padding: 10px 12px; border-radius: 10px; cursor: pointer; font-weight: 700; color: #1f2937; }
      .tab-btn.active { color: var(--accent); border-bottom: 3px solid var(--accent); }
      .filters-row { display: grid; grid-template-columns: 1fr auto; gap: 12px; align-items: center; margin: 12px 0; }
      .search-box { position: relative; }
      .search-box input { width: 100%; border: 1px solid var(--border); border-radius: 10px; padding: 10px 12px; }
      .status-filter { min-width: 160px; border: 1px solid var(--border); border-radius: 10px; padding: 10px 12px; }
      .requests-table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid var(--border); border-radius: 14px; overflow: hidden; }
      .requests-table th, .requests-table td { padding: 12px; border-bottom: 1px solid var(--border); text-align: left; font-size: 14px; }
      .requests-table thead th { background: #f8fafc; font-weight: 700; }
      .status-chip { font-weight: 700; }
      .status-chip.pending { color: #dc2626; }
      .status-chip.scheduled { color: var(--blue); }
      .status-chip.ready { color: var(--green); }
      .status-chip.released { color: #0f5132; }
      .action-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
      .btn-pill { padding: 8px 12px; border-radius: 10px; border: 1px solid transparent; font-weight: 700; cursor: pointer; }
      .btn-pill.primary { background: var(--accent); color: #fff; }
      .btn-pill.ghost { background: #fff; border-color: var(--accent); color: var(--accent); }
      .btn-pill.neutral { background: #fff; border-color: #d1d5db; color: #111827; }
      .table-card { border: 1px solid var(--border); border-radius: 14px; padding: 14px; background: var(--panel); box-shadow: 0 10px 30px rgba(0,0,0,0.04); }
      @media (max-width: 900px) {
        .controls-row, .filters-row { grid-template-columns: 1fr; }
      }
    </style>
  </head>
  <body>
    <?php include '../includes/header.php'; ?>
    <div class="layout">
      <?php include '../includes/sidebar.php'; ?>
      <main class="content">
        <?php show_flash(); ?>
        <?php if ($err): ?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>
        <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

        <div class="page-header">
          <h1>Requests Management</h1>
          <p class="text-muted">Manage student document requests and professor grading sheet re-opening requests</p>
        </div>

        <div class="stat-grid">
          <div class="stat-card">
            <div>
              <div style="color:#b7791f;font-weight:600;">Student Requests (Pending)</div>
              <div class="stat-value"><?= (int)$counts['pendingDocs']; ?></div>
            </div>
            <span class="material-symbols-rounded" style="font-size:32px;color:#b7791f;">person</span>
          </div>
          <div class="stat-card blue">
            <div>
              <div style="color:#1d4ed8;font-weight:600;">Scheduled Pickups</div>
              <div class="stat-value"><?= (int)$counts['scheduledDocs']; ?></div>
            </div>
            <span class="material-symbols-rounded" style="font-size:32px;color:#1d4ed8;">event</span>
          </div>
          <div class="stat-card red">
            <div>
              <div style="color:#b42318;font-weight:600;">Professor Requests (Pending)</div>
              <div class="stat-value"><?= (int)$counts['pendingProf']; ?></div>
            </div>
            <span class="material-symbols-rounded" style="font-size:32px;color:#b42318;">error</span>
          </div>
        </div>

        <div class="table-card">
          <div class="tabs">
            <button class="tab-btn active" data-tab="student">Student Document Requests</button>
            <button class="tab-btn" data-tab="prof">Professor Re-opening Requests</button>
          </div>

          <form class="filters-row" method="get" action="">
            <div class="search-box">
              <input type="text" name="q" value="<?= htmlspecialchars($search); ?>" placeholder="Search by student ID or name...">
            </div>
            <div>
              <select class="status-filter" name="status" onchange="this.form.submit()">
                <?php foreach (['all' => 'All', 'pending' => 'Pending', 'scheduled' => 'Scheduled', 'ready' => 'Ready', 'released' => 'Released', 'completed' => 'Completed'] as $val => $label): ?>
                  <option value="<?= $val; ?>" <?= $statusFilter === $val ? 'selected' : ''; ?>><?= $label; ?></option>
                <?php endforeach; ?>
              </select>
              <?php if ($search): ?><input type="hidden" name="q" value="<?= htmlspecialchars($search); ?>"><?php endif; ?>
            </div>
          </form>

          <div id="tab-student" class="tab-pane" style="display:block;">
            <table class="requests-table">
              <thead>
                <tr>
                  <th>Request ID</th>
                  <th>Student ID</th>
                  <th>Student Name</th>
                  <th>Course/Year</th>
                  <th>Document Type</th>
                  <th>Request Date</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$docFiltered): ?>
                  <tr><td colspan="8">No document requests.</td></tr>
                <?php else: foreach ($docFiltered as $r): ?>
                  <?php
                    $fullName = trim(($r['last_name'] ?? '').', '.($r['first_name'] ?? '').' '.($r['middle_name'] ?? ''));
                    $courseYear = trim($r['year_level'] ?? '');
                    $statusClass = 'pending';
                    if ($r['status'] === 'scheduled') { $statusClass = 'scheduled'; }
                    elseif ($r['status'] === 'ready') { $statusClass = 'ready'; }
                    elseif ($r['status'] === 'released' || $r['status'] === 'completed') { $statusClass = 'released'; }
                    $requestDate = !empty($r['request_date']) ? date('Y-m-d', strtotime($r['request_date'])) : '—';
                  ?>
                  <tr>
                    <td><?= 'REQ-STU-'.str_pad((string)$r['id'], 3, '0', STR_PAD_LEFT); ?></td>
                    <td><?= htmlspecialchars($r['student_id'] ?? ''); ?></td>
                    <td><?= htmlspecialchars($fullName ?: ''); ?></td>
                  <td><?= htmlspecialchars($courseYear ?: ''); ?></td>
                    <td><?= htmlspecialchars($r['type']); ?></td>
                    <td><?= htmlspecialchars($requestDate); ?></td>
                    <td><span class="status-chip <?= $statusClass; ?>"><?= ucfirst($r['status']); ?></span></td>
                    <td class="actions">
                      <div class="action-buttons">
                        <?php if ($r['status'] === 'pending'): ?>
                          <form method="post">
                            <input type="hidden" name="action" value="docreq_update">
                            <input type="hidden" name="id" value="<?= (int)$r['id']; ?>">
                            <input type="hidden" name="status" value="scheduled">
                            <input type="hidden" name="scheduled_at" value="">
                            <input type="hidden" name="released_at" value="">
                            <button type="submit" class="btn-pill primary">Set Pickup</button>
                          </form>
                        <?php elseif ($r['status'] === 'scheduled' || $r['status'] === 'ready'): ?>
                          <span class="btn-pill neutral">View Schedule</span>
                          <form method="post">
                            <input type="hidden" name="action" value="docreq_update">
                            <input type="hidden" name="id" value="<?= (int)$r['id']; ?>">
                            <input type="hidden" name="status" value="completed">
                            <input type="hidden" name="scheduled_at" value="<?= $r['scheduled_at'] ? htmlspecialchars($r['scheduled_at']) : ''; ?>">
                            <input type="hidden" name="released_at" value="<?= htmlspecialchars(date('Y-m-d\TH:i')); ?>">
                            <button type="submit" class="btn-pill primary" style="background:#16a34a;border-color:#16a34a;">Complete</button>
                          </form>
                        <?php else: ?>
                          <span class="btn-pill neutral">View Details</span>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>

          <div id="tab-prof" class="tab-pane" style="display:none;">
            <table class="requests-table">
              <thead>
                <tr>
                  <th>Request ID</th>
                  <th>Professor</th>
                  <th>Grading Sheet</th>
                  <th>Reason</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$editRequests): ?>
                  <tr><td colspan="6">No edit requests.</td></tr>
                <?php else: foreach ($editRequests as $r): ?>
                  <?php $profName = trim(($r['last_name'] ?? '').', '.($r['first_name'] ?? '').' '.($r['middle_name'] ?? '')); ?>
                  <tr>
                    <td><?= 'REQ-PROF-'.str_pad((string)$r['id'], 3, '0', STR_PAD_LEFT); ?></td>
                    <td><?= htmlspecialchars($profName); ?></td>
                    <td>#<?= htmlspecialchars($r['grading_sheet_id']); ?></td>
                    <td><?= htmlspecialchars($r['reason']); ?></td>
                    <td><span class="status-chip <?= $r['status']==='pending'?'pending':'ready'; ?>"><?= ucfirst($r['status']); ?></span></td>
                    <td>
                      <?php if ($r['status']==='pending'): ?>
                        <div class="action-buttons">
                          <form method="post">
                            <input type="hidden" name="action" value="editreq_decide">
                            <input type="hidden" name="id" value="<?= (int)$r['id']; ?>">
                            <button type="submit" name="decision" value="approved" class="btn-pill primary">Approve</button>
                            <button type="submit" name="decision" value="denied" class="btn-pill ghost">Deny</button>
                          </form>
                        </div>
                      <?php else: ?>
                        <span class="status-chip ready"><?= ucfirst($r['status']); ?></span>
                        <?php if (!empty($r['decided_at'])): ?> on <?= htmlspecialchars($r['decided_at']); ?><?php endif; ?>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <script>
          document.querySelectorAll('.tab-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
              document.querySelectorAll('.tab-btn').forEach(function(b){ b.classList.remove('active'); });
              document.querySelectorAll('.tab-pane').forEach(function(p){ p.style.display = 'none'; });
              btn.classList.add('active');
              var tab = btn.dataset.tab;
              var pane = document.getElementById('tab-' + tab);
              if (pane) pane.style.display = 'block';
            });
          });
        </script>
        <script src="../assets/js/admin.js"></script>
      </main>
    </div>
  </body>
  <?php include '../includes/footer.php'; ?>
</html>
