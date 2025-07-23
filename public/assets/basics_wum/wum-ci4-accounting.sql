-- Adminer 4.8.1 MySQL 10.11.11-MariaDB-0+deb12u1 dump
-- wum-ci4-accounting 

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `article`;
CREATE TABLE `article` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `language` varchar(2) DEFAULT NULL COMMENT '"de" or "en" or something else',
  `articlenumber` varchar(50) DEFAULT NULL,
  `article` mediumtext NOT NULL,
  `preis` mediumint(9) NOT NULL COMMENT 'in Cent',
  `status` tinyint(1) DEFAULT 1 COMMENT '1- Standard 2-Extra',
  `remark` varchar(255) DEFAULT NULL,
  `selection` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


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
  `selection` varchar(255) DEFAULT NULL COMMENT 'not used',
  PRIMARY KEY (`id`),
  UNIQUE KEY `userername` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `user` (`id`, `username`, `password`, `first_name`, `last_name`, `language`,`created_at`, `updated_at`, `rights`, `selection`) VALUES
(1,	'wumy',	'$2y$10$qHgW2bcQDG/k/0t30JWe.OvlcT8NkTA3kMiZbRkTCEc7ZC/aoGBD6',	'Marko',	'Jorissen','de',	'2025-04-06 15:31:37',	'2023-05-07 09:27:40',	'superadmin','2025');

DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cutomernumber` bigint(20) DEFAULT NULL COMMENT 'old: "WISO Mein Büro" ongoing',
  `company` varchar(50) DEFAULT NULL,
  `first_name` varchar(255) NOT NULL DEFAULT '',
  `last_name` varchar(255) NOT NULL DEFAULT '',
  `street` varchar(100) NOT NULL,
  `postal_code` int(11) NOT NULL,
  `city` varchar(100) NOT NULL DEFAULT 'Hamburg',
  `telephon` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL COMMENT 'per examble: pay late',
  `status` tinyint(1) DEFAULT 1 COMMENT '0-delete 1- available 2- unique',
  `selection` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

INSERT INTO `customers` (`id`, `cutomernumber`, `company`, `first_name`, `last_name`, `street`, `postal_code`, `city`, `telephon`, `email`, `remark`, `status`, `selection`) VALUES
(5,	10006,	'Malereibetrieb Björn Behnke GmbH',	'Björn',	'Behnke',	'Eulenkamp 2',	22049,	'Hamburg',	'00491622465666',	'rechnung@mbb-24.de',	'',	1,	NULL),
(6,	10007,	'Test mit Email',	'Marko',	'Jorissen',	'Börnestr. 54',	22089,	'Hamburg',	'204244',	'test@jorissen.de',	'Test',	2,	NULL),
(8,	10001,	'Taxenbetrieb Frank Wiegmann',	'Frank',	'Wiegmann',	'Ellerneck 8e',	22045,	'Hamburg',	NULL,	'info@ihrtaxi-hamburg.de',	'1. Kunde (mit Hedy)',	1,	NULL),
(9,	10014,	'Reinhard Dreger e.K.',	'Reinhard',	'Dreger',	'Römeberg 9',	21400,	'Reinstorf OT Wendhausen',	'04137 - 664 98 46',	'r.dreger@silberring.info',	'2024',	1,	NULL);

DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoicesnumber` varchar(20) NOT NULL,
  `amount` mediumint(9) NOT NULL COMMENT 'in Cent (83.886,07€)',
  `date` varchar(16) NOT NULL,
  `tax_advisor` tinyint(4) DEFAULT NULL COMMENT 'german: Steuerberater - not used today',
  `customernumber` varchar(12) NOT NULL,
  `company` varchar(50) NOT NULL,
  `status` tinyint(1) DEFAULT NULL COMMENT '1-open , 2-payed, 9-Correction invoice (german: Korrekturrechnung) - not used today',
  `dateiname` varchar(50) DEFAULT NULL COMMENT 'copy',
  `selection` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

