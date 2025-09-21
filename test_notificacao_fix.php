<?php
/**
 * Teste da correção do sistema de notificações WhatsApp
 * Após atualizar o IP do bot para 148.230.73.190
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/utils/WhatsAppBot.php';

echo "=== TESTE DO SISTEMA DE NOTIFICAÇÃO CORRIGIDO ===\n\n";

// 1. Verificar configurações atualizadas
echo "1. Configurações:\n";
echo "   - WHATSAPP_BOT_URL: " . WHATSAPP_BOT_URL . "\n";
echo "   - WHATSAPP_ENABLED: " . (WHATSAPP_ENABLED ? 'SIM' : 'NÃO') . "\n";
echo "   - CASHBACK_NOTIFICATIONS_ENABLED: " . (CASHBACK_NOTIFICATIONS_ENABLED ? 'SIM' : 'NÃO') . "\n\n";

// 2. Testar status do bot
echo "2. Status do Bot WhatsApp:\n";
$isConnected = WhatsAppBot::isConnected();
echo "   - Conectado: " . ($isConnected ? 'SIM' : 'NÃO') . "\n";

$status = WhatsAppBot::getDetailedStatus();
echo "   - Modo produção: " . ($status['api_configured'] ? 'SIM' : 'NÃO') . "\n";
echo "   - Simulação: " . ($status['simulation_mode'] ? 'SIM' : 'NÃO') . "\n\n";

// 3. Testar envio de mensagem
echo "3. Teste de Envio de Mensagem:\n";
$testResult = WhatsAppBot::sendTestMessage('5538991045205');

echo "   - Sucesso: " . ($testResult['success'] ? 'SIM' : 'NÃO') . "\n";
echo "   - Mensagem: " . $testResult['message'] . "\n";
if (isset($testResult['error'])) {
    echo "   - Erro: " . $testResult['error'] . "\n";
}
if (isset($testResult['messageId'])) {
    echo "   - Message ID: " . $testResult['messageId'] . "\n";
}
echo "\n";

// 4. Testar API de notificação diretamente
echo "4. Teste da API de Notificação:\n";
$apiUrl = CASHBACK_NOTIFICATION_API_URL;
$testData = [
    'secret' => WHATSAPP_BOT_SECRET,
    'transaction_id' => 999999  // ID inexistente para testar resposta
];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($testData),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json'
    ],
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curlError = curl_error($curl);
curl_close($curl);

echo "   - HTTP Code: " . $httpCode . "\n";
if ($curlError) {
    echo "   - Erro cURL: " . $curlError . "\n";
} else {
    $responseData = json_decode($response, true);
    echo "   - Resposta: " . $response . "\n";
}

echo "\n=== DIAGNÓSTICO ===\n";
if ($httpCode === 200 || $httpCode === 400) {
    echo "✅ API de notificação está respondendo (IP corrigido funcionou!)\n";
} else {
    echo "❌ API de notificação ainda com problemas\n";
}

if ($testResult['success']) {
    echo "✅ Sistema de mensagens funcionando\n";
} else {
    if (strpos($testResult['message'], 'Bot não está pronto') !== false) {
        echo "⚠️  Bot precisa ser conectado ao WhatsApp no servidor\n";
    } else {
        echo "❌ Problema no sistema de mensagens\n";
    }
}

echo "\n=== PRÓXIMOS PASSOS ===\n";
echo "1. Conectar o bot WhatsApp no servidor 148.230.73.190:3002\n";
echo "2. Testar criação de nova transação\n";
echo "3. Verificar se notificações funcionam automaticamente\n";
?>