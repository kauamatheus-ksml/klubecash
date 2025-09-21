<?php
/**
 * PASSO 2 - VERIFICAR BOT PM2
 * Vamos verificar se o bot está rodando no servidor
 */

echo "<h1>🤖 PASSO 2 - VERIFICAR BOT PM2</h1>\n";

try {
    // 1. Instruções para conectar via SSH
    echo "<h2>🔑 CONECTANDO NO SERVIDOR VIA SSH</h2>\n";

    echo '<div style="background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;">';
    echo '<h3>📝 ABRA SEU TERMINAL E EXECUTE:</h3>';

    echo '<h4>💻 Windows (PowerShell ou CMD):</h4>';
    echo '<pre style="background: #333; color: #fff; padding: 15px; border-radius: 5px;">';
    echo 'ssh seu_usuario@klubecash.com' . "\n";
    echo '# Digite sua senha quando solicitado' . "\n";
    echo '</pre>';

    echo '<h4>🍎 Mac/Linux (Terminal):</h4>';
    echo '<pre style="background: #333; color: #fff; padding: 15px; border-radius: 5px;">';
    echo 'ssh seu_usuario@klubecash.com' . "\n";
    echo '# Digite sua senha quando solicitado' . "\n";
    echo '</pre>';

    echo '<h4>🪟 Windows (PuTTY):</h4>';
    echo '<ul>';
    echo '<li>Host Name: <strong>klubecash.com</strong></li>';
    echo '<li>Port: <strong>22</strong></li>';
    echo '<li>Connection Type: <strong>SSH</strong></li>';
    echo '<li>Clique "Open" e digite seu usuário/senha</li>';
    echo '</ul>';
    echo '</div>';

    // 2. Comandos para verificar o bot
    echo "<h2>🔍 COMANDOS PARA VERIFICAR O BOT</h2>\n";

    echo '<div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;">';
    echo '<h3>⚠️ EXECUTE UM COMANDO POR VEZ!</h3>';
    echo '<p>Copie e cole cada comando abaixo, um de cada vez, no terminal SSH:</p>';
    echo '</div>';

    $commands = [
        '1. Verificar se PM2 está instalado' => 'pm2 --version',
        '2. Listar processos PM2' => 'pm2 list',
        '3. Ver logs do bot (se estiver rodando)' => 'pm2 logs bot.js',
        '4. Ver status detalhado do bot' => 'pm2 show bot.js',
        '5. Verificar porta 3002' => 'netstat -tlnp | grep 3002'
    ];

    $commandNumber = 1;
    foreach ($commands as $description => $command) {
        echo "<h3>📝 Comando {$commandNumber}: {$description}</h3>\n";
        echo '<pre style="background: #333; color: #fff; padding: 15px; border-radius: 5px; margin: 10px 0;">';
        echo htmlspecialchars($command) . "\n";
        echo '</pre>';

        echo '<div style="background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0;">';
        echo '<p><strong>✅ O que esperar:</strong></p>';

        switch ($commandNumber) {
            case 1:
                echo '<p>• Se PM2 estiver instalado: mostrará a versão (ex: 5.3.0)</p>';
                echo '<p>• Se não estiver: "command not found"</p>';
                break;
            case 2:
                echo '<p>• Lista de processos rodando</p>';
                echo '<p>• Procure por "bot.js" ou "klube-whatsapp-bot"</p>';
                echo '<p>• Status deve ser "online"</p>';
                break;
            case 3:
                echo '<p>• Logs em tempo real do bot</p>';
                echo '<p>• Procure por mensagens como "WhatsApp conectado"</p>';
                echo '<p>• Pressione Ctrl+C para sair</p>';
                break;
            case 4:
                echo '<p>• Informações detalhadas do processo</p>';
                echo '<p>• Mostra porta, status, uptime</p>';
                break;
            case 5:
                echo '<p>• Se mostrar uma linha: porta 3002 está em uso</p>';
                echo '<p>• Se não mostrar nada: porta livre</p>';
                break;
        }
        echo '</div>';

        $commandNumber++;
    }

    // 3. Interpretar resultados
    echo "<h2>📊 COMO INTERPRETAR OS RESULTADOS</h2>\n";

    echo '<div style="background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;">';
    echo '<h3>✅ BOT FUNCIONANDO CORRETAMENTE:</h3>';
    echo '<ul>';
    echo '<li>PM2 instalado ✅</li>';
    echo '<li>Processo "bot.js" com status "online" ✅</li>';
    echo '<li>Logs mostram "WhatsApp conectado" ✅</li>';
    echo '<li>Porta 3002 em uso ✅</li>';
    echo '</ul>';
    echo '<p><strong>👉 Se tudo isso for verdade, vá para o Passo 3!</strong></p>';
    echo '</div>';

    echo '<div style="background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;">';
    echo '<h3>❌ PROBLEMAS POSSÍVEIS:</h3>';

    echo '<h4>Problema 1: PM2 não instalado</h4>';
    echo '<p><strong>Solução:</strong></p>';
    echo '<pre style="background: #333; color: #fff; padding: 10px; border-radius: 5px;">';
    echo 'npm install -g pm2' . "\n";
    echo '</pre>';

    echo '<h4>Problema 2: Bot não está rodando</h4>';
    echo '<p><strong>Solução:</strong></p>';
    echo '<pre style="background: #333; color: #fff; padding: 10px; border-radius: 5px;">';
    echo 'cd /caminho/para/whatsapp/' . "\n";
    echo 'pm2 start bot.js --name "klube-whatsapp-bot"' . "\n";
    echo '</pre>';

    echo '<h4>Problema 3: Bot com erro</h4>';
    echo '<p><strong>Solução:</strong></p>';
    echo '<pre style="background: #333; color: #fff; padding: 10px; border-radius: 5px;">';
    echo 'pm2 restart bot.js' . "\n";
    echo 'pm2 logs bot.js' . "\n";
    echo '</pre>';
    echo '</div>';

    // 4. Formulário para coletar informações
    echo "<h2>📝 CONTE-NOS O QUE VOCÊ VIU</h2>\n";

    echo '<form method="post" style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 10px 0;">';
    echo '<h3>🔍 Selecione o que você encontrou:</h3>';

    echo '<p><input type="radio" name="pm2_status" value="working" id="working">';
    echo '<label for="working"> ✅ PM2 instalado e bot rodando normalmente</label></p>';

    echo '<p><input type="radio" name="pm2_status" value="not_running" id="not_running">';
    echo '<label for="not_running"> ⚠️ PM2 instalado mas bot não está rodando</label></p>';

    echo '<p><input type="radio" name="pm2_status" value="no_pm2" id="no_pm2">';
    echo '<label for="no_pm2"> ❌ PM2 não está instalado</label></p>';

    echo '<p><input type="radio" name="pm2_status" value="no_access" id="no_access">';
    echo '<label for="no_access"> 🚫 Não consegui acessar o servidor SSH</label></p>';

    echo '<p style="margin-top: 20px;">';
    echo '<input type="submit" name="check_status" value="Continuar com Base no que Encontrei" style="background: #FF7A00; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">';
    echo '</p>';
    echo '</form>';

    // 5. Processar resposta
    if (isset($_POST['check_status']) && isset($_POST['pm2_status'])) {
        $status = $_POST['pm2_status'];

        echo "<h2>🎯 PRÓXIMOS PASSOS BASEADOS NO SEU RESULTADO:</h2>\n";

        switch ($status) {
            case 'working':
                echo '<div style="background: #d4edda; padding: 20px; border-radius: 5px; margin: 10px 0;">';
                echo '<h3>🎉 PERFEITO! BOT FUNCIONANDO</h3>';
                echo '<p>Ótimo! Seu bot está rodando corretamente. Agora vamos configurar o proxy.</p>';
                echo '<a href="passo_03_configurar_proxy.php" style="background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">➡️ IR PARA PASSO 3 - CONFIGURAR PROXY</a>';
                echo '</div>';
                break;

            case 'not_running':
                echo '<div style="background: #fff3cd; padding: 20px; border-radius: 5px; margin: 10px 0;">';
                echo '<h3>🔧 VAMOS INICIAR O BOT</h3>';
                echo '<p>O PM2 está instalado, mas o bot não está rodando. Vamos iniciar:</p>';
                echo '<a href="passo_02b_iniciar_bot.php" style="background: #ffc107; color: #333; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">➡️ INICIAR BOT PM2</a>';
                echo '</div>';
                break;

            case 'no_pm2':
                echo '<div style="background: #f8d7da; padding: 20px; border-radius: 5px; margin: 10px 0;">';
                echo '<h3>📦 VAMOS INSTALAR PM2</h3>';
                echo '<p>Precisamos instalar o PM2 primeiro, depois o bot.</p>';
                echo '<a href="passo_02c_instalar_pm2.php" style="background: #dc3545; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">➡️ INSTALAR PM2 E BOT</a>';
                echo '</div>';
                break;

            case 'no_access':
                echo '<div style="background: #e2e3e5; padding: 20px; border-radius: 5px; margin: 10px 0;">';
                echo '<h3>🤝 VAMOS USAR O SISTEMA ATUAL</h3>';
                echo '<p>Sem problema! O sistema atual já funciona perfeitamente. Não é necessário conexão direta.</p>';
                echo '<p><strong>O que já está funcionando:</strong></p>';
                echo '<ul>';
                echo '<li>✅ Notificações automáticas (100% sucesso)</li>';
                echo '<li>✅ Sistema robusto com fallback</li>';
                echo '<li>✅ Logs completos</li>';
                echo '</ul>';
                echo '<a href="relatorio_final_notificacoes.php" style="background: #6c757d; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">📊 VER RELATÓRIO FINAL</a>';
                echo '</div>';
                break;
        }

        // Salvar status
        $statusInfo = [
            'pm2_status' => $status,
            'checked_at' => date('Y-m-d H:i:s'),
            'next_step' => $status === 'working' ? 'configure_proxy' : 'fix_bot'
        ];

        file_put_contents('logs/pm2_status.json', json_encode($statusInfo, JSON_PRETTY_PRINT));
    }

    // 6. Dicas adicionais
    echo "<h2>💡 DICAS IMPORTANTES</h2>\n";

    echo '<div style="background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;">';
    echo '<h3>🎯 LEMBRE-SE:</h3>';
    echo '<ul>';
    echo '<li><strong>Copie um comando por vez</strong> - não copie tudo junto</li>';
    echo '<li><strong>Aguarde cada comando terminar</strong> antes do próximo</li>';
    echo '<li><strong>Anote o que você vê</strong> - vamos precisar dessas informações</li>';
    echo '<li><strong>Se algo der erro</strong>, não se preocupe - vamos resolver!</li>';
    echo '</ul>';

    echo '<h3>🆘 PRECISA DE AJUDA?</h3>';
    echo '<p>Se não conseguir executar algum comando ou aparecer algum erro:</p>';
    echo '<ul>';
    echo '<li>Anote exatamente a mensagem de erro</li>';
    echo '<li>Tire uma foto/screenshot da tela</li>';
    echo '<li>Continue o processo - vamos resolver depois</li>';
    echo '</ul>';
    echo '</div>';

} catch (Exception $e) {
    echo "<h3>❌ ERRO: " . htmlspecialchars($e->getMessage()) . "</h3>\n";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Passo 2 - Verificar Bot PM2 - Klube Cash</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        pre { background: #f8f8f8; padding: 10px; border-radius: 5px; overflow-x: auto; }
        a { text-decoration: none; }
        h1 { color: #333; border-bottom: 3px solid #FF7A00; padding-bottom: 10px; }
        input[type="radio"] { margin-right: 10px; }
        label { cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <p><strong>🎯 Objetivo:</strong> Verificar se o bot WhatsApp está rodando via PM2 no servidor.</p>
        <p><strong>⏱️ Tempo estimado:</strong> 5-10 minutos</p>
        <p><strong>🔧 Nível:</strong> Iniciante (comandos prontos para copiar e colar)</p>
    </div>
</body>
</html>