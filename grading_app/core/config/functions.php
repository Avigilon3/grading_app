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
        }
    }
}
