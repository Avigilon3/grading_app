<?php
require_once '../includes/init.php';
requireAdmin();

$statusLabels = [
    'pending' => 'Pending',
    'scheduled' => 'Scheduled',
    'ready' => 'Ready',
    'released' => 'Released',
];

$filters = [
    'status' => isset($_GET['status']) ? trim($_GET['status']) : '',
    'type' => isset($_GET['type']) ? trim($_GET['type']) : '',
    'search' => isset($_GET['search']) ? trim($_GET['search']) : '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $requestId = (int)($_POST['id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    $pickupDate = trim($_POST['pickup_date'] ?? '');
    $pickupTime = trim($_POST['pickup_time'] ?? '');

    if ($requestId && isset($statusLabels[$newStatus])) {
        $scheduledAt = null;
        $releasedAt = null;

        if ($newStatus === 'scheduled' || $newStatus === 'ready') {
            if ($pickupDate !== '') {
                $dateTime = $pickupDate;
                if ($pickupTime !== '') {
                    $dateTime .= ' ' . $pickupTime;
                }
                $ts = strtotime($dateTime);
                if ($ts) {
                    $scheduledAt = date('Y-m-d H:i:s', $ts);
                }
            }
        } elseif ($newStatus === 'released') {
            $releasedAt = date('Y-m-d H:i:s');
        }

        $update = $pdo->prepare(
            "UPDATE document_requests
                SET status = :status,
                    scheduled_at = :scheduled_at,
                    released_at = CASE WHEN :released_at IS NULL THEN released_at ELSE :released_at END
              WHERE id = :id"
        );
        $update->execute([
            ':status' => $newStatus,
            ':scheduled_at' => $scheduledAt,
            ':released_at' => $releasedAt,
            ':id' => $requestId,
        ]);
        header('Location: requests.php?msg=' . urlencode('Request updated'));
        exit;
    }
}

$where = [];
$params = [];

