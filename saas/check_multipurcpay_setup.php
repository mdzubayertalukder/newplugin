<?php
/**
 * Quick check for Multipurcpay setup issues
 * Run this to verify Multipurcpay is properly configured
 */

echo "🔍 Multipurcpay Setup Checker\n";
echo "============================\n\n";

// Check if we're in Laravel environment
if (!function_exists('config')) {
    echo "❌ This script needs to be run in Laravel environment\n";
    echo "💡 Try running: php artisan tinker\n";
    echo "💡 Then copy-paste the code below:\n\n";
    
    echo "// Check payment methods\n";
    echo "\$methods = DB::table('tl_saas_payment_methods')->get();\n";
    echo "foreach(\$methods as \$method) {\n";
    echo "    echo \$method->name . ' - Status: ' . \$method->status . \"\\n\";\n";
    echo "}\n\n";
    
    echo "// Check Multipurcpay specifically\n";
    echo "\$multipurcpay = DB::table('tl_saas_payment_methods')->where('name', 'LIKE', '%multipurcpay%')->first();\n";
    echo "if(\$multipurcpay) {\n";
    echo "    echo 'Multipurcpay ID: ' . \$multipurcpay->id . \"\\n\";\n";
    echo "    echo 'Status: ' . \$multipurcpay->status . \"\\n\";\n";
    echo "} else {\n";
    echo "    echo 'Multipurcpay not found!\\n';\n";
    echo "}\n\n";
    
    echo "// Check configuration\n";
    echo "\$config = DB::table('tl_saas_payment_method_configurations')->where('payment_method_id', \$multipurcpay->id ?? 0)->get();\n";
    echo "foreach(\$config as \$conf) {\n";
    echo "    echo \$conf->key . ': ' . \$conf->value . \"\\n\";\n";
    echo "}\n";
    
    exit;
}

// If we're in Laravel environment, run the checks
try {
    echo "📋 Checking Payment Methods:\n";
    $methods = DB::table('tl_saas_payment_methods')->get();
    foreach($methods as $method) {
        $status = $method->status == 1 ? '✅ ACTIVE' : '❌ INACTIVE';
        echo "- {$method->name} (ID: {$method->id}) - {$status}\n";
    }
    echo "\n";
    
    echo "🎯 Checking Multipurcpay Specifically:\n";
    $multipurcpay = DB::table('tl_saas_payment_methods')
        ->where('name', 'LIKE', '%multipurcpay%')
        ->orWhere('name', 'LIKE', '%Multipurcpay%')
        ->first();
        
    if($multipurcpay) {
        $status = $multipurcpay->status == 1 ? '✅ ACTIVE' : '❌ INACTIVE';
        echo "- Found: {$multipurcpay->name} (ID: {$multipurcpay->id}) - {$status}\n";
        
        if($multipurcpay->status != 1) {
            echo "⚠️  WARNING: Multipurcpay is INACTIVE!\n";
            echo "💡 Solution: Enable it in Admin Panel > Payment Methods\n";
        }
        
        echo "\n🔧 Checking Configuration:\n";
        $config = DB::table('tl_saas_payment_method_configurations')
            ->where('payment_method_id', $multipurcpay->id)
            ->get();
            
        if($config->count() > 0) {
            foreach($config as $conf) {
                $value = strlen($conf->value) > 20 ? substr($conf->value, 0, 20) . '...' : $conf->value;
                echo "- {$conf->key}: {$value}\n";
            }
        } else {
            echo "❌ No configuration found!\n";
            echo "💡 Solution: Configure API key in Admin Panel\n";
        }
        
    } else {
        echo "❌ Multipurcpay not found in payment methods!\n";
        echo "💡 Solution: Run the migration or SQL insert script\n";
    }
    
    echo "\n📦 Checking Package Association:\n";
    if(isset($multipurcpay)) {
        $associations = DB::table('tl_saas_package_has_payment_methods')
            ->where('payment_method_id', $multipurcpay->id)
            ->count();
        echo "- Packages associated with Multipurcpay: {$associations}\n";
        
        if($associations == 0) {
            echo "⚠️  WARNING: No packages are associated with Multipurcpay!\n";
            echo "💡 Solution: Associate packages in Admin Panel\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🚀 Quick Fix Commands:\n";
echo "If Multipurcpay is inactive, run this in tinker:\n";
echo "DB::table('tl_saas_payment_methods')->where('name', 'LIKE', '%multipurcpay%')->update(['status' => 1]);\n";

echo "\n" . str_repeat("=", 50) . "\n";
echo "Check completed at " . date('Y-m-d H:i:s') . "\n";