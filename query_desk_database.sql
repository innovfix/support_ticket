-- Query Desk Database Export
-- Generated: 2025-08-16 22:07:01
-- Database: query_desk


-- Table structure for table `issue_types`
DROP TABLE IF EXISTS `issue_types`;
CREATE TABLE `issue_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `issue_types`
INSERT INTO `issue_types` (`id`, `name`, `is_active`, `created_at`) VALUES ('1', 'Withdrawal paid status not amount came', '1', '2025-08-13 12:28:42');
INSERT INTO `issue_types` (`id`, `name`, `is_active`, `created_at`) VALUES ('2', 'Coins not added', '1', '2025-08-13 12:28:42');
INSERT INTO `issue_types` (`id`, `name`, `is_active`, `created_at`) VALUES ('3', 'Call amount not added', '1', '2025-08-13 12:28:42');
INSERT INTO `issue_types` (`id`, `name`, `is_active`, `created_at`) VALUES ('4', 'App issue / crash', '1', '2025-08-13 12:28:42');
INSERT INTO `issue_types` (`id`, `name`, `is_active`, `created_at`) VALUES ('5', 'Bank details issue', '1', '2025-08-13 12:28:42');
INSERT INTO `issue_types` (`id`, `name`, `is_active`, `created_at`) VALUES ('6', 'KYC details related', '1', '2025-08-13 12:28:42');
INSERT INTO `issue_types` (`id`, `name`, `is_active`, `created_at`) VALUES ('7', 'Blocking user', '1', '2025-08-13 12:28:59');
INSERT INTO `issue_types` (`id`, `name`, `is_active`, `created_at`) VALUES ('8', 'block', '0', '2025-08-13 12:34:35');
INSERT INTO `issue_types` (`id`, `name`, `is_active`, `created_at`) VALUES ('9', 'Array', '0', '2025-08-15 18:05:35');


-- Table structure for table `staff_users`
DROP TABLE IF EXISTS `staff_users`;
CREATE TABLE `staff_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `staff_users`
INSERT INTO `staff_users` (`id`, `name`, `email`, `password_hash`, `is_active`, `created_at`) VALUES ('1', 'shalini', 'sahalini@gmail.com', '$2y$10$ZzwMAWXYLaq2TLhMwBvD6OIBV85c08VhEVNgSMhL/GZo.m0uAXs6O', '1', '2025-08-13 14:08:34');
INSERT INTO `staff_users` (`id`, `name`, `email`, `password_hash`, `is_active`, `created_at`) VALUES ('2', 'abi', 'abi@gmail.com', '$2y$10$4d4FBLVQ0baI9vfZOHremulaPsCE9GXqRIXcXpQQM8D7lRQQTuh42', '1', '2025-08-13 14:17:18');
INSERT INTO `staff_users` (`id`, `name`, `email`, `password_hash`, `is_active`, `created_at`) VALUES ('4', 'kala', 'kala@gmail.com', '$2y$10$Uc3hQuHLy3X7vkJV.FiOVO1wfEKj6aeEBljOtMehYLiHU60Pj4K9q', '1', '2025-08-13 15:50:33');
INSERT INTO `staff_users` (`id`, `name`, `email`, `password_hash`, `is_active`, `created_at`) VALUES ('6', 'kri', 'kri@gmail.com', '$2y$10$3K3CS4ErUmc0LoWChYq4DehzPXj0YPjBj2xj3dXcK2Z75ztsKpgAa', '1', '2025-08-15 18:16:53');
INSERT INTO `staff_users` (`id`, `name`, `email`, `password_hash`, `is_active`, `created_at`) VALUES ('8', 'subshree', 'sub@gmail.com', '$2y$10$A6kXJ8ddwCMHuFZxr1nlBeEeF6MJoroQvgKmBIfiqQJXdO2riING.', '1', '2025-08-15 21:07:49');
INSERT INTO `staff_users` (`id`, `name`, `email`, `password_hash`, `is_active`, `created_at`) VALUES ('10', 'mala', 'malal@gmail.com', '$2y$10$MyNyxTcQY06v1pawuGoM/OlPe1QQ0SxCa.OYdUeyi0NJHATlQzC8a', '1', '2025-08-17 00:15:33');


