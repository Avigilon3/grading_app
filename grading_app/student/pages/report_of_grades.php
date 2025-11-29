<?php
require_once '../includes/init.php';
requireStudent();

$yearOptions = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
$semesterOptions = ['1st Semester', '2nd Semester', 'Summer'];

$selectedYear = isset($_GET['year_level']) && in_array($_GET['year_level'], $yearOptions, true)
    ? $_GET['year_level']
    : '2nd Year';
$selectedSemester = isset($_GET['semester']) && in_array($_GET['semester'], $semesterOptions, true)
    ? $_GET['semester']
    : '1st Semester';

// Demo data (replace with real grades when available)
$gradeEntries = [
    [
        'code' => 'IT 201',
        'title' => 'Introduction to Programming 1',
        'grade' => '1.5',
        'units' => 3,
        'remarks' => 'Passed',
        'year' => '2nd Year',
        'semester' => '1st Semester',
    ],
    [
        'code' => 'IT 202',
        'title' => 'Object Oriented Programming',
        'grade' => '1.75',
        'units' => 3,
        'remarks' => 'Passed',
        'year' => '2nd Year',
        'semester' => '1st Semester',
    ],
    [
        'code' => 'GE 105',
        'title' => 'Understanding the Self',
        'grade' => '2.00',
        'units' => 3,
        'remarks' => 'Passed',
        'year' => '2nd Year',
        'semester' => '2nd Semester',
    ],
];

$filteredGrades = array_values(array_filter($gradeEntries, static function ($row) use ($selectedYear, $selectedSemester) {
    return $row['year'] === $selectedYear && $row['semester'] === $selectedSemester;
}));

