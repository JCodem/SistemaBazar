-- MySQL dump 10.13  Distrib 8.0.42, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: pos
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `precio` int(11) NOT NULL DEFAULT 0,
  `stock` int(11) NOT NULL DEFAULT 0,
  `sku` varchar(100) DEFAULT NULL,
  `codigo_barras` varchar(100) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` VALUES (52,'Arroz Tucapel 1kg',1290,50,'ARR-TUC-1KG','7804650001234','2025-07-17 10:30:39'),(53,'Fideos Carozzi Espagueti 400g',890,75,'FID-CAR-ESP400','7804650005678','2025-07-17 10:30:39'),(54,'Aceite Chef 1lt',2350,30,'ACE-CHF-1LT','7804650009012','2025-07-17 10:30:39'),(55,'Azúcar Iansa 1kg',1150,40,'AZU-IAN-1KG','7804650003456','2025-07-17 10:30:39'),(56,'Sal de Mesa 1kg',650,60,'SAL-MES-1KG','7804650007890','2025-07-17 10:30:39'),(57,'Harina Selecta 1kg',980,35,'HAR-SEL-1KG','7804650002345','2025-07-17 10:30:39'),(58,'Lentejas 500g',1250,25,'LEN-500G','7804650006789','2025-07-17 10:30:39'),(59,'Porotos Negros 500g',1380,20,'POR-NEG-500G','7804650004567','2025-07-17 10:30:39'),(60,'Té Supremo 100 sobres',2890,15,'TE-SUP-100S','7804650008901','2025-07-17 10:30:39'),(61,'Café Nescafé 170g',4250,18,'CAF-NES-170G','7804650001123','2025-07-17 10:30:39'),(62,'Leche Soprole 1lt',950,45,'LEC-SOP-1LT','7804650002234','2025-07-17 10:30:39'),(63,'Yogurt Natural Soprole 150g',450,30,'YOG-SOP-150G','7804650003345','2025-07-17 10:30:39'),(64,'Mantequilla Colún 250g',1850,20,'MAN-COL-250G','7804650004456','2025-07-17 10:30:39'),(65,'Queso Gouda Colún 200g',2950,15,'QUE-GOU-200G','7804650005567','2025-07-17 10:30:39'),(66,'Huevos Blancos x12',2100,25,'HUE-BLA-12U','7804650006678','2025-07-17 10:30:39'),(67,'Jamón de Pavo PF 200g',2450,12,'JAM-PAV-200G','7804650007789','2025-07-17 10:30:39'),(68,'Salchichas Vienesa San Jorge 250g',1650,18,'SAL-VIE-250G','7804650008890','2025-07-17 10:30:39'),(69,'Chorizo Parrillero 500g',3250,10,'CHO-PAR-500G','7804650009901','2025-07-17 10:30:39'),(70,'Coca Cola 1.5lt',1750,40,'COC-COL-1.5L','7804650001012','2025-07-17 10:30:39'),(71,'Agua Mineral Benedictino 1.5lt',890,50,'AGU-BEN-1.5L','7804650002123','2025-07-17 10:30:39'),(72,'Jugo Watts Naranja 1lt',1350,25,'JUG-WAT-NAR1L','7804650003234','2025-07-17 10:30:39'),(73,'Cerveza Cristal 350ml',950,60,'CER-CRI-350ML','7804650004345','2025-07-17 10:30:39'),(74,'Vino Santa Rita 750ml',3890,8,'VIN-STA-750ML','7804650005456','2025-07-17 10:30:39'),(75,'Papas Fritas Marco Polo 150g',1150,35,'PAP-MAR-150G','7804650006567','2025-07-17 10:30:39'),(76,'Galletas McKay Soda 300g',980,40,'GAL-MCK-SOD300','7804650007678','2025-07-17 10:30:39'),(77,'Chocolate Sahne-Nuss 155g',1890,20,'CHO-SAH-155G','7804650008789','2025-07-17 10:30:39'),(78,'Caramelos Ambrosoli 100g',650,45,'CAR-AMB-100G','7804650009890','2025-07-17 10:30:39'),(79,'Maní Salado Nutrabien 150g',890,30,'MAN-NUT-150G','7804650001901','2025-07-17 10:30:39'),(80,'Detergente Drive 1kg',2350,25,'DET-DRI-1KG','7804650002012','2025-07-17 10:30:39'),(81,'Jabón Dove 90g',1250,40,'JAB-DOV-90G','7804650003123','2025-07-17 10:30:39'),(82,'Shampoo Head & Shoulders 400ml',3450,15,'SHA-HEA-400ML','7804650004234','2025-07-17 10:30:39'),(83,'Papel Higiénico Noble 4 rollos',2890,30,'PAP-NOB-4R','7804650005345','2025-07-17 10:30:39'),(84,'Cloro Clorox 1lt',1150,20,'CLO-CLO-1LT','7804650006456','2025-07-17 10:30:39'),(85,'Pasta Dental Colgate 100ml',1650,35,'PAS-COL-100ML','7804650007567','2025-07-17 10:30:39'),(86,'Desodorante Rexona 150ml',2250,25,'DES-REX-150ML','7804650008678','2025-07-17 10:30:39'),(87,'Cepillo Dental Oral-B',1450,20,'CEP-ORA-UNI','7804650009789','2025-07-17 10:30:39'),(88,'Plátano',1200,0,'FRU-PLA-KG','2000000001','2025-07-17 10:30:39'),(89,'Manzana Roja',1500,0,'FRU-MAN-KG','2000000002','2025-07-17 10:30:39'),(90,'Palta Hass',2800,0,'FRU-PAL-KG','2000000003','2025-07-17 10:30:39'),(91,'Tomate',1800,0,'VER-TOM-KG','2000000004','2025-07-17 10:30:39'),(92,'Cebolla',900,0,'VER-CEB-KG','2000000005','2025-07-17 10:30:39'),(93,'Papa',800,0,'VER-PAP-KG','2000000006','2025-07-17 10:30:39'),(94,'Zanahoria',750,0,'VER-ZAN-KG','2000000007','2025-07-17 10:30:39'),(95,'Lechuga',650,0,'VER-LEC-UNI','2000000008','2025-07-17 10:30:39'),(96,'Pan Marraqueta',120,0,'PAN-MAR-UNI','3000000001','2025-07-17 10:30:39'),(97,'Pan de Molde Ideal',1350,15,'PAN-IDE-MOL','7804650010123','2025-07-17 10:30:39'),(98,'Sopaipillas (6 unidades)',1200,0,'PAN-SOP-6U','3000000002','2025-07-17 10:30:39'),(99,'Pilas AA Duracell x4',2890,20,'PIL-DUR-AA4','7804650011234','2025-07-17 10:30:39'),(100,'Encendedor BIC',650,25,'ENC-BIC-UNI','7804650012345','2025-07-17 10:30:39'),(101,'Bolsas Basura 50lt x10',1890,15,'BOL-BAS-50L10','7804650013456','2025-07-17 10:30:39'),(102,'Servilletas Elite 100u',890,30,'SER-ELI-100U','7804650014567','2025-07-17 10:30:39');
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-07-17  6:31:50
