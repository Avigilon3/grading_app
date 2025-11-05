
<?php
require_once '../includes/init.php';
requireAdmin();


// add later siguro wag na? check if user is MIS or Registrar
// if (!is_mis($_SESSION['admin']['role'])) { die('Unauthorized'); }

$stmt = $pdo->query("SELECT * FROM students ORDER BY last_name, first_name");
$result = $stmt->fetchAll();
?>
<!doctype html><html><head>
  <meta charset="utf-8"><title>Database Management - Students</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="layout">
  <?php include '../includes/sidebar.php'; ?>
  <main class="content">
    <?php show_flash(); ?>


    <div class="page-header">
      <h2>Manage Student Information</h2>
    </div>

<!-- Feedback (optional) -->
<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($_GET['msg']) ?>
    </div>
<?php endif; ?>

<!-- Tabs: Add / Edit Student -->
<div class="card" id="student-tabs">
  <div class="card-header tabs">
    <button type="button" class="tab-link active" data-tab="add">Add Student</button>
    <button type="button" class="tab-link" data-tab="edit">Edit Student</button>
  </div>

  <div class="card-body">
    <div class="tab-pane active" data-pane="add">
      <form action="../includes/student_process.php" method="POST">
        <input type="hidden" name="action" value="create">
        <div class="form-box">
          <div class="row-grid cols-2">
            <div class="form-group">
              <label>Student ID *</label>
              <input type="text" name="student_id" class="form-control" required>
            </div>
            <div class="form-group">
              <label>PTC Email *</label>
              <input type="email" name="ptc_email" class="form-control" required>
            </div>
          </div>
          <div class="row-grid cols-3">
            <div class="form-group">
              <label>First Name *</label>
              <input type="text" name="first_name" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Middle Name</label>
              <input type="text" name="middle_name" class="form-control">
            </div>
            <div class="form-group">
              <label>Last Name *</label>
              <input type="text" name="last_name" class="form-control" required>
            </div>
          </div>
          <div class="row-grid cols-3">
            <div class="form-group">
              <label>Year Level</label>
              <select name="year_level" class="form-control">
                <option value="">-- Select --</option>
                <option value="1">1st Year</option>
                <option value="2">2nd Year</option>
                <option value="3">3rd Year</option>
                <option value="4">4th Year</option>
              </select>
            </div>
            <div class="form-group">
              <label>Section</label>
              <input type="text" name="section" class="form-control">
            </div>
            <div class="form-group">
              <label>Status *</label>
              <select name="status" class="form-control" required>
                <option value="Regular">Regular</option>
                <option value="Irregular">Irregular</option>
                <option value="Inactive">Inactive</option>
              </select>
            </div>
          </div>
            <div class="form-actions">
                <button type="submit">Save Student</button>
            </div>
        </div>
      </form>
    </div>


    <div class="tab-pane" data-pane="edit">
      <form action="../includes/student_process.php" method="POST">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" id="edit-id">
        <div class="form-box">
          <div class="row-grid cols-2">
            <div class="form-group">
              <label>Student ID *</label>
              <input type="text" name="student_id" id="edit-student_id" class="form-control" required>
            </div>
            <div class="form-group">
              <label>PTC Email *</label>
              <input type="email" name="ptc_email" id="edit-ptc_email" class="form-control" required>
            </div>
          </div>
          <div class="row-grid cols-3">
            <div class="form-group">
              <label>First Name *</label>
              <input type="text" name="first_name" id="edit-first_name" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Middle Name</label>
              <input type="text" name="middle_name" id="edit-middle_name" class="form-control">
            </div>
            <div class="form-group">
              <label>Last Name *</label>
              <input type="text" name="last_name" id="edit-last_name" class="form-control" required>
            </div>
          </div>
          <div class="row-grid cols-3">
            <div class="form-group">
              <label>Year Level</label>
              <select name="year_level" id="edit-year_level" class="form-control">
                <option value="">-- Select --</option>
                <option value="1">1st Year</option>
                <option value="2">2nd Year</option>
                <option value="3">3rd Year</option>
                <option value="4">4th Year</option>
              </select>
            </div>
            <div class="form-group">
              <label>Section</label>
              <input type="text" name="section" id="edit-section" class="form-control">
            </div>
            <div class="form-group">
              <label>Status *</label>
              <select name="status" id="edit-status" class="form-control" required>
                <option value="Regular">Regular</option>
                <option value="Irregular">Irregular</option>
                <option value="Inactive">Inactive</option>
              </select>
            </div>
          </div>
            <div class="form-actions">
                <button type="submit">Update Student</button>
            </div>
        </div>
      </form>
    </div>
  </div>
</div>

    <div class="page-header">
      <h2>Student Information Table</h2>
    </div>

<div class="page-header"
    <h1> Student Information Table</h1>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student ID</th>
                    <th>PTC Email</th>
                    <th>Name</th>
                    <th>Year Level</th>
                    <th>Section</th>
                    <th>Status</th>
                    <th width="140">Actions</th>
                </tr>
            </thead>
           <tbody>
                <?php
                $i = 1;
                foreach ($result as $row):
                ?>
                <tr>
                    <td><?= $i++; ?></td>
                    <td><?= htmlspecialchars($row['student_id']); ?></td>
                    <td><?= htmlspecialchars($row['ptc_email']); ?></td>
                    <td><?= htmlspecialchars($row['last_name'] . ', ' . $row['first_name'] . ' ' . $row['middle_name']); ?></td>
                    <td>
                        <?php
                        $yl = $row['year_level'];
                        $labels = ['1' => '1st Year', '2' => '2nd Year', '3' => '3rd Year', '4' => '4th Year'];
                        echo htmlspecialchars($labels[$yl] ?? $yl);
                        ?>
                    </td>
                    <td><?= htmlspecialchars($row['section']); ?></td>
                    <td><?= htmlspecialchars($row['status']); ?></td>
                    <td class="actions">
                        <button 
                            class="btn btn-sm btn-warning btn-edit"
                            data-id="<?= $row['id']; ?>"
                            data-student_id="<?= htmlspecialchars($row['student_id']); ?>"
                            data-ptc_email="<?= htmlspecialchars($row['ptc_email']); ?>"
                            data-first_name="<?= htmlspecialchars($row['first_name']); ?>"
                            data-middle_name="<?= htmlspecialchars($row['middle_name']); ?>"
                            data-last_name="<?= htmlspecialchars($row['last_name']); ?>"
                            data-year_level="<?= htmlspecialchars($row['year_level']); ?>"
                            data-section="<?= htmlspecialchars($row['section']); ?>"
                            data-status="<?= htmlspecialchars($row['status']); ?>"
                        >Edit</button>

                        <form action="../includes/student_process.php" method="POST" style="display:inline-block" onsubmit="return confirm('Delete this student?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $row['id']; ?>">
                            <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>


<?php if (function_exists('renderCrudTabsScript')) { renderCrudTabsScript(); } ?>
<script src="../assets/js/admin.js"></script>

  </main>
<?php include '../includes/footer.php'; ?>
</body>
</html>
