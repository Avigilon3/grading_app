<?php


// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../includes/config.php';  // Your DB config
require_once '../includes/db.php';      // Your PDO connection ($pdo)

// Redirect non-logged-in users
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Convert raw grade to equivalent grade
function convertRawToGrade($raw) {
    if ($raw >= 97) return 1.00;
    if ($raw >= 94) return 1.25;
    if ($raw >= 91) return 1.50;
    if ($raw >= 88) return 1.75;
    if ($raw >= 85) return 2.00;
    if ($raw >= 82) return 2.25;
    if ($raw >= 79) return 2.50;
    if ($raw >= 76) return 2.75;
    if ($raw >= 75) return 3.00;
    return 5.00;
}

// Handle dark mode toggle
$darkMode = isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] === '1';
if (isset($_POST['toggle_dark_mode'])) {
    setcookie('dark_mode', $darkMode ? '0' : '1', time() + (86400 * 30), "/");
    header("Location: grading_system.php");
    exit;
}

// Initialize messages
$error = $success = null;

// Add Subject (Admin only)
if (isset($_POST['add_subject']) && $_SESSION['role'] === 'admin') {
    $subject_name = trim($_POST['subject_name']);
    $subject_code = trim($_POST['subject_code']);
    if ($subject_name === '' || $subject_code === '') {
        $error = "Subject name and code cannot be empty.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO subjects (name, code) VALUES (?, ?)");
            $stmt->execute([$subject_name, $subject_code]);
            $success = "Subject added successfully!";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Subject code already exists. Please use a unique code.";
            } else {
                $error = "An error occurred while adding the subject.";
            }
        }
    }
}

// Delete Subject (Admin only)
if (isset($_GET['delete_subject']) && $_SESSION['role'] === 'admin') {
    $delete_id = intval($_GET['delete_subject']);
    $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
    $stmt->execute([$delete_id]);
    header("Location: grading_system.php");
    exit;
}

// Add Student (Admin or Teacher)
if (isset($_POST['add_student']) && in_array($_SESSION['role'], ['admin', 'teacher'])) {
    $student_number = trim($_POST['student_number']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $default_password = password_hash('password', PASSWORD_DEFAULT);

    if ($student_number === '' || $username === '' || $email === '') {
        $error = "Please fill in all student fields.";
    } else {
        // Check if student number already exists
        $stmt = $pdo->prepare("SELECT id FROM students WHERE student_number = ?");
        $stmt->execute([$student_number]);
        if ($stmt->fetch()) {
            $error = "Student number already exists.";
        } else {
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            if ($user) {
                // Check if user is already a student
                $stmt2 = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
                $stmt2->execute([$user['id']]);
                if ($stmt2->fetch()) {
                    $error = "Username already exists and is already a student.";
                } else {
                    // Add to students table only
                    $stmt2 = $pdo->prepare("INSERT INTO students (user_id, student_number) VALUES (?, ?)");
                    $stmt2->execute([$user['id'], $student_number]);
                    $success = "Existing user linked as student successfully.";
                }
            } else {
                // Insert into users
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, 'student', ?)");
                $stmt->execute([$username, $default_password, $email]);
                $userId = $pdo->lastInsertId();

                // Insert into students
                $stmt = $pdo->prepare("INSERT INTO students (user_id, student_number) VALUES (?, ?)");
                $stmt->execute([$userId, $student_number]);
                $success = "Student added successfully.";
            }
        }
    }
}

// Link Existing User as Student (Admin or Teacher)
if (isset($_POST['link_existing_student']) && in_array($_SESSION['role'], ['admin', 'teacher'])) {
    $existing_username = trim($_POST['existing_username']);
    $student_number_existing = trim($_POST['student_number_existing']);

    // Hanapin ang user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$existing_username]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = "User not found.";
    } else {
        // Check kung may student record na
        $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        if ($stmt->fetch()) {
            $error = "This user is already a student.";
        } else {
            // Check kung may gumagamit na ng student number
            $stmt = $pdo->prepare("SELECT id FROM students WHERE student_number = ?");
            $stmt->execute([$student_number_existing]);
            if ($stmt->fetch()) {
                $error = "Student number already exists.";
            } else {
                // Insert sa students table
                $stmt = $pdo->prepare("INSERT INTO students (user_id, student_number) VALUES (?, ?)");
                $stmt->execute([$user['id'], $student_number_existing]);
                $success = "User linked as student successfully!";
            }
        }
    }
}

