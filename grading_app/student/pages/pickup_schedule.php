<?php
require_once __DIR__ . '/../includes/init.php';
requireStudent();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pickup_date = $_POST['pickup_date'];
    $pickup_time = $_POST['pickup_time'];
    $student_id = $_SESSION['user_id'];
    // Check for conflicts (e.g., no more than 5 pickups per time slot)
    $check = $conn->query("SELECT COUNT(*) as count FROM pickup_schedule WHERE pickup_date = '$pickup_date' AND pickup_time = '$pickup_time'");
    $row = $check->fetch_assoc();
    if ($row['count'] < 5) {  // Limit to 5 per slot
        $stmt = $conn->prepare("INSERT INTO pickup_schedule (student_id, pickup_date, pickup_time) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $student_id, $pickup_date, $pickup_time);
        $stmt->execute();
        echo "Pickup scheduled!";
    } else {
        echo "Slot full. Choose another time.";
    }
}
?>
<form method="POST">
    Pickup Date: <input type="date" name="pickup_date" required><br>
    Pickup Time: <input type="time" name="pickup_time" required><br>
    <button type="submit">Schedule</button>
</form>