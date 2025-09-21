<?php
/**
 * DEBUG SISTEMA DE NOTIFICAÇÕES - KLUBE CASH
 *
 * Script para diagnosticar problemas no sistema robusto de notificações
 *
 * FUNCIONALIDADES:
 * - Verifica configurações do sistema
 * - Testa conectividade com APIs
 * - Analisa logs existentes
 * - Verifica estrutura do banco de dados
 * - Testa envio de notificação
 * - Identifica problemas comuns
 */

require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'classes/BrutalNotificationSystem.php';

class NotificationDebugger {

    private $db;
    private $results = [];
    private $errors = [];
    private $warnings = [];

    public function __construct() {
        $this->db = Database::getConnection();
        $this->log("=== INICIANDO DEBUG DO SISTEMA DE NOTIFICAÇÕES ===");
    }

    /**
     * EXECUTAR TODOS OS TESTES DE DEBUG
     */
    public function runAllTests() {
        echo "<pre>";

        $this->checkEnvironment();
        $this->checkDatabase();
        $this->checkConfigurations();
        $this->checkFilesAndDirectories();
        $this->checkConnectivity();
        $this->checkLogs();
        $this->testNotificationSystem();
        $this->checkRecentTransactions();
        $this->generateReport();

        echo "</pre>";
    }

    /**
     * 1. VERIFICAR AMBIENTE
     */
    private function checkEnvironment() {
        $this->log("\n1. VERIFICANDO AMBIENTE");
        $this->log("=======================");

        // PHP Version
        $phpVersion = phpversion();
        $this->log("PHP Version: {$phpVersion}");

        // Extensões necessárias
        $requiredExtensions = ['curl', 'json', 'pdo', 'pdo_mysql'];
        foreach ($requiredExtensions as $ext) {
            if (extension_loaded($ext)) {
                $this->log("✅ Extensão {$ext}: OK");
            } else {
                $this->addError("❌ Extensão {$ext}: FALTANDO");
            }
        }

        // Verificar permissões de escrita
        $writableDirs = [
            dirname(__FILE__) . '/logs',
            dirname(__FILE__) . '/exports'
        ];

        foreach ($writableDirs as $dir) {
            if (!is_dir($dir)) {
                if (mkdir($dir, 0755, true)) {
                    $this->log("📁 Diretório criado: {$dir}");
                } else {
                    $this->addError("❌ Não foi possível criar: {$dir}");
                }
            }

            if (is_writable($dir)) {
                $this->log("✅ Permissão de escrita: {$dir}");
            } else {
                $this->addError("❌ Sem permissão de escrita: {$dir}");
            }
        }
    }

