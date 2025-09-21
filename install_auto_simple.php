<?php
/**
 * INSTALADOR SIMPLES DE NOTIFICAÇÕES AUTOMÁTICAS - KLUBE CASH
 *
 * Versão simplificada sem dependências complexas
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

class SimpleAutoNotificationInstaller {

    public function install() {
        echo "<h2>🔧 INSTALANDO NOTIFICAÇÕES AUTOMÁTICAS (VERSÃO SIMPLES)</h2>\n";

        try {
            // 1. Verificar arquivos
            echo "<h3>1. Verificando arquivos...</h3>\n";
            $this->checkFiles();

            // 2. Criar webhook simples
            echo "<h3>2. Criando webhook...</h3>\n";
            $this->createSimpleWebhook();

            // 3. Criar cron job helper
            echo "<h3>3. Criando executável para cron...</h3>\n";
            $this->createCronScript();

            // 4. Instruções finais
            echo "<h3>4. Instruções finais...</h3>\n";
            $this->showInstructions();

            echo "<h3>✅ INSTALAÇÃO CONCLUÍDA!</h3>\n";
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>🎉 Sistema instalado com sucesso!</h4>";
            echo "<p>O sistema automático de notificações foi configurado e está pronto para uso.</p>";
            echo "</div>";

        } catch (Exception $e) {
            echo "<h3>❌ ERRO: " . htmlspecialchars($e->getMessage()) . "</h3>\n";
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>Detalhes do erro:</h4>";
            echo "<pre style='font-size: 12px;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            echo "</div>";
        }
    }

    private function checkFiles() {
        $files = [
            'classes/FixedBrutalNotificationSystem.php',
            'utils/AutoNotificationTrigger.php',
            'run_single_notification.php'
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                echo "<p>✅ {$file}: OK</p>\n";
            } else {
                echo "<p>❌ {$file}: NÃO ENCONTRADO</p>\n";
            }
        }
    }

    private function createSimpleWebhook() {
        $webhookContent = '<?php
/**
 * WEBHOOK SIMPLES DE NOTIFICAÇÕES
 */

// Headers
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Verificar método
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit(0);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Método não permitido"]);
    exit;
}