// Add Grade (Admin or Teacher)
if (isset($_POST['add_grade']) && in_array($_SESSION['role'], ['admin', 'teacher'])) {
    $student_id = intval($_POST['student_id']);
    $subject_id = intval($_POST['subject_id']);
    $raw_grade = floatval($_POST['raw_grade']);
    $comments = trim($_POST['comments']);

    // Use teacher's user_id directly for teacher, or NULL for admin
    $teacher_id = ($_SESSION['role'] === 'teacher') ? $_SESSION['user_id'] : null;

    if (!isset($error)) {
        if (!is_numeric($raw_grade) || $raw_grade < 0 || $raw_grade > 100) {
            $error = "Grade must be between 0 and 100.";
        } else {
            $equiv = convertRawToGrade($raw_grade);
            $stmt = $pdo->prepare("
                INSERT INTO grades (student_id, subject_id, teacher_id, raw_grade, grade, comments) 
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    raw_grade = VALUES(raw_grade),
                    grade = VALUES(grade),
                    comments = VALUES(comments),
                    teacher_id = VALUES(teacher_id)
            ");
            $stmt->execute([$student_id, $subject_id, $teacher_id, $raw_grade, $equiv, $comments]);
            $success = "Grade added or updated successfully!";
        }
    }
}

// Edit Grade (Admin or Teacher)
if (isset($_POST['edit_grade']) && in_array($_SESSION['role'], ['admin', 'teacher'])) {
    $grade_id = intval($_POST['grade_id']);
    $subject_id = intval($_POST['subject_id']);
    $raw_grade = floatval($_POST['raw_grade']);
    $comments = trim($_POST['comments']);

    if ($raw_grade >= 0 && $raw_grade <= 100) {
        $equiv = convertRawToGrade($raw_grade);
        $stmt = $pdo->prepare("UPDATE grades SET subject_id = ?, grade = ?, raw_grade = ?, comments = ? WHERE id = ?");
        $stmt->execute([$subject_id, $equiv, $raw_grade, $comments, $grade_id]);
        $success = "Grade updated successfully!";
    } else {
        $error = "Grade must be between 0 and 100.";
    }
}

// Delete Grade (Admin or Teacher)
if (isset($_GET['delete_grade']) && in_array($_SESSION['role'], ['admin', 'teacher'])) {
    $deleteId = intval($_GET['delete_grade']);
    $stmt = $pdo->prepare("DELETE FROM grades WHERE id = ?");
    $stmt->execute([$deleteId]);
    header("Location: grading_system.php");
    exit;
}

// Delete Student (Admin or Teacher)
if (isset($_GET['delete_student']) && in_array($_SESSION['role'], ['admin', 'teacher'])) {
    $userId = intval($_GET['delete_student']);

    // Find student record
    $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt->execute([$userId]);
    $student = $stmt->fetch();

    if ($student) {
        $studentId = intval($student['id']);
        // Delete all grades for that student
        $stmt2 = $pdo->prepare("DELETE FROM grades WHERE student_id = ?");
        $stmt2->execute([$studentId]);

        // Delete student record
        $stmt2 = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $stmt2->execute([$studentId]);
    }
    // Delete user record
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);

    header("Location: grading_system.php");
    exit;
}

// Search filter
$searchTerm = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';

