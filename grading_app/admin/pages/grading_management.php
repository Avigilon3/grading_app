<?php
require_once '../includes/init.php';
requireAdmin();

// Handle simple grading sheet admin actions
$err = $msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  try {
    if ($action === 'set_deadline') {
      $id = (int)($_POST['id'] ?? 0);
      $deadline_at = trim($_POST['deadline_at'] ?? '');
      if (!$id) { throw new Exception('Invalid grading sheet.'); }
      $stmt = $pdo->prepare('UPDATE grading_sheets SET deadline_at = ? WHERE id = ?');
      $stmt->execute([$deadline_at ?: null, $id]);
      add_activity_log($pdo, $_SESSION['user']['id'] ?? null, 'SET_DEADLINE', 'Grading sheet #'.$id.' deadline set.');
      $msg = 'Deadline updated.';
    }

    if ($action === 'change_status') {
      $id = (int)($_POST['id'] ?? 0);
      $status = $_POST['status'] ?? '';
      $allowed = ['draft','submitted','locked','reopened'];
      if (!$id || !in_array($status, $allowed, true)) { throw new Exception('Invalid status.'); }
      $stmt = $pdo->prepare('UPDATE grading_sheets SET status = ? WHERE id = ?');
      $stmt->execute([$status, $id]);
      add_activity_log($pdo, $_SESSION['user']['id'] ?? null, 'CHANGE_STATUS', 'Grading sheet #'.$id.' -> '.$status);
      $msg = 'Status updated.';
    }
  } catch (Exception $e) {
    $err = 'Error: '.$e->getMessage();
  }
}

// Load grading sheets (basic info)
$sheets = [];
try {
  $q = $pdo->query(
    "SELECT gs.id, gs.status, gs.deadline_at, gs.submitted_at,
            s.name AS section_name,
            CONCAT(COALESCE(p.last_name,''), ', ', COALESCE(p.first_name,'')) AS professor_name
       FROM grading_sheets gs
  LEFT JOIN sections s   ON s.id = gs.section_id
  LEFT JOIN professors p ON p.id = gs.professor_id
   ORDER BY gs.id DESC LIMIT 100"
  );
  $sheets = $q->fetchAll();
} catch (Exception $e) { /* ignore */ }
?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Grading Management</title>
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
          <h1>Grading Management</h1>
          <p class="text-muted">Manage grading sheet templates, track submissions, and set deadlines</p>
        </div>

        <div class="card">
          <div class="card-body">
            <table class="table table-striped table-bordered">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Section</th>
                  <th>Professor</th>
                  <th>Status</th>
                  <th>Deadline</th>
                  <th>Submitted</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$sheets): ?>
                  <tr><td colspan="7">No grading sheets found.</td></tr>
                <?php else: foreach ($sheets as $r): ?>
                  <tr>
                    <td><?= (int)$r['id']; ?></td>
                    <td><?= htmlspecialchars($r['section_name'] ?? ''); ?></td>
                    <td><?= htmlspecialchars(trim($r['professor_name'] ?? '')); ?></td>
                    <td><?= htmlspecialchars($r['status'] ?? ''); ?></td>
                    <td>
                      <form method="post" style="display:inline-block;">
                        <input type="hidden" name="action" value="set_deadline">
                        <input type="hidden" name="id" value="<?= (int)$r['id']; ?>">
                        <input type="datetime-local" name="deadline_at" value="<?= !empty($r['deadline_at']) ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($r['deadline_at']))) : '' ?>" class="form-control" style="width:200px; display:inline-block;">
                        <button type="submit">Save</button>
                      </form>
                    </td>
                    <td><?= !empty($r['submitted_at']) ? htmlspecialchars($r['submitted_at']) : '—'; ?></td>
                    <td>
                      <form method="post" style="display:inline-block;">
                        <input type="hidden" name="action" value="change_status">
                        <input type="hidden" name="id" value="<?= (int)$r['id']; ?>">
                        <select name="status" class="form-control" style="width:140px; display:inline-block;">
                          <?php foreach (['draft','submitted','locked','reopened'] as $st): ?>
                            <option value="<?= $st ?>" <?= ($r['status']??'')===$st?'selected':''; ?>><?= ucfirst($st) ?></option>
                          <?php endforeach; ?>
                        </select>
                        <button type="submit">Update</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <script src="../assets/js/admin.js"></script>
      </main>
    </div>
  </body>
  <?php include '../includes/footer.php'; ?>
</html>
