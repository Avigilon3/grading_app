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
      $allowed = ['pending','scheduled','ready','released'];
      if (!$id || !in_array($status, $allowed, true)) {
        throw new Exception('Invalid document request update.');
      }

      $stmt = $pdo->prepare('UPDATE document_requests SET status=?, scheduled_at=?, released_at=? WHERE id=?');
      $stmt->execute([$status, ($scheduled_at ?: null), ($released_at ?: null), $id]);

      // When a pickup schedule is set, create a notification for the student.
      if ($status === 'scheduled' && $scheduled_at !== '') {
        try {
          $docStmt = $pdo->prepare('SELECT student_id, type FROM document_requests WHERE id = ? LIMIT 1');
          $docStmt->execute([$id]);
          $doc = $docStmt->fetch(PDO::FETCH_ASSOC);

          if ($doc && !empty($doc['student_id'])) {
            $studentId = (int)$doc['student_id'];
            $userStmt = $pdo->prepare('SELECT user_id FROM students WHERE id = ? LIMIT 1');
            $userStmt->execute([$studentId]);
            $userId = (int)$userStmt->fetchColumn();

            if ($userId > 0) {
              $docType = $doc['type'] ?? 'report';
              if ($docType === 'certificate') {
                $message = 'Your request for a Certification of Grades has been scheduled for pick-up.';
              } else {
                $message = 'Your request for a hard copy of your Report of Grades has been scheduled for pick-up.';
              }

              $notifStmt = $pdo->prepare(
                'INSERT INTO notifications (user_id, type, message, is_read) VALUES (?, ?, ?, 0)'
              );
              $notifStmt->execute([$userId, 'pickup_schedule', $message]);
            }
          }
        } catch (Throwable $e) {
          // Notification failures should not block the update.
        }
      }

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
  "SELECT dr.id, dr.type, dr.purpose, dr.status, dr.created_at, dr.scheduled_at, dr.released_at,
          dr.created_at AS request_date,
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

        <div class="table-card" id="request-tabs">
          <div class="tabs">
            <button type="button" class="tab-link active" data-tab="student">Student Document Requests</button>
            <button type="button" class="tab-link" data-tab="prof">Professor Re-opening Requests</button>
          </div>

          <form class="filters-row" method="get" action="">
            <div class="search-box">
              <input type="text" name="q" value="<?= htmlspecialchars($search); ?>" placeholder="Search by student ID or name...">
            </div>
            <div>
              <select class="status-filter" name="status" onchange="this.form.submit()">
                <?php foreach (['all' => 'All', 'pending' => 'Pending', 'scheduled' => 'Scheduled', 'ready' => 'Ready', 'released' => 'Released'] as $val => $label): ?>
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
                  <th>Pickup Schedule</th>
                  <th>Released Date</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$docFiltered): ?>
                  <tr><td colspan="10">No document requests.</td></tr>
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
                    <td><?= htmlspecialchars(!empty($r['scheduled_at']) ? date('Y-m-d', strtotime($r['scheduled_at'])) : '--'); ?></td>
                    <td><?= htmlspecialchars(!empty($r['released_at']) ? date('Y-m-d', strtotime($r['released_at'])) : '--'); ?></td>
                    <td><span class="status-chip <?= $statusClass; ?>"><?= ucfirst($r['status']); ?></span></td>
                    <td class="actions">
                      <div class="action-buttons">
                        <?php if ($r['status'] === 'pending'): ?>
                          <form method="post">
                            <input type="hidden" name="action" value="docreq_update">
                            <input type="hidden" name="id" value="<?= (int)$r['id']; ?>">
                            <input type="hidden" name="status" value="scheduled">
                            <input type="date" name="scheduled_at" required>
                            <input type="hidden" name="released_at" value="">
                            <button type="submit" class="btn-pill primary">Set Pickup</button>
                          </form>
                        <?php elseif ($r['status'] === 'scheduled' || $r['status'] === 'ready'): ?>
                          <form method="post">
                            <input type="hidden" name="action" value="docreq_update">
                            <input type="hidden" name="id" value="<?= (int)$r['id']; ?>">
                            <input type="hidden" name="status" value="released">
                            <input type="hidden" name="scheduled_at" value="<?= $r['scheduled_at'] ? htmlspecialchars($r['scheduled_at']) : ''; ?>">
                            <input type="hidden" name="released_at" value="<?= htmlspecialchars(date('Y-m-d\TH:i')); ?>">
                            <button type="submit" class="btn-pill primary" style="background:#16a34a;border-color:#16a34a;">Complete</button>
                          </form>
                        <?php else: ?>
                          <!-- <span class="btn-pill neutral">View Details</span> -->
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
          document.querySelectorAll('#request-tabs .tab-link').forEach(function(btn) {
            btn.addEventListener('click', function() {
              document.querySelectorAll('#request-tabs .tab-link').forEach(function(b){ b.classList.remove('active'); });
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
