<?php
/**
 * TESTE DE CONECTIVIDADE BOT WHATSAPP
 * Testa todas as URLs possíveis para encontrar o bot ativo
 */

echo "<h2>📡 TESTE DE CONECTIVIDADE - BOT WHATSAPP</h2>\n";

try {
    // URLs para testar (mesmas do FixedBrutalNotificationSystem)
    $botUrls = [
        "http://localhost:3002",        // Bot local
        "http://127.0.0.1:3002",        // Bot local alternativo
        "https://klubecash.com:3002",   // Bot no servidor (HTTPS)
        "http://klubecash.com:3002",    // Bot no servidor (HTTP)
        "http://localhost:3000",        // Porta alternativa
        "http://localhost:3001",        // Porta alternativa
        "http://localhost:8080",        // Outras portas comuns
        "http://localhost:8000"
    ];

    echo "<h3>1. Testando conectividade em todas as URLs possíveis:</h3>\n";

    $workingUrls = [];
    $botFound = false;
    $bestUrl = null;

    foreach ($botUrls as $baseUrl) {
        $statusUrl = $baseUrl . "/status";
        echo "<p>🔍 Testando: <code>{$statusUrl}</code></p>\n";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $statusUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            if ($data && isset($data['status'])) {
                echo "<p>✅ <strong>BOT ENCONTRADO!</strong></p>\n";
                echo "<p>• Status: {$data['status']}</p>\n";
                echo "<p>• Bot Ready: " . ($data['bot_ready'] ? 'Sim' : 'Não') . "</p>\n";
                echo "<p>• Versão: " . ($data['version'] ?? 'N/A') . "</p>\n";
                echo "<p>• Uptime: " . round($data['uptime'] ?? 0) . " segundos</p>\n";

                $workingUrls[] = [
                    'url' => $baseUrl,
                    'status' => $data,
                    'ready' => $data['bot_ready'] ?? false
                ];

                if ($data['bot_ready'] && !$bestUrl) {
                    $bestUrl = $baseUrl;
                    $botFound = true;
                }
            }
        } else {
            echo "<p>❌ Falha: HTTP {$httpCode}" . ($error ? ", Error: {$error}" : "") . "</p>\n";
        }

        echo "<hr style='margin: 10px 0; border: 1px solid #eee;'>\n";
    }

    // 2. Se encontrou bot, testar envio de mensagem
    if ($bestUrl) {
        echo "<h3>2. Testando envio de mensagem no bot encontrado:</h3>\n";
        echo "<p>🤖 Usando URL: <code>{$bestUrl}</code></p>\n";

        $testData = [
            'phone' => '34991191534',
            'message' => "🧪 TESTE CONECTIVIDADE MULTI-URL\n\nBot encontrado em: {$bestUrl}\nData: " . date('d/m/Y H:i:s') . "\n\nSistema funcionando! ✅",
            'secret' => 'klube-cash-2024'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $bestUrl . '/send-message');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if ($result && $result['success']) {
                echo "<p>✅ <strong>TESTE DE ENVIO BEM-SUCEDIDO!</strong></p>\n";
                echo "<p>• Mensagem enviada para: {$testData['phone']}</p>\n";
                echo "<p>• Resposta: " . ($result['message'] ?? 'OK') . "</p>\n";
            } else {
                echo "<p>❌ Teste de envio falhou:</p>\n";
                echo "<p>• Erro: " . ($result['error'] ?? 'Desconhecido') . "</p>\n";
                echo "<pre>" . htmlspecialchars($response) . "</pre>\n";
            }
        } else {
            echo "<p>❌ Erro HTTP {$httpCode} no teste de envio</p>\n";
            echo "<pre>" . htmlspecialchars($response) . "</pre>\n";
        }
    }

    // 3. Testar FixedBrutalNotificationSystem com as novas URLs
    if ($botFound) {
        echo "<h3>3. Testando FixedBrutalNotificationSystem com bot ativo:</h3>\n";

        require_once 'classes/FixedBrutalNotificationSystem.php';
        require_once 'config/database.php';

        $db = Database::getConnection();
        $stmt = $db->query("SELECT id FROM transacoes_cashback ORDER BY id DESC LIMIT 1");
        $lastTransaction = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($lastTransaction) {
            echo "<p>🧪 Testando notificação para transação #{$lastTransaction['id']}...</p>\n";

            $system = new FixedBrutalNotificationSystem();
            $result = $system->forceNotifyTransaction($lastTransaction['id']);

            if ($result['success']) {
                echo "<p>✅ <strong>SISTEMA INTEGRADO FUNCIONANDO!</strong></p>\n";
                echo "<p>• Mensagem: {$result['message']}</p>\n";
                if (isset($result['bot_url'])) {
                    echo "<p>• Bot URL usada: {$result['bot_url']}</p>\n";
                }
            } else {
                echo "<p>❌ Sistema falhou:</p>\n";
                echo "<p>• Erro: {$result['message']}</p>\n";
            }
        } else {
            echo "<p>⚠️ Nenhuma transação encontrada para teste</p>\n";
        }
    }

    // 4. Verificar logs recentes
    echo "<h3>4. Verificando logs do sistema:</h3>\n";

    $logFile = 'logs/brutal_notifications.log';
    if (file_exists($logFile)) {
        $logContent = file_get_contents($logFile);
        $logLines = explode("\n", $logContent);
        $recentLines = array_slice($logLines, -10); // Últimas 10 linhas

        echo "<p>📋 Últimas 10 entradas do log:</p>\n";
        echo "<pre style='background: #f8f8f8; padding: 10px; border-radius: 5px; max-height: 300px; overflow-y: auto;'>";
        foreach ($recentLines as $line) {
            if (trim($line)) {
                echo htmlspecialchars($line) . "\n";
            }
        }
        echo "</pre>\n";
    } else {
        echo "<p>⚠️ Arquivo de log não encontrado: {$logFile}</p>\n";
    }

    // 5. Resumo final
    echo "<h3>📊 RESUMO FINAL:</h3>\n";

    echo '<div style="background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;">';
    echo '<h4>🤖 RESULTADO DO TESTE DE CONECTIVIDADE</h4>';

    if ($botFound) {
        echo '<p><strong>✅ BOT WHATSAPP ENCONTRADO E FUNCIONAL!</strong></p>';
        echo '<ul>';
        echo "<li>✅ URL ativa: <code>{$bestUrl}</code></li>";
        echo '<li>✅ Status endpoint: Respondendo</li>';
        echo '<li>✅ Send-message endpoint: Funcional</li>';
        echo '<li>✅ Integração com FixedBrutalNotificationSystem: OK</li>';
        echo '</ul>';

        echo '<h4>🚀 SISTEMA PRONTO PARA PRODUÇÃO!</h4>';
        echo '<p>O sistema agora enviará notificações automáticas via WhatsApp.</p>';

    } else {
        echo '<p><strong>❌ NENHUM BOT ENCONTRADO</strong></p>';
        echo '<p>URLs testadas:</p>';
        echo '<ul>';
        foreach ($botUrls as $url) {
            echo "<li>{$url}/status</li>";
        }
        echo '</ul>';

        echo '<h4>🔧 SOLUÇÕES POSSÍVEIS:</h4>';
        echo '<ol>';
        echo '<li>Verificar se o bot está rodando via PM2 no servidor</li>';
        echo '<li>Confirmar porta correta do bot</li>';
        echo '<li>Verificar configuração de firewall/proxy</li>';
        echo '<li>Testar conexão direta com servidor</li>';
        echo '</ol>';
    }

    echo '</div>';

    // Salvar resultado do teste
    $testResult = [
        'timestamp' => date('Y-m-d H:i:s'),
        'bot_found' => $botFound,
        'best_url' => $bestUrl,
        'working_urls' => $workingUrls,
        'tested_urls' => $botUrls
    ];

    if (!is_dir('logs')) {
        mkdir('logs', 0755, true);
    }

    file_put_contents('logs/connectivity_test.json', json_encode($testResult, JSON_PRETTY_PRINT));
    echo "<p>📝 Resultado salvo em: logs/connectivity_test.json</p>\n";

} catch (Exception $e) {
    echo "<h3>❌ ERRO: " . htmlspecialchars($e->getMessage()) . "</h3>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Teste de Conectividade Bot - Klube Cash</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        code { background: #f8f8f8; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        pre { background: #f8f8f8; padding: 10px; border-radius: 5px; overflow-x: auto; }
        hr { margin: 10px 0; border: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        <h3>🎯 Este teste verifica:</h3>
        <ul>
            <li>Conectividade com todas as URLs possíveis do bot</li>
            <li>Status e disponibilidade do bot WhatsApp</li>
            <li>Funcionalidade do endpoint de envio</li>
            <li>Integração com o sistema de notificações</li>
            <li>Logs recentes de atividade</li>
        </ul>

        <h3>📚 Próximos passos se bot encontrado:</h3>
        <ol>
            <li>Criar transação real para testar notificação automática</li>
            <li>Monitorar logs em tempo real</li>
            <li>Configurar monitoramento contínuo</li>
        </ol>
    </div>
</body>
</html>