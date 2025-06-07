<?php
// controllers/store_actions.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/TransactionController.php';
require_once __DIR__ . '/StoreController.php';

// Log para debug
error_log('STORE_ACTIONS: Iniciando processamento');
error_log('STORE_ACTIONS: REQUEST_METHOD: ' . $_SERVER['REQUEST_METHOD']);
error_log('STORE_ACTIONS: REQUEST_URI: ' . $_SERVER['REQUEST_URI']);
error_log('STORE_ACTIONS: GET params: ' . print_r($_GET, true));
error_log('STORE_ACTIONS: POST params: ' . print_r($_POST, true));

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definir header JSON
header('Content-Type: application/json');

try {
    // Verificar se o usuário está autenticado e é loja
    if (!AuthController::isAuthenticated() || !AuthController::isStore()) {
        error_log('STORE_ACTIONS: Usuário não autenticado ou não é loja');
        http_response_code(403);
        echo json_encode(['status' => false, 'message' => 'Acesso negado.']);
        exit;
    }

    $userId = AuthController::getCurrentUserId();
    error_log('STORE_ACTIONS: User ID: ' . $userId);

    // Verificar se há uma ação solicitada
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    error_log('STORE_ACTIONS: Action solicitada: ' . $action);

    if (empty($action)) {
        echo json_encode(['status' => false, 'message' => 'Nenhuma ação especificada']);
        exit;
    }

    // Processar a ação
    switch ($action) {
        case 'payment_form':
            // Retornar dados para o formulário de pagamento PIX
            $lojaId = isset($_GET['loja_id']) ? intval($_GET['loja_id']) : 0;
            $transacoes = isset($_POST['transacoes']) ? $_POST['transacoes'] : [];
            
            if ($lojaId <= 0) {
                echo json_encode(['status' => false, 'message' => 'ID da loja inválido']);
                exit;
            }
            
            // Verificar se a loja pertence ao usuário atual
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT id, nome_fantasia FROM lojas WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$lojaId, $userId]);
            $loja = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$loja) {
                echo json_encode(['status' => false, 'message' => 'Loja não encontrada ou acesso não autorizado']);
                exit;
            }
            
            // Retornar dados do formulário
            echo json_encode([
                'status' => true,
                'data' => [
                    'loja_id' => $lojaId,
                    'loja_nome' => $loja['nome_fantasia'],
                    'transacoes' => $transacoes,
                    'form_action' => SITE_URL . '/store/pagamento-pix'
                ]
            ]);
            break;
            
        case 'register_transaction':
            // Registrar uma nova transação
            $data = $_POST;
            $result = TransactionController::registerTransaction($data);
            echo json_encode($result);
            break;
            
        case 'process_batch':
            // Processar transações em lote
            $file = $_FILES['arquivo'] ?? null;
            $storeId = isset($_POST['loja_id']) ? intval($_POST['loja_id']) : 0;
            $result = TransactionController::processBatchTransactions($file, $storeId);
            echo json_encode($result);
            break;
            
        case 'pending_transactions':
            // Obter transações pendentes
            $storeId = isset($_POST['loja_id']) ? intval($_POST['loja_id']) : 0;
            $filters = $_POST['filters'] ?? [];
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $result = TransactionController::getPendingTransactions($storeId, $filters, $page);
            echo json_encode($result);
            break;
            
        case 'register_payment':
            // Registrar pagamento de comissões
            $data = $_POST;
            $result = TransactionController::registerPayment($data);
            echo json_encode($result);
            break;
            
        case 'payment_history':
            // Obter histórico de pagamentos
            $storeId = isset($_POST['loja_id']) ? intval($_POST['loja_id']) : 0;
            $filters = $_POST['filters'] ?? [];
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $result = TransactionController::getPaymentHistory($storeId, $filters, $page);
            echo json_encode($result);
            break;
            
        case 'store_details':
            // Obter detalhes da loja
            $storeId = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
            
            if ($storeId <= 0) {
                echo json_encode(['status' => false, 'message' => 'ID da loja inválido']);
                exit;
            }
            
            $result = StoreController::getStoreDetails($storeId);
            echo json_encode($result);
            break;
            
        case 'update_profile':
            // Atualizar perfil da loja
            $data = $_POST;
            $result = StoreController::updateStoreProfile($userId, $data);
            echo json_encode($result);
            break;
            
        default:
            error_log('STORE_ACTIONS: Ação não reconhecida: ' . $action);
            echo json_encode(['status' => false, 'message' => 'Ação não encontrada: ' . $action]);
            break;
    }

} catch (Exception $e) {
    error_log('STORE_ACTIONS: Exceção capturada: ' . $e->getMessage());
    error_log('STORE_ACTIONS: Stack trace: ' . $e->getTraceAsString());
    echo json_encode(['status' => false, 'message' => 'Erro interno do servidor: ' . $e->getMessage()]);
}
?>