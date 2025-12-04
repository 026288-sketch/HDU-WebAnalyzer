-- phpMyAdmin SQL Dump
-- version 6.0.0-dev+20250515.4aff755277
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 16, 2025 at 06:07 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `Database`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dashboard_counters`
--

CREATE TABLE `dashboard_counters` (
  `id` bigint UNSIGNED NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `counter_type` enum('total','delta') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'total',
  `value` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dashboard_counters`
--

INSERT INTO `dashboard_counters` (`id`, `key`, `counter_type`, `value`, `created_at`, `updated_at`) VALUES
(1, 'total_nodes', 'total', '{\"count\": 0}', '2025-11-16 15:39:27', '2025-11-16 15:39:27'),
(2, 'total_tags', 'total', '{\"count\": 0}', '2025-11-16 15:39:27', '2025-11-16 15:39:27'),
(3, 'total_sources', 'total', '{\"rss\": 5, \"count\": 5, \"full_rss\": 3, \"browser_required\": 0}', '2025-11-16 15:51:16', '2025-11-16 15:51:16'),
(4, 'nodes_sentiment_positive', 'delta', '{\"count\": 0}', '2025-11-16 15:39:27', '2025-11-16 15:39:27'),
(5, 'nodes_sentiment_negative', 'delta', '{\"count\": 0}', '2025-11-16 15:39:27', '2025-11-16 15:39:27'),
(6, 'nodes_sentiment_neutral', 'delta', '{\"count\": 0}', '2025-11-16 15:39:27', '2025-11-16 15:39:27'),
(7, 'nodes_emotion_anger', 'delta', '{\"count\": 0}', '2025-11-16 15:39:27', '2025-11-16 15:39:27'),
(8, 'nodes_emotion_sadness', 'delta', '{\"count\": 0}', '2025-11-16 15:39:27', '2025-11-16 15:39:27'),
(9, 'nodes_emotion_disgust', 'delta', '{\"count\": 0}', '2025-11-16 15:39:27', '2025-11-16 15:39:27'),
(10, 'nodes_emotion_fear', 'delta', '{\"count\": 0}', '2025-11-16 15:39:27', '2025-11-16 15:39:27'),
(11, 'nodes_emotion_joy', 'delta', '{\"count\": 0}', '2025-11-16 15:39:27', '2025-11-16 15:39:27'),
(12, 'nodes_emotion_surprise', 'delta', '{\"count\": 0}', '2025-11-16 15:39:27', '2025-11-16 15:39:27'),
(13, 'nodes_emotion_neutral', 'delta', '{\"count\": 0}', '2025-11-16 15:39:27', '2025-11-16 15:39:27'),
(14, 'nodes_parsed', 'delta', '{\"count\": 0}', '2025-11-16 15:39:27', '2025-11-16 15:39:27'),
(15, 'nodes_duplicates', 'delta', '{\"count\": 0}', '2025-11-16 15:39:27', '2025-11-16 15:39:27'),
(16, 'nodes_missing_content', 'delta', '{\"count\": 0}', '2025-11-16 15:39:27', '2025-11-16 15:39:27'),
(17, 'errors', 'delta', '{\"count\": 0, \"types\": {\"parser\": 0, \"console\": 0, \"embedding\": 0, \"ai_service\": 0}, \"last_errors\": []}', '2025-11-16 15:39:27', '2025-11-16 15:39:27'),
(18, 'console_script_runs', 'delta', '{\"count\": 0}', '2025-11-16 15:39:27', '2025-11-16 15:39:27'),
(19, 'last_console_script_run', 'total', '{\"timestamp\": null}', '2025-11-16 15:39:27', '2025-11-16 15:39:27');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` bigint UNSIGNED NOT NULL,
  `service` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `context` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`id`, `service`, `level`, `message`, `context`, `created_at`, `updated_at`) VALUES
(1, 'SourceService', 'INFO', 'RSS detected', '{\"url\": \"https://pivdenukraine.com.ua/\", \"rss_url\": \"https://pivdenukraine.com.ua/feed/\", \"need_browser\": false, \"full_rss_content\": true}', '2025-11-16 15:44:30', '2025-11-16 15:44:30'),
(2, 'SourceService', 'INFO', 'RSS detected', '{\"url\": \"https://www.ukrinform.ua/\", \"rss_url\": \"https://www.ukrinform.ua/rss/block-lastnews\", \"need_browser\": false, \"full_rss_content\": false}', '2025-11-16 15:46:04', '2025-11-16 15:46:04'),
(3, 'SourceService', 'INFO', 'RSS detected', '{\"url\": \"https://suspilne.media/\", \"rss_url\": \"https://suspilne.media/rss/all.rss\", \"need_browser\": false, \"full_rss_content\": false}', '2025-11-16 15:50:51', '2025-11-16 15:50:51'),
(4, 'SourceService', 'INFO', 'RSS detected', '{\"url\": \"https://rayon.in.ua/\", \"rss_url\": \"https://rayon.in.ua/storage/rss/rayon/all-rss.xml\", \"need_browser\": false, \"full_rss_content\": true}', '2025-11-16 15:51:04', '2025-11-16 15:51:04'),
(5, 'SourceService', 'INFO', 'RSS detected', '{\"url\": \"https://most.ks.ua/\", \"rss_url\": \"https://most.ks.ua/feed/\", \"need_browser\": false, \"full_rss_content\": true}', '2025-11-16 15:51:16', '2025-11-16 15:51:16');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '0001_01_01_000003_create_parser_logs_table', 1),
(5, '0001_01_01_000004_create_nodes_table', 1),
(10, '2025_09_08_170330_create_logs_table', 3),
(12, '0001_01_01_000005_create_sources_table', 5),
(17, '2025_10_12_230231_create_tags_table', 7),
(19, '2025_10_13_092834_create_node_sentiments_table', 8),
(21, '0001_01_01_000006_create_node_links_table', 10),
(22, '2025_09_26_125101_create_node_embeddings_table', 11),
(23, '2025_10_12_230427_create_node_tag_table', 12),
(32, '2025_11_09_144458_create_stats_daily_table', 13),
(33, '2025_11_09_144700_create_stats_monthly_table', 13),
(34, '2025_11_11_131035_add_duplicate_fields_to_node_links_table', 14),
(35, '2025_10_25_183854_create_dashboard_counters_table', 15);

-- --------------------------------------------------------

--
-- Table structure for table `nodes`
--

CREATE TABLE `nodes` (
  `id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `summary` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `url` varchar(767) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` timestamp NULL DEFAULT NULL,
  `hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `node_embeddings`
