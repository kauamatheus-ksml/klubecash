<?php
// api/store-client-search.php
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

if ($action !== 'search_client') {
    echo json_encode(['status' => false, 'message' => 'Ação inválida']);
    exit;
}

$searchTerm = trim($input['search_term'] ?? ''); // Modificado de 'email' para 'search_term'
$storeId = intval($input['store_id'] ?? 0);

if (empty($searchTerm) || $storeId <= 0) { // Modificado de empty($email)
    echo json_encode(['status' => false, 'message' => 'Termo de busca (Email ou CPF) e ID da loja são obrigatórios']);
    exit;
}

// Limpar CPF de possíveis formatações (pontos, traços)
$cpfSearch = preg_replace('/[^0-9]/', '', $searchTerm);

try {
    $db = Database::getConnection();
    
    // Buscar cliente por email
    $stmt = $db->prepare("
        SELECT id, nome, email, status, data_criacao, cpf -- Adicionado cpf ao SELECT
        FROM usuarios
        WHERE (email = :searchTerm OR cpf = :cpfSearch) AND tipo = :tipo
    ");
    $stmt->bindParam(':searchTerm', $searchTerm); // Para busca de email
    $stmt->bindParam(':cpfSearch', $cpfSearch);     // Para busca de CPF (já limpo)
    $tipo = USER_TYPE_CLIENT;
    $stmt->bindParam(':tipo', $tipo);
    $stmt->execute();
    
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
        echo json_encode([
            'status' => false, 
            'message' => 'Cliente não encontrado. Verifique se o email está correto e se o cliente está cadastrado no Klube Cash.'
        ]);
        exit;
    }
    
    if ($client['status'] !== USER_ACTIVE) {
        echo json_encode([
            'status' => false, 
            'message' => 'Cliente encontrado, mas sua conta não está ativa.'
        ]);
        exit;
    }
    
    // Obter saldo do cliente na loja
    $balanceModel = new CashbackBalance();
    $saldo = $balanceModel->getStoreBalance($client['id'], $storeId);
    
    // Debug log para verificar se o saldo está sendo obtido corretamente
    error_log("DEBUG: Obtendo saldo para cliente {$client['id']} na loja {$storeId}: R$ {$saldo}");
    
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
    
    echo json_encode([
        'status' => true,
        'message' => 'Cliente encontrado com sucesso',
        'data' => [
            'id' => $client['id'],
            'nome' => $client['nome'],
            'email' => $client['email'],
            'cpf' => $client['cpf'] ?? null, // Adicionar esta linha (se 'cpf' foi selecionado na query)
            'status' => $client['status'],
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
?>