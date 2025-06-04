<?php
// api/mercadopago-webhook.php
header('Content-Type: application/json');

// Log para debug - registra tudo que chega do Mercado Pago
$input_raw = file_get_contents('php://input');
$headers = getallheaders();

error_log("=== WEBHOOK MP RECEBIDO ===");
error_log("Timestamp: " . date('Y-m-d H:i:s'));
error_log("Payload: " . $input_raw);
error_log("Headers: " . print_r($headers, true));

// SEMPRE retornar 200 para o Mercado Pago (mesmo se houver erro interno)
// Isso evita que o MP continue tentando reenviar o webhook
http_response_code(200);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/TransactionController.php';

// Verificar se o arquivo MercadoPagoClient existe, senão criar uma versão básica
if (!file_exists(__DIR__ . '/../utils/MercadoPagoClient.php')) {
    error_log("WEBHOOK: MercadoPagoClient não encontrado, usando método alternativo");
    
    // Função alternativa para buscar status do pagamento
    function getPaymentStatusAlternative($mpPaymentId) {
        $url = "https://api.mercadopago.com/v1/payments/{$mpPaymentId}";
        $headers = [
            'Authorization: Bearer ' . MP_ACCESS_TOKEN,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return ['status' => true, 'data' => json_decode($response, true)];
        } else {
            return ['status' => false, 'message' => 'Erro ao consultar MP'];
        }
    }
    
    // Função alternativa para validar webhook
    function validateWebhookAlternative($headers, $body) {
        // Se não tem MercadoPagoClient, fazer validação básica
        if (!defined('MP_WEBHOOK_SECRET') || empty(MP_WEBHOOK_SECRET)) {
            error_log("WEBHOOK: MP_WEBHOOK_SECRET não definido, pulando validação");
            return true; // Aceita se não tem secret configurado
        }
        
        // Normalizar headers
        $normalizedHeaders = array_change_key_case($headers, CASE_LOWER);
        $signature = $normalizedHeaders['x-signature'] ?? '';
        $requestId = $normalizedHeaders['x-request-id'] ?? '';
        
        if (empty($signature) || empty($requestId)) {
            error_log("WEBHOOK: Headers de assinatura ausentes");
            return false;
        }
        
        // Extrair timestamp e hash
        $parts = explode(',', $signature);
        $timestamp = '';
        $hash = '';
        
        foreach ($parts as $part) {
            $keyValue = explode('=', trim($part), 2);
            if (count($keyValue) === 2) {
                $key = trim($keyValue[0]);
                $value = trim($keyValue[1]);
                
                if ($key === 'ts') {
                    $timestamp = $value;
                } elseif ($key === 'v1') {
                    $hash = $value;
                }
            }
        }
        
        if (empty($timestamp) || empty($hash)) {
            error_log("WEBHOOK: Formato de assinatura inválido");
            return false;
        }
        
        // Verificar timestamp (máximo 15 minutos)
        $currentTime = time();
        if (abs($currentTime - (int)$timestamp) > 900) {
            error_log("WEBHOOK: Timestamp muito antigo");
            return false;
        }
        
        // Validar assinatura
        $dataToSign = $requestId . $timestamp . $body;
        $expectedHash = hash_hmac('sha256', $dataToSign, MP_WEBHOOK_SECRET);
        
        return hash_equals($expectedHash, $hash);
    }
} else {
    require_once __DIR__ . '/../utils/MercadoPagoClient.php';
}

