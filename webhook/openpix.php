<?php
require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);

if ($input && isset($input['charge']) && $input['charge']['status'] === 'COMPLETED') {
    $correlationID = $input['charge']['correlationID'];
    
    if (preg_match('/payment_(\d+)_/', $correlationID, $matches)) {
        $paymentId = $matches[1];
        $db = Database::getConnection();
        
        // Buscar pagamento específico
        $stmt = $db->prepare("SELECT * FROM pagamentos_comissao WHERE id = ? AND status IN ('pendente', 'openpix_aguardando')");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();
        
        if ($payment) {
            $db->beginTransaction();
            
            // Aprovar pagamento
            $db->prepare("UPDATE pagamentos_comissao SET status = 'aprovado' WHERE id = ?")->execute([$paymentId]);
            
            // Buscar e aprovar apenas as transações vinculadas a este pagamento
            $transStmt = $db->prepare("
                SELECT id, usuario_id, valor_total 
                FROM transacoes_cashback 
                WHERE loja_id = ? AND status = 'pendente'
                ORDER BY id DESC 
                LIMIT 10
            ");
            $transStmt->execute([$payment['loja_id']]);
            $transactions = $transStmt->fetchAll();
            
            foreach ($transactions as $trans) {
                // Aprovar transação
                $db->prepare("UPDATE transacoes_cashback SET status = 'aprovado' WHERE id = ?")->execute([$trans['id']]);
                
                // Liberar cashback
                $cashback = $trans['valor_total'] * 0.05;
                $db->prepare("
                    INSERT INTO cashback_saldos (usuario_id, loja_id, saldo_disponivel) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE saldo_disponivel = saldo_disponivel + ?
                ")->execute([$trans['usuario_id'], $payment['loja_id'], $cashback, $cashback]);
            }
            
            $db->commit();
        }
    }
}

echo json_encode(['status' => 'ok']);
?>