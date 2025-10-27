-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 04, 2025 at 03:07 AM
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
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `raw_grade` decimal(5,2) NOT NULL,
  `grade` decimal(4,2) NOT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`id`, `student_id`, `subject_id`, `teacher_id`, `raw_grade`, `grade`, `comments`, `created_at`, `updated_at`) VALUES
(6, 3, 3, NULL, 100.00, 1.00, 'passed', '2025-05-31 13:58:53', '2025-05-31 13:58:53'),
(7, 4, 3, NULL, 100.00, 1.00, 'passed', '2025-05-31 14:02:54', '2025-05-31 14:02:54'),
(8, 3, 5, NULL, 95.00, 1.25, 'passed', '2025-06-03 03:41:15', '2025-06-03 03:41:15'),
(10, 3, 6, NULL, 98.00, 1.00, 'passed', '2025-06-03 03:42:03', '2025-06-03 03:42:03'),
(12, 3, 4, NULL, 96.00, 1.25, 'passed', '2025-06-03 03:51:34', '2025-06-03 03:51:34'),
(13, 3, 7, NULL, 100.00, 1.00, 'passed', '2025-06-03 03:52:05', '2025-06-03 03:52:05'),
(14, 4, 5, NULL, 100.00, 1.00, 'passed', '2025-06-03 07:01:31', '2025-06-03 07:01:31'),
(15, 4, 6, NULL, 95.00, 1.25, 'passed', '2025-06-03 07:01:49', '2025-06-03 07:01:49'),
(16, 4, 4, NULL, 96.00, 1.25, 'passed', '2025-06-03 07:02:09', '2025-06-03 07:02:09'),
(17, 4, 7, NULL, 100.00, 1.00, 'passed', '2025-06-03 07:02:25', '2025-06-03 07:02:25'),
(18, 5, 3, NULL, 100.00, 1.00, 'promoted', '2025-06-03 07:41:34', '2025-06-03 07:41:34'),
(19, 5, 7, NULL, 100.00, 1.00, 'promoted', '2025-06-03 07:42:35', '2025-06-03 07:42:35'),
(20, 5, 5, NULL, 95.00, 1.25, 'promoted', '2025-06-03 07:43:51', '2025-06-03 07:43:51'),
(21, 5, 4, NULL, 100.00, 1.00, 'promoted', '2025-06-03 07:44:08', '2025-06-03 07:44:08'),
(22, 5, 6, NULL, 95.00, 1.25, 'promoted', '2025-06-03 07:44:31', '2025-06-03 07:44:31');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `student_number`, `created_at`, `updated_at`) VALUES
(3, 5, '123456', '2025-05-31 12:36:10', '2025-05-31 12:36:10'),
(4, 7, '1234567', '2025-05-31 14:02:39', '2025-05-31 14:02:39'),
(5, 11, '12345678', '2025-06-03 07:40:06', '2025-06-03 07:40:06');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `code`, `name`, `description`, `created_at`, `updated_at`) VALUES
(3, 'webprog4', 'webprogramming', NULL, '2025-05-31 13:51:33', '2025-05-31 13:51:33'),
(4, 'oop4', 'object oriented programming', NULL, '2025-06-03 03:39:09', '2025-06-03 03:39:09'),
(5, 'op4', 'autodesk - office productivity', NULL, '2025-06-03 03:39:42', '2025-06-03 03:39:42'),
(6, 'NETDM4', 'network design and management', NULL, '2025-06-03 03:40:08', '2025-06-03 03:40:08'),
(7, 'OJT', 'ON-THE-JOB TRAINING', NULL, '2025-06-03 03:40:24', '2025-06-03 03:40:24');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `teacher_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','student') NOT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `student_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `email`, `created_at`, `updated_at`, `last_login`, `is_active`, `student_id`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'admin@example.com', '2025-05-31 11:58:55', '2025-05-31 11:58:55', NULL, 1, NULL),
(4, 'dahyunie', '$2y$10$B/kDT.JDxfii5GzJyO.MWeRrp3JhuG.Ds1urFZLvrWep9/4ZjOabG', 'admin', 'dahyun1@gmail.com', '2025-05-31 12:20:51', '2025-05-31 14:14:58', '2025-05-31 14:14:58', 1, NULL),
(5, 'jerick Tumamak', '$2y$10$AxkBt.MDvDPxuJST6jfwa.UjIUeP99a/9YGecKIO/9a22xdQ4Za9y', 'student', 'jericktumamak@gmail.com', '2025-05-31 12:36:10', '2025-05-31 12:36:10', NULL, 1, NULL),
(6, 'pointbreak', '$2y$10$uNHXA3h/udb3uPD8l2GVLugDhBVEMk1I75lXDtrM4kjj9/owH83oa', 'admin', 'pointbreak@gmail.com', '2025-05-31 13:17:12', '2025-05-31 13:17:31', '2025-05-31 13:17:31', 1, NULL),
(7, 'phillip sanpascual', '$2y$10$tQ54DgInsTnyhE82XevYJ.wElq0RPOJm7Y1hJHbU5LqQTqoTc5.0i', 'student', 'phillip@gmail.com', '2025-05-31 14:02:39', '2025-05-31 14:02:39', NULL, 1, NULL),
(8, 'avigilon1', '$2y$10$qb6rM8HVrxf7vJEoRNgBqOf3/JDut68BxWQMeLemfgKTkJjH9JXam', 'admin', 'avigilon3@gmail.com', '2025-06-03 02:56:42', '2025-06-03 08:41:40', '2025-06-03 08:41:40', 1, NULL),
(9, 'phillip', '$2y$10$1v5xB1YkVx.FmKTlM6Mj4O3fP6rVz/IrS.IF4oYJ823snFBD/ZOg.', 'student', 'sanpascual@gmail.com', '2025-06-03 07:08:34', '2025-06-03 07:08:57', '2025-06-03 07:08:57', 1, 'ccs20220079'),
(11, 'freddiemercury', '$2y$10$A14eBtMqlEqybEAzSpmTQuHhyX19WLJm7GUmpkvEHwQ9Y1VmrFKS.', 'student', 'freddie@gmail.com', '2025-06-03 07:30:01', '2025-06-03 08:37:45', '2025-06-03 08:37:45', 1, '123456789');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_subject` (`student_id`,`subject_id`),
  ADD KEY `idx_grades_student` (`student_id`),
  ADD KEY `idx_grades_subject` (`subject_id`),
  ADD KEY `fk_teacher` (`teacher_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `student_number` (`student_number`),
  ADD KEY `idx_student_number` (`student_number`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_subject_code` (`code`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`teacher_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `teacher_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `fk_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`),
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- added by bethel for admin portal

CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('mis','registrar','staff') NOT NULL DEFAULT 'staff',
  status ENUM('pending_otp','active','disabled') NOT NULL DEFAULT 'pending_otp',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admin_otps (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  email VARCHAR(190) NOT NULL,
  code VARCHAR(6) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(admin_id),
  CONSTRAINT fk_admin_otps_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS activity_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  action VARCHAR(100) NOT NULL,
  meta JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(admin_id),
  CONSTRAINT fk_logs_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notifications (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  type ENUM('submission','edit_request') NOT NULL,
  actor VARCHAR(190) NULL,
  payload JSON NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
