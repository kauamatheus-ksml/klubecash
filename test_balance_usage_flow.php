<?php
// test_balance_usage_flow.php - Testar fluxo de uso de saldo

require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'controllers/TransactionController.php';
require_once 'models/CashbackBalance.php';

echo "<pre>";
echo "=== TESTE FLUXO DE USO DE SALDO ===\n\n";

try {
    $balanceModel = new CashbackBalance();
    $userId = 9; // Kaua Matheus
    $storeId = 13; // Sync Holding
    
    // 1. Verificar saldo inicial
    echo "1. Saldo inicial...\n";
    $initialBalance = $balanceModel->getStoreBalance($userId, $storeId);
    echo "Saldo inicial: R$ " . number_format($initialBalance, 2, ',', '.') . "\n\n";
    
    // 2. Simular venda com uso de saldo
    echo "2. Simulando venda com uso de saldo...\n";
    $transactionData = [
        'usuario_id' => $userId,
        'loja_id' => $storeId,
        'valor_total' => 100.00,
        'codigo_transacao' => 'TESTE_SALDO_' . date('YmdHis'),
        'descricao' => 'Teste de uso de saldo',
        'data_transacao' => date('Y-m-d H:i:s'),
        'usar_saldo' => true,
        'valor_saldo_usado' => 50.00 // Usar R$ 50,00 do saldo
    ];
    
    echo "Dados da transação:\n";
    echo "- Valor total: R$ " . number_format($transactionData['valor_total'], 2, ',', '.') . "\n";
    echo "- Saldo usado: R$ " . number_format($transactionData['valor_saldo_usado'], 2, ',', '.') . "\n";
    echo "- Valor efetivamente pago: R$ " . number_format($transactionData['valor_total'] - $transactionData['valor_saldo_usado'], 2, ',', '.') . "\n\n";
    
    // 3. Registrar transação (aqui deve diminuir o saldo)
    $registerResult = TransactionController::registerTransaction($transactionData);
    
    if ($registerResult['status']) {
        $transactionId = $registerResult['data']['transaction_id'];
        echo "✓ Transação registrada - ID: {$transactionId}\n";
        
        // 4. Verificar saldo após registro
        echo "\n3. Verificando saldo após registro...\n";
        $balanceAfterRegister = $balanceModel->getStoreBalance($userId, $storeId);
        echo "Saldo após registro: R$ " . number_format($balanceAfterRegister, 2, ',', '.') . "\n";
        echo "Diferença: R$ " . number_format($balanceAfterRegister - $initialBalance, 2, ',', '.') . "\n";
        
        if ($balanceAfterRegister < $initialBalance) {
            echo "✓ CORRETO: Saldo diminuiu como esperado\n";
        } else {
            echo "✗ ERRO: Saldo deveria diminuir mas não diminuiu\n";
        }
        
        // 5. Verificar movimentações
        echo "\n4. Verificando movimentações...\n";
        $db = Database::getConnection();
        $movStmt = $db->prepare("
            SELECT * FROM cashback_movimentacoes 
            WHERE usuario_id = ? AND loja_id = ? 
            ORDER BY data_operacao DESC 
            LIMIT 3
        ");
        $movStmt->execute([$userId, $storeId]);
        $movements = $movStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($movements as $mov) {
            echo "- {$mov['data_operacao']}: {$mov['tipo_operacao']} R$ " . number_format($mov['valor'], 2, ',', '.') . "\n";
            echo "  Descrição: {$mov['descricao']}\n";
            echo "  Saldo anterior: R$ " . number_format($mov['saldo_anterior'], 2, ',', '.') . 
                 " → Saldo atual: R$ " . number_format($mov['saldo_atual'], 2, ',', '.') . "\n\n";
        }
        
        // 6. Verificar detalhes da transação
        echo "5. Verificando detalhes da transação...\n";
        $transStmt = $db->prepare("
            SELECT t.*, 
                   CASE WHEN EXISTS(SELECT 1 FROM transacoes_saldo_usado tsu WHERE tsu.transacao_id = t.id) 
                        THEN (SELECT valor_usado FROM transacoes_saldo_usado WHERE transacao_id = t.id LIMIT 1)
                        ELSE 0 
                   END as saldo_usado_registrado
            FROM transacoes_cashback t 
            WHERE t.id = ?
        ");
        $transStmt->execute([$transactionId]);
        $transaction = $transStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($transaction) {
            echo "- ID: {$transaction['id']}\n";
            echo "- Valor total: R$ " . number_format($transaction['valor_total'], 2, ',', '.') . "\n";
            echo "- Valor cliente (cashback): R$ " . number_format($transaction['valor_cliente'], 2, ',', '.') . "\n";
            echo "- Status: {$transaction['status']}\n";
            echo "- Saldo usado registrado: R$ " . number_format($transaction['saldo_usado_registrado'], 2, ',', '.') . "\n";
        }
        
        // 7. Testar débito manual do saldo
        echo "\n6. Testando débito manual do saldo...\n";
        $manualDebitResult = $balanceModel->useBalance($userId, $storeId, 10.00, "Teste débito manual");
        
        if ($manualDebitResult) {
            echo "✓ Débito manual executado com sucesso\n";
            $balanceAfterDebit = $balanceModel->getStoreBalance($userId, $storeId);
            echo "Saldo após débito manual: R$ " . number_format($balanceAfterDebit, 2, ',', '.') . "\n";
        } else {
            echo "✗ Erro no débito manual\n";
        }
        
    } else {
        echo "✗ Erro no registro: {$registerResult['message']}\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>