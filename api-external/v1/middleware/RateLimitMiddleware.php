<?php

require_once 'models/ApiKey.php';

class RateLimitMiddleware {
    private $apiKeyModel;
    
    public function __construct() {
        $this->apiKeyModel = new ApiKey();
    }
    
    public function handle() {
        $keyData = AuthMiddleware::getCurrentApiKeyData();
        
        if (!$keyData) {
            // Se não há dados da API key, pode ser uma rota pública
            return true;
        }
        
        $endpoint = $this->getCurrentEndpoint();
        $rateLimits = $keyData['rate_limits'];
        
        // Verificar rate limit
        if (!$this->apiKeyModel->checkRateLimit($keyData['id'], $endpoint, $rateLimits)) {
            // Adicionar headers informativos
            header('X-RateLimit-Limit-Minute: ' . $rateLimits['per_minute']);
            header('X-RateLimit-Limit-Hour: ' . $rateLimits['per_hour']);
            header('X-RateLimit-Remaining: 0');
            header('X-RateLimit-Reset: ' . (time() + 60));
            
            throw ApiException::rateLimit('Rate limit exceeded. Try again later.');
        }
        
        // Incrementar contador
        $this->apiKeyModel->incrementRateLimit($keyData['id'], $endpoint);
        
        // Adicionar headers informativos de rate limit
        $this->addRateLimitHeaders($keyData['id'], $endpoint, $rateLimits);
        
        return true;
    }
    
    private function getCurrentEndpoint() {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Remover base path da API
        $basePath = API_BASE_URL;
        if (strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }
        
        // Normalizar endpoint (remover IDs específicos)
        $path = preg_replace('/\/\d+/', '/{id}', $path);
        
        return $method . ' ' . $path;
    }
    
    private function addRateLimitHeaders($apiKeyId, $endpoint, $rateLimits) {
        try {
            // Obter contadores atuais
            $db = Database::getConnection();
            
            // Contador por minuto
            $minuteWindow = date('Y-m-d H:i:s', floor(time() / 60) * 60);
            $stmt = $db->prepare("
                SELECT requests_count 
                FROM api_rate_limits 
                WHERE api_key_id = ? AND endpoint = ? AND window_type = 'minute' AND window_start = ?
            ");
            $stmt->execute([$apiKeyId, $endpoint, $minuteWindow]);
            $minuteCount = $stmt->fetchColumn() ?: 0;
            
            // Contador por hora
            $hourWindow = date('Y-m-d H:i:s', floor(time() / 3600) * 3600);
            $stmt = $db->prepare("
                SELECT requests_count 
                FROM api_rate_limits 
                WHERE api_key_id = ? AND endpoint = ? AND window_type = 'hour' AND window_start = ?
            ");
            $stmt->execute([$apiKeyId, $endpoint, $hourWindow]);
            $hourCount = $stmt->fetchColumn() ?: 0;
            
            // Headers de rate limit
            header('X-RateLimit-Limit-Minute: ' . $rateLimits['per_minute']);
            header('X-RateLimit-Limit-Hour: ' . $rateLimits['per_hour']);
            header('X-RateLimit-Remaining-Minute: ' . max(0, $rateLimits['per_minute'] - $minuteCount));
            header('X-RateLimit-Remaining-Hour: ' . max(0, $rateLimits['per_hour'] - $hourCount));
            header('X-RateLimit-Reset-Minute: ' . (floor(time() / 60 + 1) * 60));
            header('X-RateLimit-Reset-Hour: ' . (floor(time() / 3600 + 1) * 3600));
            
        } catch (Exception $e) {
            // Se houver erro ao obter os contadores, apenas continue
            error_log('Error adding rate limit headers: ' . $e->getMessage());
        }
    }
}
?>