<?php  ?>
<header>
  <div class="left-header">
    <div class="logo">
      <img src="<?= BASE_URL; ?>/professor/assets/images/logo-ptc.png" height="40px" alt="logo" />
    </div>
    <div class="portal-name">PTC Professor</div>
  </div>

  <div class="right-header">
    <div class="notifications">
      <a href="#" class="badge">
        <img src="<?= BASE_URL; ?>/professor/assets/images/notification.png" height="20px" alt="notification" />
        <span id="notifications-count">0</span>
      </a>
    </div>
    <div class="user">
      <?php if (isLoggedIn()): ?>
        <span><?= htmlspecialchars(currentUserName()) ?></span>
        <a href="<?= BASE_URL; ?>/logout.php">Logout</a>
      <?php endif; ?>
    </div>
  </div>
</header>
