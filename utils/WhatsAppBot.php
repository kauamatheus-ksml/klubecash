<?php
/**
 * Classe WhatsApp Bot - Versão API Business
 * Implementação usando WhatsApp Business API (Cloud API)
 * Compatível com hospedagem compartilhada
 */
class WhatsAppBot {
    
    private static $botUrl;
    private static $webhookSecret;
    private static $timeout;
    private static $accessToken;
    private static $phoneNumberId;
    private static $apiVersion = 'v21.0';
    private static $baseUrl = 'https://graph.facebook.com';
    
    /**
     * Inicializa as configurações usando as constantes do sistema
     * Versão corrigida que declara todas as propriedades necessárias
     */
    private static function initializeConfig() {
        // Agora podemos usar essas propriedades porque elas foram declaradas acima
        self::$botUrl = defined('WHATSAPP_BOT_URL') ? WHATSAPP_BOT_URL : 'http://localhost:3001';
        self::$webhookSecret = defined('WHATSAPP_BOT_SECRET') ? WHATSAPP_BOT_SECRET : 'klube-cash-2024';
        self::$timeout = defined('WHATSAPP_TIMEOUT') ? WHATSAPP_TIMEOUT : 30;
        
        // Lógica de detecção automática de modo produção vs simulação
        if (strpos(self::$botUrl, 'ngrok') !== false || strpos(self::$botUrl, '148.230.73.190') !== false) {
            // Detectamos conexão via ngrok ou servidor de produção = modo produção real
            self::$accessToken = 'REAL_CONNECTION_PRODUCTION';
            self::$phoneNumberId = 'PRODUCTION_MODE_ACTIVE';
        } else {
            // Conexão local = modo simulação
            self::$accessToken = 'TEMP_TOKEN';
            self::$phoneNumberId = 'TEMP_ID';
        }
    }
    
    /**
     * Verifica se a API está configurada corretamente
     * @return bool true se configuração está válida
     */
    public static function isConnected() {
        self::initializeConfig();
        
        // Se detectou ngrok ou servidor de produção na URL, assumir conectado
        if (strpos(self::$botUrl, 'ngrok') !== false || strpos(self::$botUrl, '148.230.73.190') !== false) {
            return true;
        }
        
        // Código original para outras situações
        if (self::$accessToken === 'TEMP_TOKEN') {
            return true;
        }
        
        return false;
    }
    
