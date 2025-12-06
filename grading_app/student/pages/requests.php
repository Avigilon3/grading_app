<?php
require_once '../includes/init.php';
requireStudent();

$currentUserId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
$studentId = null;
$requests = [];
$activeRequests = [];
$archivedRequests = [];
$pageAlert = '';
$yearOptions = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
$semesterOptions = ['1st Semester', '2nd Semester'];

if ($currentUserId) {
    $studentStmt = $pdo->prepare('SELECT id FROM students WHERE user_id = :uid LIMIT 1');
    $studentStmt->execute([':uid' => $currentUserId]);
    $studentId = $studentStmt->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'cancel' && $studentId && !empty($_POST['request_id'])) {
        $requestId = (int)$_POST['request_id'];
        if ($requestId > 0) {
            $cancelStmt = $pdo->prepare(
                "UPDATE document_requests
                    SET status = 'cancelled'
                  WHERE id = :id
                    AND student_id = :sid
                    AND status = 'pending'"
            );
            $cancelStmt->execute([':id' => $requestId, ':sid' => $studentId]);
            header('Location: requests.php?cancelled=1');
            exit;
        }
    }

    if (isset($_POST['request_type'])) {
        if ($studentId) {
            $requestType = $_POST['request_type'] === 'certificate' ? 'certificate' : 'report';

            $yearLevel = trim($_POST['year_level'] ?? '');
            $semester = trim($_POST['semester'] ?? '');
            if (!in_array($yearLevel, $yearOptions, true)) {
                $yearLevel = '';
            }
            if (!in_array($semester, $semesterOptions, true)) {
                $semester = '';
            }

            $purposeParts = [];
            if ($yearLevel !== '') {
                $purposeParts[] = 'Year Level: ' . $yearLevel;
            }
            if ($semester !== '') {
                $purposeParts[] = 'Semester: ' . $semester;
            }
            $purpose = $purposeParts ? implode(' | ', $purposeParts) : null;

            $insert = $pdo->prepare('INSERT INTO document_requests (student_id, type, purpose, status, created_at) VALUES (:sid, :type, :purpose, :status, :created_at)');
            $insert->execute([
                ':sid' => $studentId,
                ':type' => $requestType,
                ':purpose' => $purpose,
                ':status' => 'pending',
                ':created_at' => date('Y-m-d H:i:s'),
            ]);

            try {
                $studentName = '';
                $profileStmt = $pdo->prepare(
                    'SELECT first_name, middle_name, last_name
                       FROM students
                      WHERE id = :sid
                      LIMIT 1'
                );
                $profileStmt->execute([':sid' => $studentId]);
                if ($row = $profileStmt->fetch(PDO::FETCH_ASSOC)) {
                    $nameParts = [];
                    foreach (['first_name', 'middle_name', 'last_name'] as $key) {
                        $value = trim((string)($row[$key] ?? ''));
                        if ($value !== '') {
                            $nameParts[] = $value;
                        }
                    }
                    $studentName = $nameParts ? implode(' ', $nameParts) : '';
                }

                if ($studentName === '' && !empty($_SESSION['user']['name'])) {
                    $studentName = $_SESSION['user']['name'];
                } elseif ($studentName === '' && !empty($_SESSION['user']['email'])) {
                    $studentName = $_SESSION['user']['email'];
                }
                if ($studentName === '') {
                    $studentName = 'A student';
                }

                // Human-friendly document type label
                $docLabel = ($requestType === 'certificate')
                    ? 'Certificate of Grades'
                    : 'Report of Grades';

                $message = sprintf('%s is requesting for %s', $studentName, $docLabel);

                // Notify all admin-type users
                $adminStmt = $pdo->query(
                    "SELECT id
                       FROM users
                      WHERE role IN ('admin','registrar','mis','super_admin')
                        AND status = 'ACTIVE'"
                );
                $adminIds = $adminStmt->fetchAll(PDO::FETCH_COLUMN);

                if ($adminIds) {
                    $notifStmt = $pdo->prepare(
                        'INSERT INTO notifications (user_id, type, message, is_read)
                         VALUES (:uid, :type, :message, 0)'
                    );
                    foreach ($adminIds as $adminUserId) {
                        $notifStmt->execute([
                            ':uid' => (int)$adminUserId,
                            ':type' => 'document_request',
                            ':message' => $message,
                        ]);
                    }
                }
            } catch (Throwable $e) {
            }

            header('Location: requests.php?submitted=1');
            exit;
        } else {
            $pageAlert = 'We could not find your student record.';
        }
    }
}

