<?php // top bar ?>
<header class="header">
  <div class="logo">PTC Admin</div>
  <div class="spacer"></div>
  <div class="notifications">
    <a href="./activity_logs.php" class="badge">
      Edit Requests <span id="edit-req-count">0</span>
    </a>
    <a href="./grading_sheets.php" class="badge">
      Submissions <span id="submissions-count">0</span>
    </a>
  </div>
  <div class="user">
    <?php if (isLoggedIn()): ?>
      <span><?= htmlspecialchars($_SESSION['admin']['name']) ?></span>
      <a href="./login.php?logout=1">Logout</a>
    <?php endif; ?>
  </div>
</header>
