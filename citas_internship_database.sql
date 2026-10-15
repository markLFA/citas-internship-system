-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 11, 2026 at 01:06 PM
-- Server version: 11.8.9-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u894964735_internship`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`u894964735_internship`@`127.0.0.1` PROCEDURE `RecalculateInternshipTotals` (IN `p_intern_id` INT)   BEGIN
    UPDATE internships
    SET 
        days_present = (
            SELECT COUNT(DISTINCT log_date) 
            FROM time_logs 
            WHERE intern_id = p_intern_id 
              AND time_out IS NOT NULL
        ),
        total_hours = (
            SELECT COALESCE(SUM(total_hours), 0) 
            FROM time_logs 
            WHERE intern_id = p_intern_id 
              AND time_out IS NOT NULL
        )
    WHERE intern_id = p_intern_id;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `coordinator_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `body` text NOT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `coordinator_id`, `title`, `body`, `is_pinned`, `created_at`, `updated_at`) VALUES
(8, 38, 'Narrative Report', 'Submit your narrative reports by Monday.', 1, '2026-05-20 09:55:23', '2026-05-20 09:55:56'),
(9, 23, 'Weekly Report Submission Reminder', 'All interns are reminded to submit their weekly internship reports before the end of the week. Please ensure that your report accurately reflects your activities and hours completed', 0, '2026-09-12 17:36:31', '2026-10-09 18:00:08'),
(10, 23, 'Welcome Student Interns! — First Semester OJT Orientation', 'Welcome to the official Internship Monitoring System! Please ensure that your profiles, assigned companies, and supervisor details are fully updated before submitting your daily time logs. Attendance tracking officially starts today, July 20, 2026.', 0, '2026-07-20 08:00:00', '2026-10-09 17:53:06'),
(12, 23, 'Midterm Internship Monitoring & Supervisor Visits', 'Greetings interns! Starting next week, the OJT Coordinator office will conduct site visits and virtual check-ins with your respective host companies to review your progress and performance evaluation.', 0, '2026-08-14 09:15:00', NULL),
(13, 23, 'Important: DTR Logging Guidelines & Break Hours', 'Please be reminded that standard working logs should accurately reflect your daily schedule (8:00 AM - 5:00 PM). A 1-hour lunch break is automatically deducted from your daily total hours. Do not forget to log out at the end of each shift.', 0, '2026-08-25 10:00:00', NULL),
(14, 23, 'Submission of Required Internship Documents', 'For those who have not yet submitted their signed Acceptance Form and Memorandum of Agreement (MOA), please upload them to the Intern Documents section no later than September 20, 2026.', 0, '2026-09-02 14:20:00', '2026-10-09 18:00:40'),
(15, 23, 'Upcoming Final Evaluation & Completion Requirements Preview', 'As we approach the final stretch of your 500-hour requirement, please start gathering your signed Certificate of Completion (COC) and final evaluation forms from your company supervisors.', 0, '2026-09-11 11:00:00', '2026-10-09 17:53:14'),
(16, 23, 'Orientation Day', 'All BSIT fourth year students are required to attend the OJT orientation program.\n\nTime: 9:00 am Wednesday \nRoom: Annex 4\n\nAttendance is a MUST.', 1, '2026-09-14 10:45:06', '2026-10-09 17:53:46');

-- --------------------------------------------------------

--
-- Table structure for table `announcement_comments`
--

CREATE TABLE `announcement_comments` (
  `id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL COMMENT 'NULL = top-level comment, set = reply to that comment id',
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcement_comments`
--

INSERT INTO `announcement_comments` (`id`, `announcement_id`, `parent_id`, `user_id`, `comment`, `created_at`) VALUES
(10, 16, NULL, 32, 'hello', '2026-10-09 18:34:06'),
(11, 16, NULL, 32, 'hdhbfsdighihhih', '2026-10-09 18:37:29'),
(12, 16, NULL, 32, 'ruwhuihirhghigrhihg', '2026-10-09 18:37:34'),
(13, 16, NULL, 32, 'sdbhfibgifrwirj', '2026-10-09 18:38:05'),
(14, 16, NULL, 32, 'skjnjnkn', '2026-10-09 18:38:08'),
(15, 16, NULL, 32, 'hadbjbfkbfek', '2026-10-09 18:46:25'),
(16, 16, NULL, 32, 'bwdkbkbwkbkbak', '2026-10-09 18:46:27'),
(17, 16, NULL, 32, 'WFWUIHIAEHOJAFOJJOADSJOJOUAG]', '2026-10-09 18:47:33'),
(18, 16, NULL, 32, 'FEAIBHIFEBIBFAKIBKBFAEK', '2026-10-09 18:47:35'),
(19, 16, NULL, 32, 'WFWUIHIAEHOJAFOJJOADSJOJO', '2026-10-09 18:47:36');

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `address` varchar(300) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `name`, `address`, `phone`, `email`, `created_at`) VALUES
(1, 'Tecccc Innovations Inc.', '123 Bonifacio St., Catbalogan City, Samar', '+63 55 123 4567', 'hr@techhubinnovations.ph', '2026-04-27 18:27:44'),
(20, 'Delta Company', '123 street, Catbalogan City', '09877777777', '', '2026-05-01 17:35:05'),
(21, 'Innovatech Samar Solutions', 'Del Rosario St., Catbalogan City, Samar', '+63 955 112 3456', 'contact@innovatechsamar.ph', '2026-05-01 17:51:40'),
(22, 'Samar Electric Cooperative (SAMELCO II)', 'Paranas, Samar', '+63 917 123 4567', 'contact@samelco2.ph', '2026-05-13 12:55:44'),
(23, 'Department of Information and Communications Technology (DICT)', 'Arteche Blvd., Catbalogan City, Samar', '+63 918 987 6543', 'region8@dict.gov.ph', '2026-05-17 13:44:29'),
(24, 'Provincial Government of Samar', 'Capitol Bldg., Catbalogan City, Samar', '+63 55 543 8888', 'admin@samar.gov.ph', '2026-05-20 09:20:52'),
(25, 'Catbalogan Water District', 'Pier 2, Catbalogan City, Samar', '+63 55 251 2089', 'info@catbaloganwd.gov.ph', '2026-05-20 09:30:59'),
(26, 'Samar State University - IT Center', 'Arteche Blvd., Catbalogan City, Samar', '+63 55 543 8350', 'itcenter@ssu.edu.ph', '2026-05-23 17:08:49'),
(28, 'City Government of Catbalogan', 'City Hall, San Bartholomew St., Catbalogan City', '+63 55 251 3100', 'info@catbalogan.gov.ph', '2026-09-05 22:11:57'),
(29, 'Department of Trade and Industry (DTI) - Samar', 'Curry Ave., Catbalogan City, Samar', '+63 55 543 2110', 'r08.samar@dti.gov.ph', '2026-09-05 22:14:55'),
(31, 'Department of Science and Technology (DOST) - Samar', 'Brgy. Mercedes, Catbalogan City, Samar', '+63 55 543 9022', 'samar@ro8.dost.gov.ph', '2026-09-06 18:22:31'),
(32, 'Eastern Visayas Regional Medical Center - IT Office', 'Tacloban - Catbalogan Rd., Samar', '+63 55 321 4455', 'it@evrmc.gov.ph', '2026-09-07 01:33:03'),
(33, 'Landbank of the Philippines - Catbalogan Branch', 'Allen Ave., Catbalogan City, Samar', '+63 55 251 2045', 'lbp_catbalogan@landbank.com', '2026-09-11 09:44:30'),
(34, 'Development Bank of the Philippines (DBP)', 'Del Rosario St., Catbalogan City, Samar', '+63 55 251 2231', 'catbalogan@dbp.ph', '2026-09-13 17:11:32'),
(35, 'Philippine National Police - Samar PPO', 'Camp Lukban, Maulong, Catbalogan City', '+63 55 543 7011', 'samar_ppo@pnp.gov.ph', '2026-09-13 17:12:05'),
(36, 'Samar Island Biodiversity Project', 'Jiabong Highway, Samar', '+63 55 543 6099', 'sibp@denr.gov.ph', '2026-09-13 17:13:01'),
(37, 'Bureau of Internal Revenue (BIR) - District 68', 'Arteche Blvd., Catbalogan City, Samar', '+63 55 251 3004', 'rdo68@bir.gov.ph', '2026-09-13 17:13:36'),
(38, 'Civil Service Commission (CSC) Field Office - Samar', 'Catbalogan City, Samar', '+63 55 543 4055', 'ro08.field_samar@csc.gov.ph', '2026-09-13 17:14:28'),
(39, 'Department of Public Works and Highways (DPWH) - Samar', 'Brgy. San Policarpo, Calbayog City, Samar', '+63 55 209 1234', 'dpwh_samar1@dpwh.gov.ph', '2026-09-13 23:18:59'),
(40, 'National Telecommunications Commission (NTC) - Region VIII', 'Subd. Road, Catbalogan City, Samar', '+63 55 543 8901', 'ntc.region8@ntc.gov.ph', '2026-09-13 23:25:58'),
(41, 'Social Security System (SSS) - Catbalogan Branch', 'Del Rosario St., Catbalogan City, Samar', '+63 55 543 1122', 'catbalogan@sss.gov.ph', '2026-09-14 09:58:32'),
(42, 'Department of Environment and Natural Resources (DENR) - Samar', 'Arteche Blvd., Catbalogan City, Samar', '+63 55 543 2345', 'denr.samar@denr.gov.ph', '2026-09-14 11:14:13'),
(43, 'Not yet assigned', 'Enter company address here', NULL, NULL, '2026-10-10 20:05:52'),
(44, 'Not yet assigned', 'Enter company address here', NULL, NULL, '2026-10-10 22:00:03'),
(45, 'Not yet assigned', 'Enter company address here', NULL, NULL, '2026-10-10 23:27:42');

-- --------------------------------------------------------

--
-- Table structure for table `coordinator_weekly_hours_summary`
--

