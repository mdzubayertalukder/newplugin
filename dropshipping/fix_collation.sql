-- Fix collation issues between tables
-- This script ensures all relevant tables use the same collation

-- Check current collations
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    COLLATION_NAME
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME IN ('tl_saas_accounts', 'tenants', 'tl_saas_packages')
    AND COLUMN_NAME IN ('id', 'tenant_id', 'package_id');

-- Fix tl_saas_accounts table collation if needed
ALTER TABLE `tl_saas_accounts` 
MODIFY COLUMN `tenant_id` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Fix tenants table collation if needed
ALTER TABLE `tenants` 
MODIFY COLUMN `id` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Fix tl_saas_packages table collation if needed (if it exists)
-- ALTER TABLE `tl_saas_packages` 
-- MODIFY COLUMN `id` BIGINT(20) UNSIGNED;

-- Verify the changes
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    COLLATION_NAME
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME IN ('tl_saas_accounts', 'tenants', 'tl_saas_packages')
    AND COLUMN_NAME IN ('id', 'tenant_id', 'package_id');

-- Note: Run this script carefully and backup your database first
-- The exact column types may vary, so adjust the ALTER statements accordingly 