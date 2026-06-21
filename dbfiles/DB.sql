-- MySQL dump 10.13  Distrib 8.0.41, for Win64 (x86_64)
--
-- Host: localhost    Database: school_online
-- ------------------------------------------------------
-- Server version	8.0.41

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
-- Table structure for table `aichats`
--

DROP TABLE IF EXISTS `aichats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aichats` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(255) NOT NULL COMMENT 'عنوان چت',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `aichats_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `aichats`
--

LOCK TABLES `aichats` WRITE;
/*!40000 ALTER TABLE `aichats` DISABLE KEYS */;
INSERT INTO `aichats` VALUES (21,1,'سلام چه خبر...','2025-10-11 20:45:19');
/*!40000 ALTER TABLE `aichats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `aimessages`
--

DROP TABLE IF EXISTS `aimessages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aimessages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `chat_id` int NOT NULL,
  `sender` enum('user','bot') NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_chat_id` (`chat_id`),
  CONSTRAINT `aimessages_ibfk_1` FOREIGN KEY (`chat_id`) REFERENCES `aichats` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `aimessages`
--

LOCK TABLES `aimessages` WRITE;
/*!40000 ALTER TABLE `aimessages` DISABLE KEYS */;
INSERT INTO `aimessages` VALUES (81,21,'user','سلام چه خبر','2025-10-11 20:45:27'),(82,21,'bot','سلام! هم‌اکنون در دنیای داده‌ها پرواز می‌کنم—حسکوتی تازه، سوالیه؟ هر وقت نیازی داشتی، در خدمتم. – زیرو','2025-10-11 20:45:27');
/*!40000 ALTER TABLE `aimessages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `atlogs`
--

DROP TABLE IF EXISTS `atlogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `atlogs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `atlogs`
--

LOCK TABLES `atlogs` WRITE;
/*!40000 ALTER TABLE `atlogs` DISABLE KEYS */;
/*!40000 ALTER TABLE `atlogs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `session_date` date NOT NULL,
  `period` int NOT NULL,
  `status` enum('present','absent') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_date_period` (`student_id`,`session_date`,`period`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_chk_1` CHECK ((`period` between 1 and 4))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `badges`
--

DROP TABLE IF EXISTS `badges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `badges` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `image_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `badges`
--

LOCK TABLES `badges` WRITE;
/*!40000 ALTER TABLE `badges` DISABLE KEYS */;
INSERT INTO `badges` VALUES (1,'درس خوان','به دانش آموز های درس خوان برگزیده داده می شود','../../uploads/badges/slazzer-preview-hb6yt.png'),(4,'فعال','به دانش آموزان بسیار فعال داده می شود.','../../uploads/badges/17379231.png');
/*!40000 ALTER TABLE `badges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cards`
--

DROP TABLE IF EXISTS `cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cards` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `ufid` varchar(32) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ufid` (`ufid`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `cards_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cards`
--

LOCK TABLES `cards` WRITE;
/*!40000 ALTER TABLE `cards` DISABLE KEYS */;
/*!40000 ALTER TABLE `cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `class_sessions`
--

DROP TABLE IF EXISTS `class_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `class_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_course_id` int NOT NULL,
  `title` varchar(100) NOT NULL,
  `teacher_id` int NOT NULL,
  `start_time` datetime NOT NULL,
  `session_uuid` varchar(36) NOT NULL,
  `is_active` tinyint DEFAULT '1',
  `recorded_file` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_uuid` (`session_uuid`),
  KEY `class_course_id` (`class_course_id`),
  KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `class_sessions_ibfk_1` FOREIGN KEY (`class_course_id`) REFERENCES `classcourses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_sessions_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `class_sessions`
--

LOCK TABLES `class_sessions` WRITE;
/*!40000 ALTER TABLE `class_sessions` DISABLE KEYS */;
INSERT INTO `class_sessions` VALUES (2,9,'تستی',1,'2025-10-02 12:22:00','a00e4e7ddb08ca28602fdcd5bcc9da59',1,NULL,'2025-10-02 12:22:16');
/*!40000 ALTER TABLE `class_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `classcourses`
--

DROP TABLE IF EXISTS `classcourses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `classcourses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_id` int DEFAULT NULL,
  `course_name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `classcourses_ibfk_1` (`class_id`),
  CONSTRAINT `classcourses_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `classcourses`
--

LOCK TABLES `classcourses` WRITE;
/*!40000 ALTER TABLE `classcourses` DISABLE KEYS */;
INSERT INTO `classcourses` VALUES (9,1,'زیست 7/1'),(12,1,'فیزیک 7/1');
/*!40000 ALTER TABLE `classcourses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `classcourseteachers`
--

DROP TABLE IF EXISTS `classcourseteachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `classcourseteachers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_course_id` int DEFAULT NULL,
  `teacher_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `classcourseteachers_ibfk_1` (`class_course_id`),
  KEY `classcourseteachers_ibfk_2` (`teacher_id`),
  CONSTRAINT `classcourseteachers_ibfk_1` FOREIGN KEY (`class_course_id`) REFERENCES `classcourses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `classcourseteachers_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `classcourseteachers`
--

LOCK TABLES `classcourseteachers` WRITE;
/*!40000 ALTER TABLE `classcourseteachers` DISABLE KEYS */;
INSERT INTO `classcourseteachers` VALUES (7,9,13);
/*!40000 ALTER TABLE `classcourseteachers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `classes`
--

DROP TABLE IF EXISTS `classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `classes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_created_by` (`created_by`),
  CONSTRAINT `fk_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `classes`
--

LOCK TABLES `classes` WRITE;
/*!40000 ALTER TABLE `classes` DISABLE KEYS */;
INSERT INTO `classes` VALUES (1,'7/1',NULL,'2025-09-10 16:00:00'),(2,'7/2',NULL,'2025-09-10 16:00:00'),(3,'7/3',NULL,'2025-09-11 18:07:32'),(4,'7/4',1,'2025-09-28 21:07:53');
/*!40000 ALTER TABLE `classes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exam_questions`
--

DROP TABLE IF EXISTS `exam_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exam_questions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exam_id` int NOT NULL,
  `question_number` int NOT NULL,
  `correct_answer` char(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `exam_id` (`exam_id`),
  CONSTRAINT `exam_questions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exam_questions`
--

LOCK TABLES `exam_questions` WRITE;
/*!40000 ALTER TABLE `exam_questions` DISABLE KEYS */;
INSERT INTO `exam_questions` VALUES (18,5,1,'2'),(19,5,2,'3');
/*!40000 ALTER TABLE `exam_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exam_submissions`
--

DROP TABLE IF EXISTS `exam_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exam_submissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exam_id` int NOT NULL,
  `student_id` int NOT NULL,
  `answers` text,
  `grade` int DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `exam_id` (`exam_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `exam_submissions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exam_submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exam_submissions`
--

LOCK TABLES `exam_submissions` WRITE;
/*!40000 ALTER TABLE `exam_submissions` DISABLE KEYS */;
INSERT INTO `exam_submissions` VALUES (6,5,11,'{\"1\":\"1\",\"2\":\"3\"}',10,'2025-10-04 10:58:04');
/*!40000 ALTER TABLE `exam_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exams`
--

DROP TABLE IF EXISTS `exams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exams` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_course_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `title` varchar(100) NOT NULL,
  `question_count` int NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `duration` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `deadline` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `class_course_id` (`class_course_id`),
  KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`class_course_id`) REFERENCES `classcourses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exams_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exams`
--

LOCK TABLES `exams` WRITE;
/*!40000 ALTER TABLE `exams` DISABLE KEYS */;
INSERT INTO `exams` VALUES (5,12,1,'ازمون اول',2,'../../Uploads/exams/68e0cc6a2416e_Ababil-2-drone-FA-2048x1448.jpg',2,'2025-10-04 10:57:38','2025-10-05 10:57:00');
/*!40000 ALTER TABLE `exams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `form_responses`
--

DROP TABLE IF EXISTS `form_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_responses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `form_id` int NOT NULL,
  `response` text NOT NULL,
  `submitted_by` int DEFAULT NULL,
  `submitted_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `device_id` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `form_id` (`form_id`),
  KEY `submitted_by` (`submitted_by`),
  CONSTRAINT `form_responses_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`),
  CONSTRAINT `form_responses_ibfk_2` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `form_responses`
--

LOCK TABLES `form_responses` WRITE;
/*!40000 ALTER TABLE `form_responses` DISABLE KEYS */;
INSERT INTO `form_responses` VALUES (11,21,'{\"1\":\"محمدامین مدنی محمدی\",\"2\":\"گزینه 2\"}',1,'2025-10-04 11:05:14','29bac5677d7255eedc98118bf102fd3b'),(12,21,'{\"1\":\"تستی\",\"2\":\"گزینه 1\"}',11,'2025-10-04 11:05:32','cb73b1425f116931c93b7e18c10fce3a');
/*!40000 ALTER TABLE `form_responses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forms`
--

DROP TABLE IF EXISTS `forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `forms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `code` varchar(32) NOT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `forms_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forms`
--

LOCK TABLES `forms` WRITE;
/*!40000 ALTER TABLE `forms` DISABLE KEYS */;
INSERT INTO `forms` VALUES (21,'تستی','{\"title\":\"تستی\",\"description\":\"<p style=\\\"text-align: center;\\\"><strong>به نام خدا<\\/strong><\\/p>\\r\\n<p style=\\\"text-align: center;\\\"><strong>لطفا فرم را تکمیل نمایید<\\/strong><\\/p>\\r\\n<p style=\\\"text-align: center;\\\"><strong><img src=\\\"..\\/uploads\\/images\\/68e0cdab25c4c_blobid1759563177638.png\\\" alt=\\\"\\\" width=\\\"369\\\" height=\\\"77\\\"><\\/strong><\\/p>\",\"questions\":{\"1\":{\"label\":\"نام خود را وارد نمایید ؟\",\"type\":\"text\",\"required\":\"1\",\"placeholder\":\"پاسخ خود را دقیق وارد نمایید\",\"maxlength\":\"20\",\"options\":[],\"accept\":\"\",\"maxsize\":\"\",\"min\":\"\",\"max\":\"\",\"step\":\"\"},\"2\":{\"label\":\"گزینه درست را انتخاب بفرماییدض\",\"type\":\"multiple\",\"required\":\"0\",\"placeholder\":\"\",\"maxlength\":\"\",\"options\":[\"گزینه 1\",\"گزینه 2\",\"گزینه 3\"],\"accept\":\"\",\"maxsize\":\"\",\"min\":\"\",\"max\":\"\",\"step\":\"\"}}}','918b1b79306026e126e6cef22b33f7d7',1,'2025-10-04 07:34:40','2025-10-03 11:04:00','2025-10-05 11:04:00','../../uploads/logos/logo.png');
/*!40000 ALTER TABLE `forms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gallery`
--

DROP TABLE IF EXISTS `gallery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gallery` (
  `id` int NOT NULL AUTO_INCREMENT,
  `image_path` varchar(255) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `uploaded_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `gallery_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gallery`
--

LOCK TABLES `gallery` WRITE;
/*!40000 ALTER TABLE `gallery` DISABLE KEYS */;
INSERT INTO `gallery` VALUES (18,'uploads/gallery/img_68c32f3e96d1f.jpg','test','2025-09-11 23:51:08',1),(23,'uploads/gallery/img_68e0cabf7d111.png','سمپاد','2025-10-04 10:50:31',1);
/*!40000 ALTER TABLE `gallery` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `homework_submissions`
--

DROP TABLE IF EXISTS `homework_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `homework_submissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `homework_id` int NOT NULL,
  `student_id` int NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `text_response` text,
  `grade` int DEFAULT NULL,
  `feedback` text,
  `submitted_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `homework_id` (`homework_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `homework_submissions_ibfk_1` FOREIGN KEY (`homework_id`) REFERENCES `homeworks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `homework_submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `homework_submissions`
--

LOCK TABLES `homework_submissions` WRITE;
/*!40000 ALTER TABLE `homework_submissions` DISABLE KEYS */;
INSERT INTO `homework_submissions` VALUES (3,3,11,'../../Uploads/homework/students/68e0cbe7d9c28_تحقیق درمورد پهپاد ها.pptx','نتونستم ',20,'عالی','2025-10-04 10:55:27');
/*!40000 ALTER TABLE `homework_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `homeworks`
--

DROP TABLE IF EXISTS `homeworks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `homeworks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_course_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text,
  `file_path` varchar(255) DEFAULT NULL,
  `deadline` datetime NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `class_course_id` (`class_course_id`),
  KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `homeworks_ibfk_1` FOREIGN KEY (`class_course_id`) REFERENCES `classcourses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `homeworks_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `homeworks`
--

LOCK TABLES `homeworks` WRITE;
/*!40000 ALTER TABLE `homeworks` DISABLE KEYS */;
INSERT INTO `homeworks` VALUES (3,12,1,'تلکیف تست','عکس تمرین را ارسال کنید ','../../Uploads/homework/teachers/68e0cb2b846bf_تحقیق درمورد پهپاد ها.pptx','2025-10-05 10:52:00','2025-10-04 10:52:19');
/*!40000 ALTER TABLE `homeworks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `target_type` varchar(50) NOT NULL,
  `target_id` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `logs_ibfk_2_idx` (`target_id`)
) ENGINE=InnoDB AUTO_INCREMENT=402 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs`
--

LOCK TABLES `logs` WRITE;
/*!40000 ALTER TABLE `logs` DISABLE KEYS */;
INSERT INTO `logs` VALUES (10,-1,'login_failed','login',NULL,'2025-09-10 00:04:05'),(102,-1,'login_failed','login',NULL,'2025-09-11 13:24:50'),(143,-1,'login_failed','login',-1,'2025-09-11 19:55:25'),(144,1,'login_success','login',-1,'2025-09-11 19:55:34'),(145,1,'logout','logout',-1,'2025-09-11 19:55:40'),(146,1,'login_success','login',-1,'2025-09-11 19:55:46'),(147,1,'ساخت موفق دانش آموز','ساخت دانش آموز',32,'2025-09-11 20:06:05'),(148,1,'ویرایش موفق کاربر','ویرایش کاربر',11,'2025-09-11 20:09:34'),(149,1,'حذف موفق عکس','گالری',1,'2025-09-11 20:48:57'),(151,1,'خطای ثبت عکس در دیتابیس','گالری',NULL,'2025-09-11 20:54:01'),(161,1,'خطای ثبت عکس در دیتابیس','گالری',-1,'2025-09-11 20:55:58'),(163,1,'خطای ثبت عکس در دیتابیس','گالری',-1,'2025-09-11 20:56:09'),(171,1,'login_success','login',-1,'2025-09-11 23:14:54'),(183,1,'خطای سرور: SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`school_online`.`logs`, CONSTRAINT `logs_ibfk_2` FOREIGN KEY (`target_id`) REFERENCES `users` (`id`))','گالری',NULL,'2025-09-11 23:39:51'),(184,1,'آپلود موفق عکس','گالری',11,'2025-09-11 23:40:41'),(185,1,'حذف موفق عکس','گالری',11,'2025-09-11 23:40:44'),(187,1,'خطای سرور: SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`school_online`.`logs`, CONSTRAINT `logs_ibfk_2` FOREIGN KEY (`target_id`) REFERENCES `users` (`id`))','گالری',-1,'2025-09-11 23:40:49'),(188,1,'آپلود موفق عکس','گالری',13,'2025-09-11 23:40:55'),(190,1,'خطای سرور: SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`school_online`.`logs`, CONSTRAINT `logs_ibfk_2` FOREIGN KEY (`target_id`) REFERENCES `users` (`id`))','گالری',-1,'2025-09-11 23:40:58'),(191,1,'حذف موفق عکس','گالری',-1,'2025-09-11 23:41:19'),(193,1,'خطای سرور: SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`school_online`.`logs`, CONSTRAINT `logs_ibfk_2` FOREIGN KEY (`target_id`) REFERENCES `users` (`id`))','گالری',-1,'2025-09-11 23:41:24'),(194,1,'حذف موفق عکس','گالری',-1,'2025-09-11 23:41:56'),(195,1,'آپلود موفق عکس','گالری',-1,'2025-09-11 23:42:02'),(196,1,'حذف موفق عکس','گالری',-1,'2025-09-11 23:42:09'),(197,1,'آپلود موفق عکس','گالری',-1,'2025-09-11 23:42:16'),(198,1,'آپلود موفق عکس','گالری',-1,'2025-09-11 23:42:22'),(201,1,'حذف موفق عکس','گالری',-1,'2025-09-11 23:51:01'),(202,1,'حذف موفق عکس','گالری',-1,'2025-09-11 23:51:03'),(203,1,'آپلود موفق عکس','گالری',-1,'2025-09-11 23:51:08'),(204,1,'ویرایش موفق عکس','گالری',-1,'2025-09-11 23:51:13'),(205,1,'ویرایش موفق عکس','گالری',-1,'2025-09-11 23:51:18'),(206,1,'آپلود موفق عکس','گالری',-1,'2025-09-11 23:51:36'),(207,1,'آپلود موفق عکس','گالری',-1,'2025-09-11 23:51:53'),(208,1,'login_success','login',-1,'2025-09-12 10:43:59'),(209,1,'حذف موفق عکس','گالری',-1,'2025-09-12 10:44:42'),(210,1,'آپلود موفق عکس','گالری',-1,'2025-09-12 10:44:52'),(211,1,'ویرایش موفق عکس','گالری',-1,'2025-09-12 10:45:04'),(212,1,'ساخت موفق پست','ساخت پست',-1,'2025-09-12 10:49:18'),(213,1,'حذف موفق پست','حذف پست',-1,'2025-09-12 10:49:23'),(214,1,'login_success','login',-1,'2025-09-12 11:01:32'),(215,1,'ویرایش موفق کاربر','ویرایش کاربر',13,'2025-09-12 11:15:30'),(216,1,'ویرایش موفق کاربر','ویرایش کاربر',13,'2025-09-12 11:16:36'),(217,1,'ویرایش موفق کاربر','ویرایش کاربر',13,'2025-09-12 11:17:41'),(218,1,'login_success','login',-1,'2025-09-13 17:58:35'),(219,1,'ویرایش موفق کاربر','ویرایش کاربر',1,'2025-09-13 17:59:05'),(220,1,'logout','logout',-1,'2025-09-13 17:59:08'),(221,1,'login_success','login',-1,'2025-09-13 17:59:12'),(222,1,'logout','logout',-1,'2025-09-13 18:23:31'),(223,13,'login_success','login',-1,'2025-09-13 18:23:38'),(224,13,'logout','logout',-1,'2025-09-13 18:24:11'),(225,1,'login_success','login',-1,'2025-09-13 18:24:15'),(226,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-13 18:25:02'),(227,1,'logout','logout',-1,'2025-09-13 18:25:14'),(228,13,'login_success','login',-1,'2025-09-13 18:25:22'),(229,32,'login_success','login',-1,'2025-09-13 18:44:00'),(230,32,'logout','logout',-1,'2025-09-13 18:44:09'),(231,11,'login_failed','login',-1,'2025-09-13 18:44:14'),(232,11,'login_success','login',-1,'2025-09-13 18:44:19'),(233,11,'login_success','login',-1,'2025-09-13 19:23:15'),(234,11,'login_success','login',-1,'2025-09-13 20:36:38'),(235,11,'login_success','login',-1,'2025-09-13 20:49:58'),(236,13,'logout','logout',-1,'2025-09-13 21:05:42'),(237,1,'login_success','login',-1,'2025-09-13 21:05:51'),(238,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-13 21:10:53'),(239,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-13 21:10:58'),(240,11,'login_success','login',-1,'2025-09-13 21:34:02'),(241,1,'logout','logout',-1,'2025-09-14 00:07:54'),(242,1,'login_success','login',-1,'2025-09-14 00:16:28'),(243,-1,'login_failed','login',-1,'2025-09-14 10:47:29'),(244,1,'login_success','login',-1,'2025-09-14 10:47:35'),(245,11,'login_success','login',-1,'2025-09-14 11:14:40'),(246,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-14 11:33:06'),(247,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-14 11:33:12'),(248,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-14 11:33:24'),(249,1,'خطای درخواست نامعتبر','گالری',-1,'2025-09-14 14:30:11'),(250,1,'logout','logout',-1,'2025-09-14 17:59:12'),(251,1,'login_success','login',-1,'2025-09-14 17:59:20'),(252,1,'logout','logout',-1,'2025-09-14 17:59:29'),(253,1,'login_success','login',-1,'2025-09-14 18:10:22'),(254,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-14 18:11:55'),(255,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-14 18:12:23'),(256,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-14 18:12:56'),(257,1,'ویرایش موفق پست','ویرایش پست',-1,'2025-09-14 18:14:34'),(258,11,'login_success','login',-1,'2025-09-14 18:21:47'),(259,1,'logout','logout',-1,'2025-09-14 18:31:32'),(260,13,'login_success','login',-1,'2025-09-14 18:32:42'),(261,13,'logout','logout',-1,'2025-09-14 18:33:26'),(262,1,'login_success','login',-1,'2025-09-14 18:42:23'),(263,1,'login_success','login',-1,'2025-09-14 22:45:29'),(264,1,'ساخت موفق ادمین','ساخت ادمین',33,'2025-09-14 22:47:40'),(265,1,'حذف ناموفق کاربر','حذف کاربر',32,'2025-09-14 22:55:48'),(266,1,'حذف ناموفق کاربر','حذف کاربر',32,'2025-09-14 22:57:03'),(267,1,'حذف موفق کاربر','حذف کاربر',32,'2025-09-14 22:57:22'),(268,1,'ساخت موفق دانش آموز','ساخت دانش آموز',34,'2025-09-14 22:58:33'),(269,1,'ویرایش موفق کاربر','ویرایش کاربر',34,'2025-09-14 22:58:38'),(270,1,'ساخت موفق استاد','ساخت استاد',35,'2025-09-14 22:59:05'),(271,1,'حذف موفق کاربر','حذف کاربر',35,'2025-09-14 22:59:24'),(272,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-14 22:59:35'),(273,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-14 22:59:45'),(274,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-14 22:59:49'),(275,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-14 23:10:03'),(276,1,'ساخت موفق کلاس','ساخت کلاس',-1,'2025-09-14 23:10:16'),(277,1,'حذف موفق کلاس','حذف کلاس',5,'2025-09-14 23:18:13'),(278,1,'login_success','login',-1,'2025-09-15 11:10:22'),(279,1,'login_failed','login',-1,'2025-09-28 19:06:29'),(280,1,'login_failed','login',-1,'2025-09-28 19:07:08'),(281,1,'login_failed','login',-1,'2025-09-28 19:07:16'),(282,1,'login_failed','login',-1,'2025-09-28 19:07:36'),(283,1,'login_success','login',-1,'2025-09-28 19:08:12'),(284,1,'logout','logout',-1,'2025-09-28 19:59:01'),(285,1,'login_success','login',-1,'2025-09-28 19:59:29'),(286,1,'login_success','login',-1,'2025-09-28 20:55:07'),(287,1,'logout','logout',-1,'2025-09-28 20:57:08'),(288,1,'login_success','login',-1,'2025-09-28 20:57:37'),(289,1,'حذف موفق کاربر','حذف کاربر',34,'2025-09-28 20:59:02'),(290,1,'ساخت موفق دانش آموز','ساخت دانش آموز',36,'2025-09-28 21:05:47'),(291,1,'ویرایش موفق کاربر','ویرایش کاربر',36,'2025-09-28 21:06:12'),(292,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-28 21:06:39'),(293,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-28 21:06:51'),(294,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-28 21:06:56'),(295,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-28 21:07:03'),(296,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-28 21:07:06'),(297,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-28 21:07:11'),(298,1,'حذف موفق کلاس','حذف کلاس',4,'2025-09-28 21:07:47'),(299,1,'ساخت موفق کلاس','ساخت کلاس',-1,'2025-09-28 21:07:53'),(300,1,'ویرایش موفق عکس','گالری',-1,'2025-09-28 21:08:37'),(301,1,'حذف موفق عکس','گالری',-1,'2025-09-28 21:08:41'),(302,1,'حذف موفق عکس','گالری',-1,'2025-09-28 21:08:44'),(303,1,'آپلود موفق عکس','گالری',-1,'2025-09-28 21:09:17'),(304,1,'ویرایش موفق عکس','گالری',-1,'2025-09-28 21:09:42'),(305,1,'logout','logout',-1,'2025-09-28 21:14:10'),(306,11,'login_success','login',-1,'2025-09-28 21:14:22'),(307,11,'logout','logout',-1,'2025-09-28 21:17:26'),(308,1,'login_success','login',-1,'2025-09-28 22:18:30'),(309,1,'logout','logout',-1,'2025-09-28 22:18:41'),(310,1,'login_success','login',-1,'2025-10-01 18:37:49'),(311,1,'ساخت فرم','فرم',1,'2025-10-01 21:17:22'),(312,1,'حذف فرم','فرم',1,'2025-10-01 21:27:24'),(313,1,'ساخت فرم','فرم',2,'2025-10-01 21:28:16'),(314,1,'ساخت فرم','فرم',3,'2025-10-01 21:28:16'),(315,1,'ساخت فرم','فرم',4,'2025-10-01 21:28:51'),(316,1,'ساخت فرم','فرم',5,'2025-10-01 21:30:15'),(317,1,'حذف فرم','فرم',3,'2025-10-01 21:30:52'),(318,1,'حذف فرم','فرم',4,'2025-10-01 21:30:54'),(319,1,'حذف فرم','فرم',2,'2025-10-01 21:30:56'),(320,1,'حذف فرم','فرم',5,'2025-10-01 21:30:57'),(321,1,'ساخت فرم','فرم',6,'2025-10-01 21:31:10'),(322,1,'حذف فرم','فرم',6,'2025-10-01 21:35:21'),(323,1,'ساخت فرم','فرم',7,'2025-10-01 21:36:59'),(324,1,'ساخت فرم','فرم',8,'2025-10-01 21:37:17'),(325,1,'حذف فرم','فرم',7,'2025-10-01 21:37:30'),(326,1,'ساخت فرم','فرم',9,'2025-10-01 21:37:45'),(327,1,'حذف فرم','فرم',9,'2025-10-01 21:44:12'),(328,1,'حذف فرم','فرم',8,'2025-10-01 21:44:14'),(329,1,'ساخت فرم','فرم',10,'2025-10-01 21:44:54'),(330,1,'حذف فرم','فرم',10,'2025-10-01 22:01:53'),(331,1,'ساخت فرم','فرم',11,'2025-10-01 22:03:55'),(332,1,'ساخت فرم','فرم',12,'2025-10-01 22:21:59'),(333,1,'حذف فرم','فرم',11,'2025-10-01 22:22:24'),(334,1,'ساخت فرم','فرم',13,'2025-10-01 22:22:28'),(335,1,'حذف فرم','فرم',12,'2025-10-01 22:22:35'),(336,1,'ساخت فرم','فرم',14,'2025-10-01 22:25:16'),(337,1,'حذف فرم','فرم',13,'2025-10-01 22:25:19'),(338,1,'ساخت فرم','فرم',15,'2025-10-01 22:27:51'),(339,1,'حذف فرم','فرم',14,'2025-10-01 22:27:54'),(340,1,'ساخت فرم','فرم',16,'2025-10-01 22:29:25'),(341,1,'حذف فرم','فرم',15,'2025-10-01 22:29:27'),(342,1,'ساخت فرم','فرم',17,'2025-10-01 22:29:47'),(343,1,'حذف فرم','فرم',16,'2025-10-01 22:29:49'),(344,1,'ساخت فرم','فرم',18,'2025-10-01 22:36:20'),(345,1,'حذف فرم','فرم',17,'2025-10-01 22:36:22'),(346,1,'ساخت فرم','فرم',19,'2025-10-01 22:36:44'),(347,1,'حذف فرم','فرم',18,'2025-10-01 22:36:48'),(348,1,'ویرایش فرم','فرم',19,'2025-10-01 22:39:20'),(349,1,'ویرایش فرم','فرم',19,'2025-10-01 22:39:53'),(350,1,'login_success','login',-1,'2025-10-02 08:56:28'),(351,1,'ویرایش فرم','فرم',19,'2025-10-02 09:21:25'),(352,1,'ویرایش فرم','فرم',19,'2025-10-02 09:45:04'),(353,1,'ویرایش فرم','فرم',19,'2025-10-02 09:51:10'),(354,1,'ویرایش فرم','فرم',19,'2025-10-02 09:51:11'),(355,1,'ویرایش فرم','فرم',19,'2025-10-02 09:51:17'),(356,1,'ویرایش فرم','فرم',19,'2025-10-02 09:51:48'),(357,1,'ویرایش فرم','فرم',19,'2025-10-02 09:55:42'),(358,1,'حذف فرم','فرم',19,'2025-10-02 10:08:46'),(359,1,'ساخت فرم','فرم',20,'2025-10-02 10:12:56'),(360,1,'ویرایش فرم','فرم',20,'2025-10-02 10:13:13'),(361,1,'ویرایش فرم','فرم',20,'2025-10-02 10:17:16'),(362,1,'ویرایش فرم','فرم',20,'2025-10-02 10:17:30'),(363,-1,'login_failed','login',-1,'2025-10-02 10:23:17'),(364,11,'login_success','login',-1,'2025-10-02 10:43:32'),(365,1,'login_success','login',-1,'2025-10-02 21:57:59'),(366,1,'login_success','login',-1,'2025-10-03 13:58:43'),(367,1,'logout','logout',-1,'2025-10-03 14:36:24'),(368,1,'login_success','login',-1,'2025-10-03 14:36:35'),(369,1,'logout','logout',-1,'2025-10-03 14:36:57'),(370,11,'login_success','login',-1,'2025-10-03 14:37:12'),(371,11,'login_success','login',-1,'2025-10-03 14:37:45'),(372,1,'login_success','login',-1,'2025-10-03 14:46:02'),(373,11,'login_success','login',-1,'2025-10-03 14:58:15'),(374,1,'login_success','login',-1,'2025-10-03 15:51:21'),(375,33,'login_success','login',-1,'2025-10-03 16:00:54'),(376,1,'login_success','login',-1,'2025-10-03 23:14:54'),(377,1,'logout','logout',-1,'2025-10-03 23:15:51'),(378,1,'login_success','login',-1,'2025-10-03 23:17:27'),(379,1,'login_success','login',-1,'2025-10-04 10:47:08'),(380,1,'حذف موفق کاربر','حذف کاربر',36,'2025-10-04 10:48:14'),(381,1,'ساخت موفق دانش آموز','ساخت دانش آموز',37,'2025-10-04 10:48:36'),(382,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-10-04 10:49:36'),(383,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-10-04 10:49:49'),(384,1,'حذف موفق عکس','گالری',-1,'2025-10-04 10:50:18'),(385,1,'آپلود موفق عکس','گالری',-1,'2025-10-04 10:50:31'),(386,11,'login_failed','login',-1,'2025-10-04 10:54:41'),(387,11,'login_success','login',-1,'2025-10-04 10:54:47'),(388,1,'حذف فرم','فرم',20,'2025-10-04 11:02:13'),(389,1,'ساخت فرم','فرم',21,'2025-10-04 11:04:40'),(390,1,'logout','logout',-1,'2025-10-04 11:12:11'),(391,1,'login_success','login',-1,'2025-10-04 11:12:25'),(392,1,'logout','logout',-1,'2025-10-04 11:14:00'),(393,1,'login_success','login',-1,'2025-10-04 11:14:24'),(394,1,'login_success','login',-1,'2025-10-04 14:21:26'),(395,1,'logout','logout',-1,'2025-10-04 14:39:42'),(396,1,'login_success','login',-1,'2025-10-04 14:41:32'),(397,1,'login_success','login',-1,'2025-10-11 20:44:40'),(398,1,'login_success','login',-1,'2025-10-11 20:46:02'),(399,1,'logout','logout',-1,'2025-10-11 20:46:16'),(400,11,'login_failed','login',-1,'2025-10-11 20:46:27'),(401,11,'login_success','login',-1,'2025-10-11 20:46:35');
/*!40000 ALTER TABLE `logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `m_conversation_permissions`
--

DROP TABLE IF EXISTS `m_conversation_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `m_conversation_permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `conversation_id` int NOT NULL,
  `can_members_post` tinyint(1) DEFAULT '1',
  `can_members_add` tinyint(1) DEFAULT '0',
  `admins` json NOT NULL,
  PRIMARY KEY (`id`),
  KEY `conversation_id` (`conversation_id`),
  CONSTRAINT `m_conversation_permissions_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `m_conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `m_conversation_permissions`
--

LOCK TABLES `m_conversation_permissions` WRITE;
/*!40000 ALTER TABLE `m_conversation_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `m_conversation_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `m_conversations`
--

DROP TABLE IF EXISTS `m_conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `m_conversations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` enum('private','group','channel') DEFAULT 'private',
  `name` varchar(255) NOT NULL,
  `creator_id` int NOT NULL,
  `permissions` json DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `creator_id` (`creator_id`),
  CONSTRAINT `m_conversations_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `m_conversations`
--

LOCK TABLES `m_conversations` WRITE;
/*!40000 ALTER TABLE `m_conversations` DISABLE KEYS */;
INSERT INTO `m_conversations` VALUES (2,'private','دبیرستان باهنر 3',33,'{\"can_post\": true}',0,'2025-10-03 16:02:13','2025-10-03 16:02:13'),(3,'group','تست',33,'{\"can_post\": true, \"view_only\": false, \"can_manage_members\": true}',0,'2025-10-03 16:03:33','2025-10-03 16:03:33');
/*!40000 ALTER TABLE `m_conversations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `m_members`
--

DROP TABLE IF EXISTS `m_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `m_members` (
  `id` int NOT NULL AUTO_INCREMENT,
  `conversation_id` int NOT NULL,
  `user_id` int NOT NULL,
  `role_in_conv` enum('member','admin','manager') DEFAULT 'member',
  `permissions` json DEFAULT NULL,
  `joined_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_member` (`conversation_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `m_members_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `m_conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `m_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `m_members`
--

LOCK TABLES `m_members` WRITE;
/*!40000 ALTER TABLE `m_members` DISABLE KEYS */;
INSERT INTO `m_members` VALUES (3,2,33,'member',NULL,'2025-10-03 16:02:13'),(4,2,1,'member',NULL,'2025-10-03 16:02:13'),(5,3,33,'admin','{\"can_post\": true, \"can_view\": true, \"can_manage\": true}','2025-10-03 16:03:33'),(6,3,13,'admin','{\"can_post\": true, \"can_view\": true, \"can_manage\": true}','2025-10-03 16:03:33');
/*!40000 ALTER TABLE `m_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `m_messages`
--

DROP TABLE IF EXISTS `m_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `m_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `conversation_id` int NOT NULL,
  `sender_id` int NOT NULL,
  `message_text` text,
  `type` enum('text','image','voice','file') DEFAULT 'text',
  `is_pinned` tinyint(1) DEFAULT '0',
  `seen_by` json DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_conversation` (`conversation_id`),
  KEY `idx_sender` (`sender_id`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `m_messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `m_conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `m_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `m_messages`
--

LOCK TABLES `m_messages` WRITE;
/*!40000 ALTER TABLE `m_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `m_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `m_notifications`
--

DROP TABLE IF EXISTS `m_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `m_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `conversation_id` int NOT NULL,
  `message_id` int DEFAULT NULL,
  `type` enum('message','mention','group_join') DEFAULT 'message',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_conv` (`conversation_id`),
  CONSTRAINT `m_notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `m_notifications_ibfk_2` FOREIGN KEY (`conversation_id`) REFERENCES `m_conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `m_notifications`
--

LOCK TABLES `m_notifications` WRITE;
/*!40000 ALTER TABLE `m_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `m_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `m_private_chats`
--

DROP TABLE IF EXISTS `m_private_chats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `m_private_chats` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user1_id` int NOT NULL,
  `user2_id` int NOT NULL,
  `last_message_id` int DEFAULT NULL,
  `unread_count_user1` int DEFAULT '0',
  `unread_count_user2` int DEFAULT '0',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_pair` (`user1_id`,`user2_id`),
  KEY `user2_id` (`user2_id`),
  CONSTRAINT `m_private_chats_ibfk_1` FOREIGN KEY (`user1_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `m_private_chats_ibfk_2` FOREIGN KEY (`user2_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `m_private_chats`
--

LOCK TABLES `m_private_chats` WRITE;
/*!40000 ALTER TABLE `m_private_chats` DISABLE KEYS */;
INSERT INTO `m_private_chats` VALUES (2,1,33,NULL,0,0,'2025-10-03 16:02:13');
/*!40000 ALTER TABLE `m_private_chats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `class_course_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_ibfk_2` (`user_id`),
  KEY `messages_ibfk_3` (`class_course_id`),
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`class_course_id`) REFERENCES `classcourses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT INTO `messages` VALUES (22,1,'به نام خدا','2025-10-04 11:07:32',12),(23,11,'سلام تکلیف چی بود','2025-10-04 11:07:43',12);
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notes`
--

DROP TABLE IF EXISTS `notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_id` int NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `title` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `class_course_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `notes_ibfk_3` (`class_course_id`),
  CONSTRAINT `notes_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notes_ibfk_3` FOREIGN KEY (`class_course_id`) REFERENCES `classcourses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notes`
--

LOCK TABLES `notes` WRITE;
/*!40000 ALTER TABLE `notes` DISABLE KEYS */;
INSERT INTO `notes` VALUES (10,1,'uploads/notes/note_68e0ced6e8ce1.docx','تست','2025-10-04 11:07:58',12);
/*!40000 ALTER TABLE `notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `posts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `author_name` varchar(100) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts`
--

LOCK TABLES `posts` WRITE;
/*!40000 ALTER TABLE `posts` DISABLE KEYS */;
INSERT INTO `posts` VALUES (2,'۱۰ مهارت ضروری برای موفقیت تحصیلی و شغلی','محتوای کامل پست درباره مهارت‌ها...\r\nتست','محمدامین مدنی','Uploads/blog/1757585018_note_68c19042d9361.png','2024-10-19 12:00:00'),(3,'رازهای موفقیت در کار گروهی و پروژه‌های دانشجویی','محتوای کامل پست درباره کار گروهی...','محمدامین مدنی','Uploads/blog/1757585388_note_68c19042d9361.png','2024-11-05 15:00:00'),(7,'2پست تستی','تست \r\nتست 1\r\nتست 2\r\nتست 3\r\nتست 5 دو بار تست','محمدامین مدنی محمدی','Uploads/blog/1757861074_default.jpeg','2025-09-12 10:49:18');
/*!40000 ALTER TABLE `posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `report_cards`
--

DROP TABLE IF EXISTS `report_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_cards` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `class_course_id` int NOT NULL,
  `academic_year` int NOT NULL,
  `first_term_continuous` float DEFAULT NULL,
  `first_term_exam` float DEFAULT NULL,
  `second_term_continuous` float DEFAULT NULL,
  `second_term_exam` float DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`,`class_course_id`,`academic_year`),
  KEY `class_course_id` (`class_course_id`),
  CONSTRAINT `report_cards_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  CONSTRAINT `report_cards_ibfk_2` FOREIGN KEY (`class_course_id`) REFERENCES `classcourses` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_cards`
--

LOCK TABLES `report_cards` WRITE;
/*!40000 ALTER TABLE `report_cards` DISABLE KEYS */;
INSERT INTO `report_cards` VALUES (3,11,12,1404,9,20,15,17,'2025-10-04 07:29:47','2025-10-04 07:30:05');
/*!40000 ALTER TABLE `report_cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `session_files`
--

DROP TABLE IF EXISTS `session_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `session_files` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` int NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` enum('pdf','ppt') NOT NULL,
  `uploaded_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  CONSTRAINT `session_files_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `class_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `session_files`
--

LOCK TABLES `session_files` WRITE;
/*!40000 ALTER TABLE `session_files` DISABLE KEYS */;
/*!40000 ALTER TABLE `session_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `key_name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teachers`
--

DROP TABLE IF EXISTS `teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teachers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `specialty` varchar(100) DEFAULT NULL,
  `bio` text,
  `profile_image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teachers`
--

LOCK TABLES `teachers` WRITE;
/*!40000 ALTER TABLE `teachers` DISABLE KEYS */;
INSERT INTO `teachers` VALUES (1,13,'فیزیک','دبیر فیزیک','Uploads/teachers/teacher_68c3d01d26a22.png'),(2,33,'مدیر دبیرستان',NULL,'Uploads/admins/admin_68c714d3dcaa9.jpg');
/*!40000 ALTER TABLE `teachers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_badges`
--

DROP TABLE IF EXISTS `user_badges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_badges` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `badge_id` int NOT NULL,
  `awarded_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `badge_id` (`badge_id`),
  CONSTRAINT `user_badges_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `user_badges_ibfk_2` FOREIGN KEY (`badge_id`) REFERENCES `badges` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_badges`
--

LOCK TABLES `user_badges` WRITE;
/*!40000 ALTER TABLE `user_badges` DISABLE KEYS */;
INSERT INTO `user_badges` VALUES (5,11,4,'2025-10-04 11:01:35');
/*!40000 ALTER TABLE `user_badges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_points`
--

DROP TABLE IF EXISTS `user_points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_points` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `points` int DEFAULT '0',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_points_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_points`
--

LOCK TABLES `user_points` WRITE;
/*!40000 ALTER TABLE `user_points` DISABLE KEYS */;
INSERT INTO `user_points` VALUES (3,11,10,'2025-10-04 11:01:51');
/*!40000 ALTER TABLE `user_points` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','teacher','admin') NOT NULL,
  `national_id` varchar(10) DEFAULT NULL,
  `class_id` int DEFAULT NULL,
  `phone` varchar(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_active` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `class_id` (`class_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (-1,'!کاربر ثبت نام نشده','notregistereduser','0','student',NULL,NULL,NULL,'2025-09-11 19:54:16',NULL),(1,'دبیرستان باهنر 3','bahonar','$2y$10$HSLRHXj16IklWtUtv6CHIelzU0daSlGLWKlQGvpW6/g2Gz2hRzEqO','admin','',NULL,NULL,'2025-09-11 19:13:23','2025-10-03 16:30:20'),(11,'محمدامین مدنی محمدی','aminmadani','$2y$10$rzZueYc1cvcVWj9difT93u3GXg0QTgHAxsSlK2j2vtf1GG7go1eu.','student','0315324457',1,NULL,'2025-09-11 19:23:06',NULL),(13,'امیررضا یزدانی','yazdani','$2y$10$HjCS7/464XSHIoaZ1Kxa.ePBcmld/K/n3vfuftumNz9BX0CisL1rW','teacher','',NULL,NULL,'2025-09-11 19:23:46',NULL),(33,'آقای امیدی','mromidi','$2y$10$BN9959GJvphG8HFC5gDpweCpymvwH89oSOHT1lEgbjWzwQmzHAFOa','admin',NULL,NULL,NULL,'2025-09-14 22:47:40','2025-10-03 16:13:24'),(37,'آراد مظفری کاکاوند','0312345678','$2y$10$vEuyOV1HgDT9Ko1qXgSOT.lD7FndfLkNctqwHajkrC7kM/smFBvdW','student','0312345678',1,NULL,'2025-10-04 10:48:36','2025-10-04 10:49:36');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-19 23:06:37
