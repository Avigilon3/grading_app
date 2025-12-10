<?php
require_once '../includes/init.php';
requireStudent();

function findStudentProfile(PDO $pdo, ?int $userId, string $email): ?array
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
            t.is_active AS term_is_active,
            secsub.id AS section_subject_id,
            sub.subject_code,
            sub.subject_title,
            COALESCE(sub.units, 0) AS units,
            sub.is_active AS subject_is_active,
            prof.first_name AS prof_first_name,
            prof.middle_name AS prof_middle_name,
            prof.last_name AS prof_last_name,
            prof.schedule AS prof_schedule,
            gs.id AS grading_sheet_id,
            gs.status AS grading_sheet_status
        FROM sections sec
        JOIN section_subjects secsub ON secsub.section_id = sec.id
        JOIN subjects sub ON sub.id = secsub.subject_id
        LEFT JOIN courses c ON c.id = sec.course_id
        LEFT JOIN terms t ON t.id = secsub.term_id
        LEFT JOIN professors prof ON prof.id = secsub.professor_id
        LEFT JOIN grading_sheets gs ON gs.section_subject_id = secsub.id
        WHERE sec.id IN ($placeholders)
        ORDER BY sec.section_name, sub.subject_title
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_map('intval', $sectionIds));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

function ordinalSuffix(int $number): string
{
    $abs = abs($number);
    $mod100 = $abs % 100;
    if ($mod100 >= 11 && $mod100 <= 13) {
        return $number . 'th';
    }

    switch ($abs % 10) {
        case 1:
            return $number . 'st';
        case 2:
            return $number . 'nd';
        case 3:
            return $number . 'rd';
        default:
            return $number . 'th';
    }
}

