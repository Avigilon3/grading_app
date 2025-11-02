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

--kulang data ng students table 
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

--alter yung column name sa users para malinis
ALTER TABLE users
CHANGE COLUMN name_first first_name VARCHAR(100) NOT NULL;

ALTER TABLE users
CHANGE COLUMN name_last last_name VARCHAR(100) NOT NULL;

-- ----added by bethel on nov 2 at 9am: nadoble yung activity logs delete isang table ----------
DROP TABLE audit_logs;



