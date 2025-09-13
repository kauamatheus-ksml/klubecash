<?php

// Rotas de autenticação (não requerem API Key)
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
        // Testar conexão com banco
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT 1");
        $stmt->execute();
        
        Response::success([
            'status' => 'healthy',
            'database' => 'connected',
            'timestamp' => date('c'),
            'uptime' => sys_getloadavg()[0]
        ]);
        
    } catch (Exception $e) {
        Response::error('Database connection failed', 503, [
            'status' => 'unhealthy',
            'database' => 'disconnected',
            'timestamp' => date('c')
        ]);
    }
});
?>