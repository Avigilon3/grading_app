<?php
require_once '../includes/init.php';
requireStudent();

// insert functions here to get all the subjects




?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subjects</title>
    <link rel="stylesheet" href="../assets/css/student.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <div class="layout">
        <?php include '../includes/sidebar.php'; ?>
        <main class="content">
            <div class="page-header">
              <h1>Subjects</h1>
              <p class="text-muted">
                View all your enrolled subjects and current grades.
              </p>
            </div>

            <div class="search-subjects">
              <span class="material-symbols-rounded">search</span>
              <input type="text" placeholder="Search Subjects">
            </div>

            <div class="enrolled-subjects">
              <h2>Enrolled Subjects</h2>
              <table class="enrolled-table">
                <thead>
                  <tr>
                    <td>Subject Name</td>
                    <td>Professor</td>
                    <td>Grade</td>
                    <td>Action</td>
                  </tr>
                </thead>
                <tbody>
                  <!-- select all enrolled subjects for the user -->

                </tbody>
              </table>
            </div>

            <div class="archive-subjects">
              <h2>Archived Subjects</h2>
              <table class="archive-table">
                <thead>
                  <tr>
                    <td>Subject Name</td>
                    <td>Professor</td>
                    <td>Grade</td>
                    <td>Action</td>
                  </tr>
                </thead>
                <tbody>
                  <!-- select all archived subjects for the user -->
                </tbody>
              </table>
            </div>

        </main>

    </div>

    
</body>
</html>