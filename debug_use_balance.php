<?php
// debug_use_balance.php - Debugar método useBalance

require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'models/CashbackBalance.php';

echo "<pre>";
echo "=== DEBUG DO MÉTODO USEBALANCE ===\n\n";

try {
    $balanceModel = new CashbackBalance();
    $userId = 9;
    $storeId = 13;
    $amount = 50.00;
    $description = "Teste débito manual direto";
    
    // 1. Verificar saldo atual
    echo "1. Verificando saldo atual...\n";
    $currentBalance = $balanceModel->getStoreBalance($userId, $storeId);
    echo "Saldo atual: R$ " . number_format($currentBalance, 2, ',', '.') . "\n\n";
    
    if ($currentBalance < $amount) {
        echo "ERRO: Saldo insuficiente!\n";
        exit;
    }
    
    // 2. Testar useBalance step by step
    echo "2. Testando useBalance passo a passo...\n";
    
    $db = Database::getConnection();
    
    // Simular o que o useBalance faz
    try {
        echo "- Iniciando transação...\n";
        $db->beginTransaction();
        
        echo "- Calculando novo saldo...\n";
        $newBalance = $currentBalance - $amount;
        echo "  Saldo atual: R$ {$currentBalance}\n";
        echo "  Valor a debitar: R$ {$amount}\n";
        echo "  Novo saldo: R$ {$newBalance}\n";
        
        echo "- Executando UPDATE...\n";
        $stmt = $db->prepare("
            UPDATE cashback_saldos 
            SET saldo_disponivel = saldo_disponivel - :amount,
                total_usado = total_usado + :amount,
                ultima_atualizacao = CURRENT_TIMESTAMP
            WHERE usuario_id = :user_id AND loja_id = :store_id
        ");
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':store_id', $storeId);
        $stmt->bindParam(':amount', $amount);
        
        $updateResult = $stmt->execute();
        
        if ($updateResult) {
            echo "  ✓ UPDATE executado com sucesso\n";
            
            // Verificar linhas afetadas
            $rowsAffected = $stmt->rowCount();
            echo "  Linhas afetadas: {$rowsAffected}\n";
            
            if ($rowsAffected == 0) {
                echo "  ⚠️  Nenhuma linha foi atualizada!\n";
                throw new Exception('Nenhuma linha foi atualizada');
            }
        } else {
            echo "  ✗ Erro no UPDATE\n";
            $errorInfo = $stmt->errorInfo();
            echo "  Erro SQL: " . print_r($errorInfo, true);
            throw new Exception('UPDATE falhou');
        }
        
        echo "- Inserindo movimentação...\n";
        $movStmt = $db->prepare("
            INSERT INTO cashback_movimentacoes (
                usuario_id, loja_id, tipo_operacao, valor,
                saldo_anterior, saldo_atual, descricao
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $movResult = $movStmt->execute([
            $userId,
            $storeId,
            'uso',
            $amount,
            $currentBalance,
            $newBalance,
            $description
        ]);
        
        if ($movResult) {
            echo "  ✓ Movimentação inserida com sucesso\n";
        } else {
            echo "  ✗ Erro na movimentação\n";
            $errorInfo = $movStmt->errorInfo();
            echo "  Erro SQL: " . print_r($errorInfo, true);
            throw new Exception('Movimentação falhou');
        }
        
        echo "- Fazendo commit...\n";
        $db->commit();
        echo "  ✓ Commit realizado\n";
        
        // Verificar resultado
        echo "\n3. Verificando resultado...\n";
        $finalBalance = $balanceModel->getStoreBalance($userId, $storeId);
        echo "Saldo final: R$ " . number_format($finalBalance, 2, ',', '.') . "\n";
        echo "Diferença: R$ " . number_format($finalBalance - $currentBalance, 2, ',', '.') . "\n";
        
        if ($finalBalance < $currentBalance) {
            echo "✓ SUCESSO: Saldo foi debitado corretamente!\n";
        } else {
            echo "✗ ERRO: Saldo não foi debitado!\n";
        }
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
            echo "- Rollback executado\n";
        }
        echo "Erro: " . $e->getMessage() . "\n";
    }
    
    // 4. Testar método useBalance completo
    echo "\n4. Testando método useBalance completo...\n";
    $useBalanceResult = $balanceModel->useBalance($userId, $storeId, 10.00, "Teste completo");
    
    if ($useBalanceResult) {
        echo "✓ useBalance funcionou!\n";
        $balanceAfterMethod = $balanceModel->getStoreBalance($userId, $storeId);
        echo "Saldo após useBalance: R$ " . number_format($balanceAfterMethod, 2, ',', '.') . "\n";
    } else {
        echo "✗ useBalance falhou!\n";
    }
    
} catch (Exception $e) {
    echo "Erro geral: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>