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
    // This is the core SQL query. It joins many tables to get all the required details:
    // 1. students -> section_students (to link student to their sections)
    // 2. section_students -> sections (to get section details like term_id)
    // 3. sections -> terms (to check if the term is active)
    // 4. sections -> section_subjects (to link section to its subjects)
    // 5. section_subjects -> subjects (to get subject details)
    // 6. section_subjects -> professors (to get professor's name)
    $sql = "
        SELECT
            t.term_name,
            t.is_active AS term_is_active,
            s.subject_code,
            s.subject_title,
            sec.section_name,
            CONCAT(p.first_name, ' ', p.last_name) AS professor_name
        FROM
            section_students ss
        INNER JOIN sections sec ON ss.section_id = sec.id
        INNER JOIN section_subjects ssub ON sec.id = ssub.section_id
        INNER JOIN subjects s ON ssub.subject_id = s.id
        LEFT JOIN terms t ON sec.term_id = t.id -- Join with sections' term (primary term)
        LEFT JOIN professors p ON ssub.professor_id = p.id
        WHERE
            ss.student_id = :student_id
        
        -- OR conditions to include subjects linked directly to a term in section_subjects
        UNION
        
        SELECT
            t2.term_name,
            t2.is_active AS term_is_active,
            s2.subject_code,
            s2.subject_title,
            sec2.section_name,
            CONCAT(p2.first_name, ' ', p2.last_name) AS professor_name
        FROM
            section_students ss2
        INNER JOIN section_subjects ssub2 ON ss2.section_id = ssub2.section_id
        INNER JOIN sections sec2 ON ss2.section_id = sec2.id
        INNER JOIN subjects s2 ON ssub2.subject_id = s2.id
        LEFT JOIN terms t2 ON ssub2.term_id = t2.id -- Join with subject's specific term
        LEFT JOIN professors p2 ON ssub2.professor_id = p2.id
        WHERE
            ss2.student_id = :student_id
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
    // The condition for 'Currently Enrolled' is: 
    // The subject belongs to a term that is currently active (term_is_active = 1)
    if ($subject['term_is_active'] == 1) {
        $enrolled_subjects[] = $subject;
    } else {
        $archived_subjects[] = $subject;
    }
}

// Function to calculate final grade (for display - currently placeholder)
function getFinalGrade($student_id, $subject_code) {
    // NOTE: Implementing this correctly is complex and involves:
    // 1. Getting all grade_items for the subject's section/grading_sheet.
    // 2. Getting all grades for the student for those items.
    // 3. Calculating weighted average based on grade_components.
    // For now, we'll use a placeholder.
    return 'N/A'; // Placeholder for the actual grade calculation
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
                        <tr>
                            <td><?php echo htmlspecialchars($subject['subject_title']); ?></td>
                            <td><?php echo htmlspecialchars($subject['professor_name'] ?: 'TBA'); ?></td>
                            <td><?php echo htmlspecialchars(getFinalGrade($student_id, $subject['subject_code'])); ?></td>
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
                        <tr>
                            <td><?php echo htmlspecialchars($subject['subject_title']); ?></td>
                            <td><?php echo htmlspecialchars($subject['professor_name'] ?: 'TBA'); ?></td>
                            <td><?php echo htmlspecialchars(getFinalGrade($student_id, $subject['subject_code'])); ?></td>
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