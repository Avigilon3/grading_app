<?php
require_once '../includes/init.php';
requireProfessor();

$professor = requireProfessorRecord($pdo);
$professorId = (int)$professor['id'];

$sheetsStmt = $pdo->prepare(
    'SELECT gs.id,
            sec.section_name
       FROM grading_sheets gs
       JOIN sections sec ON sec.id = gs.section_id
      WHERE gs.professor_id = ?
   ORDER BY sec.section_name'
);
$sheetsStmt->execute([$professorId]);
$sheetOptions = $sheetsStmt->fetchAll(PDO::FETCH_ASSOC);
$sheetLabels = [];
foreach ($sheetOptions as $opt) {
    $sheetLabels[(int)($opt['id'] ?? 0)] = trim((string)($opt['section_name'] ?? ''));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sheetId = isset($_POST['grading_sheet_id']) ? (int)$_POST['grading_sheet_id'] : 0;
    $reason = trim($_POST['reason'] ?? '');

    if ($sheetId <= 0 || $reason === '') {
        set_flash('error', 'Please select a grading sheet and provide a reason.');
    } else {
        $insertStmt = $pdo->prepare(
            'INSERT INTO edit_requests (grading_sheet_id, professor_id, reason, status)
             VALUES (?, ?, ?, \'pending\')'
        );
        $insertStmt->execute([$sheetId, $professorId, $reason]);

        try {
            $professorName = function_exists('professorFullName')
                ? professorFullName($professor)
                : trim(($professor['first_name'] ?? '') . ' ' . ($professor['last_name'] ?? ''));
            if ($professorName === '' && !empty($_SESSION['user']['name'])) {
                $professorName = (string)$_SESSION['user']['name'];
            } elseif ($professorName === '' && !empty($_SESSION['user']['email'])) {
                $professorName = (string)$_SESSION['user']['email'];
            }
            if ($professorName === '') {
                $professorName = 'A professor';
            }

            $sectionName = trim($sheetLabels[$sheetId] ?? '');
            $message = sprintf(
                '%s is requesting to edit access to a grading sheet%s',
                $professorName,
                $sectionName !== '' ? ' for ' . $sectionName : ''
            );

            $adminStmt = $pdo->query(
                "SELECT id
                   FROM users
                  WHERE role IN ('admin','registrar','mis','super_admin')
                    AND status = 'ACTIVE'"
            );
            $adminIds = $adminStmt ? $adminStmt->fetchAll(PDO::FETCH_COLUMN) : [];

            if ($adminIds) {
                $notifStmt = $pdo->prepare(
                    'INSERT INTO notifications (user_id, type, message, is_read)
                     VALUES (:uid, :type, :message, 0)'
                );
                foreach ($adminIds as $adminUserId) {
                    $notifStmt->execute([
                        ':uid' => (int)$adminUserId,
                        ':type' => 'grading_sheet_edit_request',
                        ':message' => $message,
                    ]);
                }
            }
        } catch (Throwable $e) {
            // If notifications fail, do not block the request submission.
        }

        set_flash('success', 'Request submitted.');
    }

    header('Location: ./requests.php?submitted=1');
    exit;
}

$requestsStmt = $pdo->prepare(
    'SELECT er.id,
            er.reason,
            er.status,
            er.decided_at,
            gs.id AS grading_sheet_id,
            sec.section_name
       FROM edit_requests er
       JOIN grading_sheets gs ON gs.id = er.grading_sheet_id
       JOIN sections sec ON sec.id = gs.section_id
      WHERE er.professor_id = ?
   ORDER BY er.id DESC'
);
$requestsStmt->execute([$professorId]);
$requests = $requestsStmt->fetchAll(PDO::FETCH_ASSOC);
$activeRequests = [];
$archivedRequests = [];
foreach ($requests as $row) {
    if (($row['status'] ?? '') === 'pending') {
        $activeRequests[] = $row;
    } else {
        $archivedRequests[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requests</title>
    <link rel="stylesheet" href="../assets/css/professor.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="content">
        <div class="page-header">
            <h1>Edit Access Requests</h1>
            <p class="text-muted">Submit and monitor your grading sheet edit access requests.</p>
        </div>
        <?php show_flash(); ?>
        <?php if (isset($_GET['submitted'])): ?>
            <div class="alert alert-success">Your edit access request was submitted.</div>
        <?php endif; ?>

        <div class="requests-card">
            <h2 class="section-title">Request Edit Access</h2>
            <?php if (empty($sheetOptions)): ?>
                <p>You currently have no assigned grading sheets.</p>
            <?php else: ?>
                <form class="new-request-card" method="post">
                    <div class="request-icon" aria-hidden="true">
                      <span class="material-symbols-rounded">add_notes</span>
                    </div>
                    <div>
                        <p class="new-request-title">Request Grading Sheet Edit Access</p>
                        <p class="new-request-sub">Select the section that needs to be reopened and provide your reason.</p>
                    </div>
                    <label>
                        <span class="meta-label">Grading Sheet</span>
                        <select name="grading_sheet_id" class="form-control" required>
                            <option value="">Select section</option>
                            <?php foreach ($sheetOptions as $sheet): ?>
                                <option value="<?= (int)$sheet['id']; ?>">
                                    <?= htmlspecialchars($sheet['section_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span class="meta-label">Reason</span>
                        <textarea name="reason" rows="4" class="form-control" placeholder="Explain why edit access is needed." required></textarea>
                    </label>
                    <button type="submit" class="new-request-action">
                        <span class="material-symbols-rounded">add</span>
                        Submit Request
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <div class="requests-card">
            <h2 class="section-title">My Requests</h2>
            <?php if (empty($activeRequests)): ?>
                <p class="text-muted">You have no pending requests.</p>
            <?php else: ?>
                <div class="request-list">
                    <?php foreach ($activeRequests as $row): ?>
                        <?php
                            $statusClass = 'status-pending';
                            $statusText = 'Pending';
                            $noteClass = 'note-info';
                            $noteText = 'Awaiting review from the registrar.';
                            if ($row['status'] === 'approved') {
                                $statusClass = 'status-approved';
                                $statusText = 'Approved';
                                $noteClass = 'note-success';
                                $noteText = 'Access granted; the registrar will reopen the sheet.';
                            } elseif ($row['status'] === 'denied') {
                                $statusClass = 'status-denied';
                                $statusText = 'Denied';
                                $noteClass = 'note-error';
                                $noteText = 'Request denied.';
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
                                        <p class="request-title"><?= htmlspecialchars($row['section_name']); ?></p>
                                        <p class="request-sub">Reason: <?= htmlspecialchars($row['reason']); ?></p>
                                    </div>
                                </div>
                                <span class="status-pill <?= $statusClass; ?>">
                                    <svg class="status-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="9" />
                                        <?php if ($statusClass === 'status-approved'): ?>
                                            <path d="M9 12l2 2 4-4" />
                                        <?php elseif ($statusClass === 'status-denied'): ?>
                                            <path d="M8 8l8 8M16 8l-8 8" />
                                        <?php else: ?>
                                            <path d="M12 7v5l3 2" />
                                        <?php endif; ?>
                                    </svg>
                                    <?= htmlspecialchars($statusText); ?>
                                </span>
                            </div>
                            <div class="request-body">
                                <div class="status-note <?= $noteClass; ?>">
                                    <?= htmlspecialchars($noteText); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="requests-card">
            <h2 class="section-title">Archived Requests</h2>
            <?php if (empty($archivedRequests)): ?>
                <p class="text-muted">You have no archived requests yet.</p>
            <?php else: ?>
                <div class="request-list">
                    <?php foreach ($archivedRequests as $row): ?>
                        <?php
                            $statusClass = $row['status'] === 'approved' ? 'status-approved' : 'status-denied';
                            $statusText = ucfirst($row['status']);
                            $noteClass = $row['status'] === 'approved' ? 'note-success' : 'note-error';
                            $noteText = $row['status'] === 'approved'
                                ? 'Approved on ' . ($row['decided_at'] ? (new DateTimeImmutable($row['decided_at']))->format('M j, Y') : '—')
                                : 'Denied on ' . ($row['decided_at'] ? (new DateTimeImmutable($row['decided_at']))->format('M j, Y') : '—');
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
                                        <p class="request-title"><?= htmlspecialchars($row['section_name']); ?></p>
                                        <p class="request-sub">Reason: <?= htmlspecialchars($row['reason']); ?></p>
                                    </div>
                                </div>
                                <span class="status-pill <?= $statusClass; ?>">
                                    <svg class="status-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <?php if ($statusClass === 'status-approved'): ?>
                                            <path d="M9 12l2 2 4-4" />
                                        <?php else: ?>
                                            <path d="M8 8l8 8M16 8l-8 8" />
                                        <?php endif; ?>
                                        <circle cx="12" cy="12" r="9" />
                                    </svg>
                                    <?= htmlspecialchars($statusText); ?>
                                </span>
                            </div>
                            <div class="request-body">
                                <div class="status-note <?= $noteClass; ?>">
                                    <?= htmlspecialchars($noteText); ?>
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
                <li>Provide a clear reason to help the registrar review your request faster.</li>
                <li>Approved requests will reopen the grading sheet for edits within the allowed window.</li>
                <li>Denied requests can be appealed by submitting a new explanation.</li>
            </ul>
        </div>
    </main>
</div>
<script src="../assets/js/professor.js"></script>
</body>
<?php include '../includes/footer.php'; ?>
</html>
