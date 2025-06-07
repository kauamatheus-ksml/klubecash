<?php
/**
 * Controlador de Pagamentos - Klube Cash
 * Processa ações relacionadas a pagamentos de comissões
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/OpenPixController.php';

// Verificar se usuário está logado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];

try {
    switch ($action) {
        case 'payment_form':
            handlePaymentForm();
            break;
            
        case 'create_commission_payment':
            handleCreateCommissionPayment();
            break;
            
        case 'process_pix_payment':
            handlePixPayment();
            break;
            
        case 'get_payment_summary':
            handleGetPaymentSummary();
            break;
            
        case 'cancel_payment':
            handleCancelPayment();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['status' => false, 'message' => 'Ação não reconhecida: ' . $action]);
            break;
    }
} catch (Exception $e) {
    error_log('Erro no PaymentController: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Erro interno do servidor']);
}

/**
 * Processar formulário de pagamento
 */
function handlePaymentForm() {
    global $userId, $userType;
    
    if ($userType !== 'loja') {
        http_response_code(403);
        echo json_encode(['status' => false, 'message' => 'Acesso negado']);
        return;
    }
    
    $transactionIds = $_POST['transaction_ids'] ?? [];
    
    if (empty($transactionIds) || !is_array($transactionIds)) {
        echo json_encode(['status' => false, 'message' => 'Nenhuma transação selecionada']);
        return;
    }
    
    try {
        $db = Database::getConnection();
        
        // Validar que todas as transações pertencem à loja do usuário
        $placeholders = str_repeat('?,', count($transactionIds) - 1) . '?';
        $stmt = $db->prepare("
            SELECT t.*, c.nome as cliente_nome, c.email as cliente_email
            FROM transacoes_cashback t
            LEFT JOIN usuarios c ON t.usuario_id = c.id
            JOIN lojas l ON t.loja_id = l.id
            WHERE t.id IN ($placeholders) 
            AND l.usuario_id = ? 
            AND t.status IN ('pendente', 'pagamento_pendente')
        ");
        
        $params = array_merge($transactionIds, [$userId]);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($transactions)) {
            echo json_encode(['status' => false, 'message' => 'Nenhuma transação válida encontrada']);
            return;
        }
        
        // Calcular totais
        $summary = calculatePaymentSummary($transactions);
        
        echo json_encode([
            'status' => true,
            'data' => [
                'transactions' => $transactions,
                'summary' => $summary,
                'payment_form_url' => SITE_URL . '/loja/pagamento-comissoes'
            ]
        ]);
        
    } catch (Exception $e) {
        error_log('Erro ao processar formulário de pagamento: ' . $e->getMessage());
        echo json_encode(['status' => false, 'message' => 'Erro ao processar solicitação']);
    }
}

/**
 * Criar pagamento de comissão
 */
