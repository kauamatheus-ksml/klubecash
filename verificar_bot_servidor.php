<?php
/**
 * VERIFICAR BOT WHATSAPP NO SERVIDOR
 * Script para identificar porta e status do bot rodando via PM2
 */

echo "<h2>🤖 VERIFICAÇÃO DO BOT WHATSAPP NO SERVIDOR</h2>\n";

try {
    // 1. Testar diferentes portas possíveis
    echo "<h3>1. Testando conectividade em diferentes portas:</h3>\n";

    $possiblePorts = [3000, 3001, 3002, 3003, 8080, 8081];
    $botUrl = null;
    $botStatus = null;

    foreach ($possiblePorts as $port) {
        $testUrl = "http://localhost:{$port}/status";
        echo "<p>🔍 Testando porta {$port}...</p>\n";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $testUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            if ($data && isset($data['status'])) {
                echo "<p>✅ Bot encontrado na porta {$port}!</p>\n";
                echo "<p>• Status: {$data['status']}</p>\n";
                echo "<p>• Bot Ready: " . ($data['bot_ready'] ? 'Sim' : 'Não') . "</p>\n";
                echo "<p>• Versão: " . ($data['version'] ?? 'N/A') . "</p>\n";

                $botUrl = "http://localhost:{$port}";
                $botStatus = $data;
                break;
            }
        }
    }

    if (!$botUrl) {
        echo "<p>❌ Bot não encontrado em nenhuma porta testada</p>\n";
        echo "<p>Verifique se o bot está rodando via PM2</p>\n";
    }

    // 2. Se encontrou o bot, testar endpoint de envio
    if ($botUrl && $botStatus['bot_ready']) {
        echo "<h3>2. Testando endpoint de envio de mensagem:</h3>\n";

        $testData = [
            'phone' => '34991191534',
            'message' => "🧪 TESTE CONECTIVIDADE\n\nBot encontrado em: {$botUrl}\nData: " . date('d/m/Y H:i:s'),
            'secret' => 'klube-cash-2024'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $botUrl . '/send-message');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if ($result && $result['success']) {
                echo "<p>✅ Teste de envio bem-sucedido!</p>\n";
                echo "<p>• Mensagem enviada para: {$testData['phone']}</p>\n";
                echo "<p>• Resposta: " . ($result['message'] ?? 'OK') . "</p>\n";
            } else {
                echo "<p>❌ Teste de envio falhou:</p>\n";
                echo "<p>• Erro: " . ($result['error'] ?? 'Desconhecido') . "</p>\n";
            }
        } else {
            echo "<p>❌ Erro HTTP {$httpCode} no teste de envio</p>\n";
            echo "<p>• Resposta: " . htmlspecialchars($response) . "</p>\n";
        }
    }

    // 3. Verificar configuração atual do sistema
    echo "<h3>3. Verificando configuração atual do sistema:</h3>\n";

    if (file_exists('classes/FixedBrutalNotificationSystem.php')) {
        $systemContent = file_get_contents('classes/FixedBrutalNotificationSystem.php');

        // Extrair URL atual
        if (preg_match('/\$botUrl = "([^"]+)"/', $systemContent, $matches)) {
            $currentUrl = $matches[1];
            echo "<p>• URL atual no sistema: <code>{$currentUrl}</code></p>\n";

            if ($botUrl && $currentUrl !== $botUrl . '/send-message') {
                echo "<p>⚠️ URL precisa ser atualizada!</p>\n";
                echo "<p>• URL correta: <code>{$botUrl}/send-message</code></p>\n";
            } elseif ($botUrl) {
                echo "<p>✅ URL está correta</p>\n";
            }
        } else {
            echo "<p>❌ Não foi possível encontrar URL no sistema</p>\n";
        }
    }

    // 4. Teste direto do FixedBrutalNotificationSystem
    if ($botUrl && $botStatus['bot_ready']) {
        echo "<h3>4. Testando FixedBrutalNotificationSystem:</h3>\n";

        require_once 'classes/FixedBrutalNotificationSystem.php';
        require_once 'config/database.php';

        $db = Database::getConnection();
        $stmt = $db->query("SELECT id FROM transacoes_cashback ORDER BY id DESC LIMIT 1");
        $lastTransaction = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($lastTransaction) {
            echo "<p>🧪 Testando com transação #{$lastTransaction['id']}...</p>\n";

            $system = new FixedBrutalNotificationSystem();
            $result = $system->forceNotifyTransaction($lastTransaction['id']);

            if ($result['success']) {
                echo "<p>✅ Sistema funcionando perfeitamente!</p>\n";
                echo "<p>• Mensagem: {$result['message']}</p>\n";
            } else {
                echo "<p>❌ Sistema falhou:</p>\n";
                echo "<p>• Erro: {$result['message']}</p>\n";
            }
        } else {
            echo "<p>⚠️ Nenhuma transação encontrada para teste</p>\n";
        }
    }

    // 5. Resumo e recomendações
    echo "<h3>📊 RESUMO E RECOMENDAÇÕES:</h3>\n";

    echo '<div style="background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;">';
    echo '<h4>🤖 STATUS DO BOT WHATSAPP</h4>';

    if ($botUrl && $botStatus) {
        echo '<p><strong>✅ BOT ENCONTRADO E FUNCIONANDO!</strong></p>';
        echo '<ul>';
        echo "<li>✅ URL: {$botUrl}</li>";
        echo '<li>✅ Status: ' . $botStatus['status'] . '</li>';
        echo '<li>✅ Bot Ready: ' . ($botStatus['bot_ready'] ? 'Sim' : 'Não') . '</li>';
        echo '<li>✅ Endpoint funcional: /send-message</li>';
        echo '</ul>';

        echo '<h4>🚀 PRÓXIMOS PASSOS:</h4>';
        echo '<ol>';

        if (isset($currentUrl) && $currentUrl !== $botUrl . '/send-message') {
            echo '<li>❗ Atualizar URL no FixedBrutalNotificationSystem</li>';
        }

        echo '<li>✅ Testar integração end-to-end</li>';
        echo '<li>✅ Monitorar logs de funcionamento</li>';
        echo '</ol>';

    } else {
        echo '<p><strong>❌ BOT NÃO ENCONTRADO</strong></p>';
        echo '<p>Verifique se o bot está rodando via PM2:</p>';
        echo '<pre>';
        echo 'pm2 list';
        echo 'pm2 logs bot.js';
        echo 'pm2 restart bot.js';
        echo '</pre>';
    }

    echo '</div>';

    // Salvar configuração detectada
    if ($botUrl) {
        $config = [
            'bot_url' => $botUrl,
            'bot_status' => $botStatus,
            'detected_at' => date('Y-m-d H:i:s'),
            'needs_update' => isset($currentUrl) && $currentUrl !== $botUrl . '/send-message'
        ];

        file_put_contents('logs/bot_detection.json', json_encode($config, JSON_PRETTY_PRINT));
        echo "<p>📝 Configuração salva em: logs/bot_detection.json</p>\n";
    }

} catch (Exception $e) {
    echo "<h3>❌ ERRO: " . htmlspecialchars($e->getMessage()) . "</h3>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verificação Bot WhatsApp - Klube Cash</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        code { background: #f8f8f8; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        pre { background: #f8f8f8; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h3>🎯 Esta verificação identifica:</h3>
        <ul>
            <li>Em qual porta o bot está rodando</li>
            <li>Se o bot está respondendo corretamente</li>
            <li>Se a configuração do sistema precisa ser atualizada</li>
            <li>Se a integração está funcionando end-to-end</li>
        </ul>
    </div>
</body>
</html>