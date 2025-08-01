<?php
/**
 * API para Notificação Automática de Cashback - Klube Cash
 * 
 * Esta API é chamada automaticamente pelo sistema quando uma nova transação
 * de cashback é registrada. Segue o mesmo padrão da API de consulta de saldo
 * que já está funcionando perfeitamente.
 * 
 * Endpoint: /api/cashback-notificacao.php
 * Método: POST
 * Autenticação: Chave secreta (mesmo sistema usado na consulta de saldo)
 * 
 * Exemplo de uso:
 * POST /api/cashback-notificacao.php
 * {
 *     "secret": "klube-cash-2024",
 *     "transaction_id": 123
 * }
 */

// === CONFIGURAÇÕES DE HEADERS ===
// Garantir que a resposta seja sempre JSON e permitir CORS se necessário
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Se for requisição OPTIONS (preflight), encerrar aqui
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// === VALIDAÇÃO DO MÉTODO ===
// Esta API só aceita POST para maior segurança
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido. Use POST.',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// === INCLUIR DEPENDÊNCIAS ===
// Carregar todas as classes necessárias
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../classes/CashbackNotifier.php';

try {
    // === VALIDAÇÃO DE ENTRADA ===
    // Obter dados JSON da requisição
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Verificar se o JSON é válido
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido: ' . json_last_error_msg());
    }
    
    // === AUTENTICAÇÃO ===
    // Verificar chave secreta (mesmo sistema da consulta de saldo)
    if (!isset($data['secret']) || $data['secret'] !== WHATSAPP_BOT_SECRET) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Chave de autenticação inválida',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    // === VALIDAÇÃO DOS PARÂMETROS ===
    // Verificar se o transaction_id foi fornecido
    if (!isset($data['transaction_id']) || !is_numeric($data['transaction_id'])) {
        throw new Exception('ID da transação é obrigatório e deve ser numérico');
    }
    
    $transactionId = intval($data['transaction_id']);
    
    // === VERIFICAÇÃO DE SISTEMA ATIVO ===
    // Permitir desabilitar notificações via constante se necessário
    if (!defined('CASHBACK_NOTIFICATIONS_ENABLED') || !CASHBACK_NOTIFICATIONS_ENABLED) {
        echo json_encode([
            'success' => false,
            'message' => 'Sistema de notificações está desabilitado',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    // === ENVIO DA NOTIFICAÇÃO ===
    // Instanciar a classe notificadora e enviar
    $notifier = new CashbackNotifier();
    $result = $notifier->notifyNewTransaction($transactionId);
    
    // === RESPOSTA DE SUCESSO ===
    // Retornar resultado detalhado para debug
    http_response_code($result['success'] ? 200 : 400);
    echo json_encode([
        'success' => $result['success'],
        'message' => $result['message'],
        'data' => [
            'transaction_id' => $result['transaction_id'],
            'message_type' => $result['message_type'] ?? null,
            'phone' => $result['phone'] ?? null
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    // === TRATAMENTO DE ERROS ===
    // Log do erro para debug
    error_log('Erro na API de notificação de cashback: ' . $e->getMessage());
    
    // Resposta de erro padronizada
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

// === LIMPEZA ===
// Garantir que a conexão do banco seja fechada
if (isset($notifier)) {
    unset($notifier);
}
?>