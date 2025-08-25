<?php
// index_debug.php - Versão simplificada para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log do que está acontecendo
file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Request iniciado\n", FILE_APPEND);
file_put_contents('debug.log', "URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n", FILE_APPEND);
file_put_contents('debug.log', "Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A') . "\n", FILE_APPEND);

header('Content-Type: application/json');

try {
    // Testar includes básicos
    require_once '../../config/database.php';
    require_once 'config/api_config.php';
    require_once 'core/Response.php';
    
    file_put_contents('debug.log', "Includes OK\n", FILE_APPEND);
    
    // Verificar se é uma rota de auth (sem API key)
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $path = str_replace('/klubecash/api-external/v1', '', $path);
    
    file_put_contents('debug.log', "Path processado: $path\n", FILE_APPEND);
    
    // Rotas simples sem autenticação
    if ($path === '/auth/info' || $path === '' || $path === '/') {
        Response::success([
            'api_name' => 'KlubeCash External API',
            'version' => 'v1',
            'status' => 'running',
            'timestamp' => date('c'),
            'debug' => true,
            'path_received' => $path,
            'original_uri' => $_SERVER['REQUEST_URI'] ?? 'N/A'
        ], 'API Debug Mode Active');
        
    } else if ($path === '/auth/health') {
        // Testar conexão com banco
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT 1");
        $stmt->execute();
        
        Response::success([
            'status' => 'healthy',
            'database' => 'connected',
            'timestamp' => date('c'),
            'debug' => true
        ], 'Health Check OK');
        
    } else {
        // Outras rotas precisam de API key
        Response::error('Route not implemented in debug mode: ' . $path, 404);
    }
    
} catch (Exception $e) {
    file_put_contents('debug.log', "ERRO: " . $e->getMessage() . "\n", FILE_APPEND);
    file_put_contents('debug.log', "Stack: " . $e->getTraceAsString() . "\n", FILE_APPEND);
    
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'message' => 'Debug Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT);
}
?>