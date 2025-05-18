-- Table structure for table `settings`
CREATE TABLE `settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Default settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `updated_by`, `updated_at`) VALUES
('company_name', 'سیستەمی پارە و کریت', 1, CURRENT_TIMESTAMP),
('company_phone', '07709240894', 1, CURRENT_TIMESTAMP),
('company_address', 'سلێمانی', 1, CURRENT_TIMESTAMP),
('currency_symbol', 'د.ع', 1, CURRENT_TIMESTAMP),
('date_format', 'Y-m-d', 1, CURRENT_TIMESTAMP),
('default_theme', 'light', 1, CURRENT_TIMESTAMP);

-- Add primary key
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

-- Add foreign key for updated_by
ALTER TABLE `settings`
  ADD CONSTRAINT `settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`); 