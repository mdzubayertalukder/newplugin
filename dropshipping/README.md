# Dropshipping Plugin Database Fix

## Issue: "Table 'tenant_balances' doesn't exist" Error

If you're encountering this error:
```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'multipurc_dealfinal11111.tenant_balances' doesn't exist
```

This happens because the dropshipping plugin's database tables haven't been created for your tenant database.

## Quick Fix

### Option 1: Run the Fix Script (Recommended)

From your project root directory, run:

```bash
php plugins/dropshipping/fix_database.php
```

This script will:
- Check which dropshipping tables are missing
- Create all required tables automatically
- Verify the installation was successful

### Option 2: Manual SQL Execution

If the script doesn't work, you can manually run the SQL statements:

1. Connect to your tenant database (e.g., `multipurc_dealfinal11111`)
2. Execute the SQL from `plugins/dropshipping/data.sql`

The most important tables for fixing the immediate error are:

```sql
-- Tenant Balances Table (fixes the main error)
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
```

### Option 3: Laravel Migrations (For New Tenants)

For new tenant setups, ensure the dropshipping migrations are run:

```bash
php artisan migrate --path=/plugins/dropshipping/database/migrations
```

## What Was Fixed

1. **Column Mismatch**: Fixed `withdrawn_amount` vs `total_withdrawn` column naming inconsistency
2. **Missing Tables**: Added all required dropshipping tables to `data.sql`
3. **Database Setup**: Created a fix script to automatically resolve missing table issues

## Required Tables

The dropshipping plugin requires these tables:
- `tenant_balances` - Stores tenant earning balances
- `dropshipping_orders` - Stores dropshipping orders
- `withdrawal_requests` - Stores withdrawal requests
- `dropshipping_woocommerce_configs` - WooCommerce API configurations
- `dropshipping_products` - Imported products from WooCommerce
- `dropshipping_product_import_history` - Import history tracking
- `dropshipping_plan_limits` - Plan-based limitations
- `dropshipping_settings` - Plugin settings

## Testing the Fix

After running the fix:

1. Visit your dropshipping order management page
2. The error should be resolved
3. You should see an empty balance and order list (for new tenants)

## Prevention for Future Tenants

To prevent this issue for new tenants:
1. Ensure the `data.sql` file is properly loaded when new tenant databases are created
2. Or run the dropshipping migrations as part of the tenant setup process

## Support

If you continue to experience issues after running the fix script, check:
1. Database permissions
2. PHP error logs
3. Laravel logs in `storage/logs/`

The fix script will provide detailed output about what was created and any errors encountered. 