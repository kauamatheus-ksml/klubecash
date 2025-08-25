<?php

class SimpleApiKey {
    private $db;
    
    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    public function validateApiKey($apiKey) {
        if (!$apiKey || !str_starts_with($apiKey, 'kc_')) {
            return false;
        }
        
        $keyHash = hash('sha256', $apiKey);
        
        $stmt = $this->db->prepare("
            SELECT id, partner_name, partner_email, permissions, 
                   rate_limit_per_minute, rate_limit_per_hour
            FROM api_keys 
            WHERE key_hash = ? AND is_active = 1 
            AND (expires_at IS NULL OR expires_at > NOW())
        ");
        
        $stmt->execute([$keyHash]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return false;
        }
        
        // Atualizar último uso
        $updateStmt = $this->db->prepare("UPDATE api_keys SET last_used_at = NOW() WHERE id = ?");
        $updateStmt->execute([$result['id']]);
        
        return [
            'id' => $result['id'],
            'partner_name' => $result['partner_name'],
            'partner_email' => $result['partner_email'],
            'permissions' => json_decode($result['permissions'], true),
            'rate_limits' => [
                'per_minute' => $result['rate_limit_per_minute'],
                'per_hour' => $result['rate_limit_per_hour']
            ]
        ];
    }
}
?>