-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 10, 2025 at 11:33 AM
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
(102, 1, 'ADD_STUDENT', 'Added student: 2022-8196', '::1', '2025-12-07 09:37:55'),
(103, 1, 'CHANGE_STATUS', 'Grading sheet #7 set to draft.', '::1', '2025-12-09 15:47:42'),
(104, 1, 'SET_DEADLINE', 'Grading sheet #7 deadline updated.', '::1', '2025-12-09 15:54:40'),
(105, 1, 'CHANGE_STATUS', 'Grading sheet #7 set to locked.', '::1', '2025-12-09 15:55:26'),
(106, 1, 'SET_DEADLINE', 'Grading sheet #7 deadline updated.', '::1', '2025-12-09 16:03:03'),
(107, 1, 'CHANGE_STATUS', 'Grading sheet #7 set to draft.', '::1', '2025-12-09 16:03:08'),
(108, 1, 'CHANGE_STATUS', 'Grading sheet #7 set to draft.', '::1', '2025-12-09 16:07:41'),
(109, 1, 'SET_DEADLINE', 'Grading sheet #7 deadline updated.', '::1', '2025-12-09 16:08:00'),
(110, 1, 'CHANGE_STATUS', 'Grading sheet #7 set to locked.', '::1', '2025-12-09 16:08:12'),
(111, 1, 'DELETE_SUBJECT', 'Deleted subject id: 1', '::1', '2025-12-09 16:11:16'),
(112, 1, 'UPDATE_TERM', 'Updated term: 2nd Semester 2025-2026', '::1', '2025-12-09 16:25:39'),
(113, 1, 'UPDATE_TERM', 'Updated term: 1st Semester 2025-2026', '::1', '2025-12-09 16:25:44'),
(114, 1, 'UPDATE_TERM', 'Updated term: 1st Semester 2025-2026', '::1', '2025-12-09 16:27:03'),
(115, 1, 'UPDATE_TERM', 'Updated term: 2nd Semester 2025-2026', '::1', '2025-12-09 16:27:07'),
(116, 1, 'UPDATE_PROFESSOR', 'Updated professor: PROF-0001', '::1', '2025-12-09 16:38:00'),
(117, 1, 'UPDATE_PROFESSOR', 'Updated professor: PROF-0008', '::1', '2025-12-09 16:47:17'),
(118, 1, 'UPDATE_PROFESSOR', 'Updated professor: PROF-0001', '::1', '2025-12-09 16:47:25'),
(119, 1, 'UPDATE_PROFESSOR', 'Updated professor: PROF-0001', '::1', '2025-12-09 16:58:44'),
(120, 1, 'SET_DEADLINE', 'Grading sheet #9 deadline updated.', '::1', '2025-12-09 17:00:42'),
(121, 1, 'CHANGE_STATUS', 'Grading sheet #9 set to locked.', '::1', '2025-12-09 17:01:12'),
(122, 1, 'DECIDE_EDIT_REQUEST', 'APPROVED edit request id: 1', '::1', '2025-12-09 18:38:33'),
(123, 1, 'DECIDE_EDIT_REQUEST', 'APPROVED edit request id: 1', '::1', '2025-12-09 18:45:34'),
(124, 1, 'CHANGE_STATUS', 'Grading sheet #9 set to locked.', '::1', '2025-12-09 18:55:01'),
(125, 1, 'DECIDE_EDIT_REQUEST', 'APPROVED edit request id: 1', '::1', '2025-12-09 18:56:09'),
(126, 1, 'SET_DEADLINE', 'Grading sheet #4 deadline updated.', '::1', '2025-12-10 01:18:15'),
(127, 1, 'UPDATE_PROFESSOR', 'Updated professor: PROF-0009', '::1', '2025-12-10 01:20:51'),
(128, 1, 'SET_DEADLINE', 'Grading sheet #5 deadline updated.', '::1', '2025-12-10 01:22:10'),
(129, 1, 'SET_DEADLINE', 'Grading sheet #10 deadline updated.', '::1', '2025-12-10 01:22:14'),
(130, 1, 'SET_DEADLINE', 'Grading sheet #8 deadline updated.', '::1', '2025-12-10 01:22:18'),
(131, 1, 'SET_DEADLINE', 'Grading sheet #6 deadline updated.', '::1', '2025-12-10 01:22:23'),
(132, 1, 'UPDATE_PROFESSOR', 'Updated professor: PROF-0007', '::1', '2025-12-10 01:39:49'),
(133, 1, 'SET_DEADLINE', 'Grading sheet #11 deadline updated.', '::1', '2025-12-10 01:40:04'),
(134, 1, 'SET_DEADLINE', 'Grading sheet #4 deadline updated.', '::1', '2025-12-10 01:40:30'),
(135, 1, 'SET_DEADLINE', 'Grading sheet #4 deadline updated.', '::1', '2025-12-10 01:43:11'),
(136, 1, 'SET_DEADLINE', 'Grading sheet #4 deadline updated.', '::1', '2025-12-10 01:46:20'),
(137, 1, 'SET_DEADLINE', 'Grading sheet #4 deadline updated.', '::1', '2025-12-10 01:46:43'),
(138, 1, 'CHANGE_STATUS', 'Grading sheet #4 set to locked.', '::1', '2025-12-10 01:47:12'),
(139, 1, 'ADD_STUDENT', 'Added student: 2022-8196', '::1', '2025-12-10 04:42:59');

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
-- Table structure for table `default_grade_components`
--

