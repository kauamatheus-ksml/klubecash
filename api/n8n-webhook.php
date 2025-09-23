<?php
require_once '../config/database.php';
require_once '../config/constants.php';

class N8nWebhook {
    
    public static function sendTransactionData($transactionId, $type = 'nova_transacao') {
        try {
            if (!defined('N8N_ENABLED') || !N8N_ENABLED) {
                error_log("N8N Webhook: Sistema desabilitado");
                return false;
            }

            $db = Database::getConnection();
            
            // Buscar dados completos da transação
            $stmt = $db->prepare("
                SELECT 
                    t.id,
                    t.codigo_transacao,
                    t.valor_total,
                    t.valor_cashback,
                    t.valor_saldo_usado,
                    t.data_criacao,
                    t.status,
                    t.usuario_id,
                    u.nome as cliente_nome,
                    u.telefone as cliente_telefone,
                    u.email as cliente_email,
                    u.cpf as cliente_cpf,
                    l.nome_fantasia as loja_nome,
                    l.id as loja_id,
                    CASE WHEN t.valor_saldo_usado > 0 THEN 'mvp' ELSE 'normal' END as tipo_transacao
                FROM transacoes_cashback t
                JOIN usuarios u ON t.usuario_id = u.id
                JOIN lojas l ON t.loja_id = l.id
                WHERE t.id = ?
            ");
            $stmt->execute([$transactionId]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$transaction) {
                error_log("N8N Webhook: Transação não encontrada - ID: {$transactionId}");
                return false;
            }

            // Validar se cliente tem telefone
            if (empty($transaction['cliente_telefone'])) {
                error_log("N8N Webhook: Cliente sem telefone - Transação ID: {$transactionId}");
                return false;
            }

            // Formatar telefone para WhatsApp (garantir que tenha 55)
            $telefone = preg_replace('/\D/', '', $transaction['cliente_telefone']);
            if (!str_starts_with($telefone, '55')) {
                $telefone = '55' . $telefone;
            }
            
            // Preparar dados para envio
            $webhookData = [
                'evento' => $type,
                'timestamp' => date('Y-m-d H:i:s'),
                'source' => 'klubecash-php',
                'version' => '1.0',
                'transacao' => [
                    'id' => intval($transaction['id']),
                    'codigo' => $transaction['codigo_transacao'],
                    'valor_total' => floatval($transaction['valor_total']),
                    'valor_cashback' => floatval($transaction['valor_cashback']),
                    'valor_saldo_usado' => floatval($transaction['valor_saldo_usado']),
                    'data_criacao' => $transaction['data_criacao'],
                    'status' => $transaction['status'],
                    'tipo' => $transaction['tipo_transacao']
                ],
                'cliente' => [
                    'id' => intval($transaction['usuario_id']),
                    'nome' => $transaction['cliente_nome'],
                    'telefone' => $telefone,
                    'telefone_original' => $transaction['cliente_telefone'],
                    'email' => $transaction['cliente_email'],
                    'cpf_parcial' => substr($transaction['cliente_cpf'], 0, 3) . '***'
                ],
                'loja' => [
                    'id' => intval($transaction['loja_id']),
                    'nome' => $transaction['loja_nome']
                ]
            ];
            
            // Adicionar dados específicos do evento
            if ($type === 'cashback_liberado') {
                $webhookData['liberacao'] = [
                    'data_liberacao' => date('Y-m-d H:i:s'),
                    'valor_liberado' => floatval($transaction['valor_cashback']),
                    'metodo_liberacao' => 'pagamento_aprovado'
                ];
            }
            
            // Enviar para N8N
            return self::callN8nWebhook($webhookData);
            
        } catch (Exception $e) {
            error_log("N8N Webhook Error: " . $e->getMessage());
            return false;
        }
    }
    
    private static function callN8nWebhook($data) {
        try {
            $webhookUrl = N8N_WEBHOOK_URL;
            $webhookSecret = N8N_WEBHOOK_SECRET;
            
            // Criar payload sem assinatura primeiro
            $payload = json_encode($data);
            
            // Adicionar assinatura de segurança
            $signature = hash_hmac('sha256', $payload, $webhookSecret);
            $data['signature'] = $signature;
            
            // Payload final com assinatura
            $finalPayload = json_encode($data);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $webhookUrl,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $finalPayload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'X-Webhook-Source: KlubeCash',
                    'X-Webhook-Secret: ' . $webhookSecret,
                    'X-Webhook-Version: 1.0',
                    'User-Agent: KlubeCash-N8N-Webhook/1.0'
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => N8N_TIMEOUT,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_VERBOSE => false
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                error_log("N8N Webhook CURL Error: " . $error);
                self::logWebhookCall($data['transacao']['id'], $data['evento'], false, "CURL Error: " . $error);
                return false;
            }
            
            if ($httpCode >= 200 && $httpCode < 300) {
                error_log("N8N Webhook: Dados enviados com sucesso para transação " . $data['transacao']['id'] . " - HTTP {$httpCode}");
                self::logWebhookCall($data['transacao']['id'], $data['evento'], true, $response);
                return true;
            } else {
                error_log("N8N Webhook: Falha HTTP {$httpCode} - Response: " . $response);
                self::logWebhookCall($data['transacao']['id'], $data['evento'], false, "HTTP {$httpCode}: " . $response);
                return false;
            }
            
        } catch (Exception $e) {
            error_log("N8N Webhook Exception: " . $e->getMessage());
            self::logWebhookCall($data['transacao']['id'] ?? 0, $data['evento'] ?? 'unknown', false, "Exception: " . $e->getMessage());
            return false;
        }
    }
    
    private static function logWebhookCall($transactionId, $eventType, $success, $response) {
        try {
            $db = Database::getConnection();
            
            // Criar tabela se não existir
            $createTableStmt = $db->prepare("
                CREATE TABLE IF NOT EXISTS n8n_webhook_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    transaction_id INT,
                    event_type VARCHAR(50),
                    success BOOLEAN,
                    response TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_transaction (transaction_id),
                    INDEX idx_event_type (event_type),
                    INDEX idx_created_at (created_at)
                )
            ");
            $createTableStmt->execute();
            
            $logStmt = $db->prepare("
                INSERT INTO n8n_webhook_logs (transaction_id, event_type, success, response) 
                VALUES (?, ?, ?, ?)
            ");
            $logStmt->execute([
                $transactionId, 
                $eventType, 
                $success, 
                substr($response, 0, 2000) // Limitar tamanho da resposta
            ]);
            
        } catch (Exception $e) {
            error_log("N8N Webhook Log Error: " . $e->getMessage());
        }
    }
    
    /**
     * Método para testar conectividade do N8N
     */
    public static function testConnection() {
        try {
            $testData = [
                'evento' => 'test_connection',
                'timestamp' => date('Y-m-d H:i:s'),
                'source' => 'klubecash-test',
                'test' => true
            ];
            
            return self::callN8nWebhook($testData);
            
        } catch (Exception $e) {
            error_log("N8N Test Connection Error: " . $e->getMessage());
            return false;
        }
    }
}
?>