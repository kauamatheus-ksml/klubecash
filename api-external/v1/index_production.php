<?php
// index_production.php - Versão completa da API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Error reporting apenas para debug - remover em produção
error_reporting(E_ALL);
ini_set('display_errors', 0); // Desabilitado para produção
ini_set('log_errors', 1);

try {
    require_once '../../config/database.php';
    require_once 'config/api_config.php';
    require_once 'core/Router.php';
    require_once 'core/Response.php';
    require_once 'core/ApiException.php';
    require_once 'middleware/AuthMiddleware.php';
    require_once 'middleware/RateLimitMiddleware.php';
    require_once 'middleware/ValidationMiddleware.php';

    date_default_timezone_set('America/Sao_Paulo');
    session_start();

    $router = new Router();
    
    // Middleware global apenas para rotas protegidas
    // Será aplicado condicionalmente no router
    
    // Rotas de autenticação (não requerem API Key)
    $router->group('/auth', function($router) {
        
        $router->get('/info', function() {
            Response::success([
                'api_name' => 'KlubeCash External API',
                'version' => API_VERSION,
                'base_url' => ApiConfig::getBaseUrl(),
                'documentation_url' => ApiConfig::getBaseUrl() . '/docs',
                'requires_api_key' => true,
                'rate_limits' => [
                    'default_per_minute' => RATE_LIMIT_REQUESTS_PER_MINUTE,
                    'default_per_hour' => RATE_LIMIT_REQUESTS_PER_HOUR
                ]
            ]);
        });

        $router->get('/health', function() {
            try {
                $db = Database::getConnection();
                $stmt = $db->prepare("SELECT 1");
                $stmt->execute();
                
                Response::success([
                    'status' => 'healthy',
                    'database' => 'connected',
                    'timestamp' => date('c'),
                    'uptime' => sys_getloadavg()[0] ?? 'N/A'
                ]);
                
            } catch (Exception $e) {
                Response::error('Database connection failed', 503, [
                    'status' => 'unhealthy',
                    'database' => 'disconnected',
                    'timestamp' => date('c')
                ]);
            }
        });
        
    }, ['skip_auth' => true]);
    
    // Middleware para rotas protegidas
    $router->addMiddleware(new AuthMiddleware());
    $router->addMiddleware(new RateLimitMiddleware());
    
    // Rotas protegidas (requerem API Key)
    $router->group('/users', function($router) {
        require_once 'routes/user_routes.php';
    });
    
    $router->group('/stores', function($router) {
        require_once 'routes/store_routes.php';
    });
    
    $router->group('/transactions', function($router) {
        require_once 'routes/transaction_routes.php';
    });
    
    $router->group('/cashback', function($router) {
        require_once 'routes/cashback_routes.php';
    });
    
    $router->group('/webhooks', function($router) {
        require_once 'routes/webhook_routes.php';
    });
    
    // Processar requisição
    $router->dispatch();
    
} catch (ApiException $e) {
    error_log('API Exception: ' . $e->getMessage());
    Response::error($e->getMessage(), $e->getCode(), $e->getDetails());
} catch (Exception $e) {
    error_log('General Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    Response::error('Internal server error', 500);
}
?>