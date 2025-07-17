<?php

require_once 'vendor/autoload.php';

use Plugin\Dropshipping\Services\GoogleSearchService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Mock the DB facade for testing
class MockDB {
    public static function table($table) {
        return new MockTable();
    }
}

class MockTable {
    public function pluck($value, $key) {
        // Return mock settings for testing
        return collect([
            'google_search_api_key' => 'YOUR_GOOGLE_SEARCH_API_KEY_HERE',
            'google_search_engine_id' => 'YOUR_SEARCH_ENGINE_ID_HERE'
        ]);
    }
}

class MockLog {
    public static function info($message, $context = []) {
        echo "[INFO] $message\n";
        if (!empty($context)) {
            echo "Context: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
        }
    }
    
    public static function error($message, $context = []) {
        echo "[ERROR] $message\n";
        if (!empty($context)) {
            echo "Context: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
        }
    }
    
    public static function warning($message, $context = []) {
        echo "[WARNING] $message\n";
        if (!empty($context)) {
            echo "Context: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
        }
    }
}

// Override the facades
class_alias('MockDB', 'Illuminate\Support\Facades\DB');
class_alias('MockLog', 'Illuminate\Support\Facades\Log');

echo "=== Enhanced Google Search Service Test ===\n\n";

try {
    $googleSearchService = new GoogleSearchService();
    
    // Test product search with comprehensive Bangladesh website coverage
    $testProduct = "wireless bluetooth headphones";
    
    echo "Testing comprehensive product search for: '$testProduct'\n";
    echo "This will search across 25+ Bangladesh e-commerce websites...\n\n";
    
    $result = $googleSearchService->searchProduct($testProduct);
    
    if ($result['success']) {
        $data = $result['data'];
        
        echo "✅ Search completed successfully!\n\n";
        
        echo "=== COMPREHENSIVE SEARCH RESULTS ===\n";
        echo "Product: " . $data['product_name'] . "\n";
        echo "Total sites searched: " . ($data['comprehensive_data']['total_sites_searched'] ?? 0) . "\n";
        echo "Sites with results: " . ($data['comprehensive_data']['sites_with_results'] ?? 0) . "\n";
        echo "Total products found: " . ($data['comprehensive_data']['total_products_found'] ?? 0) . "\n";
        echo "Price range: " . ($data['comprehensive_data']['price_range']['formatted'] ?? 'Not available') . "\n\n";
        
        // Show sample products found
        if (!empty($data['all_products'])) {
            echo "=== SAMPLE PRODUCTS FOUND ===\n";
            foreach (array_slice($data['all_products'], 0, 5) as $index => $product) {
                echo ($index + 1) . ". " . $product['product_name'] . "\n";
                echo "   Price: " . $product['formatted_price'] . "\n";
                echo "   Site: " . $product['site_name'] . "\n";
                echo "   URL: " . $product['product_link'] . "\n";
                echo "   Relevance: " . $product['relevance_score'] . "/100\n\n";
            }
        }
        
        // Show competitor analysis
        if (!empty($data['competitor_analysis'])) {
            echo "=== COMPETITOR ANALYSIS ===\n";
            foreach ($data['competitor_analysis'] as $competitor) {
                echo "Site: " . $competitor['site_name'] . "\n";
                echo "Products found: " . $competitor['product_count'] . "\n";
                echo "Price range: " . $competitor['price_range'] . "\n";
                echo "Average price: ৳" . $competitor['avg_price'] . "\n";
                echo "Market presence: " . $competitor['market_presence'] . "%\n\n";
            }
        }
        
        // Show price analysis
        if (!empty($data['price_analysis']) && $data['price_analysis']['found_prices']) {
            echo "=== PRICE ANALYSIS ===\n";
            $priceData = $data['price_analysis'];
            echo "Total prices found: " . $priceData['total_prices_found'] . "\n";
            echo "Price range: " . $priceData['formatted_range'] . "\n";
            echo "Average price: ৳" . $priceData['average_price'] . "\n";
            echo "Median price: ৳" . $priceData['median_price'] . "\n\n";
            
            echo "Price Distribution:\n";
            echo "- Under ৳500: " . $priceData['price_distribution']['under_500'] . " products\n";
            echo "- ৳500-1000: " . $priceData['price_distribution']['500_to_1000'] . " products\n";
            echo "- ৳1000-2000: " . $priceData['price_distribution']['1000_to_2000'] . " products\n";
            echo "- Over ৳2000: " . $priceData['price_distribution']['over_2000'] . " products\n\n";
        }
        
        // Show market insights
        if (!empty($data['market_insights'])) {
            echo "=== MARKET INSIGHTS ===\n";
            $insights = $data['market_insights'];
            echo "Market availability: " . $insights['market_availability'] . "\n";
            echo "Competition level: " . $insights['competition_level'] . "\n";
            echo "Market saturation: " . $insights['market_saturation'] . "\n";
            echo "Most active site: " . ($insights['search_insights']['most_active_site'] ?? 'N/A') . "\n\n";
        }
        
        // Show top performing sites
        if (!empty($data['comprehensive_data']['top_sites'])) {
            echo "=== TOP PERFORMING SITES ===\n";
            foreach (array_slice($data['comprehensive_data']['top_sites'], 0, 5) as $site) {
                echo "Site: " . $site['site_name'] . "\n";
                echo "Product pages: " . $site['product_pages'] . "\n";
                echo "Products with prices: " . $site['products_with_prices'] . "\n";
                echo "Price coverage: " . $site['price_coverage'] . "%\n\n";
            }
        }
        
    } else {
        echo "❌ Search failed: " . $result['message'] . "\n";
        if (isset($result['debug'])) {
            echo "Debug info:\n";
            print_r($result['debug']);
        }
    }
    
} catch (Exception $e) {
    echo "❌ Exception occurred: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test completed ===\n";
echo "Note: To run this test with real data, update the API keys in the MockTable class above.\n";