<?php

define('API_VERSION', 'v1');
define('API_BASE_URL', '/api-external/v1');

// Rate Limiting
define('RATE_LIMIT_REQUESTS_PER_MINUTE', 60);
define('RATE_LIMIT_REQUESTS_PER_HOUR', 1000);
define('RATE_LIMIT_BURST_LIMIT', 10);

// JWT
define('JWT_SECRET', 'your_jwt_secret_key_here_change_in_production');
define('JWT_EXPIRATION', 3600); // 1 hour
define('JWT_REFRESH_EXPIRATION', 86400); // 24 hours

// API Keys
define('API_KEY_LENGTH', 64);
define('API_KEY_PREFIX', 'kc_');

// Pagination
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// Cache
define('CACHE_ENABLED', true);
define('CACHE_DEFAULT_TTL', 300); // 5 minutes

// Logs
define('API_LOG_ENABLED', true);
define('API_LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR

// Webhook settings
define('WEBHOOK_TIMEOUT', 30);
define('WEBHOOK_MAX_RETRIES', 3);
define('WEBHOOK_RETRY_DELAY', 5);

// Security
define('MAX_REQUEST_SIZE', '2MB');
define('ALLOWED_FILE_TYPES', ['json']);

class ApiConfig {
    
    public static function get($key, $default = null) {
        return defined($key) ? constant($key) : $default;
    }
    
    public static function isProduction() {
        return $_SERVER['SERVER_NAME'] !== 'localhost' && $_SERVER['SERVER_NAME'] !== '127.0.0.1';
    }
    
    public static function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        return $protocol . '://' . $_SERVER['HTTP_HOST'] . API_BASE_URL;
    }
}
?>