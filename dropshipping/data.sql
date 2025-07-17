-- Dropshipping Plugin Database Schema

-- Dropshipping Orders Table
CREATE TABLE IF NOT EXISTS `dropshipping_orders` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` varchar(255) NOT NULL,
    `order_number` varchar(255) NOT NULL UNIQUE,
    `original_order_id` bigint(20) UNSIGNED NULL,
    `order_code` varchar(255) NULL,
    `local_product_id` bigint(20) UNSIGNED NULL,
    `dropshipping_product_id` bigint(20) UNSIGNED NULL,
    `product_name` varchar(255) NOT NULL,
    `product_sku` varchar(255) NULL,
    `quantity` int(11) NOT NULL,
    `unit_price` decimal(10,2) NOT NULL,
    `total_amount` decimal(10,2) NOT NULL,
    `commission_rate` decimal(5,2) NOT NULL DEFAULT 20.00,
    `commission_amount` decimal(10,2) NOT NULL,
    `tenant_earning` decimal(10,2) NOT NULL,
    `customer_name` varchar(255) NOT NULL,
    `customer_email` varchar(255) NULL,
    `customer_phone` varchar(255) NULL,
    `shipping_address` text NULL,
    `fulfillment_note` text NULL,
    `status` enum('pending','approved','rejected','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
    `admin_notes` text NULL,
    `rejection_reason` text NULL,
    `submitted_at` timestamp NULL DEFAULT NULL,
    `approved_at` timestamp NULL DEFAULT NULL,
    `shipped_at` timestamp NULL DEFAULT NULL,
    `delivered_at` timestamp NULL DEFAULT NULL,
    `submitted_by` bigint(20) UNSIGNED NOT NULL,
    `approved_by` bigint(20) UNSIGNED NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `dropshipping_orders_tenant_id_status_index` (`tenant_id`, `status`),
    KEY `dropshipping_orders_status_index` (`status`),
    KEY `dropshipping_orders_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tenant Balances Table
CREATE TABLE IF NOT EXISTS `tenant_balances` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` varchar(255) NOT NULL UNIQUE,
    `total_earnings` decimal(12,2) NOT NULL DEFAULT 0.00,
    `available_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
    `pending_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
    `total_withdrawn` decimal(12,2) NOT NULL DEFAULT 0.00,
    `total_orders` int(11) NOT NULL DEFAULT 0,
    `pending_orders` int(11) NOT NULL DEFAULT 0,
    `approved_orders` int(11) NOT NULL DEFAULT 0,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `tenant_balances_tenant_id_index` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Withdrawal Requests Table
CREATE TABLE IF NOT EXISTS `withdrawal_requests` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` varchar(255) NOT NULL,
    `request_number` varchar(255) NOT NULL UNIQUE,
    `amount` decimal(10,2) NOT NULL,
    `payment_method` varchar(255) NOT NULL,
    `payment_details` json NOT NULL,
    `status` enum('pending','approved','rejected','processed') NOT NULL DEFAULT 'pending',
    `notes` text NULL,
    `admin_notes` text NULL,
    `rejection_reason` text NULL,
    `requested_at` timestamp NOT NULL,
    `approved_at` timestamp NULL DEFAULT NULL,
    `processed_at` timestamp NULL DEFAULT NULL,
    `requested_by` bigint(20) UNSIGNED NOT NULL,
    `approved_by` bigint(20) UNSIGNED NULL,
    `processed_by` bigint(20) UNSIGNED NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `withdrawal_requests_tenant_id_status_index` (`tenant_id`, `status`),
    KEY `withdrawal_requests_status_index` (`status`),
    KEY `withdrawal_requests_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- WooCommerce Configurations Table
CREATE TABLE IF NOT EXISTS `dropshipping_woocommerce_configs` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text NULL,
    `store_url` varchar(500) NOT NULL,
    `consumer_key` varchar(255) NOT NULL,
    `consumer_secret` varchar(255) NOT NULL,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `last_sync_at` timestamp NULL DEFAULT NULL,
    `total_products` int(11) NOT NULL DEFAULT 0,
    `sync_status` enum('not_synced','syncing','completed','failed') NOT NULL DEFAULT 'not_synced',
    `created_by` bigint(20) UNSIGNED NOT NULL,
    `updated_by` bigint(20) UNSIGNED NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    `deleted_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `dropshipping_woocommerce_configs_is_active_index` (`is_active`),
    KEY `dropshipping_woocommerce_configs_sync_status_index` (`sync_status`),
    KEY `dropshipping_woocommerce_configs_created_by_foreign` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dropshipping Products Table
