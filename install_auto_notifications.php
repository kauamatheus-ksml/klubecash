<?php
/**
 * HABILITAR EXIBIÇÃO DE ERROS PARA DEPURAÇÃO
 * Remova ou comente estas duas linhas quando o sistema estiver em produção.
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

/**
 * INSTALADOR DE NOTIFICAÇÕES AUTOMÁTICAS - KLUBE CASH
 *
 * Script para integrar automaticamente o sistema de notificações
 * nos pontos onde transações são criadas/atualizadas
 */

// Garante que o script não falhe se os arquivos não existirem ANTES de tentar incluí-los.
// A lógica de verificação de erro cuidará de reportar a ausência.
@require_once 'config/database.php';
@require_once 'utils/AutoNotificationTrigger.php';

class AutoNotificationInstaller {

    private $results = [];
    private $errors = [];

    public function install() {
        echo "<h2>🔧 INSTALANDO NOTIFICAÇÕES AUTOMÁTICAS</h2>\n";

        try {
            // 1. Verificar arquivos principais
            echo "<h3>1. Verificando arquivos...</h3>\n";
            $this->checkFiles();

            // Lança uma exceção se arquivos essenciais estiverem faltando
            if (!empty($this->errors)) {
                throw new Exception("Arquivos essenciais não encontrados. A instalação não pode continuar.");
            }

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
            echo "<h3>❌ ERRO NA INSTALAÇÃO: " . $e->getMessage() . "</h3>\n";
            if (!empty($this->errors)) {
                echo "<p>Detalhes:</p><ul>";
                foreach ($this->errors as $error) {
                    echo "<li>" . htmlspecialchars($error) . "</li>";
                }
                echo "</ul>";
            }
        }
    }

    private function checkFiles() {
        $requiredFiles = [
            'config/database.php', // Adicionado para verificação
            'utils/AutoNotificationTrigger.php',
            'classes/FixedBrutalNotificationSystem.php',
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
            'controllers/AdminController.php'       => 'AdminController',
            'controllers/ClientController.php'      => 'ClientController'
        ];

        foreach ($files as $file => $controller) {
            if (file_exists($file)) {
                $this->addHookToFile($file, $controller);
            } else {
                echo "<p>⚠️ Arquivo a ser modificado não encontrado: {$file}</p>\n";
            }
        }
    }

