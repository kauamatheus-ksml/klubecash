<?php
// debug_last_transaction.php - Diagnosticar última transação

require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'models/CashbackBalance.php';

echo "<pre>";
echo "=== DIAGNOSTICANDO ÚLTIMA TRANSAÇÃO ===\n\n";

try {
    $db = Database::getConnection();
    
    // 1. Verificar a transação mais recente
    echo "1. Verificando transação ID 41...\n";
    $transStmt = $db->prepare("
        SELECT t.*, u.nome as cliente_nome, l.nome_fantasia as loja_nome
        FROM transacoes_cashback t
        JOIN usuarios u ON t.usuario_id = u.id
        JOIN lojas l ON t.loja_id = l.id
        WHERE t.id = 41
    ");
    $transStmt->execute();
    $transaction = $transStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($transaction) {
        echo "- ID: {$transaction['id']}\n";
        echo "- Cliente: {$transaction['cliente_nome']} (ID: {$transaction['usuario_id']})\n";
        echo "- Loja: {$transaction['loja_nome']} (ID: {$transaction['loja_id']})\n";
        echo "- Valor Total: R$ {$transaction['valor_total']}\n";
        echo "- Valor Cliente: R$ {$transaction['valor_cliente']}\n";
        echo "- Status: {$transaction['status']}\n";
        echo "- Data: {$transaction['data_transacao']}\n\n";
    }
    
    // 2. Verificar o pagamento relacionado
    echo "2. Verificando pagamento ID 1016...\n";
    $paymentStmt = $db->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM pagamentos_transacoes WHERE pagamento_id = p.id) as qtd_transacoes
        FROM pagamentos_comissao p
        WHERE p.id = 1016
    ");
    $paymentStmt->execute();
    $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($payment) {
        echo "- ID: {$payment['id']}\n";
        echo "- Loja ID: {$payment['loja_id']}\n";
        echo "- Valor: R$ {$payment['valor_total']}\n";
        echo "- Status: {$payment['status']}\n";
        echo "- Transações associadas: {$payment['qtd_transacoes']}\n";
        echo "- Data registro: {$payment['data_registro']}\n";
        echo "- Data aprovação: {$payment['data_aprovacao']}\n\n";
    }
    
    // 3. Verificar associação pagamento-transação
    echo "3. Verificando associação pagamento-transação...\n";
    $assocStmt = $db->prepare("
        SELECT pt.*, t.valor_cliente, t.status as trans_status
        FROM pagamentos_transacoes pt
        JOIN transacoes_cashback t ON pt.transacao_id = t.id
        WHERE pt.pagamento_id = 1016
    ");
    $assocStmt->execute();
    $associations = $assocStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($associations as $assoc) {
        echo "- Transação {$assoc['transacao_id']}: Valor cliente R$ {$assoc['valor_cliente']}, Status: {$assoc['trans_status']}\n";
    }
    echo "\n";
    
    // 4. Verificar se existe saldo para usuario 9, loja 13
    echo "4. Verificando saldo atual...\n";
    $balanceStmt = $db->prepare("
        SELECT * FROM cashback_saldos 
        WHERE usuario_id = 9 AND loja_id = 13
    ");
    $balanceStmt->execute();
    $balance = $balanceStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($balance) {
        echo "- Saldo disponível: R$ {$balance['saldo_disponivel']}\n";
        echo "- Total creditado: R$ {$balance['total_creditado']}\n";
        echo "- Total usado: R$ {$balance['total_usado']}\n";
        echo "- Última atualização: {$balance['ultima_atualizacao']}\n\n";
    } else {
        echo "- Nenhum saldo encontrado!\n\n";
    }
    
    // 5. Verificar movimentações
    echo "5. Verificando movimentações...\n";
    $movStmt = $db->prepare("
        SELECT * FROM cashback_movimentacoes 
        WHERE usuario_id = 9 AND loja_id = 13
        ORDER BY data_operacao DESC
    ");
    $movStmt->execute();
    $movements = $movStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($movements) > 0) {
        foreach ($movements as $mov) {
            echo "- {$mov['data_operacao']}: {$mov['tipo_operacao']} R$ {$mov['valor']} (Transação origem: {$mov['transacao_origem_id']})\n";
            echo "  Descrição: {$mov['descricao']}\n";
        }
    } else {
        echo "- Nenhuma movimentação encontrada!\n";
    }
    
    echo "\n6. Testando crédito manual da transação 41...\n";
    
    if ($transaction && $transaction['status'] === 'aprovado' && $transaction['valor_cliente'] > 0) {
        $balanceModel = new CashbackBalance();
        $manualResult = $balanceModel->addBalance(
            $transaction['usuario_id'],
            $transaction['loja_id'],
            $transaction['valor_cliente'],
            "Teste manual - Transação #{$transaction['id']}",
            $transaction['id']
        );
        
        if ($manualResult) {
            echo "✓ Crédito manual realizado com sucesso!\n";
            
            // Verificar saldo após crédito manual
            $newBalance = $balanceModel->getStoreBalance($transaction['usuario_id'], $transaction['loja_id']);
            echo "✓ Novo saldo: R$ " . number_format($newBalance, 2, ',', '.') . "\n";
        } else {
            echo "✗ Erro no crédito manual!\n";
        }
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>