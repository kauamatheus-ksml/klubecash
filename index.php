<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once '../../config/database.php';
require_once 'config/api_config.php';
require_once 'core/Response.php';
require_once 'core/ApiException.php';
require_once 'models/ApiKey.php';

date_default_timezone_set('America/Sao_Paulo');

try {
    // Pegar path da requisição - versão melhorada
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $path = parse_url($requestUri, PHP_URL_PATH);
    
    // Remover múltiplas possibilidades de base path
    $possibleBasePaths = [
        '/api-external/v1',
        '/klubecash/api-external/v1', 
        'api-external/v1'
    ];
    
    foreach ($possibleBasePaths as $basePath) {
        if (strpos($path, $basePath) !== false) {
            $path = substr($path, strpos($path, $basePath) + strlen($basePath));
            break;
        }
    }
    
    // Limpar path - remover barras extras
    $path = trim($path, '/');
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Log detalhado para debug
    error_log("=== API REQUEST ===");
    error_log("Original URI: " . $requestUri);
    error_log("Parsed Path: '$path'");
    error_log("Method: $method");
    error_log("==================");
    
    // Rotas públicas (sem API Key)
    if ($path === '' || $path === 'auth/info') {
        Response::success([
            'api_name' => 'KlubeCash External API',
            'version' => API_VERSION,
            'base_url' => 'https://klubecash.com/api-external/v1',
            'documentation_url' => 'https://klubecash.com/api-external/v1/docs',
            'requires_api_key' => true,
            'rate_limits' => [
                'default_per_minute' => RATE_LIMIT_REQUESTS_PER_MINUTE,
                'default_per_hour' => RATE_LIMIT_REQUESTS_PER_HOUR
            ],
            'debug_info' => [
                'parsed_path' => $path,
                'original_uri' => $requestUri
            ]
        ]);
        
    } elseif ($path === 'auth/health') {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT 1");
        $stmt->execute();
        
        Response::success([
            'status' => 'healthy',
            'database' => 'connected',
            'timestamp' => date('c'),
            'uptime' => sys_getloadavg()[0] ?? 'N/A'
        ]);
    }
    
    // Todas as outras rotas precisam de API Key
    $apiKey = null;
    
    // Extrair API Key de múltiplas fontes
    $headers = getallheaders();
    if ($headers && isset($headers['X-API-Key'])) {
        $apiKey = trim($headers['X-API-Key']);
    } elseif ($headers && isset($headers['x-api-key'])) {
        $apiKey = trim($headers['x-api-key']);
    } elseif (isset($_GET['api_key'])) {
        $apiKey = trim($_GET['api_key']);
    }
    
    error_log("API Key received: " . ($apiKey ? 'YES' : 'NO'));
    
    if (!$apiKey) {
        Response::unauthorized('API Key is required. Provide it in X-API-Key header.');
    }
    
    // Validar API Key
    $apiKeyModel = new ApiKey();
    $keyData = $apiKeyModel->validateApiKey($apiKey);
    
    if (!$keyData) {
        error_log("API Key validation failed for: $apiKey");
        Response::unauthorized('Invalid or expired API Key');
    }
    
    error_log("API Key validated successfully for: " . $keyData['partner_name']);
    
    // Rotas protegidas - melhor matching
    if ($path === 'users' && $method === 'GET') {
        error_log("Processing users route");
        
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT id, nome as name, email, tipo as type, status, 
                   data_criacao as created_at 
            FROM usuarios 
            ORDER BY data_criacao DESC 
            LIMIT 10
        ");
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        error_log("Found " . count($users) . " users");
        Response::success($users, 'Users retrieved successfully');
        
    } elseif ($path === 'stores' && $method === 'GET') {
        error_log("Processing stores route");
        
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT id, nome_fantasia as trade_name, razao_social as legal_name,
                   cnpj, email, status, data_cadastro as created_at
            FROM lojas 
            ORDER BY data_cadastro DESC 
            LIMIT 10
        ");
        $stmt->execute();
        $stores = $stmt->fetchAll();
        
        error_log("Found " . count($stores) . " stores");
        Response::success($stores, 'Stores retrieved successfully');
        
    } elseif ($path === 'cashback/calculate' && $method === 'POST') {
        error_log("Processing cashback calculate route");
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['store_id']) || !isset($input['amount'])) {
            Response::error('store_id and amount are required', 400);
        }
        
        $storeId = intval($input['store_id']);
        $amount = floatval($input['amount']);
        
        if ($storeId <= 0 || $amount <= 0) {
            Response::error('Invalid store_id or amount', 400);
        }
        
        // Buscar configurações da loja
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT porcentagem_cashback FROM lojas WHERE id = ? AND status = 'aprovado'");
        $stmt->execute([$storeId]);
        $store = $stmt->fetch();
        
        if (!$store) {
            Response::error('Store not found or not approved', 404);
        }
        
        // Buscar configurações de distribuição
        $stmt = $db->prepare("SELECT porcentagem_cliente, porcentagem_admin, porcentagem_loja FROM configuracoes_cashback LIMIT 1");
        $stmt->execute();
        $config = $stmt->fetch();
        
        if (!$config) {
            Response::error('Cashback configuration not found', 500);
        }
        
        // Calcular
        $cashbackPercentage = floatval($store['porcentagem_cashback']);
        $totalCashback = ($amount * $cashbackPercentage) / 100;
        
        $clientAmount = ($totalCashback * $config['porcentagem_cliente']) / 100;
        $adminAmount = ($totalCashback * $config['porcentagem_admin']) / 100;
        $storeAmount = ($totalCashback * $config['porcentagem_loja']) / 100;
        
        Response::success([
            'store_id' => $storeId,
            'purchase_amount' => $amount,
            'store_cashback_percentage' => $cashbackPercentage,
            'cashback_calculation' => [
                'total_cashback' => $totalCashback,
                'client_receives' => $clientAmount,
                'admin_receives' => $adminAmount,
                'store_receives' => $storeAmount
            ]
        ]);
        
    } else {
        // Log da rota não encontrada para debug
        error_log("Route not found - Path: '$path', Method: $method");
        error_log("Available routes: auth/info, auth/health, users, stores, cashback/calculate");
        
        Response::error("Route not found: $method /$path", 404, [
            'available_routes' => [
                'GET /auth/info (no auth)',
                'GET /auth/health (no auth)', 
                'GET /users (with API key)',
                'GET /stores (with API key)',
                'POST /cashback/calculate (with API key)'
            ],
            'debug' => [
                'received_path' => $path,
                'received_method' => $method,
                'original_uri' => $requestUri
            ]
        ]);
    }
    
} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    Response::error('Internal server error: ' . $e->getMessage(), 500);
}
?>