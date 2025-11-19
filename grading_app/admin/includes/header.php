<?php // assumes init.php already loaded by the page ?>
<style>
  body {
    background-image: linear-gradient(rgba(249,249,249,0.90), rgba(249,249,249,0.90)), url('<?= BASE_URL; ?>/admin/assets/images/ptc.jpg');
    background-repeat: no-repeat;
    background-size: auto, cover;
    background-position: center center;
    background-attachment: scroll, fixed;
  }

  header { background: var(--schoolcolor); }
  .portal-name { color: var(--background); }
  .user span, .user a { color: #fff; }
  .badge span { color: #fff; }
</style>

<header>
  <div class="left-header">
    <div class="logo">
      <img src="../assets/images/logo-ptc.png" height="40" alt="logo" />
    </div>
    <div class="portal-name">PTC Admin</div>
  </div>

  <div class="right-header">
    <div class="notifications">
      <a href="#" class="badge" aria-label="Notifications">
        <img src="../assets/images/notification.png" height="20" alt="Notifications" />
        <span id="notifications-count">0</span>
      </a>
    </div>
    <div class="user">
      <?php if (adminIsLoggedIn()): ?>
        <div class="user-dropdown" data-user-dropdown>
          <button type="button" class="user-trigger" data-user-trigger aria-haspopup="true" aria-expanded="false">
            <span class="user-name"><?= htmlspecialchars(adminCurrentName()) ?></span>
            <img src="../assets/images/dropdown.svg" alt="" class="dropdown-icon" aria-hidden="true" />
          </button>
          <div class="dropdown-menu" role="menu">
            <a href="../pages/settings.php" role="menuitem">
              <img src="../assets/images/settings.svg" alt="" />Settings
            </a>
            <a href="../../logout.php" role="menuitem">
              <img src="../assets/images/logout.svg" alt="" />Logout
            </a>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</header>