-- Table structure for table `tickets`
DROP TABLE IF EXISTS `tickets`;
CREATE TABLE `tickets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_code` varchar(20) NOT NULL,
  `mobile_or_user_id` varchar(100) NOT NULL,
  `issue_type` varchar(100) NOT NULL,
  `issue_description` text NOT NULL,
  `status` enum('new','in-progress','resolved','closed') NOT NULL DEFAULT 'new',
  `assigned_to` varchar(100) DEFAULT NULL,
  `screenshot_path` varchar(255) DEFAULT NULL,
  `created_by` varchar(100) NOT NULL DEFAULT 'Staff',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `assigned_by` varchar(150) DEFAULT NULL,
  `assigned_to_name` varchar(150) DEFAULT NULL,
  `assigned_by_name` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_code` (`ticket_code`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `tickets`
INSERT INTO `tickets` (`id`, `ticket_code`, `mobile_or_user_id`, `issue_type`, `issue_description`, `status`, `assigned_to`, `screenshot_path`, `created_by`, `created_at`, `updated_at`, `assigned_by`, `assigned_to_name`, `assigned_by_name`) VALUES ('6', 'TKT-0001', '9009876543', 'Bank details issue', 'ed', 'new', NULL, NULL, 'abi@gmail.com', '2025-08-16 20:54:56', '2025-08-16 20:54:56', NULL, NULL, NULL);
INSERT INTO `tickets` (`id`, `ticket_code`, `mobile_or_user_id`, `issue_type`, `issue_description`, `status`, `assigned_to`, `screenshot_path`, `created_by`, `created_at`, `updated_at`, `assigned_by`, `assigned_to_name`, `assigned_by_name`) VALUES ('7', 'TKT-0002', '0987654321', 'Coins not added', 'sfdfdghjkl;', 'new', NULL, NULL, 'kala@gmail.com', '2025-08-16 23:31:17', '2025-08-16 23:31:17', NULL, NULL, NULL);
INSERT INTO `tickets` (`id`, `ticket_code`, `mobile_or_user_id`, `issue_type`, `issue_description`, `status`, `assigned_to`, `screenshot_path`, `created_by`, `created_at`, `updated_at`, `assigned_by`, `assigned_to_name`, `assigned_by_name`) VALUES ('8', 'TKT-0003', '0987654321', 'App issue / crash', 'DECV', 'new', NULL, NULL, 'kala@gmail.com', '2025-08-16 23:49:19', '2025-08-16 23:49:19', NULL, NULL, NULL);
INSERT INTO `tickets` (`id`, `ticket_code`, `mobile_or_user_id`, `issue_type`, `issue_description`, `status`, `assigned_to`, `screenshot_path`, `created_by`, `created_at`, `updated_at`, `assigned_by`, `assigned_to_name`, `assigned_by_name`) VALUES ('9', 'TKT-0004', '0987654321GF', 'KYC details related', 'XFGNSX', 'new', NULL, NULL, 'kala@gmail.com', '2025-08-16 23:49:53', '2025-08-16 23:49:53', NULL, NULL, NULL);
INSERT INTO `tickets` (`id`, `ticket_code`, `mobile_or_user_id`, `issue_type`, `issue_description`, `status`, `assigned_to`, `screenshot_path`, `created_by`, `created_at`, `updated_at`, `assigned_by`, `assigned_to_name`, `assigned_by_name`) VALUES ('10', 'TKT-0005', '0987654321', 'Bank details issue', 'SDCdf', 'new', NULL, NULL, 'kala@gmail.com', '2025-08-16 23:57:06', '2025-08-16 23:57:06', NULL, NULL, NULL);
INSERT INTO `tickets` (`id`, `ticket_code`, `mobile_or_user_id`, `issue_type`, `issue_description`, `status`, `assigned_to`, `screenshot_path`, `created_by`, `created_at`, `updated_at`, `assigned_by`, `assigned_to_name`, `assigned_by_name`) VALUES ('11', 'TKT-0006', '0987654321', 'Bank details issue', 'scdghjk', 'resolved', NULL, NULL, 'kala@gmail.com', '2025-08-17 00:07:31', '2025-08-17 00:13:41', NULL, NULL, NULL);
INSERT INTO `tickets` (`id`, `ticket_code`, `mobile_or_user_id`, `issue_type`, `issue_description`, `status`, `assigned_to`, `screenshot_path`, `created_by`, `created_at`, `updated_at`, `assigned_by`, `assigned_to_name`, `assigned_by_name`) VALUES ('12', 'TKT-0007', '9003604825', 'Blocking user', 'sDBv', 'closed', NULL, NULL, 'kri@gmail.com', '2025-08-17 00:16:40', '2025-08-17 00:17:14', NULL, NULL, NULL);
INSERT INTO `tickets` (`id`, `ticket_code`, `mobile_or_user_id`, `issue_type`, `issue_description`, `status`, `assigned_to`, `screenshot_path`, `created_by`, `created_at`, `updated_at`, `assigned_by`, `assigned_to_name`, `assigned_by_name`) VALUES ('13', 'TKT-0008', 'fdhz', 'Bank details issue', 'wfca', 'new', NULL, NULL, 'kri@gmail.com', '2025-08-17 00:31:23', '2025-08-17 00:31:23', NULL, NULL, NULL);
INSERT INTO `tickets` (`id`, `ticket_code`, `mobile_or_user_id`, `issue_type`, `issue_description`, `status`, `assigned_to`, `screenshot_path`, `created_by`, `created_at`, `updated_at`, `assigned_by`, `assigned_to_name`, `assigned_by_name`) VALUES ('14', 'TKT-0009', 'WFERTHYUI', 'Blocking user', 'EFDERTYUI', 'new', NULL, NULL, 'kri@gmail.com', '2025-08-17 00:34:25', '2025-08-17 00:34:25', NULL, NULL, NULL);

