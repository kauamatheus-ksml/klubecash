<?php
/**
 * CONFIGURAÇÃO PROXY REVERSO - BOT WHATSAPP
 * Script para configurar acesso ao bot via proxy
 */

echo "<h2>🌐 CONFIGURAÇÃO PROXY REVERSO - BOT WHATSAPP</h2>\n";

try {
    // 1. Detectar servidor web
    echo "<h3>1. Detectando servidor web:</h3>\n";

    $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido';
    echo "<p>🔍 <strong>Servidor detectado:</strong> {$serverSoftware}</p>\n";

    $isNginx = stripos($serverSoftware, 'nginx') !== false;
    $isApache = stripos($serverSoftware, 'apache') !== false;

    if ($isNginx) {
        echo "<p>✅ Nginx detectado - Vamos configurar proxy para Nginx</p>\n";
        $serverType = 'nginx';
    } elseif ($isApache) {
        echo "<p>✅ Apache detectado - Vamos configurar proxy para Apache</p>\n";
        $serverType = 'apache';
    } else {
        echo "<p>⚠️ Servidor não identificado automaticamente</p>\n";
        $serverType = 'unknown';
    }

    // 2. Gerar configurações de proxy
    echo "<h3>2. Configurações de proxy para o bot:</h3>\n";

    // Configuração Nginx
    $nginxConfig = '
# Configuração Proxy Bot WhatsApp - Klube Cash
location /whatsapp-bot/ {
    proxy_pass http://localhost:3002/;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header X-Forwarded-Host $host;
    proxy_set_header X-Forwarded-Port $server_port;

    # Timeouts para WhatsApp
    proxy_connect_timeout 60s;
    proxy_send_timeout 60s;
    proxy_read_timeout 60s;

    # Buffer settings
    proxy_buffering off;
    proxy_request_buffering off;
}

# Alternativa: /api/whatsapp-bot/
location /api/whatsapp-bot/ {
    proxy_pass http://localhost:3002/;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}';

    // Configuração Apache
    $apacheConfig = '
# Configuração Proxy Bot WhatsApp - Klube Cash
# Adicionar ao VirtualHost ou .htaccess

# Habilitar módulos necessários (se não estiverem)
LoadModule proxy_module modules/mod_proxy.so
LoadModule proxy_http_module modules/mod_proxy_http.so

# Configuração do proxy
ProxyPreserveHost On
ProxyRequests Off

# Proxy para /whatsapp-bot/
ProxyPass /whatsapp-bot/ http://localhost:3002/
ProxyPassReverse /whatsapp-bot/ http://localhost:3002/

# Proxy para /api/whatsapp-bot/ (alternativa)
ProxyPass /api/whatsapp-bot/ http://localhost:3002/
ProxyPassReverse /api/whatsapp-bot/ http://localhost:3002/

# Headers necessários
ProxyPassReverse /whatsapp-bot/ http://localhost:3002/
Header always set X-Forwarded-Proto "https"
Header always set X-Forwarded-Host "%{HTTP_HOST}e"';

    // Mostrar configuração apropriada
    if ($serverType === 'nginx') {
        echo "<h4>🔧 Configuração para Nginx:</h4>\n";
        echo "<p>Adicione ao arquivo de configuração do site (ex: <code>/etc/nginx/sites-available/klubecash.com</code>):</p>\n";
        echo "<pre style='background: #f8f8f8; padding: 15px; border-radius: 5px; overflow-x: auto;'>" . htmlspecialchars($nginxConfig) . "</pre>\n";

        echo "<p><strong>Comandos para aplicar:</strong></p>\n";
        echo "<pre style='background: #333; color: #fff; padding: 10px; border-radius: 5px;'>";
        echo "sudo nano /etc/nginx/sites-available/klubecash.com\n";
        echo "sudo nginx -t\n";
        echo "sudo systemctl reload nginx\n";
        echo "</pre>\n";

    } elseif ($serverType === 'apache') {
        echo "<h4>🔧 Configuração para Apache:</h4>\n";
        echo "<p>Adicione ao VirtualHost do site ou ao arquivo <code>.htaccess</code>:</p>\n";
        echo "<pre style='background: #f8f8f8; padding: 15px; border-radius: 5px; overflow-x: auto;'>" . htmlspecialchars($apacheConfig) . "</pre>\n";

        echo "<p><strong>Comandos para aplicar:</strong></p>\n";
        echo "<pre style='background: #333; color: #fff; padding: 10px; border-radius: 5px;'>";
        echo "sudo a2enmod proxy\n";
        echo "sudo a2enmod proxy_http\n";
        echo "sudo a2enmod headers\n";
        echo "sudo systemctl reload apache2\n";
        echo "</pre>\n";

    } else {
        echo "<h4>🔧 Configurações para ambos servidores:</h4>\n";

        echo "<h5>Para Nginx:</h5>\n";
        echo "<pre style='background: #f8f8f8; padding: 15px; border-radius: 5px; overflow-x: auto;'>" . htmlspecialchars($nginxConfig) . "</pre>\n";

        echo "<h5>Para Apache:</h5>\n";
        echo "<pre style='background: #f8f8f8; padding: 15px; border-radius: 5px; overflow-x: auto;'>" . htmlspecialchars($apacheConfig) . "</pre>\n";
    }

    // Salvar configurações em arquivos
    if (!is_dir('config-samples')) {
        mkdir('config-samples', 0755, true);
    }

    file_put_contents('config-samples/nginx-whatsapp-bot.conf', $nginxConfig);
    file_put_contents('config-samples/apache-whatsapp-bot.conf', $apacheConfig);

    echo "<p>📝 <strong>Configurações salvas em:</strong></p>\n";
    echo "<p>• Nginx: <code>config-samples/nginx-whatsapp-bot.conf</code></p>\n";
    echo "<p>• Apache: <code>config-samples/apache-whatsapp-bot.conf</code></p>\n";

    // 3. Teste das URLs após configuração
    echo "<h3>3. Teste das URLs após configuração do proxy:</h3>\n";

    $testUrls = [
        'https://klubecash.com/whatsapp-bot/status',
        'https://klubecash.com/api/whatsapp-bot/status',
        'http://klubecash.com/whatsapp-bot/status',
        'http://klubecash.com/api/whatsapp-bot/status'
    ];

    echo "<p>⚠️ <strong>Execute após aplicar a configuração do proxy!</strong></p>\n";

    echo "<form method='post' style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
    echo "<h4>🧪 Testar URLs do proxy:</h4>\n";
    echo "<p><input type='submit' name='test_proxy' value='Testar Proxy Configurado' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'></p>\n";
    echo "</form>\n";

    if (isset($_POST['test_proxy'])) {
        echo "<h4>🧪 Testando URLs do proxy:</h4>\n";

        $workingUrl = null;

        foreach ($testUrls as $url) {
            echo "<p>🔍 Testando: <code>{$url}</code></p>\n";

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
                    echo "<p>• Bot Ready: " . ($data['bot_ready'] ? 'Sim' : 'Não') . "</p>\n";

                    $workingUrl = str_replace('/status', '', $url);
                    echo "<p>🎯 <strong>URL base para usar:</strong> <code>{$workingUrl}</code></p>\n";

                    // Salvar configuração funcionando
                    $proxyConfig = [
                        'proxy_url' => $workingUrl,
                        'send_endpoint' => $workingUrl . '/send-message',
                        'status_endpoint' => $url,
                        'server_type' => $serverType,
                        'tested_at' => date('Y-m-d H:i:s'),
                        'working' => true
                    ];

                    file_put_contents('logs/proxy_config.json', json_encode($proxyConfig, JSON_PRETTY_PRINT));
                    echo "<p>📝 Configuração salva em: logs/proxy_config.json</p>\n";

                    break;

                } else {
                    echo "<p>❌ Resposta inválida</p>\n";
                }
            } else {
                echo "<p>❌ Falha: HTTP {$httpCode}" . ($error ? ", Error: {$error}" : "") . "</p>\n";
            }
        }

        if ($workingUrl) {
            echo '<div style="background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;">';
            echo '<h4>🎉 PROXY CONFIGURADO COM SUCESSO!</h4>';
            echo "<p><strong>URL do bot:</strong> <code>{$workingUrl}</code></p>";
            echo '<p><strong>Próximo passo:</strong> Atualizar FixedBrutalNotificationSystem</p>';

            echo '<form method="post">';
            echo '<input type="hidden" name="update_system" value="1">';
            echo '<input type="hidden" name="proxy_url" value="' . htmlspecialchars($workingUrl) . '">';
            echo '<input type="submit" value="Atualizar Sistema Agora" style="background: #FF7A00; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">';
            echo '</form>';
            echo '</div>';

        } else {
            echo '<div style="background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;">';
            echo '<h4>❌ PROXY NÃO FUNCIONANDO</h4>';
            echo '<p>Verifique se:</p>';
            echo '<ul>';
            echo '<li>A configuração foi aplicada corretamente</li>';
            echo '<li>O servidor web foi recarregado</li>';
            echo '<li>O bot PM2 está rodando na porta 3002</li>';
            echo '<li>Não há conflitos de configuração</li>';
            echo '</ul>';
            echo '</div>';
        }
    }

    // 4. Atualizar sistema se solicitado
    if (isset($_POST['update_system']) && $_POST['proxy_url']) {
        echo "<h3>4. Atualizando FixedBrutalNotificationSystem:</h3>\n";

        $proxyUrl = $_POST['proxy_url'];
        $sendEndpoint = $proxyUrl . '/send-message';

        echo "<p>🔧 Atualizando com URL: <code>{$sendEndpoint}</code></p>\n";

        // Ler arquivo atual
        $systemFile = 'classes/FixedBrutalNotificationSystem.php';
        if (file_exists($systemFile)) {
            $content = file_get_contents($systemFile);

            // Procurar pela lista de URLs e adicionar a nova no topo
            $pattern = '/(\$botUrls = \[)(.*?)(\];)/s';

            if (preg_match($pattern, $content, $matches)) {
                $newUrls = "\$botUrls = [\n";
                $newUrls .= "                \"{$sendEndpoint}\",                    // Proxy configurado (PRIORIDADE)\n";
                $newUrls .= "                \"http://localhost:3002/send-message\",        // Bot local\n";
                $newUrls .= "                \"http://127.0.0.1:3002/send-message\",        // Bot local alternativo\n";
                $newUrls .= "                \"https://klubecash.com:3002/send-message\",   // Bot no servidor (HTTPS)\n";
                $newUrls .= "                \"http://klubecash.com:3002/send-message\",    // Bot no servidor (HTTP)\n";
                $newUrls .= "                \"http://localhost:3000/send-message\",        // Porta alternativa\n";
                $newUrls .= "                \"http://localhost:3001/send-message\"         // Porta alternativa\n";
                $newUrls .= "            ];";

                $newContent = str_replace($matches[0], $newUrls, $content);

                // Fazer backup
                copy($systemFile, $systemFile . '.backup.' . date('YmdHis'));

                // Salvar nova versão
                file_put_contents($systemFile, $newContent);

                echo "<p>✅ <strong>Sistema atualizado com sucesso!</strong></p>\n";
                echo "<p>• Backup criado: {$systemFile}.backup." . date('YmdHis') . "</p>\n";
                echo "<p>• Nova URL adicionada como prioridade: <code>{$sendEndpoint}</code></p>\n";

                echo '<div style="background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;">';
                echo '<h4>🎉 CONFIGURAÇÃO COMPLETA!</h4>';
                echo '<p>O sistema agora tentará usar o proxy primeiro.</p>';
                echo '<p><strong>Próximo passo:</strong> Testar a integração completa</p>';

                echo '<form method="post">';
                echo '<input type="hidden" name="test_integration" value="1">';
                echo '<input type="submit" value="Testar Integração Completa" style="background: #6f42c1; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">';
                echo '</form>';
                echo '</div>';

            } else {
                echo "<p>❌ Não foi possível encontrar a lista de URLs para atualizar</p>\n";
                echo "<p>Atualize manualmente adicionando: <code>{$sendEndpoint}</code></p>\n";
            }

        } else {
            echo "<p>❌ Arquivo FixedBrutalNotificationSystem.php não encontrado</p>\n";
        }
    }

    // 5. Teste de integração completa
    if (isset($_POST['test_integration'])) {
        echo "<h3>5. Testando integração completa:</h3>\n";

        require_once 'classes/FixedBrutalNotificationSystem.php';

        // Criar transação de teste
        require_once 'config/database.php';
        $db = Database::getConnection();

        // Buscar usuário e loja
        $userStmt = $db->query("SELECT id, nome, telefone FROM usuarios WHERE telefone IS NOT NULL LIMIT 1");
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);

        $storeStmt = $db->query("SELECT id, nome_fantasia FROM lojas LIMIT 1");
        $store = $storeStmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $store) {
            // Criar transação de teste do proxy
            $codigo = 'PROXY_TEST_' . time();
            $valor = 75.00;
            $cashback = round($valor * 0.05, 2);

            $insertStmt = $db->prepare("
                INSERT INTO transacoes_cashback (
                    usuario_id, loja_id, valor_total, valor_cliente,
                    codigo_transacao, descricao, status,
                    data_transacao, data_criacao_usuario
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");

            $insertStmt->execute([
                $user['id'], $store['id'], $valor, $cashback,
                $codigo, 'Teste de Proxy Direto', 'aprovado'
            ]);

            $testTransactionId = $db->lastInsertId();

            echo "<p>💰 Transação de teste criada: #{$testTransactionId}</p>\n";
            echo "<p>🚀 Testando notificação via proxy...</p>\n";

            $system = new FixedBrutalNotificationSystem();
            $result = $system->forceNotifyTransaction($testTransactionId);

            if ($result['success']) {
                echo "<p>✅ <strong>TESTE COMPLETO BEM-SUCEDIDO!</strong></p>\n";
                echo "<p>• Resultado: {$result['message']}</p>\n";
                if (isset($result['bot_url'])) {
                    echo "<p>• URL usada: {$result['bot_url']}</p>\n";
                }

                echo '<div style="background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;">';
                echo '<h4>🎊 CONEXÃO DIRETA CONFIGURADA!</h4>';
                echo '<p>O sistema agora está usando conexão direta com o bot WhatsApp via proxy.</p>';
                echo '<p><strong>Benefícios:</strong></p>';
                echo '<ul>';
                echo '<li>✅ Comunicação direta (mais rápida)</li>';
                echo '<li>✅ Sem dependência de fallbacks</li>';
                echo '<li>✅ Melhor controle e monitoramento</li>';
                echo '<li>✅ Logs mais detalhados</li>';
                echo '</ul>';
                echo '</div>';

            } else {
                echo "<p>❌ Teste falhou: {$result['message']}</p>\n";
                echo "<p>O sistema continuará usando fallback</p>\n";
            }

        } else {
            echo "<p>❌ Não foi possível encontrar usuário/loja para teste</p>\n";
        }
    }

    // 6. Instruções finais
    echo "<h3>6. Instruções finais:</h3>\n";

    echo '<div style="background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;">';
    echo '<h4>📋 CHECKLIST DE CONFIGURAÇÃO DO PROXY</h4>';

    echo '<h5>✅ Passos concluídos:</h5>';
    echo '<ul>';
    echo '<li>✅ Configurações de proxy geradas</li>';
    echo '<li>✅ Arquivos de exemplo salvos</li>';
    echo '<li>✅ URLs de teste preparadas</li>';
    echo '</ul>';

    echo '<h5>🔧 Para finalizar (manual):</h5>';
    echo '<ol>';
    echo '<li><strong>Aplicar configuração:</strong> Copie a configuração apropriada para seu servidor web</li>';
    echo '<li><strong>Recarregar servidor:</strong> Execute <code>sudo systemctl reload nginx</code> ou <code>sudo systemctl reload apache2</code></li>';
    echo '<li><strong>Testar proxy:</strong> Use o botão "Testar Proxy Configurado" acima</li>';
    echo '<li><strong>Atualizar sistema:</strong> Use o botão "Atualizar Sistema Agora" se o proxy funcionar</li>';
    echo '<li><strong>Teste final:</strong> Use o botão "Testar Integração Completa"</li>';
    echo '</ol>';

    echo '<h5>🚨 Se algo não funcionar:</h5>';
    echo '<ul>';
    echo '<li>Verifique logs do servidor web</li>';
    echo '<li>Confirme que o bot PM2 está rodando</li>';
    echo '<li>Teste URLs manualmente no navegador</li>';
    echo '<li>O sistema continuará usando fallback (já funcionando)</li>';
    echo '</ul>';

    echo '</div>';

} catch (Exception $e) {
    echo "<h3>❌ ERRO: " . htmlspecialchars($e->getMessage()) . "</h3>\n";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Configuração Proxy Bot - Klube Cash</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        pre { background: #f8f8f8; padding: 10px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
        code { background: #f8f8f8; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        form { margin: 10px 0; }
        input[type="submit"] { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h3>🎯 Este script configura:</h3>
        <ul>
            <li>Proxy reverso para acessar o bot via HTTPS</li>
            <li>Configurações automáticas para Nginx/Apache</li>
            <li>Teste e validação da configuração</li>
            <li>Atualização automática do sistema</li>
        </ul>

        <h3>🚀 Benefícios da conexão direta:</h3>
        <ul>
            <li>Comunicação mais rápida e confiável</li>
            <li>Melhor controle e monitoramento</li>
            <li>Logs detalhados de todas as operações</li>
            <li>Independência de fallbacks externos</li>
        </ul>
    </div>
</body>
</html>