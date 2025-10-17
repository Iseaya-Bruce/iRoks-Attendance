-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 17, 2025 at 03:08 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `iroks`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `fullname`, `phone`, `password`) VALUES
(1, 'Admin', '1234567890', '$2y$10$jk1zLfY1WrKhNwghFKbuLuyerSLKygxBa/f7eCQx912q3KJzHNtYy');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `clockin_time` datetime DEFAULT NULL,
  `clockout_time` datetime DEFAULT NULL,
  `overtime_hours` decimal(5,2) DEFAULT 0.00,
  `work_date` date NOT NULL DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `employee_id`, `comment`, `clockin_time`, `clockout_time`, `overtime_hours`, `work_date`) VALUES
(1, 1, NULL, '2025-09-05 11:29:33', '2025-09-05 16:00:00', 0.00, '2025-09-05'),
(2, 1, NULL, '2025-09-06 09:51:37', '2025-09-06 18:09:35', 0.00, '2025-09-06'),
(3, 1, NULL, '2025-09-08 09:35:17', '2025-09-08 16:08:07', 0.00, '2025-09-08'),
(4, 4, NULL, '2025-09-08 15:46:59', '2025-09-08 23:29:00', 0.00, '2025-09-08'),
(5, 1, NULL, '2025-09-10 09:24:34', '2025-09-10 16:00:00', 0.00, '2025-09-10'),
(6, 4, NULL, '2025-09-10 15:30:10', '2025-09-10 23:30:00', 0.00, '2025-09-10'),
(7, 4, NULL, '2025-09-11 08:00:02', '2025-09-11 23:30:00', 0.00, '2025-09-11'),
(8, 1, NULL, '2025-09-11 08:28:19', '2025-09-11 16:00:00', 0.00, '2025-09-11'),
(9, 1, NULL, '2025-09-12 08:27:30', NULL, 0.00, '2025-09-12'),
(10, 1, 'loes bere tide yere', NULL, '2025-09-13 20:13:13', 0.00, '2025-09-13'),
(11, 1, NULL, '2025-09-17 08:04:45', '2025-09-17 21:00:34', 5.01, '2025-09-17'),
(12, 1, NULL, '2025-09-18 08:10:52', NULL, 0.00, '2025-09-18'),
(13, 1, NULL, '2025-09-29 12:17:55', NULL, 0.00, '2025-09-29'),
(14, 1, NULL, '2025-09-30 09:23:17', NULL, 0.00, '2025-09-30'),
(15, 1, NULL, '2025-10-01 09:22:58', '2025-10-01 17:22:00', 0.00, '2025-10-01'),
(16, 4, NULL, '2025-10-01 07:56:40', '2025-10-01 23:00:00', 0.00, '2025-10-01'),
(18, 1, NULL, '2025-10-06 08:00:00', '2025-10-06 17:00:00', 0.60, '2025-10-06'),
(19, 1, NULL, '2025-10-08 08:08:59', '2025-10-08 16:08:07', 0.00, '2025-10-08'),
(20, 1, 'I forgor to clock in', '2025-10-14 08:39:16', '2025-10-14 16:00:00', 0.00, '2025-10-14');

-- --------------------------------------------------------

--
-- Table structure for table `chat`
--

CREATE TABLE `chat` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT NULL,
  `hourly_pay` decimal(10,2) DEFAULT 0.00,
  `monthly_pay` decimal(10,2) DEFAULT 0.00,
  `expected_clockin` time DEFAULT NULL,
  `expected_clockout` time DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT 'default.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category` varchar(50) DEFAULT NULL,
  `place_of_work` enum('office','remote') DEFAULT 'office',
  `shift` enum('shift_1','shift_2') DEFAULT 'shift_1',
  `position` varchar(100) DEFAULT NULL,
  `overtime_applicable` tinyint(1) DEFAULT 0,
  `status` enum('pending','active','disabled') NOT NULL DEFAULT 'pending',
  `late_fee_applicable` tinyint(1) DEFAULT 1,
  `paid_day` tinyint(3) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `fullname`, `phone`, `password`, `role`, `hourly_pay`, `monthly_pay`, `expected_clockin`, `expected_clockout`, `profile_pic`, `created_at`, `category`, `place_of_work`, `shift`, `position`, `overtime_applicable`, `status`, `late_fee_applicable`, `paid_day`) VALUES
