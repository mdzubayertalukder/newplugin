<?php
// Debug script for Dropshipping Research functionality
// Access this via: /dropshipping/debug_research.php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use Plugin\Dropshipping\Services\SerperService;

echo "<h1>Dropshipping Research Debug</h1>";

// 1. Check if dropshipping_settings table exists
echo "<h2>1. Database Tables Check</h2>";
try {
    $tables = DB::select("SHOW TABLES LIKE 'dropshipping_settings'");
    if (count($tables) > 0) {
        echo "✅ dropshipping_settings table exists<br>";
        
        // Check settings
        $settings = DB::table('dropshipping_settings')->pluck('value', 'key');
        echo "<h3>Current Settings:</h3>";
        echo "<pre>";
        foreach ($settings as $key => $value) {
            if ($key === 'serper_api_key') {
                $value = $value ? '***CONFIGURED***' : 'NOT_SET';
            }
            echo "$key: $value\n";
        }
        echo "</pre>";
    } else {
        echo "❌ dropshipping_settings table does NOT exist<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking tables: " . $e->getMessage() . "<br>";
}

// 2. Check ProductResearchController
echo "<h2>2. Controller Check</h2>";
$controllerPath = __DIR__ . '/src/Http/Controllers/Tenant/ProductResearchController.php';
if (file_exists($controllerPath)) {
    echo "✅ ProductResearchController exists<br>";
} else {
    echo "❌ ProductResearchController NOT found<br>";
}

// 3. Check SerperService
echo "<h2>3. SerperService Check</h2>";
try {
    $serperService = new SerperService();
    if ($serperService->isEnabled()) {
        echo "✅ SerperService is enabled and configured<br>";
    } else {
        echo "❌ SerperService is NOT enabled (API key missing)<br>";
    }
} catch (Exception $e) {
    echo "❌ Error with SerperService: " . $e->getMessage() . "<br>";
}

// 4. Check routes
echo "<h2>4. Routes Check</h2>";
$routesPath = __DIR__ . '/routes/user.php';
if (file_exists($routesPath)) {
    $routesContent = file_get_contents($routesPath);
    if (strpos($routesContent, 'ProductResearchController') !== false) {
        echo "✅ Research routes are defined<br>";
    } else {
        echo "❌ Research routes NOT found in user.php<br>";
    }
} else {
    echo "❌ user.php routes file NOT found<br>";
}

// 5. Test database connection
echo "<h2>5. Database Connection Test</h2>";
try {
    $product = DB::connection('mysql')->table('dropshipping_products')
        ->where('status', 'publish')
        ->first();
    
    if ($product) {
        echo "✅ Can connect to dropshipping_products table<br>";
        echo "Sample product: {$product->name}<br>";
    } else {
        echo "⚠️ No published products found<br>";
    }
} catch (Exception $e) {
    echo "❌ Database connection error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h2>Recommendations:</h2>";
echo "<ol>";
echo "<li>Make sure Serper API key is configured in Admin → Dropshipping → Settings</li>";
echo "<li>Check if dropshipping_settings table exists in your database</li>";
echo "<li>Verify routes are properly loaded</li>";
echo "<li>Check browser console for JavaScript errors</li>";
echo "<li>Check Laravel logs for PHP errors</li>";
echo "</ol>";

echo "<p><a href='/dropshipping/all-products'>← Back to Products</a></p>";
?> 