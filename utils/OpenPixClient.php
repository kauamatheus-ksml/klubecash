<?php
/**
 * Cliente OpenPix para integração com a API do OpenPix
 * Klube Cash v2.1 - Com Debug Avançado
 */

require_once __DIR__ . '/../config/constants.php';

class OpenPixClient {
    private $apiKey;
    private $baseUrl;
    private $timeout;
    private $debug;
    private $maxRetries;
    private $retryDelay;
    private $sslVerification;
    
    public function __construct() {
        // Verificar se as constantes estão definidas
        if (!defined('OPENPIX_API_KEY')) {
            throw new Exception('OPENPIX_API_KEY não está definida');
        }
        
        $this->baseUrl = defined('OPENPIX_BASE_URL') ? OPENPIX_BASE_URL : 'https://api.openpix.com.br/api/v1';
        $this->apiKey = OPENPIX_API_KEY;
        $this->timeout = defined('OPENPIX_TIMEOUT') ? OPENPIX_TIMEOUT : 30;
        $this->debug = defined('OPENPIX_DEBUG') ? OPENPIX_DEBUG : false;
        $this->maxRetries = defined('OPENPIX_MAX_RETRIES') ? OPENPIX_MAX_RETRIES : 3;
        $this->retryDelay = defined('OPENPIX_RETRY_DELAY') ? OPENPIX_RETRY_DELAY : 1000;
        $this->sslVerification = defined('OPENPIX_ENABLE_SSL_VERIFICATION') ? OPENPIX_ENABLE_SSL_VERIFICATION : true;
        
        // Verificar se a API Key está configurada corretamente
        if (empty($this->apiKey) || $this->apiKey === 'APP_ID_YOUR_APP_ID_HERE') {
            throw new Exception('OPENPIX_API_KEY não configurada corretamente. Substitua pela sua API Key real do OpenPix.');
        }
        
        // Log de inicialização
        $this->log('OpenPixClient inicializado', [
            'baseUrl' => $this->baseUrl,
            'timeout' => $this->timeout,
            'debug' => $this->debug,
            'maxRetries' => $this->maxRetries,
            'api_key_format' => $this->validateApiKeyFormat($this->apiKey)
        ]);
    }
    
    /**
     * Validar formato da API Key
     */
    private function validateApiKeyFormat($apiKey) {
        if (empty($apiKey)) {
            return ['valid' => false, 'reason' => 'API Key vazia'];
        }
        
        if ($apiKey === 'APP_ID_YOUR_APP_ID_HERE') {
            return ['valid' => false, 'reason' => 'API Key de exemplo não substituída'];
        }
        
        // Verificar se parece com uma API Key válida do OpenPix
        if (strpos($apiKey, 'Q2xpZW50X0lk') === 0) {
            return ['valid' => true, 'type' => 'Base64 encoded'];
        }
        
        if (preg_match('/^[A-Za-z0-9_-]+$/', $apiKey) && strlen($apiKey) > 20) {
            return ['valid' => true, 'type' => 'Standard format'];
        }
        
        return ['valid' => false, 'reason' => 'Formato não reconhecido'];
    }
    
