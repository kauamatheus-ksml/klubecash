<?php
/**
 * Cliente OpenPix para Klube Cash
 * Implementação completa da API OpenPix para pagamentos PIX
 */

class OpenPixClient {
    private $baseUrl;
    private $apiKey;
    private $timeout;
    private $debug;
    
    public function __construct() {
        $this->baseUrl = OPENPIX_BASE_URL;
        $this->apiKey = OPENPIX_API_KEY;
        $this->timeout = OPENPIX_TIMEOUT;
        $this->debug = OPENPIX_DEBUG;
        
        if (empty($this->apiKey)) {
            throw new Exception('OPENPIX_API_KEY não configurada');
        }
    }
    
    /**
     * Criar uma cobrança PIX
     */
    public function createCharge($data) {
        // Validar dados obrigatórios
        $this->validateChargeData($data);
        
        // Preparar payload
        $payload = [
            'value' => (int)($data['amount'] * 100), // Converter para centavos
            'correlationID' => $data['correlation_id'],
            'comment' => $data['comment'] ?? 'Pagamento Klube Cash',
            'expiresIn' => PIX_EXPIRATION_MINUTES * 60 // Converter para segundos
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
            $charge = $response['data']['charge'];
            
            $result = [
                'status' => true,
                'charge_id' => $charge['correlationID'],
                'transaction_id' => $charge['transactionID'] ?? null,
                'qr_code' => $charge['brCode'],
                'qr_code_image' => $charge['qrCodeImage'],
                'payment_link' => $charge['paymentLinkUrl'] ?? null,
                'expires_at' => $charge['expiresDate'],
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
    }
    
    /**
     * Verificar status de uma cobrança
     */
    public function getChargeStatus($chargeId) {
        $this->log("Verificando status da cobrança: {$chargeId}");
        
        $response = $this->makeRequest('GET', "/charge/{$chargeId}");
        
        if ($response['success']) {
            $charge = $response['data']['charge'];
            
            return [
                'status' => true,
                'charge_status' => $charge['status'],
                'paid_at' => $charge['paidAt'] ?? null,
                'value' => $charge['value'],
                'customer' => $charge['customer'] ?? null,
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
            'paid_at' => null
        ];
        
        // Verificar se é um evento de cobrança
        if (isset($payload['charge'])) {
            $charge = $payload['charge'];
            
            $result['status'] = true;
            $result['event_type'] = 'charge';
            $result['charge_id'] = $charge['correlationID'];
            $result['transaction_id'] = $charge['transactionID'] ?? null;
            $result['charge_status'] = $charge['status'];
            
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
        
        $headers = [
            'Authorization: ' . $this->apiKey,
            'Content-Type: application/json',
            'User-Agent: KlubeCash/2.1 OpenPix-Integration',
            'Accept: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_MAXREDIRS => 0
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Log da requisição se debug ativo
        if ($this->debug) {
            $this->log("API Request: {$method} {$url}", [
                'data' => $data,
                'http_code' => $httpCode,
                'response' => $response,
                'curl_error' => $error
            ]);
        }
        
        if ($error) {
            return [
                'success' => false,
                'message' => "Erro de conexão: {$error}",
                'error_code' => 'CURL_ERROR'
            ];
        }
        
        $responseData = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'data' => $responseData,
                'http_code' => $httpCode
            ];
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
                'raw_response' => $response
            ];
        }
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
        if ($valueInCents < OPENPIX_MIN_CHARGE_VALUE) {
            throw new InvalidArgumentException('Valor mínimo: R$ ' . (OPENPIX_MIN_CHARGE_VALUE / 100));
        }
        
        if ($valueInCents > OPENPIX_MAX_CHARGE_VALUE) {
            throw new InvalidArgumentException('Valor máximo: R$ ' . (OPENPIX_MAX_CHARGE_VALUE / 100));
        }
        
        // Validar correlation_id
        if (strlen($data['correlation_id']) > 255) {
            throw new InvalidArgumentException('correlation_id muito longo (máximo 255 caracteres)');
        }
    }
    
    /**
     * Preparar dados do customer
     */
    private function prepareCustomerData($customer) {
        $customerData = [];
        
        if (isset($customer['name'])) {
            $customerData['name'] = $customer['name'];
        }
        
        if (isset($customer['email'])) {
            $customerData['email'] = $customer['email'];
        }
        
        if (isset($customer['phone'])) {
            $customerData['phone'] = $customer['phone'];
        }
        
        if (isset($customer['cpf'])) {
            $customerData['taxID'] = [
                'taxID' => preg_replace('/\D/', '', $customer['cpf']),
                'type' => 'BR:CPF'
            ];
        }
        
        if (isset($customer['cnpj'])) {
            $customerData['taxID'] = [
                'taxID' => preg_replace('/\D/', '', $customer['cnpj']),
                'type' => 'BR:CNPJ'
            ];
        }
        
        return $customerData;
    }
    
    /**
     * Gerar token para webhook
     */
    private function generateWebhookToken() {
        return hash('sha256', $this->apiKey . time());
    }
    
    /**
     * Log de debug
     */
    private function log($message, $data = null) {
        if (!LOG_PIX_TRANSACTIONS) {
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
            $testCharge = [
                'amount' => 1.00,
                'correlation_id' => 'test_connection_' . time(),
                'comment' => 'Teste de conectividade Klube Cash'
            ];
            
            $result = $this->createCharge($testCharge);
            
            if ($result['status']) {
                return [
                    'status' => true,
                    'message' => 'Conexão com OpenPix estabelecida com sucesso',
                    'charge_id' => $result['charge_id']
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'Erro na conexão: ' . $result['message']
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
}
?>