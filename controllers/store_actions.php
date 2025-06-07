<?php
/**
 * Store Actions - Klube Cash v3.1
 * Processa ações da área da loja com OpenPix
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/AuthController.php';

// Headers para API
header('Content-Type: application/json; charset=UTF-8');

// Verificar autenticação
if (!AuthController::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['status' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

// Verificar se é loja
if (!AuthController::isStore()) {
    http_response_code(403);
    echo json_encode(['status' => false, 'message' => 'Acesso negado. Apenas lojas podem usar esta funcionalidade.']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$userId = AuthController::getCurrentUserId();

try {
    switch ($action) {
        case 'payment_form':
            handlePaymentForm();
            break;
            
        case 'create_pix_payment':
            handleCreatePixPayment();
            break;
            
        case 'process_selected_payments':
            handleProcessSelectedPayments();
            break;
            
        case 'create_commission_payment':
            handleCreateCommissionPayment();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['status' => false, 'message' => 'Ação não reconhecida: ' . $action]);
            break;
    }
} catch (Exception $e) {
    error_log('Erro no store_actions: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Erro interno do servidor']);
}

/**
 * Processar formulário de pagamento via PIX
 */
function handlePaymentForm() {
    global $userId;
    
    $transactionIds = $_POST['transaction_ids'] ?? [];
    
    if (empty($transactionIds) || !is_array($transactionIds)) {
        echo json_encode(['status' => false, 'message' => 'Nenhuma transação selecionada']);
        return;
    }
    
    // Criar pagamento diretamente e redirecionar para PIX
    $paymentId = createCommissionPayment($transactionIds, $userId);
    
    if ($paymentId) {
        echo json_encode([
            'status' => true,
            'message' => 'Redirecionando para pagamento PIX',
            'redirect_url' => STORE_PAYMENT_PIX_URL . '?payment_id=' . $paymentId
        ]);
    } else {
        echo json_encode(['status' => false, 'message' => 'Erro ao criar pagamento']);
    }
}

/**
 * Criar pagamento PIX direto
 */
function handleCreatePixPayment() {
    global $userId;
    
    $transactionIds = $_POST['transaction_ids'] ?? [];
    
    if (empty($transactionIds) || !is_array($transactionIds)) {
        echo json_encode(['status' => false, 'message' => 'Nenhuma transação selecionada']);
        return;
    }
    
    $paymentId = createCommissionPayment($transactionIds, $userId);
    
    if ($paymentId) {
        // Criar cobrança PIX automaticamente
        $pixResponse = createPixCharge($paymentId);
        
        if ($pixResponse['status']) {
            echo json_encode([
                'status' => true,
                'message' => 'Cobrança PIX criada com sucesso',
                'data' => [
                    'payment_id' => $paymentId,
                    'pix_data' => $pixResponse['data']
                ]
            ]);
        } else {
            echo json_encode([
                'status' => false,
                'message' => 'Erro ao criar cobrança PIX: ' . $pixResponse['message']
            ]);
        }
    } else {
        echo json_encode(['status' => false, 'message' => 'Erro ao criar pagamento']);
    }
}

/**
 * Processar transações selecionadas
 */
function handleProcessSelectedPayments() {
    global $userId;
    
    $transactionIds = $_POST['transaction_ids'] ?? [];
    
    if (empty($transactionIds) || !is_array($transactionIds)) {
        echo json_encode(['status' => false, 'message' => 'Nenhuma transação selecionada']);
        return;
    }
    
    $paymentId = createCommissionPayment($transactionIds, $userId);
    
    if ($paymentId) {
        echo json_encode([
            'status' => true,
            'message' => 'Pagamento criado com sucesso',
            'redirect_url' => STORE_PAYMENT_PIX_URL . '?payment_id=' . $paymentId
        ]);
    } else {
        echo json_encode(['status' => false, 'message' => 'Erro ao criar pagamento']);
    }
}

/**
 * Criar pagamento de comissão (método legado)
 */
