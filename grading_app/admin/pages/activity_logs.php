<?php
// Activity Logs Page   
require_once __DIR__ . '/../../core/config/config.php';
require_once __DIR__ . '/../../core/auth/session.php';
require_once __DIR__ . '/../../core/auth/guards.php';
// Require admin (or registrar) login
requireLogin();
if (!in_array($_SESSION['user']['role'] ?? '', ['admin', 'registrar'])) {
  http_response_code(403);
  echo 'Unauthorized.';
  exit;
}
require_once __DIR__ . '/../includes/init.php';
$logs = [
  ['timestamp' => '2024-06
-01 10:15:30', 'user' => 'admin1', 'action' => 'Created new section BSIT-1A'],
  ['timestamp' => '2024-06-01 11:20:45', 
    'user' => 'prof_john', 'action' => 'Submitted grades for BSIT-1A'],
      ['timestamp' => '2024-06-02 09:05:12', 'user' => 'admin2', 'action' => 'Approved edit request for student ID 12345'],
];
?> 