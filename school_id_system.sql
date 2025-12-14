-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 14, 2025 at 05:07 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `school_id_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `target_id` int(11) DEFAULT NULL,
  `target_type` enum('student','user','admin') DEFAULT 'student',
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `admin_id`, `action`, `target_id`, `target_type`, `old_values`, `new_values`, `ip_address`, `created_at`) VALUES
(1, 2, 'bulk_status', 0, 'user', '{\"count\":3}', '{\"new_status\":\"approved\"}', '::1', '2025-11-16 07:23:42'),
(2, 2, 'insert', 3, 'user', NULL, '{\"full_name\":\"Kurt Andrew Dapat\",\"email\":\"kadapat@kld.edu.ph\",\"role\":\"student\"}', '::1', '2025-11-17 10:28:06'),
(3, 2, 'bulk_generate_id', 1, 'student', NULL, '{\"id_number\":\"2025100000\",\"request_id\":\"2\",\"digital_file\":\"ajcaballero@kld.edu.ph_2025100000.pdf\"}', '::1', '2025-11-19 17:43:00');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `record_id` int(11) NOT NULL,
  `table_name` varchar(100) NOT NULL,
  `old_data` longtext DEFAULT NULL,
  `new_data` longtext DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `action`, `record_id`, `table_name`, `old_data`, `new_data`, `user_id`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 'insert', 123, 'student', NULL, '{\"first_name\":\"John\",\"last_name\":\"Doe\",\"email\":\"john@example.com\"}', 1, '', '127.0.0.1', 'Test Script', '2025-11-20 10:28:42'),
(2, 'update', 123, 'student', '{\"first_name\":\"John\"}', '{\"first_name\":\"Jonathan\"}', 1, '', '127.0.0.1', 'Test Script', '2025-11-20 10:28:42'),
(3, 'delete', 123, 'student', '{\"first_name\":\"Jonathan\",\"last_name\":\"Doe\"}', NULL, 1, '', '127.0.0.1', 'Test Script', '2025-11-20 10:28:42'),
(4, 'delete', 16, 'student', '{\"id\":16,\"student_id\":null,\"email\":\"tcaballero2@kld.edu.ph\",\"first_name\":\"Test2\",\"last_name\":\"Caballero2\",\"year_level\":null,\"course\":null,\"contact_number\":null,\"address\":null,\"photo\":null,\"password\":\"\",\"created_at\":\"2025-11-20 18:10:09\",\"profile_completed\":0,\"emergency_contact\":null,\"dob\":null,\"gender\":null,\"blood_type\":null,\"signature\":null,\"cor\":null,\"digital_id_generated_at\":null,\"digital_id_path\":null,\"deleted_at\":null}', NULL, 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-20 10:29:18'),
(5, 'reset_password', 5, 'user', '{\"user_id\":5,\"full_name\":\"usertest testing\",\"email\":\"utesting@kld.edu.ph\",\"password_hash\":\"$2y$10$HWkHtK\\/aR\\/h5AhH0CTHOHeCSaO\\/EW8TMy4\\/tHwUd4z5rwPIfxKsxe\",\"role\":\"student\",\"is_verified\":0,\"verification_token\":null,\"created_at\":\"2025-11-20 18:51:16\",\"status\":\"pending\",\"deleted_at\":null,\"verified\":0}', '{\"password_reset\":true}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-20 10:58:56'),
(6, 'reset_password', 5, 'user', '{\"user_id\":5,\"full_name\":\"usertest testing\",\"email\":\"utesting@kld.edu.ph\",\"password_hash\":\"$2y$10$wJ5MxFZZ2XDfYeWUK7N2j.6R\\/HaULQGLztMe6npDEiOAcfGcPiXO.\",\"role\":\"student\",\"is_verified\":0,\"verification_token\":null,\"created_at\":\"2025-11-20 18:51:16\",\"status\":\"pending\",\"deleted_at\":null,\"verified\":0}', '{\"password_reset\":true}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-23 12:30:12'),
(7, 'insert', 23, 'student', NULL, '{\"first_name\":\"Rhamuel\",\"last_name\":\"Gonzales\",\"email\":\"rgonzales@kld.edu.ph\"}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-23 13:16:15'),
(8, 'insert', 7, 'user', NULL, '{\"full_name\":\"Jared Padida\",\"email\":\"jpadida@kld.edu.ph\",\"role\":\"student\"}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-23 13:17:09'),
(9, 'reset_password', 7, 'user', '{\"user_id\":7,\"full_name\":\"Jared Padida\",\"email\":\"jpadida@kld.edu.ph\",\"password_hash\":\"$2y$10$p3jqdjKEvFoe9i0Hu\\/LCFulIAJBuA54.dM4.b0l\\/qLds72h63L7tq\",\"role\":\"student\",\"is_verified\":0,\"verification_token\":null,\"created_at\":\"2025-11-23 21:17:09\",\"status\":\"pending\",\"deleted_at\":null,\"verified\":0}', '{\"password_reset\":true}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-23 13:17:17'),
(10, 'update', 1, 'student', '{\"id\":1,\"student_id\":\"2024-2-000548\",\"email\":\"ajcaballero@kld.edu.ph\",\"first_name\":\"Angelo Jesus\",\"last_name\":\"Caballero\",\"year_level\":\"2\",\"course\":\"BS Information System\",\"contact_number\":\"09944178708\",\"address\":\"Blk 65 e lot brgy fatima 3\",\"photo\":\"student_1_1762729247.jpg\",\"password\":\"$2y$10$S2\\/uS4JtYBiaCP\\/iEZch3u4nKZgNEaweR8\\/dnJ\\/gggQZL\\/t97sHsa\",\"created_at\":\"2025-11-07 18:05:00\",\"profile_completed\":0,\"emergency_contact\":\"09308682711\",\"dob\":\"0000-00-00\",\"gender\":\"Male\",\"blood_type\":\"O+\",\"signature\":\"signature_ajcaballero@kld.edu.ph_1762732643.jpg\",\"cor\":\"ajcaballero_kld.edu.ph_cor.png\",\"digital_id_generated_at\":\"2025-11-10 14:32:52\",\"digital_id_path\":\"digital_id_1.pdf\",\"deleted_at\":null,\"emergency_contact_name\":null}', '{\"first_name\":\"Angelo Jesus Y.\",\"last_name\":\"Caballero\",\"email\":\"ajcaballero@kld.edu.ph\",\"contact_number\":\"09944178708\",\"blood_type\":\"O+\",\"course\":\"BS Information System\",\"address\":\"Blk 65 e lot brgy fatima 3\",\"emergency_contact\":\"09308682711\"}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-23 15:39:18'),
(11, 'update', 1, 'student', '{\"id\":1,\"student_id\":\"2024-2-000548\",\"email\":\"ajcaballero@kld.edu.ph\",\"first_name\":\"Angelo Jesus Y.\",\"last_name\":\"Caballero\",\"year_level\":\"2\",\"course\":\"BS Information System\",\"contact_number\":\"09944178708\",\"address\":\"Blk 65 e lot brgy fatima 3\",\"photo\":\"student_1_1762729247.jpg\",\"password\":\"$2y$10$S2\\/uS4JtYBiaCP\\/iEZch3u4nKZgNEaweR8\\/dnJ\\/gggQZL\\/t97sHsa\",\"created_at\":\"2025-11-07 18:05:00\",\"profile_completed\":0,\"emergency_contact\":\"09308682711\",\"dob\":\"0000-00-00\",\"gender\":\"Male\",\"blood_type\":\"O+\",\"signature\":\"signature_ajcaballero@kld.edu.ph_1762732643.jpg\",\"cor\":\"ajcaballero_kld.edu.ph_cor.png\",\"digital_id_generated_at\":\"2025-11-10 14:32:52\",\"digital_id_path\":\"digital_id_1.pdf\",\"deleted_at\":null,\"emergency_contact_name\":null}', '{\"first_name\":\"Angelo Jesus\",\"last_name\":\"Caballero\",\"email\":\"ajcaballero@kld.edu.ph\",\"contact_number\":\"09944178708\",\"blood_type\":\"O+\",\"course\":\"BS Information System\",\"address\":\"Blk 65 e lot brgy fatima 3\",\"emergency_contact\":\"09308682711\"}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-23 15:39:21'),
(12, 'update', 1, 'student', '{\"id\":1,\"student_id\":\"2024-2-000548\",\"email\":\"ajcaballero@kld.edu.ph\",\"first_name\":\"Angelo Jesus\",\"last_name\":\"Caballero\",\"year_level\":\"2\",\"course\":\"BS Information System\",\"contact_number\":\"09944178708\",\"address\":\"Blk 65 e lot brgy fatima 3\",\"photo\":\"student_1_1762729247.jpg\",\"password\":\"$2y$10$S2\\/uS4JtYBiaCP\\/iEZch3u4nKZgNEaweR8\\/dnJ\\/gggQZL\\/t97sHsa\",\"created_at\":\"2025-11-07 18:05:00\",\"profile_completed\":0,\"emergency_contact\":\"09308682711\",\"dob\":\"0000-00-00\",\"gender\":\"Male\",\"blood_type\":\"O+\",\"signature\":\"signature_ajcaballero@kld.edu.ph_1762732643.jpg\",\"cor\":\"ajcaballero_kld.edu.ph_cor.png\",\"digital_id_generated_at\":\"2025-11-10 14:32:52\",\"digital_id_path\":\"digital_id_1.pdf\",\"deleted_at\":null,\"emergency_contact_name\":null}', '{\"first_name\":\"Angelo Jesus\",\"last_name\":\"Caballero\",\"email\":\"ajcaballero@kld.edu.ph\",\"contact_number\":\"09944178708\",\"blood_type\":\"O+\",\"course\":\"BS Information System\",\"address\":\"Blk 65 e lot brgy fatima 3\",\"emergency_contact\":\"09308682711\"}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-23 15:39:40'),
(13, 'update', 1, 'student', '{\"id\":1,\"student_id\":\"2024-2-000548\",\"email\":\"ajcaballero@kld.edu.ph\",\"first_name\":\"Angelo Jesus\",\"last_name\":\"Caballero\",\"year_level\":\"2\",\"course\":\"BS Information System\",\"contact_number\":\"09944178708\",\"address\":\"Blk 65 e lot brgy fatima 3\",\"photo\":\"student_1_1762729247.jpg\",\"password\":\"$2y$10$S2\\/uS4JtYBiaCP\\/iEZch3u4nKZgNEaweR8\\/dnJ\\/gggQZL\\/t97sHsa\",\"created_at\":\"2025-11-07 18:05:00\",\"profile_completed\":0,\"emergency_contact\":\"09308682711\",\"dob\":\"0000-00-00\",\"gender\":\"Male\",\"blood_type\":\"O+\",\"signature\":\"signature_ajcaballero@kld.edu.ph_1762732643.jpg\",\"cor\":\"ajcaballero_kld.edu.ph_cor.png\",\"digital_id_generated_at\":\"2025-11-10 14:32:52\",\"digital_id_path\":\"digital_id_1.pdf\",\"deleted_at\":null,\"emergency_contact_name\":null}', '{\"first_name\":\"Angelo Jesus\",\"last_name\":\"Caballero\",\"email\":\"ajcaballero@kld.edu.ph\",\"contact_number\":\"09944178708\",\"blood_type\":\"O+\",\"course\":\"BS Information System\",\"address\":\"Blk 65 e lot brgy fatima 3\",\"emergency_contact_name\":\"Genda Ybanez\",\"emergency_contact\":\"09308682711\"}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-23 15:39:49'),
(14, 'set_request_status', 4, 'id_requests', '{\"status\":\"pending\"}', '{\"status\":\"approved\"}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-29 06:13:53'),
(15, 'set_request_status', 3, 'id_requests', '{\"status\":\"pending\"}', '{\"status\":\"approved\"}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-29 06:13:53'),
(16, 'set_request_status', 2, 'id_requests', '{\"status\":\"pending\"}', '{\"status\":\"rejected\"}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-29 06:13:57'),
(17, 'set_request_status', 5, 'id_requests', '{\"status\":\"pending\"}', '{\"status\":\"approved\"}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-29 06:47:53'),
(18, 'bulk_generate_id', 7, 'student', NULL, '{\"id_number\":\"2025100002\",\"request_id\":\"5\",\"issued_id\":\"0\",\"digital_file\":\"kadapat@kld.edu.ph_20251129075200.pdf\"}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-29 06:52:00'),
(19, 'delete', 3, 'student', '{\"id\":3,\"student_id\":\"2024-002\",\"email\":\"student2@school.edu\",\"first_name\":\"Jane\",\"last_name\":\"Doe\",\"year_level\":null,\"course\":null,\"contact_number\":null,\"address\":null,\"photo\":null,\"password\":\"\",\"created_at\":\"2025-11-14 14:45:37\",\"profile_completed\":0,\"emergency_contact\":null,\"dob\":null,\"gender\":null,\"blood_type\":null,\"signature\":null,\"qr_code\":null,\"cor\":null,\"digital_id_generated_at\":null,\"digital_id_path\":null,\"deleted_at\":null,\"emergency_contact_name\":null}', NULL, 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-29 11:09:15'),
(20, 'delete', 12, 'user', '{\"user_id\":12,\"full_name\":\"gsauce318\",\"email\":\"gsauce318@gmail.com\",\"password_hash\":\"$2y$10$ZQYVe3bqO98Mk4cH\\/G1eq.iVK1Zw8cVxK7kCUlbmC2I\\/dCyeWiAPG\",\"role\":\"student\",\"is_verified\":1,\"verification_token\":null,\"created_at\":\"2025-11-29 16:23:32\",\"status\":\"pending\",\"deleted_at\":null,\"verified\":1,\"verified_at\":\"2025-11-29 16:23:41\"}', NULL, 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-29 11:13:33'),
(21, 'delete', 12, 'user', '{\"user_id\":12,\"full_name\":\"gsauce318\",\"email\":\"gsauce318@gmail.com\",\"password_hash\":\"$2y$10$ZQYVe3bqO98Mk4cH\\/G1eq.iVK1Zw8cVxK7kCUlbmC2I\\/dCyeWiAPG\",\"role\":\"student\",\"is_verified\":1,\"verification_token\":null,\"created_at\":\"2025-11-29 16:23:32\",\"status\":\"pending\",\"deleted_at\":null,\"verified\":1,\"verified_at\":\"2025-11-29 16:23:41\"}', NULL, 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-29 11:13:48'),
(22, 'generate_id', 3, 'id_requests', '{\"status\":\"approved\"}', '{\"status\":\"generated\",\"id_number\":\"2025100003\"}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-30 00:05:43'),
(23, 'generate_id_blocked', 4, 'id_requests', '{\"status\":\"approved\"}', '{\"status\":\"rejected\",\"reason\":\"Student already has generated ID\"}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-30 00:14:20'),
(24, 'set_request_status', 4, 'id_requests', '{\"status\":\"pending\"}', '{\"status\":\"approved\"}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-30 00:15:00'),
(25, 'generate_id_blocked', 4, 'id_requests', '{\"status\":\"approved\"}', '{\"status\":\"rejected\",\"reason\":\"Student already has generated ID\"}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-30 00:15:03'),
(26, 'set_request_status', 1, 'id_requests', '{\"status\":\"pending\"}', '{\"status\":\"approved\"}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-30 09:40:24'),
(27, 'generate_id_blocked', 1, 'id_requests', '{\"status\":\"approved\"}', '{\"status\":\"rejected\",\"reason\":\"Student already has generated ID\"}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-30 10:17:55'),
(28, 'generate_id_blocked', 1, 'id_requests', '{\"status\":\"approved\"}', '{\"status\":\"rejected\",\"reason\":\"Student already has generated ID\"}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-30 10:18:37'),
(29, 'generate_id_blocked', 1, 'id_requests', '{\"status\":\"approved\"}', '{\"status\":\"rejected\",\"reason\":\"Student already has generated ID\"}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-30 10:24:28'),
(30, 'mark_id_printed', 2025100001, 'issued_ids', '{\"status\":\"generated\"}', '{\"status\":\"printed\"}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-30 10:30:05'),
(31, 'generate_id_blocked', 1, 'id_requests', '{\"status\":\"approved\"}', '{\"status\":\"rejected\",\"reason\":\"Student already has generated ID\"}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-30 10:30:16'),
(32, 'generate_id', 1, 'id_requests', '{\"status\":\"approved\"}', '{\"status\":\"generated\",\"id_number\":\"2025100003\"}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-30 10:34:04'),
(33, 'set_request_status', 1, 'id_requests', '{\"status\":\"pending\"}', '{\"status\":\"approved\"}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-30 11:05:21'),
(34, 'generate_id_blocked', 1, 'id_requests', '{\"status\":\"approved\"}', '{\"status\":\"rejected\",\"reason\":\"Student already has generated ID\"}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-30 11:05:27'),
(35, 'mark_id_printed', 2025100001, 'issued_ids', '{\"status\":\"generated\"}', '{\"status\":\"printed\"}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-30 11:05:41'),
(36, 'generate_id', 1, 'id_requests', '{\"status\":\"approved\"}', '{\"status\":\"generated\",\"id_number\":\"2025100003\"}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-30 11:06:04'),
(37, 'set_request_status', 1, 'id_requests', '{\"status\":\"pending\"}', '{\"status\":\"approved\"}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-30 21:15:06'),
(38, 'mark_id_printed', 2025100002, 'issued_ids', '{\"status\":\"generated\"}', '{\"status\":\"printed\"}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 15:56:26'),
(39, 'mark_id_printed', 2025100003, 'issued_ids', '{\"status\":\"generated\"}', '{\"status\":\"printed\"}', 2, '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 15:56:51');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `dept_id` int(11) NOT NULL,
  `dept_name` varchar(150) NOT NULL,
  `abbreviation` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`dept_id`, `dept_name`, `abbreviation`, `created_at`) VALUES
(1, 'Computer Science', 'CS', '2025-11-07 01:57:03'),
(2, 'Information Technology', 'IT', '2025-11-07 01:57:03'),
(3, 'Business Administration', 'BA', '2025-11-07 01:57:03'),
(4, 'Education', 'EDU', '2025-11-07 01:57:03'),
(5, 'Engineering', 'ENG', '2025-11-07 01:57:03');

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `email_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email_type` varchar(50) DEFAULT NULL,
  `sent_to` varchar(100) DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('sent','failed') DEFAULT 'sent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_verification`
