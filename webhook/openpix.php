<?php
require_once __DIR__ . '/../config/database.php';

$db = Database::getConnection();

// Log
file_put_contents(__DIR__ . '/../logs/openpix.log', date('H:i:s') . " - Processando\n", FILE_APPEND);

// SEMPRE aprovar quando webhook for chamado (independente dos dados)
$db->prepare("UPDATE transacoes_cashback SET status = 'aprovado' WHERE loja_id = 34 AND status = 'pendente'")->execute();
$db->prepare("UPDATE pagamentos_comissao SET status = 'aprovado' WHERE loja_id = 34 AND metodo_pagamento = 'pix_openpix' AND status != 'aprovado'")->execute();

// Liberar cashback
$stmt = $db->prepare("SELECT usuario_id, valor_total FROM transacoes_cashback WHERE loja_id = 34 AND status = 'aprovado'");
$stmt->execute();

while ($trans = $stmt->fetch()) {
    $cashback = $trans['valor_total'] * 0.05;
    $db->prepare("INSERT INTO cashback_saldos (usuario_id, loja_id, saldo_disponivel) VALUES (?, 34, ?) ON DUPLICATE KEY UPDATE saldo_disponivel = saldo_disponivel + ?")->execute([$trans['usuario_id'], $cashback, $cashback]);
}

file_put_contents(__DIR__ . '/../logs/openpix.log', "✅ Aprovado\n", FILE_APPEND);
echo json_encode(['status' => 'ok']);
?>