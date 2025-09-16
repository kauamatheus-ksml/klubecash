<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../controllers/AuthController.php';
require_once '../controllers/TransactionController.php';

header('Content-Type: application/json; charset=UTF-8');
session_start();

if (!AuthController::isAuthenticated() || !AuthController::isStore()) {
    echo json_encode(['status' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log dos dados recebidos para debug
    error_log("store-payment.php - Dados recebidos: " . print_r($_POST, true));
    
    // Verificar se o valor_total está presente e é válido
    if (!isset($_POST['valor_total']) || !is_numeric($_POST['valor_total']) || floatval($_POST['valor_total']) <= 0) {
        echo json_encode([
            'status' => false, 
            'message' => 'Valor total inválido ou não informado'
        ]);
        exit;
    }
    
    // Verificar se as transações foram informadas
    if (!isset($_POST['transacoes']) || empty($_POST['transacoes'])) {
        echo json_encode([
            'status' => false, 
            'message' => 'Nenhuma transação selecionada'
        ]);
        exit;
    }
    
    // Log específico do valor total
    error_log("store-payment.php - Valor total informado: " . $_POST['valor_total']);
    
    // Chamar o TransactionController com os dados corretos
    $result = TransactionController::registerPayment($_POST);
    
    // Log do resultado
    error_log("store-payment.php - Resultado: " . print_r($result, true));
    
    echo json_encode($result);
} else {
    echo json_encode(['status' => false, 'message' => 'Método não permitido']);
}
?>