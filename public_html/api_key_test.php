<?php
// Simple API key validation test
define('AI_API_KEY', 'AIzaSyDXxu0oSWBs36WqgG04CH-nIwP2wS8c4IU');

echo "<h1>Gemini API Key Test</h1>";
echo "<pre>";

// Test 1: List available models
echo "=== Test 1: List Available Models ===\n\n";

$listUrl = "https://generativelanguage.googleapis.com/v1beta/models?key=" . AI_API_KEY;

$ch = curl_init($listUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";

if ($httpCode === 200) {
    echo "✓ API Key is VALID\n\n";

    $json = json_decode($response, true);

    if (isset($json['models'])) {
        echo "Available models:\n";
        foreach ($json['models'] as $model) {
            $name = $model['name'] ?? 'Unknown';
            $displayName = $model['displayName'] ?? 'N/A';
            $supportedMethods = isset($model['supportedGenerationMethods'])
                ? implode(', ', $model['supportedGenerationMethods'])
                : 'N/A';

            echo "  - $name\n";
            echo "    Display: $displayName\n";
            echo "    Methods: $supportedMethods\n\n";
        }
    } else {
        echo "No models found in response.\n";
    }
} else {
    echo "✗ API Key ERROR\n";
    echo "Response:\n";
    echo $response . "\n";
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// Test 2: Try a simple generation with the first available model
echo "=== Test 2: Try Simple Generation ===\n\n";

if ($httpCode === 200 && isset($json['models'][0])) {
    $firstModel = $json['models'][0]['name'];
    echo "Using model: $firstModel\n\n";

    $testUrl = "https://generativelanguage.googleapis.com/v1beta/$firstModel:generateContent?key=" . AI_API_KEY;

    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => "Say hello in one word"]
                ]
            ]
        ]
    ];

    $ch = curl_init($testUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $testResponse = curl_exec($ch);
    $testHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "HTTP Code: $testHttpCode\n";

    if ($testHttpCode === 200) {
        echo "✓ Generation WORKS!\n";
        $testJson = json_decode($testResponse, true);
        if (isset($testJson['candidates'][0]['content']['parts'][0]['text'])) {
            echo "Response: " . $testJson['candidates'][0]['content']['parts'][0]['text'] . "\n";
        }
    } else {
        echo "✗ Generation FAILED\n";
        echo "Response:\n";
        echo $testResponse . "\n";
    }
} else {
    echo "Cannot test generation - no models available\n";
}

echo "</pre>";
?>