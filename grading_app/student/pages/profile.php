<?php
require_once '../includes/init.php';
requireStudent();

$currentUserId = (int)($_SESSION['user']['id'] ?? 0);
$errors = [];
$success = null;
$uploadDirectory = dirname(__DIR__) . '/assets/uploads/profile';
$uploadWebPath = '../assets/uploads/profile';

if ($currentUserId <= 0) {
    header('Location: ../../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];
    $maxBytes = 2 * 1024 * 1024;
    $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $errors[] = 'Please choose a valid image to upload.';
    } elseif (($file['size'] ?? 0) > $maxBytes) {
        $errors[] = 'Profile pictures must be 2 MB or smaller.';
    } else {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? finfo_file($finfo, $file['tmp_name']) : false;
        if ($finfo) {
            finfo_close($finfo);
        }

        if (!isset($allowedTypes[$mimeType])) {
            $errors[] = 'Only JPG, PNG, and WebP images are supported.';
        } elseif (!is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'The uploaded file could not be verified.';
        } elseif (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true)) {
            $errors[] = 'The profile picture folder could not be created.';
        } else {
            $extension = $allowedTypes[$mimeType];
            $targetPath = $uploadDirectory . '/' . $currentUserId . '.' . $extension;
            foreach (array_values($allowedTypes) as $oldExtension) {
                $oldPath = $uploadDirectory . '/' . $currentUserId . '.' . $oldExtension;
                if ($oldPath !== $targetPath && is_file($oldPath)) {
                    unlink($oldPath);
                }
            }

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $success = 'Profile picture updated successfully.';
            } else {
                $errors[] = 'The profile picture could not be saved.';
            }
        }
    }
}

$stmt = $pdo->prepare(
    "SELECT s.*, u.email AS account_email, u.status AS account_status
       FROM students s
  LEFT JOIN users u ON u.id = s.user_id
      WHERE s.user_id = :user_id
      LIMIT 1"
);
$stmt->execute([':user_id' => $currentUserId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$firstName = trim((string)($student['first_name'] ?? $_SESSION['user']['first_name'] ?? ''));
$middleName = trim((string)($student['middle_name'] ?? ''));
$lastName = trim((string)($student['last_name'] ?? $_SESSION['user']['last_name'] ?? ''));
$fullName = trim(implode(' ', array_filter([$firstName, $middleName, $lastName])));
$fullName = $fullName !== '' ? $fullName : currentUserName();
$email = trim((string)($student['ptc_email'] ?? $student['account_email'] ?? $_SESSION['user']['email'] ?? ''));
$studentNumber = trim((string)($student['student_id'] ?? ''));
$yearValue = trim((string)($student['year_level'] ?? ''));
$yearLabels = ['1' => '1st Year', '2' => '2nd Year', '3' => '3rd Year', '4' => '4th Year'];
$yearLevel = $yearLabels[$yearValue] ?? ($yearValue !== '' ? $yearValue : 'Not assigned');
$section = trim((string)($student['section'] ?? '')) ?: 'Not assigned';
$studentStatus = trim((string)($student['status'] ?? '')) ?: 'Not assigned';
$accountStatus = trim((string)($student['account_status'] ?? '')) ?: 'ACTIVE';

$profileFields = [$fullName, $email, $studentNumber, $yearValue, $section === 'Not assigned' ? '' : $section];
$profileAccuracy = (int)round((count(array_filter($profileFields)) / count($profileFields)) * 100);

$profilePicture = null;
$profilePicturePath = null;
foreach (['jpg', 'png', 'webp'] as $extension) {
    $candidate = $uploadDirectory . '/' . $currentUserId . '.' . $extension;
    if (is_file($candidate)) {
        $profilePicturePath = $candidate;
        $profilePicture = $uploadWebPath . '/' . $currentUserId . '.' . $extension;
        break;
    }
}
$initials = strtoupper(substr($firstName !== '' ? $firstName : $fullName, 0, 1) . substr($lastName, 0, 1));
$initials = $initials !== '' ? $initials : 'ST';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile</title>
    <link rel="stylesheet" href="../assets/css/student.css?v=20260916.7">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="content">
        <div class="page-header profile-page-heading">
            <h1>My Profile</h1>
            <p class="text-muted">View your student information and keep your profile photo up to date.</p>
        </div>

        <?php if ($errors): ?>
            <div class="profile-message profile-message-error" role="alert"><?= htmlspecialchars(implode(' ', $errors)); ?></div>
        <?php elseif ($success): ?>
            <div class="profile-message profile-message-success" role="status"><?= htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <section class="profile-layout">
            <article class="profile-card profile-summary-card">
                <div class="profile-avatar-wrap">
                    <?php if ($profilePicture && $profilePicturePath): ?>
                        <img class="profile-avatar-image" src="<?= htmlspecialchars($profilePicture . '?v=' . filemtime($profilePicturePath)); ?>" alt="Profile picture">
                    <?php else: ?>
                        <div class="profile-avatar-fallback" aria-hidden="true"><?= htmlspecialchars($initials); ?></div>
                    <?php endif; ?>
                </div>
                <h2><?= htmlspecialchars($fullName); ?></h2>
                <p class="profile-role">Student Account</p>
                <form class="profile-upload-form" method="post" enctype="multipart/form-data">
                    <label class="profile-upload-button" for="profile-picture-input">
                        <span class="material-symbols-rounded" aria-hidden="true">photo_camera</span>
                        Choose photo
                    </label>
                    <input id="profile-picture-input" class="profile-file-input" type="file" name="profile_picture" accept="image/jpeg,image/png,image/webp" required>
                    <p class="profile-upload-hint">JPG, PNG, or WebP up to 2 MB</p>
                    <button class="profile-save-button" type="submit">Upload picture</button>
                </form>
            </article>

            <article class="profile-card profile-details-card">
                <div class="profile-card-heading">
                    <div>
                        <p class="eyebrow">Student record</p>
                        <h2>Profile Information</h2>
                    </div>
                    <span class="profile-status-chip <?= strtoupper($accountStatus) === 'ACTIVE' ? 'is-active' : ''; ?>"><?= htmlspecialchars($accountStatus); ?></span>
                </div>
                <div class="profile-detail-grid">
                    <div class="profile-detail"><span>Full name</span><strong><?= htmlspecialchars($fullName); ?></strong></div>
                    <div class="profile-detail"><span>Student ID</span><strong><?= htmlspecialchars($studentNumber ?: 'Not assigned'); ?></strong></div>
                    <div class="profile-detail"><span>Email address</span><strong><?= htmlspecialchars($email ?: 'Not assigned'); ?></strong></div>
                    <div class="profile-detail"><span>Year level</span><strong><?= htmlspecialchars($yearLevel); ?></strong></div>
                    <div class="profile-detail"><span>Section</span><strong><?= htmlspecialchars($section); ?></strong></div>
                    <div class="profile-detail"><span>Enrollment status</span><strong><?= htmlspecialchars($studentStatus); ?></strong></div>
                </div>
                <div class="profile-accuracy">
                    <div class="profile-accuracy-heading"><span>Profile accuracy</span><strong><?= $profileAccuracy; ?>%</strong></div>
                    <div class="profile-accuracy-track"><span style="width: <?= $profileAccuracy; ?>%"></span></div>
                    <p>Keep your student record complete so your information stays accurate.</p>
                </div>
            </article>
        </section>
    </main>
</div>
<script src="../assets/js/student.js?v=20260916.7"></script>
</body>
</html>
