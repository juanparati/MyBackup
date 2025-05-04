-- MariaDB dump 10.19  Distrib 10.6.12-MariaDB, for osx10.18 (arm64)
--
-- Host: 127.0.0.1    Database: DATABASE1
-- ------------------------------------------------------
-- Server version	11.7.2-MariaDB-ubu2404

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
-- Current Database: `DATABASE1`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `DATABASE1` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `DATABASE1`;

--
-- Table structure for table `table1`
--

DROP TABLE IF EXISTS `table1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `table1` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `table1`
--

LOCK TABLES `table1` WRITE;
/*!40000 ALTER TABLE `table1` DISABLE KEYS */;
INSERT INTO `table1` VALUES (1,'630735ce-2812-11f0-aadc-3af90aa201f8'),(2,'OZDXcuPQIVEkjqcnhYvhyUgWqMPLncBBdM oTChaGhPEFlnFsU'),(3,'vrzvFRqe lCheYDYhNuIldHdsEUJNLuLxdaTseuJoqLLvRZvgt'),(4,'ymPpcr CoJHiTUQvDIGiVdGVKMGVHAJRioUIIsLEOhrlfUjlAW'),(5,'fNzfeekMgswbVxBpT UWZkXljoQoVNcXEiYxnVRwGSuvQWloKK'),(6,'xfjCnGubZTuqwjFANofHYT TSIOSYqHpBPtvYHtRfZIsORQFFl'),(7,'nGtXGpCVWWRwEIEVS XnxwPNvLtLBzQHOZFcxcUtfxTcGcpqJA'),(8,'BfWtZTvtIlfTePNtETEpKInnzHObMGVJKzoVMYHtRfYEcykCjl'),(9,'ChdUpNWsYPdYIymRxLpozEBWdznRxIclXfIdokjpUEoGtTpQnQ'),(10,'riRMjGIymSDoICLWBSKYMNDHzwLsCMbWEkinOfmQwHXPkDoIBG'),(11,'CURAdMDDlrYWKLCErRkAYpCUSBiirhLlTLcZTvsCLZOVmwxYz '),(12,'dSfThaJtOPJaXObORSMhyUfPKgyaGgJgDxBshHPcUoKKwZHowr'),(13,'rK tsIrEamifcUthDx hnTCighspvkPssJxhsrEZiPBpRpYahG'),(14,'LL riPCqVICNjExvKrBILFTEpLNGTxHSslYnrTwApT VcB VeG'),(15,'XSyLmcyizTVZjVcAwBxGRmKRdOLrvdhzZAHJxiAfWsWGuZOXyw'),(16,'NApWMX HPaIrEbtMHXRtqxrosYRmJMGUErSqYTBawfmVSAcJq '),(17,'DrWICOmPspwphPGNTfUjkyKhEDfNApUFtbdjMh cKyixJfzdUq'),(18,'RnOflMfmTILFUJJvWyBpRqcrzvFPkDpNUm OvIgFKJqBKYJDOm'),(19,'OjDteruYHtTpRpYYXQoUIHmnBYkfWsWFmvokgdXEeIgBrdqsQc'),(20,'OPEGtUvtFWRttNHYXPkBfVnDcCGAHMMBvybQYvgt ttNKkUSGF'),(21,'fLppFiXoBRDsabeuJmhXvh faJxjDqQjvAtqzvFTCgWoFozGIx'),(22,'jGCWZgKfwOJdneGUEoEiYwjGEdDGzzWnx hmMaRgeegvJkVXcv'),(23,'QWmrWJHkbCIJvaRjuuNJfzggnWVOixLrwhyTbAyO hnPoXVMaP'),(24,'aEZgGOaKBBcJnjjusEUKTpOecbXJDS XmrXMUmyGMPQLh dRYu'),(25,'ZPbNKnhZzxPGOZEcxeddhBoLON jvzlHCTKVxxWsVCasOQMqqG'),(26,'jbFajSLenbsGhPD LjKaTvuInmvsCJQYxodygnUJMHcmfODBYk'),(27,'heYEeEOdbVyFJFajSPvHbhJYQk WhWnyAnIGfGTzMqrKDNfkIN'),(28,'QNvKtIpyuCFxrsNPHUzNtCJPTZtSinNcWCYkdKrDSDqPgiwGTB'),(29,'bCIHoxsvXFkgcUpOcTmDicNKmdEPj YtV LhBnIEVQuyiu pZc'),(30,'prOWrSqZbgCtmc uvUoIADrWGwkIQeX GHtUukSLbXKHhMqpAM'),(31,'hzXrQeZItQbJq CrWJGhMtCIHpAJTracngPJbetCIJvbSmDlqW'),(32,'NXBOqkdNGTyKfvLxbQYysvXALemWYdxYALbYObMHcmeLsBJQZC'),(33,'OmQwFQmLUqUAUVTHItPUgVkoNZGljlDltjOwKquaSpUEn NohQ'),(34,'LgyUhZAGHqFfLojdRXnulWYeygjBjpWNZKBDmx mFugxTavcXI'),(35,' yQKdjNnYdxaNMycQbFbptYLKuRa zUeLtKtLDG DuiJYPcReS'),(36,'bFXUHCRCnEloMWugyVnzITsjRFDbuTkuqxoZiPAlAVapwocyjy'),(37,'OCuolnGtWFozFGpDbvUqTwyedaTuomqTyJdlXdyhpbptYOXyyT'),(38,'bBDmxDzJbes vByMrwgsvXDaqBMemWVPoXXSzQHOXvjKYLL nQ'),(39,'qfCAUVVSAa zUdFTDlrcnfOEEjhiv qdsETEteqrJzrmhbLBzP'),(40,'D OuGWRriOwNFKNIaf hkCmzJchCtkUTKVxxVmvsBEujKYOZCS'),(41,'GFfJhFFpFjcKsJtPSSOssLFSzQHOaHlgYzBktoo JWHwjEyAlC'),(42,'fRSOuBAYpBSIPYywKmcDJLCHAJSlAYpCWXYbjSLenbsHjZvcZR'),(43,'lEqOZFcwaMJnibKxfi ZystONBvuKtNJjOwLrBJPZ CmDfRTRC'),(44,'oIDTFzAlyLkPwMxY GIynVRwDEoCXfIacjOuENcV KdiGIzuAw'),(45,'DEnzGJDRxIbhGKHjUYhOybKzoVMbUvqrKABfWtcgzcMGVJHkdL'),(46,'yhpdwYBMjEyBoLSfYzCqTyKhFGrKCIKAyPCvuKrCNiCoIDSClv'),(47,'sABiispvftynRzTWeDEoFnyzaFbrEVQuzmMYIxjFy hlG DxxV'),(48,'lrackUSFzE MlQwLrypgJdpoxvM oSyNvLwX EBRFANmWYbmdH'),(49,'eydYFhSTPtyiyRPzaFanlsfwRVdENaMKrzvGVHBLbWEkioQpbo'),(50,'oDcBzSPxRSOstORSNnWVQsnnyFGqJzuxcW KaVEmtfwRUWbrCO'),(51,'mSBhgj YuYJDPqhNvKsGgLokijxIZWMVqRmKPSWcwbRimJLFUG'),(52,'yxPIXLNIetEVMbUtiLgzZCNjGDYiTTPsrEXcrBJRhlFxuFUGzA'),(53,'hhnUILDJLCFvjIRjtpvguDJNLuLxaOSYpBPwJjSNmSCmCawfmS'),(54,'EteswhtyivENaLGXVLSeTiiriQFEhWlrXNZJvbTsfybNJiMndB'),(55,' UarHlihkCjmGzBktnjilG EzEBUVQuzoUIGjbETDoHwgvHafz'),(56,'fedgvJkWeGVGxpiTYjbCLWBQCqRoS UYhRNofIetDQrlfTeQO '),(57,'hlJMKppHqIsOTauXFllwxZDVRyPEDfPKesyqfHVEpGoxxTaype'),(58,' pXVHDSFzCrYStonxzdRdOLngSWd topHtUvpoxyWpIxnULVxx'),(59,'VnzIVEicPPGMQVfLrystNKppEhTZoywOEIADteqpDapxtAxKhG'),(60,'NUjnIGfJdpozDwtFYYZclWapvgyTcFVNdZPaIrFdBzQFEjdPRO'),(61,'sykILHcnibGfHWJHiTUSFzCqVEpHsPXtbYQjxGUCeMzlDnDfPJ'),(62,'aZUAUXfFPiuwXBQyUcCHFfGUCgXvhyUgWoDcBAZvaSoQnRxIZX'),(63,'QnPlJNMzjxJhFFnxxUfUhaFbrAINPLkRDvqrLGZewUluquXHwg'),(64,'rosYRlFwofIdrwiAgdW JUzKdmXfFPittMEOgoaldHdvPPGMSc'),(65,'GcptYKGduOKlWcwZKCGALcaYRmIItNLrytvWwrtSjrewVrRmIG'),(66,'jYtUsg giuzoTCjlDkmEmxAoNaMJmgVicQUdHduLxcWAQBl TQ'),(67,' aBHHsMLtGgIXQjyPHOYzAhglJRgeddh gcVxxVmxBqXPkFvkM'),(68,'jGEfNzffkILHduKvWvneFOhqiTVWXVMaNPMpnuiGIzstMIcoki'),(69,'mKPTYmpNXwofKmdGYVLQYuccdjKYPcSfYDVSAaCKTnGwnWXWRs'),(70,'mgUifbSjpXVJKxfkIMNC QDxybLAvAuuMDIDSFxwMzittJuShe'),(71,'dcaVDeKqubYQgizTXfKneIhIYOaKAxIckQCqUAWfIfAlEnCZns'),(72,'YPecZNVmvsCMaPY EvqozFDczqgIXPfgphNwSbDNdcdjKYMPOz'),(73,'bNLr BgbNKpsRinPoVQpaiPCtgAnKNMtGbooCXgKiIUxArbhJX'),(74,'KJrFeAvvSePMrvejINQQHPbMFRtsHlfRWhVgTbypdwWtciGJAA'),(75,'cKsHkdLyffmTJRdRaBGDYeDEmxyXxqovnZlhbMGUDknKLBBcER'),(76,'xLneIfAnMVsbetCIIqEapxswdbZPeaNNEGvfrqEcwZHqGkdLyg'),(77,'oWSzTURBijwCzPBoNaMLuL rgHTvwTfWqMQQKbcjKceofJgBtk'),(78,'WbsGiWgTXhUcCEqNWrRnNdbYKHhNvLwXzBl TSJRimKShhmQvA'),(79,'usGfFNYBNmRBijyLneGWOfkHJzsqz bIpugwLuNJgBshHQitrA'),(80,'HJ wEJHmjnIEYcsCPqhPDwxYyvJkXgMtFWTBdNDElpPjyNwTfT'),(81,'fUjkyJerwgtwbTqXNaLGYXSvygkIKBBfWrSoOdcbZQkAYrJztu'),(82,'OPGMQSTMiAfaKzqgIZVFrRkyJesAAcJrAGFiZxnXYZeycReTii'),(83,'qeyfiwGVKLDH F JZQirkYmrTvxZDXZgHVEmulXeEJKvXBPwMx'),(84,'Y HJBEsZWI CnElrZXOecbWGsUthEBWdAwAqZduIldJnjhhrmj'),(85,'ioVNddguFSyOyaJtPSVYgJg hmMbTpRpXVIFeEJJrFeBzPCupn'),(86,'uiHOXxswbWBUUNhxN lChdWvnbwaOTb wHZadpo HPaGhPCvs '),(87,' YtUupnulSJUsevMBvxZGiWjgbNMxaIrFgPDzLgzZBLcZRnLSi'),(88,'jwEJJrFcxefnXaiOwPMqqGkhfdcceofLrxlLcbYPdXCWYdvRXo'),(89,'ANodCBawehzXqJ xMvPMsBGAMgvHagCwwTgXxqmmzKgwPMpnuk'),(90,'QzXsTsfzdUpPffjHJynTHFeFOfhuB UYiSSJRkxHUAUYgKjMmW'),(91,'ZhLmZlfSbDMdbWGsRhixIdszvCDjinOjAeTfWrRlDlrcngTawg'),(92,'obq EvndDH FCWZiRITuomscjOsxgrmlrbhFGqKBFzAinNehwK'),(93,'okmDhZCOmTGCUQwIbiKcg hkBeSbDPoXXUHAHKBG EAPySVZnn'),(94,'BVamlradojeVrVAVaqzAilDnBWbrEYezhoXWQrkXjeRWkhhqgG'),(95,'SslYntciITrdpqJAAdLygkGDapxqoxruUtfwPMszsqzySXjazx'),(96,'MuImiggkHGjdOKlYiWiYucdkOpfHVHzwMwThcSgbMHcnhYvfph'),(97,'RNpkgaKAyKjMlSExwOIXMVsZVFqJ vEMWyAjriOyYwnWYcqvhw'),(98,'LuKsJsIrHovmUQvDHDSEteplmATQzYvfqmoGuYMPLpntdmYiTX'),(99,'eEMUnEmsbgAoMWwruXFkhgizWjhjxJesBHFeGSvyebVzISqZYV'),(100,'JJvXAMjHKFWRwDEmunfOEGtWBTQzUeJlZqDYfGRqduLxdXGnsc'),(101,'iIRlBdIjTRExwOIYRoUFtcepmnCeKomtiJZTw lBfUgXvgxPIZ');
/*!40000 ALTER TABLE `table1` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `table2`
--

DROP TABLE IF EXISTS `table2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `table2` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `table2`
--

