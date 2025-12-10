<?php
require_once 'core/config/config.php';
require_once 'core/db/connection.php';

$emails = [
    'jaguilar@paterostechnologicalcollege.edu.ph',
    'clamera@paterostechnologicalcollege.edu.ph',
    'mgnocon@paterostechnologicalcollege.edu.ph',
    'jramos@paterostechnologicalcollege.edu.ph',
    'flamit@paterostechnologicalcollege.edu.ph',
];

$defaultPassword = 'Prof123!'; // let them change it later

foreach ($emails as $email) {
    echo "Processing $email...\n";

    // 1) Find professor row by email
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name 
        FROM professors 
        WHERE ptc_email = ? 
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $prof = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$prof) {
        echo "  -> No professor record found for $email, skipping.\n";
        continue;
    }

    // 2) Check if a user already exists for this email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // 3) Create user
        $hash = password_hash($defaultPassword, PASSWORD_DEFAULT);

        $insert = $pdo->prepare("
            INSERT INTO users (email, password_hash, role, first_name, last_name, status)
            VALUES (?, ?, 'professor', ?, ?, 'ACTIVE')
        ");
        $insert->execute([
            $email,
            $hash,
            $prof['first_name'],
            $prof['last_name']
        ]);

        $userId = (int)$pdo->lastInsertId();
        echo "  -> Created user id $userId for $email\n";
    } else {
        $userId = (int)$user['id'];
        echo "  -> User already exists for $email (id = $userId)\n";
    }

    // 4) Link professors.user_id -> users.id
    $upd = $pdo->prepare("UPDATE professors SET user_id = ? WHERE id = ?");
    $upd->execute([$userId, $prof['id']]);

    echo "  -> Linked professor id {$prof['id']} to user id $userId\n";
}

echo "Done.\n";