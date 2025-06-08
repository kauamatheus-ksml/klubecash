<?php
// webhook/openpix.php
header('Content-Type: application/json');
$logFile = __DIR__ . '/../logs/openpix-webhook.log';

// Log da requisição
$input_raw = file_get_contents('php://input');
$logData = ['timestamp' => date('Y-m-d H:i:s'), 'data' => $input_raw];
file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/TransactionController.php';

$input = json_decode($input_raw, true);

if ($input && isset($input['charge']) && $input['charge']['status'] === 'COMPLETED') {
    $correlationID = $input['charge']['correlationID'];
    
    // Extrair payment_id
    if (preg_match('/payment_(\d+)_/', $correlationID, $matches)) {
        $paymentId = $matches[1];
        
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM pagamentos_comissao WHERE id = ?");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();
        
        if ($payment && $payment['status'] === 'openpix_aguardando') {
            // Aprovar pagamento
            $updateStmt = $db->prepare("UPDATE pagamentos_comissao SET status = 'aprovado' WHERE id = ?");
            $updateStmt->execute([$paymentId]);
            
            // Aprovar transações
            $result = TransactionController::approvePaymentAutomatically($paymentId, 'Aprovado via OpenPix');
            
            file_put_contents($logFile, "SUCCESS: Payment $paymentId approved\n", FILE_APPEND);
        }
    }
}

echo json_encode(['status' => 'ok']);
?>