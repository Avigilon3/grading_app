<?php
// Database connection settings
$host = 'http://localhost/grading_app/';
$dbname = 'professor_portal';
$username = 'root';      // teacher1
$password = '';          // password

$mysqli = new mysqli($host, $username, $password, $dbname);
if ($mysqli->connect_error) {
    die("DB Connection failed: " . $mysqli->connect_error);
}

// Simulate logged in professor (replace with your auth system)
$professor_id = 1;

// Fetch semesters for dropdown
$semesters = [];
$res = $mysqli->query("SELECT * FROM semesters ORDER BY id");
while ($row = $res->fetch_assoc()) {
    $semesters[] = $row;
}

// Handle filters for section and semester
$filter_section = isset($_GET['section']) ? $_GET['section'] : 'all';
$filter_semester = isset($_GET['semester']) ? intval($_GET['semester']) : 1;
$search_student = isset($_GET['search_student']) ? trim($_GET['search_student']) : '';

// Fetch classes assigned to professor with filtering
$sql_classes = "SELECT c.* FROM classes c WHERE c.professor_id = ? AND c.semester_id = ?";
$params = [$professor_id, $filter_semester];

// If section filter applied
if ($filter_section !== 'all') {
    $sql_classes .= " AND c.section = ?";
    $params[] = $filter_section;
}

$stmt = $mysqli->prepare($sql_classes);

if ($filter_section !== 'all') {
    $stmt->bind_param("iis", $professor_id, $filter_semester, $filter_section);
} else {
    $stmt->bind_param("ii", $professor_id, $filter_semester);
}
$stmt->execute();
$result = $stmt->get_result();

$classes = [];
while ($class = $result->fetch_assoc()) {
    // Fetch students for each class with optional search filter
    $class_id = $class['id'];
    if ($search_student !== '') {
        $search_param = '%' . $mysqli->real_escape_string($search_student) . '%';
        $sql_students = "SELECT * FROM students WHERE class_id = ? AND (student_id LIKE ? OR name LIKE ?) ORDER BY name";
        $stmt_students = $mysqli->prepare($sql_students);
        $stmt_students->bind_param("iss", $class_id, $search_param, $search_param);
        $stmt_students->execute();
        $students_result = $stmt_students->get_result();
    } else {
        $sql_students = "SELECT * FROM students WHERE class_id = ? ORDER BY name";
        $stmt_students = $mysqli->prepare($sql_students);
        $stmt_students->bind_param("i", $class_id);
        $stmt_students->execute();
        $students_result = $stmt_students->get_result();
    }
    $students = [];
    while ($student = $students_result->fetch_assoc()) {
        $students[] = $student;
    }
    $class['students'] = $students;
    $classes[] = $class;
}

// Fetch distinct sections for filtering dropdown
$sql_sections = "SELECT DISTINCT section FROM classes WHERE professor_id = ? AND semester_id = ?";
$stmt_sections = $mysqli->prepare($sql_sections);
$stmt_sections->bind_param("ii", $professor_id, $filter_semester);
$stmt_sections->execute();
$res_sections = $stmt_sections->get_result();
$sections = ['all' => 'All sections'];
while($sec = $res_sections->fetch_assoc()){
    $sections[$sec['section']] = $sec['section'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Professor Portal - My Classes</title>
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0; background: #f5f7fa; color: #212529;
    }
    /* Sidebar */
    .sidebar {
        background-color: #4f744e;
        width: 220px;
        height: 100vh;
        position: fixed;
        left: 0; top: 0;
        padding: 1.5em 1em;
        color: white;
    }
    .sidebar h1 {
        font-size: 1.3rem;
        margin-bottom: 1.5em;
    }
    .sidebar a {
        text-decoration: none;
        color: white;
        display: block;
        margin: 1em 0;
        padding: 0.4em 0.6em;
        border-radius: 6px;
        font-weight: 500;
    }
    .sidebar a.active, .sidebar a:hover {
        background-color: #6f9568;
    }

    /* Main content */
    .main {
        margin-left: 220px;
        padding: 2em 3em;
        max-width: 1200px;
    }
    h2 {
        margin-top: 0;
    }
    p.description {
        color: #514e4e;
        margin-bottom: 1.3em;
    }

    /* Controls */
    .controls {
        display: flex;
        flex-wrap: wrap;
        gap: 1em;
        margin-bottom: 2em;
        align-items: center;
    }
    select, input[type="search"] {
        padding: 0.5em 0.8em;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 1rem;
        flex: 1;
        min-width: 180px;
    }
    .controls input[type="search"] {
        flex: 2;
    }

    /* Classes grid */
    .classes-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 2em;
    }

    /* Class card */
    .class-card {
        background: white;
        border-radius: 10px;
        padding: 1.2em 1.5em 1.5em;
        box-shadow: 0 1px 10px rgb(0 0 0 / 0.1);
        display: flex;
        flex-direction: column;
    }
    .class-header {
        font-weight: 600;
        font-size: 1.1rem;
        margin-bottom: 0.8em;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: #4a5e2a;
    }

    /* Go to grading sheet button */
    .grading-btn {
        background-color: #6b8747;
        color: white;
        font-weight: 600;
        border: none;
        border-radius: 30px;
        padding: 0.4em 1em;
        cursor: pointer;
        transition: background-color 0.3s ease;
        font-size: 0.87rem;
    }
    .grading-btn:hover {
        background-color: #587238;
    }

    /* Search student input */
    .student-search {
        margin-bottom: 0.8em;
    }
    .student-search input {
        width: 100%;
        padding: 0.4em 0.6em;
        font-size: 0.9rem;
        border: 1px solid #cfd2d5;
        border-radius: 6px;
        color: #495057;
    }
    .student-search input::placeholder {
        color: #9aa1a8;
    }

    /* Students table */
    table {
        border-collapse: collapse;
        width: 100%;
        font-size: 0.9rem;
    }
    th, td {
        padding: 0.35em 0.65em;
        text-align: left;
    }
    thead tr {
        border-bottom: 2px solid #e4e7eb;
        color: #6c757d;
    }
    tbody tr {
        border-bottom: 1px solid #e1e6eb;
    }
