-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 27, 2025 at 06:38 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


-- Database name: `grading_app`

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `title` varchar(190) NOT NULL,
  `units` int(11) DEFAULT 3
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



INSERT INTO `courses` (`id`, `code`, `title`, `units`) VALUES
(1, 'IT101', 'Intro to IT', 3);

CREATE TABLE `document_requests` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `type` enum('report','certificate') NOT NULL,
  `purpose` varchar(190) DEFAULT NULL,
  `status` enum('pending','scheduled','ready','released') DEFAULT 'pending',
  `scheduled_at` datetime DEFAULT NULL,
  `released_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `edit_requests` (
  `id` int(11) NOT NULL,
  `grading_sheet_id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','denied') DEFAULT 'pending',
  `decided_by` int(11) DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `enrollments` (`id`, `section_id`, `student_id`) VALUES
(1, 1, 1);


CREATE TABLE `grades` (
  `id` int(11) NOT NULL,
  `grade_item_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `score` decimal(7,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `grade_components` (
  `id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `weight` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO `grade_components` (`id`, `section_id`, `name`, `weight`) VALUES
(1, 1, 'Exams', 30.00),
(2, 1, 'Quizzes', 25.00),
(3, 1, 'Activities', 20.00),
(4, 1, 'Recitation', 15.00),
(5, 1, 'Attendance', 10.00);


CREATE TABLE `grade_items` (
  `id` int(11) NOT NULL,
  `component_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `total_points` decimal(7,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `grading_sheets` (
  `id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `status` enum('draft','submitted','locked','reopened') DEFAULT 'draft',
  `deadline_at` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `grading_sheets` (`id`, `section_id`, `professor_id`, `status`, `deadline_at`, `submitted_at`) VALUES
(1, 1, 1, 'draft', NULL, NULL);


CREATE TABLE `professors` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `professor_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO `professors` (`id`, `user_id`, `professor_id`) VALUES
(1, 2, 'PROF-0001');


CREATE TABLE `sections` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `year_level` enum('1','2','3','4') NOT NULL,
  `term` varchar(50) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sections` (`id`, `name`, `year_level`, `term`, `course_id`) VALUES
(1, 'BSIT-3A', '3', '1st Sem 2025-2026', 1);


CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `year_level` enum('1','2','3','4') NOT NULL,
  `section` varchar(50) DEFAULT NULL,
  `status` enum('Regular','Irregular') DEFAULT 'Regular'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO `students` (`id`, `user_id`, `student_id`, `year_level`, `section`, `status`) VALUES
(1, 3, 'STUD-0001', '3', 'BSIT-3A', 'Regular');


CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `role` enum('admin','registrar','professor','student') NOT NULL DEFAULT 'student',
  `name_first` varchar(100) DEFAULT NULL,
  `name_last` varchar(100) DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO `users` (`id`, `email`, `password_hash`, `role`, `name_first`, `name_last`, `status`, `created_at`) VALUES
(1, 'jqtumamak@paterostechnologicalcollege.edu.ph', '$2y$10$Y4tMPwhtLW1Hku2iYAFT7uqoW9wToz4pYSRoGnuW1NNt69T0WeB3q', 'admin', 'MIS', 'Admin', 'ACTIVE', '2025-10-27 16:29:29'),
(2, 'jmnaling@paterostechnologicalcollege.edu.ph', '$2y$10$2.gkduk1zwBARcSeURTFk.6oNR4dLLYlWf/e5i.rvLIN0jFs5i9s.', 'professor', 'Juan', 'Naling', 'ACTIVE', '2025-10-27 16:29:29'),
(3, 'cgbaldemor@paterostechnologicalcollege.edu.ph', '$2y$10$moBHjGvIBTtCSJ.PSuJQWOlWKjY4N44E15psZiyVH64ZxUmnPqshy', 'student', 'Cris', 'Baldemor', 'ACTIVE', '2025-10-27 16:29:29');

--
-- Indexes 
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);


ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);


ALTER TABLE `document_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

ALTER TABLE `edit_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grading_sheet_id` (`grading_sheet_id`),
  ADD KEY `professor_id` (`professor_id`);


ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `student_id` (`student_id`);


ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grade_item_id` (`grade_item_id`),
  ADD KEY `student_id` (`student_id`);


ALTER TABLE `grade_components`
  ADD PRIMARY KEY (`id`),
  ADD KEY `section_id` (`section_id`);


ALTER TABLE `grade_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `component_id` (`component_id`);


ALTER TABLE `grading_sheets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `professor_id` (`professor_id`);


ALTER TABLE `professors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `professor_id` (`professor_id`);


ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `student_id` (`student_id`);


ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

-- forgot to add AUTO_INCREMENT LOL

ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `document_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


ALTER TABLE `edit_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;


ALTER TABLE `grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


ALTER TABLE `grade_components`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `grade_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


ALTER TABLE `grading_sheets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;


ALTER TABLE `professors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;


ALTER TABLE `sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;


-- constraints din pala
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;


ALTER TABLE `document_requests`
  ADD CONSTRAINT `document_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;


ALTER TABLE `edit_requests`
  ADD CONSTRAINT `edit_requests_ibfk_1` FOREIGN KEY (`grading_sheet_id`) REFERENCES `grading_sheets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `edit_requests_ibfk_2` FOREIGN KEY (`professor_id`) REFERENCES `professors` (`id`) ON DELETE CASCADE;


ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;


ALTER TABLE `grades`
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`grade_item_id`) REFERENCES `grade_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;


ALTER TABLE `grade_components`
  ADD CONSTRAINT `grade_components_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE;


ALTER TABLE `grade_items`
  ADD CONSTRAINT `grade_items_ibfk_1` FOREIGN KEY (`component_id`) REFERENCES `grade_components` (`id`) ON DELETE CASCADE;


ALTER TABLE `grading_sheets`
  ADD CONSTRAINT `grading_sheets_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grading_sheets_ibfk_2` FOREIGN KEY (`professor_id`) REFERENCES `professors` (`id`) ON DELETE CASCADE;


ALTER TABLE `professors`
  ADD CONSTRAINT `professors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;


ALTER TABLE `sections`
  ADD CONSTRAINT `sections_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL;

ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


--for database management CRUD
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
    user_id INT DEFAULT NULL,                         -- admin / MIS / registrar ID
    action VARCHAR(100) NOT NULL,                     -- e.g. "CREATE_STUDENT"
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

--test
INSERT INTO students (student_id, ptc_email, first_name, middle_name, last_name, year_level, section, status)
VALUES ('STUD-0002', 'maria.santos@ptc.edu.ph', 'Maria', 'Luna', 'Santos', '3', 'BSIT-3A', 'Regular');

--test update
UPDATE students
SET 
student_id = '2022-9800'
ptc_email = 'maria.santos@paterostechnologicalcollege.edu.ph'
section = 'BSIT-3OL'
WHERE id = 2;

--alter yung column name sa users
ALTER TABLE users
CHANGE COLUMN name_first first_name VARCHAR(100) NOT NULL;

ALTER TABLE users
CHANGE COLUMN name_last last_name VARCHAR(100) NOT NULL;
