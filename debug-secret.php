<?php
// debug-secret.php - Script para debugar o problema de autorização

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, User-Agent');

// Incluir constants para pegar o secret
require_once __DIR__ . '/config/constants.php';

// Se for OPTIONS (preflight), retornar OK
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Log tudo que chega
$method = $_SERVER['REQUEST_METHOD'];
$headers = getallheaders();
$rawInput = file_get_contents('php://input');
$decodedInput = json_decode($rawInput, true);

// Criar log detalhado
$debugInfo = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $method,
    'headers' => $headers,
    'raw_input' => $rawInput,
    'decoded_input' => $decodedInput,
    'expected_secret' => WHATSAPP_BOT_SECRET,
    'received_secret' => $decodedInput['secret'] ?? 'MISSING',
    'secret_match' => ($decodedInput['secret'] ?? '') === WHATSAPP_BOT_SECRET,
    'json_decode_error' => json_last_error_msg(),
    'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 'unknown',
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
];

// Log no arquivo
error_log('DEBUG AUTH: ' . json_encode($debugInfo));

// Retornar informações de debug
echo json_encode([
    'success' => true,
    'debug' => $debugInfo,
    'message' => 'Debug completo - verifique os logs',
    'secret_validation' => [
        'expected' => WHATSAPP_BOT_SECRET,
        'received' => $decodedInput['secret'] ?? 'MISSING',
        'match' => ($decodedInput['secret'] ?? '') === WHATSAPP_BOT_SECRET,
        'types' => [
            'expected_type' => gettype(WHATSAPP_BOT_SECRET),
            'received_type' => gettype($decodedInput['secret'] ?? null)
        ]
    ]
]);
?>