<?php
// api/cashback-notificacao.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../classes/CashbackNotificacoes.php';
require_once __DIR__ . '/../config/constants.php';

/**
 * API Endpoint para enviar notificações de cashback registrado
 * Chamado automaticamente quando uma nova transação é criada
 */

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
    
    // Log da requisição
    error_log('Cashback Notificação API - Requisição: ' . $input);
    
    // Validar chave secreta
    if (!isset($data['secret']) || $data['secret'] !== WHATSAPP_BOT_SECRET) {
        error_log('Cashback Notificação API - Chave secreta inválida');
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Acesso não autorizado'
        ]);
        exit;
    }
    
    // Validar transacao_id
    if (!isset($data['transacao_id']) || !is_numeric($data['transacao_id'])) {
        error_log('Cashback Notificação API - ID da transação inválido');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'ID da transação é obrigatório e deve ser numérico'
        ]);
        exit;
    }
    
    // Instanciar classe de notificações
    $notificacoes = new CashbackNotificacoes();
    
    // Enviar notificação
    $resultado = $notificacoes->enviarNotificacaoCashback($data['transacao_id']);
    
    // Log do resultado
    error_log('Cashback Notificação API - Resultado: ' . json_encode($resultado));
    
    // Retornar resposta
    echo json_encode([
        'success' => $resultado['success'],
        'message' => $resultado['success'] ? 'Notificação enviada com sucesso' : 'Falha no envio',
        'details' => $resultado,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log('Cashback Notificação API - Erro: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno do servidor',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}