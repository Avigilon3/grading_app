<?php
require_once '../includes/init.php';
requireStudent();

function findStudentProfileForReport(PDO $pdo, ?int $userId, string $email): ?array
{
    if ($userId) {
        $stmt = $pdo->prepare(
            "SELECT s.*, u.email AS user_email
               FROM students s
          LEFT JOIN users u ON u.id = s.user_id
              WHERE s.user_id = :user_id
              LIMIT 1"
        );
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }
    }

    $email = trim($email);
    if ($email === '') {
        return null;
    }

    $stmt = $pdo->prepare(
        "SELECT s.*, u.email AS user_email
           FROM students s
      LEFT JOIN users u ON u.id = s.user_id
          WHERE s.ptc_email = :email OR u.email = :email
          LIMIT 1"
    );
    $stmt->execute([':email' => $email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function getStudentSectionIdsForReport(PDO $pdo, int $studentId, ?string $sectionName = null): array
{
    $ids = [];

    $stmt = $pdo->prepare("SELECT section_id FROM section_students WHERE student_id = :sid");
    $stmt->execute([':sid' => $studentId]);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if ($rows) {
        foreach ($rows as $row) {
            $ids[] = (int)$row;
        }
    }

    if (!$ids && $sectionName) {
        $normalized = trim(preg_replace('/\s+/', ' ', (string)$sectionName));
        if ($normalized !== '') {
            $lookup = $pdo->prepare("SELECT id FROM sections WHERE LOWER(section_name) = LOWER(:name) LIMIT 1");
            $lookup->execute([':name' => $normalized]);
            $fallback = $lookup->fetch(PDO::FETCH_ASSOC);
            if ($fallback && !empty($fallback['id'])) {
                $ids[] = (int)$fallback['id'];
            }
        }
    }

    return array_values(array_unique(array_filter($ids, static fn($id) => $id > 0)));
}

function loadSubjectsForSectionsReport(PDO $pdo, array $sectionIds): array
{
    if (!$sectionIds) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($sectionIds), '?'));
    $sql = "
        SELECT
            sec.id AS section_id,
            sec.section_name,
            sec.year_level,
            c.code AS course_code,
            c.title AS course_title,
            t.term_name,
            t.semester,
            t.school_year,
            sub.subject_code,
            sub.subject_title,
            COALESCE(sub.units, 0) AS units
        FROM sections sec
        JOIN section_subjects secsub ON secsub.section_id = sec.id
        JOIN subjects sub ON sub.id = secsub.subject_id
        LEFT JOIN courses c ON c.id = sec.course_id
        LEFT JOIN terms t ON t.id = secsub.term_id
        WHERE sec.id IN ($placeholders)
        ORDER BY sec.section_name, sub.subject_title
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_map('intval', $sectionIds));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function formatYearLevelLabel(?string $year): ?string
{
    if ($year === null || $year === '') {
        return null;
    }
    $map = [
        '1' => '1st Year',
        '2' => '2nd Year',
        '3' => '3rd Year',
        '4' => '4th Year',
    ];
    return $map[(string)$year] ?? null;
}

function formatSemesterLabel(?string $semesterCode): ?string
{
    if ($semesterCode === null || $semesterCode === '') {
        return null;
    }
    $map = [
        '1' => '1st Semester',
        '2' => '2nd Semester',
    ];
    return $map[(string)$semesterCode] ?? null;
}

function formatUnitsForDisplay(float $value): string
{
    return (abs($value - round($value)) < 0.01) ? (string)round($value) : number_format($value, 1);
}

$currentUserId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
$currentEmail = $_SESSION['user']['email'] ?? '';

$studentProfile = null;
$subjects = [];
$sectionInfo = null;

try {
    if ($currentUserId || $currentEmail !== '') {
        $studentProfile = findStudentProfileForReport($pdo, $currentUserId, $currentEmail);
    }
} catch (Exception $e) {
    $studentProfile = null;
}

