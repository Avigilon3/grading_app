<?php  ?>
<body>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
</body>

<header>
  <div class="left-header">
    <div class="logo">
      <img src="../assets/images/logo-ptc.png" height="40px" alt="logo" />
    </div>
    <div class="portal-name">PTC Professor</div>
  </div>

  <div class="right-header">
    <div class="notifications">
      <a href="#" class="badge">
        <span class="material-symbols-rounded">notifications</span>
      </a>
    </div>
    <div class="user">
      <?php if (isLoggedIn()): ?>
          <div class="user-dropdown" data-user-dropdown>
            <button type="button" class="user-trigger" data-user-trigger aria-haspopup="true" aria-expanded="false">
              <span class="user-name"><?= htmlspecialchars(currentUserName()) ?></span>
              <span class="material-symbols-rounded" aria-hidden="true">keyboard_arrow_down</span>
            </button>
            <div class="dropdown-menu" role="menu">
              <a href="../pages/settings.php" role="menuitem">
                <span class="material-symbols-rounded">settings</span>
                  Settings</a>
              <a href="../../logout.php" role="menuitem">
                <span class="material-symbols-rounded">logout</span>
                  Logout</a>
            </div>
          </div>
      <?php endif; ?>
    </div>
  </div>
</header>
