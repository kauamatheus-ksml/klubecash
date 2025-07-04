<?php
// public_html/api2/login.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();

function api_log($message) {
    $log_file = __DIR__ . '/api_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}
api_log("PONTO 1: Script login.php iniciado.");

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    ob_end_flush();
    exit();
}

api_log("PONTO 2: Headers CORS definidos. Iniciando bloco try-catch principal.");

try {
    api_log("PONTO 3: Tentando incluir dependências...");

    // CORREÇÃO: Usando ROOT_PATH para caminhos absolutos
    require_once ROOT_PATH . 'controllers/AuthController.php';
    require_once ROOT_PATH . 'config/constants.php';
    require_once ROOT_PATH . 'config/database.php';
    require_once ROOT_PATH . 'utils/Email.php'; 
    // Se Validator.php for usado em AuthController.php ou em algum outro lugar que não foi incluído por ele
    require_once ROOT_PATH . 'utils/Validator.php'; // Adicionado, caso seja uma dependência

    api_log("PONTO 4: Dependências incluídas com sucesso.");

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input_data = file_get_contents('php://input');
        $data = json_decode($input_data, true);

        api_log("PONTO 5: Dados POST recebidos: " . $input_data);
        if (json_last_error() !== JSON_ERROR_NONE) {
            api_log("ERRO: JSON Decode falhou: " . json_last_error_msg());
            http_response_code(400);
            echo json_encode(['message' => 'JSON inválido na requisição.']);
            ob_end_flush();
            exit();
        }

        $email = $data['email'] ?? '';
        $senha = $data['senha'] ?? '';

        api_log("PONTO 6: Chamando AuthController::login para email: " . $email);

        $result = AuthController::login($email, $senha);

        api_log("PONTO 7: Resultado de AuthController::login: " . json_encode($result));

        if ($result['status']) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $user_data_for_flutter = [
                'id' => $_SESSION['user_id'] ?? null,
                'nome' => $_SESSION['user_name'] ?? null,
                'email' => $_SESSION['user_email'] ?? null,
                'tipo' => $_SESSION['user_type'] ?? null,
            ];

            http_response_code(200);
            echo json_encode([
                'message' => $result['message'],
                'user' => $user_data_for_flutter,
                'token' => 'seu_super_token_secreto_aqui_para_simulacao'
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['message' => $result['message']]);
        }
    } else {
        api_log("PONTO 8: Método não permitido ou dados POST ausentes.");
        http_response_code(405);
        echo json_encode(['message' => 'Método não permitido ou dados ausentes.']);
    }

} catch (Throwable $e) {
    $error_message = "PONTO 9: ERRO CATASTRÓFICO: " . $e->getMessage() . " em " . $e->getFile() . " na linha " . $e->getLine() . "\nStack trace:\n" . $e->getTraceAsString();
    api_log($error_message);
    http_response_code(500);
    echo json_encode(['message' => 'Erro interno do servidor. Detalhes em api_debug.log']);
}

ob_end_flush();
?>