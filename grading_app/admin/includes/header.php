<?php
require_once __DIR__ . '/../../core/config/config.php';
require_once __DIR__ . '/../../core/auth/session.php';
require_once __DIR__ . '/../../core/config/functions.php'; 
require_once __DIR__ . '/functions.php';  
?>
<header class="header">
  <div class="logo">
    <img src="/Git/grading_app/grading_app/admin/assets/images/logo-ptc.png" height="50rem"alt="logo"/>
  </div> 
  <div class="portal name">PTC Admin</div>
  <div class="spacer"></div>
  <div class="notifications">
    <a href="#" class="badge">
      Notifications <span id="notifications-count">0</span>
    </a>
  </div>
  <div class="user">
    <?php if (isLoggedIn()): ?>
      <span><?= htmlspecialchars(currentUserName()) ?></span>
      <a href="<?= BASE_URL; ?>/logout.php">Logout</a>
    <?php endif; ?>
  </div>
</header>
