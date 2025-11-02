<?php
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

// Filters
$filterAdminId = isset($_GET['admin_id']) && $_GET['admin_id'] !== '' ? (int)$_GET['admin_id'] : null;
$filterAction  = isset($_GET['action_name']) && $_GET['action_name'] !== '' ? trim($_GET['action_name']) : null;
$filterDate    = isset($_GET['date']) && $_GET['date'] !== '' ? trim($_GET['date']) : null; // YYYY-MM-DD

// Lookup: admins list (by role)
$adminsStmt = $pdo->query("SELECT id, first_name, last_name, email, role FROM users WHERE role IN ('admin','registrar','mis','super_admin') ORDER BY last_name, first_name");
$admins = $adminsStmt->fetchAll();

// Lookup: distinct actions
$actionsStmt = $pdo->query("SELECT DISTINCT action FROM activity_logs ORDER BY action");
$actions = $actionsStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

// Build query
$where = [];
$params = [];
if ($filterAdminId) { $where[] = 'a.user_id = ?'; $params[] = $filterAdminId; }
if ($filterAction)  { $where[] = 'a.action = ?';  $params[] = $filterAction; }
if ($filterDate) {
    // match day range
    $start = $filterDate . ' 00:00:00';
    $end   = $filterDate . ' 23:59:59';
    $where[] = 'a.created_at BETWEEN ? AND ?';
    $params[] = $start;
    $params[] = $end;
}
$sql = "SELECT a.*, u.first_name, u.last_name, u.email
          FROM activity_logs a
     LEFT JOIN users u ON u.id = a.user_id";
if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY a.created_at DESC LIMIT 500';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();
?>
<!doctype html><html><head>
  <meta charset="utf-8"><title>Activity Logs</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include __DIR__.'/../includes/header.php'; ?>
<div class="layout">
  <?php include __DIR__.'/../includes/sidebar.php'; ?>
  <main class="content">
    <?php show_flash(); ?>

    <div class="page-header">
      <h2>Admin Activity Logs</h2>
    </div>

    <div class="card">
      <div class="card-header">Filters</div>
      <div class="card-body">
        <form method="GET" action="./activity_logs.php">
          <div class="row-grid cols-4">
            <div class="form-group">
              <label>Admin</label>
              <select name="admin_id" class="form-control">
                <option value="">-- All Admins --</option>
                <?php foreach ($admins as $a): ?>
                  <?php $name = trim(($a['last_name'] ?? '') . ', ' . ($a['first_name'] ?? '')); ?>
                  <option value="<?= (int)$a['id']; ?>" <?= ($filterAdminId && $filterAdminId == (int)$a['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($name !== ', ' ? $name : ($a['email'] ?? 'User #'.$a['id'])); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Action</label>
              <select name="action_name" class="form-control">
                <option value="">-- All Actions --</option>
                <?php foreach ($actions as $act): ?>
                  <option value="<?= htmlspecialchars($act); ?>" <?= ($filterAction === $act ? 'selected' : ''); ?>><?= htmlspecialchars($act); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Date</label>
              <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($filterDate ?? ''); ?>">
            </div>
            <div class="form-group" style="align-self:end">
              <button type="submit" class="btn">Filter</button>
              <a href="./activity_logs.php" class="btn btn-secondary">Reset</a>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <table class="table table-striped table-bordered">
          <thead>
            <tr>
              <th>#</th>
              <th>Date/Time</th>
              <th>Admin</th>
              <th>Action</th>
              <th>Details</th>
              <th>IP</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!$logs): ?>
            <tr><td colspan="6" style="text-align:center">No activity found.</td></tr>
          <?php else: $i=1; foreach ($logs as $row): ?>
            <tr>
              <td><?= $i++; ?></td>
              <td><?= htmlspecialchars($row['created_at']); ?></td>
              <td>
                <?php
                  $n = trim(($row['last_name'] ?? '') . ', ' . ($row['first_name'] ?? ''));
                  echo htmlspecialchars($n !== ', ' && $n !== '' ? $n : ($row['email'] ?? 'System'));
                ?>
              </td>
              <td><?= htmlspecialchars($row['action']); ?></td>
              <td><?= htmlspecialchars($row['details'] ?? ''); ?></td>
              <td><?= htmlspecialchars($row['ip'] ?? ''); ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <script src="../assets/js/admin.js"></script>
  </main>
  <?php include '../includes/footer.php'; ?>
</div>
</body>
</html>
