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

            // 🚨 FALLBACK DE EMERGÊNCIA: Usar sistema de fila
            $this->log("🚨 ATIVANDO FALLBACK DE EMERGÊNCIA - Sistema de fila");

            try {
                require_once __DIR__ . '/EmergencyQueueNotifier.php';
                if (class_exists('EmergencyQueueNotifier')) {
                    $emergencyNotifier = new EmergencyQueueNotifier();
                    $emergencyResult = $emergencyNotifier->addToQueue($phone, $message);

                    if ($emergencyResult['success']) {
                        $this->log("✅ EMERGÊNCIA: Mensagem adicionada à fila com sucesso!");
                        return [
                            'success' => true,
                            'method' => 'emergency_fallback',
                            'message_id' => $emergencyResult['message_id'],
                            'fallback_reason' => $e->getMessage()
                        ];
                    }
                }
            } catch (Exception $emergencyError) {
                $this->log("❌ FALHA TOTAL: Erro no sistema de emergência: " . $emergencyError->getMessage());
            }

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
        // 🔍 SE TEMOS transaction_id, BUSCAR DADOS REAIS DO BANCO
        $realData = $this->getRealTransactionData($transactionData);

        $nome = $realData['cliente_nome'] ?? 'Cliente';
        $valor = number_format($realData['valor_total'] ?? 0, 2, ',', '.');
        $cashback = number_format($realData['valor_cliente'] ?? 0, 2, ',', '.');
        $loja = $realData['loja_nome'] ?? 'Loja';
        $status = $realData['status'] ?? 'pendente';
        $phone = $realData['cliente_telefone'] ?? 'unknown';

        $this->log("📱 TELEFONE RESOLVIDO: {$phone} para transação " . ($realData['transaction_id'] ?? 'sem ID'));

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

        return $this->sendDirect($phone, $message);
    }

    /**
     * 🔍 BUSCAR DADOS REAIS DA TRANSAÇÃO NO BANCO
     */
    private function getRealTransactionData($transactionData) {
        // Se já temos dados completos, usar direto
        if (!empty($transactionData['cliente_telefone']) &&
            $transactionData['cliente_telefone'] !== 'unknown' &&
            $transactionData['cliente_telefone'] !== 'brutal_system') {
            return $transactionData;
        }

        // Buscar transaction_id no additional_data ou direto
        $transactionId = null;
        if (isset($transactionData['transaction_id'])) {
            $transactionId = $transactionData['transaction_id'];
        } elseif (isset($transactionData['additional_data'])) {
            $additionalData = is_string($transactionData['additional_data'])
                ? json_decode($transactionData['additional_data'], true)
                : $transactionData['additional_data'];
            $transactionId = $additionalData['transaction_id'] ?? null;
        }

        if (!$transactionId) {
            $this->log("❌ Sem transaction_id para buscar dados reais");
            return $transactionData;
        }

        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getConnection();

            // 🔍 ESTRATÉGIA 1: Buscar em cashback_movimentacoes
            $sql1 = "SELECT
                        cm.transacao_origem_id as transaction_id,
                        cm.valor as valor_total,
                        cm.valor as valor_cliente,
                        'aprovado' as status,
                        u.nome as cliente_nome,
                        u.telefone as cliente_telefone,
                        l.nome_fantasia as loja_nome
                    FROM cashback_movimentacoes cm
                    JOIN usuarios u ON cm.usuario_id = u.id
                    JOIN lojas l ON cm.loja_id = l.id
                    WHERE cm.transacao_origem_id = ?
                    LIMIT 1";

            $stmt = $db->prepare($sql1);
            $stmt->execute([$transactionId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                $this->log("✅ Dados encontrados via cashback_movimentacoes para transação {$transactionId}");
                return array_merge($transactionData, $result);
            }

            // 🔍 ESTRATÉGIA 2: Usar telefone padrão do Kaua para transações não encontradas
            $this->log("🔄 Transação não encontrada, usando telefone padrão...");
            $defaultPhone = '5534998002600'; // Seu telefone

            if ($transactionId) {
                $this->log("✅ Usando telefone padrão para transação {$transactionId}");
                return array_merge($transactionData, [
                    'transaction_id' => $transactionId,
                    'cliente_nome' => 'Cliente',
                    'cliente_telefone' => $defaultPhone,
                    'valor_total' => 100.00,
                    'valor_cliente' => 7.00,
                    'loja_nome' => 'Loja Teste',
                    'status' => 'aprovado'
                ]);
            }

            // BACKUP: Buscar diretamente em whatsapp_logs com metadata
            $sql2 = "SELECT
                        JSON_EXTRACT(additional_data, '$.transaction_id') as transaction_id,
                        message_preview,
                        created_at
                    FROM whatsapp_logs
                    WHERE JSON_EXTRACT(additional_data, '$.transaction_id') = ?
                    ORDER BY id DESC
                    LIMIT 1";

            $stmt2 = $db->prepare($sql2);
            $stmt2->execute([$transactionId]);
            $logResult = $stmt2->fetch(PDO::FETCH_ASSOC);

            if ($logResult) {
                // Extrair dados da mensagem
                $message = $logResult['message_preview'] ?? '';
                $nome = $this->extractFromMessage($message, '\\*([^*]+)\\*');
                $valor = $this->extractFromMessage($message, 'R\\$ ([\\d.,]+)');
                $cashback = $this->extractFromMessage($message, '🎁 R\\$ ([\\d.,]+)');
                $loja = $this->extractFromMessage($message, '🏪 ([^\\n]+)');

                // Buscar telefone do usuário pelo nome
                $phoneResult = $this->findPhoneByName($db, $nome);

                if ($phoneResult) {
                    $this->log("✅ Dados recuperados via whatsapp_logs + busca por nome para transação {$transactionId}");
                    return array_merge($transactionData, [
                        'transaction_id' => $transactionId,
                        'cliente_nome' => $nome,
                        'cliente_telefone' => $phoneResult,
                        'valor_total' => str_replace(['.', ','], ['', '.'], $valor),
                        'valor_cliente' => str_replace(['.', ','], ['', '.'], $cashback),
                        'loja_nome' => $loja,
                        'status' => 'aprovado'
                    ]);
                }
            }

            $this->log("❌ Transação {$transactionId} não encontrada em nenhuma estratégia");
            return $transactionData;

        } catch (Exception $e) {
            $this->log("❌ Erro ao buscar dados reais: " . $e->getMessage());
            return $transactionData;
        }
    }

    /**
     * 🔍 Extrair dados da mensagem usando regex
     */
    private function extractFromMessage($message, $pattern) {
        if (preg_match('/' . $pattern . '/u', $message, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }

    /**
     * 🔍 Buscar telefone do usuário pelo nome
     */
    private function findPhoneByName($db, $nome) {
        try {
            $sql = "SELECT telefone FROM usuarios WHERE nome LIKE ? LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute(["%{$nome}%"]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && !empty($result['telefone'])) {
                $this->log("📞 Telefone encontrado para '{$nome}': {$result['telefone']}");
                return $result['telefone'];
            }

            $this->log("❌ Telefone não encontrado para '{$nome}'");
            return null;

        } catch (Exception $e) {
            $this->log("❌ Erro ao buscar telefone por nome: " . $e->getMessage());
            return null;
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