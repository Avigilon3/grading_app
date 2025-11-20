CREATE DATABASE professor_portal;
USE professor_portal;

CREATE TABLE professors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT,
    code VARCHAR(10) NOT NULL,
    title VARCHAR(100) NOT NULL,
    section VARCHAR(10),
    FOREIGN KEY (professor_id) REFERENCES professors(id)
);

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    class_id INT,
    FOREIGN KEY (class_id) REFERENCES classes(id)
);

CREATE TABLE grading_sheets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT,
    submitted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (class_id) REFERENCES classes(id)
);

CREATE TABLE deadlines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT,
    due_date DATE,
    FOREIGN KEY (class_id) REFERENCES classes(id)
);

INSERT INTO professors(name, email) VALUES ('Joshua Naling', 'jn@example.com');

INSERT INTO classes(professor_id, code, title, section) VALUES
(1, 'NET 101', 'Network Fundamentals', 'CCS 2A'),
(1, 'CS 201', 'Data Structures and Algorithms', 'BSIT 1B'),
(1, 'CS 301', 'Database Management Systems', 'BSIT 1C'),
(1, 'CS 150', 'Web Development Fundamentals', 'CCS 2B');

INSERT INTO students(name, class_id) VALUES
('Student 1', 1),
('Student 2', 2),
('Student 3', 3);

INSERT INTO grading_sheets(class_id, submitted) VALUES
(1, TRUE),
(2, TRUE),
(3, FALSE),
(4, TRUE);

INSERT INTO deadlines(class_id, due_date) VALUES
(3, DATE_ADD(CURDATE(), INTERVAL 3 DAY)),
(4, '2025-12-15');
CREATE DATABASE professor_portal;
USE professor_portal;

-- Professors table
CREATE TABLE professors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL
);

-- Semesters table
CREATE TABLE semesters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

-- Classes table
CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    code VARCHAR(20) NOT NULL,
    section VARCHAR(20) NOT NULL,
    semester_id INT NOT NULL,
    FOREIGN KEY (professor_id) REFERENCES professors(id),
    FOREIGN KEY (semester_id) REFERENCES semesters(id)
);

-- Students table
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    name VARCHAR(200) NOT NULL,
    class_id INT NOT NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id)
);
-- Insert professor
INSERT INTO professors (name, email) VALUES ('Joshua Naling', 'jnaling@example.com');

-- Insert semesters
INSERT INTO semesters (name) VALUES ('1st Semester, AY 2025-2026'), ('2nd Semester, AY 2025-2026');

-- Insert classes assigned to professor_id = 1
INSERT INTO classes (professor_id, code, section, semester_id) VALUES
(1, 'NET101', 'CSS 2A', 1),
(1, 'CS201', 'BSIT 1B', 1),
(1, 'CS301', 'BSIT 1C', 1);

-- Insert students (class_id 1 = NET101 CSS 2A)
INSERT INTO students (student_id, name, class_id) VALUES
('STUB-0011', 'Marquez, Ali Mohammad', 1),
('1900-0002', 'Burgos, Jose Apolonio', 1),
('STUB-0003', 'Maria Santos', 1),
('STUB-0004', 'Pedro Penduko', 1);

-- Insert students for class_id 2 = CS201 BSIT 1B
INSERT INTO students (student_id, name, class_id) VALUES
('1800-1111', 'Vasquez, Jacob', 2),
('1800-1001', 'Guillermo, Christian', 2),
('1800-1011', 'Reyes, Christian', 2),
('1800-1010', 'Delos Reyes, Joshua', 2);

-- Insert students for class_id 3 = CS301 BSIT 1C
INSERT INTO students (student_id, name, class_id) VALUES
('1700-2001', 'Esguerra, Titania', 3),
('1700-2002', 'Duran, Ariel', 3),
('1700-2003', 'Aguirre, Nora Ashley A.', 3);
CREATE DATABASE professor_portal;
USE professor_portal;

-- Tables to support grading sheets per class and students

CREATE TABLE professors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    code VARCHAR(20) NOT NULL,
    section VARCHAR(20) NOT NULL,
    FOREIGN KEY (professor_id) REFERENCES professors(id)
);

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    class_id INT NOT NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id)
);

-- Grading categories (e.g. Activity, Exam, Quizzes, Attendance)
CREATE TABLE grade_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    weight DECIMAL(5,2) NOT NULL, -- weight as percentage (e.g. 40.00)
    sort_order INT DEFAULT 0,
    FOREIGN KEY (class_id) REFERENCES classes(id)
);

-- Grade items (e.g. Activity 1, Midterm, Quiz 1)
CREATE TABLE grade_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    max_score DECIMAL(6,2) NOT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (category_id) REFERENCES grade_categories(id)
);

