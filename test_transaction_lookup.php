<?php
require_once __DIR__ . '/config/database.php';

echo "=== TESTE BUSCA DE TRANSAÇÃO POR ID ===\n";

// Simular dados como viriam de um sistema antigo com 'unknown'
$transactionData = [
    'cliente_telefone' => 'unknown', // TELEFONE INVÁLIDO
    'transaction_id' => 367 // ID DA TRANSAÇÃO REAL
];

echo "Dados de entrada:\n";
echo "- Telefone: " . $transactionData['cliente_telefone'] . "\n";
echo "- Transaction ID: " . $transactionData['transaction_id'] . "\n";

echo "\n=== TESTANDO ULTRADIRECTNOTIFIER COM BUSCA ===\n";

require_once __DIR__ . '/classes/UltraDirectNotifier.php';
$notifier = new UltraDirectNotifier();
$result = $notifier->notifyTransaction($transactionData);

echo "\nResultado: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
?>