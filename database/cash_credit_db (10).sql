-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 15, 2025 at 08:24 AM
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
-- Database: `cash_credit_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `customer_types`
--

CREATE TABLE `customer_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `customer_type_id` int(11) DEFAULT NULL COMMENT 'Optional: type of customer',
  `name` varchar(100) NOT NULL,
  `phone1` varchar(20) NOT NULL,
  `phone2` varchar(20) DEFAULT NULL,
  `guarantor_name` varchar(100) DEFAULT NULL,
  `guarantor_phone` varchar(20) DEFAULT NULL,
  `owed_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `advance_payment` decimal(10,2) NOT NULL DEFAULT 0.00,
  `city` varchar(50) NOT NULL,
  `location` enum('inside','outside') NOT NULL DEFAULT 'inside',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `file_sequences`
--

CREATE TABLE `file_sequences` (
  `sequence_name` varchar(50) NOT NULL,
  `current_value` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `file_sequences`
--

INSERT INTO `file_sequences` (`sequence_name`, `current_value`) VALUES
('receipt_sequence', 36);

-- --------------------------------------------------------

--
-- Table structure for table `mixed_accounts`
--

CREATE TABLE `mixed_accounts` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone1` varchar(20) NOT NULL,
  `phone2` varchar(20) DEFAULT NULL,
  `guarantor_name` varchar(100) DEFAULT NULL,
  `guarantor_phone` varchar(20) DEFAULT NULL,
  `they_owe` decimal(10,2) NOT NULL DEFAULT 0.00,
  `we_owe` decimal(10,2) NOT NULL DEFAULT 0.00,
  `they_advance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `we_advance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `city` varchar(50) NOT NULL,
  `location` enum('inside','outside') NOT NULL DEFAULT 'inside',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mixed_accounts`
--

INSERT INTO `mixed_accounts` (`id`, `name`, `phone1`, `phone2`, `guarantor_name`, `guarantor_phone`, `they_owe`, `we_owe`, `they_advance`, `we_advance`, `city`, `location`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'ڕاوێژ', '07709240892', NULL, NULL, NULL, 20000.00, 80000.00, 0.00, 0.00, '', 'inside', NULL, 1, '2025-05-14 17:42:45', '2025-05-14 18:00:11');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `description`, `created_at`) VALUES
(199, 'view_dashboard', 'دەستگەیشتن بە داشبۆرد', '2025-05-13 14:38:17'),
(200, 'view_customers', 'بینینی کڕیارەکان', '2025-05-13 14:38:17'),
(201, 'add_customer', 'زیادکردنی کڕیار', '2025-05-13 14:38:17'),
(202, 'edit_customer', 'دەستکاریکردنی کڕیار', '2025-05-13 14:38:17'),
(203, 'delete_customer', 'سڕینەوەی کڕیار', '2025-05-13 14:38:17'),
(204, 'view_suppliers', 'بینینی کۆمپانیاکان', '2025-05-13 14:38:17'),
(205, 'add_supplier', 'زیادکردنی کۆمپانیا', '2025-05-13 14:38:17'),
(206, 'edit_supplier', 'دەستکاریکردنی کۆمپانیا', '2025-05-13 14:38:17'),
(207, 'delete_supplier', 'سڕینەوەی کۆمپانیا', '2025-05-13 14:38:17'),
(208, 'view_mixed_accounts', 'بینینی ئەکاونتە تێکەڵەکان', '2025-05-13 14:38:17'),
(209, 'add_mixed_account', 'زیادکردنی ئەکاونتی تێکەڵ', '2025-05-13 14:38:17'),
(210, 'edit_mixed_account', 'دەستکاریکردنی ئەکاونتی تێکەڵ', '2025-05-13 14:38:17'),
(211, 'delete_mixed_account', 'سڕینەوەی ئەکاونتی تێکەڵ', '2025-05-13 14:38:17'),
(212, 'view_transactions', 'بینینی مامەڵەکان', '2025-05-13 14:38:17'),
(213, 'add_transaction', 'زیادکردنی مامەڵە', '2025-05-13 14:38:17'),
(214, 'edit_transaction', 'دەستکاریکردنی مامەڵە', '2025-05-13 14:38:17'),
(215, 'delete_transaction', 'سڕینەوەی مامەڵە', '2025-05-13 14:38:17'),
(216, 'view_deleted_transactions', 'بینینی مامەڵە سڕاوەکان', '2025-05-13 14:38:17'),
(217, 'restore_transaction', 'گەڕانەوەی مامەڵە', '2025-05-13 14:38:17'),
(218, 'view_reports', 'بینینی ڕاپۆرتەکان', '2025-05-13 14:38:17'),
(219, 'export_reports', 'دەرکردنی ڕاپۆرت', '2025-05-13 14:38:17'),
(220, 'view_users', 'بینینی بەکارهێنەران', '2025-05-13 14:38:17'),
(221, 'add_user', 'زیادکردنی بەکارهێنەر', '2025-05-13 14:38:17'),
(222, 'edit_user', 'دەستکاریکردنی بەکارهێنەر', '2025-05-13 14:38:17'),
(223, 'delete_user', 'سڕینەوەی بەکارهێنەر', '2025-05-13 14:38:17'),
(224, 'view_roles', 'بینینی ڕۆڵەکان', '2025-05-13 14:38:17'),
(225, 'add_role', 'زیادکردنی ڕۆڵ', '2025-05-13 14:38:17'),
(226, 'edit_role', 'دەستکاریکردنی ڕۆڵ', '2025-05-13 14:38:17'),
(227, 'delete_role', 'سڕینەوەی ڕۆڵ', '2025-05-13 14:38:17'),
(228, 'manage_permissions', 'بەڕێوەبردنی مۆڵەتەکان', '2025-05-13 14:38:17'),
(229, 'view_settings', 'بینینی ڕێکخستنەکان', '2025-05-13 14:38:17'),
(230, 'edit_settings', 'دەستکاریکردنی ڕێکخستنەکان', '2025-05-13 14:38:17');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'بەڕێوەبەری باڵا', 'دەستگەیشتنی تەواو بە سیستەم', '2025-05-07 19:26:38'),
(17, 'بەڕێوەبەر', 'دەستگەیشتنی باڵا بە سیستەم', '2025-05-13 14:38:17'),
(18, 'کارمەند', 'دەستگەیشتنی سنووردار بە سیستەم', '2025-05-13 14:38:17');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES
(1, 199, '2025-05-13 14:38:17'),
(1, 200, '2025-05-13 14:38:17'),
(1, 201, '2025-05-13 14:38:17'),
(1, 202, '2025-05-13 14:38:17'),
(1, 203, '2025-05-13 14:38:17'),
(1, 204, '2025-05-13 14:38:17'),
(1, 205, '2025-05-13 14:38:17'),
(1, 206, '2025-05-13 14:38:17'),
(1, 207, '2025-05-13 14:38:17'),
(1, 208, '2025-05-13 14:38:17'),
(1, 209, '2025-05-13 14:38:17'),
(1, 210, '2025-05-13 14:38:17'),
(1, 211, '2025-05-13 14:38:17'),
(1, 212, '2025-05-13 14:38:17'),
(1, 213, '2025-05-13 14:38:17'),
(1, 214, '2025-05-13 14:38:17'),
(1, 215, '2025-05-13 14:38:17'),
(1, 216, '2025-05-13 14:38:17'),
(1, 217, '2025-05-13 14:38:17'),
(1, 218, '2025-05-13 14:38:17'),
(1, 219, '2025-05-13 14:38:17'),
(1, 220, '2025-05-13 14:38:17'),
(1, 221, '2025-05-13 14:38:17'),
(1, 222, '2025-05-13 14:38:17'),
(1, 223, '2025-05-13 14:38:17'),
(1, 224, '2025-05-13 14:38:17'),
(1, 225, '2025-05-13 14:38:17'),
(1, 226, '2025-05-13 14:38:17'),
(1, 227, '2025-05-13 14:38:17'),
(1, 228, '2025-05-13 14:38:17'),
(1, 229, '2025-05-13 14:38:17'),
(1, 230, '2025-05-13 14:38:17'),
(17, 199, '2025-05-13 14:38:17'),
(17, 200, '2025-05-13 14:38:17'),
(17, 201, '2025-05-13 14:38:17'),
(17, 202, '2025-05-13 14:38:17'),
(17, 204, '2025-05-13 14:38:17'),
(17, 205, '2025-05-13 14:38:17'),
(17, 206, '2025-05-13 14:38:17'),
(17, 208, '2025-05-13 14:38:17'),
(17, 209, '2025-05-13 14:38:17'),
(17, 210, '2025-05-13 14:38:17'),
(17, 212, '2025-05-13 14:38:17'),
(17, 213, '2025-05-13 14:38:17'),
(17, 214, '2025-05-13 14:38:17'),
(17, 218, '2025-05-13 14:38:17'),
(17, 219, '2025-05-13 14:38:17'),
(17, 220, '2025-05-13 14:38:17'),
(18, 200, '2025-05-15 06:22:19'),
(18, 201, '2025-05-15 06:22:19'),
(18, 202, '2025-05-15 06:22:19'),
(18, 203, '2025-05-15 06:22:19'),
(18, 204, '2025-05-15 06:22:19'),
(18, 205, '2025-05-15 06:22:19'),
(18, 206, '2025-05-15 06:22:19'),
(18, 207, '2025-05-15 06:22:19'),
(18, 208, '2025-05-15 06:22:19'),
(18, 209, '2025-05-15 06:22:19'),
(18, 210, '2025-05-15 06:22:19'),
(18, 211, '2025-05-15 06:22:19'),
(18, 220, '2025-05-15 06:22:19'),
(18, 221, '2025-05-15 06:22:19'),
(18, 222, '2025-05-15 06:22:19'),
(18, 223, '2025-05-15 06:22:19');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`, `updated_by`, `updated_at`) VALUES
('company_address', 'سلێمانی', 1, '2025-05-14 17:53:19'),
('company_name', 'کۆگای احمد و ئەشکان', 1, '2025-05-14 17:53:19'),
('company_phone', '07709240894', 1, '2025-05-14 17:53:19'),
('currency_symbol', 'د.ع', 1, '2025-05-14 17:53:19'),
('date_format', 'Y-m-d', 1, '2025-05-14 17:53:19'),
('default_theme', 'light', 1, '2025-05-14 17:53:19');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone1` varchar(20) NOT NULL,
  `phone2` varchar(20) DEFAULT NULL,
  `we_owe` decimal(10,2) NOT NULL DEFAULT 0.00,
  `advance_payment` decimal(10,2) NOT NULL DEFAULT 0.00,
  `city` varchar(50) NOT NULL,
  `location` enum('inside','outside') NOT NULL DEFAULT 'inside',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `type` enum('credit','cash','advance','payment','collection','advance_refund','advance_collection') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) DEFAULT 0,
  `date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `account_type` varchar(50) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `mixed_account_id` int(11) DEFAULT NULL,
  `direction` enum('sale','purchase','advance_give','advance_receive') DEFAULT NULL COMMENT 'For mixed accounts: sale = we sell to them, purchase = we buy from them, advance_give = we give advance to them, advance_receive = we receive advance from them',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `type`, `amount`, `paid_amount`, `date`, `due_date`, `account_type`, `customer_id`, `supplier_id`, `mixed_account_id`, `direction`, `notes`, `created_by`, `created_at`, `is_deleted`, `deleted_at`) VALUES
