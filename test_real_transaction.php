<?php
require_once __DIR__ . '/config/database.php';

echo "=== TESTE DIRETO DO ULTRADIRECTNOTIFIER ===\n";

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

echo "\n=== TESTANDO ULTRADIRECTNOTIFIER ===\n";

require_once __DIR__ . '/classes/UltraDirectNotifier.php';
$notifier = new UltraDirectNotifier();
$result = $notifier->notifyTransaction($transactionData);

echo "Resultado: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";

echo "\n=== TESTE CONCLUÍDO ===\n";
echo "Mensagem enviada com telefone REAL: " . $transactionData['cliente_telefone'] . "\n";
?>