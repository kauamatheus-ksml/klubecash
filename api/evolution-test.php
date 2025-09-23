<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../utils/EvolutionWhatsApp.php';

header('Content-Type: application/json');

try {
    // Verificar se é uma requisição GET para teste
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Método não permitido']);
        exit;
    }
    
    // Teste básico de conectividade
    $testPhone = '5534988776655'; // Número de teste
    $testMessage = "🧪 *Teste Klube Cash*\n\nTeste de conectividade da Evolution API.\n\nData: " . date('d/m/Y H:i:s');
    
    $result = EvolutionWhatsApp::sendMessage($testPhone, $testMessage);
    
    echo json_encode([
        'status' => 'success',
        'test_result' => $result,
        'timestamp' => date('Y-m-d H:i:s'),
        'evolution_api_url' => EVOLUTION_API_URL,
        'instance' => EVOLUTION_INSTANCE
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>