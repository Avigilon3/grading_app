<head>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>


<aside class="sidebar">
  <nav class="sidebar-nav">
    <a href="./dashboard.php" class="nav-item">
      <span class="material-symbols-rounded">dashboard</span>
        Dashboard</a>


<!-- Dropdown -->
  <div class="dropdown" data-dropdown data-dropdown-key="database">
    <a href="./database_management.php" class="nav-item dropdown-toggle" aria-expanded="false"> 
      <span class="material-symbols-rounded">database</span>
      <span class="nav-label">Database Management</span>
      <span class="material-symbols-rounded dropdown-arrow">keyboard_arrow_down</span>
    </a>


    <!-- Dropdown menu -->
    <ul class="submenu">
        <li><a class="nav-item" href="./students.php">Students</a></li>
        <li><a class="nav-item" href="./professors.php">Professors</a></li>
        <li><a class="nav-item" href="./subjects.php">Courses / Subjects</a></li>
        <li><a class="nav-item" href="./sections.php">Sections</a></li>
    </ul>
  </div>


    <a href="./masterlist.php" class="nav-item">
      <span class="material-symbols-rounded">list_alt</span>
        Masterlist</a>
    <a href="./grading_management.php" class="nav-item">
      <span class="material-symbols-rounded">stacks</span>
        Grading Sheets</a>
    <a href="./report_management.php" class="nav-item">
      <span class="material-symbols-rounded">error</span>
        Requests</a>
    <a href="./reports.php" class="nav-item">
      <span class="material-symbols-rounded">docs</span>
        Reports</a>
    <a href="./activity_logs.php" class="nav-item">
      <span class="material-symbols-rounded">earthquake</span>
        Activity Logs</a>
  </nav>
  <script> src= "../assets/js/admin.js"</script>
</aside>