// ===== CÓDIGO 3 - VALIDAR WEBHOOK =====
// Validar assinatura do webhook ANTES de processar
if (class_exists('MercadoPagoClient')) {
    try {
        $mpClient = new MercadoPagoClient();
        if (!$mpClient->validateWebhookSignature($headers, $input_raw)) {
            error_log("WEBHOOK: ❌ Assinatura inválida, ignorando webhook");
            echo json_encode(['status' => 'ok', 'message' => 'Assinatura inválida']);
            exit;
        }
        error_log("WEBHOOK: ✅ Assinatura válida");
    } catch (Exception $e) {
        error_log("WEBHOOK: Erro na validação de assinatura: " . $e->getMessage());
        // Continua processamento em caso de erro na validação
    }
} else {
    if (!validateWebhookAlternative($headers, $input_raw)) {
        error_log("WEBHOOK: ❌ Assinatura inválida (método alternativo), ignorando webhook");
        echo json_encode(['status' => 'ok', 'message' => 'Assinatura inválida']);
        exit;
    }
    error_log("WEBHOOK: ✅ Assinatura válida (método alternativo)");
}

$input = json_decode($input_raw, true);

// Validação básica do payload
if (!$input) {
    error_log("WEBHOOK: Payload JSON inválido");
    echo json_encode(['status' => 'ok', 'message' => 'Webhook recebido - JSON inválido']);
    exit;
}

// Se não tem o ID, é apenas um teste do MP
if (!isset($input['data']['id'])) {
    error_log("WEBHOOK: Teste do MP ou payload sem ID");
    echo json_encode(['status' => 'ok', 'message' => 'Webhook recebido - teste']);
    exit;
}