CREATE TABLE IF NOT EXISTS `dropshipping_products` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `woocommerce_config_id` bigint(20) UNSIGNED NOT NULL,
    `woocommerce_product_id` int(11) NOT NULL,
    `name` varchar(500) NOT NULL,
    `slug` varchar(500) NOT NULL,
    `description` longtext NULL,
    `short_description` text NULL,
    `price` decimal(10,2) NULL,
    `regular_price` decimal(10,2) NULL,
    `sale_price` decimal(10,2) NULL,
    `sku` varchar(100) NULL,
    `stock_quantity` int(11) NULL,
    `stock_status` enum('instock','outofstock','onbackorder') NOT NULL DEFAULT 'instock',
    `categories` longtext NULL,
    `tags` longtext NULL,
    `images` longtext NULL,
    `gallery_images` longtext NULL,
    `attributes` longtext NULL,
    `variations` longtext NULL,
    `weight` varchar(50) NULL,
    `dimensions` longtext NULL,
    `meta_data` longtext NULL,
    `status` enum('draft','pending','private','publish') NOT NULL DEFAULT 'publish',
    `featured` tinyint(1) NOT NULL DEFAULT 0,
    `catalog_visibility` enum('visible','catalog','search','hidden') NOT NULL DEFAULT 'visible',
    `date_created` timestamp NULL DEFAULT NULL,
    `date_modified` timestamp NULL DEFAULT NULL,
    `last_synced_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    `deleted_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `dropshipping_products_config_woo_id_unique` (`woocommerce_config_id`, `woocommerce_product_id`),
    KEY `dropshipping_products_woocommerce_config_id_foreign` (`woocommerce_config_id`),
    KEY `dropshipping_products_woocommerce_product_id_index` (`woocommerce_product_id`),
    KEY `dropshipping_products_status_index` (`status`),
    KEY `dropshipping_products_stock_status_index` (`stock_status`),
    KEY `dropshipping_products_featured_index` (`featured`),
    KEY `dropshipping_products_sku_index` (`sku`),
    CONSTRAINT `dropshipping_products_woocommerce_config_id_foreign` FOREIGN KEY (`woocommerce_config_id`) REFERENCES `dropshipping_woocommerce_configs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product Import History Table
CREATE TABLE IF NOT EXISTS `dropshipping_product_import_history` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` varchar(255) NOT NULL,
    `woocommerce_store_id` bigint(20) UNSIGNED NULL,
    `woocommerce_config_id` bigint(20) UNSIGNED NOT NULL,
    `woocommerce_product_id` bigint(20) UNSIGNED NULL,
    `dropshipping_product_id` bigint(20) UNSIGNED NULL,
    `local_product_id` bigint(20) UNSIGNED NULL,
    `import_type` enum('single','bulk','auto_sync','manual') NOT NULL DEFAULT 'single',
    `import_status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
    `status` enum('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
    `imported_data` longtext NULL,
    `pricing_adjustments` longtext NULL,
    `error_message` text NULL,
    `import_settings` longtext NULL,
    `imported_at` timestamp NULL DEFAULT NULL,
    `imported_by` bigint(20) UNSIGNED NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `dropshipping_import_history_tenant_id_index` (`tenant_id`),
    KEY `dropshipping_import_history_woocommerce_store_id_index` (`woocommerce_store_id`),
    KEY `dropshipping_import_history_woocommerce_config_id_foreign` (`woocommerce_config_id`),
    KEY `dropshipping_import_history_woocommerce_product_id_index` (`woocommerce_product_id`),
    KEY `dropshipping_import_history_dropshipping_product_id_foreign` (`dropshipping_product_id`),
    KEY `dropshipping_import_history_import_status_index` (`import_status`),
    KEY `dropshipping_import_history_status_index` (`status`),
    KEY `dropshipping_import_history_import_type_index` (`import_type`),
    KEY `dropshipping_import_history_imported_by_foreign` (`imported_by`)
    -- Note: Foreign key constraint to dropshipping_woocommerce_configs removed 
    -- because it references a table in the main database, not tenant database
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Plan Limits Table
CREATE TABLE IF NOT EXISTS `dropshipping_plan_limits` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `package_id` bigint(20) UNSIGNED NOT NULL,
    `monthly_import_limit` int(11) NOT NULL DEFAULT 100 COMMENT '-1 for unlimited',
    `total_import_limit` int(11) NOT NULL DEFAULT -1 COMMENT '-1 for unlimited',
    `bulk_import_limit` int(11) NOT NULL DEFAULT 20 COMMENT '-1 for unlimited',
    `monthly_research_limit` int(11) NOT NULL DEFAULT 50 COMMENT '-1 for unlimited',
    `total_research_limit` int(11) NOT NULL DEFAULT -1 COMMENT '-1 for unlimited',
    `auto_sync_enabled` tinyint(1) NOT NULL DEFAULT 0,
    `pricing_markup_min` decimal(5,2) NULL COMMENT 'Minimum markup percentage',
    `pricing_markup_max` decimal(5,2) NULL COMMENT 'Maximum markup percentage',
    `allowed_categories` longtext NULL COMMENT 'JSON array of allowed category IDs',
    `restricted_categories` longtext NULL COMMENT 'JSON array of restricted category IDs',
    `settings` longtext NULL COMMENT 'Additional settings JSON',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `dropshipping_plan_limits_package_id_unique` (`package_id`),
    KEY `dropshipping_plan_limits_package_id_index` (`package_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product Research Usage Tracking Table (goes in tenant databases)
