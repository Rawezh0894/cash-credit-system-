-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 18, 2025 at 09:30 PM
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
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action_type`, `description`, `ip_address`, `created_at`) VALUES
(1, 1, 'add_product', 'کاڵای نوێ زیادکرا: بەرگی گۆشە (کۆد: PR304850)', '::1', '2025-05-16 23:11:06'),
(2, 1, 'add_product', 'کاڵای نوێ زیادکرا: test (کۆد: PR905129)', '::1', '2025-05-16 23:14:36'),
(3, 1, 'add_product', 'کاڵای نوێ زیادکرا: test (کۆد: PR266771)', '::1', '2025-05-16 23:22:00'),
(4, 1, 'add_product', 'کاڵای نوێ زیادکرا: test (کۆد: PR612525)', '::1', '2025-05-16 23:26:20'),
(5, 1, 'add_product', 'کاڵای نوێ زیادکرا: ف (کۆد: PR976862)', '::1', '2025-05-16 23:39:54'),
(6, 1, 'add_product', 'کاڵای نوێ زیادکرا: ر (کۆد: PR280848)', '::1', '2025-05-16 23:43:17'),
(7, 1, 'add_product', 'کاڵای نوێ زیادکرا: test (کۆد: PR279398)', '::1', '2025-05-17 11:20:44');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `customer_type_id` int(11) DEFAULT NULL,
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
-- Table structure for table `customer_types`
--

CREATE TABLE `customer_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_types`
--

INSERT INTO `customer_types` (`id`, `type_name`, `created_at`) VALUES
(1, 'نێرەکەر', '2025-05-18 17:27:11');

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
('receipt_sequence', 42);

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
(232, 'view_user_activities', 'بینینی چالاکیەکانی بەکارهێنەران', '2025-05-15 07:10:10'),
(233, 'view_activity_log', 'بینینی چاودێری چالاکیەکان', '2025-05-15 07:12:42'),
(234, 'view_products', 'بینینی کاڵاکان', '2025-05-15 10:27:29'),
(235, 'add_product', 'زیادکردنی کاڵا', '2025-05-15 10:27:29'),
(236, 'edit_product', 'دەستکاریکردنی کاڵا', '2025-05-15 10:27:29'),
(237, 'delete_product', 'سڕینەوەی کاڵا', '2025-05-15 10:27:29'),
(238, 'view_product_categories', 'بینینی جۆری کاڵاکان', '2025-05-15 10:27:29'),
(239, 'manage_product_categories', 'بەڕێوەبردنی جۆری کاڵاکان', '2025-05-15 10:27:29'),
(240, 'view_units', 'بینینی یەکەکان', '2025-05-15 10:27:29'),
(241, 'manage_units', 'بەڕێوەبردنی یەکەکان', '2025-05-15 10:27:29');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `product_code` varchar(50) DEFAULT NULL,
  `barcode` varchar(50) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `unit_id` int(11) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `purchase_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `retail_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `wholesale_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `min_stock_alert` int(11) DEFAULT NULL,
  `current_stock` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `product_code`, `barcode`, `category_id`, `unit_id`, `image_path`, `purchase_price`, `retail_price`, `wholesale_price`, `min_stock_alert`, `current_stock`, `created_by`, `created_at`, `updated_at`) VALUES
(7, 'test', 'PR279398', '125', 1, 2, 'uploads/products/product_68284c548d37b_1747471444.jpg', 8250.00, 10000.00, 0.00, 12, 100, 1, '2025-05-17 08:20:44', '2025-05-17 08:59:21');

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`id`, `name`, `description`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'بەرگی گۆشە', NULL, 1, '2025-05-16 19:31:48', '2025-05-16 19:31:48');

-- --------------------------------------------------------

--
-- Table structure for table `product_unit_conversions`
--

CREATE TABLE `product_unit_conversions` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `items_per_carton` int(11) DEFAULT NULL,
  `cartons_per_set` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_unit_conversions`
--

INSERT INTO `product_unit_conversions` (`id`, `product_id`, `unit_id`, `items_per_carton`, `cartons_per_set`, `created_at`) VALUES
(3, 7, 2, 12, NULL, '2025-05-17 08:20:44');

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
(1, 199, '2025-05-15 13:27:29'),
(1, 200, '2025-05-15 13:27:29'),
(1, 201, '2025-05-15 13:27:29'),
(1, 202, '2025-05-15 13:27:29'),
(1, 203, '2025-05-15 13:27:29'),
(1, 204, '2025-05-15 13:27:29'),
(1, 205, '2025-05-15 13:27:29'),
(1, 206, '2025-05-15 13:27:29'),
(1, 207, '2025-05-15 13:27:29'),
(1, 208, '2025-05-15 13:27:29'),
(1, 209, '2025-05-15 13:27:29'),
(1, 210, '2025-05-15 13:27:29'),
(1, 211, '2025-05-15 13:27:29'),
(1, 212, '2025-05-15 13:27:29'),
(1, 213, '2025-05-15 13:27:29'),
(1, 214, '2025-05-15 13:27:29'),
(1, 215, '2025-05-15 13:27:29'),
(1, 216, '2025-05-15 13:27:29'),
(1, 217, '2025-05-15 13:27:29'),
(1, 218, '2025-05-15 13:27:29'),
(1, 219, '2025-05-15 13:27:29'),
(1, 220, '2025-05-15 13:27:29'),
(1, 221, '2025-05-15 13:27:29'),
(1, 222, '2025-05-15 13:27:29'),
(1, 223, '2025-05-15 13:27:29'),
(1, 224, '2025-05-15 13:27:29'),
(1, 225, '2025-05-15 13:27:29'),
(1, 226, '2025-05-15 13:27:29'),
(1, 227, '2025-05-15 13:27:29'),
(1, 228, '2025-05-15 13:27:29'),
(1, 232, '2025-05-15 13:27:29'),
(1, 233, '2025-05-15 13:27:29'),
(18, 199, '2025-05-15 17:15:43'),
(18, 200, '2025-05-15 17:15:43'),
(18, 201, '2025-05-15 17:15:43'),
(18, 202, '2025-05-15 17:15:43'),
(18, 203, '2025-05-15 17:15:43'),
(18, 204, '2025-05-15 17:15:43'),
(18, 208, '2025-05-15 17:15:43');

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
  `date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `account_type` varchar(50) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `mixed_account_id` int(11) DEFAULT NULL,
  `direction` enum('sale','purchase','advance_give','advance_receive') DEFAULT NULL COMMENT 'For mixed accounts: sale = we sell to them, purchase = we buy from them',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `paid_amount` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `name`, `created_at`) VALUES
