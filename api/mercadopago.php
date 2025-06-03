<?php
// api/mercadopago.php

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir arquivos necessários
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../utils/MercadoPagoClient.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Log para debug
error_log("MP API Called: $method $action");

try {
    switch ($method) {
        case 'POST':
            if ($action === 'create_payment') {
                createPixPayment();
            } else {
                http_response_code(400);
                echo json_encode(['status' => false, 'message' => 'Ação inválida']);
            }
            break;
        case 'GET':
            if ($action === 'status') {
                checkPaymentStatus();
            } elseif ($action === 'test') {
                testConnection();
            } else {
                http_response_code(400);
                echo json_encode(['status' => false, 'message' => 'Ação inválida']);
            }
            break;
        default:
            http_response_code(405);
            echo json_encode(['status' => false, 'message' => 'Método não permitido']);
            break;
    }
} catch (Exception $e) {
    error_log("MP API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Erro interno do servidor']);
}

function testConnection() {
    echo json_encode([
        'status' => true,
        'message' => 'API funcionando',
        'access_token' => substr(MP_ACCESS_TOKEN, 0, 20) . '...',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

function createPixPayment() {
    // Verificar autenticação
    session_start();
    if (!AuthController::isAuthenticated() || !AuthController::isStore()) {
        http_response_code(401);
        echo json_encode(['status' => false, 'message' => 'Não autorizado']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    error_log("MP Create Payment Input: " . json_encode($input));
    
    if (!$input || !isset($input['payment_id'])) {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'payment_id obrigatório']);
        return;
    }

    try {
        $db = Database::getConnection();
        
        // Buscar dados do pagamento
        $stmt = $db->prepare("
            SELECT p.*, l.nome_fantasia, l.email 
            FROM pagamentos_comissao p
            JOIN lojas l ON p.loja_id = l.id 
            WHERE p.id = ? AND p.status = 'pendente'
        ");
        $stmt->execute([$input['payment_id']]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            echo json_encode(['status' => false, 'message' => 'Pagamento não encontrado ou já processado']);
            return;
        }
        
        // Verificar se valor é válido
        if ($payment['valor_total'] <= 0) {
            echo json_encode(['status' => false, 'message' => 'Valor do pagamento inválido']);
            return;
        }
        
        // Preparar dados para Mercado Pago
        $mpClient = new MercadoPagoClient();
        $paymentData = [
            'amount' => $payment['valor_total'],
            'payer_email' => $payment['email'],
            'payer_name' => 'Loja',
            'payer_lastname' => $payment['nome_fantasia'],
            'description' => "Comissão Klube Cash - Pagamento #{$payment['id']}",
            'external_reference' => "payment_{$payment['id']}",
            'payment_id' => $payment['id'],
            'store_id' => $payment['loja_id']
        ];
        
        error_log("MP Payment Data: " . json_encode($paymentData));
        
        $response = $mpClient->createPixPayment($paymentData);
        
        error_log("MP Response: " . json_encode($response));
        
        if ($response['status']) {
            $mpPayment = $response['data'];
            
            // Verificar se recebemos os dados necessários
            if (!isset($mpPayment['id'])) {
                echo json_encode(['status' => false, 'message' => 'Resposta inválida do Mercado Pago: ID não encontrado']);
                return;
            }
            
            // Extrair dados do PIX
            $qrCode = '';
            $qrCodeBase64 = '';
            
            if (isset($mpPayment['point_of_interaction']['transaction_data'])) {
                $transactionData = $mpPayment['point_of_interaction']['transaction_data'];
                $qrCode = $transactionData['qr_code'] ?? '';
                $qrCodeBase64 = $transactionData['qr_code_base64'] ?? '';
            }
            
            if (empty($qrCode) && empty($qrCodeBase64)) {
                echo json_encode(['status' => false, 'message' => 'QR Code não foi gerado pelo Mercado Pago']);
                return;
            }
            
            // Salvar dados do PIX no banco
            $updateStmt = $db->prepare("
                UPDATE pagamentos_comissao 
                SET mp_payment_id = ?, mp_qr_code = ?, mp_qr_code_base64 = ?, 
                    metodo_pagamento = 'pix_mercadopago', status = 'pix_aguardando'
                WHERE id = ?
            ");
            
            $updateResult = $updateStmt->execute([
                $mpPayment['id'],
                $qrCode,
                $qrCodeBase64,
                $payment['id']
            ]);
            
            if (!$updateResult) {
                echo json_encode(['status' => false, 'message' => 'Erro ao salvar dados no banco']);
                return;
            }
            
            echo json_encode([
                'status' => true,
                'data' => [
                    'mp_payment_id' => $mpPayment['id'],
                    'qr_code' => $qrCode,
                    'qr_code_base64' => $qrCodeBase64,
                    'status' => $mpPayment['status']
                ]
            ]);
        } else {
            echo json_encode([
                'status' => false, 
                'message' => 'Erro do Mercado Pago: ' . $response['message'],
                'details' => $response
            ]);
        }
        
    } catch (Exception $e) {
        error_log('Erro ao criar pagamento PIX: ' . $e->getMessage());
        echo json_encode(['status' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
    }
}

function checkPaymentStatus() {
    $mpPaymentId = $_GET['mp_payment_id'] ?? '';
    
    if (empty($mpPaymentId)) {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'mp_payment_id obrigatório']);
        return;
    }
    
    try {
        $mpClient = new MercadoPagoClient();
        $response = $mpClient->getPaymentStatus($mpPaymentId);
        echo json_encode($response);
        
    } catch (Exception $e) {
        error_log('Erro ao verificar status: ' . $e->getMessage());
        echo json_encode(['status' => false, 'message' => 'Erro interno do servidor']);
    }
}
?>