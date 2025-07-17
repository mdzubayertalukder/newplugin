<?php

// Simple test script for Google Custom Search API
$apiKey = 'AIzaSyBu0x-r_XlDBVWSjkxAIkePUpLT4hJWhc4';
$searchEngineId = ''; // You need to provide this

if (empty($searchEngineId)) {
    echo "Error: Search Engine ID is required. Please create a Custom Search Engine at https://cse.google.com/\n";
    exit;
}

$query = 'smartphone price Bangladesh';
$url = 'https://www.googleapis.com/customsearch/v1?' . http_build_query([
    'key' => $apiKey,
    'cx' => $searchEngineId,
    'q' => $query,
    'num' => 5,
    'gl' => 'bd',
    'hl' => 'en',
    'safe' => 'active'
]);

echo "Testing Google Custom Search API...\n";
echo "URL: $url\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
}

echo "Response:\n";
echo $response;

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if (isset($data['items'])) {
        echo "\n\nSuccess! Found " . count($data['items']) . " results.\n";
        foreach ($data['items'] as $index => $item) {
            echo ($index + 1) . ". " . $item['title'] . "\n";
            echo "   URL: " . $item['link'] . "\n";
            echo "   Snippet: " . substr($item['snippet'], 0, 100) . "...\n\n";
        }
    } else {
        echo "\n\nNo items found in response.\n";
    }
} else {
    echo "\n\nAPI request failed with HTTP code: $httpCode\n";
}