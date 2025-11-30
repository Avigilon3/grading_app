--restarting db from scratchhhh delete na lang yung old database from jerick
--pakilagay lahat dito ng query nyo sa sql

DROP DATABASE IF EXISTS grading_app;
CREATE DATABASE grading_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE grading_app;

-- USERS (one table for all roles)
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NULL,
  role ENUM('admin','registrar','professor','student') NOT NULL DEFAULT 'student',
  name_first VARCHAR(100),
  name_last VARCHAR(100),
  status ENUM('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- STUDENT & PROFESSOR profiles (link to users)
CREATE TABLE students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNIQUE,
  student_id VARCHAR(50) UNIQUE,
  year_level ENUM('1','2','3','4') NOT NULL,
  section VARCHAR(50),
  status ENUM('Regular','Irregular') DEFAULT 'Regular',
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE professors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNIQUE,
  professor_id VARCHAR(50) UNIQUE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- COURSES / SECTIONS / ENROLLMENTS
CREATE TABLE courses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) UNIQUE,
  title VARCHAR(190) NOT NULL,
  units INT DEFAULT 3
);

CREATE TABLE sections (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL,
  year_level ENUM('1','2','3','4') NOT NULL,
  term VARCHAR(50),
  course_id INT NULL,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL
);

CREATE TABLE enrollments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  section_id INT NOT NULL,
  student_id INT NOT NULL, 
  FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- GRADING
CREATE TABLE grading_sheets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  section_id INT NOT NULL,
  professor_id INT NOT NULL, 
  status ENUM('draft','submitted','locked','reopened') DEFAULT 'draft',
  deadline_at DATETIME NULL,
  submitted_at DATETIME NULL,
  FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
  FOREIGN KEY (professor_id) REFERENCES professors(id) ON DELETE CASCADE
);

CREATE TABLE grade_components (
  id INT AUTO_INCREMENT PRIMARY KEY,
  section_id INT NOT NULL,
  name VARCHAR(50) NOT NULL,  
  weight DECIMAL(5,2) NOT NULL,
  FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE
);

CREATE TABLE grade_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  component_id INT NOT NULL,
  title VARCHAR(100) NOT NULL,
  total_points DECIMAL(7,2) NOT NULL,
  FOREIGN KEY (component_id) REFERENCES grade_components(id) ON DELETE CASCADE
);

CREATE TABLE grades (
  id INT AUTO_INCREMENT PRIMARY KEY,
  grade_item_id INT NOT NULL,
  student_id INT NOT NULL,         
  score DECIMAL(7,2) NOT NULL,
  FOREIGN KEY (grade_item_id) REFERENCES grade_items(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE edit_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  grading_sheet_id INT NOT NULL,
  professor_id INT NOT NULL,
  reason TEXT,
  status ENUM('pending','approved','denied') DEFAULT 'pending',
  decided_by INT NULL, 
  decided_at DATETIME NULL,
  FOREIGN KEY (grading_sheet_id) REFERENCES grading_sheets(id) ON DELETE CASCADE,
  FOREIGN KEY (professor_id) REFERENCES professors(id) ON DELETE CASCADE
);

CREATE TABLE activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(100) NOT NULL,
  details TEXT,
  ip VARCHAR(45),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE document_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  type ENUM('report','certificate') NOT NULL,
  purpose VARCHAR(190),
  status ENUM('pending','scheduled','ready','released') DEFAULT 'pending',
  scheduled_at DATETIME NULL,
  released_at DATETIME NULL,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);


