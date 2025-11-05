<?php
require 'db.php';  // connect to database

try {
    $stmt = $pdo->query("SELECT id, username, role, email FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Query failed (' . __FILE__ . '): ' . $e->getMessage());
    http_response_code(500);
    echo '<p>An error occurred while loading the users list. Please try again later.</p>';
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Users List</title>
</head>
<body>
    <h1>Users List</h1>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Role</th>
            <th>Email</th>
        </tr>
        <?php foreach ($users as $user): ?>
        <tr>
            <td><?= htmlspecialchars($user['id']) ?></td>
            <td><?= htmlspecialchars($user['username']) ?></td>
            <td><?= htmlspecialchars($user['role']) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
