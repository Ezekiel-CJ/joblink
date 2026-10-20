-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql309.infinityfree.com
-- Generation Time: Apr 26, 2026 at 11:47 AM
-- Server version: 11.4.10-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_41752523_joblinklocal_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `jobseeker_id` int(11) NOT NULL,
  `cover_letter` text DEFAULT NULL,
  `resume_path` varchar(500) DEFAULT NULL,
  `status` enum('pending','reviewed','shortlisted','accepted','rejected') DEFAULT 'pending',
  `employer_notes` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity` varchar(100) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL,
  `recipient_email` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `template` varchar(100) DEFAULT NULL,
  `status` enum('sent','failed','queued') DEFAULT 'queued',
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `email_queue`
--

CREATE TABLE `email_queue` (
  `id` int(11) NOT NULL,
  `to_email` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `body` text NOT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `attempts` int(11) DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employer_profiles`
--

CREATE TABLE `employer_profiles` (
  `user_id` int(11) NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `business_type` varchar(100) DEFAULT NULL,
  `business_address` text DEFAULT NULL,
  `business_permit_no` varchar(100) DEFAULT NULL,
  `logo_path` varchar(500) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `verification_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `verification_reason` text DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `employer_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `requirements` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `salary_min` decimal(10,2) DEFAULT NULL,
  `salary_max` decimal(10,2) DEFAULT NULL,
  `employment_type` enum('full-time','part-time','contract','internship','remote') DEFAULT 'full-time',
  `required_skills` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','closed') DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `views_count` int(11) DEFAULT 0,
  `applications_count` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobseeker_profiles`
--

CREATE TABLE `jobseeker_profiles` (
  `user_id` int(11) NOT NULL,
  `bio` text DEFAULT NULL,
  `education` text DEFAULT NULL,
  `experience` text DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `resume_path` varchar(500) DEFAULT NULL,
  `desired_salary_min` decimal(10,2) DEFAULT NULL,
  `desired_salary_max` decimal(10,2) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_categories`
--

CREATE TABLE `job_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `job_categories`
--

INSERT INTO `job_categories` (`id`, `name`, `description`, `icon`, `sort_order`) VALUES
(1, 'Information Technology', 'Software development, IT support, networking', NULL, 1),
(2, 'Administration', 'Office management, clerical, secretarial', NULL, 2),
(3, 'Healthcare', 'Medical, nursing, caregiving', NULL, 3),
(4, 'Education', 'Teaching, training, academic support', NULL, 4),
(5, 'Construction', 'Building, engineering, skilled trades', NULL, 5),
(6, 'Food Service', 'Restaurants, catering, hospitality', NULL, 6),
(7, 'Sales & Marketing', 'Retail, advertising, business development', NULL, 7),
(8, 'Agriculture', 'Farming, fisheries, agribusiness', NULL, 8),
(9, 'Transport & Logistics', 'Driving, delivery, warehousing', NULL, 9),
(10, 'Manufacturing', 'Production, assembly, quality control', NULL, 10);

-- --------------------------------------------------------

--
-- Table structure for table `saved_jobs`
--

CREATE TABLE `saved_jobs` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `jobseeker_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `skills`
--

CREATE TABLE `skills` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(100) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `skills`
--

INSERT INTO `skills` (`id`, `name`, `category`) VALUES
(1, 'JavaScript', 'IT'),
(2, 'Python', 'IT'),
(3, 'PHP', 'IT'),
(4, 'SQL', 'IT'),
(5, 'HTML/CSS', 'IT'),
(6, 'React', 'IT'),
(7, 'Node.js', 'IT'),
(8, 'Customer Service', 'Soft Skills'),
(9, 'Communication', 'Soft Skills'),
(10, 'Leadership', 'Soft Skills'),
(11, 'Team Management', 'Soft Skills'),
(12, 'MS Office', 'Administration'),
(13, 'Bookkeeping', 'Administration'),
(14, 'Nursing', 'Healthcare'),
(15, 'First Aid', 'Healthcare'),
(16, 'Teaching', 'Education'),
(17, 'Curriculum Development', 'Education'),
(18, 'Carpentry', 'Construction'),
(19, 'Welding', 'Construction'),
(20, 'Cooking', 'Food Service'),
(21, 'Food Safety', 'Food Service'),
(22, 'Sales', 'Sales'),
(23, 'Digital Marketing', 'Sales'),
(24, 'Driving', 'Transport'),
(25, 'Inventory Management', 'Logistics');

-- --------------------------------------------------------

--
-- Table structure for table `system_config`
--

CREATE TABLE `system_config` (
  `config_key` varchar(100) NOT NULL,
  `config_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `system_config`
--

INSERT INTO `system_config` (`config_key`, `config_value`, `description`, `updated_at`) VALUES
('site_name', 'JobLinkLocal', 'Website name', '2026-04-26 15:16:31'),
('site_email', 'noreply@joblinklocal.com', 'Admin email for notifications', '2026-04-26 15:16:31'),
('min_match_score', '30', 'Minimum match score for job recommendations', '2026-04-26 15:16:31'),
('skill_weight', '0.7', 'Skill matching weight', '2026-04-26 15:16:31'),
('profile_weight', '0.3', 'Profile matching weight', '2026-04-26 15:16:31');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `avatar_path` varchar(500) DEFAULT NULL,
  `role` enum('jobseeker','employer','lgu_admin','sys_admin') DEFAULT 'jobseeker',
  `terms_accepted` tinyint(1) DEFAULT 0,
  `terms_accepted_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `full_name`, `phone`, `avatar_path`, `role`, `terms_accepted`, `terms_accepted_at`, `is_active`, `is_verified`, `verification_token`, `reset_token`, `reset_expires`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'lgu@joblinklocal.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'LGU Employment Officer', NULL, NULL, 'lgu_admin', 1, '2026-04-26 08:16:31', 1, 1, NULL, NULL, NULL, NULL, '2026-04-26 15:16:31', '2026-04-26 15:16:31'),
(2, 'sysadmin@joblinklocal.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', NULL, NULL, 'sys_admin', 1, '2026-04-26 08:16:31', 1, 1, NULL, NULL, NULL, NULL, '2026-04-26 15:16:31', '2026-04-26 15:16:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_application` (`job_id`,`jobseeker_id`),
  ADD KEY `jobseeker_id` (`jobseeker_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `idx_status` (`status`) USING BTREE,
  ADD KEY `idx_created` (`created_at`) USING BTREE;

--
-- Indexes for table `email_queue`
--
ALTER TABLE `email_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `employer_profiles`
--
ALTER TABLE `employer_profiles`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `verified_by` (`verified_by`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_employer` (`employer_id`);

--
-- Indexes for table `jobseeker_profiles`
--
ALTER TABLE `jobseeker_profiles`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `job_categories`
--
ALTER TABLE `job_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `saved_jobs`
--
ALTER TABLE `saved_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_saved` (`job_id`,`jobseeker_id`),
  ADD KEY `jobseeker_id` (`jobseeker_id`);

--
-- Indexes for table `skills`
--
ALTER TABLE `skills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `system_config`
--
ALTER TABLE `system_config`
  ADD PRIMARY KEY (`config_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_queue`
--
ALTER TABLE `email_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_categories`
--
ALTER TABLE `job_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `saved_jobs`
--
ALTER TABLE `saved_jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skills`
--
ALTER TABLE `skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
