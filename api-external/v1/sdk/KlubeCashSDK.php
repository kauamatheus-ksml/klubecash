<?php

/**
 * KlubeCash External API SDK
 * 
 * SDK em PHP para facilitar a integração com a API externa do KlubeCash
 * 
 * @version 1.0.0
 * @author KlubeCash Team
 */
class KlubeCashSDK {
    private $apiKey;
    private $baseUrl;
    private $timeout;
    private $debug;
    
    public function __construct($apiKey, $baseUrl, $options = []) {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/') . '/api-external/v1';
        $this->timeout = $options['timeout'] ?? 30;
        $this->debug = $options['debug'] ?? false;
    }
    
    /**
     * Gerenciar usuários
     */
    public function users() {
        return new KlubeCashUsers($this);
    }
    
    /**
     * Gerenciar lojas
     */
    public function stores() {
        return new KlubeCashStores($this);
    }
    
    /**
     * Gerenciar transações
     */
    public function transactions() {
        return new KlubeCashTransactions($this);
    }
    
    /**
     * Gerenciar cashback
     */
    public function cashback() {
        return new KlubeCashCashback($this);
    }
    
    /**
     * Fazer requisição HTTP
     */
    public function request($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-API-Key: ' . $this->apiKey,
                'User-Agent: KlubeCash-PHP-SDK/1.0.0'
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3
        ]);
        
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        if ($this->debug) {
            curl_setopt($ch, CURLOPT_VERBOSE, true);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: {$error}");
        }
        
        $data = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $message = $data['message'] ?? 'Unknown error';
            throw new KlubeCashAPIException($message, $httpCode, $data);
        }
        
        return $data;
    }
    
    /**
     * Informações da API
     */
    public function info() {
        return $this->request('GET', '/auth/info');
    }
    
    /**
     * Status de saúde da API
     */
    public function health() {
        return $this->request('GET', '/auth/health');
    }
}

/**
 * Gerenciamento de usuários
 */
class KlubeCashUsers {
    private $sdk;
    
    public function __construct(KlubeCashSDK $sdk) {
        $this->sdk = $sdk;
    }
    
    public function list($filters = []) {
        $query = http_build_query($filters);
        $endpoint = '/users' . ($query ? '?' . $query : '');
        return $this->sdk->request('GET', $endpoint);
    }
    
    public function get($id) {
        return $this->sdk->request('GET', "/users/{$id}");
    }
    
    public function getByEmail($email) {
        return $this->sdk->request('GET', "/users/email?email=" . urlencode($email));
    }
    
    public function create($data) {
        return $this->sdk->request('POST', '/users', $data);
    }
    
    public function update($id, $data) {
        return $this->sdk->request('PUT', "/users/{$id}", $data);
    }
    
    public function delete($id) {
        return $this->sdk->request('DELETE', "/users/{$id}");
    }
    
    public function getBalance($id, $storeId = null) {
        $query = $storeId ? "?store_id={$storeId}" : '';
        return $this->sdk->request('GET', "/users/{$id}/balance{$query}");
    }
    
    public function getTransactions($id, $filters = []) {
        $query = http_build_query($filters);
        $endpoint = "/users/{$id}/transactions" . ($query ? '?' . $query : '');
        return $this->sdk->request('GET', $endpoint);
    }
}

/**
 * Gerenciamento de lojas
 */
class KlubeCashStores {
    private $sdk;
    
    public function __construct(KlubeCashSDK $sdk) {
        $this->sdk = $sdk;
    }
    
    public function list($filters = []) {
        $query = http_build_query($filters);
        $endpoint = '/stores' . ($query ? '?' . $query : '');
        return $this->sdk->request('GET', $endpoint);
    }
    
    public function get($id) {
        return $this->sdk->request('GET', "/stores/{$id}");
    }
    
    public function getByCNPJ($cnpj) {
        return $this->sdk->request('GET', "/stores/cnpj?cnpj=" . urlencode($cnpj));
    }
    
    public function create($data) {
        return $this->sdk->request('POST', '/stores', $data);
    }
    
