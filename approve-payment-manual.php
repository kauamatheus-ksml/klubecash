<?php
// approve-payment-manual.php
require_once 'config/database.php';
$db = Database::getConnection();

$cashback = 0.25;
$usuarioId = 9;
$lojaId = 34;

// Liberar cashback
$stmt = $db->prepare("
    INSERT INTO cashback_saldos (usuario_id, loja_id, valor, tipo, origem_transacao_id)
    VALUES (?, ?, ?, 'cashback', 149)
    ON DUPLICATE KEY UPDATE valor = valor + VALUES(valor)
");
$result = $stmt->execute([$usuarioId, $lojaId, $cashback]);

echo $result ? "✅ Cashback de R$ 0,25 liberado!" : "❌ Erro no cashback";
?>