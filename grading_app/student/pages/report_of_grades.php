<?php
require_once '../includes/init.php';
requireStudent();

function formatYearLevel(?string $year): ?string
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

function formatUnitsDisplay(float $value): string
{
    return (abs($value - round($value)) < 0.01) ? (string)round($value) : number_format($value, 1);
}

function formatPercentage(?float $value): string
{
    if ($value === null) {
        return '--';
    }
    return number_format($value, 1) . '%';
}

function convertPercentageToGwa(?float $percentage): ?string
{
    if ($percentage === null) {
        return null;
    }
    $scale = [
        96 => '1.00',
        93 => '1.25',
        90 => '1.50',
        87 => '1.75',
        84 => '2.00',
        81 => '2.25',
        78 => '2.50',
        75 => '2.75',
        72 => '3.00',
        69 => '3.25',
        66 => '3.50',
        63 => '3.75',
        60 => '4.00',
    ];
    foreach ($scale as $threshold => $gwa) {
        if ($percentage >= $threshold) {
            return $gwa;
        }
    }
    return '5.00';
}

function formatProfessorName(?string $first, ?string $middle, ?string $last): string
{
    $parts = [];
    foreach ([$first, $middle, $last] as $piece) {
        $piece = trim((string)$piece);
        if ($piece !== '') {
            $parts[] = $piece;
        }
    }
    return $parts ? implode(' ', $parts) : 'TBA';
}

function getStudentSectionIds(PDO $pdo, int $studentId, ?string $sectionName = null): array
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

function loadSubjectsForSections(PDO $pdo, array $sectionIds): array
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
            sub.subject_code,
            sub.subject_title,
            COALESCE(sub.units, 0) AS units,
            prof.first_name AS prof_first_name,
            prof.middle_name AS prof_middle_name,
            prof.last_name AS prof_last_name
        FROM sections sec
        JOIN section_subjects secsub ON secsub.section_id = sec.id
        JOIN subjects sub ON sub.id = secsub.subject_id
        LEFT JOIN courses c ON c.id = sec.course_id
        LEFT JOIN terms t ON t.id = secsub.term_id
        LEFT JOIN professors prof ON prof.id = secsub.professor_id
        WHERE sec.id IN ($placeholders)
        ORDER BY sec.section_name, sub.subject_title
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_map('intval', $sectionIds));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$currentUserId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
$currentEmail = $_SESSION['user']['email'] ?? '';
$student = null;
$subjects = [];
$sectionIds = [];
$gradeBySection = [];
$metrics = [
    'subjects' => 0,
    'units' => 0.0,
    'average' => null,
    'gwa' => null,
];
$error = null;

