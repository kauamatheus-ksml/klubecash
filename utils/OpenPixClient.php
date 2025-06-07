<?php
/**
 * Cliente OpenPix para integração com a API do OpenPix
 * Klube Cash v2.1 - Versão Completa e Otimizada
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
        
        if (empty($this->apiKey)) {
            throw new Exception('OPENPIX_API_KEY não configurada');
        }
        
        // Log de inicialização
        $this->log('OpenPixClient inicializado', [
            'baseUrl' => $this->baseUrl,
            'timeout' => $this->timeout,
            'debug' => $this->debug,
            'maxRetries' => $this->maxRetries
        ]);
    }
    
    /**
     * Criar uma cobrança PIX
     */
    public function createCharge($data) {
        try {
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
                    'error_code' => $response['error_code'] ?? null
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
                'message' => $response['message']
            ];
        }
    }
    
    /**
     * Listar cobranças
     */
    public function listCharges($filters = []) {
        $queryParams = [];
        
        if (isset($filters['start_date'])) {
            $queryParams['start'] = $filters['start_date'];
        }
        
        if (isset($filters['end_date'])) {
            $queryParams['end'] = $filters['end_date'];
        }
        
        if (isset($filters['status'])) {
            $queryParams['status'] = $filters['status'];
        }
        
        if (isset($filters['limit'])) {
            $queryParams['limit'] = $filters['limit'];
        }
        
        if (isset($filters['offset'])) {
            $queryParams['offset'] = $filters['offset'];
        }
        
        $endpoint = '/charge';
        if (!empty($queryParams)) {
            $endpoint .= '?' . http_build_query($queryParams);
        }
        
        return $this->makeRequest('GET', $endpoint);
    }
    
    /**
     * Criar webhook
     */
    public function createWebhook($webhookUrl, $name = 'Klube Cash Webhook') {
        $payload = [
            'webhook' => [
                'name' => $name,
                'url' => $webhookUrl,
                'authorization' => 'Bearer ' . $this->generateWebhookToken(),
                'isActive' => true
            ]
        ];
        
        return $this->makeRequest('POST', '/webhook', $payload);
    }
    
    /**
     * Validar webhook recebido
     */
    public function validateWebhook($payload, $signature = null) {
        // OpenPix não usa assinatura por padrão, mas validamos o formato
        if (!isset($payload['charge']) && !isset($payload['pix'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Processar evento de webhook
     */
    public function processWebhookEvent($payload) {
        $this->log('Processando evento de webhook', $payload);
        
        $result = [
            'status' => false,
            'event_type' => null,
            'charge_id' => null,
            'transaction_id' => null,
            'charge_status' => null,
            'paid_at' => null,
            'value' => null
        ];
        
        // Verificar se é um evento de cobrança
        if (isset($payload['charge'])) {
            $charge = $payload['charge'];
            
            $result['status'] = true;
            $result['event_type'] = 'charge';
            $result['charge_id'] = $charge['correlationID'];
            $result['transaction_id'] = $charge['transactionID'] ?? null;
            $result['charge_status'] = $charge['status'];
            $result['value'] = $charge['value'] ?? null;
            
            if ($charge['status'] === 'COMPLETED' || $charge['status'] === 'CONFIRMED') {
                $result['paid_at'] = $charge['paidAt'] ?? date('Y-m-d H:i:s');
            }
        }
        
        // Verificar se é um evento de PIX
        if (isset($payload['pix'])) {
            $pix = $payload['pix'];
            
            $result['status'] = true;
            $result['event_type'] = 'pix';
            $result['transaction_id'] = $pix['transactionID'] ?? null;
            $result['charge_id'] = $pix['correlationID'] ?? null;
            $result['value'] = $pix['value'] ?? null;
            
            if (isset($pix['charge'])) {
                $result['charge_status'] = $pix['charge']['status'];
            }
        }
        
        $this->log('Evento processado', $result);
        return $result;
    }
    
    /**
     * Fazer requisição para a API
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
        while ($attempts < $this->maxRetries) {
            $attempts++;
            
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
                CURLOPT_MAXREDIRS => 0
            ]);
            
            if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $responseTime = microtime(true) - $startTime;
            curl_close($ch);
            
            // Log da requisição se debug ativo
            if ($this->debug) {
                $this->log("API Request: {$method} {$url} (tentativa {$attempts})", [
                    'data' => $data,
                    'http_code' => $httpCode,
                    'response_time' => round($responseTime * 1000, 2) . 'ms',
                    'response' => $response,
                    'curl_error' => $error
                ]);
            }
            
            if ($error) {
                if ($attempts < $this->maxRetries) {
                    usleep($this->retryDelay * 1000); // Converter para microsegundos
                    continue;
                }
                return [
                    'success' => false,
                    'message' => "Erro de conexão: {$error}",
                    'error_code' => 'CURL_ERROR',
                    'attempts' => $attempts
                ];
            }
            
            $responseData = json_decode($response, true);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                return [
                    'success' => true,
                    'data' => $responseData,
                    'http_code' => $httpCode,
                    'response_time' => $responseTime,
                    'attempts' => $attempts
                ];
            } else if ($httpCode >= 500 && $attempts < $this->maxRetries) {
                // Tentar novamente para erros de servidor
                usleep($this->retryDelay * 1000);
                continue;
            } else {
                $errorMessage = 'Erro na API OpenPix';
                
                if ($responseData && isset($responseData['error'])) {
                    $errorMessage = $responseData['error'];
                } elseif ($responseData && isset($responseData['message'])) {
                    $errorMessage = $responseData['message'];
                }
                
                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'http_code' => $httpCode,
                    'error_code' => $responseData['code'] ?? 'API_ERROR',
                    'raw_response' => $response,
                    'attempts' => $attempts
                ];
            }
        }
        
        return [
            'success' => false,
            'message' => 'Esgotadas todas as tentativas de conexão',
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
     * Gerar token para webhook
     */
    private function generateWebhookToken() {
        return hash('sha256', $this->apiKey . time() . rand(1000, 9999));
    }
    
    /**
     * Log de debug
     */
    private function log($message, $data = null) {
        if (!defined('LOG_PIX_TRANSACTIONS') || !LOG_PIX_TRANSACTIONS) {
            return;
        }
        
        $logMessage = date('Y-m-d H:i:s') . " [OpenPix] {$message}";
        
        if ($data) {
            $logMessage .= ' | Data: ' . json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        
        error_log($logMessage);
        
        // Salvar em arquivo de log específico se configurado
        if (defined('LOGS_DIR') && is_dir(LOGS_DIR)) {
            $logFile = LOGS_DIR . '/openpix_' . date('Y-m-d') . '.log';
            file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }
    
    /**
     * Teste de conectividade
     */
    public function testConnection() {
        try {
            // Fazer uma requisição simples para testar a conectividade
            $response = $this->makeRequest('GET', '/charge?limit=1');
            
            if ($response['success']) {
                return [
                    'status' => true,
                    'message' => 'Conexão com OpenPix estabelecida com sucesso!',
                    'response_time' => isset($response['response_time']) ? round($response['response_time'] * 1000, 2) . 'ms' : null,
                    'attempts' => $response['attempts'] ?? 1
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'Erro na conexão: ' . $response['message'],
                    'http_code' => $response['http_code'] ?? null,
                    'attempts' => $response['attempts'] ?? 1
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => 'Erro na conexão: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obter informações da conta
     */
    public function getAccountInfo() {
        return $this->makeRequest('GET', '/account');
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
    
    /**
     * Obter informações de status da configuração
     */
    public function getStatusInfo() {
        return [
            'api_key_configured' => !empty($this->apiKey),
            'api_key_masked' => !empty($this->apiKey) ? substr($this->apiKey, 0, 10) . '...' : 'Não configurada',
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
     * Validar CPF
     */
    public static function validateCPF($cpf) {
        $cpf = preg_replace('/\D/', '', $cpf);
        
        if (strlen($cpf) !== 11) {
            return false;
        }
        
        // Verificar se todos os dígitos são iguais
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        
        // Validar dígitos verificadores
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validar CNPJ
     */
    public static function validateCNPJ($cnpj) {
        $cnpj = preg_replace('/\D/', '', $cnpj);
        
        if (strlen($cnpj) !== 14) {
            return false;
        }
        
        // Verificar se todos os dígitos são iguais
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }
        
        // Validar primeiro dígito verificador
        $soma = 0;
        $peso = 2;
        for ($i = 11; $i >= 0; $i--) {
            $soma += $cnpj[$i] * $peso;
            $peso = ($peso == 9) ? 2 : $peso + 1;
        }
        $resto = $soma % 11;
        $dv1 = ($resto < 2) ? 0 : 11 - $resto;
        
        if ($cnpj[12] != $dv1) {
            return false;
        }
        
        // Validar segundo dígito verificador
        $soma = 0;
        $peso = 2;
        for ($i = 12; $i >= 0; $i--) {
            $soma += $cnpj[$i] * $peso;
            $peso = ($peso == 9) ? 2 : $peso + 1;
        }
        $resto = $soma % 11;
        $dv2 = ($resto < 2) ? 0 : 11 - $resto;
        
        return $cnpj[13] == $dv2;
    }
}
?>