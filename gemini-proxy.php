<?php
header('Content-Type: application/json');

// Replace with your actual Gemini API Key
$apiKey = 'AIzaSyARMU85SFP_zPwNAyRkBVywgvbXypXx5jk';
$apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $apiKey;

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'Invalid JSON received.']);
    exit;
}

if (!isset($data['prompt'])) {
    echo json_encode(['error' => 'Prompt not found in request body.']);
    exit;
}

$prompt = $data['prompt'];

$requestBody = json_encode([
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ]
]);

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
curl_setopt(CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($requestBody)
]);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo json_encode(['error' => 'cURL error: ' . curl_error($ch)]);
} else if ($httpcode !== 200) {
    echo json_encode(['error' => 'API error: HTTP status ' . $httpcode . ', Response: ' . $response]);
} else {
    $responseData = json_decode($response, true);
    if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        echo json_encode(['text' => $responseData['candidates'][0]['content']['parts'][0]['text']]);
    } else {
        echo json_encode(['error' => 'Unexpected API response format.', 'response' => $responseData]);
    }
}

curl_close($ch);
?>