INSERT INTO `invoices` (`id`, `invoicesnumber`, `amount`, `date`, `tax_advisor`, `customernumber`, `company`, `status`, `dateiname`, `selection`) VALUES
(1,	'202501001',	2160,	'02.01.2025',	0,	'10012',	'Mobiler Caravan Gasprüfer',	1,	'Rechnung_202501001.pdf',	NULL),
(2,	'202501002',	6000,	'02.01.2025',	NULL,	'10011',	'vo.lan.di',	1,	'Rechnung_202501002.pdf',	NULL),
(3,	'202501003',	8700,	'02.01.2025',	NULL,	'10001',	'Taxenbetrieb Frank Wiegmnann',	1,	'Rechnung_202501003.pdf',	NULL),
(4,	'202501004',	8130,	'02.01.2025',	NULL,	'10006',	'Malereibetrieb Björn Behnke GmbH',	1,	'Rechnung_202501004.pdf',	NULL),
(5,	'202501005',	35029,	'06.01.2025',	NULL,	'10014',	'Antiques & Design Objects',	1,	'Rechnung_202501005.pdf',	NULL),
(6,	'202504006',	9435,	'06.04.2025',	NULL,	'10014',	'Reinhard Dreger e.K.',	1,	'Rechnung_202504006.pdf',	NULL),
(7,	'202504007',	5970,	'06.04.2025',	NULL,	'10006',	'Malereibetrieb Björn Behnke GmbH',	1,	'Rechnung_202504007.pdf',	NULL),
(8,	'202504008',	6000,	'06.04.2025',	NULL,	'10001',	'Taxenbetrieb Frank Wiegmann',	1,	'Rechnung_202504008.pdf',	NULL),
(9,	'202507009',	9435,	'01.07.2025',	NULL,	'10014',	'Reinhard Dreger e.K.',	1,	'Rechnung_202507009.pdf',	NULL),
(10,	'202507010',	6000,	'01.07.2025',	NULL,	'10001',	'Taxenbetrieb Frank Wiegmann',	1,	'Rechnung_202507010.pdf',	NULL),
(11,	'202507011',	5970,	'01.07.2025',	NULL,	'10006',	'Malereibetrieb Björn Behnke GmbH',	1,	'Rechnung_202507011.pdf',	NULL);

DROP TABLE IF EXISTS `texts`;
CREATE TABLE `texts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `language` varchar(5) DEFAULT NULL COMMENT '"de-DE" or "en-EN"',
  `textnumber` varchar(50) DEFAULT NULL,
  `text` text NOT NULL,
  `status` tinyint(1) DEFAULT 1 COMMENT '1- Standard 2-Extra',
  `remark` varchar(255) DEFAULT NULL,
  `selection` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

INSERT INTO `texts` (`id`, `language`,`textnumber`, `text`, `status`, `remark`, `selection`) VALUES
(1,	'de','vor1',	'Sehr geehrte Damen und Herren,\nwir erlauben uns, wie folgt Rechnung zu stellen:',	1,	NULL,	NULL),
(3,	'de','schluss1',	'Sofern nicht anders angegeben, entspricht das Liefer-/Leistungsdatum dem Rechnungsdatum.  \r\n                                   \r\nZahlungsbedingungen: innerhalb von 14 Werktagen netto Kasse.                                                                                                                                            \r\n                                                     In dieser Rechnung ist gemäß der Kleinunternehmer-Regelung (§19 Abs. 1 UStG)                        keine Umsatzsteuer enthalten und  ausgewiesen.',	1,	'STANDARD',	NULL),
(8,	'de','hin2',	'Hinweis:',	1,	'Text erscheint direkt vor den Rechnungsartikeln in der Tabelle ()',	NULL),
(9,	'de','hin1',	'Wartungsvertrag  Nr. 01/18 (Vertragslaufzeit 1 Jahr: 01.01.2025 - 31.03.2025) Laufzeit: 1 Jahr (Domain) - Zahlung nach Erhalt der Rechnung vierteljährlich im Voraus. Automatische vierteljährliche Verlängerung Kündigung 1 Monat zum Vertragsende bzw. anschließend zum Laufzeitende.',	1,	'Malereibetrieb Björn Behnke',	NULL),
(10,'de',	'hin3',	'Projekt-Domain: https://rechnung.ihr-taxi-hamburg.de \r\n\r\nIndividuelle Buchhaltung mit Kassenbuch, Rechnung und Wallet. \r\n\r\nAuswertungen für den Steuerberater',	1,	'Taxi',	NULL),
(11,'de',	'hin4',	'Wartungsvertrag  Nr. 02/25 (Vertragslaufzeit 1 Jahr: 01.12.2025 - 31.12.2025) Laufzeit: 1 Jahr Zahlung nach Erhalt der Rechnung vierteljährlich im Vorraus. Danach automatische vierteljährliche Verlängerung Kündigung 1 Monat zum Vertragsende bzw. anschließend zum Laufzeitende. Hier: 1. Quartal 01.01.2025 bis 31.03.2025',	1,	'Silberring.info',	NULL);

-- 2025-07-15 12:11:40
