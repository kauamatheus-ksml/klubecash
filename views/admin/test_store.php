<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/constants.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    // Verificar sessão
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => false, 'message' => 'Não logado']);
        exit;
    }

    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        echo json_encode(['status' => false, 'message' => 'Não é admin']);
        exit;
    }

    // Testar conexão
    $db = Database::getConnection();
    
    // Buscar loja específica
    $storeId = 13; // ID da Sync Holding
    $stmt = $db->prepare("SELECT * FROM lojas WHERE id = ?");
    $stmt->execute([$storeId]);
    $store = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$store) {
        echo json_encode(['status' => false, 'message' => 'Loja não encontrada']);
        exit;
    }

    // Buscar transações
    $transStmt = $db->prepare("SELECT COUNT(*) as total FROM transacoes_cashback WHERE loja_id = ?");
    $transStmt->execute([$storeId]);
    $transCount = $transStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => true,
        'data' => [
            'loja' => $store,
            'transacoes' => $transCount['total'],
            'session' => [
                'user_id' => $_SESSION['user_id'],
                'user_type' => $_SESSION['user_type']
            ]
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => false, 
        'message' => 'Erro: ' . $e->getMessage(),
        'line' => $e->getLine()
    ]);
}
?>