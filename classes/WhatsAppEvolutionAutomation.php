<?php
/**
 * Sistema de Automação WhatsApp com Evolution API
 * Envia mensagens automaticamente após registro de cashback
 */

class WhatsAppEvolutionAutomation {
    
    private $evolutionConfig;
    private $db;
    private $logFile;
    
    public function __construct() {
        // Configurações da Evolution API
        $this->evolutionConfig = [
            'base_url' => 'https://evolutionapi.klubecash.com', // Ajuste para sua URL
            'instance_name' => 'klubecash',
            'api_key' => 'XjllCXtwjUXxbecrCvsM6h78ppLMgpNL' // Sua chave da Evolution
        ];
        
        // Conectar ao banco
        require_once __DIR__ . '/../config/database.php';
        $this->db = Database::getConnection();
        
        // Arquivo de log
        $this->logFile = __DIR__ . '/../logs/whatsapp_automation.log';
        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }
    
    /**
     * Enviar notificação de cashback automaticamente
     */
    public function notificarCashback($transactionId) {
        try {
            $this->log("=== INÍCIO NOTIFICAÇÃO AUTOMÁTICA ===");
            $this->log("Transaction ID: {$transactionId}");
            
            // Buscar dados da transação
            $query = "
                SELECT 
                    t.id,
                    t.valor_total,
                    t.valor_cashback,
                    t.valor_cliente,
                    t.status,
                    t.data_transacao,
                    u.id as usuario_id,
                    u.nome as cliente_nome,
                    u.telefone as cliente_telefone,
                    l.nome_fantasia as loja_nome,
                    l.porcentagem_cashback,
                    usr_loja.mvp as loja_mvp
                FROM transacoes_cashback t
                JOIN usuarios u ON t.usuario_id = u.id
                JOIN lojas l ON t.loja_id = l.id
                JOIN usuarios usr_loja ON l.usuario_id = usr_loja.id
                WHERE t.id = :transaction_id
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':transaction_id', $transactionId);
            $stmt->execute();
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$transaction) {
                throw new Exception("Transação não encontrada: {$transactionId}");
            }
            
            $this->log("Cliente: {$transaction['cliente_nome']} ({$transaction['cliente_telefone']})");
            $this->log("Loja: {$transaction['loja_nome']}");
            $this->log("Valor: R$ " . number_format($transaction['valor_total'], 2, ',', '.'));
            
            // Formatar telefone (garantir formato brasileiro com 55)
            $phone = $this->formatarTelefone($transaction['cliente_telefone']);
            
            if (!$phone) {
                throw new Exception("Telefone inválido: {$transaction['cliente_telefone']}");
            }
            
            // Criar mensagem personalizada
            $mensagem = $this->criarMensagemCashback($transaction);
            
            // Enviar via Evolution API
            $resultado = $this->enviarViaEvolution($phone, $mensagem);
            
            // Registrar no banco
            $this->registrarEnvio(
                $transactionId,
                $phone,
                $mensagem,
                $resultado['success'],
                $resultado['response'] ?? null,
                $resultado['error'] ?? null
            );
            
            if ($resultado['success']) {
                $this->log("✅ SUCESSO: Mensagem enviada para {$phone}");
                
                // Atualizar status de notificação na transação
                $updateQuery = "
                    UPDATE transacoes_cashback 
                    SET notificacao_enviada = 1,
                        data_notificacao = NOW()
                    WHERE id = :transaction_id
                ";
                $updateStmt = $this->db->prepare($updateQuery);
                $updateStmt->bindParam(':transaction_id', $transactionId);
                $updateStmt->execute();
            } else {
                $this->log("❌ ERRO: Falha ao enviar para {$phone} - " . ($resultado['error'] ?? 'Erro desconhecido'));
            }
            
