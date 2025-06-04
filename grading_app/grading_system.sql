-- Drop and create database
DROP DATABASE IF EXISTS grading_app;
CREATE DATABASE grading_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE grading_app;

-- USERS
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'student') NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB;

-- STUDENTS
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    student_number VARCHAR(50) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- SUBJECTS
CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL
) ENGINE=InnoDB;

-- GRADES
CREATE TABLE grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    raw_grade DECIMAL(5,2) NOT NULL,
    grade DECIMAL(4,2) NOT NULL,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_subject (student_id, subject_id)
) ENGINE=InnoDB;

-- Indexes
CREATE INDEX idx_student_number ON students(student_number);
CREATE INDEX idx_subject_code ON subjects(code);
CREATE INDEX idx_grades_student ON grades(student_id);
CREATE INDEX idx_grades_subject ON grades(subject_id);

-- Seed admin
INSERT INTO users (username, password, role, email)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'admin@example.com')
ON DUPLICATE KEY UPDATE email = VALUES(email);

-- Seed students
INSERT INTO users (username, password, role, email) VALUES
('student1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'student1@example.com'),
('student2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'student2@example.com')
ON DUPLICATE KEY UPDATE email = VALUES(email);

-- Add student records (get user IDs first)
-- After running the above, get the user IDs for student1 and student2:
-- SELECT id FROM users WHERE username = 'student1';
-- SELECT id FROM users WHERE username = 'student2';
-- Assume student1 = 2, student2 = 3 (adjust if different)

INSERT INTO students (user_id, student_number)
VALUES (2, 'STD001')
ON DUPLICATE KEY UPDATE student_number = VALUES(student_number);

INSERT INTO students (user_id, student_number)
VALUES (3, 'STD002')
ON DUPLICATE KEY UPDATE student_number = VALUES(student_number);

-- Seed subjects
INSERT INTO subjects (code, name, description) VALUES
('MATH101', 'Mathematics', 'Basic Math course'),
('ENG102', 'English Literature', 'Intro to English Literature')
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description);

-- Add subject IDs (get them after insert)
-- SELECT id FROM subjects WHERE code = 'MATH101';
-- SELECT id FROM subjects WHERE code = 'ENG102';
-- Assume MATH101 = 1, ENG102 = 2 (adjust if different)

-- Add student IDs (get them after insert)
-- SELECT id FROM students WHERE student_number = 'STD001';
-- SELECT id FROM students WHERE student_number = 'STD002';
-- Assume STD001 = 1, STD002 = 2 (adjust if different)

-- Seed grades (use actual IDs)
INSERT INTO grades (student_id, subject_id, raw_grade, grade, comments)
VALUES (1, 1, 95.00, 1.00, 'Excellent work')
ON DUPLICATE KEY UPDATE raw_grade = VALUES(raw_grade), grade = VALUES(grade), comments = VALUES(comments);

INSERT INTO grades (student_id, subject_id, raw_grade, grade, comments)
VALUES (1, 2, 88.50, 1.25, 'Good performance')
ON DUPLICATE KEY UPDATE raw_grade = VALUES(raw_grade), grade = VALUES(grade), comments = VALUES(comments);

INSERT INTO grades (student_id, subject_id, raw_grade, grade, comments)
VALUES (2, 1, 76.00, 2.75, 'Needs improvement')
ON DUPLICATE KEY UPDATE raw_grade = VALUES(raw_grade), grade = VALUES(grade), comments = VALUES(comments);

INSERT INTO grades (student_id, subject_id, raw_grade, grade, comments)
VALUES (2, 2, 85.00, 2.00, 'Good effort')
ON DUPLICATE KEY UPDATE raw_grade = VALUES(raw_grade), grade = VALUES(grade), comments = VALUES(comments);