-- Grades: scores per student per grade item
CREATE TABLE grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    grade_item_id INT NOT NULL,
    score DECIMAL(6,2) DEFAULT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (grade_item_id) REFERENCES grade_items(id),
    UNIQUE KEY (student_id, grade_item_id)
);

-- Grading sheet status (draft or submitted)
CREATE TABLE grading_sheets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    submitted BOOLEAN DEFAULT FALSE,
    last_saved DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id)
);
-- Insert professor
INSERT INTO professors(name, email) VALUES ('Joshua Naling', 'jnaling@example.com');

-- Insert one class
INSERT INTO classes(professor_id, code, section) VALUES (1, 'NET101', 'CCS 2A');

-- Insert students
INSERT INTO students(student_id, name, class_id) VALUES
('STUB-0011', 'Marquez, Ali', 1),
('1900-0002', 'Burgos, Jose Apolonio', 1),
('STUB-0003', 'Maria Santos', 1),
('STUB-0004', 'Pedro Penduko', 1),
('1900-0054', 'Lyka Mae Muñoz', 1);

-- Insert grade categories with weights
INSERT INTO grade_categories(class_id, name, weight, sort_order) VALUES
(1, 'Activity', 40.00, 1),
(1, 'Exam', 40.00, 2),
(1, 'Quizzes', 10.00, 3),
(1, 'Attendance', 10.00, 4);

-- Insert grade items (Activity 1 score out of 20, etc.)
-- Activity category id assumed 1
INSERT INTO grade_items(category_id, name, max_score, sort_order) VALUES
(1, 'Activity 1', 20, 1),
(1, 'Activity 2', 20, 2),
(1, 'Activity 3', 25, 3),
(1, 'Activity 4', 50, 4);

-- Exam category id assumed 2
INSERT INTO grade_items(category_id, name, max_score, sort_order) VALUES
(2, 'Midterm', 50, 1),
(2, 'Final Exam', 50, 2);

-- Quizzes category id assumed 3
INSERT INTO grade_items(category_id, name, max_score, sort_order) VALUES
(3, 'Quiz 1', 10, 1),
(3, 'Quiz 2', 10, 2),
(3, 'Quiz 3', 10, 3);

-- Attendance category id assumed 4
INSERT INTO grade_items(category_id, name, max_score, sort_order) VALUES
(4, 'Attendance', 100, 1);

-- Insert grading sheet record
INSERT INTO grading_sheets(class_id, submitted, last_saved) VALUES (1, FALSE, NOW());
CREATE DATABASE professor_portal;
USE professor_portal;

-- Professors table
CREATE TABLE professors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL
);

-- Classes table
CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    code VARCHAR(20) NOT NULL,
    section VARCHAR(20) NOT NULL,
    FOREIGN KEY (professor_id) REFERENCES professors(id)
);

-- Grading sheets
CREATE TABLE grading_sheets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    submitted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (class_id) REFERENCES classes(id)
);

-- Requests table
CREATE TABLE requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    class_id INT NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'denied') DEFAULT 'pending',
    admin_response TEXT DEFAULT NULL,
    date_submitted DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_responded DATETIME DEFAULT NULL,
    FOREIGN KEY (professor_id) REFERENCES professors(id),
    FOREIGN KEY (class_id) REFERENCES classes(id)
);
INSERT INTO professors (name, email) VALUES ('Joshua Naling', 'jnaling@example.com');

INSERT INTO classes (professor_id, code, section) VALUES
(1, 'NET101', 'CCS 2A'),
(1, 'CS301', 'Section A'),
(1, 'CS350', 'Section B');

-- Optional: sample requests
INSERT INTO requests (professor_id, class_id, reason, status, admin_response, date_submitted, date_responded) VALUES
(1, 2, 'Need to update grades for students who submitted late work with valid excuse letters.', 'approved',
 'Approved. Grading sheet has been reopened until November 10, 2025.', '2025-11-05 10:00:00', '2025-11-06 15:00:00'),
(1, 3, 'Found calculation errors in the final exam scores that need correction.', 'pending', NULL, '2025-11-07 14:30:00', NULL);
CREATE DATABASE professor_portal;
USE professor_portal;

-- Professors table with secure password hash
CREATE TABLE professors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(200) NOT NULL UNIQUE,
    role VARCHAR(50) NOT NULL DEFAULT 'Professor',
    prof_id VARCHAR(20) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL
);

-- Sample professor insert (password: ChangeMe123!)
INSERT INTO professors (full_name, email, prof_id, password_hash) VALUES 
('Joshua Naling', 'joshuaangelnaling@paterostechnologicalcollege.edu.ph', 'PROF-0001', 
 '$2y$10$KbQi/xZn1yL0mKmbo./sYOwzyY5fT.YUztDZHLEBxL6YhX5X5F2p6');
 