--

CREATE TABLE `email_verification` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_verification`
--

INSERT INTO `email_verification` (`id`, `user_id`, `email`, `token`, `expires_at`, `is_verified`, `verified_at`, `created_at`) VALUES
(5, 12, 'gsauce318@gmail.com', '66d79b39669449cd80e0d3a273c733d846f0488190b5c8bca12c467e8c4726b6', '2025-11-30 09:23:32', 1, '2025-11-29 16:23:41', '2025-11-29 08:23:32');

-- --------------------------------------------------------

--
-- Table structure for table `id_requests`
--

CREATE TABLE `id_requests` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `request_type` enum('new','replacement','update') NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('generated','pending','approved','rejected','completed') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `id_requests`
--

INSERT INTO `id_requests` (`id`, `student_id`, `request_type`, `reason`, `status`, `admin_notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'new', '', 'pending', NULL, '2025-11-08 07:53:11', '2025-11-30 21:34:03'),
(2, 1, 'replacement', 'TESTING', 'rejected', NULL, '2025-11-08 15:57:37', '2025-11-29 06:13:57'),
(3, 1, 'new', '', 'generated', NULL, '2025-11-16 07:20:10', '2025-11-30 00:05:43'),
(4, 1, 'replacement', 'TESTING', 'rejected', NULL, '2025-11-16 07:56:28', '2025-11-30 00:15:03'),
(5, 7, 'new', '', 'generated', NULL, '2025-11-29 06:47:42', '2025-11-29 23:52:20');

