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
          s.student_id, s.first_name, s.middle_name, s.last_name
     FROM document_requests dr
LEFT JOIN students s ON s.id = dr.student_id
 ORDER BY dr.id DESC LIMIT 50"
)->fetchAll();

$editRequests = $pdo->query(
  "SELECT er.id, er.grading_sheet_id, er.professor_id, er.reason, er.status, er.decided_by, er.decided_at,
          p.first_name, p.middle_name, p.last_name
     FROM edit_requests er
LEFT JOIN professors p ON p.id = er.professor_id
 ORDER BY er.id DESC LIMIT 50"
)->fetchAll();
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
          <h1>Requests</h1>
          <p>Manage student document requests and professor grading sheet re-opening requests</p>
        </div>

        <div class="row-grid cols-1">
          <div class="card">
            <div class="card-body">
              <div class="page-header compact"><h2>Document Requests</h2></div>
              <table class="table table-striped table-bordered">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Type</th>
                    <th>Purpose</th>
                    <th>Status</th>
                    <th>Schedule</th>
                    <th>Released</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!$docRequests): ?>
                    <tr><td colspan="8">No document requests.</td></tr>
                  <?php else: foreach ($docRequests as $r): ?>
                    <tr>
                      <td><?= (int)$r['id']; ?></td>
                      <td><?= htmlspecialchars(trim(($r['last_name'] ?? '').', '.($r['first_name'] ?? '').' '.($r['middle_name'] ?? ''))); ?> (<?= htmlspecialchars($r['student_id'] ?? '') ?>)</td>
                      <td><?= htmlspecialchars($r['type']); ?></td>
                      <td><?= htmlspecialchars($r['purpose']); ?></td>
                      <td><?= htmlspecialchars($r['status']); ?></td>
                      <td><?= htmlspecialchars($r['scheduled_at']); ?></td>
                      <td><?= htmlspecialchars($r['released_at']); ?></td>
                      <td>
                        <form method="post" style="display:inline-block; min-width:240px;">
                          <input type="hidden" name="action" value="docreq_update">
                          <input type="hidden" name="id" value="<?= (int)$r['id']; ?>">
                          <select name="status" class="form-control" style="width:120px; display:inline-block;">
                            <?php foreach (['pending','scheduled','ready','released'] as $st): ?>
                              <option value="<?= $st ?>" <?= $r['status']===$st?'selected':''; ?>><?= ucfirst($st) ?></option>
                            <?php endforeach; ?>
                          </select>
                          <input type="datetime-local" name="scheduled_at" value="<?= $r['scheduled_at'] ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($r['scheduled_at']))) : '' ?>" class="form-control" style="width:170px; display:inline-block;">
                          <input type="datetime-local" name="released_at" value="<?= $r['released_at'] ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($r['released_at']))) : '' ?>" class="form-control" style="width:170px; display:inline-block;">
                          <button type="submit">Save</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="row-grid cols-1">
          <div class="card">
            <div class="card-body">
              <div class="page-header compact"><h2>Grade Edit Requests</h2></div>
              <table class="table table-striped table-bordered">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Professor</th>
                    <th>Grading Sheet</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Decision</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!$editRequests): ?>
                    <tr><td colspan="6">No edit requests.</td></tr>
                  <?php else: foreach ($editRequests as $r): ?>
                    <tr>
                      <td><?= (int)$r['id']; ?></td>
                      <td><?= htmlspecialchars(trim(($r['last_name'] ?? '').', '.($r['first_name'] ?? '').' '.($r['middle_name'] ?? ''))); ?></td>
                      <td>#<?= htmlspecialchars($r['grading_sheet_id']); ?></td>
                      <td><?= htmlspecialchars($r['reason']); ?></td>
                      <td><?= htmlspecialchars($r['status']); ?></td>
                      <td>
                        <?php if ($r['status']==='pending'): ?>
                          <form method="post" style="display:inline-block;">
                            <input type="hidden" name="action" value="editreq_decide">
                            <input type="hidden" name="id" value="<?= (int)$r['id']; ?>">
                            <button type="submit" name="decision" value="approved">Approve</button>
                            <button type="submit" name="decision" value="denied">Deny</button>
                          </form>
                        <?php else: ?>
                          <?= htmlspecialchars($r['status']); ?>
                          <?php if (!empty($r['decided_at'])): ?> on <?= htmlspecialchars($r['decided_at']); ?><?php endif; ?>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <script src="../assets/js/admin.js"></script>
      </main>
    </div>
  </body>
  <?php include '../includes/footer.php'; ?>
</html>