if ($filters['status'] !== '' && isset($statusLabels[$filters['status']])) {
    $where[] = 'dr.status = :status';
    $params[':status'] = $filters['status'];
}
if ($filters['type'] !== '') {
    $where[] = 'dr.type = :type';
    $params[':type'] = $filters['type'] === 'certificate' ? 'certificate' : 'report';
}
if ($filters['search'] !== '') {
    $where[] = '(s.last_name LIKE :q OR s.first_name LIKE :q OR s.ptc_email LIKE :q)';
    $params[':q'] = '%' . $filters['search'] . '%';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT dr.*,
           s.first_name,
           s.middle_name,
           s.last_name,
           s.ptc_email,
           s.student_id,
           c.code AS course_code,
           c.title AS course_title
      FROM document_requests dr
 LEFT JOIN students s ON s.id = dr.student_id
 LEFT JOIN courses c ON c.id = s.course_id
      $whereSql
  ORDER BY dr.id DESC
");
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalsStmt = $pdo->query("
    SELECT status, COUNT(*) AS total
      FROM document_requests
  GROUP BY status
");
$totals = [
    'pending' => 0,
    'scheduled' => 0,
    'ready' => 0,
    'released' => 0,
];
foreach ($totalsStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $status = $row['status'] ?? 'pending';
    if (isset($totals[$status])) {
        $totals[$status] = (int)$row['total'];
    }
}
$totalAll = array_sum($totals);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Requests Management</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
  <style>
    .req-admin-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin: 16px 0; }
    .req-admin-card { border: 1px solid #e6e9f0; border-radius: 12px; padding: 14px; background: #fff; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08); display: flex; gap: 10px; align-items: center; }
    .req-admin-icon { width: 38px; height: 38px; border-radius: 10px; display: grid; place-items: center; background: #f1f5f9; color: #1f3d5a; }
    .req-admin-card.pending .req-admin-icon { background: #fff7ed; color: #b45309; }
    .req-admin-card.ready .req-admin-icon { background: #ecfdf3; color: #166534; }
    .req-admin-card.released .req-admin-icon { background: #eef2ff; color: #3730a3; }
    .req-admin-label { margin: 0; color: #4b5563; font-size: 13px; }
    .req-admin-value { margin: 2px 0 0; font-size: 22px; font-weight: 800; color: #0f172a; }
    .req-filter { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; align-items: end; margin-bottom: 12px; }
    .req-filter .form-group { margin: 0; }
    .req-table { width: 100%; border-collapse: collapse; }
    .req-table th, .req-table td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; text-align: left; }
    .req-table th { background: #f8fafc; font-weight: 700; color: #0f172a; }
    .req-status { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 10px; font-weight: 700; font-size: 12px; }
    .req-status.pending { background: #fff7ed; color: #b45309; }
    .req-status.scheduled { background: #fff7ed; color: #b45309; }
    .req-status.ready { background: #ecfdf3; color: #166534; }
    .req-status.released { background: #eef2ff; color: #3730a3; }
    .req-actions { display: flex; gap: 6px; flex-wrap: wrap; }
    .req-inline { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
    .req-inline input[type=date], .req-inline input[type=time], .req-inline select { padding: 8px 10px; border-radius: 8px; border: 1px solid #d1d5db; font: inherit; }
    .req-inline button { padding: 9px 12px; border-radius: 8px; border: none; background: #618A61; color: #fff; font-weight: 700; cursor: pointer; }
    .req-small { font-size: 12px; color: #4b5563; }
    @media (max-width: 768px) {
      .req-inline { flex-direction: column; align-items: flex-start; }
    }
  </style>
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="layout">
  <?php include '../includes/sidebar.php'; ?>
  <main class="content">
    <?php show_flash(); ?>
    <div class="page-header">
      <h1>Requests</h1>
      <p class="text-muted">Track and manage document requests submitted by students.</p>
    </div>
    <?php if (isset($_GET['msg'])): ?>
      <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']); ?></div>
    <?php endif; ?>

    <div class="req-admin-grid">
      <div class="req-admin-card">
        <div class="req-admin-icon">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
        </div>
        <div>
          <p class="req-admin-label">Pending</p>
          <p class="req-admin-value"><?= (int)$totals['pending']; ?></p>
        </div>
      </div>
      <div class="req-admin-card scheduled">
        <div class="req-admin-icon">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/><path d="M7 12h10"/></svg>
        </div>
        <div>
          <p class="req-admin-label">Scheduled</p>
          <p class="req-admin-value"><?= (int)$totals['scheduled']; ?></p>
        </div>
      </div>
      <div class="req-admin-card ready">
        <div class="req-admin-icon">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
        </div>
        <div>
          <p class="req-admin-label">Ready</p>
          <p class="req-admin-value"><?= (int)$totals['ready']; ?></p>
        </div>
      </div>
      <div class="req-admin-card released">
        <div class="req-admin-icon">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L19 7"/></svg>
        </div>
        <div>
          <p class="req-admin-label">Released</p>
          <p class="req-admin-value"><?= (int)$totals['released']; ?></p>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <form class="req-filter" method="get">
          <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control">
              <option value="">All statuses</option>
              <?php foreach ($statusLabels as $key => $label): ?>
                <option value="<?= htmlspecialchars($key); ?>" <?= $filters['status'] === $key ? 'selected' : ''; ?>><?= htmlspecialchars($label); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Type</label>
            <select name="type" class="form-control">
              <option value="">All types</option>
              <option value="report" <?= $filters['type'] === 'report' ? 'selected' : ''; ?>>Report of Grades</option>
              <option value="certificate" <?= $filters['type'] === 'certificate' ? 'selected' : ''; ?>>Certificate of Grades</option>
            </select>
          </div>
          <div class="form-group">
            <label>Search student</label>
            <input type="text" name="search" class="form-control" placeholder="Name or PTC email" value="<?= htmlspecialchars($filters['search']); ?>">
          </div>
          <div class="form-group">
            <button type="submit">Filter</button>
          </div>
        </form>

        <table class="req-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Student</th>
              <th>Course</th>
              <th>Type</th>
              <th>Status</th>
              <th>Requested</th>
              <th>Pickup</th>
              <th>Update</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$requests): ?>
              <tr><td colspan="8">No requests found.</td></tr>
            <?php else: $i = 1; foreach ($requests as $row): ?>
              <?php
                $fullName = trim(($row['last_name'] ?? '') . ', ' . ($row['first_name'] ?? '') . ' ' . ($row['middle_name'] ?? ''));
                if ($fullName === ',') {
                    $fullName = 'Student #' . (int)$row['student_id'];
                }
                $typeLabel = ($row['type'] ?? '') === 'certificate' ? 'Certificate of Grades' : 'Report of Grades';
                $requestedText = '';
                if (!empty($row['created_at'])) {
                    $requestedText = date('M j, Y g:i A', strtotime($row['created_at']));
                } elseif (!empty($row['requested_at'])) {
                    $requestedText = date('M j, Y g:i A', strtotime($row['requested_at']));
                }
                $pickupText = 'To be scheduled';
                if (!empty($row['scheduled_at'])) {
                    $ts = strtotime($row['scheduled_at']);
                    if ($ts) {
                        $pickupText = date('M j, Y - g:i A', $ts);
                    }
                } elseif (($row['status'] ?? '') === 'ready') {
                    $pickupText = 'Ready for pickup';
                } elseif (!empty($row['released_at'])) {
                    $pickupText = 'Released ' . date('M j, Y', strtotime($row['released_at']));
                }
                $statusKey = $row['status'] ?? 'pending';
                if (!isset($statusLabels[$statusKey])) {
                    $statusKey = 'pending';
                }
              ?>
              <tr>
                <td><?= $i++; ?></td>
                <td>
                  <div><?= htmlspecialchars($fullName); ?></div>
                  <div class="req-small"><?= htmlspecialchars($row['ptc_email'] ?? ''); ?></div>
                </td>
                <td><?= htmlspecialchars(trim(($row['course_code'] ?? '') . ' ' . ($row['course_title'] ?? ''))); ?></td>
                <td><?= htmlspecialchars($typeLabel); ?></td>
                <td><span class="req-status <?= $statusKey; ?>"><?= htmlspecialchars($statusLabels[$statusKey]); ?></span></td>
                <td><?= htmlspecialchars($requestedText); ?></td>
                <td><?= htmlspecialchars($pickupText); ?></td>
                <td>
                  <form class="req-inline" method="post">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id" value="<?= (int)$row['id']; ?>">
                    <select name="status" required>
                      <?php foreach ($statusLabels as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key); ?>" <?= $statusKey === $key ? 'selected' : ''; ?>><?= htmlspecialchars($label); ?></option>
                      <?php endforeach; ?>
                    </select>
                    <input type="date" name="pickup_date" value="<?= !empty($row['scheduled_at']) ? htmlspecialchars(date('Y-m-d', strtotime($row['scheduled_at']))) : ''; ?>">
                    <input type="time" name="pickup_time" value="<?= !empty($row['scheduled_at']) ? htmlspecialchars(date('H:i', strtotime($row['scheduled_at']))) : ''; ?>">
                    <button type="submit">Update</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
        <div class="req-small" style="margin-top:8px;"><?= (int)$totalAll; ?> total requests</div>
      </div>
    </div>
  </main>
  <?php include '../includes/footer.php'; ?>
</div>
</body>
</html>
