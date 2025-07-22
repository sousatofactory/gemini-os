<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // Em produção, restrinja para o seu domínio
header("Access-Control-Allow-Headers: Content-Type");

// Inclui o arquivo de configuração com a chave da API
// Este arquivo deve estar FORA da pasta public_html (ou www) do seu site.
require_once(__DIR__ . '/../../config.php');

// Obtenha a chave da API da constante definida no config.php
if (!defined('GEMINI_API_KEY_PRIMARY')) {
    http_response_code(500);
    echo json_encode(['error' => 'A constante GEMINI_API_KEY_PRIMARY não está definida no config.php.']);
    exit;
}
$apiKey = GEMINI_API_KEY_PRIMARY;

$rawInput = file_get_contents('php://input');

if (empty($rawInput)) {
    http_response_code(400);
    echo json_encode(['error' => 'Corpo da requisição vazio.']);
    exit;
}

$input = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Erro ao decodificar JSON: ' . json_last_error_msg()]);
    exit;
}

if (!is_array($input) || !isset($input['prompt']) || empty($input['prompt'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Nenhum prompt fornecido ou formato JSON inválido.']);
    exit;
}

$prompt = $input['prompt'];

// A URL da API do Gemini para o modelo gemini-pro
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $apiKey;

$data = [
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ]
];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
        'ignore_errors' => true // Permite capturar a resposta mesmo em caso de erro
    ]
];

$context  = stream_context_create($options);
$response = file_get_contents($url, false, $context);

$http_code = (int)explode(' ', $http_response_header[0])[1];

if ($http_code >= 400) {
    http_response_code($http_code);
    // Tenta decodificar a resposta de erro da API
    $error_details = json_decode($response, true);
    echo json_encode([
        'error' => 'Erro ao chamar a API do Gemini.',
        'details' => $error_details ?? ['message' => 'Não foi possível decodificar a resposta de erro.']
    ]);
    exit;
}

$result = json_decode($response, true);

// Extrai o texto da resposta
$text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

echo json_encode(['text' => $text]);
