<?php
require_once '../includes/init.php';
requireAdmin();

function adminFindStudentByNumber(PDO $pdo, string $studentNumber): ?array
{
    if ($studentNumber === '') {
        return null;
    }
    $stmt = $pdo->prepare('SELECT id, student_id, first_name, middle_name, last_name, year_level, section FROM students WHERE student_id = ? LIMIT 1');
    $stmt->execute([$studentNumber]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function adminGetStudentSectionIds(PDO $pdo, int $studentId, ?string $sectionName = null): array
{
    $ids = [];
    $stmt = $pdo->prepare('SELECT section_id FROM section_students WHERE student_id = ?');
    $stmt->execute([$studentId]);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($rows as $row) {
        $ids[] = (int)$row;
    }
    if (!$ids && $sectionName) {
        $normalized = trim(preg_replace('/\s+/', ' ', (string)$sectionName));
        if ($normalized !== '') {
            $lookup = $pdo->prepare('SELECT id FROM sections WHERE LOWER(section_name) = LOWER(?) LIMIT 1');
            $lookup->execute([$normalized]);
            $fallback = $lookup->fetchColumn();
            if ($fallback) {
                $ids[] = (int)$fallback;
            }
        }
    }
    return array_values(array_unique(array_filter($ids, static fn($id) => $id > 0)));
}

function adminLoadSubjectsForSections(PDO $pdo, array $sectionIds): array
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
            COALESCE(sub.units, 0) AS units,
            secsub.id AS section_subject_id
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

function adminFormatYearLevelLabel(?string $value): ?string
{
    if ($value === null || $value === '') {
        return null;
    }
    $map = [
        '1' => '1st Year',
        '2' => '2nd Year',
        '3' => '3rd Year',
        '4' => '4th Year',
    ];
    return $map[(string)$value] ?? null;
}

function adminFormatSemesterLabel(?string $value): ?string
{
    if ($value === null || $value === '') {
        return null;
    }
    $map = [
        '1' => '1st Semester',
        '2' => '2nd Semester',
    ];
    return $map[(string)$value] ?? null;
}

function adminFormatUnits(float $value): string
{
    return (abs($value - round($value)) < 0.01)
        ? (string)round($value)
        : number_format($value, 1);
}

function adminDeriveGradeDisplayAndRemarks(?array $gradeInfo): array
{
    $gradeDisplay = 'Pending';
    $remarks = 'In Progress';
    if ($gradeInfo) {
        if (!empty($gradeInfo['equivalent'])) {
            $gradeDisplay = $gradeInfo['equivalent'];
        } elseif ($gradeInfo['final_grade_display'] !== null) {
            $gradeDisplay = number_format((float)$gradeInfo['final_grade_display'], 2) . '%';
        }
        $gradeValue = $gradeInfo['final_grade_display'] ?? $gradeInfo['final_grade'];
        if ($gradeValue !== null) {
            $remarks = $gradeValue >= 75 ? 'Passed' : 'Failed';
        }
    }
    return [$gradeDisplay, $remarks];
}

$studentNumber = trim($_GET['student_id'] ?? '');
if ($studentNumber === '') {
    set_flash('error', 'No student selected.');
    header('Location: ./reports.php');
    exit;
}

$studentProfile = adminFindStudentByNumber($pdo, $studentNumber);
if (!$studentProfile) {
    set_flash('error', 'Student record not found.');
    header('Location: ./reports.php');
    exit;
}

$sectionIds = adminGetStudentSectionIds($pdo, (int)$studentProfile['id'], $studentProfile['section'] ?? null);
$subjects = $sectionIds ? adminLoadSubjectsForSections($pdo, $sectionIds) : [];

$sectionInfo = null;
if ($sectionIds) {
    $secStmt = $pdo->prepare(
        "SELECT sec.*, c.code AS course_code, c.title AS course_title, t.term_name, t.semester, t.school_year
           FROM sections sec
      LEFT JOIN courses c ON c.id = sec.course_id
      LEFT JOIN terms t ON t.id = sec.term_id
          WHERE sec.id = ?
          LIMIT 1"
    );
    $secStmt->execute([$sectionIds[0]]);
    $sectionInfo = $secStmt->fetch(PDO::FETCH_ASSOC) ?: null;
} elseif (!empty($studentProfile['section'])) {
    $secName = trim((string)$studentProfile['section']);
    $secStmt = $pdo->prepare(
        "SELECT sec.*, c.code AS course_code, c.title AS course_title, t.term_name, t.semester, t.school_year
           FROM sections sec
      LEFT JOIN courses c ON c.id = sec.course_id
      LEFT JOIN terms t ON t.id = sec.term_id
          WHERE sec.section_name = ?
          LIMIT 1"
    );
    $secStmt->execute([$secName]);
    $sectionInfo = $secStmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$normalizedSubjects = [];
foreach ($subjects as $row) {
    $row['units'] = (float)($row['units'] ?? 0);
    $row['year_label'] = adminFormatYearLevelLabel($row['year_level'] ?? null);
    $row['semester_label'] = adminFormatSemesterLabel($row['semester'] ?? null);
    $normalizedSubjects[] = $row;
}
$subjects = $normalizedSubjects;
unset($normalizedSubjects);

$yearOptions = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
$semesterOptions = ['1st Semester', '2nd Semester', 'Summer'];

$defaultYear = adminFormatYearLevelLabel($studentProfile['year_level'] ?? null) ?: '1st Year';
if (!in_array($defaultYear, $yearOptions, true)) {
    $defaultYear = '1st Year';
}
$defaultSemester = null;
if ($sectionInfo && !empty($sectionInfo['semester'])) {
    $defaultSemester = adminFormatSemesterLabel($sectionInfo['semester']);
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
        $displaySemesterLabel = adminFormatSemesterLabel($sectionInfo['semester']) ?: $displaySemesterLabel;
    }
} elseif ($sectionInfo) {
    if (!empty($sectionInfo['school_year'])) {
        $displaySchoolYear = $sectionInfo['school_year'];
    }
    if (!empty($sectionInfo['semester'])) {
        $displaySemesterLabel = adminFormatSemesterLabel($sectionInfo['semester']) ?: $displaySemesterLabel;
    }
}
$unitsEarnedDisplay = $totalUnits > 0 ? adminFormatUnits($totalUnits) : '';

$studentNameDisplay = trim(($studentProfile['last_name'] ?? '') . ', ' . ($studentProfile['first_name'] ?? ''));
if (!empty($studentProfile['middle_name'])) {
    $studentNameDisplay .= ' ' . trim((string)$studentProfile['middle_name']);
}
$studentNameDisplay = trim($studentNameDisplay, ', ');

$courseDisplay = '';
if ($sectionInfo && !empty($sectionInfo['course_title'])) {
    $courseDisplay = $sectionInfo['course_title'];
} elseif ($filteredSubjects) {
    foreach ($filteredSubjects as $row) {
        if (!empty($row['course_title'])) {
            $courseDisplay = $row['course_title'];
            break;
        }
    }
}

$yearLabel = adminFormatYearLevelLabel($studentProfile['year_level'] ?? null);
$sectionName = $sectionInfo['section_name'] ?? ($studentProfile['section'] ?? '');
$yearSectionDisplayParts = [];
if ($yearLabel) {
    $yearSectionDisplayParts[] = $yearLabel;
}
if ($sectionName) {
    $yearSectionDisplayParts[] = $sectionName;
}
$yearSectionDisplay = $yearSectionDisplayParts ? implode(' - ', $yearSectionDisplayParts) : '';

$syLine = '';
if ($displaySemesterLabel || $displaySchoolYear) {
    $syLine = trim(($displaySemesterLabel ?: '') . ($displaySchoolYear ? ' S.Y. ' . $displaySchoolYear : ''));
}

$reportDate = new DateTimeImmutable('now');
$reportFilename = preg_replace('/[^a-z0-9]+/i', '_', $studentNumber ?: 'student') . '_report_of_grades';
$autoDownload = isset($_GET['download']) && $_GET['download'] === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report of Grades &mdash; <?= htmlspecialchars($studentNumber); ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">

</head>
<body data-auto-download="<?= $autoDownload ? '1' : '0'; ?>" data-report-filename="<?= htmlspecialchars($reportFilename); ?>">
<?php include '../includes/header.php'; ?>
<div class="layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="content">
        <?php show_flash(); ?>
        <div class="page-header">
            <div>
                <h1>Report of Grades</h1>
                <p class="text-muted">Generated view for <?= htmlspecialchars($studentNameDisplay ?: $studentNumber); ?></p>
            </div>
            <a href="./reports.php" class="btn ghost">Back to Reports</a>
        </div>

        <div class="report-shell report-of-grades">
            <div class="grade-toolbar">
                <div class="info">
                    <strong>Year Level:</strong> <?= htmlspecialchars($selectedYear); ?> &middot;
                    <strong>Semester:</strong> <?= htmlspecialchars($selectedSemester); ?>
                </div>
                <div class="report-actions">
                    <button type="button" class="btn primary" id="download-pdf-btn">Download PDF</button>
                </div>
            </div>
            <div class="report-preview">
                <div class="report-paper" id="report-paper">
                    <div class="paper-header">
                        <img src="../../admin/assets/images/logo-ptc.png" alt="PTC logo">
                        <div class="paper-title-block">
                            <div class="paper-school">PATEROS TECHNOLOGICAL COLLEGE</div>
                            <p class="paper-meta">
                                COLLEGE ST., STO. ROSARIO-KANLURAN<br>
                                PATEROS, METRO MANILA<br>
                                WEBSITE: <span>paterostechnologicalcollege.edu.ph</span>
                            </p>
                        </div>
                    </div>
                    <p class="paper-date"><?= htmlspecialchars($reportDate->format('F d, Y')); ?></p>
                    <div class="paper-heading">Certification of Grades</div>
                    <p class="paper-subtitle">This is to certify that as per records on file in this Office, <strong><?= htmlspecialchars($studentNameDisplay);?></strong> has obtained the following subjects during the school year <?= htmlspecialchars($syLine ?: $displaySemesterLabel); ?> required for <strong><?= htmlspecialchars($courseDisplay ?: ''); ?> </strong>with the corresponding grades:</p>
                    <!-- <table class="student-meta">
                        <tr>
                            <td>NAME OF STUDENT:</td>
                            <td><strong><?= htmlspecialchars($studentNameDisplay ?: $studentNumber); ?></strong></td>
                        </tr>
                        <tr>
                            <td>COURSE:</td>
                            <td><?= htmlspecialchars($courseDisplay ?: ''); ?></td>
                        </tr>
                        <tr>
                            <td>YEAR &amp; SECTION:</td>
                            <td><?= htmlspecialchars($yearSectionDisplay ?: $selectedYear); ?></td>
                        </tr>
                        <tr>
                            <td>S.Y.:</td>
                            <td><?= htmlspecialchars($syLine ?: $displaySemesterLabel); ?></td>
                        </tr>
                    </table> -->
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
                                <?php
                                    $sectionId = (int)($row['section_id'] ?? 0);
                                    $sectionSubjectId = (int)($row['section_subject_id'] ?? 0);
                                    $gradeInfo = ($sectionId && $studentProfile['id'])
                                        ? computeStudentGradeForSection($pdo, (int)$studentProfile['id'], $sectionId, $sectionSubjectId ?: null)
                                        : null;
                                    [$gradeDisplay, $gradeRemarks] = adminDeriveGradeDisplayAndRemarks($gradeInfo);
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['subject_code'] ?? ''); ?></td>
                                    <td><?= htmlspecialchars($row['subject_title'] ?? ''); ?></td>
                                    <td><?= htmlspecialchars(adminFormatUnits((float)($row['units'] ?? 0))); ?></td>
                                    <td><?= htmlspecialchars($gradeDisplay); ?></td>
                                    <td><?= htmlspecialchars($gradeRemarks); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No locked grades available for the selected term.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                    <p class="unit-note"><strong>Units Earned:</strong> <?= htmlspecialchars($unitsEarnedDisplay ?: '--'); ?></p>
                    <p class="paper-date">Prepared by: </p>
                </div>

            </div>

            <div class="doc-caption">
                    Showing <?= htmlspecialchars($selectedYear); ?> &mdash; <?= htmlspecialchars($displaySemesterLabel); ?>
                    <?php if ($displaySchoolYear): ?>
                        (S.Y. <?= htmlspecialchars($displaySchoolYear); ?>)
                    <?php endif; ?>
            </div>
            
            <div class="info-card">
                <h3>Reminders</h3>
                <ul>
                    <li>Use the Download option for PDF copies shared with other departments.</li>
                    <li>Official hard copies with dry seal can only be issued by the Registrar's Office.</li>
                    <li>Ensure grades are locked by the professor before exporting official reports.</li>
                </ul>
            </div>
        </div>
    </main>
</div>
<script src="../assets/js/admin.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-YcsIPMjACz48zkRgLpmjDoE7YQDdyCjTiMQuuLHfoalGexiLRNvKcJsteVEh9UpAJZKkd06Pq4jG+7hM3w5f0A==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
(function () {
    'use strict';
    var downloadBtn = document.getElementById('download-pdf-btn');
    function triggerPdfDownload() {
        var paper = document.getElementById('report-paper');
        if (!paper) {
            window.print();
            return;
        }
        var filename = document.body.getAttribute('data-report-filename') || 'report_of_grades';
        if (typeof html2pdf === 'undefined') {
            window.print();
            return;
        }
        html2pdf().set({
            margin: 0.5,
            filename: filename + '.pdf',
            html2canvas: { scale: 2, useCORS: true },
            jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
        }).from(paper).save();
    }
    if (downloadBtn) {
        downloadBtn.addEventListener('click', function (event) {
            event.preventDefault();
            triggerPdfDownload();
        });
    }
    if (document.body.getAttribute('data-auto-download') === '1') {
        setTimeout(triggerPdfDownload, 600);
    }
})();
</script>
</body>
<?php include '../includes/footer.php'; ?>
</html>