try {
    if ($currentUserId) {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = :uid LIMIT 1");
        $stmt->execute([':uid' => $currentUserId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    if (!$student && $currentEmail) {
        $stmt = $pdo->prepare("SELECT s.* FROM students s LEFT JOIN users u ON u.id = s.user_id WHERE s.ptc_email = :em OR u.email = :em LIMIT 1");
        $stmt->execute([':em' => $currentEmail]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $error = 'Unable to load your student record right now.';
}

if ($student) {
    try {
        $sectionIds = getStudentSectionIds($pdo, (int)$student['id'], $student['section'] ?? null);
        $subjects = $sectionIds ? loadSubjectsForSections($pdo, $sectionIds) : [];
    } catch (Exception $e) {
        $error = 'We could not load your subjects right now.';
    }
}

if ($subjects) {
    foreach ($subjects as &$row) {
        $row['units'] = (float)($row['units'] ?? 0);
        $row['professor_name'] = formatProfessorName(
            $row['prof_first_name'] ?? null,
            $row['prof_middle_name'] ?? null,
            $row['prof_last_name'] ?? null
        );
        $metrics['subjects']++;
        $metrics['units'] += $row['units'];
    }
    unset($row);
}

if ($student && $sectionIds) {
    $placeholders = implode(',', array_fill(0, count($sectionIds), '?'));
    $gradeSql = "
        SELECT gc.section_id,
               SUM(COALESCE(g.score, 0)) AS total_score,
               SUM(gi.total_points) AS total_possible
          FROM grade_components gc
          JOIN grade_items gi ON gi.component_id = gc.id
     LEFT JOIN grades g ON g.grade_item_id = gi.id AND g.student_id = ?
         WHERE gc.section_id IN ($placeholders)
      GROUP BY gc.section_id
    ";
    $gradeStmt = $pdo->prepare($gradeSql);
    $gradeStmt->execute(array_merge([(int)$student['id']], $sectionIds));
    while ($row = $gradeStmt->fetch(PDO::FETCH_ASSOC)) {
        $possible = (float)($row['total_possible'] ?? 0);
        $score = (float)($row['total_score'] ?? 0);
        $percent = $possible > 0 ? ($score / $possible) * 100 : null;
        $gradeBySection[(int)$row['section_id']] = [
            'percent' => $percent,
            'gwa' => convertPercentageToGwa($percent),
        ];
    }

    $percents = array_values(array_filter(array_column($gradeBySection, 'percent'), static fn($v) => $v !== null));
    if ($percents) {
        $metrics['average'] = array_sum($percents) / count($percents);
        $metrics['gwa'] = convertPercentageToGwa($metrics['average']);
    }
}

$unitsDisplay = formatUnitsDisplay((float)$metrics['units']);
$avgDisplay = formatPercentage($metrics['average']);
$gwaDisplay = $metrics['gwa'] ?? '--';
$yearLabel = $student ? formatYearLevel($student['year_level'] ?? null) : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report of Grades</title>
    <link rel="stylesheet" href="../assets/css/student.css">
    <style>
      .rog-page { display: flex; flex-direction: column; gap: 18px; }
      .rog-hero { border-radius: 16px; background: linear-gradient(120deg, #10375c, #0f5132); color: #ecf4ff; padding: 20px; box-shadow: 0 20px 50px rgba(12,23,42,0.35); }
      .rog-hero h1 { margin: 6px 0 4px; font-size: 26px; letter-spacing: -0.02em; }
      .rog-hero p { margin: 4px 0 0; color: rgba(236,244,255,0.9); }
      .rog-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap: 12px; margin-top: 12px; }
      .rog-stat { background: #fff; border: 1px solid #e6e9f0; border-radius: 12px; padding: 12px; box-shadow: 0 12px 30px rgba(15,23,42,0.08); }
      .rog-stat-label { color: #4b5563; font-size: 13px; margin: 0; }
      .rog-stat-value { margin: 4px 0 0; font-size: 22px; font-weight: 800; color: #0f172a; }
      .rog-card { background: #fff; border: 1px solid #e6e9f0; border-radius: 12px; padding: 16px; box-shadow: 0 10px 28px rgba(15,23,42,0.08); }
      .rog-table { width: 100%; border-collapse: collapse; }
      .rog-table th, .rog-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
      .rog-table th { background: #f8fafc; font-weight: 700; color: #0f172a; }
      .rog-chip { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 999px; background: #e8f3ff; color: #0f3d2d; font-weight: 700; font-size: 12px; }
      .rog-badge { display: inline-flex; padding: 6px 10px; border-radius: 10px; font-weight: 700; font-size: 12px; }
      .rog-badge.pass { background: #ecfdf3; color: #166534; }
      .rog-badge.progress { background: #fff7ed; color: #b45309; }
      .rog-badge.risk { background: #fef2f2; color: #b91c1c; }
      .rog-note { margin-top: 10px; font-size: 13px; color: #4b5563; }
      @media (max-width: 720px) {
        .rog-table th:nth-child(3), .rog-table td:nth-child(3) { display: none; }
      }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <div class="layout">
        <?php include '../includes/sidebar.php'; ?>
        <main class="content">
            <?php show_flash(); ?>
            <?php if ($error): ?>
              <div class="alert alert-warning"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="rog-page">
              <div class="rog-hero">
                <p style="letter-spacing:0.08em;text-transform:uppercase;font-size:12px;margin:0;">Academic Records</p>
                <h1>Report of Grades</h1>
                <p>See your subjects, unit load, and recorded scores submitted by your professors.</p>
                <div class="rog-stats">
                  <div class="rog-stat">
                    <p class="rog-stat-label">Total Subjects</p>
                    <p class="rog-stat-value"><?= (int)$metrics['subjects']; ?></p>
                  </div>
                  <div class="rog-stat">
                    <p class="rog-stat-label">Units Enrolled</p>
                    <p class="rog-stat-value"><?= htmlspecialchars($unitsDisplay); ?></p>
                  </div>
                  <div class="rog-stat">
                    <p class="rog-stat-label">Average</p>
                    <p class="rog-stat-value"><?= htmlspecialchars($avgDisplay); ?></p>
                  </div>
                  <div class="rog-stat">
                    <p class="rog-stat-label">Estimated GWA</p>
                    <p class="rog-stat-value"><?= htmlspecialchars($gwaDisplay); ?></p>
                  </div>
                </div>
              </div>

              <div class="rog-card">
                <div class="card-header-actions" style="margin-bottom:12px;">
                  <div>
                    <h2 style="margin:0;">My Subjects</h2>
                    <?php if ($yearLabel): ?>
                      <p class="text-muted" style="margin:4px 0 0;"><?= htmlspecialchars($yearLabel); ?> - Enrolled sections</p>
                    <?php endif; ?>
                  </div>
                  <span class="rog-chip"><?= (int)$metrics['subjects']; ?> subjects</span>
                </div>
                <table class="rog-table">
                  <thead>
                    <tr>
                      <th>Subject</th>
                      <th>Section</th>
                      <th>Professor</th>
                      <th>Units</th>
                      <th>Grade</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!$subjects): ?>
                      <tr><td colspan="6">No subjects on record yet.</td></tr>
                    <?php else: ?>
                      <?php foreach ($subjects as $sub): ?>
                        <?php
                          $sectionId = (int)($sub['section_id'] ?? 0);
                          $gradeInfo = $gradeBySection[$sectionId] ?? ['percent' => null, 'gwa' => null];
                          $percent = $gradeInfo['percent'];
                          $percentDisplay = formatPercentage($percent);
                          $gwa = $gradeInfo['gwa'] ?? '--';
                          $statusClass = 'progress';
                          $statusLabel = 'In progress';
                          if ($percent !== null) {
                              if ($percent >= 75) {
                                  $statusClass = 'pass';
                                  $statusLabel = 'Passed';
                              } elseif ($percent < 70) {
                                  $statusClass = 'risk';
                                  $statusLabel = 'At risk';
                              }
                          }
                        ?>
                        <tr>
                          <td>
                            <div><?= htmlspecialchars(($sub['subject_code'] ?? '') . ' - ' . ($sub['subject_title'] ?? '')); ?></div>
                            <div class="req-small"><?= htmlspecialchars($sub['course_code'] ?? ''); ?></div>
                          </td>
                          <td><?= htmlspecialchars($sub['section_name'] ?? '--'); ?></td>
                          <td><?= htmlspecialchars($sub['professor_name'] ?? 'TBA'); ?></td>
                          <td><?= htmlspecialchars(formatUnitsDisplay((float)($sub['units'] ?? 0))); ?></td>
                          <td>
                            <div><?= htmlspecialchars($percentDisplay); ?></div>
                            <div class="req-small">GWA: <?= htmlspecialchars($gwa); ?></div>
                          </td>
                          <td><span class="rog-badge <?= $statusClass; ?>"><?= htmlspecialchars($statusLabel); ?></span></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
                <div class="rog-note">Grades are based on submitted grading sheets. If something looks off, request a verification from your professor.</div>
              </div>
            </div>
        </main>

    </div>

    
</body>
</html>
