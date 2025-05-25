<?php
// fix_store_balance_payments.php
require_once 'config/database.php';

echo "<h2>🔧 Correção de Pagamentos de Saldo às Lojas</h2>\n";
echo "<pre>\n";

try {
    $db = Database::getConnection();
    
    echo "📊 Analisando movimentações de uso de saldo...\n\n";
    
    // Buscar todas as movimentações de uso de saldo
    $stmt = $db->query("
        SELECT 
            cm.id,
            cm.usuario_id,
            cm.loja_id,
            cm.valor,
            cm.data_operacao,
            cm.transacao_uso_id,
            cm.pagamento_id,
            l.nome_fantasia as loja_nome,
            u.nome as cliente_nome
        FROM cashback_movimentacoes cm
        JOIN lojas l ON cm.loja_id = l.id
        JOIN usuarios u ON cm.usuario_id = u.id
        WHERE cm.tipo_operacao = 'uso' 
        AND cm.transacao_uso_id IS NOT NULL
        ORDER BY cm.loja_id, cm.data_operacao
    ");
    
    $movimentacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Encontradas " . count($movimentacoes) . " movimentações de uso de saldo\n\n";
    
    // Agrupar movimentações por loja
    $movimentacoesPorLoja = [];
    foreach ($movimentacoes as $mov) {
        $lojaId = $mov['loja_id'];
        if (!isset($movimentacoesPorLoja[$lojaId])) {
            $movimentacoesPorLoja[$lojaId] = [
                'loja_nome' => $mov['loja_nome'],
                'movimentacoes' => [],
                'total_valor' => 0
            ];
        }
        $movimentacoesPorLoja[$lojaId]['movimentacoes'][] = $mov;
        $movimentacoesPorLoja[$lojaId]['total_valor'] += $mov['valor'];
    }
    
    echo "🏪 Processando " . count($movimentacoesPorLoja) . " lojas:\n\n";
    
    foreach ($movimentacoesPorLoja as $lojaId => $dadosLoja) {
        echo "🔄 Loja: {$dadosLoja['loja_nome']} (ID: $lojaId)\n";
        echo "   💰 Valor total: R$ " . number_format($dadosLoja['total_valor'], 2, ',', '.') . "\n";
        echo "   📝 Movimentações: " . count($dadosLoja['movimentacoes']) . "\n";
        
        // Verificar se já existe um pagamento pendente para esta loja
        $checkStmt = $db->prepare("
            SELECT id, valor_total, status FROM store_balance_payments 
            WHERE loja_id = ? AND status IN ('pendente', 'em_processamento')
            ORDER BY data_criacao DESC LIMIT 1
        ");
        $checkStmt->execute([$lojaId]);
        $existingPayment = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingPayment) {
            echo "   ✨ Já existe pagamento pendente ID {$existingPayment['id']}\n";
            
            // Atualizar valor se necessário
            if (abs($existingPayment['valor_total'] - $dadosLoja['total_valor']) > 0.01) {
                $updateStmt = $db->prepare("
                    UPDATE store_balance_payments 
                    SET valor_total = ?,
                        observacao = CONCAT(COALESCE(observacao, ''), '\n📝 Valor atualizado automaticamente')
                    WHERE id = ?
                ");
                $updateStmt->execute([$dadosLoja['total_valor'], $existingPayment['id']]);
                echo "   📊 Valor atualizado para R$ " . number_format($dadosLoja['total_valor'], 2, ',', '.') . "\n";
            }
            
            $paymentId = $existingPayment['id'];
        } else {
            // Criar novo pagamento
            $insertStmt = $db->prepare("
                INSERT INTO store_balance_payments 
                (loja_id, valor_total, metodo_pagamento, observacao, status, data_criacao)
                VALUES (?, ?, 'reembolso_saldo', ?, 'pendente', NOW())
            ");
            
            $observacao = "🔄 Reembolso de saldo usado pelos clientes\n";
            $observacao .= "📊 Total de " . count($dadosLoja['movimentacoes']) . " transação(ões)\n";
            $observacao .= "💰 Valor: R$ " . number_format($dadosLoja['total_valor'], 2, ',', '.') . "\n";
            $observacao .= "⚡ Criado automaticamente pelo sistema";
            
            $insertStmt->execute([$lojaId, $dadosLoja['total_valor'], $observacao]);
            $paymentId = $db->lastInsertId();
            
            echo "   ✅ Novo pagamento criado ID $paymentId\n";
        }
        
        // Vincular todas as movimentações ao pagamento
        $vinculadas = 0;
        foreach ($dadosLoja['movimentacoes'] as $mov) {
            if (empty($mov['pagamento_id'])) {
                $linkStmt = $db->prepare("
                    UPDATE cashback_movimentacoes 
                    SET pagamento_id = ?
                    WHERE id = ?
                ");
                $linkStmt->execute([$paymentId, $mov['id']]);
                $vinculadas++;
            }
        }
        
        if ($vinculadas > 0) {
            echo "   🔗 Vinculadas $vinculadas movimentações\n";
        }
        
        echo "\n";
    }
    
    echo "🎉 Correção concluída com sucesso!\n";
    echo "💡 Agora atualize a página de 'Pagamentos de Saldo às Lojas' para ver os resultados.\n";
    
} catch (Exception $e) {
    echo "❌ Erro durante a correção: " . $e->getMessage() . "\n";
    echo "📝 Verifique os logs para mais detalhes.\n";
}

echo "</pre>\n";
?>