            return $resultado;
            
        } catch (Exception $e) {
            $this->log("❌ EXCEÇÃO: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Criar mensagem formatada de cashback
     */
    private function criarMensagemCashback($transaction) {
        $nomeCliente = explode(' ', $transaction['cliente_nome'])[0]; // Primeiro nome
        $valorCompra = number_format($transaction['valor_total'], 2, ',', '.');
        $valorCashback = number_format($transaction['valor_cliente'], 2, ',', '.');
        $nomeLoja = $transaction['loja_nome'];
        $isInstantaneo = ($transaction['loja_mvp'] === 'sim');
        
        if ($isInstantaneo) {
            // Mensagem para cashback instantâneo (loja MVP)
            $mensagem = "🎉 *Parabéns, {$nomeCliente}!*\n\n";
            $mensagem .= "✅ Seu cashback foi *creditado instantaneamente!*\n\n";
            $mensagem .= "🏪 *Loja:* {$nomeLoja}\n";
            $mensagem .= "💳 *Valor da compra:* R$ {$valorCompra}\n";
            $mensagem .= "💰 *Cashback recebido:* R$ {$valorCashback}\n\n";
            $mensagem .= "✨ *Saldo já disponível para uso!*\n\n";
            $mensagem .= "📱 Acesse sua conta: https://klubecash.com\n\n";
            $mensagem .= "_Klube Cash - Suas compras valem mais!_";
        } else {
            // Mensagem para cashback pendente
            $mensagem = "⭐ *{$nomeCliente}, sua compra foi registrada!*\n\n";
            $mensagem .= "⏰ Liberação em até 7 dias úteis.\n\n";
            $mensagem .= "🏪 {$nomeLoja}\n";
            $mensagem .= "💰 Compra: R$ {$valorCompra}\n";
            $mensagem .= "🎁 Cashback: R$ {$valorCashback}\n\n";
            $mensagem .= "💳 Acesse: https://klubecash.com\n\n";
            $mensagem .= "🔔 *Klube Cash - Dinheiro de volta no seu bolso!*";
        }
        
        return $mensagem;
    }
    
    /**
     * Formatar telefone para padrão internacional
     */
    private function formatarTelefone($telefone) {
        // Remover caracteres não numéricos
        $telefone = preg_replace('/[^0-9]/', '', $telefone);
        
        // Se tem 11 dígitos (formato brasileiro), adicionar código do país
        if (strlen($telefone) == 11) {
            $telefone = '55' . $telefone;
        }
        // Se tem 10 dígitos (sem o 9), adicionar código do país e o 9
        else if (strlen($telefone) == 10) {
            $telefone = '55' . substr($telefone, 0, 2) . '9' . substr($telefone, 2);
        }
        // Se já tem 13 dígitos (com código do país)
        else if (strlen($telefone) == 13 && substr($telefone, 0, 2) == '55') {
            // Já está no formato correto
        } else {
            return false; // Formato inválido
        }
        
        return $telefone;
    }
    
    /**
     * Enviar mensagem via Evolution API
     */
    private function enviarViaEvolution($phone, $message) {
        try {
            $url = "{$this->evolutionConfig['base_url']}/message/sendText/{$this->evolutionConfig['instance_name']}";
            
            $data = [
                'number' => $phone,
                'options' => [
                    'delay' => 1200,
                    'presence' => 'composing',
                    'linkPreview' => true
                ],
                'textMessage' => [
                    'text' => $message
                ]
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'apikey: ' . $this->evolutionConfig['api_key']
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                throw new Exception("Erro CURL: " . $error);
            }
            
            $responseData = json_decode($response, true);
            
            if ($httpCode === 201 || $httpCode === 200) {
                return [
                    'success' => true,
                    'response' => $responseData,
                    'message_id' => $responseData['key']['id'] ?? null
                ];
            } else {
                return [
                    'success' => false,
                    'error' => "HTTP {$httpCode}: " . ($responseData['message'] ?? $response)
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
     * Registrar envio no banco de dados
     */
    private function registrarEnvio($transactionId, $phone, $message, $success, $response, $error) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO whatsapp_evolution_logs (
                    transaction_id,
                    phone,
                    message,
                    success,
                    response,
                    event_type,
                    created_at
                ) VALUES (
                    :transaction_id,
                    :phone,
                    :message,
                    :success,
                    :response,
                    'cashback_notification',
                    NOW()
                )
            ");
            
            $stmt->bindParam(':transaction_id', $transactionId);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':message', $message);
            $stmt->bindParam(':success', $success, PDO::PARAM_INT);
            $stmt->bindParam(':response', json_encode($response ?? ['error' => $error]));
            $stmt->execute();
            
            // Também registrar na tabela whatsapp_logs principal
            $additionalData = [
                'transaction_id' => $transactionId,
                'message_preview' => substr($message, 0, 200)
            ];
            
            $stmt2 = $this->db->prepare("
                INSERT INTO whatsapp_logs (
                    type,
                    phone,
                    message_preview,
                    success,
                    error_message,
                    additional_data,
                    created_at
                ) VALUES (
                    'cashback_notification',
                    :phone,
                    :message_preview,
                    :success,
                    :error_message,
                    :additional_data,
                    NOW()
                )
            ");
            
            $stmt2->bindParam(':phone', $phone);
            $stmt2->bindParam(':message_preview', substr($message, 0, 200));
            $stmt2->bindParam(':success', $success, PDO::PARAM_INT);
            $stmt2->bindParam(':error_message', $error);
            $stmt2->bindParam(':additional_data', json_encode($additionalData));
            $stmt2->execute();
            
        } catch (Exception $e) {
            $this->log("Erro ao registrar envio no banco: " . $e->getMessage());
        }
    }
    
    /**
     * Registrar log em arquivo
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        error_log("WhatsApp Automation: {$message}");
    }
    
    /**
     * Verificar status da instância Evolution
     */
    public function verificarStatusInstancia() {
        try {
            $url = "{$this->evolutionConfig['base_url']}/instance/connectionState/{$this->evolutionConfig['instance_name']}";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'apikey: ' . $this->evolutionConfig['api_key']
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSABLE, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                return [
                    'connected' => ($data['state'] === 'open'),
                    'status' => $data
                ];
            }
            
            return ['connected' => false, 'status' => null];
            
        } catch (Exception $e) {
            return ['connected' => false, 'error' => $e->getMessage()];
        }
    }
}