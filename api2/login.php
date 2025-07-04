<?php
// public_html/api2/login.php - CORREÇÃO FINAL

// Ativar exibição de erros PHP e relatório completo para depuração (REMOVA EM PRODUÇÃO!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar captura de saída em buffer para evitar problemas com headers e para logar tudo
ob_start();

// --- Função de Log Personalizado ---
function api_log($message) {
    $log_file = __DIR__ . '/api_debug.log'; // Loga na mesma pasta da API
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}
api_log("PONTO 1: Script login.php iniciado.");

// Headers CORS para permitir requisições do seu app Flutter
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Mude '*' para o domínio do seu app Flutter em produção (ex: https://seuapp.com)
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Lida com requisições OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    ob_end_flush(); // Envia o buffer e finaliza
    exit();
}

api_log("PONTO 2: Headers CORS definidos. Iniciando bloco try-catch principal.");

try {
    api_log("PONTO 3: Tentando incluir dependências...");

    // CORREÇÃO CRÍTICA:
    // Incluir AuthController.php. Ele já inclui constants.php, database.php, Email.php e Validator.php.
    require_once ROOT_PATH . 'controllers/AuthController.php'; 
    // Não inclua constants.php, database.php, Email.php, Validator.php diretamente aqui novamente.

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
            // session_start() é chamado dentro de AuthController::login se a sessão não estiver ativa
            // Garantir que a sessão esteja iniciada para acessar $_SESSION
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
        api_log("PONTO 8: Método não permitido (" . $_SERVER['REQUEST_METHOD'] . ") ou dados POST ausentes.");
        http_response_code(405);
        echo json_encode(['message' => 'Método não permitido ou dados ausentes.']);
    }

} catch (Throwable $e) { // Captura qualquer erro ou exceção (incluindo erros fatais)
    $error_message = "PONTO 9: ERRO CATASTRÓFICO: " . $e->getMessage() . " em " . $e->getFile() . " na linha " . $e->getLine() . "\nStack trace:\n" . $e->getTraceAsString();
    api_log($error_message);
    http_response_code(500);
    echo json_encode(['message' => 'Erro interno do servidor. Detalhes em api_debug.log']);
}

ob_end_flush(); // Finaliza a captura de buffer e envia o conteúdo para o cliente
?>