try {
    $mpPaymentId = $input['data']['id'];
    $action = $input['action'] ?? '';
    
    error_log("WEBHOOK: Processando Payment ID: {$mpPaymentId}, Action: {$action}");
    
    // ===== PROCESSAMENTO DE PAGAMENTOS =====
    if ($action === 'payment.updated' || $action === 'payment.created') {
        
        // Buscar status atual no Mercado Pago
        if (class_exists('MercadoPagoClient')) {
            $mpClient = new MercadoPagoClient();
            $paymentResponse = $mpClient->getPaymentStatus($mpPaymentId);
        } else {
            $paymentResponse = getPaymentStatusAlternative($mpPaymentId);
        }
        
        error_log("WEBHOOK: Resposta do MP: " . print_r($paymentResponse, true));
        
        if ($paymentResponse['status'] && isset($paymentResponse['data']['status'])) {
            $mpStatus = $paymentResponse['data']['status'];
            $mpStatusDetail = $paymentResponse['data']['status_detail'] ?? '';
            
            error_log("WEBHOOK: Status do pagamento no MP: {$mpStatus} - {$mpStatusDetail}");
            
            if ($mpStatus === 'approved') {
                $db = Database::getConnection();
                
                // Buscar pagamento na nossa base pelo mp_payment_id
                $stmt = $db->prepare("
                    SELECT p.*, l.nome_fantasia as loja_nome 
                    FROM pagamentos_comissao p
                    LEFT JOIN lojas l ON p.loja_id = l.id
                    WHERE p.mp_payment_id = ?
                ");
                $stmt->execute([$mpPaymentId]);
                $payment = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$payment) {
                    error_log("WEBHOOK: Pagamento não encontrado na base local para MP ID: {$mpPaymentId}");
                    echo json_encode(['status' => 'ok', 'message' => 'Pagamento não encontrado']);
                    exit;
                }
                
                error_log("WEBHOOK: Pagamento encontrado - ID: {$payment['id']}, Status atual: {$payment['status']}");
                
                // Verificar se o pagamento ainda precisa ser processado
                if (in_array($payment['status'], ['pendente', 'pix_aguardando'])) {
                    
                    // Usar o método correto de aprovação automática
                    $result = TransactionController::approvePaymentAutomatically(
                        $payment['id'], 
                        'Pagamento PIX aprovado automaticamente via Mercado Pago - ID MP: ' . $mpPaymentId
                    );
                    
                    if ($result['status']) {
                        error_log("WEBHOOK: ✅ Pagamento aprovado com sucesso - ID: {$payment['id']}");
                        error_log("WEBHOOK: Cashback liberado: R$ " . ($result['data']['cashback_liberado'] ?? '0.00'));
                        error_log("WEBHOOK: Transações aprovadas: " . ($result['data']['transacoes_aprovadas'] ?? '0'));
                        
                        // Atualizar também o status do MP no nosso banco
                        $updateMpStmt = $db->prepare("
                            UPDATE pagamentos_comissao 
                            SET mp_status = 'approved',
                                pix_paid_at = NOW()
                            WHERE id = ?
                        ");
                        $updateMpStmt->execute([$payment['id']]);
                        
                    } else {
                        error_log("WEBHOOK: ❌ Erro ao aprovar pagamento: " . $result['message']);
                    }
                    
                } else {
                    error_log("WEBHOOK: Pagamento já foi processado - Status: {$payment['status']}");
                }
                
            } elseif ($mpStatus === 'rejected' || $mpStatus === 'cancelled') {
                
                // Lidar com pagamentos rejeitados/cancelados
                error_log("WEBHOOK: Pagamento rejeitado/cancelado no MP: {$mpStatus}");
                
                $db = Database::getConnection();
                $updateStmt = $db->prepare("
                    UPDATE pagamentos_comissao 
                    SET mp_status = ?,
                        observacao_admin = CONCAT(COALESCE(observacao_admin, ''), ' - PIX rejeitado/cancelado no MP: {$mpStatus}')
                    WHERE mp_payment_id = ?
                ");
                $updateStmt->execute([$mpStatus, $mpPaymentId]);
                
            } else {
                error_log("WEBHOOK: Status do pagamento não requer ação: {$mpStatus}");
            }
            
        } else {
            error_log("WEBHOOK: Erro ao consultar status no MP: " . ($paymentResponse['message'] ?? 'Erro desconhecido'));
        }
    }
    
    // ===== PROCESSAMENTO DE DEVOLUÇÕES =====
    elseif (strpos($action, 'refund') !== false || $action === 'payment.refunded') {
        
        error_log("WEBHOOK: Processando notificação de devolução - Action: {$action}");
        
        $db = Database::getConnection();
        
        // Buscar devoluções relacionadas a este pagamento
        $stmt = $db->prepare("
            SELECT pd.*, pc.loja_id 
            FROM pagamentos_devolucoes pd
            JOIN pagamentos_comissao pc ON pd.pagamento_id = pc.id
            WHERE pd.mp_payment_id = ?
        ");
        $stmt->execute([$mpPaymentId]);
        $refunds = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($refunds)) {
            error_log("WEBHOOK: Nenhuma devolução encontrada para Payment ID: {$mpPaymentId}");
        } else {
            error_log("WEBHOOK: Encontradas " . count($refunds) . " devoluções para processar");
            
            foreach ($refunds as $refund) {
                if ($refund['mp_refund_id']) {
                    
                    // Consultar status da devolução específica no MP
                    if (class_exists('MercadoPagoClient')) {
                        $mpClient = new MercadoPagoClient();
                        $refundResponse = $mpClient->getRefundStatus($mpPaymentId, $refund['mp_refund_id']);
                    } else {
                        // Implementação alternativa para consultar devolução
                        $url = "https://api.mercadopago.com/v1/payments/{$mpPaymentId}/refunds/{$refund['mp_refund_id']}";
                        $headers_req = [
                            'Authorization: Bearer ' . MP_ACCESS_TOKEN,
                            'Content-Type: application/json'
                        ];
                        
                        $ch = curl_init();
                        curl_setopt_array($ch, [
                            CURLOPT_URL => $url,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_HTTPHEADER => $headers_req,
                            CURLOPT_TIMEOUT => 10
                        ]);
                        
                        $response = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        
                        if ($httpCode === 200) {
                            $refundResponse = ['status' => true, 'data' => json_decode($response, true)];
                        } else {
                            $refundResponse = ['status' => false, 'message' => 'Erro ao consultar devolução'];
                        }
                    }
                    
                    if ($refundResponse['status']) {
                        $mpRefundStatus = $refundResponse['data']['status'];
                        
                        error_log("WEBHOOK: Status da devolução {$refund['mp_refund_id']}: {$mpRefundStatus}");
                        
                        // Mapear status do MP para nosso sistema
                        $ourStatus = 'processando';
                        switch ($mpRefundStatus) {
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
                        
                        // Atualizar status no banco apenas se mudou
                        if ($ourStatus !== $refund['status']) {
                            $updateStmt = $db->prepare("
                                UPDATE pagamentos_devolucoes 
                                SET status = ?, dados_mp = ?, data_processamento = NOW() 
                                WHERE id = ?
                            ");
                            $updateStmt->execute([
                                $ourStatus,
                                json_encode($refundResponse['data']),
                                $refund['id']
                            ]);
                            
                            error_log("WEBHOOK: ✅ Devolução atualizada - ID: {$refund['id']}, Status: {$ourStatus}");
                            
                            // Se foi aprovada, processa reversão do cashback
                            if ($ourStatus === 'aprovado') {
                                try {
                                    // Reverter cashback dos clientes relacionados
                                    $reversalStmt = $db->prepare("
                                        UPDATE transacoes_cashback tc
                                        JOIN pagamentos_comissao_transacoes pct ON tc.id = pct.transacao_id
                                        SET tc.status = 'devolvido'
                                        WHERE pct.pagamento_id = ?
                                    ");
                                    $reversalStmt->execute([$refund['pagamento_id']]);
                                    
                                    error_log("WEBHOOK: Cashback revertido para pagamento ID: {$refund['pagamento_id']}");
                                    
                                } catch (Exception $reversalError) {
                                    error_log("WEBHOOK: Erro ao reverter cashback: " . $reversalError->getMessage());
                                }
                            }
                        }
                        
                    } else {
                        error_log("WEBHOOK: Erro ao consultar devolução no MP: " . $refundResponse['message']);
                    }
                    
                } else {
                    error_log("WEBHOOK: Devolução sem mp_refund_id: {$refund['id']}");
                }
            }
        }
    }
    
    // ===== OUTRAS AÇÕES =====
    else {
        error_log("WEBHOOK: Action não reconhecida ou não requer processamento: {$action}");
    }
    
    echo json_encode(['status' => 'ok', 'message' => 'Webhook processado com sucesso']);
    
} catch (Exception $e) {
    // Log detalhado do erro mas ainda retorna 200 para o MP
    error_log("WEBHOOK: ❌ ERRO CRÍTICO: " . $e->getMessage());
    error_log("WEBHOOK: Stack trace: " . $e->getTraceAsString());
    
    // Tentar salvar o erro no banco para análise posterior
    try {
        $db = Database::getConnection();
        
        // Criar tabela de erros se não existir
        $createTableStmt = $db->prepare("
            CREATE TABLE IF NOT EXISTS webhook_errors (
                id INT AUTO_INCREMENT PRIMARY KEY,
                mp_payment_id VARCHAR(255),
                error_message TEXT,
                payload LONGTEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_payment (mp_payment_id, created_at)
            )
        ");
        $createTableStmt->execute();
        
        $errorStmt = $db->prepare("
            INSERT INTO webhook_errors (mp_payment_id, error_message, payload, created_at) 
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            error_message = VALUES(error_message),
            payload = VALUES(payload),
            created_at = NOW()
        ");
        $errorStmt->execute([
            $mpPaymentId ?? 'unknown',
            $e->getMessage(),
            $input_raw
        ]);
        
        error_log("WEBHOOK: Erro salvo na tabela webhook_errors");
        
    } catch (Exception $dbError) {
        error_log("WEBHOOK: Erro ao salvar erro no banco: " . $dbError->getMessage());
    }
    
    echo json_encode(['status' => 'ok', 'message' => 'Webhook recebido com erro interno']);
}

error_log("=== FIM WEBHOOK MP ===");
?>