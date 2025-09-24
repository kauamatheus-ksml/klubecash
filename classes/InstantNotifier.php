<?php
/**
 * NOTIFICADOR INSTANTÂNEO - KLUBE CASH
 *
 * Classe ultra-simples para envio imediato
 * Usa webhook interno direto
 */

class InstantNotifier {

    private $webhookUrl;
    private $logFile;

    public function __construct() {
        // Determinar URL base
        if (isset($_SERVER['HTTP_HOST'])) {
            $this->webhookUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/api/instant-whatsapp.php';
        } else {
            // Fallback para CLI ou quando não há HTTP_HOST
            $this->webhookUrl = 'https://klubecash.com/api/instant-whatsapp.php';
        }
        $this->logFile = __DIR__ . '/../logs/instant_notifier.log';

        // Criar diretório se não existir
        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }

    /**
     * Enviar notificação instantânea
     */
    public function sendInstant($phone, $message) {
        $this->log("ENVIANDO INSTANT: {$phone}");

        try {
            $data = [
                'phone' => $this->formatPhone($phone),
                'message' => $message,
                'source' => 'InstantNotifier',
                'timestamp' => time()
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->webhookUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $start = microtime(true);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $time = round((microtime(true) - $start) * 1000, 2);
            curl_close($ch);

            if ($httpCode === 200) {
                $this->log("✅ SUCESSO em {$time}ms");
                return [
                    'success' => true,
                    'time_ms' => $time,
                    'method' => 'instant_webhook',
                    'response' => json_decode($response, true)
                ];
            } else {
                throw new Exception("HTTP {$httpCode}: {$response}");
            }

        } catch (Exception $e) {
            $this->log("❌ ERRO: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Notificar transação completa
     */
    public function notifyTransaction($transactionData) {
        $nome = $transactionData['cliente_nome'] ?? 'Cliente';
        $valor = number_format($transactionData['valor_total'] ?? 0, 2, ',', '.');
        $cashback = number_format($transactionData['valor_cliente'] ?? 0, 2, ',', '.');
        $loja = $transactionData['loja_nome'] ?? 'Loja';
        $status = $transactionData['status'] ?? 'pendente';

        if ($status === 'aprovado') {
            $message = "🎉 *{$nome}*, cashback APROVADO!\n\n" .
                      "✅ *Disponível agora!*\n\n" .
                      "🏪 {$loja}\n" .
                      "💰 R$ {$valor} → 🎁 R$ {$cashback}\n\n" .
                      "💳 https://klubecash.com";
        } else {
            $message = "⭐ *{$nome}*, compra registrada!\n\n" .
                      "⏰ Cashback em até 7 dias\n\n" .
                      "🏪 {$loja}\n" .
                      "💰 R$ {$valor} → 🎁 R$ {$cashback}\n\n" .
                      "💳 https://klubecash.com";
        }

        return $this->sendInstant($transactionData['cliente_telefone'], $message);
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
     * Log simples
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->logFile, "[{$timestamp}] {$message}\n", FILE_APPEND | LOCK_EX);
    }
}
?>