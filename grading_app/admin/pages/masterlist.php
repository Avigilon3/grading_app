<?php
require_once '../includes/init.php';
requireAdmin();

$yearLabels = [
    '1' => '1st Year',
    '2' => '2nd Year',
    '3' => '3rd Year',
    '4' => '4th Year'
];

$semesterLabels = [
    '1' => '1st Semester',
    '2' => '2nd Semester'
];

$sectionsStmt = $pdo->query("SELECT s.id,
                                    s.section_name,
                                    s.year_level,
                                    s.course_id,
                                    c.code AS course_code,
                                    c.title AS course_title,
                                    t.term_name,
                                    t.semester
                               FROM sections s
                          LEFT JOIN courses c ON c.id = s.course_id
                          LEFT JOIN terms t ON t.id = s.term_id
                           ORDER BY s.section_name");
$sections = $sectionsStmt->fetchAll(PDO::FETCH_ASSOC);

$sectionId = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;
$selectedSection = null;
$subjectsBySemester = [
    '1' => [],
    '2' => []
];
$subjectUnitTotals = [
    '1' => 0,
    '2' => 0
];
$masterlistStudents = [];

if ($sectionId) {
    $secStmt = $pdo->prepare("SELECT s.*,
                                     c.code AS course_code,
                                     c.title AS course_title,
                                     t.term_name,
                                     t.semester
                                FROM sections s
                           LEFT JOIN courses c ON c.id = s.course_id
                           LEFT JOIN terms t ON t.id = s.term_id
                               WHERE s.id = ?");
    $secStmt->execute([$sectionId]);
    $selectedSection = $secStmt->fetch(PDO::FETCH_ASSOC);

    if ($selectedSection && $selectedSection['course_id']) {
        $subjectStmt = $pdo->prepare("SELECT sub.subject_code,
                                             sub.subject_title,
                                             COALESCE(sub.units, 0) AS units,
                                             t.semester,
                                             t.term_name
                                        FROM subjects sub
                                   LEFT JOIN terms t ON t.id = sub.term_id
                                       WHERE sub.course_id = :course_id
                                         AND sub.year_level = :year_level
                                    ORDER BY CASE
                                               WHEN t.semester = '1' THEN 1
                                               WHEN t.semester = '2' THEN 2
                                               ELSE 3
                                             END,
                                             sub.subject_title");
        $subjectStmt->execute([
            ':course_id' => $selectedSection['course_id'],
            ':year_level' => $selectedSection['year_level']
        ]);

        while ($row = $subjectStmt->fetch(PDO::FETCH_ASSOC)) {
            $semKey = $row['semester'] ?? ($selectedSection['semester'] ?? '1');
            if (!isset($subjectsBySemester[$semKey])) {
                $subjectsBySemester[$semKey] = [];
                $subjectUnitTotals[$semKey] = 0;
            }
            $subjectsBySemester[$semKey][] = $row;
            $subjectUnitTotals[$semKey] += (float)$row['units'];
        }
    }

    if ($selectedSection) {
        $masterlistStmt = $pdo->prepare("SELECT DISTINCT st.id AS student_pk,
                                                    st.student_id AS student_code,
                                                    st.first_name,
                                                    st.middle_name,
                                                    st.last_name,
                                                    st.year_level,
                                                    st.status,
                                                    CASE WHEN ss.id IS NULL THEN 0 ELSE 1 END AS has_section_link
                                              FROM students st
                                         LEFT JOIN section_students ss
                                                ON ss.student_id = st.id
                                               AND ss.section_id = :section_id
                                             WHERE ss.section_id = :section_id
                                                OR st.section = :section_name
                                          ORDER BY st.last_name, st.first_name, st.student_id");
        $masterlistStmt->execute([
            ':section_id' => $sectionId,
            ':section_name' => $selectedSection['section_name']
        ]);
        $masterlistStudents = $masterlistStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Section Masterlist</title>
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
        <div class="card-section-header">
          <div>
            <h3>Select Section</h3>
            <p class="text-muted">Choose a section to load its assigned subjects & masterlist.</p>
          </div>
        </div>
        <form method="GET" action="./masterlist.php">
          <div class="form-group">
            <label>Section *</label>
            <select name="section_id" class="form-control" onchange="this.form.submit()">
              <option value="">-- Choose Section --</option>
              <?php foreach ($sections as $s): ?>
                <option value="<?= (int)$s['id']; ?>" <?= ($sectionId === (int)$s['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($s['section_name'] ?? ''); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </form>

        <!-- display details card -->
        <?php if ($selectedSection): ?>
          <div class="section-meta-grid">
            <div class="meta-item">
              <span class="meta-label">Section Name</span>
              <strong><?= htmlspecialchars($selectedSection['section_name']); ?></strong>
            </div>
            <div class="meta-item">
              <span class="meta-label">Course</span>
              <strong><?= htmlspecialchars($selectedSection['course_title']);?></strong>
            </div>
            <div class="meta-item">
              <span class="meta-label">Year Level</span>
              <strong><?= htmlspecialchars(formatYearLabel($selectedSection['year_level'], $yearLabels)); ?></strong>
            </div>
            <div class="meta-item">
              <span class="meta-label">Semester</span>
              <strong>
                <?php
                  $semesterText = formatSemesterLabel($selectedSection['semester'] ?? null, $semesterLabels);
                  if (!empty($selectedSection['term_name'])) {
                      $semesterText .= ' • ' . $selectedSection['term_name'];
                  }
                  echo htmlspecialchars($semesterText);
                ?>
              </strong>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!$sectionId || !$selectedSection): ?>
      <div class="alert alert-info">Please select a section to manage its masterlist.</div>
    <?php else: ?>

      <!-- enroll student in a section -->
    <div class="card">
      <div class="card-body">
        <div class="card-section-header">
          <div>
            <h3>Enroll Student to Section</h3>
            <p class="text-muted">Search for a student ID to auto-fill their details, then add them to the current masterlist.</p>
          </div>
        </div>
        <form action="../includes/masterlist_process.php" method="POST" id="add-student-form">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="section_id" value="<?= (int)$sectionId; ?>">
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
        </form>
      </div>
    </div>

    <!-- subjectss -->
    <?php foreach (['1', '2'] as $semKey): ?>
      <div class="card">
        <div class="card-body">
          <div class="card-section-header">
            <div>
              <h3 class="subject-card-title">
                Subjects for <?= htmlspecialchars($selectedSection['section_name']); ?> - <?= htmlspecialchars($semesterLabels[$semKey] ?? ucfirst($semKey)); ?>
              </h3>
              <p class="text-muted">Course outline based on <?= htmlspecialchars($semesterLabels[$semKey] ?? ucfirst($semKey)); ?>.</p>
            </div>
            <div class="total-pill">Total Units: <?= number_format($subjectUnitTotals[$semKey] ?? 0, 1); ?></div>
          </div>

          <?php if (!empty($subjectsBySemester[$semKey])): ?>
            <div class="table-responsive">
              <table class="table table-striped table-bordered">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Subject Code</th>
                    <th>Subject Title</th>
                    <th>Units</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($subjectsBySemester[$semKey] as $index => $subject): ?>
                    <tr>
                      <td><?= $index + 1; ?></td>
                      <td><?= htmlspecialchars($subject['subject_code'] ?? ''); ?></td>
                      <td><?= htmlspecialchars($subject['subject_title'] ?? ''); ?></td>
                      <td><?= htmlspecialchars(number_format((float)$subject['units'], 1)); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <p class="text-muted">No subjects found for this semester.</p>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>

    <div class="card">
      <div class="card-body">
        <div class="card-section-header">
          <div>
            <h3>Current Masterlist - <?= htmlspecialchars($selectedSection['section_name']); ?></h3>
            <p class="text-muted"><?= count($masterlistStudents); ?> student(s) currently linked to this section.</p>
          </div>
        </div>
        <div class="search-input">
          <input type="search" id="masterlist-search" placeholder="Search students by ID or name...">
        </div>
        <div class="table-responsive">
          <table class="table table-striped table-bordered">
            <thead>
              <tr>
                <th>#</th>
                <th>Student ID</th>
                <th>Name</th>
                <th>Year Level</th>
                <th>Status</th>
                <th width="140">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$masterlistStudents): ?>
                <tr><td colspan="6" style="text-align:center">No students assigned to this section yet.</td></tr>
              <?php else: ?>
                <?php foreach ($masterlistStudents as $idx => $row): ?>
                  <?php
                    $fullName = trim(($row['last_name'] ?? '') . ', ' . ($row['first_name'] ?? '') . ' ' . ($row['middle_name'] ?? ''));
                    $searchIndex = buildStudentSearchIndex($row);
                  ?>
                  <tr data-masterlist-row data-search="<?= htmlspecialchars($searchIndex, ENT_QUOTES); ?>">
                    <td><?= $idx + 1; ?></td>
                    <td><?= htmlspecialchars($row['student_code']); ?></td>
                    <td><?= htmlspecialchars($fullName); ?></td>
                    <td><?= htmlspecialchars(formatYearLabel($row['year_level'] ?? '', $yearLabels)); ?></td>
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
                <?php endforeach; ?>
                <tr id="masterlist-empty" style="display:none;">
                  <td colspan="6" style="text-align:center">No students match your search.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <?php endif; ?>

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

    (function(){
      const searchInput = document.getElementById('masterlist-search');
      if (!searchInput) return;
      const rows = Array.from(document.querySelectorAll('[data-masterlist-row]'));
      const emptyRow = document.getElementById('masterlist-empty');

      function applyFilter() {
        const q = searchInput.value.trim().toLowerCase();
        let visible = 0;
        rows.forEach(row => {
          const haystack = row.getAttribute('data-search') || row.textContent.toLowerCase();
          const isMatch = !q || (haystack && haystack.indexOf(q) !== -1);
          row.style.display = isMatch ? '' : 'none';
          if (isMatch) visible++;
        });
        if (emptyRow) {
          emptyRow.style.display = visible ? 'none' : '';
        }
      }

      searchInput.addEventListener('input', applyFilter);
    })();
    </script>
    <script src="../assets/js/admin.js"></script>
  </main>
  <?php include '../includes/footer.php'; ?>
</div>
</body>
</html>
