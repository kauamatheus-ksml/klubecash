<?php
require_once __DIR__ . '/../config/database.php';
file_put_contents('/tmp/openpix.log', date('H:i:s') . " - Webhook recebido\n", FILE_APPEND);

$input = json_decode(file_get_contents('php://input'), true);
file_put_contents('/tmp/openpix.log', "Dados: " . print_r($input, true) . "\n", FILE_APPEND);

if ($input && isset($input['charge']) && $input['charge']['status'] === 'COMPLETED') {
    $db = Database::getConnection();
    
    $db->prepare("UPDATE transacoes_cashback SET status = 'aprovado' WHERE loja_id = 34 AND status = 'pendente'")->execute();
    $db->prepare("UPDATE pagamentos_comissao SET status = 'aprovado' WHERE loja_id = 34 AND metodo_pagamento = 'pix_openpix' AND status != 'aprovado'")->execute();
    
    $stmt = $db->prepare("SELECT usuario_id, valor_total FROM transacoes_cashback WHERE loja_id = 34 AND status = 'aprovado'");
    $stmt->execute();
    
    while ($trans = $stmt->fetch()) {
        $cashback = $trans['valor_total'] * 0.05;
        $db->prepare("INSERT INTO cashback_saldos (usuario_id, loja_id, saldo_disponivel) VALUES (?, 34, ?) ON DUPLICATE KEY UPDATE saldo_disponivel = saldo_disponivel + ?")->execute([$trans['usuario_id'], $cashback, $cashback]);
    }
    
    file_put_contents('/tmp/openpix.log', "✅ Processado\n", FILE_APPEND);
}

echo json_encode(['status' => 'ok']);
?>