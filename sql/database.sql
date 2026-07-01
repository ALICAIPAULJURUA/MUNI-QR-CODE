-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 01, 2026 at 10:38 AM
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
-- Database: `muni_vc_qr`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `name`, `email`, `password`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'Administrator', 'admin@muni.ac.ug', '$2y$10$N9qo8uLOickgx2ZMRZoMy.Mr/.8Z1kPjM2xM9.wtM5cU8Z2nM0Yy', 1, NULL, '2026-07-01 07:27:57', '2026-07-01 07:27:57');

-- --------------------------------------------------------

--
-- Table structure for table `qr_codes`
--

CREATE TABLE `qr_codes` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `qr_image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `scan_count` int(11) DEFAULT 0,
  `last_scan` timestamp NULL DEFAULT NULL,
  `design_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`design_settings`)),
  `content_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`content_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qr_codes`
--

INSERT INTO `qr_codes` (`id`, `name`, `token`, `qr_image`, `status`, `scan_count`, `last_scan`, `design_settings`, `content_data`, `created_at`, `updated_at`) VALUES
(1, 'System Administrator', 'vc_d478e4da1fe0f4b3a74ce6ed43597d6a', NULL, 'active', 9, '2026-07-01 08:35:55', '{\"pattern\":\"dots\",\"corner\":\"square\",\"color\":\"#8b0000\",\"background\":\"#ffffff\",\"size\":300,\"padding\":25}', '{\"title\":\"System Administrator\",\"description\":\"\",\"full_name\":\"Martin Emkuny\",\"title_position\":\"IT Officer\",\"office\":\"Office of DICTS\",\"biography\":\"\",\"email\":\"dicts@muni.ac.ug\",\"phone\":\"0770863080\",\"website\":\"https:\\/\\/www.muni.ac.ug\",\"linkedin\":\"https:\\/\\/linkedin.com\\/in\\/vc\",\"facebook\":\"https:\\/\\/www.facebook.com\\/\",\"twitter\":\"https:\\/\\/x.com\\/home\"}', '2026-07-01 07:30:25', '2026-07-01 08:35:55'),
(3, 'Vice Chancellor Official QR', 'vc_4390362c34f2ce1c6d3e18e885fa407c', NULL, 'active', 1, '2026-07-01 08:01:10', '{\"pattern\":\"dots\",\"corner\":\"square\",\"color\":\"#8b0000\",\"background\":\"#ffffff\",\"size\":300,\"padding\":25}', '{\"title\":\"Vice Chancellor Official QR\",\"description\":\"\",\"full_name\":\"Prof. Simon Anguma\",\"title_position\":\"Vice Chancellor\",\"office\":\"Office of the Vice Chancellor\",\"biography\":\"\",\"email\":\"vc@muni.ac.ug\",\"phone\":\"0770863080\",\"website\":\"https:\\/\\/www.muni.ac.ug\",\"linkedin\":\"https:\\/\\/linkedin.com\\/in\\/vc\",\"facebook\":\"https:\\/\\/www.facebook.com\\/\",\"twitter\":\"https:\\/\\/x.com\\/home\",\"photo\":\"profile_1782892857.jpg\"}', '2026-07-01 08:01:03', '2026-07-01 08:01:10'),
(5, 'System Administrator', 'vc_ad3cc16544cde26c1520199b5cbf322f', NULL, 'active', 1, '2026-07-01 08:04:50', '{\"pattern\":\"dots\",\"corner\":\"square\",\"color\":\"#8b0000\",\"background\":\"#ffffff\",\"size\":300,\"padding\":25}', '{\"title\":\"System Administrator\",\"description\":\"\",\"full_name\":\"Alicai Paul Jurua\",\"title_position\":\"Internship Student\",\"office\":\"Office of DICTS\",\"biography\":\"\",\"email\":\"dicts@muni.ac.ug\",\"phone\":\"+256770911635\",\"website\":\"https:\\/\\/www.muni.ac.ug\",\"linkedin\":\"https:\\/\\/linkedin.com\\/in\\/vc\",\"facebook\":\"https:\\/\\/www.facebook.com\\/\",\"twitter\":\"https:\\/\\/x.com\\/home\",\"photo\":\"profile_1782893079.jpg\"}', '2026-07-01 08:04:45', '2026-07-01 08:04:50');

-- --------------------------------------------------------

--
-- Table structure for table `scans`
--

CREATE TABLE `scans` (
  `id` int(11) NOT NULL,
  `qr_id` int(11) NOT NULL,
  `scanned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `scans`
--

INSERT INTO `scans` (`id`, `qr_id`, `scanned_at`) VALUES
(1, 1, '2026-07-01 07:30:33'),
(2, 1, '2026-07-01 07:32:00'),
(3, 1, '2026-07-01 07:40:13'),
(6, 1, '2026-07-01 07:55:35'),
(9, 1, '2026-07-01 07:58:59'),
(10, 3, '2026-07-01 08:01:10'),
(13, 5, '2026-07-01 08:04:50'),
(14, 1, '2026-07-01 08:30:05'),
(15, 1, '2026-07-01 08:30:08'),
(16, 1, '2026-07-01 08:35:52'),
(17, 1, '2026-07-01 08:35:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`);

--
-- Indexes for table `qr_codes`
--
ALTER TABLE `qr_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `scans`
--
ALTER TABLE `scans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_qr_id` (`qr_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `qr_codes`
--
ALTER TABLE `qr_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `scans`
--
ALTER TABLE `scans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `scans`
--
ALTER TABLE `scans`
  ADD CONSTRAINT `scans_ibfk_1` FOREIGN KEY (`qr_id`) REFERENCES `qr_codes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