if ($studentProfile) {
    try {
        $sectionIds = getStudentSectionIdsForReport($pdo, (int)$studentProfile['id'], $studentProfile['section'] ?? null);
        $subjects = $sectionIds ? loadSubjectsForSectionsReport($pdo, $sectionIds) : [];

        $sectionName = trim((string)($studentProfile['section'] ?? ''));
        if ($sectionName !== '') {
            $secStmt = $pdo->prepare(
                "SELECT sec.*, c.code AS course_code, c.title AS course_title, t.term_name, t.semester, t.school_year
                   FROM sections sec
              LEFT JOIN courses c ON c.id = sec.course_id
              LEFT JOIN terms t ON t.id = sec.term_id
                  WHERE sec.section_name = :name
                  LIMIT 1"
            );
            $secStmt->execute([':name' => $sectionName]);
            $sectionInfo = $secStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    } catch (Exception $e) {
        $subjects = [];
        $sectionInfo = null;
    }
}

// Normalize subjects with year/semester labels for filtering
$normalizedSubjects = [];
foreach ($subjects as $row) {
    $row['units'] = (float)($row['units'] ?? 0);
    $row['year_label'] = formatYearLevelLabel($row['year_level'] ?? null);
    $row['semester_label'] = formatSemesterLabel($row['semester'] ?? null);
    $normalizedSubjects[] = $row;
}
$subjects = $normalizedSubjects;
unset($normalizedSubjects);

$yearOptions = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
$semesterOptions = ['1st Semester', '2nd Semester', 'Summer'];

$defaultYear = $studentProfile ? formatYearLevelLabel($studentProfile['year_level'] ?? null) : null;
if (!$defaultYear || !in_array($defaultYear, $yearOptions, true)) {
    $defaultYear = '2nd Year';
}

$defaultSemester = null;
if ($sectionInfo && !empty($sectionInfo['semester'])) {
    $defaultSemester = formatSemesterLabel($sectionInfo['semester']);
}
if (!$defaultSemester && $subjects) {
    foreach ($subjects as $row) {
        if (!empty($row['semester_label'])) {
            $defaultSemester = $row['semester_label'];
            break;
        }
    }
}
if (!$defaultSemester) {
    $defaultSemester = '1st Semester';
}

$selectedYear = isset($_GET['year_level']) && in_array($_GET['year_level'], $yearOptions, true)
    ? $_GET['year_level']
    : $defaultYear;
$selectedSemester = isset($_GET['semester']) && in_array($_GET['semester'], $semesterOptions, true)
    ? $_GET['semester']
    : $defaultSemester;

$filteredSubjects = array_values(array_filter($subjects, static function ($row) use ($selectedYear, $selectedSemester) {
    if (($row['year_label'] ?? null) !== $selectedYear) {
        return false;
    }
    if (($row['semester_label'] ?? null) !== $selectedSemester) {
        return false;
    }
    return true;
}));

$totalUnits = 0.0;
$displaySchoolYear = null;
$displaySemesterLabel = $selectedSemester;
if ($filteredSubjects) {
    foreach ($filteredSubjects as $row) {
        $totalUnits += (float)($row['units'] ?? 0);
    }
    $firstRow = $filteredSubjects[0];
    if (!empty($firstRow['school_year'])) {
        $displaySchoolYear = $firstRow['school_year'];
    } elseif (!empty($sectionInfo['school_year'])) {
        $displaySchoolYear = $sectionInfo['school_year'];
    }
    if (!empty($firstRow['semester_label'])) {
        $displaySemesterLabel = $firstRow['semester_label'];
    } elseif ($sectionInfo && !empty($sectionInfo['semester'])) {
        $displaySemesterLabel = formatSemesterLabel($sectionInfo['semester']);
    }
} elseif ($sectionInfo) {
    if (!empty($sectionInfo['school_year'])) {
        $displaySchoolYear = $sectionInfo['school_year'];
    }
    if (!empty($sectionInfo['semester'])) {
        $displaySemesterLabel = formatSemesterLabel($sectionInfo['semester']) ?: $displaySemesterLabel;
    }
}

$unitsEarnedDisplay = $totalUnits > 0 ? formatUnitsForDisplay($totalUnits) : '';

// Header values
$studentNameDisplay = '';
if ($studentProfile) {
    $last = trim((string)($studentProfile['last_name'] ?? ''));
    $first = trim((string)($studentProfile['first_name'] ?? ''));
    $middle = trim((string)($studentProfile['middle_name'] ?? ''));
    $studentNameDisplay = $last !== '' || $first !== ''
        ? trim($last . ', ' . $first . ($middle !== '' ? ' ' . $middle : ''))
        : '';
}
if ($studentNameDisplay === '' && isset($_SESSION['user']['name'])) {
    $studentNameDisplay = (string)$_SESSION['user']['name'];
}

$courseDisplay = '';
if ($sectionInfo && !empty($sectionInfo['course_title'])) {
    $courseDisplay = $sectionInfo['course_title'];
}

$yearLabel = $studentProfile ? formatYearLevelLabel($studentProfile['year_level'] ?? null) : null;
$sectionName = '';
if ($sectionInfo && !empty($sectionInfo['section_name'])) {
    $sectionName = $sectionInfo['section_name'];
} elseif ($studentProfile && !empty($studentProfile['section'])) {
    $sectionName = $studentProfile['section'];
}
$yearSectionDisplayParts = [];
if ($yearLabel) {
    $yearSectionDisplayParts[] = $yearLabel;
}
if ($sectionName !== '') {
    $yearSectionDisplayParts[] = $sectionName;
}
$yearSectionDisplay = $yearSectionDisplayParts ? implode(' - ', $yearSectionDisplayParts) : '';

$syLine = '';
if ($displaySemesterLabel || $displaySchoolYear) {
    $syLine = trim(
        ($displaySemesterLabel ?: '') .
        ($displaySchoolYear ? ' S.Y. ' . $displaySchoolYear : '')
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report of Grades</title>
    <link rel="stylesheet" href="../assets/css/student.css">
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <div class="layout">
        <?php include '../includes/sidebar.php'; ?>
        <main class="content">
          <div class="page-container report-page">

            <div class="page-header">
              <h1>Report of Grades</h1>
              <p class="text-muted">
                View and download your official report of grades.
              </p>
            </div>

            <div class="report-shell report-of-grades">
              <div class="grade-toolbar">
                <div class="filter-row">
                  <label class="input-block">
                    <span class="input-label">Year Level</span>
                    <input class="form-control" name="year_level" value="<?php echo htmlspecialchars($selectedYear); ?>" placeholder="Year Level" readonly>
                  </label>
                  <label class="input-block">
                    <span class="input-label">Semester</span>
                    <input class="form-control" name="semester" value="<?php echo htmlspecialchars($selectedSemester); ?>" placeholder="Semester" readonly>
                  </label>
                </div>
                <div class="action-buttons">
                  <a class="btn solid download-btn" href="#" onclick="window.print(); return false;">
                    <span class="material-symbols-rounded" aria-hidden="true">file_download</span>
                    Download as JPEG
                  </a>
                  <a class="btn ghost request-btn" href="../pages/requests.php">
                    <span class="material-symbols-rounded" aria-hidden="true">description</span>
                    Request Hard Copy with Seal
                  </a>
                </div>
              </div>

              <div class="report-preview">
                <div class="report-paper">
                  <div class="paper-header">
                    <img src="../../admin/assets/images/logo-ptc.png" alt="PTC Logo">
                    <div class="paper-title-block">
                      <div class="paper-school">PATEROS TECHNOLOGICAL COLLEGE</div>
                      <p class="paper-meta">
                        COLLEGE ST., STO. ROSARIO-KANLURAN<br>
                        PATEROS, METRO MANILA<br>
                        WEBSITE: <span>paterostechnologicalcollege.edu.ph</span>
                      </p>
                    </div>
                  </div>

                  <p class="paper-date" id="reportDateTop">August 08, 2024</p>
                  <div class="paper-heading">REPORT OF GRADE</div>
                  <p class="paper-subtitle">Institute of Information and Communication Technology</p>

                  <table class="student-meta">
                    <tr>
                      <td>NAME OF STUDENT:</td>
                      <td><strong><?php echo htmlspecialchars($studentNameDisplay); ?></strong></td>
                    </tr>
                    <tr>
                      <td>COURSE:</td>
                      <td><?php echo htmlspecialchars($courseDisplay); ?></td>
                    </tr>
                    <tr>
                      <td>YEAR &amp; SECTION:</td>
                      <td><?php echo htmlspecialchars($yearSectionDisplay); ?></td>
                    </tr>
                    <tr>
                      <td>S.Y.:</td>
                      <td><?php echo htmlspecialchars($syLine); ?></td>
                    </tr>
                  </table>

                  <table class="grade-table">
                    <thead>
                      <tr>
                        <th>SUBJECT CODE</th>
                        <th>SUBJECT TITLE</th>
                        <th>Units</th>
                        <th>GRADE</th>
                        <th>REMARKS</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if ($filteredSubjects): ?>
                        <?php foreach ($filteredSubjects as $row): ?>
                          <tr>
                            <td><?php echo htmlspecialchars($row['subject_code'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['subject_title'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars(formatUnitsForDisplay((float)($row['units'] ?? 0))); ?></td>
                            <td></td>
                            <td></td>
                          </tr>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <tr>
                          <td colspan="5">&nbsp;</td>
                        </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>

                  <p class="unit-note">
                    <strong>Units Earned:</strong>
                    <?php if ($unitsEarnedDisplay !== ''): ?>
                      <?php echo htmlspecialchars($unitsEarnedDisplay); ?>
                    <?php else: ?>
                      &nbsp;
                    <?php endif; ?>
                  </p>
                  <p class="data-stamp" id="reportDateBottom">THIS DATA WAS GENERATED ON <span id="generationDate">August 08, 2024 </span> (Student's Copy)</p>
                </div>
                <div class="doc-caption">
                  Showing: <?php echo htmlspecialchars($selectedYear); ?> - <?php echo htmlspecialchars($selectedSemester); ?>
                  <?php if ($displaySchoolYear): ?>
                    (S.Y. <?php echo htmlspecialchars($displaySchoolYear); ?>)
                  <?php endif; ?>
                </div>
              </div>

              <div class="info-card notice-card">
                <div class="info-icon" aria-hidden="true">i</div>
                <div>
                  <h3>Important Information</h3>
                  <ul class="info-list">
                    <li>Downloaded reports are for reference only and not official documents.</li>
                    <li>Hard copy requests with school dry seal typically take 3-5 business days to process.</li>
                    <li>You must pick up hard copies personally at the Registrar's Office with a valid ID.</li>
                    <li>Official hard copies with seal are required for employment and scholarship applications.</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </main>
    </div>
</body>
<script src="../assets/js/student.js"></script>
</html>
