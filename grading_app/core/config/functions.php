<?php

// --- Log user activity ---
function add_activity_log($pdo, $user_id, $action, $details = '') {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $details, $ip]);
    } catch (Exception $e) {
        // optional: silently ignore or log to file
    }
}

//flash
function show_flash() {
    // common keys you might use
    $types = ['success', 'error', 'warning', 'info'];

    foreach ($types as $t) {
        if (isset($_SESSION['flash'][$t])) {
            $msg = $_SESSION['flash'][$t];
            unset($_SESSION['flash'][$t]);

            // adjust classes to your CSS framework
            echo '<div class="alert alert-' . $t . '">' . htmlspecialchars($msg) . '</div>';
        }
    }

    // also support a generic flash message
    if (isset($_SESSION['flash']['message'])) {
        $msg = $_SESSION['flash']['message'];
        unset($_SESSION['flash']['message']);

        echo '<div class="alert alert-info">' . htmlspecialchars($msg) . '</div>';
    }
}

if (!function_exists('syncSectionSubjects')) {
    function syncSectionSubjects(PDO $pdo, int $sectionId): void
    {
        if ($sectionId <= 0) {
            return;
        }

        $sectionStmt = $pdo->prepare("SELECT id, course_id, year_level, term_id FROM sections WHERE id = ?");
        $sectionStmt->execute([$sectionId]);
        $section = $sectionStmt->fetch(PDO::FETCH_ASSOC);
        if (!$section) {
            return;
        }

        $courseId = $section['course_id'] ?? null;
        $yearLevel = $section['year_level'] ?? null;
        $termId = $section['term_id'] ?? null;

        if (!$courseId || !$yearLevel || !$termId) {
            return;
        }

        $subjectsStmt = $pdo->prepare("SELECT id AS subject_id, term_id AS subject_term_id
                                         FROM subjects
                                        WHERE course_id = :course_id
                                          AND year_level = :year_level");
        $subjectsStmt->execute([
            ':course_id' => $courseId,
            ':year_level' => $yearLevel
        ]);
        $subjects = $subjectsStmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$subjects) {
            return;
        }

        $existsStmt = $pdo->prepare("SELECT id FROM section_subjects WHERE section_id = ? AND subject_id = ? LIMIT 1");
        $professorStmt = $pdo->prepare("SELECT id FROM professors WHERE subject_id = ? AND is_active = 1 LIMIT 2");
        $insertStmt = $pdo->prepare("INSERT INTO section_subjects (section_id, subject_id, professor_id, term_id) VALUES (?, ?, ?, ?)");

        foreach ($subjects as $subjectRow) {
            $subjectId = (int)$subjectRow['subject_id'];
            $existsStmt->execute([$sectionId, $subjectId]);
            $exists = $existsStmt->fetchColumn();
            $existsStmt->closeCursor();
            if ($exists) {
                continue;
            }

            $professorId = null;
            $professorStmt->execute([$subjectId]);
            $professors = $professorStmt->fetchAll(PDO::FETCH_COLUMN);
            $professorStmt->closeCursor();
            if (count($professors) === 1) {
                $professorId = (int)$professors[0];
            }

            $subjectTerm = $subjectRow['subject_term_id'] ?? null;
            $termForInsert = $subjectTerm ?: $termId;

            $insertStmt->execute([
                $sectionId,
                $subjectId,
                $professorId,
                $termForInsert
            ]);

            $sectionSubjectId = (int)$pdo->lastInsertId();
            if ($professorId) {
                ensureGradingSheetForSectionSubject($pdo, $sectionSubjectId);
            }
        }
    }
}