-- --------------------------------------------------------

--
-- Table structure for table `id_templates`
--

CREATE TABLE `id_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `front_background` text DEFAULT NULL,
  `back_background` text DEFAULT NULL,
  `front_css` text DEFAULT NULL,
  `back_css` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `id_templates`
--

INSERT INTO `id_templates` (`id`, `name`, `front_background`, `back_background`, `front_css`, `back_css`, `is_active`, `created_at`) VALUES
(2, 'My New Template', 'background: url(\'uploads/templates/1762623720_9f2a7ba4c1486e705f1b086bbefdca4a.jpg\'); background-size: cover; background-position: center;', 'background: #f8f9fa; color: #000000;', '.id-front {\r\n  padding: 20px;\r\n  position: relative;\r\n  height: 100%;\r\n}\r\n\r\n.student-photo {\r\n  width: 100px;\r\n  height: 120px;\r\n  border-radius: 8px;\r\n  border: 3px solid rgba(255,255,255,0.8);\r\n  position: absolute;\r\n  top: 50px;\r\n  left: 30px;\r\n}\r\n\r\n.student-name {\r\n  font-size: 24px;\r\n  font-weight: bold;\r\n  position: absolute;\r\n  top: 60px;\r\n  left: 150px;\r\n  margin: 0;\r\n  text-shadow: 2px 2px 4px rgba(0,0,0,0.5);\r\n}\r\n\r\n.student-info {\r\n  position: absolute;\r\n  top: 100px;\r\n  left: 150px;\r\n  font-size: 14px;\r\n  line-height: 1.4;\r\n  text-shadow: 1px 1px 2px rgba(0,0,0,0.5);\r\n}\r\n\r\n.student-id {\r\n  font-weight: bold;\r\n  font-size: 16px;\r\n}', '.id-back {\r\n  padding: 20px;\r\n  position: relative;\r\n  height: 100%;\r\n}\r\n\r\n.qr-code {\r\n  position: absolute;\r\n  bottom: 30px;\r\n  right: 30px;\r\n  width: 80px;\r\n  height: 80px;\r\n  background: white;\r\n  border: 2px solid rgba(0,0,0,0.3);\r\n  border-radius: 8px;\r\n  display: flex;\r\n  align-items: center;\r\n  justify-content: center;\r\n}\r\n\r\n.emergency-info {\r\n  position: absolute;\r\n  top: 50px;\r\n  left: 30px;\r\n  right: 30px;\r\n  background: rgba(255,255,255,0.9);\r\n  padding: 15px;\r\n  border-radius: 8px;\r\n  color: #000;\r\n}\r\n\r\n.school-info {\r\n  position: absolute;\r\n  top: 180px;\r\n  left: 30px;\r\n  right: 30px;\r\n  text-align: center;\r\n  color: inherit;\r\n}', 1, '2025-11-08 17:42:03');

-- --------------------------------------------------------

--
-- Table structure for table `issued_ids`
--

CREATE TABLE `issued_ids` (
  `id_number` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('printed','pending','generated','delivered','active','expired','lost') NOT NULL DEFAULT 'generated',
  `digital_id_file` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `issued_ids`
--

INSERT INTO `issued_ids` (`id_number`, `user_id`, `issue_date`, `expiry_date`, `status`, `digital_id_file`) VALUES
('2025100001', 1, '2025-11-30', '2029-11-29', 'printed', 'ajcaballero@kld.edu.ph_20251130120403.pdf'),
('2025100002', 7, '2025-11-30', '2029-11-29', 'printed', 'kadapat@kld.edu.ph_20251130144924.pdf'),
('2025100003', 1, '2025-11-30', '2029-11-30', 'printed', 'ajcaballero@kld.edu.ph_20251130144329.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_completed` tinyint(1) DEFAULT 0,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('Male','Female') DEFAULT NULL,
  `blood_type` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL,
  `signature` varchar(255) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `cor` varchar(255) DEFAULT NULL,
  `digital_id_generated_at` timestamp NULL DEFAULT NULL,
  `digital_id_path` varchar(255) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `emergency_contact_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`id`, `student_id`, `email`, `first_name`, `last_name`, `year_level`, `course`, `contact_number`, `address`, `photo`, `password`, `created_at`, `profile_completed`, `emergency_contact`, `dob`, `gender`, `blood_type`, `signature`, `qr_code`, `cor`, `digital_id_generated_at`, `digital_id_path`, `deleted_at`, `emergency_contact_name`) VALUES
(1, '2024-2-000548', 'ajcaballero@kld.edu.ph', 'Angelo Jesus', 'Caballero', '2nd Year', 'BS Information System', '09944178708', 'Blk 65 e lot brgy fatima 3', '6928db4c760db_1764285260.jpg', '$2y$10$S2/uS4JtYBiaCP/iEZch3u4nKZgNEaweR8/dnJ/gggQZL/t97sHsa', '2025-11-07 10:05:00', 0, '09308682711', '2025-11-21', 'Male', 'O+', '692bf42e1ffc8_1764488238.png', '2025100003.png', '692bf4020e3ae_1764488194.png', '2025-11-10 06:32:52', 'digital_id_1.pdf', NULL, 'Genda Ybanez'),
(2, '2024-2-000123', 'aconcepcion@kld.edu.ph', 'Alex', 'Concepcion', '2nd Year', 'BS Information System', '41i40141', '412414324', NULL, '$2y$10$UZ89hn0ZNCEDQhpr669Ss.aURcImhJP8NP1LS2wXSI57NCyjlJSVK', '2025-11-07 10:19:37', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-10 06:33:03', 'digital_id_2.pdf', NULL, NULL),
(3, '2024-002', 'student2@school.edu', 'Jane', 'Doe', NULL, NULL, NULL, NULL, NULL, '', '2025-11-14 06:45:37', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, '2024-003', 'student3@school.edu', 'Michael', 'Johnson', NULL, NULL, NULL, NULL, NULL, '', '2025-11-14 06:45:37', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, '2024-004', 'student4@school.edu', 'Sarah', 'Wilson', NULL, NULL, NULL, NULL, NULL, '', '2025-11-14 06:45:37', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, '2024-005', 'student5@school.edu', 'David', 'Brown', NULL, NULL, NULL, NULL, NULL, '', '2025-11-14 06:45:37', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, '2024-2-012', 'kadapat@kld.edu.ph', 'Kurt Andrew', 'Dapat', '2nd Year', 'BS Computer Science', '1234567890', '2014jifjklsdfjkl', '692a9489c3be5_1764398217.jpg', '', '2025-11-17 02:28:06', 0, '1234567908', '2025-11-11', 'Male', 'A+', '692bfad907098_1764489945.png', '2025100002.png', 'kadapat_kld.edu.ph_cor.png', NULL, NULL, NULL, 'TESTING DAPAT'),
(8, NULL, 'test123@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '2025-11-19 11:26:43', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(14, NULL, 'tcaballero@kld.edu.ph', 'Test', 'Caballero', NULL, NULL, NULL, NULL, NULL, '', '2025-11-20 10:05:52', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(16, NULL, 'tcaballero2@kld.edu.ph', 'Test2', 'Caballero2', NULL, NULL, NULL, NULL, NULL, '', '2025-11-20 10:10:09', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-20 18:29:18', NULL),
(17, NULL, 'tt3@test.com', 'test3', 'test', NULL, NULL, NULL, NULL, NULL, '', '2025-11-20 10:23:13', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-20 18:26:18', NULL),
(18, NULL, 'utesting@kld.edu.ph', 'usertest', 'testing', NULL, NULL, NULL, NULL, NULL, '', '2025-11-20 10:51:17', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(23, NULL, 'rgonzales@kld.edu.ph', 'Rhamuel', 'Gonzales', NULL, NULL, NULL, NULL, NULL, '', '2025-11-23 13:16:15', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(25, NULL, 'jpadida@kld.edu.ph', 'Jared', 'Padida', NULL, NULL, NULL, NULL, NULL, '', '2025-11-23 13:17:09', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(43, NULL, 'test6@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '2025-11-29 07:17:04', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(45, NULL, 'gsauce318@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '2025-11-29 08:22:09', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `teacher`
--

CREATE TABLE `teacher` (
  `id` int(11) NOT NULL,
  `teacher_id` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher`
