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
        set_flash('success', 'Request submitted.');
    }

    header('Location: ./notifications.php');
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requests</title>
    <link rel="stylesheet" href="../assets/css/professor.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="content">
        <h1>Requests</h1>
        <?php show_flash(); ?>

        <?php if (empty($sheetOptions)): ?>
            <p>No grading sheets are assigned to you yet.</p>
        <?php else: ?>
            <form method="post" class="card">
                <label for="grading_sheet_id">Grading Sheet</label>
                <select id="grading_sheet_id" name="grading_sheet_id" required>
                    <option value="">-- Select sheet --</option>
                    <?php foreach ($sheetOptions as $sheet): ?>
                        <option value="<?= (int)$sheet['id']; ?>">
                            <?= htmlspecialchars($sheet['section_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="reason">Reason</label>
                <textarea id="reason" name="reason" rows="4" placeholder="Explain why you need the sheet reopened." required></textarea>

                <button type="submit">Submit Request</button>
            </form>
        <?php endif; ?>

        <h2>Submitted Requests</h2>
        <?php if (empty($requests)): ?>
            <p>No requests yet.</p>
        <?php else: ?>
            <div class="request-list">
                <?php foreach ($requests as $request): ?>
                    <article class="card">
                        <header>
                            <strong><?= htmlspecialchars($request['section_name']); ?></strong>
                            <span><?= htmlspecialchars(strtoupper($request['status'])); ?></span>
                        </header>
                        <p><?= nl2br(htmlspecialchars($request['reason'])); ?></p>
                        <?php if ($request['decided_at']): ?>
                            <small>Reviewed: <?= htmlspecialchars((new DateTimeImmutable($request['decided_at']))->format('M j, Y')); ?></small>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>
<script src="../assets/js/professor.js"></script>
</body>
<?php include '../includes/footer.php'; ?>
</html>