if (!function_exists('ensureGradingSheetForSectionSubject')) {
    function ensureGradingSheetForSectionSubject(PDO $pdo, int $sectionSubjectId): void
    {
        if ($sectionSubjectId <= 0) {
            return;
        }

        $ssStmt = $pdo->prepare('SELECT section_id, professor_id FROM section_subjects WHERE id = ? LIMIT 1');
        $ssStmt->execute([$sectionSubjectId]);
        $sectionSubject = $ssStmt->fetch(PDO::FETCH_ASSOC);
        if (!$sectionSubject) {
            return;
        }

        $sectionId = (int)($sectionSubject['section_id'] ?? 0);
        $professorId = $sectionSubject['professor_id'] !== null ? (int)$sectionSubject['professor_id'] : null;

        $sheetStmt = $pdo->prepare('SELECT id, professor_id FROM grading_sheets WHERE section_subject_id = ? LIMIT 1');
        $sheetStmt->execute([$sectionSubjectId]);
        $gradingSheet = $sheetStmt->fetch(PDO::FETCH_ASSOC);

        if (!$gradingSheet) {
            $insert = $pdo->prepare(
                "INSERT INTO grading_sheets (section_id, professor_id, status, deadline_at, submitted_at, section_subject_id)
                 VALUES (?, ?, 'draft', NULL, NULL, ?)"
            );
            $insert->execute([$sectionId, $professorId, $sectionSubjectId]);
            $newSheetId = (int)$pdo->lastInsertId();
            syncDefaultGradeComponentsForSheet($pdo, $newSheetId);
            return;
        }

        $currentProfessor = $gradingSheet['professor_id'] !== null ? (int)$gradingSheet['professor_id'] : null;
        if ($currentProfessor !== $professorId) {
            $update = $pdo->prepare('UPDATE grading_sheets SET professor_id = ? WHERE id = ?');
            $update->execute([$professorId, $gradingSheet['id']]);
        }

        syncDefaultGradeComponentsForSheet($pdo, (int)$gradingSheet['id']);
    }
}

if (!function_exists('removeProfessorFromGradingSheet')) {
    function removeProfessorFromGradingSheet(PDO $pdo, int $sectionId, int $professorId): void
    {
        if ($sectionId <= 0 || $professorId <= 0) {
            return;
        }

        ensureGradingSheetProfessorNullable($pdo);

        $stmt = $pdo->prepare(
            "UPDATE grading_sheets
                SET professor_id = NULL,
                    status = 'draft',
                    submitted_at = NULL
              WHERE section_id = ?
                AND professor_id = ?"
        );
        $stmt->execute([$sectionId, $professorId]);

        $sectionSubjectStmt = $pdo->prepare(
            "UPDATE section_subjects
                SET professor_id = NULL
              WHERE section_id = ?
                AND professor_id = ?"
        );
        $sectionSubjectStmt->execute([$sectionId, $professorId]);
    }
}

if (!function_exists('ensureGradingSheetProfessorNullable')) {
    function ensureGradingSheetProfessorNullable(PDO $pdo): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }

        $checked = true;

        try {
            $stmt = $pdo->prepare(
                "SELECT IS_NULLABLE
                   FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'grading_sheets'
                    AND COLUMN_NAME = 'professor_id'
                  LIMIT 1"
            );
            $stmt->execute();
            $isNullable = $stmt->fetchColumn();
            if ($isNullable === 'NO') {
                $pdo->exec('ALTER TABLE grading_sheets MODIFY professor_id INT NULL');
            }
        } catch (Throwable $e) {
            // If the metadata lookup or ALTER fails, swallow silently to avoid blocking main flow.
            try {
                $pdo->exec('ALTER TABLE grading_sheets MODIFY professor_id INT NULL');
            } catch (Throwable $ignored) {
            }
        }
    }
}

if (!function_exists('deriveSubjectStatusFromTerm')) {
    function deriveSubjectStatusFromTerm(PDO $pdo, ?int $termId, int $defaultStatus): int
    {
        if ($termId === null) {
            return $defaultStatus;
        }

        $stmt = $pdo->prepare('SELECT is_active FROM terms WHERE id = ? LIMIT 1');
        $stmt->execute([$termId]);
        $termStatus = $stmt->fetchColumn();
        if ($termStatus === false || $termStatus === null) {
            return $defaultStatus;
        }

        return (int)$termStatus === 1 ? 1 : 0;
    }
}

