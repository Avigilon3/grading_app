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
      <img src="../assets/images/logo-ptc.png" height="40px"alt="logo"/>
    </div> 
    <div class="portal-name">PTC Admin</div>
  </div>


  <div class="right-header">
    <div class="notifications">
      <a href="#" class="badge">
        <img src="../assets/images/notification.png" height="20px"alt="notification" <span id="notifications-count">0</span>
      </a>
    </div>
      <div class="user">
        <?php if (adminIsLoggedIn()): ?>
          <span><?= htmlspecialchars(adminCurrentName()) ?></span>
          <a href="../../logout.php">Logout</a>
        <?php endif; ?>
      </div>
  </div>
</header>
