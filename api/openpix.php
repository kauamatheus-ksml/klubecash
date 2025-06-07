<?php
/**
 * API OpenPix para Klube Cash
 * Endpoint principal para interações com PIX
 */

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
require_once __DIR__ . '/../utils/OpenPixClient.php';

// Verificar se outros controladores existem
if (file_exists(__DIR__ . '/../controllers/AuthController.php')) {
    require_once __DIR__ . '/../controllers/AuthController.php';
}
if (file_exists(__DIR__ . '/../controllers/TransactionController.php')) {
    require_once __DIR__ . '/../controllers/TransactionController.php';
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'POST':
            if ($action === 'create_charge') {
                createPixCharge();
            } elseif ($action === 'process_payment') {
                processPayment();
            } else {
                throw new Exception('Ação não encontrada');
            }
            break;
            
        case 'GET':
            if ($action === 'status') {
                checkChargeStatus();
            } elseif ($action === 'test') {
                testEndpoint();
            } elseif ($action === 'test_connection') {
                testConnection();
            } elseif ($action === 'account_info') {
                getAccountInfo();
            } else {
                throw new Exception('Ação não encontrada');
            }
            break;
            
        default:
            throw new Exception('Método não permitido');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Teste básico do endpoint
 */
function testEndpoint() {
    http_response_code(200);
    echo json_encode([
        'status' => true,
        'message' => 'API OpenPix funcionando',
        'version' => SYSTEM_VERSION,
        'timestamp' => date('Y-m-d H:i:s'),
        'provider' => 'OpenPix'
    ]);
}

/**
 * Teste de conexão com OpenPix
 */
function testConnection() {
    try {
        $openPix = new OpenPixClient();
        $result = $openPix->testConnection();
        
        http_response_code($result['status'] ? 200 : 400);
        echo json_encode($result);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => false,
            'message' => 'Erro no teste de conexão: ' . $e->getMessage()
        ]);
    }
}

/**
 * Obter informações da conta OpenPix
 */
function getAccountInfo() {
    try {
        $openPix = new OpenPixClient();
        $result = $openPix->getAccountInfo();
        
        if ($result['success']) {
            echo json_encode([
                'status' => true,
                'data' => $result['data']
            ]);
        } else {
            http_response_code(400);
            echo json_encode([
                'status' => false,
                'message' => $result['message']
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => false,
            'message' => 'Erro ao obter informações da conta: ' . $e->getMessage()
        ]);
    }
}

/**
 * Criar cobrança PIX
 */
function createPixCharge() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['payment_id'])) {
        http_response_code(400);
        echo json_encode([
            'status' => false,
            'message' => 'payment_id obrigatório'
        ]);
        return;
    }

    try {
        $db = Database::getConnection();
        
        // Buscar dados do pagamento
        $stmt = $db->prepare("
            SELECT p.*, l.nome_fantasia, l.email, l.telefone, l.cnpj 
            FROM pagamentos_comissao p
            JOIN lojas l ON p.loja_id = l.id 
            WHERE p.id = ? AND p.status = 'pendente'
        ");
        $stmt->execute([$input['payment_id']]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            http_response_code(404);
            echo json_encode([
                'status' => false,
                'message' => 'Pagamento não encontrado ou já processado'
            ]);
            return;
        }
        
        // Preparar dados para OpenPix
        $chargeData = [
            'amount' => (float)$payment['valor_total'],
            'correlation_id' => "payment_{$payment['id']}_" . time(),
            'comment' => "Comissão Klube Cash - Pagamento #{$payment['id']} - {$payment['nome_fantasia']}",
            'customer' => [
                'name' => $payment['nome_fantasia'],
                'email' => $payment['email'],
                'phone' => $payment['telefone'] ?? null,
                'cnpj' => $payment['cnpj'] ?? null
            ],
            'additional_info' => [
                [
                    'key' => 'payment_id',
                    'value' => $payment['id']
                ],
                [
                    'key' => 'store_id',
                    'value' => $payment['loja_id']
                ],
                [
                    'key' => 'system',
                    'value' => 'Klube Cash'
                ]
            ]
        ];
        
        // Criar cobrança na OpenPix
        $openPix = new OpenPixClient();
        $result = $openPix->createCharge($chargeData);
        
        if ($result['status']) {
            // Salvar dados do PIX no banco
            $updateStmt = $db->prepare("
                UPDATE pagamentos_comissao 
                SET 
                    pix_charge_id = ?,
                    pix_correlation_id = ?,
                    pix_transaction_id = ?,
                    pix_qr_code = ?,
                    pix_qr_code_image = ?,
                    pix_payment_link = ?,
                    pix_expires_at = ?,
                    metodo_pagamento = 'pix_openpix',
                    data_atualizacao = NOW()
                WHERE id = ?
            ");
            
            $updateStmt->execute([
                $result['charge_id'],
                $chargeData['correlation_id'],
                $result['transaction_id'],
                $result['qr_code'],
                $result['qr_code_image'],
                $result['payment_link'],
                $result['expires_at'],
                $payment['id']
            ]);
            
            // Log da operação
            error_log("PIX criado com sucesso para pagamento {$payment['id']}: {$result['charge_id']}");
            
            echo json_encode([
                'status' => true,
                'data' => [
                    'charge_id' => $result['charge_id'],
                    'correlation_id' => $chargeData['correlation_id'],
                    'transaction_id' => $result['transaction_id'],
                    'qr_code' => $result['qr_code'],
                    'qr_code_image' => $result['qr_code_image'],
                    'payment_link' => $result['payment_link'],
                    'expires_at' => $result['expires_at'],
                    'value' => $result['value'],
                    'value_formatted' => OpenPixClient::formatCurrency($result['value'])
                ],
                'message' => 'PIX gerado com sucesso'
            ]);
        } else {
            http_response_code(400);
            echo json_encode([
                'status' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? null
            ]);
        }
        
    } catch (Exception $e) {
        error_log('Erro ao criar PIX: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'status' => false,
            'message' => 'Erro interno do servidor'
        ]);
    }
}