-- --------added by bethel on november 1 at 8pm: for database management CRUD-------------
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,          -- official school ID
    ptc_email VARCHAR(150) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) DEFAULT NULL,
    last_name VARCHAR(100) NOT NULL,
    year_level VARCHAR(20) DEFAULT NULL,      -- e.g. "1st Year", "2nd Year"
    section VARCHAR(50) DEFAULT NULL,         -- initial section (can be overridden by masterlist)
    status ENUM('Regular','Irregular','Inactive') DEFAULT 'Regular',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_students_student_id (student_id),
    UNIQUE KEY uq_students_ptc_email (ptc_email)
);
CREATE TABLE IF NOT EXISTS professors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id VARCHAR(50) NOT NULL,
    ptc_email VARCHAR(150) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) DEFAULT NULL,
    last_name VARCHAR(100) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_professors_professor_id (professor_id),
    UNIQUE KEY uq_professors_ptc_email (ptc_email)
);
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(50) NOT NULL,
    subject_title VARCHAR(200) NOT NULL,
    units DECIMAL(3,1) DEFAULT 0,
    description TEXT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_subjects_code (subject_code)
);
CREATE TABLE IF NOT EXISTS terms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    term_name VARCHAR(100) NOT NULL,      -- e.g. "1st Semester 2025-2026"
    school_year VARCHAR(20) DEFAULT NULL, -- e.g. "2025-2026"
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_name VARCHAR(100) NOT NULL,     -- e.g. "BSIT 3A"
    term_id INT DEFAULT NULL,               -- FK to terms.id
    subject_id INT DEFAULT NULL,            -- FK to subjects.id
    schedule VARCHAR(150) DEFAULT NULL,     -- e.g. "MWF 1:00-2:00"
    assigned_professor_id INT DEFAULT NULL, -- Registrar can assign
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sections_term FOREIGN KEY (term_id) REFERENCES terms(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_sections_subject FOREIGN KEY (subject_id) REFERENCES subjects(id)
        ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(50) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
--alter yung column name sa users para malinis
ALTER TABLE users
CHANGE COLUMN name_first first_name VARCHAR(100) NOT NULL;

ALTER TABLE users
CHANGE COLUMN name_last last_name VARCHAR(100) NOT NULL;

-- ----added by bethel on nov 2 at 9am: nadoble yung activity logs delete isang table ----------
DROP TABLE audit_logs;




--
--
-- di na pala eeffect ginawa kong create tables for student professors and sections sa taas haha alter table nga pala dapat lol
-- update tables with additional columns hehe

--student table
ALTER TABLE students
ADD COLUMN ptc_email VARCHAR(150) DEFAULT NULL AFTER student_id,
ADD COLUMN first_name VARCHAR(100) NOT NULL AFTER ptc_email,
ADD COLUMN middle_name VARCHAR(100) DEFAULT NULL AFTER first_name,
ADD COLUMN last_name VARCHAR(100) NOT NULL AFTER middle_name;

ALTER TABLE students
ADD UNIQUE KEY uq_students_ptc_email (ptc_email);

--test student info
INSERT INTO students (student_id, ptc_email, first_name, middle_name, last_name, year_level, section, status)
VALUES ('STUD-0002', 'maria.santos@ptc.edu.ph', 'Maria', 'Luna', 'Santos', '3', 'BSIT-3A', 'Regular');

--test update student info
UPDATE students
SET 
student_id = '2022-9800'
ptc_email = 'maria.santos@paterostechnologicalcollege.edu.ph'
section = 'BSIT-3OL'
WHERE id = 2;

--professor table
ALTER TABLE professors
    MODIFY professor_id VARCHAR(50) NOT NULL;

-- add new columns
ALTER TABLE professors
    ADD COLUMN ptc_email VARCHAR(150) NOT NULL AFTER professor_id,
    ADD COLUMN first_name VARCHAR(100) NOT NULL AFTER ptc_email,
    ADD COLUMN middle_name VARCHAR(100) DEFAULT NULL AFTER first_name,
    ADD COLUMN last_name VARCHAR(100) NOT NULL AFTER middle_name,
    ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER last_name,
    ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER is_active,
    ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

ALTER TABLE professors
    ADD UNIQUE KEY uq_professors_ptc_email (ptc_email);

--sections
ALTER TABLE sections
    CHANGE COLUMN name section_name VARCHAR(100) NOT NULL;

ALTER TABLE sections
    ADD COLUMN term_id INT NULL AFTER section_name,
    ADD COLUMN subject_id INT NULL AFTER term_id,
    ADD COLUMN schedule VARCHAR(150) NULL AFTER subject_id,
    ADD COLUMN assigned_professor_id INT NULL AFTER schedule,
    ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER assigned_professor_id,
    ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER is_active,
    ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

--
--
-- add FKs to terms and subjects
ALTER TABLE sections
    ADD CONSTRAINT fk_sections_term
        FOREIGN KEY (term_id) REFERENCES terms(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    ADD CONSTRAINT fk_sections_subject
        FOREIGN KEY (subject_id) REFERENCES subjects(id)
        ON UPDATE CASCADE ON DELETE SET NULL;

-- add FK to professors
ALTER TABLE sections
    ADD CONSTRAINT fk_sections_professor
        FOREIGN KEY (assigned_professor_id) REFERENCES professors(id)
        ON UPDATE CASCADE ON DELETE SET NULL;



--testing for prof
INSERT INTO professors (
  professor_id, ptc_email, first_name, middle_name, last_name,
  is_active, created_at, updated_at
)
VALUES
  ('PROF-0002', 'storre@paterostechnologicalcollege.edu.ph', 'Shider Rey', 'Dela Cruz', 'Toree', 1, NOW(), NOW()),
  ('PROF-0003', 'rnacario@paterostechnologicalcollege.edu.ph', 'Ryan Cesar', 'Legaspi', 'Nacario', 1, NOW(), NOW()),
  ('PROF-0004', 'mknazareno@paterostechnologicalcollege.edu.ph', 'Mark Kenneth', 'Borja', 'Nazareno', 0, NOW(), NOW());

--testing for terms
INSERT INTO terms (
  term_name, school_year, start_date, end_date, is_active, created_at, updated_at
)
VALUES
  ('1st Semester 2025-2026', '2025-2026', '2025-08-01', '2025-12-15', 1, NOW(), NOW()),
  ('2nd Semester 2025-2026', '2025-2026', '2026-01-10', '2026-05-15', 0, NOW(), NOW());

--testing for subjects 
INSERT INTO subjects (
  subject_code, subject_title, units, description, is_active, created_at, updated_at
)
VALUES
  ('AIS101', 'Information Assurance and Security', 3, 'About securities in IT field', 1, NOW(), NOW()),
  ('FIL1', 'Komunikasyon sa Akademikong Filipino', 3, 'Pagpapahalaga sa ating Wika', 1, NOW(), NOW()),
  ('DLD1', 'Digital Logic Design', 3, 'Covers Binary', 1, NOW(), NOW());


--testing for sections
INSERT INTO sections (
  section_name, year_level, term_id, subject_id, schedule, assigned_professor_id,
  is_active, created_at, updated_at
)
VALUES
  ('BSIT 1A', '1', 1, 1, 'MWF 8:00-9:00 AM', 8, 1, NOW(), NOW()),
  ('BSIT 2B', '2', 1, 2, 'TTH 9:00-10:30 AM', 9, 1, NOW(), NOW()),
  ('BSIT 3C', '3', 1, 3, 'MWF 1:00-2:30 PM', 10, 1, NOW(), NOW());



--modifying terms table to add the semester 
ALTER TABLE terms
  ADD COLUMN semester ENUM('1','2') NOT NULL DEFAULT '1' AFTER id;

UPDATE terms
SET term_name = CONCAT(
  CASE semester WHEN '1' THEN '1st Semester ' WHEN '2' THEN '2nd Semester ' END,
  school_year
);



--add section_subjects table para possible yung 1 section many subs 
CREATE TABLE section_subjects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  section_id INT NOT NULL,
  subject_id INT NOT NULL,
  professor_id INT NULL, 
  term_id INT NULL, 
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  FOREIGN KEY (professor_id) REFERENCES professors(id) ON DELETE SET NULL,
  FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE SET NULL
);

--add section_students table para din sa block section ng student
CREATE TABLE section_students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  section_id INT NOT NULL,
  student_id INT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_section_student (section_id, student_id),
  FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);


