<?php
// api/refunds.php
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../utils/MercadoPagoClient.php';

session_start();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'POST':
            if ($action === 'request') {
                requestRefund();
            } elseif ($action === 'approve') {
                approveRefund();
            } elseif ($action === 'reject') {
                rejectRefund();
            } else {
                http_response_code(400);
                echo json_encode(['status' => false, 'message' => 'Ação inválida']);
            }
            break;
        case 'GET':
            if ($action === 'status') {
                checkRefundStatus();
            } elseif ($action === 'list') {
                listRefunds();
            } else {
                http_response_code(400);
                echo json_encode(['status' => false, 'message' => 'Ação inválida']);
            }
            break;
        default:
            http_response_code(405);
            echo json_encode(['status' => false, 'message' => 'Método não permitido']);
            break;
    }
} catch (Exception $e) {
    error_log("Refund API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Erro interno do servidor']);
}

function requestRefund() {
    // Verificar se é admin ou loja autorizada
    if (!AuthController::isAuthenticated()) {
        http_response_code(401);
        echo json_encode(['status' => false, 'message' => 'Não autorizado']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $paymentId = $input['payment_id'] ?? 0;
    $amount = $input['amount'] ?? null; // null = devolução total
    $reason = $input['reason'] ?? 'Solicitação de devolução';
    
    if (!$paymentId) {
        echo json_encode(['status' => false, 'message' => 'payment_id obrigatório']);
        return;
    }
    
    try {
        $db = Database::getConnection();
        
        // Buscar dados do pagamento
        $stmt = $db->prepare("
            SELECT * FROM pagamentos_comissao 
            WHERE id = ? AND status = 'aprovado' AND mp_payment_id IS NOT NULL
        ");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            echo json_encode(['status' => false, 'message' => 'Pagamento não encontrado ou não elegível para devolução']);
            return;
        }
        
        // Verificar se já existe devolução em andamento
        $checkStmt = $db->prepare("
            SELECT * FROM pagamentos_devolucoes 
            WHERE pagamento_id = ? AND status IN ('solicitado', 'processando')
        ");
        $checkStmt->execute([$paymentId]);
        
        if ($checkStmt->rowCount() > 0) {
            echo json_encode(['status' => false, 'message' => 'Já existe uma devolução em andamento para este pagamento']);
            return;
        }
        
        // Determinar valor da devolução
        $refundAmount = $amount ?? $payment['valor_total'];
        $refundType = $amount ? 'parcial' : 'total';
        
        // Validar valor
        if ($refundAmount > $payment['valor_total']) {
            echo json_encode(['status' => false, 'message' => 'Valor da devolução não pode ser maior que o valor pago']);
            return;
        }
        
        // Registrar solicitação no banco
        $insertStmt = $db->prepare("
            INSERT INTO pagamentos_devolucoes 
            (pagamento_id, mp_payment_id, valor_devolucao, motivo, tipo, status, solicitado_por)
            VALUES (?, ?, ?, ?, ?, 'solicitado', ?)
        ");
        
        $insertStmt->execute([
            $paymentId,
            $payment['mp_payment_id'],
            $refundAmount,
            $reason,
            $refundType,
            AuthController::getCurrentUserId()
        ]);
        
        $refundId = $db->lastInsertId();
        
        echo json_encode([
            'status' => true,
            'message' => 'Solicitação de devolução registrada com sucesso',
            'data' => [
                'refund_id' => $refundId,
                'amount' => $refundAmount,
                'type' => $refundType
            ]
        ]);
        
    } catch (Exception $e) {
        error_log('Erro ao solicitar devolução: ' . $e->getMessage());
        echo json_encode(['status' => false, 'message' => 'Erro interno do servidor']);
    }
}

function approveRefund() {
    // Só admin pode aprovar
    if (!AuthController::isAuthenticated() || !AuthController::isAdmin()) {
        http_response_code(401);
        echo json_encode(['status' => false, 'message' => 'Acesso restrito a administradores']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $refundId = $input['refund_id'] ?? 0;
    $observation = $input['observation'] ?? '';
    
    if (!$refundId) {
        echo json_encode(['status' => false, 'message' => 'refund_id obrigatório']);
        return;
    }
    
    try {
        $db = Database::getConnection();
        
        // Buscar dados da devolução
        $stmt = $db->prepare("
            SELECT * FROM pagamentos_devolucoes 
            WHERE id = ? AND status = 'solicitado'
        ");
        $stmt->execute([$refundId]);
        $refund = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$refund) {
            echo json_encode(['status' => false, 'message' => 'Devolução não encontrada ou não está pendente']);
            return;
        }
        
        // Atualizar status para processando
        $updateStmt = $db->prepare("
            UPDATE pagamentos_devolucoes 
            SET status = 'processando', aprovado_por = ?, observacao_admin = ?
            WHERE id = ?
        ");
        $updateStmt->execute([AuthController::getCurrentUserId(), $observation, $refundId]);
        
        // Fazer solicitação para Mercado Pago
        $mpClient = new MercadoPagoClient();
        $mpResponse = $mpClient->createRefund(
            $refund['mp_payment_id'],
            $refund['valor_devolucao'],
            $refund['motivo']
        );
        
        if ($mpResponse['status']) {
            $mpRefundData = $mpResponse['data'];
            
            // Atualizar com dados do MP
            $finalUpdateStmt = $db->prepare("
                UPDATE pagamentos_devolucoes 
                SET mp_refund_id = ?, status = 'aprovado', data_processamento = NOW(), dados_mp = ?
                WHERE id = ?
            ");
            $finalUpdateStmt->execute([
                $mpRefundData['id'],
                json_encode($mpRefundData),
                $refundId
            ]);
            
            echo json_encode([
                'status' => true,
                'message' => 'Devolução processada com sucesso',
                'data' => $mpRefundData
            ]);
        } else {
            // Erro no MP - voltar status para solicitado
            $errorStmt = $db->prepare("
                UPDATE pagamentos_devolucoes 
                SET status = 'erro', observacao_admin = ?
                WHERE id = ?
            ");
            $errorStmt->execute([
                'Erro no Mercado Pago: ' . $mpResponse['message'],
                $refundId
            ]);
            
            echo json_encode([
                'status' => false,
                'message' => 'Erro ao processar devolução no Mercado Pago: ' . $mpResponse['message']
            ]);
        }
        
    } catch (Exception $e) {
        error_log('Erro ao aprovar devolução: ' . $e->getMessage());
        echo json_encode(['status' => false, 'message' => 'Erro interno do servidor']);
    }
}

function rejectRefund() {
    if (!AuthController::isAuthenticated() || !AuthController::isAdmin()) {
        http_response_code(401);
        echo json_encode(['status' => false, 'message' => 'Acesso restrito a administradores']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $refundId = $input['refund_id'] ?? 0;
    $reason = $input['reason'] ?? 'Devolução rejeitada pelo administrador';
    
    try {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            UPDATE pagamentos_devolucoes 
            SET status = 'rejeitado', aprovado_por = ?, observacao_admin = ?, data_processamento = NOW()
            WHERE id = ? AND status = 'solicitado'
        ");
        
        $result = $stmt->execute([AuthController::getCurrentUserId(), $reason, $refundId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => true, 'message' => 'Devolução rejeitada com sucesso']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Devolução não encontrada ou já processada']);
        }
        
    } catch (Exception $e) {
        error_log('Erro ao rejeitar devolução: ' . $e->getMessage());
        echo json_encode(['status' => false, 'message' => 'Erro interno do servidor']);
    }
}

function checkRefundStatus() {
    $refundId = $_GET['refund_id'] ?? 0;
    
    if (!$refundId) {
        echo json_encode(['status' => false, 'message' => 'refund_id obrigatório']);
        return;
    }
    
    try {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            SELECT pd.*, pc.valor_total as pagamento_valor
            FROM pagamentos_devolucoes pd
            JOIN pagamentos_comissao pc ON pd.pagamento_id = pc.id
            WHERE pd.id = ?
        ");
        $stmt->execute([$refundId]);
        $refund = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$refund) {
            echo json_encode(['status' => false, 'message' => 'Devolução não encontrada']);
            return;
        }
        
        // Se tem MP refund ID, verificar status no MP
        if ($refund['mp_refund_id']) {
            $mpClient = new MercadoPagoClient();
            $mpStatus = $mpClient->getRefundStatus($refund['mp_payment_id'], $refund['mp_refund_id']);
            
            if ($mpStatus['status']) {
                $refund['mp_status'] = $mpStatus['data'];
            }
        }
        
        echo json_encode(['status' => true, 'data' => $refund]);
        
    } catch (Exception $e) {
        error_log('Erro ao verificar status da devolução: ' . $e->getMessage());
        echo json_encode(['status' => false, 'message' => 'Erro interno do servidor']);
    }
}

function listRefunds() {
    if (!AuthController::isAuthenticated()) {
        http_response_code(401);
        echo json_encode(['status' => false, 'message' => 'Não autorizado']);
        return;
    }
    
    $paymentId = $_GET['payment_id'] ?? null;
    $status = $_GET['status'] ?? null;
    $limit = intval($_GET['limit'] ?? 20);
    $offset = intval($_GET['offset'] ?? 0);
    
    try {
        $db = Database::getConnection();
        
        $where = [];
        $params = [];
        
        if ($paymentId) {
            $where[] = "pd.pagamento_id = ?";
            $params[] = $paymentId;
        }
        
        if ($status) {
            $where[] = "pd.status = ?";
            $params[] = $status;
        }
        
        // Se não for admin, só pode ver devoluções próprias
        if (!AuthController::isAdmin()) {
            $where[] = "pd.solicitado_por = ?";
            $params[] = AuthController::getCurrentUserId();
        }
        
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $stmt = $db->prepare("
            SELECT pd.*, pc.valor_total as pagamento_valor, pc.loja_id,
                   us.nome as solicitante_nome, ua.nome as aprovador_nome
            FROM pagamentos_devolucoes pd
            JOIN pagamentos_comissao pc ON pd.pagamento_id = pc.id
            LEFT JOIN usuarios us ON pd.solicitado_por = us.id
            LEFT JOIN usuarios ua ON pd.aprovado_por = ua.id
            $whereClause
            ORDER BY pd.data_solicitacao DESC
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        
        $refunds = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => true, 'data' => $refunds]);
        
    } catch (Exception $e) {
        error_log('Erro ao listar devoluções: ' . $e->getMessage());
        echo json_encode(['status' => false, 'message' => 'Erro interno do servidor']);
    }
}
?>