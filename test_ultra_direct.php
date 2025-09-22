<?php
/**
 * TESTE ULTRA DIRETO
 */

require_once __DIR__ . '/classes/UltraDirectNotifier.php';

echo "=== TESTE ULTRA DIRETO ===\n";

$notifier = new UltraDirectNotifier();

// Primeiro testar o bot
echo "1. Testando conexão com bot...\n";
$botTest = $notifier->testBot();
print_r($botTest);

if ($botTest['success']) {
    echo "\n2. Enviando notificação de teste...\n";

    $transactionData = [
        'cliente_nome' => 'João Teste Ultra',
        'cliente_telefone' => '5534991191534',
        'valor_total' => 100.00,
        'valor_cliente' => 5.00,
        'loja_nome' => 'Loja Teste Ultra',
        'status' => 'pendente'
    ];

    $result = $notifier->notifyTransaction($transactionData);

    echo "Resultado:\n";
    print_r($result);
} else {
    echo "❌ Bot não está disponível!\n";
}

echo "\n=== FIM DO TESTE ===\n";
?>