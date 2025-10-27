<?php require_once __DIR__ . '/../../core/config/config.php'; ?>

<header class="header">
  <div class="logo">PTC Admin</div>
  <div class="spacer"></div>
  <div class="notifications">
    <a href="#" class="badge">
      Edit Requests <span id="edit-req-count">0</span>
    </a>
    <a href="#" class="badge">
      Submissions <span id="submissions-count">0</span>
    </a>
  </div>
  <div class="user">
    <?php if (isLoggedIn()): ?>
      <span><?= htmlspecialchars($_SESSION['admin']['name']) ?></span>
      <a href="<?php echo BASE_URL; ?>/logout.php">Logout</a>
    <?php endif; ?>
  </div>
</header>
