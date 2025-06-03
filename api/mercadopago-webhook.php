<?php
// api/mercadopago-webhook.php

header('Content-Type: application/json');

// Log para debug
$input_raw = file_get_contents('php://input');
error_log("Webhook MP recebido: " . $input_raw);

// SEMPRE retornar 200 para o Mercado Pago
http_response_code(200);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/TransactionController.php';
require_once __DIR__ . '/../utils/MercadoPagoClient.php';

$input = json_decode($input_raw, true);

// Se for apenas teste do MP
if (!$input || !isset($input['data']['id'])) {
    echo json_encode(['status' => 'ok', 'message' => 'Webhook recebido']);
    exit;
}

try {
    $mpPaymentId = $input['data']['id'];
    $action = $input['action'] ?? '';
    
    // Verificar se é notificação de pagamento
    if ($action === 'payment.updated' || $action === 'payment.created') {
        
        // Buscar status atual no Mercado Pago
        $mpClient = new MercadoPagoClient();
        $paymentResponse = $mpClient->getPaymentStatus($mpPaymentId);
        
        if ($paymentResponse['status'] && isset($paymentResponse['data']['status'])) {
            $mpStatus = $paymentResponse['data']['status'];
            
            if ($mpStatus === 'approved') {
                $db = Database::getConnection();
                
                // Buscar pagamento pelo mp_payment_id
                $stmt = $db->prepare("SELECT * FROM pagamentos_comissao WHERE mp_payment_id = ?");
                $stmt->execute([$mpPaymentId]);
                $payment = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($payment && in_array($payment['status'], ['pendente', 'pix_aguardando'])) {
                    // Atualizar status para aprovado
                    $updateStmt = $db->prepare("
                        UPDATE pagamentos_comissao 
                        SET status = 'aprovado', data_aprovacao = NOW(), 
                            observacao_admin = 'Pagamento PIX aprovado automaticamente via Mercado Pago' 
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$payment['id']]);
                    
                    // Processar aprovação usando o sistema existente
                    $result = TransactionController::approvePayment(
                        $payment['id'], 
                        'Pagamento PIX aprovado automaticamente via Mercado Pago'
                    );
                    
                    if ($result['status']) {
                        error_log("Pagamento PIX aprovado automaticamente: {$payment['id']}");
                    } else {
                        error_log("Erro ao aprovar pagamento PIX: {$result['message']}");
                    }
                }
            }
        }
    }
    
    echo json_encode(['status' => 'ok', 'message' => 'Webhook processado']);
    
} catch (Exception $e) {
    error_log('Erro no webhook MP: ' . $e->getMessage());
    echo json_encode(['status' => 'ok', 'message' => 'Webhook recebido com erro']);
}
?>