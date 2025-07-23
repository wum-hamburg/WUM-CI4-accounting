-- Adminer 4.8.1 MySQL 10.11.11-MariaDB-0+deb12u1 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` tinyint(1) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL DEFAULT '',
  `last_name` varchar(255) NOT NULL DEFAULT '',
  `language` varchar(2) DEFAULT 'de' COMMENT '"de" or "en" or something else',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `rights` varchar(10) DEFAULT 'registered' COMMENT 'and admin, superadmin',
  `selection` varchar(255) DEFAULT NULL COMMENT 'Only for tests, you see the password',
  PRIMARY KEY (`id`),
  UNIQUE KEY `userername` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `user` (`id`, `username`, `password`, `first_name`, `last_name`, `language`, `created_at`, `updated_at`, `rights`, `selection`) VALUES
(1,	'Test1',	'$2y$10$tvEx8t5vf2x7Yy0QBSNGI.Mrh2iQhJOPyNGSIBPjUK3WjDkqSyoWa',	'Gast',	'Deutschland',	'de',	'2025-07-23 13:48:02',	'2025-07-20 13:28:54',	'superadmin',	'PW: Start2025'),
(2,	'Test2',	'$2y$10$W3hZktdJIaVpwqVIOKT5t.8T68pxaCh3wt.wfrk2Se788Uk1EYuOW',	'Gast',	'England',	'en',	'2025-07-23 13:48:02',	'2025-07-23 13:45:04',	'superadmin',	'PW: Start2025'),
(3,	'Test3',	'$2y$10$lec9F0KRroL8Xfu2LwjIquBJn8Me/Ex/vSAQbRHhT0EsJGMSPtZPS',	'Gast',	'Frankreich',	'fr',	'2025-07-23 13:48:02',	'2025-07-23 13:45:46',	'superadmin',	'PW: Start2025'),
(4,	'Test4',	'$2y$10$IvKdg743x.2Qtfhm6T4w9.Kijqm1T3MuykPQ4M.3L73f7RTCIDEea',	'Gast',	'Espana',	'es',	'2025-07-23 13:48:02',	'2025-07-23 13:46:20',	'superadmin',	'PW: Start2025');

-- 2025-07-23 spain holiday