function handleCreateCommissionPayment() {
    global $userId;
    
    $transactionIds = $_POST['transaction_ids'] ?? [];
    $paymentMethod = $_POST['payment_method'] ?? 'pix_openpix';
    
    if (empty($transactionIds) || !is_array($transactionIds)) {
        echo json_encode(['status' => false, 'message' => 'Nenhuma transação selecionada']);
        return;
    }
    
    try {
        $paymentId = createCommissionPayment($transactionIds, $userId, $paymentMethod);
        
        if ($paymentId) {
            echo json_encode([
                'status' => true,
                'message' => 'Pagamento criado com sucesso',
                'data' => [
                    'payment_id' => $paymentId,
                    'redirect_url' => STORE_PAYMENT_PIX_URL . '?payment_id=' . $paymentId
                ]
            ]);
        } else {
            echo json_encode(['status' => false, 'message' => 'Erro ao criar pagamento']);
        }
        
    } catch (Exception $e) {
        error_log('Erro ao criar pagamento: ' . $e->getMessage());
        echo json_encode(['status' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
    }
}

/**
 * Criar registro de pagamento de comissão
 */
function createCommissionPayment($transactionIds, $userId, $paymentMethod = 'pix_automatico') {
    try {
        $db = Database::getConnection();
        
        // Buscar loja do usuário
        $storeStmt = $db->prepare("SELECT id, nome_fantasia FROM lojas WHERE usuario_id = ? AND status = 'aprovado'");
        $storeStmt->execute([$userId]);
        $store = $storeStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$store) {
            throw new Exception('Loja não encontrada ou não aprovada');
        }
        
        // Verificar se as transações existem e calcular total
        $placeholders = implode(',', array_fill(0, count($transactionIds), '?'));
        $transStmt = $db->prepare("
            SELECT t.id, t.valor_total, COALESCE(tsu.valor_usado, 0) as saldo_usado 
            FROM transacoes_cashback t
            LEFT JOIN transacoes_saldo_usado tsu ON t.id = tsu.transacao_id
            WHERE t.id IN ($placeholders) AND t.loja_id = ? AND t.status = 'pendente'
        ");
        $transStmt->execute([...$transactionIds, $store['id']]);
        $transactions = $transStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($transactions) !== count($transactionIds)) {
            throw new Exception('Algumas transações não foram encontradas ou não estão pendentes');
        }
        
        // Calcular valor total da comissão (10% sobre valor efetivo)
        $totalValue = 0;
        foreach ($transactions as $transaction) {
            $valorOriginal = floatval($transaction['valor_total']);
            $saldoUsado = floatval($transaction['saldo_usado']);
            $valorEfetivo = $valorOriginal - $saldoUsado;
            $totalValue += $valorEfetivo * 0.10; // 10% de comissão
        }
        
        if ($totalValue <= 0) {
            throw new Exception('Valor total inválido');
        }
        
        $db->beginTransaction();
        
        // Criar registro de pagamento
        $paymentStmt = $db->prepare("
            INSERT INTO pagamentos_comissao (loja_id, valor_total, metodo_pagamento, status, data_registro)
            VALUES (?, ?, ?, 'pendente', NOW())
        ");
        $paymentStmt->execute([$store['id'], $totalValue, $paymentMethod]);
        $paymentId = $db->lastInsertId();
        
        // Associar transações ao pagamento
        $linkStmt = $db->prepare("INSERT INTO pagamentos_transacoes (pagamento_id, transacao_id) VALUES (?, ?)");
        foreach ($transactionIds as $transactionId) {
            $linkStmt->execute([$paymentId, $transactionId]);
        }
        
        // Atualizar status das transações
        $updateStmt = $db->prepare("
            UPDATE transacoes_cashback 
            SET status = 'pagamento_pendente' 
            WHERE id IN ($placeholders)
        ");
        $updateStmt->execute($transactionIds);
        
        $db->commit();
        
        return $paymentId;
        
    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        error_log('Erro ao criar pagamento: ' . $e->getMessage());
        return false;
    }
}

/**
 * Criar cobrança PIX via OpenPix
 */
function createPixCharge($paymentId) {
    try {
        $postData = json_encode(['payment_id' => $paymentId]);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => OPENPIX_CREATE_CHARGE_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($postData)
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['status' => false, 'message' => 'Erro de comunicação: ' . $error];
        }
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            return $result ?: ['status' => false, 'message' => 'Resposta inválida da API'];
        } else {
            return ['status' => false, 'message' => 'Erro HTTP: ' . $httpCode];
        }
        
    } catch (Exception $e) {
        error_log('Erro ao criar cobrança PIX: ' . $e->getMessage());
        return ['status' => false, 'message' => 'Erro interno'];
    }
}
?>