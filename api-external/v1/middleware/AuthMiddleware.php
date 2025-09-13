<?php

require_once 'models/ApiKey.php';

class AuthMiddleware {
    private $apiKeyModel;
    
    public function __construct() {
        $this->apiKeyModel = new ApiKey();
    }
    
    public function handle() {
        $apiKey = $this->extractApiKey();
        
        if (!$apiKey) {
            throw ApiException::unauthorized('API Key is required. Provide it in X-API-Key header.');
        }
        
        $keyData = $this->apiKeyModel->validateApiKey($apiKey);
        
        if (!$keyData) {
            throw ApiException::unauthorized('Invalid or expired API Key');
        }
        
        // Armazenar dados da chave para uso posterior
        $_SESSION['api_key_data'] = $keyData;
        
        return true;
    }
    
    public function checkPermission($permission) {
        $keyData = $_SESSION['api_key_data'] ?? null;
        
        if (!$keyData) {
            throw ApiException::unauthorized('API Key data not found');
        }
        
        if (!$this->apiKeyModel->hasPermission($keyData, $permission)) {
            throw ApiException::forbidden("Permission denied. Required permission: {$permission}");
        }
        
        return true;
    }
    
    public static function getCurrentApiKeyData() {
        return $_SESSION['api_key_data'] ?? null;
    }
    
    public static function getCurrentApiKeyId() {
        $keyData = self::getCurrentApiKeyData();
        return $keyData ? $keyData['id'] : null;
    }
    
    public static function getCurrentPartnerName() {
        $keyData = self::getCurrentApiKeyData();
        return $keyData ? $keyData['partner_name'] : null;
    }
    
    private function extractApiKey() {
        // Tentar pegar da header X-API-Key
        $headers = getallheaders();
        if ($headers && isset($headers['X-API-Key'])) {
            return trim($headers['X-API-Key']);
        }
        
        // Fallback para header Authorization com Bearer
        if ($headers && isset($headers['Authorization'])) {
            $auth = trim($headers['Authorization']);
            if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
                return trim($matches[1]);
            }
        }
        
        // Fallback para query parameter (não recomendado para produção)
        if (isset($_GET['api_key'])) {
            return trim($_GET['api_key']);
        }
        
        return null;
    }
}
?>