--

INSERT INTO `teacher` (`id`, `teacher_id`, `email`, `first_name`, `last_name`, `department`, `contact_number`, `address`, `photo`, `password`, `created_at`) VALUES
(1, NULL, 'caballeroangelo321@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$jzrqbYidos4zeDpGI61YjO.LZEP26zNtwMoKgwgG/hDAa0Mbm2gD2', '2025-11-07 10:18:56');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student') DEFAULT 'student',
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'pending',
  `deleted_at` datetime DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `verified_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password_hash`, `role`, `is_verified`, `verification_token`, `created_at`, `status`, `deleted_at`, `verified`, `verified_at`) VALUES
(1, 'Angelo Jesus Caballero', 'ajcaballero@kld.edu.ph', '$2y$10$.Q2zskjXw7mA95W0DuloSO3tTAFeQg/vFTpjmBHIFtULLxQ2ythRG', 'student', 1, '8b838db50718dc0c303968984e096f17ef553ca737e4d868dc83694a60c6e165', '2025-11-07 10:05:00', 'approved', NULL, 0, NULL),
(2, 'Angelo Jesus Y. Caballero', 'caballeroangelo321@gmail.com', '$2y$10$jzrqbYidos4zeDpGI61YjO.LZEP26zNtwMoKgwgG/hDAa0Mbm2gD2', 'admin', 1, 'fcae5624ce42d293af8caeaa0c506bf52f59ad08fbaf7dea104f46c9f53b191f', '2025-11-07 10:18:56', 'approved', NULL, 0, NULL),
(3, 'Kurt Andrew Dapat', 'kadapat@kld.edu.ph', '$2y$10$vnqZMTwFSgcaKa5W9pfiuupmy5luK1aXoXXISiHofzSJqVOh342Iq', 'student', 1, NULL, '2025-11-17 02:28:06', 'pending', NULL, 0, NULL),
(4, 'Aj Caballero', 'test123@email.com', '$2y$10$J.2pRA5w0D6VtUb4QaT46eYxLyDcaFNUZr80SNN/DLGxBaLpQfT6S', 'student', 1, NULL, '2025-11-19 11:19:58', 'pending', NULL, 0, NULL),
(5, 'usertest testing', 'utesting@kld.edu.ph', '$2y$10$XCYhZdlRHC6t9RAbLZdbKezRpn1211X5HQk93gXsppal2VQMmEjGq', 'student', 0, NULL, '2025-11-20 10:51:16', 'pending', NULL, 0, NULL),
(6, 'Rhamuel Gonzales', 'rgonzales@kld.edu.ph', '$2y$10$kIQLULCTMV9Nhtu7KKI2nu1J0c9oHTU3VEp/oGhvS40QNvmbjPr96', 'student', 0, NULL, '2025-11-23 13:16:32', 'pending', NULL, 0, NULL),
(7, 'Jared Padida', 'jpadida@kld.edu.ph', '$2y$10$XcIyD5FeDeS1o9fT9L8IYOQmnnvhQNjI0ahspvBn3lQ816gLzqopG', 'student', 0, NULL, '2025-11-23 13:17:09', 'pending', NULL, 0, NULL),
(8, 'test6', 'test6@gmail.com', '$2y$10$NNDwwoKW3B24ikghuOowbe23HJ69w/kSWB3rnyk2eXuVQIf4JY97a', 'student', 0, NULL, '2025-11-29 07:16:57', 'pending', NULL, 0, NULL),
(9, 'test7 seven', 'test7@gmail.com', '$2y$10$eKSGANhUINjvmDTXELIt9ulLSfGTpVbyynN.q94PyGwgj8Yta2yO2', 'student', 0, NULL, '2025-11-29 07:24:33', 'pending', NULL, 0, NULL),
(10, 'test9', 'test9@gmail.com', '$2y$10$b5p6zsYyGoDS5JbV3pQvTeVWeLZTyMAI1og/EeF5gZDDMG7S8emjq', 'student', 0, NULL, '2025-11-29 07:47:32', 'pending', NULL, 0, NULL),
(12, 'gsauce318', 'gsauce318@gmail.com', '$2y$10$ZQYVe3bqO98Mk4cH/G1eq.iVK1Zw8cVxK7kCUlbmC2I/dCyeWiAPG', 'student', 1, NULL, '2025-11-29 08:23:32', 'pending', '2025-11-29 19:13:48', 1, '2025-11-29 16:23:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_record` (`record_id`),
  ADD KEY `idx_table` (`table_name`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`dept_id`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`email_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `email_verification`
--
ALTER TABLE `email_verification`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `id_requests`
--
ALTER TABLE `id_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `id_templates`
--
ALTER TABLE `id_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `issued_ids`
--
ALTER TABLE `issued_ids`
  ADD PRIMARY KEY (`id_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `teacher`
--
ALTER TABLE `teacher`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `dept_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `email_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_verification`
--
ALTER TABLE `email_verification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `id_requests`
--
ALTER TABLE `id_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `id_templates`
--
ALTER TABLE `id_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `teacher`
--
ALTER TABLE `teacher`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD CONSTRAINT `email_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `email_verification`
--
ALTER TABLE `email_verification`
  ADD CONSTRAINT `email_verification_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `id_requests`
--
ALTER TABLE `id_requests`
  ADD CONSTRAINT `id_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `issued_ids`
--
ALTER TABLE `issued_ids`
  ADD CONSTRAINT `issued_ids_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
