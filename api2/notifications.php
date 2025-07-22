<?php
require_once '../config/database.php';
require_once '../config/constants.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = Database::getInstance()->getConnection();
    
    switch($method) {
        case 'GET':
            if($action === 'list') {
                $userId = $_GET['user_id'] ?? null;
                if(!$userId) {
                    throw new Exception('user_id é obrigatório');
                }
                
                $stmt = $db->prepare("
                    SELECT id, titulo, mensagem, tipo, data_criacao, lida, link
                    FROM notificacoes 
                    WHERE usuario_id = ? 
                    ORDER BY data_criacao DESC 
                    LIMIT 50
                ");
                $stmt->execute([$userId]);
                $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Contar não lidas
                $stmtCount = $db->prepare("SELECT COUNT(*) as unread FROM notificacoes WHERE usuario_id = ? AND lida = 0");
                $stmtCount->execute([$userId]);
                $unreadCount = $stmtCount->fetch(PDO::FETCH_ASSOC)['unread'];
                
                echo json_encode([
                    'success' => true,
                    'notifications' => $notifications,
                    'unread_count' => (int)$unreadCount
                ]);
            }
            break;
            
        case 'PUT':
            if($action === 'mark_read') {
                $input = json_decode(file_get_contents('php://input'), true);
                $notificationId = $input['notification_id'] ?? null;
                $userId = $input['user_id'] ?? null;
                
                if(!$notificationId || !$userId) {
                    throw new Exception('notification_id e user_id são obrigatórios');
                }
                
                $stmt = $db->prepare("
                    UPDATE notificacoes 
                    SET lida = 1, data_leitura = NOW() 
                    WHERE id = ? AND usuario_id = ?
                ");
                $stmt->execute([$notificationId, $userId]);
                
                echo json_encode(['success' => true]);
            }
            elseif($action === 'mark_all_read') {
                $input = json_decode(file_get_contents('php://input'), true);
                $userId = $input['user_id'] ?? null;
                
                if(!$userId) {
                    throw new Exception('user_id é obrigatório');
                }
                
                $stmt = $db->prepare("
                    UPDATE notificacoes 
                    SET lida = 1, data_leitura = NOW() 
                    WHERE usuario_id = ? AND lida = 0
                ");
                $stmt->execute([$userId]);
                
                echo json_encode(['success' => true]);
            }
            break;
    }
} catch(Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>