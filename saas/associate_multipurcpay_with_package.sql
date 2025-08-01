-- Associate Multipurcpay (ID: 18) with package ID 20
-- Replace 20 with your actual package ID if different

INSERT INTO `tl_saas_package_has_payment_methods` (`package_id`, `payment_method_id`) 
VALUES (20, 18)
ON DUPLICATE KEY UPDATE 
    `package_id` = 20,
    `payment_method_id` = 18;

-- If you want to add Multipurcpay to ALL packages, use this query instead:
-- INSERT INTO `tl_saas_package_has_payment_methods` (`package_id`, `payment_method_id`)
-- SELECT id, 18 FROM `tl_saas_packages` WHERE id NOT IN (
--     SELECT package_id FROM `tl_saas_package_has_payment_methods` WHERE payment_method_id = 18
-- );