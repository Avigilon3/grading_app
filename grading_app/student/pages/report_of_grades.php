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

function deriveGradeDisplayAndRemarks(?array $gradeInfo): array
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
    <link rel="stylesheet" href="../assets/css/student.css?v=20260916.6">
    <style>
        .page-container.report-page {
            width: min(100%, 1180px);
            margin: 0 auto;
            padding-top: 6px;
        }

        .page-header {
            margin: 0 0 18px;
            padding: 0 8px;
        }

        .page-header h1 {
            margin: 0 0 8px;
            font-size: clamp(2.3rem, 3vw, 4rem);
            line-height: 1.08;
            color: #1d2a2d;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .page-header .text-muted {
            margin: 0;
            color: #596771;
            font-size: clamp(1.08rem, 1.6vw, 1.5rem);
        }

        .report-shell.report-of-grades {
            background: rgba(255,255,255,0.14);
            border: 1px solid rgba(21, 37, 26, 0.07);
            border-radius: 18px;
            box-shadow: none;
            padding: 12px 18px 14px;
        }

        .grade-toolbar {
          display: grid;
          grid-template-columns: minmax(0, 1fr);
            align-items: flex-end;
            margin-bottom: 18px;
            gap: 16px;
        }

        .filter-row {
          max-width: none;
            grid-template-columns: repeat(2, minmax(240px, 1fr));
            gap: 18px;
        }

        .action-buttons {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            justify-content: stretch;
            margin-left: 0;
        }

        .action-buttons .btn {
            flex: 1 1 0;
            min-width: 0;
            justify-content: center;
        }

        .input-block {
            margin: 0;
        }

        .input-label {
            font-size: 0.9rem;
            color: #1f2937;
            font-weight: 700;
        }

        .form-control {
            border: 1px solid #7d878c;
            border-radius: 10px;
            background: rgba(255,255,255,0.82);
            color: #1d2a2d;
            min-height: 44px;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.04);
        }

        .report-preview {
            background: rgba(247,248,250,0.34);
            border: 1px solid rgba(185, 191, 197, 0.75);
            border-radius: 16px;
            padding: 16px 18px 18px;
            margin: 0 0 18px;
        }

        .report-paper {
            max-width: 960px;
            min-height: 620px;
            width: 100%;
            margin: 0 auto;
            padding: 26px 30px 18px;
            border-radius: 12px;
            border: 1px solid #dfe3e7;
            background: #fff;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.04);
        }

        .paper-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 18px;
            margin-bottom: 8px;
        }

        .paper-header img {
            width: 110px;
            height: 110px;
            object-fit: contain;
            flex-shrink: 0;
            margin-right: 8px;
        }

        .paper-title-block {
            flex: 1;
            text-align: center;
        }

        .paper-school {
            font-size: clamp(1.6rem, 2vw, 2.2rem);
            letter-spacing: 0.02em;
            line-height: 1.2;
            font-weight: 700;
            text-transform: uppercase;
            color: #111827;
        }

        .paper-meta {
            margin: 8px 0 0;
            font-size: 0.8rem;
            line-height: 1.45;
            color: #1f2937;
        }

        .paper-date {
            margin: 12px 0 8px;
            font-size: 0.9rem;
            color: #1f2937;
        }

        .paper-heading {
            margin: 8px 0 0;
            font-size: 1.12rem;
            letter-spacing: 0.04em;
        }

        .paper-subtitle {
            margin: 0 0 18px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .student-meta {
            width: 100%;
            margin: 0 0 18px;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .student-meta td {
            padding: 6px 8px;
            vertical-align: top;
        }

        .student-meta td:first-child {
            width: 180px;
            font-weight: 700;
            white-space: nowrap;
            color: #111827;
        }

        .grade-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
        }

        .grade-table th,
        .grade-table td {
            padding: 8px 10px;
            border: 1px solid #111827;
            text-align: left;
        }

        .grade-table th {
            font-weight: 700;
            background: #f8fafc;
        }

        .unit-note,
        .data-stamp {
            margin: 12px 0 0;
            font-size: 0.82rem;
        }

        .doc-caption {
            margin-top: 12px;
            font-size: 0.82rem;
            text-align: center;
            color: #5b6474;
        }

        .notice-card {
            margin-top: 0;
            padding: 14px 16px;
            background: #f5f9ff;
            border-color: #dce8f7;
        }

        @media (max-width: 768px) {
            .filter-row {
                grid-template-columns: 1fr;
                max-width: none;
            }

            .grade-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .action-buttons {
                justify-content: flex-start;
            }

            .action-buttons .btn {
                flex: 1 1 auto;
                width: 100%;
            }

            .report-paper {
                min-height: auto;
                padding: 20px 18px 14px;
            }

            .paper-header {
                flex-direction: column;
            }
        }
    </style>
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
                  <a class="btn solid download-btn" id="download-jpeg-btn" href="#">
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
                          <?php
                            $sectionId = isset($row['section_id']) ? (int)$row['section_id'] : 0;
                            $sectionSubjectId = isset($row['section_subject_id']) ? (int)$row['section_subject_id'] : 0;
                            $studentIdForGrades = $studentProfile['id'] ?? 0;
                            $gradeInfo = ($sectionId && $studentIdForGrades)
                                ? computeStudentGradeForSection($pdo, (int)$studentIdForGrades, $sectionId, $sectionSubjectId ?: null)
                                : null;
                            [$gradeDisplay, $gradeRemarks] = deriveGradeDisplayAndRemarks($gradeInfo);
                          ?>
                          <tr>
                            <td><?php echo htmlspecialchars($row['subject_code'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['subject_title'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars(formatUnitsForDisplay((float)($row['units'] ?? 0))); ?></td>
                            <td><?php echo htmlspecialchars($gradeDisplay); ?></td>
                            <td><?php echo htmlspecialchars($gradeRemarks); ?></td>
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

            </div>
          </div>
        </main>
    </div>
</body>
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<script>
(function () {
  'use strict';

  function downloadReportAsJpeg() {
    var paper = document.querySelector('.report-paper');
    if (!paper) {
      window.print();
      return;
    }

    if (typeof html2canvas === 'undefined') {
      window.print();
      return;
    }

    html2canvas(paper, {
      scale: 2,
      useCORS: true,
      backgroundColor: '#ffffff'
    }).then(function (canvas) {
      if (!canvas) {
        window.print();
        return;
      }

      if (canvas.toBlob) {
        canvas.toBlob(function (blob) {
          if (!blob) {
            triggerDownload(canvas.toDataURL('image/jpeg', 0.95));
            return;
          }
          var url = URL.createObjectURL(blob);
          triggerDownload(url, true);
        }, 'image/jpeg', 0.95);
      } else {
        var dataUrl = canvas.toDataURL('image/jpeg', 0.95);
        triggerDownload(dataUrl);
      }
    }).catch(function () {
      window.print();
    });
  }

  function triggerDownload(url, isObjectUrl) {
    var link = document.createElement('a');
    link.href = url;
    link.download = 'report-of-grades.jpg';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    if (isObjectUrl) {
      try {
        URL.revokeObjectURL(url);
      } catch (e) {}
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      var btn = document.getElementById('download-jpeg-btn');
      if (btn) {
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          downloadReportAsJpeg();
        });
      }
    });
  } else {
    var btn = document.getElementById('download-jpeg-btn');
    if (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        downloadReportAsJpeg();
      });
    }
  }
})();
</script>
<script src="../assets/js/student.js?v=20260916.1"></script>
</html>
