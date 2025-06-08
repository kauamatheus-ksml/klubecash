<?php
// test-openpix-flow.php
require_once 'config/database.php';

$db = Database::getConnection();

echo "<h2>🧪 Teste Completo OpenPix</h2>";

// 1. Criar transação pendente
$db->prepare("
    INSERT INTO transacoes_cashback (usuario_id, loja_id, valor_total, status, codigo_transacao)
    VALUES (9, 34, 5.00, 'pendente', 'TEST_' . UNIX_TIMESTAMP())
")->execute();
$transId = $db->lastInsertId();
echo "✅ Transação criada: ID $transId<br>";

// 2. Criar pagamento
$db->prepare("
    INSERT INTO pagamentos_comissao (loja_id, valor_total, metodo_pagamento, status)
    VALUES (34, 0.50, 'pix_openpix', 'openpix_aguardando')
")->execute();
$paymentId = $db->lastInsertId();
echo "✅ Pagamento criado: ID $paymentId<br>";

// 3. Simular webhook OpenPix (pagamento aprovado)
$db->beginTransaction();

$db->prepare("UPDATE pagamentos_comissao SET status = 'aprovado' WHERE id = ?")->execute([$paymentId]);
echo "✅ Pagamento aprovado<br>";

$db->prepare("UPDATE transacoes_cashback SET status = 'aprovado' WHERE id = ?")->execute([$transId]);
echo "✅ Transação aprovada<br>";

$cashback = 5.00 * 0.05; // R$ 0,25
$db->prepare("
    INSERT INTO cashback_saldos (usuario_id, loja_id, saldo_disponivel) 
    VALUES (9, 34, ?)
    ON DUPLICATE KEY UPDATE saldo_disponivel = saldo_disponivel + ?
")->execute([$cashback, $cashback]);
echo "✅ Cashback R$ " . number_format($cashback, 2) . " liberado<br>";

$db->commit();

// 4. Verificar resultado
$saldo = $db->prepare("SELECT saldo_disponivel FROM cashback_saldos WHERE usuario_id = 9 AND loja_id = 34");
$saldo->execute();
$total = $saldo->fetchColumn();

echo "<br><strong>💰 Saldo final: R$ " . number_format($total, 2) . "</strong>";
?>