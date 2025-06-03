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

switch ($method) {
    case 'POST':
        if ($action === 'create_payment') {
            createPixPayment();
        }
        break;
    case 'GET':
        if ($action === 'status') {
            checkPaymentStatus();
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['status' => false, 'message' => 'Método não permitido']);
        break;
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
            echo json_encode(['status' => false, 'message' => 'Pagamento não encontrado']);
            return;
        }
        
        // Preparar dados para Mercado Pago
        $mpClient = new MercadoPagoClient();
        $paymentData = [
            'amount' => $payment['valor_total'],
            'payer_email' => $payment['email'],
            'payer_name' => explode(' ', $payment['nome_fantasia'])[0],
            'payer_lastname' => isset(explode(' ', $payment['nome_fantasia'])[1]) ? 
                               implode(' ', array_slice(explode(' ', $payment['nome_fantasia']), 1)) : 'Loja',
            'description' => "Comissão Klube Cash - Pagamento #{$payment['id']}",
            'external_reference' => "payment_{$payment['id']}",
            'payment_id' => $payment['id'],
            'store_id' => $payment['loja_id']
        ];
        
        $response = $mpClient->createPixPayment($paymentData);
        
        if ($response['status']) {
            $mpPayment = $response['data'];
            
            // Salvar dados do PIX no banco
            $updateStmt = $db->prepare("
                UPDATE pagamentos_comissao 
                SET mp_payment_id = ?, mp_qr_code = ?, mp_qr_code_base64 = ?, 
                    metodo_pagamento = 'pix_mercadopago', status = 'pix_aguardando'
                WHERE id = ?
            ");
            
            $qrCode = $mpPayment['point_of_interaction']['transaction_data']['qr_code'] ?? '';
            $qrCodeBase64 = $mpPayment['point_of_interaction']['transaction_data']['qr_code_base64'] ?? '';
            
            $updateStmt->execute([
                $mpPayment['id'],
                $qrCode,
                $qrCodeBase64,
                $payment['id']
            ]);
            
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
            echo json_encode(['status' => false, 'message' => $response['message']]);
        }
        
    } catch (Exception $e) {
        error_log('Erro ao criar pagamento PIX: ' . $e->getMessage());
        echo json_encode(['status' => false, 'message' => 'Erro interno']);
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