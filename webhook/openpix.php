<?php
require_once __DIR__ . '/../config/database.php';
$input = json_decode(file_get_contents('php://input'), true);

if ($input && isset($input['charge']) && $input['charge']['status'] === 'COMPLETED') {
    $db = Database::getConnection();
    
    // Aprovar TODAS transações pendentes da loja 34
    $db->prepare("UPDATE transacoes_cashback SET status = 'aprovado' WHERE loja_id = 34 AND status = 'pendente'")->execute();
    
    // Liberar cashback para todas aprovadas
    $stmt = $db->prepare("SELECT id, usuario_id, valor_total FROM transacoes_cashback WHERE loja_id = 34 AND status = 'aprovado'");
    $stmt->execute();
    
    while ($trans = $stmt->fetch()) {
        $cashback = $trans['valor_total'] * 0.05;
        $db->prepare("INSERT INTO cashback_saldos (usuario_id, loja_id, saldo_disponivel) VALUES (?, 34, ?) ON DUPLICATE KEY UPDATE saldo_disponivel = saldo_disponivel + ?")->execute([$trans['usuario_id'], $cashback, $cashback]);
    }
    
    // Aprovar todos pagamentos OpenPix pendentes
    $db->prepare("UPDATE pagamentos_comissao SET status = 'aprovado' WHERE loja_id = 34 AND metodo_pagamento = 'pix_openpix' AND status != 'aprovado'")->execute();
}

echo json_encode(['status' => 'ok']);
?>