    /**
     * Teste de conectividade robusto
     */
    public function testConnection() {
        try {
            $this->log('Iniciando teste de conectividade');
            
            // Verificar formato da API Key primeiro
            $apiKeyValidation = $this->validateApiKeyFormat($this->apiKey);
            if (!$apiKeyValidation['valid']) {
                return [
                    'status' => false,
                    'message' => 'API Key inválida: ' . $apiKeyValidation['reason'],
                    'debug_info' => [
                        'api_key_length' => strlen($this->apiKey),
                        'api_key_preview' => substr($this->apiKey, 0, 10) . '...',
                        'validation' => $apiKeyValidation
                    ]
                ];
            }
            
            // Tentar uma requisição simples para listar cobranças
            $response = $this->makeRequest('GET', '/charge?limit=1');
            
            if ($response['success']) {
                return [
                    'status' => true,
                    'message' => 'Conexão com OpenPix estabelecida com sucesso!',
                    'response_time' => isset($response['response_time']) ? round($response['response_time'] * 1000, 2) . 'ms' : null,
                    'attempts' => $response['attempts'] ?? 1,
                    'api_key_validation' => $apiKeyValidation
                ];
            } else {
                // Debug detalhado do erro
                $debugInfo = [
                    'http_code' => $response['http_code'] ?? 'N/A',
                    'error_code' => $response['error_code'] ?? 'N/A',
                    'raw_response' => $response['raw_response'] ?? 'N/A',
                    'attempts' => $response['attempts'] ?? 1,
                    'api_key_validation' => $apiKeyValidation
                ];
                
                $this->log('Erro no teste de conectividade', $debugInfo);
                
                return [
                    'status' => false,
                    'message' => 'Erro na conexão: ' . $response['message'],
                    'debug_info' => $debugInfo
                ];
            }
        } catch (Exception $e) {
            $this->log('Exceção no teste de conectividade', ['error' => $e->getMessage()]);
            return [
                'status' => false,
                'message' => 'Erro na conexão: ' . $e->getMessage(),
                'debug_info' => [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ];
        }
    }
    
    /**
     * Criar uma cobrança PIX com validações aprimoradas
     */
    public function createCharge($data) {
        try {
            // Validar API Key antes de prosseguir
            $apiKeyValidation = $this->validateApiKeyFormat($this->apiKey);
            if (!$apiKeyValidation['valid']) {
                return [
                    'status' => false,
                    'message' => 'API Key inválida: ' . $apiKeyValidation['reason'],
                    'error_code' => 'INVALID_API_KEY'
                ];
            }
            
            // Validar dados obrigatórios
            $this->validateChargeData($data);
            
            // Preparar payload
            $expirationMinutes = defined('PIX_EXPIRATION_MINUTES') ? PIX_EXPIRATION_MINUTES : 30;
            $payload = [
                'value' => (int)($data['amount'] * 100), // Converter para centavos
                'correlationID' => $data['correlation_id'],
                'comment' => $data['comment'] ?? 'Pagamento Klube Cash',
                'expiresIn' => $expirationMinutes * 60 // Converter para segundos
            ];
            
            // Adicionar customer se fornecido
            if (isset($data['customer'])) {
                $payload['customer'] = $this->prepareCustomerData($data['customer']);
            }
            
            // Adicionar informações adicionais se fornecidas
            if (isset($data['additional_info'])) {
                $payload['additionalInfo'] = $data['additional_info'];
            }
            
            $this->log('Criando cobrança PIX', $payload);
            
            $response = $this->makeRequest('POST', '/charge', $payload);
            
            if ($response['success']) {
                $charge = $response['data']['charge'] ?? $response['data'];
                
                $result = [
                    'status' => true,
                    'charge_id' => $charge['correlationID'] ?? $charge['id'],
                    'transaction_id' => $charge['transactionID'] ?? null,
                    'qr_code' => $charge['brCode'],
                    'qr_code_image' => $charge['qrCodeImage'],
                    'payment_link' => $charge['paymentLinkUrl'] ?? null,
                    'expires_at' => $charge['expiresDate'] ?? null,
                    'value' => $charge['value'],
                    'status_charge' => $charge['status'],
                    'raw_data' => $charge
                ];
                
                $this->log('Cobrança PIX criada com sucesso', $result);
                return $result;
            } else {
                $this->log('Erro ao criar cobrança PIX', $response);
                return [
                    'status' => false,
                    'message' => $response['message'],
                    'error_code' => $response['error_code'] ?? null,
                    'debug_info' => [
                        'http_code' => $response['http_code'] ?? null,
                        'raw_response' => $response['raw_response'] ?? null
                    ]
                ];
            }
        } catch (Exception $e) {
            $this->log('Exceção ao criar cobrança PIX', ['error' => $e->getMessage()]);
            return [
                'status' => false,
                'message' => $e->getMessage(),
                'error_code' => 'EXCEPTION'
            ];
        }
    }
    
    /**
     * Verificar status de uma cobrança
     */
    public function getChargeStatus($chargeId) {
        $this->log("Verificando status da cobrança: {$chargeId}");
        
        $response = $this->makeRequest('GET', "/charge/{$chargeId}");
        
        if ($response['success']) {
            $charge = $response['data']['charge'] ?? $response['data'];
            
            return [
                'status' => true,
                'charge_status' => $charge['status'],
                'paid_at' => $charge['paidAt'] ?? null,
                'value' => $charge['value'],
                'customer' => $charge['customer'] ?? null,
                'correlation_id' => $charge['correlationID'] ?? null,
                'raw_data' => $charge
            ];
        } else {
            return [
                'status' => false,
                'message' => $response['message'],
                'debug_info' => [
                    'http_code' => $response['http_code'] ?? null,
                    'error_code' => $response['error_code'] ?? null
                ]
            ];
        }
    }
    
    /**
     * Fazer requisição para a API com debug melhorado
     */
    private function makeRequest($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        $startTime = microtime(true);
        
        $headers = [
            'Authorization: ' . $this->apiKey,
            'Content-Type: application/json',
            'User-Agent: KlubeCash/2.1 OpenPix-Integration',
            'Accept: application/json'
        ];
        
        $attempts = 0;
        $lastError = null;
        
        while ($attempts < $this->maxRetries) {
            $attempts++;
            
            $this->log("Tentativa {$attempts} de {$this->maxRetries}: {$method} {$endpoint}");
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_SSL_VERIFYPEER => $this->sslVerification,
                CURLOPT_SSL_VERIFYHOST => $this->sslVerification ? 2 : 0,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_MAXREDIRS => 0,
                CURLOPT_VERBOSE => $this->debug
            ]);
            
            if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
                $jsonData = json_encode($data);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
                
                if ($this->debug) {
                    $this->log("Payload enviado", ['json' => $jsonData, 'data' => $data]);
                }
            }
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $curlInfo = curl_getinfo($ch);
            $responseTime = microtime(true) - $startTime;
            curl_close($ch);
            
