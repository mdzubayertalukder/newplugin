-- Dropshipping Plugin Tables for Tenant Database
-- Run this SQL on your tenant database: multipurc_dealfinal11111
-- 
-- IMPORTANT: Connect to your tenant database first!
-- USE multipurc_dealfinal11111;

-- 1. Create tenant_balances table (fixes the main error)
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

-- 2. Create dropshipping_orders table
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

-- 3. Create withdrawal_requests table
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

-- 4. Verification queries (run these to confirm tables were created)
SELECT 'tenant_balances' as table_name, COUNT(*) as row_count FROM tenant_balances
UNION ALL
SELECT 'dropshipping_orders' as table_name, COUNT(*) as row_count FROM dropshipping_orders  
UNION ALL
SELECT 'withdrawal_requests' as table_name, COUNT(*) as row_count FROM withdrawal_requests;

-- 5. Show table structure
SHOW TABLES LIKE '%tenant_balances%';
SHOW TABLES LIKE '%dropshipping%';
SHOW TABLES LIKE '%withdrawal%'; 