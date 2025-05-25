<?php
// scripts/fix_missing_reimbursements.php
require_once '../config/database.php';

echo "🔧 Iniciando correção de reembolsos faltantes...\n\n";

try {
    $db = Database::getConnection();
    
    // Buscar todas as movimentações de uso de saldo sem pagamento vinculado
    $stmt = $db->query("
        SELECT cm.*, l.nome_fantasia, u.nome as cliente_nome
        FROM cashback_movimentacoes cm
        JOIN lojas l ON cm.loja_id = l.id
        JOIN usuarios u ON cm.usuario_id = u.id
        WHERE cm.tipo_operacao = 'uso' 
        AND cm.transacao_uso_id IS NOT NULL
        AND cm.pagamento_id IS NULL
        ORDER BY cm.data_operacao DESC
    ");
    
    $movimentacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 Encontradas " . count($movimentacoes) . " movimentações sem reembolso.\n\n";
    
    foreach ($movimentacoes as $mov) {
        echo "🔄 Processando movimentação ID {$mov['id']}:\n";
        echo "   • Loja: {$mov['nome_fantasia']}\n";
        echo "   • Cliente: {$mov['cliente_nome']}\n";
        echo "   • Valor: R$ " . number_format($mov['valor'], 2, ',', '.') . "\n";
        echo "   • Data: {$mov['data_operacao']}\n";
        
        // Verificar se já existe pagamento pendente para esta loja
        $checkStmt = $db->prepare("
            SELECT id, valor_total FROM store_balance_payments 
            WHERE loja_id = ? AND status = 'pendente'
            ORDER BY data_criacao DESC LIMIT 1
        ");
        $checkStmt->execute([$mov['loja_id']]);
        $existingPayment = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingPayment) {
            // Atualizar pagamento existente
            $updateStmt = $db->prepare("
                UPDATE store_balance_payments 
                SET valor_total = valor_total + ?,
                    observacao = CONCAT(COALESCE(observacao, ''), '\n• Transação #', ?, ' - R$ ', ?, ' (Correção automática)')
                WHERE id = ?
            ");
            $updateStmt->execute([$mov['valor'], $mov['transacao_uso_id'], number_format($mov['valor'], 2, ',', '.'), $existingPayment['id']]);
            
            // Vincular movimentação ao pagamento
            $linkStmt = $db->prepare("
                UPDATE cashback_movimentacoes 
                SET pagamento_id = ?
                WHERE id = ?
            ");
            $linkStmt->execute([$existingPayment['id'], $mov['id']]);
            
            echo "   ✅ Adicionado ao pagamento existente ID {$existingPayment['id']}\n";
        } else {
            // Criar novo pagamento
            $insertStmt = $db->prepare("
                INSERT INTO store_balance_payments 
                (loja_id, valor_total, metodo_pagamento, observacao, status, data_criacao)
                VALUES (?, ?, 'reembolso_saldo', ?, 'pendente', ?)
            ");
            
            $observacao = "Reembolso de saldo usado pelos clientes\n• Transação #{$mov['transacao_uso_id']} - R$ " . number_format($mov['valor'], 2, ',', '.') . " (Correção automática)";
            $insertStmt->execute([
                $mov['loja_id'], 
                $mov['valor'], 
                $observacao,
                $mov['data_operacao']
            ]);
            
            $paymentId = $db->lastInsertId();
            
            // Vincular movimentação ao pagamento
            $linkStmt = $db->prepare("
                UPDATE cashback_movimentacoes 
                SET pagamento_id = ?
                WHERE id = ?
            ");
            $linkStmt->execute([$paymentId, $mov['id']]);
            
            echo "   ✅ Criado novo pagamento ID $paymentId\n";
        }
        echo "\n";
    }
    
    echo "🎉 Correção concluída com sucesso!\n";
    echo "💡 Agora verifique a aba 'Pagamentos de Saldo às Lojas' no painel administrativo.\n";
    
} catch (Exception $e) {
    echo "❌ Erro durante a correção: " . $e->getMessage() . "\n";
}
?>