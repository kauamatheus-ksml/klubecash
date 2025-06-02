<?php
// api/openpix.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/TransactionController.php';
require_once __DIR__ . '/../utils/OpenPixClient.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'POST':
        if ($action === 'create_charge') {
            createPixCharge();
        } elseif ($action === 'webhook') {
            handleWebhook();
        }
        break;
    case 'GET':
        if ($action === 'status') {
            checkChargeStatus();
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['status' => false, 'message' => 'Método não permitido']);
        break;
}

function createPixCharge() {
    if (!AuthController::isAuthenticated() || !AuthController::isStore()) {
        http_response_code(401);
        echo json_encode(['status' => false, 'message' => 'Acesso não autorizado']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['payment_id']) || !isset($input['transaction_ids'])) {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'Dados obrigatórios não fornecidos']);
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
            http_response_code(404);
            echo json_encode(['status' => false, 'message' => 'Pagamento não encontrado']);
            return;
        }
        
        // Criar cobrança PIX via OpenPix
        $openPix = new OpenPixClient();
        $chargeData = [
            'value' => (int)($payment['valor_total'] * 100), // Valor em centavos
            'comment' => "Comissão Klube Cash - Pagamento #{$payment['id']}",
            'correlationID' => "payment_{$payment['id']}_" . time(),
            'customer' => [
                'name' => $payment['nome_fantasia'],
                'email' => $payment['email']
            ]
        ];
        
        $charge = $openPix->createCharge($chargeData);
        
        if ($charge['status']) {
            // Salvar dados da cobrança PIX
            $updateStmt = $db->prepare("
                UPDATE pagamentos_comissao 
                SET pix_charge_id = ?, pix_qr_code = ?, pix_qr_code_image = ?, metodo_pagamento = 'pix_automatico'
                WHERE id = ?
            ");
            $updateStmt->execute([
                $charge['data']['charge']['id'],
                $charge['data']['charge']['brCode'],
                $charge['data']['charge']['qrCodeImage'],
                $payment['id']
            ]);
            
            echo json_encode([
                'status' => true,
                'data' => [
                    'charge_id' => $charge['data']['charge']['id'],
                    'qr_code' => $charge['data']['charge']['brCode'],
                    'qr_code_image' => $charge['data']['charge']['qrCodeImage'],
                    'expires_at' => $charge['data']['charge']['expiresIn']
                ]
            ]);
        } else {
            echo json_encode(['status' => false, 'message' => $charge['message']]);
        }
        
    } catch (Exception $e) {
        error_log('Erro ao criar cobrança PIX: ' . $e->getMessage());
        echo json_encode(['status' => false, 'message' => 'Erro interno do servidor']);
    }
}

function handleWebhook() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['charge'])) {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'Dados inválidos']);
        return;
    }
    
    try {
        $charge = $input['charge'];
        $chargeId = $charge['id'];
        $status = $charge['status'];
        
        if ($status === 'COMPLETED') {
            $db = Database::getConnection();
            
            // Buscar pagamento pelo charge_id
            $stmt = $db->prepare("SELECT * FROM pagamentos_comissao WHERE pix_charge_id = ?");
            $stmt->execute([$chargeId]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($payment && $payment['status'] === 'pendente') {
                // Aprovar pagamento automaticamente
                $result = TransactionController::approvePaymentAutomatically($payment['id'], 'Pagamento PIX confirmado automaticamente');
                
                if ($result['status']) {
                    error_log("Pagamento PIX aprovado automaticamente: {$payment['id']}");
                } else {
                    error_log("Erro ao aprovar pagamento PIX: {$result['message']}");
                }
            }
        }
        
        echo json_encode(['status' => true]);
        
    } catch (Exception $e) {
        error_log('Erro no webhook OpenPix: ' . $e->getMessage());
        echo json_encode(['status' => false, 'message' => 'Erro interno']);
    }
}

function checkChargeStatus() {
    if (!AuthController::isAuthenticated() || !AuthController::isStore()) {
        http_response_code(401);
        echo json_encode(['status' => false, 'message' => 'Acesso não autorizado']);
        return;
    }
    
    $chargeId = $_GET['charge_id'] ?? '';
    
    if (empty($chargeId)) {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'ID da cobrança não fornecido']);
        return;
    }
    
    try {
        $openPix = new OpenPixClient();
        $status = $openPix->getChargeStatus($chargeId);
        
        echo json_encode($status);
        
    } catch (Exception $e) {
        error_log('Erro ao verificar status PIX: ' . $e->getMessage());
        echo json_encode(['status' => false, 'message' => 'Erro interno do servidor']);
    }
}
?>