<?php // assumes init.php already loaded by the page ?>
<style>
  /* Admin background image applied via shared header include */
  body {
    background-image: linear-gradient(rgba(249,249,249,0.90), rgba(249,249,249,0.90)), url('<?= BASE_URL; ?>/admin/assets/images/ptc.jpg');
    background-repeat: no-repeat;
    background-size: auto, cover;
    background-position: center center;
    background-attachment: scroll, fixed;
  }
  /* Ensure header remains solid and readable over background */
  header { background: var(--schoolcolor); }
  .portal-name { color: var(--background); }
  .user span, .user a { color: #fff; }
  .badge span { color: #fff; }
 </style>
<header>
  <div class="left-header">
    <div class="logo">
      <img src="<?= BASE_URL; ?>/admin/assets/images/logo-ptc.png" height="40" alt="PTC Logo" />
    </div>
    <div class="portal-name">PTC Admin</div>
  </div>


  <div class="right-header">
    <div class="notifications">
      <a href="#" class="badge" title="Notifications">
        <img src="<?= BASE_URL; ?>/admin/assets/images/notification.png" height="20" alt="Notifications" />
        <span id="notifications-count">0</span>
      </a>
    </div>
      <div class="user">
        <?php if (adminIsLoggedIn()): ?>
          <span><?= htmlspecialchars(adminCurrentName()) ?></span>
          <a href="<?= BASE_URL; ?>/logout.php">Logout</a>
        <?php endif; ?>
      </div>
  </div>
</header>
