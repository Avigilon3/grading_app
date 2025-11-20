<?php
session_start();

// Database connection
$host = 'http://localhost/grading_app/';
$dbname = 'professor_portal';
$user = 'root';    // teacher1
$pass = '';        // password

$mysqli = new mysqli($host, $user, $pass, $dbname);
if ($mysqli->connect_error) {
    die("DB Connection failed: " . $mysqli->connect_error);
}

// Simulate logged-in professor
$professor_id = 1; // For demo; replace with session data when implemented

// Handle form submission for new request
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = intval($_POST['class_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');

    // Basic validation
    if ($class_id <= 0) {
        $message = "Please select a section.";
    } elseif ($reason === '') {
        $message = "Please provide a reason for your request.";
    } else {
        // Insert new request
        $stmt = $mysqli->prepare("INSERT INTO requests (professor_id, class_id, reason) VALUES (?, ?, ?)");
        $stmt->bind_param('iis', $professor_id, $class_id, $reason);
        if ($stmt->execute()) {
            $message = "Your request has been submitted.";
        } else {
            $message = "Failed to submit request. Please try again.";
        }
        $stmt->close();
    }
}

// Fetch professor's classes for dropdown
$stmt = $mysqli->prepare("SELECT id, code, section FROM classes WHERE professor_id = ? ORDER BY code, section");
$stmt->bind_param('i', $professor_id);
$stmt->execute();
$classes_result = $stmt->get_result();
$classes = $classes_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch requests for this professor ordered by date descending
$stmt = $mysqli->prepare("
    SELECT r.*, c.code, c.section 
    FROM requests r 
    JOIN classes c ON r.class_id = c.id 
    WHERE r.professor_id = ?
    ORDER BY r.date_submitted DESC
");
$stmt->bind_param('i', $professor_id);
$stmt->execute();
$requests_result = $stmt->get_result();
$requests = $requests_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Helper to format date
function format_date($dt) {
    if (!$dt) return '';
    return date('M j, Y', strtotime($dt));
}

// Map status to badge color & label
function status_badge($status) {
    switch ($status) {
        case 'approved': return '<span style="background:#d3e6d4;color:#3a6b36;padding:3px 8px;border-radius:12px;font-size:0.8rem;">APPROVED</span>';
        case 'pending': return '<span style="background:#fff3cd;color:#856404;padding:3px 8px;border-radius:12px;font-size:0.8rem;">PENDING</span>';
        case 'denied': return '<span style="background:#f8d7da;color:#721c24;padding:3px 8px;border-radius:12px;font-size:0.8rem;">DENIED</span>';
        default: return '';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Professor Portal - Requests</title>
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0; background: #f9fafb; color: #212529;
    }
    .sidebar {
        width: 200px;
        position: fixed;
        height: 100vh;
        background-color: #4f744e;
        padding: 20px;
        color: white;
    }
    .sidebar a {
        display: block;
        color: white;
        text-decoration: none;
        margin: 10px 0;
        padding: 8px 12px;
        border-radius: 6px;
    }
    .sidebar a.active, .sidebar a:hover {
        background-color: #6f9568;
    }
    .main {
        margin-left: 220px;
        padding: 20px;
        max-width: 900px;
    }
    h1 {
        margin-top: 0;
        color: #37572b;
    }
    form {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 30px;
        box-shadow: 0 0 8px #ddd;
    }
    label {
        display: block;
        font-weight: 600;
        margin-bottom: 6px;
        margin-top: 15px;
    }
    select, textarea {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ccd0d5;
        border-radius: 8px;
        font-size: 1rem;
        font-family: inherit;
        resize: vertical;
    }
    textarea {
        min-height: 90px;
    }
    .buttons {
        margin-top: 15px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }
    button {
        background-color: #5a7a4f;
        border: none;
        color: white;
        padding: 10px 18px;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        font-size: 1rem;
        transition: background-color 0.3s ease;
    }
    button:hover {
        background-color: #4a693e;
    }
    button.cancel {
        background-color: #eee;
        color: #555;
    }
    .message {
        padding: 10px 15px;
        margin-bottom: 20px;
        background-color: #d9f7d9;
        border-left: 5px solid #4f744e;
        color: #37572b;
        font-weight: 600;
        border-radius: 8px;
        max-width: 900px;
    }
    /* Each request card */
    .request-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 22px;
        box-shadow: 0 0 8px #ddd;
        color: #202020;
    }
    .request-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        font-weight: 700;
        color: #37572b;
    }
    .request-dates {
        font-weight: 400;
        font-size: 0.9rem;
        color: #555;
        display: flex;
        gap: 15px;
        margin-bottom: 10px;
    }
    .request-text {
        margin-bottom: 12px;
        white-space: pre-line;
        background: #f9f9f9;
        border-radius: 6px;
        padding: 10px 14px;
        color: #2f2f2f;
        font-weight: 500;
    }
    .label {
        font-weight: 600;
        margin-bottom: 4px;
        color: #3a6b36;
    }
</style>
</head>
<body>

<div class="sidebar">
    <h2>Professor Portal</h2>
    <a href="dashboard.php">Dashboard</a>
    <a href="classes.php">Classes</a>
    <a href="grading_sheet.php">Grading Sheets</a>
    <a href="requests.php" class="active">Requests</a>
</div>

<div class="main">
    <h1>Requests</h1>
    <p>Request to reopen grading sheets beyond deadline</p>

    <?php if (!empty($message)): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post" action="requests.php">
        <label for="class_id">Section:</label>
        <select id="class_id" name="class_id" required>
            <option value="">-- Select Section --</option>
            <?php foreach ($classes as $class): ?>
                <option value="<?= $class['id'] ?>">
                    <?= htmlspecialchars($class['code'] . ' - ' . $class['section']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="reason">Reason for Request</label>
        <textarea id="reason" name="reason" rows="5" placeholder="Please provide a detailed reason for reopening this grading sheet..." required></textarea>

        <div class="buttons">
            <button type="reset" class="cancel">Cancel</button>
            <button type="submit">Submit Request</button>
        </div>
    </form>

    <?php if (count($requests) === 0): ?>
        <p>No requests submitted yet.</p>
    <?php else: ?>
        <?php foreach ($requests as $r): ?>
            <div class="request-card">
                <div class="request-header">
                    <div><?= htmlspecialchars($r['code'] . ' - ' . $r['section']) ?></div>
                    <div><?= status_badge($r['status']) ?></div>
                </div>
                <div class="request-dates">
                    <div><strong>Submitted:</strong> <?= format_date($r['date_submitted']) ?></div>
                    <div><strong>Responded:</strong> <?= ($r['date_responded'] ? format_date($r['date_responded']) : '-') ?></div>
                </div>
                <div class="label">Reason:</div>
                <div class="request-text"><?= nl2br(htmlspecialchars($r['reason'])) ?></div>
                <?php if ($r['admin_response'] && $r['status'] !== 'pending'): ?>
                    <div class="label">Admin Response:</div>
                    <div class="request-text"><?= nl2br(htmlspecialchars($r['admin_response'])) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>
