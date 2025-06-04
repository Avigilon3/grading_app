<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'includes/config.php';
?>

<?php include 'includes/header.php'; ?>

<div class="container my-4">
    <h1 class="mb-4">Dashboard</h1>
    
    <div class="row">
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Grading Management</h5>
                    <p class="card-text">Manage student grades and academic records.</p>
                    <a href="grading_system.php" class="btn btn-primary">Go to Grading System</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($_SESSION['role'] === 'student'): ?>
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">My Grades</h5>
                    <p class="card-text">View your academic performance and grades.</p>
                    <a href="student/dashboard.php" class="btn btn-primary">View My Grades</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Profile</h5>
                    <p class="card-text">View and update your profile information.</p>
                    <a href="profile.php" class="btn btn-primary">Manage Profile</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>