LOCK TABLES `table2` WRITE;
/*!40000 ALTER TABLE `table2` DISABLE KEYS */;
INSERT INTO `table2` VALUES (1,'VDifaLHcpqJzspyvFRttJtORP gdXCYjXlkutFbmhXtaXLMByJ','2012-02-01 00:14:21'),(2,'szuyhtvWyzZCQvDIGgKlUTKSjtnieXyyUgTeOGQkAZwfocvUpN','2012-11-27 05:26:32'),(3,'oFoANodCAXkhhpahKgxUfRVc rjSNpgNybLCFtaVBZrItPUeND','2012-07-13 23:28:19'),(4,'YnrUzNqrJxjEtfvIkXjaACmBXiXmthFHvcZSqYXSxDFpHuWBVX','2012-01-16 03:19:04'),(5,'rAGCWXYYYUFvhAjpZbjPAhisopKFZdtInoFlmCfPLjOrqDYgIa','2012-01-20 05:30:51'),(6,'lWcuPQJcg fZKBBfSbBDqRlFugvIjRIQfdcYOWuepjaBHFgPGN','2012-11-25 17:24:25'),(7,'myFKIlgVn NrrGmlxBshJaYRpVKPViaGeDGucdjJTuokkuqxnW','2012-12-11 03:01:34'),(8,'VKOPJbciJWEjgffmUNgqkdMAskUWYbnjimLWzFDeIfAnKOQKdn','2012-01-19 13:05:10'),(9,' rjSOsvXDbuQZ  ZzxOFHwjGFiWlpQlFxvHcppGlmASNodAw o','2012-10-25 01:25:54'),(10,'pZgGPdYFjdPO gjzTWcyivEJJsK trCPrlhcOODAUVVPpdyecY','2012-10-03 19:21:33'),(11,'SZvcZSpUFtZQk TWcxdYKEQsorPdUqTyJdmarFeB SOrowrrHo','2012-06-06 01:10:01'),(12,'rrK uvWvndAvyeehyUeNArcppFiZxpeAvydZMNEICNhxNBsfAp','2012-11-09 04:13:24'),(13,'xFNZGlkqZaevMArezffmRyQLkOsvXBPxNCxCyGOcTkwzjwFNaM','2012-09-09 18:40:38'),(14,'meJkWfHadrztuQXqJAAazwIclXgLppEfLppEeGWNaMIiOxRUYh','2012-10-31 20:41:58'),(15,'LhCraaciKaYOdVvqrKA YsQeWxt xJfzdWvpmoKIotZSrfAnKQ','2012-12-11 04:48:08'),(16,'oCVXbmhYxrp DwuGZdvNHVH CqSrgEFnwwOJcjMjISlCeMAskW','2012-02-09 06:37:48'),(17,'KngTYkhfgoZfDCdHe jyLpmsbertSipWOfjGEc rhKhEBVXbqx','2012-04-16 04:28:53'),(18,'gMs  ZvfqlkoRsnkmEnAPzZAHHozBnHz bJqzySXkfXvjJSpRq','2012-01-31 22:57:31'),(19,'zfgnXYblYljlEqMPMrwjCoIBJSmCdJlaxhwJhGNVqRk UZlihj','2012-07-13 00:05:45'),(20,'fVpIypfIYVHAEyzcQWmrXNYEapxrqDWXWRv qaiOxSXjdMDEmx','2012-07-01 10:59:56'),(21,'irhLmZlgXtbbcg fbReWxtCHFeFNbRhgnTGBNkLdenbvTlyGQi','2012-05-11 10:02:42'),(22,'q DtetDMYJztvX GKESBeRXnuhFDeIdsABjnMYGlmyHQirkXmn','2012-08-02 08:18:18'),(23,'jgfixMvQRNnbrFd nQpdvRZxlMcdiEBTOoctLBBcGZewSaztvX','2012-07-12 12:51:17'),(24,'Pt uuONyaJvYEfMwTgaGgMrypdwXAJUvtEUIHouiGJ wIcneId','2012-05-02 23:15:39'),(25,' ySSMhAfbNMxaHpz dRZyrosU OwNDEkmFrRkyMpllyGNXz dQ','2012-11-29 12:25:04'),(26,'fQNtBEtcgzdUm QEAPyVjioUHCQwKlauVwtDNfnWTGzAjrfBuo','2012-04-06 04:23:58'),(27,'zEDdGWNaMJnknICOmPrkdJpuelTLYKES XozEARFAOuDKOQMmZ','2012-03-09 18:43:40'),(28,'axjCpNYCSF JXJFZfBwydVwsxlLZPaIouhAjrhJbdohUdHaiLh','2012-07-25 08:26:00'),(29,'uneJjRJSoOefnZiSPvHbjPzaFbopJABheZKERuyhrmkoLTjpTA','2012-12-09 17:20:11'),(30,'jcJsGgNuIjSQzWnxyXuemWXZfCwycRbHiQIRmH EzEBVYdyfht','2012-05-23 10:41:53'),(31,'UnHwkLcbcgAipYXSwCyKhBqVGyuBCihkEsZTvwUlvrztvUpOdY','2012-08-24 13:32:13'),(32,'rKAxLnfNBrewRYpAOsvYIxhxOCyGNVrSqYVGxsuUpObQYxqkfS','2012-01-09 08:58:42'),(33,'BErWGuaWElpQlHCTIMJlbAAZxmR ZyrlihkCjmJIqBNkIOSaxk','2012-09-08 08:43:55'),(34,'QdUlwxZFfJhGMPOBsetDOizWmvneFTzQISqYTBb yNwQSPzYvg','2012-05-23 03:11:54'),(35,'EQoWTAaztvXANnYgIXPhoYcqwkJRfdZLHhJbcjMlSEwqrK vAw','2012-07-07 22:32:40'),(36,'rf mIIpxsuToMTihnQttKwcWCZnqMSbBFxuDLXAOuBAazrosUw','2012-06-13 02:13:11'),(37,'ZCRBgdVsdlWZiSOqnrSoSxGSsjQFDcznQsq CrVFpFlnElrXPg','2012-03-26 23:04:31'),(38,'PqhOxWoGoANmUMcaWGtVAQCqUAUYhQGLLxbQZBLYKGajSOqjZt','2012-12-06 17:51:27'),(39,'BVVSAbFYZexXxrtQYveodCCfQRJXKIlfTbCHEcvUm RJWDfPIZ','2012-11-29 07:50:39'),(40,'EnzHOXwnbrCRAfXxuDMaPXrSnJHnqNUluoloIDUKRfaMJmfQPA','2012-03-21 05:29:59'),(41,'BZsNNCxDCbAAaACm MpkfVoGsQdSfWvjJUyEDfPJaabhDxzdUo','2012-09-06 08:45:12'),(42,'EYaiMoiYuYLKsFZjSQ aFYbkWdAtr AfXwrrItQZzxPGMTfVmy','2012-07-29 21:15:10'),(43,'ARJVAPzYxrpxsvYIxkINQQE OsxkFzCqVGwkLbaWFpGnwsxi b','2012-08-21 18:32:21'),(44,'fGRqdsBJPWmxyY CoLPTVYfGPhoXWQpZdtGeAtopGnvlVTIJwf','2012-03-26 22:45:56'),(45,'SEvndDJIqDVQuygmOoXYbjSLceplksiMnbvWxuEQqhLjNqlhfc','2012-12-20 17:20:57'),(46,'KDPpdxYDYeCAUXdxZJzqeBxFKL oWQsmgTdKtInpJEUKSgcTlx','2012-08-14 00:55:44'),(47,'MTfWsVCZsLDJNKmcDJKAxGSw nMZIwhuzpYbmeKolrXOcToLQU','2012-12-28 18:38:58'),(48,'qDWVQriSNofIaevLxcVvneHYXPkEtdkREyzaGiSRE KbdjKaU ','2012-10-22 07:17:52'),(49,'BnH CpPebSmEnzJWHvfrrGjayssJtOQLiIQeYFgOEDfPIVGsUy','2012-07-25 17:38:46'),(50,'yGOY DwsypeCzNuFVMYItTmDgXtYMQTXfGUBZrLHbkVZldLuPS','2012-11-24 21:52:07'),(51,'VRzVdHZbiMkPuDIHkeQSOtynRxIdrwjDschFIxmSCkshJYPebU','2012-05-23 20:11:41'),(52,'olpMSdNDH DqUCcEPnTGzAhhoXVNbRhlHFgNwSaynVNgrmknLT','2012-03-12 08:59:34'),(53,'shHRlErQhmOlMddh gcW LgxSVasIrEZleQOzdRbFYcqvdgvGY','2012-12-26 22:00:41'),(54,'afyZBNiCqRoRsnnAOsvYHqFgPGLMByMoiYtWDfMwUjoNZJxiyS','2012-11-26 20:29:57'),(55,'XbpudjLceoeHZYWKOMvOIZZZYT PDxzeZKCIKAxHXOdaRkyMrt','2012-11-06 07:31:58'),(56,'ioUGxuDKRcNGUBdIkWdByMqpBSGGnuhBoOfhwJhHSrdqubXKHi','2012-11-05 00:46:58'),(57,'MkKaUwxY GHqHqDYfGRoRtsFd pYZbiLiHNQRNpja xMvOKiIU','2012-06-18 09:31:08'),(58,'GOZEcvTjoPlGDXalb xIcmbxivEMWvmXfFOfhtylJNPJbgyZBM','2012-01-24 02:23:22'),(59,'fsxi ZwkKWAP dPQKcfwPMrvaTrZZackTNiBmCb vDGufswejF','2012-07-05 06:09:17'),(60,'LftBChfbSmEmxAmGxsvWAMiEyD MnbuQVidSeSZuaSnJLB WhW','2012-03-30 13:12:25'),(61,'vrwgpiVdDIFfHXRrhKhCuokhhqfFMUluppDaqCTIK wEMVrVCd','2012-08-11 20:30:56'),(62,'UHDWVOizUcDJMIeycRbGfFObRgcTnIBLYHvcdgwOHVGvgxOGPe','2012-01-05 13:57:19'),(63,'QeZIsNNBskUWWUIHmluqtUwvOIYUDifbSkwzjyMuIjSPwJiMmX','2012-01-27 11:33:02'),(64,' nQslePLmbvVtdoiYvguBBeOIbfwSYrLERxHXOefkMfobtLBBb','2012-07-09 01:08:59'),(65,'BhcSilCiiqdtJrDSFzDwtFZconvqqHoy gglJSjrhLkPvGVKQY','2012-05-17 10:47:20'),(66,'YMRVdGUFueoeGSxGRqdrzwIdryrmltlWapxrpyxQPBnKLAuvSf','2012-12-07 19:15:12'),(67,'tbaaabeszsqCOqhOBqVJJuSfaIqEbuQWkmCcESAXoyAlzOxVko','2012-10-09 04:06:30'),(68,'cUrackRE JXKGezhpdwWvkNobq EwrtVvruXFjcKuPTZouiFEi','2012-12-23 08:45:34'),(69,'wlOmSCktmcBBeQQFIztxdbYKIkYmrVDhbHkZvehzaFalcFVMWz','2012-07-26 05:13:21'),(70,'vsCJPYvguCGyvHahKdkPuEMYHsOUeOIXNYBNlOqiTVYgHTxDBY','2012-03-30 00:18:13'),(71,'qQismifbSktooCZmnDdEQqgIZVFrPcOPGOaIrIuToJGfDFrOVm','2012-06-09 14:05:15'),(72,' ipajTPwKmfN mHCTHGmpOZEYgJcjLfsxgrnoGqKERvBwBwAoP','2012-03-26 00:02:11'),(73,'MbWASJVAP cONzdVtgzbLAwCCdJnkmEpGqGoxwMxYBOodxeejG','2012-08-02 02:38:17'),(74,'dCDkoPj XmsdnfKokkvvQUarIsMJjQFDauSfZIqCTLXEfLsAEt','2012-02-08 23:48:25'),(75,'vM mLTlwydXCUSClvuLxcToLRbESDnDgVhYxrqEamhcNLppDYj','2012-12-11 04:15:37'),(76,'lkvtJorQgivCEqNVpHwgsvWzDyCxCwycQZADtdnc sopHvbVzH','2012-10-16 13:22:07'),(77,'cRcKvUnEmvoiXsRinRxIckQDtg hlG FFjcMCAWgRPBlBawflP','2012-05-16 09:01:20'),(78,'xgobrAFBPuDIIqAJTqYUEmx gj YsRimMZMLwUmzKertTmDjhj','2012-06-30 19:03:49'),(79,'WkjoSwELOPFHxoaopGrNQOyXtbaYQlGzAfbORTSGEfMvQSTMfq','2012-03-17 02:08:19'),(80,'gaJtPVjioS WhWloKJtNLrzwHbhHQirkYpCSFzEzIWFqHtSmDk','2012-03-23 04:11:30'),(81,'CdJlbynUKRdRZxoZhKhFGqIvaPcNLsAEtdlURAeUjlDjkyLogR','2012-11-05 14:25:42'),(82,'KYKHhKhDzJYOZHpywMwUmyHSqYYXRpZbjRFCWYgIYUAUYiVczq','2012-02-02 03:20:11'),(83,'ApWOegrnpKHgGRpYZbjQEyBsdpmrXPecaSnNZLESzQHOYBMemU','2012-10-06 03:09:55'),(84,'hyQMpkfY FEfLsDRxJgDzJaXMSbDRwGRtoqJ yQIWIAGBSHJ x','2012-09-26 16:58:33'),(85,'s wIfxXyuDJLCFvlQzYuZRmHAHJzssGiVc uvSfUltlXfJfyaH','2012-03-26 22:48:23'),(86,'lvtFYZbiKfuFVNbTqWMUlxAnNcYJABeTgXvjHLJorNTfTbDNde','2012-03-03 21:30:36'),(87,'GHtTpPivAwAtmfQMqozEAPzbGfFOcVumYiVcAvByJbgBoPj Yt','2012-11-16 08:02:08'),(88,'qUEpJCKSioSzQGMRVcBAZwguAxHWKLBBa vELThhmOkGCTLbV ','2012-09-21 06:06:42'),(89,'hDwuJnkmDjkwApXVHCOoanjkyHVHyssK trAHJ uzlFwrrItPW','2012-04-13 01:54:34'),(90,'EgRQEAQCqTw lDmzHQhmOjBiisnmxyYAGFljpUHBLcceoeFSxE','2012-09-11 08:18:46'),(91,'JrCSEvnaqAGGpAMiDvqqGnsaXObQZADtfvLuLAwBvvPQLiFDby','2012-03-06 22:42:18'),(92,'BgaK vAsjUTNiBlwzglJQcNHZbgAmEqOWsVDeLuLAvygjBkqZb','2012-03-03 23:02:08'),(93,'NsvbSnIEWVOjAfWsZSpXSyMrvdfrrHp DwtCKSinPkHEdCEn L','2012-02-25 01:54:39'),(94,'DvrwgtxgqiUbuSijwGRskYlkqXSxFMVrVDfPJciITupqKERuxd','2012-01-07 04:47:35'),(95,'TpTzOyaGfGSuuOKjPyWn JWHxlPqiUarFgLokikBfTcHeycRdO','2012-10-07 00:43:03'),(96,'ycReTfWqOYBNkINPNtBFwndBzQHPcReSbETEsXNVoFmscjNqns','2012-12-19 15:24:58'),(97,'PecZNTgWpJDRvDFrRlAXkhgkGEbuTikAa zUasKAzSSJUvrwfp','2012-03-02 11:35:23'),(98,'XlnHxnYe mKPUeKnklzO fbPYwjGFkgaIqCSGDWamhYwkMiBkv','2012-06-03 19:36:16'),(99,'UnARHLJmhYytznTEtbchFFkkuqvgsvZLHdwTfUiiqcqvfnbrEW','2012-11-28 10:04:14'),(100,'NcYMMBzMneFRtrCPpcwVrTxEElqTwBtnjhhqgJes zSSMiDsbe','2012-05-01 19:21:11');
/*!40000 ALTER TABLE `table2` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-05-03 13:43:20


-- MariaDB dump 10.19  Distrib 10.6.12-MariaDB, for osx10.18 (arm64)
--
-- Host: 127.0.0.1    Database: DATABASE2
-- ------------------------------------------------------
-- Server version	11.7.2-MariaDB-ubu2404

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
-- Current Database: `DATABASE2`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `DATABASE2` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `DATABASE2`;

--
-- Table structure for table `table1`
--

DROP TABLE IF EXISTS `table1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `table1` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `table1`
--

LOCK TABLES `table1` WRITE;
/*!40000 ALTER TABLE `table1` DISABLE KEYS */;
INSERT INTO `table1` VALUES (1,'whvHbiNrrIuVvpoxuFXTDjmFvgwMwUkreuIleOIZWLQVeIgDBZ'),(2,'oxvKqvh aEWUHBOpdAtoqIynTILFSwFLRaADsaZWLNJiLiEBTM'),(3,'emUMdfsuW JVCc wDHAGFkiiqfC SMkKZQhnS YpEfJhIWFpGp'),(4,'CTKWCXhPDyHPcUoLPQITuqtXHtTpQnNdekKaSqWNaMLuLygoal'),(5,'awekJQfaLDNcZOY CqUDhaEYbmhXqMPNuHezjyMrvdfqmpLMBz'),(6,'PAkyIYQlGz eTjoRskZuUrabhFFnwrtTnICMelRCmBZrHmpJFb'),(7,'omwuJmhZBKXFjgbOPLiHNUihjAbDOjClwxY EyBoOfjEwpmoJE'),(8,'VQrkYoyAkyHVElrZZYT SNpiWhXpIwflQwIfxY FDcykDrWIAF'),(9,'zBqVIDRBfWtcgxRSOqlhdVtgzaGfIYVH EvoiWmtetETCkpUEp'),(10,'KEVOgpfFMSfXz dPPITxBtlYjcHiPAkwBvydVujOqouhAhkDmx'),(11,'D NqmqRoQpbpqKDPoVOizVfPIXLPRPzbGhMsyoamfQOyXuemYe'),(12,'CAVcAvzitrDTHFfJiMlTNgtwdbWDdEQpYcnicNGTAWeHZZZZad'),(13,'quXGoALgvJlawfnWXWOiwHZXQmNddfrqGiXmsbgBrYWLSdPQKc'),(14,'g eZGkeRVcznRxKlVYdzisqzxPKfxRTTMfpfKkUUMdfqligkCm'),(15,'yHSpWPjzVgRSLguCHEZiTVUNiySVYfEKMFQpZconvohWklATTL'),(16,'ZNUhdUnFqJ vDIFanmtkSJUvquXEhXpGpCSJPYzzXqLJopFlnG'),(17,'tWFloJGdxbPWoEgPGPdVvqrLHbmdFVKRaCOmQwFQmKTnFrPcPS'),(18,'WdCBdKtJtLDIHligfjF HNVltlXgOAoPivCDlqXPiwGRskWczp'),(19,'Yaf kyKiKdlRBkshJchBoLRZxlQvFQpZconvqpAMhzXtaTuokl'),(20,'xCyHRmIFeFPhrmkoOfkFBOsvaNQRKdiDwvMByKhDzJXJEWTDpI'),(21,'zvDHBMejGGpCTMejHKCLVvpmpPeaPYxoeGSusDTFzCtiJVDeMz'),(22,'hmNfobpwkMiAgdWy gglMbXHxmPtxdZOXtaVCeJnhYuelQySVZ'),(23,'lePOyWrQgjAeRWgROyWoEhUfQNsxjFzEAOvJlbAyLpqGkheaQd'),(24,'SgZEapviFCVVSBgaIrFcxefoamjioVLTlw kAaABgaJwefpfIb'),(25,'fyZDSGBPvGVJLDJIqCQxLrxlPqjYpEdAv pYadrwiCoJGe nNf'),(26,'mQxKnjdPQKfruWBRFBRGDcznPoXWPmLZLIjSOssLCFwmXcsEYe'),(27,'ycRfaMGYZacnfPHU RHMOIaZZWKMGVHAHIuWBVVPqgJgzbMIdp'),(28,'pEgOECdFTCjlBbCJPUeKprOXwmXalaxiySTSHIuWBUTILDMZKD'),(29,'NflOpd nQrkXmpLMEMUoHyqmkqWMVuhBqUEoFllxBvuMAtpsSl'),(30,'Bb yNzdWy eYCTNi dNHabfxWrTuomqUCdHblZrIuTlxDBZrIu'),(31,'UsbfyWsVASKaVARIPaIqBMiAhfhvGTBeM oRwEJJsHmltkVZjW'),(32,'hVjgcTlzLkSHIwfmTKSkvwUkqZbjPxSXkfVqNVn OwLsEXXTDl'),(33,'rcmc spsVARIQcQYsSoMUmBatPXrQeXBQAeSdLxaNMwUlvsADr'),(34,'UAUWYeAsjTRDrXMRa zTZoxvHdsEUKSgecaV MnZnpHwiBlxFI'),(35,'CMehzYysvXCVUMbTsevMC RITtmeIhJZUAVbuSikAYrLGXTCgY'),(36,'AGFiZytwdaSoOdbUuokjoSwEKOLquaRjtrBKWBUUNiyTbxgsqB'),(37,'PrmjmGzzXsRkxFKMCDjjrfDCeMyffjGGnvmYhOyaIotZSsjQEy'),(38,'CumbvaNSXlktkVaotYNUjnLTlxDBWczpXUFvkLdjJSoOeekIQd'),(39,'UpMTightwaOSVdCFsXJGbptZPdUpNXxvJmeLvQVfOFIAFzCtkS'),(40,'JUunfOEEknGxnalfSaztsJtPSWfJhFGrMNCzMnbwZIuWzHOWqQ'),(41,'ffizYuZQgivELRbDSCjmGxrpAIRkv nOfmSEsZUBYovlUR Wmr'),(42,'ZXQmJLFUFtcdlVVRwDGxrqBNkLbWDgTauUrabeuJmhaHkbDQqh'),(43,'LnfKkWdAtprPcRZ  YvdhAjqdtGezkBeRVdCGxtzrjWeHZabjP'),(44,'xQOyWnAQDwtBEufvIkVZleOIZXQmLWyAkzMqpDXbqwmVSBgYzA'),(45,'gcUrYRoRuuPSTPvHYWOdZNSZsQbJtNMvPOBrbhHRnKOPGLPRMm'),(46,'UPrmkpQnPoYZdqwiAeVpKHiTSINPLkRGDcxgmRAbGeBvwSeRXk'),(47,'ikAayrnqLLzlHDUQsqAINRUXfGRsjRIQhkEvkMjJTpRsldJmdH'),(48,'duMAuuOKjQDvrvddiFCXeELRaCKUukRGHqEcxgnWTEqNXvjKZO'),(49,'aJuTmAWdBBbDLXCUSEteoiXrLJpsV OxPLjOqmltjRFEeHcmdE'),(50,'NaPYxstRcLzmMaOXsUzJZStnhWnyBqXQmKShiuyjyNybLBAa y'),(51,'QIUASLdhAhkCheaOVkoPgnVPmPpdydYJzuyhspueohSTPuAvAr'),(52,'iMogOAnNaPVlthFJBJOTawegvHdtGboqLIkZrK uxeekIQcNKk'),(53,'WbuRdOLmcBBfUidVtepmqSrdsABhdVvqoyzbJuTjnOhuyhrnmv'),(54,'rypfFLRYsSmDhcPTZrJxgqkdJoqMMArhLjKcdiHOXvh eWvnc '),(55,'qfCyJbdmaqBOnWVMZMOFObPVknIFbqytxfiBktmeJjTPwJjPAk'),(56,'w oQpZexaItPUeNApUFwlRAfYAIPYyuCGAJRjtpvfpkeTdLygn'),(57,'THGhTXhTVWWUGyxQOzbKwbRhkDqNUkpSvzjyNvLziwFMX GGpD'),(58,'bvWxvKsEYcqwkMgsvZLGbkUWZfCyJcjLfqmqOaIqCTKVyCvrve'),(59,'kKWDfN kzSPyWnANrpAIOYxtynRyNzeXBRDteszrldMyfizXpG'),(60,'pBQyRP hjyPHPdUqTyKfuGbkVaqzyUeNDElsbfyaHmjmEoEjeU'),(61,'ksiNqmoGuYNTdMByHVDieWxwPLkOtzoXXWMYDZmltjP cONzgj'),(62,'zWiaFaleLtHhMqsQbKvVsZWJGdzlHEcvWxuERxJdoibIlgUjkw'),(63,'DBauWBTOpfHXPgkHIuW HQiqgFMSdM qbjSNoamhcOQKdlVVQr'),(64,'mhcQWjilFwncwZHrKBBePLneIfydUoIBKUthEBWdBxFNWwqoxv'),(65,'IjRHKHfEEn KaWI BksgD QE KbaXLONzfdcbXLOKkURAfWrU '),(66,'OvLvTfXvkPtykFxvIiP hjyNvNGRoUHBLceogOBqZafxVlvryp'),(67,'cvUmATQxMwRYrNRTUQvCDjmEoFmpPfehxPJbbgAlCgXueogOAo'),(68,'OeeiDvpnvmZkdJnnyCvtFXWOgpctMDH BlxFMTjmGzAgbRcLB '),(69,'SOsuWzDwvMBwBtmeLrAEukSGDcxefnXbpvfqnrUzMofMwTfUji'),(70,'oUKOOFMRXmqUBXjcKuPQKfuGYYVKNKkWbtLDHDWVPmMddjIPaH'),(71,'keQSQAhituOPGLPOCvuIlbBCmyGLNFNbRgcTmDidUlw juwYBO'),(72,'rqDXdvTiiqfFJGfHUBaweh cLykCjoRslcDNgobosSoQnPnRxK'),(73,'mbwbTrYVGytwegrrHmnEjhhqhMpoxwPKiIRjtrDQxLqubVASMh'),(74,'zZzxPGLPQLiDwwPPBoOdaQggoXZevRVeJlYjbGcsGdxcUtfyWs'),(75,'UwzebRipWQqfDDhZBLYLIkXiViZAF JWFpDYjXlmBavcYLLzlG'),(76,'BNkMfoeFPjzRQAimLWyBnJHlhdSfYCTKUthDzKdmZlhdTjnNbS'),(77,'l QHOY CpQlG DwvMBwzhmPqeCAXjdNFMWyyUgWrPbLCFtbaaa'),(78,'YTxDEknGwjHKDMcYMRUYjYqFiYsTpQoTCjlDltjPzZzxODCdFV'),(79,'LVujLdiHLIiPCvqqIuYHqHpCRDtfvMAqagBuomrZWLNKmeIexX'),(80,'yvIgGNWsZTxCw kyMqqHozBoMSgbORQGHvcbbdlSHKETBeRWhU'),(81,'dIiMofKnicLzkErUyHTvuMCBcERv pXUHCOpgMruYLKr Dsbdm'),(82,'bvWyzcOOCvtFaiPzdSfXzAimIIrFgPDzHSsjSNlPsrGgOzdUqR'),(83,'qYZZabiKbbeqpBQzZyqkeOJervbXKHgGPfhwGVJIpywMzgiyRN'),(84,'synTEtcfvJmhaGhQGJBFyyWnyEDgRRJVBVXdydZOUgXwlQwLqu'),(85,'YJBGDZmiinOkGCWWTG EzFHtV LiDukQ eWufuDOiyPHRlBfVl'),(86,'setGduOMrxlOoZgHUzOxTdLvUkqads  YuYMNFMVsaaZXNXxwL'),(87,'vRZuaSpRsnlpRrgFKImjl SOrqDXcsFbooDcAyLpoyzdUnFpGo'),(88,' ITsfyZEbrFakbzrmlrZZZXQnNegrqDWWVKQXqKERuzj Xnyzd'),(89,'UnDfQOzcNJgECZoxuFWOhsq CqSuqwlNjEzDwvNFMUnCcENfjD'),(90,'tesyoaleMxZGlhdVrYRrdtFXWQpcrzvGVKPRQDx fedgtAzSSJ'),(91,'SlAYpALfpjcJpxocxdaSpRtqvhyVloMVtciGIypdzmJLFSyKjO'),(92,'rsKBAbESAZwgrouhBpRrgFKJq CpQlHEZkbDMYIymQtwYDZleQ'),(93,'QIRmFsUzIXMTfVpKHhLmYiWgQLlTMgtymNfkKXEhWnxwRUZlgZ'),(94,'DTJPWmulXgLnhVicMGTAYpALftzpakVbsIq GHrMKsDRzROwNB'),(95,'tniaDSEtciIUvtHeBurBGDYiUYkbCKUtfxUjlBcFVLUpOdYIym'),(96,'QtvWxuGbjSLcdiEAQCpOecZQhlIJykFzBmEltjNnbsGiUbvW H'),(97,'PdSilErUyGOcRcLzoVLUoJEWVNehxODARISmEoCYjYtVzISoPk'),(98,'EujJUwvPPECb spwobtKyjCkrevPNyZDUOjDsaZUBZrLEOgpfG'),(99,'QmIK spsWFmqVGubXLMCDlpTzMojbFXYXRvxaLEQrhKgAkw nQ'),(100,'oWPoXXT VeHbkVWZfArevRVdDIGgLmczqgJbgyaFcxaNOHSrds');
/*!40000 ALTER TABLE `table1` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-05-03 13:43:20


