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
 * VERSÃO ATUALIZADA COM MENU DINÂMICO
 * Agora retorna tipo de cliente para menu correto
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
    
    // NOVA VERSÃO: Buscar dados completos para API
    $resultado = $saldoConsulta->buscarDadosParaAPI($data['phone']);
    
    // Determinar tipo de cliente para o menu dinâmico
    $tipoCliente = 'unknown';
    $userName = '';
    
    if ($resultado['user_found'] && $resultado['user_data']) {
        $userData = $resultado['user_data'];
        $tipoCliente = $saldoConsulta->determinarTipoCliente($userData);
        $userName = $userData['nome'] ?? '';
        
        error_log("WhatsApp Saldo API - DEBUG CRÍTICO:");
        error_log("- Telefone: " . $data['phone']);
        error_log("- Usuário: {$userName}");
        error_log("- Email: " . ($userData['email'] ?: 'VAZIO'));
        error_log("- Senha: " . (empty($userData['senha_hash']) ? 'VAZIO' : 'PREENCHIDO'));
        error_log("- Tipo Cliente Determinado: {$tipoCliente}");
    } else {
        error_log("WhatsApp Saldo API - USUÁRIO NÃO ENCONTRADO: " . $data['phone']);
    }
    
    // Log do resultado
    error_log('WhatsApp Saldo API - Resultado final: ' . json_encode([
        'success' => $resultado['success'],
        'user_found' => $resultado['user_found'],
        'client_type' => $tipoCliente
    ]));
    
    // Retornar resposta com tipo de cliente
    if ($resultado['success']) {
        echo json_encode([
            'success' => true,
            'message' => $resultado['message'],
            'user_found' => $resultado['user_found'],
            'client_type' => $tipoCliente, // NOVO: Tipo de cliente para menu dinâmico
            'user_name' => $userName, // NOVO: Nome do usuário
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        echo json_encode([
            'success' => true, // True para o bot processar a mensagem
            'message' => $resultado['message'],
            'user_found' => $resultado['user_found'],
            'client_type' => $tipoCliente, // NOVO: Tipo de cliente
            'user_name' => $userName, // NOVO: Nome do usuário
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
} catch (Exception $e) {
    error_log('WhatsApp Saldo API - Erro CRÍTICO: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno do servidor',
        'message' => 'Ocorreu um erro temporário. Tente novamente em alguns instantes.',
        'user_found' => false,
        'client_type' => 'unknown', // NOVO: Tipo unknown em caso de erro
        'user_name' => '', // NOVO: Nome vazio em caso de erro
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>