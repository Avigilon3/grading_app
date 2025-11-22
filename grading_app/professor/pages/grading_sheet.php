<?php
// Database connection setup
$host = 'http://localhost/grading_app/';
$dbname = 'professor_portal';
$user = 'root';      // teacher1
$pass = '';          // password

$mysqli = new mysqli($host, $user, $pass, $dbname);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// For demo assumes professor logged in and class selected by GET param class_id = 1
$professor_id = 1;
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 1;

// Handle Save Draft or Submit post actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save grades POST data: grades[student_id][grade_item_id] = score
    $grades_post = $_POST['grades'] ?? [];
    // Update or insert grades
    foreach ($grades_post as $student_id => $items) {
        foreach ($items as $grade_item_id => $score_raw) {
            $score = is_numeric($score_raw) ? floatval($score_raw) : null;
            // Check if already exists
            $qCheck = $mysqli->prepare("SELECT id FROM grades WHERE student_id=? AND grade_item_id=?");
            $qCheck->bind_param('ii', $student_id, $grade_item_id);
            $qCheck->execute();
            $res = $qCheck->get_result();
            if ($res->num_rows) {
                // Update
                $qUpdate = $mysqli->prepare("UPDATE grades SET score=? WHERE student_id=? AND grade_item_id=?");
                $qUpdate->bind_param('dii', $score, $student_id, $grade_item_id);
                $qUpdate->execute();
            } else {
                // Insert
                $qInsert = $mysqli->prepare("INSERT INTO grades (student_id, grade_item_id, score) VALUES (?, ?, ?)");
                $qInsert->bind_param('iid', $student_id, $grade_item_id, $score);
                $qInsert->execute();
            }
        }
    }

    // Save grading sheet status (draft/submit)
    $submitted = isset($_POST['submit']) ? 1 : 0;
    $qStatus = $mysqli->prepare("UPDATE grading_sheets SET submitted=?, last_saved=NOW() WHERE class_id=?");
    $qStatus->bind_param('ii', $submitted, $class_id);
    $qStatus->execute();

    $msg = $submitted ? "Grading sheet submitted successfully." : "Draft saved successfully.";
}


// Fetch class info and verify professor owns it
$qClass = $mysqli->prepare("SELECT * FROM classes WHERE id=? AND professor_id=?");
$qClass->bind_param('ii', $class_id, $professor_id);
$qClass->execute();
$resClass = $qClass->get_result();
if ($resClass->num_rows === 0) {
    die("Class not found or you do not have permission.");
}
$class = $resClass->fetch_assoc();

// Fetch grading categories for this class ordered by sort_order
$qCategories = $mysqli->prepare("SELECT * FROM grade_categories WHERE class_id=? ORDER BY sort_order");
$qCategories->bind_param('i', $class_id);
$qCategories->execute();
$categories = $qCategories->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch grade items grouped by category
$category_ids = array_column($categories, 'id');
if (empty($category_ids)) {
    die("No grade categories found for this class.");
}

$ids_placeholder = implode(',', array_fill(0, count($category_ids), '?'));
$sqlItems = "SELECT * FROM grade_items WHERE category_id IN ($ids_placeholder) ORDER BY category_id, sort_order";
$stmtItems = $mysqli->prepare($sqlItems);

$types = str_repeat('i', count($category_ids));
$stmtItems->bind_param($types, ...$category_ids);
$stmtItems->execute();
$items_result = $stmtItems->get_result();

$grade_items = [];
while ($item = $items_result->fetch_assoc()) {
    $grade_items[$item['category_id']][] = $item;
}

// Fetch students of this class
$qStudents = $mysqli->prepare("SELECT * FROM students WHERE class_id=? ORDER BY name");
$qStudents->bind_param('i', $class_id);
$qStudents->execute();
$students = $qStudents->get_result()->fetch_all(MYSQLI_ASSOC);

// Get grading sheet status
$qStatus = $mysqli->prepare("SELECT submitted FROM grading_sheets WHERE class_id=?");
$qStatus->bind_param('i', $class_id);
$qStatus->execute();
$statusResult = $qStatus->get_result();
$submitted = ($statusResult->num_rows && $statusResult->fetch_assoc()['submitted']) ? true : false;

// Fetch existing grades to fill inputs: array[student_id][grade_item_id] = score
$all_student_ids = array_column($students, 'id');
$all_grade_item_ids = [];
foreach ($grade_items as $cat_items) {
    foreach ($cat_items as $gi) $all_grade_item_ids[] = $gi['id'];
}

