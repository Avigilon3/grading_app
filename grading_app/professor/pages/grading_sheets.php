<?php
require_once __DIR__ . '/../../core/config/config.php';
require_once __DIR__ . '/../../core/auth/session.php';
require_once __DIR__ . '/../../core/auth/guards.php';

// Require professor login
requireLogin();
if (($_SESSION['user']['role'] ?? '') !== 'professor') {
  http_response_code(403);
  echo 'Unauthorized.';
  exit;
}

require_once __DIR__ . '/../includes/init.php';

// Sample data: in a real app this would come from the database
$sheets = [
  ['section' => 'BSIT-1A', 'subject' => 'Programming 1', 'students' => 28],
  ['section' => 'BSIT-2B', 'subject' => 'Data Structures', 'students' => 32],
  ['section' => 'BSCS-3C', 'subject' => 'Algorithms', 'students' => 25],
];
?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Grading Sheets</title>
    <link rel="stylesheet" href="../assets/css/professor.css">
  </head>
  <body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <div class="layout">
      <?php include __DIR__ . '/../includes/sidebar.php'; ?>
      <main class="content">
        <?php show_flash(); ?>
        <h1>Grading Sheets</h1>

        <p class="flash info">Open a grading sheet to view or submit grades for a section.</p>

        <table style="width:100%;border-collapse:collapse">
          <thead>
            <tr style="text-align:left;">
              <th>Section</th>
              <th>Subject</th>
              <th>Students</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($sheets as $sheet): ?>
              <tr>
                <td><?= htmlspecialchars($sheet['section']) ?></td>
                <td><?= htmlspecialchars($sheet['subject']) ?></td>
                <td><?= (int)$sheet['students'] ?></td>
                <td><a class="nav-item" href="grading_sheet_view.php?section=<?= urlencode($sheet['section']) ?>">Open</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

      </main>
    </div>

    <script src="../assets/js/professor.js"></script>
    <script>
      // Example client-side hook: highlight nav if needed
      if (window.admin && typeof window.admin.highlightNav === 'function') {
        window.admin.highlightNav();
      }
    </script>
  </body>
</html>
