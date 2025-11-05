<?php

// --- Log user activity ---
function add_activity_log($pdo, $user_id, $action, $details = '') {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $details, $ip]);
    } catch (Exception $e) {
        // optional: silently ignore or log to file
    }
}

//flash
function show_flash() {
    // common keys you might use
    $types = ['success', 'error', 'warning', 'info'];

    foreach ($types as $t) {
        if (isset($_SESSION['flash'][$t])) {
            $msg = $_SESSION['flash'][$t];
            unset($_SESSION['flash'][$t]);

            // adjust classes to your CSS framework
            echo '<div class="alert alert-' . $t . '">' . htmlspecialchars($msg) . '</div>';
        }
    }

    // also support a generic flash message
    if (isset($_SESSION['flash']['message'])) {
        $msg = $_SESSION['flash']['message'];
        unset($_SESSION['flash']['message']);

        echo '<div class="alert alert-info">' . htmlspecialchars($msg) . '</div>';
    }
}
