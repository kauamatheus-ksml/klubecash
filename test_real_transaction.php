<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/TransactionController.php';

echo "=== SIMULANDO TRANSAÇÃO REAL ===\n";

// Dados reais de transação
$transactionData = [
    'cliente_id' => 1,
    'cliente_nome' => 'Kaua Teste',
    'cliente_telefone' => '5534998002600', // TELEFONE REAL
    'loja_id' => 1,
    'loja_nome' => 'Sync Holding',
    'valor_total' => 50.00,
    'valor_cliente' => 3.50,
    'status' => 'aprovado',
    'transaction_id' => 999
];

echo "Dados da transação:\n";
echo "- Telefone: " . $transactionData['cliente_telefone'] . "\n";
echo "- Nome: " . $transactionData['cliente_nome'] . "\n";
echo "- Valor: R$ " . $transactionData['valor_total'] . "\n";

echo "\n=== EXECUTANDO NOTIFICAÇÃO ===\n";

// Chamar método de notificação diretamente
TransactionController::notifyTransaction($transactionData);

echo "\n=== TESTE CONCLUÍDO ===\n";
echo "Verifique o log: logs/ultra_direct.log\n";
?>