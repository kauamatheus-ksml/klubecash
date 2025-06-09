<?php
require_once __DIR__ . '/../config/database.php';
file_put_contents('/tmp/openpix-webhook.log', date('Y-m-d H:i:s') . " - Webhook chamado\n", FILE_APPEND);

$input = json_decode(file_get_contents('php://input'), true);

if ($input && isset($input['charge']) && $input['charge']['status'] === 'COMPLETED') {
    $db = Database::getConnection();
    
    // Aprovar TODAS as transações pendentes da loja 34
    $db->prepare("UPDATE transacoes_cashback SET status = 'aprovado' WHERE loja_id = 34 AND status = 'pendente'")->execute();
    
    // Liberar cashback para cada transação aprovada
    $stmt = $db->prepare("SELECT usuario_id, valor_total FROM transacoes_cashback WHERE loja_id = 34 AND status = 'aprovado'");
    $stmt->execute();
    
    while ($trans = $stmt->fetch()) {
        $cashback = $trans['valor_total'] * 0.05;
        $db->prepare("INSERT INTO cashback_saldos (usuario_id, loja_id, saldo_disponivel) VALUES (?, 34, ?) ON DUPLICATE KEY UPDATE saldo_disponivel = saldo_disponivel + ?")->execute([$trans['usuario_id'], $cashback, $cashback]);
    }
    
    file_put_contents('/tmp/openpix-webhook.log', "Transações aprovadas e cashback liberado\n", FILE_APPEND);
}

echo json_encode(['status' => 'ok']);
?>