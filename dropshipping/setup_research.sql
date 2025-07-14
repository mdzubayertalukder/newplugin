-- Setup Dropshipping Research Database Tables and Settings

-- Create dropshipping_settings table if it doesn't exist
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

-- Insert default settings for Serper.dev integration
INSERT IGNORE INTO `dropshipping_settings` (`key`, `value`, `type`, `description`, `created_at`, `updated_at`) VALUES
('auto_sync_interval', '24', 'integer', 'Auto sync interval in hours', NOW(), NOW()),
('default_markup_percentage', '20', 'integer', 'Default markup percentage for imported products', NOW(), NOW()),
('enable_auto_price_update', '0', 'boolean', 'Enable automatic price updates from WooCommerce', NOW(), NOW()),
('enable_auto_stock_update', '1', 'boolean', 'Enable automatic stock updates from WooCommerce', NOW(), NOW()),
('import_product_reviews', '0', 'boolean', 'Import product reviews along with products', NOW(), NOW()),
('max_sync_products_per_batch', '50', 'integer', 'Maximum products to sync per batch', NOW(), NOW()),
('notification_email', '', 'string', 'Email for import notifications', NOW(), NOW()),
('enable_import_notifications', '1', 'boolean', 'Send notifications for import activities', NOW(), NOW()),
-- Serper.dev Integration Settings
('serper_api_key', '', 'string', 'Serper.dev API key for product research', NOW(), NOW()),
('enable_auto_research', '0', 'boolean', 'Enable automatic product research on view details', NOW(), NOW()),
('research_results_limit', '10', 'integer', 'Maximum number of research results to fetch per product', NOW(), NOW()),
('enable_price_tracking', '1', 'boolean', 'Enable price comparison and tracking', NOW(), NOW()),
('enable_seo_analysis', '1', 'boolean', 'Enable SEO analysis and title optimization', NOW(), NOW());

-- Check if data was inserted
SELECT 'Settings table setup completed. Please configure your Serper.dev API key in Admin → Dropshipping → Settings' as status; 