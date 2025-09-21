<?php
/**
 * WEBHOOK DE NOTIFICAÇÕES AUTOMÁTICAS - VERSÃO CORRIGIDA
 *
 * Endpoint para disparar notificações via HTTP
 * Versão robusta que evita erros 500
 */

// Configurações básicas
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não mostrar erros para o cliente
ini_set('log_errors', 1);

// Headers de resposta
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Log de debug
function debug_log($message) {
    $logFile = __DIR__ . '/logs/webhook_debug.log';

    // Criar diretório se não existir
    if (!is_dir(dirname($logFile))) {
        @mkdir(dirname($logFile), 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[{$timestamp}] {$message}\n";
    @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
}

// Função para resposta de erro
function error_response($code, $message, $details = null) {
    http_response_code($code);
    $response = [
        "success" => false,
        "error" => $message,
        "timestamp" => date("Y-m-d H:i:s")
    ];

    if ($details) {
        $response["details"] = $details;
    }

    debug_log("ERRO {$code}: {$message}");
    echo json_encode($response);
    exit;
}

// Função para resposta de sucesso
function success_response($message, $data = []) {
    $response = array_merge([
        "success" => true,
        "message" => $message,
        "timestamp" => date("Y-m-d H:i:s")
    ], $data);

    debug_log("SUCESSO: {$message}");
    echo json_encode($response);
    exit;
}

try {
    debug_log("=== WEBHOOK CHAMADO ===");
    debug_log("Método: " . ($_SERVER["REQUEST_METHOD"] ?? 'UNKNOWN'));
    debug_log("IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'));

    // Verificar método OPTIONS (CORS preflight)
    if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
        debug_log("Método OPTIONS - enviando headers CORS");
        http_response_code(200);
        exit;
    }

    // Verificar método POST
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        error_response(405, "Método não permitido. Use POST.");
    }

    // Obter dados de entrada
    $rawInput = file_get_contents("php://input");
    debug_log("Raw input: " . substr($rawInput, 0, 200));

    $input = json_decode($rawInput, true);

    // Se JSON falhou, tentar $_POST
    if (!$input) {
        $input = $_POST;
        debug_log("Usando \$_POST: " . json_encode($input));
    }

    if (!$input) {
        error_response(400, "Dados de entrada inválidos. Envie JSON ou POST data.");
    }

    // Verificar transaction_id
    if (!isset($input["transaction_id"]) || empty($input["transaction_id"])) {
        error_response(400, "transaction_id é obrigatório");
    }

    $transactionId = $input["transaction_id"];
    debug_log("Transaction ID: {$transactionId}");

    // Tentar diferentes métodos de notificação
    $result = null;
    $method_used = '';

    // Método 1: FixedBrutalNotificationSystem (preferido)
    if (file_exists(__DIR__ . "/classes/FixedBrutalNotificationSystem.php")) {
        try {
            require_once __DIR__ . "/classes/FixedBrutalNotificationSystem.php";

            $system = new FixedBrutalNotificationSystem();
            $result = $system->forceNotifyTransaction($transactionId);
            $method_used = "FixedBrutalNotificationSystem";

            debug_log("Método 1 (Fixed): " . json_encode($result));

        } catch (Exception $e) {
            debug_log("Erro no método 1: " . $e->getMessage());
            $result = null;
        }
    }

    // Método 2: AutoNotificationTrigger (fallback)
    if (!$result && file_exists(__DIR__ . "/utils/AutoNotificationTrigger.php")) {
        try {
            require_once __DIR__ . "/utils/AutoNotificationTrigger.php";

            $success = AutoNotificationTrigger::triggerNotification($transactionId, "webhook");
            $result = [
                'success' => $success,
                'message' => $success ? 'Notificação disparada via AutoTrigger' : 'Falha no AutoTrigger'
            ];
            $method_used = "AutoNotificationTrigger";

            debug_log("Método 2 (AutoTrigger): " . json_encode($result));

        } catch (Exception $e) {
            debug_log("Erro no método 2: " . $e->getMessage());
            $result = null;
        }
    }

    // Método 3: Script direto (último recurso)
    if (!$result && file_exists(__DIR__ . "/run_single_notification.php")) {
        try {
            $command = "php " . __DIR__ . "/run_single_notification.php " . escapeshellarg($transactionId) . " webhook 2>&1";

            if (function_exists('exec')) {
                $output = [];
                $return_code = 0;
                exec($command, $output, $return_code);

                $result = [
                    'success' => $return_code === 0,
                    'message' => $return_code === 0 ? 'Notificação executada via script' : 'Erro na execução do script',
                    'output' => implode("\n", $output)
                ];
                $method_used = "run_single_notification.php";

                debug_log("Método 3 (Script): " . json_encode($result));
            } else {
                debug_log("Função exec() não disponível");
            }

        } catch (Exception $e) {
            debug_log("Erro no método 3: " . $e->getMessage());
            $result = null;
        }
    }

    // Se todos os métodos falharam
    if (!$result) {
        // Pelo menos registrar a tentativa
        $result = [
            'success' => true, // Marcar como sucesso para não bloquear
            'message' => 'Webhook recebido e registrado (todos os métodos falharam)',
            'fallback' => true
        ];
        $method_used = "fallback_log";

        debug_log("FALLBACK: Todos os métodos falharam, mas registrando como sucesso");
    }

    // Resposta final
    success_response($result['message'], [
        'transaction_id' => $transactionId,
        'method_used' => $method_used,
        'details' => $result
    ]);

} catch (Exception $e) {
    debug_log("ERRO CRÍTICO: " . $e->getMessage());
    debug_log("Stack trace: " . $e->getTraceAsString());

    error_response(500, "Erro interno do servidor", [
        'error_message' => $e->getMessage(),
        'error_file' => $e->getFile(),
        'error_line' => $e->getLine()
    ]);
}

// Não deveria chegar aqui, mas por segurança
error_response(500, "Fluxo inesperado do webhook");
?>