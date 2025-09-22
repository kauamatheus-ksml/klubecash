<?php
/**
 * 🚨 PROCESSADOR DE FILA DE EMERGÊNCIA
 *
 * Script que processa mensagens pendentes na fila
 * GARANTE QUE TODAS AS MENSAGENS SEJAM ENVIADAS
 */

require_once __DIR__ . '/config/database.php';

class QueueProcessor {

    private $queueDir;
    private $logFile;
    private $botUrl;

    public function __construct() {
        $this->queueDir = __DIR__ . '/queue';
        $this->logFile = __DIR__ . '/logs/queue_processor.log';
        $this->botUrl = 'http://localhost:3003/send-message';

        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }

    /**
     * 🔄 PROCESSAR TODAS AS MENSAGENS DA FILA
     */
    public function processQueue() {
        $this->log("🔄 INICIANDO PROCESSAMENTO DA FILA...");

        if (!is_dir($this->queueDir)) {
            $this->log("❌ Diretório da fila não encontrado: {$this->queueDir}");
            return;
        }

        $messageFiles = glob($this->queueDir . '/message_*.json');
        $totalMessages = count($messageFiles);

        if ($totalMessages === 0) {
            $this->log("✅ Fila vazia - nenhuma mensagem para processar");
            return;
        }

        $this->log("📋 Encontradas {$totalMessages} mensagens na fila");

        $processed = 0;
        $successful = 0;
        $failed = 0;

        foreach ($messageFiles as $messageFile) {
            $processed++;
            $this->log("📤 Processando {$processed}/{$totalMessages}: " . basename($messageFile));

            if ($this->processMessage($messageFile)) {
                $successful++;
            } else {
                $failed++;
            }
        }

        $this->log("✅ PROCESSAMENTO CONCLUÍDO: {$successful} sucessos, {$failed} falhas");
    }

    /**
     * 📤 PROCESSAR UMA MENSAGEM ESPECÍFICA
     */
    private function processMessage($messageFile) {
        try {
            // Ler dados da mensagem
            $messageJson = file_get_contents($messageFile);
            if (!$messageJson) {
                $this->log("❌ Erro ao ler arquivo: " . basename($messageFile));
                return false;
            }

            $messageData = json_decode($messageJson, true);
            if (!$messageData) {
                $this->log("❌ JSON inválido em: " . basename($messageFile));
                unlink($messageFile); // Remove arquivo corrompido
                return false;
            }

            // Verificar se já excedeu tentativas
            if ($messageData['attempts'] >= $messageData['max_attempts']) {
                $this->log("❌ Máximo de tentativas excedido para: {$messageData['id']}");
                $this->moveToFailed($messageFile, $messageData);
                return false;
            }

            // Incrementar tentativas
            $messageData['attempts']++;
            $messageData['last_attempt'] = date('Y-m-d H:i:s');

            // Tentar enviar
            $sendResult = $this->sendMessage($messageData['phone'], $messageData['message']);

            if ($sendResult['success']) {
                $this->log("✅ SUCESSO: Mensagem enviada para {$messageData['phone']} - ID: {$messageData['id']}");
                unlink($messageFile); // Remove da fila
                return true;
            } else {
                // Salvar erro e tentar novamente
                $messageData['last_error'] = $sendResult['error'];
                file_put_contents($messageFile, json_encode($messageData, JSON_PRETTY_PRINT));

                $this->log("❌ FALHA (tentativa {$messageData['attempts']}/{$messageData['max_attempts']}): {$sendResult['error']}");
                return false;
            }

        } catch (Exception $e) {
            $this->log("❌ ERRO no processamento: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 📤 ENVIAR MENSAGEM PARA O BOT
     */
    private function sendMessage($phone, $message) {
        try {
            $data = [
                'phone' => $this->formatPhone($phone),
                'message' => $message,
                'secret' => 'klube-cash-2024'
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->botUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

            $start = microtime(true);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $time = round((microtime(true) - $start) * 1000, 2);
            curl_close($ch);

            if ($httpCode === 200) {
                $result = json_decode($response, true);
                if ($result && isset($result['success']) && $result['success']) {
                    return [
                        'success' => true,
                        'time_ms' => $time,
                        'response' => $result
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => "Bot retornou erro: " . ($result['error'] ?? 'desconhecido')
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => "HTTP {$httpCode}: {$response}"
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
     * 📁 MOVER PARA PASTA DE FALHAS
     */
    private function moveToFailed($messageFile, $messageData) {
        $failedDir = $this->queueDir . '/failed';
        if (!is_dir($failedDir)) {
            mkdir($failedDir, 0755, true);
        }

        $failedFile = $failedDir . '/' . basename($messageFile);
        $messageData['failed_at'] = date('Y-m-d H:i:s');
        file_put_contents($failedFile, json_encode($messageData, JSON_PRETTY_PRINT));
        unlink($messageFile);
    }

    /**
     * 📱 FORMATAR TELEFONE
     */
    private function formatPhone($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($phone) === 11 && substr($phone, 0, 1) !== '5') {
            $phone = '55' . $phone;
        }

        return $phone;
    }

    /**
     * 📝 LOG
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logLine = "[{$timestamp}] {$message}\n";
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
        echo $logLine; // Para execução manual
    }
}

// ===== EXECUÇÃO =====
if (php_sapi_name() === 'cli' || isset($_GET['run'])) {
    $processor = new QueueProcessor();
    $processor->processQueue();
} else {
    echo "🚨 PROCESSADOR DE FILA DE EMERGÊNCIA\n\n";
    echo "Para executar:\n";
    echo "- Via CLI: php process_queue.php\n";
    echo "- Via Web: ?run=1\n";
}
?>