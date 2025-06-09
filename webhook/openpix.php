<?php
require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);

if ($input && isset($input['charge']) && $input['charge']['status'] === 'COMPLETED') {
    $correlationID = $input['charge']['correlationID'];
    
    if (preg_match('/payment_(\d+)_/', $correlationID, $matches)) {
        $paymentId = $matches[1];
        $db = Database::getConnection();
        
        // Verificar se já foi processado
        $check = $db->prepare("SELECT status FROM pagamentos_comissao WHERE id = ?");
        $check->execute([$paymentId]);
        $status = $check->fetchColumn();
        
        if ($status !== 'aprovado') {
            // Aprovar apenas transações pendentes da loja deste pagamento
            $payment = $db->prepare("SELECT loja_id FROM pagamentos_comissao WHERE id = ?");
            $payment->execute([$paymentId]);
            $lojaId = $payment->fetchColumn();
            
            if ($lojaId) {
                $db->prepare("UPDATE pagamentos_comissao SET status = 'aprovado' WHERE id = ?")->execute([$paymentId]);
                $db->prepare("UPDATE transacoes_cashback SET status = 'aprovado' WHERE loja_id = ? AND status = 'pendente'")->execute([$lojaId]);
                
                // Liberar cashback apenas para transações SEM cashback já liberado
                $stmt = $db->prepare("
                    SELECT t.usuario_id, t.valor_total, t.id 
                    FROM transacoes_cashback t
                    WHERE t.loja_id = ? AND t.status = 'aprovado' 
                    AND NOT EXISTS (
                        SELECT 1 FROM cashback_saldos cs 
                        WHERE cs.usuario_id = t.usuario_id AND cs.loja_id = t.loja_id 
                        AND cs.origem_transacao_id = t.id
                    )
                ");
                $stmt->execute([$lojaId]);
                
                while ($trans = $stmt->fetch()) {
                    $cashback = $trans['valor_total'] * 0.05;
                    $db->prepare("INSERT INTO cashback_saldos (usuario_id, loja_id, saldo_disponivel, origem_transacao_id) VALUES (?, ?, ?, ?)")->execute([$trans['usuario_id'], $lojaId, $cashback, $trans['id']]);
                }
            }
        }
    }
}

echo json_encode(['status' => 'ok']);
?>