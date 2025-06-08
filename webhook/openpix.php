<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

$input = json_decode(file_get_contents('php://input'), true);

if ($input && isset($input['charge']) && $input['charge']['status'] === 'COMPLETED') {
    $correlationID = $input['charge']['correlationID'];
    
    if (preg_match('/payment_(\d+)_/', $correlationID, $matches)) {
        $paymentId = $matches[1];
        
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM pagamentos_comissao WHERE id = ?");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();
        
        if ($payment) {
            $db->beginTransaction();
            
            // 1. Aprovar pagamento
            $db->prepare("UPDATE pagamentos_comissao SET status = 'aprovado' WHERE id = ?")->execute([$paymentId]);
            
            // 2. Aprovar transações da loja
            $db->prepare("UPDATE transacoes_cashback SET status = 'aprovado' WHERE loja_id = ? AND status = 'pendente'")->execute([$payment['loja_id']]);
            
            // 3. Liberar cashback - buscar transações uma por uma
            $transStmt = $db->prepare("SELECT usuario_id, valor_total FROM transacoes_cashback WHERE loja_id = ? AND status = 'aprovado'");
            $transStmt->execute([$payment['loja_id']]);
            
            while ($trans = $transStmt->fetch()) {
                $cashback = $trans['valor_total'] * 0.05;
                $db->prepare("INSERT INTO cashback_saldos (usuario_id, loja_id, saldo_disponivel) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE saldo_disponivel = saldo_disponivel + ?")->execute([$trans['usuario_id'], $payment['loja_id'], $cashback, $cashback]);
            }
            
            $db->commit();
        }
    }
}

echo json_encode(['status' => 'ok']);
?>