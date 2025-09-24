<?php
/**
 * TESTE DE NOTIFICAÇÕES AUTOMÁTICAS - KLUBE CASH
 *
 * Script para testar se o sistema automático está funcionando
 */

require_once 'config/database.php';
require_once 'config/constants.php';

class AutoNotificationTester {

    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function runAllTests() {
        echo "<h2>🧪 TESTANDO NOTIFICAÇÕES AUTOMÁTICAS</h2>\n";

        try {
            // 1. Verificar arquivos necessários
            echo "<h3>1. Verificando arquivos...</h3>\n";
            $this->checkFiles();

            // 2. Testar trigger direto
            echo "<h3>2. Testando trigger direto...</h3>\n";
            $this->testDirectTrigger();

            // 3. Testar webhook
            echo "<h3>3. Testando webhook...</h3>\n";
            $this->testWebhook();

            // 4. Simular criação de transação
            echo "<h3>4. Simulando criação de transação...</h3>\n";
            $this->simulateTransactionCreation();

            // 5. Verificar logs
            echo "<h3>5. Verificando logs...</h3>\n";
            $this->checkLogs();

            echo "<h3>✅ TESTES CONCLUÍDOS!</h3>\n";

        } catch (Exception $e) {
            echo "<h3>❌ ERRO: " . $e->getMessage() . "</h3>\n";
        }
    }

    private function checkFiles() {
        $requiredFiles = [
            'utils/AutoNotificationTrigger.php' => 'Trigger automático',
            'classes/FixedBrutalNotificationSystem.php' => 'Sistema de notificação',
            'run_single_notification.php' => 'Script de background',
            'webhook_notification.php' => 'Webhook'
        ];

        foreach ($requiredFiles as $file => $description) {
            if (file_exists($file)) {
                echo "<p>✅ {$description}: OK</p>\n";
            } else {
                echo "<p>❌ {$description}: NÃO ENCONTRADO ({$file})</p>\n";
            }
        }
    }

