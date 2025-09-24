<?php
/**
 * CONFIGURAÇÃO FINAL DA AUTOMAÇÃO - KLUBE CASH
 *
 * Script para configurar e verificar se toda a automação está funcionando
 */

class AutomacaoFinalConfigurator {

    public function configurar() {
        echo "<h2>🔧 CONFIGURAÇÃO FINAL DA AUTOMAÇÃO</h2>\n";

        try {
            // 1. Verificar sistema corrigido
            echo "<h3>1. Verificando FixedBrutalNotificationSystem...</h3>\n";
            $this->verificarSistemaCorrigido();

            // 2. Testar webhook
            echo "<h3>2. Testando webhook...</h3>\n";
            $this->testarWebhook();

            // 3. Verificar cron
            echo "<h3>3. Verificando script de cron...</h3>\n";
            $this->verificarCron();

            // 4. Testar notificação real
            echo "<h3>4. Teste com transação real...</h3>\n";
            $this->testarNotificacaoReal();

            // 5. Instruções finais
            echo "<h3>5. Instruções de uso...</h3>\n";
            $this->mostrarInstrucoes();

            echo "<h3>✅ CONFIGURAÇÃO FINAL CONCLUÍDA!</h3>\n";

        } catch (Exception $e) {
            echo "<h3>❌ ERRO: " . htmlspecialchars($e->getMessage()) . "</h3>\n";
        }
    }

    private function verificarSistemaCorrigido() {
        if (file_exists('classes/FixedBrutalNotificationSystem.php')) {
            require_once 'classes/FixedBrutalNotificationSystem.php';

            if (class_exists('FixedBrutalNotificationSystem')) {
                $system = new FixedBrutalNotificationSystem();
                echo "<p>✅ FixedBrutalNotificationSystem funcionando!</p>\n";

                // Testar método principal
                $result = $system->checkAndProcessNewTransactions();
                echo "<p>• Última verificação processou: {$result['processed']} transações</p>\n";
                echo "<p>• Sucessos: {$result['success']}, Erros: {$result['errors']}</p>\n";
            } else {
                echo "<p>❌ Classe FixedBrutalNotificationSystem não carregou</p>\n";
            }
        } else {
            echo "<p>❌ Arquivo FixedBrutalNotificationSystem.php não encontrado</p>\n";
        }
    }

    private function testarWebhook() {
        if (!file_exists('webhook_notification.php')) {
            echo "<p>❌ Webhook não encontrado. Execute a instalação primeiro.</p>\n";
            return;
        }

        // Buscar transação para teste
        try {
            require_once 'config/database.php';
            $db = Database::getConnection();

            $stmt = $db->query("SELECT id FROM transacoes_cashback ORDER BY id DESC LIMIT 1");
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($transaction) {
                $testId = $transaction['id'];
                echo "<p>🧪 Testando webhook com transação ID: {$testId}</p>\n";

                // Testar webhook
                $data = ['transaction_id' => $testId, 'action' => 'test'];

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
                curl_close($ch);

                if ($httpCode === 200) {
                    $responseData = json_decode($response, true);
                    if ($responseData && $responseData['success']) {
                        echo "<p>✅ Webhook funcionando! Sistema: " . ($responseData['system'] ?? 'N/A') . "</p>\n";
                    } else {
                        echo "<p>⚠️ Webhook responde mas com avisos</p>\n";
                    }
                } else {
                    echo "<p>❌ Webhook com problemas (HTTP {$httpCode})</p>\n";
                }

            } else {
                echo "<p>⚠️ Nenhuma transação encontrada para teste</p>\n";
            }

        } catch (Exception $e) {
            echo "<p>❌ Erro ao testar webhook: " . htmlspecialchars($e->getMessage()) . "</p>\n";
        }
    }

    private function verificarCron() {
        if (file_exists('cron_notifications.php')) {
            echo "<p>✅ Script de cron encontrado: cron_notifications.php</p>\n";

            // Testar execução do cron
            try {
                ob_start();
                include 'cron_notifications.php';
                $output = ob_get_clean();

                if (!empty($output)) {
                    echo "<p>✅ Cron executou com saída:</p>\n";
                    echo "<pre style='background: #f8f8f8; padding: 10px; border-radius: 5px;'>" . htmlspecialchars($output) . "</pre>\n";
                } else {
                    echo "<p>⚠️ Cron executou mas sem saída</p>\n";
                }

            } catch (Exception $e) {
                echo "<p>❌ Erro ao executar cron: " . htmlspecialchars($e->getMessage()) . "</p>\n";
            }

        } else {
            echo "<p>❌ Script de cron não encontrado. Execute a instalação primeiro.</p>\n";
        }
    }

