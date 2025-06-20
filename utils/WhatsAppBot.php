<?php
/**
 * Classe para integração com o Venom Bot WhatsApp
 * Sistema de Cashback Klube Cash
 * 
 * Esta classe serve como uma ponte entre o sistema PHP e o bot Node.js
 * Pense nela como um "tradutor" que permite ao PHP conversar com o WhatsApp
 */
class WhatsAppBot {
    
    // Configurações básicas - como o "endereço" do nosso bot
    private static $botUrl = 'http://localhost:3001';
    private static $webhookSecret = 'klube-cash-2024';
    private static $timeout = 10; // segundos para aguardar resposta
    
    /**
     * Inicializa as configurações usando as constantes do sistema
     * É como "configurar o GPS" com o endereço correto
     */
    private static function initializeConfig() {
        // Se as constantes estão definidas, usar elas em vez dos valores padrão
        if (defined('WHATSAPP_BOT_URL')) {
            self::$botUrl = WHATSAPP_BOT_URL;
        }
        if (defined('WHATSAPP_BOT_SECRET')) {
            self::$webhookSecret = WHATSAPP_BOT_SECRET;
        }
        if (defined('WHATSAPP_TIMEOUT')) {
            self::$timeout = WHATSAPP_TIMEOUT;
        }
    }
    
    /**
     * Verifica se o bot está conectado e respondendo
     * É como "bater na porta" para ver se alguém está em casa
     * 
     * @return bool true se o bot estiver respondendo
     */
    public static function isConnected() {
        try {
            self::initializeConfig();
            
            $response = self::makeRequest('/status', 'GET');
            
            // Verificar se a resposta indica que está tudo funcionando
            return isset($response['status']) && $response['status'] === 'OK';
            
        } catch (Exception $e) {
            // Se houver qualquer erro, considerar como desconectado
            error_log('Erro ao verificar status do bot WhatsApp: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Método de teste para verificar se a integração está funcionando
     * É como um "teste de som" antes de uma apresentação
     * 
     * @param string $phone Número de teste
     * @param string $message Mensagem de teste
     * @return array Resultado do teste
     */
    public static function sendTestMessage($phone, $message) {
        // Por enquanto, apenas simula o envio e registra no log
        // Isso nos permite testar a integração sem enviar mensagens reais
        
        $logMessage = "TESTE WhatsApp - Para: $phone, Mensagem: $message";
        error_log($logMessage);
        
        return [
            'success' => true, 
            'message' => 'Teste registrado no log do servidor',
            'phone' => $phone,
            'test_message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Faz uma requisição HTTP para o bot Node.js
     * É como "fazer uma ligação telefônica" para o bot
     * 
     * @param string $endpoint Qual "ramal" chamar (ex: /status, /send-message)
     * @param string $method Tipo de chamada (GET, POST)
     * @param array $data Dados para enviar (se for POST)
     * @return array Resposta do bot
     * @throws Exception Se houver erro na comunicação
     */
    private static function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = self::$botUrl . $endpoint;
        
        // Inicializar cURL - nossa "linha telefônica"
        $ch = curl_init();
        
        // Configurar a "chamada"
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true, // Queremos a resposta de volta
            CURLOPT_TIMEOUT => self::$timeout, // Não esperar muito tempo
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_FOLLOWLOCATION => false, // Não seguir redirecionamentos
            CURLOPT_SSL_VERIFYPEER => false, // Para desenvolvimento local
        ]);
        
        // Se estamos enviando dados (POST), preparar o "envelope"
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen(json_encode($data))
            ]);
        }
        
        // Fazer a "chamada"
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Verificar se houve problemas na "ligação"
        if ($error) {
            throw new Exception("Erro de comunicação com o bot: $error");
        }
        
        // Verificar se o bot "atendeu" corretamente
        if ($httpCode !== 200) {
            throw new Exception("Bot retornou erro HTTP: $httpCode");
        }
        
        // "Entender" a resposta do bot
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Resposta do bot não é um JSON válido: " . json_last_error_msg());
        }
        
        return $decoded;
    }
    
    /**
     * Obter informações sobre o estado atual da classe
     * Útil para debug e monitoramento
     * 
     * @return array Informações de diagnóstico
     */
    public static function getStatus() {
        self::initializeConfig();
        
        return [
            'bot_url' => self::$botUrl,
            'timeout' => self::$timeout,
            'is_connected' => self::isConnected(),
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0.0-beta'
        ];
    }
}
?>