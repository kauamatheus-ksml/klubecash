<?php
/**
 * TESTE DO INSTANT NOTIFIER
 */

require_once __DIR__ . '/classes/InstantNotifier.php';

echo "=== TESTE INSTANT NOTIFIER ===\n";

$notifier = new InstantNotifier();

// Dados de teste
$transactionData = [
    'cliente_nome' => 'João Teste',
    'cliente_telefone' => '5534991191534',
    'valor_total' => 100.00,
    'valor_cliente' => 5.00,
    'loja_nome' => 'Loja Teste',
    'status' => 'pendente'
];

echo "Enviando notificação de teste...\n";

$result = $notifier->notifyTransaction($transactionData);

echo "Resultado:\n";
print_r($result);

echo "\n=== FIM DO TESTE ===\n";
?>