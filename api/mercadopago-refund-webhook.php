<?php
// api/mercadopago-refund-webhook.php
header('Content-Type: application/json');

// Log para debug
$input_raw = file_get_contents('php://input');
error_log("Refund Webhook MP recebido: " . $input_raw);

// SEMPRE retornar 200 para o Mercado Pago
http_response_code(200);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../utils/MercadoPagoClient.php';

$input = json_decode($input_raw, true);

// Se for apenas teste do MP
if (!$input || !isset($input['data']['id'])) {
    echo json_encode(['status' => 'ok', 'message' => 'Webhook recebido']);
    exit;
}

try {
    $mpRefundId = $input['data']['id'];
    $action = $input['action'] ?? '';
    
    // Verificar se é notificação de devolução
    if (strpos($action, 'refund') !== false) {
        
        $db = Database::getConnection();
        
        // Buscar devolução pelo mp_refund_id
        $stmt = $db->prepare("SELECT * FROM pagamentos_devolucoes WHERE mp_refund_id = ?");
        $stmt->execute([$mpRefundId]);
        $refund = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($refund) {
            // Buscar status atual no Mercado Pago
            $mpClient = new MercadoPagoClient();
            $refundResponse = $mpClient->getRefundStatus($refund['mp_payment_id'], $mpRefundId);
            
            if ($refundResponse['status'] && isset($refundResponse['data']['status'])) {
                $mpStatus = $refundResponse['data']['status'];
                
                // Mapear status do MP para nosso sistema
                $ourStatus = 'processando';
                switch ($mpStatus) {
                    case 'approved':
                        $ourStatus = 'aprovado';
                        break;
                    case 'rejected':
                    case 'cancelled':
                        $ourStatus = 'rejeitado';
                        break;
                    case 'pending':
                        $ourStatus = 'processando';
                        break;
                }
                
                // Atualizar status no banco
                $updateStmt = $db->prepare("
                    UPDATE pagamentos_devolucoes 
                    SET status = ?, dados_mp = ?, data_processamento = NOW() 
                    WHERE mp_refund_id = ?
                ");
                $updateStmt->execute([
                    $ourStatus,
                    json_encode($refundResponse['data']),
                    $mpRefundId
                ]);
                
                error_log("Devolução atualizada via webhook: ID {$refund['id']}, Status: $ourStatus");
                
                // Se foi aprovada, podemos reverter as transações/cashback se necessário
                if ($ourStatus === 'aprovado') {
                    // Implementar lógica de reversão aqui se necessário
                    // Por exemplo, remover cashback já creditado aos clientes
                }
            }
        }
    }
    
    echo json_encode(['status' => 'ok', 'message' => 'Webhook processado']);
    
} catch (Exception $e) {
    error_log('Erro no webhook de devolução MP: ' . $e->getMessage());
    echo json_encode(['status' => 'ok', 'message' => 'Webhook recebido com erro']);
}
?>