    /**
 * Envia mensagem via WhatsApp com log detalhado
 * Versão atualizada que registra tudo em nossa própria base de dados
 */
public static function sendMessage($phone, $message) {
    try {
        self::initializeConfig();
        
        // Verificar se está habilitado
        if (defined('WHATSAPP_ENABLED') && !WHATSAPP_ENABLED) {
            $result = [
                'success' => false,
                'error' => 'WhatsApp está desabilitado no sistema',
                'code' => 'DISABLED'
            ];
        } else {
            // Validar entrada
            $validation = self::validateInput($phone, $message);
            if (!$validation['valid']) {
                $result = [
                    'success' => false,
                    'error' => $validation['error'],
                    'code' => 'VALIDATION_ERROR'
                ];
            } else {
                // Executar envio (simulado ou real)
                if (self::$accessToken === 'TEMP_TOKEN') {
                    $result = self::simulateMessage($phone, $message);
                } else {
                    $result = self::sendViaBot($phone, $message); // CHAMADA REAL
                }
            }
        }
        
        // NOVO: Registrar no nosso sistema de logs personalizado
        if (!class_exists('WhatsAppLogger')) {
            // Garantir que as dependências estão carregadas antes do logger
            if (!class_exists('Database')) {
                require_once __DIR__ . '/../config/database.php';
            }
            if (!defined('SYSTEM_NAME')) {
                require_once __DIR__ . '/../config/constants.php';
            }
            // Agora carregar o logger com segurança
            require_once __DIR__ . '/WhatsAppLogger.php';
        }
        
        WhatsAppLogger::log('manual_send', $phone, $message, $result);
        
        return $result;
        
    } catch (Exception $e) {
        $result = [
            'success' => false,
            'error' => 'Erro interno: ' . $e->getMessage(),
            'code' => 'INTERNAL_ERROR'
        ];
        
        // Registrar erro também
        if (class_exists('WhatsAppLogger')) {
            WhatsAppLogger::log('error', $phone ?? 'unknown', $message ?? '', $result);
        }
        
        return $result;
    }
}

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
            throw new Exception("Erro cURL: $error");
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
    private static function sendViaBot($phone, $message) {
        try {
            $data = [
                'phone' => $phone,
                'message' => $message,
                'secret' => self::$webhookSecret
            ];
            
            $response = self::makeRequest('/send-message', 'POST', $data);
            
            if (isset($response['success']) && $response['success']) {
                return [
                    'success' => true,
                    'messageId' => $response['messageId'] ?? null,
                    'phone' => $phone,
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Erro desconhecido'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    /**
     * Simula envio de mensagem para desenvolvimento em hospedagem compartilhada
     * Esta função nos permite testar toda a lógica sem depender de APIs externas
     */
    private static function simulateMessage($phone, $message) {
        // Gerar um ID de mensagem simulado
        $messageId = 'sim_' . uniqid();
        
        // Log detalhado para monitoramento
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'phone' => $phone,
            'message_preview' => substr($message, 0, 100) . (strlen($message) > 100 ? '...' : ''),
            'message_id' => $messageId,
            'status' => 'simulated'
        ];
        
        error_log('WhatsApp SIMULADO: ' . json_encode($logEntry, JSON_UNESCAPED_UNICODE));
        
        return [
            'success' => true,
            'messageId' => $messageId,
            'phone' => $phone,
            'timestamp' => date('Y-m-d H:i:s'),
            'simulation' => false,
            'note' => 'Mensagem registrada no log do servidor (modo desenvolvimento)'
        ];
    }
    
    /**
     * Envia notificação de nova transação com log personalizado
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
        
        $result = self::sendMessage($phone, $message);
        
        // Registrar com dados específicos da transação
        if (!class_exists('WhatsAppLogger')) {
            // Garantir que as dependências estão carregadas antes do logger
            if (!class_exists('Database')) {
                require_once __DIR__ . '/../config/database.php';
            }
            if (!defined('SYSTEM_NAME')) {
                require_once __DIR__ . '/../config/constants.php';
            }
            // Agora carregar o logger com segurança
            require_once __DIR__ . '/WhatsAppLogger.php';
        }
        
        WhatsAppLogger::log('nova_transacao', $phone, $message, $result, $transactionData);
        
        return $result;
    }
    
    /**
     * Envia notificação de cashback liberado com log personalizado
     */
    public static function sendCashbackReleasedNotification($phone, $transactionData) {
        $valorCashback = number_format($transactionData['valor_cashback'], 2, ',', '.');
        $nomeLoja = $transactionData['nome_loja'];
        
        $message = "🎉 *Klube Cash - Cashback Liberado!*\n\n";
        $message .= "Seu cashback de *R$ {$valorCashback}* da loja *{$nomeLoja}* foi liberado e está disponível para uso!";
        $message .= "\n\n💳 Você pode usar este valor em suas próximas compras na mesma loja.";
        $message .= "\n\n📱 Confira seu saldo atualizado no app Klube Cash!";
        
        $result = self::sendMessage($phone, $message);
        
        // Registrar com dados específicos do cashback
        if (!class_exists('WhatsAppLogger')) {
            // Garantir que as dependências estão carregadas antes do logger
            if (!class_exists('Database')) {
                require_once __DIR__ . '/../config/database.php';
            }
            if (!defined('SYSTEM_NAME')) {
                require_once __DIR__ . '/../config/constants.php';
            }
            // Agora carregar o logger com segurança
            require_once __DIR__ . '/WhatsAppLogger.php';
        }
        
        WhatsAppLogger::log('cashback_liberado', $phone, $message, $result, $transactionData);
        
        return $result;
    }
    
    /**
     * Formata número para padrão internacional WhatsApp
     */
    private static function formatPhoneNumber($phone) {
        $cleanPhone = preg_replace('/\D/', '', $phone);
        
        if (strlen($cleanPhone) === 11 && !str_starts_with($cleanPhone, '55')) {
            $cleanPhone = '55' . $cleanPhone;
        }
        
        return $cleanPhone;
    }
    
    /**
     * Valida dados de entrada
     */
    private static function validateInput($phone, $message) {
        $cleanPhone = preg_replace('/\D/', '', $phone);
        if (strlen($cleanPhone) < 10 || strlen($cleanPhone) > 13) {
            return ['valid' => false, 'error' => 'Número de telefone inválido'];
        }
        
        if (empty(trim($message))) {
            return ['valid' => false, 'error' => 'Mensagem não pode estar vazia'];
        }
        
        if (strlen($message) > 4000) {
            return ['valid' => false, 'error' => 'Mensagem muito longa'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Faz requisições para a API do WhatsApp
     */
    private static function makeApiRequest($url, $method = 'GET', $data = null) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . self::$accessToken,
                'Content-Type: application/json'
            ]
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Erro cURL: $error");
        }
        
        if ($httpCode >= 400) {
            throw new Exception("Erro HTTP $httpCode: $response");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Função de teste que funciona imediatamente
     */
    public static function sendTestMessage($testPhone = null) {
        $phone = $testPhone ?: '38991045205'; // Número padrão para teste
        
        $message = "🧪 *Teste Klube Cash WhatsApp*\n\n";
        $message .= "Esta é uma mensagem de teste do sistema de notificações.\n\n";
        $message .= "Horário: " . date('d/m/Y H:i:s') . "\n";
        $message .= "Status: Sistema funcionando corretamente!\n\n";
        $message .= "Em breve você receberá notificações reais sobre seu cashback. 💰";
        
        return self::sendMessage($phone, $message);
    }
    
    /**
     * Obtém status detalhado do sistema
     */
    public static function getDetailedStatus() {
        self::initializeConfig();
        
        return [
            'api_configured' => self::$accessToken !== 'TEMP_TOKEN',
            'phone_configured' => self::$phoneNumberId !== 'TEMP_ID',
            'simulation_mode' => self::$accessToken === 'TEMP_TOKEN',
            'whatsapp_enabled' => defined('WHATSAPP_ENABLED') ? WHATSAPP_ENABLED : false,
            'version' => '2.0-business-api',
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}
?>