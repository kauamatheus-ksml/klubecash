<?php
/**
 * TESTE FINAL DO SISTEMA SIMPLES
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';

echo "=== TESTE FINAL DO SISTEMA SIMPLES ===\n\n";

// 1. Testar notificação direta
echo "1. TESTANDO NOTIFICAÇÃO DIRETA\n";
echo "-------------------------------\n";

require_once __DIR__ . '/utils/SimpleNotificationSystem.php';

echo "Enviando notificação para transação 527...\n";
$result = SimpleNotificationSystem::sendNotification(527);

echo "Resultado: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

// 2. Criar nova transação e testar automático
echo "2. CRIANDO NOVA TRANSAÇÃO COM NOTIFICAÇÃO AUTOMÁTICA\n";
echo "----------------------------------------------------\n";

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
    'valor_total' => 99.99,
    'codigo_transacao' => 'SIMPLE_FINAL_' . time(),
    'descricao' => 'Teste Final do Sistema Simples'
];

echo "Criando transação com dados:\n";
print_r($transactionData);

$transactionResult = TransactionController::registerTransaction($transactionData);

echo "\nResultado da criação: " . json_encode($transactionResult, JSON_PRETTY_PRINT) . "\n\n";

// 3. Testar com Transaction.php também
echo "3. TESTANDO VIA TRANSACTION.PHP\n";
echo "-------------------------------\n";

require_once __DIR__ . '/models/Transaction.php';

$transaction = new Transaction();
$transaction->setUsuarioId(9);
$transaction->setLojaId(59);
$transaction->setValorTotal(55.55);
$transaction->setDataTransacao(date('Y-m-d H:i:s'));
$transaction->setStatus(TRANSACTION_APPROVED);
$transaction->calcularDistribuicao();

echo "Salvando via Transaction.php...\n";
$saveResult = $transaction->save();

if ($saveResult) {
    echo "✅ Transação criada com ID: " . $transaction->getId() . "\n";
} else {
    echo "❌ Erro ao criar transação\n";
}

echo "\n=== TESTE CONCLUÍDO ===\n";
echo "Verifique os logs do PHP para ver as notificações!\n";
echo "Comando: tail -f [caminho_do_log_php]\n";
?>