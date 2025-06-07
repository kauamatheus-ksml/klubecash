<?php
// controllers/test-status.php
header('Content-Type: application/json; charset=UTF-8');

// Log da requisição
error_log('Teste de status recebido');
error_log('POST: ' . print_r($_POST, true));
error_log('REQUEST_METHOD: ' . $_SERVER['REQUEST_METHOD']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo json_encode([
        'status' => true,
        'message' => 'Teste bem-sucedido',
        'received_data' => $_POST,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} else {
    echo json_encode([
        'status' => false,
        'message' => 'Método não permitido',
        'method' => $_SERVER['REQUEST_METHOD']
    ]);
}
?>