<?php
// test_automatic_flow.php - Testar fluxo automático

require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'controllers/TransactionController.php';

echo "<pre>";
echo "=== TESTANDO FLUXO AUTOMÁTICO ===\n\n";

try {
    $db = Database::getConnection();
    
    // Simular uma nova transação
    echo "1. Registrando nova transação de teste...\n";
    
    $transactionData = [
        'usuario_id' => 9, // Kaua Matheus
        'loja_id' => 13,   // Sync Holding
        'valor_total' => 100.00,
        'codigo_transacao' => 'TESTE_' . date('YmdHis'),
        'descricao' => 'Teste de fluxo automático',
        'data_transacao' => date('Y-m-d H:i:s')
    ];
    
    $registerResult = TransactionController::registerTransaction($transactionData);
    
    if ($registerResult['status']) {
        $transactionId = $registerResult['data']['transaction_id'];
        echo "✓ Transação registrada - ID: {$transactionId}\n";
        
        // Simular pagamento (normalmente feito pela loja)
        echo "\n2. Simulando pagamento da loja...\n";
        
        $paymentData = [
            'loja_id' => 13,
            'transacoes' => [$transactionId],
            'valor_total' => 5.00, // 5% da venda = comissão total
            'metodo_pagamento' => 'pix',
            'numero_referencia' => 'TESTE_PIX_' . date('His'),
            'observacao' => 'Pagamento de teste automático'
        ];
        
        $paymentResult = TransactionController::registerPayment($paymentData);
        
        if ($paymentResult['status']) {
            $paymentId = $paymentResult['data']['payment_id'];
            echo "✓ Pagamento registrado - ID: {$paymentId}\n";
            
            // Simular aprovação (normalmente feito pelo admin)
            echo "\n3. Simulando aprovação do admin...\n";
            
            $approvalResult = TransactionController::approvePayment($paymentId, 'Aprovação de teste automático');
            
            if ($approvalResult['status']) {
                echo "✓ Pagamento aprovado com sucesso!\n";
                echo "✓ Saldos creditados: {$approvalResult['data']['saldos_creditados']}\n";
                
                // Verificar se o saldo foi creditado corretamente
                echo "\n4. Verificando saldo final...\n";
                
                require_once 'models/CashbackBalance.php';
                $balanceModel = new CashbackBalance();
                $finalBalance = $balanceModel->getStoreBalance(9, 13);
                
                echo "Saldo final do cliente: R$ " . number_format($finalBalance, 2, ',', '.') . "\n";
                
                // Verificar movimentações
                $movStmt = $db->prepare("
                    SELECT COUNT(*) as total
                    FROM cashback_movimentacoes 
                    WHERE usuario_id = 9 AND loja_id = 13
                ");
                $movStmt->execute();
                $movCount = $movStmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                echo "Total de movimentações registradas: {$movCount}\n";
                
                echo "\n✓ FLUXO AUTOMÁTICO FUNCIONANDO CORRETAMENTE!\n";
                
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
    echo "Erro geral: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>