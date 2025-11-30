/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.5.27-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: bombayengg
-- ------------------------------------------------------
-- Server version	10.5.27-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `mx_pump`
--

DROP TABLE IF EXISTS `mx_pump`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mx_pump` (
  `pumpID` int(11) NOT NULL AUTO_INCREMENT,
  `categoryPID` int(11) NOT NULL DEFAULT 0,
  `pumpTitle` varchar(50) DEFAULT NULL,
  `seoUri` varchar(200) DEFAULT NULL,
  `pumpImage` varchar(200) DEFAULT NULL,
  `pumpFeatures` text DEFAULT NULL,
  `kwhp` varchar(10) DEFAULT NULL,
  `supplyPhase` varchar(10) DEFAULT NULL,
  `deliveryPipe` varchar(10) DEFAULT NULL,
  `noOfStage` varchar(10) DEFAULT NULL,
  `isi` varchar(10) DEFAULT NULL,
  `mnre` varchar(10) DEFAULT NULL,
  `pumpType` varchar(50) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`pumpID`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mx_pump`
--

LOCK TABLES `mx_pump` WRITE;
/*!40000 ALTER TABLE `mx_pump` DISABLE KEYS */;
INSERT INTO `mx_pump` VALUES (1,2,'Centrifugal Monoset Pumps','centrifugal-monoset-pumps','borewell-submersible-pump-100w-v__530x530-1.png','<p>Monoset Construction with High Quality Mechanical Seal High Grade Electrical Stamping CRNGO-M47 for higher efficiency Works in wide voltage band effectively</p>','0.1 to 10,','1 Ph, 3 Ph','21 to 40,','0 to 10','Yes','No','Centrifugal Monoset',0),(2,3,'Janta Series','janta-series','borewell-submersible-pump-100w-v__530x530.png','<p>Monoset Construction with High Quality Mechanical Seal</p>\n<p>High Grade Electrical Stamping CRNGO-M47 for higher efficiency</p>\n<p>Works in wide voltage band effectively</p>',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0),(3,4,'V-4 Stainless Steel Pumps','v-4-stainless-steel-pumps','borewell-submersible-pump-100w-v__530x530-2.png','<p>Specially designed thrust bearing ensures highest reliability</p>\n<p>High Grade Electrical Stamping CRNGO-M47 for higher efficiency</p>\n<p>Wide voltage operation from 250 -440V</p>','0.1 to 10','1 Ph, 3 Ph','21 to 40','21 to 30,','No','No','Borewell Submersible',1),(4,5,'V-4 Stainless Steel Pumps','v-4-stainless-steel-pumps','borewell-submersible-pump-3w__530x530.png','<p>Specially designed thrust bearing ensures highest reliability</p>\n<p>High Grade Electrical Stamping CRNGO-M47 for higher efficiency</p>\n<p>Wide voltage operation from 250 -440V</p>','0.1 to 10','1 Ph, 3 Ph','1 Ph, 3 Ph','1 Ph, 3 Ph','Yes','No','Borewell Submersible',1),(5,6,'V-4 Water filled Motor','v-4-water-filled-motor','borewell-submersible-pump-100w-v__530x530-3.png','<p>Specially designed thrust bearing ensures highest reliability</p>\n<p>High Grade Electrical Stamping CRNGO-M47 for higher efficiency</p>\n<p>Wide voltage operation from 250 -440V</p>','0.1 to 10','1 Ph, 3 Ph','21 to 40,','0 to 10, 1','Yes','Yes','Borewell Submersible',0),(6,7,'V-6 50 feet per stage Pumps','v-6-50-feet-per-stage-pumps','horizontal-openwell__530x530.png','<p>Specially designed thrust bearing ensures highest reliability</p>\n<p>High Grade Electrical Stamping CRNGO-M47 for higher efficiency</p>\n<p>Wide voltage operation from 250 -440V</p>','0.1 to 10','3 Ph','81 and Abo','0 to 10, 1','yes','no',NULL,0),(7,12,'V-6 Water Filled Motor','v-6-water-filled-motor','v4-stainless-steel-pumps-1__530x530.png','<p></p>\n<p>Specially designed thrust bearing ensures highest reliability</p>\n<p>High Grade Electrical Stamping CRNGO-M47 for higher efficiency</p>\n<p>In built Check Valve prevents pumps parts from damage due to sudden back pressure</p>',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0),(8,8,'Centrifugal Monoset Pumps','centrifugal-monoset-pumps','v-6-50-feet-per-stage-pumps__530x530.png','<p>Specially designed thrust bearing ensures highest reliability</p>\n<p>High Grade Electrical Stamping CRNGO-M47 for higher efficiency</p>\n<p>In built Check Valve prevents pumps parts from damage due to sudden back pressure</p>','0.1 to 10','1 Ph, 3 Ph','21 to 40','0 to 10','Yes','No','Borewell Submersible',0),(9,13,'V-9 Water Filled Motor','v-9-water-filled-motor','v-6-50-feet-per-stage-pumps__530x530-1.png','<p>Specially designed thrust bearing ensures highest reliability</p>\n<p>High Grade Electrical Stamping CRNGO-M47 for higher efficiency</p>\n<p>In built Check Valve prevents pumps parts from damage due to sudden back pressure</p>','0.1 to 10','1 Ph, 3 Ph','21 to 40,','0 to 10','Yes','No','Centrifugal Monoset',0),(10,9,'Centrifugal Monoset Pumps','centrifugal-monoset-pumps','vertical-openwell__530x530.png','<p>Monoset Construction with High Quality Mechanical Seal</p>\n<p>High Grade Electrical Stamping CRNGO-M47 for higher efficiency</p>\n<p>Works in wide voltage band effectively</p>','0.1 to 10','3 Ph','21 to 40','0 to 10','Yes','No','Centrifugal Monoset',0),(11,2,'XYZ','xyz',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0),(12,17,'Pumps third level testing','pumps-third-level-testing','service-2.jpeg','<p><strong>Donate:</strong> If you use this site regularly and would like to help keep the site on the Internet, please consider donating a small sum to help pay for the hosting and bandwidth bill. There is no minimum donation, any sum is appreciated - click <a href=\"https://www.lipsum.com/donate\" target=\"_blank\">here</a> to donate using PayPal. Thank you for your support.</p>',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0),(13,18,'Third level testing 2nd','third-level-testing-2nd','service-3.jpeg','<h4>\"Neque porro quisquam est qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit...\"</h4>\n<h5>\"There is no one who loves pain itself, who seeks after it and wants to have it, simply because it is pain...\"</h5>',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0),(14,20,'V4 Water Filled Borewell Motor','v4-water-filled-borewell-motor',NULL,'<p>Capable of withstanding back pressure without sustaining damage<br />\nDurable and resilient mechanical construction<br />\nOptimized efficiency leading to energy conservation</p>','0.1 to 10','1ph, 3ph','21 to 40, ','0 to 10, 1','YES','NO','Borewell Submersible',1),(15,20,'V3 Oil Filled Motor','v3-oil-filled-motor',NULL,'<p>Resilient against back pressure, preventing any harm<br />\nUser-friendly maintenance<br />\nExtended lifespan attributed to a superior mechanical seal of exceptional quality</p>','0.1 to 10','1ph','21 to 40','0 to 10, 1','No','No','Borewell Submersible',1),(16,20,'V3 Water Filled Borewell Submersible Pump','v3-water-filled-borewell-submersible-pump',NULL,'<p>Can withstand back pressure without damage</p>\n<p>Strong and robust mechanical design</p>\n<p>High efficiency with energy saving</p>','0.1 to 10','1Ph','21 to 40','0 to 10, 1','No','No','Borewell Submersible',1),(17,20,'V4 Oil Filled Motor','v4-oil-filled-motor',NULL,'<p>Can withstand back pressure without damage</p>\n<p>Easy for maintenance</p>\n<p>Longer life due to high quality mechanical seal</p>','0.1 to 10','1ph','21 to 40, ','0 to 10, 1','Yes','No','Borewell Submersible',1),(18,20,'OW Water Filled Motor','ow-water-filled-motor',NULL,'<p>Specially designed thrust bearing ensures highest reliability</p>\n<p>High Grade Electrical Stamping CRNGO-M47 for higher efficiency</p>\n<p>Motor prefilled with coolant for better cooling</p>','0.1 to 10','1ph, 3ph','21 to 40','0 to 10','Yes','No','Openwell Submersible',1),(19,20,'OW – Dry Type Openwell Submersible Motor','ow-dry-type-openwell-submersible-motor',NULL,'<p>Specially designed thrust bearing ensures highest reliability</p>\n<p>High Grade Electrical Stamping CRNGO-M47 for higher efficiency</p>\n<p>Motor prefilled with coolant for better cooling</p>','0.1 to 10','1ph','21 to 40','0 to 10','No','No','Openwell Submersible',1),(20,6,'tata pump','tata-pump',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1);
/*!40000 ALTER TABLE `mx_pump` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mx_pump_detail`
--

DROP TABLE IF EXISTS `mx_pump_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mx_pump_detail` (
  `pumpDID` int(11) NOT NULL AUTO_INCREMENT,
  `pumpID` int(11) NOT NULL DEFAULT 0,
  `categoryref` varchar(250) DEFAULT NULL,
  `powerKw` double DEFAULT NULL,
  `powerHp` double DEFAULT NULL,
  `supplyPhaseD` int(11) DEFAULT NULL,
  `pipePhase` double DEFAULT NULL,
  `noOfStageD` int(11) DEFAULT NULL,
  `headRange` double DEFAULT NULL,
  `dischargeRange` varchar(100) DEFAULT NULL,
  `mrp` varchar(100) DEFAULT NULL,
  `warrenty` varchar(100) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`pumpDID`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mx_pump_detail`
--

LOCK TABLES `mx_pump_detail` WRITE;
/*!40000 ALTER TABLE `mx_pump_detail` DISABLE KEYS */;
INSERT INTO `mx_pump_detail` VALUES (8,1,'MAD052(1PH)Y-14',0.5,0.36,1,25,1,9,'90-50 LPM','4,100/-','12 Months',1),(19,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'4,400/-	',NULL,1),(20,3,'2.2',2.2,2.2,2,2.2,2,2.2,'2.2','2.2','2.2',1),(22,5,'4W12F5TPJ-VX',2.2,2.2,2,2.2,2,2.2,'2.2','2.2','2.2',1),(23,6,'6W5R5-65',3.7,5,3,2,8,80,'90-300 LPM','32,950/-','12 Months',1),(24,7,'6W14U5',90,2.2,3,1,8,80,'90-300 LPM','1200','90-300 LPM',1),(25,8,'6W5R5-65',90,5,1,2,1,80,'90-300 LPM','1200','12 Months',1),(26,9,'1 Ph, 3 Ph',3.7,0.36,2,2.2,2,2.2,'90-50 LPM','1200','1 Ph, 3 Ph',1),(28,10,'2.2',1,1,1,25,2,80,'2.2','4,100/-','12 Months',1),(32,4,'1 Ph, 3 Ph',1,1,1,1,1,1,'1 Ph, 3 Ph','1 Ph, 3 Ph','1 Ph, 3 Ph',1);
/*!40000 ALTER TABLE `mx_pump_detail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mx_pump_category`
--

DROP TABLE IF EXISTS `mx_pump_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mx_pump_category` (
  `categoryPID` int(11) NOT NULL AUTO_INCREMENT,
  `parentID` int(11) NOT NULL DEFAULT 0,
  `categoryTitle` varchar(250) DEFAULT NULL,
  `imageName` varchar(255) DEFAULT NULL,
  `synopsis` text DEFAULT NULL,
  `templateFile` varchar(100) DEFAULT NULL,
  `xOrder` int(11) NOT NULL DEFAULT 0,
  `seoUri` varchar(255) DEFAULT NULL,
  `langCode` varchar(4) DEFAULT NULL,
  `langChild` varchar(150) DEFAULT NULL,
  `parentLID` int(11) DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`categoryPID`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mx_pump_category`
--

LOCK TABLES `mx_pump_category` WRITE;
/*!40000 ALTER TABLE `mx_pump_category` DISABLE KEYS */;
INSERT INTO `mx_pump_category` VALUES (1,0,'Pump',NULL,NULL,NULL,0,'pump','en',NULL,0,1),(2,1,'Agricultural Pump',NULL,NULL,NULL,0,'pump/agricultural-pump','en',NULL,0,1),(3,2,'Borewell',NULL,NULL,NULL,0,'pump/agricultural-pump/borewell','en',NULL,0,1),(4,2,'CentriFugial',NULL,NULL,NULL,0,'pump/agricultural-pump/centrifugial','en',NULL,0,1),(5,2,'Open Well',NULL,NULL,NULL,0,'pump/agricultural-pump/open-well','en',NULL,0,1),(6,1,'Residential Pumps',NULL,NULL,NULL,0,'pump/residential-pumps','en',NULL,0,1),(7,6,'Borewell Submersible',NULL,NULL,NULL,0,'pump/residential-pumps/borewell-submersible','en',NULL,0,0),(8,6,'CentriFugial Deep Well Jet',NULL,NULL,NULL,0,'pump/residential-pumps/centrifugial-deep-well-jet','en',NULL,0,0),(9,6,'Heavy Duty Regenerative Self Priming',NULL,NULL,NULL,0,'pump/residential-pumps/heavy-duty-regenerative-self-priming','en',NULL,0,0),(10,6,'Self Priming Regenerative Mini',NULL,NULL,NULL,0,'pump/residential-pumps/self-priming-regenerative-mini','en',NULL,0,1),(11,6,'Single Pump Booster',NULL,NULL,NULL,0,'pump/residential-pumps/single-pump-booster','en',NULL,0,1),(12,6,'CentriFugial Shallow Well Jet',NULL,NULL,NULL,0,'pump/residential-pumps/centrifugial-shallow-well-jet','en',NULL,0,0),(13,6,'Compressor Pump',NULL,NULL,NULL,0,'pump/residential-pumps/compressor-pump','en',NULL,0,0),(14,6,'Inline Circulation Pump',NULL,NULL,NULL,0,'pump/residential-pumps/inline-circulation-pump','en',NULL,0,1),(15,6,'Open Well Submersible',NULL,NULL,NULL,0,'pump/residential-pumps/open-well-submersible','en',NULL,0,0),(16,6,'Residential Self Priming Pumps',NULL,NULL,NULL,0,'pump/residential-pumps/residential-self-priming-pumps','en',NULL,0,1),(17,5,'Testing 3 level one',NULL,NULL,NULL,0,'pump/agricultural-pump/open-well/testing-3-level-one','en',NULL,0,0),(18,5,'Testing 3rd level Two',NULL,NULL,NULL,0,'pump/agricultural-pump/open-well/testing-3rd-level-two','en',NULL,0,0),(19,5,'Pumps third level Category',NULL,'Testing',NULL,0,'pump/agricultural-pump/open-well/pumps-third-level-category','en',NULL,0,0),(20,6,'Residential Submersible Pumps',NULL,NULL,NULL,0,'pump/residential-pumps/residential-submersible-pumps','en',NULL,0,1),(21,6,'Residential Centrifugal Pumps',NULL,NULL,NULL,0,'pump/residential-pumps/residential-centrifugal-pumps','en',NULL,0,0),(22,0,'home pump',NULL,NULL,NULL,0,'home-pump','en',NULL,0,1);
/*!40000 ALTER TABLE `mx_pump_category` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-05 10:20:19
