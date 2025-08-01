<?php
/**
 * Test script for Multipurcpay API integration
 * This script tests the API connection using the provided example code
 */

// Step 1: Include the Guzzle autoloader (if available)
// require 'vendor/autoload.php';
// For testing without Guzzle, we'll use cURL

// --- Configuration (from your details) ---
const API_KEY = '1086227048687936865b7fa20065340062067396923687936865b805260911739';
const API_URL = 'https://aidroppay.xyz/api/create-charge';

// Sample data for creating a charge
$chargeData = [
    'full_name'    => 'Test Customer',
    'email_mobile' => 'test@example.com',
    'amount'       => '1',
    'currency'     => 'BDT',
    'redirect_url' => 'https://example.com/success',
    'cancel_url'   => 'https://example.com/cancel',
    'webhook_url'  => 'https://example.com/webhook',
    'return_type'  => 'GET',
    'metadata'     => ['order_id' => 'order_123']
];

echo "🚀 Testing Multipurcpay API Integration\n";
echo "=====================================\n\n";

echo "📋 Request Data:\n";
print_r($chargeData);
echo "\n";

// Initialize cURL
$ch = curl_init();

// Set cURL options
curl_setopt_array($ch, [
    CURLOPT_URL => API_URL,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($chargeData),
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Content-Type: application/json',
        // ✅ CORRECT HEADER FORMAT FOR AIDROPPAY:
        'mh-piprapay-api-key: ' . API_KEY,
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false, // For testing only
]);

echo "🔄 Making API Request...\n";

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

if ($error) {
    echo "❌ cURL Error: $error\n";
    exit(1);
}

echo "📊 Response Status Code: $httpCode\n";
echo "📄 Response Body:\n";
echo $response . "\n\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    
    if ($data && isset($data['pp_url'])) {
        echo "✅ Success! API Response Received:\n\n";
        echo "🚀 Payment URL Generated:\n";
        echo $data['pp_url'] . "\n";
        echo "\n📋 Payment Details:\n";
        echo "Payment ID: " . ($data['pp_id'] ?? 'N/A') . "\n";
        echo "Status: " . (isset($data['status']) && $data['status'] ? 'Success' : 'Failed') . "\n";
        
        echo "\n💡 Next Steps:\n";
        echo "1. Open the payment URL in your browser to test\n";
        echo "2. Check your Aidroppay dashboard for the transaction\n";
        echo "3. Test the complete payment flow\n";
    } else {
        echo "⚠️ Warning: Response received but no payment URL found\n";
        echo "Expected 'pp_url' key in response\n";
    }
} else {
    echo "❌ Error! API Request Failed.\n";
    echo "HTTP Status Code: $httpCode\n";
    
    $errorData = json_decode($response, true);
    if ($errorData && isset($errorData['message'])) {
        echo "Error Message: " . $errorData['message'] . "\n";
    }
    
    echo "\n🔧 Troubleshooting:\n";
    echo "1. Verify your API key is correct\n";
    echo "2. Check your internet connection\n";
    echo "3. Ensure Aidroppay service is available\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Test completed at " . date('Y-m-d H:i:s') . "\n";