    /**
     * 2. VERIFICAR BANCO DE DADOS
     */
    private function checkDatabase() {
        $this->log("\n2. VERIFICANDO BANCO DE DADOS");
        $this->log("=============================");

        try {
            // Testar conexão
            $this->log("✅ Conexão com banco: OK");

            // Verificar tabelas essenciais
            $requiredTables = [
                'transacoes_cashback',
                'usuarios',
                'lojas',
                'whatsapp_logs',
                'cashback_notification_retries'
            ];

            foreach ($requiredTables as $table) {
                $stmt = $this->db->prepare("SHOW TABLES LIKE :table");
                $stmt->bindParam(':table', $table);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $this->log("✅ Tabela {$table}: OK");

                    // Verificar estrutura das tabelas críticas
                    if ($table === 'transacoes_cashback') {
                        $this->checkTransactionTableStructure();
                    } elseif ($table === 'whatsapp_logs') {
                        $this->checkWhatsAppLogsStructure();
                    }
                } else {
                    $this->addError("❌ Tabela {$table}: NÃO ENCONTRADA");
                }
            }

        } catch (Exception $e) {
            $this->addError("❌ Erro na conexão com banco: " . $e->getMessage());
        }
    }

    /**
     * Verificar estrutura da tabela de transações
     */
    private function checkTransactionTableStructure() {
        try {
            $stmt = $this->db->query("DESCRIBE transacoes_cashback");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $requiredColumns = [
                'id', 'usuario_id', 'loja_id', 'status',
                'valor_total', 'valor_cliente', 'data_criacao_usuario'
            ];

            foreach ($requiredColumns as $col) {
                if (in_array($col, $columns)) {
                    $this->log("  ✅ Coluna {$col}: OK");
                } else {
                    $this->addError("  ❌ Coluna {$col}: FALTANDO");
                }
            }

            // Contar transações
            $stmt = $this->db->query("SELECT COUNT(*) FROM transacoes_cashback");
            $count = $stmt->fetchColumn();
            $this->log("  📊 Total de transações: {$count}");

        } catch (Exception $e) {
            $this->addError("Erro ao verificar estrutura de transacoes_cashback: " . $e->getMessage());
        }
    }

    /**
     * Verificar estrutura da tabela de logs WhatsApp
     */
    private function checkWhatsAppLogsStructure() {
        try {
            $stmt = $this->db->query("DESCRIBE whatsapp_logs");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $this->log("  📋 Colunas encontradas: " . implode(', ', $columns));

            // Contar logs
            $stmt = $this->db->query("SELECT COUNT(*) FROM whatsapp_logs");
            $count = $stmt->fetchColumn();
            $this->log("  📊 Total de logs WhatsApp: {$count}");

            // Verificar logs recentes
            $stmt = $this->db->query("
                SELECT COUNT(*) FROM whatsapp_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $recentCount = $stmt->fetchColumn();
            $this->log("  📊 Logs das últimas 24h: {$recentCount}");

        } catch (Exception $e) {
            $this->addWarning("Aviso ao verificar whatsapp_logs: " . $e->getMessage());
        }
    }

    /**
     * 3. VERIFICAR CONFIGURAÇÕES
     */
    private function checkConfigurations() {
        $this->log("\n3. VERIFICANDO CONFIGURAÇÕES");
        $this->log("============================");

        // Verificar constantes essenciais
        $requiredConstants = [
            'WHATSAPP_BOT_URL',
            'WHATSAPP_BOT_SECRET',
            'WHATSAPP_ENABLED',
            'SITE_URL'
        ];

        foreach ($requiredConstants as $const) {
            if (defined($const)) {
                $value = constant($const);
                if (!empty($value)) {
                    $this->log("✅ {$const}: " . (is_bool($value) ? ($value ? 'true' : 'false') : substr($value, 0, 50) . '...'));
                } else {
                    $this->addWarning("⚠️ {$const}: VAZIO");
                }
            } else {
                $this->addError("❌ {$const}: NÃO DEFINIDA");
            }
        }

        // Verificar se sistema está habilitado
        if (defined('WHATSAPP_ENABLED') && WHATSAPP_ENABLED) {
            $this->log("✅ Sistema WhatsApp: HABILITADO");
        } else {
            $this->addWarning("⚠️ Sistema WhatsApp: DESABILITADO");
        }
    }

    /**
     * 4. VERIFICAR ARQUIVOS E DIRETÓRIOS
     */
    private function checkFilesAndDirectories() {
        $this->log("\n4. VERIFICANDO ARQUIVOS E DIRETÓRIOS");
        $this->log("====================================");

        // Verificar classes essenciais
        $requiredFiles = [
            'classes/BrutalNotificationSystem.php',
            'config/database.php',
            'config/constants.php'
        ];

        foreach ($requiredFiles as $file) {
            $fullPath = dirname(__FILE__) . '/' . $file;
            if (file_exists($fullPath)) {
                $this->log("✅ Arquivo {$file}: OK");
            } else {
                $this->addError("❌ Arquivo {$file}: NÃO ENCONTRADO");
            }
        }

        // Verificar diretório de logs
        $logsDir = dirname(__FILE__) . '/logs';
        if (is_dir($logsDir)) {
            $this->log("✅ Diretório logs: OK");

            // Listar arquivos de log
            $logFiles = glob($logsDir . '/*.log');
            if ($logFiles) {
                $this->log("  📄 Arquivos de log encontrados:");
                foreach ($logFiles as $logFile) {
                    $size = filesize($logFile);
                    $modified = date('Y-m-d H:i:s', filemtime($logFile));
                    $this->log("    - " . basename($logFile) . " ({$size} bytes, modificado: {$modified})");
                }
            } else {
                $this->addWarning("  ⚠️ Nenhum arquivo de log encontrado");
            }
        } else {
            $this->addWarning("⚠️ Diretório logs: NÃO EXISTE");
        }
    }

    /**
     * 5. VERIFICAR CONECTIVIDADE
     */
    private function checkConnectivity() {
        $this->log("\n5. VERIFICANDO CONECTIVIDADE");
        $this->log("============================");

        // Testar conectividade com WhatsApp Bot
        if (defined('WHATSAPP_BOT_URL')) {
            $this->testWhatsAppConnection();
        }

        // Testar conectividade geral
        $this->testInternetConnection();
    }

    /**
     * Testar conexão com WhatsApp Bot
     */
    private function testWhatsAppConnection() {
        try {
            $url = WHATSAPP_BOT_URL . '/status';
            $this->log("🔗 Testando: {$url}");

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_FOLLOWLOCATION => true
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            curl_close($curl);

            if ($error) {
                $this->addError("❌ Erro cURL WhatsApp Bot: {$error}");
            } elseif ($httpCode === 200) {
                $this->log("✅ WhatsApp Bot: CONECTADO (HTTP {$httpCode})");
                $this->log("  📄 Resposta: " . substr($response, 0, 200) . '...');
            } else {
                $this->addWarning("⚠️ WhatsApp Bot: HTTP {$httpCode}");
                $this->log("  📄 Resposta: " . substr($response, 0, 200) . '...');
            }

        } catch (Exception $e) {
            $this->addError("❌ Erro ao testar WhatsApp Bot: " . $e->getMessage());
        }
    }

    /**
     * Testar conectividade geral
     */
    private function testInternetConnection() {
        $testUrls = [
            'https://google.com',
            'https://httpbin.org/status/200'
        ];

        foreach ($testUrls as $url) {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_NOBODY => true,
                CURLOPT_SSL_VERIFYPEER => false
            ]);

            curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            curl_close($curl);

            if (!$error && $httpCode === 200) {
                $this->log("✅ Conectividade externa: OK ({$url})");
                break;
            } else {
                $this->addWarning("⚠️ Problema conectividade: {$url} - HTTP {$httpCode}");
            }
        }
    }

    /**
     * 6. VERIFICAR LOGS
     */
    private function checkLogs() {
        $this->log("\n6. ANALISANDO LOGS");
        $this->log("==================");

        $logFiles = [
            'logs/brutal_notifications.log',
            'logs/whatsapp.log',
            'logs/notifications.log'
        ];

        foreach ($logFiles as $logFile) {
            $fullPath = dirname(__FILE__) . '/' . $logFile;
            if (file_exists($fullPath)) {
                $this->analyzeLogFile($fullPath);
            } else {
                $this->log("📄 Log {$logFile}: NÃO EXISTE");
            }
        }
    }

    /**
     * Analisar arquivo de log específico
     */
    private function analyzeLogFile($filePath) {
        $this->log("📄 Analisando: " . basename($filePath));

        $size = filesize($filePath);
        $this->log("  📊 Tamanho: {$size} bytes");

        if ($size > 0) {
            $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $totalLines = count($lines);
            $this->log("  📊 Total de linhas: {$totalLines}");

            // Analisar últimas 10 linhas
            $lastLines = array_slice($lines, -10);
            $this->log("  📋 Últimas entradas:");
            foreach ($lastLines as $line) {
                $this->log("    " . substr($line, 0, 100) . '...');
            }

            // Buscar por erros
            $errorCount = 0;
            foreach ($lines as $line) {
                if (stripos($line, 'erro') !== false || stripos($line, 'error') !== false) {
                    $errorCount++;
                }
            }

            if ($errorCount > 0) {
                $this->addWarning("  ⚠️ {$errorCount} erros encontrados no log");
            } else {
                $this->log("  ✅ Nenhum erro encontrado no log");
            }
        }
    }

    /**
     * 7. TESTAR SISTEMA DE NOTIFICAÇÃO
     */
    private function testNotificationSystem() {
        $this->log("\n7. TESTANDO SISTEMA DE NOTIFICAÇÃO");
        $this->log("==================================");

        try {
            // Instanciar sistema
            $notificationSystem = new BrutalNotificationSystem();
            $this->log("✅ Sistema instanciado com sucesso");

            // Testar verificação de transações
            $this->log("🔍 Testando verificação de transações...");
            $result = $notificationSystem->checkAndProcessNewTransactions();

            $this->log("📊 Resultado do teste:");
            $this->log("  - Processadas: " . ($result['processed'] ?? 0));
            $this->log("  - Sucessos: " . ($result['success'] ?? 0));
            $this->log("  - Erros: " . ($result['errors'] ?? 0));

            if (isset($result['critical_error'])) {
                $this->addError("❌ Erro crítico: " . $result['critical_error']);
            }

        } catch (Exception $e) {
            $this->addError("❌ Erro ao testar sistema: " . $e->getMessage());
        }
    }

    /**
     * 8. VERIFICAR TRANSAÇÕES RECENTES
     */
    private function checkRecentTransactions() {
        $this->log("\n8. VERIFICANDO TRANSAÇÕES RECENTES");
        $this->log("==================================");

        try {
            // Buscar transações dos últimos 7 dias
            $stmt = $this->db->prepare("
                SELECT t.id, t.status, t.valor_total, t.data_criacao_usuario,
                       u.nome, u.telefone
                FROM transacoes_cashback t
                LEFT JOIN usuarios u ON t.usuario_id = u.id
                WHERE t.data_criacao_usuario >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                ORDER BY t.data_criacao_usuario DESC
                LIMIT 10
            ");
            $stmt->execute();
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($transactions) {
                $this->log("📊 " . count($transactions) . " transações encontradas (últimos 7 dias):");

                foreach ($transactions as $t) {
                    $hasPhone = !empty($t['telefone']) ? '✅' : '❌';
                    $this->log("  ID {$t['id']}: {$t['status']}, R$ {$t['valor_total']}, {$hasPhone} Tel, {$t['data_criacao_usuario']}");
                }

                // Verificar quantas têm telefone
                $withPhone = array_filter($transactions, function($t) {
                    return !empty($t['telefone']);
                });

                $phonePercent = count($withPhone) / count($transactions) * 100;
                $this->log("📊 {$phonePercent}% das transações têm telefone");

            } else {
                $this->addWarning("⚠️ Nenhuma transação encontrada nos últimos 7 dias");
            }

        } catch (Exception $e) {
            $this->addError("❌ Erro ao verificar transações: " . $e->getMessage());
        }
    }

    /**
     * 9. GERAR RELATÓRIO FINAL
     */
    private function generateReport() {
        $this->log("\n9. RELATÓRIO FINAL");
        $this->log("==================");

        $this->log("📊 RESUMO DO DIAGNÓSTICO:");
        $this->log("  ✅ Sucessos: " . count($this->results));
        $this->log("  ⚠️ Avisos: " . count($this->warnings));
        $this->log("  ❌ Erros: " . count($this->errors));

        if (!empty($this->errors)) {
            $this->log("\n❌ ERROS ENCONTRADOS:");
            foreach ($this->errors as $error) {
                $this->log("  - {$error}");
            }
        }

        if (!empty($this->warnings)) {
            $this->log("\n⚠️ AVISOS:");
            foreach ($this->warnings as $warning) {
                $this->log("  - {$warning}");
            }
        }

        // Recomendações
        $this->log("\n💡 RECOMENDAÇÕES:");

        if (empty($this->errors) && empty($this->warnings)) {
            $this->log("  🎉 Sistema aparenta estar funcionando corretamente!");
        } else {
            $this->log("  🔧 Corrija os erros listados acima");
            $this->log("  📋 Verifique os logs para mais detalhes");
            $this->log("  🔄 Execute este debug novamente após as correções");
        }

        $this->log("\n=== DEBUG CONCLUÍDO ===");

        // Salvar relatório
        $this->saveReport();
    }

    /**
     * Salvar relatório em arquivo
     */
    private function saveReport() {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "logs/debug_notificacoes_{$timestamp}.txt";

        $content = ob_get_contents();
        file_put_contents($filename, $content);

        $this->log("💾 Relatório salvo em: {$filename}");
    }

    /**
     * MÉTODOS AUXILIARES
     */
    private function log($message) {
        echo $message . "\n";
        $this->results[] = $message;
    }

    private function addError($error) {
        echo $error . "\n";
        $this->errors[] = $error;
    }

    private function addWarning($warning) {
        echo $warning . "\n";
        $this->warnings[] = $warning;
    }
}

