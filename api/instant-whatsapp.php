<?php
/**
 * WEBHOOK INTERNO DIRETO - KLUBE CASH
 *
 * Sistema ultra-simples que recebe dados de transação
 * e envia imediatamente via bot local
 */

// Headers para permitir chamadas internas
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Log de debug
$logFile = __DIR__ . '/../logs/instant_webhook.log';
function logInstant($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[{$timestamp}] {$message}\n", FILE_APPEND | LOCK_EX);
}

logInstant("=== WEBHOOK INTERNO ATIVADO ===");

try {
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }

    // Pegar dados
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    logInstant("Dados recebidos: " . substr($input, 0, 200));

    // Validar dados obrigatórios
    if (!isset($data['phone']) || !isset($data['message'])) {
        throw new Exception('Phone e message são obrigatórios');
    }

    $phone = $data['phone'];
    $message = $data['message'];

    // Enviar diretamente para o bot local (porta 3003)
    $botData = [
        'phone' => $phone,
        'message' => $message,
        'secret' => 'klube-cash-2024'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:3003/send-message');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($botData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);

    $start = microtime(true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $time = round((microtime(true) - $start) * 1000, 2);
    curl_close($ch);

    if ($httpCode === 200) {
        logInstant("✅ SUCESSO: Mensagem enviada em {$time}ms para {$phone}");

        echo json_encode([
            'success' => true,
            'method' => 'instant_webhook',
            'time_ms' => $time,
            'message' => 'Mensagem enviada com sucesso',
            'bot_response' => json_decode($response, true)
        ]);
    } else {
        throw new Exception("Bot respondeu com HTTP {$httpCode}: {$response}");
    }

} catch (Exception $e) {
    logInstant("❌ ERRO: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>