function computeSheetClassStanding(PDO $pdo, int $sheetId): array
{
    static $cache = [];
    if (array_key_exists($sheetId, $cache)) {
        return $cache[$sheetId];
    }

    $cache[$sheetId] = [];
    if ($sheetId <= 0) {
        return $cache[$sheetId];
    }

    $sheetStmt = $pdo->prepare('SELECT id, section_id, status FROM grading_sheets WHERE id = ? LIMIT 1');
    $sheetStmt->execute([$sheetId]);
    $sheet = $sheetStmt->fetch(PDO::FETCH_ASSOC);
    if (!$sheet || ($sheet['status'] ?? '') !== 'locked') {
        return $cache[$sheetId];
    }

    $sectionId = (int)($sheet['section_id'] ?? 0);
    if ($sectionId <= 0) {
        return $cache[$sheetId];
    }

    $componentsStmt = $pdo->prepare('SELECT id, weight FROM grade_components WHERE grading_sheet_id = ? ORDER BY id');
    $componentsStmt->execute([$sheetId]);
    $components = $componentsStmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$components) {
        return $cache[$sheetId];
    }

    $componentIds = array_column($components, 'id');
    $placeholders = implode(',', array_fill(0, count($componentIds), '?'));
    $itemStmt = $pdo->prepare("SELECT id, component_id, total_points FROM grade_items WHERE component_id IN ($placeholders) ORDER BY component_id, id");
    $itemStmt->execute($componentIds);

    $items = [];
    $componentTotals = [];
    while ($row = $itemStmt->fetch(PDO::FETCH_ASSOC)) {
        $componentId = (int)$row['component_id'];
        $itemId = (int)$row['id'];
        $totalPoints = (float)($row['total_points'] ?? 0);
        $items[$componentId][] = [
            'id' => $itemId,
            'total_points' => $totalPoints,
        ];
        $componentTotals[$componentId] = ($componentTotals[$componentId] ?? 0) + $totalPoints;
    }
    foreach ($componentIds as $cid) {
        if (!isset($componentTotals[$cid])) {
            $componentTotals[$cid] = 0.0;
        }
    }

    $studentStmt = $pdo->prepare('SELECT student_id FROM section_students WHERE section_id = ?');
    $studentStmt->execute([$sectionId]);
    $studentIds = array_map('intval', $studentStmt->fetchAll(PDO::FETCH_COLUMN));
    if (!$studentIds) {
        return $cache[$sheetId];
    }

    $gradeItemIds = [];
    foreach ($items as $componentItems) {
        foreach ($componentItems as $item) {
            $gradeItemIds[] = $item['id'];
        }
    }

    $gradeMap = [];
    if ($gradeItemIds) {
        $studentPlaceholders = implode(',', array_fill(0, count($studentIds), '?'));
        $itemPlaceholders = implode(',', array_fill(0, count($gradeItemIds), '?'));
        $gradeStmt = $pdo->prepare(
            "SELECT student_id, grade_item_id, score
               FROM grades
              WHERE student_id IN ($studentPlaceholders)
                AND grade_item_id IN ($itemPlaceholders)"
        );
        $gradeStmt->execute([...$studentIds, ...$gradeItemIds]);
        while ($row = $gradeStmt->fetch(PDO::FETCH_ASSOC)) {
            $sid = (int)$row['student_id'];
            $gradeMap[$sid][(int)$row['grade_item_id']] = $row['score'];
        }
    }

    $studentSummaries = [];
    foreach ($studentIds as $sid) {
        $finalGradeTotal = 0.0;
        $weightAccumulated = 0.0;
        $finalGradeDisplayTotal = 0.0;

        foreach ($components as $component) {
            $componentId = (int)$component['id'];
            $componentWeight = (float)$component['weight'];
            $componentItems = $items[$componentId] ?? [];

            $earned = 0.0;
            foreach ($componentItems as $item) {
                $itemId = $item['id'];
                if (isset($gradeMap[$sid][$itemId]) && $gradeMap[$sid][$itemId] !== '' && $gradeMap[$sid][$itemId] !== null) {
                    $earned += (float)$gradeMap[$sid][$itemId];
                }
            }

            $possible = $componentTotals[$componentId] ?? 0.0;
            $percent = $possible > 0 ? ($earned / $possible) : null;
            $finalPercent = $percent !== null ? $percent * $componentWeight : null;
            $finalPercentDisplay = $finalPercent;
            if ($finalPercentDisplay === null && empty($componentItems)) {
                $finalPercentDisplay = $componentWeight;
            }

            if ($finalPercent !== null) {
                $finalGradeTotal += $finalPercent;
                $weightAccumulated += $componentWeight;
            }
            if ($finalPercentDisplay !== null) {
                $finalGradeDisplayTotal += $finalPercentDisplay;
            }
        }

        $finalGradeDisplay = $finalGradeDisplayTotal > 0 ? round($finalGradeDisplayTotal, 2) : null;
        $finalGrade = null;
        if ($weightAccumulated > 0) {
            $finalGrade = round($finalGradeTotal, 2);
        } elseif ($finalGradeDisplay !== null) {
            $finalGrade = $finalGradeDisplay;
        }

        $gradeForEquivalent = $finalGradeDisplay ?? $finalGrade;
        $equivalent = $gradeForEquivalent !== null ? convertRawGradeToEquivalent((float)$gradeForEquivalent) : null;

        $studentSummaries[$sid] = [
            'final_grade' => $finalGrade,
            'final_grade_display' => $finalGradeDisplay,
            'equivalent' => $equivalent,
        ];
    }

    $rankingData = [];
    foreach ($studentSummaries as $sid => $summary) {
        $score = $summary['final_grade_display'] ?? $summary['final_grade'];
        if ($score !== null) {
            $rankingData[] = ['student_id' => $sid, 'score' => (float)$score];
        }
    }

    usort($rankingData, static function ($a, $b) {
        if ($a['score'] === $b['score']) {
            return 0;
        }
        return ($a['score'] < $b['score']) ? 1 : -1;
    });

    $rankings = [];
    $position = 0;
    $currentRank = 0;
    $previousScore = null;
    foreach ($rankingData as $row) {
        $position++;
        if ($previousScore === null || abs($row['score'] - $previousScore) > 0.009) {
            $currentRank = $position;
            $previousScore = $row['score'];
        }
        $rankings[$row['student_id']] = $currentRank;
    }

    $classAverage = null;
    if ($rankingData) {
        $sum = array_sum(array_column($rankingData, 'score'));
        $classAverage = $sum / count($rankingData);
    }

    $cache[$sheetId] = [
        'students' => $studentSummaries,
        'ranks' => $rankings,
        'class_average' => $classAverage,
        'population' => count($studentIds),
    ];

    return $cache[$sheetId];
}

