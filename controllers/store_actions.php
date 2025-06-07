<?php
/**
 * Store Actions - Klube Cash
 * Processa ações da área da loja
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

// Verificar se usuário está logado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];

// Verificar se é loja
if ($userType !== 'loja') {
    http_response_code(403);
    echo json_encode(['status' => false, 'message' => 'Acesso negado']);
    exit;
}

try {
    switch ($action) {
        case 'payment_form':
            handlePaymentForm();
            break;
            
        case 'create_commission_payment':
            handleCreateCommissionPayment();
            break;
            
        case 'process_selected_payments':
            handleProcessSelectedPayments();
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
 * Processar formulário de pagamento (redirecionar)
 */
function handlePaymentForm() {
    global $userId;
    
    $transactionIds = $_POST['transaction_ids'] ?? [];
    
    if (empty($transactionIds) || !is_array($transactionIds)) {
        echo json_encode(['status' => false, 'message' => 'Nenhuma transação selecionada']);
        return;
    }
    
    // Redirecionar para formulário de pagamento
    $url = SITE_URL . '/loja/formulario-pagamento?transactions=' . implode(',', $transactionIds);
    
    echo json_encode([
        'status' => true,
        'message' => 'Redirecionando para formulário de pagamento',
        'redirect_url' => $url
    ]);
}

/**
 * Processar transações selecionadas (redirecionar direto para PIX)
 */
function handleProcessSelectedPayments() {
    global $userId;
    
    $transactionIds = $_POST['transaction_ids'] ?? [];
    
    if (empty($transactionIds) || !is_array($transactionIds)) {
        echo json_encode(['status' => false, 'message' => 'Nenhuma transação selecionada']);
        return;
    }
    
    try {
        // Criar pagamento diretamente
        $paymentId = createCommissionPayment($transactionIds, $userId);
        
        if ($paymentId) {
            echo json_encode([
                'status' => true,
                'message' => 'Pagamento criado com sucesso',
                'redirect_url' => SITE_URL . '/loja/pagamento-pix?payment_id=' . $paymentId
            ]);
        } else {
            echo json_encode(['status' => false, 'message' => 'Erro ao criar pagamento']);
        }
        
    } catch (Exception $e) {
        error_log('Erro ao processar pagamentos selecionados: ' . $e->getMessage());
        echo json_encode(['status' => false, 'message' => 'Erro ao processar pagamentos']);
    }
}

/**
 * Criar pagamento de comissão
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
                    'redirect_url' => SITE_URL . '/loja/pagamento-pix?payment_id=' . $paymentId
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
 * Função auxiliar para criar pagamento
 */
function createCommissionPayment($transactionIds, $userId, $paymentMethod = 'pix_openpix') {
    $db = Database::getConnection();
    
    try {
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
        return $paymentId;
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}
?>