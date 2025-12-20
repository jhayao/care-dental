-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 20, 2025 at 11:02 AM
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
-- Database: `dentist_project`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `time_slot` time NOT NULL,
  `status` enum('pending','confirmed','cancelled','rescheduled') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `booking_fee` decimal(10,2) NOT NULL DEFAULT 50.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `cancelled_at` datetime DEFAULT NULL,
  `appointment_date` date NOT NULL DEFAULT curdate(),
  `appointment_time` time NOT NULL DEFAULT '09:00:00',
  `duration_minutes` int(11) NOT NULL DEFAULT 60
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking_fees`
--

CREATE TABLE `booking_fees` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_fee` decimal(10,2) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `booking_fees`
--

INSERT INTO `booking_fees` (`id`, `booking_fee`, `status`, `created_at`, `updated_at`) VALUES
(1, 5.00, 'Active', '2025-11-06 23:50:09', '2025-11-06 23:50:09'),
(2, 5.00, 'Active', '2025-11-06 23:51:59', '2025-11-06 23:51:59');

-- --------------------------------------------------------

--
-- Table structure for table `booking_items`
--

CREATE TABLE `booking_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `item_type` enum('package','service') NOT NULL,
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `booking_items`
--

INSERT INTO `booking_items` (`id`, `booking_id`, `item_type`, `item_id`, `created_at`, `updated_at`) VALUES
(0, 4, 'package', 3, NULL, NULL),
(0, 4, 'service', 4, NULL, NULL),
(0, 4, 'service', 8, NULL, NULL),
(0, 5, 'service', 4, '2025-12-04 06:29:25', '2025-12-04 06:29:25'),
(0, 6, 'service', 4, '2025-12-04 06:39:38', '2025-12-04 06:39:38'),
(0, 7, 'package', 3, '2025-12-04 06:48:47', '2025-12-04 06:48:47'),
(0, 8, 'package', 5, '2025-12-04 10:08:47', '2025-12-04 10:08:47'),
(0, 9, 'service', 4, '2025-12-04 10:11:48', '2025-12-04 10:11:48'),
(0, 10, 'service', 4, '2025-12-04 16:28:42', '2025-12-04 16:28:42'),
(0, 11, 'service', 17, '2025-12-05 23:35:28', '2025-12-05 23:35:28'),
(0, 12, 'service', 17, '2025-12-05 23:49:59', '2025-12-05 23:49:59'),
(0, 13, 'service', 16, '2025-12-05 23:57:23', '2025-12-05 23:57:23'),
(0, 14, 'service', 17, '2025-12-06 00:13:51', '2025-12-06 00:13:51'),
(0, 15, 'service', 16, '2025-12-06 01:03:26', '2025-12-06 01:03:26'),
(0, 16, 'service', 16, '2025-12-06 01:50:54', '2025-12-06 01:50:54'),
(0, 17, 'package', 5, '2025-12-06 02:06:09', '2025-12-06 02:06:09'),
(0, 18, 'service', 8, '2025-12-06 02:07:01', '2025-12-06 02:07:01'),
(0, 19, 'package', 3, '2025-12-06 02:07:32', '2025-12-06 02:07:32'),
(0, 20, 'package', 5, '2025-12-06 03:49:23', '2025-12-06 03:49:23'),
(0, 21, 'service', 17, '2025-12-06 03:52:06', '2025-12-06 03:52:06'),
(0, 21, 'package', 3, '2025-12-06 03:52:06', '2025-12-06 03:52:06'),
(0, 22, 'service', 16, '2025-12-06 04:21:41', '2025-12-06 04:21:41'),
(0, 23, 'package', 5, '2025-12-06 04:32:01', '2025-12-06 04:32:01'),
(0, 24, 'package', 5, '2025-12-07 08:20:30', '2025-12-07 08:20:30'),
(0, 25, 'service', 16, '2025-12-16 07:45:12', '2025-12-16 07:45:12'),
(0, 26, 'service', 17, '2025-12-16 07:48:39', '2025-12-16 07:48:39'),
(0, 27, 'package', 5, '2025-12-16 07:49:40', '2025-12-16 07:49:40'),
(0, 28, 'service', 17, '2025-12-18 10:26:26', '2025-12-18 10:26:26'),
(0, 28, 'package', 5, '2025-12-18 10:26:26', '2025-12-18 10:26:26'),
(0, 29, 'service', 17, '2025-12-18 10:30:05', '2025-12-18 10:30:05'),
(0, 29, 'package', 7, '2025-12-18 10:30:05', '2025-12-18 10:30:05'),
(0, 30, 'service', 17, '2025-12-18 10:31:16', '2025-12-18 10:31:16'),
(0, 31, 'package', 5, '2025-12-18 10:38:16', '2025-12-18 10:38:16'),
(0, 32, 'package', 5, '2025-12-18 10:38:45', '2025-12-18 10:38:45'),
(0, 33, 'service', 17, '2025-12-18 10:48:40', '2025-12-18 10:48:40'),
(0, 34, 'service', 17, '2025-12-18 10:51:03', '2025-12-18 10:51:03'),
(0, 35, 'service', 16, '2025-12-18 10:53:04', '2025-12-18 10:53:04'),
(0, 36, 'package', 3, '2025-12-18 10:53:14', '2025-12-18 10:53:14'),
(0, 37, 'service', 16, '2025-12-18 10:59:05', '2025-12-18 10:59:05'),
(0, 38, 'package', 3, '2025-12-18 10:59:46', '2025-12-18 10:59:46'),
(0, 39, 'service', 16, '2025-12-18 11:03:42', '2025-12-18 11:03:42'),
(0, 40, 'package', 3, '2025-12-18 11:13:21', '2025-12-18 11:13:21'),
(0, 41, 'package', 3, '2025-12-18 11:14:41', '2025-12-18 11:14:41'),
(0, 42, 'service', 18, '2025-12-18 11:17:54', '2025-12-18 11:17:54'),
(0, 43, 'service', 16, '2025-12-18 11:18:06', '2025-12-18 11:18:06'),
(0, 44, 'package', 5, '2025-12-18 11:22:22', '2025-12-18 11:22:22'),
(0, 45, 'package', 3, '2025-12-18 11:22:38', '2025-12-18 11:22:38'),
(0, 46, 'service', 16, '2025-12-18 11:29:56', '2025-12-18 11:29:56'),
(0, 47, 'service', 16, '2025-12-18 11:30:14', '2025-12-18 11:30:14'),
(0, 48, 'service', 17, '2025-12-18 11:32:39', '2025-12-18 11:32:39'),
(0, 49, 'service', 17, '2025-12-18 11:32:51', '2025-12-18 11:32:51'),
(0, 50, 'service', 18, '2025-12-18 11:55:04', '2025-12-18 11:55:04'),
(0, 51, 'package', 7, '2025-12-18 11:55:18', '2025-12-18 11:55:18'),
(0, 52, 'service', 17, '2025-12-18 11:59:43', '2025-12-18 11:59:43');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cart_id` bigint(20) UNSIGNED NOT NULL,
  `itemable_type` varchar(255) NOT NULL,
  `itemable_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dentist_calendar`