CREATE TABLE `default_grade_components` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `weight` decimal(5,2) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `default_grade_components`
--

INSERT INTO `default_grade_components` (`id`, `name`, `weight`, `sort_order`, `created_at`) VALUES
(1, 'Activity', 40.00, 1, '2025-12-09 15:12:29'),
(2, 'Exam', 40.00, 2, '2025-12-09 15:12:29'),
(3, 'Quizzes', 10.00, 3, '2025-12-09 15:12:29'),
(4, 'Attendance', 10.00, 4, '2025-12-09 15:12:29');

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

--
-- Dumping data for table `edit_requests`
--

INSERT INTO `edit_requests` (`id`, `grading_sheet_id`, `professor_id`, `reason`, `status`, `decided_by`, `decided_at`) VALUES
(1, 9, 1, 'I need to edit the score of a student', 'approved', 1, '2025-12-10 02:56:09');

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `id` int(11) NOT NULL,
  `grade_item_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `score` int(7) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`id`, `grade_item_id`, `student_id`, `score`) VALUES
(55, 36, 4, 10),
(56, 36, 5, 10),
(57, 36, 7, 9),
(58, 37, 4, 10),
(59, 37, 5, 10),
(60, 37, 7, 10),
(61, 38, 4, 10),
(62, 38, 5, 10),
(63, 38, 7, 10),
(67, 40, 4, 0),
(68, 40, 5, 5),
(69, 40, 7, 10),
(70, 41, 4, 10),
(71, 41, 5, 10),
(72, 41, 7, 10),
(73, 42, 4, 10),
(74, 42, 5, 10),
(75, 42, 7, 0),
(76, 43, 4, 10),
(77, 43, 5, 10),
(78, 43, 7, 10),
(79, 44, 4, 10),
(80, 44, 5, 10),
(81, 44, 7, 8),
(82, 45, 4, 10),
(83, 45, 5, 10),
(84, 45, 7, 10),
(85, 46, 4, 10),
(86, 46, 5, 10),
(87, 46, 7, 10),
(88, 47, 4, 32),
(89, 47, 5, 48),
(90, 47, 7, 41),
(91, 48, 4, 50),
(92, 48, 5, 50),
(93, 48, 7, 50),
(97, 50, 4, 3),
(98, 50, 5, 10),
(99, 50, 7, 7),
(100, 51, 4, 5),
(101, 51, 5, 10),
(102, 51, 7, 7),
(103, 52, 4, 10),
(104, 52, 5, 10),
(105, 52, 7, 10),
(106, 53, 4, 10),
(107, 53, 5, 10),
(108, 53, 7, 10),
(109, 54, 4, 10),
(110, 54, 5, 10),
(111, 54, 7, 10),
(112, 55, 4, 10),
(113, 55, 5, 10),
(114, 55, 7, 0),
(115, 56, 4, 10),
(116, 56, 5, 10),
(117, 56, 7, 10),
(118, 57, 4, 50),
(119, 57, 5, 50),
(120, 57, 7, 48),
(121, 58, 4, 100),
(122, 58, 5, 100),
(123, 58, 7, 100),
(124, 59, 4, 10),
(125, 59, 5, 10),
(126, 59, 7, 9),
(127, 60, 4, 10),
(128, 60, 5, 10),
(129, 60, 7, 10),
(130, 61, 4, 10),
(131, 61, 5, 10),
(132, 61, 7, 10),
(133, 62, 4, 10),
(134, 62, 5, 10),
(135, 62, 7, 5),
(136, 63, 4, 10),
(137, 63, 5, 10),
(138, 63, 7, 8),
(139, 64, 4, 10),
(140, 64, 5, 10),
(141, 64, 7, 9),
(142, 65, 4, 10),
(143, 65, 5, 10),
(144, 65, 7, 10),
(145, 66, 4, 10),
(146, 66, 5, 10),
(147, 66, 7, 5),
(148, 67, 4, 10),
(149, 67, 5, 10),
(150, 67, 7, 10),
(151, 68, 4, 46),
(152, 68, 5, 38),
(153, 68, 7, 46),
(154, 69, 4, 34),
(155, 69, 5, 40),
(156, 69, 7, 48),
(157, 70, 4, 10),
(158, 70, 5, 10),
(159, 70, 7, 10),
(160, 71, 4, 10),
(161, 71, 5, 10),
(162, 71, 7, 9),
(163, 72, 4, 10),
(164, 72, 5, 10),
(165, 72, 7, 10),
(166, 73, 4, 10),
(167, 73, 5, 8),
(168, 73, 7, 9),
(169, 74, 4, 10),
(170, 74, 5, 10),
(171, 74, 7, 10),
(172, 75, 4, 10),
(173, 75, 5, 10),
(174, 75, 7, 10),
(175, 76, 4, 10),
(176, 76, 5, 10),
(177, 76, 7, 10),
(178, 77, 4, 10),
(179, 77, 5, 10),
(180, 77, 7, 10),
(181, 78, 4, 10),
(182, 78, 5, 10),
(183, 78, 7, 10),
(184, 79, 4, 10),
(185, 79, 5, 10),
(186, 79, 7, 10),
(187, 80, 4, 10),
(188, 80, 5, 10),
(189, 80, 7, 10),
(190, 81, 4, 10),
(191, 81, 5, 10),
(192, 81, 7, 10),
(193, 82, 4, 10),
(194, 82, 5, 10),
(195, 82, 7, 10),
(196, 83, 4, 100),
(197, 83, 5, 100),
(198, 83, 7, 78),
(199, 84, 4, 52),
(200, 84, 5, 100),
(201, 84, 7, 88),
(202, 85, 4, 10),
(203, 85, 5, 10),
(204, 85, 7, 10),
(205, 86, 4, 10),
(206, 86, 5, 10),
(207, 86, 7, 10),
(208, 87, 4, 10),
(209, 87, 5, 10),
(210, 87, 7, 8),
(211, 44, 9, 10),
(212, 45, 9, 1),
(213, 46, 9, 10),
(214, 47, 9, 25),
(215, 48, 9, 15),
(216, 50, 9, 10),
(217, 51, 9, 3),
(218, 52, 9, 5);

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
(22, 6, 'Activity', 40.00),
(23, 6, 'Exam', 40.00),
(24, 6, 'Quizzes', 10.00),
(25, 6, 'Attendance', 10.00),
(30, 5, 'Activity', 40.00),
(31, 5, 'Exam', 40.00),
(32, 5, 'Quizzes', 10.00),
(33, 5, 'Attendance', 10.00),
(34, 4, 'Activity', 40.00),
(35, 4, 'Exam', 40.00),
(36, 4, 'Quizzes', 10.00),
(37, 4, 'Attendance', 10.00),
(40, 8, 'Activity', 40.00),
(41, 8, 'Exam', 40.00),
(42, 8, 'Quizzes', 10.00),
(43, 8, 'Attendance', 10.00),
(44, 9, 'Activity', 40.00),
(45, 9, 'Exam', 40.00),
(46, 9, 'Quizzes', 10.00),
(47, 9, 'Attendance', 10.00),
(48, 10, 'Activity', 40.00),
(49, 10, 'Exam', 40.00),
(50, 10, 'Quizzes', 10.00),
(51, 10, 'Attendance', 10.00),
(52, 11, 'Activity', 40.00),
(53, 11, 'Exam', 40.00),
(54, 11, 'Quizzes', 10.00),
(55, 11, 'Attendance', 10.00);

-- --------------------------------------------------------

--
-- Table structure for table `grade_items`
--

CREATE TABLE `grade_items` (
  `id` int(11) NOT NULL,
  `component_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `total_points` int(7) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grade_items`
--

INSERT INTO `grade_items` (`id`, `component_id`, `title`, `total_points`) VALUES
(36, 44, 'Activity 1', 10),
(37, 45, 'Exam 1', 10),
(38, 46, 'Quizzes 1', 10),
(40, 34, 'Activity 1', 10),
(41, 35, 'Midterm', 10),
(42, 36, 'Quizzes 1', 10),
(43, 37, 'Attendance 1', 10),
(44, 30, 'Activity 1', 10),
(45, 30, 'Activity 2', 10),
(46, 30, 'Activity 3', 10),
(47, 31, 'Midterm Exam', 50),
(48, 31, 'Final Exam', 50),
(50, 32, 'Quizzes 1', 10),
(51, 32, 'Quizzes 2', 10),
(52, 33, 'Attendance 1', 10),
(53, 48, 'Activity 1', 10),
(54, 48, 'Activity 2', 10),
(55, 48, 'Activity 3', 10),
(56, 48, 'Activity 4', 10),
(57, 49, 'Midterm Exam', 50),
(58, 49, 'Final Exam', 100),
(59, 50, 'Quizzes 1', 10),
(60, 50, 'Quizzes 2', 10),
(61, 50, 'Quizzes 3', 10),
(62, 22, 'Activity 1', 10),
(63, 22, 'Activity 2', 10),
(64, 22, 'Activity 3', 10),
(65, 22, 'Activity 4', 10),
(66, 22, 'Activity 5', 10),
(67, 23, 'Pretest', 10),
(68, 23, 'Midterm', 50),
(69, 23, 'Final', 50),
(70, 24, 'Quizzes 1', 10),
(71, 24, 'Quizzes 2', 10),
(72, 24, 'Quizzes 3', 10),
(73, 24, 'Quizzes 4', 10),
(74, 34, 'Activity 2', 10),
(75, 35, 'Final', 10),
(76, 36, 'Quizzes 2', 10),
(77, 36, 'Quizzes 3', 10),
(78, 34, 'Activity 3', 10),
(79, 34, 'Activity 4', 10),
(80, 52, 'Activity 1', 10),
(81, 52, 'Activity 2', 10),
(82, 52, 'Activity 3', 10),
(83, 53, 'Midterm', 100),
(84, 53, 'Final', 100),
(85, 54, 'Quizzes 1', 10),
(86, 54, 'Quizzes 2', 10),
(87, 55, 'Attendance 1', 10);

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
(4, 6, 11, 20, 'locked', '2025-12-09 00:00:00', '2025-12-10 09:45:53'),
(5, 5, 11, 19, 'submitted', '2025-12-11 00:00:00', '2025-12-10 15:20:56'),
(6, 1, 11, 15, 'draft', '2025-12-11 00:00:00', NULL),
(8, 11, 11, 17, 'draft', '2025-12-11 00:00:00', NULL),
(9, 3, 11, 1, 'locked', '2025-12-08 00:00:00', NULL),
(10, 4, 11, 18, 'submitted', '2025-12-11 00:00:00', '2025-12-10 09:30:50'),
(11, 2, 11, 16, 'draft', '2025-12-11 00:00:00', NULL);

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
(3, 1, 'document_request', 'Carlo Guzman Baldemor is requesting for Certificate of Grades', 1, '2025-12-06 22:12:00', '2025-12-10 17:22:16'),
(4, 1, 'grading_sheet_edit_request', 'Joshua Angel Mandia Naling is requesting to edit access to a grading sheet for BSIT 2B', 1, '2025-12-10 02:13:44', '2025-12-10 17:22:16'),
(5, 2, 'edit_request_decision', 'The admin has a decision to your edit request for grading sheet #9. Status: Approved.', 0, '2025-12-10 02:38:33', NULL),
(6, 2, 'edit_request_decision', 'The admin has a decision to your edit request for grading sheet #9. Status: Approved.', 0, '2025-12-10 02:45:34', NULL),
(7, 2, 'edit_request_decision', 'The admin has a decision to your edit request for grading sheet #9. Status: Approved.', 0, '2025-12-10 02:56:09', NULL);

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
(1, 2, 'PROF-0001', 'jmnaling@paterostechnologicalcollege.edu.ph', 'Joshua Angel', 'Mandia', 'Naling', 7, 'WTH 4PM-6PM', 1, '2025-11-02 15:05:53', '2025-12-10 00:41:44'),
(8, NULL, 'PROF-0002', 'storre@paterostechnologicalcollege.edu.ph', 'Shider Rey', 'Dela Cruz', 'Torre', NULL, 'Sat 3:00PM-5:00PM', 1, '2025-11-02 15:22:08', '2025-11-17 14:51:31'),
(9, NULL, 'PROF-0003', 'rnacario@paterostechnologicalcollege.edu.ph', 'Ryan Cesar', 'Legaspi', 'Nacario', 2, 'Tue 1:00PM-4:00PM', 1, '2025-11-02 15:22:08', '2025-11-17 14:49:15'),
(10, NULL, 'PROF-0004', 'mknazareno@paterostechnologicalcollege.edu.ph', 'Mark Kenneth', 'Borja', 'Nazareno', 3, 'TH 4:00PM-7:00PM', 1, '2025-11-02 15:22:08', '2025-11-17 14:49:48'),
(14, NULL, 'PROF-0005', 'maria.santos@paterostechnologicalcollege.edu.ph', 'Maria Elena', 'Cruz', 'Santos', NULL, 'Mon 8:00AM-11:00AM', 0, '2025-11-02 16:00:45', '2025-11-17 14:50:45'),
(15, 8, 'PROF-0006', 'flamit@paterostechnologicalcollege.edu.ph', 'Flor', '', 'Lamit', 5, 'Mon 9:00 AM - 11:00AM', 1, '2025-11-17 21:48:11', '2025-12-10 09:15:31'),
(16, 6, 'PROF-0007', 'mgnocon@paterostechnologicalcollege.edu.ph', 'Mary Grace', '', 'Nocon', 6, 'Fri 4:00 PM - 9:00PM', 1, '2025-11-17 21:48:57', '2025-12-10 09:15:31'),
(17, NULL, 'PROF-0008', 'jabarracozo@paterostechnologicalcollege.edu.ph', 'Jissalyn', '', 'Abarracozo', 15, 'Mon 4:00 PM - 9:00PM', 1, '2025-11-17 21:49:52', '2025-12-10 00:39:08'),
(18, 7, 'PROF-0009', 'jramos@paterostechnologicalcollege.edu.ph', 'Jocelyn', '', 'Ramos', 8, 'Wed 7:00AM - 11:00AM', 1, '2025-11-17 21:50:25', '2025-12-10 09:15:31'),
(19, 5, 'PROF-0010', 'clamera@paterostechnologicalcollege.edu.ph', 'Cornelio', '', 'Lamera', 9, 'Sat 4:00PM - 9:00PM', 1, '2025-11-17 21:50:56', '2025-12-10 09:15:31'),
(20, 4, 'PROF-0011', 'jaguilar@paterostechnologicalcollege.edu.ph', 'Jasper', '', 'Aguilar', 10, 'Sat 10:00AM - 3:00PM', 1, '2025-11-17 21:51:33', '2025-12-10 09:15:31'),
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
(4, 11, 7, '2025-11-18 23:14:23'),
(6, 11, 9, '2025-12-10 12:42:59');

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
(3, 11, 7, 1, 2, '2025-11-18 13:05:46'),
(4, 11, 8, 18, 2, '2025-11-18 13:05:46'),
(5, 11, 9, 19, 2, '2025-11-18 13:05:46'),
(6, 11, 10, 20, 2, '2025-11-18 13:05:46'),
(7, 11, 11, 21, 6, '2025-11-18 13:10:54'),
(8, 11, 12, NULL, 6, '2025-11-18 13:10:54'),
(9, 11, 13, 22, 6, '2025-11-18 13:10:54'),
(10, 11, 14, NULL, 6, '2025-11-18 13:10:54'),
(11, 11, 15, 17, 6, '2025-11-18 13:10:54'),
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
(7, 3, '25BSIT-1545', 'cgbaldemor@paterostechnologicalcollege.edu.ph', 'Carlo', 'Guzman', 'Baldemor', '2', 'BSIT 2B', 'Regular'),
(9, 9, '2022-8196', 'bralbor@paterostechnologicalcollege.edu.ph', 'Bethel', 'Rodriguez', 'Albor', '2', 'BSIT 2B', 'Irregular');

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
(2, '1', '1st Semester 2025-2026', '2025-2026', '2026-01-10', '2026-05-15', 1, '2025-11-02 15:47:11', '2025-12-10 00:27:03'),
(4, '1', '1st Semester 2021-2022', '2021-2022', '2025-11-02', '2025-11-03', 0, '2025-11-02 16:36:38', '2025-11-08 22:08:10'),
(5, '1', '1st Semester 2024-2025', '2024-2025', '2024-08-04', '2024-12-08', 0, '2025-11-02 16:46:07', '2025-11-08 22:08:10'),
(6, '2', '2nd Semester 2025-2026', '2025-2026', '2026-01-01', '2026-05-31', 0, '2025-11-17 20:33:17', '2025-12-10 00:27:07');

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
(3, 'cgbaldemor@paterostechnologicalcollege.edu.ph', '$2y$10$moBHjGvIBTtCSJ.PSuJQWOlWKjY4N44E15psZiyVH64ZxUmnPqshy', NULL, 'student', 'Carlo', 'Baldemor', 'ACTIVE', '2025-10-27 16:29:29'),
(4, 'jaguilar@paterostechnologicalcollege.edu.ph', '$2y$10$6R.19BNV1lp6cshfXQwkZ.V1T3jrJKYnTLveBClN/i5OK.s6z8S/q', NULL, 'professor', 'Jasper', 'Aguilar', 'ACTIVE', '2025-12-10 01:15:31'),
(5, 'clamera@paterostechnologicalcollege.edu.ph', '$2y$10$3JLvlS8ZadywLTqZtl/wJOPWADV44fcVCE9.w00ohF.0LlxJpsjgu', NULL, 'professor', 'Cornelio', 'Lamera', 'ACTIVE', '2025-12-10 01:15:31'),
(6, 'mgnocon@paterostechnologicalcollege.edu.ph', '$2y$10$2gH.kf7QliICZIjLGGGS/uOBAcbbcB4En1tdY4bz6u6hYW3tXIwTW', NULL, 'professor', 'Mary Grace', 'Nocon', 'ACTIVE', '2025-12-10 01:15:31'),
(7, 'jramos@paterostechnologicalcollege.edu.ph', '$2y$10$qez9L/a2w/580TbJTbRrSup27YQE0.qoS1AAG/wSLU5V47fQT4FjO', NULL, 'professor', 'Jocelyn', 'Ramos', 'ACTIVE', '2025-12-10 01:15:31'),
(8, 'flamit@paterostechnologicalcollege.edu.ph', '$2y$10$q3kR9YOSKSXPDxW/wJxRV.NYVWV8SuM9kT0VnK8Es.r5j5MLBr6rK', NULL, 'professor', 'Flor', 'Lamit', 'ACTIVE', '2025-12-10 01:15:31'),
(9, 'bralbor@paterostechnologicalcollege.edu.ph', NULL, '436852', 'student', 'Bethel', 'Albor', 'INACTIVE', '2025-12-10 04:46:41');

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
-- Indexes for table `default_grade_components`
--
ALTER TABLE `default_grade_components`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=140;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `default_grade_components`
--
ALTER TABLE `default_grade_components`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `document_requests`
--
ALTER TABLE `document_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `edit_requests`
--
ALTER TABLE `edit_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=219;

--
-- AUTO_INCREMENT for table `grade_components`
--
ALTER TABLE `grade_components`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `grade_items`
--
ALTER TABLE `grade_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `grading_sheets`
--
ALTER TABLE `grading_sheets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `section_subjects`
--
ALTER TABLE `section_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
