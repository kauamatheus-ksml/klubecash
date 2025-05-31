<?php
// controllers/AjaxStoreController.php - Versão melhorada

// Iniciar sessão
session_start();

// Headers obrigatórios
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache, must-revalidate');

// Incluir dependências
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/AuthController.php';

// Verificar autenticação
if (!AuthController::isAuthenticated() || !AuthController::isAdmin()) {
    echo json_encode(['status' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'test_ajax':
            echo json_encode([
                'status' => true,
                'message' => 'AJAX funcionando perfeitamente!',
                'timestamp' => date('Y-m-d H:i:s'),
                'user_id' => $_SESSION['user_id'] ?? null,
                'user_type' => $_SESSION['user_type'] ?? null
            ]);
            break;
            
        case 'store_details':
            $storeId = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
            
            if ($storeId <= 0) {
                echo json_encode(['status' => false, 'message' => 'ID da loja inválido']);
                exit;
            }
            
            $db = Database::getConnection();
            
            // Buscar dados básicos da loja
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
                WHERE loja_id = ? AND status = 'aprovado'
            ");
            $statsStmt->execute([$storeId]);
            $statistics = $statsStmt->fetch(PDO::FETCH_ASSOC);
            
            // Buscar endereço se existir
            $addrStmt = $db->prepare("SELECT * FROM lojas_endereco WHERE loja_id = ?");
            $addrStmt->execute([$storeId]);
            $address = $addrStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($address) {
                $store['endereco'] = $address;
            }
            
            echo json_encode([
                'status' => true,
                'data' => [
                    'loja' => $store,
                    'estatisticas' => $statistics
                ]
            ]);
            break;

        case 'test_connection':
            $db = Database::getConnection();
            $stmt = $db->query("SELECT COUNT(*) as total FROM lojas");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'status' => true,
                'message' => 'Conexão funcionando!',
                'total_lojas' => $result['total'],
                'user_type' => $_SESSION['user_type'] ?? 'não definido',
                'user_id' => $_SESSION['user_id'] ?? 'não definido'
            ]);
            break;
            
        default:
            echo json_encode(['status' => false, 'message' => 'Ação não encontrada: ' . $action]);
    }
    
} catch (Exception $e) {
    error_log('Erro em AjaxStoreController: ' . $e->getMessage());
    echo json_encode([
        'status' => false,
        'message' => 'Erro interno: ' . $e->getMessage()
    ]);
}
?>