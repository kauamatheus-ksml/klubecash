<?php
/**
 * Debug Profundo - Sistema de Notificação WhatsApp
 *
 * Este arquivo faz um debug completo para descobrir exatamente
 * onde está o problema no envio de mensagens WhatsApp.
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';

echo "🔍 DEBUG PROFUNDO - Sistema WhatsApp Klube Cash\n";
echo "================================================\n\n";

// Telefone de teste
$testPhone = '5538991045205';

try {
    // 1. TESTE DE CONFIGURAÇÕES DETALHADO
    echo "1️⃣ CONFIGURAÇÕES DO SISTEMA:\n";
    echo "----------------------------\n";
    echo "WHATSAPP_BOT_URL: " . (defined('WHATSAPP_BOT_URL') ? WHATSAPP_BOT_URL : 'NÃO DEFINIDO') . "\n";
    echo "WHATSAPP_BOT_SECRET: " . (defined('WHATSAPP_BOT_SECRET') ? WHATSAPP_BOT_SECRET : 'NÃO DEFINIDO') . "\n";
    echo "CASHBACK_NOTIFICATIONS_ENABLED: " . (defined('CASHBACK_NOTIFICATIONS_ENABLED') && CASHBACK_NOTIFICATIONS_ENABLED ? 'SIM' : 'NÃO') . "\n";
    echo "WHATSAPP_TIMEOUT: " . (defined('WHATSAPP_TIMEOUT') ? WHATSAPP_TIMEOUT : 'NÃO DEFINIDO') . "\n\n";

    // 2. TESTE DE CONECTIVIDADE HTTP
    echo "2️⃣ TESTE DE CONECTIVIDADE:\n";
    echo "---------------------------\n";

    $statusUrl = WHATSAPP_BOT_URL . '/status';
    echo "Testando: $statusUrl\n";

    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'method' => 'GET'
        ]
    ]);

    $response = @file_get_contents($statusUrl, false, $context);

    if ($response === false) {
        echo "❌ ERRO: Não foi possível conectar com o bot\n";
        echo "💡 Verifique se o bot está rodando na porta 3002\n\n";
    } else {
        echo "✅ Conectividade OK\n";
        $data = json_decode($response, true);
        echo "Status do bot: " . ($data['status'] ?? 'desconhecido') . "\n";
        echo "Bot pronto: " . ($data['bot_ready'] ? 'SIM' : 'NÃO') . "\n";
        echo "Uptime: " . ($data['uptime'] ?? 0) . " segundos\n\n";
    }

    // 3. TESTE DIRETO DA API SEND-MESSAGE
    echo "3️⃣ TESTE DIRETO DO ENDPOINT /send-message:\n";
    echo "-------------------------------------------\n";

    $sendUrl = WHATSAPP_BOT_URL . '/send-message';
    $testMessage = "🧪 Teste de debug profundo - " . date('H:i:s');

    $postData = [
        'secret' => WHATSAPP_BOT_SECRET,
        'phone' => $testPhone,
        'message' => $testMessage
    ];

    echo "URL: $sendUrl\n";
    echo "Dados enviados: " . json_encode($postData, JSON_PRETTY_PRINT) . "\n";

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $sendUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'User-Agent: KlubeCash-Debug/1.0'
        ],
        CURLOPT_VERBOSE => true,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    $curlInfo = curl_getinfo($curl);
    curl_close($curl);

    echo "HTTP Code: $httpCode\n";
    if ($curlError) {
        echo "❌ Erro cURL: $curlError\n";
    }
    echo "Resposta: $response\n";
    echo "Tempo de resposta: " . ($curlInfo['total_time'] ?? 0) . " segundos\n\n";

    // 4. TESTE DA CLASSE CashbackNotifier
    echo "4️⃣ TESTE DA CLASSE CashbackNotifier:\n";
    echo "------------------------------------\n";

    if (file_exists(__DIR__ . '/classes/CashbackNotifier.php')) {
        require_once __DIR__ . '/classes/CashbackNotifier.php';

        // Buscar uma transação para teste
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT id FROM transacoes_cashback
            ORDER BY data_transacao DESC
            LIMIT 1
        ");
        $stmt->execute();
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($transaction) {
            echo "Testando com transação ID: {$transaction['id']}\n";

            $notifier = new CashbackNotifier();
            $result = $notifier->notifyNewTransaction($transaction['id']);

            echo "Resultado da notificação:\n";
            echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
        } else {
            echo "❌ Nenhuma transação encontrada para teste\n\n";
        }
    } else {
        echo "❌ Arquivo CashbackNotifier.php não encontrado\n\n";
    }

    // 5. TESTE DO NotificationTrigger
    echo "5️⃣ TESTE DO NotificationTrigger:\n";
    echo "--------------------------------\n";

    if (file_exists(__DIR__ . '/utils/NotificationTrigger.php')) {
        require_once __DIR__ . '/utils/NotificationTrigger.php';

        if ($transaction) {
            echo "Testando NotificationTrigger com transação ID: {$transaction['id']}\n";

            $result = NotificationTrigger::send($transaction['id'], [
                'async' => false,
                'debug' => true
            ]);

            echo "Resultado do NotificationTrigger:\n";
            echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
        }
    } else {
        echo "❌ Arquivo NotificationTrigger.php não encontrado\n\n";
    }

    // 6. TESTE MANUAL DE ENVIO WHATSAPP
    echo "6️⃣ TESTE MANUAL DE ENVIO WHATSAPP:\n";
    echo "----------------------------------\n";

    // Simular exatamente o que o CashbackNotifier faz
    $manualPostData = [
        'secret' => WHATSAPP_BOT_SECRET,
        'phone' => $testPhone,
        'message' => "✅ Teste manual de envio\n\nEste é um teste direto para o telefone $testPhone\n\nData/Hora: " . date('d/m/Y H:i:s'),
        'type' => 'debug_manual'
    ];

    echo "Enviando mensagem manual para $testPhone...\n";

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => WHATSAPP_BOT_URL . '/send-message',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($manualPostData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'User-Agent: KlubeCash-ManualTest/1.0'
        ],
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $manualResponse = curl_exec($curl);
    $manualHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $manualCurlError = curl_error($curl);
    curl_close($curl);

    echo "HTTP Code: $manualHttpCode\n";
    echo "Erro cURL: " . ($manualCurlError ?: 'Nenhum') . "\n";
    echo "Resposta: $manualResponse\n\n";

    // 7. ANÁLISE DE LOGS
    echo "7️⃣ VERIFICAÇÃO DE LOGS:\n";
    echo "-----------------------\n";

    $logFiles = [
        'error_log',
        'whatsapp/logs/bot-' . date('Y-m-d') . '.log',
        'integration_trace.log'
    ];

    foreach ($logFiles as $logFile) {
        if (file_exists($logFile)) {
            echo "📋 Últimas 5 linhas de $logFile:\n";
            $lines = file($logFile);
            $lastLines = array_slice($lines, -5);
            foreach ($lastLines as $line) {
                echo "  " . trim($line) . "\n";
            }
            echo "\n";
        } else {
            echo "⚠️ Log $logFile não encontrado\n";
        }
    }

    // 8. SUGESTÕES DE RESOLUÇÃO
    echo "8️⃣ DIAGNÓSTICO E SUGESTÕES:\n";
    echo "----------------------------\n";

    if (!$response) {
        echo "🔧 PROBLEMA PRINCIPAL: Bot WhatsApp não está acessível\n";
        echo "💡 SOLUÇÕES:\n";
        echo "   1. Verificar se o bot está rodando: cd whatsapp && npm start\n";
        echo "   2. Verificar se a porta 3002 está livre\n";
        echo "   3. Verificar firewall/antivírus\n";
        echo "   4. Testar manualmente: curl http://localhost:3002/status\n\n";
    } else {
        $statusData = json_decode($response, true);
        if (!($statusData['bot_ready'] ?? false)) {
            echo "🔧 PROBLEMA PRINCIPAL: Bot não está conectado ao WhatsApp\n";
            echo "💡 SOLUÇÕES:\n";
            echo "   1. Escanear o QR Code que aparece no terminal do bot\n";
            echo "   2. Aguardar a mensagem 'WhatsApp conectado e pronto!'\n";
            echo "   3. Verificar se o WhatsApp Web não está aberto em outro lugar\n\n";
        } else {
            echo "🔧 PROBLEMA PRINCIPAL: Configuração ou implementação\n";
            echo "💡 VERIFICAR:\n";
            echo "   1. Secret key correta: " . WHATSAPP_BOT_SECRET . "\n";
            echo "   2. Formato do telefone: deve ser 55XXXXXXXXXXX\n";
            echo "   3. Logs do bot para erros específicos\n\n";
        }
    }

} catch (Exception $e) {
    echo "❌ ERRO FATAL: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "=================================================\n";
echo "Debug concluído em " . date('d/m/Y H:i:s') . "\n";
?>