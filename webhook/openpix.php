<?php
// webhook/openpix.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/OpenPixController.php';

// Log todas as requisições para debug
$input_raw = file_get_contents('php://input');
$headers = getallheaders();
error_log("OpenPix Webhook recebido: " . $input_raw);
error_log("Headers: " . print_r($headers, true));

// Verificar autorização
$authorization = $headers['Authorization'] ?? $headers['authorization'] ?? '';
if ($authorization !== OPENPIX_WEBHOOK_AUTH) {
    error_log("OpenPix Webhook: Autorização inválida - Recebido: {$authorization}");
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Processar apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Decodificar JSON
$input = json_decode($input_raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("OpenPix Webhook: JSON inválido - " . json_last_error_msg());
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Processar webhook
$result = OpenPixController::processWebhook($input);

// Sempre retornar 200 para OpenPix
http_response_code(200);
echo json_encode([
    'status' => $result['success'] ? 'success' : 'error',
    'message' => $result['message'],
    'data' => $result['data'] ?? null
]);
?>