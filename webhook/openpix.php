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
            $payment = $db->prepare("SELECT loja_id FROM pagamentos_comissao WHERE id = ?");
            $payment->execute([$paymentId]);
            $lojaId = $payment->fetchColumn();
            
            if ($lojaId) {
                // Buscar transações pendentes ANTES de aprovar
                $pendingStmt = $db->prepare("SELECT id, usuario_id, valor_total FROM transacoes_cashback WHERE loja_id = ? AND status = 'pendente'");
                $pendingStmt->execute([$lojaId]);
                $pendingTransactions = $pendingStmt->fetchAll();
                
                // Aprovar pagamento e transações
                $db->prepare("UPDATE pagamentos_comissao SET status = 'aprovado' WHERE id = ?")->execute([$paymentId]);
                $db->prepare("UPDATE transacoes_cashback SET status = 'aprovado' WHERE loja_id = ? AND status = 'pendente'")->execute([$lojaId]);
                
                // Liberar cashback APENAS para as que estavam pendentes
                foreach ($pendingTransactions as $trans) {
                    $cashback = $trans['valor_total'] * 0.05;
                    $db->prepare("INSERT INTO cashback_saldos (usuario_id, loja_id, saldo_disponivel) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE saldo_disponivel = saldo_disponivel + ?")->execute([$trans['usuario_id'], $lojaId, $cashback, $cashback]);
                }
            }
        }
    }
}

echo json_encode(['status' => 'ok']);
?>