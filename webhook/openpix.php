<?php
require_once __DIR__ . '/../config/database.php';
$input = json_decode(file_get_contents('php://input'), true);

if ($input && isset($input['charge']) && $input['charge']['status'] === 'COMPLETED') {
    $correlationID = $input['charge']['correlationID'];
    
    if (preg_match('/payment_(\d+)_/', $correlationID, $matches)) {
        $paymentId = $matches[1];
        $db = Database::getConnection();
        
        // Buscar pagamento e transações
        $stmt = $db->prepare("SELECT loja_id FROM pagamentos_comissao WHERE id = ?");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();
        
        if ($payment) {
            // Aprovar pagamento
            $db->prepare("UPDATE pagamentos_comissao SET status = 'aprovado' WHERE id = ?")->execute([$paymentId]);
            
            // Aprovar transações e liberar cashback
            $db->prepare("UPDATE transacoes_cashback SET status = 'aprovado' WHERE loja_id = ? AND status = 'pendente'")->execute([$payment['loja_id']]);
            
            $trans = $db->prepare("SELECT usuario_id, valor_total FROM transacoes_cashback WHERE loja_id = ? AND status = 'aprovado'");
            $trans->execute([$payment['loja_id']]);
            
            while ($t = $trans->fetch()) {
                $cashback = $t['valor_total'] * 0.05;
                $db->prepare("INSERT INTO cashback_saldos (usuario_id, loja_id, saldo_disponivel) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE saldo_disponivel = saldo_disponivel + ?")->execute([$t['usuario_id'], $payment['loja_id'], $cashback, $cashback]);
            }
        }
    }
}

echo json_encode(['status' => 'ok']);
?>