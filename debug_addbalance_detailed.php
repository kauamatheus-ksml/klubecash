<?php
// debug_addbalance_detailed.php - Debug detalhado do addBalance

require_once 'config/database.php';
require_once 'config/constants.php';

echo "<pre>";
echo "=== DEBUG DETALHADO DO ADDBALANCE ===\n\n";

try {
    $db = Database::getConnection();
    
    // Dados da transação que queremos creditar
    $userId = 9;
    $storeId = 13;
    $amount = 2.50;
    $description = "Teste debug manual";
    $transactionId = 41;
    
    echo "1. Testando conexão e estrutura básica...\n";
    
    // Verificar se a tabela existe e suas colunas
    $tableStmt = $db->query("DESCRIBE cashback_saldos");
    $columns = $tableStmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Colunas da tabela cashback_saldos: " . implode(', ', $columns) . "\n\n";
    
    echo "2. Verificando saldo atual...\n";
    $currentStmt = $db->prepare("
        SELECT saldo_disponivel 
        FROM cashback_saldos 
        WHERE usuario_id = ? AND loja_id = ?
    ");
    $currentStmt->execute([$userId, $storeId]);
    $currentBalance = $currentStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($currentBalance) {
        $currentValue = floatval($currentBalance['saldo_disponivel']);
        echo "Saldo atual encontrado: R$ {$currentValue}\n";
    } else {
        $currentValue = 0.00;
        echo "Nenhum saldo encontrado\n";
    }
    
    echo "\n3. Tentando INSERT ON DUPLICATE KEY UPDATE...\n";
    
    try {
        $stmt = $db->prepare("
            INSERT INTO cashback_saldos (usuario_id, loja_id, saldo_disponivel, total_creditado)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                saldo_disponivel = saldo_disponivel + VALUES(saldo_disponivel),
                total_creditado = total_creditado + VALUES(total_creditado),
                ultima_atualizacao = CURRENT_TIMESTAMP
        ");
        
        $result = $stmt->execute([$userId, $storeId, $amount, $amount]);
        
        if ($result) {
            echo "✓ INSERT/UPDATE executado com sucesso\n";
            
            // Verificar se realmente atualizou
            $checkStmt = $db->prepare("
                SELECT saldo_disponivel, total_creditado 
                FROM cashback_saldos 
                WHERE usuario_id = ? AND loja_id = ?
            ");
            $checkStmt->execute([$userId, $storeId]);
            $newBalance = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($newBalance) {
                echo "Novo saldo: R$ {$newBalance['saldo_disponivel']}\n";
                echo "Total creditado: R$ {$newBalance['total_creditado']}\n";
            }
        } else {
            echo "✗ Erro no INSERT/UPDATE\n";
            $errorInfo = $stmt->errorInfo();
            echo "Erro SQL: " . print_r($errorInfo, true) . "\n";
        }
    } catch (Exception $e) {
        echo "✗ Exceção no INSERT/UPDATE: " . $e->getMessage() . "\n";
    }
    
    echo "\n4. Tentando inserir movimentação...\n";
    
    try {
        $movStmt = $db->prepare("
            INSERT INTO cashback_movimentacoes (
                usuario_id, loja_id, tipo_operacao, valor,
                saldo_anterior, saldo_atual, descricao,
                transacao_origem_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $newCurrentValue = $currentValue + $amount;
        $movResult = $movStmt->execute([
            $userId,
            $storeId,
            'credito',
            $amount,
            $currentValue,
            $newCurrentValue,
            $description,
            $transactionId
        ]);
        
        if ($movResult) {
            echo "✓ Movimentação inserida com sucesso\n";
        } else {
            echo "✗ Erro na inserção da movimentação\n";
            $errorInfo = $movStmt->errorInfo();
            echo "Erro SQL: " . print_r($errorInfo, true) . "\n";
        }
        
    } catch (Exception $e) {
        echo "✗ Exceção na movimentação: " . $e->getMessage() . "\n";
    }
    
    echo "\n5. Verificação final...\n";
    
    // Verificar saldo final
    $finalStmt = $db->prepare("
        SELECT * FROM cashback_saldos 
        WHERE usuario_id = ? AND loja_id = ?
    ");
    $finalStmt->execute([$userId, $storeId]);
    $finalBalance = $finalStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($finalBalance) {
        echo "Saldo final: R$ {$finalBalance['saldo_disponivel']}\n";
        echo "Total creditado: R$ {$finalBalance['total_creditado']}\n";
    }
    
    // Verificar movimentações
    $movFinalStmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM cashback_movimentacoes 
        WHERE usuario_id = ? AND loja_id = ?
    ");
    $movFinalStmt->execute([$userId, $storeId]);
    $movCount = $movFinalStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Total de movimentações: {$movCount['total']}\n";
    
} catch (Exception $e) {
    echo "Erro geral: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>