function buildClassStandingSummary(PDO $pdo, int $studentId, array $subjects): array
{
    $rows = [];
    $chartData = [];
    $studentTotals = [];
    $classTotals = [];
    $hasLockedSheet = false;

    foreach ($subjects as $subject) {
        $subjectTitle = $subject['subject_title'] ?? 'Untitled Subject';
        $sheetId = isset($subject['grading_sheet_id']) ? (int)$subject['grading_sheet_id'] : 0;
        $sheetStatus = $subject['grading_sheet_status'] ?? null;

        $entry = [
            'subject' => $subjectTitle,
            'rank_label' => 'N/A',
            'grade_display' => 'N/A',
            'rank' => null,
            'population' => null,
            'equivalent_value' => null,
        ];

        if ($sheetId > 0 && $sheetStatus === 'locked') {
            $hasLockedSheet = true;
            $standing = computeSheetClassStanding($pdo, $sheetId);
            if ($standing) {
                $population = $standing['population'] ?? null;
                $entry['population'] = $population;

                $studentSummary = $standing['students'][$studentId] ?? null;
                $gradeValue = null;
                if ($studentSummary) {
                    $gradeValue = $studentSummary['final_grade_display'] ?? $studentSummary['final_grade'];
                    if (!empty($studentSummary['equivalent'])) {
                        $entry['grade_display'] = $studentSummary['equivalent'];
                        $entry['equivalent_value'] = is_numeric($studentSummary['equivalent'])
                            ? (float)$studentSummary['equivalent']
                            : null;
                    } elseif ($gradeValue !== null) {
                        $entry['grade_display'] = number_format((float)$gradeValue, 2) . '%';
                    }
                }

                $rank = $standing['ranks'][$studentId] ?? null;
                if ($rank !== null && $population) {
                    $entry['rank'] = $rank;
                    $entry['rank_label'] = ordinalSuffix($rank) . ' / ' . $population;
                } elseif ($population) {
                    $entry['rank_label'] = 'Pending';
                }

                $classAverage = $standing['class_average'] ?? null;
                if ($gradeValue !== null) {
                    $studentTotals[] = $gradeValue;
                }
                if ($classAverage !== null) {
                    $classTotals[] = $classAverage;
                }

                if ($gradeValue !== null || $classAverage !== null) {
                    $chartData[] = [
                        'label' => $subjectTitle,
                        'you' => $gradeValue,
                        'class' => $classAverage,
                    ];
                }
            }
        }

        $rows[] = $entry;
    }

    $studentAverage = $studentTotals ? array_sum($studentTotals) / count($studentTotals) : null;
    $classAverage = $classTotals ? array_sum($classTotals) / count($classTotals) : null;

    $standingLabel = 'Standing data not available yet.';
    if ($studentAverage !== null && $classAverage !== null) {
        $delta = $studentAverage - $classAverage;
        if ($delta >= 5) {
            $standingLabel = 'Above class average';
        } elseif ($delta <= -5) {
            $standingLabel = 'Below class average';
        } else {
            $standingLabel = 'On track with class';
        }
    } elseif ($studentAverage !== null) {
        $standingLabel = 'Awaiting class data';
    } elseif ($hasLockedSheet) {
        $standingLabel = 'Waiting for your scores to be finalized.';
    }

    return [
        'rows' => $rows,
        'chart' => $chartData,
        'hasLocked' => $hasLockedSheet,
        'gradeStats' => [
            'studentAverage' => $studentAverage,
            'classAverage' => $classAverage,
            'standingLabel' => $standingLabel,
        ],
    ];
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

function formatPercentage(?float $value): string
{
    if ($value === null) {
        return '--';
    }
    return number_format($value, 1) . '%';
}

$currentUserId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
$currentEmail = $_SESSION['user']['email'] ?? '';
$studentProfile = null;
$subjects = [];
$sectionSummary = [];
$chartBars = [];
$sectionIds = [];
$standingRows = [];
$standingChart = [];
$hasStandingData = false;
$gradeStats = [
    'studentAverage' => null,
    'classAverage' => null,
    'standingLabel' => null,
];
$kpis = [
    'subjects' => 0,
    'gwa' => null,
    'units' => 0.0,
    'attendance' => null,
];
$errorMessage = null;

try {
    if ($currentUserId || $currentEmail !== '') {
        $studentProfile = findStudentProfile($pdo, $currentUserId, $currentEmail);
    }
} catch (Exception $e) {
    $errorMessage = 'Unable to load your student profile right now.';
}

if (!$studentProfile && !$errorMessage) {
    $errorMessage = 'We could not find a student record linked to your account yet.';
}

if ($studentProfile) {
    try {
        $sectionIds = getStudentSectionIds($pdo, (int)$studentProfile['id'], $studentProfile['section'] ?? null);
        $subjects = $sectionIds ? loadSubjectsForSections($pdo, $sectionIds) : [];
    } catch (Exception $e) {
        $errorMessage = 'We could not load your subjects right now.';
        $subjects = [];
    }

    $subjectsForStanding = [];
    if ($subjects) {
        foreach ($subjects as &$row) {
            $row['units'] = (float)$row['units'];
            $row['professor_name'] = formatProfessorName(
                $row['prof_first_name'] ?? null,
                $row['prof_middle_name'] ?? null,
                $row['prof_last_name'] ?? null
            );

            $subjectIsActive = !isset($row['subject_is_active']) || (int)$row['subject_is_active'] === 1;
            $termFlag = $row['term_is_active'] ?? null;
            $termIsActive = $termFlag === null ? true : ((int)$termFlag === 1);
            $row['is_enrolled_subject'] = $subjectIsActive && $termIsActive;

            if ($row['is_enrolled_subject']) {
                $enrolledSubjects[] = $row;
                $kpis['subjects']++;
                $kpis['units'] += $row['units'];
            }

            $sectionKey = $row['section_name'] ?: ($studentProfile['section'] ?? 'Unassigned');
            if (!isset($sectionSummary[$sectionKey])) {
                $sectionSummary[$sectionKey] = [
                    'subjects' => 0,
                    'units' => 0.0,
                    'term' => $row['term_name'] ?? null,
                ];
            }
            $sectionSummary[$sectionKey]['subjects']++;
            $sectionSummary[$sectionKey]['units'] += $row['units'];
        }
        unset($row);

        foreach ($sectionSummary as $label => $info) {
            $chartBars[] = [
                'label' => $label,
                'subjects' => $info['subjects'],
                'units' => $info['units'],
                'term' => $info['term'],
            ];
        }
        if ($chartBars) {
            usort($chartBars, function ($a, $b) {
                return strcmp($a['label'], $b['label']);
            });
        }

        $subjectsForStanding = $enrolledSubjects;
    }

    try {
        $standingSummary = buildClassStandingSummary($pdo, (int)$studentProfile['id'], $subjectsForStanding);
        $standingRows = $standingSummary['rows'];
        $standingChart = $standingSummary['chart'];
        $hasStandingData = $standingSummary['hasLocked'];
        $gradeStats = $standingSummary['gradeStats'];
    } catch (Exception $e) {
        $standingRows = [];
        $standingChart = [];
    }

    $equivalentSum = 0.0;
    $equivalentCount = 0;
    foreach ($standingRows as $row) {
        if (isset($row['equivalent_value']) && $row['equivalent_value'] !== null) {
            $equivalentSum += (float)$row['equivalent_value'];
            $equivalentCount++;
        }
    }
    if ($equivalentCount > 0) {
        $kpis['gwa'] = number_format($equivalentSum / $equivalentCount, 2);
    }

    $attendanceSamples = [];
    foreach ($subjectsForStanding as $subjectRow) {
        $sectionId = isset($subjectRow['section_id']) ? (int)$subjectRow['section_id'] : 0;
        if ($sectionId <= 0) {
            continue;
        }
        $sectionSubjectId = isset($subjectRow['section_subject_id']) ? (int)$subjectRow['section_subject_id'] : 0;
        try {
            $breakdown = getStudentGradingSheetBreakdown(
                $pdo,
                (int)$studentProfile['id'],
                $sectionId,
                $sectionSubjectId ?: null
            );
        } catch (Throwable $e) {
            continue;
        }
        if (!$breakdown || empty($breakdown['components'])) {
            continue;
        }
        foreach ($breakdown['components'] as $component) {
            $normalized = normalizeGradeComponentNameKey((string)($component['name'] ?? ''));
            if ($normalized !== 'attendance') {
                continue;
            }
            $attendancePercent = null;
            $totalPoints = (float)($component['total_points'] ?? 0);
            $earnedPoints = (float)($component['earned_points'] ?? 0);
            if ($totalPoints > 0) {
                $attendancePercent = ($earnedPoints / $totalPoints) * 100;
            } elseif (isset($component['final_percent']) && $component['final_percent'] !== null) {
                $attendancePercent = (float)$component['final_percent'];
            }
            if ($attendancePercent !== null) {
                $attendanceSamples[] = $attendancePercent;
            }
            break;
        }
    }
    if ($attendanceSamples) {
        $kpis['attendance'] = array_sum($attendanceSamples) / count($attendanceSamples);
    }
}

$primarySectionName = trim((string)($studentProfile['section'] ?? ''));
$yearLevelLabel = $studentProfile ? formatYearLevel($studentProfile['year_level'] ?? null) : null;
$unitsDisplay = formatUnitsDisplay((float)$kpis['units']);
$gwaDisplay = $kpis['gwa'] ?? '--';
$attendanceDisplay = $kpis['attendance'] !== null ? number_format((float)$kpis['attendance'], 1) . '%' : '--';
$studentAverageDisplay = formatPercentage($gradeStats['studentAverage']);
$classAverageDisplay = formatPercentage($gradeStats['classAverage']);
$standingLabel = $gradeStats['standingLabel'] ?? 'Standing data not available yet.';
$chartMaxSubjects = 0;
foreach ($chartBars as $bar) {
    if ($bar['subjects'] > $chartMaxSubjects) {
        $chartMaxSubjects = $bar['subjects'];
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="../assets/css/student.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="content">
        <?php show_flash(); ?>
        <?php if ($errorMessage): ?>
            <div class="alert alert-warning"><?= htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <div class="page-header">
            <h1>Dashboard</h1>
            <p class="text-muted">
                Welcome back, <?= htmlspecialchars(currentUserName()); ?>.
                <?php if ($yearLevelLabel): ?>
                    You're currently tagged as a <?= htmlspecialchars($yearLevelLabel); ?> student of Pateros Technological College.
                <?php endif; ?>
            </p>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label">Total Subjects</div>
                <div class="kpi-value"><?= (int)$kpis['subjects']; ?></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Current GWA</div>
                <div class="kpi-value"><?= htmlspecialchars($gwaDisplay); ?></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Units Enrolled</div>
                <div class="kpi-value"><?= htmlspecialchars($unitsDisplay); ?></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Attendance Rate</div>
                <div class="kpi-value"><?= htmlspecialchars($attendanceDisplay); ?></div>
            </div>
        </div>

        <div class="chart-card performance-card">
            <p class="chart-title">Your Class Standing</p>
            <p class="chart-subtitle">We surface results as soon as professors lock their grading sheets.</p>
            <?php if ($hasStandingData): ?>
                <?php if ($gradeStats['studentAverage'] !== null || $gradeStats['classAverage'] !== null): ?>
                    <div class="chart-header">
                        <div>
                            <div class="chart-total"><?= htmlspecialchars($studentAverageDisplay); ?></div>
                            <div class="chart-delta">My Average</div>
                        </div>
                        <div>
                            <div class="chart-total"><?= htmlspecialchars($classAverageDisplay); ?></div>
                            <div class="chart-delta">Class Average</div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="class-standing-grid">
                    <div class="standing-box">
                        <h3>Overall Standing</h3>
                        <p class="chart-subtitle">Subjects without a final equivalent yet are marked N/A.</p>
                        <?php if ($standingRows): ?>
                            <div class="standing-table-wrapper">
                                <table class="standing-table">
                                    <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Rank</th>
                                        <th>Grade</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($standingRows as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['subject']); ?></td>
                                            <td>
                                                <?php if (!empty($row['rank_label']) && $row['rank_label'] !== 'N/A'): ?>
                                                    <span class="rank-label"><?= htmlspecialchars($row['rank_label']); ?></span>
                                                <?php else: ?>
                                                    <span class="rank-empty">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($row['grade_display'] ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No subjects to list yet.</p>
                        <?php endif; ?>
                    </div>
                    <div class="standing-box">
                        <h3>You vs The Class Average Grade</h3>
                        <?php if ($standingChart): ?>
                            <div class="standing-chart-legend">
                                <span><span class="dot dot-you"></span>You</span>
                                <span><span class="dot dot-class"></span>Class Average</span>
                            </div>
                            <div class="standing-chart">
                                <?php foreach ($standingChart as $bar): ?>
                                    <?php
                                        $youValue = $bar['you'];
                                        $classValue = $bar['class'];
                                        $youHeight = $youValue !== null ? max(0, min(100, round($youValue))) : 0;
                                        $classHeight = $classValue !== null ? max(0, min(100, round($classValue))) : 0;
                                    ?>
                                    <div class="subject-bars" title="<?= htmlspecialchars($bar['label']); ?>">
                                        <div class="bar-track">
                                            <div class="bar-col you<?= $youValue === null ? ' empty' : ''; ?>" style="height: <?= $youHeight; ?>%"></div>
                                            <div class="bar-col class<?= $classValue === null ? ' empty' : ''; ?>" style="height: <?= $classHeight; ?>%"></div>
                                        </div>
                                        <div class="bar-caption"><?= htmlspecialchars($bar['label']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Waiting for class average data.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="chart-actions">
                    <span class="text-muted"><?= htmlspecialchars($standingLabel); ?></span>
                </div>
            <?php else: ?>
                <p class="text-muted">No locked grading sheets yet to compute your class standing.</p>
            <?php endif; ?>
        </div>
    </main>
</div>
<script src="../assets/js/student.js"></script>
</body>
</html>
