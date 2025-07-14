<?php
// Simple setup script for Dropshipping Research Database
// Run this once to set up the required database tables

require_once __DIR__ . '/../../bootstrap/app.php';

use Illuminate\Support\Facades\DB;

echo "Setting up Dropshipping Research Database...\n";

try {
    // Create dropshipping_settings table
    DB::statement("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    echo "✅ dropshipping_settings table created/verified\n";
    
    // Insert default settings
    $settings = [
        'auto_sync_interval' => ['value' => '24', 'type' => 'integer', 'description' => 'Auto sync interval in hours'],
        'default_markup_percentage' => ['value' => '20', 'type' => 'integer', 'description' => 'Default markup percentage for imported products'],
        'enable_auto_price_update' => ['value' => '0', 'type' => 'boolean', 'description' => 'Enable automatic price updates from WooCommerce'],
        'enable_auto_stock_update' => ['value' => '1', 'type' => 'boolean', 'description' => 'Enable automatic stock updates from WooCommerce'],
        'import_product_reviews' => ['value' => '0', 'type' => 'boolean', 'description' => 'Import product reviews along with products'],
        'max_sync_products_per_batch' => ['value' => '50', 'type' => 'integer', 'description' => 'Maximum products to sync per batch'],
        'notification_email' => ['value' => '', 'type' => 'string', 'description' => 'Email for import notifications'],
        'enable_import_notifications' => ['value' => '1', 'type' => 'boolean', 'description' => 'Send notifications for import activities'],
        'serper_api_key' => ['value' => '', 'type' => 'string', 'description' => 'Serper.dev API key for product research'],
        'enable_auto_research' => ['value' => '0', 'type' => 'boolean', 'description' => 'Enable automatic product research on view details'],
        'research_results_limit' => ['value' => '10', 'type' => 'integer', 'description' => 'Maximum number of research results to fetch per product'],
        'enable_price_tracking' => ['value' => '1', 'type' => 'boolean', 'description' => 'Enable price comparison and tracking'],
        'enable_seo_analysis' => ['value' => '1', 'type' => 'boolean', 'description' => 'Enable SEO analysis and title optimization'],
    ];
    
    foreach ($settings as $key => $config) {
        DB::table('dropshipping_settings')->updateOrInsert(
            ['key' => $key],
            [
                'value' => $config['value'],
                'type' => $config['type'],
                'description' => $config['description'],
                'created_at' => now(),
                'updated_at' => now()
            ]
        );
    }
    
    echo "✅ Default settings inserted/updated\n";
    
    // Verify the setup
    $settingsCount = DB::table('dropshipping_settings')->count();
    echo "✅ Total settings in database: $settingsCount\n";
    
    echo "\n🎉 Setup completed successfully!\n";
    echo "\nNext steps:\n";
    echo "1. Go to Admin Panel → Dropshipping → Settings\n";
    echo "2. Configure your Serper.dev API key\n";
    echo "3. Test the research functionality on a product\n";
    echo "\nYou can delete this setup file after successful setup.\n";
    
} catch (Exception $e) {
    echo "❌ Error during setup: " . $e->getMessage() . "\n";
    echo "Please check your database configuration and try again.\n";
}
?> 