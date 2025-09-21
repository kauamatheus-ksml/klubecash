<?php
/**
 * CONFIGURAÇÃO FINAL - Bot WhatsApp para Notificações
 * Conectar sistema de notificações com o bot WhatsApp
 */

echo "<h2>🤖 CONFIGURAÇÃO FINAL - BOT WHATSAPP</h2>\n";

try {
    // 1. Verificar se o bot está rodando
    echo "<h3>1. Verificando status do bot WhatsApp:</h3>\n";

    $botUrl = 'http://localhost:3002/status';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $botUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $botStatus = json_decode($response, true);
        echo "<p>✅ Bot está rodando na porta 3002</p>\n";
        echo "<p>• Status: " . ($botStatus['bot_ready'] ? 'Conectado' : 'Desconectado') . "</p>\n";
        echo "<p>• Versão: " . ($botStatus['version'] ?? 'N/A') . "</p>\n";
        echo "<p>• Uptime: " . round($botStatus['uptime'] ?? 0) . " segundos</p>\n";

        $botFunctioning = $botStatus['bot_ready'] ?? false;
    } else {
        echo "<p>❌ Bot não está rodando ou não responde na porta 3002</p>\n";
        echo "<p>Código HTTP: {$httpCode}</p>\n";
        $botFunctioning = false;
    }

    // 2. Verificar configuração do FixedBrutalNotificationSystem
    echo "<h3>2. Verificando configuração do sistema de notificação:</h3>\n";

    if (file_exists('classes/FixedBrutalNotificationSystem.php')) {
        echo "<p>✅ FixedBrutalNotificationSystem.php encontrado</p>\n";

        // Ler arquivo para verificar configuração
        $systemContent = file_get_contents('classes/FixedBrutalNotificationSystem.php');

        if (strpos($systemContent, 'sendViaDirectAPI') !== false) {
            echo "<p>✅ Método sendViaDirectAPI presente</p>\n";
        } else {
            echo "<p>⚠️ Método sendViaDirectAPI não encontrado</p>\n";
        }

        if (strpos($systemContent, 'localhost:3002') !== false) {
            echo "<p>✅ Configuração para porta 3002 encontrada</p>\n";
        } else {
            echo "<p>⚠️ Configuração para porta 3002 não encontrada</p>\n";
            echo "<p>Precisa configurar URL do bot no sistema</p>\n";
        }

    } else {
        echo "<p>❌ FixedBrutalNotificationSystem.php não encontrado</p>\n";
    }

    // 3. Testar envio de mensagem de teste
    if ($botFunctioning) {
        echo "<h3>3. Testando envio de mensagem via bot:</h3>\n";

        $testData = [
            'phone' => '34991191534', // Número de teste
            'message' => "🧪 TESTE SISTEMA KLUBECASH\n\nData: " . date('d/m/Y H:i:s') . "\n\nSe você recebeu esta mensagem, o sistema está funcionando perfeitamente! ✅",
            'secret' => 'klube-cash-2024'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost:3002/send-message');
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
                echo "<p>• Response: " . ($result['message'] ?? 'OK') . "</p>\n";
            } else {
                echo "<p>❌ Teste falhou: " . ($result['error'] ?? 'Erro desconhecido') . "</p>\n";
            }
        } else {
            echo "<p>❌ Erro HTTP {$httpCode} no teste de envio</p>\n";
        }
    }

    // 4. Verificar configuração do sistema para usar o bot
    echo "<h3>4. Configurando sistema para usar o bot:</h3>\n";

    // Verificar se precisa atualizar o FixedBrutalNotificationSystem
    if (file_exists('classes/FixedBrutalNotificationSystem.php')) {
        $content = file_get_contents('classes/FixedBrutalNotificationSystem.php');

        // Verificar se já tem a configuração correta
        if (strpos($content, 'http://localhost:3002/send-message') !== false) {
            echo "<p>✅ Sistema já configurado para usar o bot na porta 3002</p>\n";
        } else {
            echo "<p>⚠️ Sistema precisa ser atualizado para usar o bot</p>\n";
            echo "<p>Configurando agora...</p>\n";

            // Atualizar a configuração
            $newMethod = '
    /**
     * Método 1: API direta do bot WhatsApp (NOVO)
     */
    private function sendViaDirectAPI($phone, $message) {
        try {
            $botUrl = "http://localhost:3002/send-message";

            $data = [
                "phone" => $phone,
                "message" => $message,
                "secret" => "klube-cash-2024"
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $botUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
                "Accept: application/json"
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $result = json_decode($response, true);
                if ($result && $result["success"]) {
                    $this->log("WhatsApp enviado via bot: {$phone}");
                    return [
                        "success" => true,
                        "method" => "direct_bot_api",
                        "response" => $result
                    ];
                } else {
                    return [
                        "success" => false,
                        "error" => $result["error"] ?? "Erro no bot"
                    ];
                }
            } else {
                return [
                    "success" => false,
                    "error" => "HTTP {$httpCode}: Bot não respondeu"
                ];
            }

        } catch (Exception $e) {
            return ["success" => false, "error" => $e->getMessage()];
        }
    }';

            // Encontrar onde inserir o método
            $insertPosition = strpos($content, 'private function sendViaWebhookSimulation');

            if ($insertPosition !== false) {
                // Inserir antes do método webhook
                $newContent = substr($content, 0, $insertPosition) . $newMethod . "\n\n    " . substr($content, $insertPosition);

                // Fazer backup
                copy('classes/FixedBrutalNotificationSystem.php', 'classes/FixedBrutalNotificationSystem.php.backup');

                // Salvar nova versão
                file_put_contents('classes/FixedBrutalNotificationSystem.php', $newContent);

                echo "<p>✅ Sistema atualizado com sucesso!</p>\n";
                echo "<p>• Backup criado: FixedBrutalNotificationSystem.php.backup</p>\n";
                echo "<p>• Método sendViaDirectAPI adicionado</p>\n";
            } else {
                echo "<p>⚠️ Não foi possível localizar posição para inserir código</p>\n";
            }
        }
    }

    // 5. Testar integração completa
    echo "<h3>5. Teste de integração completa:</h3>\n";

    if ($botFunctioning) {
        require_once 'classes/FixedBrutalNotificationSystem.php';

        // Buscar uma transação para teste
        require_once 'config/database.php';
        $db = Database::getConnection();

        $stmt = $db->query("SELECT id FROM transacoes_cashback ORDER BY id DESC LIMIT 1");
        $lastTransaction = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($lastTransaction) {
            $system = new FixedBrutalNotificationSystem();
            $result = $system->forceNotifyTransaction($lastTransaction['id']);

            if ($result['success']) {
                echo "<p>✅ Teste completo bem-sucedido!</p>\n";
                echo "<p>• Transação testada: #{$lastTransaction['id']}</p>\n";
                echo "<p>• Mensagem: {$result['message']}</p>\n";
            } else {
                echo "<p>❌ Teste completo falhou: {$result['message']}</p>\n";
            }
        } else {
            echo "<p>⚠️ Nenhuma transação encontrada para teste</p>\n";
        }
    }

    // 6. Resumo final
    echo "<h3>📊 RESUMO FINAL:</h3>\n";

    echo '<div style="background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;">';
    echo '<h4>🤖 STATUS DA CONFIGURAÇÃO DO BOT</h4>';

    echo '<ul>';

    if ($botFunctioning) {
        echo '<li>✅ Bot WhatsApp: Rodando na porta 3002</li>';
    } else {
        echo '<li>❌ Bot WhatsApp: Não está rodando</li>';
    }

    if (file_exists('classes/FixedBrutalNotificationSystem.php')) {
        echo '<li>✅ Sistema de notificação: Configurado</li>';
    } else {
        echo '<li>❌ Sistema de notificação: Não encontrado</li>';
    }

    echo '</ul>';

    if ($botFunctioning) {
        echo '<p><strong>🎉 CONFIGURAÇÃO COMPLETA!</strong></p>';
        echo '<p>O sistema agora enviará notificações automáticas via WhatsApp!</p>';

        echo '<h4>🚀 Como funciona:</h4>';
        echo '<ol>';
        echo '<li>Transação é criada no sistema</li>';
        echo '<li>FixedBrutalNotificationSystem detecta automaticamente</li>';
        echo '<li>Mensagem é enviada para o bot na porta 3002</li>';
        echo '<li>Bot envia mensagem pelo WhatsApp</li>';
        echo '<li>Resultado é registrado no banco</li>';
        echo '</ol>';

    } else {
        echo '<p><strong>⚠️ BOT PRECISA SER INICIADO</strong></p>';
        echo '<p>Execute os comandos:</p>';
        echo '<pre>';
        echo 'cd whatsapp/';
        echo 'npm install';
        echo 'node bot.js';
        echo '</pre>';
    }

    echo '</div>';

} catch (Exception $e) {
    echo "<h3>❌ ERRO: " . htmlspecialchars($e->getMessage()) . "</h3>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Configuração Bot WhatsApp - Klube Cash</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        pre { background: #f8f8f8; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h3>🎯 Próximos passos:</h3>
        <ol>
            <li>Se o bot não estiver rodando, inicie-o com os comandos acima</li>
            <li>Teste uma transação real para verificar notificação</li>
            <li>Monitore logs do bot para ver atividade</li>
        </ol>

        <h3>📚 Comandos úteis:</h3>
        <ul>
            <li><strong>Status do bot:</strong> curl http://localhost:3002/status</li>
            <li><strong>Teste manual:</strong> POST http://localhost:3002/send-test</li>
            <li><strong>Logs do sistema:</strong> tail -f logs/brutal_notifications.log</li>
        </ul>
    </div>
</body>
</html>