            // Log detalhado se debug ativo
            if ($this->debug) {
                $this->log("Resposta detalhada da API (tentativa {$attempts})", [
                    'url' => $url,
                    'method' => $method,
                    'http_code' => $httpCode,
                    'response_time' => round($responseTime * 1000, 2) . 'ms',
                    'response_size' => strlen($response) . ' bytes',
                    'curl_error' => $error,
                    'curl_info' => $curlInfo,
                    'headers_sent' => $headers,
                    'response_preview' => substr($response, 0, 500) . (strlen($response) > 500 ? '...' : '')
                ]);
            }
            
            if ($error) {
                $lastError = "Erro cURL: {$error}";
                if ($attempts < $this->maxRetries) {
                    $this->log("Erro cURL, tentando novamente em " . ($this->retryDelay / 1000) . "s", ['error' => $error]);
                    usleep($this->retryDelay * 1000);
                    continue;
                }
                return [
                    'success' => false,
                    'message' => $lastError,
                    'error_code' => 'CURL_ERROR',
                    'attempts' => $attempts,
                    'curl_info' => $curlInfo ?? null
                ];
            }
            
            $responseData = json_decode($response, true);
            $jsonError = json_last_error();
            
            if ($jsonError !== JSON_ERROR_NONE) {
                $this->log("Erro ao decodificar JSON", [
                    'json_error' => json_last_error_msg(),
                    'response' => $response
                ]);
            }
            
