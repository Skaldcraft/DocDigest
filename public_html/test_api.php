<?php
// test_api.php - Simple API test script
// This file helps diagnose API connection issues

define('AI_API_KEY', 'AIzaSyDXxu0oSWBs36WqgG04CH-nIwP2wS8c4IU');

echo "<h1>DocDigest API Test</h1>";
echo "<pre>";

// Test 1: Check if API key is set
echo "Test 1: API Key Check\n";
echo "API Key: " . (defined('AI_API_KEY') ? "✓ Set (length: " . strlen(AI_API_KEY) . ")" : "✗ Not set") . "\n\n";

// Test 2: Try different API endpoints
$endpoints = [
    'v1' => "https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key=" . AI_API_KEY,
    'v1beta' => "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . AI_API_KEY,
];

foreach ($endpoints as $version => $url) {
    echo "Test 2.$version: Testing $version endpoint\n";
    echo "URL: " . substr($url, 0, 80) . "...\n";

    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => "Say 'Hello' in one word"]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    echo "HTTP Code: $httpCode\n";

    if ($curlError) {
        echo "CURL Error: $curlError\n";
    }

    if ($httpCode === 200) {
        echo "✓ SUCCESS! API is working\n";
        $json = json_decode($response, true);
        if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
            echo "Response: " . $json['candidates'][0]['content']['parts'][0]['text'] . "\n";
        }
    } else {
        echo "✗ FAILED\n";
        echo "Response (first 500 chars):\n" . substr($response, 0, 500) . "\n";
    }

    echo "\n" . str_repeat("-", 80) . "\n\n";
}

// Test 3: Check PHP/CURL info
echo "Test 3: Server Environment\n";
echo "PHP Version: " . phpversion() . "\n";
echo "CURL Enabled: " . (function_exists('curl_init') ? "✓ Yes" : "✗ No") . "\n";
echo "CURL Version: " . (function_exists('curl_version') ? curl_version()['version'] : "N/A") . "\n";

echo "</pre>";
?>