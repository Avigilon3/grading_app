<?php
if (!function_exists('admin_time_ago')) {
    function admin_time_ago(?string $datetime): string
    {
        if (!$datetime) {
            return '';
        }

        try {
            $timestamp = strtotime($datetime);
            if (!$timestamp) {
                return '';
            }

            $diff = time() - $timestamp;
            if ($diff < 60) {
                return 'Just now';
            }

            $minutes = (int)floor($diff / 60);
            if ($minutes < 60) {
                return $minutes . ' minute' . ($minutes === 1 ? '' : 's') . ' ago';
            }

            $hours = (int)floor($minutes / 60);
            if ($hours < 24) {
                return $hours . ' hour' . ($hours === 1 ? '' : 's') . ' ago';
            }

            $days = (int)floor($hours / 24);
            return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
        } catch (Throwable $e) {
            return '';
        }
    }
}

$adminNotifications = [];
$adminUnreadCount = 0;

if (function_exists('adminIsLoggedIn') && adminIsLoggedIn() && isset($pdo)) {
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
            $adminNotifications = $notifStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            foreach ($adminNotifications as $notificationRow) {
                if (isset($notificationRow['is_read']) && (int)$notificationRow['is_read'] === 0) {
                    $adminUnreadCount++;
                }
            }
        } catch (Throwable $e) {
            $adminNotifications = [];
            $adminUnreadCount = 0;
        }
    }
}
?>
<header>
  <div class="left-header">
    <div class="logo">
      <img src="../assets/images/logo-ptc.png" height="40px" alt="logo"/>
    </div> 
    <div class="portal-name">Admin Portal</div>
  </div>


  <div class="right-header">
    <div class="notifications" data-notifications-dropdown>
      <button type="button" class="notif-trigger" data-notif-trigger aria-haspopup="true" aria-expanded="false">
        <span class="material-symbols-rounded">notifications</span>
        <?php if ($adminUnreadCount > 0): ?>
          <span class="notif-count"><?= (int)$adminUnreadCount; ?></span>
        <?php endif; ?>
      </button>
      <div class="notif-menu" role="menu">
        <div class="notif-header">
          <span class="notif-title">
            <span class="material-symbols-rounded" aria-hidden="true">notifications</span>
            Notifications
          </span>
          <div class="notif-header-right">
            <?php if ($adminUnreadCount > 0): ?>
              <span class="notif-total"><?= (int)$adminUnreadCount; ?></span>
              <form method="post" action="../includes/notifications_mark_read.php">
                <button type="submit" class="notif-mark-all">Mark all as read</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
        <div class="notif-list">
          <?php if (empty($adminNotifications)): ?>
            <div class="notif-empty">No notifications yet.</div>
          <?php else: ?>
            <?php foreach ($adminNotifications as $notificationRow): ?>
              <?php
                $isUnread = (int)($notificationRow['is_read'] ?? 0) === 0;
                $createdText = admin_time_ago($notificationRow['created_at'] ?? null);
              ?>
              <div class="notif-item<?= $isUnread ? ' notif-unread' : ''; ?>">
                <div class="notif-main">
                  <p class="notif-message"><?= htmlspecialchars($notificationRow['message'] ?? ''); ?></p>
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
        <?php if (adminIsLoggedIn()): ?>
          <div class="user-dropdown" data-user-dropdown>
            <button type="button" class="user-trigger" data-user-trigger aria-haspopup="true" aria-expanded="false">
              <span class="user-name"><?= htmlspecialchars(adminCurrentName()) ?></span>
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
