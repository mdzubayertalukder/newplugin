-- Insert Multipurcpay payment method into tl_saas_payment_methods table
-- Run this SQL script if you prefer direct database insertion over migration

INSERT INTO `tl_saas_payment_methods` (`id`, `name`, `status`, `created_at`, `updated_at`) 
VALUES (18, 'multipurcpay', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE 
    `name` = 'multipurcpay',
    `status` = 1,
    `updated_at` = NOW();