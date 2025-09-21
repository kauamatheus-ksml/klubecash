<?php
/**
 * Teste Completo do Sistema de Notificações
 * Simula uma transação real para testar o fluxo completo
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/models/Transaction.php';
require_once __DIR__ . '/utils/NotificationTrigger.php';

echo "=== TESTE COMPLETO DE NOTIFICAÇÃO ===\n\n";

try {
    // 1. Verificar configurações
    echo "1. Verificando configurações:\n";
    echo "   - Bot URL: " . WHATSAPP_BOT_URL . "\n";
    echo "   - API URL: " . CASHBACK_NOTIFICATION_API_URL . "\n";
    echo "   - Notificações habilitadas: " . (CASHBACK_NOTIFICATIONS_ENABLED ? 'SIM' : 'NÃO') . "\n\n";

    // 2. Criar uma transação de teste
    echo "2. Criando transação de teste:\n";

    $transaction = new Transaction();
    $transaction->setUsuarioId(9); // ID de usuário existente
    $transaction->setLojaId(59);   // ID de loja existente
    $transaction->setValorTotal(25.00);
    $transaction->setDataTransacao(date('Y-m-d H:i:s'));
    $transaction->setStatus(TRANSACTION_PENDING);

    // Calcular distribuição
    $transaction->calcularDistribuicao();

    echo "   - Usuário ID: 9\n";
    echo "   - Loja ID: 59\n";
    echo "   - Valor: R$ 25,00\n";
    echo "   - Status: " . TRANSACTION_PENDING . "\n";

    // 3. Salvar a transação (isso deve disparar a notificação automaticamente)
    echo "\n3. Salvando transação (deve disparar notificação):\n";

    $result = $transaction->save();

    if ($result) {
        $transactionId = $transaction->getId();
        echo "   ✅ Transação criada com ID: " . $transactionId . "\n";

        // 4. Aguardar um momento e verificar logs
        echo "\n4. Aguardando processamento da notificação...\n";
        sleep(2);

        // 5. Testar NotificationTrigger diretamente também
        echo "\n5. Testando NotificationTrigger diretamente:\n";
        $notificationResult = NotificationTrigger::send($transactionId, ['debug' => true]);

        echo "   - Sucesso: " . ($notificationResult['success'] ? 'SIM' : 'NÃO') . "\n";
        echo "   - Mensagem: " . $notificationResult['message'] . "\n";

        if (isset($notificationResult['api_response'])) {
            echo "   - Resposta API: " . json_encode($notificationResult['api_response']) . "\n";
        }

        // 6. Verificar últimos logs do WhatsApp
        echo "\n6. Verificando logs WhatsApp recentes:\n";

        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT id, type, phone, success, error_message, created_at
            FROM whatsapp_logs
            ORDER BY id DESC
            LIMIT 3
        ");
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($logs as $log) {
            echo "   - Log ID " . $log['id'] . ": " . $log['type'] . " para " . $log['phone'] .
                 " - " . ($log['success'] ? 'SUCESSO' : 'FALHA') .
                 " em " . $log['created_at'] . "\n";
            if ($log['error_message']) {
                echo "     Erro: " . $log['error_message'] . "\n";
            }
        }

    } else {
        echo "   ❌ Erro ao criar transação\n";
    }

} catch (Exception $e) {
    echo "❌ Erro no teste: " . $e->getMessage() . "\n";
}

echo "\n=== RESULTADO DO TESTE ===\n";
echo "Se você vir logs recentes com sucesso, o sistema está funcionando!\n";
echo "Se houver erros, isso nos ajuda a identificar onde está o problema.\n";
?>