    private function testDirectTrigger() {
        try {
            require_once 'utils/AutoNotificationTrigger.php';

            // Buscar uma transação para teste
            $stmt = $this->db->query("
                SELECT t.id FROM transacoes_cashback t
                LEFT JOIN usuarios u ON t.usuario_id = u.id
                WHERE u.telefone IS NOT NULL
                ORDER BY t.id DESC
                LIMIT 1
            ");

            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($transaction) {
                $testId = $transaction['id'];
                echo "<p>🧪 Testando com transação ID: {$testId}</p>\n";

                // Executar trigger
                $result = AutoNotificationTrigger::onTransactionCreated($testId);

                if ($result) {
                    echo "<p>✅ Trigger executado com sucesso!</p>\n";
                } else {
                    echo "<p>⚠️ Trigger executado com avisos</p>\n";
                }

            } else {
                echo "<p>⚠️ Nenhuma transação encontrada para teste</p>\n";
            }

        } catch (Exception $e) {
            echo "<p>❌ Erro no teste do trigger: " . $e->getMessage() . "</p>\n";
        }
    }

    private function testWebhook() {
        try {
            if (!file_exists('webhook_notification.php')) {
                echo "<p>⚠️ Webhook não encontrado</p>\n";
                return;
            }

            // Buscar transação para teste
            $stmt = $this->db->query("SELECT id FROM transacoes_cashback ORDER BY id DESC LIMIT 1");
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($transaction) {
                $testId = $transaction['id'];

                // Dados para o webhook
                $data = [
                    'transaction_id' => $testId,
                    'action' => 'test',
                    'secret' => 'klube-cash-webhook-2024'
                ];

                // Simular chamada do webhook
                $url = 'https://klubecash.com/webhook_notification.php';

                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($data),
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                    CURLOPT_TIMEOUT => 10,
                    CURLOPT_SSL_VERIFYPEER => false
                ]);

                $response = curl_exec($curl);
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);

                echo "<p>🔗 Teste do webhook:</p>\n";
                echo "<p>• Código HTTP: {$httpCode}</p>\n";
                echo "<p>• Resposta: " . substr($response, 0, 200) . "</p>\n";

                if ($httpCode === 200) {
                    echo "<p>✅ Webhook funcionando!</p>\n";
                } else {
                    echo "<p>⚠️ Webhook com problemas</p>\n";
                }

            } else {
                echo "<p>⚠️ Nenhuma transação para testar webhook</p>\n";
            }

        } catch (Exception $e) {
            echo "<p>❌ Erro no teste do webhook: " . $e->getMessage() . "</p>\n";
        }
    }

    private function simulateTransactionCreation() {
        try {
            // Buscar um usuário com telefone
            $stmt = $this->db->query("
                SELECT u.id, u.nome, u.telefone FROM usuarios u
                WHERE u.telefone IS NOT NULL AND u.telefone != ''
                LIMIT 1
            ");

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                echo "<p>⚠️ Nenhum usuário com telefone encontrado</p>\n";
                return;
            }

            // Buscar uma loja
            $stmt = $this->db->query("SELECT id FROM lojas LIMIT 1");
            $store = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$store) {
                echo "<p>⚠️ Nenhuma loja encontrada</p>\n";
                return;
            }

            echo "<p>👤 Usuário teste: {$user['nome']} ({$user['telefone']})</p>\n";
            echo "<p>🏪 Loja ID: {$store['id']}</p>\n";

            // Criar transação de teste
            $valor = 50.00;
            $cashback = $valor * 0.05; // 5%

            $stmt = $this->db->prepare("
                INSERT INTO transacoes_cashback (
                    usuario_id, loja_id, valor_total, valor_cliente,
                    codigo_transacao, descricao, status,
                    data_transacao, data_criacao_usuario
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");

            $codigoTransacao = 'TEST_AUTO_' . time();

            $stmt->execute([
                $user['id'],
                $store['id'],
                $valor,
                $cashback,
                $codigoTransacao,
                'Transação de teste para notificação automática',
                'aprovado'
            ]);

            $transactionId = $this->db->lastInsertId();

            echo "<p>💰 Transação criada: ID {$transactionId}</p>\n";
            echo "<p>• Valor: R$ " . number_format($valor, 2, ',', '.') . "</p>\n";
            echo "<p>• Cashback: R$ " . number_format($cashback, 2, ',', '.') . "</p>\n";

            // Disparar notificação manualmente para simular o hook
            require_once 'utils/AutoNotificationTrigger.php';
            AutoNotificationTrigger::onTransactionCreated($transactionId);

            echo "<p>✅ Notificação disparada para transação {$transactionId}</p>\n";

        } catch (Exception $e) {
            echo "<p>❌ Erro na simulação: " . $e->getMessage() . "</p>\n";
        }
    }

    private function checkLogs() {
        $logFiles = [
            'logs/auto_trigger.log' => 'Log do trigger automático',
            'logs/brutal_notifications.log' => 'Log do sistema de notificação'
        ];

        foreach ($logFiles as $file => $description) {
            if (file_exists($file)) {
                $size = filesize($file);
                $modified = date('Y-m-d H:i:s', filemtime($file));

                echo "<p>📋 {$description}:</p>\n";
                echo "<p>• Arquivo: {$file}</p>\n";
                echo "<p>• Tamanho: {$size} bytes</p>\n";
                echo "<p>• Modificado: {$modified}</p>\n";

                // Mostrar últimas linhas
                if ($size > 0) {
                    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    $lastLines = array_slice($lines, -5);

                    echo "<p>• Últimas entradas:</p>\n";
                    echo "<pre style='background: #f8f8f8; padding: 10px; border-radius: 5px;'>";
                    foreach ($lastLines as $line) {
                        echo htmlspecialchars($line) . "\n";
                    }
                    echo "</pre>\n";
                }

            } else {
                echo "<p>⚠️ {$description}: Arquivo não encontrado ({$file})</p>\n";
            }
        }
    }
}

// Executar testes
if (isset($_GET['run'])) {
    $tester = new AutoNotificationTester();
    $tester->runAllTests();
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Teste de Notificações Automáticas - Klube Cash</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
            .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .btn { background: #FF7A00; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
            .btn:hover { background: #e56a00; }
            .info { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 10px 0; }
            pre { background: #f8f8f8; padding: 10px; border-radius: 5px; overflow-x: auto; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🧪 Teste de Notificações Automáticas</h1>

            <div class="info">
                <h3>📋 O que será testado:</h3>
                <ul>
                    <li>✅ Verificação de arquivos necessários</li>
                    <li>✅ Teste do trigger automático</li>
                    <li>✅ Teste do webhook</li>
                    <li>✅ Simulação de criação de transação</li>
                    <li>✅ Análise de logs gerados</li>
                </ul>
            </div>

            <p><strong>Este teste irá:</strong></p>
            <ol>
                <li>Verificar se todos os componentes estão instalados</li>
                <li>Testar o sistema de trigger automático</li>
                <li>Criar uma transação de teste</li>
                <li>Disparar notificação automaticamente</li>
                <li>Mostrar logs gerados</li>
            </ol>

            <p><strong>Importante:</strong> Uma transação de teste será criada durante o processo.</p>

            <a href="?run=1" class="btn">🚀 Executar Testes Completos</a>

            <h3>Links úteis:</h3>
            <ul>
                <li><a href="install_auto_notifications.php">Instalador de automação</a></li>
                <li><a href="debug_notificacoes.php?run=1">Debug geral do sistema</a></li>
                <li><a href="utils/AutoNotificationTrigger.php">Executar verificação manual</a></li>
            </ul>
        </div>
    </body>
    </html>
    <?php
}
?>