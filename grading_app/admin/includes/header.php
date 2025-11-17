<?php // assumes init.php already loaded by the page ?>
<header>
  <div class="left-header">
    <div class="logo">
      <img src="../assets/images/logo-ptc.png" height="40px"alt="logo"/>
    </div> 
    <div class="portal-name">Admin Portal</div>
  </div>


  <div class="right-header">
    <div class="notifications">
      <a href="#" class="badge">
        <img src="../assets/images/notification.png" height="20px"alt="notification" <span id="notifications-count">0</span>
      </a>
    </div>
      <div class="user">
        <?php if (adminIsLoggedIn()): ?>
          <div class="user-dropdown" data-user-dropdown>
            <button type="button" class="user-trigger" data-user-trigger aria-haspopup="true" aria-expanded="false">
              <span class="user-name"><?= htmlspecialchars(adminCurrentName()) ?></span>
              <img src="../assets/images/dropdown.svg" alt="▾" class="dropdown-icon" aria-hidden="true" />
            </button>
            <div class="dropdown-menu" role="menu">
              <a href="../pages/settings.php" role="menuitem"> <img src="../assets/images/settings.svg">Settings</a>
              <a href="../../logout.php" role="menuitem"> <img src="../assets/images/logout.svg">Logout</a>
            </div>
          </div>
        <?php endif; ?>
      </div>
  </div>
</header>
