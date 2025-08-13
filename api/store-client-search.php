<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../controllers/AuthController.php';
require_once '../models/CashbackBalance.php';

session_start();

// Verificar autenticação
if (!AuthController::isAuthenticated()) {
    echo json_encode(['status' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

// Verificar se é loja
if (!AuthController::isStore()) {
    echo json_encode(['status' => false, 'message' => 'Acesso restrito a lojas']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// === AÇÃO PRINCIPAL: BUSCAR CLIENTE ===
if ($action === 'search_client') {
    $searchTerm = trim($input['search_term'] ?? '');
    $storeId = intval($input['store_id'] ?? 0);

    if (empty($searchTerm) || $storeId <= 0) {
        echo json_encode(['status' => false, 'message' => 'Termo de busca (Email, CPF ou Telefone) e ID da loja são obrigatórios']);
        exit;
    }

    // Limpar telefone e CPF de possíveis formatações
    $phoneSearch = preg_replace('/[^0-9]/', '', $searchTerm);
    $cpfSearch = preg_replace('/[^0-9]/', '', $searchTerm);

    try {
        $db = Database::getConnection();
        
        // Buscar cliente por email, CPF ou telefone
        $stmt = $db->prepare("
            SELECT id, nome, email, telefone, cpf, status, data_criacao, tipo_cliente, loja_criadora_id
            FROM usuarios
            WHERE (email = :searchTerm OR cpf = :cpfSearch OR telefone = :phoneSearch) 
            AND tipo = :tipo
        ");
        $stmt->bindParam(':searchTerm', $searchTerm);
        $stmt->bindParam(':cpfSearch', $cpfSearch);
        $stmt->bindParam(':phoneSearch', $phoneSearch);
        $tipo = USER_TYPE_CLIENT;
        $stmt->bindParam(':tipo', $tipo);
        $stmt->execute();
        
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$client) {
            // Cliente não encontrado - retornar opção de criar visitante
            echo json_encode([
                'status' => false,
                'code' => CLIENT_SEARCH_NOT_FOUND,
                'message' => 'Cliente não encontrado. Verifique se o email, CPF ou telefone está correto e se o cliente está cadastrado no Klube Cash.',
                'can_create_visitor' => true,
                'search_term' => $searchTerm,
                'search_type' => determineSearchType($searchTerm)
            ]);
            exit;
        }
        
        // Verificar se é cliente visitante de outra loja
        if ($client['tipo_cliente'] === CLIENT_TYPE_VISITOR && $client['loja_criadora_id'] != $storeId) {
            echo json_encode([
                'status' => false,
                'code' => CLIENT_SEARCH_NOT_FOUND,
                'message' => 'Este é um cliente visitante de outra loja. Cada loja mantém seus próprios clientes visitantes.',
                'can_create_visitor' => true,
                'search_term' => $searchTerm,
                'search_type' => determineSearchType($searchTerm)
            ]);
            exit;
        }
        
        if ($client['status'] !== USER_ACTIVE) {
            echo json_encode([
                'status' => false,
                'code' => CLIENT_SEARCH_INACTIVE,
                'message' => 'Cliente encontrado, mas sua conta não está ativa.'
            ]);
            exit;
        }
        
        // Obter saldo do cliente na loja
        $balanceModel = new CashbackBalance();
        $saldo = $balanceModel->getStoreBalance($client['id'], $storeId);
        
        // Obter estatísticas do cliente na loja
        $statsStmt = $db->prepare("
            SELECT 
                COUNT(*) as total_compras,
                SUM(valor_total) as total_gasto,
                SUM(valor_cliente) as total_cashback_recebido,
                MAX(data_transacao) as ultima_compra
            FROM transacoes_cashback 
            WHERE usuario_id = :usuario_id AND loja_id = :loja_id AND status = 'aprovado'
        ");
        $statsStmt->bindParam(':usuario_id', $client['id']);
        $statsStmt->bindParam(':loja_id', $storeId);
        $statsStmt->execute();
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        // Determinar o tipo de exibição do cliente
        $clientType = $client['tipo_cliente'] === CLIENT_TYPE_VISITOR ? 'visitante' : 'cadastrado';
        $clientTypeLabel = $client['tipo_cliente'] === CLIENT_TYPE_VISITOR ? 'Cliente Visitante' : 'Cliente Cadastrado';
        
        echo json_encode([
            'status' => true,
            'code' => CLIENT_SEARCH_FOUND,
            'message' => 'Cliente encontrado com sucesso',
            'data' => [
                'id' => $client['id'],
                'nome' => $client['nome'],
                'email' => $client['email'],
                'telefone' => $client['telefone'],
                'cpf' => $client['cpf'] ?? null,
                'status' => $client['status'],
                'tipo_cliente' => $clientType,
                'tipo_cliente_label' => $clientTypeLabel,
                'data_cadastro' => date('d/m/Y', strtotime($client['data_criacao'])),
                'saldo' => $saldo,
                'estatisticas' => [
                    'total_compras' => $stats['total_compras'] ?? 0,
                    'total_gasto' => $stats['total_gasto'] ?? 0,
                    'total_cashback_recebido' => $stats['total_cashback_recebido'] ?? 0,
                    'ultima_compra' => $stats['ultima_compra'] ? date('d/m/Y', strtotime($stats['ultima_compra'])) : null
                ]
            ]
        ]);
        
    } catch (Exception $e) {
        error_log('Erro ao buscar cliente: ' . $e->getMessage());
        echo json_encode([
            'status' => false, 
            'message' => 'Erro interno do servidor. Tente novamente.'
        ]);
    }
}

// === NOVA AÇÃO: CRIAR CLIENTE VISITANTE ===
elseif ($action === 'create_visitor_client') {
    $nome = trim($input['nome'] ?? '');
    $telefone = preg_replace('/[^0-9]/', '', $input['telefone'] ?? '');
    $storeId = intval($input['store_id'] ?? 0);

    // Validações
    if (empty($nome) || strlen($nome) < 2) {
        echo json_encode(['status' => false, 'message' => 'Nome é obrigatório e deve ter pelo menos 2 caracteres']);
        exit;
    }

    if (empty($telefone) || strlen($telefone) < VISITOR_PHONE_MIN_LENGTH) {
        echo json_encode(['status' => false, 'message' => 'Telefone é obrigatório e deve ter pelo menos 10 dígitos']);
        exit;
    }

    if ($storeId <= 0) {
        echo json_encode(['status' => false, 'message' => 'ID da loja é obrigatório']);
        exit;
    }

    try {
        $db = Database::getConnection();
        
        // Verificar se já existe cliente visitante com este telefone nesta loja
        $checkStmt = $db->prepare("
            SELECT id FROM usuarios 
            WHERE telefone = :telefone 
            AND tipo = :tipo 
            AND tipo_cliente = :tipo_cliente 
            AND loja_criadora_id = :loja_id
        ");
        $checkStmt->bindParam(':telefone', $telefone);
        $tipo = USER_TYPE_CLIENT;
        $checkStmt->bindParam(':tipo', $tipo);
        $tipoCliente = CLIENT_TYPE_VISITOR;
        $checkStmt->bindParam(':tipo_cliente', $tipoCliente);
        $checkStmt->bindParam(':loja_id', $storeId);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            echo json_encode(['status' => false, 'message' => MSG_VISITOR_EXISTS]);
            exit;
        }
        
        // Criar cliente visitante
        $insertStmt = $db->prepare("
            INSERT INTO usuarios (nome, telefone, tipo, tipo_cliente, loja_criadora_id, status, data_criacao)
            VALUES (:nome, :telefone, :tipo, :tipo_cliente, :loja_id, :status, NOW())
        ");
        $insertStmt->bindParam(':nome', $nome);
        $insertStmt->bindParam(':telefone', $telefone);
        $insertStmt->bindParam(':tipo', $tipo);
        $insertStmt->bindParam(':tipo_cliente', $tipoCliente);
        $insertStmt->bindParam(':loja_id', $storeId);
        $status = USER_ACTIVE;
        $insertStmt->bindParam(':status', $status);
        $insertStmt->execute();
        
        $clientId = $db->lastInsertId();
        
        echo json_encode([
            'status' => true,
            'message' => MSG_VISITOR_CREATED,
            'data' => [
                'id' => $clientId,
                'nome' => $nome,
                'telefone' => $telefone,
                'tipo_cliente' => 'visitante',
                'tipo_cliente_label' => 'Cliente Visitante',
                'saldo' => 0,
                'data_cadastro' => date('d/m/Y'),
                'estatisticas' => [
                    'total_compras' => 0,
                    'total_gasto' => 0,
                    'total_cashback_recebido' => 0,
                    'ultima_compra' => null
                ]
            ]
        ]);
        
    } catch (Exception $e) {
        error_log('Erro ao criar cliente visitante: ' . $e->getMessage());
        echo json_encode([
            'status' => false, 
            'message' => 'Erro interno do servidor. Tente novamente.'
        ]);
    }
}

else {
    echo json_encode(['status' => false, 'message' => 'Ação inválida']);
    exit;
}

// === FUNÇÃO AUXILIAR ===
function determineSearchType($searchTerm) {
    $cleaned = preg_replace('/[^0-9]/', '', $searchTerm);
    
    if (filter_var($searchTerm, FILTER_VALIDATE_EMAIL)) {
        return 'email';
    } elseif (strlen($cleaned) == 11 && substr($cleaned, 0, 1) != '0') {
        return 'telefone';
    } elseif (strlen($cleaned) == 11) {
        return 'cpf';
    } else {
        return 'unknown';
    }
}
?>