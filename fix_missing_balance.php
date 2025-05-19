<?php
// fix_missing_balance.php - Versão atualizada com mais debug

require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'models/CashbackBalance.php';

// Habilitar exibição de erros para debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<pre>";
echo "=== DEBUGANDO SISTEMA DE SALDO ===\n\n";

try {
    $db = Database::getConnection();
    
    // Primeiro, vamos verificar se as tabelas existem
    echo "1. Verificando estrutura do banco...\n";
    
    $tables = ['cashback_saldos', 'cashback_movimentacoes', 'transacoes_cashback'];
    foreach ($tables as $table) {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->rowCount() > 0) {
            echo "✓ Tabela $table existe\n";
            
            // Verificar estrutura
            $descStmt = $db->prepare("DESCRIBE $table");
            $descStmt->execute();
            $columns = $descStmt->fetchAll(PDO::FETCH_COLUMN);
            echo "  Colunas: " . implode(', ', $columns) . "\n";
        } else {
            echo "✗ Tabela $table NÃO existe\n";
        }
    }
    
    echo "\n2. Testando transação específica...\n";
    
    // Buscar a transação problema
    $stmt = $db->prepare("
        SELECT * FROM transacoes_cashback 
        WHERE id = 33
    ");
    $stmt->execute();
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($transaction) {
        echo "Transação encontrada:\n";
        echo "- ID: {$transaction['id']}\n";
        echo "- Usuario ID: {$transaction['usuario_id']}\n";
        echo "- Loja ID: {$transaction['loja_id']}\n";
        echo "- Valor Cliente: {$transaction['valor_cliente']}\n";
        echo "- Status: {$transaction['status']}\n";
        
        // Verificar se já existe saldo
        $balanceStmt = $db->prepare("
            SELECT * FROM cashback_saldos 
            WHERE usuario_id = ? AND loja_id = ?
        ");
        $balanceStmt->execute([$transaction['usuario_id'], $transaction['loja_id']]);
        $existingBalance = $balanceStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingBalance) {
            echo "Saldo já existe: R$ " . number_format($existingBalance['saldo_disponivel'], 2, ',', '.') . "\n";
        } else {
            echo "Nenhum saldo encontrado para este usuário/loja\n";
        }
        
        echo "\n3. Tentando creditar saldo...\n";
        
        $balanceModel = new CashbackBalance();
        $result = $balanceModel->addBalance(
            $transaction['usuario_id'],
            $transaction['loja_id'],
            $transaction['valor_cliente'],
            "Teste de correção",
            $transaction['id']
        );
        
        if ($result) {
            echo "✓ Saldo creditado com sucesso!\n";
        } else {
            echo "✗ Erro ao creditar saldo!\n";
        }
        
        // Verificar resultado
        $balanceStmt->execute([$transaction['usuario_id'], $transaction['loja_id']]);
        $newBalance = $balanceStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($newBalance) {
            echo "Saldo atual: R$ " . number_format($newBalance['saldo_disponivel'], 2, ',', '.') . "\n";
        }
        
    } else {
        echo "Transação #33 não encontrada!\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>