// EXECUTAR DEBUG
if (isset($_GET['run']) || php_sapi_name() === 'cli') {
    $debugger = new NotificationDebugger();
    $debugger->runAllTests();
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Debug Sistema de Notificações - Klube Cash</title>
        <style>
            body { font-family: monospace; margin: 20px; background: #f5f5f5; }
            .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .btn { background: #FF7A00; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
            .btn:hover { background: #e56a00; }
            pre { background: #f8f8f8; padding: 15px; border-radius: 5px; overflow-x: auto; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔍 Debug Sistema de Notificações - Klube Cash</h1>
            <p>Este script irá diagnosticar completamente o sistema de notificações, identificando problemas e fornecendo soluções.</p>

            <a href="?run=1" class="btn">🚀 Executar Debug Completo</a>

            <h3>O que será verificado:</h3>
            <ul>
                <li>✅ Ambiente PHP e extensões</li>
                <li>🗃️ Conectividade e estrutura do banco de dados</li>
                <li>⚙️ Configurações do sistema</li>
                <li>📁 Arquivos e diretórios necessários</li>
                <li>🌐 Conectividade com APIs externas</li>
                <li>📋 Análise de logs existentes</li>
                <li>🧪 Teste do sistema de notificação</li>
                <li>📊 Análise de transações recentes</li>
            </ul>
        </div>
    </body>
    </html>
    <?php
}
?>