<?php
// api/whatsapp-saldo.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir dependências
require_once __DIR__ . '/../classes/SaldoConsulta.php';
require_once __DIR__ . '/../config/constants.php';

/**
 * API Endpoint para Consulta de Saldo via WhatsApp
 * 
 * Este endpoint recebe requisições do bot WhatsApp quando um usuário
 * envia a palavra "saldo" e retorna o saldo formatado do usuário
 */

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método não permitido. Use POST.'
    ]);
    exit;
}

try {
    // Ler dados JSON da requisição
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Log da requisição recebida
    error_log('WhatsApp Saldo API - Requisição recebida: ' . $input);
    
    // Validar chave secreta
    if (!isset($data['secret']) || $data['secret'] !== WHATSAPP_BOT_SECRET) {
        error_log('WhatsApp Saldo API - Chave secreta inválida');
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Acesso não autorizado'
        ]);
        exit;
    }
    
    // Validar telefone
    if (!isset($data['phone']) || empty($data['phone'])) {
        error_log('WhatsApp Saldo API - Telefone não informado');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Telefone é obrigatório'
        ]);
        exit;
    }
    
    // Instanciar classe de consulta
    $saldoConsulta = new SaldoConsulta();
    
    // Consultar saldo
    $resultado = $saldoConsulta->consultarSaldoPorTelefone($data['phone']);
    
    // Log do resultado
    error_log('WhatsApp Saldo API - Resultado: ' . json_encode($resultado));
    
    // Retornar resposta
    if ($resultado['success']) {
        echo json_encode([
            'success' => true,
            'message' => $resultado['message'],
            'user_found' => $resultado['user_found'],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        echo json_encode([
            'success' => true, // True para o bot processar a mensagem
            'message' => $resultado['message'],
            'user_found' => isset($resultado['user_found']) ? $resultado['user_found'] : false,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
} catch (Exception $e) {
    error_log('WhatsApp Saldo API - Erro: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno do servidor',
        'message' => 'Ocorreu um erro temporário. Tente novamente em alguns instantes.',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}