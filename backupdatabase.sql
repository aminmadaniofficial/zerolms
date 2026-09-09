/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.18-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: school_online
-- ------------------------------------------------------
-- Server version	10.11.18-MariaDB-ubu2204

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
-- Table structure for table `aichats`
--

DROP TABLE IF EXISTS `aichats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `aichats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL COMMENT 'عنوان چت',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `aichats_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `aichats`
--

LOCK TABLES `aichats` WRITE;
/*!40000 ALTER TABLE `aichats` DISABLE KEYS */;
INSERT INTO `aichats` VALUES
(23,11,'سلام...','2026-06-10 08:25:17'),
(32,1,'سلام...','2026-07-21 16:27:16'),
(33,11,'سلام...','2026-07-21 17:38:59'),
(34,1,'سلام...','2026-07-21 18:05:48'),
(35,1,'سلام...','2026-07-22 06:03:08');
/*!40000 ALTER TABLE `aichats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `aimessages`
--

DROP TABLE IF EXISTS `aimessages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `aimessages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chat_id` int(11) NOT NULL,
  `sender` enum('user','bot') NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_chat_id` (`chat_id`),
  CONSTRAINT `aimessages_ibfk_1` FOREIGN KEY (`chat_id`) REFERENCES `aichats` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `aimessages`
--

