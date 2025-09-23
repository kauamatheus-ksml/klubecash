<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

class EvolutionWhatsApp {
    
    public static function sendMessage($phone, $message, $transactionData = null) {
        try {
            if (!defined('EVOLUTION_API_ENABLED') || !EVOLUTION_API_ENABLED) {
                return ['success' => false, 'error' => 'Evolution API desabilitada'];
            }
            
            // Formatar telefone
            $phoneFormatted = self::formatPhone($phone);
            
            if (!$phoneFormatted) {
                return ['success' => false, 'error' => 'Telefone inválido: ' . $phone];
            }
            
            // Preparar dados da mensagem
            $messageData = [
                'number' => $phoneFormatted,
                'text' => $message
            ];
            
            // URL da Evolution API
            $url = EVOLUTION_API_URL . '/message/sendText/' . EVOLUTION_INSTANCE;
            
            // Enviar via cURL
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($messageData),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'apikey: ' . EVOLUTION_API_KEY
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => EVOLUTION_TIMEOUT,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_USERAGENT => 'KlubeCash-Evolution/1.0'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                error_log("Evolution WhatsApp CURL Error: " . $error);
                self::logMessage($phoneFormatted, $message, false, ['curl_error' => $error], $transactionData);
                return ['success' => false, 'error' => $error];
            }
            
            $responseData = json_decode($response, true);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                error_log("Evolution WhatsApp: Mensagem enviada com sucesso para {$phoneFormatted}");
                self::logMessage($phoneFormatted, $message, true, $responseData, $transactionData);
                
                return [
                    'success' => true,
                    'messageId' => $responseData['key']['id'] ?? uniqid(),
                    'phone' => $phoneFormatted,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'response' => $responseData
                ];
            } else {
                error_log("Evolution WhatsApp Error HTTP {$httpCode}: " . $response);
                self::logMessage($phoneFormatted, $message, false, $responseData, $transactionData);
                
                return [
                    'success' => false,
                    'error' => "HTTP {$httpCode}: " . ($responseData['message'] ?? 'Erro desconhecido'),
                    'response' => $responseData
                ];
            }
            
        } catch (Exception $e) {
            error_log("Evolution WhatsApp Exception: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private static function formatPhone($phone) {
        // Remover todos os caracteres não numéricos
        $phone = preg_replace('/\D/', '', $phone);
        
        // Validar se tem pelo menos 10 dígitos
        if (strlen($phone) < 10) {
            return false;
        }
        
        // Adicionar código do Brasil se não tiver
        if (!str_starts_with($phone, '55')) {
            $phone = '55' . $phone;
        }
        
        // Garantir que telefones celulares tenham 9 dígitos
        if (strlen($phone) == 12 && !in_array(substr($phone, 4, 1), ['9'])) {
            // Inserir 9 na frente do número celular se for necessário
            $ddd = substr($phone, 2, 2);
            $numero = substr($phone, 4);
            
            // DDDs que requerem 9º dígito
            $ddds_9_digito = ['11', '12', '13', '14', '15', '16', '17', '18', '19', '21', '22', '24', '27', '28'];
            
            if (in_array($ddd, $ddds_9_digito) && strlen($numero) == 8) {
                $phone = '55' . $ddd . '9' . $numero;
            }
        }
        
        return $phone;
    }
    
    private static function logMessage($phone, $message, $success, $response, $transactionData) {
        try {
            $db = Database::getConnection();
            
            // Criar tabela se não existir
            $createTableStmt = $db->prepare("
                CREATE TABLE IF NOT EXISTS whatsapp_evolution_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    phone VARCHAR(20),
                    message TEXT,
                    success BOOLEAN,
                    response TEXT,
                    transaction_id INT NULL,
                    event_type VARCHAR(50) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_phone (phone),
                    INDEX idx_transaction (transaction_id),
                    INDEX idx_created_at (created_at)
                )
            ");
            $createTableStmt->execute();
            
            $logStmt = $db->prepare("
                INSERT INTO whatsapp_evolution_logs (phone, message, success, response, transaction_id, event_type) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $logStmt->execute([
                $phone,
                substr($message, 0, 1000), // Limitar tamanho da mensagem no log
                $success,
                json_encode($response),
                $transactionData['transaction_id'] ?? null,
                $transactionData['event_type'] ?? 'direct_send'
            ]);
            
        } catch (Exception $e) {
            error_log("Evolution WhatsApp Log Error: " . $e->getMessage());
        }
    }
    
    public static function sendNewTransactionNotification($phone, $transactionData) {
        $valorCashback = number_format($transactionData['valor_cashback'], 2, ',', '.');
        $valorUsado = isset($transactionData['valor_usado']) ? 
                    number_format($transactionData['valor_usado'], 2, ',', '.') : '0,00';
        $nomeLoja = $transactionData['nome_loja'];
        $nomeCliente = $transactionData['nome_cliente'] ?? 'Cliente';
        
        if ($valorUsado !== '0,00') {
            // Transação MVP
            $message = "🎯 *Klube Cash - Compra MVP Realizada*\n\n";
            $message .= "Olá {$nomeCliente}! 👋\n\n";
            $message .= "✅ Sua compra na loja *{$nomeLoja}* foi registrada com sucesso!\n\n";
            $message .= "💰 *Saldo utilizado:* R$ {$valorUsado}\n";
            $message .= "🎁 *Novo cashback:* R$ {$valorCashback} (pendente)\n\n";
            $message .= "🔄 Parabéns por usar nosso programa MVP! Você economizou usando seu saldo e ainda ganhou mais cashback.\n\n";
            $message .= "⏰ O cashback ficará disponível após a aprovação do pagamento pela loja.\n\n";
            $message .= "📱 Continue acompanhando no app Klube Cash!";
        } else {
            // Transação normal
            $message = "🔔 *Klube Cash - Novo Cashback Gerado*\n\n";
            $message .= "Parabéns {$nomeCliente}! 🎉\n\n";
            $message .= "Nova transação registrada na loja *{$nomeLoja}*:\n\n";
            $message .= "💎 *Cashback gerado:* R$ {$valorCashback}\n";
            $message .= "📅 *Data:* " . date('d/m/Y H:i') . "\n\n";
            $message .= "✅ O cashback ficará disponível após a aprovação do pagamento pela loja.\n\n";
            $message .= "💰 Baixe o app e acompanhe seu saldo em tempo real!";
        }
        
        $transactionData['event_type'] = 'nova_transacao';
        return self::sendMessage($phone, $message, $transactionData);
    }
    
    public static function sendCashbackReleasedNotification($phone, $transactionData) {
        $valorCashback = number_format($transactionData['valor_cashback'], 2, ',', '.');
        $nomeLoja = $transactionData['nome_loja'];
        $nomeCliente = $transactionData['nome_cliente'] ?? 'Cliente';
        
        $message = "🎉 *Cashback Liberado - Klube Cash*\n\n";
        $message .= "Ótimas notícias {$nomeCliente}! 🎊\n\n";
        $message .= "✅ Seu cashback da loja *{$nomeLoja}* foi liberado!\n\n";
        $message .= "💰 *Valor disponível:* R$ {$valorCashback}\n\n";
        $message .= "🛒 Agora você pode usar este valor em uma nova compra na mesma loja.\n\n";
        $message .= "📱 Acesse o app Klube Cash e aproveite seu saldo!";
        
        $transactionData['event_type'] = 'cashback_liberado';
        return self::sendMessage($phone, $message, $transactionData);
    }
    
    /**
     * Método para testar conectividade da Evolution API
     */
    public static function testConnection() {
        try {
            $url = EVOLUTION_API_URL . '/instance/connectionState/' . EVOLUTION_INSTANCE;
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => [
                    'apikey: ' . EVOLUTION_API_KEY
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                return ['success' => false, 'error' => $error];
            }
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                return [
                    'success' => true, 
                    'connection_state' => $data,
                    'api_responsive' => true
                ];
            } else {
                return ['success' => false, 'error' => "HTTP {$httpCode}: {$response}"];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>