            if ($httpCode >= 200 && $httpCode < 300) {
                return [
                    'success' => true,
                    'data' => $responseData,
                    'http_code' => $httpCode,
                    'response_time' => $responseTime,
                    'attempts' => $attempts
                ];
            } else if ($httpCode >= 500 && $attempts < $this->maxRetries) {
                $this->log("Erro de servidor (HTTP {$httpCode}), tentando novamente", ['response' => $response]);
                usleep($this->retryDelay * 1000);
                continue;
            } else {
                $errorMessage = 'Erro na API OpenPix';
                
                if ($responseData && isset($responseData['error'])) {
                    $errorMessage = $responseData['error'];
                } elseif ($responseData && isset($responseData['message'])) {
                    $errorMessage = $responseData['message'];
                } elseif ($httpCode === 401) {
                    $errorMessage = 'API Key inválida ou sem permissão';
                } elseif ($httpCode === 403) {
                    $errorMessage = 'Acesso negado - verifique suas permissões';
                } elseif ($httpCode === 404) {
                    $errorMessage = 'Endpoint não encontrado';
                }
                
                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'http_code' => $httpCode,
                    'error_code' => $responseData['code'] ?? 'API_ERROR',
                    'raw_response' => $response,
                    'attempts' => $attempts,
                    'response_data' => $responseData
                ];
            }
        }
        
        return [
            'success' => false,
            'message' => $lastError ?? 'Esgotadas todas as tentativas de conexão',
            'attempts' => $attempts
        ];
    }
    
    /**
     * Validar dados para criar cobrança
     */
    private function validateChargeData($data) {
        $required = ['amount', 'correlation_id'];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new InvalidArgumentException("Campo obrigatório: {$field}");
            }
        }
        
        // Validar valor
        $amount = (float)$data['amount'];
        if ($amount <= 0) {
            throw new InvalidArgumentException('Valor deve ser maior que zero');
        }
        
        $valueInCents = (int)($amount * 100);
        $minValue = defined('OPENPIX_MIN_CHARGE_VALUE') ? OPENPIX_MIN_CHARGE_VALUE : 100;
        $maxValue = defined('OPENPIX_MAX_CHARGE_VALUE') ? OPENPIX_MAX_CHARGE_VALUE : 100000000;
        
        if ($valueInCents < $minValue) {
            throw new InvalidArgumentException('Valor mínimo: R$ ' . ($minValue / 100));
        }
        
        if ($valueInCents > $maxValue) {
            throw new InvalidArgumentException('Valor máximo: R$ ' . ($maxValue / 100));
        }
        
        // Validar correlation_id
        if (strlen($data['correlation_id']) > 255) {
            throw new InvalidArgumentException('correlation_id muito longo (máximo 255 caracteres)');
        }
        
        // Validar caracteres especiais no correlation_id
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $data['correlation_id'])) {
            throw new InvalidArgumentException('correlation_id deve conter apenas letras, números, _ e -');
        }
    }
    
    /**
     * Preparar dados do customer
     */
    private function prepareCustomerData($customer) {
        $customerData = [];
        
        if (isset($customer['name']) && !empty($customer['name'])) {
            $customerData['name'] = trim($customer['name']);
        }
        
        if (isset($customer['email']) && !empty($customer['email'])) {
            if (filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
                $customerData['email'] = trim($customer['email']);
            }
        }
        
        if (isset($customer['phone']) && !empty($customer['phone'])) {
            $customerData['phone'] = preg_replace('/\D/', '', $customer['phone']);
        }
        
        if (isset($customer['cpf']) && !empty($customer['cpf'])) {
            $cpf = preg_replace('/\D/', '', $customer['cpf']);
            if (strlen($cpf) === 11) {
                $customerData['taxID'] = [
                    'taxID' => $cpf,
                    'type' => 'BR:CPF'
                ];
            }
        }
        
        if (isset($customer['cnpj']) && !empty($customer['cnpj'])) {
            $cnpj = preg_replace('/\D/', '', $customer['cnpj']);
            if (strlen($cnpj) === 14) {
                $customerData['taxID'] = [
                    'taxID' => $cnpj,
                    'type' => 'BR:CNPJ'
                ];
            }
        }
        
        return $customerData;
    }
    
    /**
     * Log com debug melhorado
     */
    private function log($message, $data = null) {
        if (!defined('LOG_PIX_TRANSACTIONS') || !LOG_PIX_TRANSACTIONS) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "{$timestamp} [OpenPix] {$message}";
        
        if ($data) {
            $logMessage .= ' | Data: ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        
        error_log($logMessage);
        
        // Salvar em arquivo de log específico se configurado
        if (defined('LOGS_DIR') && is_dir(LOGS_DIR)) {
            $logFile = LOGS_DIR . '/openpix_' . date('Y-m-d') . '.log';
            file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
        
        // Se debug ativo, também exibir no console (para desenvolvimento)
        if ($this->debug && php_sapi_name() === 'cli') {
            echo $logMessage . PHP_EOL;
        }
    }
    
    /**
     * Obter informações de status detalhadas
     */
    public function getStatusInfo() {
        $apiKeyValidation = $this->validateApiKeyFormat($this->apiKey);
        
        return [
            'api_key_configured' => !empty($this->apiKey),
            'api_key_valid' => $apiKeyValidation['valid'],
            'api_key_validation' => $apiKeyValidation,
            'api_key_masked' => !empty($this->apiKey) ? substr($this->apiKey, 0, 15) . '...' : 'Não configurada',
            'base_url' => $this->baseUrl,
            'timeout' => $this->timeout . 's',
            'debug_mode' => $this->debug,
            'ssl_verification' => $this->sslVerification,
            'max_retries' => $this->maxRetries,
            'retry_delay' => $this->retryDelay . 'ms',
            'min_charge_value' => defined('OPENPIX_MIN_CHARGE_VALUE') ? self::formatCurrency(OPENPIX_MIN_CHARGE_VALUE) : 'R$ 1,00',
            'max_charge_value' => defined('OPENPIX_MAX_CHARGE_VALUE') ? self::formatCurrency(OPENPIX_MAX_CHARGE_VALUE) : 'R$ 1.000.000,00',
            'pix_expiration' => defined('PIX_EXPIRATION_MINUTES') ? PIX_EXPIRATION_MINUTES . ' minutos' : '30 minutos',
            'logging_enabled' => defined('LOG_PIX_TRANSACTIONS') ? LOG_PIX_TRANSACTIONS : false
        ];
    }
    
    /**
     * Formatar valor para exibição
     */
    public static function formatCurrency($valueInCents) {
        return 'R$ ' . number_format($valueInCents / 100, 2, ',', '.');
    }
    
    /**
     * Converter valor de reais para centavos
     */
    public static function toCents($value) {
        return (int)round($value * 100);
    }
    
    /**
     * Converter valor de centavos para reais
     */
    public static function toReais($valueInCents) {
        return $valueInCents / 100;
    }
}
?>