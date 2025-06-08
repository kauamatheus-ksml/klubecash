<?php
/**
 * API para pagamentos de lojas - Klube Cash
 * Processa pagamentos de comissões via PIX/OpenPix
 */

// Headers para JSON
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Responder OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir arquivos necessários
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/AuthController.php';

// Verificar se Logger existe, senão usar error_log
if (file_exists(__DIR__ . '/../utils/Logger.php')) {
    require_once __DIR__ . '/../utils/Logger.php';
    $useLogger = true;
} else {
    $useLogger = false;
}

// Função helper para log
function logMessage($context, $message, $level = 'INFO') {
    global $useLogger;
    if ($useLogger && class_exists('Logger')) {
        Logger::log($context, $message, $level);
    } else {
        error_log("[$level] $context: $message");
    }
}

// Iniciar sessão se necessário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticação
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'loja') {
    http_response_code(401);
    echo json_encode(['status' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

$action = $_REQUEST['action'] ?? '';
$userId = $_SESSION['user_id'];

logMessage('store_payment', "Ação: {$action}, Método: {$_SERVER['REQUEST_METHOD']}, User: {$userId}");

try {
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
            http_response_code(400);
            echo json_encode(['status' => false, 'message' => 'Ação não reconhecida: ' . $action]);
            break;
    }
} catch (Exception $e) {
    logMessage('store_payment', 'Erro geral: ' . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Erro interno do servidor']);
}

/**
 * Processar formulário de pagamento
 */
function handlePaymentForm() {
    global $userId;
    
    $transactionIds = $_POST['transaction_ids'] ?? [];
    
    // Validar entrada
    if (empty($transactionIds) || !is_array($transactionIds)) {
        echo json_encode(['status' => false, 'message' => 'Nenhuma transação selecionada']);
        return;
    }
    
    // Filtrar e validar IDs
    $validIds = array_filter($transactionIds, function($id) {
        return is_numeric($id) && $id > 0;
    });
    
    if (empty($validIds)) {
        echo json_encode(['status' => false, 'message' => 'IDs de transação inválidos']);
        return;
    }
    
    logMessage('store_payment', 'Processando ' . count($validIds) . ' transações para usuário ' . $userId);
    
    try {
        $db = Database::getConnection();
        
        // Buscar dados da loja
        $stmtLoja = $db->prepare("
            SELECT id, nome_fantasia, cnpj, email, telefone, status 
            FROM lojas 
            WHERE usuario_id = ? AND status = 'aprovado'
            LIMIT 1
        ");
        $stmtLoja->execute([$userId]);
        $loja = $stmtLoja->fetch(PDO::FETCH_ASSOC);
        
        if (!$loja) {
            throw new Exception('Loja não encontrada ou não aprovada');
        }
        
        // Preparar placeholders para consulta
        $placeholders = str_repeat('?,', count($validIds) - 1) . '?';
        
        // Buscar transações válidas
        $stmt = $db->prepare("
            SELECT 
                t.id,
                t.valor_total,
                t.data_transacao,
                t.codigo_transacao,
                COALESCE(tsu.valor_usado, 0) as saldo_usado,
                (t.valor_total - COALESCE(tsu.valor_usado, 0)) * 0.10 as valor_comissao,
                u.nome as cliente_nome
            FROM transacoes_cashback t
            LEFT JOIN transacoes_saldo_usado tsu ON tsu.transacao_id = t.id
            LEFT JOIN usuarios u ON t.usuario_id = u.id
            WHERE t.id IN ($placeholders) 
            AND t.loja_id = ?
            AND t.status IN ('pendente', 'pagamento_pendente')
            ORDER BY t.data_transacao DESC
        ");
        
        $params = array_merge($validIds, [$loja['id']]);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($transactions)) {
            throw new Exception('Nenhuma transação válida encontrada para pagamento');
        }
        
        // Calcular valores
        $valorTotal = 0;
        $detalhes = [];
        
        foreach ($transactions as $transaction) {
            $comissao = floatval($transaction['valor_comissao']);
            $valorTotal += $comissao;
            
            $detalhes[] = [
                'id' => $transaction['id'],
                'codigo' => $transaction['codigo_transacao'],
                'cliente' => $transaction['cliente_nome'],
                'valor_original' => floatval($transaction['valor_total']),
                'saldo_usado' => floatval($transaction['saldo_usado']),
                'comissao' => $comissao,
                'data' => $transaction['data_transacao']
            ];
        }
        
        if ($valorTotal <= 0) {
            throw new Exception('Valor total da comissão inválido');
        }
        
        // Iniciar transação do banco
        $db->beginTransaction();
        
        try {
            // Criar registro de pagamento
            $stmtPagamento = $db->prepare("
                INSERT INTO pagamentos_comissao (
                    loja_id, 
                    valor_total, 
                    data_criacao, 
                    status,
                    metodo_pagamento,
                    observacao
                ) VALUES (?, ?, NOW(), 'pendente', 'pix_openpix', ?)
            ");
            
            $observacao = 'Pagamento de comissão - ' . count($transactions) . ' transações';
            $stmtPagamento->execute([$loja['id'], $valorTotal, $observacao]);
            $paymentId = $db->lastInsertId();
            
            // Verificar se tabela pagamentos_transacoes existe
            $tableExists = false;
            try {
                $tableCheck = $db->query("SELECT 1 FROM pagamentos_transacoes LIMIT 1");
                $tableExists = true;
            } catch (PDOException $e) {
                logMessage('store_payment', 'Tabela pagamentos_transacoes não existe, criando...', 'WARNING');
                
                // Criar tabela se não existir
                $createTable = $db->exec("
                    CREATE TABLE IF NOT EXISTS pagamentos_transacoes (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        pagamento_id INT NOT NULL,
                        transacao_id INT NOT NULL,
                        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (pagamento_id) REFERENCES pagamentos_comissao(id),
                        FOREIGN KEY (transacao_id) REFERENCES transacoes_cashback(id),
                        UNIQUE KEY unique_payment_transaction (pagamento_id, transacao_id)
                    )
                ");
                $tableExists = true;
            }
            
            // Associar transações ao pagamento
            if ($tableExists) {
                $stmtAssoc = $db->prepare("
                    INSERT INTO pagamentos_transacoes (pagamento_id, transacao_id) 
                    VALUES (?, ?)
                ");
                
                foreach ($transactions as $transaction) {
                    $stmtAssoc->execute([$paymentId, $transaction['id']]);
                }
            }
            
            // Atualizar status das transações
            $stmtUpdate = $db->prepare("
                UPDATE transacoes_cashback 
                SET status = 'pagamento_pendente', data_atualizacao = NOW()
                WHERE id IN ($placeholders)
            ");
            $stmtUpdate->execute($validIds);
            
            // Confirmar transação
            $db->commit();
            
            logMessage('store_payment', "Pagamento {$paymentId} criado com sucesso - Valor: R$ {$valorTotal}");
            
            // Retornar sucesso
            echo json_encode([
                'status' => true,
                'message' => 'Pagamento criado com sucesso',
                'data' => [
                    'payment_id' => $paymentId,
                    'valor_total' => $valorTotal,
                    'quantidade_transacoes' => count($transactions),
                    'loja' => $loja['nome_fantasia'],
                    'detalhes' => $detalhes
                ],
                'redirect_url' => SITE_URL . '/store/pagamento?id=' . $paymentId
            ]);
            
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        logMessage('store_payment', 'Erro em handlePaymentForm: ' . $e->getMessage(), 'ERROR');
        echo json_encode(['status' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Criar cobrança PIX via OpenPix
 */
function handleCreatePix() {
    global $userId;
    
    $paymentId = $_POST['payment_id'] ?? $_GET['payment_id'] ?? 0;
    
    if (!$paymentId || !is_numeric($paymentId)) {
        echo json_encode(['status' => false, 'message' => 'ID do pagamento inválido']);
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
        
        // Verificar se PIX já foi criado
        if (!empty($payment['pix_charge_id'])) {
            echo json_encode([
                'status' => true,
                'message' => 'PIX já criado anteriormente',
                'data' => [
                    'charge_id' => $payment['pix_charge_id'],
                    'qr_code' => $payment['pix_qr_code'],
                    'qr_code_image' => $payment['pix_qr_code_image'],
                    'value' => floatval($payment['valor_total'])
                ]
            ]);
            return;
        }
        
        // Preparar dados para OpenPix
        $correlationId = "payment_{$paymentId}_" . time();
        $chargeData = [
            'value' => (int)(floatval($payment['valor_total']) * 100), // Centavos
            'comment' => "Comissão Klube Cash - {$payment['nome_fantasia']} - Pagamento #{$paymentId}",
            'correlationID' => $correlationId,
            'expiresIn' => 86400 // 24 horas
        ];
        
        logMessage('store_payment', "Criando PIX para pagamento {$paymentId} - Valor: R$ {$payment['valor_total']}");
        
        // Fazer requisição para OpenPix
        $response = makeOpenPixRequest('POST', '/charge', $chargeData);
        
        if (isset($response['charge']) && !empty($response['charge']['id'])) {
            $charge = $response['charge'];
            
            // Calcular data de expiração
            $expiresAt = isset($charge['expiresDate']) ? 
                date('Y-m-d H:i:s', strtotime($charge['expiresDate'])) : 
                date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Atualizar pagamento com dados do PIX
            $updateStmt = $db->prepare("
                UPDATE pagamentos_comissao 
                SET 
                    pix_charge_id = ?,
                    pix_correlation_id = ?,
                    pix_qr_code = ?,
                    pix_qr_code_image = ?,
                    pix_expires_at = ?,
                    status = 'pix_aguardando',
                    data_atualizacao = NOW()
                WHERE id = ?
            ");
            
            $updateStmt->execute([
                $charge['id'],
                $correlationId,
                $charge['brCode'] ?? '',
                $charge['qrCodeImage'] ?? '',
                $expiresAt,
                $paymentId
            ]);
            
            logMessage('store_payment', "PIX criado com sucesso - Charge ID: {$charge['id']}");
            
            // Retornar dados do PIX
            echo json_encode([
                'status' => true,
                'message' => 'PIX criado com sucesso',
                'data' => [
                    'charge_id' => $charge['id'],
                    'qr_code' => $charge['brCode'] ?? '',
                    'qr_code_image' => $charge['qrCodeImage'] ?? '',
                    'expires_at' => $expiresAt,
                    'value' => floatval($payment['valor_total']),
                    'correlation_id' => $correlationId
                ]
            ]);
        } else {
            throw new Exception('Resposta inválida da API OpenPix');
        }
        
    } catch (Exception $e) {
        logMessage('store_payment', 'Erro ao criar PIX: ' . $e->getMessage(), 'ERROR');
        echo json_encode(['status' => false, 'message' => 'Erro ao criar PIX: ' . $e->getMessage()]);
    }
}

/**
 * Verificar status do pagamento
 */
function handleCheckStatus() {
    $chargeId = $_GET['charge_id'] ?? '';
    $paymentId = $_GET['payment_id'] ?? 0;
    
    if (empty($chargeId) && !$paymentId) {
        echo json_encode(['status' => false, 'message' => 'ID da cobrança ou pagamento é obrigatório']);
        return;
    }
    
    try {
        $db = Database::getConnection();
        
        // Se temos payment_id, buscar charge_id
        if ($paymentId && empty($chargeId)) {
            $stmt = $db->prepare("SELECT pix_charge_id FROM pagamentos_comissao WHERE id = ?");
            $stmt->execute([$paymentId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $chargeId = $result['pix_charge_id'] ?? '';
        }
        
        if (empty($chargeId)) {
            throw new Exception('Cobrança PIX não encontrada');
        }
        
        // Verificar status na OpenPix
        $response = makeOpenPixRequest('GET', "/charge/{$chargeId}");
        
        if (isset($response['charge'])) {
            $charge = $response['charge'];
            $status = $charge['status'] ?? 'UNKNOWN';
            $isPaid = ($status === 'COMPLETED');
            
            // Se pago, atualizar no banco
            if ($isPaid) {
                $updateStmt = $db->prepare("
                    UPDATE pagamentos_comissao 
                    SET status = 'pago', pix_paid_at = NOW(), data_atualizacao = NOW()
                    WHERE pix_charge_id = ? AND status IN ('pendente', 'pix_aguardando')
                ");
                $result = $updateStmt->execute([$chargeId]);
                
                if ($result && $updateStmt->rowCount() > 0) {
                    logMessage('store_payment', "Pagamento automaticamente aprovado - Charge: {$chargeId}");
                }
            }
            
            echo json_encode([
                'status' => true,
                'paid' => $isPaid,
                'charge_status' => $status,
                'value' => isset($charge['value']) ? $charge['value'] / 100 : 0,
                'paid_at' => $charge['paidAt'] ?? null
            ]);
        } else {
            throw new Exception('Cobrança não encontrada na OpenPix');
        }
        
    } catch (Exception $e) {
        logMessage('store_payment', 'Erro ao verificar status: ' . $e->getMessage(), 'ERROR');
        echo json_encode(['status' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Fazer requisição para API da OpenPix
 */
function makeOpenPixRequest($method, $endpoint, $data = null) {
    if (!defined('OPENPIX_API_KEY') || !defined('OPENPIX_BASE_URL')) {
        throw new Exception('Configurações OpenPix não encontradas');
    }
    
    $url = OPENPIX_BASE_URL . $endpoint;
    
    $headers = [
        'Authorization: ' . OPENPIX_API_KEY,
        'Content-Type: application/json',
        'Accept: application/json',
        'User-Agent: KlubeCash/2.1'
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => false
    ]);
    
    if ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("Erro cURL: {$error}");
    }
    
    if (!$response) {
        throw new Exception('Resposta vazia da API OpenPix');
    }
    
    $result = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Resposta JSON inválida da OpenPix');
    }
    
    if ($httpCode >= 400) {
        $errorMsg = 'Erro HTTP ' . $httpCode;
        if (isset($result['error'])) {
            $errorMsg .= ': ' . $result['error'];
        } elseif (isset($result['message'])) {
            $errorMsg .= ': ' . $result['message'];
        }
        throw new Exception($errorMsg);
    }
    
    return $result;
}
?>