(1, 'payment', 30000.00, 0.00, '2025-05-14', NULL, '', NULL, NULL, 1, NULL, '', 1, '2025-05-14 17:43:10', 0, NULL),
(2, 'credit', 50000.00, 0.00, '2025-05-14', '2025-05-20', '', NULL, NULL, 1, 'purchase', '', 1, '2025-05-14 17:50:41', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transactions_backup`
--

CREATE TABLE `transactions_backup` (
  `backup_id` int(11) NOT NULL,
  `original_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `account_type` varchar(50) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `mixed_account_id` int(11) DEFAULT NULL,
  `direction` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `receipt_files` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions_backup`
--

INSERT INTO `transactions_backup` (`backup_id`, `original_id`, `type`, `amount`, `date`, `due_date`, `account_type`, `customer_id`, `supplier_id`, `mixed_account_id`, `direction`, `notes`, `receipt_files`, `created_at`, `updated_at`, `deleted_at`) VALUES
(11, 1, 'credit', 20000.00, '2025-05-13', NULL, 'customer', 1, NULL, NULL, NULL, '', NULL, '2025-05-13 10:37:58', '2025-05-13 10:38:10', NULL),
(12, 1, 'credit', 20000.00, '2025-05-13', NULL, 'customer', 1, NULL, NULL, NULL, '', NULL, '2025-05-13 10:37:58', '2025-05-13 10:55:32', NULL),
(13, 2, 'cash', 50000.00, '2025-05-13', NULL, 'customer', 1, NULL, NULL, NULL, '', NULL, '2025-05-13 15:38:17', '2025-05-13 15:49:49', NULL),
(14, 1, 'credit', 20000.00, '2025-05-13', NULL, 'customer', 1, NULL, NULL, NULL, '', NULL, '2025-05-13 10:37:58', '2025-05-13 15:57:13', NULL),
(15, 2, 'cash', 50000.00, '2025-05-13', NULL, 'customer', 1, NULL, NULL, NULL, '', NULL, '2025-05-13 15:38:17', '2025-05-13 15:57:27', NULL),
(16, 3, 'cash', 50000.00, '2025-05-13', NULL, 'customer', 2, NULL, NULL, NULL, '', NULL, '2025-05-13 17:26:45', '2025-05-13 17:27:24', NULL),
(17, 1, 'cash', 20000.00, '2025-05-14', NULL, 'customer', 5, NULL, NULL, NULL, '', NULL, '2025-05-14 10:12:40', '2025-05-14 10:13:36', NULL),
(18, 2, 'cash', 2000.00, '2025-05-14', NULL, 'customer', 5, NULL, NULL, NULL, '', NULL, '2025-05-14 10:22:31', '2025-05-14 10:22:39', NULL),
(19, 2, 'cash', 2000.00, '2025-05-14', NULL, 'customer', 5, NULL, NULL, NULL, '', NULL, '2025-05-14 10:22:31', '2025-05-14 10:22:49', NULL),
(20, 3, 'cash', 20000.00, '2025-05-14', NULL, 'customer', 5, NULL, NULL, NULL, '', NULL, '2025-05-14 12:57:57', '2025-05-14 13:32:45', NULL),
(21, 4, 'credit', 60000.00, '2025-05-14', NULL, 'customer', 5, NULL, NULL, NULL, '', NULL, '2025-05-14 13:04:03', '2025-05-14 13:32:47', NULL),
(22, 5, 'collection', 61000.00, '2025-05-14', NULL, 'customer', 5, NULL, NULL, NULL, '', NULL, '2025-05-14 13:16:20', '2025-05-14 13:32:50', NULL),
(23, 7, 'collection', 19000.00, '2025-05-14', NULL, 'customer', 5, NULL, NULL, NULL, '', NULL, '2025-05-14 13:20:50', '2025-05-14 13:32:52', NULL),
(24, 9, 'collection', 20000.00, '2025-05-14', NULL, 'customer', 9, NULL, NULL, NULL, '', NULL, '2025-05-14 13:26:35', '2025-05-14 13:32:54', NULL),
(25, 10, 'collection', 20000.00, '2025-05-14', NULL, 'customer', 10, NULL, NULL, NULL, '', NULL, '2025-05-14 13:29:54', '2025-05-14 13:32:56', NULL),
(26, 11, 'collection', 1000.00, '2025-05-14', NULL, 'customer', 11, NULL, NULL, NULL, '', NULL, '2025-05-14 13:31:24', '2025-05-14 13:32:58', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transaction_files`
--

CREATE TABLE `transaction_files` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role_id`, `is_active`, `last_login`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'ashkan@5678', '$2y$10$/XOdKeOkgSScFNiybnua3eg1A0Gh/Vk.lI5lQKu.kaCxUxC6mSaXe', 'ئەشکان', 1, 1, '2025-05-14 09:11:15', 1, '2025-05-07 19:26:38', '2025-05-14 09:11:15'),
(3, 'ahmed@0101', '$2y$10$ORtutNO2i8bQ5qDxO0jpheWfljVltNjzSHMzka95TOV7MhLN/0O1K', 'ئەحمەد', 18, 1, '2025-05-15 06:17:06', 1, '2025-05-13 14:56:00', '2025-05-15 06:17:06');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `customer_type_id` (`customer_type_id`);

--
-- Indexes for table `file_sequences`
--
ALTER TABLE `file_sequences`
  ADD PRIMARY KEY (`sequence_name`);

--
-- Indexes for table `mixed_accounts`
--
ALTER TABLE `mixed_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`),
  ADD KEY `settings_ibfk_1` (`updated_by`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `mixed_account_id` (`mixed_account_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `transactions_backup`
--
ALTER TABLE `transactions_backup`
  ADD PRIMARY KEY (`backup_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `mixed_account_id` (`mixed_account_id`);

--
-- Indexes for table `transaction_files`
--
ALTER TABLE `transaction_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction_id` (`transaction_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `created_by` (`created_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `mixed_accounts`
--
ALTER TABLE `mixed_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=231;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `transactions_backup`
--
ALTER TABLE `transactions_backup`
  MODIFY `backup_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `transaction_files`
--
ALTER TABLE `transaction_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_customer_type` FOREIGN KEY (`customer_type_id`) REFERENCES `customer_types`(`id`);

--
-- Constraints for table `mixed_accounts`
--
ALTER TABLE `mixed_accounts`
  ADD CONSTRAINT `mixed_accounts_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `settings`
--
ALTER TABLE `settings`
  ADD CONSTRAINT `settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD CONSTRAINT `suppliers_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `transactions_ibfk_3` FOREIGN KEY (`mixed_account_id`) REFERENCES `mixed_accounts` (`id`),
  ADD CONSTRAINT `transactions_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `transaction_files`
--
ALTER TABLE `transaction_files`
  ADD CONSTRAINT `transaction_files_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
