<?php
/**
 * CONFIGURAÇÃO CONEXÃO DIRETA COM BOT WHATSAPP
 * Script para conectar diretamente com o bot PM2 no servidor
 */

echo "<h2>🔗 CONFIGURAÇÃO CONEXÃO DIRETA - BOT WHATSAPP</h2>\n";

try {
    // 1. Informações sobre configuração atual
    echo "<h3>1. Análise da configuração atual:</h3>\n";

    echo "<p>📋 <strong>Situação atual:</strong></p>\n";
    echo "<p>• Bot está rodando via PM2 no servidor</p>\n";
    echo "<p>• Sistema usa fallback webhook_simulation (funcionando)</p>\n";
    echo "<p>• Precisamos conectar diretamente na porta do servidor</p>\n";

    // 2. Opções de conexão direta
    echo "<h3>2. Opções para conexão direta:</h3>\n";

    $connectionOptions = [
        'server_internal' => [
            'title' => 'Conexão interna do servidor',
            'urls' => [
                'http://localhost:3002/send-message',
                'http://127.0.0.1:3002/send-message'
            ],
            'description' => 'Se o PHP roda no mesmo servidor que o bot'
        ],
        'server_external' => [
            'title' => 'Conexão externa via IP/domínio',
            'urls' => [
                'http://IP_DO_SERVIDOR:3002/send-message',
                'https://klubecash.com:3002/send-message',
                'http://klubecash.com:3002/send-message'
            ],
            'description' => 'Se o bot aceita conexões externas'
        ],
        'reverse_proxy' => [
            'title' => 'Via proxy reverso (recomendado)',
            'urls' => [
                'https://klubecash.com/whatsapp-bot/send-message',
                'https://klubecash.com/api/bot/send-message'
            ],
            'description' => 'Bot acessível via proxy do Apache/Nginx'
        ]
    ];

    foreach ($connectionOptions as $key => $option) {
        echo "<h4>{$option['title']}</h4>\n";
        echo "<p>{$option['description']}</p>\n";

        foreach ($option['urls'] as $url) {
            echo "<p>🔍 Testando: <code>{$url}</code></p>\n";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, str_replace('/send-message', '/status', $url));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
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
                    echo "<p>• URL para usar: <code>{$url}</code></p>\n";

                    // Salvar URL funcionando
                    file_put_contents('logs/bot_working_url.txt', $url);
                    echo "<p>📝 URL salva em: logs/bot_working_url.txt</p>\n";

                } else {
                    echo "<p>❌ Resposta inválida: " . htmlspecialchars(substr($response, 0, 100)) . "</p>\n";
                }
            } else {
                echo "<p>❌ Falha: HTTP {$httpCode}" . ($error ? ", Error: {$error}" : "") . "</p>\n";
            }
        }

        echo "<hr style='margin: 15px 0; border: 1px solid #eee;'>\n";
    }

    // 3. Configuração via proxy reverso (mais provável)
    echo "<h3>3. Configuração de Proxy Reverso (Recomendado):</h3>\n";

    echo '<div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;">';
    echo '<h4>🔧 CONFIGURAÇÃO NGINX/APACHE</h4>';
    echo '<p>Para acessar o bot via <code>https://klubecash.com/whatsapp-bot/</code>, adicione:</p>';

    echo '<h5>Para Nginx:</h5>';
    echo '<pre style="background: #f8f8f8; padding: 10px; border-radius: 5px;">';
    echo 'location /whatsapp-bot/ {' . "\n";
    echo '    proxy_pass http://localhost:3002/;' . "\n";
    echo '    proxy_http_version 1.1;' . "\n";
    echo '    proxy_set_header Upgrade $http_upgrade;' . "\n";
    echo '    proxy_set_header Connection "upgrade";' . "\n";
    echo '    proxy_set_header Host $host;' . "\n";
    echo '    proxy_set_header X-Real-IP $remote_addr;' . "\n";
    echo '    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;' . "\n";
    echo '    proxy_set_header X-Forwarded-Proto $scheme;' . "\n";
    echo '}' . "\n";
    echo '</pre>';

    echo '<h5>Para Apache:</h5>';
    echo '<pre style="background: #f8f8f8; padding: 10px; border-radius: 5px;">';
    echo 'ProxyPass /whatsapp-bot/ http://localhost:3002/' . "\n";
    echo 'ProxyPassReverse /whatsapp-bot/ http://localhost:3002/' . "\n";
    echo 'ProxyPreserveHost On' . "\n";
    echo '</pre>';

    echo '</div>';

    // 4. Teste manual de configuração
    echo "<h3>4. Teste manual personalizado:</h3>\n";

    echo "<form method='post' style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
    echo "<h4>🧪 Teste URL personalizada:</h4>\n";
    echo "<p><label>URL do bot: <input type='text' name='custom_url' value='https://klubecash.com:3002' style='width: 300px; padding: 5px;'></label></p>\n";
    echo "<p><input type='submit' name='test_custom' value='Testar URL' style='background: #FF7A00; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'></p>\n";
    echo "</form>\n";

    if (isset($_POST['test_custom']) && $_POST['custom_url']) {
        $customUrl = $_POST['custom_url'];
        echo "<h4>🧪 Testando URL personalizada: <code>{$customUrl}</code></h4>\n";

        // Testar status
        $statusUrl = rtrim($customUrl, '/') . '/status';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $statusUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if ($data && isset($data['status'])) {
                echo "<p>✅ <strong>URL FUNCIONA!</strong></p>\n";
                echo "<p>• Status: {$data['status']}</p>\n";
                echo "<p>• Bot Ready: " . ($data['bot_ready'] ? 'Sim' : 'Não') . "</p>\n";

                if ($data['bot_ready']) {
                    // Testar envio
                    echo "<p>🚀 Testando envio de mensagem...</p>\n";

                    $sendUrl = rtrim($customUrl, '/') . '/send-message';
                    $testData = [
                        'phone' => '34991191534',
                        'message' => "🧪 TESTE CONEXÃO DIRETA\n\nURL: {$customUrl}\nData: " . date('d/m/Y H:i:s') . "\n\nConexão direta funcionando! ✅",
                        'secret' => 'klube-cash-2024'
                    ];

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $sendUrl);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                    $sendResponse = curl_exec($ch);
                    $sendHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if ($sendHttpCode === 200) {
                        $result = json_decode($sendResponse, true);
                        if ($result && $result['success']) {
                            echo "<p>✅ <strong>ENVIO BEM-SUCEDIDO!</strong></p>\n";
                            echo "<p>• Mensagem enviada com sucesso</p>\n";

                            // Salvar URL como a correta
                            $config = [
                                'working_url' => $customUrl,
                                'send_endpoint' => $sendUrl,
                                'tested_at' => date('Y-m-d H:i:s'),
                                'test_success' => true
                            ];

                            file_put_contents('logs/bot_direct_config.json', json_encode($config, JSON_PRETTY_PRINT));
                            echo "<p>📝 Configuração salva em: logs/bot_direct_config.json</p>\n";

                            echo '<div style="background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;">';
                            echo '<h4>🎉 CONFIGURAÇÃO ENCONTRADA!</h4>';
                            echo "<p>Use esta URL: <code>{$sendUrl}</code></p>";
                            echo '<p><strong>Próximo passo:</strong> Atualizar FixedBrutalNotificationSystem</p>';
                            echo '</div>';

                        } else {
                            echo "<p>❌ Envio falhou: " . ($result['error'] ?? 'Erro desconhecido') . "</p>\n";
                        }
                    } else {
                        echo "<p>❌ Erro HTTP {$sendHttpCode} no envio</p>\n";
                    }
                }

            } else {
                echo "<p>❌ Resposta inválida do bot</p>\n";
            }
        } else {
            echo "<p>❌ URL não responde: HTTP {$httpCode}</p>\n";
        }
    }

    // 5. Próximos passos
    echo "<h3>5. Próximos passos:</h3>\n";

    echo '<div style="background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;">';
    echo '<h4>📋 CHECKLIST PARA CONEXÃO DIRETA</h4>';

    echo '<h5>1️⃣ Identificar URL do bot:</h5>';
    echo '<ul>';
    echo '<li>✅ Testamos URLs comuns acima</li>';
    echo '<li>🔧 Configure proxy reverso se necessário</li>';
    echo '<li>🧪 Use o teste personalizado para outras URLs</li>';
    echo '</ul>';

    echo '<h5>2️⃣ Configurar sistema:</h5>';
    echo '<ul>';
    echo '<li>📝 Atualizar FixedBrutalNotificationSystem com URL correta</li>';
    echo '<li>🧪 Testar integração completa</li>';
    echo '<li>📊 Monitorar funcionamento</li>';
    echo '</ul>';

    echo '<h5>3️⃣ Alternativas se não conseguir:</h5>';
    echo '<ul>';
    echo '<li>🔀 Manter fallback atual (já funcionando 100%)</li>';
    echo '<li>🌐 Configurar túnel SSH para desenvolvimento</li>';
    echo '<li>🔧 Configurar webhook no servidor</li>';
    echo '</ul>';

    echo '</div>';

    // Verificar se já temos alguma configuração salva
    if (file_exists('logs/bot_direct_config.json')) {
        $savedConfig = json_decode(file_get_contents('logs/bot_direct_config.json'), true);
        echo "<h3>6. Configuração salva encontrada:</h3>\n";
        echo "<p>📝 <strong>URL salva:</strong> <code>{$savedConfig['working_url']}</code></p>\n";
        echo "<p>⏰ <strong>Testada em:</strong> {$savedConfig['tested_at']}</p>\n";

        if ($savedConfig['test_success']) {
            echo '<div style="background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;">';
            echo '<h4>✅ CONFIGURAÇÃO PRONTA PARA USO!</h4>';
            echo '<p>Clique no botão abaixo para aplicar esta configuração:</p>';
            echo '<form method="post">';
            echo '<input type="hidden" name="apply_config" value="1">';
            echo '<input type="submit" value="Aplicar Configuração Direta" style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">';
            echo '</form>';
            echo '</div>';
        }
    }

    // Aplicar configuração se solicitado
    if (isset($_POST['apply_config'])) {
        echo "<h3>7. Aplicando configuração direta:</h3>\n";

        if (file_exists('logs/bot_direct_config.json')) {
            $config = json_decode(file_get_contents('logs/bot_direct_config.json'), true);
            $workingUrl = $config['working_url'];

            echo "<p>🔧 Atualizando FixedBrutalNotificationSystem com URL: <code>{$workingUrl}</code></p>\n";

            // Aqui atualizaríamos o arquivo, mas vou apenas mostrar as instruções
            echo '<div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;">';
            echo '<h4>⚠️ ATUALIZAÇÃO MANUAL NECESSÁRIA</h4>';
            echo '<p>Edite o arquivo <code>classes/FixedBrutalNotificationSystem.php</code>:</p>';
            echo '<p>Na linha onde está:</p>';
            echo '<pre style="background: #f8f8f8; padding: 10px;">"http://localhost:3002/send-message",</pre>';
            echo '<p>Substitua por:</p>';
            echo '<pre style="background: #f8f8f8; padding: 10px;">"' . $workingUrl . '/send-message",</pre>';
            echo '<p>E coloque como primeira opção na lista para ser testada primeiro.</p>';
            echo '</div>';

        } else {
            echo "<p>❌ Nenhuma configuração salva encontrada</p>\n";
        }
    }

} catch (Exception $e) {
    echo "<h3>❌ ERRO: " . htmlspecialchars($e->getMessage()) . "</h3>\n";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Configuração Bot Direto - Klube Cash</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        pre { background: #f8f8f8; padding: 10px; border-radius: 5px; overflow-x: auto; }
        code { background: #f8f8f8; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        form { margin: 10px 0; }
        input[type="text"] { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        input[type="submit"] { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h3>🎯 Este script:</h3>
        <ul>
            <li>Testa diferentes URLs para encontrar o bot</li>
            <li>Configura proxy reverso se necessário</li>
            <li>Permite teste de URLs personalizadas</li>
            <li>Salva configuração funcionando para uso</li>
        </ul>

        <h3>💡 Dicas:</h3>
        <ul>
            <li>Se o bot roda no mesmo servidor, use localhost:3002</li>
            <li>Se precisa acesso externo, configure proxy no Apache/Nginx</li>
            <li>Teste URLs personalizadas na seção apropriada</li>
        </ul>
    </div>
</body>
</html>