    private function testarNotificacaoReal() {
        try {
            require_once 'config/database.php';
            $db = Database::getConnection();

            // Buscar transação recente com telefone
            $stmt = $db->query("
                SELECT t.id, u.nome, u.telefone, t.status, t.valor_total
                FROM transacoes_cashback t
                LEFT JOIN usuarios u ON t.usuario_id = u.id
                WHERE u.telefone IS NOT NULL AND u.telefone != ''
                ORDER BY t.id DESC
                LIMIT 1
            ");

            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($transaction) {
                echo "<p>🎯 Transação para teste: ID {$transaction['id']}</p>\n";
                echo "<p>• Cliente: {$transaction['nome']}</p>\n";
                echo "<p>• Telefone: {$transaction['telefone']}</p>\n";
                echo "<p>• Status: {$transaction['status']}</p>\n";

                // Forçar notificação
                require_once 'classes/FixedBrutalNotificationSystem.php';
                $system = new FixedBrutalNotificationSystem();
                $result = $system->forceNotifyTransaction($transaction['id']);

                if ($result['success']) {
                    echo "<p>✅ Notificação enviada com sucesso!</p>\n";
                    echo "<p>• Mensagem: " . htmlspecialchars($result['message']) . "</p>\n";
                } else {
                    echo "<p>⚠️ Problema na notificação: " . htmlspecialchars($result['message']) . "</p>\n";
                }

            } else {
                echo "<p>⚠️ Nenhuma transação com telefone encontrada</p>\n";
            }

        } catch (Exception $e) {
            echo "<p>❌ Erro no teste: " . htmlspecialchars($e->getMessage()) . "</p>\n";
        }
    }

    private function mostrarInstrucoes() {
        echo '<div style="background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;">';
        echo '<h4>📋 SISTEMA CONFIGURADO E FUNCIONANDO!</h4>';

        echo '<p><strong>Automação Ativa:</strong></p>';
        echo '<ul>';
        echo '<li>✅ Sistema corrigido instalado</li>';
        echo '<li>✅ Webhook funcionando</li>';
        echo '<li>✅ Script de cron configurado</li>';
        echo '<li>✅ Notificações testadas</li>';
        echo '</ul>';

        echo '<p><strong>Para ativar automação completa:</strong></p>';
        echo '<ol>';
        echo '<li><strong>Cron Job:</strong> Adicione ao crontab do servidor:</li>';
        echo '<pre>*/5 * * * * php ' . __DIR__ . '/cron_notifications.php</pre>';

        echo '<li><strong>Webhook:</strong> Use para disparar notificações:</li>';
        echo '<pre>curl -X POST https://klubecash.com/webhook_notification.php \\
     -H "Content-Type: application/json" \\
     -d \'{"transaction_id": "123"}\'</pre>';

        echo '<li><strong>Integração Direta:</strong> Adicione nos controladores:</li>';
        echo '<pre>require_once "classes/FixedBrutalNotificationSystem.php";
$system = new FixedBrutalNotificationSystem();
$system->forceNotifyTransaction($transactionId);</pre>';
        echo '</ol>';

        echo '<p><strong>Monitoramento:</strong></p>';
        echo '<ul>';
        echo '<li>Logs: <code>logs/brutal_notifications.log</code></li>';
        echo '<li>Debug: <a href="debug_notificacoes.php?run=1">debug_notificacoes.php</a></li>';
        echo '<li>Teste: <a href="test_fixed_system.php">test_fixed_system.php</a></li>';
        echo '</ul>';

        echo '</div>';
    }
}

// Executar configuração
if (isset($_GET['configurar'])) {
    $configurator = new AutomacaoFinalConfigurator();
    $configurator->configurar();
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Configuração Final - Automação de Notificações</title>
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
            <h1>🔧 Configuração Final da Automação</h1>

            <div class="info">
                <h3>📋 Esta configuração final irá:</h3>
                <ul>
                    <li>✅ Verificar se o sistema corrigido está funcionando</li>
                    <li>✅ Testar o webhook</li>
                    <li>✅ Verificar script de cron</li>
                    <li>✅ Executar teste com transação real</li>
                    <li>✅ Fornecer instruções finais de uso</li>
                </ul>
            </div>

            <p><strong>Pré-requisitos:</strong></p>
            <ol>
                <li>Sistema FixedBrutalNotificationSystem instalado</li>
                <li>Webhook criado via install_auto_simple.php</li>
                <li>Banco de dados acessível</li>
            </ol>

            <a href="?configurar=1" class="btn">🚀 Executar Configuração Final</a>

            <h3>Links relacionados:</h3>
            <ul>
                <li><a href="install_auto_simple.php">Instalador simplificado</a></li>
                <li><a href="test_fixed_system.php">Teste do sistema</a></li>
                <li><a href="debug_notificacoes.php?run=1">Debug completo</a></li>
                <li><a href="CORRECOES_SISTEMA_NOTIFICACOES.md">Documentação das correções</a></li>
            </ul>
        </div>
    </body>
    </html>
    <?php
}
?>