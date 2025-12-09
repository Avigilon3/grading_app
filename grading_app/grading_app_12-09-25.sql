-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 09, 2025 at 11:05 AM
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
(22, 1, 'ADD_TO_MASTERLIST', 'Added Burgos, Jose Apolonio to section ID 10', '::1', '2025-11-02 14:24:41'),
(23, 1, 'UPDATE_PROFILE', 'Updated name', '::1', '2025-11-08 04:33:32'),
(24, 1, 'UPDATE_PROFILE', 'Updated name', '::1', '2025-11-08 04:33:38'),
(25, 1, 'ADD_TO_MASTERLIST', 'Added Burgos, Jose Apolonio to section ID 1', '::1', '2025-11-08 08:59:35'),
(26, 1, 'SET_ACTIVE_TERM', 'Active term id: 2', '::1', '2025-11-08 14:08:10'),
(27, 1, 'UPDATE_PROFESSOR', 'Updated professor: PROF-0001', '::1', '2025-11-17 06:48:28'),
(28, 1, 'UPDATE_PROFESSOR', 'Updated professor: PROF-0003', '::1', '2025-11-17 06:49:15'),
(29, 1, 'UPDATE_PROFESSOR', 'Updated professor: PROF-0004', '::1', '2025-11-17 06:49:48'),
(30, 1, 'UPDATE_PROFESSOR', 'Updated professor: PROF-0005', '::1', '2025-11-17 06:50:45'),
(31, 1, 'UPDATE_PROFESSOR', 'Updated professor: PROF-0002', '::1', '2025-11-17 06:51:31'),
(32, 1, 'ADD_COURSE', 'Added course: BSIT', '::1', '2025-11-17 08:35:49'),
(33, 1, 'ADD_COURSE', 'Added course: CSS', '::1', '2025-11-17 08:36:18'),
(34, 1, 'ADD_COURSE', 'Added course: BSOA', '::1', '2025-11-17 08:55:52'),
(35, 1, 'UPDATE_SUBJECT', 'Updated subject: AIS101', '::1', '2025-11-17 09:30:15'),
(36, 1, 'UPDATE_SUBJECT', 'Updated subject: AIS101', '::1', '2025-11-17 09:34:29'),
(37, 1, 'UPDATE_SUBJECT', 'Updated subject: FIL1', '::1', '2025-11-17 09:49:46'),
(38, 1, 'UPDATE_SUBJECT', 'Updated subject: DLD1', '::1', '2025-11-17 10:03:18'),
(39, 1, 'UPDATE_SUBJECT', 'Updated subject: MS 102', '::1', '2025-11-17 10:05:04'),
(40, 1, 'UPDATE_SUBJECT', 'Updated subject: AIS101', '::1', '2025-11-17 10:05:09'),
(41, 1, 'UPDATE_SUBJECT', 'Updated subject: FIL1', '::1', '2025-11-17 10:05:13'),
(42, 1, 'UPDATE_SUBJECT', 'Updated subject: AIS101', '::1', '2025-11-17 11:47:53'),
(43, 1, 'UPDATE_SECTION', 'Updated section: BSIT 3C', '::1', '2025-11-17 12:26:04'),
(44, 1, 'DELETE_TERM', 'Deleted term id: 1', '::1', '2025-11-17 12:31:31'),
(45, 1, 'UPDATE_SECTION', 'Updated section: BSIT-3A', '::1', '2025-11-17 12:32:21'),
(46, 1, 'ADD_TERM', 'Added term: 2nd Semester 2025-2026', '::1', '2025-11-17 12:33:17'),
(47, 1, 'ADD_SUBJECT', 'Added subject: GECETH', '::1', '2025-11-17 13:44:28'),
(48, 1, 'ADD_SUBJECT', 'Added subject: WS 101', '::1', '2025-11-17 13:44:40'),
(49, 1, 'ADD_SUBJECT', 'Added subject: CC 104', '::1', '2025-11-17 13:45:00'),
(50, 1, 'ADD_SUBJECT', 'Added subject: OP 3', '::1', '2025-11-17 13:45:09'),
(51, 1, 'ADD_SUBJECT', 'Added subject: IM 101', '::1', '2025-11-17 13:45:20'),
(52, 1, 'ADD_SUBJECT', 'Added subject: CRM 101', '::1', '2025-11-17 13:45:43'),
(53, 1, 'UPDATE_SUBJECT', 'Updated subject: CRM 101', '::1', '2025-11-17 13:46:00'),
(54, 1, 'ADD_SUBJECT', 'Added subject: NET 101', '::1', '2025-11-17 13:46:13'),
(55, 1, 'ADD_SUBJECT', 'Added subject: WS 102', '::1', '2025-11-17 13:46:22'),
(56, 1, 'ADD_SUBJECT', 'Added subject: OOP 1', '::1', '2025-11-17 13:46:32'),
(57, 1, 'ADD_SUBJECT', 'Added subject: OP 4', '::1', '2025-11-17 13:46:51'),
(58, 1, 'ADD_SUBJECT', 'Added subject: OJT', '::1', '2025-11-17 13:47:03'),
(59, 1, 'ADD_PROFESSOR', 'Added professor: PROF-0006', '::1', '2025-11-17 13:48:11'),
(60, 1, 'ADD_PROFESSOR', 'Added professor: PROF-0007', '::1', '2025-11-17 13:48:57'),
(61, 1, 'ADD_PROFESSOR', 'Added professor: PROF-0008', '::1', '2025-11-17 13:49:52'),
(62, 1, 'ADD_PROFESSOR', 'Added professor: PROF-0009', '::1', '2025-11-17 13:50:25'),
(63, 1, 'ADD_PROFESSOR', 'Added professor: PROF-0010', '::1', '2025-11-17 13:50:56'),
(64, 1, 'ADD_PROFESSOR', 'Added professor: PROF-0011', '::1', '2025-11-17 13:51:33'),
(65, 1, 'ADD_PROFESSOR', 'Added professor: PROF-00012', '::1', '2025-11-17 13:52:19'),
(66, 1, 'ADD_PROFESSOR', 'Added professor: PROF-0013', '::1', '2025-11-17 13:53:19'),
(67, 1, 'UPDATE_SECTION', 'Updated section: BSIT 2B', '::1', '2025-11-17 13:54:47'),
(68, 1, 'ADD_TO_MASTERLIST', 'Added Rizal, Jose Mercado to section ID 9', '::1', '2025-11-17 14:27:26'),
(69, 1, 'DELETE_SECTION', 'Deleted section id: 9', '::1', '2025-11-17 14:45:24'),
(70, 1, 'UPDATE_SECTION', 'Updated section: BSIT 1A', '::1', '2025-11-17 14:45:38'),
(71, 1, 'ADD_SECTION', 'Added section: BSOA 4S', '::1', '2025-11-17 14:46:04'),
(72, 1, 'ADD_SECTION', 'Added section: CSS 3M', '::1', '2025-11-17 14:46:25'),
(73, 1, 'ADD_TO_MASTERLIST', 'Added Rizal, Jose Mercado to section ID 11', '::1', '2025-11-17 15:33:28'),
(74, 1, 'ADD_TO_MASTERLIST', 'Added Burgos, Jose Apolonio to section ID 11', '::1', '2025-11-18 02:10:11'),
(75, 1, 'ADD_STUDENT', 'Added student: 2022-9800', '::1', '2025-11-18 04:02:35'),
(76, 1, 'UPDATE_SUBJECT', 'Updated subject: CC 104', '::1', '2025-11-18 04:59:28'),
(77, 1, 'UPDATE_PROFESSOR', 'Updated professor: PROF-0008', '::1', '2025-11-18 04:59:45'),
(78, 1, 'ADD_SUBJECT', 'Added subject: AAA222', '::1', '2025-11-18 05:01:58'),
(79, 1, 'ADD_PROFESSOR', 'Added professor: PROF-0016', '::1', '2025-11-18 05:12:29'),
(80, 1, 'ADD_STUDENT', 'Added student: 25BSIT-1545', '::1', '2025-11-18 13:46:04'),
(81, 1, 'DELETE_STUDENT', 'Deleted student id: 1', '::1', '2025-11-18 15:14:18'),
(82, 1, 'UPDATE_STUDENT', 'Updated student: 25BSIT-1545', '::1', '2025-11-18 15:14:23'),
(83, 1, 'UPDATE_PROFESSOR', 'Updated professor: PROF-0001', '::1', '2025-11-22 13:15:22'),
(84, 1, 'UPDATE_PROFESSOR', 'Updated professor: PROF-0016', '::1', '2025-11-22 13:17:12'),
(85, 1, 'UPDATE_PROFESSOR', 'Updated professor: PROF-0001', '::1', '2025-11-22 13:22:35'),
(86, 1, 'UPDATE_PROFESSOR', 'Updated professor: PROF-0008', '::1', '2025-11-22 14:45:30'),
(87, 1, 'UPDATE_PROFESSOR', 'Updated professor: PROF-0011', '::1', '2025-11-22 14:45:33'),
(88, 1, 'UPDATE_PROFESSOR', 'Updated professor: PROF-0010', '::1', '2025-11-22 14:45:35'),
(89, 1, 'UPDATE_PROFESSOR', 'Updated professor: PROF-0006', '::1', '2025-11-22 14:45:38'),
(90, 1, 'UPDATE_PROFESSOR', 'Updated professor: PROF-0001', '::1', '2025-11-22 14:45:42'),
(91, 1, 'SET_DEADLINE', 'Grading sheet #7 deadline updated.', '::1', '2025-11-23 09:14:33'),
(92, 1, 'UPDATE_DOC_REQUEST', 'Updated document request id: 1 -> scheduled', '::1', '2025-11-23 10:26:53'),
(93, 1, 'UPDATE_DOC_REQUEST', 'Updated document request id: 1 -> completed', '::1', '2025-11-30 06:47:46'),
(94, 1, 'UPDATE_TERM', 'Updated term: 2nd Semester 2025-2026', '::1', '2025-11-30 06:48:48'),
(95, 1, 'UPDATE_DOC_REQUEST', 'Updated document request id: 4 -> scheduled', '::1', '2025-11-30 08:16:07'),
(96, 1, 'UPDATE_DOC_REQUEST', 'Updated document request id: 5 -> scheduled', '::1', '2025-11-30 09:16:49'),
(97, 1, 'UPDATE_DOC_REQUEST', 'Updated document request id: 6 -> scheduled', '::1', '2025-11-30 11:35:23'),
(98, 1, 'UPDATE_DOC_REQUEST', 'Updated document request id: 5 -> completed', '::1', '2025-11-30 11:39:47'),
(99, 1, 'UPDATE_DOC_REQUEST', 'Updated document request id: 4 -> completed', '::1', '2025-11-30 11:39:48'),
(100, 1, 'UPDATE_DOC_REQUEST', 'Updated document request id: 7 -> scheduled', '::1', '2025-11-30 11:49:51'),
(101, 1, 'UPDATE_DOC_REQUEST', 'Updated document request id: 7 -> released', '::1', '2025-11-30 11:49:55'),
(102, 1, 'ADD_STUDENT', 'Added student: 2022-8196', '::1', '2025-12-07 09:37:55');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `title` varchar(190) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `code`, `title`, `description`, `is_active`) VALUES
(1, 'IT101', 'Intro to IT', NULL, 1),
(2, 'BSIT', 'Bachelor of Science Information Technology', 'The emphasis of this four-year degree is on technology and computers. Its main goal is to prepare students for the rapidly changing demands of the IT sector by teaching them the fundamentals of computer hardware, software, databases, algorithms, telecommunications, user strategies, web development, application testing, and computer graphics. As a result, when applying for IT support business processes, be well-prepared.', 1),
(3, 'CSS', 'Certificate in Computer Sciences', 'Under the Institute of Information and Communication Technology, this two-year ladderized curriculum is offered. Information technology, database management, and system maintenance are its key focus.', 1),
(4, 'BSOA', 'Bachelor of Science in Office Administration', 'Students enrolled in this four-year degree are prepared for a job in a professional, outcome-focused, high-tech setting. The program includes courses that will fully acquaint students with office administration procedures, technology, and modern business setup.', 1);

