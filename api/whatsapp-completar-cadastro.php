<?php
// api/whatsapp-completar-cadastro.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/constants.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método não permitido. Use POST.'
    ]);
    exit;
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    error_log('WhatsApp Completar Cadastro API - Requisição: ' . $input);
    
    // Validar chave secreta
    if (!isset($data['secret']) || $data['secret'] !== WHATSAPP_BOT_SECRET) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Acesso não autorizado'
        ]);
        exit;
    }
    
    // Validar telefone
    if (!isset($data['phone']) || empty($data['phone'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Telefone é obrigatório'
        ]);
        exit;
    }
    
    // Retornar mensagem de completar cadastro
    echo json_encode([
        'success' => true,
        'message' => WHATSAPP_COMPLETAR_CADASTRO_MESSAGE,
        'user_found' => true,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log('WhatsApp Completar Cadastro API - Erro: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno do servidor',
        'message' => 'Ocorreu um erro temporário. Tente novamente em alguns instantes.',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>