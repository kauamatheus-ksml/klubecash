<?php
// approve-payment-manual.php
require_once 'config/database.php';
require_once 'controllers/TransactionController.php';

$paymentId = 1103; // Seu pagamento

$db = Database::getConnection();

// 1. Atualizar status do pagamento
$updateStmt = $db->prepare("
    UPDATE pagamentos_comissao 
    SET status = 'aprovado',
        openpix_status = 'COMPLETED',
        observacao_admin = 'PIX OpenPix aprovado manualmente'
    WHERE id = ?
");
$result1 = $updateStmt->execute([$paymentId]);

echo $result1 ? "✅ Pagamento atualizado<br>" : "❌ Erro ao atualizar pagamento<br>";

// 2. Aprovar transações usando TransactionController
$result2 = TransactionController::approvePaymentAutomatically($paymentId, 'PIX OpenPix aprovado manualmente');

echo "<pre>";
print_r($result2);
echo "</pre>";

// 3. Verificar resultado
$stmt = $db->prepare("SELECT status FROM pagamentos_comissao WHERE id = ?");
$stmt->execute([$paymentId]);
$newStatus = $stmt->fetchColumn();

echo "Status final: $newStatus";
?>