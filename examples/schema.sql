DROP DATABASE IF EXISTS `barton`;
CREATE DATABASE `barton`;
USE barton;

-- MySQL dump 10.13  Distrib 8.0.30, for Linux (x86_64)
--
-- Host: localhost    Database: barton
-- ------------------------------------------------------
-- Server version	8.0.30-0ubuntu0.20.04.2

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
-- Table structure for table `bots`
--

DROP TABLE IF EXISTS `bots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bots` (
  `ip` varchar(40) NOT NULL DEFAULT '',
  `agent` text NOT NULL,
  `count` int DEFAULT NULL,
  `robots` int DEFAULT '0',
  `site` varchar(255) DEFAULT NULL,
  `creation_time` datetime DEFAULT NULL,
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`ip`,`agent`(254))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trig_bots` BEFORE INSERT ON `bots` FOR EACH ROW SET NEW.creation_time = NOW() */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `bots2`
--

DROP TABLE IF EXISTS `bots2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bots2` (
  `ip` varchar(40) NOT NULL DEFAULT '',
  `agent` text NOT NULL,
  `page` text,
  `date` date NOT NULL,
  `site` varchar(50) NOT NULL DEFAULT '',
  `which` int NOT NULL DEFAULT '0',
  `count` int DEFAULT NULL,
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`ip`,`agent`(254),`date`,`site`,`which`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `contact_emails`
--

DROP TABLE IF EXISTS `contact_emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_emails` (
  `id` int NOT NULL AUTO_INCREMENT,
  `site` varchar(255) DEFAULT NULL,
  `ip` varchar(255) DEFAULT NULL,
  `agent` varchar(255) DEFAULT NULL,
  `subject` text,
  `message` text,
  `verify` tinyint(1) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created` date DEFAULT NULL,
  `lasttime` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `counter`
--

DROP TABLE IF EXISTS `counter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `counter` (
  `filename` varchar(255) NOT NULL,
  `site` varchar(50) NOT NULL DEFAULT '',
  `count` int DEFAULT NULL,
  `realcnt` int DEFAULT '0',
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`filename`,`site`),
  KEY `site` (`site`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `counter2`
--

DROP TABLE IF EXISTS `counter2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `counter2` (
  `site` varchar(50) NOT NULL DEFAULT '',
  `date` date NOT NULL,
  `filename` varchar(255) NOT NULL DEFAULT '',
  `real` int DEFAULT '0',
  `bots` int DEFAULT '0',
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`site`,`date`,`filename`),
  KEY `site` (`site`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `daycounts`
--

DROP TABLE IF EXISTS `daycounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `daycounts` (
  `site` varchar(50) NOT NULL DEFAULT '',
  `date` date NOT NULL,
  `real` int DEFAULT '0',
  `bots` int DEFAULT '0',
  `visits` int DEFAULT '0',
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`site`,`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dayrecords`
--

DROP TABLE IF EXISTS `dayrecords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dayrecords` (
  `fid` int DEFAULT NULL,
  `ip` varchar(20) DEFAULT NULL,
  `site` varchar(20) DEFAULT NULL,
  `page` varchar(255) DEFAULT NULL,
  `finger` varchar(20) DEFAULT NULL,
  `jsin` varchar(10) DEFAULT NULL,
  `jsout` varchar(20) DEFAULT NULL,
  `dayreal` int DEFAULT NULL,
  `daybots` int DEFAULT NULL,
  `dayvisits` int DEFAULT NULL,
  `visits` smallint DEFAULT '0',
  `lasttime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `geo`
--

DROP TABLE IF EXISTS `geo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `geo` (
  `lat` varchar(50) NOT NULL,
  `lon` varchar(50) NOT NULL,
  `finger` varchar(100) DEFAULT NULL,
  `site` varchar(100) DEFAULT NULL,
  `ip` varchar(20) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `lasttime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ipcountry`
--

DROP TABLE IF EXISTS `ipcountry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipcountry` (
  `ipFROM` int(10) unsigned zerofill NOT NULL DEFAULT '0000000000',
  `ipTO` int(10) unsigned zerofill NOT NULL DEFAULT '0000000000',
  `countrySHORT` char(2) NOT NULL DEFAULT '',
  `countryLONG` varchar(255) NOT NULL DEFAULT ' ',
  PRIMARY KEY (`ipFROM`,`ipTO`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ipcountry6`
--

DROP TABLE IF EXISTS `ipcountry6`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipcountry6` (
  `ipFROM` decimal(39,0) NOT NULL,
  `ipTO` decimal(39,0) NOT NULL,
  `countrySHORT` char(2) NOT NULL,
  `countryLONG` varchar(256) NOT NULL,
  PRIMARY KEY (`ipFROM`,`ipTO`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ipcountry6Save`
--

DROP TABLE IF EXISTS `ipcountry6Save`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipcountry6Save` (
  `ipFROM` decimal(39,0) NOT NULL,
  `ipTO` decimal(39,0) NOT NULL,
  `countrySHORT` char(2) NOT NULL,
  `countryLONG` varchar(256) NOT NULL,
  PRIMARY KEY (`ipFROM`,`ipTO`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ipcountrySave`
--

DROP TABLE IF EXISTS `ipcountrySave`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipcountrySave` (
  `ipFROM` int(10) unsigned zerofill NOT NULL DEFAULT '0000000000',
  `ipTO` int(10) unsigned zerofill NOT NULL DEFAULT '0000000000',
  `countrySHORT` char(2) NOT NULL DEFAULT '',
  `countryLONG` varchar(255) NOT NULL DEFAULT ' ',
  PRIMARY KEY (`ipFROM`,`ipTO`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `logagent`
--

DROP TABLE IF EXISTS `logagent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `logagent` (
  `site` varchar(25) NOT NULL DEFAULT '',
  `ip` varchar(40) NOT NULL DEFAULT '',
  `agent` text NOT NULL,
  `finger` varchar(20) DEFAULT NULL,
  `count` int DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`site`,`ip`,`agent`(254)),
  KEY `ip` (`ip`),
  KEY `site` (`site`),
  KEY `created` (`created`),
  KEY `lasttime` (`lasttime`),
  KEY `agent` (`agent`(254))
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 PACK_KEYS=1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `myip`
--

DROP TABLE IF EXISTS `myip`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `myip` (
  `myIp` varchar(40) NOT NULL DEFAULT '',
  `count` int DEFAULT '0',
  `createtime` datetime DEFAULT NULL,
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`myIp`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tracker`
--

DROP TABLE IF EXISTS `tracker`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tracker` (
  `id` int NOT NULL AUTO_INCREMENT,
  `botAs` varchar(30) DEFAULT NULL,
  `site` varchar(25) DEFAULT NULL,
  `page` varchar(255) NOT NULL DEFAULT '',
  `finger` varchar(50) DEFAULT NULL,
  `nogeo` tinyint(1) DEFAULT NULL,
  `ip` varchar(40) DEFAULT NULL,
  `agent` text,
  `referer` varchar(255) DEFAULT '',
  `starttime` datetime DEFAULT NULL,
  `endtime` datetime DEFAULT NULL,
  `difftime` varchar(20) DEFAULT NULL,
  `isJavaScript` int DEFAULT '0',
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `site` (`site`),
  KEY `ip` (`ip`),
  KEY `lasttime` (`lasttime`),
  KEY `starttime` (`starttime`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-08-14  2:00:01
