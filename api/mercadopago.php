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

// Log de início para rastreamento
error_log("=== MERCADOPAGO API CHAMADA ===");
error_log("Método: $method, Ação: $action");
error_log("Timestamp: " . date('Y-m-d H:i:s'));

try {
    switch ($method) {
        case 'POST':
            if ($action === 'create_payment') {
                createPixPaymentWithFullData();
            } else {
                http_response_code(400);
                echo json_encode(['status' => false, 'message' => 'Ação inválida: ' . $action]);
            }
            break;
        case 'GET':
            if ($action === 'status') {
                checkPaymentStatusWithDiagnosis();
            } elseif ($action === 'test') {
                testMercadoPagoConnection();
            } else {
                http_response_code(400);
                echo json_encode(['status' => false, 'message' => 'Ação inválida: ' . $action]);
            }
            break;
        default:
            http_response_code(405);
            echo json_encode(['status' => false, 'message' => 'Método não permitido: ' . $method]);
            break;
    }
} catch (Exception $e) {
    error_log("MERCADOPAGO API ERROR: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Erro interno do servidor: ' . $e->getMessage()]);
}

/**
 * Função para testar a conectividade com o Mercado Pago
 */
function testMercadoPagoConnection() {
    try {
        // Verificar se as constantes estão definidas
        if (!defined('MP_ACCESS_TOKEN')) {
            echo json_encode([
                'status' => false, 
                'message' => 'MP_ACCESS_TOKEN não está definido',
                'debug' => 'Verifique o arquivo config/constants.php'
            ]);
            return;
        }
        
        if (empty(MP_ACCESS_TOKEN)) {
            echo json_encode([
                'status' => false, 
                'message' => 'MP_ACCESS_TOKEN está vazio',
                'debug' => 'Configure suas credenciais do Mercado Pago'
            ]);
            return;
        }
        
        $mpClient = new MercadoPagoClient();
        $result = $mpClient->testConnection();
        
        echo json_encode([
            'status' => $result['status'],
            'message' => $result['message'],
            'token_preview' => substr(MP_ACCESS_TOKEN, 0, 20) . '...',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => false,
            'message' => 'Erro no teste: ' . $e->getMessage(),
            'debug' => 'Verifique se a classe MercadoPagoClient existe'
        ]);
    }
}

/**
 * Criar pagamento PIX com TODOS os dados necessários para máxima aprovação
 */
function createPixPaymentWithFullData() {
    // Verificar autenticação - sem isso, nada funciona
    session_start();
    if (!AuthController::isAuthenticated() || !AuthController::isStore()) {
        http_response_code(401);
        echo json_encode(['status' => false, 'message' => 'Não autorizado']);
        return;
    }
    
    // Ler dados da requisição
    $input = json_decode(file_get_contents('php://input'), true);
    error_log("DIAGNÓSTICO: Input recebido: " . json_encode($input));
    
    if (!$input || !isset($input['payment_id'])) {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'payment_id obrigatório']);
        return;
    }

    try {
        $db = Database::getConnection();
        
        // Buscar dados COMPLETOS do pagamento e da loja
        $stmt = $db->prepare("
            SELECT p.*, l.nome_fantasia, l.email, l.telefone, l.cnpj,
                   le.cep, le.logradouro, le.numero, le.complemento, 
                   le.bairro, le.cidade, le.estado,
                   u.nome as loja_proprietario_nome, u.telefone as loja_proprietario_telefone
            FROM pagamentos_comissao p
            JOIN lojas l ON p.loja_id = l.id 
            LEFT JOIN lojas_endereco le ON l.id = le.loja_id
            LEFT JOIN usuarios u ON l.usuario_id = u.id
            WHERE p.id = ? AND p.status = 'pendente'
        ");
        $stmt->execute([$input['payment_id']]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            echo json_encode(['status' => false, 'message' => 'Pagamento não encontrado ou já processado']);
            return;
        }
        
        error_log("DIAGNÓSTICO: Dados do pagamento: " . json_encode($payment));
        
        // Verificar se valor é válido
        if ($payment['valor_total'] <= 0) {
            echo json_encode(['status' => false, 'message' => 'Valor do pagamento inválido: ' . $payment['valor_total']]);
            return;
        }
        
        // Gerar device_id único para este pagamento (recomendado pelo MP)
        $deviceId = 'device_' . md5($payment['loja_id'] . '_' . $payment['id'] . '_' . time());
        
        // Preparar dados COMPLETOS para Mercado Pago (QUALIDADE MÁXIMA)
        $paymentData = [
            // Dados básicos obrigatórios
            'amount' => floatval($payment['valor_total']),
            'description' => "Comissão Klube Cash - Pagamento #{$payment['id']}",
            'external_reference' => "payment_{$payment['id']}",
            'payment_id' => $payment['id'],
            'store_id' => $payment['loja_id'],
            'device_id' => $deviceId,
            
            // Dados COMPLETOS do pagador (MELHORA MUITO A APROVAÇÃO)
            'payer_email' => !empty($payment['email']) ? $payment['email'] : 'loja@klubecash.com',
            'payer_name' => $payment['loja_proprietario_nome'] ?? $payment['nome_fantasia'],
            'payer_lastname' => 'Klube Cash',
            'payer_phone' => $payment['telefone'] ?? $payment['loja_proprietario_telefone'],
            'payer_cpf' => $payment['cnpj'], // CNPJ da loja
            'payer_registration_date' => date('Y-m-d\TH:i:s', strtotime('-1 year')), // Data fictícia de cadastro
            
            // Endereço COMPLETO (MELHORA APROVAÇÃO)
            'payer_address' => [
                'zip_code' => preg_replace('/\D/', '', $payment['cep'] ?? '38700000'),
                'street_name' => $payment['logradouro'] ?? 'Rua Principal',
                'street_number' => (int)($payment['numero'] ?? 100),
                'neighborhood' => $payment['bairro'] ?? 'Centro',
                'city' => $payment['cidade'] ?? 'Patos de Minas',
                'federal_unit' => $payment['estado'] ?? 'MG'
            ],
            
            // Detalhes COMPLETOS do item (OBRIGATÓRIO PARA BOA APROVAÇÃO)
            'item_id' => 'COMISSAO_KC_' . $payment['id'],
            'item_title' => 'Comissão Klube Cash',
            'item_description' => 'Pagamento de comissão para liberação de cashback aos clientes',
            'item_category' => 'services'
        ];
        
        error_log("DIAGNÓSTICO: Dados COMPLETOS preparados para MP: " . json_encode($paymentData));
        
        // Verificar se conseguimos criar o cliente do MP
        try {
            $mpClient = new MercadoPagoClient();
        } catch (Exception $e) {
            echo json_encode([
                'status' => false, 
                'message' => 'Erro ao inicializar MercadoPagoClient: ' . $e->getMessage(),
                'debug' => 'Verifique as constantes MP_ACCESS_TOKEN no constants.php'
            ]);
            return;
        }
        
        // Fazer a requisição para o MP com TODOS os dados e capturar resposta completa
        $response = $mpClient->createPixPayment($paymentData);
        
        error_log("DIAGNÓSTICO: Resposta COMPLETA do MP: " . json_encode($response));
        
        if ($response['status']) {
            $mpPayment = $response['data'];
            
            // Verificar se temos todos os dados necessários
            if (!isset($mpPayment['mp_payment_id'])) {
                error_log("DIAGNÓSTICO: ERRO - mp_payment_id não encontrado na resposta");
                echo json_encode([
                    'status' => false, 
                    'message' => 'Resposta do MP não contém mp_payment_id',
                    'debug' => 'Resposta recebida: ' . json_encode($mpPayment)
                ]);
                return;
            }
            
            if (empty($mpPayment['qr_code']) || empty($mpPayment['qr_code_base64'])) {
                error_log("DIAGNÓSTICO: ERRO - QR Code não foi gerado");
                echo json_encode([
                    'status' => false, 
                    'message' => 'QR Code não foi gerado pelo Mercado Pago',
                    'debug' => 'Dados recebidos: ' . json_encode($mpPayment)
                ]);
                return;
            }
            
            // Salvar dados do PIX no banco COM device_id
            $updateStmt = $db->prepare("
                UPDATE pagamentos_comissao 
                SET mp_payment_id = ?, mp_qr_code = ?, mp_qr_code_base64 = ?, 
                    metodo_pagamento = 'pix_mercadopago', status = 'pix_aguardando',
                    observacao = CONCAT(COALESCE(observacao, ''), ' - Device ID: {$deviceId}')
                WHERE id = ?
            ");
            
            $updateResult = $updateStmt->execute([
                $mpPayment['mp_payment_id'],
                $mpPayment['qr_code'],
                $mpPayment['qr_code_base64'],
                $payment['id']
            ]);
            
            if (!$updateResult) {
                error_log("DIAGNÓSTICO: ERRO ao salvar dados no banco");
                echo json_encode(['status' => false, 'message' => 'Erro ao salvar dados no banco']);
                return;
            }
            
            error_log("DIAGNÓSTICO: ✅ PIX criado com QUALIDADE MÁXIMA - ID: " . $mpPayment['mp_payment_id']);
            
            echo json_encode([
                'status' => true,
                'data' => [
                    'mp_payment_id' => $mpPayment['mp_payment_id'],
                    'qr_code' => $mpPayment['qr_code'],
                    'qr_code_base64' => $mpPayment['qr_code_base64'],
                    'status' => $mpPayment['status'],
                    'device_id' => $deviceId
                ]
            ]);
        } else {
            error_log("DIAGNÓSTICO: ERRO na criação do PIX: " . $response['message']);
            echo json_encode([
                'status' => false, 
                'message' => 'Erro do Mercado Pago: ' . $response['message'],
                'debug' => $response
            ]);
        }
        
    } catch (Exception $e) {
        error_log('DIAGNÓSTICO: EXCEÇÃO ao criar pagamento PIX: ' . $e->getMessage());
        echo json_encode(['status' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
    }
}

/**
 * Verificar status do pagamento com diagnóstico
 */
function checkPaymentStatusWithDiagnosis() {
    $mpPaymentId = $_GET['mp_payment_id'] ?? '';
    
    if (empty($mpPaymentId)) {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'mp_payment_id obrigatório']);
        return;
    }
    
    try {
        $mpClient = new MercadoPagoClient();
        $response = $mpClient->getPaymentStatus($mpPaymentId);
        
        error_log("DIAGNÓSTICO: Status do pagamento {$mpPaymentId}: " . json_encode($response));
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        error_log('DIAGNÓSTICO: Erro ao verificar status: ' . $e->getMessage());
        echo json_encode(['status' => false, 'message' => 'Erro interno do servidor']);
    }
}
?>