if (empty($all_student_ids) || empty($all_grade_item_ids)) {
    die("No students or grade items to display.");
}

$students_placeholder = implode(',', array_fill(0, count($all_student_ids), '?'));
$items_placeholder = implode(',', array_fill(0, count($all_grade_item_ids), '?'));

$sqlGrades = "SELECT student_id, grade_item_id, score FROM grades WHERE student_id IN ($students_placeholder) AND grade_item_id IN ($items_placeholder)";
$stmtGrades = $mysqli->prepare($sqlGrades);

$params = array_merge($all_student_ids, $all_grade_item_ids);
$types = str_repeat('i', count($params));
$stmtGrades->bind_param($types, ...$params);
$stmtGrades->execute();

$gradesResult = $stmtGrades->get_result();
$grades = [];
while ($row = $gradesResult->fetch_assoc()) {
    $grades[$row['student_id']][$row['grade_item_id']] = $row['score'];
}

// Helper function to calculate final % per category for a student
function calculate_category_percentage($student_id, $category_id, $grade_items, $grades) {
    $total_score = 0;
    $total_max = 0;
    if (!isset($grade_items[$category_id])) return 0;
    foreach ($grade_items[$category_id] as $item) {
        $score = $grades[$student_id][$item['id']] ?? 0;
        $max = $item['max_score'];
        $total_score += $score;
        $total_max += $max;
    }
    if ($total_max == 0) return 0;
    return ($total_score / $total_max) * 100;
}

