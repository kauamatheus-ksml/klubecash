<?php
// api/store-payment.php

// Incluir arquivos necessários
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../utils/Logger.php';

// Iniciar sessão se ainda não iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticação
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'loja') {
    http_response_code(401);
    echo json_encode(['status' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

// Obter ação - pode vir via GET ou POST
$action = $_REQUEST['action'] ?? '';
$userId = $_SESSION['user_id'];

// Debug - registrar a requisição
Logger::log('store_payment', "Requisição recebida - Ação: {$action}, Método: {$_SERVER['REQUEST_METHOD']}", 'INFO');

// Processar ação
switch ($action) {
    case 'payment_form':
        handlePaymentForm();
        break;
        
    case 'create_pix':
        handleCreatePix();
        break;
        
    case 'check_status':
        handleCheckStatus();
        break;
        
    default:
        echo json_encode(['status' => false, 'message' => 'Ação não reconhecida: ' . $action]);
        break;
}

/**
 * Processar formulário de pagamento
 */
function handlePaymentForm() {
    global $userId;
    
    // Obter IDs das transações
    $transactionIds = $_POST['transaction_ids'] ?? [];
    
    // Log para debug
    Logger::log('store_payment', 'IDs recebidos: ' . json_encode($transactionIds), 'DEBUG');
    
    if (empty($transactionIds)) {
        echo json_encode(['status' => false, 'message' => 'Nenhuma transação selecionada']);
        return;
    }
    
    try {
        $db = Database::getConnection();
        
        // Buscar dados da loja
        $stmtLoja = $db->prepare("
            SELECT id, nome_fantasia, cnpj, email, telefone 
            FROM lojas 
            WHERE usuario_id = ? 
            LIMIT 1
        ");
        $stmtLoja->execute([$userId]);
        $loja = $stmtLoja->fetch(PDO::FETCH_ASSOC);
        
        if (!$loja) {
            throw new Exception('Loja não encontrada');
        }
        
        // Preparar consulta para buscar transações
        $placeholders = str_repeat('?,', count($transactionIds) - 1) . '?';
        $stmt = $db->prepare("
            SELECT 
                t.id,
                t.valor_total,
                t.data_transacao,
                COALESCE(tc.valor_admin, t.valor_total * 0.10) as valor_comissao
            FROM transacoes_cashback t
            LEFT JOIN transacoes_comissao tc ON tc.transacao_id = t.id AND tc.tipo = 'admin'
            WHERE t.id IN ($placeholders) 
            AND t.loja_id = ?
            AND t.status IN ('pendente', 'pagamento_pendente')
        ");
        
        // Executar consulta
        $params = array_merge($transactionIds, [$loja['id']]);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($transactions)) {
            throw new Exception('Nenhuma transação válida encontrada');
        }
        
        // Calcular valor total da comissão
        $valorTotal = 0;
        foreach ($transactions as $transaction) {
            $valorTotal += $transaction['valor_comissao'];
        }
        
        // Iniciar transação no banco
        $db->beginTransaction();
        
        try {
            // Criar registro de pagamento
            $stmtPagamento = $db->prepare("
                INSERT INTO pagamentos_comissao (
                    loja_id, 
                    valor_total, 
                    data_criacao, 
                    status,
                    metodo_pagamento
                ) VALUES (?, ?, NOW(), 'pendente', 'pix_openpix')
            ");
            $stmtPagamento->execute([$loja['id'], $valorTotal]);
            $paymentId = $db->lastInsertId();
            
            // Verificar se a tabela pagamento_transacoes existe
            $tableCheck = $db->query("SHOW TABLES LIKE 'pagamento_transacoes'");
            if ($tableCheck->rowCount() > 0) {
                // Associar transações ao pagamento
                $stmtAssoc = $db->prepare("
                    INSERT INTO pagamento_transacoes (pagamento_id, transacao_id) 
                    VALUES (?, ?)
                ");
                
                foreach ($transactions as $transaction) {
                    $stmtAssoc->execute([$paymentId, $transaction['id']]);
                }
            }
            
            // Atualizar status das transações
            $stmtUpdate = $db->prepare("
                UPDATE transacoes_cashback 
                SET status = 'pagamento_pendente' 
                WHERE id IN ($placeholders)
            ");
            $stmtUpdate->execute($transactionIds);
            
            // Confirmar transação
            $db->commit();
            
            // Retornar sucesso com URL de redirecionamento
            echo json_encode([
                'status' => true,
                'payment_id' => $paymentId,
                'valor_total' => $valorTotal,
                'quantidade_transacoes' => count($transactions),
                'redirect_url' => SITE_URL . '/store/pagamento?id=' . $paymentId
            ]);
            
        } catch (Exception $e) {
            // Reverter transação em caso de erro
            $db->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        Logger::log('store_payment', 'Erro em handlePaymentForm: ' . $e->getMessage(), 'ERROR');
        echo json_encode(['status' => false, 'message' => 'Erro ao processar pagamento: ' . $e->getMessage()]);
    }
}

/**
 * Criar cobrança PIX via OpenPix
 */
function handleCreatePix() {
    global $userId;
    
    $paymentId = $_POST['payment_id'] ?? 0;
    
    if (!$paymentId) {
        echo json_encode(['status' => false, 'message' => 'ID do pagamento não informado']);
        return;
    }
    
    try {
        $db = Database::getConnection();
        
        // Buscar dados do pagamento
        $stmt = $db->prepare("
            SELECT p.*, l.nome_fantasia, l.cnpj, l.email, l.telefone 
            FROM pagamentos_comissao p
            JOIN lojas l ON p.loja_id = l.id
            WHERE p.id = ? AND l.usuario_id = ? AND p.status = 'pendente'
        ");
        $stmt->execute([$paymentId, $userId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            throw new Exception('Pagamento não encontrado ou já processado');
        }
        
        // Preparar dados para OpenPix
        $chargeData = [
            'value' => (int)($payment['valor_total'] * 100), // Valor em centavos
            'comment' => "Comissão Klube Cash - Pagamento #{$payment['id']} - {$payment['nome_fantasia']}",
            'correlationID' => "payment_{$payment['id']}_" . time(),
            'expiresIn' => 86400 // 24 horas
        ];
        
        // Fazer requisição para OpenPix
        $response = makeOpenPixRequest('POST', '/charge', $chargeData);
        
        if (isset($response['charge'])) {
            $charge = $response['charge'];
            
            // Atualizar pagamento com dados do PIX
            $updateStmt = $db->prepare("
                UPDATE pagamentos_comissao 
                SET 
                    pix_charge_id = ?,
                    pix_correlation_id = ?,
                    pix_qr_code = ?,
                    pix_qr_code_image = ?,
                    pix_expires_at = ?,
                    data_atualizacao = NOW()
                WHERE id = ?
            ");
            
            $expiresAt = isset($charge['expiresDate']) ? 
                date('Y-m-d H:i:s', strtotime($charge['expiresDate'])) : 
                date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            $updateStmt->execute([
                $charge['id'],
                $charge['correlationID'],
                $charge['brCode'],
                $charge['qrCodeImage'],
                $expiresAt,
                $payment['id']
            ]);
            
            // Retornar dados do PIX
            echo json_encode([
                'status' => true,
                'data' => [
                    'charge_id' => $charge['id'],
                    'qr_code' => $charge['brCode'],
                    'qr_code_image' => $charge['qrCodeImage'],
                    'expires_at' => $expiresAt,
                    'value' => $payment['valor_total']
                ]
            ]);
        } else {
            throw new Exception('Erro ao criar cobrança PIX');
        }
        
    } catch (Exception $e) {
        Logger::log('store_payment', 'Erro ao criar PIX: ' . $e->getMessage(), 'ERROR');
        echo json_encode(['status' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Verificar status do pagamento
 */
function handleCheckStatus() {
    $chargeId = $_GET['charge_id'] ?? '';
    
    if (empty($chargeId)) {
        echo json_encode(['status' => false, 'message' => 'ID da cobrança não informado']);
        return;
    }
    
    try {
        // Verificar status na OpenPix
        $response = makeOpenPixRequest('GET', "/charge/{$chargeId}");
        
        if (isset($response['charge'])) {
            $charge = $response['charge'];
            $isPaid = $charge['status'] === 'COMPLETED';
            
            // Se pago, atualizar no banco
            if ($isPaid) {
                $db = Database::getConnection();
                $stmt = $db->prepare("
                    UPDATE pagamentos_comissao 
                    SET status = 'pago', pix_paid_at = NOW() 
                    WHERE pix_charge_id = ? AND status = 'pendente'
                ");
                $stmt->execute([$chargeId]);
            }
            
            echo json_encode([
                'status' => true,
                'paid' => $isPaid,
                'charge_status' => $charge['status']
            ]);
        } else {
            throw new Exception('Cobrança não encontrada');
        }
        
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Fazer requisição para API da OpenPix
 */
function makeOpenPixRequest($method, $endpoint, $data = null) {
    $baseUrl = OPENPIX_BASE_URL;
    $url = $baseUrl . $endpoint;
    
    $headers = [
        'Authorization: ' . OPENPIX_API_KEY,
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception('Erro na requisição: ' . $error);
    }
    
    $result = json_decode($response, true);
    
    if ($httpCode >= 400) {
        $errorMessage = $result['error'] ?? 'Erro na API OpenPix';
        throw new Exception($errorMessage);
    }
    
    return $result;
}
?>