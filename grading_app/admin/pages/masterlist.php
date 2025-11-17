<?php
require_once '../includes/init.php';
requireAdmin();

$sectionsStmt = $pdo->query("SELECT id, section_name FROM sections ORDER BY section_name");
$sections = $sectionsStmt->fetchAll(PDO::FETCH_ASSOC);

$subjectStmt = $pdo->query("SELECT ss.section_id,
                                   sub.subject_code,
                                   sub.subject_title,
                                   t.term_name
                              FROM section_subjects ss
                         LEFT JOIN subjects sub ON sub.id = ss.subject_id
                         LEFT JOIN terms t ON t.id = ss.term_id
                          ORDER BY sub.subject_code, sub.subject_title");
$subjectRows = $subjectStmt->fetchAll(PDO::FETCH_ASSOC);
$sectionSubjects = [];
$sectionTerms = [];
foreach ($subjectRows as $row) {
    $secId = (int)$row['section_id'];
    if (!isset($sectionSubjects[$secId])) {
        $sectionSubjects[$secId] = [];
    }
    if (!isset($sectionTerms[$secId])) {
        $sectionTerms[$secId] = [];
    }
    $subjectLabel = trim(
        ($row['subject_code'] ? $row['subject_code'] . ' - ' : '') .
        ($row['subject_title'] ?? '')
    );
    if ($subjectLabel !== '') {
        $sectionSubjects[$secId][$subjectLabel] = $subjectLabel;
    }
    if (!empty($row['term_name'])) {
        $sectionTerms[$secId][$row['term_name']] = $row['term_name'];
    }
}

$sectionId = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;
$section = null;
$enrolled = [];
$selectedSubjectLabels = [];
$selectedTermLabels = [];

if ($sectionId) {
    $secStmt = $pdo->prepare("SELECT * FROM sections WHERE id = ?");
    $secStmt->execute([$sectionId]);
    $section = $secStmt->fetch();

    if ($section) {
        $selectedSubjectLabels = array_values($sectionSubjects[$sectionId] ?? []);
        $selectedTermLabels = array_values($sectionTerms[$sectionId] ?? []);

        $enStmt = $pdo->prepare("SELECT ss.student_id AS student_pk,
                                        st.student_id AS student_code,
                                        st.first_name, st.middle_name, st.last_name,
                                        st.year_level, st.status
                                   FROM section_students ss
                                   JOIN students st ON st.id = ss.student_id
                                  WHERE ss.section_id = ?
                               ORDER BY st.last_name, st.first_name");
        $enStmt->execute([$sectionId]);
        $enrolled = $enStmt->fetchAll();
    }
}
?>
<!doctype html><html><head>
  <meta charset="utf-8"><title>Masterlist</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="layout">
<?php include '../includes/sidebar.php'; ?>
  <main class="content">
    <?php show_flash(); ?>

    <div class="page-header">
      <h1>Section Masterlist</h1>
      <p>Manage student enrollment by section and view assigned subjects</p>
    </div>

    <div class="card">
      <div class="card-body">
        <form method="GET" action="./masterlist.php" class="row-grid cols-3">
          <div class="form-group">
            <label>Select Section</label>
            <select name="section_id" class="form-control" onchange="this.form.submit()">
              <option value="">-- Choose Section --</option>
              <?php foreach ($sections as $s): ?>
                <?php
                  $label = trim($s['section_name'] ?? '');
                  $subjectsLabel = '';
                  if (!empty($sectionSubjects[$s['id']])) {
                    $subjectsLabel = implode('; ', array_values($sectionSubjects[$s['id']]));
                  }
                  $termsLabel = '';
                  if (!empty($sectionTerms[$s['id']])) {
                    $termsLabel = implode(', ', array_values($sectionTerms[$s['id']]));
                  }
                  if ($subjectsLabel !== '') {
                    $label .= ($label !== '' ? ' - ' : '') . $subjectsLabel;
                  }
                  if ($termsLabel !== '') {
                    $label .= ' (' . $termsLabel . ')';
                  }
                  $label = trim($label);
                ?>
                <option value="<?= (int)$s['id']; ?>" <?= ($sectionId === (int)$s['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($label); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </form>
      </div>
    </div>

    <?php if (!$sectionId || !$section): ?>
      <div class="alert alert-info">Please select a section to manage its masterlist.</div>
    <?php else: ?>

      <div class="card">
        <div class="card-body">
        <form action="../includes/masterlist_process.php" method="POST" id="add-student-form">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="section_id" value="<?= (int)$sectionId; ?>">
          <div class="form-box">
            <div class="row-grid cols-3">
              <div class="form-group">
                <label>Student ID</label>
                <input type="text" name="student_code" id="student_code" class="form-control" placeholder="Enter Student ID" required>
              </div>
              <div class="form-group">
                <label>Student Name</label>
                <input type="text" id="student_name" class="form-control" placeholder="Auto-filled" disabled>
              </div>
              <div class="form-group">
                <label>Year Level</label>
                <input type="text" id="student_year" class="form-control" placeholder="Auto-filled" disabled>
              </div>
            </div>
            <div class="form-actions">
              <button type="submit">Add to Masterlist</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="page-header">
      <h2>Current Masterlist</h2>
    </div>

    <div class="card">
      <div class="card-body">
        <table class="table table-striped table-bordered">
          <thead>
            <tr>
              <th>#</th>
              <th>Student ID</th>
              <th>Name</th>
              <th>Year Level</th>
              <th>Status</th>
              <th width="120">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!$enrolled): ?>
            <tr><td colspan="6" style="text-align:center">No students in this masterlist yet.</td></tr>
          <?php else: $i=1; foreach ($enrolled as $row): ?>
            <tr>
              <td><?= $i++; ?></td>
              <td><?= htmlspecialchars($row['student_code']); ?></td>
              <td><?= htmlspecialchars(trim(($row['last_name'] ?? '') . ', ' . ($row['first_name'] ?? '') . ' ' . ($row['middle_name'] ?? ''))); ?></td>
              <td><?php $yl=$row['year_level']??''; $labels=['1'=>'1st Year','2'=>'2nd Year','3'=>'3rd Year','4'=>'4th Year']; echo htmlspecialchars($labels[$yl] ?? $yl); ?></td>
              <td><?= htmlspecialchars($row['status'] ?? ''); ?></td>
              <td class="actions">
                <form action="../includes/masterlist_process.php" method="POST" onsubmit="return confirm('Remove this student from the section?');">
                  <input type="hidden" name="action" value="remove">
                  <input type="hidden" name="section_id" value="<?= (int)$sectionId; ?>">
                  <input type="hidden" name="student_pk" value="<?= (int)$row['student_pk']; ?>">
                  <button class="btn btn-sm btn-danger" type="submit">Remove</button>
                </form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php endif; // section selected ?>

    <script>
    (function(){
      const codeEl = document.getElementById('student_code');
      const nameEl = document.getElementById('student_name');
      const yearEl = document.getElementById('student_year');
      if (!codeEl) return;

      let typingTimer;
      function lookup(){
        const code = codeEl.value.trim();
        if (!code) { nameEl.value=''; yearEl.value=''; return; }
        fetch('../includes/masterlist_process.php?action=lookup_student&student_code=' + encodeURIComponent(code))
          .then(r => r.ok ? r.json() : null)
          .then(d => {
            if (!d || !d.success) { nameEl.value='Not found'; yearEl.value=''; return; }
            nameEl.value = d.data.last_name + ', ' + d.data.first_name + (d.data.middle_name?(' ' + d.data.middle_name):'');
            yearEl.value = ({'1':'1st Year','2':'2nd Year','3':'3rd Year','4':'4th Year'})[d.data.year_level] || d.data.year_level || '';
          })
          .catch(() => { /* ignore */ });
      }
      codeEl.addEventListener('input', function(){
        clearTimeout(typingTimer);
        typingTimer = setTimeout(lookup, 300);
      });
    })();
    </script>
    <script src="../assets/js/admin.js"></script>
  </main>
  <?php include '../includes/footer.php'; ?>
</div>
</body>
</html>
