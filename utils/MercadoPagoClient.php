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
        
        $payload = [
            'transaction_amount' => $data['amount'],
            'payment_method_id' => 'pix',
            'payer' => [
                'email' => $data['payer_email'],
                'first_name' => $data['payer_name'] ?? 'Lojista',
                'last_name' => $data['payer_lastname'] ?? 'Klube Cash'
            ],
            'description' => $data['description'] ?? 'Pagamento comissão Klube Cash',
            'notification_url' => MP_WEBHOOK_URL,
            'external_reference' => $data['external_reference'] ?? '',
            'metadata' => [
                'payment_id' => $data['payment_id'] ?? '',
                'store_id' => $data['store_id'] ?? ''
            ]
        ];
        
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
            'X-Idempotency-Key: ' . uniqid()
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['status' => true, 'data' => json_decode($response, true)];
        } else {
            return ['status' => false, 'message' => 'Erro na API Mercado Pago', 'http_code' => $httpCode];
        }
    }
}
?>