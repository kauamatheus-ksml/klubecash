<?php
/**
 * TESTE DO SISTEMA BRUTAL DE NOTIFICAÇÃO
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';

echo "=== TESTE DO SISTEMA BRUTAL DE NOTIFICAÇÃO ===\n\n";

// 1. Testar sistema brutal diretamente
echo "1. TESTANDO SISTEMA BRUTAL DIRETAMENTE\n";
echo "---------------------------------------\n";

require_once __DIR__ . '/classes/BrutalNotificationSystem.php';

$brutalSystem = new BrutalNotificationSystem();

// Testar com uma transação específica (última do banco)
echo "Forçando notificação da transação 527...\n";
$result = $brutalSystem->forceNotifyTransaction(527);

echo "Resultado: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

// 2. Testar trigger automático
echo "2. TESTANDO TRIGGER AUTOMÁTICO\n";
echo "-------------------------------\n";

require_once __DIR__ . '/utils/AutoNotificationTrigger.php';

echo "Testando AutoNotificationTrigger...\n";
$triggerResult = AutoNotificationTrigger::notifyNewTransaction(526);

echo "Resultado Trigger: " . json_encode($triggerResult, JSON_PRETTY_PRINT) . "\n\n";

// 3. Verificar todas as transações não notificadas
echo "3. VERIFICANDO TODAS AS TRANSAÇÕES NÃO NOTIFICADAS\n";
echo "---------------------------------------------------\n";

echo "Executando verificação completa...\n";
$checkResult = AutoNotificationTrigger::checkAllPendingNotifications();

echo "Resultado da Verificação: " . json_encode($checkResult, JSON_PRETTY_PRINT) . "\n\n";

// 4. Criar nova transação e testar notificação automática
echo "4. TESTANDO TRANSAÇÃO NOVA COM NOTIFICAÇÃO AUTOMÁTICA\n";
echo "-----------------------------------------------------\n";

require_once __DIR__ . '/controllers/TransactionController.php';
require_once __DIR__ . '/controllers/AuthController.php';

// Simular sessão
if (!session_id()) {
    session_start();
}

$_SESSION['authenticated'] = true;
$_SESSION['user_type'] = USER_TYPE_STORE;
$_SESSION['user_id'] = 59;
$_SESSION['store_id'] = 59;

$transactionData = [
    'loja_id' => 59,
    'usuario_id' => 9,
    'valor_total' => 77.77,
    'codigo_transacao' => 'BRUTAL_TEST_' . time(),
    'descricao' => 'Teste do Sistema Brutal de Notificação'
];

echo "Criando nova transação com dados:\n";
print_r($transactionData);

$transactionResult = TransactionController::registerTransaction($transactionData);

echo "\nResultado da criação: " . json_encode($transactionResult, JSON_PRETTY_PRINT) . "\n\n";

echo "=== TESTE CONCLUÍDO ===\n";
echo "Verifique os logs em:\n";
echo "- logs/brutal_notifications.log\n";
echo "- integration_trace.log\n";
echo "- Logs do PHP\n\n";

echo "Se o sistema funcionou, você verá mensagens de SUCESSO nos logs!\n";
?>