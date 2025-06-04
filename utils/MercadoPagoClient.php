<?php
// utils/MercadoPagoClient.php

/**
 * Cliente para integração com a API do Mercado Pago
 * Esta classe é responsável por criar pagamentos PIX e consultar seu status
 * 
 * O Mercado Pago funciona assim:
 * 1. Enviamos dados do pagamento para eles
 * 2. Eles retornam um QR Code e dados do PIX
 * 3. Cliente paga o PIX
 * 4. MP nos notifica via webhook quando o pagamento é aprovado
 */
class MercadoPagoClient {
    private $accessToken;
    private $baseUrl = 'https://api.mercadopago.com';
    private $timeout = 30; // Timeout padrão para requisições
    
    public function __construct() {
        // Verificar se as constantes estão definidas antes de usar
        if (!defined('MP_ACCESS_TOKEN')) {
            throw new Exception('MP_ACCESS_TOKEN não está definido. Verifique o arquivo constants.php');
        }
        
        $this->accessToken = MP_ACCESS_TOKEN;
        
        // Validar se o token não está vazio
        if (empty($this->accessToken)) {
            throw new Exception('MP_ACCESS_TOKEN está vazio. Configure suas credenciais do Mercado Pago.');
        }
        
        // Log de inicialização para debug
        error_log("MercadoPagoClient inicializado com token: " . substr($this->accessToken, 0, 20) . "...");
    }
    
