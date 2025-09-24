<?php
// api/whatsapp-saldo-loja.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../classes/SaldoConsulta.php';
require_once __DIR__ . '/../config/constants.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido. Use POST.']);
    exit;
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Validar chave secreta
    if (!isset($data['secret']) || $data['secret'] !== WHATSAPP_BOT_SECRET) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Acesso não autorizado']);
        exit;
    }
    
    // Validar dados
    if (!isset($data['phone']) || empty($data['phone'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Telefone é obrigatório']);
        exit;
    }
    
    if (!isset($data['loja']) || empty($data['loja'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Identificação da loja é obrigatória']);
        exit;
    }
    
    // Instanciar classe de consulta
    $saldoConsulta = new SaldoConsulta();
    
    // Consultar saldo da loja específica
    $resultado = $saldoConsulta->consultarSaldoLoja($data['phone'], $data['loja']);
    
    // Retornar resposta
    echo json_encode([
        'success' => $resultado['success'],
        'message' => $resultado['message'],
        'user_found' => $resultado['user_found'] ?? false,
        'send_image' => $resultado['send_image'] ?? false,
        'image_url' => $resultado['image_url'] ?? null,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log('WhatsApp Saldo Loja API - Erro: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno do servidor',
        'message' => 'Ocorreu um erro temporário. Tente novamente em alguns instantes.',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>