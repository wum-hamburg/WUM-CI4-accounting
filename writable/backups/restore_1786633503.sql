-- MySQL dump 10.13  Distrib 8.2.0, for Win64 (x86_64)
--
-- Host: localhost    Database: wum-ci4-accounting
-- ------------------------------------------------------
-- Server version	8.2.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `articles`
--

DROP TABLE IF EXISTS `articles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `articles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `article_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `price_cents` mediumint NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `meta` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `article_number` (`article_number`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `articles`
--

LOCK TABLES `articles` WRITE;
/*!40000 ALTER TABLE `articles` DISABLE KEYS */;
INSERT INTO `articles` VALUES (1,'mbb24-hosting','Anteilige Hostinggebühr, alle erforderlichen monatlichen Arbeiten, im Zusammenhang mit der Webseite! Jedoch ohne größere Änderungen, diese müssen separat berechnet werden.',8845,1,'vierteljährlich',NULL,'2026-07-06 10:23:15','2026-07-06 10:23:15'),(2,'mbb24-domain','Jährliche Domainkosten für mbb-24.de',240,1,NULL,NULL,'2026-07-06 10:23:15','2026-07-06 10:23:15'),(3,'mbb24-sicherheit','Sicherheitspaket gemäß E-Mail:Upgrade CMS - System (Joomla6): 95€Impressum & Datenschutz: 77€',17200,0,'einmalig 2026',NULL,'2026-07-06 10:23:15','2026-07-06 17:00:09'),(4,'mbb24-sicherheit-jährlich','Aktualisierung von Impressum &amp; Datenschutz, wenn erforderlich\r\nGrundlage: Mein Abo bei Erecht24 als Basis\r\nKeine Rechtsberatung',1900,1,'NEU',NULL,'2026-07-06 10:23:15','2026-07-06 10:23:15'),(5,'silber-datensicherung','Monatliche Datensicherung mit Akeeba Backup\r\nPro, einschl. automatisierter Speicherung auf\r\nexterner deutscher Qloud (Hetzner)',995,1,'monatlich',NULL,'2026-07-06 10:23:15','2026-07-06 10:23:15'),(6,'silber-wartung','Joomla! Aktualisierungen nach Erfordernis,\r\nAktualisierungen von meinen Erweiterungen,\r\nwelche bei mir enthalten sind, JCE - Editor (ggf. Pro),\r\nAkeeba Backup (Pro),Template / Builder: Yootheme Pro\r\nBackupMonkey bis 31.12.2026 kostenlos',2150,1,'monatlich, ab 2027 +2€ für BM',NULL,'2026-07-06 10:23:15','2026-07-06 10:23:15'),(7,'ihrtaxi-domain','Jährliche Domainkosten für Endung .de (IhrTaxi-Hamburg.de)',2400,1,'(alt: 1,80/Monat)',NULL,'2026-07-06 10:23:15','2026-07-06 10:23:15'),(8,'ihrtaxi-email-postfach','Emailpostfach 3 Gigabyte, mit SPAM-Schutz und\r\nunendlich vielen Email- Adressen',6975,1,'jährlich (vorher 65,40)',NULL,'2026-07-06 10:23:15','2026-07-06 10:23:15'),(9,'ihrtaxi-programm','Updates und Pflege der Webseite, einschließlich\r\nDatensicherung (ohne Gewähr, Datensicherung ist per Hand)\r\nSpeicherung auf der Webseite &amp; bei Hetzner in externer deutscher Cloud',120,1,'jährlich (Preis bleibt konstant)',NULL,'2026-07-06 10:23:15','2026-07-06 10:23:15');
/*!40000 ALTER TABLE `articles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `customer_number` bigint DEFAULT NULL,
  `company` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `first_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `last_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `street` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `postal_code` int NOT NULL,
  `city` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Hamburg',
  `phone` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `meta` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_number` (`customer_number`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,10001,'Taxenbetrieb Frank Wiegmann','Frank','Wiegmann','Ellerneck 8e',22045,'Hamburg','','info@ihrtaxi-hamburg.de','',1,NULL,'2026-07-06 10:23:15','2026-07-06 10:23:15'),(2,10015,'vo.lan.di','Volker','Lange','Lübecker Straße 10',39576,'Stendal','','volandi@freepasswort.de','Altes Notebook OFFLINE',1,NULL,'2026-07-06 10:23:15','2026-07-06 10:23:15'),(3,10006,'Malereibetrieb Björn Behnke GmbH','Björn','Behnke','Eulenkamp 2',22049,'Hamburg','','rechnung@mbb-24.de','',1,NULL,'2026-07-06 10:23:15','2026-07-06 10:23:15'),(4,10014,'Reinhard Dreger e.K.','Reinhard','Dreger','Römeberg 9',21400,'Reinstorf OT Wendhausen','','info@silberring.info','',1,NULL,'2026-07-06 10:23:15','2026-07-06 10:23:15'),(5,10016,'TEST','Marko','Jorissen','Börnestr. 54',22089,'Hamburg','01773311590','camping2026@jorissen.de','TEST intern',1,NULL,'2026-07-06 10:23:15','2026-07-06 10:23:15');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `amount_cents` mediumint NOT NULL,
  `invoice_date` date NOT NULL,
  `customer_number` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `customer_last_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `filename` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `meta` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `source_quote_id` int unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES (6,'202601014',6000,'2026-04-01','10015','vo.lan.di',1,'202601014volandi.pdf',NULL,NULL,'2026-07-06 10:23:29','2026-07-06 10:23:29'),(7,'202601015',8130,'2026-04-01','10006','mbb-24',1,'202601015mbb-24.pdf',NULL,NULL,'2026-07-06 10:23:29','2026-07-06 10:23:29'),(8,'202601017',20700,'2026-04-01','10001','ihrtaxi-hamburg',1,'202601017ihrtaxi-hamburg.pdf',NULL,NULL,'2026-07-06 10:23:29','2026-07-14 12:41:32'),(9,'202603018',5970,'2026-03-31','10006','mbb-24',1,'202603018mbb-24.pdf',NULL,NULL,'2026-07-06 10:23:29','2026-07-06 10:23:29'),(10,'202601016',9435,'2026-04-01','10014','siberring',2,'202601016silbering.pdf',NULL,NULL,'2026-07-06 10:23:29','2026-07-14 12:41:03'),(11,'202601019',9435,'2026-03-31','10014','siberring',1,'202601019silbering.pdf',NULL,NULL,'2026-07-06 10:23:29','2026-07-06 10:23:29'),(12,'202607020',34890,'2026-07-04','10006','Malereibetrieb Björn Behnke GmbH',1,'202607020mbb-24.pdf',NULL,NULL,'2026-07-06 10:23:29','2026-07-06 10:23:29'),(13,'202607021',9435,'2026-07-04','10014','Reinhard Dreger e.K.',1,'202607021silberring.pdf',NULL,NULL,'2026-07-06 10:23:29','2026-07-06 10:23:29');
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `class` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `group` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `namespace` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `time` int NOT NULL,
  `batch` int unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026-07-06-100000','App\\Database\\Migrations\\CreateWumAccountingTables','default','App',1784206422,1),(2,'2026-07-07-100000','App\\Database\\Migrations\\AddDocumentPositions','default','App',1784206422,1),(3,'2026-07-14-100000','App\\Database\\Migrations\\ExpandMetaColumn','default','App',1784206422,1),(4,'2026-07-16-100000','App\\Database\\Migrations\\MigrateLegacyRightsAndDemoUser','default','App',1784206422,1),(5,'2026-07-31-100000','App\\Database\\Migrations\\CreateReceiptsTable','default','App',1785495653,2);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotes`
