<?php
/**
 * API para pagamentos de lojas
 */

header('Content-Type: application/json; charset=UTF-8');
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/AuthController.php';

// Verificar autenticação
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'loja') {
    http_response_code(401);
    echo json_encode(['status' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'payment_form':
            handlePaymentForm();
            break;
            
        case 'create_commission_payment':
            handleCreateCommissionPayment();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['status' => false, 'message' => 'Ação não reconhecida: ' . $action]);
            break;
    }
} catch (Exception $e) {
    error_log('Erro store-payment.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Erro interno']);
}

function handlePaymentForm() {
    $transactionIds = $_POST['transaction_ids'] ?? [];
    
    if (empty($transactionIds)) {
        echo json_encode(['status' => false, 'message' => 'Nenhuma transação selecionada']);
        return;
    }
    
    // Validar IDs como números
    $validIds = array_filter($transactionIds, 'is_numeric');
    
    if (empty($validIds)) {
        echo json_encode(['status' => false, 'message' => 'IDs de transação inválidos']);
        return;
    }
    
    // Redirecionar para formulário de pagamento
    $url = SITE_URL . '/store/pagamento?transactions=' . implode(',', $validIds);
    
    echo json_encode([
        'status' => true,
        'message' => 'Redirecionando para pagamento',
        'redirect_url' => $url
    ]);
}

function handleCreateCommissionPayment() {
    // Implementar criação de pagamento
    echo json_encode(['status' => false, 'message' => 'Funcionalidade em desenvolvimento']);
}
?>