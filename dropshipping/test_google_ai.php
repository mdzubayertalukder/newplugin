<?php

// Simple test script to verify Google AI integration
require_once 'vendor/autoload.php';

use Plugin\Dropshipping\Services\GoogleAIStudioService;
use Illuminate\Support\Facades\DB;

// Mock the DB facade for testing
class MockDB {
    public static function table($table) {
        return new MockQueryBuilder();
    }
}

class MockQueryBuilder {
    public function pluck($value, $key) {
        // Return mock settings with Google AI key
        return collect([
            'google_ai_studio_api_key' => 'YOUR_GOOGLE_AI_KEY_HERE', // Replace with actual key
            'ai_service' => 'google'
        ]);
    }
}

// Test the Google AI service directly
$service = new GoogleAIStudioService();

// Set a test API key (you would need to replace this with actual key)
$service->setApiKey('YOUR_GOOGLE_AI_KEY_HERE');

if ($service->isEnabled()) {
    echo "Google AI Service is enabled\n";
    
    // Test with a simple product research
    $prompt = "Research the product: wireless headphones with cost price ৳500 for Bangladesh market";
    
    echo "Testing Google AI with prompt: $prompt\n";
    
    $result = $service->researchProduct($prompt);
    
    if ($result['success']) {
        echo "SUCCESS: Google AI returned data\n";
        echo "Product name: " . ($result['data']['product_name'] ?? 'Not found') . "\n";
        echo "Viability score: " . ($result['data']['dropshipping_analysis']['viability_score'] ?? 'Not found') . "\n";
        echo "Competition level: " . ($result['data']['dropshipping_analysis']['competition_level'] ?? 'Not found') . "\n";
    } else {
        echo "FAILED: " . $result['message'] . "\n";
    }
} else {
    echo "Google AI Service is not enabled - check API key\n";
}