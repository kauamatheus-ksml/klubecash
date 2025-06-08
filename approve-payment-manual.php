<?php
// approve-payment-manual.php
require_once 'config/database.php';

$db = Database::getConnection();

// Aprovar transação ID 149
$stmt = $db->prepare("
    UPDATE transacoes_cashback 
    SET status = 'aprovado',
        data_aprovacao = NOW()
    WHERE id = 149
");
$result1 = $stmt->execute();

// Calcular e liberar cashback (5% de R$ 5,00 = R$ 0,25)
$stmt = $db->prepare("
    SELECT usuario_id, loja_id, valor_total * 0.05 as cashback 
    FROM transacoes_cashback 
    WHERE id = 149
");
$stmt->execute();
$trans = $stmt->fetch();

if ($trans) {
    $cashbackStmt = $db->prepare("
        INSERT INTO saldos_cashback (usuario_id, loja_id, valor, tipo, origem_transacao_id)
        VALUES (?, ?, ?, 'cashback', 149)
        ON DUPLICATE KEY UPDATE valor = valor + VALUES(valor)
    ");
    $result2 = $cashbackStmt->execute([$trans['usuario_id'], $trans['loja_id'], $trans['cashback']]);
    
    echo $result1 && $result2 ? "✅ Transação aprovada e cashback de R$ " . number_format($trans['cashback'], 2) . " liberado!" : "❌ Erro";
} else {
    echo "❌ Transação não encontrada";
}
?>