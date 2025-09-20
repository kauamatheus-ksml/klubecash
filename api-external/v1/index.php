<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once '../../config/database.php';
require_once 'config/api_config.php';
require_once 'core/Response.php';
require_once 'core/ApiException.php';
require_once 'models/SimpleApiKey.php';

date_default_timezone_set('America/Sao_Paulo');

function logDebug($message) {
    error_log("[API_DEBUG] $message");
}

try {
    // Capturar todas as informações de path possíveis
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $pathInfo = $_SERVER['PATH_INFO'] ?? '';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];
    
    logDebug("=== REQUEST DEBUG ===");
    logDebug("REQUEST_URI: $requestUri");
    logDebug("PATH_INFO: $pathInfo");
    logDebug("SCRIPT_NAME: $scriptName");
    logDebug("METHOD: $method");
    
    // Tentar múltiplas formas de extrair o path
    $path = '';
    
    // 1. Tentar PATH_INFO primeiro (LiteSpeed)
    if (!empty($pathInfo)) {
        $path = trim($pathInfo, '/');
        logDebug("Using PATH_INFO: $path");
    }
    // 2. Senão, extrair do REQUEST_URI
    else {
        $urlPath = parse_url($requestUri, PHP_URL_PATH);
        
        // Remover script name se presente
        if (strpos($urlPath, '/index.php') !== false) {
            $urlPath = str_replace('/index.php', '', $urlPath);
        }
        
        // Remover base paths
        $basePaths = [
            '/api-external/v1',
            '/klubecash/api-external/v1'
        ];
        
        foreach ($basePaths as $basePath) {
            if (strpos($urlPath, $basePath) !== false) {
                $urlPath = substr($urlPath, strpos($urlPath, $basePath) + strlen($basePath));
                break;
            }
        }
        
        $path = trim($urlPath, '/');
        logDebug("Using REQUEST_URI extraction: $path");
    }
    
    logDebug("Final path: '$path'");
    logDebug("====================");
    
    // Função helper para responder
    function respondWithSuccess($data, $message = 'Success') {
        Response::success($data, $message);
    }
    
    // Rotas públicas (sem API Key)
    if (empty($path) || $path === 'auth/info') {
        logDebug("Handling auth/info route");
        respondWithSuccess([
            'api_name' => 'KlubeCash External API',
            'version' => API_VERSION,
            'base_url' => 'https://klubecash.com/api-external/v1',
            'documentation_url' => 'https://klubecash.com/api-external/v1/docs',
            'requires_api_key' => true,
            'rate_limits' => [
                'default_per_minute' => RATE_LIMIT_REQUESTS_PER_MINUTE,
                'default_per_hour' => RATE_LIMIT_REQUESTS_PER_HOUR
            ]
        ]);
        
    } elseif ($path === 'auth/health') {
        logDebug("Handling auth/health route");
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT 1");
        $stmt->execute();
        
        respondWithSuccess([
            'status' => 'healthy',
            'database' => 'connected',
            'timestamp' => date('c'),
            'uptime' => sys_getloadavg()[0] ?? 'N/A'
        ]);
    }
    
    // Função para extrair API Key
    function getApiKey() {
        $headers = getallheaders();
        
        // Tentar diferentes formas
        if ($headers) {
            foreach (['X-API-Key', 'x-api-key', 'HTTP_X_API_KEY'] as $headerName) {
                if (isset($headers[$headerName])) {
                    return trim($headers[$headerName]);
                }
            }
        }
        
        // Fallback para $_SERVER
        if (isset($_SERVER['HTTP_X_API_KEY'])) {
            return trim($_SERVER['HTTP_X_API_KEY']);
        }
        
        // Fallback para GET
        if (isset($_GET['api_key'])) {
            return trim($_GET['api_key']);
        }
        
        return null;
    }
    
    // Para todas as outras rotas, validar API Key
    $apiKey = getApiKey();
    
    logDebug("API Key present: " . ($apiKey ? 'YES' : 'NO'));
    
    if (!$apiKey) {
        logDebug("No API Key provided");
        Response::unauthorized('API Key is required. Provide it in X-API-Key header.');
    }
    
    // Validar API Key
    $apiKeyModel = new SimpleApiKey();
    $keyData = $apiKeyModel->validateApiKey($apiKey);
    
    if (!$keyData) {
        logDebug("API Key validation failed");
        Response::unauthorized('Invalid or expired API Key');
    }
    
    logDebug("API Key validated for: " . $keyData['partner_name']);
    
    // Rotas protegidas
    if ($path === 'users' && $method === 'GET') {
        logDebug("Processing users route");
        
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
        
        logDebug("Found " . count($users) . " users");
        respondWithSuccess($users, 'Users retrieved successfully');
        
    } elseif ($path === 'stores' && $method === 'GET') {
        logDebug("Processing stores route");
        
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
        
        logDebug("Found " . count($stores) . " stores");
        respondWithSuccess($stores, 'Stores retrieved successfully');
        
    } elseif ($path === 'cashback/calculate' && $method === 'POST') {
        logDebug("Processing cashback calculate route");
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['store_id']) || !isset($input['amount'])) {
            Response::error('store_id and amount are required', 400);
        }
        
        $storeId = intval($input['store_id']);
        $amount = floatval($input['amount']);
        
        if ($storeId <= 0 || $amount <= 0) {
            Response::error('Invalid store_id or amount', 400);
        }
        
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT porcentagem_cashback FROM lojas WHERE id = ? AND status = 'aprovado'");
        $stmt->execute([$storeId]);
        $store = $stmt->fetch();
        
        if (!$store) {
            Response::error('Store not found or not approved', 404);
        }
        
        $stmt = $db->prepare("SELECT porcentagem_cliente, porcentagem_admin, porcentagem_loja FROM configuracoes_cashback LIMIT 1");
        $stmt->execute();
        $config = $stmt->fetch();
        
        if (!$config) {
            Response::error('Cashback configuration not found', 500);
        }
        
        $cashbackPercentage = floatval($store['porcentagem_cashback']);
        $totalCashback = ($amount * $cashbackPercentage) / 100;
        
        $clientAmount = ($totalCashback * $config['porcentagem_cliente']) / 100;
        $adminAmount = ($totalCashback * $config['porcentagem_admin']) / 100;
        $storeAmount = ($totalCashback * $config['porcentagem_loja']) / 100;
        
        respondWithSuccess([
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
        logDebug("Route not found - Path: '$path', Method: $method");
        Response::error("Route not found: $method /$path", 404);
    }
    
} catch (Exception $e) {
    logDebug('Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    Response::error('Internal server error', 500);
}
?>