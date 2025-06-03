<?php
// api/openpix.php

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
// Adicione temporariamente no início do api/openpix.php
echo "API Key: " . OPENPIX_API_KEY;
exit;
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir arquivos básicos
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

// Verificar se outros arquivos existem antes de incluir
if (file_exists(__DIR__ . '/../controllers/AuthController.php')) {
    require_once __DIR__ . '/../controllers/AuthController.php';
}
if (file_exists(__DIR__ . '/../controllers/TransactionController.php')) {
    require_once __DIR__ . '/../controllers/TransactionController.php';
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
        } elseif ($action === 'test') {
            testEndpoint();
        } elseif ($action === 'test_charge') {
            testCreateCharge();
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['status' => false, 'message' => 'Método não permitido']);
        break;
}

function testEndpoint() {
    http_response_code(200);
    echo json_encode(['status' => true, 'message' => 'Endpoint funcionando']);
}

function createPixCharge() {
    // Verificar se AuthController existe
    if (!class_exists('AuthController')) {
        http_response_code(500);
        echo json_encode(['status' => false, 'message' => 'Sistema em manutenção']);
        return;
    }

    if (!AuthController::isAuthenticated() || !AuthController::isStore()) {
        http_response_code(401);
        echo json_encode(['status' => false, 'message' => 'Acesso não autorizado']);
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
            http_response_code(404);
            echo json_encode(['status' => false, 'message' => 'Pagamento não encontrado']);
            return;
        }
        
        // Criar cobrança PIX usando cURL diretamente
        $chargeData = [
            'value' => (int)($payment['valor_total'] * 100),
            'comment' => "Comissão Klube Cash - Pagamento #{$payment['id']}",
            'correlationID' => "payment_{$payment['id']}_" . time(),
            'customer' => [
                'name' => $payment['nome_fantasia'],
                'email' => $payment['email']
            ]
        ];
        
        $response = makeOpenPixRequest('POST', '/charge', $chargeData);
        
        if ($response['success']) {
            $charge = $response['data']['charge'];
            
            // Salvar dados da cobrança PIX
            $updateStmt = $db->prepare("
                UPDATE pagamentos_comissao 
                SET pix_charge_id = ?, pix_qr_code = ?, pix_qr_code_image = ?, metodo_pagamento = 'pix_automatico'
                WHERE id = ?
            ");
            $updateStmt->execute([
                $charge['id'],
                $charge['brCode'],
                $charge['qrCodeImage'],
                $payment['id']
            ]);
            
            echo json_encode([
                'status' => true,
                'data' => [
                    'charge_id' => $charge['id'],
                    'qr_code' => $charge['brCode'],
                    'qr_code_image' => $charge['qrCodeImage']
                ]
            ]);
        } else {
            echo json_encode(['status' => false, 'message' => $response['message']]);
        }
        
    } catch (Exception $e) {
        error_log('Erro ao criar cobrança PIX: ' . $e->getMessage());
        echo json_encode(['status' => false, 'message' => 'Erro interno do servidor']);
    }
}

function handleWebhook() {
    // Log para debug
    $input_raw = file_get_contents('php://input');
    error_log("Webhook OpenPix recebido: " . $input_raw);
    
    // SEMPRE retornar 200 para o OpenPix
    http_response_code(200);
    
    $input = json_decode($input_raw, true);
    
    // Se for apenas teste do OpenPix
    if (!$input || !isset($input['charge'])) {
        echo json_encode(['status' => 'ok', 'message' => 'Webhook recebido']);
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
                // Verificar se TransactionController existe
                if (class_exists('TransactionController') && 
                    method_exists('TransactionController', 'approvePaymentAutomatically')) {
                    
                    $result = TransactionController::approvePaymentAutomatically(
                        $payment['id'], 
                        'Pagamento PIX confirmado automaticamente'
                    );
                    
                    if ($result['status']) {
                        error_log("Pagamento PIX aprovado automaticamente: {$payment['id']}");
                    } else {
                        error_log("Erro ao aprovar pagamento PIX: {$result['message']}");
                    }
                } else {
                    // Fallback: aprovar diretamente no banco
                    $updateStmt = $db->prepare("
                        UPDATE pagamentos_comissao 
                        SET status = 'aprovado', data_aprovacao = NOW(), pix_paid_at = NOW() 
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$payment['id']]);
                    error_log("Pagamento PIX aprovado via fallback: {$payment['id']}");
                }
            }
        }
        
        echo json_encode(['status' => 'ok', 'message' => 'Webhook processado']);
        
    } catch (Exception $e) {
        error_log('Erro no webhook OpenPix: ' . $e->getMessage());
        echo json_encode(['status' => 'ok', 'message' => 'Webhook recebido com erro']);
    }
}

function checkChargeStatus() {
    $chargeId = $_GET['charge_id'] ?? '';
    
    if (empty($chargeId)) {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'charge_id obrigatório']);
        return;
    }
    
    try {
        $response = makeOpenPixRequest('GET', "/charge/{$chargeId}");
        echo json_encode($response);
        
    } catch (Exception $e) {
        error_log('Erro ao verificar status PIX: ' . $e->getMessage());
        echo json_encode(['status' => false, 'message' => 'Erro interno do servidor']);
    }
}

function makeOpenPixRequest($method, $endpoint, $data = null) {
    $baseUrl = 'https://api.openpix.com.br/api/v1';
    $url = $baseUrl . $endpoint;
    
    // DEBUG: Log da API key
    error_log("API Key sendo usada: " . OPENPIX_API_KEY);

    $headers = [
        'Authorization: ' . OPENPIX_API_KEY,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    if ($data && in_array($method, ['POST', 'PUT'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'message' => "Erro cURL: {$error}"];
    }
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return ['success' => true, 'data' => json_decode($response, true)];
    } else {
        return ['success' => false, 'message' => "Erro HTTP {$httpCode}", 'response' => $response];
    }
}
function testCreateCharge() {
    $chargeData = [
        'value' => 100, // R$ 1,00
        'comment' => "Teste Klube Cash",
        'correlationID' => "test_" . time(),
        'customer' => [
            'name' => "Teste Loja",
            'email' => "teste@klubecash.com"
        ]
    ];
    
    $response = makeOpenPixRequest('POST', '/charge', $chargeData);
    echo json_encode($response);
}
?>