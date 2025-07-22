<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: *');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=u383946504_klubecash", "u383946504_klubecash", "Aaku_2004@");
    
    $userId = $_GET['user_id'] ?? null;
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'user_id required']);
        exit();
    }
    
    $stmt = $pdo->prepare("SELECT id, titulo, mensagem, tipo, data_criacao, lida FROM notificacoes WHERE usuario_id = ? ORDER BY data_criacao DESC LIMIT 50");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmtCount = $pdo->prepare("SELECT COUNT(*) as unread FROM notificacoes WHERE usuario_id = ? AND lida = 0");
    $stmtCount->execute([$userId]);
    $unreadCount = $stmtCount->fetch(PDO::FETCH_ASSOC)['unread'];
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => (int)$unreadCount
    ]);
    
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>