(1, 'Iseaya Bruce', '8858034', '$2y$10$Ru.ELXOJG6If4lyAdJ8Pr.DuKNtP0VCGuQ22MjuwyPdDaUEg.eGES', 'software engineer', 60.00, 12000.00, '08:00:00', '16:00:00', 'profile_1_1760441920.jpg', '2025-09-04 13:55:37', 'software engineer', 'office', 'shift_1', NULL, 1, 'active', 1, 17),
(4, 'Terencia Djasmo', '8456333', '$2y$10$885XdLdLkFIJz3CWBvAZ2.KJL1q87f51mxxyHyOmD9VdlaZUq1vPO', 'verkoop medewerker', 52.00, 12500.00, '15:30:00', '23:30:00', 'profile_4.jpg', '2025-09-08 13:19:28', 'nuts', 'office', 'shift_2', NULL, 0, 'active', 1, 16),
(31, 'Max', '8456333', '$2y$10$HaxlQyct0FZb8TRW1.OAO.0UIVKqwe04RPio6GH/zIJFc5v0f3ux6', 'verkoop medewerker', 0.00, 0.00, NULL, NULL, 'default.png', '2025-10-04 16:47:29', 'nuts', 'office', 'shift_1', NULL, 0, 'pending', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

CREATE TABLE `holidays` (
  `id` int(11) NOT NULL,
  `holiday_name` varchar(100) DEFAULT NULL,
  `holiday_date` date NOT NULL,
  `description` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holidays`
--

INSERT INTO `holidays` (`id`, `holiday_name`, `holiday_date`, `description`) VALUES
(7, 'New Year\'s Day', '2025-01-01', 'New Year\'s Day'),
(8, 'Three Kings Day', '2025-01-06', 'Three Kings Day'),
(9, 'World Religion Day', '2025-01-19', 'World Religion Day'),
(10, 'Chinese New Year', '2025-01-29', 'Chinese New Year'),
(11, 'Day of the Revolution', '2025-02-25', 'Day of the Revolution'),
(12, 'Good Friday', '2025-04-18', 'Good Friday'),
(13, 'Easter Sunday', '2025-04-20', 'Easter Sunday'),
(14, 'Labour Day', '2025-05-01', 'Labour Day'),
(15, 'Ascension Day', '2025-05-29', 'Ascension Day'),
(16, 'Indian Arrival Day', '2025-06-05', 'Indian Arrival Day'),
(17, 'Keti Koti', '2025-07-01', 'Keti Koti'),
(18, 'Javanese Arrival Day', '2025-08-08', 'Javanese Arrival Day'),
(19, 'Indigenous People\'s Day', '2025-08-09', 'Indigenous People\'s Day'),
(20, 'Day of the Maroons', '2025-10-10', 'Day of the Maroons'),
(21, 'Chinese Arrival day', '2025-10-20', 'Chinese Arrival day'),
(22, 'Independence Day', '2025-11-25', 'Independence Day'),
(23, 'Christmas Day', '2025-12-25', 'Christmas Day'),
(24, 'Boxing Day', '2025-12-26', 'Boxing Day'),
(25, 'test', '2025-10-08', 'test voor dubbel');

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','denied') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`id`, `employee_id`, `start_date`, `end_date`, `reason`, `status`, `created_at`) VALUES
(1, 1, '2025-09-15', '2025-09-16', 'ik wil naar tapC met mijn familie uit NL', 'approved', '2025-09-11 12:21:37'),
(2, 1, '2025-09-17', '2025-09-19', 'ik wil naar tapc', 'approved', '2025-09-11 17:49:51'),
(3, 1, '2025-09-17', '2025-09-19', 'ik wil naar tapc', 'approved', '2025-09-11 17:49:54'),
(4, 1, '2025-09-17', '2025-09-19', 'ik wil naar tapc', 'denied', '2025-09-11 17:49:56'),
(5, 1, '2025-09-17', '2025-09-19', 'ik wil naar tapc', 'denied', '2025-09-11 17:49:57'),
(6, 1, '2025-09-17', '2025-09-19', 'ik wil naar tapc', 'approved', '2025-09-11 17:50:10');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `message`, `file_path`, `created_at`, `is_read`) VALUES
(3, 1, 0, 'goedemiddag Boss', NULL, '2025-09-05 17:25:02', 1),
(4, 1, 0, 'SYBAU', NULL, '2025-09-05 17:25:13', 1),
(5, 1, 0, 'TU MADRE ERES UN PUTAAA', NULL, '2025-09-06 14:47:37', 1),
(6, 0, 1, 'SYBAU', NULL, '2025-09-06 18:37:54', 1),
(7, 0, 1, '', 'uploads/chat_files/1757183980_Timesheet_Iseaya Bruce (2).pdf', '2025-09-06 18:39:40', 1),
(8, 1, 0, '', 'uploads/chat_files/1757184115_Timesheet_Iseaya Bruce (2).pdf', '2025-09-06 18:41:55', 1),
(9, 0, 1, 'kan je jou timesheet voor me sturen ?', NULL, '2025-09-11 17:06:10', 1),
(10, 0, 4, 'waarom ben je vandaag laat ?', NULL, '2025-09-11 17:06:26', 1),
(11, 1, 0, 'no ye begi', NULL, '2025-09-11 17:16:55', 1),
(12, 1, 0, 'ando ando dja waai boi angai sanii', NULL, '2025-09-11 17:17:20', 1),
(13, 1, 0, '', 'uploads/chat_files/1757612919_Timesheet_Iseaya Bruce (6).pdf', '2025-09-11 17:48:39', 1),
(14, 4, 0, 'goedemiddag Boss ik wachtte op raishri', NULL, '2025-09-11 19:11:28', 1),
(15, 0, 1, 'je timesheet klopt niet mr. Abrahams', NULL, '2025-09-12 11:27:09', 1),
(16, 1, 0, 'ik ga me moeder voor je zeggen', NULL, '2025-09-12 11:28:01', 1),
(17, 1, 0, '', 'uploads/chat_files/1757683369_Snapchat-1031296323.jpg', '2025-09-12 13:22:49', 1),
(18, 0, 1, 'Jokkebrok', NULL, '2025-09-12 14:52:22', 1),
(19, 1, 0, 'Ofa', NULL, '2025-09-13 17:36:02', 1),
(20, 0, 1, 'Ey go', NULL, '2025-09-30 14:34:52', 1),
(21, 1, 0, 'TU MADRE ERES UN PUTAAA', NULL, '2025-09-30 14:35:21', 1),
(22, 1, 0, 'SYBAU', NULL, '2025-09-30 14:35:34', 1),
(23, 1, 0, 'goedemiddag Boss', NULL, '2025-09-30 14:43:34', 1),
(24, 1, 0, 'no ye begi', NULL, '2025-09-30 14:43:40', 1),
(25, 0, 1, 'Wat wil je ????', NULL, '2025-09-30 14:44:20', 1),
(26, 0, 1, 'Stoorkunde', NULL, '2025-09-30 14:44:30', 1),
(27, 0, 1, 'And i didn\'t even cry no not a single tear and im sick of waiting patiently for someone that wont even lie', NULL, '2025-09-30 14:45:33', 1),
(28, 0, 1, 'hi', NULL, '2025-09-30 16:55:42', 1),
(29, 0, 1, 'SYBAU', NULL, '2025-10-01 19:56:38', 1),
(30, 1, 0, 'TU MADRE ERES UN PUTAAA', NULL, '2025-10-01 19:57:02', 1),
(31, 1, 0, 'waarom ben je vandaag laat ?', NULL, '2025-10-06 12:14:35', 1),
(32, 1, 0, '', 'uploads/chat_files/1759753235_Snapchat-1627696080.mp4', '2025-10-06 12:20:35', 1),
(33, 1, 0, 'goedemiddag Boss', NULL, '2025-10-06 14:45:47', 1),
(34, 1, 0, '', 'uploads/chat_files/1759761964_Iseaya Timesheet - Timesheet (2).pdf', '2025-10-06 14:46:04', 1),
(35, 0, 1, 'no ye begi', NULL, '2025-10-06 14:48:16', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `chat`
--
ALTER TABLE `chat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `holiday_date` (`holiday_date`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `chat`
--
ALTER TABLE `chat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `holidays`
--
ALTER TABLE `holidays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `chat`
--
ALTER TABLE `chat`
  ADD CONSTRAINT `chat_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `chat_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