try {
    // Obter dados
    $input = json_decode(file_get_contents("php://input"), true);

    if (!$input) {
        $input = $_POST;
    }

    if (!isset($input["transaction_id"])) {
        http_response_code(400);
        echo json_encode(["error" => "transaction_id obrigatório"]);
        exit;
    }

    $transactionId = $input["transaction_id"];

    // Log da tentativa
    error_log("Webhook recebido para transação: " . $transactionId);

    // Executar notificação usando sistema corrigido
    if (file_exists("classes/FixedBrutalNotificationSystem.php")) {
        require_once "classes/FixedBrutalNotificationSystem.php";

        $system = new FixedBrutalNotificationSystem();
        $result = $system->forceNotifyTransaction($transactionId);

        echo json_encode([
            "success" => $result['success'],
            "message" => $result['message'],
            "transaction_id" => $transactionId,
            "timestamp" => date("Y-m-d H:i:s"),
            "system" => "FixedBrutalNotificationSystem"
        ]);
    } else if (file_exists("utils/AutoNotificationTrigger.php")) {
        require_once "utils/AutoNotificationTrigger.php";
        AutoNotificationTrigger::triggerNotification($transactionId, "webhook");

        echo json_encode([
            "success" => true,
            "message" => "Notificação disparada via AutoTrigger",
            "transaction_id" => $transactionId,
            "timestamp" => date("Y-m-d H:i:s")
        ]);
    } else if (file_exists("run_single_notification.php")) {
        // Fallback: executar script diretamente
        $command = "php run_single_notification.php " . escapeshellarg($transactionId) . " webhook > /dev/null 2>&1 &";
        exec($command);

        echo json_encode([
            "success" => true,
            "message" => "Notificação disparada via fallback",
            "transaction_id" => $transactionId
        ]);
    } else {
        throw new Exception("Sistema de notificação não encontrado");
    }

} catch (Exception $e) {
    error_log("Erro no webhook: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        "error" => "Erro interno",
        "message" => $e->getMessage()
    ]);
}
?>';

        if (file_put_contents('webhook_notification.php', $webhookContent)) {
            echo "<p>✅ Webhook criado: webhook_notification.php</p>\n";
        } else {
            echo "<p>❌ Erro ao criar webhook</p>\n";
        }
    }

    private function createCronScript() {
        $cronContent = '#!/bin/bash
# Script para cron job - Klube Cash Notifications

cd /home/u383946504/domains/klubecash.com/public_html/
php utils/AutoNotificationTrigger.php >> logs/cron_notifications.log 2>&1
';

        if (file_put_contents('cron_notifications.sh', $cronContent)) {
            chmod('cron_notifications.sh', 0755);
            echo "<p>✅ Script de cron criado: cron_notifications.sh</p>\n";
        } else {
            echo "<p>❌ Erro ao criar script de cron</p>\n";
        }

        // Criar PHP para cron também
        $phpCronContent = '<?php
/**
 * Script PHP para execução via cron
 */

// Mudar para diretório correto
chdir(__DIR__);

// Executar verificação automática
if (file_exists("utils/AutoNotificationTrigger.php")) {
    require_once "utils/AutoNotificationTrigger.php";

    $result = AutoNotificationTrigger::checkAllPendingNotifications();

    echo "[" . date("Y-m-d H:i:s") . "] Cron executado: " . json_encode($result) . "\n";
} else {
    echo "[" . date("Y-m-d H:i:s") . "] Erro: Arquivo de trigger não encontrado\n";
}
?>';

        if (file_put_contents('cron_notifications.php', $phpCronContent)) {
            echo "<p>✅ Script PHP de cron criado: cron_notifications.php</p>\n";
        } else {
            echo "<p>❌ Erro ao criar script PHP de cron</p>\n";
        }
    }

    private function showInstructions() {
        echo '<div style="background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;">';
        echo '<h4>📋 COMO ATIVAR A AUTOMAÇÃO:</h4>';
        echo '<p><strong>Opção 1 - Webhook (Recomendado):</strong></p>';
        echo '<p>Use o webhook criado: <code>https://klubecash.com/webhook_notification.php</code></p>';
        echo '<p>Exemplo de uso:</p>';
        echo '<pre>curl -X POST https://klubecash.com/webhook_notification.php \\
     -H "Content-Type: application/json" \\
     -d \'{"transaction_id": "123"}\'</pre>';

        echo '<p><strong>Opção 2 - Cron Job:</strong></p>';
        echo '<p>Adicione ao crontab do servidor:</p>';
        echo '<pre># Executar a cada 5 minutos
*/5 * * * * php /home/u383946504/domains/klubecash.com/public_html/cron_notifications.php</pre>';

        echo '<p><strong>Opção 3 - Integração Manual:</strong></p>';
        echo '<p>Adicione este código após criar transações:</p>';
        echo '<pre>require_once "utils/AutoNotificationTrigger.php";
AutoNotificationTrigger::onTransactionCreated($transactionId);</pre>';

        echo '<p><strong>Teste:</strong></p>';
        echo '<p>Execute: <a href="test_auto_notifications.php?run=1">test_auto_notifications.php</a></p>';
        echo '</div>';
    }

    public function testWebhook() {
        echo "<h2>🧪 TESTANDO WEBHOOK</h2>\n";

        // Simular chamada do webhook
        $data = [
            'transaction_id' => '999',
            'action' => 'test'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://klubecash.com/webhook_notification.php');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        echo "<p><strong>Resultado do teste:</strong></p>\n";
        echo "<p>• Código HTTP: {$httpCode}</p>\n";

        if ($error) {
            echo "<p>• Erro cURL: {$error}</p>\n";
        }

        echo "<p>• Resposta:</p>\n";
        echo "<pre>" . htmlspecialchars($response) . "</pre>\n";

        if ($httpCode === 200) {
            echo "<p>✅ Webhook funcionando!</p>\n";
        } else {
            echo "<p>❌ Problema no webhook</p>\n";
        }
    }
}

// Executar baseado na ação
$action = $_GET['action'] ?? '';

if ($action === 'install') {
    $installer = new SimpleAutoNotificationInstaller();
    $installer->install();
} elseif ($action === 'test') {
    $installer = new SimpleAutoNotificationInstaller();
    $installer->testWebhook();
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Instalador Simples - Notificações Automáticas</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
            .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .btn { background: #FF7A00; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
            .btn:hover { background: #e56a00; }
            .btn.test { background: #28a745; }
            .btn.test:hover { background: #218838; }
            .info { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 10px 0; }
            pre { background: #f8f8f8; padding: 10px; border-radius: 5px; overflow-x: auto; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔧 Instalador Simples - Notificações Automáticas</h1>

            <div class="info">
                <h3>📋 Esta versão simplificada irá criar:</h3>
                <ul>
                    <li>✅ Webhook para integração HTTP</li>
                    <li>✅ Scripts para cron job</li>
                    <li>✅ Instruções de uso completas</li>
                </ul>
                <p><strong>Vantagem:</strong> Sem modificação de arquivos existentes!</p>
            </div>

            <h3>Ações:</h3>
            <a href="?action=install" class="btn">🚀 Instalar Sistema</a>
            <a href="?action=test" class="btn test">🧪 Testar Webhook</a>

            <h3>Método recomendado:</h3>
            <ol>
                <li>Clique em "Instalar Sistema"</li>
                <li>Configure um cron job no servidor</li>
                <li>Use o webhook para disparar notificações</li>
                <li>Teste com "Testar Webhook"</li>
            </ol>

            <p><strong>Sem riscos:</strong> Esta versão não modifica arquivos existentes!</p>
        </div>
    </body>
    </html>
    <?php
}
?>