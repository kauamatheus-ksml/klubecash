<?php
/**
 * INSTALADOR DE NOTIFICAÇÕES AUTOMÁTICAS - KLUBE CASH
 *
 * Script para integrar automaticamente o sistema de notificações
 * nos pontos onde transações são criadas/atualizadas
 */

require_once 'config/database.php';
require_once 'utils/AutoNotificationTrigger.php';

class AutoNotificationInstaller {

    private $results = [];
    private $errors = [];

    public function install() {
        echo "<h2>🔧 INSTALANDO NOTIFICAÇÕES AUTOMÁTICAS</h2>\n";

        try {
            // 1. Verificar arquivos principais
            echo "<h3>1. Verificando arquivos...</h3>\n";
            $this->checkFiles();

            // 2. Instalar hooks nos controladores
            echo "<h3>2. Instalando hooks...</h3>\n";
            $this->installHooks();

            // 3. Criar webhook de apoio
            echo "<h3>3. Configurando webhook...</h3>\n";
            $this->createWebhook();

            // 4. Testar integração
            echo "<h3>4. Testando integração...</h3>\n";
            $this->testIntegration();

            echo "<h3>✅ INSTALAÇÃO CONCLUÍDA!</h3>\n";
            echo "<p>As notificações agora serão enviadas automaticamente!</p>\n";

        } catch (Exception $e) {
            echo "<h3>❌ ERRO: " . $e->getMessage() . "</h3>\n";
        }
    }

    private function checkFiles() {
        $requiredFiles = [
            'classes/FixedBrutalNotificationSystem.php',
            'utils/AutoNotificationTrigger.php',
            'run_single_notification.php'
        ];

        foreach ($requiredFiles as $file) {
            if (file_exists($file)) {
                echo "<p>✅ {$file}: OK</p>\n";
            } else {
                $this->errors[] = "Arquivo não encontrado: {$file}";
                echo "<p>❌ {$file}: NÃO ENCONTRADO</p>\n";
            }
        }
    }

    private function installHooks() {
        // Arquivos para modificar
        $files = [
            'controllers/TransactionController.php' => 'TransactionController',
            'controllers/AdminController.php' => 'AdminController',
            'controllers/ClientController.php' => 'ClientController'
        ];

        foreach ($files as $file => $controller) {
            if (file_exists($file)) {
                $this->addHookToFile($file, $controller);
            } else {
                echo "<p>⚠️ Arquivo não encontrado: {$file}</p>\n";
            }
        }
    }

    private function addHookToFile($file, $controller) {
        $content = file_get_contents($file);

        // Hook para adicionar após lastInsertId
        $hookCode = '
                // AUTO NOTIFICATION TRIGGER - KLUBE CASH
                try {
                    require_once __DIR__ . \'/../utils/AutoNotificationTrigger.php\';
                    AutoNotificationTrigger::onTransactionCreated($transactionId);
                } catch (Exception $e) {
                    error_log("Erro no trigger de notificação: " . $e->getMessage());
                }';

        // Procurar padrões onde transações são inseridas
        $patterns = [
            '/(\$transactionId\s*=\s*\$\w+->lastInsertId\(\);)/',
            '/(\$transaction_id\s*=\s*\$\w+->lastInsertId\(\);)/',
            '/(\$lastId\s*=\s*\$\w+->lastInsertId\(\);)/'
        ];

        $modified = false;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                // Verificar se o hook já não existe
                if (strpos($content, 'AUTO NOTIFICATION TRIGGER') === false) {
                    $content = preg_replace($pattern, '$1' . $hookCode, $content);
                    $modified = true;
                    break;
                }
            }
        }

        if ($modified) {
            file_put_contents($file, $content);
            echo "<p>✅ Hook instalado em: {$file}</p>\n";
        } else {
            echo "<p>⚠️ Padrão não encontrado ou hook já existe em: {$file}</p>\n";
        }
    }

    private function createWebhook() {
        // Criar webhook simples para integração externa
        $webhookContent = '<?php
/**
 * WEBHOOK DE NOTIFICAÇÕES AUTOMÁTICAS
 *
 * Endpoint para disparar notificações via HTTP
 */

header("Content-Type: application/json");

// Verificar método
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Método não permitido"]);
    exit;
}

// Obter dados
$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input["transaction_id"])) {
    http_response_code(400);
    echo json_encode(["error" => "transaction_id obrigatório"]);
    exit;
}

// Verificar secret (opcional)
$secret = $input["secret"] ?? "";
if (!empty($secret) && $secret !== "klube-cash-webhook-2024") {
    http_response_code(401);
    echo json_encode(["error" => "Secret inválido"]);
    exit;
}

