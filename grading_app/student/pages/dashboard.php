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
            sub.subject_code,
            sub.subject_title,
            COALESCE(sub.units, 0) AS units,
            prof.first_name AS prof_first_name,
            prof.middle_name AS prof_middle_name,
            prof.last_name AS prof_last_name,
            prof.schedule AS prof_schedule
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

function loadGradeStatistics(PDO $pdo, int $studentId, array $sectionIds): array
{
    if (!$sectionIds) {
        return [
            'studentAverage' => null,
            'classAverage' => null,
            'standingLabel' => null,
        ];
    }

    $placeholders = implode(',', array_fill(0, count($sectionIds), '?'));
    $sql = "
        SELECT
            g.student_id,
            SUM(g.score) AS total_score,
            SUM(gi.total_points) AS total_possible
        FROM grades g
        JOIN grade_items gi ON gi.id = g.grade_item_id
        JOIN grade_components gc ON gc.id = gi.component_id
        WHERE gc.section_id IN ($placeholders)
        GROUP BY g.student_id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_map('intval', $sectionIds));
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $perStudent = [];
    foreach ($rows as $row) {
        $sid = (int)($row['student_id'] ?? 0);
        $score = (float)($row['total_score'] ?? 0);
        $possible = (float)($row['total_possible'] ?? 0);
        if ($sid <= 0 || $possible <= 0) {
            continue;
        }
        if (!isset($perStudent[$sid])) {
            $perStudent[$sid] = ['score' => 0.0, 'possible' => 0.0];
        }
        $perStudent[$sid]['score'] += $score;
        $perStudent[$sid]['possible'] += $possible;
    }

    $studentAverage = null;
    if (isset($perStudent[$studentId]) && $perStudent[$studentId]['possible'] > 0) {
        $studentAverage = ($perStudent[$studentId]['score'] / $perStudent[$studentId]['possible']) * 100;
    }

    $classAverage = null;
    if ($perStudent) {
        $percentages = [];
        foreach ($perStudent as $data) {
            if ($data['possible'] <= 0) {
                continue;
            }
            $percentages[] = ($data['score'] / $data['possible']) * 100;
        }
        if ($percentages) {
            $classAverage = array_sum($percentages) / count($percentages);
        }
    }

    $standing = null;
    if ($studentAverage !== null && $classAverage !== null) {
        $delta = $studentAverage - $classAverage;
        if ($delta >= 5) {
            $standing = 'Above class average';
        } elseif ($delta <= -5) {
            $standing = 'Below class average';
        } else {
            $standing = 'On track with class';
        }
    } elseif ($studentAverage !== null) {
        $standing = 'Awaiting class data';
    }

    return [
        'studentAverage' => $studentAverage,
        'classAverage' => $classAverage,
        'standingLabel' => $standing,
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

    if ($subjects) {
        foreach ($subjects as &$row) {
            $row['units'] = (float)$row['units'];
            $row['professor_name'] = formatProfessorName(
                $row['prof_first_name'] ?? null,
                $row['prof_middle_name'] ?? null,
                $row['prof_last_name'] ?? null
            );

            $kpis['subjects']++;
            $kpis['units'] += $row['units'];

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
    }

    try {
        $gradeStats = loadGradeStatistics($pdo, (int)$studentProfile['id'], $sectionIds);
        if ($gradeStats['studentAverage'] !== null) {
            $kpis['gwa'] = convertPercentageToGwa($gradeStats['studentAverage']);
        }
    } catch (Exception $e) {
        // leave defaults when grade data cannot be fetched
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
            <p class="chart-title">Class Standing</p>
            <p class="chart-subtitle">Comparison of your grades versus the class average.</p>
            <?php if ($gradeStats['studentAverage'] !== null): ?>
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
                <?php
                    $studentBarHeight = (int)max(0, min(100, round($gradeStats['studentAverage'] ?? 0)));
                    $classBarHeight = (int)max(0, min(100, round($gradeStats['classAverage'] ?? 0)));
                ?>
                <div class="bar-chart" style="grid-template-columns: repeat(2, 1fr);">
                    <div class="bar-wrap">
                        <div class="bar today"
                             style="height: <?= $studentBarHeight; ?>%"
                             title="You: <?= htmlspecialchars($studentAverageDisplay); ?>"></div>
                        <div class="bar-label">You</div>
                    </div>
                    <div class="bar-wrap">
                        <div class="bar"
                             style="height: <?= $classBarHeight; ?>%"
                             title="Class: <?= htmlspecialchars($classAverageDisplay); ?>"></div>
                        <div class="bar-label">Class</div>
                    </div>
                </div>
                <div class="chart-actions">
                    <span class="text-muted"><?= htmlspecialchars($standingLabel); ?></span>
                </div>
            <?php else: ?>
                <p class="text-muted">No graded submissions yet to compute your class standing.</p>
            <?php endif; ?>
        </div>
    </main>
</div>
<script src="../assets/js/student.js"></script>
</body>
</html>