CREATE TABLE `coordinator_weekly_hours_summary` (
  `id` int(11) NOT NULL,
  `coordinator_id` int(11) NOT NULL,
  `week_start` date NOT NULL,
  `week_end` date NOT NULL,
  `total_interns` int(11) NOT NULL DEFAULT 0,
  `total_hours` decimal(10,2) NOT NULL DEFAULT 0.00,
  `batch_avg_hours` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `internships`
--

CREATE TABLE `internships` (
  `id` int(11) NOT NULL,
  `intern_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `supervisor` varchar(100) DEFAULT NULL,
  `supervisor_phone` varchar(30) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','completed','dropped') NOT NULL DEFAULT 'active',
  `school_year` varchar(9) DEFAULT NULL COMMENT 'e.g. 2025-2026  — auto-set on registration',
  `is_profile_reviewed` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 = not yet reviewed, 1 = coordinator has verified and locked',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `total_hours` double NOT NULL,
  `days_present` int(11) NOT NULL,
  `reports_submitted` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `internships`
--

INSERT INTO `internships` (`id`, `intern_id`, `company_id`, `position`, `supervisor`, `supervisor_phone`, `start_date`, `end_date`, `status`, `school_year`, `is_profile_reviewed`, `created_at`, `total_hours`, `days_present`, `reports_submitted`) VALUES
(15, 32, 20, 'intern', 'Mike Santiago', '09877777777', '2026-07-13', '2026-10-08', 'active', '2026-2027', 0, '2026-05-01 17:35:05', 344.79, 43, 10),
(27, 45, 32, 'intern', 'Maria Clara Reyes', '09184445678', '2026-07-13', '2026-10-08', 'active', '2025-2026', 0, '2026-09-07 01:33:03', 368.86, 45, 1),
(28, 49, 33, 'intern', 'Juan Carlos Dizon', '09203339876', '2026-07-13', '2026-10-08', 'active', '2026-2027', 0, '2026-09-11 09:44:30', 365.85, 47, 0),
(29, 50, 34, 'intern', 'Elena Torres', '09192223456', '2026-07-13', '2026-10-08', 'active', '2026-2027', 0, '2026-09-13 17:11:32', 360.46, 45, 0),
(30, 51, 35, 'intern', 'Mark Anthony Cruz', '09171118765', '2026-07-13', '2026-10-08', 'active', '2026-2027', 0, '2026-09-13 17:12:05', 368.79, 45, 0),
(31, 52, 36, 'intern', 'Patricia Gomez', '09289994321', '2026-07-13', '2026-10-08', 'active', '2026-2027', 0, '2026-09-13 17:13:01', 367.21, 45, 10),
(32, 53, 37, 'intern', 'Gabriel Mendoza', '09178886543', '2026-07-13', '2026-10-08', 'active', '2026-2027', 0, '2026-09-13 17:13:36', 366.66, 45, 0),
(33, 54, 38, 'intern', 'Sofia Villanueva', '09197771209', '2026-07-13', '2026-10-08', 'active', '2026-2027', 0, '2026-09-13 17:14:28', 366.89, 45, 0),
(34, 55, 39, 'intern', 'Engr. Manuel Roxas', '09176662345', '2026-07-13', '2026-10-08', 'active', '2026-2027', 0, '2026-09-13 23:18:59', 366.75, 45, 0),
(35, 56, 40, 'intern', 'Clarissa Mercado', '09183337890', '2026-07-13', '2026-10-08', 'active', '2026-2027', 0, '2026-09-13 23:25:58', 367.2, 45, 0),
(36, 57, 41, 'intern', 'Engr. Fernando Poe Jr.', '09174448901', '2026-07-13', '2026-10-08', 'active', '2026-2027', 0, '2026-09-14 09:58:32', 376.15, 47, 0),
(37, 58, 42, 'intern', 'Atty. Rafael Aquino', '09175554321', '2026-07-13', '2026-10-08', 'active', '2026-2027', 0, '2026-09-14 11:14:13', 377.27, 46, 0),
(38, 59, 43, NULL, NULL, '', NULL, NULL, 'active', '2026-2027', 0, '2026-10-10 20:05:52', 0, 0, 0),
(40, 61, 45, NULL, NULL, '', NULL, NULL, 'active', '2026-2027', 0, '2026-10-10 23:27:42', 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `intern_documents`
--

CREATE TABLE `intern_documents` (
  `id` int(11) NOT NULL,
  `intern_id` int(11) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','accepted') NOT NULL DEFAULT 'pending',
  `feedback` text DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL,
  `coordinator_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `intern_documents`
--

INSERT INTO `intern_documents` (`id`, `intern_id`, `document_type`, `file_path`, `file_name`, `notes`, `status`, `feedback`, `submitted_at`, `reviewed_at`, `coordinator_id`) VALUES
(18, 32, 'TOR', 'documents/32/doc_5119559a1540735753ccf0a70dc6b272.docx', 'weekly report 2.docx', NULL, 'rejected', NULL, '2026-09-06 23:45:39', '2026-10-09 18:01:26', 23),
(19, 32, 'Certificate of Completion', 'documents/32/doc_c180ede42e0acb678e552b159ab45060.docx', 'Technopreneurs Assignment.docx', NULL, 'approved', NULL, '2026-09-07 00:46:25', '2026-09-07 00:49:40', 23),
(20, 32, 'Medical Certificate', 'documents/32/doc_8f034027ffbd82193d4dc92171a60b9d.docx', 'attendane.docx', NULL, 'pending', NULL, '2026-09-07 13:47:30', NULL, 23),
(21, 32, 'Endorsement Letter', 'documents/32/doc_959c9813fb26c4536479a5819e66a2f8.docx', 'MPS 2025.docx', NULL, 'approved', NULL, '2026-09-07 13:47:40', '2026-09-07 13:50:25', 23),
(22, 32, 'Waiver / MOA', 'documents/32/doc_bf1557b3cb818a3346073d0175721ebf.docx', 'Pic.docx', NULL, 'approved', NULL, '2026-09-07 13:48:08', '2026-09-07 13:50:22', 23),
(23, 32, 'Final Report', 'documents/32/doc_364e502752c05df58794ee04e4d6bacd.pdf', 'FINAL-EXAMINATION-in-ENTREPRENEURIAL-MIND.pdf', NULL, 'approved', NULL, '2026-09-07 13:48:32', '2026-09-07 13:50:18', 23),
(24, 52, 'TOR', 'documents/52/doc_33a228cf76182c8044aa6a5d5090cef4.docx', 'REport Sample 1.docx', NULL, 'pending', NULL, '2026-09-14 00:16:15', NULL, 23),
(25, 45, 'TOR', 'documents/45/doc_2683f33d5d8f4f20d8e0781f7cffee7f.docx', 'Document.docx', NULL, 'approved', NULL, '2026-10-09 21:08:43', '2026-10-09 21:43:32', 23),
(26, 45, 'Certificate of Completion', 'documents/45/doc_23fab370fb39e89b79b41c5bc1e70ed9.docx', 'Document.docx', NULL, 'approved', NULL, '2026-10-09 21:08:51', '2026-10-09 21:43:28', 23),
(27, 45, 'Endorsement Letter', 'documents/45/doc_4e31984149d08f97e58c475f06a27e09.docx', 'Document.docx', NULL, 'approved', NULL, '2026-10-09 21:09:09', '2026-10-09 21:43:24', 23),
(28, 45, 'Final Report', 'documents/45/doc_5ebf8db671eebf07f7e135263feeddac.docx', 'Document.docx', NULL, 'approved', NULL, '2026-10-09 21:09:18', '2026-10-09 21:43:21', 23),
(29, 45, 'Waiver / MOA', 'documents/45/doc_7e3e833605c28d8d9acd77a252459d05.docx', 'Document.docx', NULL, 'approved', NULL, '2026-10-09 21:09:30', '2026-10-09 21:43:18', 23),
(30, 45, 'Medical Certificate', 'documents/45/doc_0e4fbd1171e73a79ac06e4d74684f42f.docx', 'Document.docx', NULL, 'approved', NULL, '2026-10-09 21:09:45', '2026-10-09 21:43:15', 23);

-- --------------------------------------------------------

--
-- Table structure for table `intern_profiles`
--

CREATE TABLE `intern_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course` varchar(100) DEFAULT NULL,
  `year_level` varchar(30) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `coordinator_id` int(11) NOT NULL,
  `required_hours` smallint(6) NOT NULL DEFAULT 500,
  `joined_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `intern_profiles`
--

INSERT INTO `intern_profiles` (`id`, `user_id`, `course`, `year_level`, `phone`, `coordinator_id`, `required_hours`, `joined_date`) VALUES
(17, 32, 'BSIT', '4th Year', '0978785435', 23, 500, NULL),
(29, 45, 'BSIT', NULL, '0978785435', 23, 500, NULL),
(30, 49, 'BSIT', '4th Year', '09924010938', 23, 500, NULL),
(31, 50, 'BSIT', '4th Year', '0987754578798', 23, 500, NULL),
(32, 51, 'BSIT', '4th Year', '098758757789', 23, 500, NULL),
(33, 52, 'BSIT', '4th Year', '099745789809', 23, 500, NULL),
(34, 53, 'BSIT', '4th Year', '098787554324', 23, 500, NULL),
(35, 54, 'BSIT', '4th Year', '0987898787', 23, 500, NULL),
(36, 55, 'BSIT', '4th Year', '0987345539', 23, 500, NULL),
(37, 56, 'BSIT', '4th Year', '09234523458', 23, 500, NULL),
(38, 57, 'BSIT', '4th Year', '09562644115', 23, 500, NULL),
(39, 58, 'BSIT', '4th Year', '+639094772431', 23, 500, NULL),
(40, 59, 'BSIT', '4th Year', '098765454', 23, 500, NULL),
(42, 61, 'BSIT', '4th Year', '09452764646', 23, 500, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `time_logs`
--

CREATE TABLE `time_logs` (
  `id` int(11) NOT NULL,
  `intern_id` int(11) NOT NULL,
  `coordinator_id` int(12) DEFAULT NULL,
  `log_date` date NOT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `total_hours` decimal(5,2) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `time_logs`
--

INSERT INTO `time_logs` (`id`, `intern_id`, `coordinator_id`, `log_date`, `time_in`, `time_out`, `total_hours`, `notes`) VALUES
(57, 32, 23, '2026-07-20', '2026-07-20 08:00:00', '2026-07-20 17:00:00', 8.00, ''),
(58, 32, 23, '2026-07-21', '2026-07-21 08:00:00', '2026-07-21 17:00:00', 8.00, ''),
(59, 32, 23, '2026-07-22', '2026-07-22 08:00:00', '2026-07-22 17:00:00', 8.00, ''),
(60, 32, 23, '2026-07-23', '2026-07-23 08:00:00', '2026-07-23 17:00:00', 8.00, ''),
(61, 32, 23, '2026-07-24', '2026-07-24 08:00:00', '2026-07-24 17:00:00', 8.00, ''),
(62, 32, 23, '2026-07-27', '2026-07-27 08:00:00', '2026-07-27 17:00:00', 8.00, ''),
(63, 32, 23, '2026-07-28', '2026-07-28 08:00:00', '2026-07-28 17:00:00', 8.00, ''),
(64, 32, 23, '2026-07-29', '2026-07-29 08:00:00', '2026-07-29 17:00:00', 8.00, ''),
(65, 32, 23, '2026-07-30', '2026-07-30 08:00:00', '2026-07-30 17:00:00', 8.00, ''),
(66, 32, 23, '2026-07-31', '2026-07-31 08:00:00', '2026-07-31 17:00:00', 8.00, ''),
(67, 32, 23, '2026-08-03', '2026-08-03 08:00:00', '2026-08-03 17:00:00', 8.00, ''),
(68, 32, 23, '2026-08-04', '2026-08-04 08:00:00', '2026-08-04 17:00:00', 8.00, ''),
(69, 32, 23, '2026-08-05', '2026-08-05 08:00:00', '2026-08-05 17:00:00', 8.00, ''),
(70, 32, 23, '2026-08-06', '2026-08-06 08:00:00', '2026-08-06 17:00:00', 8.00, ''),
(71, 32, 23, '2026-08-07', '2026-08-07 08:00:00', '2026-08-07 17:00:00', 8.00, ''),
(72, 32, 23, '2026-08-10', '2026-08-10 08:00:00', '2026-08-10 17:00:00', 8.00, ''),
(73, 32, 23, '2026-08-11', '2026-08-11 08:00:00', '2026-08-11 17:00:00', 8.00, ''),
(74, 32, 23, '2026-08-12', '2026-08-12 08:00:00', '2026-08-12 17:00:00', 8.00, ''),
(75, 32, 23, '2026-08-13', '2026-08-13 08:00:00', '2026-08-13 17:00:00', 8.00, ''),
(76, 32, 23, '2026-08-14', '2026-08-14 08:00:00', '2026-08-14 17:00:00', 8.00, ''),
(77, 32, 23, '2026-08-17', '2026-08-17 08:00:00', '2026-08-17 17:00:00', 8.00, ''),
(78, 32, 23, '2026-08-18', '2026-08-18 08:00:00', '2026-08-18 17:00:00', 8.00, ''),
(79, 32, 23, '2026-08-19', '2026-08-19 08:00:00', '2026-08-19 17:00:00', 8.00, ''),
(80, 32, 23, '2026-08-20', '2026-08-20 08:00:00', '2026-08-20 17:00:00', 8.00, ''),
(81, 32, 23, '2026-08-21', '2026-08-21 08:00:00', '2026-08-21 17:00:00', 8.00, ''),
(82, 32, 23, '2026-08-24', '2026-08-24 08:00:00', '2026-08-24 17:00:00', 8.00, ''),
(83, 32, 23, '2026-08-25', '2026-08-25 08:00:00', '2026-08-25 17:00:00', 8.00, ''),
(84, 32, 23, '2026-08-26', '2026-08-26 08:00:00', '2026-08-26 17:00:00', 8.00, ''),
(85, 32, 23, '2026-08-27', '2026-08-27 08:00:00', '2026-08-27 17:00:00', 8.00, ''),
(86, 32, 23, '2026-08-28', '2026-08-28 08:00:00', '2026-08-28 17:00:00', 8.00, ''),
(87, 32, 23, '2026-09-03', '2026-09-03 08:00:00', '2026-09-03 17:00:00', 8.00, ''),
(88, 32, 23, '2026-09-04', '2026-09-04 08:00:00', '2026-09-04 17:00:00', 8.00, ''),
(89, 32, 23, '2026-09-07', '2026-09-07 08:00:00', '2026-09-07 17:00:00', 8.00, ''),
(90, 32, 23, '2026-09-08', '2026-09-08 08:00:00', '2026-09-08 17:00:00', 8.00, ''),
(91, 32, 23, '2026-09-09', '2026-09-09 08:00:00', '2026-09-09 17:00:00', 8.00, ''),
(92, 32, 23, '2026-09-10', '2026-09-10 08:00:00', '2026-09-10 17:00:00', 8.00, ''),
(93, 32, 23, '2026-09-11', '2026-09-11 08:00:00', '2026-09-11 17:00:00', 8.00, ''),
(94, 50, 23, '2026-07-20', '2026-07-20 08:00:00', '2026-07-20 17:00:00', 8.00, 'Regular daily log'),
(95, 50, 23, '2026-07-21', '2026-07-21 08:00:00', '2026-07-21 17:00:00', 8.00, 'Regular daily log'),
(96, 50, 23, '2026-07-22', '2026-07-22 08:00:00', '2026-07-22 17:00:00', 8.00, 'Regular daily log'),
(97, 50, 23, '2026-07-23', '2026-07-23 08:00:00', '2026-07-23 17:00:00', 8.00, 'Regular daily log'),
(98, 50, 23, '2026-07-24', '2026-07-24 08:00:00', '2026-07-24 17:00:00', 8.00, 'Regular daily log'),
(99, 50, 23, '2026-07-27', '2026-07-27 08:00:00', '2026-07-27 17:00:00', 8.00, 'Regular daily log'),
(100, 50, 23, '2026-07-28', '2026-07-28 08:00:00', '2026-07-28 17:00:00', 8.00, 'Regular daily log'),
(101, 50, 23, '2026-07-29', '2026-07-29 08:00:00', '2026-07-29 17:00:00', 8.00, 'Regular daily log'),
(102, 50, 23, '2026-07-30', '2026-07-30 08:00:00', '2026-07-30 17:00:00', 8.00, 'Regular daily log'),
(103, 50, 23, '2026-07-31', '2026-07-31 08:00:00', '2026-07-31 17:00:00', 8.00, 'Regular daily log'),
(104, 50, 23, '2026-08-03', '2026-08-03 08:00:00', '2026-08-03 17:00:00', 8.00, 'Regular daily log'),
(105, 50, 23, '2026-08-04', '2026-08-04 08:00:00', '2026-08-04 17:00:00', 8.00, 'Regular daily log'),
(106, 50, 23, '2026-08-05', '2026-08-05 08:00:00', '2026-08-05 17:00:00', 8.00, 'Regular daily log'),
(107, 50, 23, '2026-08-06', '2026-08-06 08:00:00', '2026-08-06 17:00:00', 8.00, 'Regular daily log'),
(108, 50, 23, '2026-08-07', '2026-08-07 08:00:00', '2026-08-07 17:00:00', 8.00, 'Regular daily log'),
(109, 50, 23, '2026-08-10', '2026-08-10 08:00:00', '2026-08-10 17:00:00', 8.00, 'Regular daily log'),
(110, 50, 23, '2026-08-11', '2026-08-11 08:00:00', '2026-08-11 17:00:00', 8.00, 'Regular daily log'),
(111, 50, 23, '2026-08-12', '2026-08-12 08:00:00', '2026-08-12 17:00:00', 8.00, 'Regular daily log'),
(112, 50, 23, '2026-08-13', '2026-08-13 08:00:00', '2026-08-13 17:00:00', 8.00, 'Regular daily log'),
(113, 50, 23, '2026-08-14', '2026-08-14 08:00:00', '2026-08-14 17:00:00', 8.00, 'Regular daily log'),
(114, 50, 23, '2026-08-17', '2026-08-17 08:00:00', '2026-08-17 17:00:00', 8.00, 'Regular daily log'),
(115, 50, 23, '2026-08-18', '2026-08-18 08:00:00', '2026-08-18 17:00:00', 8.00, 'Regular daily log'),
(116, 50, 23, '2026-08-19', '2026-08-19 08:00:00', '2026-08-19 17:00:00', 8.00, 'Regular daily log'),
(117, 50, 23, '2026-08-20', '2026-08-20 08:00:00', '2026-08-20 17:00:00', 8.00, 'Regular daily log'),
(118, 50, 23, '2026-08-21', '2026-08-21 08:00:00', '2026-08-21 17:00:00', 8.00, 'Regular daily log'),
(119, 50, 23, '2026-08-24', '2026-08-24 08:00:00', '2026-08-24 17:00:00', 8.00, 'Regular daily log'),
(120, 50, 23, '2026-08-25', '2026-08-25 08:00:00', '2026-08-25 17:00:00', 8.00, 'Regular daily log'),
(121, 50, 23, '2026-08-26', '2026-08-26 08:00:00', '2026-08-26 17:00:00', 8.00, 'Regular daily log'),
(122, 50, 23, '2026-08-27', '2026-08-27 08:00:00', '2026-08-27 17:00:00', 8.00, 'Regular daily log'),
(123, 50, 23, '2026-08-28', '2026-08-28 08:00:00', '2026-08-28 17:00:00', 8.00, 'Regular daily log'),
(124, 50, 23, '2026-08-31', '2026-08-31 08:00:00', '2026-08-31 17:00:00', 8.00, 'Regular daily log'),
(125, 50, 23, '2026-09-01', '2026-09-01 08:00:00', '2026-09-01 17:00:00', 8.00, 'Regular daily log'),
(126, 50, 23, '2026-09-02', '2026-09-02 08:00:00', '2026-09-02 17:00:00', 8.00, 'Regular daily log'),
(127, 50, 23, '2026-09-03', '2026-09-03 08:00:00', '2026-09-03 17:00:00', 8.00, 'Regular daily log'),
(128, 50, 23, '2026-09-04', '2026-09-04 08:00:00', '2026-09-04 17:00:00', 8.00, 'Regular daily log'),
(129, 50, 23, '2026-09-07', '2026-09-07 08:00:00', '2026-09-07 17:00:00', 8.00, 'Regular daily log'),
(130, 50, 23, '2026-09-08', '2026-09-08 08:00:00', '2026-09-08 17:00:00', 8.00, 'Regular daily log'),
(131, 50, 23, '2026-09-09', '2026-09-09 08:00:00', '2026-09-09 17:00:00', 8.00, 'Regular daily log'),
(132, 50, 23, '2026-09-10', '2026-09-10 08:00:00', '2026-09-10 17:00:00', 8.00, 'Regular daily log'),
(133, 50, 23, '2026-09-11', '2026-09-11 08:00:00', '2026-09-11 17:00:00', 8.00, 'Regular daily log'),
(157, 51, 23, '2026-07-20', '2026-07-20 08:08:34', '2026-07-20 17:09:53', 8.02, 'Regular daily log'),
(158, 51, 23, '2026-07-21', '2026-07-21 08:03:45', '2026-07-21 17:19:07', 8.26, 'Regular daily log'),
(159, 51, 23, '2026-07-22', '2026-07-22 08:04:21', '2026-07-22 17:14:23', 8.17, 'Regular daily log'),
(160, 51, 23, '2026-07-23', '2026-07-23 07:58:54', '2026-07-23 17:01:21', 8.04, 'Regular daily log'),
(161, 51, 23, '2026-07-24', '2026-07-24 07:50:05', '2026-07-24 17:16:21', 8.44, 'Regular daily log'),
(162, 51, 23, '2026-07-27', '2026-07-27 07:51:29', '2026-07-27 17:18:24', 8.45, 'Regular daily log'),
(163, 51, 23, '2026-07-28', '2026-07-28 07:57:34', '2026-07-28 17:02:40', 8.09, 'Regular daily log'),
(164, 51, 23, '2026-07-29', '2026-07-29 08:00:37', '2026-07-29 17:05:05', 8.07, 'Regular daily log'),
(165, 51, 23, '2026-07-30', '2026-07-30 08:03:35', '2026-07-30 17:12:41', 8.15, 'Regular daily log'),
(166, 51, 23, '2026-07-31', '2026-07-31 07:52:42', '2026-07-31 17:15:25', 8.38, 'Regular daily log'),
(167, 51, 23, '2026-08-03', '2026-08-03 07:59:01', '2026-08-03 17:18:49', 8.33, 'Regular daily log'),
(168, 51, 23, '2026-08-04', '2026-08-04 07:57:04', '2026-08-04 17:18:55', 8.36, 'Regular daily log'),
(169, 51, 23, '2026-08-05', '2026-08-05 08:03:20', '2026-08-05 17:09:58', 8.11, 'Regular daily log'),
(170, 51, 23, '2026-08-06', '2026-08-06 07:59:48', '2026-08-06 17:19:07', 8.32, 'Regular daily log'),
(171, 51, 23, '2026-08-07', '2026-08-07 07:56:10', '2026-08-07 17:13:31', 8.29, 'Regular daily log'),
(172, 51, 23, '2026-08-10', '2026-08-10 07:59:06', '2026-08-10 17:04:59', 8.10, 'Regular daily log'),
(173, 51, 23, '2026-08-11', '2026-08-11 08:07:35', '2026-08-11 17:13:01', 8.09, 'Regular daily log'),
(174, 51, 23, '2026-08-12', '2026-08-12 08:02:18', '2026-08-12 17:02:28', 8.00, 'Regular daily log'),
(175, 51, 23, '2026-08-13', '2026-08-13 08:05:26', '2026-08-13 17:09:45', 8.07, 'Regular daily log'),
(176, 51, 23, '2026-08-14', '2026-08-14 07:52:30', '2026-08-14 17:03:15', 8.18, 'Regular daily log'),
(177, 51, 23, '2026-08-17', '2026-08-17 07:58:45', '2026-08-17 17:13:59', 8.25, 'Regular daily log'),
(178, 51, 23, '2026-08-18', '2026-08-18 07:53:43', '2026-08-18 17:16:38', 8.38, 'Regular daily log'),
(179, 51, 23, '2026-08-19', '2026-08-19 08:02:03', '2026-08-19 17:10:21', 8.14, 'Regular daily log'),
(180, 51, 23, '2026-08-20', '2026-08-20 08:05:38', '2026-08-20 17:07:06', 8.02, 'Regular daily log'),
(181, 51, 23, '2026-08-21', '2026-08-21 07:58:37', '2026-08-21 17:01:49', 8.05, 'Regular daily log'),
(182, 51, 23, '2026-08-24', '2026-08-24 07:53:12', '2026-08-24 17:10:34', 8.29, 'Regular daily log'),
(183, 51, 23, '2026-08-25', '2026-08-25 07:53:16', '2026-08-25 17:04:38', 8.19, 'Regular daily log'),
(184, 51, 23, '2026-08-26', '2026-08-26 08:03:22', '2026-08-26 17:12:58', 8.16, 'Regular daily log'),
(185, 51, 23, '2026-08-27', '2026-08-27 07:54:42', '2026-08-27 17:04:36', 8.17, 'Regular daily log'),
(186, 51, 23, '2026-08-28', '2026-08-28 07:58:55', '2026-08-28 17:10:46', 8.20, 'Regular daily log'),
(187, 51, 23, '2026-08-31', '2026-08-31 07:57:07', '2026-08-31 17:03:16', 8.10, 'Regular daily log'),
(188, 51, 23, '2026-09-01', '2026-09-01 08:05:01', '2026-09-01 17:05:16', 8.00, 'Regular daily log'),
(189, 51, 23, '2026-09-02', '2026-09-02 07:51:17', '2026-09-02 17:10:37', 8.32, 'Regular daily log'),
(190, 51, 23, '2026-09-03', '2026-09-03 07:59:15', '2026-09-03 17:14:25', 8.25, 'Regular daily log'),
(191, 51, 23, '2026-09-04', '2026-09-04 07:54:19', '2026-09-04 17:18:23', 8.40, 'Regular daily log'),
(192, 51, 23, '2026-09-07', '2026-09-07 08:08:56', '2026-09-07 17:19:34', 8.18, 'Regular daily log'),
(193, 51, 23, '2026-09-08', '2026-09-08 07:51:02', '2026-09-08 17:06:28', 8.26, 'Regular daily log'),
(194, 51, 23, '2026-09-09', '2026-09-09 07:59:15', '2026-09-09 17:06:51', 8.13, 'Regular daily log'),
(195, 51, 23, '2026-09-10', '2026-09-10 07:56:31', '2026-09-10 17:12:01', 8.26, 'Regular daily log'),
(196, 51, 23, '2026-09-11', '2026-09-11 07:50:34', '2026-09-11 17:06:49', 8.27, 'Regular daily log'),
(220, 52, 23, '2026-07-20', '2026-07-20 08:06:54', '2026-07-20 17:19:54', 8.22, 'Regular daily log'),
(221, 52, 23, '2026-07-21', '2026-07-21 07:58:46', '2026-07-21 17:04:10', 8.09, 'Regular daily log'),
(222, 52, 23, '2026-07-22', '2026-07-22 08:04:34', '2026-07-22 17:00:21', 7.93, 'Regular daily log'),
(223, 52, 23, '2026-07-23', '2026-07-23 08:08:02', '2026-07-23 17:09:06', 8.02, 'Regular daily log'),
(224, 52, 23, '2026-07-24', '2026-07-24 08:01:26', '2026-07-24 17:09:54', 8.14, 'Regular daily log'),
(225, 52, 23, '2026-07-27', '2026-07-27 08:05:11', '2026-07-27 17:06:14', 8.02, 'Regular daily log'),
(226, 52, 23, '2026-07-28', '2026-07-28 07:55:37', '2026-07-28 17:09:24', 8.23, 'Regular daily log'),
(227, 52, 23, '2026-07-29', '2026-07-29 08:00:08', '2026-07-29 17:02:28', 8.04, 'Regular daily log'),
(228, 52, 23, '2026-07-30', '2026-07-30 07:52:00', '2026-07-30 17:02:33', 8.18, 'Regular daily log'),
(229, 52, 23, '2026-07-31', '2026-07-31 07:56:47', '2026-07-31 17:06:17', 8.16, 'Regular daily log'),
(230, 52, 23, '2026-08-03', '2026-08-03 08:01:03', '2026-08-03 17:16:26', 8.26, 'Regular daily log'),
(231, 52, 23, '2026-08-04', '2026-08-04 07:59:02', '2026-08-04 17:15:51', 8.28, 'Regular daily log'),
(232, 52, 23, '2026-08-05', '2026-08-05 08:02:09', '2026-08-05 17:13:14', 8.18, 'Regular daily log'),
(233, 52, 23, '2026-08-06', '2026-08-06 07:59:42', '2026-08-06 17:08:51', 8.15, 'Regular daily log'),
(234, 52, 23, '2026-08-07', '2026-08-07 08:05:07', '2026-08-07 17:09:03', 8.07, 'Regular daily log'),
(235, 52, 23, '2026-08-10', '2026-08-10 08:09:54', '2026-08-10 17:12:24', 8.04, 'Regular daily log'),
(236, 52, 23, '2026-08-11', '2026-08-11 07:52:16', '2026-08-11 17:14:11', 8.37, 'Regular daily log'),
(237, 52, 23, '2026-08-12', '2026-08-12 07:54:07', '2026-08-12 17:18:03', 8.40, 'Regular daily log'),
(238, 52, 23, '2026-08-13', '2026-08-13 08:07:53', '2026-08-13 17:15:18', 8.12, 'Regular daily log'),
(239, 52, 23, '2026-08-14', '2026-08-14 07:52:53', '2026-08-14 17:08:28', 8.26, 'Regular daily log'),
(240, 52, 23, '2026-08-17', '2026-08-17 08:03:43', '2026-08-17 17:03:10', 7.99, 'Regular daily log'),
(241, 52, 23, '2026-08-18', '2026-08-18 08:04:40', '2026-08-18 17:03:54', 7.99, 'Regular daily log'),
(242, 52, 23, '2026-08-19', '2026-08-19 08:05:30', '2026-08-19 17:05:50', 8.01, 'Regular daily log'),
(243, 52, 23, '2026-08-20', '2026-08-20 07:52:38', '2026-08-20 17:15:41', 8.38, 'Regular daily log'),
(244, 52, 23, '2026-08-21', '2026-08-21 08:00:30', '2026-08-21 17:05:28', 8.08, 'Regular daily log'),
(245, 52, 23, '2026-08-24', '2026-08-24 08:05:53', '2026-08-24 17:02:59', 7.95, 'Regular daily log'),
(246, 52, 23, '2026-08-25', '2026-08-25 07:57:19', '2026-08-25 17:07:37', 8.17, 'Regular daily log'),
(247, 52, 23, '2026-08-26', '2026-08-26 08:06:10', '2026-08-26 17:17:59', 8.20, 'Regular daily log'),
(248, 52, 23, '2026-08-27', '2026-08-27 07:51:24', '2026-08-27 17:13:06', 8.36, 'Regular daily log'),
(249, 52, 23, '2026-08-28', '2026-08-28 07:51:17', '2026-08-28 17:07:10', 8.26, 'Regular daily log'),
(250, 52, 23, '2026-08-31', '2026-08-31 08:01:57', '2026-08-31 17:18:16', 8.27, 'Regular daily log'),
(251, 52, 23, '2026-09-01', '2026-09-01 08:05:29', '2026-09-01 17:02:36', 7.95, 'Regular daily log'),
(252, 52, 23, '2026-09-02', '2026-09-02 07:56:35', '2026-09-02 17:05:09', 8.14, 'Regular daily log'),
(253, 52, 23, '2026-09-03', '2026-09-03 07:55:59', '2026-09-03 17:14:29', 8.31, 'Regular daily log'),
(254, 52, 23, '2026-09-04', '2026-09-04 08:04:29', '2026-09-04 17:08:56', 8.07, 'Regular daily log'),
(255, 52, 23, '2026-09-07', '2026-09-07 07:51:16', '2026-09-07 17:19:33', 8.47, 'Regular daily log'),
(256, 52, 23, '2026-09-08', '2026-09-08 08:03:58', '2026-09-08 17:11:10', 8.12, 'Regular daily log'),
(257, 52, 23, '2026-09-09', '2026-09-09 08:03:57', '2026-09-09 17:16:17', 8.21, 'Regular daily log'),
(258, 52, 23, '2026-09-10', '2026-09-10 08:09:32', '2026-09-10 17:08:49', 7.99, 'Regular daily log'),
(259, 52, 23, '2026-09-11', '2026-09-11 07:55:31', '2026-09-11 17:01:06', 8.09, 'Regular daily log'),
(283, 53, 23, '2026-07-20', '2026-07-20 07:58:25', '2026-07-20 17:19:54', 8.36, 'Regular daily log'),
(284, 53, 23, '2026-07-21', '2026-07-21 08:04:15', '2026-07-21 17:11:33', 8.12, 'Regular daily log'),
(285, 53, 23, '2026-07-22', '2026-07-22 08:05:00', '2026-07-22 17:00:24', 7.92, 'Regular daily log'),
(286, 53, 23, '2026-07-23', '2026-07-23 08:07:01', '2026-07-23 17:03:51', 7.95, 'Regular daily log'),
(287, 53, 23, '2026-07-24', '2026-07-24 07:58:11', '2026-07-24 17:09:26', 8.19, 'Regular daily log'),
(288, 53, 23, '2026-07-27', '2026-07-27 07:52:36', '2026-07-27 17:04:41', 8.20, 'Regular daily log'),
(289, 53, 23, '2026-07-28', '2026-07-28 08:05:37', '2026-07-28 17:04:04', 7.97, 'Regular daily log'),
(290, 53, 23, '2026-07-29', '2026-07-29 08:03:29', '2026-07-29 17:15:15', 8.20, 'Regular daily log'),
(291, 53, 23, '2026-07-30', '2026-07-30 08:05:49', '2026-07-30 17:13:20', 8.13, 'Regular daily log'),
(292, 53, 23, '2026-07-31', '2026-07-31 08:09:12', '2026-07-31 17:16:03', 8.11, 'Regular daily log'),
(293, 53, 23, '2026-08-03', '2026-08-03 07:52:39', '2026-08-03 17:05:08', 8.21, 'Regular daily log'),
(294, 53, 23, '2026-08-04', '2026-08-04 08:07:42', '2026-08-04 17:13:08', 8.09, 'Regular daily log'),
(295, 53, 23, '2026-08-05', '2026-08-05 08:02:33', '2026-08-05 17:03:24', 8.01, 'Regular daily log'),
(296, 53, 23, '2026-08-06', '2026-08-06 08:09:19', '2026-08-06 17:06:23', 7.95, 'Regular daily log'),
(297, 53, 23, '2026-08-07', '2026-08-07 08:04:00', '2026-08-07 17:10:52', 8.11, 'Regular daily log'),
(298, 53, 23, '2026-08-10', '2026-08-10 08:02:18', '2026-08-10 17:08:56', 8.11, 'Regular daily log'),
(299, 53, 23, '2026-08-11', '2026-08-11 07:57:45', '2026-08-11 17:11:57', 8.24, 'Regular daily log'),
(300, 53, 23, '2026-08-12', '2026-08-12 08:06:32', '2026-08-12 17:06:51', 8.01, 'Regular daily log'),
(301, 53, 23, '2026-08-13', '2026-08-13 07:54:38', '2026-08-13 17:02:37', 8.13, 'Regular daily log'),
(302, 53, 23, '2026-08-14', '2026-08-14 08:09:12', '2026-08-14 17:08:11', 7.98, 'Regular daily log'),
(303, 53, 23, '2026-08-17', '2026-08-17 07:53:17', '2026-08-17 17:11:54', 8.31, 'Regular daily log'),
(304, 53, 23, '2026-08-18', '2026-08-18 07:59:38', '2026-08-18 17:12:31', 8.21, 'Regular daily log'),
(305, 53, 23, '2026-08-19', '2026-08-19 08:03:41', '2026-08-19 17:10:50', 8.12, 'Regular daily log'),
(306, 53, 23, '2026-08-20', '2026-08-20 08:03:10', '2026-08-20 17:13:21', 8.17, 'Regular daily log'),
(307, 53, 23, '2026-08-21', '2026-08-21 07:57:14', '2026-08-21 17:16:06', 8.31, 'Regular daily log'),
(308, 53, 23, '2026-08-24', '2026-08-24 08:08:49', '2026-08-24 17:05:49', 7.95, 'Regular daily log'),
(309, 53, 23, '2026-08-25', '2026-08-25 08:02:38', '2026-08-25 17:05:43', 8.05, 'Regular daily log'),
(310, 53, 23, '2026-08-26', '2026-08-26 08:00:41', '2026-08-26 17:16:18', 8.26, 'Regular daily log'),
(311, 53, 23, '2026-08-27', '2026-08-27 07:59:25', '2026-08-27 17:18:12', 8.31, 'Regular daily log'),
(312, 53, 23, '2026-08-28', '2026-08-28 07:52:46', '2026-08-28 17:19:15', 8.44, 'Regular daily log'),
(313, 53, 23, '2026-08-31', '2026-08-31 07:57:57', '2026-08-31 17:02:01', 8.07, 'Regular daily log'),
(314, 53, 23, '2026-09-01', '2026-09-01 07:56:12', '2026-09-01 17:05:01', 8.15, 'Regular daily log'),
(315, 53, 23, '2026-09-02', '2026-09-02 07:56:27', '2026-09-02 17:17:15', 8.35, 'Regular daily log'),
(316, 53, 23, '2026-09-03', '2026-09-03 07:56:54', '2026-09-03 17:02:47', 8.10, 'Regular daily log'),
(317, 53, 23, '2026-09-04', '2026-09-04 08:03:12', '2026-09-04 17:17:38', 8.24, 'Regular daily log'),
(318, 53, 23, '2026-09-07', '2026-09-07 07:58:36', '2026-09-07 17:10:05', 8.19, 'Regular daily log'),
(319, 53, 23, '2026-09-08', '2026-09-08 07:54:37', '2026-09-08 17:12:52', 8.30, 'Regular daily log'),
(320, 53, 23, '2026-09-09', '2026-09-09 08:00:30', '2026-09-09 17:13:55', 8.22, 'Regular daily log'),
(321, 53, 23, '2026-09-10', '2026-09-10 08:08:05', '2026-09-10 17:08:42', 8.01, 'Regular daily log'),
(322, 53, 23, '2026-09-11', '2026-09-11 07:59:15', '2026-09-11 17:00:09', 8.02, 'Regular daily log'),
(346, 54, 23, '2026-07-20', '2026-07-20 08:06:47', '2026-07-20 17:11:25', 8.08, 'Regular daily log'),
(347, 54, 23, '2026-07-21', '2026-07-21 07:56:46', '2026-07-21 17:19:36', 8.38, 'Regular daily log'),
(348, 54, 23, '2026-07-22', '2026-07-22 08:07:42', '2026-07-22 17:09:44', 8.03, 'Regular daily log'),
(349, 54, 23, '2026-07-23', '2026-07-23 08:05:35', '2026-07-23 17:08:41', 8.05, 'Regular daily log'),
(350, 54, 23, '2026-07-24', '2026-07-24 08:06:43', '2026-07-24 17:17:33', 8.18, 'Regular daily log'),
(351, 54, 23, '2026-07-27', '2026-07-27 08:07:35', '2026-07-27 17:15:19', 8.13, 'Regular daily log'),
(352, 54, 23, '2026-07-28', '2026-07-28 07:53:48', '2026-07-28 17:13:04', 8.32, 'Regular daily log'),
(353, 54, 23, '2026-07-29', '2026-07-29 08:03:56', '2026-07-29 17:10:29', 8.11, 'Regular daily log'),
(354, 54, 23, '2026-07-30', '2026-07-30 08:00:37', '2026-07-30 17:01:40', 8.02, 'Regular daily log'),
(355, 54, 23, '2026-07-31', '2026-07-31 08:06:27', '2026-07-31 17:17:15', 8.18, 'Regular daily log'),
(356, 54, 23, '2026-08-03', '2026-08-03 08:06:54', '2026-08-03 17:12:47', 8.10, 'Regular daily log'),
(357, 54, 23, '2026-08-04', '2026-08-04 08:03:14', '2026-08-04 17:07:51', 8.08, 'Regular daily log'),
(358, 54, 23, '2026-08-05', '2026-08-05 08:09:31', '2026-08-05 17:14:01', 8.08, 'Regular daily log'),
(359, 54, 23, '2026-08-06', '2026-08-06 08:01:36', '2026-08-06 17:15:54', 8.24, 'Regular daily log'),
(360, 54, 23, '2026-08-07', '2026-08-07 07:54:45', '2026-08-07 17:16:03', 8.36, 'Regular daily log'),
(361, 54, 23, '2026-08-10', '2026-08-10 07:55:58', '2026-08-10 17:01:45', 8.10, 'Regular daily log'),
(362, 54, 23, '2026-08-11', '2026-08-11 08:00:48', '2026-08-11 17:08:49', 8.13, 'Regular daily log'),
(363, 54, 23, '2026-08-12', '2026-08-12 08:01:39', '2026-08-12 17:11:49', 8.17, 'Regular daily log'),
(364, 54, 23, '2026-08-13', '2026-08-13 07:54:07', '2026-08-13 17:05:10', 8.18, 'Regular daily log'),
(365, 54, 23, '2026-08-14', '2026-08-14 08:03:31', '2026-08-14 17:12:03', 8.14, 'Regular daily log'),
(366, 54, 23, '2026-08-17', '2026-08-17 08:09:44', '2026-08-17 17:02:33', 7.88, 'Regular daily log'),
(367, 54, 23, '2026-08-18', '2026-08-18 08:03:33', '2026-08-18 17:00:06', 7.94, 'Regular daily log'),
(368, 54, 23, '2026-08-19', '2026-08-19 08:09:50', '2026-08-19 17:18:55', 8.15, 'Regular daily log'),
(369, 54, 23, '2026-08-20', '2026-08-20 08:05:05', '2026-08-20 17:18:42', 8.23, 'Regular daily log'),
(370, 54, 23, '2026-08-21', '2026-08-21 07:58:15', '2026-08-21 17:05:10', 8.12, 'Regular daily log'),
(371, 54, 23, '2026-08-24', '2026-08-24 07:51:03', '2026-08-24 17:09:46', 8.31, 'Regular daily log'),
(372, 54, 23, '2026-08-25', '2026-08-25 07:55:42', '2026-08-25 17:19:11', 8.39, 'Regular daily log'),
(373, 54, 23, '2026-08-26', '2026-08-26 08:08:52', '2026-08-26 17:16:48', 8.13, 'Regular daily log'),
(374, 54, 23, '2026-08-27', '2026-08-27 07:57:25', '2026-08-27 17:06:42', 8.15, 'Regular daily log'),
(375, 54, 23, '2026-08-28', '2026-08-28 08:01:12', '2026-08-28 17:15:58', 8.25, 'Regular daily log'),
(376, 54, 23, '2026-08-31', '2026-08-31 07:56:14', '2026-08-31 17:03:17', 8.12, 'Regular daily log'),
(377, 54, 23, '2026-09-01', '2026-09-01 08:07:42', '2026-09-01 17:18:40', 8.18, 'Regular daily log'),
(378, 54, 23, '2026-09-02', '2026-09-02 07:50:13', '2026-09-02 17:05:07', 8.25, 'Regular daily log'),
(379, 54, 23, '2026-09-03', '2026-09-03 07:54:57', '2026-09-03 17:09:25', 8.24, 'Regular daily log'),
(380, 54, 23, '2026-09-04', '2026-09-04 08:02:16', '2026-09-04 17:13:02', 8.18, 'Regular daily log'),
(381, 54, 23, '2026-09-07', '2026-09-07 07:58:24', '2026-09-07 17:02:55', 8.08, 'Regular daily log'),
(382, 54, 23, '2026-09-08', '2026-09-08 07:59:24', '2026-09-08 17:18:14', 8.31, 'Regular daily log'),
(383, 54, 23, '2026-09-09', '2026-09-09 07:53:00', '2026-09-09 17:00:18', 8.12, 'Regular daily log'),
(384, 54, 23, '2026-09-10', '2026-09-10 08:02:31', '2026-09-10 17:01:41', 7.99, 'Regular daily log'),
(385, 54, 23, '2026-09-11', '2026-09-11 08:00:55', '2026-09-11 17:09:29', 8.14, 'Regular daily log'),
(409, 49, 23, '2026-07-20', '2026-07-20 07:55:13', '2026-07-20 17:01:56', 8.11, 'Regular daily log'),
(410, 49, 23, '2026-07-21', '2026-07-21 08:04:04', '2026-07-21 17:04:30', 8.01, 'Regular daily log'),
(411, 49, 23, '2026-07-22', '2026-07-22 07:50:20', '2026-07-22 17:08:11', 8.30, 'Regular daily log'),
(412, 49, 23, '2026-07-23', '2026-07-23 08:09:55', '2026-07-23 17:15:04', 8.09, 'Regular daily log'),
(413, 49, 23, '2026-07-24', '2026-07-24 08:05:36', '2026-07-24 17:12:47', 8.12, 'Regular daily log'),
(414, 49, 23, '2026-07-27', '2026-07-27 08:07:08', '2026-07-27 17:07:21', 8.00, 'Regular daily log'),
(415, 49, 23, '2026-07-28', '2026-07-28 07:55:21', '2026-07-28 17:04:43', 8.16, 'Regular daily log'),
(416, 49, 23, '2026-07-29', '2026-07-29 07:57:33', '2026-07-29 17:03:36', 8.10, 'Regular daily log'),
(417, 49, 23, '2026-07-30', '2026-07-30 08:05:23', '2026-07-30 17:06:06', 8.01, 'Regular daily log'),
(418, 49, 23, '2026-07-31', '2026-07-31 07:54:23', '2026-07-31 17:03:35', 8.15, 'Regular daily log'),
(419, 49, 23, '2026-08-03', '2026-08-03 07:54:47', '2026-08-03 17:13:10', 8.31, 'Regular daily log'),
(420, 49, 23, '2026-08-04', '2026-08-04 08:01:32', '2026-08-04 17:18:09', 8.28, 'Regular daily log'),
(421, 49, 23, '2026-08-05', '2026-08-05 08:06:12', '2026-08-05 17:06:31', 8.01, 'Regular daily log'),
(422, 49, 23, '2026-08-06', '2026-08-06 07:53:58', '2026-08-06 17:00:21', 8.11, 'Regular daily log'),
(423, 49, 23, '2026-08-07', '2026-08-07 07:59:50', '2026-08-07 17:08:07', 8.14, 'Regular daily log'),
(424, 49, 23, '2026-08-10', '2026-08-10 08:01:04', '2026-08-10 17:11:01', 8.17, 'Regular daily log'),
(425, 49, 23, '2026-08-11', '2026-08-11 07:51:54', '2026-08-11 17:16:29', 8.41, 'Regular daily log'),
(426, 49, 23, '2026-08-12', '2026-08-12 08:06:43', '2026-08-12 17:14:08', 8.12, 'Regular daily log'),
(427, 49, 23, '2026-08-13', '2026-08-13 07:50:31', '2026-08-13 17:00:13', 8.16, 'Regular daily log'),
(428, 49, 23, '2026-08-14', '2026-08-14 08:09:32', '2026-08-14 17:17:02', 8.13, 'Regular daily log'),
(429, 49, 23, '2026-08-17', '2026-08-17 07:56:33', '2026-08-17 17:01:40', 8.09, 'Regular daily log'),
(430, 49, 23, '2026-08-18', '2026-08-18 07:58:40', '2026-08-18 17:18:22', 8.33, 'Regular daily log'),
(431, 49, 23, '2026-08-19', '2026-08-19 07:55:49', '2026-08-19 17:14:00', 8.30, 'Regular daily log'),
(432, 49, 23, '2026-08-20', '2026-08-20 08:02:34', '2026-08-20 17:00:51', 7.97, 'Regular daily log'),
(433, 49, 23, '2026-08-21', '2026-08-21 07:56:35', '2026-08-21 17:10:19', 8.23, 'Regular daily log'),
(434, 49, 23, '2026-08-24', '2026-08-24 08:01:53', '2026-08-24 17:08:26', 8.11, 'Regular daily log'),
(435, 49, 23, '2026-08-25', '2026-08-25 07:56:32', '2026-08-25 17:07:23', 8.18, 'Regular daily log'),
(436, 49, 23, '2026-08-26', '2026-08-26 08:07:18', '2026-08-26 17:04:23', 7.95, 'Regular daily log'),
(437, 49, 23, '2026-08-27', '2026-08-27 08:00:02', '2026-08-27 17:17:02', 8.28, 'Regular daily log'),
(438, 49, 23, '2026-08-28', '2026-08-28 08:05:06', '2026-08-28 17:04:22', 7.99, 'Regular daily log'),
(439, 49, 23, '2026-08-31', '2026-08-31 08:06:33', '2026-08-31 17:09:38', 8.05, 'Regular daily log'),
(440, 49, 23, '2026-09-01', '2026-09-01 08:08:33', '2026-09-01 17:03:50', 7.92, 'Regular daily log'),
(441, 49, 23, '2026-09-02', '2026-09-02 07:53:32', '2026-09-02 17:06:11', 8.21, 'Regular daily log'),
(442, 49, 23, '2026-09-03', '2026-09-03 07:50:21', '2026-09-03 17:03:11', 8.21, 'Regular daily log'),
(443, 49, 23, '2026-09-04', '2026-09-04 08:04:51', '2026-09-04 17:04:45', 8.00, 'Regular daily log'),
(444, 49, 23, '2026-09-07', '2026-09-07 08:09:13', '2026-09-07 17:01:50', 7.88, 'Regular daily log'),
(445, 49, 23, '2026-09-08', '2026-09-08 08:01:29', '2026-09-08 17:11:58', 8.17, 'Regular daily log'),
(446, 49, 23, '2026-09-09', '2026-09-09 07:55:24', '2026-09-09 17:11:05', 8.26, 'Regular daily log'),
(447, 49, 23, '2026-09-10', '2026-09-10 08:09:12', '2026-09-10 17:02:46', 7.89, 'Regular daily log'),
(448, 49, 23, '2026-09-11', '2026-09-11 08:06:17', '2026-09-11 17:13:05', 8.11, 'Regular daily log'),
(472, 45, 23, '2026-07-20', '2026-07-20 08:01:25', '2026-07-20 17:08:03', 8.11, 'Regular daily log'),
(473, 45, 23, '2026-07-21', '2026-07-21 07:56:02', '2026-07-21 17:05:58', 8.17, 'Regular daily log'),
(474, 45, 23, '2026-07-22', '2026-07-22 08:01:45', '2026-07-22 17:00:54', 7.99, 'Regular daily log'),
(475, 45, 23, '2026-07-23', '2026-07-23 07:59:15', '2026-07-23 17:03:33', 8.07, 'Regular daily log'),
(476, 45, 23, '2026-07-24', '2026-07-24 08:00:02', '2026-07-24 17:19:29', 8.32, 'Regular daily log'),
(477, 45, 23, '2026-07-27', '2026-07-27 07:57:20', '2026-07-27 17:18:15', 8.35, 'Regular daily log'),
(478, 45, 23, '2026-07-28', '2026-07-28 07:59:17', '2026-07-28 17:11:38', 8.21, 'Regular daily log'),
(479, 45, 23, '2026-07-29', '2026-07-29 08:00:19', '2026-07-29 17:16:43', 8.27, 'Regular daily log'),
(480, 45, 23, '2026-07-30', '2026-07-30 08:02:36', '2026-07-30 17:12:53', 8.17, 'Regular daily log'),
(481, 45, 23, '2026-07-31', '2026-07-31 07:56:37', '2026-07-31 17:14:28', 8.30, 'Regular daily log'),
(482, 45, 23, '2026-08-03', '2026-08-03 08:02:28', '2026-08-03 17:18:57', 8.27, 'Regular daily log'),
(483, 45, 23, '2026-08-04', '2026-08-04 08:07:22', '2026-08-04 17:09:59', 8.04, 'Regular daily log'),
(484, 45, 23, '2026-08-05', '2026-08-05 08:07:50', '2026-08-05 17:19:12', 8.19, 'Regular daily log'),
(485, 45, 23, '2026-08-06', '2026-08-06 07:52:32', '2026-08-06 17:15:02', 8.38, 'Regular daily log'),
(486, 45, 23, '2026-08-07', '2026-08-07 07:57:37', '2026-08-07 17:12:58', 8.26, 'Regular daily log'),
(487, 45, 23, '2026-08-10', '2026-08-10 07:52:02', '2026-08-10 17:11:16', 8.32, 'Regular daily log'),
(488, 45, 23, '2026-08-11', '2026-08-11 08:00:13', '2026-08-11 17:17:19', 8.29, 'Regular daily log'),
(489, 45, 23, '2026-08-12', '2026-08-12 08:05:57', '2026-08-12 17:07:50', 8.03, 'Regular daily log'),
(490, 45, 23, '2026-08-13', '2026-08-13 08:01:16', '2026-08-13 17:12:53', 8.19, 'Regular daily log'),
(491, 45, 23, '2026-08-14', '2026-08-14 08:00:37', '2026-08-14 17:14:28', 8.23, 'Regular daily log'),
(492, 45, 23, '2026-08-17', '2026-08-17 07:50:30', '2026-08-17 17:19:05', 8.48, 'Regular daily log'),
(493, 45, 23, '2026-08-18', '2026-08-18 08:03:56', '2026-08-18 17:12:24', 8.14, 'Regular daily log'),
(494, 45, 23, '2026-08-19', '2026-08-19 07:50:12', '2026-08-19 17:03:50', 8.23, 'Regular daily log'),
(495, 45, 23, '2026-08-20', '2026-08-20 08:08:34', '2026-08-20 17:01:19', 7.88, 'Regular daily log'),
(496, 45, 23, '2026-08-21', '2026-08-21 08:00:54', '2026-08-21 17:10:33', 8.16, 'Regular daily log'),
(497, 45, 23, '2026-08-24', '2026-08-24 07:50:04', '2026-08-24 17:08:41', 8.31, 'Regular daily log'),
(498, 45, 23, '2026-08-25', '2026-08-25 07:53:16', '2026-08-25 17:10:15', 8.28, 'Regular daily log'),
(499, 45, 23, '2026-08-26', '2026-08-26 07:51:28', '2026-08-26 17:16:34', 8.42, 'Regular daily log'),
(500, 45, 23, '2026-08-27', '2026-08-27 08:08:26', '2026-08-27 17:02:30', 7.90, 'Regular daily log'),
(501, 45, 23, '2026-08-28', '2026-08-28 08:07:14', '2026-08-28 17:18:40', 8.19, 'Regular daily log'),
(502, 45, 23, '2026-08-31', '2026-08-31 07:51:39', '2026-08-31 17:12:14', 8.34, 'Regular daily log'),
(503, 45, 23, '2026-09-01', '2026-09-01 08:06:13', '2026-09-01 17:04:24', 7.97, 'Regular daily log'),
(504, 45, 23, '2026-09-02', '2026-09-02 08:03:20', '2026-09-02 17:13:31', 8.17, 'Regular daily log'),
(505, 45, 23, '2026-09-03', '2026-09-03 07:57:36', '2026-09-03 17:17:28', 8.33, 'Regular daily log'),
(506, 45, 23, '2026-09-04', '2026-09-04 07:54:33', '2026-09-04 17:10:18', 8.26, 'Regular daily log'),
(507, 45, 23, '2026-09-07', '2026-09-07 08:07:55', '2026-09-07 17:18:41', 8.18, 'Regular daily log'),
(508, 45, 23, '2026-09-08', '2026-09-08 08:09:39', '2026-09-08 17:02:13', 7.88, 'Regular daily log'),
(509, 45, 23, '2026-09-09', '2026-09-09 08:02:10', '2026-09-09 17:14:10', 8.20, 'Regular daily log'),
(510, 45, 23, '2026-09-10', '2026-09-10 08:04:20', '2026-09-10 17:09:10', 8.08, 'Regular daily log'),
(511, 45, 23, '2026-09-11', '2026-09-11 07:52:53', '2026-09-11 17:06:55', 8.23, 'Regular daily log'),
(535, 49, 38, '2026-09-13', '2026-09-13 21:03:46', '2026-09-13 21:03:51', 0.00, NULL),
(536, 55, 23, '2026-07-20', '2026-07-20 08:07:34', '2026-07-20 17:09:29', 8.03, 'Regular daily log'),
(537, 55, 23, '2026-07-21', '2026-07-21 08:04:43', '2026-07-21 17:05:09', 8.01, 'Regular daily log'),
(538, 55, 23, '2026-07-22', '2026-07-22 07:51:37', '2026-07-22 17:12:36', 8.35, 'Regular daily log'),
(539, 55, 23, '2026-07-23', '2026-07-23 08:08:10', '2026-07-23 17:13:05', 8.08, 'Regular daily log'),
(540, 55, 23, '2026-07-24', '2026-07-24 08:00:54', '2026-07-24 17:15:14', 8.24, 'Regular daily log'),
(541, 55, 23, '2026-07-27', '2026-07-27 07:53:29', '2026-07-27 17:11:44', 8.30, 'Regular daily log'),
(542, 55, 23, '2026-07-28', '2026-07-28 07:58:14', '2026-07-28 17:05:58', 8.13, 'Regular daily log'),
(543, 55, 23, '2026-07-29', '2026-07-29 07:55:07', '2026-07-29 17:07:41', 8.21, 'Regular daily log'),
(544, 55, 23, '2026-07-30', '2026-07-30 07:53:05', '2026-07-30 17:12:22', 8.32, 'Regular daily log'),
(545, 55, 23, '2026-07-31', '2026-07-31 08:02:37', '2026-07-31 17:05:59', 8.06, 'Regular daily log'),
(546, 55, 23, '2026-08-03', '2026-08-03 08:02:06', '2026-08-03 17:02:32', 8.01, 'Regular daily log'),
(547, 55, 23, '2026-08-04', '2026-08-04 08:06:21', '2026-08-04 17:14:09', 8.13, 'Regular daily log'),
(548, 55, 23, '2026-08-05', '2026-08-05 07:51:46', '2026-08-05 17:06:20', 8.24, 'Regular daily log'),
(549, 55, 23, '2026-08-06', '2026-08-06 07:56:24', '2026-08-06 17:13:01', 8.28, 'Regular daily log'),
(550, 55, 23, '2026-08-07', '2026-08-07 07:55:52', '2026-08-07 17:10:18', 8.24, 'Regular daily log'),
(551, 55, 23, '2026-08-10', '2026-08-10 08:03:53', '2026-08-10 17:18:34', 8.24, 'Regular daily log'),
(552, 55, 23, '2026-08-11', '2026-08-11 08:01:09', '2026-08-11 17:00:06', 7.98, 'Regular daily log'),
(553, 55, 23, '2026-08-12', '2026-08-12 07:57:03', '2026-08-12 17:14:56', 8.30, 'Regular daily log'),
(554, 55, 23, '2026-08-13', '2026-08-13 08:03:33', '2026-08-13 17:02:57', 7.99, 'Regular daily log'),
(555, 55, 23, '2026-08-14', '2026-08-14 08:04:06', '2026-08-14 17:01:39', 7.96, 'Regular daily log'),
(556, 55, 23, '2026-08-17', '2026-08-17 07:55:59', '2026-08-17 17:04:58', 8.15, 'Regular daily log'),
(557, 55, 23, '2026-08-18', '2026-08-18 07:56:52', '2026-08-18 17:19:29', 8.38, 'Regular daily log'),
(558, 55, 23, '2026-08-19', '2026-08-19 08:06:49', '2026-08-19 17:05:37', 7.98, 'Regular daily log'),
(559, 55, 23, '2026-08-20', '2026-08-20 08:07:38', '2026-08-20 17:11:19', 8.06, 'Regular daily log'),
(560, 55, 23, '2026-08-21', '2026-08-21 07:53:41', '2026-08-21 17:04:31', 8.18, 'Regular daily log'),
(561, 55, 23, '2026-08-24', '2026-08-24 08:01:32', '2026-08-24 17:04:08', 8.04, 'Regular daily log'),
(562, 55, 23, '2026-08-25', '2026-08-25 07:56:03', '2026-08-25 17:17:53', 8.36, 'Regular daily log'),
(563, 55, 23, '2026-08-26', '2026-08-26 08:01:16', '2026-08-26 17:02:42', 8.02, 'Regular daily log'),
(564, 55, 23, '2026-08-27', '2026-08-27 08:09:43', '2026-08-27 17:10:27', 8.01, 'Regular daily log'),
(565, 55, 23, '2026-08-28', '2026-08-28 08:03:08', '2026-08-28 17:14:18', 8.19, 'Regular daily log'),
(566, 55, 23, '2026-08-31', '2026-08-31 08:02:09', '2026-08-31 17:17:50', 8.26, 'Regular daily log'),
(567, 55, 23, '2026-09-01', '2026-09-01 08:02:46', '2026-09-01 17:10:18', 8.13, 'Regular daily log'),
(568, 55, 23, '2026-09-02', '2026-09-02 08:03:12', '2026-09-02 17:15:06', 8.20, 'Regular daily log'),
(569, 55, 23, '2026-09-03', '2026-09-03 08:05:53', '2026-09-03 17:14:11', 8.14, 'Regular daily log'),
(570, 55, 23, '2026-09-04', '2026-09-04 07:53:16', '2026-09-04 17:13:46', 8.34, 'Regular daily log'),
(571, 55, 23, '2026-09-07', '2026-09-07 08:09:04', '2026-09-07 17:14:01', 8.08, 'Regular daily log'),
(572, 55, 23, '2026-09-08', '2026-09-08 08:02:55', '2026-09-08 17:02:33', 7.99, 'Regular daily log'),
(573, 55, 23, '2026-09-09', '2026-09-09 08:03:58', '2026-09-09 17:02:14', 7.97, 'Regular daily log'),
(574, 55, 23, '2026-09-10', '2026-09-10 07:59:16', '2026-09-10 17:19:37', 8.34, 'Regular daily log'),
(575, 55, 23, '2026-09-11', '2026-09-11 08:00:19', '2026-09-11 17:12:46', 8.21, 'Regular daily log'),
(599, 56, 23, '2026-07-20', '2026-07-20 08:03:16', '2026-07-20 17:02:50', 7.99, 'Regular daily log'),
(600, 56, 23, '2026-07-21', '2026-07-21 08:04:24', '2026-07-21 17:03:28', 7.98, 'Regular daily log'),
(601, 56, 23, '2026-07-22', '2026-07-22 08:04:09', '2026-07-22 17:00:21', 7.94, 'Regular daily log'),
(602, 56, 23, '2026-07-23', '2026-07-23 08:09:20', '2026-07-23 17:15:35', 8.10, 'Regular daily log'),
(603, 56, 23, '2026-07-24', '2026-07-24 08:09:57', '2026-07-24 17:13:00', 8.05, 'Regular daily log'),
(604, 56, 23, '2026-07-27', '2026-07-27 07:55:10', '2026-07-27 17:06:49', 8.19, 'Regular daily log'),
(605, 56, 23, '2026-07-28', '2026-07-28 08:08:36', '2026-07-28 17:12:32', 8.07, 'Regular daily log'),
(606, 56, 23, '2026-07-29', '2026-07-29 07:56:52', '2026-07-29 17:16:47', 8.33, 'Regular daily log'),
(607, 56, 23, '2026-07-30', '2026-07-30 07:53:20', '2026-07-30 17:06:19', 8.22, 'Regular daily log'),
(608, 56, 23, '2026-07-31', '2026-07-31 07:51:34', '2026-07-31 17:08:53', 8.29, 'Regular daily log'),
(609, 56, 23, '2026-08-03', '2026-08-03 08:09:44', '2026-08-03 17:12:00', 8.04, 'Regular daily log'),
(610, 56, 23, '2026-08-04', '2026-08-04 07:50:51', '2026-08-04 17:08:16', 8.29, 'Regular daily log'),
(611, 56, 23, '2026-08-05', '2026-08-05 08:08:45', '2026-08-05 17:09:00', 8.00, 'Regular daily log'),
(612, 56, 23, '2026-08-06', '2026-08-06 07:58:46', '2026-08-06 17:16:50', 8.30, 'Regular daily log'),
(613, 56, 23, '2026-08-07', '2026-08-07 08:07:53', '2026-08-07 17:18:55', 8.18, 'Regular daily log'),
(614, 56, 23, '2026-08-10', '2026-08-10 07:50:57', '2026-08-10 17:07:59', 8.28, 'Regular daily log'),
(615, 56, 23, '2026-08-11', '2026-08-11 08:07:05', '2026-08-11 17:01:30', 7.91, 'Regular daily log'),
(616, 56, 23, '2026-08-12', '2026-08-12 08:06:16', '2026-08-12 17:16:50', 8.18, 'Regular daily log'),
(617, 56, 23, '2026-08-13', '2026-08-13 08:05:24', '2026-08-13 17:06:30', 8.02, 'Regular daily log'),
(618, 56, 23, '2026-08-14', '2026-08-14 07:56:20', '2026-08-14 17:12:08', 8.26, 'Regular daily log'),
(619, 56, 23, '2026-08-17', '2026-08-17 07:51:39', '2026-08-17 17:11:55', 8.34, 'Regular daily log'),
(620, 56, 23, '2026-08-18', '2026-08-18 08:04:35', '2026-08-18 17:17:14', 8.21, 'Regular daily log'),
(621, 56, 23, '2026-08-19', '2026-08-19 07:52:24', '2026-08-19 17:00:20', 8.13, 'Regular daily log'),
(622, 56, 23, '2026-08-20', '2026-08-20 08:04:26', '2026-08-20 17:11:13', 8.11, 'Regular daily log'),
(623, 56, 23, '2026-08-21', '2026-08-21 08:02:48', '2026-08-21 17:10:21', 8.13, 'Regular daily log'),
(624, 56, 23, '2026-08-24', '2026-08-24 08:03:20', '2026-08-24 17:15:40', 8.21, 'Regular daily log'),
(625, 56, 23, '2026-08-25', '2026-08-25 08:08:19', '2026-08-25 17:04:35', 7.94, 'Regular daily log'),
(626, 56, 23, '2026-08-26', '2026-08-26 07:58:01', '2026-08-26 17:06:19', 8.14, 'Regular daily log'),
(627, 56, 23, '2026-08-27', '2026-08-27 07:57:34', '2026-08-27 17:18:52', 8.36, 'Regular daily log'),
(628, 56, 23, '2026-08-28', '2026-08-28 08:01:39', '2026-08-28 17:01:41', 8.00, 'Regular daily log'),
(629, 56, 23, '2026-08-31', '2026-08-31 08:03:28', '2026-08-31 17:02:17', 7.98, 'Regular daily log'),
(630, 56, 23, '2026-09-01', '2026-09-01 08:01:03', '2026-09-01 17:08:23', 8.12, 'Regular daily log'),
(631, 56, 23, '2026-09-02', '2026-09-02 07:58:45', '2026-09-02 17:18:37', 8.33, 'Regular daily log'),
(632, 56, 23, '2026-09-03', '2026-09-03 07:56:50', '2026-09-03 17:18:21', 8.36, 'Regular daily log'),
(633, 56, 23, '2026-09-04', '2026-09-04 08:01:15', '2026-09-04 17:01:11', 8.00, 'Regular daily log'),
(634, 56, 23, '2026-09-07', '2026-09-07 08:02:11', '2026-09-07 17:17:21', 8.25, 'Regular daily log'),
(635, 56, 23, '2026-09-08', '2026-09-08 08:00:15', '2026-09-08 17:19:12', 8.32, 'Regular daily log'),
(636, 56, 23, '2026-09-09', '2026-09-09 07:55:16', '2026-09-09 17:08:44', 8.22, 'Regular daily log'),
(637, 56, 23, '2026-09-10', '2026-09-10 07:57:54', '2026-09-10 17:13:16', 8.26, 'Regular daily log'),
(638, 56, 23, '2026-09-11', '2026-09-11 07:52:41', '2026-09-11 17:13:37', 8.35, 'Regular daily log'),
(663, 57, 23, '2026-07-20', '2026-07-20 08:01:28', '2026-07-20 17:17:12', 8.26, 'Regular daily log'),
(664, 57, 23, '2026-07-21', '2026-07-21 08:01:39', '2026-07-21 17:06:36', 8.08, 'Regular daily log'),
(665, 57, 23, '2026-07-22', '2026-07-22 08:08:07', '2026-07-22 17:10:45', 8.04, 'Regular daily log'),
(666, 57, 23, '2026-07-23', '2026-07-23 08:09:26', '2026-07-23 17:04:55', 7.92, 'Regular daily log'),
(667, 57, 23, '2026-07-24', '2026-07-24 07:56:19', '2026-07-24 17:16:48', 8.34, 'Regular daily log'),
(668, 57, 23, '2026-07-27', '2026-07-27 07:55:04', '2026-07-27 17:14:56', 8.33, 'Regular daily log'),
(669, 57, 23, '2026-07-28', '2026-07-28 08:09:31', '2026-07-28 17:12:46', 8.05, 'Regular daily log'),
(670, 57, 23, '2026-07-29', '2026-07-29 07:55:17', '2026-07-29 17:08:08', 8.21, 'Regular daily log'),
(671, 57, 23, '2026-07-30', '2026-07-30 07:54:50', '2026-07-30 17:19:48', 8.42, 'Regular daily log'),
(672, 57, 23, '2026-07-31', '2026-07-31 07:54:27', '2026-07-31 17:02:54', 8.14, 'Regular daily log'),
(673, 57, 23, '2026-08-03', '2026-08-03 07:51:11', '2026-08-03 17:17:11', 8.43, 'Regular daily log'),
(674, 57, 23, '2026-08-04', '2026-08-04 07:52:21', '2026-08-04 17:00:16', 8.13, 'Regular daily log'),
(675, 57, 23, '2026-08-05', '2026-08-05 08:04:16', '2026-08-05 17:10:32', 8.10, 'Regular daily log'),
(676, 57, 23, '2026-08-06', '2026-08-06 07:59:55', '2026-08-06 17:17:57', 8.30, 'Regular daily log'),
(677, 57, 23, '2026-08-07', '2026-08-07 07:50:00', '2026-08-07 17:06:10', 8.27, 'Regular daily log'),
(678, 57, 23, '2026-08-10', '2026-08-10 08:00:50', '2026-08-10 17:15:42', 8.25, 'Regular daily log'),
(679, 57, 23, '2026-08-11', '2026-08-11 07:56:01', '2026-08-11 17:03:00', 8.12, 'Regular daily log'),
(680, 57, 23, '2026-08-12', '2026-08-12 08:06:58', '2026-08-12 17:15:51', 8.15, 'Regular daily log'),
(681, 57, 23, '2026-08-13', '2026-08-13 07:58:19', '2026-08-13 17:14:04', 8.26, 'Regular daily log'),
(682, 57, 23, '2026-08-14', '2026-08-14 07:55:24', '2026-08-14 17:04:47', 8.16, 'Regular daily log'),
(683, 57, 23, '2026-08-17', '2026-08-17 07:57:44', '2026-08-17 17:04:21', 8.11, 'Regular daily log'),
(684, 57, 23, '2026-08-18', '2026-08-18 08:08:31', '2026-08-18 17:19:33', 8.18, 'Regular daily log'),
(685, 57, 23, '2026-08-19', '2026-08-19 07:52:12', '2026-08-19 17:12:22', 8.34, 'Regular daily log'),
(686, 57, 23, '2026-08-20', '2026-08-20 08:05:15', '2026-08-20 17:19:10', 8.23, 'Regular daily log'),
(687, 57, 23, '2026-08-21', '2026-08-21 08:00:03', '2026-08-21 17:12:49', 8.21, 'Regular daily log'),
(688, 57, 23, '2026-08-24', '2026-08-24 08:03:53', '2026-08-24 17:11:00', 8.12, 'Regular daily log'),
(689, 57, 23, '2026-08-25', '2026-08-25 08:03:20', '2026-08-25 17:13:42', 8.17, 'Regular daily log'),
(690, 57, 23, '2026-08-26', '2026-08-26 07:58:31', '2026-08-26 17:01:27', 8.05, 'Regular daily log'),
(691, 57, 23, '2026-08-27', '2026-08-27 07:51:45', '2026-08-27 17:04:25', 8.21, 'Regular daily log'),
(692, 57, 23, '2026-08-28', '2026-08-28 08:06:49', '2026-08-28 17:10:53', 8.07, 'Regular daily log'),
(693, 57, 23, '2026-08-31', '2026-08-31 07:53:57', '2026-08-31 17:07:05', 8.22, 'Regular daily log'),
(694, 57, 23, '2026-09-01', '2026-09-01 07:53:35', '2026-09-01 17:16:43', 8.39, 'Regular daily log'),
(695, 57, 23, '2026-09-02', '2026-09-02 08:02:47', '2026-09-02 17:13:49', 8.18, 'Regular daily log'),
(696, 57, 23, '2026-09-03', '2026-09-03 08:00:43', '2026-09-03 17:12:11', 8.19, 'Regular daily log'),
(697, 57, 23, '2026-09-04', '2026-09-04 07:58:45', '2026-09-04 17:07:12', 8.14, 'Regular daily log'),
(698, 57, 23, '2026-09-07', '2026-09-07 07:59:44', '2026-09-07 17:07:07', 8.12, 'Regular daily log'),
(699, 57, 23, '2026-09-08', '2026-09-08 07:56:21', '2026-09-08 17:10:25', 8.23, 'Regular daily log'),
(700, 57, 23, '2026-09-09', '2026-09-09 08:03:04', '2026-09-09 17:14:03', 8.18, 'Regular daily log'),
(701, 57, 23, '2026-09-10', '2026-09-10 08:01:05', '2026-09-10 17:13:15', 8.20, 'Regular daily log'),
(702, 57, 23, '2026-09-11', '2026-09-11 08:03:01', '2026-09-11 17:05:22', 8.04, 'Regular daily log'),
(703, 57, 23, '2026-09-14', '2026-09-14 07:57:47', '2026-09-14 17:02:48', 8.08, 'Regular daily log'),
(726, 58, 23, '2026-07-20', '2026-07-20 08:07:40', '2026-07-20 17:06:48', 7.99, 'Regular daily log'),
(727, 58, 23, '2026-07-21', '2026-07-21 07:51:02', '2026-07-21 17:04:43', 8.23, 'Regular daily log'),
(728, 58, 23, '2026-07-22', '2026-07-22 07:50:30', '2026-07-22 17:08:22', 8.30, 'Regular daily log'),
(729, 58, 23, '2026-07-23', '2026-07-23 07:50:19', '2026-07-23 17:16:29', 8.44, 'Regular daily log'),
(730, 58, 23, '2026-07-24', '2026-07-24 07:51:29', '2026-07-24 17:18:00', 8.44, 'Regular daily log'),
(731, 58, 23, '2026-07-27', '2026-07-27 07:55:34', '2026-07-27 17:13:48', 8.30, 'Regular daily log'),
(732, 58, 23, '2026-07-28', '2026-07-28 08:02:21', '2026-07-28 17:00:23', 7.97, 'Regular daily log'),
(733, 58, 23, '2026-07-29', '2026-07-29 07:54:49', '2026-07-29 17:03:00', 8.14, 'Regular daily log'),
(734, 58, 23, '2026-07-30', '2026-07-30 07:50:31', '2026-07-30 17:13:36', 8.38, 'Regular daily log'),
(735, 58, 23, '2026-07-31', '2026-07-31 07:56:26', '2026-07-31 17:11:24', 8.25, 'Regular daily log'),
(736, 58, 23, '2026-08-03', '2026-08-03 08:07:42', '2026-08-03 17:14:17', 8.11, 'Regular daily log'),
(737, 58, 23, '2026-08-04', '2026-08-04 08:08:22', '2026-08-04 17:08:58', 8.01, 'Regular daily log'),
(738, 58, 23, '2026-08-05', '2026-08-05 07:59:44', '2026-08-05 17:01:46', 8.03, 'Regular daily log'),
(739, 58, 23, '2026-08-06', '2026-08-06 08:09:41', '2026-08-06 17:13:06', 8.06, 'Regular daily log'),
(740, 58, 23, '2026-08-07', '2026-08-07 07:56:30', '2026-08-07 17:13:09', 8.28, 'Regular daily log'),
(741, 58, 23, '2026-08-10', '2026-08-10 07:56:18', '2026-08-10 17:12:01', 8.26, 'Regular daily log'),
(742, 58, 23, '2026-08-11', '2026-08-11 07:51:14', '2026-08-11 17:10:07', 8.31, 'Regular daily log'),
(743, 58, 23, '2026-08-12', '2026-08-12 07:56:54', '2026-08-12 17:04:08', 8.12, 'Regular daily log'),
(744, 58, 23, '2026-08-13', '2026-08-13 07:50:00', '2026-08-13 17:07:38', 8.29, 'Regular daily log'),
(745, 58, 23, '2026-08-14', '2026-08-14 08:08:08', '2026-08-14 17:07:47', 7.99, 'Regular daily log'),
(746, 58, 23, '2026-08-17', '2026-08-17 07:54:33', '2026-08-17 17:19:24', 8.41, 'Regular daily log'),
(747, 58, 23, '2026-08-18', '2026-08-18 07:53:22', '2026-08-18 17:18:37', 8.42, 'Regular daily log'),
(748, 58, 23, '2026-08-19', '2026-08-19 07:53:02', '2026-08-19 17:19:19', 8.44, 'Regular daily log'),
(749, 58, 23, '2026-08-20', '2026-08-20 07:57:27', '2026-08-20 17:19:21', 8.37, 'Regular daily log'),
(750, 58, 23, '2026-08-21', '2026-08-21 08:04:22', '2026-08-21 17:13:49', 8.16, 'Regular daily log'),
(751, 58, 23, '2026-08-24', '2026-08-24 07:56:00', '2026-08-24 17:08:31', 8.21, 'Regular daily log'),
(752, 58, 23, '2026-08-25', '2026-08-25 07:54:38', '2026-08-25 17:17:37', 8.38, 'Regular daily log'),
(753, 58, 23, '2026-08-26', '2026-08-26 08:04:10', '2026-08-26 17:17:59', 8.23, 'Regular daily log'),
(754, 58, 23, '2026-08-27', '2026-08-27 07:57:25', '2026-08-27 17:03:10', 8.10, 'Regular daily log'),
(755, 58, 23, '2026-08-28', '2026-08-28 08:03:34', '2026-08-28 17:18:22', 8.25, 'Regular daily log'),
(756, 58, 23, '2026-08-31', '2026-08-31 08:01:10', '2026-08-31 17:00:44', 7.99, 'Regular daily log'),
(757, 58, 23, '2026-09-01', '2026-09-01 08:00:09', '2026-09-01 17:08:34', 8.14, 'Regular daily log'),
(758, 58, 23, '2026-09-02', '2026-09-02 08:02:25', '2026-09-02 17:16:21', 8.23, 'Regular daily log'),
(759, 58, 23, '2026-09-03', '2026-09-03 07:54:32', '2026-09-03 17:13:36', 8.32, 'Regular daily log'),
(760, 58, 23, '2026-09-04', '2026-09-04 08:04:23', '2026-09-04 17:11:10', 8.11, 'Regular daily log'),
(761, 58, 23, '2026-09-07', '2026-09-07 08:02:42', '2026-09-07 17:09:59', 8.12, 'Regular daily log'),
(762, 58, 23, '2026-09-08', '2026-09-08 08:01:48', '2026-09-08 17:09:05', 8.12, 'Regular daily log'),
(763, 58, 23, '2026-09-09', '2026-09-09 08:00:03', '2026-09-09 17:02:58', 8.05, 'Regular daily log'),
(764, 58, 23, '2026-09-10', '2026-09-10 07:54:41', '2026-09-10 17:14:34', 8.33, 'Regular daily log'),
(765, 58, 23, '2026-09-11', '2026-09-11 08:08:47', '2026-09-11 17:10:13', 8.02, 'Regular daily log'),
(766, 58, 23, '2026-09-14', '2026-09-14 08:04:46', '2026-09-14 17:03:11', 7.97, 'Regular daily log'),
(789, 32, 23, '2026-07-13', '2026-07-13 08:06:17', '2026-07-13 17:12:12', 8.10, 'Regular daily log'),
(790, 32, 23, '2026-07-14', '2026-07-14 08:02:11', '2026-07-14 17:04:17', 8.04, 'Regular daily log'),
(791, 32, 23, '2026-07-15', '2026-07-15 07:54:53', '2026-07-15 17:11:33', 8.28, 'Regular daily log'),
(792, 32, 23, '2026-07-16', '2026-07-16 07:53:08', '2026-07-16 17:01:00', 8.13, 'Regular daily log'),
(793, 32, 23, '2026-07-17', '2026-07-17 08:05:37', '2026-07-17 17:15:06', 8.16, 'Regular daily log'),
(794, 45, 23, '2026-07-13', '2026-07-13 07:58:40', '2026-07-13 17:18:02', 8.32, 'Regular daily log'),
(795, 45, 23, '2026-07-14', '2026-07-14 07:54:12', '2026-07-14 17:06:53', 8.21, 'Regular daily log'),
(796, 45, 23, '2026-07-15', '2026-07-15 07:51:48', '2026-07-15 17:08:22', 8.28, 'Regular daily log'),
(797, 45, 23, '2026-07-16', '2026-07-16 08:06:27', '2026-07-16 17:17:10', 8.18, 'Regular daily log'),
(798, 45, 23, '2026-07-17', '2026-07-17 08:06:30', '2026-07-17 17:11:00', 8.08, 'Regular daily log'),
(799, 49, 23, '2026-07-13', '2026-07-13 07:55:29', '2026-07-13 17:14:27', 8.32, 'Regular daily log'),
(800, 49, 23, '2026-07-14', '2026-07-14 08:05:47', '2026-07-14 17:15:36', 8.16, 'Regular daily log'),
(801, 49, 23, '2026-07-15', '2026-07-15 08:00:40', '2026-07-15 17:06:31', 8.10, 'Regular daily log'),
(802, 49, 23, '2026-07-16', '2026-07-16 07:50:36', '2026-07-16 17:03:28', 8.21, 'Regular daily log'),
(803, 49, 23, '2026-07-17', '2026-07-17 08:05:31', '2026-07-17 17:07:11', 8.03, 'Regular daily log'),
(804, 50, 23, '2026-07-13', '2026-07-13 07:59:23', '2026-07-13 17:05:23', 8.10, 'Regular daily log'),
(805, 50, 23, '2026-07-14', '2026-07-14 08:08:48', '2026-07-14 17:17:51', 8.15, 'Regular daily log'),
(806, 50, 23, '2026-07-15', '2026-07-15 08:02:51', '2026-07-15 17:10:44', 8.13, 'Regular daily log'),
(807, 50, 23, '2026-07-16', '2026-07-16 08:05:08', '2026-07-16 17:03:25', 7.97, 'Regular daily log'),
(808, 50, 23, '2026-07-17', '2026-07-17 08:01:45', '2026-07-17 17:08:29', 8.11, 'Regular daily log'),
(809, 51, 23, '2026-07-13', '2026-07-13 07:57:12', '2026-07-13 17:10:32', 8.22, 'Regular daily log'),
(810, 51, 23, '2026-07-14', '2026-07-14 08:01:07', '2026-07-14 17:03:57', 8.05, 'Regular daily log'),
(811, 51, 23, '2026-07-15', '2026-07-15 07:56:24', '2026-07-15 17:00:09', 8.06, 'Regular daily log'),
(812, 51, 23, '2026-07-16', '2026-07-16 07:51:32', '2026-07-16 17:07:17', 8.26, 'Regular daily log'),
(813, 51, 23, '2026-07-17', '2026-07-17 08:01:47', '2026-07-17 17:17:07', 8.26, 'Regular daily log');
INSERT INTO `time_logs` (`id`, `intern_id`, `coordinator_id`, `log_date`, `time_in`, `time_out`, `total_hours`, `notes`) VALUES
(814, 52, 23, '2026-07-13', '2026-07-13 08:00:12', '2026-07-13 17:19:42', 8.33, 'Regular daily log'),
(815, 52, 23, '2026-07-14', '2026-07-14 07:57:55', '2026-07-14 17:00:27', 8.04, 'Regular daily log'),
(816, 52, 23, '2026-07-15', '2026-07-15 08:08:32', '2026-07-15 17:11:17', 8.05, 'Regular daily log'),
(817, 52, 23, '2026-07-16', '2026-07-16 07:50:51', '2026-07-16 17:10:27', 8.33, 'Regular daily log'),
(818, 52, 23, '2026-07-17', '2026-07-17 07:59:39', '2026-07-17 17:16:56', 8.29, 'Regular daily log'),
(819, 53, 23, '2026-07-13', '2026-07-13 08:05:42', '2026-07-13 17:07:41', 8.03, 'Regular daily log'),
(820, 53, 23, '2026-07-14', '2026-07-14 08:01:23', '2026-07-14 17:13:50', 8.21, 'Regular daily log'),
(821, 53, 23, '2026-07-15', '2026-07-15 08:05:04', '2026-07-15 17:13:48', 8.15, 'Regular daily log'),
(822, 53, 23, '2026-07-16', '2026-07-16 07:53:50', '2026-07-16 17:17:45', 8.40, 'Regular daily log'),
(823, 53, 23, '2026-07-17', '2026-07-17 08:07:16', '2026-07-17 17:13:05', 8.10, 'Regular daily log'),
(824, 54, 23, '2026-07-13', '2026-07-13 08:03:36', '2026-07-13 17:08:48', 8.09, 'Regular daily log'),
(825, 54, 23, '2026-07-14', '2026-07-14 07:53:12', '2026-07-14 17:09:36', 8.27, 'Regular daily log'),
(826, 54, 23, '2026-07-15', '2026-07-15 08:08:24', '2026-07-15 17:03:15', 7.91, 'Regular daily log'),
(827, 54, 23, '2026-07-16', '2026-07-16 07:51:02', '2026-07-16 17:15:24', 8.41, 'Regular daily log'),
(828, 54, 23, '2026-07-17', '2026-07-17 08:03:57', '2026-07-17 17:03:32', 7.99, 'Regular daily log'),
(829, 55, 23, '2026-07-13', '2026-07-13 08:05:50', '2026-07-13 17:08:35', 8.05, 'Regular daily log'),
(830, 55, 23, '2026-07-14', '2026-07-14 08:05:27', '2026-07-14 17:11:29', 8.10, 'Regular daily log'),
(831, 55, 23, '2026-07-15', '2026-07-15 08:01:06', '2026-07-15 17:01:04', 8.00, 'Regular daily log'),
(832, 55, 23, '2026-07-16', '2026-07-16 08:02:01', '2026-07-16 17:16:53', 8.25, 'Regular daily log'),
(833, 55, 23, '2026-07-17', '2026-07-17 07:58:25', '2026-07-17 17:11:25', 8.22, 'Regular daily log'),
(834, 56, 23, '2026-07-13', '2026-07-13 08:01:50', '2026-07-13 17:04:58', 8.05, 'Regular daily log'),
(835, 56, 23, '2026-07-14', '2026-07-14 07:59:19', '2026-07-14 17:11:42', 8.21, 'Regular daily log'),
(836, 56, 23, '2026-07-15', '2026-07-15 08:00:33', '2026-07-15 17:17:41', 8.29, 'Regular daily log'),
(837, 56, 23, '2026-07-16', '2026-07-16 08:06:45', '2026-07-16 17:10:45', 8.07, 'Regular daily log'),
(838, 56, 23, '2026-07-17', '2026-07-17 07:53:29', '2026-07-17 17:05:11', 8.20, 'Regular daily log'),
(839, 57, 23, '2026-07-13', '2026-07-13 08:05:26', '2026-07-13 17:01:41', 7.94, 'Regular daily log'),
(840, 57, 23, '2026-07-14', '2026-07-14 07:52:05', '2026-07-14 17:05:23', 8.22, 'Regular daily log'),
(841, 57, 23, '2026-07-15', '2026-07-15 07:50:40', '2026-07-15 17:07:11', 8.28, 'Regular daily log'),
(842, 57, 23, '2026-07-16', '2026-07-16 08:03:56', '2026-07-16 17:08:08', 8.07, 'Regular daily log'),
(843, 57, 23, '2026-07-17', '2026-07-17 08:08:53', '2026-07-17 17:10:00', 8.02, 'Regular daily log'),
(844, 58, 23, '2026-07-13', '2026-07-13 08:03:23', '2026-07-13 17:16:57', 8.23, 'Regular daily log'),
(845, 58, 23, '2026-07-14', '2026-07-14 07:54:33', '2026-07-14 17:11:57', 8.29, 'Regular daily log'),
(846, 58, 23, '2026-07-15', '2026-07-15 07:56:06', '2026-07-15 17:14:41', 8.31, 'Regular daily log'),
(847, 58, 23, '2026-07-16', '2026-07-16 08:05:06', '2026-07-16 17:11:29', 8.11, 'Regular daily log'),
(848, 58, 23, '2026-07-17', '2026-07-17 08:02:05', '2026-07-17 17:05:58', 8.06, 'Regular daily log'),
(852, 57, 23, '2026-09-15', '2026-09-15 09:08:57', '2026-09-15 09:09:01', 0.00, NULL),
(853, 49, 23, '2026-09-15', '2026-09-15 10:09:00', '2026-09-15 10:09:46', 0.01, NULL),
(854, 32, 23, '2026-09-14', '2026-09-14 09:00:00', '2026-09-14 17:05:00', 8.08, '');

--
-- Triggers `time_logs`
--
DELIMITER $$
CREATE TRIGGER `trg_timelogs_after_delete` AFTER DELETE ON `time_logs` FOR EACH ROW BEGIN
    CALL RecalculateInternshipTotals(OLD.intern_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_timelogs_after_insert` AFTER INSERT ON `time_logs` FOR EACH ROW BEGIN
    CALL RecalculateInternshipTotals(NEW.intern_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_timelogs_after_update` AFTER UPDATE ON `time_logs` FOR EACH ROW BEGIN
    -- Recalculate for the intern
    CALL RecalculateInternshipTotals(NEW.intern_id);
    
    -- If the log was reassigned to a different intern ID, update the old intern too
    IF OLD.intern_id <> NEW.intern_id THEN
        CALL RecalculateInternshipTotals(OLD.intern_id);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('intern','coordinator','admin') NOT NULL DEFAULT 'intern',
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `verification_code` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `is_active`, `created_at`, `updated_at`, `verification_code`) VALUES
(1, 'Administrator', 'admin@citas.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, '2026-04-27 18:27:44', NULL, NULL),
(23, 'Prescilla Catalan', 'citas.internship@gmail.com', '$2y$12$NVBt3Er1NZ40BzAUkz72/Osuf1nacc6tEPyRWVj5RX3q.QWCxLG6m', 'coordinator', 1, '2026-05-01 14:01:03', '2026-10-10 15:04:48', NULL),
(32, 'Mark Francis L. Albos', 'soblamark@gmail.com', '$2y$10$aRV9jKLOqZsYT7D7MhL/4uvaZXXGJTbJFqkxHzK/2rv42NP7o9TOC', 'intern', 1, '2026-05-01 17:35:05', '2026-10-10 22:57:09', NULL),
(38, 'Prescilla Catalan', 'pcatalan.it101@gmail.com', '$2y$10$NQ33cl96aX79fH6sUBFcDebXtw5ae6VDMWIj5ZF4pLN05Pds3InoS', 'coordinator', 1, '2026-05-20 09:49:36', '2026-05-20 09:50:29', NULL),
(45, 'Juan Dela Cruz', 'juan23@gmail.com', '$2y$10$/0apbXIEymhm1B3x4uEVEe8GN6jk2pKcx4xk0m28ErWlm4vp9KdlW', 'intern', 1, '2026-09-07 01:33:03', '2026-09-13 17:14:47', NULL),
(46, 'Prescilla Francia Catalan', 'kdramemail@gmail.com', '$2y$10$UaBU0eY442OclzcJEy6xi.k3Ufg4tBpgLA5UCbesYiGbfmNIed8ge', 'coordinator', 1, '2026-09-10 22:32:38', '2026-09-13 15:20:01', NULL),
(48, 'ADMIN', 'admin.citas.internship@gmail.com', '$2y$10$0JICDIBgBaN/F7BYgtOmN.KM3.fDGZfoufu/AyT6dxJ7eAiaH3txm', 'admin', 1, '2026-09-10 22:49:37', '2026-10-10 15:04:27', NULL),
(49, 'Eric E. Dacuma', 'ericdacuma69@gmail.com', '$2y$10$XCPiFiDr2FQLjJprKxe2uO24KsJgwK7T7wEvSMVk0AKIjrgLPT6Ue', 'intern', 1, '2026-09-11 09:44:30', '2026-09-13 15:54:52', NULL),
(50, 'Mark Anthony Reyes', 'mark.reyes@samar.edu.ph', '$2y$10$FQlPcU6l6WtI91/5ndoFpe3rZC/LFHmeOg8A0TKCFjSTmobRpJL0S', 'intern', 1, '2026-09-13 17:11:32', '2026-09-13 17:14:46', NULL),
(51, 'Maria Kristina Santos', 'mariakristina.santos@samar.edu.ph', '$2y$10$UxG/hxn/PFOi2XvxX3rjQOhg9jAoRrhY3Bd2KlqPHEpHX4zNKJ3r.', 'intern', 1, '2026-09-13 17:12:05', '2026-09-13 17:14:45', NULL),
(52, 'John Paul Mendoza', 'johnpaul.mendoza@samar.edu.ph', '$2y$10$mUceMwGHr.BMVPfAvm1wDOj3Z83A6sn7lKCRNy7IgzA5MAKwv6Lrm', 'intern', 1, '2026-09-13 17:13:01', '2026-09-13 17:14:43', NULL),
(53, 'Angela Nicole Dela Cruz', 'angela.delacruz@samar.edu.ph', '$2y$10$zo/9PQnxb8vi.iZ3ctrO6OVkBTx2PsfI3YrMOPr/WHsu7mF1AX31W', 'intern', 1, '2026-09-13 17:13:36', '2026-09-13 17:14:42', NULL),
(54, 'Joshua Garcia', 'joshua.garcia@samar.edu.ph', '$2y$10$8GQCiVlLxw3AuNctCG541uM80ZxhEvsdkOSqJUPc35twbQXUMpthC', 'intern', 1, '2026-09-13 17:14:28', '2026-09-13 17:14:40', NULL),
(55, 'Jennifer Rose Fernandez', 'jennifer.fernandez@gmail.com', '$2y$10$B2boTSq3KR.qvaJr.zhflu7xcTveSnsEhaydAgJQeRTrscUv.hbwi', 'intern', 1, '2026-09-13 23:18:59', '2026-09-13 23:31:55', NULL),
(56, 'Paolo Miguel Castillo', 'paolo.castillo@gmail.com', '$2y$10$aIo24MXJALXN9cPdAbvKse4jD8nlKui0.qCER.Sv.mNykOiWFnb6C', 'intern', 1, '2026-09-13 23:25:58', '2026-09-13 23:31:28', NULL),
(57, 'Sanico, Junnie P.', 'junniesanico35@gmail.com', '$2y$10$fCPhhAQpDyKtROi17t42Euj8fHlnXj.tGizOPVofJQRJtobwGfIYG', 'intern', 1, '2026-09-14 09:58:32', '2026-09-14 10:26:46', NULL),
(58, 'Jofel Baclayo Casiño', 'jofelcasino52@gmail.com', '$2y$10$M5mMVj90SvSL4eICZM1ygeaE95wEbUooj0KM.MKKp9q4pRuGqLDf2', 'intern', 1, '2026-09-14 11:14:13', '2026-09-14 12:34:42', NULL),
(59, 'merk merk', 'merc@gmail.com', '$2y$10$mnviS1nzgPXMhrdjWipbxuMORRuINFm3y.jfWmzdpl3ByEuHUNqCe', 'intern', 0, '2026-10-10 20:05:52', NULL, NULL),
(61, 'Mark Fr bleh', 'markfrancisalbos26@gmail.com', '$2y$10$eSbV2KEpK8yvVltHm6C07.5zcJquA9zS.gkuvyzKY5qsAJ/fC.sTy', 'intern', 0, '2026-10-10 23:27:42', NULL, '341965');

-- --------------------------------------------------------

--
-- Table structure for table `weekly_reports`
--

CREATE TABLE `weekly_reports` (
  `id` int(11) NOT NULL,
  `intern_id` int(11) NOT NULL,
  `week_label` varchar(80) NOT NULL,
  `week_start` date NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','reviewed','approved','revision') NOT NULL DEFAULT 'pending',
  `feedback` text DEFAULT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `coordinator_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `weekly_reports`
--

INSERT INTO `weekly_reports` (`id`, `intern_id`, `week_label`, `week_start`, `description`, `status`, `feedback`, `uploaded_at`, `reviewed_at`, `reviewed_by`, `coordinator_id`) VALUES
(33, 52, 'Week 1', '2026-07-13', NULL, 'approved', NULL, '2026-09-14 20:58:17', '2026-09-15 09:19:43', 23, 23),
(34, 52, 'week 2', '2026-07-20', NULL, 'approved', NULL, '2026-09-14 20:58:50', '2026-09-15 09:19:48', 23, 23),
(35, 52, 'week 3', '2026-09-14', NULL, 'approved', NULL, '2026-09-14 20:59:08', '2026-09-15 09:19:53', 23, 23),
(36, 52, 'wwek 4', '2026-09-14', NULL, 'approved', NULL, '2026-09-14 21:00:47', '2026-09-15 09:19:57', 23, 23),
(37, 52, 'week 5', '2026-09-14', NULL, 'approved', NULL, '2026-09-14 21:01:54', '2026-09-14 23:41:25', 23, 23),
(38, 32, 'week 1', '2026-07-13', NULL, 'approved', NULL, '2026-09-14 21:03:01', '2026-09-14 23:41:09', 23, 23),
(39, 32, 'week 2', '2026-07-20', NULL, 'approved', NULL, '2026-09-14 21:03:55', '2026-09-14 22:04:16', 23, 23),
(40, 52, 'week 7', '2026-09-14', NULL, 'approved', NULL, '2026-09-14 21:34:31', '2026-09-14 22:03:58', 23, 23),
(42, 32, 'week 3', '2026-09-14', NULL, 'approved', NULL, '2026-09-15 09:14:39', '2026-09-15 09:20:01', 23, 23),
(43, 32, 'week 4', '2026-09-14', NULL, 'approved', NULL, '2026-09-15 09:14:52', '2026-09-15 09:20:06', 23, 23),
(44, 32, 'week 5', '2026-09-14', NULL, 'approved', NULL, '2026-09-15 09:15:07', '2026-09-15 09:20:13', 23, 23),
(45, 52, 'week 6', '2026-08-17', NULL, 'approved', NULL, '2026-09-15 09:18:05', '2026-09-15 09:20:17', 23, 23),
(46, 52, 'week 7', '2026-08-24', NULL, 'approved', NULL, '2026-09-15 09:18:29', '2026-09-15 09:20:22', 23, 23),
(47, 52, 'week 8', '2026-08-31', NULL, 'approved', NULL, '2026-09-15 09:18:54', '2026-09-15 09:20:31', 23, 23),
(48, 52, 'week 9', '2026-09-07', NULL, 'pending', NULL, '2026-09-15 09:19:19', NULL, NULL, 23),
(49, 32, 'week 6', '2026-08-17', NULL, 'approved', NULL, '2026-09-15 09:36:36', '2026-09-15 09:37:14', 23, 23),
(50, 32, 'week 7', '2026-09-14', NULL, 'approved', NULL, '2026-09-15 09:36:54', '2026-09-15 09:38:33', 23, 23),
(51, 32, 'week 8', '2026-08-14', NULL, 'approved', NULL, '2026-09-15 09:38:17', '2026-09-15 10:16:07', 23, 23),
(52, 32, 'week 9', '2026-09-25', 'hello the changes made were', 'pending', 'please edit the details regarding fe', '2026-09-21 18:47:49', '2026-09-21 19:09:37', 23, 23),
(53, 32, 'week 12', '2026-09-28', 'test', 'pending', NULL, '2026-10-02 22:20:29', NULL, NULL, 23),
(54, 45, 'week 1', '2026-10-05', NULL, 'approved', NULL, '2026-10-09 22:11:22', '2026-10-09 22:16:06', 23, 23);

--
-- Triggers `weekly_reports`
--
DELIMITER $$
CREATE TRIGGER `trg_after_weekly_report_delete` AFTER DELETE ON `weekly_reports` FOR EACH ROW BEGIN
    UPDATE internships
    SET reports_submitted = (
        SELECT COUNT(*) 
        FROM weekly_reports 
        WHERE intern_id = OLD.intern_id
    )
    WHERE intern_id = OLD.intern_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_after_weekly_report_insert` AFTER INSERT ON `weekly_reports` FOR EACH ROW BEGIN
    UPDATE internships
    SET reports_submitted = (
        SELECT COUNT(*) 
        FROM weekly_reports 
        WHERE intern_id = NEW.intern_id
    )
    WHERE intern_id = NEW.intern_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `weekly_report_files`
--

CREATE TABLE `weekly_report_files` (
  `id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `file_path` varchar(300) NOT NULL,
  `file_name` varchar(200) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `weekly_report_files`
--

INSERT INTO `weekly_report_files` (`id`, `report_id`, `file_path`, `file_name`, `file_size`, `mime_type`, `uploaded_at`) VALUES
(35, 33, 'reports/52/rpt_9b120b41f1be1164b91e2358c63b0d19.docx', 'REport Sample 1.docx', 10151, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '2026-09-14 20:58:18'),
(36, 34, 'reports/52/rpt_c6598a356dbd38f25fb1f2ef72dbe076.docx', 'weekly report 2.docx', 12263, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '2026-09-14 20:58:52'),
(37, 35, 'reports/52/rpt_7419ec0774489466aa0d4392a3198c7a.docx', 'weekly report 3.docx', 12265, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '2026-09-14 20:59:09'),
(38, 36, 'reports/52/rpt_40b1975233eb7447c15e18e4b0cc6386.docx', 'weekly report 4.docx', 12284, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '2026-09-14 21:00:48'),
(39, 37, 'reports/52/rpt_1602873a196e9b24d0540b071d9c8f58.docx', 'weekly report .docx', 12309, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '2026-09-14 21:01:55'),
(40, 38, 'reports/32/rpt_152a03b5403dba73d17584c63fb3cf46.docx', 'weekly report .docx', 12309, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '2026-09-14 21:03:01'),
(41, 39, 'reports/32/rpt_bb5b47f290fa89527356d2e1a1d8e82d.docx', 'weekly report 2.docx', 12263, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '2026-09-14 21:03:55'),
(42, 40, 'reports/52/rpt_25315798f7203085a9d5145721c172b1.docx', 'rpt_bb5b47f290fa89527356d2e1a1d8e82d.docx', 12263, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '2026-09-14 21:34:31'),
(43, 40, 'reports/52/rpt_a029bba81b9fb2445fba6acbc2a734fb.docx', 'weekly report .docx', 12309, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '2026-09-14 21:34:31'),
(45, 42, 'reports/32/rpt_e28dccd362ee51cdc5ff35551eb30c81.docx', 'weekly report 3.docx', 12265, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '2026-09-15 09:14:40'),
(46, 43, 'reports/32/rpt_28518859666aa6eea1658856957508c1.docx', 'weekly report 4.docx', 12284, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '2026-09-15 09:14:52'),
(47, 44, 'reports/32/rpt_8ee1c0ffbb607cf1c2bd68f96b93bb95.docx', 'weekly report .docx', 12309, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '2026-09-15 09:15:08'),
(48, 45, 'reports/52/rpt_ce327d5310d34b5fc637c7a8fab0bc03.docx', 'weekly report .docx', 12309, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '2026-09-15 09:18:05'),
(49, 46, 'reports/52/rpt_ebd2f47914cb0fbe2f0d919c9e931364.docx', 'weekly report .docx', 12309, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '2026-09-15 09:18:29'),
(50, 47, 'reports/52/rpt_f1198b200811fad37a266bb3e59d7d92.docx', 'weekly report .docx', 12309, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '2026-09-15 09:18:54'),
(51, 48, 'reports/52/rpt_e0fa04de0fdd455e222628611c3ab94a.docx', 'weekly report .docx', 12309, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '2026-09-15 09:19:20'),
(52, 49, 'reports/32/rpt_a64b24ba0bcacacf9857ca37be6e6246.docx', 'weekly report .docx', 12309, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '2026-09-15 09:36:36'),
(53, 50, 'reports/32/rpt_2f09d27b35075b19f28414d0289a97f6.docx', 'weekly report .docx', 12309, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '2026-09-15 09:36:55'),
(54, 51, 'reports/32/rpt_12656d0e053875858d9063b1920a0af8.docx', 'weekly report .docx', 12309, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '2026-09-15 09:38:18'),
(57, 52, 'reports/32/rpt_1d4434bba731216104e0a70897bf395d.docx', 'DLP t2 w2 oct-2.docx', 71381, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '2026-10-02 22:38:21'),
(59, 53, 'reports/32/rpt_12fb21d27023c0a77b82eaf9793d1f48.docx', 'DLP t2 w2 oct-1.docx', 374213, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '2026-10-04 13:28:00'),
(60, 54, 'reports/45/rpt_402461ce4e289aa279727f3ac4a3570f.docx', 'weekly report .docx', 12309, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '2026-10-09 22:11:22');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ann_coordinator` (`coordinator_id`),
  ADD KEY `idx_announcements_pinned_date` (`is_pinned` DESC,`created_at` DESC);

--
-- Indexes for table `announcement_comments`
--
ALTER TABLE `announcement_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ac_user` (`user_id`),
  ADD KEY `idx_ac_announcement` (`announcement_id`,`created_at`),
  ADD KEY `idx_ac_parent` (`parent_id`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `coordinator_weekly_hours_summary`
--
ALTER TABLE `coordinator_weekly_hours_summary`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_coordinator_week` (`coordinator_id`,`week_start`);

--
-- Indexes for table `internships`
--
ALTER TABLE `internships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_internship_company` (`company_id`),
  ADD KEY `idx_internships_intern` (`intern_id`),
  ADD KEY `idx_internships_school_year` (`school_year`),
  ADD KEY `idx_internships_reviewed` (`is_profile_reviewed`);

--
-- Indexes for table `intern_documents`
--
ALTER TABLE `intern_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_doc_intern` (`intern_id`),
  ADD KEY `idx_doc_status` (`status`),
  ADD KEY `fk_docs_coordinator_user` (`coordinator_id`);

--
-- Indexes for table `intern_profiles`
--
ALTER TABLE `intern_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_intern_profiles_user` (`user_id`),
  ADD KEY `fk_iprofile_coordinator` (`coordinator_id`);

--
-- Indexes for table `time_logs`
--
ALTER TABLE `time_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_time_logs_intern_date` (`intern_id`,`log_date`),
  ADD KEY `idx_time_logs_intern_date` (`intern_id`,`log_date` DESC),
  ADD KEY `fk_tlog_coordinator` (`coordinator_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`);

--
-- Indexes for table `weekly_reports`
--
ALTER TABLE `weekly_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_wrpt_reviewer` (`reviewed_by`),
  ADD KEY `idx_weekly_reports_intern` (`intern_id`,`week_start` DESC),
  ADD KEY `idx_weekly_reports_status` (`status`),
  ADD KEY `idx_weekly_reports_coordinator` (`coordinator_id`);

--
-- Indexes for table `weekly_report_files`
--
ALTER TABLE `weekly_report_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rfiles_report` (`report_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `announcement_comments`
--
ALTER TABLE `announcement_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `coordinator_weekly_hours_summary`
--
ALTER TABLE `coordinator_weekly_hours_summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `internships`
--
ALTER TABLE `internships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `intern_documents`
--
ALTER TABLE `intern_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `intern_profiles`
--
ALTER TABLE `intern_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `time_logs`
--
ALTER TABLE `time_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=855;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `weekly_reports`
--
ALTER TABLE `weekly_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `weekly_report_files`
--
ALTER TABLE `weekly_report_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `fk_ann_coordinator` FOREIGN KEY (`coordinator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `announcement_comments`
--
ALTER TABLE `announcement_comments`
  ADD CONSTRAINT `fk_ac_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ac_parent` FOREIGN KEY (`parent_id`) REFERENCES `announcement_comments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ac_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `internships`
--
ALTER TABLE `internships`
  ADD CONSTRAINT `fk_internship_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `fk_internship_intern` FOREIGN KEY (`intern_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `intern_documents`
--
ALTER TABLE `intern_documents`
  ADD CONSTRAINT `fk_docs_coordinator_user` FOREIGN KEY (`coordinator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_docs_intern_user` FOREIGN KEY (`intern_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `intern_profiles`
--
ALTER TABLE `intern_profiles`
  ADD CONSTRAINT `fk_iprofile_coordinator` FOREIGN KEY (`coordinator_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_iprofile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `time_logs`
--
ALTER TABLE `time_logs`
  ADD CONSTRAINT `fk_tlog_coordinator` FOREIGN KEY (`coordinator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tlog_intern` FOREIGN KEY (`intern_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `weekly_reports`
--
ALTER TABLE `weekly_reports`
  ADD CONSTRAINT `fk_wrpt_intern` FOREIGN KEY (`intern_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_wrpt_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `weekly_report_files`
--
ALTER TABLE `weekly_report_files`
  ADD CONSTRAINT `fk_rfile_report` FOREIGN KEY (`report_id`) REFERENCES `weekly_reports` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
