<?php // assumes init.php already loaded by the page ?>
<header>
  <div class="left-header">

    <div class="logo">
      <img src="../assets/images/logo-ptc.png" height="40px"alt="logo"/>
    </div> 
    <div class="portal-name">PTC Admin</div>
    <!-- <div class="spacer"></div> -->

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
