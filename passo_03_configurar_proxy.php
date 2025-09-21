<?php
/**
 * PASSO 3 - CONFIGURAR PROXY
 * Vamos configurar o proxy reverso para acessar o bot
 */

echo "<h1>🌐 PASSO 3 - CONFIGURAR PROXY REVERSO</h1>\n";

try {
    // Verificar se chegamos aqui corretamente
    if (!file_exists('logs/pm2_status.json')) {
        echo '<div style="background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;">';
        echo '<h3>⚠️ VOLTE AO PASSO 2</h3>';
        echo '<p>Parece que você pulou o Passo 2. Vamos verificar o bot primeiro.</p>';
        echo '<a href="passo_02_verificar_bot.php" style="background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">⬅️ VOLTAR AO PASSO 2</a>';
        echo '</div>';
        return;
    }

    // 1. Detectar tipo de servidor
    echo "<h2>🔍 DETECTANDO SEU SERVIDOR WEB</h2>\n";

    $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido';
    $isNginx = stripos($serverSoftware, 'nginx') !== false;
    $isApache = stripos($serverSoftware, 'apache') !== false;

    echo "<p><strong>Servidor detectado:</strong> {$serverSoftware}</p>\n";

    if ($isNginx) {
        echo '<div style="background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;">';
        echo '<h3>✅ NGINX DETECTADO</h3>';
        echo '<p>Perfeito! Vamos configurar o proxy para Nginx.</p>';
        echo '</div>';
        $serverType = 'nginx';
    } elseif ($isApache) {
        echo '<div style="background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;">';
        echo '<h3>✅ APACHE DETECTADO</h3>';
        echo '<p>Perfeito! Vamos configurar o proxy para Apache.</p>';
        echo '</div>';
        $serverType = 'apache';
    } else {
        echo '<div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;">';
        echo '<h3>⚠️ VAMOS TENTAR AMBOS</h3>';
        echo '<p>Não detectamos automaticamente. Vamos dar instruções para Nginx E Apache.</p>';
        echo '</div>';
        $serverType = 'both';
    }

    // 2. Instruções específicas por servidor
    if ($serverType === 'nginx' || $serverType === 'both') {
        echo "<h2>🔧 CONFIGURAÇÃO PARA NGINX</h2>\n";

        echo '<div style="background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;">';
        echo '<h3>📝 PASSO A PASSO NGINX:</h3>';

        echo '<h4>1️⃣ Encontrar arquivo de configuração:</h4>';
        echo '<pre style="background: #333; color: #fff; padding: 10px; border-radius: 5px;">';
        echo '# Execute para encontrar o arquivo do seu site:' . "\n";
        echo 'sudo ls /etc/nginx/sites-available/' . "\n";
        echo 'sudo ls /etc/nginx/conf.d/' . "\n";
        echo '</pre>';

        echo '<h4>2️⃣ Editar arquivo de configuração:</h4>';
        echo '<pre style="background: #333; color: #fff; padding: 10px; border-radius: 5px;">';
        echo '# Substitua "klubecash.com" pelo nome do seu arquivo:' . "\n";
        echo 'sudo nano /etc/nginx/sites-available/klubecash.com' . "\n";
        echo '</pre>';

        echo '<h4>3️⃣ Adicionar ESTA configuração:</h4>';
        echo '<p><strong>⚠️ COPIE EXATAMENTE COMO ESTÁ ABAIXO:</strong></p>';
        echo '<pre style="background: #28a745; color: #fff; padding: 15px; border-radius: 5px; border: 3px solid #155724;">';
        echo '    # Proxy Bot WhatsApp - Klube Cash' . "\n";
        echo '    location /whatsapp-bot/ {' . "\n";
        echo '        proxy_pass http://localhost:3002/;' . "\n";
        echo '        proxy_http_version 1.1;' . "\n";
        echo '        proxy_set_header Upgrade $http_upgrade;' . "\n";
        echo '        proxy_set_header Connection "upgrade";' . "\n";
        echo '        proxy_set_header Host $host;' . "\n";
        echo '        proxy_set_header X-Real-IP $remote_addr;' . "\n";
        echo '        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;' . "\n";
        echo '        proxy_set_header X-Forwarded-Proto $scheme;' . "\n";
        echo '    }' . "\n";
        echo '</pre>';

        echo '<p><strong>📍 ONDE COLOCAR:</strong> Dentro do bloco <code>server { ... }</code>, antes da última chave <code>}</code></p>';

        echo '<h4>4️⃣ Salvar e testar:</h4>';
        echo '<pre style="background: #333; color: #fff; padding: 10px; border-radius: 5px;">';
        echo '# Pressione Ctrl+X, depois Y, depois Enter para salvar' . "\n";
        echo 'sudo nginx -t' . "\n";
        echo 'sudo systemctl reload nginx' . "\n";
        echo '</pre>';
        echo '</div>';
    }

    if ($serverType === 'apache' || $serverType === 'both') {
        echo "<h2>🔧 CONFIGURAÇÃO PARA APACHE</h2>\n";

        echo '<div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;">';
        echo '<h3>📝 PASSO A PASSO APACHE:</h3>';

        echo '<h4>1️⃣ Habilitar módulos necessários:</h4>';
        echo '<pre style="background: #333; color: #fff; padding: 10px; border-radius: 5px;">';
        echo 'sudo a2enmod proxy' . "\n";
        echo 'sudo a2enmod proxy_http' . "\n";
        echo 'sudo a2enmod headers' . "\n";
        echo '</pre>';

        echo '<h4>2️⃣ Encontrar arquivo de configuração:</h4>';
        echo '<pre style="background: #333; color: #fff; padding: 10px; border-radius: 5px;">';
        echo '# Execute para encontrar o arquivo do seu site:' . "\n";
        echo 'sudo ls /etc/apache2/sites-available/' . "\n";
        echo '</pre>';

        echo '<h4>3️⃣ Editar arquivo de configuração:</h4>';
        echo '<pre style="background: #333; color: #fff; padding: 10px; border-radius: 5px;">';
        echo '# Substitua pelo nome do seu arquivo:' . "\n";
        echo 'sudo nano /etc/apache2/sites-available/klubecash.com.conf' . "\n";
        echo '</pre>';

        echo '<h4>4️⃣ Adicionar ESTA configuração:</h4>';
        echo '<p><strong>⚠️ COPIE EXATAMENTE COMO ESTÁ ABAIXO:</strong></p>';
        echo '<pre style="background: #ffc107; color: #333; padding: 15px; border-radius: 5px; border: 3px solid #856404;">';
        echo '    # Proxy Bot WhatsApp - Klube Cash' . "\n";
        echo '    ProxyPreserveHost On' . "\n";
        echo '    ProxyRequests Off' . "\n";
        echo '    ' . "\n";
        echo '    ProxyPass /whatsapp-bot/ http://localhost:3002/' . "\n";
        echo '    ProxyPassReverse /whatsapp-bot/ http://localhost:3002/' . "\n";
        echo '    ' . "\n";
        echo '    Header always set X-Forwarded-Proto "https"' . "\n";
        echo '</pre>';

        echo '<p><strong>📍 ONDE COLOCAR:</strong> Dentro do bloco <code>&lt;VirtualHost *:443&gt; ... &lt;/VirtualHost&gt;</code></p>';

        echo '<h4>5️⃣ Salvar e testar:</h4>';
        echo '<pre style="background: #333; color: #fff; padding: 10px; border-radius: 5px;">';
        echo '# Pressione Ctrl+X, depois Y, depois Enter para salvar' . "\n";
        echo 'sudo apache2ctl configtest' . "\n";
        echo 'sudo systemctl reload apache2' . "\n";
        echo '</pre>';
        echo '</div>';
    }

    // 3. Teste de configuração
    echo "<h2>🧪 TESTAR A CONFIGURAÇÃO</h2>\n";

    echo '<div style="background: #17a2b8; color: white; padding: 15px; border-radius: 5px; margin: 10px 0;">';
    echo '<h3>🔍 APÓS CONFIGURAR, TESTE ASSIM:</h3>';

    echo '<h4>No terminal SSH:</h4>';
    echo '<pre style="background: #333; color: #fff; padding: 10px; border-radius: 5px;">';
    echo 'curl https://klubecash.com/whatsapp-bot/status' . "\n";
    echo '</pre>';

    echo '<h4>Ou no navegador:</h4>';
    echo '<p><strong>Acesse:</strong> <a href="https://klubecash.com/whatsapp-bot/status" target="_blank" style="color: #fff; text-decoration: underline;">https://klubecash.com/whatsapp-bot/status</a></p>';

    echo '<h4>✅ Se funcionar, você verá algo assim:</h4>';
    echo '<pre style="background: #28a745; color: #fff; padding: 10px; border-radius: 5px;">';
    echo '{' . "\n";
    echo '  "status": "connected",' . "\n";
    echo '  "bot_ready": true,' . "\n";
    echo '  "version": "2.1.0"' . "\n";
    echo '}' . "\n";
    echo '</pre>';
    echo '</div>';

    // 4. Formulário para coletar resultado
    echo "<h2>📝 COMO FOI O TESTE?</h2>\n";

    echo '<form method="post" style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 10px 0;">';
    echo '<h3>🎯 O que aconteceu quando você testou?</h3>';

    echo '<p><input type="radio" name="proxy_test" value="working" id="proxy_working">';
    echo '<label for="proxy_working"> ✅ Funcionou! Vi o JSON com "status": "connected"</label></p>';

    echo '<p><input type="radio" name="proxy_test" value="404" id="proxy_404">';
    echo '<label for="proxy_404"> ❌ Erro 404 - Página não encontrada</label></p>';

    echo '<p><input type="radio" name="proxy_test" value="500" id="proxy_500">';
    echo '<label for="proxy_500"> ❌ Erro 500 - Erro interno do servidor</label></p>';

    echo '<p><input type="radio" name="proxy_test" value="timeout" id="proxy_timeout">';
    echo '<label for="proxy_timeout"> ⏰ Timeout - Não carregou</label></p>';

    echo '<p><input type="radio" name="proxy_test" value="other_error" id="proxy_other">';
    echo '<label for="proxy_other"> 🤷 Outro erro ou não consegui testar</label></p>';

    echo '<p style="margin-top: 20px;">';
    echo '<input type="submit" name="test_proxy" value="Continuar com Base no Resultado" style="background: #FF7A00; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">';
    echo '</p>';
    echo '</form>';

    // 5. Processar resultado do teste
    if (isset($_POST['test_proxy']) && isset($_POST['proxy_test'])) {
        $testResult = $_POST['proxy_test'];

        echo "<h2>🎯 PRÓXIMOS PASSOS BASEADOS NO SEU RESULTADO:</h2>\n";

        switch ($testResult) {
            case 'working':
                echo '<div style="background: #d4edda; padding: 20px; border-radius: 5px; margin: 10px 0;">';
                echo '<h3>🎉 PERFEITO! PROXY FUNCIONANDO</h3>';
                echo '<p>Excelente! O proxy está configurado corretamente. Agora vamos atualizar o sistema PHP.</p>';
                echo '<a href="passo_04_atualizar_sistema.php" style="background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">➡️ IR PARA PASSO 4 - ATUALIZAR SISTEMA</a>';
                echo '</div>';

                // Salvar configuração funcionando
                $proxyConfig = [
                    'status' => 'working',
                    'proxy_url' => 'https://klubecash.com/whatsapp-bot',
                    'tested_at' => date('Y-m-d H:i:s')
                ];
                file_put_contents('logs/proxy_config.json', json_encode($proxyConfig, JSON_PRETTY_PRINT));
                break;

            case '404':
                echo '<div style="background: #fff3cd; padding: 20px; border-radius: 5px; margin: 10px 0;">';
                echo '<h3>🔧 ERRO 404 - VAMOS RESOLVER</h3>';
                echo '<p>O proxy não foi configurado corretamente. Vamos revisar:</p>';
                echo '<a href="passo_03b_resolver_404.php" style="background: #ffc107; color: #333; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">🔧 RESOLVER ERRO 404</a>';
                echo '</div>';
                break;

            case '500':
            case 'timeout':
            case 'other_error':
                echo '<div style="background: #f8d7da; padding: 20px; border-radius: 5px; margin: 10px 0;">';
                echo '<h3>🔧 VAMOS DIAGNOSTICAR</h3>';
                echo '<p>Houve um problema. Vamos investigar e resolver passo a passo.</p>';
                echo '<a href="passo_03c_diagnosticar.php" style="background: #dc3545; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">🔍 DIAGNOSTICAR PROBLEMA</a>';
                echo '</div>';
                break;
        }

        // Salvar resultado do teste
        $testInfo = [
            'proxy_test_result' => $testResult,
            'tested_at' => date('Y-m-d H:i:s'),
            'next_step' => $testResult === 'working' ? 'update_system' : 'fix_proxy'
        ];

        file_put_contents('logs/proxy_test.json', json_encode($testInfo, JSON_PRETTY_PRINT));
    }

    // 6. Alternativa sem configuração
    echo "<h2>🤝 ALTERNATIVA SEM PROXY</h2>\n";

    echo '<div style="background: #e2e3e5; padding: 15px; border-radius: 5px; margin: 10px 0;">';
    echo '<h3>💡 SE NÃO QUISER CONFIGURAR PROXY:</h3>';
    echo '<p>Lembre-se: o sistema atual já funciona 100% com fallback!</p>';
    echo '<ul>';
    echo '<li>✅ Notificações automáticas funcionando</li>';
    echo '<li>✅ Sistema robusto e confiável</li>';
    echo '<li>✅ Logs completos</li>';
    echo '</ul>';
    echo '<p><strong>O proxy é apenas uma otimização, não é obrigatório!</strong></p>';
    echo '<a href="relatorio_final_notificacoes.php" style="background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">📊 MANTER SISTEMA ATUAL</a>';
    echo '</div>';

} catch (Exception $e) {
    echo "<h3>❌ ERRO: " . htmlspecialchars($e->getMessage()) . "</h3>\n";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Passo 3 - Configurar Proxy - Klube Cash</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        pre { background: #f8f8f8; padding: 10px; border-radius: 5px; overflow-x: auto; font-size: 14px; }
        a { text-decoration: none; }
        h1 { color: #333; border-bottom: 3px solid #FF7A00; padding-bottom: 10px; }
        input[type="radio"] { margin-right: 10px; }
        label { cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <p><strong>🎯 Objetivo:</strong> Configurar proxy reverso para acessar o bot via HTTPS.</p>
        <p><strong>⏱️ Tempo estimado:</strong> 10-15 minutos</p>
        <p><strong>🔧 Nível:</strong> Intermediário (configuração de servidor web)</p>
        <p><strong>⚠️ Importante:</strong> Copie as configurações exatamente como mostrado!</p>
    </div>
</body>
</html>