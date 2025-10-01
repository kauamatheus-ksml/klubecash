<?php
// public_html/api2/reset-password.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();
function api_log($message) { file_put_contents(__DIR__ . '/api_debug.log', "[" . date('Y-m-d H:i:s') . "] $message\n", FILE_APPEND); } api_log("PONTO 1: Script reset-password.php iniciado.");

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); ob_end_flush(); exit();
}

api_log("PONTO 2: Headers CORS definidos. Iniciando bloco try-catch principal.");

try {
    api_log("PONTO 3: Tentando incluir dependências...");
    require_once __DIR__ . '/../controllers/AuthController.php';
    require_once __DIR__ . '/../config/constants.php';
    require_once __DIR__ . '/../config/database.php';
    // require_once __DIR__ . '/../utils/Email.php'; // Pode não ser necessário aqui
    api_log("PONTO 4: Dependências incluídas com sucesso.");

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input_data = file_get_contents('php://input');
        $data = json_decode($input_data, true);
        api_log("PONTO 5: Dados POST recebidos: " . $input_data);
        if (json_last_error() !== JSON_ERROR_NONE) {
            api_log("ERRO: JSON Decode falhou: " . json_last_error_msg());
            http_response_code(400); echo json_encode(['message' => 'JSON inválido na requisição.']); ob_end_flush(); exit();
        }

        $token = $data['token'] ?? '';
        $newPassword = $data['newPassword'] ?? '';

        api_log("PONTO 6: Chamando AuthController::resetPassword para token: " . $token);
        $result = AuthController::resetPassword($token, $newPassword);
        api_log("PONTO 7: Resultado de AuthController::resetPassword: " . json_encode($result));

        if ($result['status']) {
            http_response_code(200);
            echo json_encode(['message' => $result['message']]);
        } else {
            http_response_code(400);
            echo json_encode(['message' => $result['message']]);
        }
    } else {
        api_log("PONTO 8: Método não permitido ou dados POST ausentes.");
        http_response_code(405); echo json_encode(['message' => 'Método não permitido ou dados ausentes.']);
    }

} catch (Throwable $e) {
    $error_message = "PONTO 9: ERRO CATASTRÓFICO: " . $e->getMessage() . " em " . $e->getFile() . " na linha " . $e->getLine() . "\nStack trace:\n" . $e->getTraceAsString();
    api_log($error_message);
    http_response_code(500); echo json_encode(['message' => 'Erro interno do servidor. Detalhes em api_debug.log']);
}
ob_end_flush();
?>