function handleCreateCommissionPayment() {
    global $userId, $userType;
    
    if ($userType !== 'loja') {
        http_response_code(403);
        echo json_encode(['status' => false, 'message' => 'Acesso negado']);
        return;
    }
    
    $transactionIds = $_POST['transaction_ids'] ?? [];
    $paymentMethod = $_POST['payment_method'] ?? 'pix_openpix';
    
    if (empty($transactionIds) || !is_array($transactionIds)) {
        echo json_encode(['status' => false, 'message' => 'Nenhuma transação selecionada']);
        return;
    }
    
    try {
        $db = Database::getConnection();
        $db->beginTransaction();
        
        // Buscar dados da loja
        $stmtLoja = $db->prepare("SELECT * FROM lojas WHERE usuario_id = ?");
        $stmtLoja->execute([$userId]);
        $loja = $stmtLoja->fetch(PDO::FETCH_ASSOC);
        
        if (!$loja) {
            throw new Exception('Loja não encontrada');
        }
        
        // Buscar transações válidas
        $placeholders = str_repeat('?,', count($transactionIds) - 1) . '?';
        $stmt = $db->prepare("
            SELECT * FROM transacoes_cashback 
            WHERE id IN ($placeholders) 
            AND loja_id = ? 
            AND status IN ('pendente', 'pagamento_pendente')
        ");
        
        $params = array_merge($transactionIds, [$loja['id']]);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($transactions)) {
            throw new Exception('Nenhuma transação válida encontrada');
        }
        
        // Calcular valor total da comissão
        $valorTotal = 0;
        foreach ($transactions as $transaction) {
            $valorTotal += $transaction['valor_total'] * 0.10; // 10% de comissão
        }
        
        // Criar registro de pagamento
        $stmtPayment = $db->prepare("
            INSERT INTO pagamentos_comissao 
            (loja_id, valor_total, metodo_pagamento, status, data_criacao) 
            VALUES (?, ?, ?, 'pendente', NOW())
        ");
        
        $stmtPayment->execute([$loja['id'], $valorTotal, $paymentMethod]);
        $paymentId = $db->lastInsertId();
        
        // Vincular transações ao pagamento
        foreach ($transactions as $transaction) {
            $comissaoValor = $transaction['valor_total'] * 0.10;
            
            $stmtComissao = $db->prepare("
                INSERT INTO transacoes_comissao 
                (transacao_id, pagamento_id, valor_comissao, tipo, status) 
                VALUES (?, ?, ?, 'admin', 'pendente')
            ");
            
            $stmtComissao->execute([$transaction['id'], $paymentId, $comissaoValor]);
            
            // Atualizar status da transação
            $stmtUpdate = $db->prepare("
                UPDATE transacoes_cashback 
                SET status = 'pagamento_pendente' 
                WHERE id = ?
            ");
            $stmtUpdate->execute([$transaction['id']]);
        }
        
        $db->commit();
        
        echo json_encode([
            'status' => true,
            'message' => 'Pagamento criado com sucesso',
            'data' => [
                'payment_id' => $paymentId,
                'valor_total' => $valorTotal,
                'redirect_url' => SITE_URL . '/loja/pagamento-pix?payment_id=' . $paymentId
            ]
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log('Erro ao criar pagamento de comissão: ' . $e->getMessage());
        echo json_encode(['status' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Processar pagamento PIX
 */
function handlePixPayment() {
    global $userId, $userType;
    
    if ($userType !== 'loja') {
        http_response_code(403);
        echo json_encode(['status' => false, 'message' => 'Acesso negado']);
        return;
    }
    
    $paymentId = $_POST['payment_id'] ?? '';
    
    if (empty($paymentId)) {
        echo json_encode(['status' => false, 'message' => 'ID do pagamento obrigatório']);
        return;
    }
    
    try {
        $openPixController = new OpenPixController();
        $result = $openPixController->createChargeForPayment($paymentId, $userId);
        
        echo json_encode($result);
        
    } catch (Exception $e) {
        error_log('Erro ao processar pagamento PIX: ' . $e->getMessage());
        echo json_encode(['status' => false, 'message' => 'Erro ao processar PIX']);
    }
}

/**
 * Obter resumo do pagamento
 */
function handleGetPaymentSummary() {
    global $userId, $userType;
    
    if ($userType !== 'loja') {
        http_response_code(403);
        echo json_encode(['status' => false, 'message' => 'Acesso negado']);
        return;
    }
    
    $transactionIds = $_GET['transaction_ids'] ?? '';
    
    if (empty($transactionIds)) {
        echo json_encode(['status' => false, 'message' => 'IDs das transações obrigatórios']);
        return;
    }
    
    $transactionIds = explode(',', $transactionIds);
    
    try {
        $db = Database::getConnection();
        
        $placeholders = str_repeat('?,', count($transactionIds) - 1) . '?';
        $stmt = $db->prepare("
            SELECT t.*, c.nome as cliente_nome
            FROM transacoes_cashback t
            LEFT JOIN usuarios c ON t.usuario_id = c.id
            JOIN lojas l ON t.loja_id = l.id
            WHERE t.id IN ($placeholders) 
            AND l.usuario_id = ?
        ");
        
        $params = array_merge($transactionIds, [$userId]);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $summary = calculatePaymentSummary($transactions);
        
        echo json_encode([
            'status' => true,
            'data' => [
                'summary' => $summary,
                'transactions' => $transactions
            ]
        ]);
        
    } catch (Exception $e) {
        error_log('Erro ao obter resumo: ' . $e->getMessage());
        echo json_encode(['status' => false, 'message' => 'Erro ao obter resumo']);
    }
}

/**
 * Cancelar pagamento
 */
function handleCancelPayment() {
    global $userId, $userType;
    
    if ($userType !== 'loja') {
        http_response_code(403);
        echo json_encode(['status' => false, 'message' => 'Acesso negado']);
        return;
    }
    
    $paymentId = $_POST['payment_id'] ?? '';
    $reason = $_POST['reason'] ?? 'Cancelado pela loja';
    
    if (empty($paymentId)) {
        echo json_encode(['status' => false, 'message' => 'ID do pagamento obrigatório']);
        return;
    }
    
    try {
        $openPixController = new OpenPixController();
        $result = $openPixController->cancelPix($paymentId, $reason);
        
        echo json_encode($result);
        
    } catch (Exception $e) {
        error_log('Erro ao cancelar pagamento: ' . $e->getMessage());
        echo json_encode(['status' => false, 'message' => 'Erro ao cancelar pagamento']);
    }
}

/**
 * Calcular resumo do pagamento
 */
function calculatePaymentSummary($transactions) {
    $summary = [
        'total_transactions' => count($transactions),
        'valor_total_vendas' => 0,
        'valor_total_cashback' => 0,
        'valor_total_comissao' => 0,
        'saldo_usado_total' => 0
    ];
    
    foreach ($transactions as $transaction) {
        $summary['valor_total_vendas'] += $transaction['valor_total'];
        $summary['valor_total_cashback'] += $transaction['valor_cashback'];
        $summary['valor_total_comissao'] += $transaction['valor_total'] * 0.10;
        $summary['saldo_usado_total'] += $transaction['saldo_usado'] ?? 0;
    }
    
    return $summary;
}
?>