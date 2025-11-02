-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 02, 2025 at 04:42 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `grading_app`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip`, `created_at`) VALUES
(1, 1, 'UPDATE_STUDENT', 'Updated student: 2022-9800', '::1', '2025-11-01 17:40:23'),
(2, 1, 'ADD_STUDENT', 'Added student: 1900-0001', '::1', '2025-11-01 17:41:30'),
(3, 1, 'ADD_STUDENT', 'Added student: 1900-0002', '::1', '2025-11-02 05:47:44'),
(4, 1, 'ADD_STUDENT', 'Added student: 1900-0001', '::1', '2025-11-02 05:47:55'),
(5, 1, 'ADD_STUDENT', 'Added student: 1900-0003', '::1', '2025-11-02 06:27:48'),
(6, 1, 'ADD_STUDENT', 'Added student: ', '::1', '2025-11-02 06:27:57'),
(7, 1, 'ADD_STUDENT', 'Added student: 2022-9800', '::1', '2025-11-02 06:29:51'),
(8, 1, 'ADD_STUDENT', 'Added student: 1900-0002', '::1', '2025-11-02 06:30:05'),
(9, 1, 'UPDATE_PROFESSOR', 'Updated professor: PROF-0004', '::1', '2025-11-02 07:51:29'),
(10, 1, 'ADD_PROFESSOR', 'Added professor: PROF-0005', '::1', '2025-11-02 08:00:45'),
(11, 1, 'UPDATE_PROFESSOR', 'Updated professor: PROF-0005', '::1', '2025-11-02 08:02:19'),
(12, 1, 'UPDATE_SUBJECT', 'Updated subject: FIL1', '::1', '2025-11-02 08:03:08'),
(13, 1, 'ADD_SUBJECT', 'Added subject: MS 102', '::1', '2025-11-02 08:03:35'),
(14, 1, 'ADD_TERM', 'Added term: 2nd Semester', '::1', '2025-11-02 08:06:52'),
(15, 1, 'ADD_TERM', 'Added term: 1st Semester 2021-2022', '::1', '2025-11-02 08:36:38'),
(16, 1, 'DELETE_TERM', 'Deleted term id: 3', '::1', '2025-11-02 08:37:00'),
(17, 1, 'ADD_TERM', 'Added term: 1st Semester 2024-2025', '::1', '2025-11-02 08:46:07'),
(18, 1, 'ADD_SECTION', 'Added section: BSIT 2B', '::1', '2025-11-02 09:15:07'),
(19, 1, 'ADD_TO_MASTERLIST', 'Added Burgos, Jose Apolonio to section ID 8', '::1', '2025-11-02 14:23:04'),
(20, 1, 'ADD_TO_MASTERLIST', 'Added Burgos, Jose Apolonio to section ID 9', '::1', '2025-11-02 14:23:41'),
(21, 1, 'ADD_TO_MASTERLIST', 'Added Burgos, Jose Apolonio to section ID 11', '::1', '2025-11-02 14:24:32'),
(22, 1, 'ADD_TO_MASTERLIST', 'Added Burgos, Jose Apolonio to section ID 10', '::1', '2025-11-02 14:24:41');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `title` varchar(190) NOT NULL,
  `units` int(11) DEFAULT 3
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `code`, `title`, `units`) VALUES
(1, 'IT101', 'Intro to IT', 3);

-- --------------------------------------------------------

--
-- Table structure for table `document_requests`
--

CREATE TABLE `document_requests` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `type` enum('report','certificate') NOT NULL,
  `purpose` varchar(190) DEFAULT NULL,
  `status` enum('pending','scheduled','ready','released') DEFAULT 'pending',
  `scheduled_at` datetime DEFAULT NULL,
  `released_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `edit_requests`
--