--

DROP TABLE IF EXISTS `quotes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quotes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `quote_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `amount_cents` mediumint NOT NULL,
  `quote_date` date NOT NULL,
  `customer_number` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `company_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `filename` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `meta` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `invoice_id` int unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quote_number` (`quote_number`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotes`
--

LOCK TABLES `quotes` WRITE;
/*!40000 ALTER TABLE `quotes` DISABLE KEYS */;
INSERT INTO `quotes` VALUES (3,'202601',20600,'2026-07-14','10016','TEST',3,'Angebot202601.pdf','{\"positions\":[{\"type\":\"custom\",\"title\":\"Upgrade Joomla! von  der aktuellen 5er auf die aktuelle 6er Version (einschl. Testlauf)\",\"qty\":1,\"price_cents\":20000,\"unit\":\"psch.\",\"position_number\":\"001\",\"position_source\":\"quote\"},{\"type\":\"custom\",\"title\":\"ab 2026 Pro: BackupMonkey (monatlich - hier zusätzliche Kosten je Quartal)\",\"qty\":3,\"price_cents\":200,\"unit\":\"Stck.\",\"position_number\":\"002\",\"position_source\":\"quote\"},{\"type\":\"custom\",\"title\":\"Alternativ: test\",\"qty\":5,\"price_cents\":600,\"unit\":\"Stck.\",\"position_number\":\"003\",\"position_source\":\"quote\"},{\"type\":\"custom\",\"title\":\"Eventual: e - Test\",\"qty\":0,\"price_cents\":30000,\"unit\":\"Stck.\",\"position_number\":\"004\",\"position_source\":\"quote\"}],\"texts\":{\"intro_header\":\"Sehr geehrte Damen und Herren,\\r\\nwir erlauben uns, wie folgt in Rechnung zu stellen:\",\"intro_items\":\"Angebotene Leistungen:\",\"footer\":\"Hinweis:\"}}',NULL,'2026-07-14 12:40:33','2026-07-14 12:59:35'),(4,'202602',500,'2026-07-14','10016','TEST',3,'Angebot202602.pdf','{\"positions\":[{\"type\":\"custom\",\"title\":\"Test\",\"qty\":1,\"price_cents\":500,\"unit\":\"Stck.\",\"position_number\":\"001\",\"position_source\":\"quote\"}],\"texts\":{\"intro_header\":\"Hello World\",\"intro_items\":\"Angebotene Leistungen: Test\",\"footer\":\"\"},\"email_sent\":true}',NULL,'2026-07-14 12:59:23','2026-07-16 13:11:30');
/*!40000 ALTER TABLE `quotes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `receipts`
--

DROP TABLE IF EXISTS `receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `receipts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `receipt_number` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `from_text` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `via_text` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `via2_text` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `to_text` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `time_text` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `waiting_time` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `agent` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `persons` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `price` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `receipt_date` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vessel` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `signature_data` mediumtext COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipt_number` (`receipt_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `receipts`
--

LOCK TABLES `receipts` WRITE;
/*!40000 ALTER TABLE `receipts` DISABLE KEYS */;
/*!40000 ALTER TABLE `receipts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `text_blocks`
--

DROP TABLE IF EXISTS `text_blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `text_blocks` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `text_key` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `meta` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `text_key` (`text_key`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `text_blocks`
--

LOCK TABLES `text_blocks` WRITE;
/*!40000 ALTER TABLE `text_blocks` DISABLE KEYS */;
INSERT INTO `text_blocks` VALUES (1,'vor1','Sehr geehrte Damen und Herren,\r\nwir erlauben uns, wie folgt in Rechnung zu stellen:',1,'Anrede',NULL,'2026-07-06 10:23:15','2026-07-06 10:23:15'),(2,'schluss1','Sofern nicht anders angegeben, entspricht das Liefer-/Leistungsdatum dem Rechnungsdatum. \r\nZahlungsbedingungen: innerhalb von 14 Werktagen netto Kasse\r\nIn dieser Rechnung ist gemäß der Kleinunternehmer-Regelung (§19 Abs. 1 UStG) keine Umsatzsteuer enthalten und ausgewiesen.',1,'Standard',NULL,'2026-07-06 10:23:15','2026-07-06 11:30:49'),(5,'hin1','Hinweis:',1,'Text erscheint direkt vor den Rechnungsartikeln in der Tabelle ()',NULL,'2026-07-06 10:23:15','2026-07-06 10:23:15'),(6,'hin-silber','Wartungsvertrag Nr. 02/25 Automatische vierteljährliche Verlängerung, Kündigung: 1 Monat zum Laufzeitende. \r\n\r\nHier: 3. Quartal 01.10.2026 bis 31.12.2026',1,'silberring-Wartungsvertrag',NULL,'2026-07-06 10:23:15','2026-07-06 11:40:18'),(7,'hin-taxi','Projekt-Domain: https://rechnung.ihr-taxi-hamburg.de Individuelle Buchhaltung mit Kassenbuch,\r\nRechnung und Wallet. Auswertungen für den Steuerberater',1,'ihrtaxi-Projekt',NULL,'2026-07-06 10:23:15','2026-07-06 11:40:43'),(8,'hin-mbb-24','Wartungsvertrag Nr. 01/18 (Vertragslaufzeit 1 Jahr). \r\nAutomatische jährliche Verlängerung!\r\nKündigung 1 Monat zum Quartalsende.\r\n\r\nHier: 4. Quartale: 01.01.2027 bis 31.12.2027',1,'mbb24 - Wartungsvertrag',NULL,'2026-07-06 10:23:15','2026-07-06 11:42:51');
/*!40000 ALTER TABLE `text_blocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user` (
  `id` tinyint(1) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `first_name` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '',
  `last_name` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `rights` varchar(10) COLLATE utf8mb3_unicode_ci DEFAULT 'tz67',
  `selection` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL COMMENT 'not used',
  PRIMARY KEY (`id`),
  UNIQUE KEY `userername` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,'wumy','$2y$10$qHgW2bcQDG/k/0t30JWe.OvlcT8NkTA3kMiZbRkTCEc7ZC/aoGBD6','Marko','Jorissen','2026-07-16 12:38:14','2023-05-07 09:27:40','superadmin','2025'),(2,'demo','$2y$10$9K2xGA8ByQU7.qceRYgsNu4Sr56NlC/JZpLX9SbZysMQMXPWIiOuq','Max','Tester','2026-07-28 13:15:06','2026-07-16 11:52:03','demo',NULL),(3,'hansen','$2y$10$HkaIIPJA3zCQTmc0NX2iC.BxWJMReEOQSb1d.qR9U/kU7c06lVGxa','Hermann','Hansen','2026-07-31 07:54:53','2026-07-31 07:54:53','demo',NULL);
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-10 19:18:54