CREATE TABLE IF NOT EXISTS `dropshipping_research_usage` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` varchar(255) NOT NULL,
    `product_id` bigint(20) UNSIGNED NOT NULL,
    `product_name` varchar(255) NOT NULL,
    `research_type` enum('full_research','price_comparison','seo_analysis','competitor_analysis') NOT NULL DEFAULT 'full_research',
    `api_calls_used` int(11) NOT NULL DEFAULT 1,
    `success` tinyint(1) NOT NULL DEFAULT 1,
    `error_message` text NULL,
    `research_data` longtext NULL COMMENT 'JSON data of research results',
    `researched_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `dropshipping_research_usage_tenant_id_index` (`tenant_id`),
    KEY `dropshipping_research_usage_product_id_index` (`product_id`),
    KEY `dropshipping_research_usage_researched_at_index` (`researched_at`),
    KEY `dropshipping_research_usage_research_type_index` (`research_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Plugin Settings Table
CREATE TABLE IF NOT EXISTS `dropshipping_settings` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `key` varchar(255) NOT NULL,
    `value` longtext NULL,
    `type` enum('string','integer','boolean','json','array') NOT NULL DEFAULT 'string',
    `description` text NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `dropshipping_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Withdrawal Settings Table
CREATE TABLE IF NOT EXISTS `withdrawal_settings` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `minimum_withdrawal_amount` decimal(10,2) NOT NULL DEFAULT 50.00,
    `maximum_withdrawal_amount` decimal(10,2) NULL,
    `withdrawal_fee_percentage` decimal(5,2) NOT NULL DEFAULT 0,
    `withdrawal_fee_fixed` decimal(10,2) NOT NULL DEFAULT 0,
    `withdrawal_processing_days` int(11) NOT NULL DEFAULT 3,
    `auto_approve_withdrawals` tinyint(1) NOT NULL DEFAULT 0,
    `withdrawal_terms` text NULL,
    `bank_requirements` text NULL,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT IGNORE INTO `dropshipping_settings` (`key`, `value`, `type`, `description`) VALUES
('auto_sync_interval', '24', 'integer', 'Auto sync interval in hours'),
('default_markup_percentage', '20', 'integer', 'Default markup percentage for imported products'),
('enable_auto_price_update', '0', 'boolean', 'Enable automatic price updates from WooCommerce'),
('enable_auto_stock_update', '1', 'boolean', 'Enable automatic stock updates from WooCommerce'),
('import_product_reviews', '0', 'boolean', 'Import product reviews along with products'),
('max_sync_products_per_batch', '50', 'integer', 'Maximum products to sync per batch'),
('notification_email', '', 'string', 'Email for import notifications'),
('enable_import_notifications', '1', 'boolean', 'Send notifications for import activities'),
-- Serper.dev Integration Settings
('serper_api_key', '', 'string', 'Serper.dev API key for product research'),
('enable_auto_research', '0', 'boolean', 'Enable automatic product research on view details'),
('research_results_limit', '10', 'integer', 'Maximum number of research results to fetch per product'),
('enable_price_tracking', '1', 'boolean', 'Enable price comparison and tracking'),
('enable_seo_analysis', '1', 'boolean', 'Enable SEO analysis and title optimization');

-- Insert default withdrawal settings
INSERT IGNORE INTO `withdrawal_settings` (`minimum_withdrawal_amount`, `maximum_withdrawal_amount`, `withdrawal_fee_percentage`, `withdrawal_fee_fixed`, `withdrawal_processing_days`, `auto_approve_withdrawals`, `withdrawal_terms`, `bank_requirements`, `is_active`, `created_at`, `updated_at`) VALUES
(50.00, NULL, 0, 0, 3, 0, NULL, NULL, 1, NOW(), NOW());

-- Note: Plan limits will be set when the plugin is activated for specific packages
-- This avoids referencing main database tables during tenant database creation

-- Note: Plugin registration is handled by the insertThirdPartyPluginTables method
-- to ensure compatibility with tenant database structure 