$totalUnits = array_reduce($filteredGrades, static function ($carry, $row) {
    return $carry + (float)$row['units'];
}, 0.0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report of Grades</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
      :root {
        --green: #4b7250;
        --bg: #f5f7fb;
        --panel: #ffffff;
        --border: #e5e7eb;
        --text: #111827;
        --muted: #4b5563;
      }
      * { box-sizing: border-box; }
      body {
        margin: 0;
        font-family: 'Plus Jakarta Sans', sans-serif;
        background: var(--bg);
        color: var(--text);
        padding-top: 70px;
      }
      header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 70px;
        background: var(--green);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 18px;
        z-index: 20;
      }
      header a { color: #fff; text-decoration: none; }
      .left-header { display: flex; align-items: center; gap: 10px; color: #fff; font-weight: 700; }
      .portal-name { color: #ffd700; font-weight: 700; }
      .right-header { display: flex; align-items: center; gap: 14px; color: #fff; }
      .badge { color: #fff; }
      .user-trigger { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.25); color: #fff; border-radius: 12px; padding: 8px 12px; display: inline-flex; gap: 6px; align-items: center; cursor: pointer; text-decoration: none; }
      .layout { display: grid; grid-template-columns: 240px 1fr; gap: 14px; padding: 14px; min-height: calc(100vh - 70px); }
      .sidebar { background: #ffffff; border: 1px solid var(--border); border-radius: 16px; padding: 14px; box-shadow: 0 10px 24px rgba(0,0,0,0.04); }
      .sidebar-nav { display: flex; flex-direction: column; gap: 6px; }
      .nav-item { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: 12px; color: #1f2937; text-decoration: none; }
      .nav-item:hover { background: #eef3ef; }
      .content { background: transparent; padding: 8px; }
      .page-container { max-width: 1200px; margin: 0 auto; }
      .report-shell { background: var(--panel); border: 1px solid #e7eaf0; border-radius: 18px; padding: 22px; box-shadow: 0 18px 40px rgba(0,0,0,0.05); }
      .page-header { margin: 0 0 10px; }
      .page-header h1 { margin: 0 0 6px; }
      .page-header p { margin: 0; color: var(--muted); }
      .page-header { margin: 0 0 16px; }
      .page-header h1 { margin: 0 0 6px; font-size: 34px; }
      .page-header p { margin: 0; color: var(--muted); }
      .controls-row { display: grid; grid-template-columns: repeat(2, 1fr) repeat(2, auto); gap: 14px; align-items: center; background: #fff; padding: 18px; border-radius: 18px; border: 1px solid #e1e5ed; box-shadow: 0 10px 28px rgba(0,0,0,0.04); }
      .form-control { width: 100%; padding: 12px 14px; border: 1px solid #cfd6e1; border-radius: 12px; font: inherit; background: #fff; font-size: 16px; }
      .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; border-radius: 12px; font-weight: 700; border: 1px solid var(--border); background: #fff; color: var(--text); cursor: pointer; text-decoration: none; }
      .btn.primary { background: var(--green); border-color: var(--green); color: #fff; box-shadow: inset 0 -2px 0 rgba(0,0,0,0.1); }
      .btn.ghost { background: #fff; border-color: var(--green); color: var(--green); }
      .document-frame { margin-top: 16px; background: #fff; border: 1px solid #e1e5ed; border-radius: 18px; box-shadow: 0 16px 36px rgba(0,0,0,0.05); min-height: 760px; display: grid; place-items: center; padding: 22px; }
      .grade-page { width: 100%; max-width: 980px; background: #fff; border: 1px solid #dfe4ea; border-radius: 18px; padding: 20px 24px 32px; }
      .grade-header { text-align: center; }
      .grade-header img { width: 90px; height: 90px; object-fit: contain; }
      .college-name { font-size: 22px; font-weight: 700; margin: 8px 0 4px; }
      .college-meta { margin: 0; color: #111; font-size: 14px; line-height: 1.4; }
      .report-title { margin: 18px 0 10px; font-weight: 700; text-transform: uppercase; }
      .student-meta { width: 100%; margin: 10px 0 18px; }
      .student-meta td { padding: 3px 6px; font-size: 14px; }
      .grade-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
      .grade-table th, .grade-table td { border: 1px solid #111; padding: 8px 10px; font-size: 14px; text-align: left; }
      .grade-table th { font-weight: 700; background: #f3f4f6; }
      .grade-table td:nth-child(3), .grade-table td:nth-child(4), .grade-table td:nth-child(5) { text-align: center; }
      .doc-footer { margin-top: 14px; font-size: 13px; color: #374151; }
      .doc-caption { margin-top: 12px; text-align: center; color: #4b5563; font-size: 14px; }
      .info-card { margin-top: 16px; background: #eef5ff; border: 1px solid #d9e5fb; border-radius: 16px; padding: 16px 18px; color: #111827; }
      .info-card h3 { margin: 0 0 8px; font-size: 16px; }
      .info-card ul { margin: 0; padding-left: 18px; color: #1f2937; line-height: 1.6; }
      @media (max-width: 1024px) {
        .layout { grid-template-columns: 1fr; }
        .sidebar { position: static; }
        .controls-row { grid-template-columns: 1fr; }
      }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <div class="layout">
        <?php include '../includes/sidebar.php'; ?>
        <main class="content content-neutral">
          <div class="page-container">
          <div class="report-shell">
            <div class="page-header">
              <h1>Report of Grades</h1>
              <p>View and download your official report of grades.</p>
            </div>

            <div class="controls-row">
              <label style="display:flex; flex-direction:column; gap:6px;">
                <div>Year Level</div>
                <input class="form-control" name="year_level" value="<?php echo htmlspecialchars($selectedYear); ?>" placeholder="Year Level">
              </label>
              <label style="display:flex; flex-direction:column; gap:6px;">
                <div>Semester</div>
                <input class="form-control" name="semester" value="<?php echo htmlspecialchars($selectedSemester); ?>" placeholder="Semester">
              </label>
              <a class="btn primary" href="#" onclick="window.print(); return false;">
                <span class="material-symbols-rounded" aria-hidden="true">file_download</span>
                Download as JPEG
              </a>
              <a class="btn ghost" href="../pages/requests.php">
                <span class="material-symbols-rounded" aria-hidden="true">description</span>
                Request Hard Copy with Seal
              </a>
            </div>

            <div class="document-frame">
              <div class="grade-page">
                <div class="grade-header">
                  <img src="../../admin/assets/images/logo-ptc.png" alt="PTC Logo">
                  <div class="college-name">PATEROS TECHNOLOGICAL COLLEGE</div>
                  <p class="college-meta">
                    COLLEGE ST., STO. ROSARIO-KANLURAN<br>
                    PATEROS, METRO MANILA<br>
                    WEBSITE: paterostechnologicalcollege.edu.ph
                  </p>
                </div>

                <p style="text-align:right; margin: 12px 0 6px; font-weight: 600;">August 08, 2024</p>
                <div class="report-title">REPORT OF GRADE</div>
                <p style="margin: 0 0 14px; font-weight: 600;">Institute of Information and Communication Technology</p>

                <table class="student-meta">
                  <tr>
                    <td style="width: 140px;">NAME OF STUDENT:</td>
                    <td><strong>ALBOR, BETHEL RODRIGUEZ</strong></td>
                  </tr>
                  <tr>
                    <td>COURSE:</td>
                    <td>Certificate in Computer Science</td>
                  </tr>
                  <tr>
                    <td>YEAR &amp; SECTION:</td>
                    <td>CCS - 2Irregular</td>
                  </tr>
                  <tr>
                    <td>S.Y.:</td>
                    <td>2nd Semester S.Y. 2023-2024</td>
                  </tr>
                </table>

                <table class="grade-table">
                  <thead>
                    <tr>
                      <th>SUBJECT CODE</th>
                      <th>SUBJECT TITLE</th>
                      <th>Units</th>
                      <th>GRADE</th>
                      <th>REMARKS</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr><td>NET 101</td><td>Network Design and Management</td><td>3</td><td>1</td><td>PASSED</td></tr>
                    <tr><td>WS 102</td><td>Web Programming 2</td><td>3</td><td>1</td><td>PASSED</td></tr>
                    <tr><td>OOP 1</td><td>Programming 4 (OOP)</td><td>3</td><td>1</td><td>PASSED</td></tr>
                    <tr><td>OP 4</td><td>Office Productivity 4 (Computer Aided Design)</td><td>3</td><td>1.25</td><td>PASSED</td></tr>
                  </tbody>
                </table>

                <div class="doc-footer">
                  <p style="margin: 12px 0 4px;">Prepared by: _______________________</p>
                  <p style="margin: 0;">Registrar's Office</p>
                </div>
              </div>
              <div class="doc-caption">Showing: <?php echo htmlspecialchars($selectedYear); ?> - <?php echo htmlspecialchars($selectedSemester); ?> (S.Y. 2023-2024)</div>
            </div>

            <div class="info-card">
              <h3>Important Information</h3>
              <ul>
                <li>Downloaded reports are for reference only and not official documents.</li>
                <li>Hard copy requests with school dry seal typically take 3-5 business days to process.</li>
                <li>You must pick up hard copies personally at the Registrar's Office with a valid ID.</li>
                <li>Official hard copies with seal are required for employment and scholarship applications.</li>
              </ul>
            </div>
          </div>
          </div>
        </main>

    </div>

    
</body>
</html>
