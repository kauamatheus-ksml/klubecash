<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/database.php';
require_once 'config/api_config.php';
require_once 'core/Router.php';
require_once 'core/Response.php';
require_once 'core/ApiException.php';
require_once 'middleware/AuthMiddleware.php';
require_once 'middleware/RateLimitMiddleware.php';
require_once 'middleware/ValidationMiddleware.php';

date_default_timezone_set('America/Sao_Paulo');

try {
    $router = new Router();
    
    // Middleware global
    $router->addMiddleware(new RateLimitMiddleware());
    $router->addMiddleware(new AuthMiddleware());
    
    // Rotas de autenticação (não requerem API Key)
    $router->group('/auth', function($router) {
        require_once 'routes/auth_routes.php';
    }, ['skip_auth' => true]);
    
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
    Response::error($e->getMessage(), $e->getCode(), $e->getDetails());
} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    Response::error('Internal server error', 500);
}
?>