-- --------------------------------------------------------

--
-- Table structure for table `document_requests`
--

CREATE TABLE `document_requests` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `type` enum('report','certificate') NOT NULL,
  `purpose` varchar(190) DEFAULT NULL,
  `status` enum('pending','scheduled','ready','released','cancelled') DEFAULT 'pending',
  `created_at` datetime DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `released_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_requests`
--

INSERT INTO `document_requests` (`id`, `student_id`, `type`, `purpose`, `status`, `created_at`, `scheduled_at`, `released_at`) VALUES
(6, 7, 'certificate', 'Year Level: 1st Year | Semester: 1st Semester', 'scheduled', '2025-11-30 12:11:48', '2025-12-17 00:00:00', NULL),
(7, 7, 'certificate', 'Year Level: 3rd Year | Semester: 2nd Semester', 'released', '2025-11-30 12:49:14', '2025-12-04 00:00:00', '2025-11-30 12:49:00'),
(8, 7, 'report', NULL, 'cancelled', '2025-12-06 14:55:55', NULL, NULL),
(9, 7, 'report', 'Year Level: 1st Year | Semester: 1st Semester', 'pending', '2025-12-06 15:02:27', NULL, NULL),
(10, 7, 'certificate', 'Year Level: 4th Year | Semester: 2nd Semester', 'pending', '2025-12-06 15:12:00', NULL, NULL);

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
  `grading_sheet_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `weight` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grade_components`
