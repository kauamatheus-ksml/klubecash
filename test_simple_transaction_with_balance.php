<?php
// test_simple_transaction_with_balance.php

require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'controllers/TransactionController.php';
require_once 'models/CashbackBalance.php';

echo "<pre>";
echo "=== TESTE SIMPLES DE TRANSAÇÃO COM SALDO ===\n\n";

try {
    $balanceModel = new CashbackBalance();
    $userId = 9;
    $storeId = 13;
    
    // Verificar saldo inicial
    $initialBalance = $balanceModel->getStoreBalance($userId, $storeId);
    echo "Saldo inicial: R$ " . number_format($initialBalance, 2, ',', '.') . "\n\n";
    
    // Registrar transação com uso de saldo
    $transactionData = [
        'usuario_id' => $userId,
        'loja_id' => $storeId,
        'valor_total' => 100.00,
        'codigo_transacao' => 'TESTE_SIMPLES_' . time(),
        'usar_saldo' => true,
        'valor_saldo_usado' => 30.00
    ];
    
    echo "Registrando transação...\n";
    echo "- Valor total: R$ 100,00\n";
    echo "- Saldo a usar: R$ 30,00\n";
    echo "- Valor pago: R$ 70,00\n\n";
    
    $result = TransactionController::registerTransaction($transactionData);
    
    if ($result['status']) {
        echo "✓ Transação registrada: #{$result['data']['transaction_id']}\n\n";
        
        // Verificar saldo final
        $finalBalance = $balanceModel->getStoreBalance($userId, $storeId);
        echo "Saldo final: R$ " . number_format($finalBalance, 2, ',', '.') . "\n";
        echo "Diferença: R$ " . number_format($finalBalance - $initialBalance, 2, ',', '.') . "\n\n";
        
        if ($finalBalance < $initialBalance) {
            echo "🎉 SUCESSO! Saldo foi debitado automaticamente!\n";
        } else {
            echo "❌ ERRO! Saldo não foi debitado!\n";
        }
    } else {
        echo "❌ Erro: {$result['message']}\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>