if (!function_exists('syncSubjectStatusesWithTerms')) {
    function syncSubjectStatusesWithTerms(PDO $pdo, ?int $termId = null): void
    {
        if ($termId) {
            $stmt = $pdo->prepare(
                "UPDATE subjects s
                    JOIN terms t ON t.id = s.term_id
                   SET s.is_active = t.is_active
                 WHERE s.term_id = ?"
            );
            $stmt->execute([$termId]);
            return;
        }

        $pdo->exec(
            "UPDATE subjects s
                JOIN terms t ON t.id = s.term_id
               SET s.is_active = t.is_active
             WHERE s.term_id IS NOT NULL"
        );
    }
}

if (!function_exists('ensureDefaultGradeTemplateStorage')) {
    function ensureDefaultGradeTemplateStorage(PDO $pdo): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS default_grade_components (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                weight DECIMAL(5,2) NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $count = (int)$pdo->query('SELECT COUNT(*) FROM default_grade_components')->fetchColumn();
        if ($count === 0) {
            $defaults = [
                ['Activity', 40.0, 1],
                ['Exam', 40.0, 2],
                ['Quizzes', 10.0, 3],
                ['Attendance', 10.0, 4],
            ];
            $insert = $pdo->prepare('INSERT INTO default_grade_components (name, weight, sort_order) VALUES (?, ?, ?)');
            foreach ($defaults as $row) {
                $insert->execute([$row[0], $row[1], $row[2]]);
            }
        }

        $ensured = true;
    }
}