--

INSERT INTO `grade_components` (`id`, `grading_sheet_id`, `name`, `weight`) VALUES
(18, 7, 'Activity', 40.00),
(19, 7, 'Exam', 40.00),
(20, 7, 'Quizzes', 10.00),
(21, 7, 'Attendance', 10.00);

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
  `section_subject_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `status` enum('draft','submitted','locked','reopened') DEFAULT 'draft',
  `deadline_at` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grading_sheets`
--

INSERT INTO `grading_sheets` (`id`, `section_subject_id`, `section_id`, `professor_id`, `status`, `deadline_at`, `submitted_at`) VALUES
(3, 3, 11, 17, 'draft', NULL, NULL),
(4, 6, 11, 20, 'draft', NULL, NULL),
(5, 5, 11, 19, 'draft', NULL, NULL),
(6, 1, 11, 15, 'draft', NULL, NULL),
(7, 10, 11, 1, 'draft', '2025-12-30 00:00:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `message`, `is_read`, `created_at`, `read_at`) VALUES
(1, 3, 'pickup_schedule', 'Your request for a Certification of Grades has been scheduled for pick-up.', 1, '2025-11-30 19:35:23', '2025-11-30 19:49:20'),
(2, 3, 'pickup_schedule', 'Your request for a Certification of Grades has been scheduled for pick-up.', 0, '2025-11-30 19:49:51', NULL),
(3, 1, 'document_request', 'Carlo Guzman Baldemor is requesting for Certificate of Grades', 0, '2025-12-06 22:12:00', NULL);

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
  `subject_id` int(11) DEFAULT NULL,
  `schedule` varchar(150) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `professors`
--

INSERT INTO `professors` (`id`, `user_id`, `professor_id`, `ptc_email`, `first_name`, `middle_name`, `last_name`, `subject_id`, `schedule`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 2, 'PROF-0001', 'jmnaling@paterostechnologicalcollege.edu.ph', 'Alden', 'Asurion', 'Richards', 14, 'WTH 4PM-6PM', 1, '2025-11-02 15:05:53', '2025-11-22 21:15:22'),
(8, NULL, 'PROF-0002', 'storre@paterostechnologicalcollege.edu.ph', 'Shider Rey', 'Dela Cruz', 'Torre', 1, 'Sat 3:00PM-5:00PM', 1, '2025-11-02 15:22:08', '2025-11-17 14:51:31'),
(9, NULL, 'PROF-0003', 'rnacario@paterostechnologicalcollege.edu.ph', 'Ryan Cesar', 'Legaspi', 'Nacario', 2, 'Tue 1:00PM-4:00PM', 1, '2025-11-02 15:22:08', '2025-11-17 14:49:15'),
(10, NULL, 'PROF-0004', 'mknazareno@paterostechnologicalcollege.edu.ph', 'Mark Kenneth', 'Borja', 'Nazareno', 3, 'TH 4:00PM-7:00PM', 1, '2025-11-02 15:22:08', '2025-11-17 14:49:48'),
(14, NULL, 'PROF-0005', 'maria.santos@paterostechnologicalcollege.edu.ph', 'Maria Elena', 'Cruz', 'Santos', 1, 'Mon 8:00AM-11:00AM', 0, '2025-11-02 16:00:45', '2025-11-17 14:50:45'),
(15, NULL, 'PROF-0006', 'flamit@paterostechnologicalcollege.edu.ph', 'Flor', '', 'Lamit', 5, 'Mon 9:00 AM - 11:00AM', 1, '2025-11-17 21:48:11', '2025-11-17 21:48:11'),
(16, NULL, 'PROF-0007', 'mgnocon@paterostechnologicalcollege.edu.ph', 'Mary Grace', '', 'Nocon', 6, 'Fri 4:00 PM - 9:00PM', 1, '2025-11-17 21:48:57', '2025-11-17 21:48:57'),
(17, NULL, 'PROF-0008', 'jabarracozo@paterostechnologicalcollege.edu.ph', 'Jissalyn', '', 'Abarracozo', 7, 'Mon 4:00 PM - 9:00PM', 1, '2025-11-17 21:49:52', '2025-11-17 21:49:52'),
(18, NULL, 'PROF-0009', 'jramos@paterostechnologicalcollege.edu.ph', 'Jocelyn', '', 'Ramos', 8, 'Wed 7:00AM - 11:00AM', 1, '2025-11-17 21:50:25', '2025-11-17 21:50:25'),
(19, NULL, 'PROF-0010', 'clamera@paterostechnologicalcollege.edu.ph', 'Cornelio', '', 'Lamera', 9, 'Sat 4:00PM - 9:00PM', 1, '2025-11-17 21:50:56', '2025-11-17 21:50:56'),
(20, NULL, 'PROF-0011', 'jaguilar@paterostechnologicalcollege.edu.ph', 'Jasper', '', 'Aguilar', 10, 'Sat 10:00AM - 3:00PM', 1, '2025-11-17 21:51:33', '2025-11-17 21:51:33'),
(21, NULL, 'PROF-00012', 'msaavedra@paterostechnologicalcollege.edu.ph', 'Manuel', '', 'Saavedra III', 11, NULL, 1, '2025-11-17 21:52:19', '2025-11-17 21:52:19'),
(22, NULL, 'PROF-0013', 'aarsison@paterostechnologicalcollege.edu.ph', 'Ariel Antwan Rolando', '', 'Sison', 13, 'Mon 7:00AM - 12:00PM', 1, '2025-11-17 21:53:19', '2025-11-17 21:53:19'),
(23, NULL, 'PROF-0016', 'testingduplicate@paterostechnologicalcollege.edu.ph', 'Test', 'Test', 'Test', 15, 'WTH 4PM-6PM', 1, '2025-11-18 13:12:29', '2025-11-22 21:17:12');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` int(11) NOT NULL,
  `section_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `year_level` enum('1','2','3','4') NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `term_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`id`, `section_name`, `is_active`, `created_at`, `updated_at`, `year_level`, `course_id`, `term_id`) VALUES
(1, 'BSIT-3A', 1, '2025-11-02 15:05:53', '2025-11-17 20:32:21', '3', 1, 5),
(8, 'BSIT 1A', 1, '2025-11-02 15:49:49', '2025-11-17 22:45:38', '1', 2, 6),
(10, 'BSIT 3C', 1, '2025-11-02 15:49:49', '2025-11-17 20:26:04', '3', 2, 2),
(11, 'BSIT 2B', 1, '2025-11-02 17:15:07', '2025-11-17 21:54:47', '2', 2, 2),
(12, 'BSOA 4S', 1, '2025-11-17 22:46:04', '2025-11-17 22:46:04', '4', 4, 2),
(13, 'CSS 3M', 1, '2025-11-17 22:46:25', '2025-11-17 22:46:25', '3', 3, 6);

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

--
-- Dumping data for table `section_students`
--

INSERT INTO `section_students` (`id`, `section_id`, `student_id`, `created_at`) VALUES
(2, 11, 4, '2025-11-17 23:33:28'),
(3, 11, 5, '2025-11-18 10:10:11'),
(4, 11, 7, '2025-11-18 23:14:23');

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

--
-- Dumping data for table `section_subjects`
--

INSERT INTO `section_subjects` (`id`, `section_id`, `subject_id`, `professor_id`, `term_id`, `created_at`) VALUES
(1, 11, 5, 15, 2, '2025-11-18 13:05:46'),
(2, 11, 6, 16, 2, '2025-11-18 13:05:46'),
(3, 11, 7, 17, 2, '2025-11-18 13:05:46'),
(4, 11, 8, 18, 2, '2025-11-18 13:05:46'),
(5, 11, 9, 19, 2, '2025-11-18 13:05:46'),
(6, 11, 10, 20, 2, '2025-11-18 13:05:46'),
(7, 11, 11, 21, 6, '2025-11-18 13:10:54'),
(8, 11, 12, NULL, 6, '2025-11-18 13:10:54'),
(9, 11, 13, 22, 6, '2025-11-18 13:10:54'),
(10, 11, 14, 1, 6, '2025-11-18 13:10:54'),
(11, 11, 15, NULL, 6, '2025-11-18 13:10:54'),
(12, 11, 16, NULL, 6, '2025-11-18 13:10:54'),
(13, 10, 2, 9, 2, '2025-11-22 21:49:16'),
(14, 10, 3, 10, 2, '2025-11-22 21:49:16'),
(15, 10, 4, NULL, 2, '2025-11-22 21:49:16');

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
(2, NULL, '2022-9800', 'maria.santos@paterostechnologicalcollege.edu.ph', 'Maria', 'Luna', 'Santos', '4', 'BSIT 2B', 'Irregular'),
(4, NULL, '1900-0001', 'joserizal@paterostechnologicalcollege.edu.ph', 'Jose', 'Mercado', 'Rizal', '2', 'CCS 1A', 'Regular'),
(5, NULL, '1900-0002', 'jburgos@paterostechnologicalcollege.edu.ph', 'Jose', 'Apolonio', 'Burgos', '1', 'BSIT 2B', 'Irregular'),
(7, 3, '25BSIT-1545', 'cgbaldemor@paterostechnologicalcollege.edu.ph', 'Carlo', 'Guzman', 'Baldemor', '2', 'BSIT 2B', 'Regular');

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
  `course_id` int(11) DEFAULT NULL,
  `year_level` int(11) DEFAULT NULL,
  `term_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_code`, `subject_title`, `units`, `description`, `course_id`, `year_level`, `term_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'AIS101', 'Information Assurance and Security', 3.0, 'About securities in IT field', NULL, 3, 2, 1, '2025-11-02 15:47:41', '2025-11-17 19:47:53'),
