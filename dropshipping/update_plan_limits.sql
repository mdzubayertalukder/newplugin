-- Update Plan Limits Database Script
-- This script adds research limits to existing dropshipping_plan_limits table
-- and creates the research usage tracking table

-- Add research limit columns to existing dropshipping_plan_limits table
ALTER TABLE `dropshipping_plan_limits` 
ADD COLUMN `monthly_research_limit` int(11) NOT NULL DEFAULT 50 COMMENT '-1 for unlimited' AFTER `bulk_import_limit`,
ADD COLUMN `total_research_limit` int(11) NOT NULL DEFAULT -1 COMMENT '-1 for unlimited' AFTER `monthly_research_limit`;

-- Create the research usage tracking table in tenant databases
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

-- Insert default research limits for existing packages
UPDATE `dropshipping_plan_limits` 
SET `monthly_research_limit` = 10, `total_research_limit` = -1 
WHERE `monthly_research_limit` = 0 OR `monthly_research_limit` IS NULL;

-- Update limits based on package type (you may need to adjust these based on your package structure)
-- For free packages
UPDATE `dropshipping_plan_limits` 
SET `monthly_research_limit` = 5 
WHERE `package_id` IN (
    SELECT id FROM `tl_saas_packages` WHERE `type` = 'free'
);

-- For paid packages
UPDATE `dropshipping_plan_limits` 
SET `monthly_research_limit` = 100 
WHERE `package_id` IN (
    SELECT id FROM `tl_saas_packages` WHERE `type` = 'paid'
);

-- Create default limits for packages that don't have any limits yet
INSERT INTO `dropshipping_plan_limits` (`package_id`, `monthly_import_limit`, `total_import_limit`, `bulk_import_limit`, `monthly_research_limit`, `total_research_limit`, `auto_sync_enabled`, `settings`, `created_at`, `updated_at`)
SELECT 
    p.id,
    CASE 
        WHEN p.type = 'free' THEN 5
        WHEN p.type = 'paid' THEN 100
        ELSE 10
    END as monthly_import_limit,
    -1 as total_import_limit,
    CASE 
        WHEN p.type = 'free' THEN 2
        WHEN p.type = 'paid' THEN 20
        ELSE 5
    END as bulk_import_limit,
    CASE 
        WHEN p.type = 'free' THEN 5
        WHEN p.type = 'paid' THEN 100
        ELSE 10
    END as monthly_research_limit,
    -1 as total_research_limit,
    CASE 
        WHEN p.type = 'paid' THEN 1
        ELSE 0
    END as auto_sync_enabled,
    '{"auto_update_prices":false,"auto_update_stock":false,"import_reviews":false}' as settings,
    NOW() as created_at,
    NOW() as updated_at
FROM `tl_saas_packages` p
LEFT JOIN `dropshipping_plan_limits` l ON p.id = l.package_id
WHERE l.package_id IS NULL;

-- Create a view for easy package limits overview (optional)
CREATE OR REPLACE VIEW `dropshipping_package_limits_view` AS
SELECT 
    p.id as package_id,
    p.name as package_name,
    p.type as package_type,
    l.monthly_import_limit,
    l.total_import_limit,
    l.bulk_import_limit,
    l.monthly_research_limit,
    l.total_research_limit,
    l.auto_sync_enabled,
    l.created_at as limits_created_at,
    l.updated_at as limits_updated_at
FROM `tl_saas_packages` p
LEFT JOIN `dropshipping_plan_limits` l ON p.id = l.package_id
ORDER BY p.id; 