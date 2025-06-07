<?php
/**
 * Webhook OpenPix para Klube Cash
 * Recebe notificações de pagamentos da OpenPix
 */

// Headers de resposta para OpenPix
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: https://api.openpix.com.br');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Log de entrada
$inputRaw = file_get_contents('php://input');
$requestMethod = $_SERVER['REQUEST_METHOD'];
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

error_log("Webhook OpenPix recebido - Method: {$requestMethod}, User-Agent: {$userAgent}");

// Sempre responder 200 para OpenPix (obrigatório)
http_response_code(200);

// Incluir arquivos necessários
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../utils/OpenPixClient.php';

// Verificar se outros controladores existem
if (file_exists(__DIR__ . '/../controllers/TransactionController.php')) {
    require_once __DIR__ . '/../controllers/TransactionController.php';
}

// Responder apenas a métodos POST
if ($requestMethod !== 'POST') {
    echo json_encode(['status' => 'ok', 'message' => 'Webhook deve usar POST']);
    exit;
}

// Log do payload recebido
if (LOG_WEBHOOK_CALLS) {
    error_log("Webhook OpenPix payload: " . $inputRaw);
}

// Decodificar JSON
$input = json_decode($inputRaw, true);

// Se não há dados ou é apenas teste
if (!$input) {
    echo json_encode(['status' => 'ok', 'message' => 'Webhook recebido - dados vazios']);
    exit;
}

try {
    // Processar webhook
    $openPix = new OpenPixClient();
    
    // Validar webhook
    if (!$openPix->validateWebhook($input)) {
        error_log("Webhook OpenPix inválido: " . $inputRaw);
        echo json_encode(['status' => 'ok', 'message' => 'Webhook inválido']);
        exit;
    }
    
    // Processar evento
    $event = $openPix->processWebhookEvent($input);
    
    if ($event['status']) {
        processPaymentConfirmation($event);
    }
    
    echo json_encode(['status' => 'ok', 'message' => 'Webhook processado com sucesso']);
    
} catch (Exception $e) {
    error_log('Erro no webhook OpenPix: ' . $e->getMessage());
    echo json_encode(['status' => 'ok', 'message' => 'Webhook recebido com erro']);
}

/**
 * Processar confirmação de pagamento
 */
function processPaymentConfirmation($event) {
    if ($event['charge_status'] !== 'COMPLETED' && $event['charge_status'] !== 'CONFIRMED') {
        error_log("Status de cobrança não é COMPLETED/CONFIRMED: {$event['charge_status']}");
        return;
    }
    
    try {
        $db = Database::getConnection();
        
        // Buscar pagamento pelo correlation_id ou charge_id
        $stmt = $db->prepare("
            SELECT * FROM pagamentos_comissao 
            WHERE (pix_correlation_id = ? OR pix_charge_id = ?) 
            AND status = 'pendente'
        ");
        $stmt->execute([$event['charge_id'], $event['charge_id']]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            error_log("Pagamento não encontrado para charge_id: {$event['charge_id']}");
            return;
        }
        
        error_log("Processando confirmação de pagamento PIX: {$payment['id']}");
        
        // Usar TransactionController se disponível
        if (class_exists('TransactionController') && 
            method_exists('TransactionController', 'approvePaymentAutomatically')) {
            
            $result = TransactionController::approvePaymentAutomatically(
                $payment['id'], 
                'Pagamento PIX confirmado automaticamente via webhook OpenPix'
            );
            
            if ($result['status']) {
                error_log("Pagamento PIX aprovado automaticamente: {$payment['id']}");
                
                // Atualizar dados específicos do PIX
                updatePixPaymentData($payment['id'], $event);
            } else {
                error_log("Erro ao aprovar pagamento PIX: {$result['message']}");
            }
        } else {
            // Fallback: aprovar diretamente no banco
            $updateStmt = $db->prepare("
                UPDATE pagamentos_comissao 
                SET 
                    status = 'aprovado', 
                    data_aprovacao = NOW(), 
                    pix_paid_at = ?,
                    observacoes = 'Pagamento PIX confirmado automaticamente via webhook OpenPix'
                WHERE id = ?
            ");
            
            $paidAt = $event['paid_at'] ?? date('Y-m-d H:i:s');
            $updateStmt->execute([$paidAt, $payment['id']]);
            
            error_log("Pagamento PIX aprovado via fallback: {$payment['id']}");
            
            // Atualizar dados específicos do PIX
            updatePixPaymentData($payment['id'], $event);
        }
        
    } catch (Exception $e) {
        error_log('Erro ao processar confirmação de pagamento PIX: ' . $e->getMessage());
    }
}

/**
 * Atualizar dados específicos do PIX
 */
function updatePixPaymentData($paymentId, $event) {
    try {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            UPDATE pagamentos_comissao 
            SET 
                pix_transaction_id = COALESCE(?, pix_transaction_id),
                pix_paid_at = COALESCE(?, pix_paid_at, NOW()),
                pix_webhook_received_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $event['transaction_id'],
            $event['paid_at'],
            $paymentId
        ]);
        
        error_log("Dados PIX atualizados para pagamento: {$paymentId}");
        
    } catch (Exception $e) {
        error_log('Erro ao atualizar dados PIX: ' . $e->getMessage());
    }
}

/**
 * Enviar notificação de pagamento confirmado
 */
function sendPaymentConfirmationNotification($paymentId) {
    try {
        // Implementar envio de email/notificação
        // Este é um placeholder para futura implementação
        error_log("Notificação de pagamento confirmado deveria ser enviada para: {$paymentId}");
        
    } catch (Exception $e) {
        error_log('Erro ao enviar notificação: ' . $e->getMessage());
    }
}
?>