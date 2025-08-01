<?php
/**
 * Debug script for Package Confirmation issues
 * Run this to check what's happening during package confirmation
 */

echo "🔍 Package Confirmation Debug Script\n";
echo "===================================\n\n";

// Check if log files exist
$debug_log = storage_path('logs/store_controller_debug.log');
$error_log = storage_path('logs/package_confirmation_error.log');

echo "📁 Checking log files:\n";
echo "Debug log: " . ($debug_log ? "EXISTS" : "NOT FOUND") . "\n";
echo "Error log: " . ($error_log ? "EXISTS" : "NOT FOUND") . "\n\n";

// Read debug log if exists
if (file_exists($debug_log)) {
    echo "📋 Latest Debug Log Entry:\n";
    echo str_repeat("-", 50) . "\n";
    $debug_content = file_get_contents($debug_log);
    $debug_entries = explode("--- New Request ---", $debug_content);
    $latest_debug = end($debug_entries);
    echo $latest_debug;
    echo str_repeat("-", 50) . "\n\n";
}

// Read error log if exists
if (file_exists($error_log)) {
    echo "❌ Latest Error Log Entry:\n";
    echo str_repeat("-", 50) . "\n";
    $error_content = file_get_contents($error_log);
    $error_entries = explode("--- Package Confirmation Error ---", $error_content);
    $latest_error = end($error_entries);
    echo $latest_error;
    echo str_repeat("-", 50) . "\n\n";
}

// Check database tables
echo "🗄️ Database Table Checks:\n";

try {
    // Check if we can connect to database (basic check)
    $pdo = new PDO("mysql:host=localhost;dbname=your_database", "username", "password");
    echo "✅ Database connection: OK\n";
    
    // Check payment methods table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tl_saas_payment_methods WHERE status = 1");
    $result = $stmt->fetch();
    echo "💳 Active payment methods: " . $result['count'] . "\n";
    
    // Check multipurcpay specifically
    $stmt = $pdo->query("SELECT id, name, status FROM tl_saas_payment_methods WHERE LOWER(name) LIKE '%multipurcpay%'");
    $multipurcpay = $stmt->fetch();
    if ($multipurcpay) {
        echo "🎯 Multipurcpay found: ID=" . $multipurcpay['id'] . ", Status=" . $multipurcpay['status'] . "\n";
    } else {
        echo "❌ Multipurcpay not found in payment methods\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "💡 Please update database credentials in this script\n";
}

echo "\n🔧 Troubleshooting Steps:\n";
echo "1. Check if Multipurcpay is enabled in admin panel\n";
echo "2. Verify all required form fields are filled\n";
echo "3. Check browser console for JavaScript errors\n";
echo "4. Review the error logs above for specific issues\n";
echo "5. Ensure database tables exist and have correct structure\n";

echo "\n📝 Next Steps:\n";
echo "1. Try submitting the form again\n";
echo "2. Check the log files created in storage/logs/\n";
echo "3. Run this script again to see new error details\n";

echo "\n" . str_repeat("=", 50) . "\n";
echo "Debug completed at " . date('Y-m-d H:i:s') . "\n";