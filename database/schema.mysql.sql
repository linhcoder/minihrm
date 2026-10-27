
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
DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `employee_id` int unsigned NOT NULL,
  `schedule_id` int unsigned DEFAULT NULL,
  `kind` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `occurred_at` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `local_timestamp` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `latitude` decimal(12,6) DEFAULT NULL,
  `longitude` decimal(12,6) DEFAULT NULL,
  `accuracy` bigint NOT NULL DEFAULT '0',
  `mock_location` bigint NOT NULL DEFAULT '0',
  `offline` bigint NOT NULL DEFAULT '0',
  `status` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `idempotency_key` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `device_id` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_id_idempotency_key` (`tenant_id`,`idempotency_key`),
  UNIQUE KEY `attendance_tenant_object` (`tenant_id`,`id`),
  KEY `tenant_id_deleted_at` (`tenant_id`,`deleted_at`),
  KEY `fk_attendance_employee_id` (`tenant_id`,`employee_id`),
  KEY `fk_attendance_schedule_id` (`tenant_id`,`schedule_id`),
  CONSTRAINT `attendance_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_attendance_employee_id` FOREIGN KEY (`tenant_id`, `employee_id`) REFERENCES `employees` (`tenant_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_attendance_schedule_id` FOREIGN KEY (`tenant_id`, `schedule_id`) REFERENCES `schedules` (`tenant_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=110 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attendance_corrections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance_corrections` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `employee_id` int unsigned NOT NULL,
  `timesheet_id` int unsigned NOT NULL,
  `original_values` text COLLATE utf8mb4_general_ci,
  `new_values` text COLLATE utf8mb4_general_ci NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `approved_by` int unsigned DEFAULT NULL,
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendance_corrections_tenant_object` (`tenant_id`,`id`),
  KEY `tenant_id_deleted_at` (`tenant_id`,`deleted_at`),
  KEY `fk_attendance_corrections_employee_id` (`tenant_id`,`employee_id`),
  KEY `fk_attendance_corrections_timesheet_id` (`tenant_id`,`timesheet_id`),
  CONSTRAINT `attendance_corrections_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_attendance_corrections_employee_id` FOREIGN KEY (`tenant_id`, `employee_id`) REFERENCES `employees` (`tenant_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_attendance_corrections_timesheet_id` FOREIGN KEY (`tenant_id`, `timesheet_id`) REFERENCES `timesheets` (`tenant_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `actor_id` int unsigned DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `resource` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `record_id` bigint NOT NULL DEFAULT '0',
  `before_value` text COLLATE utf8mb4_general_ci,
  `after_value` text COLLATE utf8mb4_general_ci,
  `ip` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_id_deleted_at` (`tenant_id`,`deleted_at`),
  CONSTRAINT `audit_logs_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=362 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `auth_challenges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_challenges` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `kind` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `token_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `expires` bigint NOT NULL DEFAULT '0',
  `used` bigint NOT NULL DEFAULT '0',
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `fk_auth_challenges_user` (`user_id`),
  CONSTRAINT `fk_auth_challenges_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `auth_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_tokens` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `access_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `refresh_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `access_expires` bigint NOT NULL DEFAULT '0',
  `refresh_expires` bigint NOT NULL DEFAULT '0',
  `revoked` bigint NOT NULL DEFAULT '0',
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `access_hash` (`access_hash`),
  UNIQUE KEY `refresh_hash` (`refresh_hash`),
  KEY `fk_auth_tokens_user` (`user_id`),
  CONSTRAINT `fk_auth_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branches` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `latitude` decimal(12,6) DEFAULT NULL,
  `longitude` decimal(12,6) DEFAULT NULL,
  `radius` bigint NOT NULL DEFAULT '0',
  `timezone` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `attendance_policy` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `wifi_bssid` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `active` bigint NOT NULL DEFAULT '0',
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branches_tenant_object` (`tenant_id`,`id`),
  KEY `tenant_id_deleted_at` (`tenant_id`,`deleted_at`),
  CONSTRAINT `branches_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `manager_id` int unsigned DEFAULT NULL,
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_tenant_object` (`tenant_id`,`id`),
  KEY `tenant_id_deleted_at` (`tenant_id`,`deleted_at`),
  KEY `fk_departments_manager_id` (`tenant_id`,`manager_id`),
  CONSTRAINT `departments_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_departments_manager_id` FOREIGN KEY (`tenant_id`, `manager_id`) REFERENCES `employees` (`tenant_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `birth_date` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `department_id` int unsigned DEFAULT NULL,
  `branch_id` int unsigned DEFAULT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `avatar_file_id` int unsigned DEFAULT NULL,
  `job_title` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `employment_type` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hire_date` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `salary_type` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `base_salary` bigint NOT NULL DEFAULT '0',
  `allowance` bigint NOT NULL DEFAULT '0',
  `emergency_contact` text COLLATE utf8mb4_general_ci,
  `notes` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_id_code` (`tenant_id`,`code`),
  UNIQUE KEY `employees_tenant_object` (`tenant_id`,`id`),
  KEY `tenant_id_deleted_at` (`tenant_id`,`deleted_at`),
  KEY `fk_employees_department_id` (`tenant_id`,`department_id`),
  KEY `fk_employees_branch_id` (`tenant_id`,`branch_id`),
  KEY `fk_employees_avatar_file_id` (`tenant_id`,`avatar_file_id`),
  CONSTRAINT `employees_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_employees_avatar_file_id` FOREIGN KEY (`tenant_id`, `avatar_file_id`) REFERENCES `files` (`tenant_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_employees_branch_id` FOREIGN KEY (`tenant_id`, `branch_id`) REFERENCES `branches` (`tenant_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_employees_department_id` FOREIGN KEY (`tenant_id`, `department_id`) REFERENCES `departments` (`tenant_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `files` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `employee_id` int unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `storage_key` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `mime` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `size` bigint NOT NULL DEFAULT '0',
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `files_tenant_object` (`tenant_id`,`id`),
  KEY `tenant_id_deleted_at` (`tenant_id`,`deleted_at`),
  KEY `fk_files_employee_id` (`tenant_id`,`employee_id`),
  CONSTRAINT `files_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_files_employee_id` FOREIGN KEY (`tenant_id`, `employee_id`) REFERENCES `employees` (`tenant_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `holidays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `holidays` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `work_date` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `holidays_tenant_object` (`tenant_id`,`id`),
  KEY `tenant_id_deleted_at` (`tenant_id`,`deleted_at`),
  CONSTRAINT `holidays_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `idempotency`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `idempotency` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `request_key` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `request_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `response` text COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_id_user_id_request_key` (`tenant_id`,`user_id`,`request_key`),
  KEY `tenant_id_deleted_at` (`tenant_id`,`deleted_at`),
  CONSTRAINT `idempotency_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `subscription_id` int unsigned NOT NULL,
  `amount` bigint NOT NULL DEFAULT '0',
  `status` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `due_date` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `period_end` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `plan_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_id_deleted_at` (`tenant_id`,`deleted_at`),
  KEY `fk_invoice_plan` (`plan_id`),
  CONSTRAINT `fk_invoice_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`),
  CONSTRAINT `invoices_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `event_key` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `channel` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `payload` text COLLATE utf8mb4_general_ci NOT NULL,
  `attempts` bigint NOT NULL DEFAULT '0',
  `available_at` bigint NOT NULL DEFAULT '0',
  `reserved_at` bigint NOT NULL DEFAULT '0',
  `completed_at` bigint NOT NULL DEFAULT '0',
  `last_error` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_key_channel` (`event_key`,`channel`),
  KEY `tenant_id_deleted_at` (`tenant_id`,`deleted_at`),
  CONSTRAINT `jobs_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_requests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `employee_id` int unsigned NOT NULL,
  `leave_type_id` int unsigned NOT NULL,
  `start_date` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `end_date` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `half_day` bigint NOT NULL DEFAULT '0',
  `reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `days` decimal(12,6) DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `approved_by` int unsigned DEFAULT NULL,
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `leave_requests_tenant_object` (`tenant_id`,`id`),
  KEY `tenant_id_deleted_at` (`tenant_id`,`deleted_at`),
  KEY `fk_leave_requests_employee_id` (`tenant_id`,`employee_id`),
  KEY `fk_leave_requests_leave_type_id` (`tenant_id`,`leave_type_id`),
  CONSTRAINT `fk_leave_requests_employee_id` FOREIGN KEY (`tenant_id`, `employee_id`) REFERENCES `employees` (`tenant_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_leave_requests_leave_type_id` FOREIGN KEY (`tenant_id`, `leave_type_id`) REFERENCES `leave_types` (`tenant_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `leave_requests_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `leave_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `paid` bigint NOT NULL DEFAULT '0',
  `annual_days` bigint NOT NULL DEFAULT '0',
  `carryover_days` bigint NOT NULL DEFAULT '0',
  `effective_from` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `leave_types_tenant_object` (`tenant_id`,`id`),
  KEY `tenant_id_deleted_at` (`tenant_id`,`deleted_at`),
  CONSTRAINT `leave_types_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `legal_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `legal_rules` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `effective_from` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `effective_to` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rules` text COLLATE utf8mb4_general_ci NOT NULL,
  `verified` bigint NOT NULL DEFAULT '0',
  `reference` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `legal_rules_tenant_object` (`tenant_id`,`id`),
  KEY `tenant_id_deleted_at` (`tenant_id`,`deleted_at`),
  CONSTRAINT `legal_rules_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `identity` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `attempted_at` bigint NOT NULL DEFAULT '0',
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `body` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `read_at` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `event_key` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_id_event_key_user_id` (`tenant_id`,`event_key`,`user_id`),
  UNIQUE KEY `notifications_tenant_object` (`tenant_id`,`id`),
  KEY `tenant_id_deleted_at` (`tenant_id`,`deleted_at`),
  CONSTRAINT `notifications_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `invoice_id` int unsigned NOT NULL,
  `amount` bigint NOT NULL DEFAULT '0',
  `provider` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `reference` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `verified_by` int unsigned DEFAULT NULL,
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `provider_reference` (`provider`,`reference`),
  KEY `tenant_id_deleted_at` (`tenant_id`,`deleted_at`),
  CONSTRAINT `payments_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payroll_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_adjustments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `employee_id` int unsigned NOT NULL,
  `payroll_period_id` int unsigned NOT NULL,
  `kind` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `amount` bigint NOT NULL DEFAULT '0',
  `reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `approved_by` int unsigned DEFAULT NULL,
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payroll_adjustments_tenant_object` (`tenant_id`,`id`),
  KEY `tenant_id_deleted_at` (`tenant_id`,`deleted_at`),
  KEY `fk_payroll_adjustments_employee_id` (`tenant_id`,`employee_id`),
  KEY `fk_payroll_adjustments_payroll_period_id` (`tenant_id`,`payroll_period_id`),
  CONSTRAINT `fk_payroll_adjustments_employee_id` FOREIGN KEY (`tenant_id`, `employee_id`) REFERENCES `employees` (`tenant_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_payroll_adjustments_payroll_period_id` FOREIGN KEY (`tenant_id`, `payroll_period_id`) REFERENCES `payroll_periods` (`tenant_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `payroll_adjustments_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payroll_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_periods` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `start_date` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `end_date` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `branch_id` int unsigned DEFAULT NULL,
  `department_id` int unsigned DEFAULT NULL,
  `policy_id` int unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `total_net` bigint NOT NULL DEFAULT '0',
  `calculation_version` bigint NOT NULL DEFAULT '0',
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payroll_periods_tenant_object` (`tenant_id`,`id`),
  KEY `tenant_id_deleted_at` (`tenant_id`,`deleted_at`),
  KEY `fk_payroll_periods_branch_id` (`tenant_id`,`branch_id`),
  KEY `fk_payroll_periods_department_id` (`tenant_id`,`department_id`),
  KEY `fk_payroll_periods_policy_id` (`tenant_id`,`policy_id`),
  CONSTRAINT `fk_payroll_periods_branch_id` FOREIGN KEY (`tenant_id`, `branch_id`) REFERENCES `branches` (`tenant_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_payroll_periods_department_id` FOREIGN KEY (`tenant_id`, `department_id`) REFERENCES `departments` (`tenant_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_payroll_periods_policy_id` FOREIGN KEY (`tenant_id`, `policy_id`) REFERENCES `salary_policies` (`tenant_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `payroll_periods_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payslips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payslips` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `employee_id` int unsigned NOT NULL,
  `payroll_period_id` int unsigned NOT NULL,
  `gross` bigint NOT NULL DEFAULT '0',
  `deductions` bigint NOT NULL DEFAULT '0',
  `employer_cost` bigint NOT NULL DEFAULT '0',
  `statutory` bigint NOT NULL DEFAULT '0',
  `advances` bigint NOT NULL DEFAULT '0',
  `net` bigint NOT NULL DEFAULT '0',
  `inputs` text COLLATE utf8mb4_general_ci,
  `breakdown` text COLLATE utf8mb4_general_ci,
  `status` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_id_employee_id_payroll_period_id` (`tenant_id`,`employee_id`,`payroll_period_id`),
  UNIQUE KEY `payslips_tenant_object` (`tenant_id`,`id`),
  KEY `tenant_id_deleted_at` (`tenant_id`,`deleted_at`),
  KEY `fk_payslips_payroll_period_id` (`tenant_id`,`payroll_period_id`),
  CONSTRAINT `fk_payslips_employee_id` FOREIGN KEY (`tenant_id`, `employee_id`) REFERENCES `employees` (`tenant_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_payslips_payroll_period_id` FOREIGN KEY (`tenant_id`, `payroll_period_id`) REFERENCES `payroll_periods` (`tenant_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `payslips_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `plans` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `price` bigint NOT NULL DEFAULT '0',
  `employee_limit` bigint NOT NULL DEFAULT '0',
  `features` text COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `platform_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `platform_audit` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `actor_id` int unsigned NOT NULL,
  `action` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `payload` text COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `privacy_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `privacy_requests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `kind` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `privacy_requests_tenant_object` (`tenant_id`,`id`),
  KEY `tenant_id_deleted_at` (`tenant_id`,`deleted_at`),
  CONSTRAINT `privacy_requests_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `push_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `push_devices` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `token` varchar(512) COLLATE utf8mb4_general_ci NOT NULL,
  `token_hash` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `push_devices_tenant_id_foreign` (`tenant_id`),
  KEY `push_devices_user_id_foreign` (`user_id`),
  CONSTRAINT `push_devices_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `push_devices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `permissions` text COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `salary_advances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salary_advances` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `employee_id` int unsigned NOT NULL,
  `amount` bigint NOT NULL DEFAULT '0',
  `request_date` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payroll_period_id` int unsigned DEFAULT NULL,
  `approved_by` int unsigned DEFAULT NULL,
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `salary_advances_tenant_object` (`tenant_id`,`id`),
  KEY `tenant_id_deleted_at` (`tenant_id`,`deleted_at`),
  KEY `fk_salary_advances_employee_id` (`tenant_id`,`employee_id`),
  KEY `fk_salary_advances_payroll_period_id` (`tenant_id`,`payroll_period_id`),
  CONSTRAINT `fk_salary_advances_employee_id` FOREIGN KEY (`tenant_id`, `employee_id`) REFERENCES `employees` (`tenant_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_salary_advances_payroll_period_id` FOREIGN KEY (`tenant_id`, `payroll_period_id`) REFERENCES `payroll_periods` (`tenant_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `salary_advances_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `salary_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salary_policies` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `effective_from` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `standard_days` bigint NOT NULL DEFAULT '0',
  `standard_minutes` bigint NOT NULL DEFAULT '0',
  `overtime_multiplier_bps` bigint NOT NULL DEFAULT '0',
  `version` bigint NOT NULL DEFAULT '0',
  `components` text COLLATE utf8mb4_general_ci,
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `salary_policies_tenant_object` (`tenant_id`,`id`),
  KEY `tenant_id_deleted_at` (`tenant_id`,`deleted_at`),
  CONSTRAINT `salary_policies_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schedules` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `employee_id` int unsigned NOT NULL,
  `shift_id` int unsigned NOT NULL,
  `branch_id` int unsigned NOT NULL,
  `work_date` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_id_employee_id_shift_id_work_date` (`tenant_id`,`employee_id`,`shift_id`,`work_date`),
  UNIQUE KEY `schedules_tenant_object` (`tenant_id`,`id`),
  KEY `tenant_id_deleted_at` (`tenant_id`,`deleted_at`),
  KEY `fk_schedules_shift_id` (`tenant_id`,`shift_id`),
  KEY `fk_schedules_branch_id` (`tenant_id`,`branch_id`),
  CONSTRAINT `fk_schedules_branch_id` FOREIGN KEY (`tenant_id`, `branch_id`) REFERENCES `branches` (`tenant_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_schedules_employee_id` FOREIGN KEY (`tenant_id`, `employee_id`) REFERENCES `employees` (`tenant_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_schedules_shift_id` FOREIGN KEY (`tenant_id`, `shift_id`) REFERENCES `shifts` (`tenant_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `schedules_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `value` text COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shift_swaps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_swaps` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `employee_id` int unsigned NOT NULL,
  `schedule_id` int unsigned NOT NULL,
  `target_schedule_id` int unsigned NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shift_swaps_tenant_object` (`tenant_id`,`id`),
  KEY `tenant_id_deleted_at` (`tenant_id`,`deleted_at`),
  KEY `fk_shift_swaps_employee_id` (`tenant_id`,`employee_id`),
  KEY `fk_shift_swaps_schedule_id` (`tenant_id`,`schedule_id`),
  KEY `fk_shift_swaps_target_schedule_id` (`tenant_id`,`target_schedule_id`),
  CONSTRAINT `fk_shift_swaps_employee_id` FOREIGN KEY (`tenant_id`, `employee_id`) REFERENCES `employees` (`tenant_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_shift_swaps_schedule_id` FOREIGN KEY (`tenant_id`, `schedule_id`) REFERENCES `schedules` (`tenant_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_shift_swaps_target_schedule_id` FOREIGN KEY (`tenant_id`, `target_schedule_id`) REFERENCES `schedules` (`tenant_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `shift_swaps_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shifts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `kind` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `start_time` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `end_time` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `break_minutes` bigint NOT NULL DEFAULT '0',
  `paid_break` bigint NOT NULL DEFAULT '0',
  `grace_minutes` bigint NOT NULL DEFAULT '0',
  `earliest_minutes` bigint NOT NULL DEFAULT '0',
  `latest_minutes` bigint NOT NULL DEFAULT '0',
  `overtime_eligible` bigint NOT NULL DEFAULT '0',
  `working_days` text COLLATE utf8mb4_general_ci,
  `effective_from` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `effective_to` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shifts_tenant_object` (`tenant_id`,`id`),
  KEY `tenant_id_deleted_at` (`tenant_id`,`deleted_at`),
  CONSTRAINT `shifts_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscriptions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `plan_id` int unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `starts_at` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ends_at` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `grace_until` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `employee_limit` bigint NOT NULL DEFAULT '0',
  `cancel_at_end` bigint NOT NULL DEFAULT '0',
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_id` (`tenant_id`),
  KEY `tenant_id_deleted_at` (`tenant_id`,`deleted_at`),
  KEY `fk_subscription_plan` (`plan_id`),
  CONSTRAINT `fk_subscription_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`),
  CONSTRAINT `subscriptions_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_memberships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_memberships` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `branch_ids` text COLLATE utf8mb4_general_ci,
  `permissions` text COLLATE utf8mb4_general_ci,
  `employee_id` bigint NOT NULL DEFAULT '0',
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_id_user_id` (`tenant_id`,`user_id`),
  KEY `tenant_id_deleted_at` (`tenant_id`,`deleted_at`),
  KEY `fk_tenant_memberships_user` (`user_id`),
  CONSTRAINT `fk_tenant_memberships_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `tenant_memberships_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenants` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `business_type` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `timezone` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `working_days` text COLLATE utf8mb4_general_ci,
  `payroll_day` bigint NOT NULL DEFAULT '0',
  `onboarded` bigint NOT NULL DEFAULT '0',
  `status` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `retention_days` bigint NOT NULL DEFAULT '0',
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `timesheets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `timesheets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int unsigned NOT NULL,
  `employee_id` int unsigned NOT NULL,
  `schedule_id` int unsigned NOT NULL,
  `work_date` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `check_in` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `check_out` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `worked_minutes` bigint NOT NULL DEFAULT '0',
  `late_minutes` bigint NOT NULL DEFAULT '0',
  `early_minutes` bigint NOT NULL DEFAULT '0',
  `overtime_minutes` bigint NOT NULL DEFAULT '0',
  `status` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `review_status` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `approved_by` int unsigned DEFAULT NULL,
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_id_schedule_id` (`tenant_id`,`schedule_id`),
  UNIQUE KEY `timesheets_tenant_object` (`tenant_id`,`id`),
  KEY `tenant_id_deleted_at` (`tenant_id`,`deleted_at`),
  KEY `fk_timesheets_employee_id` (`tenant_id`,`employee_id`),
  CONSTRAINT `fk_timesheets_employee_id` FOREIGN KEY (`tenant_id`, `employee_id`) REFERENCES `employees` (`tenant_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_timesheets_schedule_id` FOREIGN KEY (`tenant_id`, `schedule_id`) REFERENCES `schedules` (`tenant_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `timesheets_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `verified_at` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_super` bigint NOT NULL DEFAULT '0',
  `active` bigint NOT NULL DEFAULT '0',
  `created_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