(1, 'دانە', '2025-05-16 19:14:30'),
(2, 'کیلۆگرام', '2025-05-16 19:14:30'),
(3, 'لیتر', '2025-05-16 19:14:30'),
(4, 'مەتر', '2025-05-16 19:14:30'),
(5, 'بۆکس', '2025-05-16 19:14:30');

-- --------------------------------------------------------

--
-- Table structure for table `units_of_measurement`
--

CREATE TABLE `units_of_measurement` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `symbol` varchar(10) NOT NULL,
  `type` enum('unit','weight','volume','length','set') NOT NULL,
  `base_unit_id` int(11) DEFAULT NULL,
  `conversion_factor` decimal(10,3) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `units_of_measurement`
--

INSERT INTO `units_of_measurement` (`id`, `name`, `symbol`, `type`, `base_unit_id`, `conversion_factor`, `created_by`, `created_at`) VALUES
(1, 'دانە', 'دانە', 'unit', NULL, NULL, NULL, '2025-05-16 18:54:48'),
(2, 'کارتۆن', 'کارتۆن', 'unit', NULL, NULL, NULL, '2025-05-16 18:54:48'),
(3, 'سێت', 'سێت', 'set', NULL, NULL, NULL, '2025-05-16 18:54:48'),
(4, 'مەتر', 'م', 'length', NULL, NULL, NULL, '2025-05-16 18:54:48'),
(5, 'لیتر', 'ل', 'volume', NULL, NULL, NULL, '2025-05-16 18:54:48'),
(6, 'کیلۆگرام', 'کگم', 'weight', NULL, NULL, NULL, '2025-05-16 18:54:48');

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
(1, 'ashkan@5678', '$2y$10$/XOdKeOkgSScFNiybnua3eg1A0Gh/Vk.lI5lQKu.kaCxUxC6mSaXe', 'ئەشکان', 1, 1, '2025-05-18 19:10:44', 1, '2025-05-07 19:26:38', '2025-05-18 19:10:44');

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
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `fk_customer_type` (`customer_type_id`);

--
-- Indexes for table `customer_types`
--
ALTER TABLE `customer_types`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_code` (`product_code`),
  ADD UNIQUE KEY `barcode` (`barcode`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `unit_id` (`unit_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `product_unit_conversions`
--
ALTER TABLE `product_unit_conversions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `unit_id` (`unit_id`);

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
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `units_of_measurement`
--
ALTER TABLE `units_of_measurement`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `symbol` (`symbol`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `base_unit_id` (`base_unit_id`);

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
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_types`
--
ALTER TABLE `customer_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `mixed_accounts`
--
ALTER TABLE `mixed_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=242;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `product_unit_conversions`
--
ALTER TABLE `product_unit_conversions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions_backup`
--
ALTER TABLE `transactions_backup`
  MODIFY `backup_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `transaction_files`
--
ALTER TABLE `transaction_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `units_of_measurement`
--
ALTER TABLE `units_of_measurement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_customer_type` FOREIGN KEY (`customer_type_id`) REFERENCES `customer_types` (`id`);

--
-- Constraints for table `mixed_accounts`
--
ALTER TABLE `mixed_accounts`
  ADD CONSTRAINT `mixed_accounts_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`unit_id`) REFERENCES `units_of_measurement` (`id`),
  ADD CONSTRAINT `products_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD CONSTRAINT `product_categories_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `product_unit_conversions`
--
ALTER TABLE `product_unit_conversions`
  ADD CONSTRAINT `product_unit_conversions_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_unit_conversions_ibfk_2` FOREIGN KEY (`unit_id`) REFERENCES `units_of_measurement` (`id`);

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `units_of_measurement`
--
ALTER TABLE `units_of_measurement`
  ADD CONSTRAINT `units_of_measurement_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `units_of_measurement_ibfk_2` FOREIGN KEY (`base_unit_id`) REFERENCES `units_of_measurement` (`id`);

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
