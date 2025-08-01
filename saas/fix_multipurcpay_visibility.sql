-- Fix Multipurcpay Visibility Issues
-- Run this SQL script to resolve the payment gateway visibility problem

-- 1. Ensure multipurcpay is in the payment methods table and is active
INSERT INTO `tl_saas_payment_methods` (`id`, `name`, `status`, `created_at`, `updated_at`) 
VALUES (18, 'multipurcpay', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE 
    `name` = 'multipurcpay',
    `status` = 1,
    `updated_at` = NOW();

-- 2. Associate multipurcpay with ALL packages (so it appears for all users)
-- First, get all package IDs and associate them with multipurcpay (ID: 18)
INSERT INTO `tl_saas_package_has_payment_methods` (`package_id`, `payment_method_id`)
SELECT p.id, 18 
FROM `tl_saas_packages` p 
WHERE p.id NOT IN (
    SELECT package_id 
    FROM `tl_saas_package_has_payment_methods` 
    WHERE payment_method_id = 18
);

-- 3. Verify the associations were created
SELECT 
    p.name as package_name,
    pm.name as payment_method_name,
    pm.status as payment_method_status
FROM `tl_saas_packages` p
JOIN `tl_saas_package_has_payment_methods` ppm ON p.id = ppm.package_id
JOIN `tl_saas_payment_methods` pm ON ppm.payment_method_id = pm.id
WHERE pm.name = 'multipurcpay'
ORDER BY p.name;