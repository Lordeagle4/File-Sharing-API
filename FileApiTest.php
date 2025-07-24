<?php

// Setup
error_reporting(E_ERROR | E_PARSE);
$baseUrl = 'http://localhost:8000';

function request($method, $endpoint, $payload = [], $isFile = false)
{
    global $baseUrl;

    $url = "$baseUrl$endpoint";

    $ch = curl_init();

    $headers = [];

    if (!$isFile && $method === 'post') {
        $headers[] = 'Content-Type: application/json';
    }

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
    ];

    if ($method === 'post') {
        if ($isFile) {
            $payload['file'] = new CURLFile(__DIR__ . '/test-image.jpg');
            $options[CURLOPT_POSTFIELDS] = $payload;
        } else {
            $options[CURLOPT_POSTFIELDS] = json_encode($payload);
        }
    }

    if ($method === 'get' && !empty($payload)) {
        $options[CURLOPT_URL] .= '?' . http_build_query($payload);
    }

    curl_setopt_array($ch, $options);
    $raw = curl_exec($ch);
    curl_close($ch);

    // === Strip HTML/junk and isolate JSON ===
    preg_match('/({.*})/s', $raw, $matches);
    $json = $matches[1] ?? null;

    return $json ? json_decode($json, true) : null;
}


function printResult($label, $result)
{
    echo "[$label]: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
}


// === TESTS ===

echo "=== File Sharing API Test ===\n";

// 1. Upload
$response = request('post', '/upload', ['file' => true], true);
printResult('POST /upload', $response);

$fileId = $response['data']['id'] ?? null;

if (!$fileId) {
    echo "❌ Failed to extract file ID. Aborting remaining tests.\n";
    exit(1);
}

echo "✅ File uploaded. File ID: $fileId\n";

// 2. Fetch Metadata
$response = request('post', '/fetch', ['file_id' => $fileId]);
printResult('POST /fetch', $response);

// 3. Add View Count
$response = request('post', '/addViewCount', ['file_id' => $fileId]);
printResult('POST /addViewCount', $response);

// 4. Add Download Count
$response = request('post', '/addDownloadCount', ['file_id' => $fileId]);
printResult('POST /addDownloadCount', $response);

// 5. Fetch Stats
$response = request('get', '/fetchStats', ['file_id' => $fileId]);
printResult('GET /fetchStats', $response);

// 6. Upload Progress
$response = request('get', '/upload/progress', ['file_id' => $fileId]);
printResult('GET /upload/progress', $response);

echo "=== End of Tests ===\n";
