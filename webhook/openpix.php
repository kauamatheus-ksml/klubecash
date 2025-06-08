<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);

if ($input && isset($input['charge']) && $input['charge']['status'] === 'COMPLETED') {
    $correlationID = $input['charge']['correlationID'];
    
    if (preg_match('/payment_(\d+)_/', $correlationID, $matches)) {
        $paymentId = $matches[1];
        
        $db = Database::getConnection();
        
        // Buscar quais transações estão vinculadas a este pagamento
        $transStmt = $db->prepare("
            SELECT t.id, t.usuario_id, t.valor_total 
            FROM transacoes_cashback t
            JOIN pagamentos_comissao p ON t.loja_id = p.loja_id
            WHERE p.id = ? AND t.status = 'pendente'
        ");
        $transStmt->execute([$paymentId]);
        $transacoes = $transStmt->fetchAll();
        
        if ($transacoes) {
            $db->beginTransaction();
            
            // Aprovar pagamento
            $db->prepare("UPDATE pagamentos_comissao SET status = 'aprovado' WHERE id = ?")->execute([$paymentId]);
            
            // Aprovar cada transação e liberar cashback
            foreach ($transacoes as $trans) {
                // Aprovar transação
                $db->prepare("UPDATE transacoes_cashback SET status = 'aprovado' WHERE id = ?")->execute([$trans['id']]);
                
                // Liberar cashback
                $cashback = $trans['valor_total'] * 0.05;
                $db->prepare("
                    INSERT INTO cashback_saldos (usuario_id, loja_id, saldo_disponivel) 
                    VALUES (?, (SELECT loja_id FROM pagamentos_comissao WHERE id = ?), ?)
                    ON DUPLICATE KEY UPDATE saldo_disponivel = saldo_disponivel + ?
                ")->execute([$trans['usuario_id'], $paymentId, $cashback, $cashback]);
            }
            
            $db->commit();
        }
    }
}

echo json_encode(['status' => 'ok']);
?>