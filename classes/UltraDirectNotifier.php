<?php
/**
 * NOTIFICADOR ULTRA DIRETO - KLUBE CASH
 *
 * Conecta DIRETAMENTE no bot local
 * Sem webhooks, sem complexidade
 */

class UltraDirectNotifier {

    private $botUrl;
    private $logFile;

    public function __construct() {
        $this->botUrl = 'http://localhost:3003/send-message';
        $this->logFile = __DIR__ . '/../logs/ultra_direct.log';

        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }

    /**
     * Enviar mensagem DIRETAMENTE para o bot
     */
    public function sendDirect($phone, $message) {
        $this->log("ENVIANDO DIRETO: {$phone}");

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
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);

            $start = microtime(true);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $time = round((microtime(true) - $start) * 1000, 2);
            curl_close($ch);

            $this->log("Bot respondeu: HTTP {$httpCode} em {$time}ms");

            if ($httpCode === 200) {
                $result = json_decode($response, true);
                if ($result && isset($result['success']) && $result['success']) {
                    $this->log("✅ SUCESSO DIRETO em {$time}ms");
                    return [
                        'success' => true,
                        'time_ms' => $time,
                        'method' => 'ultra_direct',
                        'response' => $result
                    ];
                } else {
                    throw new Exception("Bot retornou erro: " . ($result['error'] ?? 'desconhecido'));
                }
            } else {
                throw new Exception("HTTP {$httpCode}: {$response}");
            }

        } catch (Exception $e) {
            $this->log("❌ ERRO DIRETO: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'method' => 'ultra_direct_failed'
            ];
        }
    }

    /**
     * Notificar transação (método principal)
     */
    public function notifyTransaction($transactionData) {
        $nome = $transactionData['cliente_nome'] ?? 'Cliente';
        $valor = number_format($transactionData['valor_total'] ?? 0, 2, ',', '.');
        $cashback = number_format($transactionData['valor_cliente'] ?? 0, 2, ',', '.');
        $loja = $transactionData['loja_nome'] ?? 'Loja';
        $status = $transactionData['status'] ?? 'pendente';

        // Mensagem otimizada
        if ($status === 'aprovado') {
            $message = "🎉 *{$nome}*, cashback APROVADO!\n\n" .
                      "✅ Disponível agora!\n" .
                      "🏪 {$loja}\n" .
                      "💰 R$ {$valor} → 🎁 R$ {$cashback}\n\n" .
                      "💳 https://klubecash.com";
        } else {
            $message = "⭐ *{$nome}*, compra registrada!\n\n" .
                      "⏰ Cashback em até 7 dias\n" .
                      "🏪 {$loja}\n" .
                      "💰 R$ {$valor} → 🎁 R$ {$cashback}\n\n" .
                      "💳 https://klubecash.com";
        }

        return $this->sendDirect($transactionData['cliente_telefone'], $message);
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
     * Log ultra simples
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logLine = "[{$timestamp}] {$message}\n";
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
        echo $logLine; // Para debug imediato
    }

    /**
     * Testar conexão com o bot
     */
    public function testBot() {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://localhost:3003/status');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $status = json_decode($response, true);
                $this->log("Bot Status: " . ($status['status'] ?? 'unknown'));
                return ['success' => true, 'status' => $status];
            } else {
                throw new Exception("HTTP {$httpCode}");
            }

        } catch (Exception $e) {
            $this->log("Erro ao testar bot: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>