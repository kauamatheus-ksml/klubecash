<?php
// fix_missing_balance.php - Versão corrigida para MariaDB

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
    
    // Primeiro, vamos verificar se as tabelas existem (versão corrigida)
    echo "1. Verificando estrutura do banco...\n";
    
    $tables = ['cashback_saldos', 'cashback_movimentacoes', 'transacoes_cashback'];
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "✓ Tabela $table existe\n";
                
                // Verificar estrutura
                $descStmt = $db->query("DESCRIBE $table");
                $columns = $descStmt->fetchAll(PDO::FETCH_COLUMN);
                echo "  Colunas: " . implode(', ', $columns) . "\n";
            } else {
                echo "✗ Tabela $table NÃO existe\n";
            }
        } catch (Exception $e) {
            echo "✗ Erro ao verificar tabela $table: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n2. Testando conexão e operações básicas...\n";
    
    // Teste básico de conexão
    $testStmt = $db->query("SELECT 1 as test");
    $testResult = $testStmt->fetch();
    
    if ($testResult) {
        echo "✓ Conexão com banco funcionando\n";
    }
    
    echo "\n3. Verificando transação específica...\n";
    
    // Buscar a transação problema
    $stmt = $db->prepare("SELECT * FROM transacoes_cashback WHERE id = ?");
    $stmt->execute([33]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($transaction) {
        echo "Transação encontrada:\n";
        echo "- ID: {$transaction['id']}\n";
        echo "- Usuario ID: {$transaction['usuario_id']}\n";
        echo "- Loja ID: {$transaction['loja_id']}\n";
        echo "- Valor Cliente: {$transaction['valor_cliente']}\n";
        echo "- Status: {$transaction['status']}\n";
        
        // Verificar se já existe saldo
        $balanceStmt = $db->prepare("SELECT * FROM cashback_saldos WHERE usuario_id = ? AND loja_id = ?");
        $balanceStmt->execute([$transaction['usuario_id'], $transaction['loja_id']]);
        $existingBalance = $balanceStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingBalance) {
            echo "Saldo já existe: R$ " . number_format($existingBalance['saldo_disponivel'], 2, ',', '.') . "\n";
        } else {
            echo "Nenhum saldo encontrado para este usuário/loja\n";
        }
        
        echo "\n4. Testando inserção manual na tabela cashback_saldos...\n";
        
        // Primeiro vamos tentar inserir diretamente para ver se o problema é na estrutura
        try {
            $insertStmt = $db->prepare("
                INSERT INTO cashback_saldos (usuario_id, loja_id, saldo_disponivel, total_creditado)
                VALUES (?, ?, ?, ?)
            ");
            
            $manualResult = $insertStmt->execute([
                $transaction['usuario_id'],
                $transaction['loja_id'],
                $transaction['valor_cliente'],
                $transaction['valor_cliente']
            ]);
            
            if ($manualResult) {
                echo "✓ Inserção manual bem-sucedida!\n";
                
                // Verificar se realmente inseriu
                $balanceStmt->execute([$transaction['usuario_id'], $transaction['loja_id']]);
                $newBalance = $balanceStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($newBalance) {
                    echo "✓ Saldo criado: R$ " . number_format($newBalance['saldo_disponivel'], 2, ',', '.') . "\n";
                }
            } else {
                echo "✗ Erro na inserção manual\n";
                $errorInfo = $insertStmt->errorInfo();
                echo "Erro SQL: " . json_encode($errorInfo) . "\n";
            }
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "! Registro já existe, tentando atualização...\n";
                
                $updateStmt = $db->prepare("
                    UPDATE cashback_saldos 
                    SET saldo_disponivel = saldo_disponivel + ?,
                        total_creditado = total_creditado + ?
                    WHERE usuario_id = ? AND loja_id = ?
                ");
                
                $updateResult = $updateStmt->execute([
                    $transaction['valor_cliente'],
                    $transaction['valor_cliente'],
                    $transaction['usuario_id'],
                    $transaction['loja_id']
                ]);
                
                if ($updateResult) {
                    echo "✓ Atualização bem-sucedida!\n";
                } else {
                    echo "✗ Erro na atualização\n";
                    $errorInfo = $updateStmt->errorInfo();
                    echo "Erro SQL: " . json_encode($errorInfo) . "\n";
                }
            } else {
                echo "✗ Erro manual: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n5. Testando inserção na tabela de movimentações...\n";
        
        try {
            $movStmt = $db->prepare("
                INSERT INTO cashback_movimentacoes (
                    usuario_id, loja_id, tipo_operacao, valor,
                    saldo_anterior, saldo_atual, descricao,
                    transacao_origem_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $movResult = $movStmt->execute([
                $transaction['usuario_id'],
                $transaction['loja_id'],
                'credito',
                $transaction['valor_cliente'],
                0,
                $transaction['valor_cliente'],
                'Teste de correção manual',
                $transaction['id']
            ]);
            
            if ($movResult) {
                echo "✓ Movimentação registrada com sucesso!\n";
            } else {
                echo "✗ Erro ao registrar movimentação\n";
                $errorInfo = $movStmt->errorInfo();
                echo "Erro SQL: " . json_encode($errorInfo) . "\n";
            }
            
        } catch (Exception $e) {
            echo "✗ Erro ao inserir movimentação: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "Transação #33 não encontrada!\n";
    }
    
    echo "\n6. Verificando resultado final...\n";
    
    // Ver todos os saldos
    $allBalancesStmt = $db->query("
        SELECT cs.*, u.nome as cliente_nome, l.nome_fantasia as loja_nome
        FROM cashback_saldos cs
        JOIN usuarios u ON cs.usuario_id = u.id
        JOIN lojas l ON cs.loja_id = l.id
    ");
    
    $balances = $allBalancesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($balances) > 0) {
        echo "Saldos encontrados:\n";
        foreach ($balances as $balance) {
            echo "- {$balance['cliente_nome']} na {$balance['loja_nome']}: R$ " . 
                 number_format($balance['saldo_disponivel'], 2, ',', '.') . "\n";
        }
    } else {
        echo "Nenhum saldo encontrado\n";
    }
    
    // Ver todas as movimentações
    $allMovsStmt = $db->query("
        SELECT cm.*, u.nome as cliente_nome, l.nome_fantasia as loja_nome
        FROM cashback_movimentacoes cm
        JOIN usuarios u ON cm.usuario_id = u.id
        JOIN lojas l ON cm.loja_id = l.id
    ");
    
    $movements = $allMovsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($movements) > 0) {
        echo "\nMovimentações encontradas:\n";
        foreach ($movements as $mov) {
            echo "- {$mov['cliente_nome']} na {$mov['loja_nome']}: {$mov['tipo_operacao']} R$ " . 
                 number_format($mov['valor'], 2, ',', '.') . "\n";
        }
    } else {
        echo "\nNenhuma movimentação encontrada\n";
    }
    
} catch (Exception $e) {
    echo "Erro geral: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>