LOCK TABLES `aimessages` WRITE;
/*!40000 ALTER TABLE `aimessages` DISABLE KEYS */;
INSERT INTO `aimessages` VALUES
(89,32,'user','سلام','2026-07-21 16:27:20'),
(90,32,'bot','سلام! 👋 من **زیرو** هستم، دستیار هوشمند شما در سامانه مدرسه.\nچطور می‌توانم امروز به شما کمک کنم؟ 😊','2026-07-21 16:27:20'),
(91,34,'user','سلام','2026-07-21 18:05:57'),
(92,34,'bot','سلام! 👋 به **مدرسه** خوش اومدی.  \nمن **زیرو** هستم، دستیار هوشمند یادگیری‌ت. اینجام تا درسی‌ها رو ساده‌تر، جذاب‌تر و موثرتر برات کنم.\n\nچطور می‌تونم امروز کمکت کنم؟ 🚀  \nمثلاً می‌تونم:\n- یه مفهوم سخت رو **به زبان ساده** توض\n- **برنامه‌ی مطالعاتی** أسبوعی/ماهانه برات بسازم  \n- **تمرین و نمونه‌سوال** با راهنمای گام‌به‌گام بدهم  \n- تکنیک‌های **یادگیری فعال** و **مدیریت زمان** رو یاد بدم  \n- یا فقط برای **انگیزشی و مشاوره** اینجام باشم  \n\nکافیه بگی **چه درسی؟** و **چه هدفی** داری، تا یه برنامه‌ی عملًا ببری. 💡','2026-07-21 18:05:57'),
(93,35,'user','سلام','2026-07-22 06:03:25'),
(94,35,'bot','سلام! 👋  \nمن **«زیرو»** هستم، دستیار هوشمند سامانه مدرسه. چطور می‌توانم امروز بهت کمک کنم؟','2026-07-22 06:03:25');
/*!40000 ALTER TABLE `aimessages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `atlogs`
--

DROP TABLE IF EXISTS `atlogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `atlogs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `session_date` date NOT NULL,
  `period` int(11) NOT NULL,
  `status` enum('present','absent') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_date_period` (`student_id`,`session_date`,`period`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_chk_1` CHECK (`period` between 1 and 4)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `badges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `badges`
--

LOCK TABLES `badges` WRITE;
/*!40000 ALTER TABLE `badges` DISABLE KEYS */;
INSERT INTO `badges` VALUES
(1,'درس خوان','به دانش آموز های درس خوان برگزیده داده می شود','../../uploads/badges/slazzer-preview-hb6yt.png'),
(4,'فعال','به دانش آموزان بسیار فعال داده می شود.','../../uploads/badges/17379231.png');
/*!40000 ALTER TABLE `badges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cards`
--

DROP TABLE IF EXISTS `cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `ufid` varchar(32) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ufid` (`ufid`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `cards_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `class_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_course_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `session_uuid` varchar(36) NOT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `recorded_file` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_uuid` (`session_uuid`),
  KEY `class_course_id` (`class_course_id`),
  KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `class_sessions_ibfk_1` FOREIGN KEY (`class_course_id`) REFERENCES `classcourses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_sessions_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `class_sessions`
--

LOCK TABLES `class_sessions` WRITE;
/*!40000 ALTER TABLE `class_sessions` DISABLE KEYS */;
INSERT INTO `class_sessions` VALUES
(2,9,'تستی',1,'2025-10-02 12:22:00','a00e4e7ddb08ca28602fdcd5bcc9da59',1,NULL,'2025-10-02 12:22:16');
/*!40000 ALTER TABLE `class_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `classcourses`
--

DROP TABLE IF EXISTS `classcourses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `classcourses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) DEFAULT NULL,
  `course_name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `classcourses_ibfk_1` (`class_id`),
  CONSTRAINT `classcourses_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `classcourses`
--

LOCK TABLES `classcourses` WRITE;
/*!40000 ALTER TABLE `classcourses` DISABLE KEYS */;
INSERT INTO `classcourses` VALUES
(9,1,'زیست 7/1'),
(12,1,'فیزیک 7/1'),
(13,7,'ریاضی 8/1');
/*!40000 ALTER TABLE `classcourses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `classcourseteachers`
--

DROP TABLE IF EXISTS `classcourseteachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `classcourseteachers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_course_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `classcourseteachers_ibfk_1` (`class_course_id`),
  KEY `classcourseteachers_ibfk_2` (`teacher_id`),
  CONSTRAINT `classcourseteachers_ibfk_1` FOREIGN KEY (`class_course_id`) REFERENCES `classcourses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `classcourseteachers_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `classcourseteachers`
--

LOCK TABLES `classcourseteachers` WRITE;
/*!40000 ALTER TABLE `classcourseteachers` DISABLE KEYS */;
/*!40000 ALTER TABLE `classcourseteachers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `classes`
--

DROP TABLE IF EXISTS `classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `classes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_created_by` (`created_by`),
  CONSTRAINT `fk_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `classes`
--

LOCK TABLES `classes` WRITE;
/*!40000 ALTER TABLE `classes` DISABLE KEYS */;
INSERT INTO `classes` VALUES
(1,'7/1',NULL,'2025-09-10 16:00:00'),
(2,'7/2',NULL,'2025-09-10 16:00:00'),
(3,'7/3',NULL,'2025-09-11 18:07:32'),
(4,'7/4',1,'2025-09-28 21:07:53'),
(7,'8/1',1,'2026-07-21 17:15:17');
/*!40000 ALTER TABLE `classes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exam_questions`
--

DROP TABLE IF EXISTS `exam_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `exam_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_id` int(11) NOT NULL,
  `question_number` int(11) NOT NULL,
  `correct_answer` char(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `exam_id` (`exam_id`),
  CONSTRAINT `exam_questions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exam_questions`
--

LOCK TABLES `exam_questions` WRITE;
/*!40000 ALTER TABLE `exam_questions` DISABLE KEYS */;
INSERT INTO `exam_questions` VALUES
(18,5,1,'2'),
(19,5,2,'3');
/*!40000 ALTER TABLE `exam_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exam_submissions`
--

DROP TABLE IF EXISTS `exam_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `exam_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `answers` text DEFAULT NULL,
  `grade` int(11) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `exam_id` (`exam_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `exam_submissions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exam_submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exam_submissions`
--

LOCK TABLES `exam_submissions` WRITE;
/*!40000 ALTER TABLE `exam_submissions` DISABLE KEYS */;
INSERT INTO `exam_submissions` VALUES
(6,5,11,'{\"1\":\"1\",\"2\":\"3\"}',9,'2025-10-04 10:58:04');
/*!40000 ALTER TABLE `exam_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exams`
--

DROP TABLE IF EXISTS `exams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `exams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_course_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `question_count` int(11) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `duration` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `deadline` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `class_course_id` (`class_course_id`),
  KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`class_course_id`) REFERENCES `classcourses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exams_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exams`
--

LOCK TABLES `exams` WRITE;
/*!40000 ALTER TABLE `exams` DISABLE KEYS */;
INSERT INTO `exams` VALUES
(5,12,1,'ازمون اول',2,'../../Uploads/exams/68e0cc6a2416e_Ababil-2-drone-FA-2048x1448.jpg',2,'2025-10-04 10:57:38','2025-10-05 10:57:00');
/*!40000 ALTER TABLE `exams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `extensions`
--

DROP TABLE IF EXISTS `extensions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `extensions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `extension_name` varchar(255) DEFAULT NULL,
  `extension_token` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `version` varchar(50) DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `extensions`
--

LOCK TABLES `extensions` WRITE;
/*!40000 ALTER TABLE `extensions` DISABLE KEYS */;
/*!40000 ALTER TABLE `extensions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback`
--

LOCK TABLES `feedback` WRITE;
/*!40000 ALTER TABLE `feedback` DISABLE KEYS */;
INSERT INTO `feedback` VALUES
(1,1,5,'عالی','2026-07-31 18:33:00');
/*!40000 ALTER TABLE `feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `form_responses`
--

DROP TABLE IF EXISTS `form_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_responses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `form_id` int(11) NOT NULL,
  `response` text NOT NULL,
  `submitted_by` int(11) DEFAULT NULL,
  `submitted_at` datetime DEFAULT current_timestamp(),
  `device_id` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `form_id` (`form_id`),
  KEY `submitted_by` (`submitted_by`),
  CONSTRAINT `form_responses_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`),
  CONSTRAINT `form_responses_ibfk_2` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `form_responses`
--

LOCK TABLES `form_responses` WRITE;
/*!40000 ALTER TABLE `form_responses` DISABLE KEYS */;
INSERT INTO `form_responses` VALUES
(11,21,'{\"1\":\"محمدامین مدنی محمدی\",\"2\":\"گزینه 2\"}',1,'2025-10-04 11:05:14','29bac5677d7255eedc98118bf102fd3b'),
(12,21,'{\"1\":\"تستی\",\"2\":\"گزینه 1\"}',11,'2025-10-04 11:05:32','cb73b1425f116931c93b7e18c10fce3a');
/*!40000 ALTER TABLE `form_responses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forms`
--

DROP TABLE IF EXISTS `forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `forms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `code` varchar(32) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `forms_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forms`
--

LOCK TABLES `forms` WRITE;
/*!40000 ALTER TABLE `forms` DISABLE KEYS */;
INSERT INTO `forms` VALUES
(21,'تستی','{\"title\":\"تستی\",\"description\":\"<p style=\\\"text-align: center;\\\"><strong>به نام خدا<\\/strong><\\/p>\\r\\n<p style=\\\"text-align: center;\\\"><strong>لطفا فرم را تکمیل نمایید<\\/strong><\\/p>\\r\\n<p style=\\\"text-align: center;\\\"><strong><img src=\\\"..\\/uploads\\/images\\/68e0cdab25c4c_blobid1759563177638.png\\\" alt=\\\"\\\" width=\\\"369\\\" height=\\\"77\\\"><\\/strong><\\/p>\",\"questions\":{\"1\":{\"label\":\"نام خود را وارد نمایید ؟\",\"type\":\"text\",\"required\":\"1\",\"placeholder\":\"پاسخ خود را دقیق وارد نمایید\",\"maxlength\":\"20\",\"options\":[],\"accept\":\"\",\"maxsize\":\"\",\"min\":\"\",\"max\":\"\",\"step\":\"\"},\"2\":{\"label\":\"گزینه درست را انتخاب بفرماییدض\",\"type\":\"multiple\",\"required\":\"0\",\"placeholder\":\"\",\"maxlength\":\"\",\"options\":[\"گزینه 1\",\"گزینه 2\",\"گزینه 3\"],\"accept\":\"\",\"maxsize\":\"\",\"min\":\"\",\"max\":\"\",\"step\":\"\"}}}','918b1b79306026e126e6cef22b33f7d7',1,'2025-10-04 07:34:40',NULL,'2026-06-13 21:07:00',NULL);
/*!40000 ALTER TABLE `forms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gallery`
--

DROP TABLE IF EXISTS `gallery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `gallery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image_path` varchar(255) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `uploaded_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `gallery_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gallery`
--

LOCK TABLES `gallery` WRITE;
/*!40000 ALTER TABLE `gallery` DISABLE KEYS */;
INSERT INTO `gallery` VALUES
(18,'uploads/gallery/img_68c32f3e96d1f.jpg','test','2025-09-11 23:51:08',1);
/*!40000 ALTER TABLE `gallery` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `homework_submissions`
--

DROP TABLE IF EXISTS `homework_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `homework_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `homework_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `text_response` text DEFAULT NULL,
  `grade` int(11) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `submitted_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `homework_id` (`homework_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `homework_submissions_ibfk_1` FOREIGN KEY (`homework_id`) REFERENCES `homeworks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `homework_submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `homework_submissions`
--

LOCK TABLES `homework_submissions` WRITE;
/*!40000 ALTER TABLE `homework_submissions` DISABLE KEYS */;
INSERT INTO `homework_submissions` VALUES
(3,3,11,'../../Uploads/homework/students/68e0cbe7d9c28_تحقیق درمورد پهپاد ها.pptx','نتونستم ',20,'عالی','2025-10-04 10:55:27');
/*!40000 ALTER TABLE `homework_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `homeworks`
--

DROP TABLE IF EXISTS `homeworks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `homeworks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_course_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `deadline` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `class_course_id` (`class_course_id`),
  KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `homeworks_ibfk_1` FOREIGN KEY (`class_course_id`) REFERENCES `classcourses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `homeworks_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `homeworks`
--

LOCK TABLES `homeworks` WRITE;
/*!40000 ALTER TABLE `homeworks` DISABLE KEYS */;
INSERT INTO `homeworks` VALUES
(3,12,1,'تلکیف تست','عکس تمرین را ارسال کنید ','../../Uploads/homework/teachers/68e0cb2b846bf_تحقیق درمورد پهپاد ها.pptx','2025-10-05 10:52:00','2025-10-04 10:52:19');
/*!40000 ALTER TABLE `homeworks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `target_type` varchar(50) NOT NULL,
  `target_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `logs_ibfk_2_idx` (`target_id`)
) ENGINE=InnoDB AUTO_INCREMENT=502 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs`
--

LOCK TABLES `logs` WRITE;
/*!40000 ALTER TABLE `logs` DISABLE KEYS */;
INSERT INTO `logs` VALUES
(10,-1,'login_failed','login',NULL,'2025-09-10 00:04:05'),
(102,-1,'login_failed','login',NULL,'2025-09-11 13:24:50'),
(143,-1,'login_failed','login',-1,'2025-09-11 19:55:25'),
(144,1,'login_success','login',-1,'2025-09-11 19:55:34'),
(145,1,'logout','logout',-1,'2025-09-11 19:55:40'),
(146,1,'login_success','login',-1,'2025-09-11 19:55:46'),
(147,1,'ساخت موفق دانش آموز','ساخت دانش آموز',32,'2025-09-11 20:06:05'),
(148,1,'ویرایش موفق کاربر','ویرایش کاربر',11,'2025-09-11 20:09:34'),
(149,1,'حذف موفق عکس','گالری',1,'2025-09-11 20:48:57'),
(151,1,'خطای ثبت عکس در دیتابیس','گالری',NULL,'2025-09-11 20:54:01'),
(161,1,'خطای ثبت عکس در دیتابیس','گالری',-1,'2025-09-11 20:55:58'),
(163,1,'خطای ثبت عکس در دیتابیس','گالری',-1,'2025-09-11 20:56:09'),
(171,1,'login_success','login',-1,'2025-09-11 23:14:54'),
(183,1,'خطای سرور: SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`school_online`.`logs`, CONSTRAINT `logs_ibfk_2` FOREIGN KEY (`target_id`) REFERENCES `users` (`id`))','گالری',NULL,'2025-09-11 23:39:51'),
(184,1,'آپلود موفق عکس','گالری',11,'2025-09-11 23:40:41'),
(185,1,'حذف موفق عکس','گالری',11,'2025-09-11 23:40:44'),
(187,1,'خطای سرور: SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`school_online`.`logs`, CONSTRAINT `logs_ibfk_2` FOREIGN KEY (`target_id`) REFERENCES `users` (`id`))','گالری',-1,'2025-09-11 23:40:49'),
(188,1,'آپلود موفق عکس','گالری',13,'2025-09-11 23:40:55'),
(190,1,'خطای سرور: SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`school_online`.`logs`, CONSTRAINT `logs_ibfk_2` FOREIGN KEY (`target_id`) REFERENCES `users` (`id`))','گالری',-1,'2025-09-11 23:40:58'),
(191,1,'حذف موفق عکس','گالری',-1,'2025-09-11 23:41:19'),
(193,1,'خطای سرور: SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`school_online`.`logs`, CONSTRAINT `logs_ibfk_2` FOREIGN KEY (`target_id`) REFERENCES `users` (`id`))','گالری',-1,'2025-09-11 23:41:24'),
(194,1,'حذف موفق عکس','گالری',-1,'2025-09-11 23:41:56'),
(195,1,'آپلود موفق عکس','گالری',-1,'2025-09-11 23:42:02'),
(196,1,'حذف موفق عکس','گالری',-1,'2025-09-11 23:42:09'),
(197,1,'آپلود موفق عکس','گالری',-1,'2025-09-11 23:42:16'),
(198,1,'آپلود موفق عکس','گالری',-1,'2025-09-11 23:42:22'),
(201,1,'حذف موفق عکس','گالری',-1,'2025-09-11 23:51:01'),
(202,1,'حذف موفق عکس','گالری',-1,'2025-09-11 23:51:03'),
(203,1,'آپلود موفق عکس','گالری',-1,'2025-09-11 23:51:08'),
(204,1,'ویرایش موفق عکس','گالری',-1,'2025-09-11 23:51:13'),
(205,1,'ویرایش موفق عکس','گالری',-1,'2025-09-11 23:51:18'),
(206,1,'آپلود موفق عکس','گالری',-1,'2025-09-11 23:51:36'),
(207,1,'آپلود موفق عکس','گالری',-1,'2025-09-11 23:51:53'),
(208,1,'login_success','login',-1,'2025-09-12 10:43:59'),
(209,1,'حذف موفق عکس','گالری',-1,'2025-09-12 10:44:42'),
(210,1,'آپلود موفق عکس','گالری',-1,'2025-09-12 10:44:52'),
(211,1,'ویرایش موفق عکس','گالری',-1,'2025-09-12 10:45:04'),
(212,1,'ساخت موفق پست','ساخت پست',-1,'2025-09-12 10:49:18'),
(213,1,'حذف موفق پست','حذف پست',-1,'2025-09-12 10:49:23'),
(214,1,'login_success','login',-1,'2025-09-12 11:01:32'),
(215,1,'ویرایش موفق کاربر','ویرایش کاربر',13,'2025-09-12 11:15:30'),
(216,1,'ویرایش موفق کاربر','ویرایش کاربر',13,'2025-09-12 11:16:36'),
(217,1,'ویرایش موفق کاربر','ویرایش کاربر',13,'2025-09-12 11:17:41'),
(218,1,'login_success','login',-1,'2025-09-13 17:58:35'),
(219,1,'ویرایش موفق کاربر','ویرایش کاربر',1,'2025-09-13 17:59:05'),
(220,1,'logout','logout',-1,'2025-09-13 17:59:08'),
(221,1,'login_success','login',-1,'2025-09-13 17:59:12'),
(222,1,'logout','logout',-1,'2025-09-13 18:23:31'),
(223,13,'login_success','login',-1,'2025-09-13 18:23:38'),
(224,13,'logout','logout',-1,'2025-09-13 18:24:11'),
(225,1,'login_success','login',-1,'2025-09-13 18:24:15'),
(226,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-13 18:25:02'),
(227,1,'logout','logout',-1,'2025-09-13 18:25:14'),
(228,13,'login_success','login',-1,'2025-09-13 18:25:22'),
(229,32,'login_success','login',-1,'2025-09-13 18:44:00'),
(230,32,'logout','logout',-1,'2025-09-13 18:44:09'),
(231,11,'login_failed','login',-1,'2025-09-13 18:44:14'),
(232,11,'login_success','login',-1,'2025-09-13 18:44:19'),
(233,11,'login_success','login',-1,'2025-09-13 19:23:15'),
(234,11,'login_success','login',-1,'2025-09-13 20:36:38'),
(235,11,'login_success','login',-1,'2025-09-13 20:49:58'),
(236,13,'logout','logout',-1,'2025-09-13 21:05:42'),
(237,1,'login_success','login',-1,'2025-09-13 21:05:51'),
(238,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-13 21:10:53'),
(239,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-13 21:10:58'),
(240,11,'login_success','login',-1,'2025-09-13 21:34:02'),
(241,1,'logout','logout',-1,'2025-09-14 00:07:54'),
(242,1,'login_success','login',-1,'2025-09-14 00:16:28'),
(243,-1,'login_failed','login',-1,'2025-09-14 10:47:29'),
(244,1,'login_success','login',-1,'2025-09-14 10:47:35'),
(245,11,'login_success','login',-1,'2025-09-14 11:14:40'),
(246,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-14 11:33:06'),
(247,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-14 11:33:12'),
(248,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-14 11:33:24'),
(249,1,'خطای درخواست نامعتبر','گالری',-1,'2025-09-14 14:30:11'),
(250,1,'logout','logout',-1,'2025-09-14 17:59:12'),
(251,1,'login_success','login',-1,'2025-09-14 17:59:20'),
(252,1,'logout','logout',-1,'2025-09-14 17:59:29'),
(253,1,'login_success','login',-1,'2025-09-14 18:10:22'),
(254,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-14 18:11:55'),
(255,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-14 18:12:23'),
(256,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-14 18:12:56'),
(257,1,'ویرایش موفق پست','ویرایش پست',-1,'2025-09-14 18:14:34'),
(258,11,'login_success','login',-1,'2025-09-14 18:21:47'),
(259,1,'logout','logout',-1,'2025-09-14 18:31:32'),
(260,13,'login_success','login',-1,'2025-09-14 18:32:42'),
(261,13,'logout','logout',-1,'2025-09-14 18:33:26'),
(262,1,'login_success','login',-1,'2025-09-14 18:42:23'),
(263,1,'login_success','login',-1,'2025-09-14 22:45:29'),
(264,1,'ساخت موفق ادمین','ساخت ادمین',33,'2025-09-14 22:47:40'),
(265,1,'حذف ناموفق کاربر','حذف کاربر',32,'2025-09-14 22:55:48'),
(266,1,'حذف ناموفق کاربر','حذف کاربر',32,'2025-09-14 22:57:03'),
(267,1,'حذف موفق کاربر','حذف کاربر',32,'2025-09-14 22:57:22'),
(268,1,'ساخت موفق دانش آموز','ساخت دانش آموز',34,'2025-09-14 22:58:33'),
(269,1,'ویرایش موفق کاربر','ویرایش کاربر',34,'2025-09-14 22:58:38'),
(270,1,'ساخت موفق استاد','ساخت استاد',35,'2025-09-14 22:59:05'),
(271,1,'حذف موفق کاربر','حذف کاربر',35,'2025-09-14 22:59:24'),
(272,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-14 22:59:35'),
(273,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-14 22:59:45'),
(274,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-14 22:59:49'),
(275,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-14 23:10:03'),
(276,1,'ساخت موفق کلاس','ساخت کلاس',-1,'2025-09-14 23:10:16'),
(277,1,'حذف موفق کلاس','حذف کلاس',5,'2025-09-14 23:18:13'),
(278,1,'login_success','login',-1,'2025-09-15 11:10:22'),
(279,1,'login_failed','login',-1,'2025-09-28 19:06:29'),
(280,1,'login_failed','login',-1,'2025-09-28 19:07:08'),
(281,1,'login_failed','login',-1,'2025-09-28 19:07:16'),
(282,1,'login_failed','login',-1,'2025-09-28 19:07:36'),
(283,1,'login_success','login',-1,'2025-09-28 19:08:12'),
(284,1,'logout','logout',-1,'2025-09-28 19:59:01'),
(285,1,'login_success','login',-1,'2025-09-28 19:59:29'),
(286,1,'login_success','login',-1,'2025-09-28 20:55:07'),
(287,1,'logout','logout',-1,'2025-09-28 20:57:08'),
(288,1,'login_success','login',-1,'2025-09-28 20:57:37'),
(289,1,'حذف موفق کاربر','حذف کاربر',34,'2025-09-28 20:59:02'),
(290,1,'ساخت موفق دانش آموز','ساخت دانش آموز',36,'2025-09-28 21:05:47'),
(291,1,'ویرایش موفق کاربر','ویرایش کاربر',36,'2025-09-28 21:06:12'),
(292,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-28 21:06:39'),
(293,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-28 21:06:51'),
(294,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-28 21:06:56'),
(295,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-28 21:07:03'),
(296,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-28 21:07:06'),
(297,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-09-28 21:07:11'),
(298,1,'حذف موفق کلاس','حذف کلاس',4,'2025-09-28 21:07:47'),
(299,1,'ساخت موفق کلاس','ساخت کلاس',-1,'2025-09-28 21:07:53'),
(300,1,'ویرایش موفق عکس','گالری',-1,'2025-09-28 21:08:37'),
(301,1,'حذف موفق عکس','گالری',-1,'2025-09-28 21:08:41'),
(302,1,'حذف موفق عکس','گالری',-1,'2025-09-28 21:08:44'),
(303,1,'آپلود موفق عکس','گالری',-1,'2025-09-28 21:09:17'),
(304,1,'ویرایش موفق عکس','گالری',-1,'2025-09-28 21:09:42'),
(305,1,'logout','logout',-1,'2025-09-28 21:14:10'),
(306,11,'login_success','login',-1,'2025-09-28 21:14:22'),
(307,11,'logout','logout',-1,'2025-09-28 21:17:26'),
(308,1,'login_success','login',-1,'2025-09-28 22:18:30'),
(309,1,'logout','logout',-1,'2025-09-28 22:18:41'),
(310,1,'login_success','login',-1,'2025-10-01 18:37:49'),
(311,1,'ساخت فرم','فرم',1,'2025-10-01 21:17:22'),
(312,1,'حذف فرم','فرم',1,'2025-10-01 21:27:24'),
(313,1,'ساخت فرم','فرم',2,'2025-10-01 21:28:16'),
(314,1,'ساخت فرم','فرم',3,'2025-10-01 21:28:16'),
(315,1,'ساخت فرم','فرم',4,'2025-10-01 21:28:51'),
(316,1,'ساخت فرم','فرم',5,'2025-10-01 21:30:15'),
(317,1,'حذف فرم','فرم',3,'2025-10-01 21:30:52'),
(318,1,'حذف فرم','فرم',4,'2025-10-01 21:30:54'),
(319,1,'حذف فرم','فرم',2,'2025-10-01 21:30:56'),
(320,1,'حذف فرم','فرم',5,'2025-10-01 21:30:57'),
(321,1,'ساخت فرم','فرم',6,'2025-10-01 21:31:10'),
(322,1,'حذف فرم','فرم',6,'2025-10-01 21:35:21'),
(323,1,'ساخت فرم','فرم',7,'2025-10-01 21:36:59'),
(324,1,'ساخت فرم','فرم',8,'2025-10-01 21:37:17'),
(325,1,'حذف فرم','فرم',7,'2025-10-01 21:37:30'),
(326,1,'ساخت فرم','فرم',9,'2025-10-01 21:37:45'),
(327,1,'حذف فرم','فرم',9,'2025-10-01 21:44:12'),
(328,1,'حذف فرم','فرم',8,'2025-10-01 21:44:14'),
(329,1,'ساخت فرم','فرم',10,'2025-10-01 21:44:54'),
(330,1,'حذف فرم','فرم',10,'2025-10-01 22:01:53'),
(331,1,'ساخت فرم','فرم',11,'2025-10-01 22:03:55'),
(332,1,'ساخت فرم','فرم',12,'2025-10-01 22:21:59'),
(333,1,'حذف فرم','فرم',11,'2025-10-01 22:22:24'),
(334,1,'ساخت فرم','فرم',13,'2025-10-01 22:22:28'),
(335,1,'حذف فرم','فرم',12,'2025-10-01 22:22:35'),
(336,1,'ساخت فرم','فرم',14,'2025-10-01 22:25:16'),
(337,1,'حذف فرم','فرم',13,'2025-10-01 22:25:19'),
(338,1,'ساخت فرم','فرم',15,'2025-10-01 22:27:51'),
(339,1,'حذف فرم','فرم',14,'2025-10-01 22:27:54'),
(340,1,'ساخت فرم','فرم',16,'2025-10-01 22:29:25'),
(341,1,'حذف فرم','فرم',15,'2025-10-01 22:29:27'),
(342,1,'ساخت فرم','فرم',17,'2025-10-01 22:29:47'),
(343,1,'حذف فرم','فرم',16,'2025-10-01 22:29:49'),
(344,1,'ساخت فرم','فرم',18,'2025-10-01 22:36:20'),
(345,1,'حذف فرم','فرم',17,'2025-10-01 22:36:22'),
(346,1,'ساخت فرم','فرم',19,'2025-10-01 22:36:44'),
(347,1,'حذف فرم','فرم',18,'2025-10-01 22:36:48'),
(348,1,'ویرایش فرم','فرم',19,'2025-10-01 22:39:20'),
(349,1,'ویرایش فرم','فرم',19,'2025-10-01 22:39:53'),
(350,1,'login_success','login',-1,'2025-10-02 08:56:28'),
(351,1,'ویرایش فرم','فرم',19,'2025-10-02 09:21:25'),
(352,1,'ویرایش فرم','فرم',19,'2025-10-02 09:45:04'),
(353,1,'ویرایش فرم','فرم',19,'2025-10-02 09:51:10'),
(354,1,'ویرایش فرم','فرم',19,'2025-10-02 09:51:11'),
(355,1,'ویرایش فرم','فرم',19,'2025-10-02 09:51:17'),
(356,1,'ویرایش فرم','فرم',19,'2025-10-02 09:51:48'),
(357,1,'ویرایش فرم','فرم',19,'2025-10-02 09:55:42'),
(358,1,'حذف فرم','فرم',19,'2025-10-02 10:08:46'),
(359,1,'ساخت فرم','فرم',20,'2025-10-02 10:12:56'),
(360,1,'ویرایش فرم','فرم',20,'2025-10-02 10:13:13'),
(361,1,'ویرایش فرم','فرم',20,'2025-10-02 10:17:16'),
(362,1,'ویرایش فرم','فرم',20,'2025-10-02 10:17:30'),
(363,-1,'login_failed','login',-1,'2025-10-02 10:23:17'),
(364,11,'login_success','login',-1,'2025-10-02 10:43:32'),
(365,1,'login_success','login',-1,'2025-10-02 21:57:59'),
(366,1,'login_success','login',-1,'2025-10-03 13:58:43'),
(367,1,'logout','logout',-1,'2025-10-03 14:36:24'),
(368,1,'login_success','login',-1,'2025-10-03 14:36:35'),
(369,1,'logout','logout',-1,'2025-10-03 14:36:57'),
(370,11,'login_success','login',-1,'2025-10-03 14:37:12'),
(371,11,'login_success','login',-1,'2025-10-03 14:37:45'),
(372,1,'login_success','login',-1,'2025-10-03 14:46:02'),
(373,11,'login_success','login',-1,'2025-10-03 14:58:15'),
(374,1,'login_success','login',-1,'2025-10-03 15:51:21'),
(375,33,'login_success','login',-1,'2025-10-03 16:00:54'),
(376,1,'login_success','login',-1,'2025-10-03 23:14:54'),
(377,1,'logout','logout',-1,'2025-10-03 23:15:51'),
(378,1,'login_success','login',-1,'2025-10-03 23:17:27'),
(379,1,'login_success','login',-1,'2025-10-04 10:47:08'),
(380,1,'حذف موفق کاربر','حذف کاربر',36,'2025-10-04 10:48:14'),
(381,1,'ساخت موفق دانش آموز','ساخت دانش آموز',37,'2025-10-04 10:48:36'),
(382,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-10-04 10:49:36'),
(383,1,'ویرایش موفق کلاس','ویرایش کلاس',-1,'2025-10-04 10:49:49'),
(384,1,'حذف موفق عکس','گالری',-1,'2025-10-04 10:50:18'),
(385,1,'آپلود موفق عکس','گالری',-1,'2025-10-04 10:50:31'),
(386,11,'login_failed','login',-1,'2025-10-04 10:54:41'),
(387,11,'login_success','login',-1,'2025-10-04 10:54:47'),
(388,1,'حذف فرم','فرم',20,'2025-10-04 11:02:13'),
(389,1,'ساخت فرم','فرم',21,'2025-10-04 11:04:40'),
(390,1,'logout','logout',-1,'2025-10-04 11:12:11'),
(391,1,'login_success','login',-1,'2025-10-04 11:12:25'),
(392,1,'logout','logout',-1,'2025-10-04 11:14:00'),
(393,1,'login_success','login',-1,'2025-10-04 11:14:24'),
(394,1,'login_success','login',-1,'2025-10-04 14:21:26'),
(395,1,'logout','logout',-1,'2025-10-04 14:39:42'),
(396,1,'login_success','login',-1,'2025-10-04 14:41:32'),
(397,1,'login_success','login',-1,'2025-10-11 20:44:40'),
(398,1,'login_success','login',-1,'2025-10-11 20:46:02'),
(399,1,'logout','logout',-1,'2025-10-11 20:46:16'),
(400,11,'login_failed','login',-1,'2025-10-11 20:46:27'),
(401,11,'login_success','login',-1,'2025-10-11 20:46:35'),
(402,1,'logout','logout',-1,'2026-05-19 23:12:08'),
(403,1,'logout','logout',-1,'2026-05-26 19:22:51'),
(404,-1,'login_failed','login',-1,'2026-05-26 19:29:28'),
(405,-1,'login_failed','login',-1,'2026-05-26 19:29:33'),
(406,1,'login_success','login',-1,'2026-05-26 19:29:36'),
(407,1,'logout','logout',-1,'2026-05-26 19:29:51'),
(408,-1,'login_failed','login',-1,'2026-05-26 20:12:49'),
(409,1,'login_success','login',-1,'2026-05-26 20:12:53'),
(410,1,'logout','logout',-1,'2026-05-26 20:13:11'),
(411,-1,'login_failed','login',-1,'2026-06-06 12:45:31'),
(412,-1,'login_failed','login',-1,'2026-06-06 12:45:35'),
(413,1,'login_success','login',-1,'2026-06-06 12:45:42'),
(414,1,'logout','logout',-1,'2026-06-06 12:45:51'),
(415,-1,'login_failed','login',-1,'2026-06-06 14:08:33'),
(416,1,'login_success','login',-1,'2026-06-06 14:08:39'),
(417,1,'ویرایش موفق کاربر','ویرایش کاربر',33,'2026-06-06 14:09:41'),
(418,1,'ویرایش موفق کاربر','ویرایش کاربر',33,'2026-06-06 14:09:41'),
(419,1,'ویرایش موفق کاربر','ویرایش کاربر',33,'2026-06-06 14:10:44'),
(420,1,'ویرایش موفق کاربر','ویرایش کاربر',33,'2026-06-06 14:11:34'),
(421,1,'حذف موفق کاربر','حذف کاربر',33,'2026-06-06 14:11:46'),
(422,1,'ساخت موفق ادمین','ساخت ادمین',38,'2026-06-06 14:12:58'),
(423,1,'ویرایش موفق کاربر','ویرایش کاربر',38,'2026-06-06 14:15:30'),
(424,1,'ویرایش موفق کاربر','ویرایش کاربر',38,'2026-06-06 14:17:44'),
(425,1,'ویرایش موفق کاربر','ویرایش کاربر',38,'2026-06-06 14:17:59'),
(426,1,'ویرایش موفق کاربر','ویرایش کاربر',38,'2026-06-06 14:18:14'),
(427,1,'ساخت admin','مدیریت',-1,'2026-06-06 14:21:26'),
(428,1,'حذف موفق کاربر','حذف کاربر',39,'2026-06-06 14:21:30'),
(429,1,'حذف موفق کاربر','حذف کاربر',13,'2026-06-06 14:23:45'),
(430,1,'ساخت admin','مدیریت',-1,'2026-06-06 14:26:31'),
(431,1,'ساخت admin','مدیریت',-1,'2026-06-06 14:31:03'),
(432,1,'ساخت admin','مدیریت',-1,'2026-06-06 14:32:28'),
(433,1,'ساخت admin','مدیریت',-1,'2026-06-06 14:33:57'),
(434,1,'ساخت admin','مدیریت',-1,'2026-06-06 14:35:00'),
(435,-1,'login_failed','login',-1,'2026-06-06 14:59:26'),
(436,1,'login_success','login',-1,'2026-06-06 14:59:31'),
(437,1,'حذف موفق پست','حذف پست',-1,'2026-06-06 15:00:01'),
(438,1,'حذف موفق پست','حذف پست',-1,'2026-06-06 15:00:03'),
(439,1,'حذف موفق پست','حذف پست',-1,'2026-06-06 15:41:03'),
(440,1,'login_success','login',-1,'2026-06-06 20:59:46'),
(441,11,'login_success','login',-1,'2026-06-06 21:05:46'),
(442,1,'ویرایش فرم','فرم',21,'2026-06-06 21:07:26'),
(443,1,'logout','logout',-1,'2026-06-06 21:09:46'),
(444,11,'login_success','login',-1,'2026-06-09 17:45:51'),
(445,1,'login_failed','login',-1,'2026-06-09 17:46:37'),
(446,1,'login_success','login',-1,'2026-06-09 17:46:44'),
(447,1,'login_success','login',-1,'2026-06-09 21:40:30'),
(448,11,'login_success','login',-1,'2026-06-10 08:19:47'),
(449,11,'logout','logout',-1,'2026-06-10 08:26:52'),
(450,1,'login_success','login',-1,'2026-06-10 08:27:00'),
(451,1,'ویرایش ناموفق کلاس','ویرایش کلاس',-1,'2026-06-10 08:30:03'),
(452,1,'login_success','login',-1,'2026-06-13 13:43:49'),
(453,1,'login_success','login',-1,'2026-06-13 18:01:06'),
(454,-1,'login_failed','login',-1,'2026-06-14 06:08:09'),
(455,-1,'login_failed','login',-1,'2026-06-14 06:08:19'),
(456,1,'login_success','login',-1,'2026-06-14 07:45:04'),
(457,1,'login_success','login',-1,'2026-06-14 12:25:17'),
(458,1,'login_success','login',-1,'2026-06-15 08:19:20'),
(459,40,'login_success','login',-1,'2026-06-15 08:20:20'),
(460,1,'حذف موفق کاربر','حذف کاربر',37,'2026-06-15 08:20:50'),
(461,40,'login_success','login',-1,'2026-06-16 08:15:22'),
(462,1,'login_success','login',-1,'2026-06-21 08:52:21'),
(463,1,'logout','logout',-1,'2026-06-21 09:11:04'),
(464,1,'login_success','login',-1,'2026-06-21 11:43:27'),
(465,1,'حذف موفق کاربر','حذف کاربر',46,'2026-06-21 11:44:35'),
(466,-1,'login_failed','login',-1,'2026-07-13 10:41:05'),
(467,1,'login_success','login',-1,'2026-07-21 11:34:09'),
(468,1,'login_success','login',-1,'2026-07-21 15:54:28'),
(469,1,'logout','logout',-1,'2026-07-21 15:55:47'),
(470,1,'login_success','login',-1,'2026-07-21 15:55:53'),
(471,1,'login_success','login',-1,'2026-07-21 16:56:55'),
(472,1,'ساخت موفق کلاس','مدیریت کلاس‌ها',-1,'2026-07-21 17:15:17'),
(473,1,'ویرایش موفق کلاس','مدیریت کلاس‌ها',7,'2026-07-21 17:15:57'),
(474,11,'login_success','login',-1,'2026-07-21 17:38:36'),
(475,1,'logout','logout',-1,'2026-07-21 17:44:37'),
(476,1,'login_success','login',-1,'2026-07-21 17:53:40'),
(477,1,'logout','logout',-1,'2026-07-21 18:03:26'),
(478,1,'login_success','login',-1,'2026-07-21 18:05:06'),
(479,1,'login_success','login',-1,'2026-07-21 18:59:01'),
(480,1,'login_success','login',-1,'2026-07-21 19:09:08'),
(481,1,'login_success','login',-1,'2026-07-22 05:56:19'),
(482,11,'login_success','login',-1,'2026-07-22 06:13:42'),
(483,1,'login_success','login',-1,'2026-07-28 13:07:23'),
(484,1,'login_success','login',-1,'2026-07-28 13:28:41'),
(485,1,'login_success','login',-1,'2026-07-29 08:56:53'),
(486,1,'login_success','login',-1,'2026-07-29 18:38:02'),
(487,1,'ویرایش موفق کاربر ID: 64','مدیریت کاربران',64,'2026-07-29 18:38:27'),
(488,1,'ویرایش موفق کاربر ID: 11','مدیریت کاربران',11,'2026-07-29 18:39:08'),
(489,1,'logout','logout',-1,'2026-07-29 18:52:02'),
(490,1,'login_success','login',-1,'2026-07-29 19:06:06'),
(491,11,'login_success','login',-1,'2026-07-29 19:17:16'),
(492,1,'logout','logout',-1,'2026-07-29 19:22:53'),
(493,1,'login_success','login',-1,'2026-07-29 19:29:13'),
(494,1,'حذف موفق عکس','گالری',-1,'2026-07-29 19:30:36'),
(495,11,'login_success','login',-1,'2026-07-29 19:36:20'),
(496,11,'login_success','login',-1,'2026-07-31 17:24:12'),
(497,11,'logout','logout',-1,'2026-07-31 17:59:53'),
(498,1,'login_success','login',-1,'2026-07-31 18:00:05'),
(499,1,'logout','logout',-1,'2026-07-31 18:23:30'),
(500,1,'login_success','login',-1,'2026-07-31 18:23:40'),
(501,1,'ثبت امتیاز 5 ستاره به سایت','بازخورد',-1,'2026-07-31 18:33:00');
/*!40000 ALTER TABLE `logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `class_course_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_ibfk_2` (`user_id`),
  KEY `messages_ibfk_3` (`class_course_id`),
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`class_course_id`) REFERENCES `classcourses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT INTO `messages` VALUES
(26,1,'سلام','2026-07-29 19:34:54',9);
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notes`
--

DROP TABLE IF EXISTS `notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `title` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `class_course_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `notes_ibfk_3` (`class_course_id`),
  CONSTRAINT `notes_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notes_ibfk_3` FOREIGN KEY (`class_course_id`) REFERENCES `classcourses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notes`
--

LOCK TABLES `notes` WRITE;
/*!40000 ALTER TABLE `notes` DISABLE KEYS */;
INSERT INTO `notes` VALUES
(10,1,'uploads/notes/note_68e0ced6e8ce1.docx','تست','2025-10-04 11:07:58',12);
/*!40000 ALTER TABLE `notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `author_name` varchar(100) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts`
--

LOCK TABLES `posts` WRITE;
/*!40000 ALTER TABLE `posts` DISABLE KEYS */;
INSERT INTO `posts` VALUES
(8,'اطلاعیه عضویت دانش‌آموزان پایه هفتم','با سلام خدمت اولیای محترم پایه هفتم. لطفا فقط دانش آموزان پایه هفتم سال تحصیلی ۱۴۰۵_۱۴۰۴ در کانال مربوطه عضو شوند. با تشکر فراوان - قریشی','قریشی','images/posts/grade7.jpg','2025-10-01 08:30:00'),
(9,'برنامه زمان‌بندی جلسات آموزش خانواده','با سلام و احترام، قابل توجه اولیا محترم: برنامه زمان‌بندی جلسات آموزش خانواده، به منظور اطلاع و برنامه ریزی جهت حضور در جلسات ارسال می‌گردد. حضور شما عزیزان موجب امتنان است.','مدیریت','images/posts/family_edu.jpg','2025-10-05 10:15:00'),
(10,'فراخوان مرحله اول انتخابی تیم هندبال','دانش‌آموزانی که در مرحله اول انتخابی تیم هندبال شرکت نمودند جهت تمرین روز دوشنبه ۱۴۰۴/۷/۲۱ ساعت ۲:۱۵ الی ۳:۴۵ در سالن ورزشی مدرسه حاضر باشند. حضور احدی، جمشیدی، پناهی، احمدی و سایر نفرات لیست الزامی است.','عقبایی','images/posts/handball.jpg','2025-10-10 14:00:00'),
(11,'شروع مسابقات المپیاد ورزشی درون مدرسه‌ای','آغاز مسابقات با اجرای حرکات پرش پایه، طناب‌زنی جفت جلو عقب و طرفین. لطفا حرکات را در محل مناسب (حیاط یا پارکینگ) تمرین کنید.','تربیت بدنی','images/posts/sports_olympiad.jpg','2025-10-11 09:00:00'),
(12,'دعوت به اولین جلسه آموزش خانواده','فردا جلسه آموزش خانواده با موضوع «اضطراب و تاثیر آن بر فرزندان» ساعت ۸:۳۰ صبح در سالن اجتماعات برگزار می‌گردد. حضور اولیای پایه هفتم الزامی است.','امیدی','images/posts/meeting.jpg','2025-10-11 16:30:00'),
(13,'نتایج مرحله اول و دعوت به مرحله دوم تیم هندبال','مرحله دوم انتخابی تیم هندبال روز چهارشنبه ۱۴۰۴/۷/۲۳ برگزار می‌شود. لیست منتخبین: جمشیدی، پناهی، کیای، صادقی نیک، تیره کار و... راس ساعت در سالن حاضر باشند.','عقبایی','images/posts/handball2.jpg','2025-10-13 11:00:00'),
(14,'برنامه مسابقات والیبال المپیاد درون مدرسه‌ای','مسابقات والیبال روز پنج‌شنبه ۷/۲۴ برگزار خواهد شد. برنامه بازی‌ها در تابلو ورزشی طبقه اول نصب شده است. یک ربع قبل از بازی با لباس ورزشی حاضر باشید.','عقبایی','images/posts/volleyball.jpg','2025-10-14 13:20:00'),
(15,'برنامه مسابقات شطرنج مدرسه','مسابقات شطرنج روز پنج‌شنبه ۷/۲۴ قبل از ساعت ۸ صبح آغاز می‌شود. نفرات اول تا چهارم به مسابقات ناحیه اعزام خواهند شد.','عقبایی','images/posts/chess.jpg','2025-10-14 15:45:00'),
(16,'ارسال تمرینات فصل اول شیمی','سلام خدمت دانش‌آموزان عزیز، تمرین‌های فصل اول شیمی را برای جلسه آینده انجام داده و تحویل دهید. فایل تمرینات توسط استاد رضوانی بارگذاری شد.','رضوانی','images/posts/chemistry.jpg','2025-10-15 18:00:00'),
(17,'تمدید مهلت عضویت در انجمن‌های علمی-پژوهشی','بنا به درخواست دانش‌آموزان، لینک عضویت در انجمن‌های علمی تا ساعت ۲۴ امشب فعال می‌باشد. جهت ثبت‌نام به بخش پژوهش سایت مراجعه کنید.','جاویدنسب','images/posts/research.jpg','2025-10-16 09:00:00'),
(18,'تمرین سرود المپیاد ورزشی استان البرز','با توجه به میزبانی باهنر ۳ در المپیاد استانی، متن سرود و سوگندنامه را مطالعه و تمرین نمایید: «مهر خدا در دل من، هرچه توان در تن من...»','معاونت پرورشی','images/posts/anthem.jpg','2025-10-17 12:00:00'),
(19,'اطلاعیه مراسم افتتاحیه المپیاد ورزشی استان','روز شنبه ۱۴۰۴/۰۷/۲۶ مراسم افتتاحیه در سالن ورزشی برگزار می‌گردد. دانش‌آموزان پایه هفتم حتما با گرم‌کن ورزشی و فرم رضایتنامه حضور یابند.','امیدی','images/posts/opening.jpg','2025-10-17 19:00:00'),
(20,'انتشار تقویم اجرایی سالانه دبیرستان','تقویم فعالیت‌های انجمن‌های علمی، جشنواره خوارزمی، کارسوق‌های سمپاد و نمایشگاه فرداد جهت اطلاع دانش‌آموزان و اولیا تقدیم می‌گردد.','جاویدنسب','images/posts/calendar.jpg','2025-10-18 10:00:00'),
(21,'فراخوان اجرای موسیقی و حرکات رزمی در افتتاحیه','از دانش‌آموزان هنرمند و نوازنده و همچنین رزمی‌کاران دعوت می‌شود جهت اجرای برنامه در مراسم روز شنبه، ابزار و لباس خود را به همراه داشته باشند.','دفتر مدرسه','images/posts/talents.jpg','2025-10-18 14:30:00'),
(22,'گزارش تصویری مراسم افتتاحیه المپیاد ورزشی','رژه دانش‌آموزان ورزشکار و اجرای مراسم باشکوه افتتاحیه المپیاد ورزشی درون مدرسه‌ای استان البرز در سالن اختصاصی باهنر ۳ برگزار شد.','روابط عمومی','images/posts/report.jpg','2025-10-19 11:00:00'),
(23,'سخنان مدیرکل آموزش و پرورش البرز در باهنر ۳','فرزاد شعبانی: لازمه سلامت زیستن، تحرک و تغییر سبک زندگی است. ورزش از افسردگی و اضطراب که طاعون قرن ۲۱ است جلوگیری می‌کند.','خبرگزاری','images/posts/news_shabani.jpg','2025-10-19 15:45:00'),
(24,'اطلاعیه توزیع لباس فرم مدرسه','آقای فرزین‌فرد (تولیدی لباس) روز سه‌شنبه ۱۴۰۴/۷/۲۹ جهت تحویل لباس‌های باقی‌مانده و ثبت سفارشات جدید در مدرسه حضور خواهند داشت.','تدارکات','images/posts/uniform.jpg','2025-10-20 08:00:00'),
(25,'برنامه مسابقات بدمینتون المپیاد','مسابقات بدمینتون روز پنج‌شنبه ۸/۱ ساعت ۸ صبح برگزار می‌شود. مسابقات به صورت تک‌حذفی است و داشتن راکت به عهده ورزشکار می‌باشد.','عقبایی','images/posts/badminton.jpg','2025-10-21 13:00:00'),
(26,'دعوت به اولین مجمع عمومی اولیا و مربیان','زمان: سه‌شنبه ۱۴۰۴/۷/۲۹. ساعت ۸:۲۰ پایه هفتم و ۱۰:۱۵ پایه هشتم و نهم. مکان: سالن جلسات دبیرستان شهید باهنر ۳.','امیدی','images/posts/pta_meeting.jpg','2025-10-22 09:30:00'),
(27,'فراخوان انتخابی تیم فوتسال مدرسه','دانش‌آموزان منتخب جهت انتخابی تیم فوتسال روز چهارشنبه ۱۴۰۴/۷/۳۰ ساعت ۲:۱۵ در سالن ورزشی حاضر باشند. لیست ۱۷ نفره اعلام شد.','عقبایی','images/posts/futsal.jpg','2025-10-23 11:00:00'),
(28,'گزارش اولین جلسه مجمع اولیا و مربیان','مدیر دبیرستان ضمن خیرمقدم، گزارش عملکرد سال گذشته را ارائه داد. انتخابات انجمن امسال به صورت مجازی از طریق my.medu.ir برگزار می‌شود.','روابط عمومی','images/posts/report_pta.jpg','2025-10-23 16:00:00'),
(29,'تقدیر مدیر دبیرستان از برگزارکنندگان افتتاحیه المپیاد','محمدرضا امیدی: صمیمانه‌ترین مراتب قدردانی خود را از دانش‌آموزان، والدین و کادر اجرایی بابت برگزاری باشکوه افتتاحیه ابراز می‌نمایم.','امیدی','images/posts/thanks.jpg','2025-10-24 10:15:00'),
(30,'ثبت‌نام مسابقات قرآن، عترت و نماز آغاز شد','علاقمندان به رشته‌های حفظ، قرائت، اذان، مداحی و احکام جهت ثبت‌نام به سامانه my.medu.ir (نورینو) مراجعه نمایند.','معاونت پرورشی','images/posts/quran.jpg','2025-10-25 09:00:00'),
(31,'فیلم آموزشی شرکت در انتخابات انجمن اولیا','ویدئوی آموزشی نحوه رأی‌دهی به داوطلبین عضویت در انجمن اولیا و مربیان در سایت بارگذاری شد. مهلت رأی‌گیری تا ساعت ۲۳ امشب.','فناوری','images/posts/voting_edu.jpg','2025-10-25 14:00:00'),
(32,'دعوت به همایش کوهپیمایی «پدر و پسر»','به مناسبت هفته تربیت بدنی، همایش کوهپیمایی روز جمعه ۱۴۰۴/۸/۲ ساعت ۷ صبح در پارک کوهستانی باغستان برگزار می‌شود.','عقبایی','images/posts/hiking.jpg','2025-10-26 12:00:00'),
(33,'معرفی دیکشنری مریم-وبستر برای دانش‌آموزان','جهت ریشه‌شناسی لغات و تقویت زبان انگلیسی، استفاده از دیکشنری Merriam-Webster توصیه می‌گردد. لینک دانلود در کانال قرار گرفت.','گروه زبان','images/posts/dictionary.jpg','2025-10-27 08:30:00'),
(34,'مسابقات تنیس روی میز مدرسه','مسابقات تنیس روی میز روزهای زوج برگزار خواهد شد. ورزشکاران حرفه‌ای حتماً راکت اختصاصی خود را همراه داشته باشند.','تربیت بدنی','images/posts/pingpong.jpg','2025-10-27 15:00:00'),
(35,'راهنمای نصب نرم‌افزار «ایران دیجیتال»','پیرو بخشنامه آموزش برنامه نویسی و هوش مصنوعی، دانش‌آموزان نسبت به نصب نرم‌افزار ایران دیجیتال از مایکت اقدام نمایند.','جاویدنسب','images/posts/ai_iran.jpg','2025-10-28 10:00:00'),
(36,'تمرین تیم هندبال (مرحله نهایی)','لیست نهایی تیم هندبال جهت تمرین روز دوشنبه ۱۴۰۴/۸/۵ ساعت ۲:۱۵ در سالن مدرسه حاضر باشند. لباس ورزشی الزامی است.','عقبایی','images/posts/handball_final.jpg','2025-10-29 11:30:00'),
(37,'درخشش دانش‌آموزان باهنر ۳ در المپیاد شطرنج کشور','تیم شطرنج البرز با حضور متین درویشی و امیرماهان قاسمی به مقام پنجم کشور دست یافت. با آرزوی موفقیت برای این عزیزان.','امیدی','images/posts/chess_champion.jpg','2025-10-30 14:00:00'),
(38,'رویداد بزرگ “مهرگان ادبی”','علاقه‌مندان به زبان و ادبیات فارسی؛ رویداد “مهرگان ادبی” در چهار محور داستان کوتاه، شعر، نقالی و مشاعره در تاریخ یکشنبه دوم آذر ماه ساعت ۱۰:۳۰ برگزار می‌شود. برای شرکت آثار خود را بارگذاری کنید. حضور اولیا باعث دلگرمی ماست.','آهنگرپور','images/posts/adabiyat.webp','2025-11-10 09:00:00'),
(39,'تمدید ثبت‌نام دومین دوره مسابقات حلی‌کد','به درخواست شما ثبت‌نام مسابقات برنامه‌نویسی حلی‌کد تمدید شد! تا این لحظه بیش از ۶۰۰ تیم ثبت‌نام کرده‌اند. مهلت تا دوشنبه ۳ آذرماه.','انجمن برنامه‌نویسی','images/posts/hellicode.webp','2025-11-12 11:30:00'),
(40,'آغاز بیست‌وششمین دوره کارسوق مهرگان','با انتخاب حلقه جدید، ۲۶امین دوره کارسوق مهرگان رسما شروع شد! چالش‌های هفتگی هر یکشنبه منتشر می‌شوند. برای اطلاع از جزئیات، کانال‌های مهرگان را دنبال کنید.','جاویدنسب','images/posts/mehregancourse.webp','2025-11-15 14:00:00'),
(41,'پیشنهاد مطالعه: فهرست رمان‌های Culture','اگر می‌خواهید بدانید دنیای ۱۰-۲۰ سال آینده در حوزه‌های هوش مصنوعی و اقتصاد چگونه خواهد بود، مطالعه مجموعه رمان‌های Culture که پیشنهاد ایلان ماسک است را از دست ندهید.','کتابخانه','images/posts/culture_books.webp','2025-11-18 10:15:00'),
(42,'یادآوری چالش اول کارسوق ریاضی','درود بچه‌ها؛ چالش اول کارسوق ریاضی مهرگان رو فراموش نکنین. منتظر پاسخ‌های خلاقانه شما هستیم.','قلی‌زاده','images/posts/math_challenge1.webp','2025-11-20 08:45:00'),
(43,'معرفی نفرات برتر رویداد مهرگان ادبی','رویداد بی‌نظیر مهرگان ادبی برگزار شد. برترین‌های داستان‌نویسی: علی قلی‌زاده، رامتین دینی، کوروش موسوی. برترین‌های شعر: امیرعباس ایران‌نژاد، مهربد محمدزاده. مشاعره: پارسا آران. با سپاس از آقایان آهنگرپور و بدری.','دپارتمان پژوهش','images/posts/adabiyat_winners.webp','2025-11-24 13:00:00'),
(44,'کسب رتبه برتر کارسوق کشوری نجوم','با افتخار، دریافت عنوان نفر برتر کارسوق کشوری نجوم را به پژوهشگر نوجوان “محمد داودی” صمیمانه شادباش می‌گوییم. با آرزوی موفقیت بیشتر.','جاویدنسب','images/posts/astronomy_winner.webp','2025-11-26 15:30:00'),
(45,'اردوی اصفهان ویژه علاقه‌مندان ریاضی','علاقه‌مندان به ریاضی حتما در کارسوق مهرگان شرکت کنند تا ان‌شاءالله اردوی علمی اصفهان برگزار شود. جهت ثبت‌نام به علی قلی‌زاده مراجعه کنید.','قلی‌زاده','images/posts/isfahan_trip.webp','2025-11-28 10:00:00'),
(46,'تقدیر از فعالیت‌های انجمن زیست‌شناسی','فعالیت‌های انجمن زیست‌شناسی به مدیریت آقای حسن‌زاده در راستای ترویج مبحث جذاب زیست و ژنتیک قابل تقدیر است. انجمن‌های علمی فرصتی برای شکوفایی استعدادهاست.','دپارتمان پژوهش','images/posts/biology_club.webp','2025-11-30 11:20:00'),
(47,'برنامه ویژه نجومی: کار عملی با تلسکوپ','آموزش و کار عملی با تلسکوپ و آشنایی با اجزای آن. زمان: سه‌شنبه ۱۸ آذر زنگ تفریح سوم در سالن اجتماعات.','انجمن نجوم','images/posts/telescope.webp','2025-12-05 09:00:00'),
(48,'اعلام نمرات آزمون مقاله زیست‌شناسی','رتبه‌های برتر آزمون زیست‌شناسی مشخص شدند. رتبه ۱: ماهان قاسمی و ایلیا محمودی (۶۰/۶۰). رتبه ۲: رادین رضائی و عرفان روستایی. خسته نباشید به همه عزیزان.','حسن‌زاده','images/posts/bio_grades.webp','2025-12-08 12:45:00'),
(49,'گزارش جامع فعالیت‌های واحد پژوهش','از ابتدای سال تحصیلی، انجمن‌های علمی با رویکرد دانش‌آموز‌محور راه‌اندازی شدند. از برگزاری وب‌سایت برنامه‌نویسی تا کارگاه تلسکوپ. جشنواره خوارزمی نیز به اطلاع دانش‌آموزان رسید.','جاویدنسب','images/posts/research_report.webp','2025-12-10 14:00:00'),
(50,'آغاز ثبت‌نام جشنواره نوجوان خوارزمی','با مراجعه به سایت my.medu.ir، طرح (ایده) پژوهشی خود را در جشنواره خوارزمی ثبت نمایید. از اساتید راهنما کمک بگیرید.','دپارتمان پژوهش','images/posts/kharazmi.webp','2025-12-12 08:30:00'),
(51,'برگزاری جشن شب چله و آزمون خوارزمی','آزمون محور ادبیات خوارزمی چهارشنبه برگزار خواهد شد. همچنین جشن \"شب چله\" به صورت پایه‌ای در ۲۹ آذر در سالن اجتماعات برگزار می‌گردد.','انجمن ادبیات','images/posts/yalda.webp','2025-12-15 10:00:00'),
(52,'چالش سوم کارسوق و قرعه‌کشی','تا پایان شنبه آینده برای ارسال پاسخ چالش سوم فرصت دارید. قرعه‌کشی بین افرادی انجام می‌شود که پاسخ صحیح داده و کمترین تکرار را داشته باشند.','مهرگان','images/posts/challenge3.webp','2025-12-18 13:20:00'),
(53,'گزارش برگزاری باشکوه جشن شب چله','امروز در کنار مربیان به پیشواز شب چله رفتیم. از اجرای تئاتر (بردیا حسین‌پور و تیم)، موسیقی (مهبد خراسانی و تیم) و مجری‌گری علی قلی‌زاده بی‌نهایت سپاسگزاریم.','دپارتمان پژوهش','images/posts/yalda_report.webp','2025-12-21 16:40:00'),
(54,'چهارمین کارسوق ملی فراگیر روش پژوهش','سازمان سمپاد با همکاری استان سیستان و بلوچستان برگزار می‌کند. زمان ثبت‌نام: ۱۸ آذر تا ۱۸ دی در سایت rt.sampad.gov.ir.','جاویدنسب','images/posts/research_course.webp','2025-12-25 09:15:00'),
(55,'ثبت‌نام کارسوق کشوری مهندسی ژنتیک','این کارسوق به صورت مجازی-حضوری برای دانش‌آموزان متوسطه اول سمپاد برگزار می‌شود. ثبت‌نام در قالب گروه‌های ۲ تا ۴ نفره از طریق دپارتمان پژوهش انجام می‌گردد.','جاویدنسب','images/posts/genetics.webp','2025-12-28 11:00:00'),
(56,'معرفی برنده چالش سوم کارسوق ریاضی','برنده چالش سوم کارسوق ریاضی مهرگان: دانیال عبدالمحمدی. تبریک فراوان به دانیال عزیز.','دپارتمان پژوهش','images/posts/math_winner.webp','2026-01-02 10:30:00'),
(57,'رخدادهای نجومی دی‌ماه زیر سقف آسمان','مهم‌ترین وقایع دی‌ماه: قِران ماه و خوشه پروین، آخرین ابرماه سال، همنشینی با مشتری و مقابله سیاره هرمز. با انجمن نجوم همراه باشید.','انجمن نجوم','images/posts/astronomy_jan.webp','2026-01-05 14:00:00'),
(58,'تقویم اجرایی مراحل جشنواره نوجوان خوارزمی','مرحله مدرسه‌ای تا ۳۰ بهمن ۱۴۰۴، مرحله منطقه‌ای تا ۳۰ فروردین ۱۴۰۵، و مرحله کشوری تابستان ۱۴۰۵ برگزار خواهد شد.','دبیرخانه خوارزمی','images/posts/kharazmi_calendar.webp','2026-01-08 09:00:00'),
(59,'ثبت‌نام کارسوق فراگیر بازی‌سازی بباز','کارسوق هیجان‌انگیز بازی‌سازی بباز. مهلت ثبت‌نام تا سه‌شنبه ۳۰ دی ۱۴۰۴. جهت ثبت‌نام به سایت bebaz.sampad.gov.ir مراجعه کنید.','فناوری','images/posts/game_dev.webp','2026-01-12 12:30:00'),
(60,'شیوه‌نامه اجرایی بازارچه کسب و کار دانش‌آموزی','لینک‌های دانلود شیوه‌نامه اجرایی، بوم کسب‌وکار، مهارت‌های رزین‌کاری، میناکاری، تاپستری و ثبت برند جهت جشنواره بازارچه در کانال قرار گرفت.','دپارتمان پژوهش','images/posts/bazaar.webp','2026-01-15 15:45:00'),
(61,'تمدید مهلت ثبت‌نام جشنواره خوارزمی','جهت فراهم آوردن فرصت بیشتر برای دانش‌آموزان خلاق، مهلت ثبت‌نام دوازدهمین دوره جشنواره نوجوان خوارزمی تا ۱۵ بهمن تمدید شد.','دبیرخانه خوارزمی','images/posts/kharazmi_ext.webp','2026-01-18 10:00:00'),
(62,'تغییر زمان آزمون کارسوق مهندسی ژنتیک','آزمون علمی در روزهای ۱۹ و ۲۰ بهمن ۱۴۰۴ برگزار می‌گردد. آزمون آزمایشی در تاریخ ۱۵ بهمن جهت آشنایی با محیط برگزار خواهد شد.','جاویدنسب','images/posts/genetics.webp','2026-01-22 08:30:00'),
(63,'مهلت ثبت‌نام کارسوق ملی علوم اعصاب شناختی','نهمین دوره کارسوق ملی علوم اعصاب شناختی تمدید شد. آخرین مهلت: ۱۷ بهمن. لینک ثبت‌نام: cog.sampad.gov.ir.','دپارتمان پژوهش','images/posts/research.webp','2026-01-25 11:20:00'),
(64,'اختلال در سامانه آزمون مهندسی ژنتیک','به اطلاع می‌رساند به دلیل اختلال زیرساخت‌ها، آزمون ۱۹ و ۲۰ بهمن برگزار نخواهد شد. زمان جدید متعاقباً اطلاع‌رسانی می‌گردد.','دبیرخانه ژنتیک','images/posts/genetics.webp','2026-02-08 14:00:00');
/*!40000 ALTER TABLE `posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `report_cards`
--

DROP TABLE IF EXISTS `report_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_cards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `class_course_id` int(11) NOT NULL,
  `academic_year` int(11) NOT NULL,
  `first_term_continuous` float DEFAULT NULL,
  `first_term_exam` float DEFAULT NULL,
  `second_term_continuous` float DEFAULT NULL,
  `second_term_exam` float DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`,`class_course_id`,`academic_year`),
  KEY `class_course_id` (`class_course_id`),
  CONSTRAINT `report_cards_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  CONSTRAINT `report_cards_ibfk_2` FOREIGN KEY (`class_course_id`) REFERENCES `classcourses` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_cards`
--

LOCK TABLES `report_cards` WRITE;
/*!40000 ALTER TABLE `report_cards` DISABLE KEYS */;
INSERT INTO `report_cards` VALUES
(3,11,12,1404,9,20,15,17,'2025-10-04 07:29:47','2025-10-04 07:30:05'),
(4,11,12,1405,20,20,18,20,'2026-06-06 17:35:05','2026-06-06 17:35:05');
/*!40000 ALTER TABLE `report_cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `session_files`
--

DROP TABLE IF EXISTS `session_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `session_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` enum('pdf','ppt') NOT NULL,
  `uploaded_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  CONSTRAINT `session_files_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `class_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `session_files`
--

LOCK TABLES `session_files` WRITE;
/*!40000 ALTER TABLE `session_files` DISABLE KEYS */;
/*!40000 ALTER TABLE `session_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `session_logs`
--

DROP TABLE IF EXISTS `session_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `session_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `session_logs`
--

LOCK TABLES `session_logs` WRITE;
/*!40000 ALTER TABLE `session_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `session_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_course_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES
(1,1,13,'جلسه جدید','session_68c59315c9d52',1);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `key_name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `teachers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `specialty` varchar(100) DEFAULT NULL,
  `specialty_en` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `bio_en` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teachers`
--

LOCK TABLES `teachers` WRITE;
/*!40000 ALTER TABLE `teachers` DISABLE KEYS */;
INSERT INTO `teachers` VALUES
(4,38,'مدیر دبیرستان','High School Principal',NULL,NULL,'Uploads/admins/admin_6a23fc2adc7d1.webp'),
(6,40,'مشاور دبیرستان','High School Counselor',NULL,NULL,'Uploads/admins/admin_6a23fcdfa6079.webp'),
(7,41,'معاون آموزشی','Educational Deputy',NULL,NULL,'Uploads/admins/admin_6a23fdefc8816.webp'),
(8,42,'معاون پایه هشتم','8th Grade Deputy',NULL,NULL,'Uploads/admins/admin_6a23fe4400aee.webp'),
(9,43,'معاون پایه هفتم','7th Grade Deputy',NULL,NULL,'Uploads/admins/admin_6a23fe9d8c870.webp'),
(10,44,'معاون اجرایی','Executive Deputy',NULL,NULL,'Uploads/admins/admin_6a23fedc2844b.webp'),
(11,45,'معاون پرورشی','Educational & Cultural Deputy','معاون پرورشی مدرسه','Cultural Deputy of the School',NULL),
(13,47,'دبیر عربی و معارف','Arabic & Theology Teacher','فوق لیسانس روابط بین‌الملل','MSc in International Relations',NULL),
(14,48,'دبیر معارف','Theology Teacher','فوق لیسانس الهیات','MSc in Theology',NULL),
(15,49,'دبیر قرآن','Quran Teacher','لیسانس الهیات','BSc in Theology',NULL),
(16,50,'دبیر مطالعات اجتماعی','Social Studies Teacher','فوق لیسانس تاریخ','MSc in History',NULL),
(17,51,'دبیر','Teacher','لیسانس','BSc',NULL),
(18,52,'دبیر مطالعات اجتماعی','Social Studies Teacher','فوق لیسانس مردم‌شناسی','MSc in Anthropology',NULL),
(19,53,'دبیر ادبیات','Persian Literature Teacher','فوق لیسانس ادبیات فارسی','MSc in Persian Literature',NULL),
(20,54,'دبیر ادبیات','Persian Literature Teacher','دکترای تخصصی ادبیات فارسی','PhD in Persian Literature',NULL),
(21,55,'دبیر ادبیات','Persian Literature Teacher','لیسانس ادبیات فارسی','BSc in Persian Literature',NULL),
(22,56,'دبیر زبان انگلیسی','English Teacher','لیسانس زبان انگلیسی','BSc in English Language',NULL),
(23,57,'دبیر ریاضی','Mathematics Teacher','فوق لیسانس ریاضی','MSc in Mathematics',NULL),
(24,58,'دبیر ریاضی','Mathematics Teacher','فوق لیسانس ریاضی','MSc in Mathematics',NULL),
(25,59,'دبیر','Teacher','فوق لیسانس','MSc',NULL),
(26,60,'دبیر','Teacher','فوق لیسانس','MSc',NULL),
(27,61,'دبیر فیزیک و آزمایشگاه','Physics & Lab Teacher','فوق لیسانس فیزیک جامدات','MSc in Solid State Physics',NULL),
(28,62,'دبیر زیست شناسی','Biology Teacher','لیسانس زیست شناسی','BSc in Biology',NULL),
(29,63,'دبیر','Teacher','لیسانس','BSc',NULL),
(30,64,'دبیر شیمی','Chemistry Teacher','فوق لیسانس فیزیک','MSc in Physics',NULL),
(31,65,'دبیر تربیت بدنی','Physical Education Teacher','لیسانس تربیت بدنی','BSc in Physical Education',NULL),
(32,66,'دبیر فرهنگ و هنر','Art & Culture Teacher','لیسانس آموزش هنرهای تجسمی','BSc in Visual Arts',NULL),
(33,67,'دبیر تفکر و سبک زندگی','Lifestyle & Thinking Teacher','لیسانس آموزش ابتدایی','BSc in Primary Education',NULL),
(34,68,'هنرآموز کار و فناوری','Technology Instructor','فوق لیسانس الکترونیک','MSc in Electronics',NULL),
(35,69,'دبیر دفاعی','Defense Readiness Teacher','لیسانس مدیریت آموزشی','BSc in Educational Management',NULL),
(36,70,'مربی آزمایشگاه','Laboratory Instructor','لیسانس شیمی محض','BSc in Pure Chemistry',NULL),
(37,71,'معاون','Deputy','لیسانس زبان انگلیسی','BSc in English Language',NULL),
(38,72,'مربی تربیتی','Educational Instructor','مربی پرورشی','Educational Mentor',NULL),
(39,73,'خدمتگزار و سرایدار','Janitor','پرسنل خدماتی','School Staff',NULL);
/*!40000 ALTER TABLE `teachers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_badges`
--

DROP TABLE IF EXISTS `user_badges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_badges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `badge_id` int(11) NOT NULL,
  `awarded_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `badge_id` (`badge_id`),
  CONSTRAINT `user_badges_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `user_badges_ibfk_2` FOREIGN KEY (`badge_id`) REFERENCES `badges` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_badges`
--

LOCK TABLES `user_badges` WRITE;
/*!40000 ALTER TABLE `user_badges` DISABLE KEYS */;
INSERT INTO `user_badges` VALUES
(5,11,4,'2025-10-04 11:01:35');
/*!40000 ALTER TABLE `user_badges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_points`
--

DROP TABLE IF EXISTS `user_points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_points` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `points` int(11) DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_points_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_points`
--

LOCK TABLES `user_points` WRITE;
/*!40000 ALTER TABLE `user_points` DISABLE KEYS */;
INSERT INTO `user_points` VALUES
(3,11,10,'2025-10-04 11:01:51');
/*!40000 ALTER TABLE `user_points` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','teacher','admin') NOT NULL,
  `national_id` varchar(10) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `phone` varchar(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `last_active` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `extension_token` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `class_id` (`class_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(-1,'!کاربر ثبت نام نشده',NULL,'notregistereduser','0','student',NULL,NULL,NULL,'2025-09-11 19:54:16',NULL,NULL),
(1,'دبیرستان باهنر 3',NULL,'bahonar','$2y$10$HSLRHXj16IklWtUtv6CHIelzU0daSlGLWKlQGvpW6/g2Gz2hRzEqO','admin','',NULL,NULL,'2025-09-11 19:13:23','2026-05-19 23:11:00','43926726fc65c6366b746bedeff1803f'),
(11,'محمدامین مدنی محمدی',NULL,'aminmadani','$2y$10$4tne8i4TwASYybaB8PZP2euswECdwXN1QroOqJI/GFQ1DNnzgWnaq','student','0315324457',1,NULL,'2025-09-11 19:23:06','2026-07-29 19:36:41','13b725c599f803e1b7bbcf53d008bbe9'),
(38,'محمدرضا امیدی','Mohammadreza Omidi','mromidi','$2y$10$IAeU8u/3eySSH1kz3eyefOZdwslfKJxPgKhzLFNw0v9Rd3ZnPN1Wu','admin','',NULL,NULL,'2026-06-06 14:12:58','2026-06-21 09:08:47',NULL),
(40,'رضا جاویدنسب','Reza Javidnasab','mrjavidnasab','$2y$10$Q4jiUVhsi1UCIE3oTCHzG.N3.ei/VMnRaR265YeRzRF7V09TF6nPO','admin','',NULL,NULL,'2026-06-06 14:26:31','2026-06-21 09:01:15','78728abc6a76a2604a9bbf9ff8ed0ebc'),
(41,'محمد اجلالی خلف','Mohammad Ejlali Khalaf','M','$2y$10$3g33Dhx1.sf770yr7gM9JODA.21wv6D8xfRq1.9Mwps2axFcIS1p.','admin','',NULL,NULL,'2026-06-06 14:31:03','2026-06-21 09:01:15',NULL),
(42,'عباس ارمندپور','Abbas Armandpour','mrarmandpoor','$2y$10$VE0nDOvVDIsK/4dl0n6aMewcift4ZRqBNlCRBw96wCjJTgbdJCcN.','admin','',NULL,NULL,'2026-06-06 14:32:28','2026-06-21 09:01:15',NULL),
(43,'سیدحسین قریشی','Seyed Hossein Ghoreishi','mrghoreishi','$2y$10$2KxBV/WQsPFY/ejQxFXO.O3QDeniWrFxK1EHCvGJKVav73m65Cg..','admin','',NULL,NULL,'2026-06-06 14:33:57','2026-06-21 09:01:15',NULL),
(44,'مهدی کشاورز رضائی','Mehdi Keshavarz Rezaei','mrkeshavarz','$2y$10$whfn01bQ1Ba5wnLLpzrKmOAj4NubUo/80Ph6vd83llodrO2smQjpm','admin','',NULL,NULL,'2026-06-06 14:35:00','2026-06-21 09:01:15',NULL),
(45,'ابراهیم محمدعلی خانی','Ebrahim Mohammadalikhani','emohammadi','$2y$10$HSLRHXj16IklWtUtv6CHIelzU0daSlGLWKlQGvpW6/g2Gz2hRzEqO','admin',NULL,NULL,NULL,'2026-06-21 09:01:36',NULL,NULL),
(47,'حیدر سمیر کرم','Heydar Samir Karam','hsamir','$2y$10$HSLRHXj16IklWtUtv6CHIelzU0daSlGLWKlQGvpW6/g2Gz2hRzEqO','teacher',NULL,NULL,NULL,'2026-06-21 09:01:36',NULL,NULL),
(48,'مصطفی فلاح اصغرزاده','Mostafa Fallah Asgharzadeh','mfallah','$2y$10$HSLRHXj16IklWtUtv6CHIelzU0daSlGLWKlQGvpW6/g2Gz2hRzEqO','teacher',NULL,NULL,NULL,'2026-06-21 09:01:36',NULL,NULL),
(49,'محمدرضا مشهدی','Mohammadreza Mashhadi','mmashhadi','$2y$10$HSLRHXj16IklWtUtv6CHIelzU0daSlGLWKlQGvpW6/g2Gz2hRzEqO','teacher',NULL,NULL,NULL,'2026-06-21 09:01:36',NULL,NULL),
(50,'حبیب رمضانخانی','Habib Ramezankhani','hramezankhani','$2y$10$HSLRHXj16IklWtUtv6CHIelzU0daSlGLWKlQGvpW6/g2Gz2hRzEqO','teacher',NULL,NULL,NULL,'2026-06-21 09:01:36',NULL,NULL),
(51,'سیدمصطفی سیدآقائی','Seyed Mostafa Seyedaghaei','sseyedaghaei','$2y$10$HSLRHXj16IklWtUtv6CHIelzU0daSlGLWKlQGvpW6/g2Gz2hRzEqO','teacher',NULL,NULL,NULL,'2026-06-21 09:01:36',NULL,NULL),
(52,'فتح الله ابوالفتحی','Fathollah Abolfathi','fabolfathi','$2y$10$HSLRHXj16IklWtUtv6CHIelzU0daSlGLWKlQGvpW6/g2Gz2hRzEqO','teacher',NULL,NULL,NULL,'2026-06-21 09:01:36',NULL,NULL),
(53,'ابوالفضل رامیان','Abolfazl Ramian','aramian','$2y$10$HSLRHXj16IklWtUtv6CHIelzU0daSlGLWKlQGvpW6/g2Gz2hRzEqO','teacher',NULL,NULL,NULL,'2026-06-21 09:01:36',NULL,NULL),
(54,'علی سلطانی زاده','Ali Soltanizadeh','asoltani','$2y$10$HSLRHXj16IklWtUtv6CHIelzU0daSlGLWKlQGvpW6/g2Gz2hRzEqO','teacher',NULL,NULL,NULL,'2026-06-21 09:01:36',NULL,NULL),
(55,'احسان اله محمدی','Ehsanollah Mohammadi','ehmohammadi','$2y$10$HSLRHXj16IklWtUtv6CHIelzU0daSlGLWKlQGvpW6/g2Gz2hRzEqO','teacher',NULL,NULL,NULL,'2026-06-21 09:01:36',NULL,NULL),
(56,'حسین ولایی','Hossein Valaei','hvalaei','$2y$10$HSLRHXj16IklWtUtv6CHIelzU0daSlGLWKlQGvpW6/g2Gz2hRzEqO','teacher',NULL,NULL,NULL,'2026-06-21 09:01:36',NULL,NULL),
(57,'سیدمجید موسوی','Seyed Majid Mousavi','smousavi','$2y$10$HSLRHXj16IklWtUtv6CHIelzU0daSlGLWKlQGvpW6/g2Gz2hRzEqO','teacher',NULL,NULL,NULL,'2026-06-21 09:01:36',NULL,NULL),
(58,'رضا البرزی اوانکی','Reza Alborzi','ralborzi','$2y$10$HSLRHXj16IklWtUtv6CHIelzU0daSlGLWKlQGvpW6/g2Gz2hRzEqO','teacher',NULL,NULL,NULL,'2026-06-21 09:01:36',NULL,NULL),
(59,'رضا قربانی','Reza Ghorbani','rghorbani','$2y$10$HSLRHXj16IklWtUtv6CHIelzU0daSlGLWKlQGvpW6/g2Gz2hRzEqO','teacher',NULL,NULL,NULL,'2026-06-21 09:01:36',NULL,NULL),
(60,'فرشاد قلیزاده','Farshad Gholizadeh','fgholizadeh','$2y$10$HSLRHXj16IklWtUtv6CHIelzU0daSlGLWKlQGvpW6/g2Gz2hRzEqO','teacher',NULL,NULL,NULL,'2026-06-21 09:01:36',NULL,NULL),
(61,'امیررضا یزدانی','Amirreza Yazdani','ayazdani','$2y$10$HSLRHXj16IklWtUtv6CHIelzU0daSlGLWKlQGvpW6/g2Gz2hRzEqO','teacher',NULL,NULL,NULL,'2026-06-21 09:01:36',NULL,NULL),
(62,'نادر قلیزاده','Nader Gholizadeh','ngholizadeh','$2y$10$HSLRHXj16IklWtUtv6CHIelzU0daSlGLWKlQGvpW6/g2Gz2hRzEqO','teacher',NULL,NULL,NULL,'2026-06-21 09:01:36',NULL,NULL),
(63,'محمدعلی نصرتی','Mohammadali Nosrati','mnosrati','$2y$10$HSLRHXj16IklWtUtv6CHIelzU0daSlGLWKlQGvpW6/g2Gz2hRzEqO','teacher',NULL,NULL,NULL,'2026-06-21 09:01:36',NULL,NULL),
(64,'میثم رضوانی','Meysam Rezvani','mrezvani','$2y$10$ZWg/sfeyiU58lMUuasM3hOz2f5EKTs5xsje3O1lZ1tS71lYeosL96','teacher',NULL,NULL,NULL,'2026-06-21 09:01:36','2026-07-29 18:38:27',NULL),
(65,'حسن عقبائی','Hassan Aghbaei','haghbaei','$2y$10$HSLRHXj16IklWtUtv6CHIelzU0daSlGLWKlQGvpW6/g2Gz2hRzEqO','teacher',NULL,NULL,NULL,'2026-06-21 09:01:36',NULL,NULL),
(66,'رامین الوندی','Ramin Alvandi','ralvandi','$2y$10$HSLRHXj16IklWtUtv6CHIelzU0daSlGLWKlQGvpW6/g2Gz2hRzEqO','teacher',NULL,NULL,NULL,'2026-06-21 09:01:36',NULL,NULL),
(67,'فریدون باقری','Fereydoun Bagheri','fbagheri','$2y$10$HSLRHXj16IklWtUtv6CHIelzU0daSlGLWKlQGvpW6/g2Gz2hRzEqO','teacher',NULL,NULL,NULL,'2026-06-21 09:01:36',NULL,NULL),
(68,'پیام امیرخانی','Payam Amirkhani','pamirkhani','$2y$10$HSLRHXj16IklWtUtv6CHIelzU0daSlGLWKlQGvpW6/g2Gz2hRzEqO','teacher',NULL,NULL,NULL,'2026-06-21 09:01:36',NULL,NULL),
(69,'مجتبی خدابین','Mojtaba Khodabin','mkhodabin','$2y$10$HSLRHXj16IklWtUtv6CHIelzU0daSlGLWKlQGvpW6/g2Gz2hRzEqO','teacher',NULL,NULL,NULL,'2026-06-21 09:01:36',NULL,NULL),
(70,'حسن مهدوی','Hassan Mahdavi','hmahdavi','$2y$10$HSLRHXj16IklWtUtv6CHIelzU0daSlGLWKlQGvpW6/g2Gz2hRzEqO','teacher',NULL,NULL,NULL,'2026-06-21 09:01:36',NULL,NULL),
(71,'رضا صابری زاده','Reza Saberizadeh','rsaberi','$2y$10$HSLRHXj16IklWtUtv6CHIelzU0daSlGLWKlQGvpW6/g2Gz2hRzEqO','admin',NULL,NULL,NULL,'2026-06-21 09:01:36',NULL,NULL),
(72,'مجید بهرامی','Majid Bahrami','mbahrami','$2y$10$HSLRHXj16IklWtUtv6CHIelzU0daSlGLWKlQGvpW6/g2Gz2hRzEqO','teacher',NULL,NULL,NULL,'2026-06-21 09:01:36',NULL,NULL),
(73,'مرحمت فتحی پور','Marhamat Fathipour','mfathipour','$2y$10$HSLRHXj16IklWtUtv6CHIelzU0daSlGLWKlQGvpW6/g2Gz2hRzEqO','teacher',NULL,NULL,NULL,'2026-06-21 09:01:36',NULL,NULL);
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

-- Dump completed on 2026-07-31 18:43:55