    public function update($id, $data) {
        return $this->sdk->request('PUT', "/stores/{$id}", $data);
    }
    
    public function delete($id) {
        return $this->sdk->request('DELETE', "/stores/{$id}");
    }
    
    public function getStats($id, $days = 30) {
        return $this->sdk->request('GET', "/stores/{$id}/stats?days={$days}");
    }
    
    public function getTransactions($id, $filters = []) {
        $query = http_build_query($filters);
        $endpoint = "/stores/{$id}/transactions" . ($query ? '?' . $query : '');
        return $this->sdk->request('GET', $endpoint);
    }
    
    public function getCashbackRules($id) {
        return $this->sdk->request('GET', "/stores/{$id}/cashback-rules");
    }
}

/**
 * Gerenciamento de transações
 */
class KlubeCashTransactions {
    private $sdk;
    
    public function __construct(KlubeCashSDK $sdk) {
        $this->sdk = $sdk;
    }
    
    public function list($filters = []) {
        $query = http_build_query($filters);
        $endpoint = '/transactions' . ($query ? '?' . $query : '');
        return $this->sdk->request('GET', $endpoint);
    }
    
    public function get($id) {
        return $this->sdk->request('GET', "/transactions/{$id}");
    }
    
    public function create($data) {
        return $this->sdk->request('POST', '/transactions', $data);
    }
    
    public function updateStatus($id, $status, $reason = null) {
        $data = ['status' => $status];
        if ($reason) {
            $data['reason'] = $reason;
        }
        return $this->sdk->request('PUT', "/transactions/{$id}/status", $data);
    }
    
    public function getStats($filters = []) {
        $query = http_build_query($filters);
        $endpoint = '/transactions/stats' . ($query ? '?' . $query : '');
        return $this->sdk->request('GET', $endpoint);
    }
}

/**
 * Gerenciamento de cashback
 */
class KlubeCashCashback {
    private $sdk;
    
    public function __construct(KlubeCashSDK $sdk) {
        $this->sdk = $sdk;
    }
    
    public function calculate($data) {
        return $this->sdk->request('POST', '/cashback/calculate', $data);
    }
    
    public function getUserCashback($userId) {
        return $this->sdk->request('GET', "/cashback/user/{$userId}");
    }
}

/**
 * Exceção customizada para erros da API
 */
class KlubeCashAPIException extends Exception {
    private $httpCode;
    private $responseData;
    
    public function __construct($message, $httpCode, $responseData = null) {
        parent::__construct($message);
        $this->httpCode = $httpCode;
        $this->responseData = $responseData;
    }
    
    public function getHttpCode() {
        return $this->httpCode;
    }
    
    public function getResponseData() {
        return $this->responseData;
    }
    
    public function getValidationErrors() {
        return $this->responseData['details']['validation_errors'] ?? [];
    }
}

// Exemplo de uso:
/*
try {
    $sdk = new KlubeCashSDK('kc_sua_api_key_aqui', 'https://seu-dominio.com');
    
    // Criar usuário
    $user = $sdk->users()->create([
        'name' => 'João Silva',
        'email' => 'joao@email.com',
        'password' => 'senha123'
    ]);
    
    echo "Usuário criado: ID " . $user['data']['id'] . "\n";
    
    // Listar lojas aprovadas
    $stores = $sdk->stores()->list(['status' => 'aprovado']);
    echo "Lojas encontradas: " . count($stores['data']) . "\n";
    
    // Criar transação
    if (!empty($stores['data'])) {
        $transaction = $sdk->transactions()->create([
            'user_id' => $user['data']['id'],
            'store_id' => $stores['data'][0]['id'],
            'total_amount' => 150.00
        ]);
        
        echo "Transação criada: ID " . $transaction['data']['id'] . "\n";
    }
    
} catch (KlubeCashAPIException $e) {
    echo "Erro da API: " . $e->getMessage() . " (HTTP " . $e->getHttpCode() . ")\n";
    
    if ($e->getValidationErrors()) {
        echo "Erros de validação:\n";
        foreach ($e->getValidationErrors() as $field => $error) {
            echo "- {$field}: {$error}\n";
        }
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
*/
?>