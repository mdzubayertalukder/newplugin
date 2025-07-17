CREATE TABLE IF NOT EXISTS `dropshipping_search_cache` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `search_query` varchar(255) NOT NULL,
  `search_hash` varchar(32) NOT NULL,
  `search_results` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`search_results`)),
  `total_websites` int(11) NOT NULL DEFAULT 0,
  `search_summary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`search_summary`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dropshipping_search_cache_search_hash_unique` (`search_hash`),
  KEY `dropshipping_search_cache_search_query_index` (`search_query`),
  KEY `dropshipping_search_cache_search_hash_is_active_index` (`search_hash`,`is_active`),
  KEY `dropshipping_search_cache_last_used_at_index` (`last_used_at`),
  KEY `dropshipping_search_cache_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;