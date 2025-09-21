<?php
/**
 * SCRIPT PARA CORRIGIR O WEBHOOK PROBLEMÁTICO
 *
 * Substitui o webhook que está dando erro 500 pela versão corrigida
 */

echo "<h2>🔧 CORRIGINDO WEBHOOK COM ERRO 500</h2>\n";

try {
    // 1. Verificar se o webhook com problema existe
    echo "<h3>1. Verificando webhook atual...</h3>\n";

    if (file_exists('webhook_notification.php')) {
        echo "<p>❌ Webhook problemático encontrado</p>\n";

        // Fazer backup
        $backupName = 'webhook_notification_backup_' . date('Y-m-d_H-i-s') . '.php';
        copy('webhook_notification.php', $backupName);
        echo "<p>📁 Backup criado: {$backupName}</p>\n";

    } else {
        echo "<p>⚠️ Webhook original não encontrado</p>\n";
    }

    // 2. Copiar versão corrigida
    echo "<h3>2. Instalando webhook corrigido...</h3>\n";
    

    if (file_exists('webhook_notification_fixed.php')) {
        $content = file_get_contents('webhook_notification_fixed.php');

        if (file_put_contents('webhook_notification.php', $content)) {
            echo "<p>✅ Webhook corrigido instalado com sucesso!</p>\n";
        } else {
            echo "<p>❌ Erro ao escrever webhook corrigido</p>\n";
        }

    } else {
        echo "<p>❌ Arquivo webhook_notification_fixed.php não encontrado</p>\n";
    }

    // 3. Testar novo webhook
    echo "<h3>3. Testando webhook corrigido...</h3>\n";

    // Buscar transação para teste
    $testId = '999'; // ID padrão

    try {
        if (file_exists('config/database.php')) {
            require_once 'config/database.php';
            $db = Database::getConnection();

            $stmt = $db->query("SELECT id FROM transacoes_cashback ORDER BY id DESC LIMIT 1");
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($transaction) {
                $testId = $transaction['id'];
                echo "<p>🎯 Usando transação real: ID {$testId}</p>\n";
            }
        }
    } catch (Exception $e) {
        echo "<p>⚠️ Usando ID de teste: {$e->getMessage()}</p>\n";
    }

    // Testar webhook
    $data = [
        'transaction_id' => $testId,
        'action' => 'test_correction'
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
        } else {
            echo "<p>⚠️ Webhook responde mas pode ter avisos</p>\n";
        }
    } else {
        echo "<p>❌ Webhook ainda com problemas (HTTP {$httpCode})</p>\n";
    }

    // 4. Verificar logs de debug
    echo "<h3>4. Verificando logs de debug...</h3>\n";

    if (file_exists('logs/webhook_debug.log')) {
        $logSize = filesize('logs/webhook_debug.log');
        echo "<p>📋 Log de debug: {$logSize} bytes</p>\n";

        if ($logSize > 0) {
            $logContent = file_get_contents('logs/webhook_debug.log');
            $lines = explode("\n", $logContent);
            $lastLines = array_slice($lines, -10); // Últimas 10 linhas

            echo "<p>Últimas entradas do log:</p>\n";
            echo "<pre style='background: #f8f8f8; padding: 10px; border-radius: 5px; max-height: 200px; overflow-y: auto;'>";
            foreach ($lastLines as $line) {
                if (!empty(trim($line))) {
                    echo htmlspecialchars($line) . "\n";
                }
            }
            echo "</pre>\n";
        }
    } else {
        echo "<p>⚠️ Log de debug ainda não criado</p>\n";
    }

    echo "<h3>✅ CORREÇÃO CONCLUÍDA!</h3>\n";

    echo '<div style="background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;">';
    echo '<h4>🎉 Webhook corrigido!</h4>';
    echo '<p>O webhook agora tem tratamento robusto de erros e múltiplos métodos de fallback.</p>';
    echo '<p><strong>URL do webhook:</strong> <code>https://klubecash.com/webhook_notification.php</code></p>';
    echo '<p><strong>Logs de debug:</strong> <code>logs/webhook_debug.log</code></p>';
    echo '</div>';

} catch (Exception $e) {
    echo "<h3>❌ ERRO: " . htmlspecialchars($e->getMessage()) . "</h3>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Correção do Webhook - Klube Cash</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .btn { background: #FF7A00; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
        .btn:hover { background: #e56a00; }
        pre { background: #f8f8f8; padding: 10px; border-radius: 5px; overflow-x: auto; max-height: 300px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h3>Próximos passos:</h3>
        <ul>
            <li><a href="install_auto_simple.php?action=test">🧪 Testar webhook novamente</a></li>
            <li><a href="configurar_automacao_final.php?configurar=1">⚙️ Configuração final</a></li>
            <li><a href="debug_notificacoes.php?run=1">🔍 Debug completo do sistema</a></li>
            <li><a href="test_fixed_system.php">🧪 Teste local do sistema</a></li>
        </ul>
    </div>
</body>
</html>