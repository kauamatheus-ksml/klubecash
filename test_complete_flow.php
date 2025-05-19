<?php
// test_complete_flow.php - Teste completo corrigido

require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'controllers/TransactionController.php';
require_once 'models/CashbackBalance.php';

echo "<pre>";
echo "=== TESTE FLUXO COMPLETO CORRIGIDO ===\n\n";

try {
    $balanceModel = new CashbackBalance();
    
    // 1. Verificar saldo inicial
    echo "1. Saldo inicial...\n";
    $initialBalance = $balanceModel->getStoreBalance(9, 13);
    echo "Saldo inicial: R$ " . number_format($initialBalance, 2, ',', '.') . "\n\n";
    
    // 2. Registrar nova transação
    echo "2. Registrando nova transação...\n";
    $transactionData = [
        'usuario_id' => 9,
        'loja_id' => 13,
        'valor_total' => 200.00,
        'codigo_transacao' => 'TESTE_FINAL_' . date('YmdHis'),
        'descricao' => 'Teste fluxo completo corrigido',
        'data_transacao' => date('Y-m-d H:i:s')
    ];
    
    $registerResult = TransactionController::registerTransaction($transactionData);
    
    if ($registerResult['status']) {
        $transactionId = $registerResult['data']['transaction_id'];
        echo "✓ Transação registrada - ID: {$transactionId}\n";
        echo "  Valor cliente esperado: R$ " . number_format($registerResult['data']['valor_cashback'] * 0.5, 2, ',', '.') . "\n\n";
        
        // 3. Registrar pagamento
        echo "3. Registrando pagamento...\n";
        $paymentData = [
            'loja_id' => 13,
            'transacoes' => [$transactionId],
            'valor_total' => 10.00, // 5% da venda
            'metodo_pagamento' => 'pix',
            'numero_referencia' => 'TESTE_FINAL_PIX_' . date('His'),
            'observacao' => 'Pagamento teste final'
        ];
        
        $paymentResult = TransactionController::registerPayment($paymentData);
        
        if ($paymentResult['status']) {
            $paymentId = $paymentResult['data']['payment_id'];
            echo "✓ Pagamento registrado - ID: {$paymentId}\n\n";
            
            // 4. Aprovar pagamento
            echo "4. Aprovando pagamento...\n";
            $approvalResult = TransactionController::approvePayment($paymentId, 'Aprovação teste final');
            
            if ($approvalResult['status']) {
                echo "✓ Pagamento aprovado!\n";
                echo "✓ Saldos creditados: {$approvalResult['data']['saldos_creditados']}\n\n";
                
                // 5. Verificar saldo final
                echo "5. Verificando saldo final...\n";
                $finalBalance = $balanceModel->getStoreBalance(9, 13);
                echo "Saldo final: R$ " . number_format($finalBalance, 2, ',', '.') . "\n";
                echo "Diferença: R$ " . number_format($finalBalance - $initialBalance, 2, ',', '.') . "\n";
                
                if ($finalBalance > $initialBalance) {
                    echo "\n🎉 SUCESSO! O sistema está funcionando automaticamente!\n";
                } else {
                    echo "\n❌ ERRO! O saldo não foi creditado automaticamente.\n";
                }
                
            } else {
                echo "✗ Erro na aprovação: {$approvalResult['message']}\n";
            }
        } else {
            echo "✗ Erro no pagamento: {$paymentResult['message']}\n";
        }
    } else {
        echo "✗ Erro no registro: {$registerResult['message']}\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>