// Executar notificação
try {
    require_once "utils/AutoNotificationTrigger.php";

    $transactionId = $input["transaction_id"];
    $action = $input["action"] ?? "webhook";

    AutoNotificationTrigger::triggerNotification($transactionId, $action);

    echo json_encode([
        "success" => true,
        "message" => "Notificação disparada",
        "transaction_id" => $transactionId
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Erro interno",
        "message" => $e->getMessage()
    ]);
}
?>';

        file_put_contents('webhook_notification.php', $webhookContent);
        echo "<p>✅ Webhook criado: webhook_notification.php</p>\n";
    }

    private function testIntegration() {
        try {
            // Testar trigger direto
            require_once 'utils/AutoNotificationTrigger.php';

            // Buscar uma transação recente para teste
            $db = Database::getConnection();
            $stmt = $db->query("SELECT id FROM transacoes_cashback ORDER BY id DESC LIMIT 1");
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($transaction) {
                $testId = $transaction['id'];
                echo "<p>🧪 Testando com transação ID: {$testId}</p>\n";

                AutoNotificationTrigger::onTransactionCreated($testId);
                echo "<p>✅ Teste executado com sucesso!</p>\n";
                echo "<p>📋 Verifique o log: logs/auto_trigger.log</p>\n";
            } else {
                echo "<p>⚠️ Nenhuma transação encontrada para teste</p>\n";
            }

        } catch (Exception $e) {
            echo "<p>❌ Erro no teste: " . $e->getMessage() . "</p>\n";
        }
    }

    public function uninstall() {
        echo "<h2>🗑️ REMOVENDO NOTIFICAÇÕES AUTOMÁTICAS</h2>\n";

        // Remover hooks dos arquivos
        $files = [
            'controllers/TransactionController.php',
            'controllers/AdminController.php',
            'controllers/ClientController.php'
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);

                // Remover o bloco do hook
                $pattern = '/\n\s*\/\/ AUTO NOTIFICATION TRIGGER - KLUBE CASH.*?}\s*catch.*?}\s*/s';
                $content = preg_replace($pattern, '', $content);

                file_put_contents($file, $content);
                echo "<p>✅ Hook removido de: {$file}</p>\n";
            }
        }

        echo "<p>✅ Desinstalação concluída!</p>\n";
    }
}

// Executar instalação
if (isset($_GET['action'])) {
    $installer = new AutoNotificationInstaller();

    if ($_GET['action'] === 'install') {
        $installer->install();
    } elseif ($_GET['action'] === 'uninstall') {
        $installer->uninstall();
    }
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Instalador de Notificações Automáticas - Klube Cash</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
            .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .btn { background: #FF7A00; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
            .btn:hover { background: #e56a00; }
            .btn.danger { background: #dc3545; }
            .btn.danger:hover { background: #c82333; }
            .info { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 10px 0; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 10px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔧 Instalador de Notificações Automáticas</h1>

            <div class="info">
                <h3>📋 O que será instalado:</h3>
                <ul>
                    <li>✅ Hooks automáticos nos controladores de transação</li>
                    <li>✅ Sistema de execução em background</li>
                    <li>✅ Webhook para integração externa</li>
                    <li>✅ Logs de monitoramento</li>
                </ul>
            </div>

            <div class="warning">
                <h3>⚠️ Importante:</h3>
                <p>Este instalador irá modificar os seguintes arquivos:</p>
                <ul>
                    <li>controllers/TransactionController.php</li>
                    <li>controllers/AdminController.php</li>
                    <li>controllers/ClientController.php</li>
                </ul>
                <p><strong>Faça backup dos arquivos antes de continuar!</strong></p>
            </div>

            <h3>Como funciona:</h3>
            <ol>
                <li>Toda vez que uma transação for criada/atualizada, o sistema dispara automaticamente uma notificação</li>
                <li>A notificação é executada em background para não atrasar a resposta da API</li>
                <li>Logs são gerados para monitoramento</li>
                <li>Sistema compatível com a estrutura atual do banco</li>
            </ol>

            <h3>Ações:</h3>
            <a href="?action=install" class="btn">🚀 Instalar Sistema Automático</a>
            <a href="?action=uninstall" class="btn danger">🗑️ Desinstalar (Remover Hooks)</a>

            <h3>Após a instalação:</h3>
            <p>• Teste criando uma nova transação</p>
            <p>• Verifique os logs em: <code>logs/auto_trigger.log</code></p>
            <p>• Use o webhook: <code>webhook_notification.php</code> para integração externa</p>
        </div>
    </body>
    </html>
    <?php
}
?>