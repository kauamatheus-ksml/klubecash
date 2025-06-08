<?php
// approve-payment-manual.php
require_once 'config/database.php';

$paymentId = 1103;
$db = Database::getConnection();

// Buscar transações relacionadas ao pagamento pela correlation_id
$stmt = $db->prepare("
    SELECT t.* FROM transacoes_cashback t 
    WHERE t.loja_id = 34 AND t.status = 'pendente'
    ORDER BY t.id DESC LIMIT 5
");
$stmt->execute();
$transacoes = $stmt->fetchAll();

echo "Transações encontradas:<br>";
foreach ($transacoes as $t) {
    echo "ID: {$t['id']} - Status: {$t['status']} - Valor: {$t['valor_total']}<br>";
}

// Aprovar as transações pendentes da loja
$updateTransactions = $db->prepare("
    UPDATE transacoes_cashback 
    SET status = 'aprovado',
        data_aprovacao = NOW()
    WHERE loja_id = 149 AND status = 'pendente'
");
$result = $updateTransactions->execute();

echo $result ? "✅ Transações aprovadas" : "❌ Erro";

// Liberar cashback para clientes
$cashbackStmt = $db->prepare("
    INSERT INTO saldos_cashback (usuario_id, loja_id, valor, tipo, origem_transacao_id)
    SELECT usuario_id, loja_id, valor_cashback, 'cashback', id 
    FROM transacoes_cashback 
    WHERE loja_id = 34 AND status = 'aprovado' AND valor_cashback > 0
    ON DUPLICATE KEY UPDATE valor = valor
");
$cashbackStmt->execute();

echo "<br>✅ Cashback liberado!";
?>