if ($_SESSION['role'] === 'student') {
    $stmt = $pdo->prepare("
        SELECT s.id AS student_id, s.student_number, u.username, u.id AS user_id
        FROM students s
        JOIN users u ON u.id = s.user_id
        LEFT JOIN grades g ON s.id = g.student_id
        LEFT JOIN subjects sub ON g.subject_id = sub.id
        WHERE u.id = ? AND (u.username LIKE ? OR s.student_number LIKE ? OR sub.name LIKE ?)
        GROUP BY s.id
        ORDER BY u.username ASC
    ");
    $stmt->execute([$_SESSION['user_id'], $searchTerm, $searchTerm, $searchTerm]);
} else {
    $stmt = $pdo->prepare("
        SELECT s.id AS student_id, s.student_number, u.username, u.id AS user_id
        FROM students s
        JOIN users u ON u.id = s.user_id
        LEFT JOIN grades g ON s.id = g.student_id
        LEFT JOIN subjects sub ON g.subject_id = sub.id
        WHERE u.username LIKE ? OR s.student_number LIKE ? OR sub.name LIKE ?
        GROUP BY s.id
        ORDER BY u.username ASC
    ");
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
}

$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch subjects (all)
$subjects = [];
$stmt = $pdo->query("SELECT * FROM subjects ORDER BY name ASC");
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare grades array for display
$grades = [];
if (!empty($students)) {
    $studentIds = array_column($students, 'student_id');
    $placeholders = implode(',', array_fill(0, count($studentIds), '?'));

    if ($_SESSION['role'] === 'student') {
        $stmt = $pdo->prepare("SELECT g.*, s.student_number, sub.name AS subject_name, u.username
            FROM grades g
            JOIN students s ON s.id = g.student_id
            JOIN subjects sub ON sub.id = g.subject_id
            JOIN users u ON u.id = s.user_id
            WHERE g.student_id = ?
            ORDER BY u.username, sub.name
        ");
        $stmt->execute([$_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare("SELECT g.*, s.student_number, sub.name AS subject_name, u.username
            FROM grades g
            JOIN students s ON s.id = g.student_id
            JOIN subjects sub ON sub.id = g.subject_id
            JOIN users u ON u.id = s.user_id
            WHERE g.student_id IN ($placeholders)
            ORDER BY u.username, sub.name
        ");
        $stmt->execute($studentIds);
    }
    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Prepare data for charts
$gradeDistribution = [
    '1.00' => 0,
    '1.25' => 0,
    '1.50' => 0,
    '1.75' => 0,
    '2.00' => 0,
    '2.25' => 0,
    '2.50' => 0,
    '2.75' => 0,
    '3.00' => 0,
    '5.00' => 0
];

foreach ($grades as $grade) {
    $gradeValue = number_format($grade['grade'], 2);
    if (isset($gradeDistribution[$gradeValue])) {
        $gradeDistribution[$gradeValue]++;
    }
}

$subjectGrades = [];
foreach ($grades as $grade) {
    $subj = $grade['subject_name'];
    if (!isset($subjectGrades[$subj])) {
        $subjectGrades[$subj] = [];
    }
    $subjectGrades[$subj][] = $grade['grade'];
}

$avgGradesPerSubject = [];
foreach ($subjectGrades as $subject => $gradesArr) {
    $avgGradesPerSubject[$subject] = round(array_sum($gradesArr) / count($gradesArr), 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Grading System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body.bg-dark {
            background-color: #121212 !important;
            color: #e0e0e0 !important;
        }
        .table-dark th, .table-dark td {
            color: #e0e0e0 !important;
        }
    </style>
</head>
<body class="<?= $darkMode ? 'bg-dark text-light' : '' ?>">
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Grading System</h2>
        <div>
            <span class="me-3">
                Welcome,
                <?= htmlspecialchars($_SESSION['username']) ?>
                (<?= ucfirst(htmlspecialchars($_SESSION['role'])) ?>)
            </span>
            <form method="post" style="display:inline;">
                <button type="submit" name="toggle_dark_mode" class="btn btn-outline-secondary btn-sm"><?= $darkMode ? 'Light Mode' : 'Dark Mode' ?></button>
            </form>
            <a href="../logout.php" class="btn btn-danger btn-sm ms-2">Logout</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="get" class="mb-4">
        <input type="text" name="search" class="form-control" placeholder="Search students or subjects" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
    </form>

    <?php if ($_SESSION['role'] === 'admin'): ?>
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">Add Subject</div>
            <div class="card-body">
                <form method="post" class="row g-2">
                    <div class="col-md-5">
                        <input type="text" name="subject_name" class="form-control" placeholder="Subject Name" required>
                    </div>
                    <div class="col-md-5">
                        <input type="text" name="subject_code" class="form-control" placeholder="Subject Code" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="add_subject" class="btn btn-warning w-100">Add</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if (in_array($_SESSION['role'], ['admin', 'teacher'])): ?>
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">Add Student</div>
            <div class="card-body">
                <form method="post" class="row g-2">
                    <div class="col-md-3">
                        <input type="text" name="student_number" class="form-control" placeholder="Student Number" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="username" class="form-control" placeholder="Username" required>
                    </div>
                    <div class="col-md-4">
                        <input type="email" name="email" class="form-control" placeholder="Email" required>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" name="add_student" class="btn btn-primary w-100">Add</button>
                    </div>
                </form>
                <small class="text-muted">Default password for new students is <strong>password</strong>.</small>
            </div>
        </div>
        <!-- Link Existing User as Student -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">Link Existing User as Student</div>
            <div class="card-body">
                <form method="post" class="row g-2">
                    <div class="col-md-4">
                        <input type="text" name="existing_username" class="form-control" placeholder="Existing Username" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="student_number_existing" class="form-control" placeholder="Student Number" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" name="link_existing_student" class="btn btn-secondary w-100">Link as Student</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Add/Update Grade Form (Admin/Teacher) -->
    <?php if (in_array($_SESSION['role'], ['admin', 'teacher'])): ?>
        <div class="card mb-4">
            <div class="card-header bg-success text-white">Add or Update Grade</div>
            <div class="card-body">
                <form method="post" class="row g-2">
                    <div class="col-md-3">
                        <select name="student_id" class="form-select" required>
                            <option value="">Select Student</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?= $student['student_id'] ?>">
                                    <?= htmlspecialchars($student['username']) ?> (<?= htmlspecialchars($student['student_number']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="subject_id" class="form-select" required>
                            <option value="">Select Subject</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="number" step="0.01" min="0" max="100" name="raw_grade" placeholder="Raw Grade (0-100)" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="comments" placeholder="Comments (optional)" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <button type="submit" name="add_grade" class="btn btn-success w-100">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($subjects)): ?>
        <h4>Subjects</h4>
        <table class="table table-bordered <?= $darkMode ? 'table-dark' : '' ?>">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <th>Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subjects as $subject): ?>
                    <tr>
                        <td><?= htmlspecialchars($subject['name']) ?></td>
                        <td><?= htmlspecialchars($subject['code']) ?></td>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <td>
                                <a href="grading_system.php?delete_subject=<?= $subject['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this subject?');">Delete</a>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if (!empty($students)): ?>
        <h4>Students</h4>
        <table class="table table-bordered <?= $darkMode ? 'table-dark' : '' ?>">
            <thead>
                <tr>
                    <th>Student Number</th>
                    <th>Username</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?= htmlspecialchars($student['student_number']) ?></td>
                        <td><?= htmlspecialchars($student['username']) ?></td>
                        <td>
                            <?php if (in_array($_SESSION['role'], ['admin', 'teacher'])): ?>
                                <a href="grading_system.php?delete_student=<?= $student['user_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this student and all their grades?');">Delete</a>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php
    // --- GROUPED GRADES BY STUDENT WITH TOTAL AND PASS/FAIL ---
    if (!empty($grades)):
        // Group grades by student
        $gradesByStudent = [];
        foreach ($grades as $grade) {
            $key = $grade['username'] . ' (' . $grade['student_number'] . ')';
            if (!isset($gradesByStudent[$key])) {
                $gradesByStudent[$key] = [];
            }
            $gradesByStudent[$key][] = $grade;
        }
        echo '<h4>Grades</h4>';
        foreach ($gradesByStudent as $studentKey => $studentGrades):
            $total = 0;
            $count = count($studentGrades);
            foreach ($studentGrades as $g) {
                $total += $g['grade'];
            }
            $average = $count ? round($total / $count, 2) : 0;
            $passed = $average <= 3.00 ? 'PASSED' : 'FAILED';
    ?>
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <?= htmlspecialchars($studentKey) ?>
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered mb-0">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Raw Grade</th>
                        <th>Equivalent Grade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($studentGrades as $grade): ?>
                    <tr>
                        <td><?= htmlspecialchars($grade['subject_name']) ?></td>
                        <td><?= htmlspecialchars(number_format($grade['raw_grade'], 2)) ?></td>
                        <td><?= htmlspecialchars(number_format($grade['grade'], 2)) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="table-secondary fw-bold">
                        <td>Total/Average</td>
                        <td></td>
                        <td><?= $average ?></td>
                    </tr>
                    <tr class="table-secondary fw-bold">
                        <td>Status</td>
                        <td colspan="2">
                            <span class="<?= $passed == 'PASSED' ? 'text-success' : 'text-danger' ?>"><?= $passed ?></span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; else: ?>
        <p>No grades to display.</p>
    <?php endif; ?>

    <div class="row mt-5">
        <div class="col-md-6">
            <h5>Grade Distribution</h5>
            <canvas id="gradeDistributionChart"></canvas>
        </div>
        <div class="col-md-6">
            <h5>Average Grade per Subject</h5>
            <canvas id="avgGradesChart"></canvas>
        </div>
    </div>
</div>

<script>
    const gradeDistributionCtx = document.getElementById('gradeDistributionChart').getContext('2d');
    const avgGradesCtx = document.getElementById('avgGradesChart').getContext('2d');

    const gradeDistributionData = {
        labels: <?= json_encode(array_keys($gradeDistribution)) ?>,
        datasets: [{
            label: 'Number of Grades',
            data: <?= json_encode(array_values($gradeDistribution)) ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.6)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    };

    const avgGradesData = {
        labels: <?= json_encode(array_keys($avgGradesPerSubject)) ?>,
        datasets: [{
            label: 'Average Grade',
            data: <?= json_encode(array_values($avgGradesPerSubject)) ?>,
            backgroundColor: 'rgba(255, 206, 86, 0.6)',
            borderColor: 'rgba(255, 206, 86, 1)',
            borderWidth: 1
        }]
    };

    new Chart(gradeDistributionCtx, {
        type: 'bar',
        data: gradeDistributionData,
        options: {
            scales: {
                y: { beginAtZero: true, stepSize: 1 }
            }
        }
    });

    new Chart(avgGradesCtx, {
        type: 'bar',
        data: avgGradesData,
        options: {
            scales: {
                y: { beginAtZero: true, max: 5, stepSize: 0.5 }
            }
        }
    });
</script>
</body>
</html>