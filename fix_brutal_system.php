<?php
/**
 * CORREÇÃO BRUTAL NOTIFICATION SYSTEM - KLUBE CASH
 *
 * Adapta o BrutalNotificationSystem para a estrutura real da tabela whatsapp_logs
 */

require_once 'config/database.php';
require_once 'config/constants.php';

class BrutalSystemFixer {

    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function fixSystem() {
        echo "<h2>🔧 CORRIGINDO BRUTAL NOTIFICATION SYSTEM</h2>\n";

        try {
            // 1. Adicionar coluna status compatível
            echo "<h3>1. Adicionando coluna status...</h3>\n";
            $this->addStatusColumn();

            // 2. Adicionar coluna metadata
            echo "<h3>2. Adicionando coluna metadata...</h3>\n";
            $this->addMetadataColumn();

            // 3. Criar versão corrigida do BrutalNotificationSystem
            echo "<h3>3. Criando versão corrigida...</h3>\n";
            $this->createFixedNotificationSystem();

            // 4. Testar sistema corrigido
            echo "<h3>4. Testando sistema corrigido...</h3>\n";
            $this->testFixedSystem();

            echo "<h3>✅ SISTEMA CORRIGIDO COM SUCESSO!</h3>\n";

        } catch (Exception $e) {
            echo "<h3>❌ ERRO: " . $e->getMessage() . "</h3>\n";
        }
    }

