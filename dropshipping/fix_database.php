<?php

/**
 * Dropshipping Database Fix Script
 * 
 * This script creates the missing dropshipping plugin tables for tenant databases.
 * Run this script when you encounter "Table 'tenant_balances' doesn't exist" errors.
 * 
 * Usage: php dropshipping/fix_database.php
 */

// Ensure this script is run from the correct directory
$rootPath = getcwd();
if (!file_exists($rootPath . '/index.php')) {
    die("Error: Please run this script from the main application directory.\nCurrent directory: $rootPath\n");
}

// Include the Laravel application
require_once $rootPath . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once $rootPath . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Dropshipping Database Fix Script\n";
echo "=================================\n\n";

try {
    // Get current database name
    $databaseName = DB::connection()->getDatabaseName();
    echo "Current database: {$databaseName}\n\n";

    // Check which tables are missing
    $requiredTables = [
        'dropshipping_orders',
        'tenant_balances',
        'withdrawal_requests',
        'dropshipping_woocommerce_configs',
        'dropshipping_products',
        'dropshipping_product_import_history',
        'dropshipping_plan_limits',
        'dropshipping_settings'
    ];

    $missingTables = [];
    foreach ($requiredTables as $table) {
        if (!Schema::hasTable($table)) {
            $missingTables[] = $table;
        }
    }

    if (empty($missingTables)) {
        echo "✅ All dropshipping tables already exist.\n";
        exit(0);
    }

    echo "Missing tables found: " . implode(', ', $missingTables) . "\n\n";
    echo "Creating missing tables...\n\n";

    // Read and execute the SQL from data.sql
    $sqlFile = __DIR__ . '/data.sql';
    if (!file_exists($sqlFile)) {
        die("Error: data.sql file not found at {$sqlFile}\n");
    }

    $sql = file_get_contents($sqlFile);

    // Split the SQL into individual statements
    $statements = explode(';', $sql);

    $createdTables = [];
    $errors = [];

    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }

        try {
            DB::statement($statement);

            // Check if this was a CREATE TABLE statement
            if (preg_match('/CREATE TABLE IF NOT EXISTS `?(\w+)`?/i', $statement, $matches)) {
                $tableName = $matches[1];
                if (in_array($tableName, $missingTables)) {
                    $createdTables[] = $tableName;
                    echo "✅ Created table: {$tableName}\n";
                }
            }
        } catch (Exception $e) {
            $errors[] = [
                'statement' => substr($statement, 0, 100) . '...',
                'error' => $e->getMessage()
            ];
        }
    }

    echo "\n=================================\n";
    echo "Summary:\n";
    echo "✅ Created " . count($createdTables) . " tables\n";

    if (!empty($errors)) {
        echo "❌ " . count($errors) . " errors occurred\n\n";
        echo "Errors:\n";
        foreach ($errors as $error) {
            echo "- " . $error['error'] . "\n";
        }
    }

    // Verify all required tables now exist
    echo "\nVerifying tables...\n";
    $stillMissing = [];
    foreach ($requiredTables as $table) {
        if (!Schema::hasTable($table)) {
            $stillMissing[] = $table;
        }
    }

    if (empty($stillMissing)) {
        echo "✅ All dropshipping tables are now present!\n";
        echo "\nYou should now be able to access the dropshipping features without errors.\n";
    } else {
        echo "❌ Still missing: " . implode(', ', $stillMissing) . "\n";
        echo "Please check the errors above and try running the script again.\n";
    }
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\nScript completed.\n";
