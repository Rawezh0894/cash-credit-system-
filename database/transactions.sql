-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 18, 2025 at 09:04 AM
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
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `type`, `amount`, `date`, `due_date`, `account_type`, `customer_id`, `supplier_id`, `mixed_account_id`, `direction`, `notes`, `created_by`, `created_at`, `is_deleted`, `deleted_at`) VALUES
(1, 'advance_collection', 200000.00, '2025-05-17', '0000-00-00', '', NULL, NULL, 1, NULL, '', 1, '2025-05-17 17:30:55', 0, NULL),
(2, 'advance_collection', 200000.00, '2025-05-17', '0000-00-00', '', NULL, NULL, 1, NULL, '', 1, '2025-05-17 17:34:27', 0, NULL),
(3, 'advance_collection', 200000.00, '2025-05-17', '0000-00-00', '', NULL, NULL, 1, NULL, '', 1, '2025-05-17 17:35:31', 0, NULL),
(4, 'advance_collection', 200000.00, '2025-05-17', '0000-00-00', '', NULL, NULL, 1, NULL, '', 1, '2025-05-17 17:40:16', 0, NULL),
(6, 'advance_collection', 100000.00, '2025-05-17', '0000-00-00', '', NULL, NULL, 1, NULL, '', 1, '2025-05-17 17:41:00', 0, NULL),
(9, 'credit', 50000.00, '2025-05-17', '0000-00-00', '', NULL, NULL, 1, 'purchase', '', 1, '2025-05-17 17:41:55', 0, NULL),
(10, 'credit', 100000.00, '2025-05-17', '0000-00-00', '', 1, NULL, NULL, NULL, '', 1, '2025-05-17 17:42:58', 0, NULL),
(11, 'cash', 200000.00, '2025-05-17', '0000-00-00', '', 1, NULL, NULL, NULL, '', 1, '2025-05-17 17:45:04', 0, NULL),
(12, 'payment', 200000.00, '2025-05-17', '0000-00-00', '', 2, NULL, NULL, NULL, '', 1, '2025-05-17 17:47:04', 0, NULL),
(13, 'collection', 50000.00, '2025-05-17', '0000-00-00', '', 2, NULL, NULL, NULL, '', 1, '2025-05-17 17:47:31', 0, NULL),
(14, 'collection', 250000.00, '2025-05-17', '0000-00-00', '', 2, NULL, NULL, NULL, '', 1, '2025-05-17 18:01:49', 0, NULL),
(15, 'advance', 500000.00, '2025-05-17', '0000-00-00', '', 2, NULL, NULL, NULL, '', 1, '2025-05-17 18:02:08', 0, NULL),
(16, 'credit', 500000.00, '2025-05-17', '0000-00-00', '', NULL, 1, NULL, NULL, '', 1, '2025-05-17 18:05:03', 0, NULL),
(17, 'payment', 1500000.00, '2025-05-17', '0000-00-00', '', NULL, 1, NULL, NULL, '', 1, '2025-05-17 18:06:21', 0, NULL),
(18, 'credit', 450000.00, '2025-05-17', '0000-00-00', '', NULL, NULL, 2, 'purchase', '', 1, '2025-05-17 18:38:34', 0, NULL),
(19, 'collection', 50000.00, '2025-05-17', '0000-00-00', '', NULL, NULL, 1, NULL, '', 1, '2025-05-17 18:39:09', 0, NULL),
(20, 'payment', 50000.00, '2025-05-17', '0000-00-00', '', NULL, NULL, 1, NULL, '', 1, '2025-05-17 18:39:49', 0, NULL),
(21, 'collection', 50000.00, '2025-05-17', '0000-00-00', '', NULL, NULL, 2, NULL, '', 1, '2025-05-17 18:40:14', 0, NULL),
(23, 'advance_refund', 50000.00, '2025-05-17', '0000-00-00', '', NULL, NULL, 2, NULL, '', 1, '2025-05-17 18:43:48', 0, NULL),
(24, 'payment', 150000.00, '2025-05-18', '0000-00-00', '', NULL, NULL, 3, NULL, '', 1, '2025-05-18 06:15:29', 0, NULL),
(27, 'payment', 150000.00, '2025-05-18', '0000-00-00', '', NULL, NULL, 3, NULL, '', 1, '2025-05-18 06:25:49', 0, NULL),
(30, 'payment', 150000.00, '2025-05-18', '0000-00-00', '', NULL, NULL, 4, NULL, '', 1, '2025-05-18 06:27:43', 0, NULL),
(33, 'collection', 500000.00, '2025-05-18', '0000-00-00', '', NULL, NULL, 4, NULL, '', 1, '2025-05-18 06:28:25', 0, NULL),
(36, 'advance', 500000.00, '2025-05-18', '0000-00-00', '', NULL, NULL, 4, '', '', 1, '2025-05-18 06:59:56', 1, '2025-05-18 07:01:08'),
(37, 'advance', 120000.00, '2025-05-18', '0000-00-00', '', NULL, NULL, 4, '', '', 1, '2025-05-18 07:01:31', 0, NULL);

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `transactions_ibfk_3` FOREIGN KEY (`mixed_account_id`) REFERENCES `mixed_accounts` (`id`),
  ADD CONSTRAINT `transactions_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
