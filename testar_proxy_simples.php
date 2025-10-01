<?php
/**
 * TESTE SIMPLES - PROXY BOT WHATSAPP
 * Script rápido para testar se o proxy está funcionando
 */

echo "<h2>🧪 TESTE SIMPLES - PROXY BOT WHATSAPP</h2>\n";

// URLs para testar
$testUrls = [
    'https://klubecash.com/whatsapp-bot/status',
    'https://klubecash.com/api/whatsapp-bot/status',
    'http://klubecash.com/whatsapp-bot/status',
    'http://klubecash.com/api/whatsapp-bot/status'
];

echo "<h3>🔍 Testando URLs do proxy:</h3>\n";

$working = false;
$workingUrl = null;

foreach ($testUrls as $url) {
    echo "<p><strong>Testando:</strong> <code>{$url}</code></p>\n";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        if ($data && isset($data['status'])) {
            echo "<p>✅ <strong>PROXY FUNCIONANDO!</strong></p>\n";
            echo "<p>• Status: {$data['status']}</p>\n";
            echo "<p>• Bot Ready: " . ($data['bot_ready'] ? '✅ Sim' : '❌ Não') . "</p>\n";
            echo "<p>• Versão: " . ($data['version'] ?? 'N/A') . "</p>\n";
            echo "<p>• Uptime: " . round($data['uptime'] ?? 0) . " segundos</p>\n";

            $working = true;
            $workingUrl = str_replace('/status', '', $url);
            echo "<p>🎯 <strong>URL base:</strong> <code>{$workingUrl}</code></p>\n";
            break;
        } else {
            echo "<p>❌ Resposta inválida: " . htmlspecialchars(substr($response, 0, 100)) . "</p>\n";
        }
    } else {
        echo "<p>❌ Falha: HTTP {$httpCode}" . ($error ? " - {$error}" : "") . "</p>\n";
    }

    echo "<hr style='margin: 10px 0; border: 1px solid #eee;'>\n";
}

echo "<h3>📊 RESULTADO:</h3>\n";

if ($working) {
    echo '<div style="background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;">';
    echo '<h4>🎉 PROXY CONFIGURADO COM SUCESSO!</h4>';
    echo "<p><strong>URL funcionando:</strong> <code>{$workingUrl}</code></p>";
    echo '<p><strong>Status:</strong> ✅ Bot ativo e respondendo</p>';

    echo '<h4>🚀 Próximos passos:</h4>';
    echo '<ol>';
    echo '<li>Execute: <code>php configurar_proxy_bot.php</code></li>';
    echo '<li>Clique em "Atualizar Sistema Agora"</li>';
    echo '<li>Teste com: <code>php teste_end_to_end.php</code></li>';
    echo '</ol>';

    echo '<p><strong>🎯 O bot agora pode ser usado diretamente!</strong></p>';
    echo '</div>';

} else {
    echo '<div style="background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;">';
    echo '<h4>❌ PROXY NÃO CONFIGURADO</h4>';
    echo '<p>O proxy ainda não está funcionando. Verifique:</p>';
    echo '<ul>';
    echo '<li>Se a configuração foi aplicada no servidor web</li>';
    echo '<li>Se o servidor foi recarregado (nginx/apache)</li>';
    echo '<li>Se o bot PM2 está rodando na porta 3002</li>';
    echo '<li>Logs do servidor: <code>sudo tail -f /var/log/nginx/error.log</code></li>';
    echo '</ul>';

    echo '<h4>📋 Para configurar:</h4>';
    echo '<ol>';
    echo '<li>Leia o arquivo: <code>GUIA_CONEXAO_DIRETA.md</code></li>';
    echo '<li>Configure o proxy no servidor</li>';
    echo '<li>Execute este teste novamente</li>';
    echo '</ol>';

    echo '<p><strong>⚠️ O sistema continuará funcionando com fallback</strong></p>';
    echo '</div>';
}

echo "<h3>🔧 Comandos úteis:</h3>\n";
echo "<pre style='background: #f8f8f8; padding: 10px; border-radius: 5px;'>";
echo "# Testar manualmente:\n";
echo "curl https://klubecash.com/whatsapp-bot/status\n\n";
echo "# Ver logs do nginx:\n";
echo "sudo tail -f /var/log/nginx/error.log\n\n";
echo "# Ver logs do bot:\n";
echo "pm2 logs bot.js\n\n";
echo "# Status do PM2:\n";
echo "pm2 list\n";
echo "</pre>\n";

?>

<!DOCTYPE html>
<html>
<head>
    <title>Teste Proxy Simples - Klube Cash</title>
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
            <li>Se o proxy reverso está configurado</li>
            <li>Se o bot está acessível via HTTPS</li>
            <li>Se o bot está respondendo corretamente</li>
            <li>Qual URL usar para conexão direta</li>
        </ul>

        <p><strong>💡 Dica:</strong> Execute este teste após configurar o proxy no servidor para verificar se tudo está funcionando.</p>
    </div>
</body>
</html>