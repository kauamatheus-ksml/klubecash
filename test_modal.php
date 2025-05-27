<?php
// test_modal.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

try {
    echo json_encode([
        'status' => true,
        'message' => 'Endpoint de teste funcionando!',
        'timestamp' => date('Y-m-d H:i:s'),
        'session_user_id' => $_SESSION['user_id'] ?? 'não logado',
        'get_params' => $_GET,
        'requested_store_id' => $_GET['loja_id'] ?? 'não informado'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
?>