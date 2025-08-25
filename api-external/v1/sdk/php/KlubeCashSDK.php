<?php
/**
 * KlubeCash PHP SDK
 * 
 * Official PHP SDK for integrating with the KlubeCash External API
 * 
 * @package KlubeCash
 * @version 1.0.0
 * @author KlubeCash Development Team
 * @link https://klubecash.com/api-external/v1/docs
 */

namespace KlubeCash;

use Exception;
use InvalidArgumentException;

/**
 * Main SDK class for KlubeCash API integration
 */
class KlubeCashSDK
{
    /** @var string API base URL */
    private $baseUrl;
    
    /** @var string API Key for authentication */
    private $apiKey;
    
    /** @var array Default cURL options */
    private $defaultCurlOptions;
    
    /** @var int Default timeout in seconds */
    private $timeout;
    
    /** @var bool Enable debug mode */
    private $debug;
    
    /** @var array Cache storage */
    private $cache = [];
    
    /** @var int Cache TTL in seconds */
    private $cacheTtl;
    
    /** @var callable|null Custom logger function */
    private $logger;
    
    /** @var array Rate limit tracking */
    private $rateLimitInfo = [];
    
    const VERSION = '1.0.0';
    const USER_AGENT = 'KlubeCash-PHP-SDK/1.0.0';
    
