test_exact_form_data.php<?php
// test_exact_form_data.php - Testar com dados idênticos ao formulário

require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/TransactionController.php';
require_once 'models/CashbackBalance.php';

// Simular autenticação exata
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_type'] = 'loja';

echo "<pre>";
echo "=== TESTE COM DADOS EXATOS DO FORMULÁRIO ===\n\n";

try {
    $balanceModel = new CashbackBalance();
    $userId = 9;
    $storeId = 13;
    
    // Saldo inicial
    $initialBalance = $balanceModel->getStoreBalance($userId, $storeId);
    echo "Saldo inicial: R$ " . number_format($initialBalance, 2, ',', '.') . "\n\n";
    
    // Usar dados EXATAMENTE como o formulário envia
    $transactionData = [
        'usuario_id' => 9,
        'loja_id' => 13,
        'valor_total' => 100.0,
        'codigo_transacao' => 'TESTE_FORM_' . time(),
        'usar_saldo' => true,  // Como mostrado no debug
        'valor_saldo_usado' => 50.0,  // Como mostrado no debug
        'descricao' => 'Teste de formulário'
    ];
    
    echo "Dados sendo enviados:\n";
    foreach ($transactionData as $key => $value) {
        echo "  {$key}: " . var_export($value, true) . "\n";
    }
    echo "\n";
    
    // Log para debug
    error_log("TESTE FORM: Iniciando teste com dados do formulário");
    error_log("TESTE FORM: usar_saldo = " . var_export($transactionData['usar_saldo'], true));
    error_log("TESTE FORM: valor_saldo_usado = " . var_export($transactionData['valor_saldo_usado'], true));
    
    // Registrar transação
    echo "Chamando TransactionController::registerTransaction...\n";
    $result = TransactionController::registerTransaction($transactionData);
    
    echo "Resultado:\n";
    echo "  Status: " . ($result['status'] ? 'SUCCESS' : 'ERROR') . "\n";
    echo "  Message: " . $result['message'] . "\n";
    
    if ($result['status']) {
        echo "  Transaction ID: " . $result['data']['transaction_id'] . "\n\n";
        
        // Verificar saldo final
        $finalBalance = $balanceModel->getStoreBalance($userId, $storeId);
        echo "Saldo final: R$ " . number_format($finalBalance, 2, ',', '.') . "\n";
        echo "Diferença: R$ " . number_format($finalBalance - $initialBalance, 2, ',', '.') . "\n\n";
        
        if ($finalBalance < $initialBalance) {
            echo "🎉 SUCESSO! Saldo foi debitado!\n";
        } else {
            echo "❌ PROBLEMA! Saldo não foi debitado!\n";
            
            // Debug adicional
            echo "\nDEBUG ADICIONAL:\n";
            
            // Verificar se a transação foi salva
            $db = Database::getConnection();
            $transStmt = $db->prepare("SELECT * FROM transacoes_cashback WHERE id = ?");
            $transStmt->execute([$result['data']['transaction_id']]);
            $transaction = $transStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($transaction) {
                echo "- Transação salva: ✓\n";
                echo "- Valor total: R$ " . $transaction['valor_total'] . "\n";
                echo "- Status: " . $transaction['status'] . "\n";
            }
            
            // Verificar se foi registrado uso de saldo
            $saldoStmt = $db->prepare("SELECT * FROM transacoes_saldo_usado WHERE transacao_id = ?");
            $saldoStmt->execute([$result['data']['transaction_id']]);
            $saldoUsado = $saldoStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($saldoUsado) {
                echo "- Uso de saldo registrado: ✓\n";
                echo "- Valor usado: R$ " . $saldoUsado['valor_usado'] . "\n";
            } else {
                echo "- Uso de saldo registrado: ✗\n";
            }
            
            // Verificar últimas movimentações
            $movStmt = $db->prepare("
                SELECT * FROM cashback_movimentacoes 
                WHERE usuario_id = ? AND loja_id = ? 
                ORDER BY data_operacao DESC 
                LIMIT 3
            ");
            $movStmt->execute([$userId, $storeId]);
            $movements = $movStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "- Últimas movimentações:\n";
            foreach ($movements as $mov) {
                echo "  {$mov['data_operacao']}: {$mov['tipo_operacao']} R$ {$mov['valor']}\n";
            }
        }
    } else {
        echo "❌ ERRO no registro!\n";
        error_log("TESTE FORM: ERRO - " . $result['message']);
    }
    
} catch (Exception $e) {
    echo "❌ EXCEÇÃO: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>