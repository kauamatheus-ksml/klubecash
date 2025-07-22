<?php
require_once '../config/database.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=u383946504_klubecash", "u383946504_klubecash", "Aaku_2004@");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if($method === 'GET' && $action === 'list') {
        $userId = $_GET['user_id'] ?? null;
        if(!$userId) {
            throw new Exception('user_id obrigatório');
        }
        
        $stmt = $pdo->prepare("
            SELECT id, titulo, mensagem, tipo, data_criacao, lida, link
            FROM notificacoes 
            WHERE usuario_id = ? 
            ORDER BY data_criacao DESC 
            LIMIT 50
        ");
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
    }
    elseif($method === 'PUT' && $action === 'mark_read') {
        $input = json_decode(file_get_contents('php://input'), true);
        $notificationId = $input['notification_id'] ?? null;
        $userId = $input['user_id'] ?? null;
        
        $stmt = $pdo->prepare("UPDATE notificacoes SET lida = 1, data_leitura = NOW() WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$notificationId, $userId]);
        
        echo json_encode(['success' => true]);
    }
    elseif($method === 'PUT' && $action === 'mark_all_read') {
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $input['user_id'] ?? null;
        
        $stmt = $pdo->prepare("UPDATE notificacoes SET lida = 1, data_leitura = NOW() WHERE usuario_id = ? AND lida = 0");
        $stmt->execute([$userId]);
        
        echo json_encode(['success' => true]);
    }
    
} catch(Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>