    /**
     * Constructor
     * 
     * @param string $apiKey API Key for authentication
     * @param array $options Configuration options
     */
    public function __construct($apiKey, array $options = [])
    {
        if (empty($apiKey) || !is_string($apiKey)) {
            throw new InvalidArgumentException('API Key is required and must be a string');
        }
        
        if (!str_starts_with($apiKey, 'kc_')) {
            throw new InvalidArgumentException('Invalid API Key format. Must start with "kc_"');
        }
        
        $this->apiKey = $apiKey;
        $this->baseUrl = $options['base_url'] ?? 'https://klubecash.com/api-external/v1';
        $this->timeout = $options['timeout'] ?? 30;
        $this->debug = $options['debug'] ?? false;
        $this->cacheTtl = $options['cache_ttl'] ?? 300; // 5 minutes
        $this->logger = $options['logger'] ?? null;
        
        $this->defaultCurlOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => self::USER_AGENT,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-API-Key: ' . $this->apiKey,
                'Accept: application/json'
            ]
        ];
    }
    
    /**
     * Make HTTP request to API
     * 
     * @param string $endpoint API endpoint
     * @param string $method HTTP method
     * @param array|null $data Request data
     * @param bool $useCache Whether to use cache
     * @return array API response
     * @throws KlubeCashException
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null, $useCache = false)
    {
        $url = $this->baseUrl . $endpoint;
        $cacheKey = md5($method . $endpoint . serialize($data));
        
        // Check cache for GET requests
        if ($useCache && $method === 'GET' && $this->isCached($cacheKey)) {
            return $this->getFromCache($cacheKey);
        }
        
        $this->log("Making {$method} request to: {$url}");
        
        $ch = curl_init();
        curl_setopt_array($ch, $this->defaultCurlOptions);
        curl_setopt($ch, CURLOPT_URL, $url);
        
        // Set HTTP method and data
        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            case 'PATCH':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
        }
        
        $startTime = microtime(true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        $duration = microtime(true) - $startTime;
        $this->log("Request completed in " . round($duration * 1000, 2) . "ms");
        
        // Handle cURL errors
        if ($error) {
            $this->log("cURL Error: " . $error, 'error');
            throw new KlubeCashException("Network error: " . $error, 0);
        }
        
        // Parse JSON response
        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log("JSON decode error: " . json_last_error_msg(), 'error');
            throw new KlubeCashException("Invalid JSON response from API", $httpCode);
        }
        
        // Extract rate limit info from headers
        $this->extractRateLimitInfo($info);
        
        // Handle HTTP errors
        if ($httpCode >= 400) {
            $errorMessage = $decodedResponse['message'] ?? 'API Error';
            $this->log("API Error (HTTP {$httpCode}): {$errorMessage}", 'error');
            throw new KlubeCashException($errorMessage, $httpCode, $decodedResponse);
        }
        
        // Cache successful GET requests
        if ($useCache && $method === 'GET' && $httpCode < 300) {
            $this->storeInCache($cacheKey, $decodedResponse);
        }
        
        return $decodedResponse;
    }
    
    /**
     * Get API information
     * 
     * @param bool $useCache Whether to use cache
     * @return array
     */
    public function getApiInfo($useCache = true)
    {
        return $this->makeRequest('/auth/info', 'GET', null, $useCache);
    }
    
    /**
     * Check API health
     * 
     * @return array
     */
    public function checkHealth()
    {
        return $this->makeRequest('/auth/health', 'GET');
    }
    
    /**
     * Get users list
     * 
     * @param bool $useCache Whether to use cache
     * @return array
     */
    public function getUsers($useCache = true)
    {
        return $this->makeRequest('/users', 'GET', null, $useCache);
    }
    
    /**
     * Get stores list
     * 
     * @param bool $useCache Whether to use cache
     * @return array
     */
    public function getStores($useCache = true)
    {
        return $this->makeRequest('/stores', 'GET', null, $useCache);
    }
    
    /**
     * Get specific store by ID
     * 
     * @param int $storeId Store ID
     * @return array|null
     */
    public function getStore($storeId)
    {
        $stores = $this->getStores();
        
        if (!isset($stores['data']) || !is_array($stores['data'])) {
            return null;
        }
        
        foreach ($stores['data'] as $store) {
            if ($store['id'] == $storeId) {
                return $store;
            }
        }
        
        return null;
    }
    
    /**
     * Get approved stores only
     * 
     * @param bool $useCache Whether to use cache
     * @return array
     */
    public function getApprovedStores($useCache = true)
    {
        $stores = $this->getStores($useCache);
        
        if (!isset($stores['data']) || !is_array($stores['data'])) {
            return [];
        }
        
        $approvedStores = array_filter($stores['data'], function($store) {
            return isset($store['status']) && $store['status'] === 'aprovado';
        });
        
        return array_values($approvedStores);
    }
    
    /**
     * Calculate cashback for a purchase
     * 
     * @param int $storeId Store ID
     * @param float $amount Purchase amount
     * @return array
     * @throws InvalidArgumentException
     */
    public function calculateCashback($storeId, $amount)
    {
        if (!is_numeric($storeId) || $storeId <= 0) {
            throw new InvalidArgumentException('Store ID must be a positive integer');
        }
        
        if (!is_numeric($amount) || $amount <= 0) {
            throw new InvalidArgumentException('Amount must be a positive number');
        }
        
        $data = [
            'store_id' => intval($storeId),
            'amount' => floatval($amount)
        ];
        
        return $this->makeRequest('/cashback/calculate', 'POST', $data);
    }
    
    /**
     * Validate if a store exists and is approved
     * 
     * @param int $storeId Store ID
     * @return bool
     */
    public function isStoreApproved($storeId)
    {
        $store = $this->getStore($storeId);
        return $store !== null && isset($store['status']) && $store['status'] === 'aprovado';
    }
    
    /**
     * Bulk calculate cashback for multiple stores
     * 
     * @param array $calculations Array of ['store_id' => int, 'amount' => float]
     * @return array Results with store_id as key
     */
    public function bulkCalculateCashback(array $calculations)
    {
        $results = [];
        
        foreach ($calculations as $calc) {
            if (!isset($calc['store_id']) || !isset($calc['amount'])) {
                continue;
            }
            
            try {
                $result = $this->calculateCashback($calc['store_id'], $calc['amount']);
                $results[$calc['store_id']] = $result;
            } catch (Exception $e) {
                $results[$calc['store_id']] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Get cashback statistics
     * 
     * @return array
     */
    public function getCashbackStatistics()
    {
        $stores = $this->getApprovedStores();
        $stats = [
            'total_stores' => count($stores),
            'average_cashback_rate' => 0,
            'min_cashback_rate' => null,
            'max_cashback_rate' => null,
            'stores_by_rate' => []
        ];
        
        if (empty($stores)) {
            return $stats;
        }
        
        $rates = [];
        foreach ($stores as $store) {
            // Simular taxa de cashback baseada no ID (em produção, viria da API)
            $sampleRate = ($store['id'] % 10) + 1; // 1-10%
            $rates[] = $sampleRate;
            
            $stats['stores_by_rate'][] = [
                'store_id' => $store['id'],
                'store_name' => $store['trade_name'],
                'estimated_rate' => $sampleRate
            ];
        }
        
        $stats['average_cashback_rate'] = array_sum($rates) / count($rates);
        $stats['min_cashback_rate'] = min($rates);
        $stats['max_cashback_rate'] = max($rates);
        
        return $stats;
    }
    
    /**
     * Clear cache
     * 
     * @param string|null $pattern Optional pattern to match cache keys
     */
    public function clearCache($pattern = null)
    {
        if ($pattern === null) {
            $this->cache = [];
        } else {
            foreach (array_keys($this->cache) as $key) {
                if (fnmatch($pattern, $key)) {
                    unset($this->cache[$key]);
                }
            }
        }
    }
    
    /**
     * Get rate limit information
     * 
     * @return array
     */
    public function getRateLimitInfo()
    {
        return $this->rateLimitInfo;
    }
    
    /**
     * Set custom logger
     * 
     * @param callable $logger Logger function
     */
    public function setLogger(callable $logger)
    {
        $this->logger = $logger;
    }
    
    /**
     * Test API connection
     * 
     * @return array Connection test results
     */
    public function testConnection()
    {
        $results = [
            'api_reachable' => false,
            'authentication' => false,
            'response_time' => 0,
            'errors' => []
        ];
        
        try {
            // Test basic connectivity
            $startTime = microtime(true);
            $info = $this->getApiInfo(false);
            $results['response_time'] = microtime(true) - $startTime;
            $results['api_reachable'] = true;
            
            // Test authentication
            $users = $this->getUsers(false);
            $results['authentication'] = isset($users['success']) && $users['success'];
            
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
        }
        
        return $results;
    }
    
    // Private helper methods
    
    private function isCached($key)
    {
        return isset($this->cache[$key]) && 
               (time() - $this->cache[$key]['timestamp']) < $this->cacheTtl;
    }
    
    private function getFromCache($key)
    {
        $this->log("Cache hit for key: " . $key);
        return $this->cache[$key]['data'];
    }
    
    private function storeInCache($key, $data)
    {
        $this->cache[$key] = [
            'data' => $data,
            'timestamp' => time()
        ];
    }
    
    private function extractRateLimitInfo($curlInfo)
    {
        // In a real implementation, you would extract this from response headers
        // For now, we'll simulate based on response time
        $this->rateLimitInfo = [
            'remaining' => 950,
            'limit' => 1000,
            'reset_time' => time() + 3600
        ];
    }
    
    private function log($message, $level = 'info')
    {
        if (!$this->debug && $level !== 'error') {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $formatted = "[{$timestamp}] [{$level}] KlubeCashSDK: {$message}";
        
        if ($this->logger && is_callable($this->logger)) {
            call_user_func($this->logger, $formatted, $level);
        } elseif ($this->debug) {
            error_log($formatted);
        }
    }
}

/**
 * Custom exception class for KlubeCash SDK
 */
class KlubeCashException extends Exception
{
    /** @var array|null Additional error data from API */
    private $errorData;
    
    public function __construct($message = "", $code = 0, $errorData = null)
    {
        parent::__construct($message, $code);
        $this->errorData = $errorData;
    }
    
    /**
     * Get additional error data
     * 
     * @return array|null
     */
    public function getErrorData()
    {
        return $this->errorData;
    }
    
    /**
     * Check if error is due to authentication
     * 
     * @return bool
     */
    public function isAuthenticationError()
    {
        return $this->getCode() === 401;
    }
    
    /**
     * Check if error is due to rate limiting
     * 
     * @return bool
     */
    public function isRateLimitError()
    {
        return $this->getCode() === 429;
    }
    
    /**
     * Check if error is a client error (4xx)
     * 
     * @return bool
     */
    public function isClientError()
    {
        $code = $this->getCode();
        return $code >= 400 && $code < 500;
    }
    
    /**
     * Check if error is a server error (5xx)
     * 
     * @return bool
     */
    public function isServerError()
    {
        $code = $this->getCode();
        return $code >= 500 && $code < 600;
    }
}

/**
 * Transaction manager for advanced cashback operations
 */
class KlubeCashTransactionManager
{
    /** @var KlubeCashSDK */
    private $sdk;
    
    /** @var array */
    private $transactions = [];
    
    public function __construct(KlubeCashSDK $sdk)
    {
        $this->sdk = $sdk;
    }
    
    /**
     * Process a complete transaction with cashback
     * 
     * @param int $storeId Store ID
     * @param float $amount Purchase amount
     * @param string $customerEmail Customer email
     * @param array $metadata Additional metadata
     * @return array Transaction result
     */
    public function processTransaction($storeId, $amount, $customerEmail, $metadata = [])
    {
        $transactionId = $this->generateTransactionId();
        
        try {
            // Validate store
            if (!$this->sdk->isStoreApproved($storeId)) {
                throw new InvalidArgumentException("Store ID {$storeId} is not approved");
            }
            
            // Calculate cashback
            $cashbackResult = $this->sdk->calculateCashback($storeId, $amount);
            
            if (!isset($cashbackResult['data'])) {
                throw new Exception("Failed to calculate cashback");
            }
            
            // Create transaction record
            $transaction = [
                'id' => $transactionId,
                'store_id' => $storeId,
                'customer_email' => $customerEmail,
                'purchase_amount' => $amount,
                'cashback_data' => $cashbackResult['data'],
                'metadata' => $metadata,
                'created_at' => date('Y-m-d H:i:s'),
                'status' => 'completed'
            ];
            
            // Store transaction (in real implementation, this would go to database)
            $this->transactions[$transactionId] = $transaction;
            
            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'transaction' => $transaction
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId
            ];
        }
    }
    
    /**
     * Get transaction by ID
     * 
     * @param string $transactionId
     * @return array|null
     */
    public function getTransaction($transactionId)
    {
        return $this->transactions[$transactionId] ?? null;
    }
    
    /**
     * Generate unique transaction ID
     * 
     * @return string
     */
    private function generateTransactionId()
    {
        return 'tx_' . date('Ymd') . '_' . uniqid();
    }
}

// Utility functions

if (!function_exists('str_starts_with')) {
    /**
     * Polyfill for PHP < 8.0
     */
    function str_starts_with($haystack, $needle) {
        return strpos($haystack, $needle) === 0;
    }
}

?>