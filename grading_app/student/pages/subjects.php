<?php
require_once '../includes/init.php';
requireStudent();

// 1. Get the current logged-in student's ID (from the students table)
$user_id = $_SESSION['user']['id'];
$student_id = getStudentIDByUserID($pdo, $user_id);

// If the student ID isn't found, stop and display an error.
if (!$student_id) {
    echo "Error: Student information not found.";
    exit;
}

// 2. Function to get the student's subjects (Enrolled and Archived)
function getStudentSubjects(PDO $pdo, int $student_id): array
{

    $sql = "
        SELECT
            t.term_name,
            t.is_active AS term_is_active,
            s.subject_code,
            s.subject_title,
            s.is_active AS subject_is_active,
            sec.section_name,
            sec.id AS section_id,
            ssub.id AS section_subject_id,
            CONCAT(p.first_name, ' ', p.last_name) AS professor_name
        FROM
            section_students ss
        INNER JOIN section_subjects ssub ON ss.section_id = ssub.section_id
        INNER JOIN sections sec ON ss.section_id = sec.id
        INNER JOIN subjects s ON ssub.subject_id = s.id
        LEFT JOIN terms t ON ssub.term_id = t.id
        LEFT JOIN professors p ON ssub.professor_id = p.id
        WHERE
            ss.student_id = :student_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['student_id' => $student_id]);
    return $stmt->fetchAll();
}

// Helper to get the student's ID (from the students table) using their logged-in user ID (from the users table)
function getStudentIDByUserID(PDO $pdo, int $user_id): ?int
{
    $sql = "SELECT id FROM students WHERE user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    $result = $stmt->fetchColumn();
    return $result !== false ? (int)$result : null;
}

// 3. Fetch all subjects and categorize them
$all_subjects = getStudentSubjects($pdo, $student_id);

$enrolled_subjects = [];
$archived_subjects = [];

foreach ($all_subjects as $subject) {
    $subjectIsActive = isset($subject['subject_is_active']) ? (int)$subject['subject_is_active'] === 1 : true;
    $termIsActive = isset($subject['term_is_active']) ? (int)$subject['term_is_active'] === 1 : false;

    if ($subjectIsActive && $termIsActive) {
        $enrolled_subjects[] = $subject;
    } else {
        $archived_subjects[] = $subject;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subjects</title>
    <link rel="stylesheet" href="../assets/css/student.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <div class="layout">
        <?php include '../includes/sidebar.php'; ?>
        <main class="content">
            <div class="page-header">
              <h1>Subjects</h1>
              <p class="text-muted">
                View all your enrolled subjects and current grades.
              </p>
            </div>

            <div class="search-subjects">
              <span class="material-symbols-rounded">search</span>
              <input type="text" placeholder="Search Subjects">
            </div>

            <div class="display-subjects">
              <div class="display-header">
                <h2>Enrolled Subjects</h2>
              </div>
              
              <table class="subjects-table">
                <thead>
                  <tr>
                    <td>Subject Name</td>
                    <td>Professor</td>
                    <td>Grade</td>
                    <td>Action</td>
                  </tr>
                </thead>
                <tbody>
                  <!-- select all enrolled subjects for the user -->
                    <?php if (empty($enrolled_subjects)): ?>
                        <tr>
                            <td colspan="5">No subjects currently enrolled in an active term.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($enrolled_subjects as $subject): ?>
                        <?php
                            $sectionId = isset($subject['section_id']) ? (int)$subject['section_id'] : 0;
                            $sectionSubjectId = isset($subject['section_subject_id']) ? (int)$subject['section_subject_id'] : 0;
                            $gradeInfo = $sectionId ? computeStudentGradeForSection($pdo, $student_id, $sectionId, $sectionSubjectId ?: null) : null;
                            if ($gradeInfo) {
                                if (!empty($gradeInfo['equivalent'])) {
                                    $gradeDisplay = $gradeInfo['equivalent'];
                                } elseif ($gradeInfo['final_grade_display'] !== null) {
                                    $gradeDisplay = number_format((float)$gradeInfo['final_grade_display'], 2) . '%';
                                } else {
                                    $gradeDisplay = 'Pending';
                                }
                            } else {
                                $gradeDisplay = 'Pending';
                            }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($subject['subject_title']); ?></td>
                            <td><?php echo htmlspecialchars($subject['professor_name'] ?: 'TBA'); ?></td>
                            <td><?php echo htmlspecialchars($gradeDisplay); ?></td>
                            <td><a href="#" class="action-link">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
              </table>
            </div>

<!-- Archived -->
            <div class="display-subjects">
               <div class="display-header">
                <h2>Archived Subjects</h2>              
              </div>
              <table class="subjects-table">
                <thead>
                  <tr>
                    <td>Subject Name</td>
                    <td>Professor</td>
                    <td>Grade</td>
                    <td>Action</td>
                  </tr>
                </thead>
                <tbody>
                    <?php if (empty($archived_subjects)): ?>
                        <tr>
                            <td colspan="5">No archived subjects found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($archived_subjects as $subject): ?>
                        <?php
                            $sectionId = isset($subject['section_id']) ? (int)$subject['section_id'] : 0;
                            $sectionSubjectId = isset($subject['section_subject_id']) ? (int)$subject['section_subject_id'] : 0;
                            $gradeInfo = $sectionId ? computeStudentGradeForSection($pdo, $student_id, $sectionId, $sectionSubjectId ?: null) : null;
                            if ($gradeInfo) {
                                if (!empty($gradeInfo['equivalent'])) {
                                    $gradeDisplay = $gradeInfo['equivalent'];
                                } elseif ($gradeInfo['final_grade_display'] !== null) {
                                    $gradeDisplay = number_format((float)$gradeInfo['final_grade_display'], 2) . '%';
                                } else {
                                    $gradeDisplay = 'Pending';
                                }
                            } else {
                                $gradeDisplay = 'Pending';
                            }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($subject['subject_title']); ?></td>
                            <td><?php echo htmlspecialchars($subject['professor_name'] ?: 'TBA'); ?></td>
                            <td><?php echo htmlspecialchars($gradeDisplay); ?></td>
                            <td><a href="#" class="action-link">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
              </table>
            </div>

        </main>

    </div>

    
</body>
<script src="../assets/js/student.js"></script>
</html>
