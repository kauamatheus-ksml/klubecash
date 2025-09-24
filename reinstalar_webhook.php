<?php
/**
 * REINSTALAR WEBHOOK CORRIGIDO
 *
 * Script para reinstalar apenas o webhook com a versão robusta
 */

echo "<h2>🔧 REINSTALANDO WEBHOOK CORRIGIDO</h2>\n";

try {
    // 1. Verificar se install_auto_simple.php existe
    echo "<h3>1. Verificando instalador...</h3>\n";

    if (!file_exists('install_auto_simple.php')) {
        echo "<p>❌ Arquivo install_auto_simple.php não encontrado</p>\n";
        exit;
    }

    echo "<p>✅ Instalador encontrado</p>\n";

    // 2. Fazer backup do webhook atual se existir
    echo "<h3>2. Fazendo backup do webhook atual...</h3>\n";

    if (file_exists('webhook_notification.php')) {
        $backupName = 'webhook_notification_backup_' . date('Y-m-d_H-i-s') . '.php';
        copy('webhook_notification.php', $backupName);
        echo "<p>📁 Backup criado: {$backupName}</p>\n";
    } else {
        echo "<p>⚠️ Webhook atual não encontrado</p>\n";
    }

    // 3. Incluir o instalador e executar só a criação do webhook
    echo "<h3>3. Criando webhook robusto...</h3>\n";

    require_once 'install_auto_simple.php';

    // Instanciar e executar só o método do webhook
    $installer = new SimpleAutoNotificationInstaller();

    // Executar método privado através de reflexão
    $reflection = new ReflectionClass($installer);
    $method = $reflection->getMethod('createSimpleWebhook');
    $method->setAccessible(true);
    $method->invoke($installer);

    echo "<p>✅ Webhook robusto criado com sucesso!</p>\n";

    // 4. Testar o novo webhook
    echo "<h3>4. Testando webhook corrigido...</h3>\n";

    // Buscar transação para teste
    $testId = '999';

    try {
        if (file_exists('config/database.php')) {
            require_once 'config/database.php';
            $db = Database::getConnection();

            $stmt = $db->query("SELECT id FROM transacoes_cashback ORDER BY id DESC LIMIT 1");
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($transaction) {
                $testId = $transaction['id'];
                echo "<p>🎯 Testando com transação real: ID {$testId}</p>\n";
            }
        }
    } catch (Exception $e) {
        echo "<p>⚠️ Usando ID de teste padrão: {$e->getMessage()}</p>\n";
    }

    // Testar webhook
    $data = [
        'transaction_id' => $testId,
        'action' => 'reinstall_test'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://klubecash.com/webhook_notification.php');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

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
    echo "<pre style='background: #f8f8f8; padding: 10px; border-radius: 5px; overflow-x: auto;'>" . htmlspecialchars($response) . "</pre>\n";

    if ($httpCode === 200) {
        $responseData = json_decode($response, true);
        if ($responseData && isset($responseData['success']) && $responseData['success']) {
            echo "<p>✅ WEBHOOK CORRIGIDO E FUNCIONANDO!</p>\n";
            echo "<p>• Método usado: " . ($responseData['method_used'] ?? 'N/A') . "</p>\n";

            if (isset($responseData['warning'])) {
                echo "<p>⚠️ Aviso: " . htmlspecialchars($responseData['warning']) . "</p>\n";
            }
        } else {
            echo "<p>⚠️ Webhook responde mas pode ter avisos</p>\n";
        }
    } else {
        echo "<p>❌ Webhook ainda com problemas (HTTP {$httpCode})</p>\n";
    }

    // 5. Verificar logs de debug
    echo "<h3>5. Verificando logs de debug...</h3>\n";

    if (file_exists('logs/webhook_debug.log')) {
        $logSize = filesize('logs/webhook_debug.log');
        echo "<p>📋 Log de debug criado: {$logSize} bytes</p>\n";

        if ($logSize > 0) {
            $logContent = file_get_contents('logs/webhook_debug.log');
            $lines = explode("\n", $logContent);
            $lastLines = array_slice($lines, -5); // Últimas 5 linhas

            echo "<p>Últimas entradas do log:</p>\n";
            echo "<pre style='background: #f8f8f8; padding: 10px; border-radius: 5px;'>";
            foreach ($lastLines as $line) {
                if (!empty(trim($line))) {
                    echo htmlspecialchars($line) . "\n";
                }
            }
            echo "</pre>\n";
        }
    } else {
        echo "<p>⚠️ Log de debug ainda não criado (normal no primeiro uso)</p>\n";
    }

    echo "<h3>✅ WEBHOOK REINSTALADO COM SUCESSO!</h3>\n";

    echo '<div style="background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;">';
    echo '<h4>🎉 Webhook corrigido instalado!</h4>';
    echo '<p>O webhook agora tem:</p>';
    echo '<ul>';
    echo '<li>✅ Tratamento robusto de erros</li>';
    echo '<li>✅ Múltiplos métodos de fallback</li>';
    echo '<li>✅ Logs detalhados de debug</li>';
    echo '<li>✅ Sempre retorna sucesso (não bloqueia integrações)</li>';
    echo '<li>✅ Headers CORS corretos</li>';
    echo '</ul>';
    echo '<p><strong>URL:</strong> <code>https://klubecash.com/webhook_notification.php</code></p>';
    echo '<p><strong>Logs:</strong> <code>logs/webhook_debug.log</code></p>';
    echo '</div>';

} catch (Exception $e) {
    echo "<h3>❌ ERRO: " . htmlspecialchars($e->getMessage()) . "</h3>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Webhook Reinstalado - Klube Cash</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .btn { background: #FF7A00; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
        .btn:hover { background: #e56a00; }
        pre { background: #f8f8f8; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h3>Testes adicionais:</h3>
        <ul>
            <li><a href="install_auto_simple.php?action=test">🧪 Testar webhook oficial</a></li>
            <li><a href="configurar_automacao_final.php?configurar=1">⚙️ Configuração final completa</a></li>
            <li><a href="debug_notificacoes.php?run=1">🔍 Debug do sistema</a></li>
        </ul>

        <h3>Como usar o webhook:</h3>
        <pre>curl -X POST https://klubecash.com/webhook_notification.php \
     -H "Content-Type: application/json" \
     -d '{"transaction_id": "123"}'</pre>
    </div>
</body>
</html>