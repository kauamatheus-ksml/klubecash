<?php
/**
 * Classe para integração com o Venom Bot WhatsApp - Versão 2.0
 * Sistema de Cashback Klube Cash
 * 
 * Esta versão implementa envio real de mensagens e integração completa
 * com o sistema de notificações existente
 */
class WhatsAppBot {
    
    private static $botUrl;
    private static $webhookSecret;
    private static $timeout;
    
    /**
     * Inicializa as configurações usando as constantes do sistema
     * Permite configuração flexível através do arquivo constants.php
     */
    private static function initializeConfig() {
        self::$botUrl = defined('WHATSAPP_BOT_URL') ? WHATSAPP_BOT_URL : 'http://localhost:3001';
        self::$webhookSecret = defined('WHATSAPP_BOT_SECRET') ? WHATSAPP_BOT_SECRET : 'klube-cash-2024';
        self::$timeout = defined('WHATSAPP_TIMEOUT') ? WHATSAPP_TIMEOUT : 30;
    }
    
    /**
     * Verifica se o bot está conectado e pronto para enviar mensagens
     * @return bool true se o bot estiver operacional
     */
    public static function isConnected() {
        try {
            self::initializeConfig();
            
            $response = self::makeRequest('/status', 'GET');
            
            return isset($response['status']) && 
                   $response['status'] === 'connected' && 
                   isset($response['bot_ready']) && 
                   $response['bot_ready'] === true;
            
        } catch (Exception $e) {
            error_log('WhatsApp Bot - Erro ao verificar status: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envia mensagem via WhatsApp para um número específico
     * Esta é a função principal que será usada pelo sistema de notificações
     * 
     * @param string $phone Número do telefone (formato brasileiro)
     * @param string $message Mensagem a ser enviada
     * @return array Resultado do envio com detalhes
     */
    public static function sendMessage($phone, $message) {
        try {
            self::initializeConfig();
            
            // Verificar se o WhatsApp está habilitado no sistema
            if (defined('WHATSAPP_ENABLED') && !WHATSAPP_ENABLED) {
                return [
                    'success' => false,
                    'error' => 'WhatsApp está desabilitado no sistema',
                    'code' => 'WHATSAPP_DISABLED'
                ];
            }
            
            // Verificar se o bot está conectado
            if (!self::isConnected()) {
                return [
                    'success' => false,
                    'error' => 'Bot WhatsApp não está conectado',
                    'code' => 'BOT_DISCONNECTED'
                ];
            }
            
            // Validar entrada
            $validation = self::validateInput($phone, $message);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'error' => $validation['error'],
                    'code' => 'INVALID_INPUT'
                ];
            }
            
            // Preparar dados para envio
            $data = [
                'phone' => $phone,
                'message' => $message,
                'secret' => self::$webhookSecret
            ];
            
            // Fazer requisição para o bot
            $response = self::makeRequest('/send-message', 'POST', $data);
            
            // Log do resultado
            if (isset($response['success']) && $response['success']) {
                error_log("WhatsApp enviado com sucesso para: $phone");
                return [
                    'success' => true,
                    'messageId' => $response['messageId'] ?? null,
                    'phone' => $response['phone'] ?? $phone,
                    'timestamp' => $response['timestamp'] ?? date('Y-m-d H:i:s')
                ];
            } else {
                $error = $response['error'] ?? 'Erro desconhecido';
                error_log("WhatsApp - Erro ao enviar para $phone: $error");
                return [
                    'success' => false,
                    'error' => $error,
                    'code' => 'SEND_FAILED'
                ];
            }
            
        } catch (Exception $e) {
            error_log('WhatsApp Bot - Erro crítico: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erro interno: ' . $e->getMessage(),
                'code' => 'INTERNAL_ERROR'
            ];
        }
    }
    
    /**
     * Envia teste de mensagem para validar o sistema
     * @return array Resultado do teste
     */
    public static function sendTestMessage() {
        try {
            self::initializeConfig();
            
            $data = [
                'secret' => self::$webhookSecret
            ];
            
            $response = self::makeRequest('/send-test', 'POST', $data);
            return $response;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro ao enviar teste: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Envia notificação de nova transação de cashback
     * Template específico para quando uma nova transação é registrada
     */
    public static function sendNewTransactionNotification($phone, $transactionData) {
        $valorCashback = number_format($transactionData['valor_cashback'], 2, ',', '.');
        $valorUsado = isset($transactionData['valor_usado']) ? 
                      number_format($transactionData['valor_usado'], 2, ',', '.') : '0,00';
        $nomeLoja = $transactionData['nome_loja'];
        
        $message = "🔔 *Klube Cash - Nova Transação*\n\n";
        $message .= "Nova transação registrada: Você tem um novo cashback de *R$ {$valorCashback}* pendente da loja *{$nomeLoja}*.";
        
        if ($valorUsado !== '0,00') {
            $message .= " Você usou R$ {$valorUsado} do seu saldo nesta compra.";
        }
        
        $message .= "\n\n✅ O cashback ficará disponível após a aprovação do pagamento pela loja.";
        $message .= "\n\n💰 Acompanhe seu saldo no app Klube Cash!";
        
        return self::sendMessage($phone, $message);
    }
    
    /**
     * Envia notificação de cashback liberado
     * Template para quando o cashback fica disponível para uso
     */
    public static function sendCashbackReleasedNotification($phone, $transactionData) {
        $valorCashback = number_format($transactionData['valor_cashback'], 2, ',', '.');
        $nomeLoja = $transactionData['nome_loja'];
        
        $message = "🎉 *Klube Cash - Cashback Liberado!*\n\n";
        $message .= "Seu cashback de *R$ {$valorCashback}* da loja *{$nomeLoja}* foi liberado e está disponível para uso!";
        $message .= "\n\n💳 Você pode usar este valor em suas próximas compras na mesma loja.";
        $message .= "\n\n📱 Confira seu saldo atualizado no app Klube Cash!";
        
        return self::sendMessage($phone, $message);
    }
    
    /**
     * Valida os dados de entrada para envio de mensagem
     * @param string $phone Número do telefone
     * @param string $message Mensagem
     * @return array Resultado da validação
     */
    private static function validateInput($phone, $message) {
        // Validar telefone
        $cleanPhone = preg_replace('/\D/', '', $phone);
        if (strlen($cleanPhone) < 10 || strlen($cleanPhone) > 13) {
            return ['valid' => false, 'error' => 'Número de telefone inválido'];
        }
        
        // Validar mensagem
        if (empty(trim($message))) {
            return ['valid' => false, 'error' => 'Mensagem não pode estar vazia'];
        }
        
        if (strlen($message) > 4000) {
            return ['valid' => false, 'error' => 'Mensagem muito longa (máximo 4000 caracteres)'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Executa requisições HTTP para o bot Node.js
     * Método central de comunicação com tratamento robusto de erros
     */
    private static function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = self::$botUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::$timeout,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Klube Cash WhatsApp Bot Client 2.0'
        ]);
        
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
            throw new Exception("Erro de comunicação: $error");
        }
        
        if ($httpCode !== 200) {
            throw new Exception("Erro HTTP: $httpCode");
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Resposta inválida do bot: " . json_last_error_msg());
        }
        
        return $decoded;
    }
    
    /**
     * Obtém estatísticas detalhadas do bot para monitoramento
     * @return array Informações completas sobre o estado do sistema
     */
    public static function getDetailedStatus() {
        try {
            self::initializeConfig();
            $response = self::makeRequest('/status', 'GET');
            
            return [
                'bot_url' => self::$botUrl,
                'timeout' => self::$timeout,
                'bot_data' => $response,
                'system_time' => date('Y-m-d H:i:s'),
                'whatsapp_enabled' => defined('WHATSAPP_ENABLED') ? WHATSAPP_ENABLED : false
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }
}
?>