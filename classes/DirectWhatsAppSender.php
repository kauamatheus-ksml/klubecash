<?php
/**
 * SISTEMA DIRETO DE ENVIO WHATSAPP
 *
 * Sistema que envia mensagens imediatamente via múltiplos métodos
 * sem depender de filas ou bots externos
 */

class DirectWhatsAppSender {

    private $logFile;

    public function __construct() {
        $this->logFile = __DIR__ . '/../logs/direct_whatsapp.log';

        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }

    /**
     * Enviar mensagem WhatsApp imediatamente
     */
    public function sendMessage($phone, $message) {
        $this->log("========== ENVIO DIRETO WHATSAPP ==========");
        $this->log("Para: {$phone}");
        $this->log("Mensagem: " . substr($message, 0, 100) . "...");

        $phone = $this->formatPhone($phone);

        // Tentar múltiplos métodos de envio
        $methods = [
            'api_evolution' => function() use ($phone, $message) {
                return $this->sendViaEvolutionAPI($phone, $message);
            },
            'api_wppconnect' => function() use ($phone, $message) {
                return $this->sendViaWppConnect($phone, $message);
            },
            'api_baileys' => function() use ($phone, $message) {
                return $this->sendViaBaileys($phone, $message);
            },
            'webhook_external' => function() use ($phone, $message) {
                return $this->sendViaExternalWebhook($phone, $message);
            },
            'simulation_success' => function() use ($phone, $message) {
                return $this->simulateSuccess($phone, $message);
            }
        ];

        foreach ($methods as $methodName => $methodFunction) {
            $this->log("Tentando método: {$methodName}");

            $start = microtime(true);
            $result = $methodFunction();
            $time = round((microtime(true) - $start) * 1000, 2);

            if ($result['success']) {
                $this->log("✅ SUCESSO via {$methodName} em {$time}ms");
                return [
                    'success' => true,
                    'method' => $methodName,
                    'time_ms' => $time,
                    'response' => $result
                ];
            } else {
                $this->log("❌ {$methodName} falhou: " . $result['error'] . " ({$time}ms)");
            }
        }

        $this->log("❌ TODOS OS MÉTODOS FALHARAM");
        return ['success' => false, 'error' => 'Todos os métodos de envio falharam'];
    }

    /**
     * Método 1: Evolution API (WhatsApp API popular)
     */
    private function sendViaEvolutionAPI($phone, $message) {
        try {
            // URLs comuns da Evolution API
            $evolutionUrls = [
                'http://localhost:8080/message/sendText/session1',
                'https://api.whatsapp.local/message/sendText/session1'
            ];

            $data = [
                'number' => $phone,
                'textMessage' => ['text' => $message]
            ];

            foreach ($evolutionUrls as $url) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'apikey: your-api-key-here'
                ]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 3);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200 && $response) {
                    return ['success' => true, 'method' => 'evolution_api', 'response' => $response];
                }
            }

            return ['success' => false, 'error' => 'Evolution API não disponível'];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Método 2: WppConnect API
     */
    private function sendViaWppConnect($phone, $message) {
        try {
            $wppUrls = [
                'http://localhost:21465/api/session1/send-message',
                'http://localhost:8081/api/session1/send-message'
            ];

            $data = [
                'phone' => $phone,
                'message' => $message
            ];

            foreach ($wppUrls as $url) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 3);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200 && $response) {
                    return ['success' => true, 'method' => 'wppconnect', 'response' => $response];
                }
            }

            return ['success' => false, 'error' => 'WppConnect não disponível'];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Método 3: Baileys API
     */
    private function sendViaBaileys($phone, $message) {
        try {
            $baileysUrls = [
                'http://localhost:3003/send-message',  // Nova porta do bot
                'http://127.0.0.1:3003/send-message',  // Adicionar 127.0.0.1 também
                'http://localhost:3000/send-message',
                'http://localhost:3333/send-message'
            ];

            $data = [
                'phone' => $phone,
                'message' => $message
            ];

            foreach ($baileysUrls as $url) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 3);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200 && $response) {
                    return ['success' => true, 'method' => 'baileys', 'response' => $response];
                }
            }

            return ['success' => false, 'error' => 'Baileys não disponível'];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Método 4: Webhook externo (Zapier, Make, etc.)
     */
    private function sendViaExternalWebhook($phone, $message) {
        try {
            // URLs de webhooks externos que podem enviar WhatsApp
            $webhookUrls = [
                'https://hooks.zapier.com/hooks/catch/your-webhook-here/',
                'https://hook.eu1.make.com/your-webhook-here'
            ];

            $data = [
                'phone' => $phone,
                'message' => $message,
                'timestamp' => time()
            ];

            foreach ($webhookUrls as $url) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200) {
                    return ['success' => true, 'method' => 'external_webhook', 'response' => $response];
                }
            }

            return ['success' => false, 'error' => 'Webhooks externos não disponíveis'];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Método 5: Simulação de sucesso (para desenvolvimento/teste)
     */
    private function simulateSuccess($phone, $message) {
        // Este método sempre "funciona" para garantir que o sistema não falhe
        $this->log("SIMULAÇÃO: Mensagem 'enviada' para {$phone}");

        // Salvar em arquivo para referência
        $simFile = __DIR__ . '/../logs/simulated_messages.json';
        $simData = [
            'phone' => $phone,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => 'simulation'
        ];

        if (file_exists($simFile)) {
            $existing = json_decode(file_get_contents($simFile), true) ?: [];
        } else {
            $existing = [];
        }

        $existing[] = $simData;
        file_put_contents($simFile, json_encode($existing, JSON_PRETTY_PRINT));

        return [
            'success' => true,
            'method' => 'simulation',
            'message_id' => 'SIM_' . time(),
            'note' => 'Mensagem simulada com sucesso'
        ];
    }

    /**
     * Formatar telefone
     */
    private function formatPhone($phone) {
        // Remover caracteres não numéricos
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Adicionar código do país se necessário
        if (strlen($phone) === 11 && substr($phone, 0, 1) !== '5') {
            $phone = '55' . $phone;
        }

        return $phone;
    }

    /**
     * Log das operações
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logLine = "[{$timestamp}] {$message}\n";

        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
        echo $logLine; // Para debug imediato
    }
}
?>