if ($studentId) {
    $requestStmt = $pdo->prepare('SELECT * FROM document_requests WHERE student_id = :sid ORDER BY id DESC');
    $requestStmt->execute([':sid' => $studentId]);
    $requests = $requestStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($requests as $row) {
        if (in_array($row['status'], ['released', 'cancelled'], true)) {
            $archivedRequests[] = $row;
        } else {
            $activeRequests[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requests</title>
    <link rel="stylesheet" href="../assets/css/student.css">
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <div class="layout">
        <?php include '../includes/sidebar.php'; ?>
        <main class="content">
            <div class="page-header">
              <h1>Requests</h1>
              <p class="text-muted">
                Request documents and track their status for pickup at the Registrar's Office
              </p>
            </div>

          <?php if (isset($_GET['submitted'])): ?>
            <div class="alert-simple alert-success">Your request was submitted.</div>
          <?php endif; ?>
          <?php if ($pageAlert): ?>
            <div class="alert-simple alert-warning"><?php echo htmlspecialchars($pageAlert); ?></div>
          <?php endif; ?>

            <div class="requests-card">
              <h2 class="section-title">Request New Document</h2>
              <div class="new-request-grid">
                <form class="new-request-card" method="post">
                  <input type="hidden" name="request_type" value="report">
                  <div class="request-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6">
                      <path d="M7 3h10l3 3v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke="currentColor" />
                      <path d="M17 3v4h4" stroke="currentColor" />
                      <path d="M12 9v6M9 12h6" stroke="currentColor" />
                    </svg>
                  </div>
                  <p class="new-request-title">Report of Grades</p>
                  <p class="new-request-sub">Official grade report with school dry seal</p>
                  <div class="row-grid cols-2" style="margin-top:8px;">
                    <label>
                      <span class="meta-label">Year Level</span>
                      <select name="year_level" class="form-control">
                        <option value="">Select year level</option>
                        <?php foreach ($yearOptions as $opt): ?>
                          <option value="<?php echo htmlspecialchars($opt); ?>"><?php echo htmlspecialchars($opt); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </label>
                    <label>
                      <span class="meta-label">Semester</span>
                      <select name="semester" class="form-control">
                        <option value="">Select semester</option>
                        <?php foreach ($semesterOptions as $opt): ?>
                          <option value="<?php echo htmlspecialchars($opt); ?>"><?php echo htmlspecialchars($opt); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </label>
                  </div>
                  <button type="submit" class="new-request-action">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M12 5v14M5 12h14" />
                    </svg>
                    Submit Request
                  </button>
                </form>
                <form class="new-request-card" method="post">
                  <input type="hidden" name="request_type" value="certificate">
                  <div class="request-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6">
                      <path d="M7 3h10l3 3v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke="currentColor" />
                      <path d="M17 3v4h4" stroke="currentColor" />
                      <path d="M12 9v6M9 12h6" stroke="currentColor" />
                    </svg>
                  </div>
                  <p class="new-request-title">Certificate of Grades</p>
                  <p class="new-request-sub">Official certificate with school dry seal</p>
                  <div class="row-grid cols-2" style="margin-top:8px;">
                    <label>
                      <span class="meta-label">Year Level</span>
                      <select name="year_level" class="form-control">
                        <option value="">Select year level</option>
                        <?php foreach ($yearOptions as $opt): ?>
                          <option value="<?php echo htmlspecialchars($opt); ?>"><?php echo htmlspecialchars($opt); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </label>
                    <label>
                      <span class="meta-label">Semester</span>
                      <select name="semester" class="form-control">
                        <option value="">Select semester</option>
                        <?php foreach ($semesterOptions as $opt): ?>
                          <option value="<?php echo htmlspecialchars($opt); ?>"><?php echo htmlspecialchars($opt); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </label>
                  </div>
                  <button type="submit" class="new-request-action">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M12 5v14M5 12h14" />
                    </svg>
                    Submit Request
                  </button>
                </form>
              </div>
            </div>

            <!-- My requests -->
          <div class="requests-wrapper">
            <div class="requests-card">
              <h2 class="section-title">My Requests</h2>
              <?php if (empty($activeRequests)): ?>
                <div class="empty-requests">You haven't requested grade related documents yet.</div>
              <?php else: ?>
                <div class="request-list">
                  <?php foreach ($activeRequests as $row): ?>
                    <?php
                      $typeLabel = ($row['type'] === 'certificate') ? 'Certificate of Grades' : 'Report of Grades';
                      $typeSub = ($row['type'] === 'certificate') ? 'Official certificate with school dry seal' : 'Official grade report with school dry seal';

                      $statusClass = 'status-pending';
                      $statusText = 'Pending';
                      $noteClass = 'note-warn';
                      $noteText = 'Your request is being processed. You will be notified of the pickup date and time.';

                      if ($row['status'] === 'scheduled') {
                          $statusClass = 'status-approved';
                          $statusText = 'Approved';
                          $noteText = 'Pickup schedule set.';
                      } elseif ($row['status'] === 'ready') {
                          $statusClass = 'status-approved';
                          $statusText = 'Approved';
                          $noteClass = 'note-success';
                          $noteText = 'Ready for pickup at the Registrar\'s Office.';
                      } elseif ($row['status'] === 'cancelled') {
                          $statusClass = 'status-pending';
                          $statusText = 'Cancelled';
                          $noteClass = 'note-warn';
                          $noteText = 'You cancelled this request.';
                      } elseif ($row['status'] === 'released') {
                          $statusClass = 'status-approved';
                          $statusText = 'Released';
                          $noteClass = 'note-success';
                          $noteText = 'Released to you.';
                      }

                      $requestedText = 'Requested date not recorded';
                      if (isset($row['created_at']) && $row['created_at']) {
                          $requestedText = date('M j, Y', strtotime($row['created_at']));
                      } elseif (isset($row['requested_at']) && $row['requested_at']) {
                          $requestedText = date('M j, Y', strtotime($row['requested_at']));
                      }

                      $scheduleDate = '';
                      $scheduleTime = '';
                      if (!empty($row['scheduled_at'])) {
                          $timestamp = strtotime($row['scheduled_at']);
                          if ($timestamp) {
                              $scheduleDate = date('M j, Y', $timestamp);
                              $scheduleTime = date('g:i A', $timestamp);
                          }
                      }
                      $releaseText = '';
                      if (!empty($row['released_at'])) {
                          $releaseTime = strtotime($row['released_at']);
                          if ($releaseTime) {
                              $releaseText = date('M j, Y', $releaseTime);
                          }
                      }
                    ?>
                    <div class="request-card">
                      <div class="request-header">
                        <div class="request-meta">
                          <div class="request-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6">
                              <path d="M7 3h10l3 3v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke="currentColor" />
                              <path d="M17 3v4h4" stroke="currentColor" />
                              <path d="M9 13h6M9 17h3M9 9h6" stroke="currentColor" />
                            </svg>
                          </div>
                          <div>
                            <p class="request-title"><?php echo htmlspecialchars($typeLabel); ?></p>
                            <p class="request-sub"><?php echo htmlspecialchars($typeSub); ?></p>
                          </div>
                        </div>
                        <span class="status-pill <?php echo $statusClass; ?>">
                          <svg class="status-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <?php if ($statusClass === 'status-approved'): ?>
                              <path d="M9 12l2 2 4-4" />
                            <?php else: ?>
                              <path d="M12 7v5l3 2" />
                            <?php endif; ?>
                            <circle cx="12" cy="12" r="9" />
                          </svg>
                          <?php echo htmlspecialchars($statusText); ?>
                        </span>
                      </div>
                      <div class="request-body">
                        <div class="request-meta-line">
                          <span class="muted-label">Requested: <?php echo htmlspecialchars($requestedText); ?></span>
                        </div>
                        <div class="status-note <?php echo $noteClass; ?>">
                          <?php if ($row['status'] === 'ready' || $row['status'] === 'scheduled'): ?>
                            <span class="note-label">
                              <svg class="status-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 12l2 2 4-4" />
                                <circle cx="12" cy="12" r="9" />
                              </svg>
                              Ready for Pickup
                            </span><br>
                            <?php if ($scheduleDate): ?>
                              Date: <?php echo htmlspecialchars($scheduleDate); ?><br>
                            <?php endif; ?>
                            <?php if ($scheduleTime): ?>
                              Time: <?php echo htmlspecialchars($scheduleTime); ?><br>
                            <?php endif; ?>
                            Location: Registrar's Office
                          <?php elseif ($row['status'] === 'released' && $releaseText): ?>
                            Released on <?php echo htmlspecialchars($releaseText); ?>.
                          <?php else: ?>
                            <?php echo htmlspecialchars($noteText); ?>
                          <?php endif; ?>
                        </div>
                        <?php if ($row['status'] === 'pending'): ?>
                          <form method="post" class="cancel-request-form">
                            <input type="hidden" name="action" value="cancel">
                            <input type="hidden" name="request_id" value="<?php echo (int)$row['id']; ?>">
                            <button type="submit" class="cancel-btn">Cancel Request</button>
                          </form>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
          </div>

          <div class="requests-card">
              <h2 class="section-title">Archived Requests</h2>
              <?php if (empty($archivedRequests)): ?>
                <div class="empty-requests">You don't have archived requests yet.</div>
              <?php else: ?>
                <div class="request-list">
                  <?php foreach ($archivedRequests as $row): ?>
                    <?php
                      $typeLabel = ($row['type'] === 'certificate') ? 'Certificate of Grades' : 'Report of Grades';
                      $typeSub = ($row['type'] === 'certificate') ? 'Official certificate with school dry seal' : 'Official grade report with school dry seal';

                      $statusClass = 'status-approved';
                      $statusText = 'Released';
                      $noteClass = 'note-success';
                      $noteText = 'Released to you.';

                      if ($row['status'] === 'cancelled') {
                          $statusClass = 'status-pending';
                          $statusText = 'Cancelled';
                          $noteClass = 'note-warn';
                          $noteText = 'You cancelled this request.';
                      }

                      $requestedText = 'Requested date not recorded';
                      if (!empty($row['created_at'])) {
                          $requestedText = date('M j, Y', strtotime($row['created_at']));
                      } elseif (!empty($row['requested_at'])) {
                          $requestedText = date('M j, Y', strtotime($row['requested_at']));
                      }

                      $releaseText = '';
                      if (!empty($row['released_at'])) {
                          $releaseTime = strtotime($row['released_at']);
                          if ($releaseTime) {
                              $releaseText = date('M j, Y', $releaseTime);
                          }
                      }
                    ?>
                    <div class="request-card">
                      <div class="request-header">
                        <div class="request-meta">
                          <div class="request-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6">
                              <path d="M7 3h10l3 3v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke="currentColor" />
                              <path d="M17 3v4h4" stroke="currentColor" />
                              <path d="M9 13h6M9 17h3M9 9h6" stroke="currentColor" />
                            </svg>
                          </div>
                          <div>
                            <p class="request-title"><?php echo htmlspecialchars($typeLabel); ?></p>
                            <p class="request-sub"><?php echo htmlspecialchars($typeSub); ?></p>
                          </div>
                        </div>
                        <span class="status-pill <?php echo $statusClass; ?>">
                          <svg class="status-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <?php if ($statusClass === 'status-approved'): ?>
                              <path d="M9 12l2 2 4-4" />
                            <?php else: ?>
                              <path d="M12 7v5l3 2" />
                            <?php endif; ?>
                            <circle cx="12" cy="12" r="9" />
                          </svg>
                          <?php echo htmlspecialchars($statusText); ?>
                        </span>
                      </div>
                      <div class="request-body">
                        <div class="request-meta-line">
                          <span class="muted-label">Requested: <?php echo htmlspecialchars($requestedText); ?></span>
                        </div>
                        <div class="status-note <?php echo $noteClass; ?>">
                          <?php if ($row['status'] === 'released' && $releaseText): ?>
                            Released on <?php echo htmlspecialchars($releaseText); ?>.
                          <?php else: ?>
                            <?php echo htmlspecialchars($noteText); ?>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
          </div>
          <div class="info-card">
              <strong>Important Information</strong>
              <ul class="info-list">
                <li>All documents come with the official school dry seal which is subject to fees.</li>
                <li>Processing typically takes 3-5 business days.</li>
                <li>You must pick up the documents personally at the Registrar's Office.</li>
                <li>Please bring a valid ID when picking up your documents.</li>
                <li>The registrar's office is open Monday to Friday, 8:00 AM - 5:00 PM.</li>
              </ul>
            </div>
          </div>

        </main>

    </div>

    
</body>
<script src="../assets/js/student.js"></script>
</html>