/**
 * Processar pagamento (aprovar/rejeitar)
 */
function processPayment() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['payment_id']) || !isset($input['action'])) {
        http_response_code(400);
        echo json_encode([
            'status' => false,
            'message' => 'payment_id e action obrigatórios'
        ]);
        return;
    }
    
    $allowedActions = ['approve', 'reject'];
    if (!in_array($input['action'], $allowedActions)) {
        http_response_code(400);
        echo json_encode([
            'status' => false,
            'message' => 'action deve ser approve ou reject'
        ]);
        return;
    }
    
    try {
        // Usar o TransactionController se existir
        if (class_exists('TransactionController') && 
            method_exists('TransactionController', 'approvePaymentAutomatically')) {
            
            if ($input['action'] === 'approve') {
                $result = TransactionController::approvePaymentAutomatically(
                    $input['payment_id'], 
                    $input['notes'] ?? 'Pagamento PIX confirmado automaticamente'
                );
            } else {
                $result = TransactionController::rejectPayment(
                    $input['payment_id'],
                    $input['notes'] ?? 'Pagamento rejeitado'
                );
            }
            
            echo json_encode($result);
        } else {
            // Fallback: aprovar/rejeitar diretamente no banco
            $db = Database::getConnection();
            
            $newStatus = $input['action'] === 'approve' ? 'aprovado' : 'rejeitado';
            $dateField = $input['action'] === 'approve' ? 'data_aprovacao' : 'data_rejeicao';
            
            $stmt = $db->prepare("
                UPDATE pagamentos_comissao 
                SET status = ?, {$dateField} = NOW(), observacoes = ? 
                WHERE id = ?
            ");
            
            $stmt->execute([
                $newStatus,
                $input['notes'] ?? 'Processado automaticamente',
                $input['payment_id']
            ]);
            
            echo json_encode([
                'status' => true,
                'message' => 'Pagamento processado com sucesso'
            ]);
        }
        
    } catch (Exception $e) {
        error_log('Erro ao processar pagamento: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'status' => false,
            'message' => 'Erro interno do servidor'
        ]);
    }
}

/**
 * Verificar status de cobrança
 */
function checkChargeStatus() {
    $chargeId = $_GET['charge_id'] ?? '';
    
    if (empty($chargeId)) {
        http_response_code(400);
        echo json_encode([
            'status' => false,
            'message' => 'charge_id obrigatório'
        ]);
        return;
    }
    
    try {
        $openPix = new OpenPixClient();
        $result = $openPix->getChargeStatus($chargeId);
        
        if ($result['status']) {
            echo json_encode([
                'status' => true,
                'data' => $result
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'status' => false,
                'message' => $result['message']
            ]);
        }
        
    } catch (Exception $e) {
        error_log('Erro ao verificar status PIX: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'status' => false,
            'message' => 'Erro interno do servidor'
        ]);
    }
}
?>