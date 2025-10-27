<?php
if (session_status() === PHP_SESSION_NONE) {
  session_name('ptc_admin');
  session_start();
}

require_once __DIR__ . '/../../core/auth/session.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
