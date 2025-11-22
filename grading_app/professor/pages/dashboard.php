<?php
// DB connection
$host = 'http://localhost/grading_app/';
$db = 'professor_portal';
$user = 'root'; // teacher1
$pass = '';     // password

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Assuming professor is logged in, professor_id = 1
$professor_id = 1;

// Fetch totals
$totalClasses = 0;
$totalStudents = 0;
$pendingGrades = 0;
$submittedSheets = 0;

// Total Classes
$sql = "SELECT COUNT(*) as cnt FROM classes WHERE professor_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $professor_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $totalClasses = $row['cnt'];
}

// Total Students (for classes of this professor)
$sql = "SELECT COUNT(s.id) as cnt FROM students s JOIN classes c ON s.class_id = c.id WHERE c.professor_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $professor_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $totalStudents = $row['cnt'];
}

// Pending Grades (grading sheets submitted = FALSE)
$sql = "SELECT COUNT(*) as cnt FROM grading_sheets gs JOIN classes c ON gs.class_id = c.id WHERE c.professor_id = ? AND gs.submitted = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $professor_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $pendingGrades = $row['cnt'];
}

// Submitted Sheets
$sql = "SELECT COUNT(*) as cnt FROM grading_sheets gs JOIN classes c ON gs.class_id = c.id WHERE c.professor_id = ? AND gs.submitted = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $professor_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $submittedSheets = $row['cnt'];
}

// Fetch all classes
$sql = "SELECT * FROM classes WHERE professor_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $professor_id);
$stmt->execute();
$classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch deadlines
$sql = "SELECT d.due_date, c.code, c.title FROM deadlines d JOIN classes c ON d.class_id = c.id WHERE c.professor_id = ? ORDER BY d.due_date ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $professor_id);
$stmt->execute();
$deadlines = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Function to calculate due text
function dueText($date) {
    $now = new DateTime();
    $due = new DateTime($date);
    $diff = $now->diff($due);
    if($due < $now) {
        return "Past due";
    }
    if($diff->days == 0) {
        return "Due today";
    }
    if($diff->days <= 7) {
        return "Due in " . $diff->days . " days";
    }
    return "Due " . $due->format('M j, Y');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Professor Portal Dashboard</title>
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 0; background: #f9fafb; color: #111;
    }
    .sidebar {
        width: 200px;
        background: #5a7a4f;
        height: 100vh;
        color: white;
        padding: 20px;
        position: fixed;
    }
    .sidebar h1 {
        font-size: 20px;
        margin-bottom: 20px;
    }
    .sidebar a {
        color: white;
        display: block;
        padding: 10px 0;
        text-decoration: none;
        border-radius: 5px;
    }
    .sidebar a.active, .sidebar a:hover {
        background: #4b6b3f;
    }
    .main {
        margin-left: 220px;
        padding: 20px;
    }
    h2 {
        margin-bottom: 0;
    }
    .stats {
        display: flex;
        gap: 20px;
        margin: 20px 0 40px 0;
    }
    .stat {
        background: white;
        padding: 15px 20px;
        border-radius: 10px;
        flex: 1;
        box-shadow: 0 0 8px #ddd;
        font-weight: bold;
        font-size: 18px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .stat small {
        font-weight: normal;
        font-size: 14px;
        color: #666;
    }
    .classes {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
    }
    .class-card {
        background: white;
        padding: 15px 20px;
        border-radius: 10px;
        box-shadow: 0 0 8px #ddd;
        flex: 1 1 45%;
        position: relative;
    }
    .class-card button {
        background: #5a7a4f;
        border: none;
        color: white;
        border-radius: 8px;
        padding: 7px 15px;
        cursor: pointer;
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
    }
    .deadline-list {
        background: white;
        padding: 10px 20px;
        border-radius: 10px;
        box-shadow: 0 0 8px #ddd;
        margin-top: 40px;
    }
    .deadline-item {
        padding: 10px;
        margin-bottom: 10px;
        border-radius: 5px;
    }
    .deadline-upcoming {
        background: #fef3e6;
        color: #c15f00;
    }
    .deadline-later {
        background: #e6f3ff;
        color: #0070c1;
    }
    h3 {
        margin-bottom: 15px;
    }
</style>
</head>
<body>

<div class="sidebar">
    <h1>Professor Portal</h1>
    <a href="#" class="active">Dashboard</a>
    <a href="#">Classes</a>
    <a href="#">Grading Sheets</a>
    <a href="#">Requests</a>
</div>

<div class="main">
    <h2>Dashboard Overview</h2>
    <p>Welcome back! Here's your grading summary for this term.</p>

    <div class="stats">
        <div class="stat"><small>Total Classes</small> <span><?= $totalClasses ?></span></div>
        <div class="stat"><small>Total Students</small> <span><?= $totalStudents ?></span></div>
        <div class="stat"><small>Pending Grades</small> <span><?= $pendingGrades ?></span></div>
        <div class="stat"><small>Submitted Sheets</small> <span><?= $submittedSheets ?></span></div>
    </div>

    <h3>My Classes</h3>
    <div class="classes">
        <?php foreach ($classes as $class): ?>
            <div class="class-card">
                <strong><?= htmlspecialchars($class['code']) ?></strong><br>
                <small><?= htmlspecialchars($class['title']) ?></small><br>
                <small><?= htmlspecialchars($class['section']) ?></small>
                <button>View</button>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="deadline-list">
        <h3>Upcoming Deadlines</h3>
        <?php if(empty($deadlines)): ?>
            <p>No upcoming deadlines.</p>
        <?php endif; ?>
        <?php foreach($deadlines as $d): ?>
            <?php 
                $dueClass = (new DateTime($d['due_date']) < new DateTime()) ? 'deadline-upcoming' : 'deadline-later'; 
                $dueText = dueText($d['due_date']);
            ?>
            <div class="deadline-item <?= $dueClass ?>">
                <strong><?= htmlspecialchars($d['code']) ?></strong><br>
                <small><?= htmlspecialchars($d['title']) ?></small>
                <span style="float: right;"><?= $dueText ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>
