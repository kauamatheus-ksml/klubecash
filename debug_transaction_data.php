<?php
require_once __DIR__ . '/config/database.php';

// Simular dados da transação para debug
$transactionData = [
    'cliente_telefone' => '5534998002600',
    'cliente_nome' => 'Teste Debug',
    'valor_total' => 100.00,
    'valor_cliente' => 7.00,
    'loja_nome' => 'Loja Teste',
    'status' => 'aprovado'
];

echo "=== DEBUG TRANSACTION DATA ===\n";
echo "Telefone: " . ($transactionData['cliente_telefone'] ?? 'NOT SET') . "\n";
echo "Nome: " . ($transactionData['cliente_nome'] ?? 'NOT SET') . "\n";

// Testar UltraDirectNotifier
echo "\n=== TESTANDO ULTRADIRECTNOTIFIER ===\n";
require_once __DIR__ . '/classes/UltraDirectNotifier.php';

$notifier = new UltraDirectNotifier();
$result = $notifier->notifyTransaction($transactionData);

echo "Resultado: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
?>