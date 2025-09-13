<?php
// utils/OpenPixClient.php
class OpenPixClient {
    private $baseUrl = 'https://api.openpix.com.br/api/v1';
    private $apiKey;
    
    public function __construct() {
        $this->apiKey = OPENPIX_API_KEY; // Definir em constants.php
    }
    
    public function createCharge($data) {
        $endpoint = '/charge';
        
        $payload = [
            'value' => $data['value'],
            'comment' => $data['comment'],
            'correlationID' => $data['correlationID'],
            'customer' => $data['customer'],
            'expiresIn' => 3600 // 1 hora para expirar
        ];
        
        return $this->makeRequest('POST', $endpoint, $payload);
    }
    
    public function getChargeStatus($chargeId) {
        $endpoint = "/charge/{$chargeId}";
        return $this->makeRequest('GET', $endpoint);
    }
    
    private function makeRequest($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $headers = [
            'Authorization: ' . $this->apiKey,
            'Content-Type: application/json'
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
            return ['status' => false, 'message' => 'Erro na API OpenPix', 'http_code' => $httpCode];
        }
    }
}
?>