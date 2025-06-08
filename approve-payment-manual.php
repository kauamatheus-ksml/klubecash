<?php
// approve-payment-manual.php
require_once 'config/database.php';

$paymentId = 1103; // Seu ID

$db = Database::getConnection();

// Verificar pagamento
$stmt = $db->prepare("SELECT * FROM pagamentos_comissao WHERE id = ?");
$stmt->execute([$paymentId]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h3>Status do Pagamento ID: $paymentId</h3>";
if ($payment) {
    echo "<pre>";
    print_r($payment);
    echo "</pre>";
    
    // Se status for 'openpix_aguardando', aprovar diretamente
    if ($payment['status'] === 'openpix_aguardando') {
        $updateStmt = $db->prepare("
            UPDATE pagamentos_comissao 
            SET status = 'aprovado', 
                observacao_admin = 'Aprovado manualmente - PIX OpenPix pago'
            WHERE id = ?
        ");
        $result = $updateStmt->execute([$paymentId]);
        
        echo $result ? "<p style='color:green'>✅ Pagamento aprovado!</p>" : "<p style='color:red'>❌ Erro ao aprovar</p>";
    } else {
        echo "<p>Status atual: " . $payment['status'] . "</p>";
    }
} else {
    echo "<p style='color:red'>❌ Pagamento não encontrado!</p>";
}

// Listar todos os pagamentos
echo "<h3>Todos os Pagamentos:</h3>";
$all = $db->query("SELECT id, loja_id, valor_total, status, metodo_pagamento FROM pagamentos_comissao ORDER BY id DESC LIMIT 10")->fetchAll();
echo "<pre>";
print_r($all);
echo "</pre>";
?>