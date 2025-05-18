-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 17, 2025 at 05:04 PM
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
(1, 'ڕاوێژ', '07501211541', NULL, NULL, NULL, 0.00, 0.00, 500000.00, 150000.00, '', 'inside', NULL, 1, '2025-05-17 14:56:03', '2025-05-17 14:56:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `mixed_accounts`
--
ALTER TABLE `mixed_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `mixed_accounts`
--
ALTER TABLE `mixed_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `mixed_accounts`
--
ALTER TABLE `mixed_accounts`
  ADD CONSTRAINT `mixed_accounts_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
