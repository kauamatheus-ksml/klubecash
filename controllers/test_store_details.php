<?php
// controllers/test_store_details.php
session_start();

// Incluir arquivos necessários
require_once '../config/database.php';
require_once '../config/constants.php';
require_once 'AuthController.php';

// Headers para JSON
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

// Verificar se é admin
if ($_SESSION['user_type'] !== 'admin') {
    echo json_encode(['status' => false, 'message' => 'Acesso restrito a administradores']);
    exit;
}

try {
    $storeId = isset($_POST['store_id']) ? intval($_POST['store_id']) : 13; // ID da loja Sync Holding
    
    $db = Database::getConnection();
    
    // Teste simples - buscar dados básicos da loja
    $stmt = $db->prepare("SELECT * FROM lojas WHERE id = ?");
    $stmt->execute([$storeId]);
    $store = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$store) {
        echo json_encode(['status' => false, 'message' => 'Loja não encontrada']);
        exit;
    }
    
    // Buscar estatísticas básicas
    $statsStmt = $db->prepare("
        SELECT 
            COUNT(*) as total_transacoes,
            COALESCE(SUM(valor_total), 0) as total_vendas,
            COALESCE(SUM(valor_cliente), 0) as total_cashback
        FROM transacoes_cashback
        WHERE loja_id = ?
    ");
    $statsStmt->execute([$storeId]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => true,
        'message' => 'Teste bem-sucedido',
        'data' => [
            'loja' => $store,
            'estatisticas' => $stats,
            'estatisticas_saldo' => [
                'total_saldo_clientes' => 0,
                'clientes_com_saldo' => 0,
                'total_saldo_usado' => 0,
                'total_transacoes' => $stats['total_transacoes'],
                'transacoes_com_saldo' => 0
            ],
            'transacoes' => []
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => 'Erro: ' . $e->getMessage(),
        'line' => $e->getLine(),
        'file' => $e->getFile()
    ]);
}
?>