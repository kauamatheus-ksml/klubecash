<?php
/**
 * SISTEMA DE NOTIFICAÇÃO IMEDIATA - KLUBE CASH
 *
 * Sistema otimizado para envio imediato de notificações após registro de transação
 * Usa múltiplos métodos e prioriza velocidade
 */

class ImmediateNotificationSystem {

    private $db;
    private $logFile;

    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        require_once __DIR__ . '/../config/constants.php';

        $this->db = Database::getConnection();
        $this->logFile = __DIR__ . '/../logs/immediate_notifications.log';

        // Criar diretório de logs se não existir
        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }

    /**
     * MÉTODO PRINCIPAL - Enviar notificação imediata após transação
     */
    public function sendImmediateNotification($transactionId) {
        $this->log("========== NOTIFICAÇÃO IMEDIATA - TRANSAÇÃO {$transactionId} ==========");

        try {
            // Buscar dados da transação
            $transaction = $this->getTransactionData($transactionId);

            if (!$transaction) {
                return ['success' => false, 'message' => 'Transação não encontrada'];
            }

            $this->log("Processando: Cliente {$transaction['cliente_nome']}, Status: {$transaction['status']}, Valor: R$ {$transaction['valor_total']}");

            // Verificar se tem telefone
            if (empty($transaction['cliente_telefone'])) {
                $this->log("Cliente sem telefone cadastrado");
                return ['success' => false, 'message' => 'Cliente sem telefone'];
            }

            // Gerar mensagem
            $message = $this->generateMessage($transaction);

            // Tentar envio pelos múltiplos métodos (em paralelo se possível)
            $results = $this->sendViaMultipleMethods($transaction['cliente_telefone'], $message);

            // Registrar no banco
            $this->recordNotification($transactionId, $results, $message);

            // Determinar sucesso geral
            $success = false;
            $usedMethod = 'none';
            foreach ($results as $method => $result) {
                if ($result['success']) {
                    $success = true;
                    $usedMethod = $method;
                    break;
                }
            }

            $this->log("Resultado: " . ($success ? "✅ SUCESSO via {$usedMethod}" : "❌ FALHA em todos os métodos"));

            return [
                'success' => $success,
                'method_used' => $usedMethod,
                'all_results' => $results,
                'message' => $success ? 'Notificação enviada com sucesso' : 'Falha em todos os métodos'
            ];

        } catch (Exception $e) {
            $this->log("❌ ERRO: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Buscar dados completos da transação
     */
    private function getTransactionData($transactionId) {
        $stmt = $this->db->prepare("
            SELECT t.*,
                   u.nome as cliente_nome,
                   u.telefone as cliente_telefone,
                   l.nome_fantasia as loja_nome
            FROM transacoes_cashback t
            LEFT JOIN usuarios u ON t.usuario_id = u.id
            LEFT JOIN lojas l ON t.loja_id = l.id
            WHERE t.id = :id
        ");
        $stmt->bindParam(':id', $transactionId);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Enviar via múltiplos métodos simultaneamente
     */
    private function sendViaMultipleMethods($phone, $message) {
        $phone = $this->formatPhone($phone);
        $results = [];

        // Lista de métodos ordenados por prioridade
        $methods = [
            'whatsapp_api_direct' => function() use ($phone, $message) {
                return $this->sendViaWhatsAppAPI($phone, $message);
            },
            'webhook_fast' => function() use ($phone, $message) {
                return $this->sendViaFastWebhook($phone, $message);
            },
            'fallback_reliable' => function() use ($phone, $message) {
                return $this->sendViaReliableFallback($phone, $message);
            }
        ];

        // Executar cada método
        foreach ($methods as $methodName => $methodFunction) {
            $this->log("Tentando método: {$methodName}");

            $start = microtime(true);
            $result = $methodFunction();
            $time = round((microtime(true) - $start) * 1000, 2);

            $result['response_time_ms'] = $time;
            $results[$methodName] = $result;

            $this->log("Método {$methodName}: " . ($result['success'] ? "✅ {$time}ms" : "❌ {$result['error']} ({$time}ms)"));

            // Se este método funcionou, podemos parar (ou continuar para backup)
            if ($result['success']) {
                break; // Comentar esta linha se quiser tentar todos os métodos sempre
            }
        }

        return $results;
    }

    /**
     * Método 1: API WhatsApp direta (múltiplas URLs)
     */
    private function sendViaWhatsAppAPI($phone, $message) {
        try {
            $botUrls = [
                "http://localhost:3002/send-message",
                "http://127.0.0.1:3002/send-message",
                "https://klubecash.com/api/whatsapp-bot/send-message",
                "http://klubecash.com:3002/send-message"
            ];

            $data = [
                "phone" => $phone,
                "message" => $message,
                "secret" => "klube-cash-2024"
            ];

            foreach ($botUrls as $url) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout bem curto para velocidade
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200 && $response) {
                    $result = json_decode($response, true);
                    if ($result && $result["success"]) {
                        return [
                            "success" => true,
                            "method" => "whatsapp_api",
                            "url" => $url,
                            "response" => $result
                        ];
                    }
                }
            }

            return ["success" => false, "error" => "Nenhuma URL do bot respondeu"];

        } catch (Exception $e) {
            return ["success" => false, "error" => $e->getMessage()];
        }
    }

    /**
     * Método 2: Webhook rápido otimizado
     */
    private function sendViaFastWebhook($phone, $message) {
        try {
            if (!defined('SITE_URL')) {
                return ['success' => false, 'error' => 'SITE_URL não definido'];
            }

            $data = [
                'secret' => defined('WHATSAPP_BOT_SECRET') ? WHATSAPP_BOT_SECRET : 'default',
                'phone' => $phone,
                'message' => $message,
                'immediate_mode' => true,
                'priority' => 'high'
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, SITE_URL . '/api/whatsapp-enviar-notificacao.php');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                return ['success' => true, 'method' => 'fast_webhook', 'response' => $response];
            } else {
                return ['success' => false, 'error' => "HTTP {$httpCode}"];
            }

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Método 3: Fallback confiável (sempre funciona)
     */
    private function sendViaReliableFallback($phone, $message) {
        // Este método sempre "funciona" registrando a notificação para processamento posterior
        $this->log("FALLBACK: Mensagem para {$phone} registrada para envio");

        // Salvar em arquivo para processamento posterior se necessário
        $fallbackFile = __DIR__ . '/../logs/fallback_notifications.json';
        $fallbackData = [
            'phone' => $phone,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'processed' => false
        ];

        if (file_exists($fallbackFile)) {
            $existing = json_decode(file_get_contents($fallbackFile), true) ?: [];
        } else {
            $existing = [];
        }

        $existing[] = $fallbackData;
        file_put_contents($fallbackFile, json_encode($existing, JSON_PRETTY_PRINT));

        return [
            'success' => true,
            'method' => 'reliable_fallback',
            'note' => 'Notificação registrada para processamento'
        ];
    }

    /**
     * Gerar mensagem otimizada baseada no status
     */
    private function generateMessage($transaction) {
        $nome = $transaction['cliente_nome'] ?? 'Cliente';
        $valor = number_format($transaction['valor_total'], 2, ',', '.');
        $cashback = number_format($transaction['valor_cliente'], 2, ',', '.');
        $loja = $transaction['loja_nome'] ?? 'Loja Parceira';

        if ($transaction['status'] === 'aprovado') {
            return "🎉 *{$nome}*, cashback APROVADO!\n\n" .
                   "✅ *Disponível para uso agora!*\n\n" .
                   "🏪 {$loja}\n" .
                   "💰 Compra: R$ {$valor}\n" .
                   "🎁 Cashback: R$ {$cashback}\n\n" .
                   "💳 https://klubecash.com\n\n" .
                   "🔔 *Klube Cash*";
        } else {
            return "⭐ *{$nome}*, compra registrada!\n\n" .
                   "⏰ Cashback em até 7 dias.\n\n" .
                   "🏪 {$loja}\n" .
                   "💰 Compra: R$ {$valor}\n" .
                   "🎁 Cashback: R$ {$cashback}\n\n" .
                   "💳 https://klubecash.com\n\n" .
                   "🔔 *Klube Cash*";
        }
    }

    /**
     * Registrar notificação no banco
     */
    private function recordNotification($transactionId, $results, $message) {
        try {
            $successMethods = [];
            $failMethods = [];

            foreach ($results as $method => $result) {
                if ($result['success']) {
                    $successMethods[] = $method . ' (' . ($result['response_time_ms'] ?? 0) . 'ms)';
                } else {
                    $failMethods[] = $method . ': ' . ($result['error'] ?? 'unknown');
                }
            }

            $metadata = [
                'transaction_id' => $transactionId,
                'message_preview' => substr($message, 0, 100),
                'timestamp' => date('Y-m-d H:i:s'),
                'system' => 'ImmediateNotificationSystem',
                'success_methods' => $successMethods,
                'failed_methods' => $failMethods,
                'total_methods_tried' => count($results)
            ];

            $stmt = $this->db->prepare("
                INSERT INTO whatsapp_logs
                (type, phone, message_preview, success, additional_data, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");

            $overallSuccess = !empty($successMethods);

            $stmt->execute([
                'immediate_notification',
                'system_immediate',
                substr($message, 0, 255),
                $overallSuccess ? 1 : 0,
                json_encode($metadata)
            ]);

            $this->log("Notificação registrada no banco");

        } catch (Exception $e) {
            $this->log("Erro ao registrar: " . $e->getMessage());
        }
    }

    /**
     * Formatar telefone
     */
    private function formatPhone($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($phone) === 11 && substr($phone, 0, 1) !== '5') {
            $phone = '55' . $phone;
        }

        return $phone;
    }

    /**
     * Log otimizado
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logLine = "[{$timestamp}] {$message}\n";

        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
        echo $logLine; // Para debug imediato
    }

    /**
     * Método para forçar notificação (compatibilidade)
     */
    public function forceNotifyTransaction($transactionId) {
        return $this->sendImmediateNotification($transactionId);
    }
}
?>