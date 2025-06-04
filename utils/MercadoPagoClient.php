<?php
// utils/MercadoPagoClient.php

/**
 * Cliente completo para integração com a API do Mercado Pago
 * 
 * Esta classe é responsável por:
 * - Criar pagamentos PIX e consultar seu status
 * - Processar devoluções (refunds) totais e parciais
 * - Validar webhooks de forma segura
 * - Gerenciar comunicação segura com a API do MP
 * 
 * Fluxo do Mercado Pago:
 * 1. Enviamos dados do pagamento para eles
 * 2. Eles retornam um QR Code e dados do PIX
 * 3. Cliente paga o PIX
 * 4. MP nos notifica via webhook quando o pagamento é aprovado
 * 5. Podemos solicitar devoluções se necessário
 */
class MercadoPagoClient {
    // Configurações da API
    private $accessToken;
    private $baseUrl = 'https://api.mercadopago.com';
    private $timeout = 30;
    
    // Endpoints da API organizados por funcionalidade
    const ENDPOINTS = [
        'payments' => '/v1/payments',
        'payment_methods' => '/v1/payment_methods',
        'refunds' => '/v1/payments/{payment_id}/refunds',
        'refund_detail' => '/v1/payments/{payment_id}/refunds/{refund_id}'
    ];
    
    // Status possíveis dos pagamentos no MP
    const PAYMENT_STATUS = [
        'pending' => 'Pendente',
        'approved' => 'Aprovado',
        'authorized' => 'Autorizado',
        'in_process' => 'Em processamento',
        'in_mediation' => 'Em mediação',
        'rejected' => 'Rejeitado',
        'cancelled' => 'Cancelado',
        'refunded' => 'Estornado',
        'charged_back' => 'Chargeback'
    ];
    
    // Status possíveis das devoluções no MP
    const REFUND_STATUS = [
        'pending' => 'Pendente',
        'approved' => 'Aprovado',
        'rejected' => 'Rejeitado',
        'cancelled' => 'Cancelado'
    ];
    
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
        
        // Validar formato básico do token (deve começar com APP_USR)
        if (!str_starts_with($this->accessToken, 'APP_USR-')) {
            throw new Exception('MP_ACCESS_TOKEN parece inválido. Verifique se está usando o token correto.');
        }
        
        // Log de inicialização para debug (mascarando dados sensíveis)
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
        try {
            // Validar dados obrigatórios
            $validation = $this->validatePaymentData($data);
            if (!$validation['valid']) {
                return ['status' => false, 'message' => $validation['message']];
            }
            
            // Montar o payload para enviar ao Mercado Pago
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
                ],
                
