<?php
// test_final_fixed.php

require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/TransactionController.php';
require_once 'models/CashbackBalance.php';

// Simular autenticação
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_type'] = 'loja';

echo "<pre>";
echo "=== TESTE FINAL CORRIGIDO ===\n\n";

try {
    $balanceModel = new CashbackBalance();
    $userId = 9;
    $storeId = 13;
    
    // Saldo inicial
    $initialBalance = $balanceModel->getStoreBalance($userId, $storeId);
    echo "Saldo inicial: R$ " . number_format($initialBalance, 2, ',', '.') . "\n\n";
    
    // Registrar transação
    $transactionData = [
        'usuario_id' => $userId,
        'loja_id' => $storeId,
        'valor_total' => 100.00,
        'codigo_transacao' => 'FINAL_TEST_' . time(),
        'usar_saldo' => true,
        'valor_saldo_usado' => 25.00
    ];
    
    echo "Registrando transação com uso de saldo...\n";
    $result = TransactionController::registerTransaction($transactionData);
    
    if ($result['status']) {
        echo "✓ Transação registrada: #{$result['data']['transaction_id']}\n\n";
        
        // Saldo final
        $finalBalance = $balanceModel->getStoreBalance($userId, $storeId);
        echo "Saldo final: R$ " . number_format($finalBalance, 2, ',', '.') . "\n";
        echo "Diferença: R$ " . number_format($finalBalance - $initialBalance, 2, ',', '.') . "\n\n";
        
        if ($finalBalance < $initialBalance) {
            echo "🎉 SUCESSO TOTAL! Saldo debitado automaticamente!\n";
            echo "Sistema funcionando perfeitamente!\n";
        } else {
            echo "❌ Ainda há problema - saldo não foi debitado\n";
        }
    } else {
        echo "❌ Erro: {$result['message']}\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>