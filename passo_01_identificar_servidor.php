<?php
/**
 * PASSO 1 - IDENTIFICAR SERVIDOR
 * Vamos descobrir que tipo de servidor você tem
 */

echo "<h1>🔍 PASSO 1 - IDENTIFICAR SEU SERVIDOR</h1>\n";

try {
    // 1. Detectar informações do servidor
    echo "<h2>📊 Informações do seu servidor:</h2>\n";

    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>\n";
    echo "<tr><th>Informação</th><th>Valor</th></tr>\n";

    $serverInfo = [
        'Sistema Operacional' => php_uname('s') . ' ' . php_uname('r'),
        'Servidor Web' => $_SERVER['SERVER_SOFTWARE'] ?? 'Não detectado',
        'Versão PHP' => phpversion(),
        'Documento Root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Não detectado',
        'Host' => $_SERVER['HTTP_HOST'] ?? 'Não detectado',
        'IP do Servidor' => $_SERVER['SERVER_ADDR'] ?? 'Não detectado'
    ];

    foreach ($serverInfo as $info => $valor) {
        echo "<tr><td><strong>{$info}</strong></td><td>{$valor}</td></tr>\n";
    }
    echo "</table>\n";

    // 2. Determinar tipo de servidor
    $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? '';
    $isNginx = stripos($serverSoftware, 'nginx') !== false;
    $isApache = stripos($serverSoftware, 'apache') !== false;

    echo "<h2>🎯 Tipo de servidor detectado:</h2>\n";

    if ($isNginx) {
        echo '<div style="background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;">';
        echo '<h3>✅ NGINX DETECTADO</h3>';
        echo '<p>Seu servidor usa Nginx. Vamos configurar o proxy para Nginx.</p>';
        echo '</div>';
        $serverType = 'nginx';
    } elseif ($isApache) {
        echo '<div style="background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;">';
        echo '<h3>✅ APACHE DETECTADO</h3>';
        echo '<p>Seu servidor usa Apache. Vamos configurar o proxy para Apache.</p>';
        echo '</div>';
        $serverType = 'apache';
    } else {
        echo '<div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;">';
        echo '<h3>⚠️ SERVIDOR NÃO IDENTIFICADO</h3>';
        echo '<p>Não conseguimos detectar automaticamente. Vamos tentar ambas as configurações.</p>';
        echo '</div>';
        $serverType = 'unknown';
    }

    // 3. Verificar acesso SSH
    echo "<h2>🔑 Como acessar seu servidor:</h2>\n";

    echo '<div style="background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;">';
    echo '<h3>📝 VOCÊ PRECISA DE:</h3>';
    echo '<ul>';
    echo '<li><strong>Acesso SSH ao servidor</strong> (Terminal/Putty)</li>';
    echo '<li><strong>Usuário com sudo</strong> (permissões de administrador)</li>';
    echo '<li><strong>IP ou endereço do servidor</strong></li>';
    echo '</ul>';

    echo '<h3>💻 COMANDOS PARA CONECTAR:</h3>';
    echo '<pre style="background: #333; color: #fff; padding: 10px; border-radius: 5px;">';
    echo '# Via SSH (Linux/Mac/Windows com WSL)' . "\n";
    echo 'ssh seu_usuario@klubecash.com' . "\n\n";
    echo '# Via Putty (Windows)' . "\n";
    echo '# Host: klubecash.com' . "\n";
    echo '# Port: 22' . "\n";
    echo '# Username: seu_usuario' . "\n";
    echo '</pre>';
    echo '</div>';

    // 4. Próximos passos específicos
    echo "<h2>📋 PRÓXIMOS PASSOS PARA VOCÊ:</h2>\n";

    echo '<div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;">';
    echo '<h3>🎯 O QUE VAMOS FAZER:</h3>';
    echo '<ol>';
    echo '<li><strong>Conectar no servidor via SSH</strong></li>';
    echo '<li><strong>Verificar se o bot PM2 está rodando</strong></li>';
    echo '<li><strong>Configurar proxy reverso</strong> (automático)</li>';
    echo '<li><strong>Testar conexão direta</strong></li>';
    echo '<li><strong>Atualizar sistema PHP</strong> (automático)</li>';
    echo '</ol>';
    echo '</div>';

    // Salvar informações
    $detectedInfo = [
        'server_type' => $serverType,
        'server_software' => $serverSoftware,
        'php_version' => phpversion(),
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? '',
        'host' => $_SERVER['HTTP_HOST'] ?? '',
        'detected_at' => date('Y-m-d H:i:s')
    ];

    if (!is_dir('logs')) {
        mkdir('logs', 0755, true);
    }

    file_put_contents('logs/server_info.json', json_encode($detectedInfo, JSON_PRETTY_PRINT));

    echo "<p>📝 <strong>Informações salvas em:</strong> logs/server_info.json</p>\n";

    // 5. Botão para próximo passo
    echo "<h2>🚀 PRONTO PARA CONTINUAR?</h2>\n";

    echo '<div style="background: #28a745; color: white; padding: 20px; border-radius: 5px; margin: 20px 0; text-align: center;">';
    echo '<h3>✅ INFORMAÇÕES COLETADAS!</h3>';
    echo '<p>Agora vamos para o próximo passo: verificar o bot PM2</p>';
    echo '<a href="passo_02_verificar_bot.php" style="background: white; color: #28a745; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">➡️ IR PARA PASSO 2</a>';
    echo '</div>';

    // 6. Instruções de emergência
    echo "<h2>🆘 SE VOCÊ NÃO TEM ACESSO SSH:</h2>\n";

    echo '<div style="background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;">';
    echo '<h3>❌ SEM ACESSO SSH?</h3>';
    echo '<p>Se você não tem acesso SSH ao servidor, você tem algumas opções:</p>';
    echo '<ul>';
    echo '<li><strong>Opção 1:</strong> Pedir para alguém com acesso SSH fazer isso</li>';
    echo '<li><strong>Opção 2:</strong> Usar painel de controle (cPanel, Plesk, etc)</li>';
    echo '<li><strong>Opção 3:</strong> Manter o sistema atual (já funciona 100%)</li>';
    echo '</ul>';

    echo '<p><strong>⚠️ IMPORTANTE:</strong> O sistema atual já está funcionando perfeitamente com fallback. A conexão direta é apenas uma otimização!</p>';
    echo '</div>';

} catch (Exception $e) {
    echo "<h3>❌ ERRO: " . htmlspecialchars($e->getMessage()) . "</h3>\n";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Passo 1 - Identificar Servidor - Klube Cash</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        pre { background: #f8f8f8; padding: 10px; border-radius: 5px; overflow-x: auto; }
        a { text-decoration: none; }
        h1 { color: #333; border-bottom: 3px solid #FF7A00; padding-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <p><strong>🎯 Objetivo:</strong> Identificar seu servidor para configurar a conexão direta com o bot WhatsApp.</p>
        <p><strong>⏱️ Tempo estimado:</strong> 2 minutos</p>
        <p><strong>🔧 Nível:</strong> Iniciante (vamos te guiar em tudo!)</p>
    </div>
</body>
</html>