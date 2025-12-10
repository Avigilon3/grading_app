<?php
require_once '../includes/init.php';
requireAdmin();

function adminTableExists(PDO $pdo, string $table): bool
{
    try {
        $pdo->query("SELECT 1 FROM {$table} LIMIT 1");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Simple CSV export handler (students, professors, sections, subjects, activity_logs)
function csv_out(array $rows, array $headers, string $filename) {
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="'.$filename.'"');
  $out = fopen('php://output', 'w');
  fputcsv($out, $headers);
  foreach ($rows as $r) {
    $line = [];
    foreach ($headers as $h) { $line[] = $r[$h] ?? ''; }
    fputcsv($out, $line);
  }
  fclose($out);
  exit;
}

$export = $_GET['export'] ?? '';
if ($export) {
  if ($export === 'students') {
    $rows = $pdo->query('SELECT student_id, ptc_email, last_name, first_name, middle_name, year_level, section, status FROM students ORDER BY last_name, first_name')->fetchAll();
    csv_out($rows, ['student_id','ptc_email','last_name','first_name','middle_name','year_level','section','status'], 'students.csv');
  }
  if ($export === 'professors') {
    $rows = $pdo->query('SELECT professor_id, ptc_email, last_name, first_name, middle_name, is_active FROM professors ORDER BY last_name, first_name')->fetchAll();
    csv_out($rows, ['professor_id','ptc_email','last_name','first_name','middle_name','is_active'], 'professors.csv');
  }
  if ($export === 'sections') {
    $rows = $pdo->query('SELECT id, section_name, schedule, is_active FROM sections ORDER BY section_name')->fetchAll();
    csv_out($rows, ['id','section_name','schedule','is_active'], 'sections.csv');
  }
  if ($export === 'subjects') {
    $rows = $pdo->query('SELECT subject_code, subject_title, units, is_active FROM subjects ORDER BY subject_code')->fetchAll();
    csv_out($rows, ['subject_code','subject_title','units','is_active'], 'subjects.csv');
  }
  if ($export === 'activity') {
    $rows = $pdo->query('SELECT id, user_id, action, details, ip, created_at FROM activity_logs ORDER BY id DESC LIMIT 500')->fetchAll();
    csv_out($rows, ['id','user_id','action','details','ip','created_at'], 'activity_logs.csv');
  }
}

$courseFilter = isset($_GET['course']) ? (int)$_GET['course'] : 0;
$yearFilter = isset($_GET['year_level']) ? trim($_GET['year_level']) : '';
$sectionFilter = isset($_GET['section']) ? (int)$_GET['section'] : 0;
$termFilter = isset($_GET['term']) ? (int)$_GET['term'] : 0;
$searchQuery = trim($_GET['search'] ?? '');

$courseOptions = $pdo->query('SELECT id, code, title FROM courses ORDER BY code')->fetchAll(PDO::FETCH_ASSOC);
$yearOptions = $pdo->query('SELECT DISTINCT year_level FROM students WHERE year_level IS NOT NULL ORDER BY year_level')->fetchAll(PDO::FETCH_COLUMN);
$sectionOptions = $pdo->query('SELECT id, section_name FROM sections ORDER BY section_name')->fetchAll(PDO::FETCH_ASSOC);
$termOptions = $pdo->query('SELECT id, term_name FROM terms ORDER BY start_date DESC')->fetchAll(PDO::FETCH_ASSOC);

$reportParams = [];
$gradeReports = [];
$reportTableExists = adminTableExists($pdo, 'report_of_grades');

if ($reportTableExists) {
    $reportSql = "
      SELECT st.student_id,
             CONCAT(st.last_name, ', ', st.first_name) AS student_name,
             c.code AS course_code,
             st.year_level,
             sec.section_name,
             t.term_name,
             COALESCE(ro.grade_average, 0) AS gwa,
             ro.status
        FROM report_of_grades ro
        JOIN students st ON st.id = ro.student_id
        LEFT JOIN sections sec ON sec.id = ro.section_id
        LEFT JOIN courses c ON c.id = sec.course_id
        LEFT JOIN terms t ON t.id = ro.term_id
       WHERE 1=1";

    if ($courseFilter > 0) {
        $reportSql .= ' AND st.course_id = :course';
        $reportParams[':course'] = $courseFilter;
    }
    if ($yearFilter !== '') {
        $reportSql .= ' AND st.year_level = :year_level';
        $reportParams[':year_level'] = $yearFilter;
    }
    if ($sectionFilter > 0) {
        $reportSql .= ' AND ro.section_id = :section';
        $reportParams[':section'] = $sectionFilter;
    }
    if ($termFilter > 0) {
        $reportSql .= ' AND ro.term_id = :term';
        $reportParams[':term'] = $termFilter;
    }
    if ($searchQuery !== '') {
        $reportSql .= ' AND (st.student_id LIKE :search OR CONCAT(st.last_name, ", ", st.first_name) LIKE :search)';
        $reportParams[':search'] = '%' . $searchQuery . '%';
    }

    $reportSql .= ' ORDER BY st.last_name, st.first_name';
    $reportStmt = $pdo->prepare($reportSql);
    $reportStmt->execute($reportParams);
    $gradeReports = $reportStmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $reportSql = "
      SELECT st.student_id,
             CONCAT(st.last_name, ', ', st.first_name) AS student_name,
             c.code AS course_code,
             st.year_level,
             MAX(sec.section_name) AS section_name,
             MAX(t.term_name) AS term_name,
             NULL AS gwa,
             NULL AS status
        FROM students st
   LEFT JOIN section_students ss ON ss.student_id = st.id
   LEFT JOIN sections sec ON sec.id = ss.section_id
   LEFT JOIN terms t ON t.id = sec.term_id
   LEFT JOIN courses c ON c.id = sec.course_id
       WHERE 1=1";

    if ($courseFilter > 0) {
        $reportSql .= ' AND sec.course_id = :course';
        $reportParams[':course'] = $courseFilter;
    }
    if ($yearFilter !== '') {
        $reportSql .= ' AND st.year_level = :year_level';
        $reportParams[':year_level'] = $yearFilter;
    }
    if ($sectionFilter > 0) {
        $reportSql .= ' AND sec.id = :section';
        $reportParams[':section'] = $sectionFilter;
    }
    if ($termFilter > 0) {
        $reportSql .= ' AND sec.term_id = :term';
        $reportParams[':term'] = $termFilter;
    }
    if ($searchQuery !== '') {
        $reportSql .= ' AND (st.student_id LIKE :search OR CONCAT(st.last_name, ", ", st.first_name) LIKE :search)';
        $reportParams[':search'] = '%' . $searchQuery . '%';
    }

    $reportSql .= '
        GROUP BY st.id, st.student_id, st.last_name, st.first_name, c.code, st.year_level
        ORDER BY st.last_name, st.first_name';

    $reportStmt = $pdo->prepare($reportSql);
    $reportStmt->execute($reportParams);
    $gradeReports = $reportStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Reports</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
  </head>
  <body>
    <?php include '../includes/header.php'; ?>
    <div class="layout">
      <?php include '../includes/sidebar.php'; ?>
      <main class="content">
        <?php show_flash(); ?>

        <div class="page-header">
          <h1>Reports</h1>
          <p class="text-muted">View and manage student grade reports by section and term</p>
        </div>

        <section class="reports-filter card">
          <div class="card-body">
            <div class="section-header">
              <div>
                <h2>Filter Reports</h2>
                <p class="text-muted">Refine grade reports by course, year level, section, and term.</p>
              </div>
              <a class="btn ghost" href="./reports.php">Clear All Filters</a>
            </div>
            <form method="get" class="filters-row">
              <label>
                <span>Course</span>
                <select name="course">
                  <option value="0">-- Select Course --</option>
                  <?php foreach ($courseOptions as $course): ?>
                    <?php $courseId = (int)$course['id']; ?>
                    <option value="<?= $courseId; ?>" <?= $courseFilter === $courseId ? 'selected' : ''; ?>>
                      <?= htmlspecialchars($course['code'] ?: $course['title']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label>
                <span>Year Level</span>
                <select name="year_level">
                  <option value="">-- Select Year --</option>
                  <?php foreach ($yearOptions as $year): ?>
                    <option value="<?= htmlspecialchars($year); ?>" <?= $yearFilter === $year ? 'selected' : ''; ?>>
                      <?= htmlspecialchars($year); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label>
                <span>Section</span>
                <select name="section">
                  <option value="0">-- Select Section --</option>
                  <?php foreach ($sectionOptions as $section): ?>
                    <?php $secId = (int)$section['id']; ?>
                    <option value="<?= $secId; ?>" <?= $sectionFilter === $secId ? 'selected' : ''; ?>>
                      <?= htmlspecialchars($section['section_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label>
                <span>Term/Semester</span>
                <select name="term">
                  <option value="0">-- Select Term --</option>
                  <?php foreach ($termOptions as $term): ?>
                    <?php $termId = (int)$term['id']; ?>
                    <option value="<?= $termId; ?>" <?= $termFilter === $termId ? 'selected' : ''; ?>>
                      <?= htmlspecialchars($term['term_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label>
                <span>Search</span>
                <input type="text" name="search" placeholder="Search student..." value="<?= htmlspecialchars($searchQuery); ?>">
              </label>
              <button type="submit">Apply Filters</button>
            </form>
          </div>
        </section>

        <section class="reports-table card">
          <div class="card-body">
            <div class="section-header">
              <div>
                <h2>Student Grade Reports</h2>
                <p class="text-muted">Latest grade submissions grouped by section and term.</p>
              </div>
            </div>
            <div class="table-responsive">
              <table class="reports-data-table">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>Course</th>
                    <th>Year Level</th>
                    <th>Section</th>
                    <th>Term</th>
                    <th>GWA</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($gradeReports)): ?>
                    <tr>
                      <td colspan="10" class="empty-cell">No grade reports found for the selected filters.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($gradeReports as $index => $report): ?>
                      <?php
                        $gwaValue = $report['gwa'];
                        $gwaDisplay = $gwaValue !== null ? number_format((float)$gwaValue, 2) : '--';
                        $statusRaw = strtolower((string)($report['status'] ?? ''));
                        if ($statusRaw === 'fail' || $statusRaw === 'failed') {
                            $statusLabel = 'Failed';
                            $statusClass = 'status-failed';
                        } elseif ($statusRaw === 'pass' || $statusRaw === 'passed') {
                            $statusLabel = 'Passed';
                            $statusClass = 'status-passed';
                        } else {
                            $statusLabel = 'Pending';
                            $statusClass = 'status-pending';
                        }
                      ?>
                      <tr>
                        <td><?= $index + 1; ?></td>
                        <td><?= htmlspecialchars($report['student_id']); ?></td>
                        <td><?= htmlspecialchars($report['student_name']); ?></td>
                        <td><?= htmlspecialchars($report['course_code'] ?? ''); ?></td>
                        <td><?= htmlspecialchars($report['year_level'] ?? ''); ?></td>
                        <td><?= htmlspecialchars($report['section_name'] ?? ''); ?></td>
                        <td><?= htmlspecialchars($report['term_name'] ?? ''); ?></td>
                        <td><?= htmlspecialchars($gwaDisplay); ?></td>
                        <td><span class="status-pill <?= $statusClass; ?>"><?= htmlspecialchars($statusLabel); ?></span></td>
                        <td class="actions">
                          <a class="btn ghost" href="./report_view.php?student_id=<?= urlencode($report['student_id']); ?>">View</a>
                          <a class="btn primary" href="./report_view.php?student_id=<?= urlencode($report['student_id']); ?>&download=1">Download</a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </section>


        <script src="../assets/js/admin.js"></script>
      </main>
    </div>
  </body>
  <?php include '../includes/footer.php'; ?>
</html>