--
--
--
-- --------added by bethel on november 17 at 5pm: database cleaning-------------
--
-- Add subject_id and schedule to professor table
ALTER TABLE professors
  ADD COLUMN subject_id INT NULL AFTER last_name,
  ADD COLUMN schedule VARCHAR(150) DEFAULT NULL AFTER subject_id;

ALTER TABLE professors
  ADD CONSTRAINT fk_professors_subject
    FOREIGN KEY (subject_id)
    REFERENCES subjects(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL;

ALTER TABLE courses
  DROP COLUMN units;

ALTER TABLE courses
  ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER title;

ALTER TABLE courses
  ADD COLUMN description TEXT AFTER title;

ALTER TABLE subjects 
  ADD COLUMN course_id INT NULL AFTER description,
  ADD COLUMN year_level INT NULL AFTER course_id,
  ADD COLUMN term_id INT NULL AFTER year_level;

ALTER TABLE subjects 
  ADD CONSTRAINT fk_subjects_course 
    FOREIGN KEY (course_id)
    REFERENCES courses(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL;

ALTER TABLE subjects 
  ADD CONSTRAINT fk_subjects_term
  FOREIGN KEY (term_id)
  REFERENCES terms(id)
  ON UPDATE CASCADE
  ON DELETE SET NULL;

ALTER TABLE sections
  DROP FOREIGN KEY fk_sections_professor,
  DROP FOREIGN KEY fk_sections_subject,
  DROP COLUMN schedule,
  DROP COLUMN assigned_professor_id,
  DROP COLUMN subject_id,
  DROP COLUMN term;



--
--
--
-- --------added by bethel on november 22 at 10pm: grading_sheet table missing attribute-------------
--
-- Add foreign key of section_subject to grading_sheet table

ALTER TABLE grading_sheets
  ADD COLUMN section_subject_id INT(11) DEFAULT NULL AFTER id;

UPDATE grading_sheets gs
JOIN section_subjects ss
  ON ss.section_id   = gs.section_id
 AND ss.professor_id = gs.professor_id
SET gs.section_subject_id = ss.id
WHERE gs.section_subject_id IS NULL;

--deleted the first entry na for testing lang naman tapos add tong foreign key
ALTER TABLE grading_sheets
  MODIFY section_subject_id INT(11) NOT NULL,
  ADD CONSTRAINT fk_grading_sheets_section_subject
    FOREIGN KEY (section_subject_id) REFERENCES section_subjects(id) ON DELETE CASCADE,
  ADD UNIQUE KEY uq_grading_sheets_section_subject (section_subject_id);


--
--
--
-- --------added by bethel on november 30 at 4:12pm: add cancelled status in document requests-------------
--
-- 
ALTER TABLE document_requests
MODIFY COLUMN status ENUM('pending','scheduled','ready','released','cancelled') DEFAULT 'pending';

-- notifications table
CREATE TABLE notifications (
  id int(11) NOT NULL,
  user_id int(11) NOT NULL, 
  type varchar(50) DEFAULT NULL,
  message text NOT NULL,
  is_read tinyint(1) NOT NULL DEFAULT 0,
  created_at datetime DEFAULT current_timestamp(), 
  read_at datetime DEFAULT NULL     
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE notifications
  ADD PRIMARY KEY (id),
  ADD KEY user_id (user_id),
  ADD KEY idx_notifications_user_unread (user_id,is_read);

ALTER TABLE notifications
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE notifications
  ADD CONSTRAINT fk_notifications_user
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE;

UPDATE users
SET first_name='Carlo'
WHERE id = 3;

ALTER TABLE document_requests
  ADD COLUMN created_at DATETIME NULL AFTER status;