    /**
     * Criar um pagamento PIX no Mercado Pago
     * 
     * Este método pega os dados da nossa loja e cria um pagamento PIX.
     * O MP retorna um QR Code que o cliente pode escanear para pagar.
     * 
     * @param array $data Dados do pagamento contendo:
     *                   - amount: valor em reais (obrigatório)
     *                   - payer_email: email do pagador (obrigatório)  
     *                   - payer_name: nome do pagador (opcional)
     *                   - payer_lastname: sobrenome do pagador (opcional)
     *                   - description: descrição do pagamento (opcional)
     *                   - external_reference: referência externa (opcional)
     *                   - payment_id: ID do nosso pagamento interno (opcional)
     *                   - store_id: ID da loja (opcional)
     * 
     * @return array Resultado da operação com status e dados do pagamento
     */
    public function createPixPayment($data) {
        $endpoint = '/v1/payments';
        
        // Primeiro, validamos os dados obrigatórios
        // Sem estes dados, o Mercado Pago rejeitará a requisição
        if (empty($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
            return ['status' => false, 'message' => 'Valor do pagamento é obrigatório e deve ser maior que zero'];
        }
        
        if (empty($data['payer_email']) || !filter_var($data['payer_email'], FILTER_VALIDATE_EMAIL)) {
            return ['status' => false, 'message' => 'Email do pagador é obrigatório e deve ser válido'];
        }
        
        // Montar o payload para enviar ao Mercado Pago
        // Este é o formato que a API do MP espera receber
        $payload = [
            // Valor do pagamento (deve ser um número decimal)
            'transaction_amount' => (float) $data['amount'],
            
            // Especifica que queremos um pagamento PIX
            'payment_method_id' => 'pix',
            
            // Dados do pagador (pessoa que vai pagar)
            'payer' => [
                'email' => trim($data['payer_email']),
                'first_name' => $data['payer_name'] ?? 'Lojista',
                'last_name' => $data['payer_lastname'] ?? 'Klube Cash'
            ]
        ];
        
        // Adicionar campos opcionais se foram fornecidos
        // Estes campos ajudam no controle e rastreamento do pagamento
        
        if (!empty($data['description'])) {
            // Descrição que aparece no extrato do cliente
            $payload['description'] = substr(trim($data['description']), 0, 255); // MP limita a 255 caracteres
        }
        
        if (!empty($data['external_reference'])) {
            // Nossa referência interna para identificar o pagamento
            $payload['external_reference'] = trim($data['external_reference']);
        }
        
        // Metadados para armazenar informações extras
        // Úteis para identificar o pagamento nos nossos sistemas
        if (!empty($data['payment_id']) || !empty($data['store_id'])) {
            $payload['metadata'] = [];
            if (!empty($data['payment_id'])) {
                $payload['metadata']['payment_id'] = (string) $data['payment_id'];
            }
            if (!empty($data['store_id'])) {
                $payload['metadata']['store_id'] = (string) $data['store_id'];
            }
        }
        
        // Configurar URL de notificação se estiver definida
        // Esta é a URL que o MP chamará quando o pagamento for processado
        if (defined('MP_WEBHOOK_URL') && !empty(MP_WEBHOOK_URL)) {
            $payload['notification_url'] = MP_WEBHOOK_URL;
        }
        
        // Log detalhado para debug - importante para resolver problemas
        error_log("MP createPixPayment - Payload preparado: " . json_encode($payload, JSON_PRETTY_PRINT));
        
        // Fazer a requisição para o Mercado Pago
        $response = $this->makeRequest('POST', $endpoint, $payload);
        
        // Se a requisição foi bem-sucedida, extrair os dados do PIX
        if ($response['status'] && isset($response['data'])) {
            $mpData = $response['data'];
            
            // Verificar se recebemos os dados necessários do PIX
            $qrCode = '';
            $qrCodeBase64 = '';
            
            // O MP retorna os dados do PIX dentro de point_of_interaction
            if (isset($mpData['point_of_interaction']['transaction_data'])) {
                $transactionData = $mpData['point_of_interaction']['transaction_data'];
                $qrCode = $transactionData['qr_code'] ?? '';
                $qrCodeBase64 = $transactionData['qr_code_base64'] ?? '';
            }
            
            // Validar se recebemos os dados essenciais
            if (empty($qrCode) || empty($qrCodeBase64)) {
                error_log("MP createPixPayment - ERRO: QR Code não foi gerado. Resposta: " . json_encode($mpData));
                return [
                    'status' => false, 
                    'message' => 'Mercado Pago não gerou o QR Code PIX. Tente novamente.',
                    'mp_response' => $mpData
                ];
            }
            
            // Retornar os dados organizados para nosso sistema usar
            return [
                'status' => true,
                'data' => [
                    'mp_payment_id' => $mpData['id'],
                    'qr_code' => $qrCode,
                    'qr_code_base64' => $qrCodeBase64,
                    'status' => $mpData['status'],
                    'status_detail' => $mpData['status_detail'] ?? '',
                    'amount' => $mpData['transaction_amount'],
                    'currency_id' => $mpData['currency_id'] ?? 'BRL',
                    'date_created' => $mpData['date_created'] ?? '',
                    'external_reference' => $mpData['external_reference'] ?? ''
                ]
            ];
        }
        
        // Se chegou aqui, houve algum erro na requisição
        return $response;
    }
    
    /**
     * Consultar o status de um pagamento no Mercado Pago
     * 
     * Este método é usado pelo webhook e pela verificação manual
     * para saber se um pagamento foi aprovado, rejeitado, etc.
     * 
     * @param string $paymentId ID do pagamento no Mercado Pago
     * @return array Resultado com status e dados do pagamento
     */
    public function getPaymentStatus($paymentId) {
        // Validar o ID do pagamento
        if (empty($paymentId)) {
            return ['status' => false, 'message' => 'ID do pagamento é obrigatório'];
        }
        
        $endpoint = "/v1/payments/{$paymentId}";
        
        error_log("MP getPaymentStatus - Consultando pagamento: {$paymentId}");
        
        $response = $this->makeRequest('GET', $endpoint);
        
        // Se a consulta foi bem-sucedida, organizar os dados importantes
        if ($response['status'] && isset($response['data'])) {
            $mpData = $response['data'];
            
            return [
                'status' => true,
                'data' => [
                    'id' => $mpData['id'],
                    'status' => $mpData['status'],
                    'status_detail' => $mpData['status_detail'] ?? '',
                    'amount' => $mpData['transaction_amount'],
                    'date_created' => $mpData['date_created'] ?? '',
                    'date_approved' => $mpData['date_approved'] ?? '',
                    'external_reference' => $mpData['external_reference'] ?? '',
                    'metadata' => $mpData['metadata'] ?? []
                ]
            ];
        }
        
        return $response;
    }
    
    /**
     * Método para testar a conectividade com o Mercado Pago
     * Útil para verificar se as credenciais estão funcionando
     * 
     * @return array Resultado do teste
     */
    public function testConnection() {
        try {
            // Fazer uma requisição simples para verificar se tudo está configurado
            $response = $this->makeRequest('GET', '/v1/payment_methods');
            
            if ($response['status']) {
                return [
                    'status' => true, 
                    'message' => 'Conexão com Mercado Pago funcionando corretamente',
                    'token_valid' => true
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'Erro na conexão: ' . $response['message'],
                    'token_valid' => false
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => 'Erro no teste: ' . $e->getMessage(),
                'token_valid' => false
            ];
        }
    }
    
    /**
     * Método central para fazer requisições HTTP para o Mercado Pago
     * 
     * Este método cuida de toda a comunicação com a API do MP,
     * incluindo autenticação, headers, tratamento de erros, etc.
     * 
     * @param string $method Método HTTP (GET, POST, PUT, DELETE)
     * @param string $endpoint Endpoint da API (ex: /v1/payments)
     * @param array|null $data Dados para enviar na requisição (para POST/PUT)
     * @return array Resultado da requisição
     */
    private function makeRequest($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        // Configurar headers necessários para a API do Mercado Pago
        $headers = [
            // Autenticação usando Bearer Token
            'Authorization: Bearer ' . $this->accessToken,
            
            // Indicar que enviamos e esperamos JSON
            'Content-Type: application/json',
            'Accept: application/json',
            
            // Header para identificar nossa aplicação
            'User-Agent: KlubeCash/1.0 (PHP/' . PHP_VERSION . ')',
            
            // Chave de idempotência - evita duplicação de pagamentos
            'X-Idempotency-Key: ' . uniqid('klube_' . time() . '_', true)
        ];
        
        // Log detalhado para debug (útil para resolver problemas)
        error_log("MP Request: {$method} {$url}");
        
        // Inicializar cURL para fazer a requisição HTTP
        $ch = curl_init();
        
        // Configurações do cURL para comunicação segura com o MP
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true, // Retornar resposta como string
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CUSTOMREQUEST => $method,
            
            // Configurações de segurança
            CURLOPT_SSL_VERIFYPEER => true, // Verificar certificado SSL
            CURLOPT_SSL_VERIFYHOST => 2,    // Verificar hostname
            CURLOPT_FOLLOWLOCATION => false, // Não seguir redirects
            
            // Configurações de protocolo
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_USERAGENT => 'KlubeCash/1.0',
            
            // Configurações para debug
            CURLOPT_VERBOSE => false
        ]);
        
        // Se temos dados para enviar (POST/PUT), adicionar ao body da requisição
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $jsonData = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            
            // Log dos dados enviados (sem dados sensíveis)
            $logData = $data;
            if (isset($logData['payer']['email'])) {
                $logData['payer']['email'] = substr($logData['payer']['email'], 0, 3) . '***';
            }
            error_log("MP Request Data: " . json_encode($logData));
        }
        
