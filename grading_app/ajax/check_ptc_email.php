<?php
require_once '../core/config/config.php';
require_once '../core/auth/session.php';
require_once '../core/db/connection.php';
require_once '../core/config/functions.php';

header('Content-Type: application/json');

$notFoundMessage = "Invalid email address. Your email address doesn't exist in the database. Contact Registrar or MIS for assistance.";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$email = isset($payload['email']) ? trim((string)$payload['email']) : '';

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'message' => 'Please enter a valid PTC email address.']);
    exit;
}

try {
    $userStmt = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1');
    $userStmt->execute([$email]);
    $userExists = $userStmt->fetchColumn();
    if ($userExists) {
        $message = 'You have an existing account. Please login instead';
        set_flash('error', $message);
        echo json_encode([
            'ok' => false,
            'message' => $message,
            'redirect' => BASE_URL . '/login.php',
        ]);
        exit;
    }

    $profile = lookupDirectoryProfileByEmail($pdo, $email);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Unable to check this email right now. Please try again later.']);
    exit;
}

if (!$profile) {
    echo json_encode(['ok' => false, 'message' => $notFoundMessage]);
    exit;
}

echo json_encode([
    'ok' => true,
    'data' => [
        'first_name' => $profile['first_name'],
        'last_name'  => $profile['last_name'],
        'role'       => $profile['role'],
    ],
]);