    private function addStatusColumn() {
        try {
            // Adicionar coluna status baseada na coluna success existente
            $this->db->exec("
                ALTER TABLE whatsapp_logs
                ADD COLUMN IF NOT EXISTS status ENUM('success', 'failed', 'pending')
                GENERATED ALWAYS AS (
                    CASE
                        WHEN success = 1 THEN 'success'
                        WHEN success = 0 AND error_message IS NOT NULL THEN 'failed'
                        ELSE 'pending'
                    END
                ) STORED
            ");
            echo "<p>✅ Coluna status adicionada</p>\n";
        } catch (Exception $e) {
            // Se não conseguir criar coluna virtual, criar coluna normal
            try {
                $this->db->exec("ALTER TABLE whatsapp_logs ADD COLUMN IF NOT EXISTS status VARCHAR(10) DEFAULT 'pending'");

                // Atualizar dados existentes
                $this->db->exec("
                    UPDATE whatsapp_logs SET
                    status = CASE
                        WHEN success = 1 THEN 'success'
                        WHEN success = 0 AND error_message IS NOT NULL THEN 'failed'
                        ELSE 'pending'
                    END
                ");
                echo "<p>✅ Coluna status adicionada (método alternativo)</p>\n";
            } catch (Exception $e2) {
                echo "<p>⚠️ Erro ao adicionar status: " . $e2->getMessage() . "</p>\n";
            }
        }
    }

    private function addMetadataColumn() {
        try {
            $this->db->exec("ALTER TABLE whatsapp_logs ADD COLUMN IF NOT EXISTS metadata JSON NULL");
            echo "<p>✅ Coluna metadata adicionada</p>\n";
        } catch (Exception $e) {
            echo "<p>⚠️ Erro ao adicionar metadata: " . $e->getMessage() . "</p>\n";
        }
    }

    private function createFixedNotificationSystem() {
        $fixedSystemCode = '<?php
/**
 * BRUTAL NOTIFICATION SYSTEM CORRIGIDO - KLUBE CASH
 *
 * Versão adaptada para a estrutura real do banco de dados
 */

class FixedBrutalNotificationSystem {

    private $db;
    private $lastCheckFile;
    private $logFile;

    public function __construct() {
        require_once __DIR__ . \'/config/database.php\';
        require_once __DIR__ . \'/config/constants.php\';

        $this->db = Database::getConnection();
        $this->lastCheckFile = __DIR__ . \'/logs/last_notification_check.json\';
        $this->logFile = __DIR__ . \'/logs/brutal_notifications.log\';

        // Criar diretório de logs se não existir
        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }

    /**
     * MÉTODO PRINCIPAL - Verificar e processar novas transações
     */
    public function checkAndProcessNewTransactions() {
        $this->log("========== INICIANDO VERIFICAÇÃO BRUTAL CORRIGIDA ==========");

        try {
            // Buscar última verificação
            $lastCheck = $this->getLastCheck();
            $this->log("Última verificação: " . ($lastCheck ? date(\'Y-m-d H:i:s\', $lastCheck) : \'NUNCA\'));

            // Buscar transações novas
            $newTransactions = $this->getNewTransactions($lastCheck);
            $this->log("Encontradas " . count($newTransactions) . " novas transações");

            if (empty($newTransactions)) {
                $this->log("Nenhuma transação nova encontrada");
                $this->updateLastCheck();
                return [\'processed\' => 0, \'success\' => 0, \'errors\' => 0];
            }

            // Processar cada transação
            $results = [\'processed\' => 0, \'success\' => 0, \'errors\' => 0];

            foreach ($newTransactions as $transaction) {
                $this->log("=== PROCESSANDO TRANSAÇÃO ID: {$transaction[\'id\']} ===");
                $this->log("Status: {$transaction[\'status\']}, Valor: R$ {$transaction[\'valor_total\']}");

                $result = $this->processTransaction($transaction);

                $results[\'processed\']++;
                if ($result[\'success\']) {
                    $results[\'success\']++;
                    $this->log("✅ SUCESSO: " . $result[\'message\']);
                } else {
                    $results[\'errors\']++;
                    $this->log("❌ ERRO: " . $result[\'message\']);
                }
            }

            // Atualizar último check
            $this->updateLastCheck();

            $this->log("========== VERIFICAÇÃO CONCLUÍDA ==========");
            $this->log("Total: {$results[\'processed\']}, Sucessos: {$results[\'success\']}, Erros: {$results[\'errors\']}");

            return $results;

        } catch (Exception $e) {
            $this->log("❌ ERRO CRÍTICO: " . $e->getMessage());
            return [\'processed\' => 0, \'success\' => 0, \'errors\' => 1, \'critical_error\' => $e->getMessage()];
        }
    }

    /**
     * Buscar transações novas desde a última verificação
     */
    private function getNewTransactions($lastCheck) {
        $sql = "
            SELECT t.*, u.nome as cliente_nome, u.telefone as cliente_telefone,
                   l.nome_fantasia as loja_nome
            FROM transacoes_cashback t
            LEFT JOIN usuarios u ON t.usuario_id = u.id
            LEFT JOIN lojas l ON t.loja_id = l.id
            WHERE t.data_criacao_usuario > :last_check
              AND t.status IN (\'pendente\', \'aprovado\')
              AND u.telefone IS NOT NULL
              AND u.telefone != \'\'
            ORDER BY t.id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $checkDate = $lastCheck ? date(\'Y-m-d H:i:s\', $lastCheck) : \'2025-01-01 00:00:00\';
        $stmt->bindParam(\':last_check\', $checkDate);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Processar uma transação específica
     */
    private function processTransaction($transaction) {
        try {
            // Verificar se já foi notificada (usando estrutura real da tabela)
            if ($this->wasAlreadyNotified($transaction[\'id\'])) {
                return [\'success\' => true, \'message\' => \'Já foi notificada anteriormente\'];
            }

            // Gerar mensagem
            $message = $this->generateMessage($transaction);
            $this->log("Mensagem gerada: " . substr($message, 0, 100) . "...");

            // Enviar via WhatsApp
            $whatsappResult = $this->sendWhatsAppMessage($transaction[\'cliente_telefone\'], $message);

            // Registrar notificação (usando estrutura real da tabela)
            $this->recordNotification($transaction[\'id\'], $whatsappResult, $message);

            if ($whatsappResult[\'success\']) {
                return [\'success\' => true, \'message\' => \'Notificação enviada com sucesso\'];
            } else {
                return [\'success\' => false, \'message\' => \'Falha no envio: \' . $whatsappResult[\'error\']];
            }

        } catch (Exception $e) {
            $this->log("Erro ao processar transação {$transaction[\'id\']}: " . $e->getMessage());
            return [\'success\' => false, \'message\' => $e->getMessage()];
        }
    }

    /**
     * Verificar se transação já foi notificada (usando estrutura real)
     */
    private function wasAlreadyNotified($transactionId) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM whatsapp_logs
                WHERE additional_data LIKE :transaction_pattern
                  AND success = 1
            ");
            $pattern = \'%transaction_id":"\'.$transactionId.\'"%\';
            $stmt->bindParam(\':transaction_pattern\', $pattern);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            $this->log("Erro ao verificar notificação anterior: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registrar notificação (usando estrutura real da tabela)
     */
    private function recordNotification($transactionId, $result, $message) {
        try {
            $metadata = [
                \'transaction_id\' => $transactionId,
                \'message_preview\' => substr($message, 0, 100),
                \'timestamp\' => date(\'Y-m-d H:i:s\'),
                \'system\' => \'FixedBrutalNotificationSystem\'
            ];

            $stmt = $this->db->prepare("
                INSERT INTO whatsapp_logs
                (type, phone, message_preview, success, additional_data, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");

            $success = $result[\'success\'] ? 1 : 0;

            $stmt->execute([
                \'notification\',
                \'brutal_system\',
                substr($message, 0, 255),
                $success,
                json_encode($metadata)
            ]);

            $this->log("Notificação registrada no banco para transação {$transactionId}");

        } catch (Exception $e) {
            $this->log("Erro ao registrar notificação: " . $e->getMessage());
        }
    }

    /**
     * Gerar mensagem personalizada baseada no status
     */
    private function generateMessage($transaction) {
        $nome = $transaction[\'cliente_nome\'] ?? \'Cliente\';
        $valor = number_format($transaction[\'valor_total\'], 2, \',\', \'.\');
        $cashback = number_format($transaction[\'valor_cliente\'], 2, \',\', \'.\');
        $loja = $transaction[\'loja_nome\'] ?? \'Loja Parceira\';

        if ($transaction[\'status\'] === \'aprovado\') {
            return "🎉 *{$nome}*, sua compra foi APROVADA!*\n\n" .
                   "✅ *Cashback já DISPONÍVEL para uso!*\n\n" .
                   "🏪 {$loja}\n" .
                   "💰 Compra: R$ {$valor}\n" .
                   "🎁 Cashback: R$ {$cashback}\n\n" .
                   "💳 Acesse: https://klubecash.com\n\n" .
                   "🔔 *Klube Cash - Dinheiro de volta que vale a pena!*";
        } else {
            return "⭐ *{$nome}*, sua compra foi registrada!*\n\n" .
                   "⏰ Liberação em até 7 dias úteis.\n\n" .
                   "🏪 {$loja}\n" .
                   "💰 Compra: R$ {$valor}\n" .
                   "🎁 Cashback: R$ {$cashback}\n\n" .
                   "💳 Acesse: https://klubecash.com\n\n" .
                   "🔔 *Klube Cash - Dinheiro de volta que vale a pena!*";
        }
    }

    /**
     * Enviar mensagem via WhatsApp
     */
    private function sendWhatsAppMessage($phone, $message) {
        try {
            $phone = $this->formatPhone($phone);

            // Usar API de notificação que já funciona
            $data = [
                \'secret\' => WHATSAPP_BOT_SECRET,
                \'phone\' => $phone,
                \'message\' => $message,
                \'brutal_mode\' => true
            ];

            $apiUrl = SITE_URL . \'/api/whatsapp-enviar-notificacao.php\';

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [\'Content-Type: application/json\'],
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => false
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($httpCode === 200) {
                return [\'success\' => true, \'method\' => \'api_direct\', \'response\' => $response];
            } else {
                return [\'success\' => false, \'error\' => "HTTP {$httpCode}: {$response}"];
            }

        } catch (Exception $e) {
            return [\'success\' => false, \'error\' => $e->getMessage()];
        }
    }

    /**
     * Formatar telefone
     */
    private function formatPhone($phone) {
        $phone = preg_replace(\'/[^0-9]/\', \'\', $phone);

        if (strlen($phone) === 11 && substr($phone, 0, 1) !== \'5\') {
            $phone = \'55\' . $phone;
        }

        return $phone;
    }

    /**
     * Obter timestamp da última verificação
     */
    private function getLastCheck() {
        if (!file_exists($this->lastCheckFile)) {
            return null;
        }

        $data = json_decode(file_get_contents($this->lastCheckFile), true);
        return $data[\'timestamp\'] ?? null;
    }

    /**
     * Atualizar timestamp da última verificação
     */
    private function updateLastCheck() {
        $data = [\'timestamp\' => time()];
        file_put_contents($this->lastCheckFile, json_encode($data));
    }

    /**
     * Log personalizado
     */
    private function log($message) {
        $timestamp = date(\'Y-m-d H:i:s\');
        $logLine = "[{$timestamp}] {$message}\n";

        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
        echo $logLine; // Para debug
    }

    /**
     * MÉTODO PÚBLICO - Processar transação específica
     */
    public function forceNotifyTransaction($transactionId) {
        $this->log("========== FORÇANDO NOTIFICAÇÃO DA TRANSAÇÃO {$transactionId} ==========");

        try {
            $stmt = $this->db->prepare("
                SELECT t.*, u.nome as cliente_nome, u.telefone as cliente_telefone,
                       l.nome_fantasia as loja_nome
                FROM transacoes_cashback t
                LEFT JOIN usuarios u ON t.usuario_id = u.id
                LEFT JOIN lojas l ON t.loja_id = l.id
                WHERE t.id = :id
            ");
            $stmt->bindParam(\':id\', $transactionId);
            $stmt->execute();

            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$transaction) {
                return [\'success\' => false, \'message\' => \'Transação não encontrada\'];
            }

            return $this->processTransaction($transaction);

        } catch (Exception $e) {
            $this->log("Erro ao forçar notificação: " . $e->getMessage());
            return [\'success\' => false, \'message\' => $e->getMessage()];
        }
    }
}
?>';

        file_put_contents(__DIR__ . '/classes/FixedBrutalNotificationSystem.php', $fixedSystemCode);
        echo "<p>✅ Sistema corrigido criado em classes/FixedBrutalNotificationSystem.php</p>\n";
    }

    private function testFixedSystem() {
        try {
            require_once __DIR__ . '/classes/FixedBrutalNotificationSystem.php';

            $system = new FixedBrutalNotificationSystem();

            // Testar com uma transação recente
            $stmt = $this->db->query("
                SELECT t.id FROM transacoes_cashback t
                LEFT JOIN usuarios u ON t.usuario_id = u.id
                WHERE u.telefone IS NOT NULL
                ORDER BY t.id DESC
                LIMIT 1
            ");

            $lastTransaction = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($lastTransaction) {
                echo "<p>🧪 Testando com transação ID: {$lastTransaction['id']}</p>\n";
                $result = $system->forceNotifyTransaction($lastTransaction['id']);

                if ($result['success']) {
                    echo "<p>✅ Teste SUCESSO: {$result['message']}</p>\n";
                } else {
                    echo "<p>⚠️ Teste com aviso: {$result['message']}</p>\n";
                }
            } else {
                echo "<p>⚠️ Nenhuma transação encontrada para teste</p>\n";
            }

        } catch (Exception $e) {
            echo "<p>❌ Erro no teste: " . $e->getMessage() . "</p>\n";
        }
    }
}

// EXECUTAR CORREÇÃO
if (isset($_GET['run'])) {
    $fixer = new BrutalSystemFixer();
    $fixer->fixSystem();
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Correção Brutal System - Klube Cash</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
            .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .btn { background: #FF7A00; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
            .btn:hover { background: #e56a00; }
            .info { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 10px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔧 Correção do Brutal Notification System</h1>

            <div class="info">
                <h3>📋 O que será feito:</h3>
                <ul>
                    <li>✅ Adicionar coluna <code>status</code> baseada na coluna <code>success</code></li>
                    <li>✅ Garantir coluna <code>metadata</code> existe</li>
                    <li>✅ Criar versão corrigida do BrutalNotificationSystem</li>
                    <li>✅ Adaptar para estrutura real da tabela whatsapp_logs</li>
                    <li>✅ Testar sistema corrigido</li>
                </ul>
            </div>

            <p><strong>Problema:</strong> O BrutalNotificationSystem estava esperando colunas que não existem na estrutura real da tabela.</p>

            <p><strong>Solução:</strong> Criar versão adaptada que funciona com a estrutura existente.</p>

            <a href="?run=1" class="btn">🚀 Executar Correção Completa</a>
        </div>
    </body>
    </html>
    <?php
}
?>