<?php
// approve-payment-manual.php
require_once 'config/database.php';
require_once 'controllers/TransactionController.php';

$paymentId = 1103; // Mude para ID do seu pagamento

$result = TransactionController::approvePaymentAutomatically($paymentId, 'Aprovação manual - PIX OpenPix pago');

echo "<pre>";
print_r($result);
echo "</pre>";
?>