// Final grade calculation: weighted sum of category percentages
function calculate_final_grade($student_id, $categories, $grade_items, $grades) {
    $final = 0;
    foreach ($categories as $cat) {
        $percent = calculate_category_percentage($student_id, $cat['id'], $grade_items, $grades);
        $final += ($percent * ($cat['weight'] / 100));
    }
    return $final;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Grading Sheet - <?= htmlspecialchars($class['code']." - ".$class['section']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f9fafb; }
        h1 { color: #3a663f; }
        table { border-collapse: collapse; width: 100%; background: white; }
        th, td { border: 1px solid #ddd; text-align: center; padding: 6px 8px; }
        th { background-color: #f0f3f1; }
        input[type="number"] { width: 70px; padding: 4px 6px; font-size: 0.9rem; }
        .category-header { font-weight: bold; text-transform: uppercase; font-size: 0.9rem; }
        .yellow { background: #fffbdb; }
        .red { background: #fcc9c9; }
        .purple { background: #d8cefa; }
        .blue { background: #d0e7ff; }
        .green { background: #d9ead3; }
        .final-col { background: #e1f4e5; font-weight: bold; }
        .button-group { margin: 10px 0 30px; }
        button {
            background-color: #5a7a4f;
            border: none;
            color: white;
            padding: 10px 18px;
            margin-right: 10px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }
        button.submit { background-color: #496637; }
        button:disabled { background-color: #a5b79c; cursor: not-allowed; }
        .message {
            margin: 15px 0;
            padding: 15px;
            background: #e5f6e4;
            border-left: 4px solid #5a7a4f;
            color: #3a663f;
            font-weight: bold;
        }
        .info-box {
            margin-top: 40px;
            background: #d7ebfd;
            padding: 15px 20px;
            border-radius: 10px;
            color: #005db6;
        }
    </style>
    <script>
    function calculateFinalPercentages() {
        const categories = JSON.parse('<?= json_encode($categories, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>');
        const gradeItems = JSON.parse('<?= json_encode($grade_items, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>');

        let studentRows = document.querySelectorAll('tbody tr');
        studentRows.forEach(row => {
            const studentId = row.getAttribute('data-student-id');
            let finalGrade = 0;
            categories.forEach(cat => {
                let catTotalScore = 0;
                let catTotalMax = 0;
                if (!gradeItems[cat.id]) return;
                gradeItems[cat.id].forEach(item => {
                    let input = row.querySelector('input[name="grades['+studentId+']['+item.id+']"]');
                    let val = parseFloat(input.value) || 0;
                    catTotalScore += val;
                    catTotalMax += parseFloat(item.max_score);
                });
                const catPercent = (catTotalMax > 0) ? (catTotalScore / catTotalMax) * 100 : 0;
                const weighted = catPercent * (cat.weight / 100);
                finalGrade += weighted;

                // Update category final % cell if present
                let catFinalCell = row.querySelector('.cat-final-' + cat.id);
                if(catFinalCell){
                    catFinalCell.textContent = catPercent.toFixed(2) + '%';
                }
            });
            // Update final grade cell
            let finalCell = row.querySelector('.final-grade');
            if(finalCell){
                finalCell.textContent = finalGrade.toFixed(2) + '%';
            }
        });
    }

    window.addEventListener('DOMContentLoaded', () => {
        // Attach input event to all score inputs
        const inputs = document.querySelectorAll('input[type=number]');
        inputs.forEach(input => {
            input.addEventListener('input', calculateFinalPercentages);
        });
        // Calculate initial percentages on page load
        calculateFinalPercentages();
    });
    </script>
</head>
<body>

<h1>Grading Sheet for <?= htmlspecialchars($class['code']." - ".$class['section']) ?></h1>

<?php if (!empty($msg)): ?>
    <div class="message"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<form method="POST" action="grading_sheet.php?class_id=<?= $class_id ?>">

<table>
    <thead>
        <tr>
            <th rowspan="2">Student ID</th>
            <th rowspan="2">Student Name</th>

            <?php foreach ($categories as $category): 
                $color = '';
                switch (strtolower($category['name'])) {
                    case 'activity': $color = 'yellow'; break;
                    case 'exam': $color = 'red'; break;
                    case 'quizzes': $color = 'purple'; break;
                    case 'attendance': $color = 'blue'; break;
                    default: $color = 'green'; break;
                }
                $items = $grade_items[$category['id']] ?? [];
            ?>
                <th colspan="<?= count($items) + 1 ?>" class="<?= $color ?> category-header">
                    <?= htmlspecialchars(strtoupper($category['name'].' '.$category['weight'].'%')) ?>
                </th>
            <?php endforeach; ?>

            <th rowspan="2" class="final-col">Final Grade %</th>
        </tr>
        <tr>
            <?php foreach ($categories as $category):
                $items = $grade_items[$category['id']] ?? [];
                foreach ($items as $item):
            ?>
                <th><?= htmlspecialchars($item['name'].' '.$item['max_score']) ?></th>
            <?php endforeach; ?>
            <th>Final %</th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>

    <?php foreach ($students as $student): ?>
        <tr data-student-id="<?= $student['id'] ?>">
            <td><?= htmlspecialchars($student['student_id']) ?></td>
            <td style="text-align:left; padding-left:6px; font-weight:600;"><?= htmlspecialchars($student['name']) ?></td>

            <?php
            foreach ($categories as $category):
                $items = $grade_items[$category['id']] ?? [];
                $category_total_score = 0;
                $category_total_max = 0;

                foreach ($items as $item):
                    $score = $grades[$student['id']][$item['id']] ?? '';
                    if ($score === null) $score = '';
                    ?>
                    <td class="<?= strtolower($category['name']) ?>">
                        <input
                            type="number"
                            min="0"
                            max="<?= $item['max_score'] ?>"
                            name="grades[<?= $student['id'] ?>][<?= $item['id'] ?>]"
                            value="<?= htmlspecialchars($score) ?>"
                            <?= $submitted ? 'readonly' : '' ?>
                            step="0.01"
                        />
                    </td>
                    <?php
                    $category_total_score += ($score !== '' ? floatval($score) : 0);
                    $category_total_max += $item['max_score'];
                endforeach;

                // Calculate final % for category (output updated dynamically by JS on input)
                $cat_final_percent = ($category_total_max > 0) ? ($category_total_score / $category_total_max) * 100 : 0;
            ?>
                <td class="cat-final-<?= $category['id'] ?> final-col"><?= number_format($cat_final_percent, 2) ?>%</td>
            <?php endforeach; ?>

            <?php
                $final_grade = calculate_final_grade($student['id'], $categories, $grade_items, $grades);
            ?>
            <td class="final-grade final-col"><?= number_format($final_grade, 2) ?>%</td>
        </tr>
    <?php endforeach; ?>

    </tbody>
</table>

<div class="button-group">
    <button type="submit" name="save" <?= $submitted ? 'disabled' : '' ?>>Save Draft</button>
    <button type="submit" name="submit" class="submit" <?= $submitted ? 'disabled' : '' ?>>Submit</button>
</div>

</form>

<div class="info-box">
    <strong>Grading Information</strong>
    <ul>
        <li>Final grades are calculated based on the weighted percentage of each category.</li>
        <li>Category final % = (sum of scores for all items / sum of max scores) × 100.</li>
        <li>Final Grade % = sum of (category final % × category weight%) across all categories.</li>
        <li>Scores can be saved as draft before submitting.</li>
        <li>After submission, scores become read-only.</li>
    </ul>
</div>

</body>
</html>
