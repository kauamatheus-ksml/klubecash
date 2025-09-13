<?php

class ApiKey {
    private $db;
    
    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    public function generateApiKey($partnerName, $partnerEmail, $permissions = [], $options = []) {
        $key = API_KEY_PREFIX . bin2hex(random_bytes(API_KEY_LENGTH / 2));
        $keyHash = hash('sha256', $key);
        $keyPrefix = substr($key, 0, 10);
        
        $stmt = $this->db->prepare("
            INSERT INTO api_keys (
                key_hash, key_prefix, partner_name, partner_email, permissions,
                rate_limit_per_minute, rate_limit_per_hour, expires_at, 
                webhook_url, webhook_secret, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $permissionsJson = json_encode($permissions);
        $rateLimitMinute = $options['rate_limit_per_minute'] ?? RATE_LIMIT_REQUESTS_PER_MINUTE;
        $rateLimitHour = $options['rate_limit_per_hour'] ?? RATE_LIMIT_REQUESTS_PER_HOUR;
        $expiresAt = $options['expires_at'] ?? null;
        $webhookUrl = $options['webhook_url'] ?? null;
        $webhookSecret = $options['webhook_secret'] ?? null;
        $notes = $options['notes'] ?? null;
        
        if ($stmt->execute([
            $keyHash, $keyPrefix, $partnerName, $partnerEmail, $permissionsJson,
            $rateLimitMinute, $rateLimitHour, $expiresAt,
            $webhookUrl, $webhookSecret, $notes
        ])) {
            return [
                'api_key' => $key,
                'api_key_id' => $this->db->lastInsertId(),
                'permissions' => $permissions,
                'rate_limits' => [
                    'per_minute' => $rateLimitMinute,
                    'per_hour' => $rateLimitHour
                ]
            ];
        }
        
        return false;
    }
    
    public function validateApiKey($apiKey) {
        if (!$apiKey || !str_starts_with($apiKey, API_KEY_PREFIX)) {
            return false;
        }
        
        $keyHash = hash('sha256', $apiKey);
        
        $stmt = $this->db->prepare("
            SELECT id, partner_name, partner_email, permissions, 
                   rate_limit_per_minute, rate_limit_per_hour, 
                   is_active, expires_at, webhook_url
            FROM api_keys 
            WHERE key_hash = ? AND is_active = 1
        ");
        
        $stmt->execute([$keyHash]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return false;
        }
        
        // Verificar expiração
        if ($result['expires_at'] && strtotime($result['expires_at']) < time()) {
            return false;
        }
        
        // Atualizar último uso
        $this->updateLastUsed($result['id']);
        
        return [
            'id' => $result['id'],
            'partner_name' => $result['partner_name'],
            'partner_email' => $result['partner_email'],
            'permissions' => json_decode($result['permissions'], true),
            'rate_limits' => [
                'per_minute' => $result['rate_limit_per_minute'],
                'per_hour' => $result['rate_limit_per_hour']
            ],
            'webhook_url' => $result['webhook_url']
        ];
    }
    
    public function hasPermission($apiKeyData, $permission) {
        if (!isset($apiKeyData['permissions'])) {
            return false;
        }
        
        return in_array($permission, $apiKeyData['permissions']) || 
               in_array('*', $apiKeyData['permissions']);
    }
    
    public function checkRateLimit($apiKeyId, $endpoint, $rateLimits) {
        $windows = [
            'minute' => ['limit' => $rateLimits['per_minute'], 'seconds' => 60],
            'hour' => ['limit' => $rateLimits['per_hour'], 'seconds' => 3600]
        ];
        
        foreach ($windows as $windowType => $config) {
            $windowStart = date('Y-m-d H:i:s', floor(time() / $config['seconds']) * $config['seconds']);
            
            $stmt = $this->db->prepare("
                SELECT requests_count 
                FROM api_rate_limits 
                WHERE api_key_id = ? AND endpoint = ? AND window_type = ? AND window_start = ?
            ");
            
            $stmt->execute([$apiKeyId, $endpoint, $windowType, $windowStart]);
            $result = $stmt->fetch();
            
            $currentCount = $result ? $result['requests_count'] : 0;
            
            if ($currentCount >= $config['limit']) {
                return false;
            }
        }
        
        return true;
    }
    
    public function incrementRateLimit($apiKeyId, $endpoint) {
        $windows = [
            'minute' => 60,
            'hour' => 3600
        ];
        
        foreach ($windows as $windowType => $seconds) {
            $windowStart = date('Y-m-d H:i:s', floor(time() / $seconds) * $seconds);
            
            $stmt = $this->db->prepare("
                INSERT INTO api_rate_limits (api_key_id, endpoint, window_type, window_start, requests_count)
                VALUES (?, ?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE requests_count = requests_count + 1
            ");
            
            $stmt->execute([$apiKeyId, $endpoint, $windowType, $windowStart]);
        }
    }
    
    public function revokeApiKey($keyId) {
        $stmt = $this->db->prepare("
            UPDATE api_keys SET is_active = 0 WHERE id = ?
        ");
        
        return $stmt->execute([$keyId]);
    }
    
    public function listApiKeys($partnerEmail = null) {
        $sql = "
            SELECT id, key_prefix, partner_name, partner_email, permissions,
                   rate_limit_per_minute, rate_limit_per_hour, is_active,
                   last_used_at, created_at, expires_at, notes
            FROM api_keys
        ";
        
        $params = [];
        if ($partnerEmail) {
            $sql .= " WHERE partner_email = ?";
            $params[] = $partnerEmail;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    public function getApiKeyStats($keyId, $days = 7) {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as requests,
                AVG(response_time_ms) as avg_response_time,
                COUNT(CASE WHEN status_code >= 400 THEN 1 END) as errors
            FROM api_logs 
            WHERE api_key_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        
        $stmt->execute([$keyId, $days]);
        return $stmt->fetchAll();
    }
    
    private function updateLastUsed($keyId) {
        $stmt = $this->db->prepare("
            UPDATE api_keys SET last_used_at = NOW() WHERE id = ?
        ");
        
        $stmt->execute([$keyId]);
    }
}
?>