CREATE TABLE `edit_requests` (
  `id` int(11) NOT NULL,
  `grading_sheet_id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','denied') DEFAULT 'pending',
  `decided_by` int(11) DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `section_id`, `student_id`) VALUES
(1, 1, 1),
(2, 8, 5),
(3, 9, 5),
(4, 11, 5),
(5, 10, 5);

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `id` int(11) NOT NULL,
  `grade_item_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `score` decimal(7,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grade_components`
--

CREATE TABLE `grade_components` (
  `id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `weight` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grade_components`
--

INSERT INTO `grade_components` (`id`, `section_id`, `name`, `weight`) VALUES
(1, 1, 'Exams', 30.00),
(2, 1, 'Quizzes', 25.00),
(3, 1, 'Activities', 20.00),
(4, 1, 'Recitation', 15.00),
(5, 1, 'Attendance', 10.00);

-- --------------------------------------------------------

--
-- Table structure for table `grade_items`
--

CREATE TABLE `grade_items` (
  `id` int(11) NOT NULL,
  `component_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `total_points` decimal(7,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grading_sheets`
--

CREATE TABLE `grading_sheets` (
  `id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `status` enum('draft','submitted','locked','reopened') DEFAULT 'draft',
  `deadline_at` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grading_sheets`
--

INSERT INTO `grading_sheets` (`id`, `section_id`, `professor_id`, `status`, `deadline_at`, `submitted_at`) VALUES
(1, 1, 1, 'draft', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `professors`
--

CREATE TABLE `professors` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `professor_id` varchar(50) NOT NULL,
  `ptc_email` varchar(150) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `professors`
--

INSERT INTO `professors` (`id`, `user_id`, `professor_id`, `ptc_email`, `first_name`, `middle_name`, `last_name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 2, 'PROF-0001', '', '', NULL, '', 1, '2025-11-02 15:05:53', '2025-11-02 15:05:53'),
(8, NULL, 'PROF-0002', 'storre@paterostechnologicalcollege.edu.ph', 'Shider Rey', 'Dela Cruz', 'Toree', 1, '2025-11-02 15:22:08', '2025-11-02 15:22:08'),
(9, NULL, 'PROF-0003', 'rnacario@paterostechnologicalcollege.edu.ph', 'Ryan Cesar', 'Legaspi', 'Nacario', 1, '2025-11-02 15:22:08', '2025-11-02 15:22:08'),
(10, NULL, 'PROF-0004', 'mknazareno@paterostechnologicalcollege.edu.ph', 'Mark Kenneth', 'Borja', 'Nazareno', 1, '2025-11-02 15:22:08', '2025-11-02 15:51:29'),
(14, NULL, 'PROF-0005', 'maria.santos@paterostechnologicalcollege.edu.ph', 'Maria Elena', 'Cruz', 'Santos', 0, '2025-11-02 16:00:45', '2025-11-02 16:02:19');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` int(11) NOT NULL,
  `section_name` varchar(100) NOT NULL,
  `term_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `schedule` varchar(150) DEFAULT NULL,
  `assigned_professor_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `year_level` enum('1','2','3','4') NOT NULL,
  `term` varchar(50) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`id`, `section_name`, `term_id`, `subject_id`, `schedule`, `assigned_professor_id`, `is_active`, `created_at`, `updated_at`, `year_level`, `term`, `course_id`) VALUES
(1, 'BSIT-3A', NULL, NULL, NULL, NULL, 1, '2025-11-02 15:05:53', '2025-11-02 15:05:53', '3', '1st Sem 2025-2026', 1),
(8, 'BSIT 1A', 1, 1, 'MWF 8:00-9:00 AM', 8, 1, '2025-11-02 15:49:49', '2025-11-02 15:49:49', '1', NULL, NULL),
(9, 'BSIT 2B', 1, 2, 'TTH 9:00-10:30 AM', 9, 1, '2025-11-02 15:49:49', '2025-11-02 15:49:49', '2', NULL, NULL),
(10, 'BSIT 3C', 1, 3, 'MWF 1:00-2:30 PM', 10, 1, '2025-11-02 15:49:49', '2025-11-02 15:49:49', '3', NULL, NULL),
(11, 'BSIT 2B', 2, 3, 'TFSA 16:00-18:00', 10, 1, '2025-11-02 17:15:07', '2025-11-02 17:15:07', '1', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `section_students`
--

CREATE TABLE `section_students` (
  `id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `section_subjects`
--

CREATE TABLE `section_subjects` (
  `id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `professor_id` int(11) DEFAULT NULL,
  `term_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `ptc_email` varchar(150) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `year_level` enum('1','2','3','4') NOT NULL,
  `section` varchar(50) DEFAULT NULL,
  `status` enum('Regular','Irregular') DEFAULT 'Regular'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `student_id`, `ptc_email`, `first_name`, `middle_name`, `last_name`, `year_level`, `section`, `status`) VALUES
(1, 3, 'STUD-0001', NULL, '', NULL, '', '3', 'BSIT-3A', 'Regular'),
(2, NULL, '2022-9800', 'maria.santos@paterostechnologicalcollege.edu.ph', 'Maria', 'Luna', 'Santos', '4', 'BSIT-3OL', 'Irregular'),
(4, NULL, '1900-0001', 'joserizal@paterostechnologicalcollege.edu.ph', 'Jose', 'Mercado', 'Rizal', '2', 'CCS 1A', 'Regular'),
(5, NULL, '1900-0002', 'jburgos@paterostechnologicalcollege.edu.ph', 'Jose', 'Apolonio', 'Burgos', '1', 'CCS 2C', 'Irregular');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_code` varchar(50) NOT NULL,
  `subject_title` varchar(200) NOT NULL,
  `units` decimal(3,1) DEFAULT 0.0,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_code`, `subject_title`, `units`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'AIS101', 'Information Assurance and Security', 3.0, 'About securities in IT field', 1, '2025-11-02 15:47:41', '2025-11-02 15:47:41'),
(2, 'FIL1', 'Komunikasyon sa Akademikong Filipino', 3.0, 'Pagpapahalaga sa ating Wika eme', 1, '2025-11-02 15:47:41', '2025-11-02 16:03:08'),
(3, 'DLD1', 'Digital Logic Design', 3.0, 'Covers Binary', 1, '2025-11-02 15:47:41', '2025-11-02 15:47:41'),
(4, 'MS 102', 'Modeling and Simulation', 3.0, 'Simulations', 1, '2025-11-02 16:03:35', '2025-11-02 16:03:35');

-- --------------------------------------------------------

--
-- Table structure for table `terms`
--

CREATE TABLE `terms` (
  `id` int(11) NOT NULL,
  `semester` enum('1','2') NOT NULL DEFAULT '1',
  `term_name` varchar(100) NOT NULL,
  `school_year` varchar(20) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `terms`
--

INSERT INTO `terms` (`id`, `semester`, `term_name`, `school_year`, `start_date`, `end_date`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '1', '1st Semester 2025-2026', '2025-2026', '2025-08-01', '2025-12-15', 1, '2025-11-02 15:47:11', '2025-11-02 15:47:11'),
(2, '1', '1st Semester 2025-2026', '2025-2026', '2026-01-10', '2026-05-15', 0, '2025-11-02 15:47:11', '2025-11-02 16:30:17'),
(4, '1', '1st Semester 2021-2022', '2021-2022', '2025-11-02', '2025-11-03', 1, '2025-11-02 16:36:38', '2025-11-02 16:36:38'),
(5, '1', '1st Semester 2024-2025', '2024-2025', '2024-08-04', '2024-12-08', 1, '2025-11-02 16:46:07', '2025-11-02 16:46:07');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `role` enum('admin','registrar','professor','student') NOT NULL DEFAULT 'student',
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `role`, `first_name`, `last_name`, `status`, `created_at`) VALUES
(1, 'jqtumamak@paterostechnologicalcollege.edu.ph', '$2y$10$Y4tMPwhtLW1Hku2iYAFT7uqoW9wToz4pYSRoGnuW1NNt69T0WeB3q', 'admin', 'MIS', 'Admin', 'ACTIVE', '2025-10-27 16:29:29'),
(2, 'jmnaling@paterostechnologicalcollege.edu.ph', '$2y$10$2.gkduk1zwBARcSeURTFk.6oNR4dLLYlWf/e5i.rvLIN0jFs5i9s.', 'professor', 'Juan', 'Naling', 'ACTIVE', '2025-10-27 16:29:29'),
(3, 'cgbaldemor@paterostechnologicalcollege.edu.ph', '$2y$10$moBHjGvIBTtCSJ.PSuJQWOlWKjY4N44E15psZiyVH64ZxUmnPqshy', 'student', 'Cris', 'Baldemor', 'ACTIVE', '2025-10-27 16:29:29');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `document_requests`
--
ALTER TABLE `document_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `edit_requests`
--
ALTER TABLE `edit_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grading_sheet_id` (`grading_sheet_id`),
  ADD KEY `professor_id` (`professor_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grade_item_id` (`grade_item_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `grade_components`
--
ALTER TABLE `grade_components`
  ADD PRIMARY KEY (`id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `grade_items`
--
ALTER TABLE `grade_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `component_id` (`component_id`);

--
-- Indexes for table `grading_sheets`
--
ALTER TABLE `grading_sheets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `professor_id` (`professor_id`);

--
-- Indexes for table `professors`
--
ALTER TABLE `professors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `professor_id` (`professor_id`),
  ADD UNIQUE KEY `uq_professors_ptc_email` (`ptc_email`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `fk_sections_term` (`term_id`),
  ADD KEY `fk_sections_subject` (`subject_id`),
  ADD KEY `fk_sections_professor` (`assigned_professor_id`);

--
-- Indexes for table `section_students`
--
ALTER TABLE `section_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_section_student` (`section_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `section_subjects`
--
ALTER TABLE `section_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `professor_id` (`professor_id`),
  ADD KEY `term_id` (`term_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD UNIQUE KEY `uq_students_ptc_email` (`ptc_email`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_subjects_code` (`subject_code`);

--
-- Indexes for table `terms`
--
ALTER TABLE `terms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `document_requests`
--
ALTER TABLE `document_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `edit_requests`
--
ALTER TABLE `edit_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grade_components`
--
ALTER TABLE `grade_components`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `grade_items`
--
ALTER TABLE `grade_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grading_sheets`
--
ALTER TABLE `grading_sheets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `professors`
--
ALTER TABLE `professors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `section_students`
--
ALTER TABLE `section_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `section_subjects`
--
ALTER TABLE `section_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `terms`
--
ALTER TABLE `terms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `document_requests`
--
ALTER TABLE `document_requests`
  ADD CONSTRAINT `document_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `edit_requests`
--
ALTER TABLE `edit_requests`
  ADD CONSTRAINT `edit_requests_ibfk_1` FOREIGN KEY (`grading_sheet_id`) REFERENCES `grading_sheets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `edit_requests_ibfk_2` FOREIGN KEY (`professor_id`) REFERENCES `professors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`grade_item_id`) REFERENCES `grade_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grade_components`
--
ALTER TABLE `grade_components`
  ADD CONSTRAINT `grade_components_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grade_items`
--
ALTER TABLE `grade_items`
  ADD CONSTRAINT `grade_items_ibfk_1` FOREIGN KEY (`component_id`) REFERENCES `grade_components` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grading_sheets`
--
ALTER TABLE `grading_sheets`
  ADD CONSTRAINT `grading_sheets_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grading_sheets_ibfk_2` FOREIGN KEY (`professor_id`) REFERENCES `professors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `professors`
--
ALTER TABLE `professors`
  ADD CONSTRAINT `professors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sections`
--
ALTER TABLE `sections`
  ADD CONSTRAINT `fk_sections_professor` FOREIGN KEY (`assigned_professor_id`) REFERENCES `professors` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sections_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sections_term` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `sections_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `section_students`
--
ALTER TABLE `section_students`
  ADD CONSTRAINT `section_students_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `section_students_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `section_subjects`
--
ALTER TABLE `section_subjects`
  ADD CONSTRAINT `section_subjects_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `section_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `section_subjects_ibfk_3` FOREIGN KEY (`professor_id`) REFERENCES `professors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `section_subjects_ibfk_4` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