--

CREATE TABLE `node_embeddings` (
  `id` bigint UNSIGNED NOT NULL,
  `node_id` bigint UNSIGNED NOT NULL,
  `chroma_id` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `similarity` double DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `node_links`
--

CREATE TABLE `node_links` (
  `id` bigint UNSIGNED NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('rss','html') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'html',
  `use_browser` tinyint(1) NOT NULL DEFAULT '0',
  `parsed` tinyint(1) NOT NULL DEFAULT '0',
  `is_duplicate` tinyint(1) NOT NULL DEFAULT '0',
  `duplicate_of` bigint UNSIGNED DEFAULT NULL,
  `attempts` int NOT NULL DEFAULT '0',
  `last_error` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `node_sentiments`
--

CREATE TABLE `node_sentiments` (
  `id` bigint UNSIGNED NOT NULL,
  `node_id` bigint UNSIGNED NOT NULL,
  `sentiment` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emotion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `node_tag`
--

CREATE TABLE `node_tag` (
  `node_id` bigint UNSIGNED NOT NULL,
  `tag_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sources`
--

CREATE TABLE `sources` (
  `id` bigint UNSIGNED NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `isActive` tinyint(1) NOT NULL DEFAULT '0',
  `rss_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_rss_content` tinyint(1) NOT NULL DEFAULT '0',
  `need_browser` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sources`
--

INSERT INTO `sources` (`id`, `url`, `isActive`, `rss_url`, `full_rss_content`, `need_browser`, `created_at`, `updated_at`) VALUES
(1, 'https://pivdenukraine.com.ua/', 1, 'https://pivdenukraine.com.ua/feed/', 1, 0, '2025-11-16 15:44:30', '2025-11-16 15:44:30'),
(2, 'https://www.ukrinform.ua/', 0, 'https://www.ukrinform.ua/rss/block-lastnews', 0, 0, '2025-11-16 15:46:04', '2025-11-16 15:46:04'),
(3, 'https://suspilne.media/', 0, 'https://suspilne.media/rss/all.rss', 0, 0, '2025-11-16 15:50:51', '2025-11-16 15:50:51'),
(4, 'https://rayon.in.ua/', 0, 'https://rayon.in.ua/storage/rss/rayon/all-rss.xml', 1, 0, '2025-11-16 15:51:04', '2025-11-16 15:51:04'),
(5, 'https://most.ks.ua/', 0, 'https://most.ks.ua/feed/', 1, 0, '2025-11-16 15:51:16', '2025-11-16 15:51:16');

-- --------------------------------------------------------

--
-- Table structure for table `stats_daily`
--

CREATE TABLE `stats_daily` (
  `id` bigint UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `data` json NOT NULL,
  `is_synced` tinyint NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stats_monthly`
--

CREATE TABLE `stats_monthly` (
  `id` bigint UNSIGNED NOT NULL,
  `month` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` json NOT NULL,
  `is_synced` tinyint NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@example.com', NULL, '$2y$12$1DPmdDKejk5gf8zTEvpk4Ozkox/xrdH9JBusXQrYS98wm47MMlU2a', 'wesb3ysIs8CIjVYSYEjFoyYbTX1DVB4cXlsaHHbTuxE87RZiMoHuuIjQeyKU', '2025-05-30 17:52:21', '2025-05-30 17:52:21');

--
-- Indexes for dumped tables
--

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
-- Indexes for table `dashboard_counters`
--
ALTER TABLE `dashboard_counters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dashboard_counters_key_unique` (`key`),
  ADD KEY `dashboard_counters_key_index` (`key`),
  ADD KEY `dashboard_counters_counter_type_index` (`counter_type`);

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
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `nodes`
--
ALTER TABLE `nodes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nodes_url_unique` (`url`),
  ADD UNIQUE KEY `nodes_hash_unique` (`hash`);

--
-- Indexes for table `node_embeddings`
--
ALTER TABLE `node_embeddings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `node_embeddings_chroma_id_unique` (`chroma_id`),
  ADD KEY `1` (`node_id`);

--
-- Indexes for table `node_links`
--
ALTER TABLE `node_links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `node_links_url_unique` (`url`),
  ADD KEY `node_links_duplicate_of_foreign` (`duplicate_of`),
  ADD KEY `node_links_is_duplicate_index` (`is_duplicate`);

--
-- Indexes for table `node_sentiments`
--
ALTER TABLE `node_sentiments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `node_sentiments_node_id_foreign` (`node_id`);

--
-- Indexes for table `node_tag`
--
ALTER TABLE `node_tag`
  ADD PRIMARY KEY (`node_id`,`tag_id`),
  ADD KEY `node_tag_tag_id_foreign` (`tag_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `sources`
--
ALTER TABLE `sources`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stats_daily`
--
ALTER TABLE `stats_daily`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stats_daily_date_unique` (`date`),
  ADD KEY `stats_daily_date_index` (`date`),
  ADD KEY `stats_daily_is_synced_index` (`is_synced`);

--
-- Indexes for table `stats_monthly`
--
ALTER TABLE `stats_monthly`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stats_monthly_month_unique` (`month`),
  ADD KEY `stats_monthly_month_index` (`month`),
  ADD KEY `stats_monthly_is_synced_index` (`is_synced`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tags_name_unique` (`name`),
  ADD UNIQUE KEY `tags_slug_unique` (`slug`);

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
-- AUTO_INCREMENT for table `dashboard_counters`
--
ALTER TABLE `dashboard_counters`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `nodes`
--
ALTER TABLE `nodes`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `node_embeddings`
--
ALTER TABLE `node_embeddings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `node_links`
--
ALTER TABLE `node_links`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `node_sentiments`
--
ALTER TABLE `node_sentiments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=215;

--
-- AUTO_INCREMENT for table `sources`
--
ALTER TABLE `sources`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `stats_daily`
--
ALTER TABLE `stats_daily`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stats_monthly`
--
ALTER TABLE `stats_monthly`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `node_embeddings`
--
ALTER TABLE `node_embeddings`
  ADD CONSTRAINT `1` FOREIGN KEY (`node_id`) REFERENCES `nodes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `node_links`
--
ALTER TABLE `node_links`
  ADD CONSTRAINT `node_links_duplicate_of_foreign` FOREIGN KEY (`duplicate_of`) REFERENCES `nodes` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `node_sentiments`
--
ALTER TABLE `node_sentiments`
  ADD CONSTRAINT `node_sentiments_node_id_foreign` FOREIGN KEY (`node_id`) REFERENCES `nodes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `node_tag`
--
ALTER TABLE `node_tag`
  ADD CONSTRAINT `node_tag_node_id_foreign` FOREIGN KEY (`node_id`) REFERENCES `nodes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `node_tag_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