                // Configurações adicionais para PIX
                'date_of_expiration' => date('c', strtotime('+30 minutes')), // PIX expira em 30 minutos
                'statement_descriptor' => 'KLUBECASH' // Aparece no extrato do cliente
            ];
            
            // Adicionar campos opcionais se foram fornecidos
            if (!empty($data['description'])) {
                $payload['description'] = substr(trim($data['description']), 0, 255);
            }
            
            if (!empty($data['external_reference'])) {
                $payload['external_reference'] = trim($data['external_reference']);
            }
            
            // Metadados para armazenar informações extras
            if (!empty($data['payment_id']) || !empty($data['store_id'])) {
                $payload['metadata'] = [];
                if (!empty($data['payment_id'])) {
                    $payload['metadata']['payment_id'] = (string) $data['payment_id'];
                }
                if (!empty($data['store_id'])) {
                    $payload['metadata']['store_id'] = (string) $data['store_id'];
                }
                $payload['metadata']['created_at'] = date('Y-m-d H:i:s');
            }
            
            // Configurar URL de notificação
            if (defined('MP_WEBHOOK_URL') && !empty(MP_WEBHOOK_URL)) {
                $payload['notification_url'] = MP_WEBHOOK_URL;
            }
            
            // Log para debug (mascarando dados sensíveis)
            $logPayload = $this->maskSensitiveData($payload);
            error_log("MP createPixPayment - Payload: " . json_encode($logPayload, JSON_PRETTY_PRINT));
            
            // Fazer a requisição para o Mercado Pago
            $response = $this->makeRequest('POST', self::ENDPOINTS['payments'], $payload);
            
            // Se a requisição foi bem-sucedida, extrair os dados do PIX
            if ($response['status'] && isset($response['data'])) {
                return $this->processPixPaymentResponse($response['data']);
            }
            
            return $response;
            
        } catch (Exception $e) {
            error_log("MP createPixPayment Exception: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Erro interno ao criar pagamento PIX: ' . $e->getMessage(),
                'error_type' => 'internal_error'
            ];
        }
    }
    
    /**
     * Consultar o status de um pagamento no Mercado Pago
     * 
     * @param string $paymentId ID do pagamento no Mercado Pago
     * @return array Resultado com status e dados do pagamento
     */
    public function getPaymentStatus($paymentId) {
        if (empty($paymentId)) {
            return ['status' => false, 'message' => 'ID do pagamento é obrigatório'];
        }
        
        $endpoint = str_replace('{payment_id}', $paymentId, self::ENDPOINTS['payments'] . '/{payment_id}');
        
        error_log("MP getPaymentStatus - Consultando pagamento: {$paymentId}");
        
        $response = $this->makeRequest('GET', $endpoint);
        
        if ($response['status'] && isset($response['data'])) {
            $mpData = $response['data'];
            
            return [
                'status' => true,
                'data' => [
                    'id' => $mpData['id'],
                    'status' => $mpData['status'],
                    'status_detail' => $mpData['status_detail'] ?? '',
                    'status_description' => self::PAYMENT_STATUS[$mpData['status']] ?? $mpData['status'],
                    'amount' => $mpData['transaction_amount'],
                    'date_created' => $mpData['date_created'] ?? '',
                    'date_approved' => $mpData['date_approved'] ?? '',
                    'date_last_updated' => $mpData['date_last_updated'] ?? '',
                    'external_reference' => $mpData['external_reference'] ?? '',
                    'metadata' => $mpData['metadata'] ?? [],
                    'payment_method_id' => $mpData['payment_method_id'] ?? '',
                    'payment_type_id' => $mpData['payment_type_id'] ?? ''
                ]
            ];
        }
        
        return $response;
    }
    
    /**
     * Criar uma devolução (refund) para um pagamento
     * 
     * @param string $paymentId ID do pagamento no MP
     * @param float|null $amount Valor a devolver (null = devolução total)
     * @param string|null $reason Motivo da devolução
     * @return array Resultado da operação
     */
    public function createRefund($paymentId, $amount = null, $reason = null) {
        if (empty($paymentId)) {
            return ['status' => false, 'message' => 'ID do pagamento é obrigatório'];
        }
        
        try {
            // Primeiro verificar se o pagamento existe e está aprovado
            $paymentStatus = $this->getPaymentStatus($paymentId);
            if (!$paymentStatus['status']) {
                return ['status' => false, 'message' => 'Não foi possível verificar o status do pagamento'];
            }
            
            if ($paymentStatus['data']['status'] !== 'approved') {
                return ['status' => false, 'message' => 'Só é possível fazer devolução de pagamentos aprovados'];
            }
            
            $endpoint = str_replace('{payment_id}', $paymentId, self::ENDPOINTS['refunds']);
            
            $payload = [];
            
            // Se amount for especificado, é devolução parcial
            if ($amount !== null) {
                if (!is_numeric($amount) || $amount <= 0) {
                    return ['status' => false, 'message' => 'Valor da devolução deve ser maior que zero'];
                }
                
                // Verificar se o valor não é maior que o valor do pagamento
                $paymentAmount = $paymentStatus['data']['amount'];
                if ($amount > $paymentAmount) {
                    return [
                        'status' => false, 
                        'message' => "Valor da devolução (R$ " . number_format($amount, 2, ',', '.') . 
                                   ") não pode ser maior que o valor do pagamento (R$ " . 
                                   number_format($paymentAmount, 2, ',', '.') . ")"
                    ];
                }
                
                $payload['amount'] = (float) $amount;
            }
            
            // Adicionar metadata com motivo e informações de controle
            $payload['metadata'] = [
                'refund_date' => date('Y-m-d H:i:s'),
                'system' => 'KlubeCash'
            ];
            
            if ($reason !== null) {
                $payload['metadata']['reason'] = substr(trim($reason), 0, 255);
            }
            
            error_log("MP createRefund - Payment: {$paymentId}, Amount: " . ($amount ?? 'total') . ", Reason: " . ($reason ?? 'none'));
            
            $response = $this->makeRequest('POST', $endpoint, $payload);
            
            if ($response['status'] && isset($response['data'])) {
                $refundData = $response['data'];
                
                return [
                    'status' => true,
                    'data' => [
                        'id' => $refundData['id'],
                        'payment_id' => $refundData['payment_id'],
                        'amount' => $refundData['amount'],
                        'status' => $refundData['status'],
                        'status_description' => self::REFUND_STATUS[$refundData['status']] ?? $refundData['status'],
                        'date_created' => $refundData['date_created'] ?? '',
                        'metadata' => $refundData['metadata'] ?? [],
                        'source' => $refundData['source'] ?? []
                    ]
                ];
            }
            
            return $response;
            
        } catch (Exception $e) {
            error_log("MP createRefund Exception: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Erro interno ao criar devolução: ' . $e->getMessage(),
                'error_type' => 'internal_error'
            ];
        }
    }
    
    /**
     * Consultar status de uma devolução específica ou listar todas as devoluções de um pagamento
     * 
     * @param string $paymentId ID do pagamento
     * @param string|null $refundId ID da devolução (se null, lista todas)
     * @return array Resultado da consulta
     */
    public function getRefundStatus($paymentId, $refundId = null) {
        if (empty($paymentId)) {
            return ['status' => false, 'message' => 'ID do pagamento é obrigatório'];
        }
        
        try {
            if ($refundId) {
                // Consultar devolução específica
                $endpoint = str_replace(
                    ['{payment_id}', '{refund_id}'], 
                    [$paymentId, $refundId], 
                    self::ENDPOINTS['refund_detail']
                );
            } else {
                // Listar todas as devoluções do pagamento
                $endpoint = str_replace('{payment_id}', $paymentId, self::ENDPOINTS['refunds']);
            }
            
            error_log("MP getRefundStatus - Payment: {$paymentId}, Refund: " . ($refundId ?? 'all'));
            
            $response = $this->makeRequest('GET', $endpoint);
            
            if ($response['status'] && isset($response['data'])) {
                $data = $response['data'];
                
                // Se é uma devolução específica, formatar os dados
                if ($refundId && isset($data['id'])) {
                    return [
                        'status' => true,
                        'data' => [
                            'id' => $data['id'],
                            'payment_id' => $data['payment_id'],
                            'amount' => $data['amount'],
                            'status' => $data['status'],
                            'status_description' => self::REFUND_STATUS[$data['status']] ?? $data['status'],
                            'date_created' => $data['date_created'] ?? '',
                            'metadata' => $data['metadata'] ?? [],
                            'source' => $data['source'] ?? []
                        ]
                    ];
                }
                
                // Se é lista de devoluções, formatar cada uma
                if (is_array($data) && isset($data['results'])) {
                    $refunds = [];
                    foreach ($data['results'] as $refund) {
                        $refunds[] = [
                            'id' => $refund['id'],
                            'payment_id' => $refund['payment_id'],
                            'amount' => $refund['amount'],
                            'status' => $refund['status'],
                            'status_description' => self::REFUND_STATUS[$refund['status']] ?? $refund['status'],
                            'date_created' => $refund['date_created'] ?? '',
                            'metadata' => $refund['metadata'] ?? []
                        ];
                    }
                    
                    return [
                        'status' => true,
                        'data' => [
                            'refunds' => $refunds,
                            'total' => count($refunds)
                        ]
                    ];
                }
                
                // Retorno direto se formato diferente
                return ['status' => true, 'data' => $data];
            }
            
            return $response;
            
        } catch (Exception $e) {
            error_log("MP getRefundStatus Exception: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Erro interno ao consultar devolução: ' . $e->getMessage(),
                'error_type' => 'internal_error'
            ];
        }
    }
    
    /**
     * Validar assinatura do webhook do Mercado Pago
     * 
     * @param array $headers Headers da requisição
     * @param string $body Corpo da requisição
     * @return bool True se a assinatura for válida
     */
    public function validateWebhookSignature($headers, $body) {
        try {
            // MP envia a assinatura nos headers (normalizar nomes dos headers)
            $normalizedHeaders = array_change_key_case($headers, CASE_LOWER);
            $signature = $normalizedHeaders['x-signature'] ?? '';
            $requestId = $normalizedHeaders['x-request-id'] ?? '';
            
            if (empty($signature) || empty($requestId)) {
                error_log("MP Webhook - Headers de assinatura ausentes");
                return false;
            }
            
            // Separar timestamp e hash da assinatura
            $parts = explode(',', $signature);
            $timestamp = '';
            $hash = '';
            
            foreach ($parts as $part) {
                $keyValue = explode('=', trim($part), 2);
                if (count($keyValue) === 2) {
                    $key = trim($keyValue[0]);
                    $value = trim($keyValue[1]);
                    
                    if ($key === 'ts') {
                        $timestamp = $value;
                    } elseif ($key === 'v1') {
                        $hash = $value;
                    }
                }
            }
            
            if (empty($timestamp) || empty($hash)) {
                error_log("MP Webhook - Formato de assinatura inválido: {$signature}");
                return false;
            }
            
            // Verificar se o timestamp não é muito antigo (máximo 15 minutos)
            $currentTime = time();
            $timestampInt = (int)$timestamp;
            if (abs($currentTime - $timestampInt) > 900) {
                error_log("MP Webhook - Timestamp muito antigo: {$timestamp}, atual: {$currentTime}");
                return false;
            }
            
            // Construir string para validação
            $dataToSign = $requestId . $timestamp . $body;
            
            // Calcular hash esperado usando a chave secreta
            if (defined('MP_WEBHOOK_SECRET') && !empty(MP_WEBHOOK_SECRET)) {
                $expectedHash = hash_hmac('sha256', $dataToSign, MP_WEBHOOK_SECRET);
                
                $isValid = hash_equals($expectedHash, $hash);
                
                if (!$isValid) {
                    error_log("MP Webhook - Assinatura inválida. Esperado: {$expectedHash}, Recebido: {$hash}");
                }
                
                return $isValid;
            }
            
            error_log("MP Webhook - MP_WEBHOOK_SECRET não definido, pulando validação");
            return true; // Se não tem secret configurado, aceita (não recomendado para produção)
            
        } catch (Exception $e) {
            error_log("MP Webhook - Erro na validação: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Método para testar a conectividade com o Mercado Pago
     * 
     * @return array Resultado do teste
     */
    public function testConnection() {
        try {
            $response = $this->makeRequest('GET', self::ENDPOINTS['payment_methods']);
            
            if ($response['status']) {
                return [
                    'status' => true, 
                    'message' => 'Conexão com Mercado Pago funcionando corretamente',
                    'token_valid' => true,
                    'response_time' => $response['response_time'] ?? 'unknown'
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
     * Validar dados obrigatórios para criação de pagamento
     * 
     * @param array $data Dados a validar
     * @return array Resultado da validação
     */
    private function validatePaymentData($data) {
        // Verificar valor
        if (empty($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
            return ['valid' => false, 'message' => 'Valor do pagamento é obrigatório e deve ser maior que zero'];
        }
        
        // Verificar limites do valor
        if ($data['amount'] < 0.01) {
            return ['valid' => false, 'message' => 'Valor mínimo é R$ 0,01'];
        }
        
        if ($data['amount'] > 1000000) {
            return ['valid' => false, 'message' => 'Valor máximo é R$ 1.000.000,00'];
        }
        
        // Verificar email
        if (empty($data['payer_email']) || !filter_var($data['payer_email'], FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Email do pagador é obrigatório e deve ser válido'];
        }
        
        // Verificar tamanho da descrição
        if (!empty($data['description']) && strlen($data['description']) > 255) {
            return ['valid' => false, 'message' => 'Descrição não pode ter mais que 255 caracteres'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Processar resposta de criação de pagamento PIX
     * 
     * @param array $mpData Dados retornados pelo MP
     * @return array Resultado processado
     */
    private function processPixPaymentResponse($mpData) {
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
                'error_type' => 'qr_code_error',
                'mp_payment_id' => $mpData['id'] ?? null
            ];
        }
        
        // Retornar os dados organizados
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
                'date_of_expiration' => $mpData['date_of_expiration'] ?? '',
                'external_reference' => $mpData['external_reference'] ?? '',
                'description' => $mpData['description'] ?? ''
            ]
        ];
    }
    
    /**
     * Mascarar dados sensíveis para logs
     * 
     * @param array $data Dados a mascarar
     * @return array Dados mascarados
     */
    private function maskSensitiveData($data) {
        $masked = $data;
        
        // Mascarar email
        if (isset($masked['payer']['email'])) {
            $email = $masked['payer']['email'];
            $atPos = strpos($email, '@');
            if ($atPos !== false) {
                $masked['payer']['email'] = substr($email, 0, 3) . '***' . substr($email, $atPos);
            }
        }
        
        // Mascarar outros dados sensíveis se necessário
        if (isset($masked['payer']['first_name'])) {
            $masked['payer']['first_name'] = substr($masked['payer']['first_name'], 0, 3) . '***';
        }
        
        return $masked;
    }
    
    /**
     * Método central para fazer requisições HTTP para o Mercado Pago
     * 
     * @param string $method Método HTTP (GET, POST, PUT, DELETE)
     * @param string $endpoint Endpoint da API (ex: /v1/payments)
     * @param array|null $data Dados para enviar na requisição (para POST/PUT)
     * @return array Resultado da requisição
     */
    private function makeRequest($method, $endpoint, $data = null) {
        $startTime = microtime(true);
        $url = $this->baseUrl . $endpoint;
        
        // Configurar headers necessários
        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: KlubeCash/2.0 (PHP/' . PHP_VERSION . ')',
            'X-Idempotency-Key: ' . uniqid('klube_' . time() . '_', true)
        ];
        
        error_log("MP Request: {$method} {$url}");
        
        // Inicializar cURL
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
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_USERAGENT => 'KlubeCash/2.0',
            CURLOPT_VERBOSE => false
        ]);
        
        // Adicionar dados se necessário
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            
            // Log dos dados (mascarados)
            $logData = $this->maskSensitiveData($data);
            error_log("MP Request Data: " . json_encode($logData));
        }
        
        // Executar requisição
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlInfo = curl_getinfo($ch);
        curl_close($ch);
        
        $responseTime = microtime(true) - $startTime;
        
        // Log da resposta
        error_log("MP Response: HTTP {$httpCode} em {$responseTime}s");
        
        // Verificar erros de conexão
        if ($curlError) {
            error_log("MP cURL Error: {$curlError}");
            return [
                'status' => false, 
                'message' => 'Erro de conexão com Mercado Pago: ' . $curlError,
                'error_type' => 'connection_error',
                'response_time' => $responseTime
            ];
        }
        
        // Verificar resposta vazia
        if (empty($response)) {
            error_log("MP Empty Response - HTTP Code: {$httpCode}");
            return [
                'status' => false,
                'message' => 'Mercado Pago retornou resposta vazia',
                'error_type' => 'empty_response',
                'http_code' => $httpCode,
                'response_time' => $responseTime
            ];
        }
        
        // Log da resposta (truncado)
        $logResponse = strlen($response) > 1000 ? substr($response, 0, 1000) . '...' : $response;
        error_log("MP Response Body: {$logResponse}");
        
        // Decodificar JSON
        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("MP JSON Decode Error: " . json_last_error_msg());
            return [
                'status' => false,
                'message' => 'Resposta inválida do Mercado Pago: ' . json_last_error_msg(),
                'error_type' => 'json_error',
                'raw_response' => substr($response, 0, 500),
                'response_time' => $responseTime
            ];
        }
        
        // Verificar sucesso HTTP
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'status' => true, 
                'data' => $decodedResponse,
                'response_time' => $responseTime
            ];
        }
        
        // Processar erro da API
        $errorMessage = $this->extractErrorMessage($decodedResponse, $httpCode);
        
        error_log("MP API Error: {$errorMessage}");
        
        return [
            'status' => false,
            'message' => $errorMessage,
            'error_type' => 'api_error',
            'http_code' => $httpCode,
            'response_data' => $decodedResponse,
            'response_time' => $responseTime
        ];
    }
    
    /**
     * Extrair mensagem de erro mais legível da resposta do MP
     * 
     * @param array $response Resposta decodificada
     * @param int $httpCode Código HTTP
     * @return string Mensagem de erro
     */
    private function extractErrorMessage($response, $httpCode) {
        $errorMessage = 'Erro HTTP ' . $httpCode;
        
        if (!is_array($response)) {
            return $errorMessage;
        }
        
        // Diferentes formatos de erro do MP
        if (isset($response['message'])) {
            $errorMessage .= ': ' . $response['message'];
        } elseif (isset($response['error'])) {
            $errorMessage .= ': ' . $response['error'];
        } elseif (isset($response['cause'])) {
            $causes = is_array($response['cause']) ? $response['cause'] : [$response['cause']];
            $causeMessages = [];
            
            foreach ($causes as $cause) {
                if (is_array($cause)) {
                    $causeMessages[] = $cause['description'] ?? $cause['code'] ?? 'Erro desconhecido';
                } else {
                    $causeMessages[] = $cause;
                }
            }
            
            $errorMessage .= ': ' . implode(', ', $causeMessages);
        } elseif (isset($response['errors'])) {
            // Formato alternativo de erros
            $errorMessages = [];
            foreach ($response['errors'] as $error) {
                if (is_array($error)) {
                    $errorMessages[] = $error['message'] ?? $error['description'] ?? 'Erro desconhecido';
                }
            }
            if (!empty($errorMessages)) {
                $errorMessage .= ': ' . implode(', ', $errorMessages);
            }
        }
        
        return $errorMessage;
    }
}
?>