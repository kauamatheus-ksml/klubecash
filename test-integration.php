<?php
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'api/n8n-webhook.php';
require_once 'utils/EvolutionWhatsApp.php';

echo "=== TESTE DE INTEGRAÇÃO KLUBE CASH ===\n\n";

// 1. Teste N8N Connection
echo "1. Testando conexão N8N...\n";
$n8nTest = N8nWebhook::testConnection();
echo "   Resultado: " . ($n8nTest ? "✅ Sucesso" : "❌ Falha") . "\n\n";

// 2. Teste Evolution API Connection
echo "2. Testando conexão Evolution API...\n";
$evolutionTest = EvolutionWhatsApp::testConnection();
echo "   Resultado: " . ($evolutionTest['success'] ? "✅ Conectado" : "❌ Falha: " . $evolutionTest['error']) . "\n\n";

// 3. Teste envio de mensagem de teste
echo "3. Testando envio de mensagem...\n";
$testMessage = "🧪 Teste Klube Cash\n\nTeste de integração realizado em " . date('d/m/Y H:i:s');
$messageTest = EvolutionWhatsApp::sendMessage('5534988776655', $testMessage);
echo "   Resultado: " . ($messageTest['success'] ? "✅ Enviado" : "❌ Falha: " . $messageTest['error']) . "\n\n";

// 4. Buscar última transação para teste N8N
echo "4. Testando webhook N8N com transação real...\n";
try {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT id FROM transacoes_cashback ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $lastTransaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($lastTransaction) {
        $webhookTest = N8nWebhook::sendTransactionData($lastTransaction['id'], 'nova_transacao');
        echo "   Resultado: " . ($webhookTest ? "✅ Webhook enviado" : "❌ Falha no webhook") . "\n";
    } else {
        echo "   ⚠️ Nenhuma transação encontrada para teste\n";
    }
} catch (Exception $e) {
    echo "   ❌ Erro: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DOS TESTES ===\n";
?>