--

CREATE TABLE `dentist_calendar` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `available_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dentist_calendar`
--

INSERT INTO `dentist_calendar` (`id`, `user_id`, `available_date`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES
(5, 7, '2026-01-01', '14:01:00', '15:01:00', '2025-12-16 03:23:25', NULL),
(6, 7, '2025-12-24', '15:00:00', '17:00:00', '2025-12-16 03:30:42', NULL),
(7, 7, '2025-12-17', '13:30:00', '17:30:00', '2025-12-17 02:18:07', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

CREATE TABLE `medical_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_07_10_052055_create_staff_table', 1),
(5, '2025_07_11_120429_create_dentist_calendars_table', 1),
(6, '2025_07_13_025932_create_bookings_table', 1),
(7, '2025_07_14_023650_create_services_table', 1),
(8, '2025_07_14_023700_create_packages_table', 1),
(9, '2025_07_14_023826_create_package_items_table', 1),
(10, '2025_07_15_092211_create_medical_records_table', 1),
(11, '2025_07_18_065404_create_carts_table', 1),
(12, '2025_07_19_065116_create_cart_items_table', 1),
(13, '2025_07_19_070536_create_booking_items_table', 1),
(14, '2025_07_19_134850_create_payments_table', 1),
(15, '2025_07_20_120338_create_booking_fees_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `posted_by` int(11) NOT NULL,
  `package_name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `inclusions` text DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `duration_minutes` int(11) NOT NULL DEFAULT 60,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `posted_by`, `package_name`, `description`, `inclusions`, `status`, `price`, `duration_minutes`, `created_at`, `updated_at`) VALUES
(3, 5, 'Emergency Care Package', 'Immediate treatment for dental emergencies.', '[\"Examination\",\"Pain Relief\",\"Temporary Filling or Treatment\"]', 'Active', 1200.00, 60, '2025-12-04 04:57:46', '2025-12-07 05:06:46'),
(5, 5, 'Dental Whitening Package', 'A complete tooth whitening package.', '[\"Cleaning\",\"Whitening Gel\",\"Consultation\"]', 'Active', 2500.00, 60, '2025-12-04 05:00:12', '2025-12-07 05:05:21'),
(7, 7, 'Cavity Treatment', 'Treatment for minor to moderate cavities.', 'Examination, Filling, Follow-up Check', 'Active', 1200.00, 60, '2025-12-04 15:50:18', '2025-12-06 05:08:53'),
(9, 7, 'sample', 'dsdfs', '[\"fsfs\",\"ffd\",\"dd\"]', 'Active', 44.00, 140, '2025-12-19 05:10:57', '2025-12-19 05:21:29');

-- --------------------------------------------------------

--
-- Table structure for table `package_items`
--

CREATE TABLE `package_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `packages_id` bigint(20) UNSIGNED NOT NULL,
  `services_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `payment_method` varchar(255) NOT NULL,
  `status` enum('pending','approved','declined','cancelled') NOT NULL DEFAULT 'pending',
  `xendit_invoice_id` varchar(255) NOT NULL,
  `xendit_payment_id` varchar(255) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `posted_by` int(11) NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `service_image` varchar(255) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `duration_minutes` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `posted_by`, `service_name`, `description`, `service_image`, `status`, `price`, `duration_minutes`, `created_at`, `updated_at`) VALUES
