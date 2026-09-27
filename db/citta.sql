-- ==========================================================================
-- Città Empreendimentos — dump completo do banco "citta" (schema + dados)
-- ==========================================================================
-- Gerado localmente (XAMPP/MariaDB) via mysqldump, já com o catálogo de
-- casas e terrenos migrado de data/*.json (removidos — o banco agora é a
-- fonte da verdade) e o usuário admin (admin / 12345, hash bcrypt).
--
-- ATENÇÃO: este arquivo tem "DROP DATABASE IF EXISTS citta" logo abaixo —
-- rodá-lo APAGA e recria o banco "citta" inteiro. Use para a importação
-- inicial em produção; se já existir um "citta" em produção com dados que
-- não estão aqui, faça backup antes de importar.
--
-- Como importar em produção:
--   mysql -u SEU_USUARIO -p < db/citta.sql
-- ou pelo phpMyAdmin: aba "Importar" -> selecionar este arquivo -> Executar.
--
-- Depois de importar:
--  1. Configure as credenciais reais em admin/config.local.php (nunca
--     versionado — ver .gitignore; copie a estrutura de admin/config.php).
--  2. Troque a senha do admin (admin/12345 é só o valor inicial). Gere um
--     hash novo com `php -r "echo password_hash('nova-senha', PASSWORD_DEFAULT);"`
--     e rode: UPDATE admin_users SET password_hash='...' WHERE username='admin';
-- ==========================================================================

-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: citta
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Current Database: `citta`
--

/*!40000 DROP DATABASE IF EXISTS `citta`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `citta` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `citta`;

--
-- Table structure for table `admin_users`
--

DROP TABLE IF EXISTS `admin_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(60) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_users_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_users`
--

LOCK TABLES `admin_users` WRITE;
/*!40000 ALTER TABLE `admin_users` DISABLE KEYS */;
INSERT INTO `admin_users` VALUES (1,'admin','$2y$10$oyBCrtrXyuiPxWyIby4UXOkcYPySgHmPguoCXrOcr/39aBsclbgR2','2026-09-12 12:29:18');
/*!40000 ALTER TABLE `admin_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `casas`
--

DROP TABLE IF EXISTS `casas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `casas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `slug` varchar(80) NOT NULL,
  `status` enum('disponivel','reservada','vendida','indisponivel') NOT NULL DEFAULT 'disponivel',
  `destaque` tinyint(1) NOT NULL DEFAULT 0,
  `ordem` int(11) NOT NULL DEFAULT 1,
  `titulo` varchar(255) NOT NULL,
  `descricao` varchar(500) NOT NULL DEFAULT '',
  `localizacao` varchar(255) NOT NULL DEFAULT '',
  `condominio` varchar(120) NOT NULL DEFAULT '',
  `area_terreno` decimal(10,2) DEFAULT NULL,
  `area_construida` decimal(10,2) DEFAULT NULL,
  `quartos` smallint(5) unsigned DEFAULT NULL,
  `suites` smallint(5) unsigned DEFAULT NULL,
  `banheiros` smallint(5) unsigned DEFAULT NULL,
  `vagas` smallint(5) unsigned DEFAULT NULL,
  `caracteristicas` text DEFAULT NULL COMMENT 'JSON array de strings',
  `imagens_pasta` varchar(255) NOT NULL COMMENT 'ex: img/casas/F08',
  `imagens_largura` int(11) NOT NULL DEFAULT 640,
  `imagens_altura` int(11) NOT NULL DEFAULT 480,
  `imagens_arquivos` text NOT NULL COMMENT 'JSON array de nomes de arquivo, em ordem (capa = primeiro)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_casas_codigo` (`codigo`),
  UNIQUE KEY `uq_casas_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `casas`
--

LOCK TABLES `casas` WRITE;
/*!40000 ALTER TABLE `casas` DISABLE KEYS */;
INSERT INTO `casas` VALUES (1,'F08','f08','disponivel',1,1,'Casa à venda em Jundiaí/SP - Lote F08','Projeto Residencial - Condomínio Terras de Gênova','Condomínio Terras de Gênova, Jundiaí/SP','Terras de Gênova',250.00,195.24,NULL,NULL,NULL,NULL,'[]','img/casas/F08',1024,576,'[\"projeto-de-construcao-de-casa-f08-06.jpg\",\"projeto-de-construcao-de-casa-f08-01.jpg\",\"projeto-de-construcao-de-casa-f08-02.jpg\",\"projeto-de-construcao-de-casa-f08-03.jpg\",\"projeto-de-construcao-de-casa-f08-04.jpg\",\"projeto-de-construcao-de-casa-f08-05.jpg\",\"projeto-de-construcao-de-casa-f08-07.jpg\",\"projeto-de-construcao-de-casa-f08-08.jpg\"]','2026-09-12 12:32:32','2026-09-12 12:32:32'),(2,'I03','i03','disponivel',1,2,'Casa à venda em Jundiaí/SP - Lote I03','Projeto Residencial - Condomínio Terras de Gênova','Condomínio Terras de Gênova, Jundiaí/SP','Terras de Gênova',250.00,179.90,NULL,NULL,NULL,NULL,'[]','img/casas/I03',1280,762,'[\"projeto-de-construcao-de-casa-i03-6.jpg\",\"projeto-de-construcao-de-casa-i03-1.jpg\",\"projeto-de-construcao-de-casa-i03-2.jpg\",\"projeto-de-construcao-de-casa-i03-3.jpg\",\"projeto-de-construcao-de-casa-i03-4.jpg\",\"projeto-de-construcao-de-casa-i03-5.jpg\",\"projeto-de-construcao-de-casa-i03-7.jpg\"]','2026-09-12 12:32:32','2026-09-12 12:32:32'),(3,'C08','c08','disponivel',1,3,'Casa à venda em Jundiaí/SP - Lote C08','Projeto Residencial - Condomínio Terras de Gênova','Condomínio Terras de Gênova, Jundiaí/SP','Terras de Gênova',285.19,160.76,3,1,2,2,'[\"Vaga coberta para 2 autos\",\"Sala de estar\",\"Lavabo\",\"Sala de jantar integrada à cozinha\",\"Cozinha integrada\",\"Espaço Gourmet\",\"Lavanderia\",\"Varanda coberta\",\"Jardim interno\",\"Depósito para guarda de objetos\",\"Hall de circulação\",\"Dormitório 1: Suíte com closet, banho e varanda\",\"Dormitório 2\",\"Dormitório 3\",\"Banho comum\"]','img/casas/C08',640,480,'[\"projeto-de-construcao-de-casa-c08-6.jpg\",\"projeto-de-construcao-de-casa-c08-1.jpg\",\"projeto-de-construcao-de-casa-c08-2.jpg\",\"projeto-de-construcao-de-casa-c08-3.jpg\",\"projeto-de-construcao-de-casa-c08-4.jpg\",\"projeto-de-construcao-de-casa-c08-5.jpg\",\"projeto-de-construcao-de-casa-c08-7.jpg\"]','2026-09-12 12:32:32','2026-09-12 12:32:32'),(4,'A04','a04','vendida',0,4,'Casa à venda em Jundiaí/SP - Lote A04','Projeto Residencial - Condomínio Terras de Gênova','Condomínio Terras de Gênova, Jundiaí/SP','Terras de Gênova',261.19,187.10,NULL,NULL,NULL,NULL,'[]','img/casas/A04',640,480,'[\"projeto-de-construcao-de-casa-a04-1.jpg\",\"projeto-de-construcao-de-casa-a04-2.jpg\",\"projeto-de-construcao-de-casa-a04-3.jpg\",\"projeto-de-construcao-de-casa-a04-4.jpg\",\"projeto-de-construcao-de-casa-a04-5.jpg\",\"projeto-de-construcao-de-casa-a04-6.jpg\",\"projeto-de-construcao-de-casa-a04-7.jpg\",\"projeto-de-construcao-de-casa-a04-8.jpg\"]','2026-09-12 12:32:32','2026-09-12 12:32:32'),(5,'A06','a06','vendida',0,5,'Casa à venda em Jundiaí/SP - Lote A06','Projeto Residencial - Condomínio Terras de Gênova','Condomínio Terras de Gênova, Jundiaí/SP','Terras de Gênova',251.18,175.23,NULL,NULL,NULL,NULL,'[]','img/casas/A06',640,480,'[\"projeto-de-construcao-de-casa-a06-1.jpg\",\"projeto-de-construcao-de-casa-a06-2.jpg\",\"projeto-de-construcao-de-casa-a06-3.jpg\",\"projeto-de-construcao-de-casa-a06-4.jpg\",\"projeto-de-construcao-de-casa-a06-5.jpg\",\"projeto-de-construcao-de-casa-a06-6.jpg\"]','2026-09-12 12:32:32','2026-09-12 12:32:32'),(6,'C09','c09','vendida',0,6,'Casa à venda em Jundiaí/SP - Lote C09','Projeto Residencial - Condomínio Terras de Gênova','Condomínio Terras de Gênova, Jundiaí/SP','Terras de Gênova',250.00,176.44,NULL,NULL,NULL,NULL,'[]','img/casas/C09',640,488,'[\"projeto-de-construcao-de-casa-c09-1.jpg\",\"projeto-de-construcao-de-casa-c09-2.jpg\",\"projeto-de-construcao-de-casa-c09-3.jpg\",\"projeto-de-construcao-de-casa-c09-4.jpg\",\"projeto-de-construcao-de-casa-c09-5.jpg\",\"projeto-de-construcao-de-casa-c09-6.jpg\",\"projeto-de-construcao-de-casa-c09-7.jpg\"]','2026-09-12 12:32:32','2026-09-12 12:32:32'),(7,'D09','d09','vendida',0,7,'Casa à venda em Jundiaí/SP - Lote D09','Projeto Residencial - Condomínio Terras de Gênova','Condomínio Terras de Gênova, Jundiaí/SP','Terras de Gênova',265.30,157.27,NULL,NULL,NULL,NULL,'[]','img/casas/D09',640,480,'[\"projeto-de-construcao-de-casa-d09-1.jpg\",\"projeto-de-construcao-de-casa-d09-2.jpg\",\"projeto-de-construcao-de-casa-d09-3.jpg\",\"projeto-de-construcao-de-casa-d09-4.jpg\",\"projeto-de-construcao-de-casa-d09-5.jpg\",\"projeto-de-construcao-de-casa-d09-6.jpg\"]','2026-09-12 12:32:32','2026-09-12 12:32:32'),(8,'H03','h03','vendida',0,8,'Casa à venda em Jundiaí/SP - Lote H03','Projeto Residencial - Condomínio Terras de Gênova','Condomínio Terras de Gênova, Jundiaí/SP','Terras de Gênova',250.00,222.07,NULL,NULL,NULL,NULL,'[]','img/casas/H03',640,480,'[\"projeto-de-construcao-de-casa-h03-1.jpg\",\"projeto-de-construcao-de-casa-h03-2.jpg\",\"projeto-de-construcao-de-casa-h03-3.jpg\",\"projeto-de-construcao-de-casa-h03-4.jpg\",\"projeto-de-construcao-de-casa-h03-5.jpg\",\"projeto-de-construcao-de-casa-h03-6.jpg\"]','2026-09-12 12:32:32','2026-09-12 12:32:32'),(9,'I10','i10','vendida',0,9,'Casa à venda em Jundiaí/SP - Lote I10','Projeto Residencial - Condomínio Terras de Gênova','Condomínio Terras de Gênova, Jundiaí/SP','Terras de Gênova',250.00,162.82,NULL,NULL,NULL,NULL,'[]','img/casas/I10',640,480,'[\"projeto-de-construcao-de-casa-i10-1.jpg\",\"projeto-de-construcao-de-casa-i10-2.jpg\",\"projeto-de-construcao-de-casa-i10-3.jpg\",\"projeto-de-construcao-de-casa-i10-4.jpg\",\"projeto-de-construcao-de-casa-i10-5.jpg\",\"projeto-de-construcao-de-casa-i10-6.jpg\"]','2026-09-12 12:32:32','2026-09-12 12:32:32'),(10,'I14','i14','vendida',0,10,'Casa à venda em Jundiaí/SP - Lote I14','Projeto Residencial - Condomínio Terras de Gênova','Condomínio Terras de Gênova, Jundiaí/SP','Terras de Gênova',250.00,197.20,NULL,NULL,NULL,NULL,'[]','img/casas/I14',640,480,'[\"projeto-de-construcao-de-casa-i14-1.jpg\",\"projeto-de-construcao-de-casa-i14-2.jpg\",\"projeto-de-construcao-de-casa-i14-3.jpg\",\"projeto-de-construcao-de-casa-i14-4.jpg\"]','2026-09-12 12:32:32','2026-09-12 12:32:32'),(11,'I15','i15','vendida',0,11,'Casa à venda em Jundiaí/SP - Lote I15','Projeto Residencial - Condomínio Terras de Gênova','Condomínio Terras de Gênova, Jundiaí/SP','Terras de Gênova',250.00,191.92,NULL,NULL,NULL,NULL,'[]','img/casas/I15',640,480,'[\"projeto-de-construcao-de-casa-i15-1.jpg\",\"projeto-de-construcao-de-casa-i15-2.jpg\",\"projeto-de-construcao-de-casa-i15-3.jpg\",\"projeto-de-construcao-de-casa-i15-4.jpg\",\"projeto-de-construcao-de-casa-i15-5.jpg\",\"projeto-de-construcao-de-casa-i15-6.jpg\"]','2026-09-12 12:32:32','2026-09-12 12:32:32'),(12,'F19','f19','vendida',0,12,'Casa à venda em Jundiaí/SP - Lote F19','Projeto Residencial - Condomínio Terras de Gênova','Condomínio Terras de Gênova, Jundiaí/SP','Terras de Gênova',291.18,176.10,NULL,NULL,NULL,NULL,'[]','img/casas/F19',640,480,'[\"projeto-de-construcao-de-casa-f19-1.jpg\",\"projeto-de-construcao-de-casa-f19-2.jpg\",\"projeto-de-construcao-de-casa-f19-3.jpg\",\"projeto-de-construcao-de-casa-f19-4.jpg\",\"projeto-de-construcao-de-casa-f19-5.jpg\",\"projeto-de-construcao-de-casa-f19-6.jpg\",\"projeto-de-construcao-de-casa-f19-7.jpg\",\"projeto-de-construcao-de-casa-f19-8.jpg\",\"projeto-de-construcao-de-casa-f19-9.jpg\",\"projeto-de-construcao-de-casa-f19-10.jpg\",\"projeto-de-construcao-de-casa-f19-11.jpg\"]','2026-09-12 12:32:32','2026-09-12 12:32:32'),(13,'I34','i34','vendida',0,13,'Casa à venda em Jundiaí/SP - Lote I34','Projeto Residencial - Condomínio Terras de Gênova','Condomínio Terras de Gênova, Jundiaí/SP','Terras de Gênova',250.00,172.95,NULL,NULL,NULL,NULL,'[]','img/casas/I34',640,480,'[\"projeto-de-construcao-de-casa-i34-1.jpg\",\"projeto-de-construcao-de-casa-i34-2.jpg\",\"projeto-de-construcao-de-casa-i34-3.jpg\",\"projeto-de-construcao-de-casa-i34-4.jpg\",\"projeto-de-construcao-de-casa-i34-5.jpg\",\"projeto-de-construcao-de-casa-i34-6.jpg\",\"projeto-de-construcao-de-casa-i34-7.jpg\",\"projeto-de-construcao-de-casa-i34-8.jpg\",\"projeto-de-construcao-de-casa-i34-9.jpg\"]','2026-09-12 12:32:32','2026-09-12 12:32:32'),(14,'J03','j03','vendida',0,14,'Casa à venda em Jundiaí/SP - Lote J03','Projeto Residencial - Condomínio Terras de Gênova','Condomínio Terras de Gênova, Jundiaí/SP','Terras de Gênova',259.02,217.51,3,1,2,2,'[\"Vaga coberta para 2 autos\",\"Sala de estar\",\"Lavabo\",\"Sala de jantar integrada à cozinha\",\"Cozinha integrada\",\"Espaço Gourmet\",\"Lavanderia\",\"Varanda coberta\",\"Jardim interno\",\"Depósito para guarda de objetos\",\"Hall de circulação\",\"Dormitório 1: Suíte com closet, banho e varanda\",\"Dormitório 2\",\"Dormitório 3\",\"Banho comum\"]','img/casas/J03',640,480,'[\"projeto-de-construcao-de-casa-j03-1.jpg\",\"projeto-de-construcao-de-casa-j03-2.jpg\",\"projeto-de-construcao-de-casa-j03-3.jpg\",\"projeto-de-construcao-de-casa-j03-4.jpg\",\"projeto-de-construcao-de-casa-j03-5.jpg\",\"projeto-de-construcao-de-casa-j03-6.jpg\",\"projeto-de-construcao-de-casa-j03-7.jpg\",\"projeto-de-construcao-de-casa-j03-8.jpg\",\"projeto-de-construcao-de-casa-j03-9.jpg\",\"projeto-de-construcao-de-casa-j03-10.jpg\",\"projeto-de-construcao-de-casa-j03-11.jpg\",\"projeto-de-construcao-de-casa-j03-12.jpg\",\"projeto-de-construcao-de-casa-j03-13.jpg\",\"projeto-de-construcao-de-casa-j03-14.jpg\",\"projeto-de-construcao-de-casa-j03-15.jpg\",\"projeto-de-construcao-de-casa-j03-16.jpg\"]','2026-09-12 12:32:32','2026-09-12 12:32:32');
/*!40000 ALTER TABLE `casas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lotes`
--

DROP TABLE IF EXISTS `lotes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lotes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `slug` varchar(80) NOT NULL,
  `status` enum('disponivel','reservada','vendida','indisponivel') NOT NULL DEFAULT 'disponivel',
  `destaque` tinyint(1) NOT NULL DEFAULT 0,
  `ordem` int(11) NOT NULL DEFAULT 1,
  `titulo` varchar(255) NOT NULL,
  `descricao` varchar(500) NOT NULL DEFAULT '',
  `localizacao` varchar(255) NOT NULL DEFAULT '',
  `condominio` varchar(120) NOT NULL DEFAULT '',
  `area` decimal(10,2) DEFAULT NULL,
  `quadra` varchar(10) NOT NULL DEFAULT '',
  `numero_lote` int(11) DEFAULT NULL,
  `topografia` enum('plano','declive','aclive') NOT NULL DEFAULT 'plano',
  `imagens_pasta` varchar(255) NOT NULL COMMENT 'ex: img/lotes/A01',
  `imagens_largura` int(11) NOT NULL DEFAULT 480,
  `imagens_altura` int(11) NOT NULL DEFAULT 270,
  `imagens_arquivos` text NOT NULL COMMENT 'JSON array de nomes de arquivo, em ordem (capa = primeiro)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lotes_codigo` (`codigo`),
  UNIQUE KEY `uq_lotes_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lotes`
--

LOCK TABLES `lotes` WRITE;
/*!40000 ALTER TABLE `lotes` DISABLE KEYS */;
INSERT INTO `lotes` VALUES (1,'A01','a01','indisponivel',0,1,'Terreno à venda em Jundiaí/SP - Quadra A, Lote 1','Loteamento fechado Terras de Gênova','Loteamento Terras de Gênova, Jundiaí/SP','Terras de Gênova',318.91,'A',1,'plano','img/lotes/A01',480,270,'[\"terreno-a-1.jpg\"]','2026-09-12 12:32:32','2026-09-12 12:32:32'),(2,'A02','a02','indisponivel',0,2,'Terreno à venda em Jundiaí/SP - Quadra A, Lote 2','Loteamento fechado Terras de Gênova','Loteamento Terras de Gênova, Jundiaí/SP','Terras de Gênova',267.19,'A',2,'plano','img/lotes/A02',480,270,'[\"terreno-a-2.jpg\"]','2026-09-12 12:32:32','2026-09-12 12:32:32'),(3,'G02','g02','indisponivel',0,3,'Terreno à venda em Jundiaí/SP - Quadra G, Lote 2','Loteamento fechado Terras de Gênova','Loteamento Terras de Gênova, Jundiaí/SP','Terras de Gênova',250.00,'G',2,'declive','img/lotes/G02',480,270,'[\"terreno-g-2.jpg\"]','2026-09-12 12:32:32','2026-09-12 12:32:32'),(4,'H08','h08','indisponivel',0,4,'Terreno à venda em Jundiaí/SP - Quadra H, Lote 8','Loteamento fechado Terras de Gênova','Loteamento Terras de Gênova, Jundiaí/SP','Terras de Gênova',268.04,'H',8,'plano','img/lotes/H08',480,270,'[\"terreno-h-8.jpg\"]','2026-09-12 12:32:32','2026-09-12 12:32:32'),(5,'J12','j12','indisponivel',0,5,'Terreno à venda em Jundiaí/SP - Quadra J, Lote 12','Loteamento fechado Terras de Gênova','Loteamento Terras de Gênova, Jundiaí/SP','Terras de Gênova',255.81,'J',12,'aclive','img/lotes/J12',480,270,'[\"terreno-j-12.jpg\"]','2026-09-12 12:32:32','2026-09-12 12:32:32'),(6,'J13','j13','indisponivel',0,6,'Terreno à venda em Jundiaí/SP - Quadra J, Lote 13','Loteamento fechado Terras de Gênova','Loteamento Terras de Gênova, Jundiaí/SP','Terras de Gênova',253.56,'J',13,'aclive','img/lotes/J13',480,270,'[\"terreno-j-13.jpg\"]','2026-09-12 12:32:32','2026-09-12 12:32:32'),(7,'J21','j21','indisponivel',0,7,'Terreno à venda em Jundiaí/SP - Quadra J, Lote 21','Loteamento fechado Terras de Gênova','Loteamento Terras de Gênova, Jundiaí/SP','Terras de Gênova',332.80,'J',21,'aclive','img/lotes/J21',480,270,'[\"terreno-j-21.jpg\"]','2026-09-12 12:32:32','2026-09-12 12:32:32');
/*!40000 ALTER TABLE `lotes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'citta'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