if (!function_exists('defaultGradeComponentsTemplate')) {
    function defaultGradeComponentsTemplate(PDO $pdo): array
    {
        ensureDefaultGradeTemplateStorage($pdo);
        $stmt = $pdo->query('SELECT id, name, weight, sort_order FROM default_grade_components ORDER BY sort_order, id');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('normalizeGradeComponentNameKey')) {
    function normalizeGradeComponentNameKey(string $name): string
    {
        $normalized = strtolower(trim($name));
        $normalized = preg_replace('/[^a-z]/', '', $normalized ?? '');

        if (substr($normalized, -3) === 'ies') {
            $normalized = substr($normalized, 0, -3) . 'y';
        } elseif (substr($normalized, -2) === 'es') {
            $normalized = substr($normalized, 0, -2);
        } elseif (substr($normalized, -1) === 's') {
            $normalized = substr($normalized, 0, -1);
        }

        return $normalized;
    }
}

if (!function_exists('syncDefaultGradeComponentsForSheet')) {
    function syncDefaultGradeComponentsForSheet(PDO $pdo, int $gradingSheetId, ?array $template = null, bool $removeMissing = false): void
    {
        if ($gradingSheetId <= 0) {
            return;
        }

        $template = $template ?: defaultGradeComponentsTemplate($pdo);
        if (!$template) {
            return;
        }

        $existingStmt = $pdo->prepare('SELECT id, name, weight FROM grade_components WHERE grading_sheet_id = ?');
        $existingStmt->execute([$gradingSheetId]);
        $existingRows = $existingStmt->fetchAll(PDO::FETCH_ASSOC);

        $existingMap = [];
        foreach ($existingRows as $row) {
            $key = normalizeGradeComponentNameKey((string)$row['name']);
            $existingMap[$key] = $row;
        }

        $keptIds = [];
        $updateStmt = $pdo->prepare('UPDATE grade_components SET name = ?, weight = ? WHERE id = ?');
        $insertStmt = $pdo->prepare('INSERT INTO grade_components (grading_sheet_id, name, weight) VALUES (?, ?, ?)');

        foreach ($template as $component) {
            $name = $component['name'] ?? ($component[0] ?? null);
            $weight = $component['weight'] ?? ($component[1] ?? null);
            if ($name === null || $weight === null) {
                continue;
            }

            $weight = (float)$weight;
            $key = normalizeGradeComponentNameKey((string)$name);
            $existing = $existingMap[$key] ?? null;

            if ($existing) {
                $needsUpdate = false;
                if ($existing['name'] !== $name) {
                    $needsUpdate = true;
                }
                if ((float)$existing['weight'] !== $weight) {
                    $needsUpdate = true;
                }

                if ($needsUpdate) {
                    $updateStmt->execute([$name, $weight, $existing['id']]);
                }
                $keptIds[] = (int)$existing['id'];
                continue;
            }

            $insertStmt->execute([$gradingSheetId, $name, $weight]);
            $keptIds[] = (int)$pdo->lastInsertId();
        }

        if ($removeMissing && $existingRows) {
            $existingIds = array_map('intval', array_column($existingRows, 'id'));
            $removeIds = array_diff($existingIds, $keptIds);
            if ($removeIds) {
                $placeholders = implode(',', array_fill(0, count($removeIds), '?'));
                $deleteStmt = $pdo->prepare("DELETE FROM grade_components WHERE grading_sheet_id = ? AND id IN ($placeholders)");
                $deleteStmt->execute([$gradingSheetId, ...$removeIds]);
            }
        }
    }
}

if (!function_exists('convertRawGradeToEquivalent')) {
    function convertRawGradeToEquivalent(float $grade): string
    {
        if ($grade >= 97) {
            return '1.00';
        }
        if ($grade >= 94) {
            return '1.25';
        }
        if ($grade >= 91) {
            return '1.50';
        }
        if ($grade >= 88) {
            return '1.75';
        }
        if ($grade >= 85) {
            return '2.00';
        }
        if ($grade >= 82) {
            return '2.25';
        }
        if ($grade >= 79) {
            return '2.50';
        }
        if ($grade >= 76) {
            return '2.75';
        }
        if ($grade >= 75) {
            return '3.00';
        }

        return '5.00';
    }
}

if (!function_exists('computeStudentGradeForSection')) {
    function computeStudentGradeForSection(PDO $pdo, int $studentId, int $sectionId, ?int $sectionSubjectId = null): ?array
    {
        static $cache = [];
        $key = $studentId . ':' . $sectionId . ':' . ($sectionSubjectId ?? 0);
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        if ($studentId <= 0 || $sectionId <= 0) {
            $cache[$key] = null;
            return null;
        }

        if ($sectionSubjectId) {
            $sheetStmt = $pdo->prepare("SELECT id FROM grading_sheets WHERE section_subject_id = ? AND status = 'locked' ORDER BY id DESC LIMIT 1");
            $sheetStmt->execute([$sectionSubjectId]);
        } else {
            $sheetStmt = $pdo->prepare("SELECT id FROM grading_sheets WHERE section_id = ? AND status = 'locked' ORDER BY id DESC LIMIT 1");
            $sheetStmt->execute([$sectionId]);
        }
        $sheetId = (int)($sheetStmt->fetchColumn() ?: 0);
        if (!$sheetId) {
            $cache[$key] = null;
            return null;
        }

        $componentStmt = $pdo->prepare('SELECT id, weight FROM grade_components WHERE grading_sheet_id = ? ORDER BY id');
        $componentStmt->execute([$sheetId]);
        $components = $componentStmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$components) {
            $cache[$key] = null;
            return null;
        }

        $componentIds = array_column($components, 'id');
        if (!$componentIds) {
            $cache[$key] = null;
            return null;
        }

        $placeholders = implode(',', array_fill(0, count($componentIds), '?'));
        $itemStmt = $pdo->prepare("SELECT id, component_id, total_points FROM grade_items WHERE component_id IN ($placeholders) ORDER BY component_id, id");
        $itemStmt->execute($componentIds);

        $items = [];
        $componentTotals = [];
        while ($row = $itemStmt->fetch(PDO::FETCH_ASSOC)) {
            $componentId = (int)$row['component_id'];
            $items[$componentId][] = [
                'id' => (int)$row['id'],
                'total_points' => (float)$row['total_points'],
            ];
            $componentTotals[$componentId] = ($componentTotals[$componentId] ?? 0) + (float)$row['total_points'];
        }
        foreach ($componentIds as $cid) {
            if (!isset($componentTotals[$cid])) {
                $componentTotals[$cid] = 0.0;
            }
        }

        $gradeItemIds = [];
        foreach ($items as $componentItems) {
            foreach ($componentItems as $item) {
                $gradeItemIds[] = $item['id'];
            }
        }

        $gradeMap = [];
        if ($gradeItemIds) {
            $gradePlaceholders = implode(',', array_fill(0, count($gradeItemIds), '?'));
            $gradeStmt = $pdo->prepare("SELECT grade_item_id, score FROM grades WHERE student_id = ? AND grade_item_id IN ($gradePlaceholders)");
            $gradeStmt->execute(array_merge([$studentId], $gradeItemIds));
            while ($row = $gradeStmt->fetch(PDO::FETCH_ASSOC)) {
                $gradeMap[(int)$row['grade_item_id']] = $row['score'];
            }
        }

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
                if (isset($gradeMap[$itemId]) && $gradeMap[$itemId] !== '' && $gradeMap[$itemId] !== null) {
                    $earned += (float)$gradeMap[$itemId];
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

        if ($finalGradeDisplay === null && $finalGrade === null) {
            $cache[$key] = null;
            return null;
        }

        $gradeForEquivalent = $finalGradeDisplay ?? $finalGrade;
        $equivalent = $gradeForEquivalent !== null ? convertRawGradeToEquivalent($gradeForEquivalent) : null;

        $cache[$key] = [
            'sheet_id' => $sheetId,
            'final_grade' => $finalGrade,
            'final_grade_display' => $finalGradeDisplay,
            'equivalent' => $equivalent,
        ];

        return $cache[$key];
    }
}

if (!function_exists('getStudentGradingSheetBreakdown')) {
    function getStudentGradingSheetBreakdown(PDO $pdo, int $studentId, int $sectionId, ?int $sectionSubjectId = null): ?array
    {
        static $cache = [];
        $key = 'breakdown:' . $studentId . ':' . $sectionId . ':' . ($sectionSubjectId ?? 0);
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        if ($studentId <= 0 || $sectionId <= 0) {
            $cache[$key] = null;
            return null;
        }

        if ($sectionSubjectId) {
            $sheetStmt = $pdo->prepare("SELECT id FROM grading_sheets WHERE section_subject_id = ? AND status = 'locked' ORDER BY id DESC LIMIT 1");
            $sheetStmt->execute([$sectionSubjectId]);
        } else {
            $sheetStmt = $pdo->prepare("SELECT id FROM grading_sheets WHERE section_id = ? AND status = 'locked' ORDER BY id DESC LIMIT 1");
            $sheetStmt->execute([$sectionId]);
        }
        $sheetId = (int)($sheetStmt->fetchColumn() ?: 0);
        if (!$sheetId) {
            $cache[$key] = null;
            return null;
        }

        $componentStmt = $pdo->prepare('SELECT id, name, weight FROM grade_components WHERE grading_sheet_id = ? ORDER BY id');
        $componentStmt->execute([$sheetId]);
        $components = $componentStmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$components) {
            $cache[$key] = null;
            return null;
        }

        $componentIds = array_column($components, 'id');
        $placeholders = implode(',', array_fill(0, count($componentIds), '?'));
        $itemStmt = $pdo->prepare("SELECT id, component_id, title, total_points FROM grade_items WHERE component_id IN ($placeholders) ORDER BY component_id, id");
        $itemStmt->execute($componentIds);

        $items = [];
        $componentTotals = [];
        while ($row = $itemStmt->fetch(PDO::FETCH_ASSOC)) {
            $componentId = (int)$row['component_id'];
            $itemId = (int)$row['id'];
            $items[$componentId][] = [
                'id' => $itemId,
                'title' => $row['title'],
                'total_points' => (float)($row['total_points'] ?? 0),
            ];
            $componentTotals[$componentId] = ($componentTotals[$componentId] ?? 0) + (float)($row['total_points'] ?? 0);
        }
        foreach ($componentIds as $cid) {
            if (!isset($componentTotals[$cid])) {
                $componentTotals[$cid] = 0.0;
            }
        }

        $gradeItemIds = [];
        foreach ($items as $componentItems) {
            foreach ($componentItems as $item) {
                $gradeItemIds[] = $item['id'];
            }
        }

        $gradeMap = [];
        if ($gradeItemIds) {
            $gradePlaceholders = implode(',', array_fill(0, count($gradeItemIds), '?'));
            $gradeStmt = $pdo->prepare("SELECT grade_item_id, score FROM grades WHERE student_id = ? AND grade_item_id IN ($gradePlaceholders)");
            $gradeStmt->execute(array_merge([$studentId], $gradeItemIds));
            while ($row = $gradeStmt->fetch(PDO::FETCH_ASSOC)) {
                $gradeMap[(int)$row['grade_item_id']] = $row['score'];
            }
        }

        $componentData = [];
        $finalGradeTotal = 0.0;
        $weightAccumulated = 0.0;
        $finalGradeDisplayTotal = 0.0;

        foreach ($components as $component) {
            $componentId = (int)$component['id'];
            $componentWeight = (float)$component['weight'];
            $componentItems = $items[$componentId] ?? [];

            $earned = 0.0;
            $itemEntries = [];

            foreach ($componentItems as $item) {
                $itemId = $item['id'];
                $score = $gradeMap[$itemId] ?? null;
                if ($score !== null && $score !== '') {
                    $earned += (float)$score;
                }
                $itemEntries[] = [
                    'id' => $itemId,
                    'title' => $item['title'],
                    'score' => $score === null || $score === '' ? null : (float)$score,
                    'total_points' => $item['total_points'],
                ];
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

            $componentData[] = [
                'id' => $componentId,
                'name' => $component['name'],
                'weight' => $componentWeight,
                'items' => $itemEntries,
                'total_points' => $possible,
                'earned_points' => $earned,
                'final_percent' => $finalPercent,
                'final_percent_display' => $finalPercentDisplay,
            ];
        }

        $finalGradeDisplay = $finalGradeDisplayTotal > 0 ? round($finalGradeDisplayTotal, 2) : null;
        $finalGrade = null;
        if ($weightAccumulated > 0) {
            $finalGrade = round($finalGradeTotal, 2);
        } elseif ($finalGradeDisplay !== null) {
            $finalGrade = $finalGradeDisplay;
        }

        if ($finalGradeDisplay === null && $finalGrade === null) {
            $cache[$key] = null;
            return null;
        }

        $gradeForEquivalent = $finalGradeDisplay ?? $finalGrade;
        $equivalent = $gradeForEquivalent !== null ? convertRawGradeToEquivalent((float)$gradeForEquivalent) : null;

        $weightsBreakdown = array_map(static function ($component) {
            $name = $component['name'] ?? 'Component';
            $weight = rtrim(rtrim(number_format((float)($component['weight'] ?? 0), 2), '0'), '.');
            return $name . ': ' . ($weight === '' ? '0' : $weight) . '%';
        }, $components);

        $cache[$key] = [
            'sheet_id' => $sheetId,
            'components' => $componentData,
            'final_grade' => $finalGrade,
            'final_grade_display' => $finalGradeDisplay,
            'equivalent' => $equivalent,
            'weights_breakdown' => $weightsBreakdown,
        ];

        return $cache[$key];
    }
}

if (!function_exists('lookupDirectoryProfileByEmail')) {
    function lookupDirectoryProfileByEmail(PDO $pdo, string $email): ?array
    {
        $needle = trim($email);
        if ($needle === '') {
            return null;
        }

        $profStmt = $pdo->prepare('SELECT id, first_name, last_name FROM professors WHERE LOWER(ptc_email) = LOWER(?) LIMIT 1');
        $profStmt->execute([$needle]);
        $professor = $profStmt->fetch(PDO::FETCH_ASSOC);
        if ($professor) {
            return [
                'id' => (int)$professor['id'],
                'first_name' => (string)$professor['first_name'],
                'last_name' => (string)$professor['last_name'],
                'role' => 'professor',
                'table' => 'professors',
            ];
        }

        $studentStmt = $pdo->prepare('SELECT id, first_name, last_name FROM students WHERE LOWER(ptc_email) = LOWER(?) LIMIT 1');
        $studentStmt->execute([$needle]);
        $student = $studentStmt->fetch(PDO::FETCH_ASSOC);
        if ($student) {
            return [
                'id' => (int)$student['id'],
                'first_name' => (string)$student['first_name'],
                'last_name' => (string)$student['last_name'],
                'role' => 'student',
                'table' => 'students',
            ];
        }

        return null;
    }
}
