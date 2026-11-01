-- MySQL dump 10.13  Distrib 8.4.9, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: gagandeep_steels
-- ------------------------------------------------------
-- Server version	8.4.9

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
-- Table structure for table `calendar_assignments`
--

DROP TABLE IF EXISTS `calendar_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `actor_id` bigint unsigned DEFAULT NULL,
  `recipient_id` bigint unsigned NOT NULL,
  `lead_id` bigint unsigned DEFAULT NULL,
  `kind` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `detail` text COLLATE utf8mb4_unicode_ci,
  `link` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `assigned_at` timestamp NOT NULL,
  `due_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `calendar_assignments_actor_id_foreign` (`actor_id`),
  KEY `calendar_assignments_recipient_id_assigned_at_index` (`recipient_id`,`assigned_at`),
  KEY `calendar_assignments_assigned_at_index` (`assigned_at`),
  KEY `calendar_assignments_lead_id_assigned_at_index` (`lead_id`,`assigned_at`),
  CONSTRAINT `calendar_assignments_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `calendar_assignments_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `calendar_assignments_recipient_id_foreign` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calendar_assignments`
--

LOCK TABLES `calendar_assignments` WRITE;
/*!40000 ALTER TABLE `calendar_assignments` DISABLE KEYS */;
INSERT INTO `calendar_assignments` VALUES (6,1,1,43,'Lead','Lead assigned: Yuvraj Saini','Unassigned → Gagandeep Steels Administrator (claimed on first open)','http://127.0.0.1:8000/leads/43','2026-10-19 09:09:45',NULL),(7,1,1,41,'Lead','Lead assigned: Rajveer Grewal','Unassigned → Gagandeep Steels Administrator (claimed on first open)','http://127.0.0.1:8000/leads/41','2026-10-19 11:53:34',NULL),(8,1,1,20,'Lead','Lead assigned: Manpreet Singh','Unassigned → Gagandeep Steels Administrator (claimed on first open)','http://127.0.0.1:8000/leads/20','2026-10-19 11:57:28',NULL),(9,1,1,43,'Follow-up','Follow-up assigned: give rates',NULL,'http://127.0.0.1:8000/lead-work?q=give%20rates','2026-10-20 16:37:32','2026-10-20 16:38:00'),(10,1,1,37,'Lead','Lead assigned: Mohit Singla','Unassigned → Gagandeep Steels Administrator (claimed on first open)','http://127.0.0.1:8000/leads/37','2026-10-23 10:17:28',NULL),(11,1,1,39,'Lead','Lead assigned: Abhishek Bedi','Unassigned → Gagandeep Steels Administrator (claimed on first open)','http://127.0.0.1:8000/leads/39','2026-10-23 10:18:12',NULL),(12,1,4,NULL,'Role','Role assigned: Manager','Your CRM access has been set to Manager.','http://127.0.0.1:8000','2026-10-23 17:09:26',NULL),(13,1,4,43,'Task','Task assigned: Get update','jhgtgtyftyfytftf','http://127.0.0.1:8000/lead-work?q=Get%20update','2026-10-26 08:08:01','2026-10-26 08:06:00'),(14,1,1,42,'Lead','Lead assigned: Tanvi Mahajan','Unassigned → Gagandeep Steels Administrator (claimed on first open)','http://127.0.0.1:8000/leads/42','2026-10-31 07:07:30',NULL);
/*!40000 ALTER TABLE `calendar_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_group_members`
--

DROP TABLE IF EXISTS `chat_group_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_group_members` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `chat_group_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chat_group_members_chat_group_id_user_id_unique` (`chat_group_id`,`user_id`),
  KEY `chat_group_members_user_id_foreign` (`user_id`),
  CONSTRAINT `chat_group_members_chat_group_id_foreign` FOREIGN KEY (`chat_group_id`) REFERENCES `chat_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_group_members_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_group_members`
--

LOCK TABLES `chat_group_members` WRITE;
/*!40000 ALTER TABLE `chat_group_members` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_group_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_group_messages`
--

DROP TABLE IF EXISTS `chat_group_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_group_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `chat_group_id` bigint unsigned NOT NULL,
  `sender_id` bigint unsigned NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `edited_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_group_messages_sender_id_foreign` (`sender_id`),
  KEY `chat_group_messages_chat_group_id_id_index` (`chat_group_id`,`id`),
  CONSTRAINT `chat_group_messages_chat_group_id_foreign` FOREIGN KEY (`chat_group_id`) REFERENCES `chat_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_group_messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_group_messages`
--

LOCK TABLES `chat_group_messages` WRITE;
/*!40000 ALTER TABLE `chat_group_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_group_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_groups`
--

DROP TABLE IF EXISTS `chat_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_groups_created_by_foreign` (`created_by`),
  CONSTRAINT `chat_groups_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_groups`
--

LOCK TABLES `chat_groups` WRITE;
/*!40000 ALTER TABLE `chat_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `content_documents`
--

DROP TABLE IF EXISTS `content_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `content_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tab` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `attachment_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachment_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachment_mime` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment_size` bigint unsigned NOT NULL DEFAULT '0',
  `uploaded_by` bigint unsigned DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `content_documents_uploaded_by_foreign` (`uploaded_by`),
  KEY `content_documents_tab_created_at_index` (`tab`,`created_at`),
  KEY `content_documents_tab_index` (`tab`),
  CONSTRAINT `content_documents_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `content_documents`
--

LOCK TABLES `content_documents` WRITE;
/*!40000 ALTER TABLE `content_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `content_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_activities`
--

DROP TABLE IF EXISTS `crm_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_activities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_by` bigint unsigned NOT NULL,
  `assigned_to` bigint unsigned DEFAULT NULL,
  `lead_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activity_type` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'General',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Scheduled',
  `scheduled_at` timestamp NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `link` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `send_calendar_invite` tinyint(1) NOT NULL DEFAULT '0',
  `send_calendar_to_assignee` tinyint(1) NOT NULL DEFAULT '0',
  `send_calendar_to_client` tinyint(1) NOT NULL DEFAULT '0',
  `completion_comment` text COLLATE utf8mb4_unicode_ci,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_activities_assigned_to_foreign` (`assigned_to`),
  KEY `crm_activities_lead_id_foreign` (`lead_id`),
  KEY `crm_activities_status_scheduled_at_index` (`status`,`scheduled_at`),
  KEY `crm_activities_created_by_assigned_to_index` (`created_by`,`assigned_to`),
  CONSTRAINT `crm_activities_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_activities_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_activities_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_activities`
--

LOCK TABLES `crm_activities` WRITE;
/*!40000 ALTER TABLE `crm_activities` DISABLE KEYS */;
INSERT INTO `crm_activities` VALUES (1,1,4,39,'Logic Integratorz','Call','ReScheduled','2026-10-25 19:22:00',NULL,NULL,1,0,1,NULL,NULL,'2026-10-25 18:22:59','2026-10-25 19:20:58');
/*!40000 ALTER TABLE `crm_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_activity_lead`
--

DROP TABLE IF EXISTS `crm_activity_lead`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_activity_lead` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `crm_activity_id` bigint unsigned NOT NULL,
  `lead_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_activity_lead_crm_activity_id_lead_id_unique` (`crm_activity_id`,`lead_id`),
  KEY `crm_activity_lead_lead_id_foreign` (`lead_id`),
  CONSTRAINT `crm_activity_lead_crm_activity_id_foreign` FOREIGN KEY (`crm_activity_id`) REFERENCES `crm_activities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_activity_lead_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_activity_lead`
--

