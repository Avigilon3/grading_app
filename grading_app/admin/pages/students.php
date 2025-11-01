
<?php
// require_once '../../core/db/connection.php';
// require_once '../../core/auth/session.php';
// require_once '../../core/config/functions.php';
require_once __DIR__ . '/../includes/init.php';
requireAdminLogin();


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
<?php include __DIR__.'/../includes/header.php'; ?>
<div class="layout">
  <?php include __DIR__.'/../includes/sidebar.php'; ?>
  <main class="content">
    <?php show_flash(); ?>


<div class="page-header">
    <h2>Students</h2>
    <button class="btn btn-primary" data-toggle="modal" data-target="#addStudentModal">Add Student</button>
</div>

<!-- Feedback (optional) -->
<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($_GET['msg']) ?>
    </div>
<?php endif; ?>

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
                    <th>Section (default)</th>
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
                        // your DB enum is '1','2','3','4' — we can prettify it here
                        $yl = $row['year_level'];
                        $labels = ['1' => '1st Year', '2' => '2nd Year', '3' => '3rd Year', '4' => '4th Year'];
                        echo htmlspecialchars($labels[$yl] ?? $yl);
                        ?>
                    </td>
                    <td><?= htmlspecialchars($row['section']); ?></td>
                    <td><?= htmlspecialchars($row['status']); ?></td>
                    <td>
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
                            data-toggle="modal"
                            data-target="#editStudentModal"
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

<!-- ADD STUDENT MODAL -->
<div class="modal fade" id="addStudentModal" tabindex="-1" role="dialog" aria-labelledby="addStudentModalLabel">
  <div class="modal-dialog" role="document">
    <form action="../includes/student_process.php" method="POST">
        <input type="hidden" name="action" value="create">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title" id="addStudentModalLabel">Add Student</h4>
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
          </div>
          <div class="modal-body">
            <div class="form-group">
                <label>Student ID *</label>
                <input type="text" name="student_id" class="form-control" required>
            </div>
            <div class="form-group">
                <label>PTC Email *</label>
                <input type="email" name="ptc_email" class="form-control" required>
            </div>
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
                <label>Default Section</label>
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
          <div class="modal-footer">
            <button class="btn btn-secondary" data-dismiss="modal" type="button">Close</button>
            <button class="btn btn-primary" type="submit">Save Student</button>
          </div>
        </div>
    </form>
  </div>
</div>

<!-- EDIT STUDENT MODAL -->
<div class="modal fade" id="editStudentModal" tabindex="-1" role="dialog" aria-labelledby="editStudentModalLabel">
  <div class="modal-dialog" role="document">
    <form action="../includes/student_process.php" method="POST">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" id="edit-id">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title" id="editStudentModalLabel">Edit Student</h4>
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
          </div>
          <div class="modal-body">
            <div class="form-group">
                <label>Student ID *</label>
                <input type="text" name="student_id" id="edit-student_id" class="form-control" required>
            </div>
            <div class="form-group">
                <label>PTC Email *</label>
                <input type="email" name="ptc_email" id="edit-ptc_email" class="form-control" required>
            </div>
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
                <label>Default Section</label>
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
          <div class="modal-footer">
            <button class="btn btn-secondary" data-dismiss="modal" type="button">Close</button>
            <button class="btn btn-primary" type="submit">Update Student</button>
          </div>
        </div>
    </form>
  </div>
</div>

<script>
// fill edit modal
document.addEventListener('DOMContentLoaded', function() {
    const editButtons = document.querySelectorAll('.btn-edit');
    editButtons.forEach(function(btn){
        btn.addEventListener('click', function(){
            document.getElementById('edit-id').value = this.dataset.id;
            document.getElementById('edit-student_id').value = this.dataset.student_id;
            document.getElementById('edit-ptc_email').value = this.dataset.ptc_email;
            document.getElementById('edit-first_name').value = this.dataset.first_name;
            document.getElementById('edit-middle_name').value = this.dataset.middle_name;
            document.getElementById('edit-last_name').value = this.dataset.last_name;
            document.getElementById('edit-year_level').value = this.dataset.year_level;
            document.getElementById('edit-section').value = this.dataset.section;
            document.getElementById('edit-status').value = this.dataset.status;
        });
    });
});
</script>
<script src="../assets/js/admin.js"></script>

  </main>
<?php include '../includes/footer.php'; ?>
</body>
</html>