        // Executar a requisição
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlInfo = curl_getinfo($ch);
        curl_close($ch);
        
        // Log da resposta para debug
        error_log("MP Response Code: {$httpCode}");
        error_log("MP Response Time: " . ($curlInfo['total_time'] ?? 'unknown') . "s");
        
        // Verificar se houve erro de conexão (problemas de rede, SSL, etc.)
        if ($curlError) {
            error_log("MP cURL Error: {$curlError}");
            return [
                'status' => false, 
                'message' => 'Erro de conexão com Mercado Pago: ' . $curlError,
                'error_type' => 'connection_error'
            ];
        }
        
        // Verificar se a resposta está vazia
        if (empty($response)) {
            error_log("MP Empty Response - HTTP Code: {$httpCode}");
            return [
                'status' => false,
                'message' => 'Mercado Pago retornou resposta vazia',
                'error_type' => 'empty_response',
                'http_code' => $httpCode
            ];
        }
        
        // Log da resposta (truncado para não logar dados sensíveis)
        $logResponse = strlen($response) > 1000 ? substr($response, 0, 1000) . '...' : $response;
        error_log("MP Response Body: {$logResponse}");
        
        // Tentar decodificar a resposta JSON
        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("MP JSON Decode Error: " . json_last_error_msg());
            return [
                'status' => false,
                'message' => 'Resposta inválida do Mercado Pago: ' . json_last_error_msg(),
                'error_type' => 'json_error',
                'raw_response' => $response
            ];
        }
        
        // Verificar se o código HTTP indica sucesso (200-299)
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['status' => true, 'data' => $decodedResponse];
        }
        
        // Se chegou aqui, houve algum erro da API do Mercado Pago
        $errorMessage = 'Erro HTTP ' . $httpCode;
        
        // Tentar extrair mensagem de erro mais específica
        if (is_array($decodedResponse)) {
            if (isset($decodedResponse['message'])) {
                $errorMessage .= ': ' . $decodedResponse['message'];
            } elseif (isset($decodedResponse['error'])) {
                $errorMessage .= ': ' . $decodedResponse['error'];
            } elseif (isset($decodedResponse['cause'])) {
                // MP às vezes retorna erros em formato diferente
                $causes = is_array($decodedResponse['cause']) ? $decodedResponse['cause'] : [$decodedResponse['cause']];
                $errorMessage .= ': ' . implode(', ', array_map(function($cause) {
                    return $cause['description'] ?? $cause['code'] ?? 'Erro desconhecido';
                }, $causes));
            }
        }
        
        error_log("MP Error Response: {$errorMessage}");
        
        return [
            'status' => false,
            'message' => $errorMessage,
            'error_type' => 'api_error',
            'http_code' => $httpCode,
            'response_data' => $decodedResponse
        ];
    }
}
?>