LOCK TABLES `crm_activity_lead` WRITE;
/*!40000 ALTER TABLE `crm_activity_lead` DISABLE KEYS */;
INSERT INTO `crm_activity_lead` VALUES (1,1,39,'2026-10-25 18:22:59','2026-10-25 18:22:59');
/*!40000 ALTER TABLE `crm_activity_lead` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_audit_events`
--

DROP TABLE IF EXISTS `crm_audit_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_audit_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `route_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `method` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `link` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `occurred_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_audit_events_user_id_occurred_at_index` (`user_id`,`occurred_at`),
  KEY `crm_audit_events_occurred_at_index` (`occurred_at`),
  CONSTRAINT `crm_audit_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=282 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_audit_events`
--

LOCK TABLES `crm_audit_events` WRITE;
/*!40000 ALTER TABLE `crm_audit_events` DISABLE KEYS */;
INSERT INTO `crm_audit_events` VALUES (28,1,'Broadcasts Store','broadcasts.store','POST','http://127.0.0.1:8000/notifications/compose','2026-10-19 03:55:41'),(29,1,'Notifications Destroy','notifications.destroy','DELETE','http://127.0.0.1:8000/notifications','2026-10-19 08:20:08'),(30,1,'Broadcasts Store','broadcasts.store','POST','http://127.0.0.1:8000/notifications/compose','2026-10-19 08:22:02'),(31,1,'Notifications Sound','notifications.sound','POST','http://127.0.0.1:8000/notifications','2026-10-19 08:26:00'),(32,1,'Notifications Sound','notifications.sound','POST','http://127.0.0.1:8000/notifications','2026-10-19 08:26:04'),(33,1,'Notifications Sound','notifications.sound','POST','http://127.0.0.1:8000/notifications','2026-10-19 08:26:08'),(34,1,'Notifications Sound','notifications.sound','POST','http://127.0.0.1:8000/notifications','2026-10-19 08:26:11'),(35,1,'Notifications Read','notifications.read','PATCH','http://127.0.0.1:8000/notifications','2026-10-19 08:27:23'),(36,1,'Broadcasts Store','broadcasts.store','POST','http://127.0.0.1:8000/notifications/compose','2026-10-19 08:28:20'),(37,1,'Notifications Destroy','notifications.destroy','DELETE','http://127.0.0.1:8000/notifications','2026-10-19 08:28:44'),(38,1,'Notifications Sound','notifications.sound','POST','http://127.0.0.1:8000/notifications','2026-10-20 16:25:45'),(39,1,'Notifications Sound','notifications.sound','POST','http://127.0.0.1:8000/notifications','2026-10-20 16:25:47'),(40,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/20','2026-10-20 17:39:21'),(41,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000/leads/20','2026-10-20 17:48:28'),(42,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/20','2026-10-20 17:48:34'),(43,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000/leads/20','2026-10-20 17:51:18'),(44,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/20','2026-10-20 18:01:41'),(45,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000/leads/20','2026-10-20 18:01:55'),(46,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/43','2026-10-21 07:23:53'),(47,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000/leads/43','2026-10-21 07:33:28'),(48,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/43','2026-10-21 07:45:11'),(49,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000/leads/43','2026-10-21 08:29:27'),(50,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/43','2026-10-21 08:29:39'),(51,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000/leads/43','2026-10-21 08:31:47'),(52,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/43','2026-10-21 08:32:02'),(53,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000/leads/43','2026-10-21 09:09:54'),(54,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-21 09:38:10'),(55,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-21 09:38:23'),(56,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-21 09:39:01'),(57,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000/leads/43','2026-10-21 09:40:20'),(58,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-21 09:40:46'),(59,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000/leads/43','2026-10-21 09:41:10'),(60,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-21 09:41:29'),(61,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-21 09:41:49'),(62,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000/leads/43','2026-10-21 09:45:05'),(63,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-21 09:48:30'),(64,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000/leads/43','2026-10-21 09:48:40'),(65,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-21 09:48:50'),(66,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000/leads/43','2026-10-21 09:48:59'),(67,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-21 09:55:07'),(68,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-21 09:55:25'),(69,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000','2026-10-21 09:55:42'),(70,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-21 09:56:14'),(71,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-21 10:00:20'),(72,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-21 10:02:57'),(73,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-21 10:04:29'),(74,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-21 10:06:30'),(75,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-21 10:06:48'),(76,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-21 10:07:06'),(77,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-21 10:07:24'),(78,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-21 10:07:42'),(79,1,'Leads Settings Save','leads.settings.save','POST','http://127.0.0.1:8000/lead-settings','2026-10-23 10:49:55'),(80,1,'Leads Settings Save','leads.settings.save','POST','http://127.0.0.1:8000/lead-settings','2026-10-23 10:49:57'),(81,1,'Leads Settings Save','leads.settings.save','POST','http://127.0.0.1:8000/lead-settings','2026-10-23 10:49:59'),(82,1,'Leads Settings Save','leads.settings.save','POST','http://127.0.0.1:8000/lead-settings','2026-10-23 16:38:42'),(83,1,'Leads Settings Save','leads.settings.save','POST','http://127.0.0.1:8000/lead-settings','2026-10-23 16:38:43'),(84,1,'Leads Settings Save','leads.settings.save','POST','http://127.0.0.1:8000/lead-settings','2026-10-23 16:38:43'),(85,1,'Leads Settings Save','leads.settings.save','POST','http://127.0.0.1:8000/lead-settings','2026-10-23 16:38:46'),(86,1,'Leads Settings Save','leads.settings.save','POST','http://127.0.0.1:8000/lead-settings','2026-10-23 16:38:49'),(87,1,'Leads Settings Save','leads.settings.save','POST','http://127.0.0.1:8000/lead-settings','2026-10-23 16:38:52'),(88,1,'Roles Store','roles.store','POST','http://127.0.0.1:8000/users-permissions','2026-10-23 17:06:54'),(89,1,'Users Store','users.store','POST','http://127.0.0.1:8000/users-permissions','2026-10-23 17:08:59'),(90,1,'Users Store','users.store','POST','http://127.0.0.1:8000/users-permissions','2026-10-23 17:09:26'),(91,1,'Chat Store','chat.store','POST','http://127.0.0.1:8000/chat','2026-10-23 17:09:39'),(92,4,'Chat Store','chat.store','POST','http://127.0.0.1:8000/chat','2026-10-23 17:11:03'),(93,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/storage-overview','2026-10-23 17:43:25'),(94,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/storage-overview','2026-10-23 17:43:35'),(95,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/product-rates/logs','2026-10-23 17:43:37'),(96,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/product-rates/logs','2026-10-23 17:43:42'),(97,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/notifications','2026-10-23 17:43:44'),(98,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/notifications','2026-10-23 17:43:48'),(99,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:44:21'),(100,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:44:38'),(101,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:44:39'),(102,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:44:44'),(103,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/leads/pipeline','2026-10-23 17:44:45'),(104,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/leads/pipeline','2026-10-23 17:44:46'),(105,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/lead-groups','2026-10-23 17:44:47'),(106,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/lead-groups','2026-10-23 17:44:51'),(107,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:44:52'),(108,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:44:57'),(109,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:44:57'),(110,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:45:03'),(111,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:45:29'),(112,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:45:31'),(113,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:45:32'),(114,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:45:37'),(115,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:45:45'),(116,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:45:47'),(117,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:46:26'),(118,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:46:30'),(119,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:46:31'),(120,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:46:36'),(121,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:46:37'),(122,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:46:38'),(123,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:46:39'),(124,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:46:40'),(125,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:46:41'),(126,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:46:44'),(127,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:49:35'),(128,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:49:35'),(129,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:51:00'),(130,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:51:02'),(131,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:51:20'),(132,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:51:25'),(133,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/leads','2026-10-23 17:51:26'),(134,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/leads','2026-10-23 17:51:29'),(135,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/leads/pipeline','2026-10-23 17:51:30'),(136,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/leads/pipeline','2026-10-23 17:51:38'),(137,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000','2026-10-23 17:51:40'),(138,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000','2026-10-23 17:51:42'),(139,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:51:44'),(140,1,'Screen Time Heartbeat','screen-time.heartbeat','POST','http://127.0.0.1:8000/screen-time','2026-10-23 17:51:46'),(141,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/20','2026-10-24 11:43:28'),(142,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000/leads/20','2026-10-24 11:43:46'),(143,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-24 11:43:57'),(144,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-24 11:44:09'),(145,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000','2026-10-24 11:44:40'),(146,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-24 11:45:32'),(147,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000','2026-10-24 11:45:41'),(148,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-24 11:45:51'),(149,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000','2026-10-24 11:48:02'),(150,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-24 11:48:04'),(151,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-24 11:49:28'),(152,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000','2026-10-24 11:49:31'),(153,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-24 12:06:48'),(154,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000','2026-10-24 12:06:51'),(155,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-24 12:07:21'),(156,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000','2026-10-24 12:07:24'),(157,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-24 12:08:31'),(158,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000','2026-10-24 12:08:35'),(159,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-24 12:08:50'),(160,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000','2026-10-24 12:12:46'),(161,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-24 12:12:49'),(162,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000','2026-10-24 12:12:51'),(163,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-24 12:12:54'),(164,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000','2026-10-24 12:12:58'),(165,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-24 12:13:05'),(166,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000','2026-10-24 12:13:12'),(167,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-24 12:13:59'),(168,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000','2026-10-24 12:14:01'),(169,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-24 12:14:53'),(170,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000','2026-10-24 12:14:55'),(171,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-24 12:14:58'),(172,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000','2026-10-24 12:15:00'),(173,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-24 12:15:18'),(174,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000','2026-10-24 12:15:21'),(175,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-24 12:15:27'),(176,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000','2026-10-24 12:15:32'),(177,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-24 12:15:42'),(178,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000','2026-10-24 12:15:50'),(179,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-24 12:15:52'),(180,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000','2026-10-24 12:16:00'),(181,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-24 12:16:06'),(182,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-24 12:18:58'),(183,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-24 12:19:09'),(184,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-24 12:20:52'),(185,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000','2026-10-24 12:20:59'),(186,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-25 17:43:05'),(187,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-25 17:43:53'),(188,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/43','2026-10-25 17:44:52'),(189,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000/leads/43','2026-10-25 17:44:57'),(190,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-25 17:50:48'),(191,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-25 17:51:54'),(192,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000','2026-10-25 17:51:57'),(193,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-25 17:52:00'),(194,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000','2026-10-25 17:52:04'),(195,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-25 17:52:35'),(196,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000','2026-10-25 17:52:37'),(197,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-25 17:53:34'),(198,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000','2026-10-25 17:53:39'),(199,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/20','2026-10-25 17:55:24'),(200,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000/leads/20','2026-10-25 17:59:20'),(201,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/20','2026-10-25 18:00:44'),(202,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000/leads/20','2026-10-25 18:01:15'),(203,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/20','2026-10-25 18:01:20'),(204,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/20','2026-10-25 18:07:39'),(205,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/20','2026-10-25 18:07:54'),(206,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/20','2026-10-25 18:16:42'),(207,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-25 18:17:29'),(208,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/20','2026-10-25 18:20:40'),(209,1,'Activities Store','activities.store','POST','http://127.0.0.1:8000/activities','2026-10-25 18:22:59'),(210,1,'Leads Contact Information Update','leads.contact-information.update','PUT','http://127.0.0.1:8000/leads/39','2026-10-25 18:23:39'),(211,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/20','2026-10-25 18:24:12'),(212,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000/leads/20','2026-10-25 18:27:34'),(213,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/43','2026-10-25 18:30:21'),(214,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-25 18:30:41'),(215,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-25 18:31:00'),(216,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-25 18:33:23'),(217,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/43','2026-10-25 18:33:29'),(218,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/43','2026-10-25 18:38:13'),(219,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/43','2026-10-25 18:44:35'),(220,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/43','2026-10-25 18:44:44'),(221,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/43','2026-10-25 18:47:26'),(222,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/43','2026-10-25 18:47:42'),(223,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/43','2026-10-25 18:47:45'),(224,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/43','2026-10-25 18:53:17'),(225,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000/leads/43','2026-10-25 18:53:23'),(226,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/43','2026-10-25 18:53:25'),(227,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/43','2026-10-25 18:53:28'),(228,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000/leads/43','2026-10-25 18:54:27'),(229,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/43','2026-10-25 19:10:22'),(230,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000/leads/43','2026-10-25 19:10:29'),(231,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/43','2026-10-25 19:18:40'),(232,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000/leads/43','2026-10-25 19:18:46'),(233,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/20','2026-10-25 19:19:49'),(234,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000/leads/20','2026-10-25 19:19:56'),(235,1,'Activities Status','activities.status','POST','http://127.0.0.1:8000/activities','2026-10-25 19:20:53'),(236,1,'Activities Status','activities.status','POST','http://127.0.0.1:8000/activities','2026-10-25 19:20:58'),(237,1,'Leads Contact Information Update','leads.contact-information.update','PUT','http://127.0.0.1:8000/leads/43','2026-10-25 19:39:33'),(238,1,'Chat Messages Update','chat.messages.update','PUT','http://127.0.0.1:8000/chat','2026-10-25 19:54:04'),(239,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/43','2026-10-26 07:18:52'),(240,1,'Leads Call Outcome','leads.call.outcome','POST','http://127.0.0.1:8000/leads/43','2026-10-26 07:19:00'),(241,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000/leads/43','2026-10-26 07:57:37'),(242,1,'Users Update','users.update','PUT','http://127.0.0.1:8000/users/4/edit','2026-10-26 08:12:18'),(243,1,'Users Update','users.update','PUT','http://127.0.0.1:8000/users/4/edit','2026-10-26 08:13:25'),(244,1,'Tasks Quick Action','tasks.quick-action','POST','http://127.0.0.1:8000/tasks','2026-10-26 10:49:14'),(245,1,'Tasks Quick Action','tasks.quick-action','POST','http://127.0.0.1:8000/tasks','2026-10-26 10:49:23'),(246,1,'Tasks Quick Action','tasks.quick-action','POST','http://127.0.0.1:8000/tasks','2026-10-26 10:51:53'),(247,1,'Tasks Quick Action','tasks.quick-action','POST','http://127.0.0.1:8000/tasks','2026-10-26 10:53:47'),(248,1,'Tasks Quick Action','tasks.quick-action','POST','http://127.0.0.1:8000/tasks','2026-10-26 11:00:14'),(249,1,'Tasks Quick Action','tasks.quick-action','POST','http://127.0.0.1:8000/tasks','2026-10-28 03:20:22'),(250,1,'Task Statuses Store','task-statuses.store','POST','http://127.0.0.1:8000/tasks','2026-10-28 03:20:53'),(251,1,'Tasks Quick Action','tasks.quick-action','POST','http://127.0.0.1:8000/tasks','2026-10-28 03:21:01'),(252,1,'Tasks Quick Action','tasks.quick-action','POST','http://127.0.0.1:8000/tasks','2026-10-28 03:27:56'),(253,1,'Tasks Quick Action','tasks.quick-action','POST','http://127.0.0.1:8000/tasks','2026-10-28 03:28:01'),(254,1,'Tasks Quick Action','tasks.quick-action','POST','http://127.0.0.1:8000/tasks','2026-10-28 06:33:14'),(255,1,'Tasks Quick Action','tasks.quick-action','POST','http://127.0.0.1:8000/tasks','2026-10-28 06:33:19'),(256,1,'Tasks Quick Action','tasks.quick-action','POST','http://127.0.0.1:8000/tasks','2026-10-28 06:38:45'),(257,1,'Tasks Quick Action','tasks.quick-action','POST','http://127.0.0.1:8000/tasks','2026-10-28 06:38:51'),(258,1,'Tasks Quick Action','tasks.quick-action','POST','http://127.0.0.1:8000/tasks','2026-10-28 06:38:56'),(259,1,'Tasks Quick Action','tasks.quick-action','POST','http://127.0.0.1:8000/tasks','2026-10-28 06:39:00'),(260,1,'Tasks Quick Action','tasks.quick-action','POST','http://127.0.0.1:8000/tasks','2026-10-28 06:49:16'),(261,1,'Tasks Quick Action','tasks.quick-action','POST','http://127.0.0.1:8000/tasks','2026-10-30 10:09:42'),(262,1,'Tasks Quick Action','tasks.quick-action','POST','http://127.0.0.1:8000/tasks','2026-10-30 10:09:49'),(263,1,'Tasks Quick Action','tasks.quick-action','POST','http://127.0.0.1:8000/tasks','2026-10-30 10:09:55'),(264,1,'Tasks Quick Action','tasks.quick-action','POST','http://127.0.0.1:8000/tasks','2026-10-30 10:10:09'),(265,1,'Tasks Attachment','tasks.attachment','POST','http://127.0.0.1:8000/tasks','2026-10-30 10:10:32'),(266,1,'Sidebar Groups Store','sidebar-groups.store','POST','http://127.0.0.1:8000/sidebar-groups','2026-10-31 06:38:42'),(267,1,'Sidebar Groups Destroy','sidebar-groups.destroy','DELETE','http://127.0.0.1:8000/sidebar-groups','2026-10-31 06:38:55'),(268,1,'Sidebar Groups Store','sidebar-groups.store','POST','http://127.0.0.1:8000/sidebar-groups','2026-10-31 06:39:14'),(269,1,'Sidebar Groups Update','sidebar-groups.update','PUT','http://127.0.0.1:8000/sidebar-groups','2026-10-31 06:42:39'),(270,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-31 08:10:58'),(271,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-31 08:13:39'),(272,1,'Leads Call Start','leads.call.start','POST','http://127.0.0.1:8000','2026-10-31 16:54:17'),(273,1,'Users Update','users.update','PUT','http://127.0.0.1:8000/users/1/edit','2026-10-31 17:27:58'),(274,1,'Sidebar Items Move','sidebar-items.move','POST','http://127.0.0.1:8000/sidebar-groups','2026-11-01 13:20:26'),(275,1,'Sidebar Items Move','sidebar-items.move','POST','http://127.0.0.1:8000/sidebar-groups','2026-11-01 13:21:19'),(276,1,'Sidebar Items Move','sidebar-items.move','POST','http://127.0.0.1:8000/sidebar-groups','2026-11-01 13:21:23'),(277,1,'Sidebar Items Reorder','sidebar-items.reorder','POST','http://127.0.0.1:8000/sidebar-groups','2026-11-01 13:24:08'),(278,1,'Sidebar Items Reorder','sidebar-items.reorder','POST','http://127.0.0.1:8000/sidebar-groups','2026-11-01 13:24:11'),(279,1,'Sidebar Items Reorder','sidebar-items.reorder','POST','http://127.0.0.1:8000/sidebar-groups','2026-11-01 13:25:06'),(280,1,'Sidebar Items Reorder','sidebar-items.reorder','POST','http://127.0.0.1:8000/sidebar-groups','2026-11-01 13:25:11'),(281,1,'Leads Settings Save','leads.settings.save','POST','http://127.0.0.1:8000/lead-settings','2026-11-01 16:04:35');
/*!40000 ALTER TABLE `crm_audit_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_notifications`
--

DROP TABLE IF EXISTS `crm_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `title` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `permission` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `broadcast_id` bigint unsigned DEFAULT NULL,
  `dismissed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_notifications_user_id_read_at_id_index` (`user_id`,`read_at`,`id`),
  KEY `crm_notifications_broadcast_id_foreign` (`broadcast_id`),
  CONSTRAINT `crm_notifications_broadcast_id_foreign` FOREIGN KEY (`broadcast_id`) REFERENCES `notification_broadcasts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=174 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_notifications`
--

LOCK TABLES `crm_notifications` WRITE;
/*!40000 ALTER TABLE `crm_notifications` DISABLE KEYS */;
INSERT INTO `crm_notifications` VALUES (63,1,'Hello Team','From Gagandeep Steels Administrator: Good Morning to all',NULL,'2026-10-19 08:20:08','2026-10-19 03:55:41','2026-10-19 08:20:08',7,'2026-10-19 08:20:08'),(64,1,'Hi, Rohit','From Gagandeep Steels Administrator: connect to shiva steels',NULL,'2026-10-19 08:27:23','2026-10-19 08:22:02','2026-10-19 08:27:23',8,NULL),(65,1,'t','From Gagandeep Steels Administrator: call to sam',NULL,'2026-10-19 08:28:44','2026-10-19 08:28:20','2026-10-19 08:28:44',9,'2026-10-19 08:28:44'),(66,1,'Follow-up assigned: give rates','Gagandeep Steels Administrator assigned this action to you. Open Follow-ups, Tasks & Reminders.','leads.view',NULL,'2026-10-20 16:37:32','2026-10-20 16:37:32',NULL,NULL),(67,1,'Follow-up due: give rates','Yuvraj Saini · Due 20 Oct 2026 22:08. Open Follow-ups, Tasks & Reminders to complete or reschedule.','leads.view',NULL,'2026-10-20 16:38:00','2026-10-20 16:38:00',NULL,NULL),(68,1,'Role created','Gagandeep Steels Administrator: Role created. Assign it to a user to apply its permissions.','roles.view',NULL,'2026-10-23 17:06:54','2026-10-23 17:06:54',NULL,NULL),(69,1,'User created','Gagandeep Steels Administrator: User created. Share the initial password securely with the user.','users.view',NULL,'2026-10-23 17:09:26','2026-10-23 17:09:26',NULL,NULL),(70,4,'Welcome to Gagandeep Steels','Your CRM account is ready. You can change your password in My account. Contact your Administrator if you need different access.',NULL,NULL,'2026-10-23 17:09:26','2026-10-23 17:09:26',NULL,NULL),(71,4,'Task assigned: Get update','Gagandeep Steels Administrator assigned this action to you. Open Follow-ups, Tasks & Reminders.','leads.view',NULL,'2026-10-26 08:08:01','2026-10-26 08:08:01',NULL,NULL),(72,4,'Task due: Get update','Yuvraj Saini · Due 26 Oct 2026 13:36. Open Follow-ups, Tasks & Reminders to complete or reschedule.','leads.view',NULL,'2026-10-26 08:09:00','2026-10-26 08:09:00',NULL,NULL),(73,1,'User account updated','Gagandeep Steels Administrator: User updated and password reset successfully. Share the new password securely. Existing sessions have been signed out.','users.view',NULL,'2026-10-26 08:13:25','2026-10-26 08:13:25',NULL,NULL),(74,4,'Your account was updated','An Administrator reset your password. Contact your Administrator for your new password.',NULL,NULL,'2026-10-26 08:13:25','2026-10-26 08:13:25',NULL,NULL),(75,1,'User account updated','Gagandeep Steels Administrator: User updated. Role and permission changes apply on the user’s next action.','users.view',NULL,'2026-10-31 17:27:58','2026-10-31 17:27:58',NULL,NULL),(76,1,'Overdue Follow-up: give rates','Due 20 Oct 2026, 10:08 PM. Lead: Yuvraj Saini. Open Follow-ups, Tasks & Reminders.',NULL,NULL,'2026-10-31 17:34:01','2026-10-31 17:34:01',NULL,NULL),(77,4,'Overdue Activity: Logic Integratorz','Due 26 Oct 2026, 12:52 AM. Open Activities.',NULL,NULL,'2026-10-31 17:34:01','2026-10-31 17:34:01',NULL,NULL),(78,1,'Overdue Activity: Logic Integratorz','Admin alert · Responsible: Logic Integratorz · Due 26 Oct 2026, 12:52 AM. Open Activities.',NULL,NULL,'2026-10-31 17:34:01','2026-10-31 17:34:01',NULL,NULL),(79,4,'Overdue Note: testing','Due 28 Oct 2026, 12:05 PM. Open Note.',NULL,NULL,'2026-10-31 17:34:01','2026-10-31 17:34:01',NULL,NULL),(80,1,'Overdue Note: testing','Admin alert · Responsible: Logic Integratorz · Due 28 Oct 2026, 12:05 PM. Open Note.',NULL,NULL,'2026-10-31 17:34:01','2026-10-31 17:34:01',NULL,NULL),(114,1,'Lead pending in New: Arjun Mehta','Admin alert · Responsible: Unassigned · This lead has stayed in the same stage for 3 days. Open Leads or Pipeline and update its progress.',NULL,NULL,'2026-11-01 16:01:50','2026-11-01 16:01:50',NULL,NULL),(115,1,'Lead pending in New: Simran Kaur','Admin alert · Responsible: Unassigned · This lead has stayed in the same stage for 3 days. Open Leads or Pipeline and update its progress.',NULL,NULL,'2026-11-01 16:01:50','2026-11-01 16:01:50',NULL,NULL),(116,1,'Lead pending in New: Rohit Sharma','Admin alert · Responsible: Unassigned · This lead has stayed in the same stage for 3 days. Open Leads or Pipeline and update its progress.',NULL,NULL,'2026-11-01 16:01:50','2026-11-01 16:01:50',NULL,NULL),(117,1,'Lead pending in New: Neha Bansal','Admin alert · Responsible: Unassigned · This lead has stayed in the same stage for 3 days. Open Leads or Pipeline and update its progress.',NULL,NULL,'2026-11-01 16:01:50','2026-11-01 16:01:50',NULL,NULL),(118,1,'Lead pending in New: Karan Sethi','Admin alert · Responsible: Unassigned · This lead has stayed in the same stage for 3 days. Open Leads or Pipeline and update its progress.',NULL,NULL,'2026-11-01 16:01:50','2026-11-01 16:01:50',NULL,NULL),(119,1,'Lead pending in New: Priya Verma','Admin alert · Responsible: Unassigned · This lead has stayed in the same stage for 3 days. Open Leads or Pipeline and update its progress.',NULL,NULL,'2026-11-01 16:01:50','2026-11-01 16:01:50',NULL,NULL),(120,1,'Lead pending in New: Manpreet Singh','This lead has stayed in the same stage for 3 days. Open Leads or Pipeline and update its progress.',NULL,NULL,'2026-11-01 16:01:50','2026-11-01 16:01:50',NULL,NULL),(121,1,'Lead pending in New: Aditi Gupta','Admin alert · Responsible: Unassigned · This lead has stayed in the same stage for 3 days. Open Leads or Pipeline and update its progress.',NULL,NULL,'2026-11-01 16:01:50','2026-11-01 16:01:50',NULL,NULL),(122,1,'Lead pending in New: Vikram Arora','Admin alert · Responsible: Unassigned · This lead has stayed in the same stage for 3 days. Open Leads or Pipeline and update its progress.',NULL,NULL,'2026-11-01 16:01:50','2026-11-01 16:01:50',NULL,NULL),(123,1,'Lead pending in New: Kavya Malhotra','Admin alert · Responsible: Unassigned · This lead has stayed in the same stage for 3 days. Open Leads or Pipeline and update its progress.',NULL,NULL,'2026-11-01 16:01:50','2026-11-01 16:01:50',NULL,NULL),(124,1,'Lead pending in New: Harshdeep Gill','Admin alert · Responsible: Unassigned · This lead has stayed in the same stage for 3 days. Open Leads or Pipeline and update its progress.',NULL,NULL,'2026-11-01 16:01:50','2026-11-01 16:01:50',NULL,NULL),(125,1,'Lead pending in New: Isha Kapoor','Admin alert · Responsible: Unassigned · This lead has stayed in the same stage for 3 days. Open Leads or Pipeline and update its progress.',NULL,NULL,'2026-11-01 16:01:50','2026-11-01 16:01:50',NULL,NULL),(126,1,'Lead pending in New: Nitin Aggarwal','Admin alert · Responsible: Unassigned · This lead has stayed in the same stage for 3 days. Open Leads or Pipeline and update its progress.',NULL,NULL,'2026-11-01 16:01:50','2026-11-01 16:01:50',NULL,NULL),(127,1,'Lead pending in New: Gurleen Sandhu','Admin alert · Responsible: Unassigned · This lead has stayed in the same stage for 3 days. Open Leads or Pipeline and update its progress.',NULL,NULL,'2026-11-01 16:01:50','2026-11-01 16:01:50',NULL,NULL),(128,1,'Lead pending in New: Deepak Jain','Admin alert · Responsible: Unassigned · This lead has stayed in the same stage for 3 days. Open Leads or Pipeline and update its progress.',NULL,NULL,'2026-11-01 16:01:50','2026-11-01 16:01:50',NULL,NULL),(129,1,'Lead pending in New: Mehak Chawla','Admin alert · Responsible: Unassigned · This lead has stayed in the same stage for 3 days. Open Leads or Pipeline and update its progress.',NULL,NULL,'2026-11-01 16:01:50','2026-11-01 16:01:50',NULL,NULL),(130,1,'Lead pending in New: Sahil Khanna','Admin alert · Responsible: Unassigned · This lead has stayed in the same stage for 3 days. Open Leads or Pipeline and update its progress.',NULL,NULL,'2026-11-01 16:01:50','2026-11-01 16:01:50',NULL,NULL),(131,1,'Lead pending in New: Ritika Sood','Admin alert · Responsible: Unassigned · This lead has stayed in the same stage for 3 days. Open Leads or Pipeline and update its progress.',NULL,NULL,'2026-11-01 16:01:50','2026-11-01 16:01:50',NULL,NULL),(132,1,'Lead pending in New: Jaspreet Dhillon','Admin alert · Responsible: Unassigned · This lead has stayed in the same stage for 3 days. Open Leads or Pipeline and update its progress.',NULL,NULL,'2026-11-01 16:01:50','2026-11-01 16:01:50',NULL,NULL),(133,1,'Lead pending in New: Ananya Kohli','Admin alert · Responsible: Unassigned · This lead has stayed in the same stage for 3 days. Open Leads or Pipeline and update its progress.',NULL,NULL,'2026-11-01 16:01:50','2026-11-01 16:01:50',NULL,NULL),(134,1,'Lead pending in New: Varun Mittal','Admin alert · Responsible: Unassigned · This lead has stayed in the same stage for 3 days. Open Leads or Pipeline and update its progress.',NULL,NULL,'2026-11-01 16:01:50','2026-11-01 16:01:50',NULL,NULL),(135,1,'Lead pending in New: Navjot Brar','Admin alert · Responsible: Unassigned · This lead has stayed in the same stage for 3 days. Open Leads or Pipeline and update its progress.',NULL,NULL,'2026-11-01 16:01:50','2026-11-01 16:01:50',NULL,NULL),(136,1,'Lead pending in New: Riya Oberoi','Admin alert · Responsible: Unassigned · This lead has stayed in the same stage for 3 days. Open Leads or Pipeline and update its progress.',NULL,NULL,'2026-11-01 16:01:50','2026-11-01 16:01:50',NULL,NULL),(137,1,'Lead pending in New: Pooja Narang','Admin alert · Responsible: Unassigned · This lead has stayed in the same stage for 3 days. Open Leads or Pipeline and update its progress.',NULL,NULL,'2026-11-01 16:01:50','2026-11-01 16:01:50',NULL,NULL),(138,1,'Lead pending in New: Abhishek Bedi','This lead has stayed in the same stage for 3 days. Open Leads or Pipeline and update its progress.',NULL,NULL,'2026-11-01 16:01:50','2026-11-01 16:01:50',NULL,NULL),(139,1,'Lead pending in New: Sneha Arora','Admin alert · Responsible: Unassigned · This lead has stayed in the same stage for 3 days. Open Leads or Pipeline and update its progress.',NULL,NULL,'2026-11-01 16:01:50','2026-11-01 16:01:50',NULL,NULL),(140,1,'Lead pending in Site Approval: Rajveer Grewal','This lead has stayed in the same stage for 3 days. Open Leads or Pipeline and update its progress.',NULL,NULL,'2026-11-01 16:01:50','2026-11-01 16:01:50',NULL,NULL);
/*!40000 ALTER TABLE `crm_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `direct_messages`
--

DROP TABLE IF EXISTS `direct_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `direct_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` bigint unsigned NOT NULL,
  `recipient_id` bigint unsigned NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `edited_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `direct_messages_sender_id_recipient_id_id_index` (`sender_id`,`recipient_id`,`id`),
  KEY `direct_messages_recipient_id_read_at_index` (`recipient_id`,`read_at`),
  CONSTRAINT `direct_messages_recipient_id_foreign` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `direct_messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `direct_messages`
--

LOCK TABLES `direct_messages` WRITE;
/*!40000 ALTER TABLE `direct_messages` DISABLE KEYS */;
INSERT INTO `direct_messages` VALUES (1,1,4,'hello213','2026-10-23 17:10:51','2026-10-25 19:54:04','2026-10-23 17:09:39','2026-10-25 19:54:04',NULL),(2,4,1,'How are you','2026-10-23 17:13:10',NULL,'2026-10-23 17:11:03','2026-10-23 17:13:10',NULL);
/*!40000 ALTER TABLE `direct_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `freight_rates`
--

DROP TABLE IF EXISTS `freight_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `freight_rates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `origin` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `destination` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(11,2) NOT NULL,
  `transporter` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `freight_rates_updated_at_id_index` (`updated_at`,`id`),
  KEY `freight_rates_transporter_index` (`transporter`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `freight_rates`
--

LOCK TABLES `freight_rates` WRITE;
/*!40000 ALTER TABLE `freight_rates` DISABLE KEYS */;
/*!40000 ALTER TABLE `freight_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_roles`
--

DROP TABLE IF EXISTS `job_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_roles_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_roles`
--

LOCK TABLES `job_roles` WRITE;
/*!40000 ALTER TABLE `job_roles` DISABLE KEYS */;
INSERT INTO `job_roles` VALUES (8,'Rolling Mill / Manufacturer','2026-10-18 16:18:07','2026-10-18 16:18:07'),(9,'Finish Goods Godown','2026-10-18 16:18:07','2026-10-18 16:18:07'),(10,'MS Melting Units','2026-10-18 16:18:07','2026-10-18 16:18:07'),(11,'Cast Iron Foundries','2026-10-18 16:18:07','2026-10-18 16:18:07'),(12,'Cast Iron Wires','2026-10-18 16:18:07','2026-10-18 16:18:07'),(13,'Cast Iron Sellers','2026-10-18 16:18:07','2026-10-18 16:18:07'),(14,'Scrap Suppliers','2026-10-18 16:18:07','2026-10-18 16:18:07');
/*!40000 ALTER TABLE `job_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_activities`
--

DROP TABLE IF EXISTS `lead_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_activities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint unsigned NOT NULL,
  `actor` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `actor_id` bigint unsigned DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `kind` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'System',
  `email_to` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_status` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_key` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `lead_activities_request_key_unique` (`request_key`),
  KEY `lead_activities_lead_id_foreign` (`lead_id`),
  KEY `lead_activities_kind_index` (`kind`),
  KEY `lead_activities_actor_id_foreign` (`actor_id`),
  CONSTRAINT `lead_activities_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lead_activities_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=169 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_activities`
--

LOCK TABLES `lead_activities` WRITE;
/*!40000 ALTER TABLE `lead_activities` DISABLE KEYS */;
INSERT INTO `lead_activities` VALUES (55,43,'Gagandeep Steels Administrator',1,'Lead assigned','Unassigned → Gagandeep Steels Administrator (claimed on first open)','2026-10-19 09:09:45','2026-10-19 09:09:45','System',NULL,NULL,NULL,NULL,1),(56,43,'Gagandeep Steels Administrator',1,'Incoming call: call receieved','rate inquiry received','2026-10-19 09:14:03','2026-10-19 09:14:03','Call log',NULL,NULL,'6cc52994-929d-44a9-8f02-090c902a474e',NULL,1),(57,41,'Gagandeep Steels Administrator',1,'Lead assigned','Unassigned → Gagandeep Steels Administrator (claimed on first open)','2026-10-19 11:53:34','2026-10-19 11:53:34','System',NULL,NULL,NULL,NULL,1),(58,20,'Gagandeep Steels Administrator',1,'Lead assigned','Unassigned → Gagandeep Steels Administrator (claimed on first open)','2026-10-19 11:57:28','2026-10-19 11:57:28','System',NULL,NULL,NULL,NULL,1),(59,20,'Gagandeep Steels Administrator',1,'Stage changed','New → Paperwork','2026-10-19 11:57:35','2026-10-19 11:57:35','System',NULL,NULL,NULL,NULL,1),(60,43,'Gagandeep Steels Administrator',1,'Follow-up scheduled','give rates · 20 Oct 2026 22:08 · Assigned to Gagandeep Steels Administrator','2026-10-20 16:37:32','2026-10-20 16:37:32','System',NULL,NULL,NULL,NULL,1),(61,20,'Gagandeep Steels Administrator',1,'Lead updated','Phone: — → 9888234444\nJob_role: — → Cast Iron Wires','2026-10-20 17:32:40','2026-10-20 17:32:40','System',NULL,NULL,NULL,NULL,1),(62,20,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','Call was not answered.','2026-10-20 17:48:28','2026-10-20 17:48:28','Call log',NULL,NULL,'4f856bb5-5be5-49ce-a36e-edc4f7611f45',NULL,1),(63,20,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','called 2 times but not answered','2026-10-20 17:51:17','2026-10-20 17:51:17','Call log',NULL,NULL,'8691e0c4-f6b2-4dce-a7af-cd2d84e7b102',NULL,1),(64,20,'Gagandeep Steels Administrator',1,'Outgoing call: Connected','rates sent','2026-10-20 18:01:55','2026-10-20 18:01:55','Call log',NULL,NULL,'dfc49542-bf13-40be-b6e7-c890fc1a4538',NULL,1),(65,43,'Gagandeep Steels Administrator',1,'Lead updated','Phone: — → 9888231944\nJob_role: — → MS Melting Units','2026-10-21 07:23:40','2026-10-21 07:23:40','System',NULL,NULL,NULL,NULL,1),(66,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','ok','2026-10-21 07:33:28','2026-10-21 07:33:28','Call log',NULL,NULL,'279f7661-35ad-4a78-9485-264595c2f9c4',NULL,1),(67,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-21 08:29:27','2026-10-21 08:29:27','Call log',NULL,NULL,'693260ad-1e57-4938-b5eb-a332f6f8b3ac',NULL,1),(68,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-21 08:31:47','2026-10-21 08:31:47','Call log',NULL,NULL,'ea34100b-bb09-43e7-883a-d640ddb3c1b3',NULL,1),(69,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-21 09:09:54','2026-10-21 09:09:54','Call log',NULL,NULL,'e86a7fde-ca6f-4361-adb3-18b8f494439a',NULL,1),(70,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-21 09:40:20','2026-10-21 09:40:20','Call log',NULL,NULL,'5a50e37e-9c6d-4ba0-ac97-bf7588bf5ae7',NULL,1),(71,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-21 09:41:10','2026-10-21 09:41:10','Call log',NULL,NULL,'c23824d9-85c1-4f83-bd6a-9e08da50c34d',NULL,1),(72,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-21 09:45:05','2026-10-21 09:45:05','Call log',NULL,NULL,'9fbcf052-d04b-45c6-9d68-ab56bbabcc42',NULL,1),(73,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-21 09:48:40','2026-10-21 09:48:40','Call log',NULL,NULL,'85077439-a951-4db8-960a-3ecf5e829e01',NULL,1),(74,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-21 09:48:59','2026-10-21 09:48:59','Call log',NULL,NULL,'f655a7e3-e5c8-48ba-b092-5b80f7b34327',NULL,1),(75,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','hello','2026-10-21 09:55:42','2026-10-21 09:55:42','Call log',NULL,NULL,'0ed3a3c8-7ca8-49a4-981e-43750a8c278a',NULL,1),(76,20,'Gagandeep Steels Administrator',1,'Stage changed','Paperwork → New','2026-10-23 08:29:00','2026-10-23 08:29:00','System',NULL,NULL,NULL,NULL,1),(77,20,'Gagandeep Steels Administrator',1,'Missed call: missed title','he called but i missed','2026-10-23 08:46:49','2026-10-23 08:46:49','Call log',NULL,NULL,'8980c3c2-397c-4333-a122-c7af724213a0',NULL,1),(78,14,'Gagandeep Steels Administrator',1,'Untouched lead archived','Archived while remaining visible in the homepage pool.','2026-10-23 09:39:14','2026-10-23 09:48:38','System',NULL,NULL,NULL,NULL,1),(79,15,'Gagandeep Steels Administrator',1,'Untouched lead archived','Archived while remaining visible in the homepage pool.','2026-10-23 09:39:14','2026-10-23 09:48:38','System',NULL,NULL,NULL,NULL,1),(80,16,'Gagandeep Steels Administrator',1,'Untouched lead archived','Archived while remaining visible in the homepage pool.','2026-10-23 09:39:14','2026-10-23 09:48:38','System',NULL,NULL,NULL,NULL,1),(81,17,'Gagandeep Steels Administrator',1,'Untouched lead archived','Archived while remaining visible in the homepage pool.','2026-10-23 09:39:14','2026-10-23 09:48:38','System',NULL,NULL,NULL,NULL,1),(82,18,'Gagandeep Steels Administrator',1,'Untouched lead archived','Archived while remaining visible in the homepage pool.','2026-10-23 09:39:14','2026-10-23 09:48:38','System',NULL,NULL,NULL,NULL,1),(83,19,'Gagandeep Steels Administrator',1,'Untouched lead archived','Archived while remaining visible in the homepage pool.','2026-10-23 09:39:14','2026-10-23 09:48:38','System',NULL,NULL,NULL,NULL,1),(84,21,'Gagandeep Steels Administrator',1,'Untouched lead archived','Archived while remaining visible in the homepage pool.','2026-10-23 09:39:14','2026-10-23 09:48:38','System',NULL,NULL,NULL,NULL,1),(85,22,'Gagandeep Steels Administrator',1,'Untouched lead archived','Archived while remaining visible in the homepage pool.','2026-10-23 09:39:14','2026-10-23 09:48:38','System',NULL,NULL,NULL,NULL,1),(86,23,'Gagandeep Steels Administrator',1,'Untouched lead archived','Archived while remaining visible in the homepage pool.','2026-10-23 09:39:14','2026-10-23 09:48:38','System',NULL,NULL,NULL,NULL,1),(87,24,'Gagandeep Steels Administrator',1,'Untouched lead archived','Archived while remaining visible in the homepage pool.','2026-10-23 09:39:14','2026-10-23 09:48:38','System',NULL,NULL,NULL,NULL,1),(88,25,'Gagandeep Steels Administrator',1,'Untouched lead archived','Archived while remaining visible in the homepage pool.','2026-10-23 09:39:14','2026-10-23 09:48:38','System',NULL,NULL,NULL,NULL,1),(89,26,'Gagandeep Steels Administrator',1,'Untouched lead archived','Archived while remaining visible in the homepage pool.','2026-10-23 09:39:14','2026-10-23 09:48:38','System',NULL,NULL,NULL,NULL,1),(90,27,'Gagandeep Steels Administrator',1,'Untouched lead archived','Archived while remaining visible in the homepage pool.','2026-10-23 09:39:14','2026-10-23 09:48:38','System',NULL,NULL,NULL,NULL,1),(91,28,'Gagandeep Steels Administrator',1,'Untouched lead archived','Archived while remaining visible in the homepage pool.','2026-10-23 09:39:14','2026-10-23 09:48:38','System',NULL,NULL,NULL,NULL,1),(92,29,'Gagandeep Steels Administrator',1,'Untouched lead archived','Archived while remaining visible in the homepage pool.','2026-10-23 09:39:14','2026-10-23 09:48:38','System',NULL,NULL,NULL,NULL,1),(93,30,'Gagandeep Steels Administrator',1,'Untouched lead archived','Archived while remaining visible in the homepage pool.','2026-10-23 09:39:14','2026-10-23 09:48:38','System',NULL,NULL,NULL,NULL,1),(94,31,'Gagandeep Steels Administrator',1,'Untouched lead archived','Archived while remaining visible in the homepage pool.','2026-10-23 09:39:14','2026-10-23 09:48:38','System',NULL,NULL,NULL,NULL,1),(95,32,'Gagandeep Steels Administrator',1,'Untouched lead archived','Archived while remaining visible in the homepage pool.','2026-10-23 09:39:14','2026-10-23 09:48:38','System',NULL,NULL,NULL,NULL,1),(96,33,'Gagandeep Steels Administrator',1,'Untouched lead archived','Archived while remaining visible in the homepage pool.','2026-10-23 09:39:14','2026-10-23 09:48:38','System',NULL,NULL,NULL,NULL,1),(97,34,'Gagandeep Steels Administrator',1,'Untouched lead archived','Archived while remaining visible in the homepage pool.','2026-10-23 09:39:14','2026-10-23 09:48:38','System',NULL,NULL,NULL,NULL,1),(98,35,'Gagandeep Steels Administrator',1,'Untouched lead archived','Archived while remaining visible in the homepage pool.','2026-10-23 09:39:14','2026-10-23 09:48:38','System',NULL,NULL,NULL,NULL,1),(99,36,'Gagandeep Steels Administrator',1,'Untouched lead archived','Archived while remaining visible in the homepage pool.','2026-10-23 09:39:14','2026-10-23 09:48:38','System',NULL,NULL,NULL,NULL,1),(100,37,'Gagandeep Steels Administrator',1,'Untouched lead archived','Archived while remaining visible in the homepage pool.','2026-10-23 09:39:14','2026-10-23 09:48:38','System',NULL,NULL,NULL,NULL,1),(101,38,'Gagandeep Steels Administrator',1,'Untouched lead archived','Archived while remaining visible in the homepage pool.','2026-10-23 09:39:14','2026-10-23 09:48:38','System',NULL,NULL,NULL,NULL,1),(102,39,'Gagandeep Steels Administrator',1,'Untouched lead archived','Archived while remaining visible in the homepage pool.','2026-10-23 09:39:14','2026-10-23 09:48:38','System',NULL,NULL,NULL,NULL,1),(103,40,'Gagandeep Steels Administrator',1,'Untouched lead archived','Archived while remaining visible in the homepage pool.','2026-10-23 09:39:14','2026-10-23 09:48:38','System',NULL,NULL,NULL,NULL,1),(104,42,'Gagandeep Steels Administrator',1,'Untouched lead archived','Archived while remaining visible in the homepage pool.','2026-10-23 09:39:14','2026-10-23 09:48:38','System',NULL,NULL,NULL,NULL,1),(105,37,'Gagandeep Steels Administrator',1,'Lead assigned','Unassigned → Gagandeep Steels Administrator (claimed on first open)','2026-10-23 10:17:28','2026-10-23 10:17:28','System',NULL,NULL,NULL,NULL,1),(106,39,'Gagandeep Steels Administrator',1,'Lead assigned','Unassigned → Gagandeep Steels Administrator (claimed on first open)','2026-10-23 10:18:12','2026-10-23 10:18:12','System',NULL,NULL,NULL,NULL,1),(107,20,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-24 11:43:46','2026-10-24 11:43:46','Call log',NULL,NULL,'d354472e-1eae-46c3-86e7-17ef14e6bb89',NULL,1),(108,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-24 11:44:40','2026-10-24 11:44:40','Call log',NULL,NULL,'2bd2e636-c95f-4828-9d7e-e726f9039d0a',NULL,1),(109,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-24 11:45:41','2026-10-24 11:45:41','Call log',NULL,NULL,'0ef0c50d-ef66-44db-8c91-0918617120aa',NULL,1),(110,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-24 11:48:02','2026-10-24 11:48:02','Call log',NULL,NULL,'b0f53380-81b5-4b8f-a2d8-b4e405b2c7a1',NULL,1),(111,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-24 11:49:31','2026-10-24 11:49:31','Call log',NULL,NULL,'914da248-4f4f-4f64-9e20-fbf6e00ca826',NULL,1),(112,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-24 12:06:51','2026-10-24 12:06:51','Call log',NULL,NULL,'19667469-cc10-47e7-9c37-726ba33a27c8',NULL,1),(113,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-24 12:07:24','2026-10-24 12:07:24','Call log',NULL,NULL,'1ab6202a-3c3e-4b5e-89f0-ed910950dbb3',NULL,1),(114,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-24 12:08:35','2026-10-24 12:08:35','Call log',NULL,NULL,'f56857b7-e0c5-4441-9969-a16f601a0855',NULL,1),(115,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-24 12:12:46','2026-10-24 12:12:46','Call log',NULL,NULL,'b91e4f3b-b3ce-4821-a0c7-342d31994f13',NULL,1),(116,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-24 12:12:51','2026-10-24 12:12:51','Call log',NULL,NULL,'20a708da-a575-4873-b3c3-9dd3d9742cb7',NULL,1),(117,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-24 12:12:58','2026-10-24 12:12:58','Call log',NULL,NULL,'136de157-a162-4be0-86b3-304685edb621',NULL,1),(118,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-24 12:13:12','2026-10-24 12:13:12','Call log',NULL,NULL,'b2ce40b6-9b0a-4130-8217-25b37896fbf1',NULL,1),(119,20,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-24 12:14:01','2026-10-24 12:14:01','Call log',NULL,NULL,'56a905f7-f925-4106-9146-9e4b8b4359bc',NULL,1),(120,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-24 12:14:55','2026-10-24 12:14:55','Call log',NULL,NULL,'45ff44fa-25f9-45bd-bf9c-38e00d1ddf0c',NULL,1),(121,20,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-24 12:15:00','2026-10-24 12:15:00','Call log',NULL,NULL,'3c4f405c-8c63-4c54-aa66-48a693c148c5',NULL,1),(122,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-24 12:15:21','2026-10-24 12:15:21','Call log',NULL,NULL,'7897980d-bfb5-4ac4-bd9d-804ae1cb9daf',NULL,1),(123,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-24 12:15:32','2026-10-24 12:15:32','Call log',NULL,NULL,'5de492f8-9589-4be1-bb88-f9fdec7d3533',NULL,1),(124,43,'Gagandeep Steels Administrator',1,'Outgoing call: Connected','deal','2026-10-24 12:15:50','2026-10-24 12:15:50','Call log',NULL,NULL,'f82cf2dd-1cd8-477d-bd6b-9cd28bffd91a',NULL,1),(125,43,'Gagandeep Steels Administrator',1,'Outgoing call: Connected','ssw','2026-10-24 12:16:00','2026-10-24 12:16:00','Call log',NULL,NULL,'3f7e865d-e0c2-4274-8e11-0e9186ff24dc',NULL,1),(126,43,'Gagandeep Steels Administrator',1,'Outgoing call: Connected','hello','2026-10-24 12:20:59','2026-10-24 12:20:59','Call log',NULL,NULL,'9ba5bf72-8066-4a77-9e1e-f92e94156e88',NULL,1),(127,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-25 17:44:57','2026-10-25 17:44:57','Call log',NULL,NULL,'990a4cbc-ec97-4dea-8e70-e3f79ee87270',NULL,1),(128,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-25 17:51:57','2026-10-25 17:51:57','Call log',NULL,NULL,'a396b3da-d825-4f24-85db-2df4e4f63621',NULL,1),(129,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-25 17:52:04','2026-10-25 17:52:04','Call log',NULL,NULL,'6ebb9df3-ae16-4aae-826a-ae7a5c42b6c7',NULL,1),(130,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-25 17:52:37','2026-10-25 17:52:37','Call log',NULL,NULL,'07a0f76d-a737-4fdd-a9d3-c724053adabb',NULL,1),(131,20,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-25 17:53:39','2026-10-25 17:53:39','Call log',NULL,NULL,'e5e2c15b-a213-4984-a3b5-d8b6a1c93aac',NULL,1),(132,20,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-25 17:59:20','2026-10-25 17:59:20','Call log',NULL,NULL,'0c779b8a-64f6-4875-9319-39010560a6c0',NULL,1),(133,43,'Gagandeep Steels Administrator',1,'Stage changed','New → Initial Contact','2026-10-25 18:00:04','2026-10-25 18:00:04','System',NULL,NULL,NULL,NULL,1),(134,20,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-25 18:01:15','2026-10-25 18:01:15','Call log',NULL,NULL,'211abdaa-acbd-40e6-ac00-307f23b66ebb',NULL,1),(135,39,'Gagandeep Steels Administrator',1,'Contact information updated','Email','2026-10-25 18:23:39','2026-10-25 18:23:39','System',NULL,NULL,NULL,NULL,1),(136,20,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-25 18:27:34','2026-10-25 18:27:34','Call log',NULL,NULL,'24c13ec2-93bf-44a2-8804-06be9c9b0ceb',NULL,1),(137,43,'Gagandeep Steels Administrator',1,'Stage changed','Initial Contact → New','2026-10-25 18:30:36','2026-10-25 18:30:36','System',NULL,NULL,NULL,NULL,1),(138,37,'Gagandeep Steels Administrator',1,'Stage changed','New → Negotiation','2026-10-25 18:53:02','2026-10-25 18:53:02','System',NULL,NULL,NULL,NULL,1),(139,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-25 18:53:23','2026-10-25 18:53:23','Call log',NULL,NULL,'eff084b6-a173-42c7-838b-8bf0336f3a09',NULL,1),(140,43,'Gagandeep Steels Administrator',1,'Outgoing call: Connected','deal done','2026-10-25 18:54:27','2026-10-25 18:54:27','Call log',NULL,NULL,'014b5754-181d-4efe-b63d-e511ec8aa9d7',NULL,1),(141,43,'Gagandeep Steels Administrator',1,'Outgoing call: Connected','okay','2026-10-25 19:10:29','2026-10-25 19:10:29','Call log',NULL,NULL,'141a8209-c360-45f1-9b86-8ece3ca01400',NULL,1),(142,43,'Gagandeep Steels Administrator',1,'Outgoing call: Connected','njsdnfwefo','2026-10-25 19:18:46','2026-10-25 19:18:46','Call log',NULL,NULL,'917b7bf8-197e-4d9a-9e04-46d0c44633a0',NULL,1),(143,20,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-25 19:19:56','2026-10-25 19:19:56','Call log',NULL,NULL,'7e685c76-a31d-49ab-8bab-265be9710bb1',NULL,1),(144,43,'Gagandeep Steels Administrator',1,'Contact information updated','Email','2026-10-25 19:39:33','2026-10-25 19:39:33','System',NULL,NULL,NULL,NULL,1),(145,43,'Gagandeep Steels Administrator',1,'Call action prepared','okay','2026-10-25 19:44:11','2026-10-25 19:44:11','Call log',NULL,NULL,NULL,NULL,1),(146,43,'Gagandeep Steels Administrator',1,'WhatsApp action prepared','nfisfihsiufhiurfhfriuf','2026-10-25 19:44:31','2026-10-25 19:44:31','Note',NULL,NULL,NULL,NULL,1),(147,43,'Gagandeep Steels Administrator',1,'Email action prepared','okay · knfisfuiwfw','2026-10-25 19:45:11','2026-10-25 19:45:11','Note',NULL,NULL,NULL,NULL,1),(148,43,'Gagandeep Steels Administrator',1,'SMS action prepared','hello, how are you','2026-10-26 07:04:37','2026-10-26 07:04:37','Note',NULL,NULL,NULL,NULL,1),(149,43,'Gagandeep Steels Administrator',1,'WhatsApp action prepared','hello','2026-10-26 07:06:03','2026-10-26 07:06:03','Note',NULL,NULL,NULL,NULL,1),(150,20,'Gagandeep Steels Administrator',1,'WhatsApp action prepared','hello','2026-10-26 07:06:35','2026-10-26 07:06:35','Note',NULL,NULL,NULL,NULL,1),(151,43,'Gagandeep Steels Administrator',1,'WhatsApp action prepared','hello','2026-10-26 07:06:35','2026-10-26 07:06:35','Note',NULL,NULL,NULL,NULL,1),(152,43,'Gagandeep Steels Administrator',1,'title','notes message','2026-10-26 07:08:04','2026-10-26 07:08:04','Note',NULL,NULL,NULL,NULL,1),(153,43,'Gagandeep Steels Administrator',1,'Outgoing call: Unanswered','','2026-10-26 07:19:00','2026-10-26 07:19:00','Call log',NULL,NULL,'cede4418-2946-4040-9991-0f4d935fc664',NULL,1),(154,43,'Gagandeep Steels Administrator',1,'Task scheduled','Get update · 26 Oct 2026 13:36 · Assigned to Logic Integratorz','2026-10-26 08:08:01','2026-10-26 08:08:01','System',NULL,NULL,NULL,NULL,1),(155,43,'Gagandeep Steels Administrator',1,'Stage changed','New → Initial Contact','2026-10-28 02:44:13','2026-10-28 02:44:13','System',NULL,NULL,NULL,NULL,1),(156,43,'Gagandeep Steels Administrator',1,'Stage changed','Initial Contact → Enquiry Confirmed','2026-10-28 02:44:19','2026-10-28 02:44:19','System',NULL,NULL,NULL,NULL,1),(157,42,'Gagandeep Steels Administrator',1,'Stage changed','New → Initial Contact','2026-10-28 06:39:05','2026-10-28 06:39:05','System',NULL,NULL,NULL,NULL,1),(158,41,'Gagandeep Steels Administrator',1,'Stage changed','New → Site Approval','2026-10-28 06:39:12','2026-10-28 06:39:12','System',NULL,NULL,NULL,NULL,1),(159,42,'Gagandeep Steels Administrator',1,'Stage changed','Initial Contact → New','2026-10-28 06:39:16','2026-10-28 06:39:16','System',NULL,NULL,NULL,NULL,1),(160,42,'Gagandeep Steels Administrator',1,'Stage changed','New → Initial Contact','2026-10-28 06:39:20','2026-10-28 06:39:20','System',NULL,NULL,NULL,NULL,1),(161,42,'Gagandeep Steels Administrator',1,'Stage changed','Initial Contact → New','2026-10-28 06:39:23','2026-10-28 06:39:23','System',NULL,NULL,NULL,NULL,1),(162,39,'Gagandeep Steels Administrator',1,'Stage changed','New → Initial Contact','2026-10-28 06:39:27','2026-10-28 06:39:27','System',NULL,NULL,NULL,NULL,1),(163,39,'Gagandeep Steels Administrator',1,'Stage changed','Initial Contact → New','2026-10-28 06:48:53','2026-10-28 06:48:53','System',NULL,NULL,NULL,NULL,1),(164,42,'Gagandeep Steels Administrator',1,'Stage changed','New → Initial Contact','2026-10-30 10:09:09','2026-10-30 10:09:09','System',NULL,NULL,NULL,NULL,1),(165,42,'Gagandeep Steels Administrator',1,'Stage changed','Initial Contact → New','2026-10-30 10:09:13','2026-10-30 10:09:13','System',NULL,NULL,NULL,NULL,1),(166,37,'Gagandeep Steels Administrator',1,'Stage changed','Negotiation → Initial Contact','2026-10-30 10:09:32','2026-10-30 10:09:32','System',NULL,NULL,NULL,NULL,1),(167,42,'Gagandeep Steels Administrator',1,'Lead assigned','Unassigned → Gagandeep Steels Administrator (claimed on first open)','2026-10-31 07:07:30','2026-10-31 07:07:30','System',NULL,NULL,NULL,NULL,1),(168,43,'Gagandeep Steels Administrator',1,'Stage changed','Enquiry Confirmed → New','2026-10-31 07:37:58','2026-10-31 07:37:58','System',NULL,NULL,NULL,NULL,1);
/*!40000 ALTER TABLE `lead_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_backup_settings`
--

DROP TABLE IF EXISTS `lead_backup_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_backup_settings` (
  `id` int unsigned NOT NULL,
  `backup_after_days` int unsigned NOT NULL DEFAULT '30',
  `delete_after_days` int unsigned NOT NULL DEFAULT '365',
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_backup_settings`
--

LOCK TABLES `lead_backup_settings` WRITE;
/*!40000 ALTER TABLE `lead_backup_settings` DISABLE KEYS */;
INSERT INTO `lead_backup_settings` VALUES (1,30,365,1,'2026-10-31 17:10:35','2026-10-31 17:10:35');
/*!40000 ALTER TABLE `lead_backup_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_backups`
--

DROP TABLE IF EXISTS `lead_backups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_backups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint unsigned DEFAULT NULL,
  `source` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Manual',
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Backed up',
  `snapshot` json NOT NULL,
  `backed_up_at` timestamp NOT NULL,
  `purge_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lead_backups_lead_id_unique` (`lead_id`),
  KEY `lead_backups_source_index` (`source`),
  KEY `lead_backups_status_index` (`status`),
  KEY `lead_backups_backed_up_at_index` (`backed_up_at`),
  KEY `lead_backups_purge_at_index` (`purge_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_backups`
--

LOCK TABLES `lead_backups` WRITE;
/*!40000 ALTER TABLE `lead_backups` DISABLE KEYS */;
/*!40000 ALTER TABLE `lead_backups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_connections`
--

DROP TABLE IF EXISTS `lead_connections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_connections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `credentials` text COLLATE utf8mb4_unicode_ci,
  `owner_id` bigint unsigned DEFAULT NULL,
  `last_attempt_at` timestamp NULL DEFAULT NULL,
  `synced_until` timestamp NULL DEFAULT NULL,
  `last_success_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Not configured',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lead_connections_provider_unique` (`provider`),
  KEY `lead_connections_owner_id_foreign` (`owner_id`),
  CONSTRAINT `lead_connections_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_connections`
--

LOCK TABLES `lead_connections` WRITE;
/*!40000 ALTER TABLE `lead_connections` DISABLE KEYS */;
INSERT INTO `lead_connections` VALUES (4,'IndiaMART',0,NULL,NULL,NULL,NULL,NULL,'Not configured','2026-10-18 16:18:07','2026-10-18 16:18:07'),(5,'Justdial',0,NULL,NULL,NULL,NULL,NULL,'Not configured','2026-10-18 16:18:07','2026-10-18 16:18:07'),(6,'TradeIndia',0,NULL,NULL,NULL,NULL,NULL,'Not configured','2026-10-18 16:18:07','2026-10-18 16:18:07');
/*!40000 ALTER TABLE `lead_connections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_group_members`
--

DROP TABLE IF EXISTS `lead_group_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_group_members` (
  `lead_group_id` bigint unsigned NOT NULL,
  `lead_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`lead_group_id`,`lead_id`),
  KEY `lead_group_members_lead_id_foreign` (`lead_id`),
  CONSTRAINT `lead_group_members_lead_group_id_foreign` FOREIGN KEY (`lead_group_id`) REFERENCES `lead_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lead_group_members_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_group_members`
--

LOCK TABLES `lead_group_members` WRITE;
/*!40000 ALTER TABLE `lead_group_members` DISABLE KEYS */;
/*!40000 ALTER TABLE `lead_group_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_groups`
--

DROP TABLE IF EXISTS `lead_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_by` bigint unsigned NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lead_groups_created_by_name_unique` (`created_by`,`name`),
  CONSTRAINT `lead_groups_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_groups`
--

LOCK TABLES `lead_groups` WRITE;
/*!40000 ALTER TABLE `lead_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `lead_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_list_automations`
--

DROP TABLE IF EXISTS `lead_list_automations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_list_automations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lead_list_id` bigint unsigned NOT NULL,
  `channel` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `frequency` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `send_time` time NOT NULL,
  `day_of_week` tinyint unsigned DEFAULT NULL,
  `day_of_month` tinyint unsigned DEFAULT NULL,
  `next_run_at` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_list_automations_lead_list_id_foreign` (`lead_list_id`),
  CONSTRAINT `lead_list_automations_lead_list_id_foreign` FOREIGN KEY (`lead_list_id`) REFERENCES `lead_lists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_list_automations`
--

LOCK TABLES `lead_list_automations` WRITE;
/*!40000 ALTER TABLE `lead_list_automations` DISABLE KEYS */;
/*!40000 ALTER TABLE `lead_list_automations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_list_deliveries`
--

DROP TABLE IF EXISTS `lead_list_deliveries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_list_deliveries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lead_list_id` bigint unsigned DEFAULT NULL,
  `lead_id` bigint unsigned DEFAULT NULL,
  `automation_id` bigint unsigned DEFAULT NULL,
  `sent_by` bigint unsigned DEFAULT NULL,
  `channel` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `error` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_list_deliveries_lead_id_foreign` (`lead_id`),
  KEY `lead_list_deliveries_automation_id_foreign` (`automation_id`),
  KEY `lead_list_deliveries_sent_by_foreign` (`sent_by`),
  KEY `lead_list_deliveries_lead_list_id_created_at_index` (`lead_list_id`,`created_at`),
  CONSTRAINT `lead_list_deliveries_automation_id_foreign` FOREIGN KEY (`automation_id`) REFERENCES `lead_list_automations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lead_list_deliveries_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lead_list_deliveries_lead_list_id_foreign` FOREIGN KEY (`lead_list_id`) REFERENCES `lead_lists` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lead_list_deliveries_sent_by_foreign` FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_list_deliveries`
--

LOCK TABLES `lead_list_deliveries` WRITE;
/*!40000 ALTER TABLE `lead_list_deliveries` DISABLE KEYS */;
/*!40000 ALTER TABLE `lead_list_deliveries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_list_members`
--

DROP TABLE IF EXISTS `lead_list_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_list_members` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lead_list_id` bigint unsigned NOT NULL,
  `lead_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lead_list_members_lead_list_id_lead_id_unique` (`lead_list_id`,`lead_id`),
  KEY `lead_list_members_lead_id_foreign` (`lead_id`),
  CONSTRAINT `lead_list_members_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lead_list_members_lead_list_id_foreign` FOREIGN KEY (`lead_list_id`) REFERENCES `lead_lists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_list_members`
--

LOCK TABLES `lead_list_members` WRITE;
/*!40000 ALTER TABLE `lead_list_members` DISABLE KEYS */;
/*!40000 ALTER TABLE `lead_list_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_lists`
--

DROP TABLE IF EXISTS `lead_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_lists` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_by` bigint unsigned NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `lead_lists_created_by_foreign` (`created_by`),
  CONSTRAINT `lead_lists_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_lists`
--

LOCK TABLES `lead_lists` WRITE;
/*!40000 ALTER TABLE `lead_lists` DISABLE KEYS */;
/*!40000 ALTER TABLE `lead_lists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_repeat_matches`
--

DROP TABLE IF EXISTS `lead_repeat_matches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_repeat_matches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint unsigned NOT NULL,
  `previous_lead_id` bigint unsigned NOT NULL,
  `reason` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lead_repeat_matches_lead_id_previous_lead_id_unique` (`lead_id`,`previous_lead_id`),
  KEY `lead_repeat_matches_previous_lead_id_foreign` (`previous_lead_id`),
  CONSTRAINT `lead_repeat_matches_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lead_repeat_matches_previous_lead_id_foreign` FOREIGN KEY (`previous_lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_repeat_matches`
--

LOCK TABLES `lead_repeat_matches` WRITE;
/*!40000 ALTER TABLE `lead_repeat_matches` DISABLE KEYS */;
/*!40000 ALTER TABLE `lead_repeat_matches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_settings`
--

DROP TABLE IF EXISTS `lead_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `weights` json DEFAULT NULL,
  `email_config` json DEFAULT NULL,
  `email_password` text COLLATE utf8mb4_unicode_ci,
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `whatsapp_config` json DEFAULT NULL,
  `whatsapp_token` text COLLATE utf8mb4_unicode_ci,
  `same_stage_alert_days` int unsigned NOT NULL DEFAULT '3',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_settings`
--

LOCK TABLES `lead_settings` WRITE;
/*!40000 ALTER TABLE `lead_settings` DISABLE KEYS */;
INSERT INTO `lead_settings` VALUES (1,'{\"name\": 5, \"email\": 0, \"phone\": 0, \"source\": 0, \"company\": 5, \"website\": 0, \"category\": 0, \"location\": 0, \"whatsapp\": 0, \"created_by\": 5}',NULL,NULL,11,'2026-10-18 16:18:07','2026-11-01 16:04:35',NULL,NULL,1);
/*!40000 ALTER TABLE `lead_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_source_events`
--

DROP TABLE IF EXISTS `lead_source_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_source_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint unsigned DEFAULT NULL,
  `source` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Manual',
  `received_at` timestamp NOT NULL,
  `lost_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lead_source_events_lead_id_unique` (`lead_id`),
  KEY `lead_source_events_lead_id_index` (`lead_id`),
  KEY `lead_source_events_source_index` (`source`),
  KEY `lead_source_events_received_at_index` (`received_at`),
  KEY `lead_source_events_lost_at_index` (`lost_at`),
  KEY `lead_source_events_deleted_at_index` (`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_source_events`
--

LOCK TABLES `lead_source_events` WRITE;
/*!40000 ALTER TABLE `lead_source_events` DISABLE KEYS */;
INSERT INTO `lead_source_events` VALUES (1,14,'IndiaMART','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35'),(2,15,'Justdial','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35'),(3,16,'TradeIndia','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35'),(4,17,'Website','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35'),(5,18,'IndiaMART','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35'),(6,19,'Justdial','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35'),(7,20,'TradeIndia','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35'),(8,21,'Website','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35'),(9,22,'IndiaMART','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35'),(10,23,'Justdial','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35'),(11,24,'TradeIndia','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35'),(12,25,'Website','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35'),(13,26,'IndiaMART','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35'),(14,27,'Justdial','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35'),(15,28,'TradeIndia','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35'),(16,29,'Website','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35'),(17,30,'IndiaMART','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35'),(18,31,'Justdial','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35'),(19,32,'TradeIndia','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35'),(20,33,'Website','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35'),(21,34,'IndiaMART','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35'),(22,35,'Justdial','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35'),(23,36,'TradeIndia','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35'),(24,37,'Website','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35'),(25,38,'IndiaMART','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35'),(26,39,'Justdial','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35'),(27,40,'TradeIndia','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35'),(28,41,'Website','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35'),(29,42,'IndiaMART','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35'),(30,43,'Justdial','2026-10-18 16:22:11',NULL,NULL,'2026-10-31 17:10:35','2026-10-31 17:10:35');
/*!40000 ALTER TABLE `lead_source_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_visits`
--

DROP TABLE IF EXISTS `lead_visits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_visits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `session_key` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `opened_at` timestamp NOT NULL,
  `last_seen_at` timestamp NOT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `stage_updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lead_visits_lead_id_session_key_unique` (`lead_id`,`session_key`),
  KEY `lead_visits_user_id_foreign` (`user_id`),
  KEY `lead_visits_lead_id_id_index` (`lead_id`,`id`),
  CONSTRAINT `lead_visits_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lead_visits_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_visits`
--

LOCK TABLES `lead_visits` WRITE;
/*!40000 ALTER TABLE `lead_visits` DISABLE KEYS */;
/*!40000 ALTER TABLE `lead_visits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_work_items`
--

DROP TABLE IF EXISTS `lead_work_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_work_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint unsigned NOT NULL,
  `kind` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `comments` text COLLATE utf8mb4_unicode_ci,
  `attachment_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment_mime` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment_size` bigint unsigned DEFAULT NULL,
  `due_at` datetime NOT NULL,
  `priority` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Normal',
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recurring` tinyint(1) NOT NULL DEFAULT '0',
  `recurrence_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `outcome` text COLLATE utf8mb4_unicode_ci,
  `completed_at` datetime DEFAULT NULL,
  `notified_at` datetime DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `assigned_to` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_work_items_lead_id_foreign` (`lead_id`),
  KEY `lead_work_items_due_at_index` (`due_at`),
  KEY `lead_work_items_assigned_to_foreign` (`assigned_to`),
  CONSTRAINT `lead_work_items_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lead_work_items_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_work_items`
--

LOCK TABLES `lead_work_items` WRITE;
/*!40000 ALTER TABLE `lead_work_items` DISABLE KEYS */;
INSERT INTO `lead_work_items` VALUES (3,43,'Follow-up','give rates',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-20 22:08:00','High',NULL,0,NULL,'Pending',NULL,NULL,'2026-10-20 22:08:00',1,'2026-10-20 16:37:32','2026-10-20 16:38:00',1),(4,43,'Task','Get update','jhgtgtyftyfytftf','[26 Oct 2026 04:21 PM] Gagandeep Steels Administrator: deal done\n\n[26 Oct 2026 04:23 PM] Gagandeep Steels Administrator: dwbuwbcbeyerfr\n\n[30 Oct 2026 03:40 PM] Gagandeep Steels Administrator: dnvkokerklgnoernoer','task-attachments/o6LQXeME8Ak8mSSwcrsBv8OFenkmeWqpq9T20PpA.jpg','WhatsApp Image 2026-09-25 at 11.45.59 PM.jpeg','image/jpeg',441506,'2026-10-26 13:36:00','High',NULL,1,'Daily','Processing',NULL,NULL,'2026-10-26 13:39:00',22,'2026-10-26 08:08:01','2026-10-30 10:10:32',4);
/*!40000 ALTER TABLE `lead_work_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leads`
--

DROP TABLE IF EXISTS `leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alt_phone` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stage` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'New',
  `priority` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Normal',
  `value` decimal(15,2) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `owner_id` bigint unsigned DEFAULT NULL,
  `claimed_by` bigint unsigned DEFAULT NULL,
  `claimed_at` timestamp NULL DEFAULT NULL,
  `untouched_archived_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `website` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `connection_id` bigint unsigned DEFAULT NULL,
  `external_id` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `archived_external_id` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_saved_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `job_role` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `identity_phone` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `identity_profile` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stage_changed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `leads_connection_id_external_id_unique` (`connection_id`,`external_id`),
  KEY `leads_owner_id_foreign` (`owner_id`),
  KEY `leads_created_by_foreign` (`created_by`),
  KEY `leads_stage_index` (`stage`),
  KEY `leads_job_role_index` (`job_role`),
  KEY `leads_claimed_by_foreign` (`claimed_by`),
  KEY `leads_identity_phone_index` (`identity_phone`),
  KEY `leads_identity_profile_index` (`identity_profile`),
  KEY `leads_untouched_archived_at_index` (`untouched_archived_at`),
  KEY `leads_stage_changed_at_index` (`stage_changed_at`),
  CONSTRAINT `leads_claimed_by_foreign` FOREIGN KEY (`claimed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leads_connection_id_foreign` FOREIGN KEY (`connection_id`) REFERENCES `lead_connections` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leads_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leads_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leads`
--

LOCK TABLES `leads` WRITE;
/*!40000 ALTER TABLE `leads` DISABLE KEYS */;
INSERT INTO `leads` VALUES (14,'Arjun Mehta','Northstar Steel Industries',NULL,NULL,NULL,'Ludhiana','IndiaMART',NULL,'New','Normal',NULL,NULL,NULL,NULL,NULL,'2026-10-23 09:39:14',NULL,2,'2026-10-18 16:22:11','2026-10-23 09:48:38',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'9a169d1c768b2f78c8ddfcbec6e13607adc0972377f622025d671c0552ef5eca','2026-10-23 09:48:38'),(15,'Simran Kaur','Punjab Alloy Traders',NULL,NULL,NULL,'Mandi Gobindgarh','Justdial',NULL,'New','Normal',NULL,NULL,NULL,NULL,NULL,'2026-10-23 09:39:14',NULL,2,'2026-10-18 16:22:11','2026-10-23 09:48:38',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'df6396af44eb27cb178ac21dfc927d5902488efbb8c42b7d301ec8d1c719e1d7','2026-10-23 09:48:38'),(16,'Rohit Sharma','Everest Rolling Mills',NULL,NULL,NULL,'Khanna','TradeIndia',NULL,'New','Normal',NULL,NULL,NULL,NULL,NULL,'2026-10-23 09:39:14',NULL,2,'2026-10-18 16:22:11','2026-10-23 09:48:38',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'b4e3a4fbffdcdc54b0971418b316fa81ddaa8a60dff420ee94a2ba125085b642','2026-10-23 09:48:38'),(17,'Neha Bansal','Silverline Metal Works',NULL,NULL,NULL,'Jalandhar','Website',NULL,'New','Normal',NULL,NULL,NULL,NULL,NULL,'2026-10-23 09:39:14',NULL,2,'2026-10-18 16:22:11','2026-10-23 09:48:38',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'52a58d6c8781ac39932f3c38200c02ace61a2c89f5c7e42e475841660fc88973','2026-10-23 09:48:38'),(18,'Karan Sethi','Prime Iron Distributors',NULL,NULL,NULL,'Amritsar','IndiaMART',NULL,'New','Normal',NULL,NULL,NULL,NULL,NULL,'2026-10-23 09:39:14',NULL,2,'2026-10-18 16:22:11','2026-10-23 09:48:38',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'f0058e0c9c84ac14a231c74ae61eb1ba2e63fff7b53ae98b458849c3e496ef95','2026-10-23 09:48:38'),(19,'Priya Verma','Metro Steel Processing',NULL,NULL,NULL,'Chandigarh','Justdial',NULL,'New','Normal',NULL,NULL,NULL,NULL,NULL,'2026-10-23 09:39:14',NULL,2,'2026-10-18 16:22:11','2026-10-23 09:48:38',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'8c065c575eafe90d0bad8816be3e8b50f3d3b940c35b94d83b479c39d0588830','2026-10-23 09:48:38'),(20,'Manpreet Singh','Crown Foundry Solutions','9888234444',NULL,NULL,'Patiala','TradeIndia',NULL,'New','Normal',NULL,NULL,1,1,'2026-10-19 11:57:28',NULL,NULL,5,'2026-10-18 16:22:11','2026-10-23 08:29:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Cast Iron Wires','9888234444','8b270f1710d1bf3dbfc7bebea4e02848e7ca8eeb55c7febc5a495420e64080d9','2026-10-23 08:29:00'),(21,'Aditi Gupta','Sunrise Scrap Suppliers',NULL,NULL,NULL,'Bathinda','Website',NULL,'New','Normal',NULL,NULL,NULL,NULL,NULL,'2026-10-23 09:39:14',NULL,2,'2026-10-18 16:22:11','2026-10-23 09:48:38',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'9ebb9ef1ccb2cf0f451025a146077de288539186f7cdbce6a43c7ed3621df814','2026-10-23 09:48:38'),(22,'Vikram Arora','Apex TMT Products',NULL,NULL,NULL,'Mohali','IndiaMART',NULL,'New','Normal',NULL,NULL,NULL,NULL,NULL,'2026-10-23 09:39:14',NULL,2,'2026-10-18 16:22:11','2026-10-23 09:48:38',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1d4d2bf31a253ec3f4b47203a844c995a14e7882dd61adfdced227c3280c0229','2026-10-23 09:48:38'),(23,'Kavya Malhotra','Trident Pipe & Tube Co.',NULL,NULL,NULL,'Fatehgarh Sahib','Justdial',NULL,'New','Normal',NULL,NULL,NULL,NULL,NULL,'2026-10-23 09:39:14',NULL,2,'2026-10-18 16:22:11','2026-10-23 09:48:38',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'46cd018d3be05990cc18b4180ae37304acb5b8ef58eadad958a3edd8211d179e','2026-10-23 09:48:38'),(24,'Harshdeep Gill','Blue Ridge Steel Depot',NULL,NULL,NULL,'Ludhiana','TradeIndia',NULL,'New','Normal',NULL,NULL,NULL,NULL,NULL,'2026-10-23 09:39:14',NULL,2,'2026-10-18 16:22:11','2026-10-23 09:48:38',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ea3db12c0d6fa035a6e9580ee4fe44b7054dedd75fe70e99c175adb358573958','2026-10-23 09:48:38'),(25,'Isha Kapoor','Orion Industrial Metals',NULL,NULL,NULL,'Jalandhar','Website',NULL,'New','Normal',NULL,NULL,NULL,NULL,NULL,'2026-10-23 09:39:14',NULL,2,'2026-10-18 16:22:11','2026-10-23 09:48:38',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'226fd157ef93b12e252021034db86cea57d932e3114600d86d49404871eb966e','2026-10-23 09:48:38'),(26,'Nitin Aggarwal','Shakti Billet Manufacturing',NULL,NULL,NULL,'Mandi Gobindgarh','IndiaMART',NULL,'New','Normal',NULL,NULL,NULL,NULL,NULL,'2026-10-23 09:39:14',NULL,2,'2026-10-18 16:22:11','2026-10-23 09:48:38',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'21c51a1a722cecdbcf7bf59089c2bff2c47fafa863dd30a512cf77ab903cd552','2026-10-23 09:48:38'),(27,'Gurleen Sandhu','Pioneer Castings & Alloys',NULL,NULL,NULL,'Khanna','Justdial',NULL,'New','Normal',NULL,NULL,NULL,NULL,NULL,'2026-10-23 09:39:14',NULL,2,'2026-10-18 16:22:11','2026-10-23 09:48:38',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'dc3282b6aeba3912e3544938751b141df76dcb2af319755e85816f47a6bba246','2026-10-23 09:48:38'),(28,'Deepak Jain','Reliable Ferro Traders',NULL,NULL,NULL,'Amritsar','TradeIndia',NULL,'New','Normal',NULL,NULL,NULL,NULL,NULL,'2026-10-23 09:39:14',NULL,2,'2026-10-18 16:22:11','2026-10-23 09:48:38',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'5f85b30c1cef8d52b2042da5791d49bb59bd86d92c65aa6cd237078b04ded1b3','2026-10-23 09:48:38'),(29,'Mehak Chawla','Meridian Steel Logistics',NULL,NULL,NULL,'Chandigarh','Website',NULL,'New','Normal',NULL,NULL,NULL,NULL,NULL,'2026-10-23 09:39:14',NULL,2,'2026-10-18 16:22:11','2026-10-23 09:48:38',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'8c50ddab6e2516fb1703953172c6aaed6235f96cb745ea74f88577508f547826','2026-10-23 09:48:38'),(30,'Sahil Khanna','Capital Iron Foundries',NULL,NULL,NULL,'Patiala','IndiaMART',NULL,'New','Normal',NULL,NULL,NULL,NULL,NULL,'2026-10-23 09:39:14',NULL,2,'2026-10-18 16:22:11','2026-10-23 09:48:38',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'f9d152c958fa30dbc769f62f41688096a005fdb2e6f209842a4dd11cd5be1206','2026-10-23 09:48:38'),(31,'Ritika Sood','Westfield Metal Supplies',NULL,NULL,NULL,'Bathinda','Justdial',NULL,'New','Normal',NULL,NULL,NULL,NULL,NULL,'2026-10-23 09:39:14',NULL,2,'2026-10-18 16:22:11','2026-10-23 09:48:38',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'bc0ca7b9aa192d7d763d959212047ee77c0adeb9867e9d5b5c32d819a5632de2','2026-10-23 09:48:38'),(32,'Jaspreet Dhillon','Nova Structural Steel',NULL,NULL,NULL,'Mohali','TradeIndia',NULL,'New','Normal',NULL,NULL,NULL,NULL,NULL,'2026-10-23 09:39:14',NULL,2,'2026-10-18 16:22:11','2026-10-23 09:48:38',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'9284cf279440b2c728bd727efe9a86b8f0d88eedf2dbdab9473e0a2ebac91054','2026-10-23 09:48:38'),(33,'Ananya Kohli','Crescent Alloy House',NULL,NULL,NULL,'Ludhiana','Website',NULL,'New','Normal',NULL,NULL,NULL,NULL,NULL,'2026-10-23 09:39:14',NULL,2,'2026-10-18 16:22:11','2026-10-23 09:48:38',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'4b21d07e109c68c5d5e669f06526df52a8dff44abf175168f909214351033b66','2026-10-23 09:48:38'),(34,'Varun Mittal','Sterling Rebar Industries',NULL,NULL,NULL,'Mandi Gobindgarh','IndiaMART',NULL,'New','Normal',NULL,NULL,NULL,NULL,NULL,'2026-10-23 09:39:14',NULL,2,'2026-10-18 16:22:11','2026-10-23 09:48:38',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'899864fec4159646e1d7185be526d1cc69b14a1b420d6d9a84f99117fe3dc1b7','2026-10-23 09:48:38'),(35,'Navjot Brar','Summit Fabrication Works',NULL,NULL,NULL,'Fatehgarh Sahib','Justdial',NULL,'New','Normal',NULL,NULL,NULL,NULL,NULL,'2026-10-23 09:39:14',NULL,2,'2026-10-18 16:22:11','2026-10-23 09:48:38',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'db5cef1148e41f77c73a97ea7616e9ebff0dd53becd4816093f245fbd3d68c3c','2026-10-23 09:48:38'),(36,'Riya Oberoi','Heritage Steel Components',NULL,NULL,NULL,'Jalandhar','TradeIndia',NULL,'New','Normal',NULL,NULL,NULL,NULL,NULL,'2026-10-23 09:39:14',NULL,2,'2026-10-18 16:22:11','2026-10-23 09:48:38',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'23991d4593b5aa24505d34593e9316905ac64e142dea6b11ee487f607a17162f','2026-10-23 09:48:38'),(37,'Mohit Singla','Titan Scrap & Metals',NULL,NULL,NULL,'Khanna','Website',NULL,'Initial Contact','Normal',NULL,NULL,1,1,'2026-10-23 10:17:28','2026-10-23 09:39:14',NULL,5,'2026-10-18 16:22:11','2026-10-30 10:09:32',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'44a418a36406ec9087978e1c4458763b1356e6b7a33903c680e22e6906e6f7cb','2026-10-30 10:09:32'),(38,'Pooja Narang','Galaxy Finished Goods Depot',NULL,NULL,NULL,'Amritsar','IndiaMART',NULL,'New','Normal',NULL,NULL,NULL,NULL,NULL,'2026-10-23 09:39:14',NULL,2,'2026-10-18 16:22:11','2026-10-23 09:48:38',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'5446a873e929a9fd111a996facd1ff67e230b5e0c33efe9121a44298fdc13eaa','2026-10-23 09:48:38'),(39,'Abhishek Bedi','Atlas Rolling Products',NULL,NULL,'logicintegratorz@gmail.com','Chandigarh','Justdial',NULL,'New','Normal',NULL,NULL,1,1,'2026-10-23 10:18:12','2026-10-23 09:39:14',NULL,6,'2026-10-18 16:22:11','2026-10-28 06:48:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'97fe6aeff31bc03dd83776d62545511c19bd826ae20ada915b58f1c3be8e2040','2026-10-28 06:48:53'),(40,'Sneha Arora','Copperleaf Industrial Steels',NULL,NULL,NULL,'Patiala','TradeIndia',NULL,'New','Normal',NULL,NULL,NULL,NULL,NULL,'2026-10-23 09:39:14',NULL,2,'2026-10-18 16:22:11','2026-10-23 09:48:38',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2bbcf884e9b26c02e3021464ea8affcec4e3f3c39922496f5fe80c6352cbeb67','2026-10-23 09:48:38'),(41,'Rajveer Grewal','Fortune Melting Units',NULL,NULL,NULL,'Bathinda','Website',NULL,'Site Approval','Normal',NULL,NULL,1,1,'2026-10-19 11:53:34',NULL,NULL,3,'2026-10-18 16:22:11','2026-10-28 06:39:12',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'be010c8ee4ec71c39665a4bf72eb1f5e575f95d28469aefa26ab53c28e2c82da','2026-10-28 06:39:12'),(42,'Tanvi Mahajan','Oakridge Foundry Supplies',NULL,NULL,NULL,'Mohali','IndiaMART',NULL,'New','Normal',NULL,NULL,1,1,'2026-10-31 07:07:30','2026-10-23 09:39:14',NULL,9,'2026-10-18 16:22:11','2026-10-31 07:07:30',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'f324e296bdc7db300260afd7377b1484e709fad9e97f7078143030c443dc3f75','2026-10-31 07:07:30'),(43,'Yuvraj Saini','Eastern Steel Merchants','9888231944',NULL,'logicintegratorz@gmail.com','Ludhiana','Justdial',NULL,'New','Normal',NULL,NULL,1,1,'2026-10-19 09:09:44',NULL,NULL,9,'2026-10-18 16:22:11','2026-10-31 07:37:58',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'MS Melting Units','9888231944','29ad1eb7de10d330c42781a7bf6cfa0218d03fe21d2cd3888ddb4dfb3d4f66e1','2026-10-31 07:37:58');
/*!40000 ALTER TABLE `leads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026_09_29_000001_create_freight_rates_table',1),(2,'2026_09_30_000002_create_transporters_table',2),(3,'2026_09_30_000003_add_transporter_contact_details',3),(4,'2026_09_30_000004_add_transporter_version',4),(5,'2026_09_30_000005_create_access_control',5),(6,'2026_10_01_000006_create_crm_notifications',6),(7,'2026_10_01_000007_add_notification_broadcasts',7),(8,'2026_10_08_000008_create_products_and_prices',8),(9,'2026_10_08_000009_create_product_price_logs',9),(10,'2026_10_08_000010_allow_free_text_product_fields',10),(11,'2026_10_08_000011_create_product_sizes',11),(12,'2026_10_08_000012_add_base_price_to_product_sizes',12),(13,'2026_10_08_000013_create_product_size_activities',13),(14,'2026_10_08_000014_add_removed_to_product_prices',14),(15,'2026_10_09_000015_add_product_order_to_users',15),(16,'2026_10_09_000016_add_product_slots_to_users',16),(17,'2026_10_10_000017_add_size_location_prices',17),(18,'2026_10_10_000018_scope_sizes_to_company',18),(19,'2026_10_11_000019_notification_sound_setting',19),(20,'2026_10_11_000020_create_lead_management',20),(21,'2026_10_11_000021_lead_details_and_settings',21),(22,'2026_10_11_000022_lead_connections',21),(23,'2026_10_12_000023_lead_contacts_and_work_assignment',22),(24,'2026_10_13_000024_create_lead_lists',23),(25,'2026_10_13_000025_add_soft_deletes_to_leads',24),(26,'2026_10_13_000026_create_lead_visits',25),(27,'2026_10_14_000027_add_active_to_lead_lists',26),(28,'2026_10_14_000028_pause_legacy_whatsapp_reminder_schedules',27),(29,'2026_10_14_000029_skip_legacy_whatsapp_queue',28),(30,'2026_10_15_000001_create_crm_audit_events',29),(31,'2026_10_15_000002_create_calendar_assignments',30),(32,'2026_10_15_000003_cap_contact_weightage_total',31),(33,'2026_10_15_000004_add_job_role_to_leads',32),(34,'2026_10_17_000001_create_job_roles',33),(35,'2026_10_18_000001_add_actor_to_lead_activities',34),(36,'2026_10_18_000002_link_calendar_assignments_to_leads',35),(37,'2026_10_18_000003_backfill_lead_history_links',36),(38,'2026_10_18_000004_lead_claims_and_repeat_matches',37),(39,'2026_10_18_000005_clear_short_identity_phones',38),(40,'2026_10_18_000006_backfill_repeat_inquiries',39),(41,'2026_10_18_000007_preserve_archived_inquiry_id',40),(42,'2026_10_18_000008_create_lead_groups',41),(43,'2026_10_23_000001_add_untouched_archived_at_to_leads',42),(44,'2026_10_23_000002_create_direct_messages_table',43),(45,'2026_10_23_000003_create_chat_groups',44),(46,'2026_10_23_000004_create_screen_time_entries_table',45),(47,'2026_10_23_000005_add_last_active_at_to_screen_time_entries_table',46),(48,'2026_10_23_000006_create_user_login_events_table',47),(49,'2026_10_24_000001_create_crm_activities_table',48),(50,'2026_10_24_000002_create_crm_activity_lead_table',49),(51,'2026_10_24_000003_add_calendar_recipients_to_crm_activities_table',50),(52,'2026_10_26_000001_add_editing_to_chat_messages',51),(53,'2026_10_26_000002_add_task_fields_to_lead_work_items',52),(54,'2026_10_26_000003_create_productivity_items_table',53),(55,'2026_10_26_000004_expand_productivity_contact_fields',54),(56,'2026_10_26_000005_add_task_board_fields',55),(57,'2026_10_28_000001_create_task_statuses_table',56),(58,'2026_10_28_000002_add_status_to_productivity_items',57),(59,'2026_10_28_000003_create_productivity_item_user_table',57),(60,'2026_10_31_000001_create_sidebar_groups',58),(61,'2026_10_31_000002_lead_reporting_user_profiles_and_backups',59),(62,'2026_10_31_000003_create_overdue_alerts_table',60),(63,'2026_10_31_000004_add_alt_phone_to_leads',61),(64,'2026_11_01_000001_create_content_manager_and_sidebar_order',62),(65,'2026_11_01_000002_add_stagnant_lead_alerts',63);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_broadcasts`
--

DROP TABLE IF EXISTS `notification_broadcasts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_broadcasts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` bigint unsigned DEFAULT NULL,
  `submission_key` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `audience` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient_count` int unsigned NOT NULL,
  `recalled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_broadcasts_submission_key_unique` (`submission_key`),
  KEY `notification_broadcasts_sender_id_foreign` (`sender_id`),
  CONSTRAINT `notification_broadcasts_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_broadcasts`
--

LOCK TABLES `notification_broadcasts` WRITE;
/*!40000 ALTER TABLE `notification_broadcasts` DISABLE KEYS */;
INSERT INTO `notification_broadcasts` VALUES (7,1,'0fdc9b8c-9800-49d8-af12-85208deff98c','Hello Team','Good Morning to all','all',1,NULL,'2026-10-19 03:55:41','2026-10-19 03:55:41'),(8,1,'6fab53ee-fb17-47a7-b59c-38ba77f61c16','Hi, Rohit','connect to shiva steels','all',1,NULL,'2026-10-19 08:22:02','2026-10-19 08:22:02'),(9,1,'35943f87-f7b7-41de-bcbf-92799b959b18','t','call to sam','all',1,NULL,'2026-10-19 08:28:20','2026-10-19 08:28:20');
/*!40000 ALTER TABLE `notification_broadcasts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_settings`
--

DROP TABLE IF EXISTS `notification_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_settings` (
  `id` int unsigned NOT NULL,
  `sound_enabled` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_settings`
--

LOCK TABLES `notification_settings` WRITE;
/*!40000 ALTER TABLE `notification_settings` DISABLE KEYS */;
INSERT INTO `notification_settings` VALUES (1,1);
/*!40000 ALTER TABLE `notification_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overdue_alerts`
--

DROP TABLE IF EXISTS `overdue_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `overdue_alerts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `source_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `overdue_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `overdue_alerts_source_type_source_id_user_id_unique` (`source_type`,`source_id`,`user_id`),
  KEY `overdue_alerts_user_id_foreign` (`user_id`),
  KEY `overdue_alerts_source_type_source_id_index` (`source_type`,`source_id`),
  CONSTRAINT `overdue_alerts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=99 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overdue_alerts`
--

LOCK TABLES `overdue_alerts` WRITE;
/*!40000 ALTER TABLE `overdue_alerts` DISABLE KEYS */;
INSERT INTO `overdue_alerts` VALUES (1,'lead_work',3,1,'2026-10-20 16:38:00','2026-10-31 17:34:01','2026-10-31 17:34:01'),(2,'activity',1,4,'2026-10-25 19:22:00','2026-10-31 17:34:01','2026-10-31 17:34:01'),(3,'activity',1,1,'2026-10-25 19:22:00','2026-10-31 17:34:01','2026-10-31 17:34:01'),(4,'productivity',1,4,'2026-10-28 06:35:00','2026-10-31 17:34:01','2026-10-31 17:34:01'),(5,'productivity',1,1,'2026-10-28 06:35:00','2026-10-31 17:34:01','2026-10-31 17:34:01');
/*!40000 ALTER TABLE `overdue_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_price_logs`
--

DROP TABLE IF EXISTS `product_price_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_price_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `product_name` text COLLATE utf8mb4_unicode_ci,
  `item_code` text COLLATE utf8mb4_unicode_ci,
  `unit` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `effective_date` date NOT NULL,
  `old_purchase_price` text COLLATE utf8mb4_unicode_ci,
  `old_retail_price` text COLLATE utf8mb4_unicode_ci,
  `purchase_price` text COLLATE utf8mb4_unicode_ci,
  `retail_price` text COLLATE utf8mb4_unicode_ci,
  `event` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `changed_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `product_price_logs_product_id_index` (`product_id`),
  KEY `product_price_logs_effective_date_index` (`effective_date`),
  KEY `product_price_logs_changed_at_index` (`changed_at`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_price_logs`
--

LOCK TABLES `product_price_logs` WRITE;
/*!40000 ALTER TABLE `product_price_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_price_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_prices`
--

DROP TABLE IF EXISTS `product_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_prices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `company` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `effective_date` date NOT NULL,
  `purchase_price` text COLLATE utf8mb4_unicode_ci,
  `retail_price` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `removed` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `product_prices_effective_date_product_id_index` (`effective_date`,`product_id`),
  KEY `product_prices_product_id_index` (`product_id`),
  CONSTRAINT `product_prices_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_prices`
--

LOCK TABLES `product_prices` WRITE;
/*!40000 ALTER TABLE `product_prices` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_prices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_size_activities`
--

DROP TABLE IF EXISTS `product_size_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_size_activities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `size_id` bigint unsigned NOT NULL,
  `size_name` text COLLATE utf8mb4_unicode_ci,
  `activity` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `actor_name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `before_values` json NOT NULL,
  `after_values` json DEFAULT NULL,
  `occurred_at` timestamp NOT NULL,
  `company` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `product_size_activities_product_id_foreign` (`product_id`),
  KEY `product_size_activities_size_id_index` (`size_id`),
  CONSTRAINT `product_size_activities_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_size_activities`
--

LOCK TABLES `product_size_activities` WRITE;
/*!40000 ALTER TABLE `product_size_activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_size_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_sizes`
--

DROP TABLE IF EXISTS `product_sizes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_sizes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `size` text COLLATE utf8mb4_unicode_ci,
  `difference` text COLLATE utf8mb4_unicode_ci,
  `weight` text COLLATE utf8mb4_unicode_ci,
  `cc` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `base_price` text COLLATE utf8mb4_unicode_ci,
  `manufacturer_price` text COLLATE utf8mb4_unicode_ci,
  `godown_price` text COLLATE utf8mb4_unicode_ci,
  `company` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `product_sizes_product_id_foreign` (`product_id`),
  CONSTRAINT `product_sizes_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_sizes`
--

LOCK TABLES `product_sizes` WRITE;
/*!40000 ALTER TABLE `product_sizes` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_sizes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productivity_item_user`
--

DROP TABLE IF EXISTS `productivity_item_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productivity_item_user` (
  `productivity_item_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`productivity_item_id`,`user_id`),
  KEY `productivity_item_user_user_id_foreign` (`user_id`),
  CONSTRAINT `productivity_item_user_productivity_item_id_foreign` FOREIGN KEY (`productivity_item_id`) REFERENCES `productivity_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `productivity_item_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productivity_item_user`
--

LOCK TABLES `productivity_item_user` WRITE;
/*!40000 ALTER TABLE `productivity_item_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `productivity_item_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productivity_items`
--

DROP TABLE IF EXISTS `productivity_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productivity_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `module` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'New',
  `created_by` bigint unsigned NOT NULL,
  `assigned_to` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `recurrence` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Once',
  `priority` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `carry_forward` tinyint(1) NOT NULL DEFAULT '0',
  `send_instant` tinyint(1) NOT NULL DEFAULT '0',
  `template_type` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `template_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_automation` tinyint(1) NOT NULL DEFAULT '0',
  `whatsapp_automation` tinyint(1) NOT NULL DEFAULT '0',
  `customer_email` text COLLATE utf8mb4_unicode_ci,
  `customer_mobile` text COLLATE utf8mb4_unicode_ci,
  `attachment_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment_mime` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment_size` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `productivity_items_created_by_foreign` (`created_by`),
  KEY `productivity_items_assigned_to_foreign` (`assigned_to`),
  KEY `productivity_items_module_index` (`module`),
  KEY `productivity_items_scheduled_at_index` (`scheduled_at`),
  KEY `productivity_items_status_index` (`status`),
  CONSTRAINT `productivity_items_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `productivity_items_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productivity_items`
--

LOCK TABLES `productivity_items` WRITE;
/*!40000 ALTER TABLE `productivity_items` DISABLE KEYS */;
INSERT INTO `productivity_items` VALUES (1,'Note','New',1,4,'testing',NULL,'2026-10-28 06:35:00','Once',NULL,0,0,NULL,NULL,0,0,'','',NULL,NULL,NULL,NULL,'2026-10-28 06:36:03','2026-10-28 06:36:03');
/*!40000 ALTER TABLE `productivity_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` text COLLATE utf8mb4_unicode_ci,
  `item_code` text COLLATE utf8mb4_unicode_ci,
  `hsn_sac` text COLLATE utf8mb4_unicode_ci,
  `item_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Product',
  `category` text COLLATE utf8mb4_unicode_ci,
  `unit` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tax_type` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `picture_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `permissions` json NOT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Administrator','Full CRM and account administration. Protected system role.',1,'[\"rates.view\", \"rates.create\", \"rates.edit\", \"rates.delete\", \"rates.export\", \"transporters.view\", \"transporters.create\", \"transporters.edit\", \"transporters.delete\", \"users.view\", \"users.create\", \"users.edit\", \"users.delete\", \"users.assign_roles\", \"users.reset_password\", \"roles.view\", \"roles.create\", \"roles.edit\", \"roles.delete\"]',1,'2026-09-30 16:37:12','2026-09-30 16:37:12'),(5,'Manager','Manage freight rates and transporters, including exports and deletion.',0,'[\"leads.view\", \"leads.view_all\", \"leads.create\", \"leads.edit\", \"leads.delete\", \"leads.stage\", \"leads.assign\", \"leads.followups\", \"leads.tasks\", \"leads.reminders\", \"leads.activity\", \"leads.email\", \"leads.lists\", \"leads.broadcast\", \"leads.automations\"]',1,'2026-10-23 17:06:54','2026-10-23 17:06:54');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `screen_time_entries`
--

DROP TABLE IF EXISTS `screen_time_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `screen_time_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `tracked_on` date NOT NULL,
  `seconds` int unsigned NOT NULL DEFAULT '0',
  `first_seen_at` timestamp NULL DEFAULT NULL,
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `last_active_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `screen_time_entries_user_id_tracked_on_unique` (`user_id`,`tracked_on`),
  KEY `screen_time_entries_tracked_on_index` (`tracked_on`),
  CONSTRAINT `screen_time_entries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `screen_time_entries`
--

LOCK TABLES `screen_time_entries` WRITE;
/*!40000 ALTER TABLE `screen_time_entries` DISABLE KEYS */;
INSERT INTO `screen_time_entries` VALUES (1,1,'2026-10-23',170,'2026-10-23 17:43:25',NULL,'2026-10-23 17:59:38','2026-10-23 17:43:25','2026-10-23 17:59:38'),(2,1,'2026-10-24',7047,'2026-10-24 06:24:51',NULL,'2026-10-24 12:22:24','2026-10-24 06:24:51','2026-10-24 12:22:24'),(3,1,'2026-10-25',927,'2026-10-25 17:43:03','2026-10-25 18:29:51','2026-10-25 18:29:51','2026-10-25 17:43:03','2026-10-25 18:29:51'),(4,1,'2026-10-26',6674,'2026-10-25 18:30:12',NULL,'2026-10-26 11:52:14','2026-10-25 18:30:12','2026-10-26 11:52:14'),(5,4,'2026-10-26',36,'2026-10-26 08:14:05',NULL,'2026-10-26 08:14:45','2026-10-26 08:14:05','2026-10-26 08:14:45'),(6,1,'2026-10-28',5162,'2026-10-27 19:57:18',NULL,'2026-10-28 10:25:31','2026-10-27 19:57:18','2026-10-28 10:25:31'),(7,1,'2026-10-29',326,'2026-10-28 19:29:57',NULL,'2026-10-29 02:57:39','2026-10-28 19:29:57','2026-10-29 02:57:39'),(8,1,'2026-10-30',420,'2026-10-30 07:37:21',NULL,'2026-10-30 10:12:16','2026-10-30 07:37:21','2026-10-30 10:12:16'),(9,1,'2026-10-31',8592,'2026-10-31 06:20:50',NULL,'2026-10-31 18:05:37','2026-10-31 06:20:50','2026-10-31 18:05:37'),(10,1,'2026-11-01',296,'2026-11-01 13:17:10',NULL,'2026-11-01 16:13:21','2026-11-01 13:17:10','2026-11-01 16:13:21');
/*!40000 ALTER TABLE `screen_time_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sidebar_group_items`
--

DROP TABLE IF EXISTS `sidebar_group_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sidebar_group_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sidebar_group_id` bigint unsigned NOT NULL,
  `item_key` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sidebar_group_items_item_key_unique` (`item_key`),
  KEY `sidebar_group_items_sidebar_group_id_foreign` (`sidebar_group_id`),
  CONSTRAINT `sidebar_group_items_sidebar_group_id_foreign` FOREIGN KEY (`sidebar_group_id`) REFERENCES `sidebar_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sidebar_group_items`
--

LOCK TABLES `sidebar_group_items` WRITE;
/*!40000 ALTER TABLE `sidebar_group_items` DISABLE KEYS */;
INSERT INTO `sidebar_group_items` VALUES (1,2,'leads',23,'2026-10-31 06:39:14','2026-11-01 13:25:11'),(2,2,'pipeline',24,'2026-10-31 06:39:14','2026-11-01 13:25:11'),(3,2,'lead-groups',25,'2026-10-31 06:39:14','2026-11-01 13:25:11'),(4,2,'lead-work',26,'2026-10-31 06:42:39','2026-11-01 13:25:11');
/*!40000 ALTER TABLE `sidebar_group_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sidebar_groups`
--

DROP TABLE IF EXISTS `sidebar_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sidebar_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` int unsigned NOT NULL DEFAULT '0',
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sidebar_groups_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sidebar_groups`
--

LOCK TABLES `sidebar_groups` WRITE;
/*!40000 ALTER TABLE `sidebar_groups` DISABLE KEYS */;
INSERT INTO `sidebar_groups` VALUES (2,'Manager',1,2,'2026-10-31 06:39:14','2026-10-31 06:42:39');
/*!40000 ALTER TABLE `sidebar_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sidebar_item_orders`
--

DROP TABLE IF EXISTS `sidebar_item_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sidebar_item_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_key` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sidebar_item_orders_item_key_unique` (`item_key`),
  KEY `sidebar_item_orders_position_index` (`position`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sidebar_item_orders`
--

LOCK TABLES `sidebar_item_orders` WRITE;
/*!40000 ALTER TABLE `sidebar_item_orders` DISABLE KEYS */;
INSERT INTO `sidebar_item_orders` VALUES (1,'dashboard',1,'2026-11-01 13:16:55','2026-11-01 13:21:23'),(2,'leads',23,'2026-11-01 13:16:55','2026-11-01 13:24:08'),(3,'lead-source-report',6,'2026-11-01 13:16:55','2026-11-01 13:24:11'),(4,'pipeline',24,'2026-11-01 13:16:55','2026-11-01 13:24:08'),(5,'lead-groups',25,'2026-11-01 13:16:55','2026-11-01 13:24:08'),(6,'list-manager',2,'2026-11-01 13:16:55','2026-11-01 13:24:11'),(7,'tasks',3,'2026-11-01 13:16:55','2026-11-01 13:24:11'),(8,'reminder',4,'2026-11-01 13:16:55','2026-11-01 13:25:11'),(9,'meeting',5,'2026-11-01 13:16:55','2026-11-01 13:25:11'),(10,'todo',7,'2026-11-01 13:16:55','2026-11-01 13:24:07'),(11,'note',8,'2026-11-01 13:16:55','2026-11-01 13:24:07'),(12,'lead-work',26,'2026-11-01 13:16:55','2026-11-01 13:24:08'),(13,'activities',9,'2026-11-01 13:16:55','2026-11-01 13:24:07'),(14,'content-manager',10,'2026-11-01 13:16:55','2026-11-01 13:24:07'),(15,'products',11,'2026-11-01 13:16:55','2026-11-01 13:24:07'),(16,'product-logs',12,'2026-11-01 13:16:55','2026-11-01 13:24:07'),(17,'sizes',13,'2026-11-01 13:16:55','2026-11-01 13:24:07'),(18,'add-product',14,'2026-11-01 13:16:55','2026-11-01 13:24:07'),(19,'rates',15,'2026-11-01 13:16:55','2026-11-01 13:24:08'),(20,'transporters',16,'2026-11-01 13:16:55','2026-11-01 13:24:08'),(21,'access',17,'2026-11-01 13:16:55','2026-11-01 13:24:08'),(22,'storage',18,'2026-11-01 13:16:55','2026-11-01 13:24:08'),(23,'screen-time',19,'2026-11-01 13:16:55','2026-11-01 13:24:08'),(24,'chat',20,'2026-11-01 13:16:55','2026-11-01 13:24:08'),(25,'notifications',21,'2026-11-01 13:16:55','2026-11-01 13:24:08'),(26,'account',22,'2026-11-01 13:16:55','2026-11-01 13:24:08');
/*!40000 ALTER TABLE `sidebar_item_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `task_statuses`
--

DROP TABLE IF EXISTS `task_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_statuses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#40358e',
  `position` int unsigned NOT NULL DEFAULT '0',
  `is_completed` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_statuses_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_statuses`
--

LOCK TABLES `task_statuses` WRITE;
/*!40000 ALTER TABLE `task_statuses` DISABLE KEYS */;
INSERT INTO `task_statuses` VALUES (1,'New','#08798b',1,0,'2026-10-28 02:07:32','2026-10-28 02:07:32'),(2,'In Feedback','#40358e',2,0,'2026-10-28 02:07:32','2026-10-28 02:07:32'),(3,'Completed','#397400',3,1,'2026-10-28 02:07:32','2026-10-28 02:07:32'),(4,'Rejected','#ed0027',4,0,'2026-10-28 02:07:32','2026-10-28 02:07:32'),(5,'Processing','#40358e',5,0,'2026-10-28 02:07:32','2026-10-28 02:07:32'),(6,'test','#348d5f',6,0,'2026-10-28 03:20:52','2026-10-28 03:20:52');
/*!40000 ALTER TABLE `task_statuses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transporters`
--

DROP TABLE IF EXISTS `transporters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transporters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `contact_person` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gst_number` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `transporters_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transporters`
--

LOCK TABLES `transporters` WRITE;
/*!40000 ALTER TABLE `transporters` DISABLE KEYS */;
/*!40000 ALTER TABLE `transporters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_login_events`
--

DROP TABLE IF EXISTS `user_login_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_login_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `logged_in_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_login_events_user_id_logged_in_at_index` (`user_id`,`logged_in_at`),
  KEY `user_login_events_logged_in_at_index` (`logged_in_at`),
  CONSTRAINT `user_login_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_login_events`
--

LOCK TABLES `user_login_events` WRITE;
/*!40000 ALTER TABLE `user_login_events` DISABLE KEYS */;
INSERT INTO `user_login_events` VALUES (1,1,'2026-10-24 06:24:50','2026-10-24 06:24:50','2026-10-24 06:24:50'),(2,1,'2026-10-24 11:41:26','2026-10-24 11:41:26','2026-10-24 11:41:26'),(3,1,'2026-10-25 17:43:01','2026-10-25 17:43:01','2026-10-25 17:43:01'),(4,1,'2026-10-25 18:30:10','2026-10-25 18:30:10','2026-10-25 18:30:10'),(5,1,'2026-10-26 06:59:17','2026-10-26 06:59:17','2026-10-26 06:59:17'),(6,1,'2026-10-26 08:12:52','2026-10-26 08:12:52','2026-10-26 08:12:52'),(7,4,'2026-10-26 08:14:03','2026-10-26 08:14:03','2026-10-26 08:14:03'),(8,1,'2026-10-26 08:14:50','2026-10-26 08:14:50','2026-10-26 08:14:50'),(9,1,'2026-10-26 10:27:28','2026-10-26 10:27:28','2026-10-26 10:27:28'),(10,1,'2026-10-27 19:57:17','2026-10-27 19:57:17','2026-10-27 19:57:17'),(11,1,'2026-10-28 01:30:53','2026-10-28 01:30:53','2026-10-28 01:30:53'),(12,1,'2026-10-28 02:38:39','2026-10-28 02:38:39','2026-10-28 02:38:39'),(13,1,'2026-10-28 06:13:29','2026-10-28 06:13:29','2026-10-28 06:13:29'),(14,1,'2026-10-28 19:29:56','2026-10-28 19:29:56','2026-10-28 19:29:56'),(15,1,'2026-10-29 01:23:58','2026-10-29 01:23:58','2026-10-29 01:23:58'),(16,1,'2026-10-29 02:48:47','2026-10-29 02:48:47','2026-10-29 02:48:47'),(17,1,'2026-10-30 07:37:19','2026-10-30 07:37:19','2026-10-30 07:37:19'),(18,1,'2026-10-30 08:35:09','2026-10-30 08:35:09','2026-10-30 08:35:09'),(19,1,'2026-10-30 10:08:07','2026-10-30 10:08:07','2026-10-30 10:08:07'),(20,1,'2026-10-31 06:20:40','2026-10-31 06:20:40','2026-10-31 06:20:40'),(21,1,'2026-10-31 10:36:22','2026-10-31 10:36:22','2026-10-31 10:36:22'),(22,1,'2026-10-31 16:43:17','2026-10-31 16:43:17','2026-10-31 16:43:17'),(23,1,'2026-11-01 13:17:06','2026-11-01 13:17:06','2026-11-01 13:17:06'),(24,1,'2026-11-01 16:02:48','2026-11-01 16:02:48','2026-11-01 16:02:48');
/*!40000 ALTER TABLE `user_login_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `session_version` int unsigned NOT NULL DEFAULT '1',
  `version` int unsigned NOT NULL DEFAULT '1',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `product_order` json DEFAULT NULL,
  `product_slots` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_phone_unique` (`phone`),
  KEY `users_role_id_foreign` (`role_id`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Gagandeep Steels Administrator','admin@gagandeepsteels.local',NULL,'user-avatars/IRKkGDU0ZTStyalrGHWKFQR2b4HUnL9lkwTP89lg.jpg','$2y$12$ySbeL4GIF3uhEU4c4C1DsOxkUEdQuGD1VgaQZbawv0ik.G/DyNR7C',1,1,3,4,'2026-11-01 16:02:48','7Wrz5mz7bAHYX5bkOZ38okZPA2w4D11ReCD92oFkRJi1nYTAJS2xZA9Efl9F','2026-09-30 16:41:26','2026-11-01 16:02:48','[1, 2, 5, 4, 3]','[1, 2, 3, 4, 5, null, null]'),(4,'Logic Integratorz','logicintegratorz@gmail.com',NULL,NULL,'$2y$12$HiDs1Zq4MQWEs1O5TQYNJOI/Hf2NrRolQu.IVcRINQ.NM0nQMnWS2',5,1,2,2,'2026-10-26 08:14:03',NULL,'2026-10-23 17:09:26','2026-10-26 08:14:03',NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'gagandeep_steels'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-01 21:44:20
