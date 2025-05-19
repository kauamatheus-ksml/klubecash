<?php
// fix_missing_balance.php - Script temporário para corrigir saldos

require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'models/CashbackBalance.php';

try {
    $db = Database::getConnection();
    
    // Buscar transações aprovadas que não tem saldo creditado
    $stmt = $db->prepare("
        SELECT t.*, u.nome as cliente_nome, l.nome_fantasia as loja_nome
        FROM transacoes_cashback t
        JOIN usuarios u ON t.usuario_id = u.id
        JOIN lojas l ON t.loja_id = l.id
        WHERE t.status = 'aprovado'
        AND t.valor_cliente > 0
        AND NOT EXISTS (
            SELECT 1 FROM cashback_saldos cs 
            WHERE cs.usuario_id = t.usuario_id 
            AND cs.loja_id = t.loja_id
        )
        ORDER BY t.data_transacao
    ");
    
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Encontradas " . count($transactions) . " transações aprovadas sem saldo creditado:\n\n";
    
    if (count($transactions) > 0) {
        $balanceModel = new CashbackBalance();
        
        foreach ($transactions as $transaction) {
            echo "Processando transação #{$transaction['id']}:\n";
            echo "- Cliente: {$transaction['cliente_nome']}\n";
            echo "- Loja: {$transaction['loja_nome']}\n";
            echo "- Valor do cashback: R$ " . number_format($transaction['valor_cliente'], 2, ',', '.') . "\n";
            
            $description = "Correção: Cashback da compra - Transação #{$transaction['id']}";
            
            $result = $balanceModel->addBalance(
                $transaction['usuario_id'],
                $transaction['loja_id'],
                $transaction['valor_cliente'],
                $description,
                $transaction['id']
            );
            
            if ($result) {
                echo "✓ Saldo creditado com sucesso!\n";
            } else {
                echo "✗ Erro ao creditar saldo!\n";
            }
            echo "\n";
        }
    }
    
    // Verificar saldos atuais
    echo "\n=== SALDOS ATUAIS ===\n";
    $balanceStmt = $db->prepare("
        SELECT cs.*, u.nome as cliente_nome, l.nome_fantasia as loja_nome
        FROM cashback_saldos cs
        JOIN usuarios u ON cs.usuario_id = u.id
        JOIN lojas l ON cs.loja_id = l.id
        ORDER BY cs.saldo_disponivel DESC
    ");
    
    $balanceStmt->execute();
    $balances = $balanceStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($balances as $balance) {
        echo "Cliente: {$balance['cliente_nome']} | ";
        echo "Loja: {$balance['loja_nome']} | ";
        echo "Saldo: R$ " . number_format($balance['saldo_disponivel'], 2, ',', '.') . "\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>