</style>
<script>
function filterStudents(inputId, tableId) {
    const input = document.getElementById(inputId);
    const filter = input.value.toLowerCase();
    const table = document.getElementById(tableId);
    const trs = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    for (let i=0; i < trs.length; i++) {
        const td_id = trs[i].getElementsByTagName('td')[1];
        const td_name = trs[i].getElementsByTagName('td')[2];
        if (td_id && td_name) {
            const idText = td_id.textContent.toLowerCase();
            const nameText = td_name.textContent.toLowerCase();
            if (idText.indexOf(filter) > -1 || nameText.indexOf(filter) > -1) {
                trs[i].style.display = "";
            } else {
                trs[i].style.display = "none";
            }
        }
    }
}
</script>
</head>
<body>

<div class="sidebar">
    <h1>Professor Portal</h1>
    <a href="dashboard.php">Dashboard</a>
    <a href="classes.php" class="active">Classes</a>
    <a href="#">Grading Sheets</a>
    <a href="#">Requests</a>
</div>

<div class="main">
    <h2>My Classes</h2>
    <p class="description">Manage and view all your assigned sections for this semester.</p>

    <form method="GET" action="classes.php" class="controls">
        <select name="section" onchange="this.form.submit()">
            <?php foreach ($sections as $sec_key => $sec_val): ?>
                <option value="<?= htmlspecialchars($sec_key) ?>" <?= $filter_section === $sec_key ? 'selected' : '' ?>><?= htmlspecialchars($sec_val) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="semester" onchange="this.form.submit()">
            <?php foreach ($semesters as $sem): ?>
                <option value="<?= $sem['id'] ?>" <?= $sem['id'] === $filter_semester ? 'selected' : '' ?>><?= htmlspecialchars($sem['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <input type="search" name="search_student" placeholder="Search classes..." value="<?= htmlspecialchars($search_student) ?>" />
        <button type="submit" style="display:none;">Search</button>
    </form>

    <div class="classes-container">
        <?php if (empty($classes)): ?>
            <p>No classes found for selected criteria.</p>
        <?php else: ?>
            <?php foreach($classes as $index => $class): ?>
                <?php
                    $searchInputId = "search-student-" . $class['id'];
                    $tableId = "students-table-" . $class['id'];
                ?>
                <div class="class-card">
                    <div class="class-header">
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle;" width="18" height="18" fill="#6b8747" viewBox="0 0 16 16"><path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3z"/><path fill-rule="evenodd" d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
                            &nbsp;
                            <?= htmlspecialchars($class['code']) ?> - <?= htmlspecialchars($class['section']) ?>
                        </div>
                        <button class="grading-btn" onclick="alert('Go to grading sheet for <?= htmlspecialchars($class['code']) ?>'); return false;">Go to Grading Sheet</button>
                    </div>
                    <div class="student-search">
                        <input type="text" id="<?= $searchInputId ?>" onkeyup="filterStudents('<?= $searchInputId ?>','<?= $tableId ?>')" placeholder="Search students by ID or name..." />
                    </div>
                    <table id="<?= $tableId ?>">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student ID</th>
                                <th>Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($class['students'] as $i => $student): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($student['student_id']) ?></td>
                                    <td><?= htmlspecialchars($student['name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($class['students'])): ?>
                                <tr><td colspan="3" style="text-align:center; color:#999;">No students found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