(8, 5, 'Dental X-Ray', 'Digital X-ray imaging to assess oral health.', 'uploads/services/6933bcf6caf76_images (2).jpg', 'Active', 400.00, 30, '2025-12-04 04:40:18', '2025-12-19 05:27:11'),
(16, 6, 'Teeth Cleaning', 'Professional cleaning to remove plaque and tartar buildup.', 'uploads/services/6933bcd657b09_images (1).jpg', 'Active', 500.00, 40, '2025-12-05 23:18:27', '2025-12-06 05:10:36'),
(17, 7, 'Gum Treatment', 'Care for gum disease including cleaning and medication.', 'uploads/services/6933bc1e9d3bc_images.jpg', 'Active', 1800.00, 40, '2025-12-05 23:26:59', '2025-12-06 05:14:08'),
(18, 8, 'sampleee ', 'ddd', 'uploads/services/69411b2aa455b_images.jpg', 'Active', 333.00, 30, '2025-12-16 08:41:14', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('MzrJFU2NRlO15ZZnGFp5wbYIi22O3FEj0v1Rtpn2', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiZW82WnpOc3psV3VUaUhLMmZXNmpKTTM3NEhjYkdFalVsaDgwZ0tpSyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjk6Il9wcmV2aW91cyI7YToyOntzOjM6InVybCI7czozMDoiaHR0cDovLzEyNy4wLjAuMTo4MDAwL2NhbGVuZGFyIjtzOjU6InJvdXRlIjtzOjg6ImNhbGVuZGFyIjt9fQ==', 1762509536),
('RHrVeDSjccVEE5PEVbZ8slDiHOMfVkbxPEnVQiCX', 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiSDhYSWd5dUZZYjU2aWRDTVpvcjRWamh5aEQ0Y2dWaVJGZG1MTHBjMSI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozMDoiaHR0cDovLzEyNy4wLjAuMTo4MDAwL2Jvb2tpbmdzIjt9czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDg6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hdmFpbGFibGUtdGltZXMvMjAyNS0xMS0xMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6Mzt9', 1762509395),
('xMOKVD7xOSctSz312Z2yLVM9WVmep29iDBHknjTH', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiU0J6SFJ0VmRKdXNTSnJqOG5UeXFwaGdPNVdJSW5uQ2wwYzE5QzBPViI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozNDoiaHR0cDovLzEyNy4wLjAuMTo4MDAwL2FwcG9pbnRtZW50cyI7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjMwOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYm9va2luZ3MiO3M6NToicm91dGUiO3M6ODoiYm9va2luZ3MiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToyO30=', 1762509493);

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `address_` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `pword` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status_` tinyint(1) NOT NULL,
  `role_` enum('admin','staff','patient') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `first_name`, `last_name`, `address_`, `email`, `pword`, `created_at`, `updated_at`, `status_`, `role_`) VALUES
(1, 'pedro', 'Encartional', 'email@email', 'balo', '', NULL, NULL, 0, 'admin'),
(2, 'Sam', 'saminodin', 'balo lng', 'dsaminodin@gmail.com', 'password', NULL, NULL, 1, 'admin'),
(3, 'Sam', 'Sami', 'wala ko kabalo', 'email@email', 'password', NULL, NULL, 1, 'staff'),
(4, 'Fretchel Ann', 'Mahinay', 'Dumingag, ZDS', 'fretchelanndmahinay@gmail.com', 'password', NULL, NULL, 1, 'staff'),
(5, 'Fretchel Ann', 'Mahinay', 'Dumingag, ZDS', 'fretchelanndmahinay@gmail.com', 'password', NULL, NULL, 1, 'staff'),
(6, 'Fretchel Ann', 'Mahinay', 'Dumingag, ZDS', 'fretchelanndmahinay@gmail.com', 'password', NULL, NULL, 1, 'staff'),
(7, 'Fretchel Ann', 'Mahinay', 'Dumingag, ZDS', 'fretchelanndmahinay@gmail.com', 'password', NULL, NULL, 1, 'staff'),
(8, 'Fretchel Ann', 'Mahinay', 'Dumingag, ZDS', 'fretchelanndmahinay@gmail.com', 'password', NULL, NULL, 1, 'staff'),
(9, 'Fretchel Ann', 'Mahinay', 'Dumingag, ZDS', 'fretchelanndmahinay@gmail.com', 'password', NULL, NULL, 1, 'staff'),
(10, 'Heerold', 'Pedro', 'ozamiz', 'mariaterea.encarnacion@nmsc.edu.ph', '123', NULL, NULL, 1, 'staff'),
(11, 'Heerold', 'Pedro', 'ozamiz', 'mariaterea.encarnacion@nmsc.edu.ph', '123', NULL, NULL, 1, 'staff'),
(12, 'Teresa', 'Encartional', 'balo', 'email@email', '', NULL, NULL, 1, 'admin'),
(13, 'mario', 'Encartional', 'balo', 'email@email', '', NULL, NULL, 1, 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `address_` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `pword` text NOT NULL,
  `user_type` enum('admin','staff','patient') NOT NULL,
  `status_` enum('Active','Inactive','Archived') DEFAULT 'Active',
  `proof_file` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `reference_no` varchar(50) DEFAULT NULL,
  `category` enum('None','Senior','PWD') NOT NULL DEFAULT 'None',
  `discount` decimal(5,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `address_`, `email`, `email_verified_at`, `pword`, `user_type`, `status_`, `proof_file`, `remember_token`, `created_at`, `updated_at`, `gender`, `reference_no`, `category`, `discount`) VALUES
(2, 'Maria Teresa', 'Encarnacion', 'P-4 Basirang, Tudela, Mis.Occ', 'encarnacionmariateresa7@gmail.com', NULL, '$2y$12$yKmPnngaO7Q2YqMTzQanUOs9mou5yaN0xO48N7YcY2zuxom7yI8pC', 'staff', 'Active', NULL, NULL, '2025-11-06 23:48:39', '2025-11-06 23:48:39', 'Male', NULL, 'None', 0.00),
(7, 'Saminodin', 'Admin', 'Tudela', 'dsaminodin@gmail.com', '2025-12-04 06:04:53', '$2y$10$CF6wnMZb5OxCXg7pEll49uB21Lzkeg/nNXn4q.zxt6RAqhFh2SAHK', 'admin', 'Active', NULL, NULL, '2025-12-04 06:04:53', '2025-12-19 05:34:33', 'Male', 'ADM1764853493', 'None', 0.00),
(8, 'Juan', 'Cruz', 'Tangub city', 'staff@gmail.com', NULL, '$2y$10$FFgJBMJnRByqf/yeSNDvQ.RrOprW8e8HA7FVWzoFGZk7KE8o98YXS', 'staff', 'Active', NULL, NULL, '2025-12-04 14:06:53', NULL, 'Male', NULL, 'None', 0.00),
(10, 'Hermegildo', 'Fiel', 'Labuyo Tangub', 'jromee2005@gmail.com', NULL, '$2y$10$9Y.01ZTrj5G4WwJJKOspC..o2DPEvJ.uRJbuUuxdw748hGKrob7aS', 'patient', 'Active', NULL, NULL, '2025-12-06 00:13:17', '2025-12-06 00:13:17', 'Male', NULL, 'PWD', 20.00),
(11, 'fiel', 'Fiel', 'Labuyo Tangub', 'fiel21@gmail.com', NULL, '$2y$10$E28iuAjiQsxCiWVnkK3Z8eNDVXgmqAT9PqiSqtLd6Fge9IrzyyUX.', 'patient', 'Active', '694112472d03d_Screenshot 2025-12-16 160257.png', NULL, '2025-12-16 08:03:19', '2025-12-16 08:03:19', 'Male', NULL, 'Senior', 20.00),
(12, 'hermegildo', 'cejudo', 'Labuyo Tangub', 'hermegildo.fiel@nmsc.edu.ph', NULL, '$2y$10$wpqiOj8tj114zczHJ8zMi.z5FxVut45/1d00CvZiqZxrCKUrlt0C.', 'staff', 'Inactive', NULL, NULL, '2025-12-17 02:15:53', NULL, 'Male', NULL, 'None', 0.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bookings_user_id_foreign` (`user_id`);

--
-- Indexes for table `booking_fees`
--
ALTER TABLE `booking_fees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `carts_user_id_foreign` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_items_cart_id_foreign` (`cart_id`),
  ADD KEY `cart_items_itemable_type_itemable_id_index` (`itemable_type`,`itemable_id`);

--
-- Indexes for table `dentist_calendar`
--
ALTER TABLE `dentist_calendar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user` (`user_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `package_items`
--
ALTER TABLE `package_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `package_items_packages_id_foreign` (`packages_id`),
  ADD KEY `package_items_services_id_foreign` (`services_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payments_booking_id_foreign` (`booking_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `booking_fees`
--
ALTER TABLE `booking_fees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `dentist_calendar`
--
ALTER TABLE `dentist_calendar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `package_items`
--
ALTER TABLE `package_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dentist_calendar`
--
ALTER TABLE `dentist_calendar`
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `package_items`
--
ALTER TABLE `package_items`
  ADD CONSTRAINT `package_items_packages_id_foreign` FOREIGN KEY (`packages_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `package_items_services_id_foreign` FOREIGN KEY (`services_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
