<?php
if (!function_exists('professor_time_ago')) {
    function professor_time_ago(?string $datetime): string
    {
        if (!$datetime) {
            return '';
        }
        $timestamp = strtotime($datetime);
        if (!$timestamp) {
            return '';
        }
        $diff = time() - $timestamp;
        if ($diff < 60) {
            return 'Just now';
        }
        $mins = floor($diff / 60);
        if ($mins < 60) {
            return $mins . ' minute' . ($mins === 1 ? '' : 's') . ' ago';
        }
        $hours = floor($mins / 60);
        if ($hours < 24) {
            return $hours . ' hour' . ($hours === 1 ? '' : 's') . ' ago';
        }
        $days = floor($hours / 24);
        return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
    }
}

$profNotifications = [];
$profUnreadCount = 0;

if (function_exists('isLoggedIn') && isLoggedIn() && isset($pdo)) {
    $currentUserId = $_SESSION['user']['id'] ?? null;
    if ($currentUserId) {
        try {
            $notifStmt = $pdo->prepare(
                "SELECT id, type, message, is_read, created_at
                   FROM notifications
                  WHERE user_id = :uid
                  ORDER BY created_at DESC
                  LIMIT 10"
            );
            $notifStmt->execute([':uid' => $currentUserId]);
            $profNotifications = $notifStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($profNotifications as $row) {
                if ((int)($row['is_read'] ?? 0) === 0) {
                    $profUnreadCount++;
                }
            }
        } catch (Throwable $e) {
            $profNotifications = [];
            $profUnreadCount = 0;
        }
    }
}
?>
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
    <div class="notifications" data-notifications-dropdown>
      <button type="button" class="notif-trigger" data-notif-trigger aria-haspopup="true" aria-expanded="false">
        <span class="material-symbols-rounded">notifications</span>
        <?php if ($profUnreadCount > 0): ?>
          <span class="notif-count"><?= (int)$profUnreadCount; ?></span>
        <?php endif; ?>
      </button>
      <div class="notif-menu" role="menu">
        <div class="notif-header">
          <span class="notif-title">
            <span class="material-symbols-rounded" aria-hidden="true">notifications</span>
            Notifications
          </span>
          <div class="notif-header-right">
            <?php if ($profUnreadCount > 0): ?>
              <span class="notif-total"><?= (int)$profUnreadCount; ?></span>
            <?php endif; ?>
            <?php if ($profUnreadCount > 0): ?>
              <form method="post" action="../includes/notifications_mark_read.php">
                <button type="submit" class="notif-mark-all">Mark all as read</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
        <div class="notif-list">
          <?php if (empty($profNotifications)): ?>
            <div class="notif-empty">You're all caught up. No new notifications.</div>
          <?php else: ?>
            <?php foreach ($profNotifications as $note): ?>
              <?php
                $isUnread = (int)($note['is_read'] ?? 0) === 0;
                $createdText = professor_time_ago($note['created_at'] ?? null);
              ?>
              <div class="notif-item<?= $isUnread ? ' notif-unread' : ''; ?>">
                <div class="notif-main">
                  <p class="notif-message"><?= htmlspecialchars($note['message'] ?? ''); ?></p>
                  <?php if ($createdText !== ''): ?>
                    <p class="notif-time"><?= htmlspecialchars($createdText); ?></p>
                  <?php endif; ?>
                </div>
                <?php if ($isUnread): ?>
                  <span class="notif-dot" aria-hidden="true"></span>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
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
