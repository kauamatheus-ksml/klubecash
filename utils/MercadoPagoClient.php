<?php
// utils/MercadoPagoClient.php
class MercadoPagoClient {
    private $accessToken;
    private $baseUrl = 'https://api.mercadopago.com';
    
    public function __construct() {
        $this->accessToken = MP_ACCESS_TOKEN;
    }
    
    public function createPixPayment($data) {
        $endpoint = '/v1/payments';
        
        // Validar dados obrigatórios
        if (empty($data['amount']) || empty($data['payer_email'])) {
            return ['status' => false, 'message' => 'Dados obrigatórios faltando: amount ou payer_email'];
        }
        
        $payload = [
            'transaction_amount' => (float) $data['amount'],
            'payment_method_id' => 'pix',
            'payer' => [
                'email' => $data['payer_email'],
                'first_name' => $data['payer_name'] ?? 'Lojista',
                'last_name' => $data['payer_lastname'] ?? 'Klube Cash'
            ]
        ];
        
        // Adicionar campos opcionais se fornecidos
        if (!empty($data['description'])) {
            $payload['description'] = $data['description'];
        }
        
        if (!empty($data['external_reference'])) {
            $payload['external_reference'] = $data['external_reference'];
        }
        
        if (!empty($data['payment_id']) || !empty($data['store_id'])) {
            $payload['metadata'] = [];
            if (!empty($data['payment_id'])) $payload['metadata']['payment_id'] = $data['payment_id'];
            if (!empty($data['store_id'])) $payload['metadata']['store_id'] = $data['store_id'];
        }
        
        // Log para debug
        error_log("MP Payload: " . json_encode($payload));
        
        return $this->makeRequest('POST', $endpoint, $payload);
    }
    
    public function getPaymentStatus($paymentId) {
        $endpoint = "/v1/payments/{$paymentId}";
        return $this->makeRequest('GET', $endpoint);
    }
    
    private function makeRequest($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json',
            'Accept: application/json',
            'X-Idempotency-Key: ' . uniqid('klube_', true)
        ];
        
        // Log para debug
        error_log("MP Request: $method $url");
        error_log("MP Headers: " . json_encode($headers));
        if ($data) error_log("MP Data: " . json_encode($data));
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSL_VERIFYPEER => false, // Para teste apenas
            CURLOPT_SSL_VERIFYHOST => false, // Para teste apenas
            CURLOPT_USERAGENT => 'KlubeCash/1.0',
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Log detalhado para debug
        error_log("MP Response Code: $httpCode");
        error_log("MP Response Body: " . ($response ?: 'empty'));
        if ($curlError) error_log("MP cURL Error: $curlError");
        
        if ($curlError) {
            return ['status' => false, 'message' => 'Erro de conexão: ' . $curlError];
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $decodedResponse = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['status' => false, 'message' => 'Resposta inválida da API: ' . json_last_error_msg()];
            }
            return ['status' => true, 'data' => $decodedResponse];
        } else {
            // Tentar decodificar erro
            $errorData = json_decode($response, true);
            $errorMessage = 'Erro HTTP ' . $httpCode;
            
            if ($errorData && isset($errorData['message'])) {
                $errorMessage .= ': ' . $errorData['message'];
            } elseif ($errorData && isset($errorData['error'])) {
                $errorMessage .= ': ' . $errorData['error'];
            }
            
            return [
                'status' => false, 
                'message' => $errorMessage,
                'http_code' => $httpCode,
                'response' => $response
            ];
        }
    }
}
?>