(2, 'FIL1', 'Komunikasyon sa Akademikong Filipino', 3.0, 'Pagpapahalaga sa ating Wika eme', 2, 3, 2, 1, '2025-11-02 15:47:41', '2025-11-17 18:05:13'),
(3, 'DLD1', 'Digital Logic Design', 3.0, 'Covers Binary', 2, 3, 2, 1, '2025-11-02 15:47:41', '2025-11-17 18:03:18'),
(4, 'MS 102', 'Modeling and Simulation', 3.0, 'Simulations', 2, 3, 2, 1, '2025-11-02 16:03:35', '2025-11-17 18:05:04'),
(5, 'GECETH', 'Professional Ethics', 3.0, '', 2, 2, 2, 1, '2025-11-17 21:44:28', '2025-11-17 21:44:28'),
(6, 'WS 101', 'Introduction to Web Technologies', 3.0, '', 2, 2, 2, 1, '2025-11-17 21:44:40', '2025-11-17 21:44:40'),
(7, 'CC 104', 'Data Structures and Algorithm', 5.0, '', 2, 2, 2, 1, '2025-11-17 21:45:00', '2025-11-17 21:45:00'),
(8, 'OP 3', 'Office Productivity 3 (Animation)', 3.0, '', 2, 2, 2, 1, '2025-11-17 21:45:09', '2025-11-17 21:45:09'),
(9, 'IM 101', 'Database Management System', 3.0, '', 2, 2, 2, 1, '2025-11-17 21:45:20', '2025-11-17 21:45:20'),
(10, 'CRM 101', 'Computer Repair Maintenance', 3.0, 'CSS (COMPUTER SYSTEM SERVICING)', 2, 2, 2, 1, '2025-11-17 21:45:43', '2025-11-17 21:46:00'),
(11, 'NET 101', 'Network Design and Management', 3.0, '', 2, 2, 6, 1, '2025-11-17 21:46:13', '2025-11-17 21:46:13'),
(12, 'WS 102', 'Web Programming 2', 3.0, '', 2, 2, 6, 1, '2025-11-17 21:46:22', '2025-11-17 21:46:22'),
(13, 'OOP 1', 'Programming 4 (OOP)', 3.0, '', 2, 2, 6, 1, '2025-11-17 21:46:32', '2025-11-17 21:46:32'),
(14, 'OP 4', 'Office Productivity 4 (Computer Aided design)', 3.0, '', 2, 2, 6, 1, '2025-11-17 21:46:51', '2025-11-17 21:46:51'),
(15, 'OJT', 'On-The-Job-Training (300 hrs.)', 1.0, '', 2, 2, 6, 1, '2025-11-17 21:47:03', '2025-11-17 21:47:03'),
(16, 'AAA222', 'Testing the Subject', 3.0, '', 2, 2, 6, 1, '2025-11-18 13:01:58', '2025-11-18 13:01:58');

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
(2, '1', '1st Semester 2025-2026', '2025-2026', '2026-01-10', '2026-05-15', 1, '2025-11-02 15:47:11', '2025-11-08 22:08:10'),
(4, '1', '1st Semester 2021-2022', '2021-2022', '2025-11-02', '2025-11-03', 0, '2025-11-02 16:36:38', '2025-11-08 22:08:10'),
(5, '1', '1st Semester 2024-2025', '2024-2025', '2024-08-04', '2024-12-08', 0, '2025-11-02 16:46:07', '2025-11-08 22:08:10'),
(6, '2', '2nd Semester 2025-2026', '2025-2026', '2026-01-01', '2026-05-31', 0, '2025-11-17 20:33:17', '2025-11-30 14:48:48');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `verify_code` varchar(50) DEFAULT NULL,
  `role` enum('admin','registrar','professor','student') NOT NULL DEFAULT 'student',
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `verify_code`, `role`, `first_name`, `last_name`, `status`, `created_at`) VALUES
(1, 'jqtumamak@paterostechnologicalcollege.edu.ph', '$2y$10$Y4tMPwhtLW1Hku2iYAFT7uqoW9wToz4pYSRoGnuW1NNt69T0WeB3q', NULL, 'admin', 'Jerick', 'Tumamak', 'ACTIVE', '2025-10-27 16:29:29'),
(2, 'jmnaling@paterostechnologicalcollege.edu.ph', '$2y$10$2.gkduk1zwBARcSeURTFk.6oNR4dLLYlWf/e5i.rvLIN0jFs5i9s.', NULL, 'professor', 'Joshua', 'Naling', 'ACTIVE', '2025-10-27 16:29:29'),
(3, 'cgbaldemor@paterostechnologicalcollege.edu.ph', '$2y$10$moBHjGvIBTtCSJ.PSuJQWOlWKjY4N44E15psZiyVH64ZxUmnPqshy', NULL, 'student', 'Carlo', 'Baldemor', 'ACTIVE', '2025-10-27 16:29:29');

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
  ADD KEY `fk_gc_sheet` (`grading_sheet_id`);

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
  ADD UNIQUE KEY `uq_grading_sheets_section_subject` (`section_subject_id`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `professor_id` (`professor_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_notifications_user_unread` (`user_id`,`is_read`);

--
-- Indexes for table `professors`
--
ALTER TABLE `professors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `professor_id` (`professor_id`),
  ADD UNIQUE KEY `uq_professors_ptc_email` (`ptc_email`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `fk_professors_subject` (`subject_id`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `fk_sections_term` (`term_id`);

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
  ADD UNIQUE KEY `uq_subjects_code` (`subject_code`),
  ADD KEY `fk_subjects_course` (`course_id`),
  ADD KEY `fk_subjects_term` (`term_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `document_requests`
--
ALTER TABLE `document_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `edit_requests`
--
ALTER TABLE `edit_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grade_components`
--
ALTER TABLE `grade_components`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `grade_items`
--
ALTER TABLE `grade_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grading_sheets`
--
ALTER TABLE `grading_sheets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `professors`
--
ALTER TABLE `professors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `section_students`
--
ALTER TABLE `section_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `section_subjects`
--
ALTER TABLE `section_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `terms`
--
ALTER TABLE `terms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
-- Constraints for table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`grade_item_id`) REFERENCES `grade_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grade_components`
--
ALTER TABLE `grade_components`
  ADD CONSTRAINT `fk_gc_sheet` FOREIGN KEY (`grading_sheet_id`) REFERENCES `grading_sheets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grade_items`
--
ALTER TABLE `grade_items`
  ADD CONSTRAINT `grade_items_ibfk_1` FOREIGN KEY (`component_id`) REFERENCES `grade_components` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grading_sheets`
--
ALTER TABLE `grading_sheets`
  ADD CONSTRAINT `fk_grading_sheets_section_subject` FOREIGN KEY (`section_subject_id`) REFERENCES `section_subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grading_sheets_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grading_sheets_ibfk_2` FOREIGN KEY (`professor_id`) REFERENCES `professors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `professors`
--
ALTER TABLE `professors`
  ADD CONSTRAINT `fk_professors_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `professors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sections`
--
ALTER TABLE `sections`
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

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `fk_subjects_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_subjects_term` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
