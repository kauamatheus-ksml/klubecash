<?php
/**
 * API específica para pagamentos da loja
 * Captura todas as chamadas relacionadas a pagamentos
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

// Verificar se usuário está logado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];

// Verificar se é loja
if ($userType !== 'loja') {
    http_response_code(403);
    echo json_encode(['status' => false, 'message' => 'Acesso negado']);
    exit;
}

// Capturar ação
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Log para debug
error_log("API store-payment recebeu action: " . $action);
error_log("POST data: " . json_encode($_POST));

try {
    switch ($action) {
        case 'payment_form':
        case 'process_payment_form':
        case 'redirect_to_payment':
            handlePaymentFormRedirect();
            break;
            
        case 'create_payment':
        case 'create_commission_payment':
            handleCreatePayment();
            break;
            
        case 'process_selected':
        case 'process_selected_payments':
            handleProcessSelected();
            break;
            
        default:
            // Se chegou aqui com action desconhecida, vamos tentar processar como pagamento
            if (!empty($_POST['transaction_ids']) || !empty($_POST['selected_transactions'])) {
                handlePaymentFormRedirect();
            } else {
                http_response_code(400);
                echo json_encode([
                    'status' => false, 
                    'message' => 'Ação não reconhecida: ' . $action,
                    'debug' => [
                        'received_action' => $action,
                        'post_data' => $_POST,
                        'get_data' => $_GET
                    ]
                ]);
            }
            break;
    }
} catch (Exception $e) {
    error_log('Erro na API store-payment: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}

/**
 * Redirecionar para formulário de pagamento
 */
function handlePaymentFormRedirect() {
    global $userId;
    
    // Capturar IDs das transações de diferentes formas possíveis
    $transactionIds = [];
    
    if (!empty($_POST['transaction_ids'])) {
        if (is_array($_POST['transaction_ids'])) {
            $transactionIds = $_POST['transaction_ids'];
        } else {
            $transactionIds = [$_POST['transaction_ids']];
        }
    } elseif (!empty($_POST['selected_transactions'])) {
        if (is_array($_POST['selected_transactions'])) {
            $transactionIds = $_POST['selected_transactions'];
        } else {
            $transactionIds = [$_POST['selected_transactions']];
        }
    } elseif (!empty($_GET['transaction_ids'])) {
        $transactionIds = explode(',', $_GET['transaction_ids']);
    }
    
    // Filtrar valores vazios
    $transactionIds = array_filter($transactionIds);
    
    if (empty($transactionIds)) {
        echo json_encode(['status' => false, 'message' => 'Nenhuma transação selecionada']);
        return;
    }
    
    // Validar que as transações pertencem à loja
    if (!validateTransactions($transactionIds, $userId)) {
        echo json_encode(['status' => false, 'message' => 'Transações inválidas ou não autorizadas']);
        return;
    }
    
    // Criar URL de redirecionamento
    $redirectUrl = SITE_URL . '/loja/formulario-pagamento?transactions=' . implode(',', $transactionIds);
    
    echo json_encode([
        'status' => true,
        'message' => 'Redirecionando para formulário de pagamento',
        'redirect_url' => $redirectUrl,
        'transaction_count' => count($transactionIds)
    ]);
}

/**
 * Criar pagamento diretamente
 */
function handleCreatePayment() {
    global $userId;
    
    $transactionIds = $_POST['transaction_ids'] ?? [];
    $paymentMethod = $_POST['payment_method'] ?? 'pix_openpix';
    
    if (empty($transactionIds)) {
        echo json_encode(['status' => false, 'message' => 'Nenhuma transação selecionada']);
        return;
    }
    
    if (!is_array($transactionIds)) {
        $transactionIds = [$transactionIds];
    }
    
    try {
        $paymentId = createCommissionPayment($transactionIds, $userId, $paymentMethod);
        
        echo json_encode([
            'status' => true,
            'message' => 'Pagamento criado com sucesso',
            'data' => [
                'payment_id' => $paymentId,
                'redirect_url' => SITE_URL . '/loja/pagamento-pix?payment_id=' . $paymentId
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Processar transações selecionadas (criar pagamento e redirecionar para PIX)
 */
function handleProcessSelected() {
    global $userId;
    
    $transactionIds = $_POST['transaction_ids'] ?? $_POST['selected_transactions'] ?? [];
    
    if (empty($transactionIds)) {
        echo json_encode(['status' => false, 'message' => 'Nenhuma transação selecionada']);
        return;
    }
    
    if (!is_array($transactionIds)) {
        $transactionIds = [$transactionIds];
    }
    
    try {
        $paymentId = createCommissionPayment($transactionIds, $userId);
        
        echo json_encode([
            'status' => true,
            'message' => 'Redirecionando para pagamento PIX',
            'redirect_url' => SITE_URL . '/loja/pagamento-pix?payment_id=' . $paymentId
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Validar se as transações pertencem à loja
 */
function validateTransactions($transactionIds, $userId) {
    try {
        $db = Database::getConnection();
        
        $placeholders = str_repeat('?,', count($transactionIds) - 1) . '?';
        $stmt = $db->prepare("
            SELECT COUNT(*) as count
            FROM transacoes_cashback t
            JOIN lojas l ON t.loja_id = l.id
            WHERE t.id IN ($placeholders) 
            AND l.usuario_id = ?
            AND t.status IN ('pendente', 'pagamento_pendente')
        ");
        
        $params = array_merge($transactionIds, [$userId]);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] == count($transactionIds);
        
    } catch (Exception $e) {
        error_log('Erro ao validar transações: ' . $e->getMessage());
        return false;
    }
}

/**
 * Criar pagamento de comissão
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
            throw new Exception('Nenhuma transação válida encontrada para pagamento');
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
            
            // Verificar se a tabela transacoes_comissao existe
            $checkTable = $db->query("SHOW TABLES LIKE 'transacoes_comissao'")->fetch();
            
            if ($checkTable) {
                $stmtComissao = $db->prepare("
                    INSERT INTO transacoes_comissao 
                    (transacao_id, pagamento_id, valor_comissao, tipo, status) 
                    VALUES (?, ?, ?, 'admin', 'pendente')
                ");
                
                $stmtComissao->execute([$transaction['id'], $paymentId, $comissaoValor]);
            }
            
            // Atualizar status da transação
            $stmtUpdate = $db->prepare("
                UPDATE transacoes_cashback 
                SET status = 'pagamento_pendente' 
                WHERE id = ?
            ");
            $stmtUpdate->execute([$transaction['id']]);
        }
        
        $db->commit();
        
        error_log("Pagamento criado com sucesso - ID: {$paymentId}, Valor: {$valorTotal}");
        
        return $paymentId;
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log('Erro ao criar pagamento: ' . $e->getMessage());
        throw $e;
    }
}
?>