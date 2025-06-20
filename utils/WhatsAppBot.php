<?php
/**
 * Classe para integração com o Venom Bot WhatsApp
 * 
 * @author Klube Cash
 * @version 1.0
 */
class WhatsAppBot {
    
    private static $botUrl = 'http://localhost:3001';
    private static $webhookSecret = 'klube-cash-2024';
    
    /**
     * Verifica se o bot está conectado
     * 
     * @return bool
     */
    public static function isConnected() {
        try {
            $response = self::makeRequest('/status', 'GET');
            return isset($response['status']) && $response['status'] === 'connected';
        } catch (Exception $e) {
            error_log('Erro ao verificar status do bot WhatsApp: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envia mensagem via WhatsApp
     * 
     * @param string $phone Número do telefone
     * @param string $message Mensagem a ser enviada
     * @return array Resultado do envio
     */
    public static function sendMessage($phone, $message) {
        try {
            // Verificar se o bot está conectado
            if (!self::isConnected()) {
                return [
                    'success' => false,
                    'error' => 'Bot WhatsApp não está conectado'
                ];
            }
            
            // Dados para envio
            $data = [
                'phone' => $phone,
                'message' => $message,
                'secret' => self::$webhookSecret
            ];
            
            // Fazer requisição para o bot
            $response = self::makeRequest('/send-message', 'POST', $data);
            
            if (isset($response['success']) && $response['success']) {
                error_log("WhatsApp enviado com sucesso para: $phone");
                return [
                    'success' => true,
                    'messageId' => $response['messageId'] ?? null
                ];
            } else {
                error_log("Erro ao enviar WhatsApp: " . ($response['error'] ?? 'Erro desconhecido'));
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Erro desconhecido'
                ];
            }
            
        } catch (Exception $e) {
            error_log('Erro ao enviar mensagem WhatsApp: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erro interno: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Envia notificação de nova transação
     * 
     * @param string $phone Número do telefone do cliente
     * @param array $transactionData Dados da transação
     * @return array Resultado do envio
     */
    public static function sendNewTransactionNotification($phone, $transactionData) {
        $valorCashback = number_format($transactionData['valor_cashback'], 2, ',', '.');
        $valorUsado = isset($transactionData['valor_usado']) ? number_format($transactionData['valor_usado'], 2, ',', '.') : '0,00';
        $nomeLoja = $transactionData['nome_loja'];
        
        $message = "🔔 *Klube Cash - Nova Transação*\n\n";
        $message .= "Nova transação registrada: Você tem um novo cashback de R$ {$valorCashback} pendente da loja {$nomeLoja}.";
        
        if ($valorUsado !== '0,00') {
            $message .= " Você usou R$ {$valorUsado} do seu saldo nesta compra.";
        }
        
        $message .= "\n\n✅ O cashback ficará disponível após a aprovação do pagamento pela loja.";
        $message .= "\n\n💰 Acompanhe seu saldo no app Klube Cash!";
        
        return self::sendMessage($phone, $message);
    }
    
    /**
     * Envia notificação de cashback liberado
     * 
     * @param string $phone Número do telefone do cliente
     * @param array $transactionData Dados da transação
     * @return array Resultado do envio
     */
    public static function sendCashbackReleasedNotification($phone, $transactionData) {
        $valorCashback = number_format($transactionData['valor_cashback'], 2, ',', '.');
        $nomeLoja = $transactionData['nome_loja'];
        
        $message = "🎉 *Klube Cash - Cashback Liberado!*\n\n";
        $message .= "Seu cashback de R$ {$valorCashback} da loja {$nomeLoja} foi liberado e está disponível para uso!";
        $message .= "\n\n💳 Você pode usar este valor em suas próximas compras na mesma loja.";
        $message .= "\n\n📱 Confira seu saldo atualizado no app Klube Cash!";
        
        return self::sendMessage($phone, $message);
    }
    
    /**
     * Formata número de telefone
     * 
     * @param string $phone Número original
     * @return string Número formatado
     */
    private static function formatPhone($phone) {
        // Remove todos os caracteres não numéricos
        $phone = preg_replace('/\D/', '', $phone);
        
        // Se não tem código do país, adiciona 55 (Brasil)
        if (strlen($phone) == 11 && !str_starts_with($phone, '55')) {
            $phone = '55' . $phone;
        } elseif (strlen($phone) == 10 && !str_starts_with($phone, '55')) {
            $phone = '55' . $phone;
        }
        
        return $phone;
    }
    
    /**
     * Faz requisição HTTP para o bot
     * 
     * @param string $endpoint Endpoint da API
     * @param string $method Método HTTP
     * @param array $data Dados para envio
     * @return array Resposta da API
     */
    private static function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = self::$botUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen(json_encode($data))
            ]);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Erro cURL: $error");
        }
        
        if ($httpCode !== 200) {
            throw new Exception("Erro HTTP: $httpCode");
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Erro ao decodificar JSON: " . json_last_error_msg());
        }
        
        return $decoded;
    }
}
?>