    private function addHookToFile($file, $controller) {
        $content = file_get_contents($file);

        // Hook para adicionar após lastInsertId
        // ==================================================================
        // CORREÇÃO APLICADA AQUI: A barra invertida (\) foi trocada pela barra normal (/)
        // ==================================================================
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
                    $content = preg_replace($pattern, '$1' . $hookCode, $content, 1); // Adiciona o hook apenas uma vez
                    $modified = true;
                    break;
                }
            }
        }

        if ($modified) {
            if (is_writable($file)) {
                file_put_contents($file, $content);
                echo "<p>✅ Hook instalado em: {$file}</p>\n";
            } else {
                echo "<p>❌ ERRO DE PERMISSÃO: Não foi possível escrever no arquivo {$file}. Verifique as permissões.</p>\n";
                $this->errors[] = "Não foi possível escrever no arquivo: {$file}";
            }
        } else {
            echo "<p>ℹ️ Padrão de código não encontrado ou hook já existe em: {$file}</p>\n";
        }
    }

    private function createWebhook() {
        // Criar webhook simples para integração externa
        $webhookFile = 'webhook_notification.php';
        $webhookContent = '<?php
/**
 * WEBHOOK DE NOTIFICAÇÕES AUTOMÁTICAS
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
    // Garante que o caminho seja relativo ao webhook
    require_once __DIR__ . "/utils/AutoNotificationTrigger.php";

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
    error_log("Webhook Error: " . $e->getMessage()); // Log do erro
    echo json_encode([
        "error" => "Erro interno",
        "message" => $e->getMessage()
    ]);
}
?>';
        
        if (is_writable('.')) {
             file_put_contents($webhookFile, $webhookContent);
             echo "<p>✅ Webhook criado: {$webhookFile}</p>\n";
        } else {
             echo "<p>❌ ERRO DE PERMISSÃO: Não foi possível criar o arquivo {$webhookFile} na pasta raiz. Verifique as permissões.</p>\n";
             $this->errors[] = "Não foi possível criar o arquivo: {$webhookFile}";
        }
    }

    private function testIntegration() {
        try {
            // Testar trigger direto
            if (!class_exists('Database') || !class_exists('AutoNotificationTrigger')) {
                 echo "<p>⚠️ Classes Database ou AutoNotificationTrigger não encontradas. Teste cancelado.</p>\n";
                 return;
            }

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
            echo "<p>❌ Erro no teste de integração: " . $e->getMessage() . "</p>\n";
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
                if (is_writable($file)) {
                    $content = file_get_contents($file);

                    // Remover o bloco do hook
                    $pattern = '/\s*\/\/ AUTO NOTIFICATION TRIGGER - KLUBE CASH.*?\}\s*catch.*?\}\s*/s';
                    $newContent = preg_replace($pattern, '', $content);
                    
                    if ($newContent !== $content) {
                        file_put_contents($file, $newContent);
                        echo "<p>✅ Hook removido de: {$file}</p>\n";
                    } else {
                        echo "<p>ℹ️ Hook não encontrado em: {$file}</p>\n";
                    }
                } else {
                    echo "<p>❌ ERRO DE PERMISSÃO: Não foi possível modificar o arquivo {$file} para remover o hook.</p>\n";
                }
            }
        }

        // Remover webhook
        if (file_exists('webhook_notification.php')) {
            if (is_writable('webhook_notification.php')) {
                unlink('webhook_notification.php');
                echo "<p>✅ Webhook removido: webhook_notification.php</p>\n";
            } else {
                echo "<p>❌ ERRO DE PERMISSÃO: Não foi possível remover o arquivo webhook_notification.php.</p>\n";
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
            body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; color: #333; }
            .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 800px; margin: auto; }
            h1, h2, h3 { color: #FF7A00; }
            h3 { border-bottom: 1px solid #eee; padding-bottom: 5px; margin-top: 25px; }
            .btn { background: #FF7A00; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; border: none; cursor: pointer; font-size: 16px; }
            .btn:hover { background: #e56a00; }
            .btn.danger { background: #dc3545; }
            .btn.danger:hover { background: #c82333; }
            .info { background: #e2f0ff; border-left: 5px solid #0069d9; padding: 15px; margin: 20px 0; }
            .warning { background: #fff3cd; border-left: 5px solid #ffc107; padding: 15px; margin: 20px 0; }
            ul, ol { line-height: 1.6; }
            code { background: #eee; padding: 2px 5px; border-radius: 3px; }
            p, li { color: #555; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔧 Instalador de Notificações Automáticas</h1>

            <div class="info">
                <h3>📋 O que este script faz:</h3>
                <ul>
                    <li>Verifica se os arquivos necessários para o sistema de notificação existem.</li>
                    <li>Modifica os arquivos de controller para adicionar um "gatilho" (hook) que dispara a notificação sempre que uma transação for criada.</li>
                    <li>Cria um arquivo de webhook (`webhook_notification.php`) para permitir integrações externas.</li>
                    <li>Executa um teste para garantir que a integração foi bem-sucedida.</li>
                </ul>
            </div>

            <div class="warning">
                <h3>⚠️ Importante:</h3>
                <p>Este instalador irá <strong>modificar</strong> os seguintes arquivos:</p>
                <ul>
                    <li><code>controllers/TransactionController.php</code></li>
                    <li><code>controllers/AdminController.php</code></li>
                    <li><code>controllers/ClientController.php</code></li>
                </ul>
                <p><strong>É altamente recomendado fazer um backup desses arquivos antes de continuar!</strong></p>
            </div>

            <h3>Ações:</h3>
            <a href="?action=install" class="btn">🚀 Instalar Sistema Automático</a>
            <a href="?action=uninstall" class="btn danger">🗑️ Desinstalar (Remover Hooks e Webhook)</a>

            <h3>Após a instalação:</h3>
            <ol>
                <li>Teste criando uma nova transação no seu sistema.</li>
                <li>Verifique os logs de erro do servidor e o arquivo <code>logs/auto_trigger.log</code> (se configurado) para confirmar o envio.</li>
                <li>O webhook estará disponível em: <code>webhook_notification.php</code> para ser usado por outros sistemas.</li>
            </ol>
        </div>
    </body>
    </html>
    <?php
}
?>