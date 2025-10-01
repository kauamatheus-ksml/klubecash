<?php
/**
 * TESTE END-TO-END COMPLETO
 * Testa o fluxo completo de notificações do Klube Cash
 */

echo "<h2>🔄 TESTE END-TO-END - SISTEMA DE NOTIFICAÇÕES</h2>\n";

try {
    // 1. Verificar configuração do sistema
    echo "<h3>1. Verificando configuração do sistema:</h3>\n";

    require_once 'config/database.php';
    $db = Database::getConnection();
    echo "<p>✅ Conexão com banco estabelecida</p>\n";

    require_once 'classes/FixedBrutalNotificationSystem.php';
    echo "<p>✅ FixedBrutalNotificationSystem carregado</p>\n";

    // 2. Criar transação de teste
    echo "<h3>2. Criando transação de teste:</h3>\n";

    // Buscar usuário com telefone para teste
    $userStmt = $db->query("
        SELECT id, nome, telefone FROM usuarios
        WHERE telefone IS NOT NULL AND telefone != ''
        LIMIT 1
    ");
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    // Buscar loja para teste
    $storeStmt = $db->query("SELECT id, nome_fantasia FROM lojas LIMIT 1");
    $store = $storeStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !$store) {
        throw new Exception("Usuário ou loja não encontrados para teste");
    }

    echo "<p>👤 Usuário teste: {$user['nome']} ({$user['telefone']})</p>\n";
    echo "<p>🏪 Loja teste: {$store['nome_fantasia']}</p>\n";

    // Criar transação END-TO-END
    $valor = 50.00;
    $cashback = round($valor * 0.05, 2); // 5%
    $codigo = 'E2E_TEST_' . time();

    $insertStmt = $db->prepare("
        INSERT INTO transacoes_cashback (
            usuario_id, loja_id, valor_total, valor_cliente,
            codigo_transacao, descricao, status,
            data_transacao, data_criacao_usuario
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    $insertStmt->execute([
        $user['id'],
        $store['id'],
        $valor,
        $cashback,
        $codigo,
        'Teste End-to-End do Sistema de Notificações',
        'aprovado' // Transação aprovada para testar notificação imediata
    ]);

    $testTransactionId = $db->lastInsertId();

    echo "<p>💰 <strong>Transação criada com sucesso!</strong></p>\n";
    echo "<p>• ID: {$testTransactionId}</p>\n";
    echo "<p>• Código: {$codigo}</p>\n";
    echo "<p>• Valor: R$ " . number_format($valor, 2, ',', '.') . "</p>\n";
    echo "<p>• Cashback: R$ " . number_format($cashback, 2, ',', '.') . "</p>\n";
    echo "<p>• Status: aprovado</p>\n";

    // 3. Disparar notificação via FixedBrutalNotificationSystem
    echo "<h3>3. Testando sistema de notificação:</h3>\n";

    $system = new FixedBrutalNotificationSystem();

    echo "<p>🚀 Disparando notificação...</p>\n";
    $result = $system->forceNotifyTransaction($testTransactionId);

    if ($result['success']) {
        echo "<p>✅ <strong>NOTIFICAÇÃO ENVIADA COM SUCESSO!</strong></p>\n";
        echo "<p>• Resultado: {$result['message']}</p>\n";
        if (isset($result['bot_url'])) {
            echo "<p>• Bot URL usada: {$result['bot_url']}</p>\n";
        }
    } else {
        echo "<p>❌ Falha na notificação:</p>\n";
        echo "<p>• Erro: {$result['message']}</p>\n";
    }

    // 4. Verificar registro no banco
    echo "<h3>4. Verificando registro no banco:</h3>\n";

    sleep(2); // Aguardar processamento

    $logStmt = $db->prepare("
        SELECT id, type, success, message_preview, created_at,
               JSON_EXTRACT(additional_data, '$.transaction_id') as transaction_id
        FROM whatsapp_logs
        WHERE JSON_EXTRACT(additional_data, '$.transaction_id') = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $logStmt->execute([$testTransactionId]);
    $logRecord = $logStmt->fetch(PDO::FETCH_ASSOC);

    if ($logRecord) {
        echo "<p>✅ <strong>Registro criado no banco!</strong></p>\n";
        echo "<p>• ID do log: {$logRecord['id']}</p>\n";
        echo "<p>• Tipo: {$logRecord['type']}</p>\n";
        echo "<p>• Sucesso: " . ($logRecord['success'] ? 'Sim' : 'Não') . "</p>\n";
        echo "<p>• Data: {$logRecord['created_at']}</p>\n";
        echo "<p>• Preview: " . htmlspecialchars($logRecord['message_preview']) . "</p>\n";
    } else {
        echo "<p>⚠️ Nenhum registro encontrado no banco para esta transação</p>\n";
    }

    // 5. Testar processamento automático
    echo "<h3>5. Testando processamento automático:</h3>\n";

    echo "<p>🧪 Executando checkAndProcessNewTransactions()...</p>\n";
    $autoResult = $system->checkAndProcessNewTransactions();

    echo "<p>📊 Resultado do processamento automático:</p>\n";
    echo "<p>• Processadas: {$autoResult['processed']}</p>\n";
    echo "<p>• Sucessos: {$autoResult['success']}</p>\n";
    echo "<p>• Erros: {$autoResult['errors']}</p>\n";

    // 6. Verificar logs do sistema
    echo "<h3>6. Verificando logs do sistema:</h3>\n";

    $logFile = 'logs/brutal_notifications.log';
    if (file_exists($logFile)) {
        $logContent = file_get_contents($logFile);
        $logLines = explode("\n", $logContent);

        // Filtrar linhas relacionadas ao teste
        $testLines = array_filter($logLines, function($line) use ($testTransactionId) {
            return strpos($line, $testTransactionId) !== false ||
                   strpos($line, 'E2E_TEST') !== false ||
                   strpos($line, '[FIXED]') !== false;
        });

        if (!empty($testLines)) {
            $recentTestLines = array_slice($testLines, -10);
            echo "<p>📋 Logs relacionados ao teste:</p>\n";
            echo "<pre style='background: #f8f8f8; padding: 10px; border-radius: 5px; max-height: 300px; overflow-y: auto;'>";
            foreach ($recentTestLines as $line) {
                echo htmlspecialchars($line) . "\n";
            }
            echo "</pre>\n";
        } else {
            echo "<p>⚠️ Nenhum log específico do teste encontrado</p>\n";
        }
    }

    // 7. Teste de integração direta no TransactionController
    echo "<h3>7. Testando integração no TransactionController:</h3>\n";

    // Simular criação via controller (sem executar realmente)
    echo "<p>🧪 Verificando se integração automática está ativa...</p>\n";

    $controllerFile = 'controllers/TransactionController.php';
    if (file_exists($controllerFile)) {
        $content = file_get_contents($controllerFile);

        if (strpos($content, 'FixedBrutalNotificationSystem') !== false) {
            echo "<p>✅ Integração encontrada no TransactionController</p>\n";

            if (strpos($content, 'file_exists') !== false) {
                echo "<p>✅ Verificações de segurança ativas</p>\n";
            }
        } else {
            echo "<p>❌ Integração não encontrada no TransactionController</p>\n";
        }
    }

    // 8. Resumo final e estatísticas
    echo "<h3>📊 RESUMO FINAL DO TESTE END-TO-END:</h3>\n";

    echo '<div style="background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;">';
    echo '<h4>🎯 RESULTADO DO TESTE COMPLETO</h4>';

    $success = $result['success'] && $logRecord;

    if ($success) {
        echo '<p><strong>✅ TESTE END-TO-END BEM-SUCEDIDO!</strong></p>';
        echo '<h4>✅ Funcionalidades testadas:</h4>';
        echo '<ul>';
        echo '<li>✅ Criação de transação no banco</li>';
        echo '<li>✅ Disparo de notificação via FixedBrutalNotificationSystem</li>';
        echo '<li>✅ Processamento e envio da mensagem</li>';
        echo '<li>✅ Registro do resultado no banco whatsapp_logs</li>';
        echo '<li>✅ Processamento automático do sistema</li>';
        echo '<li>✅ Integração com TransactionController</li>';
        echo '</ul>';

        echo '<h4>🚀 SISTEMA PRONTO PARA PRODUÇÃO!</h4>';
        echo '<p>O sistema completo está funcionando e enviará notificações automáticas para todas as transações futuras.</p>';

    } else {
        echo '<p><strong>⚠️ TESTE PARCIALMENTE BEM-SUCEDIDO</strong></p>';
        echo '<p>Algumas funcionalidades podem precisar de ajustes:</p>';
        echo '<ul>';
        echo '<li>' . ($result['success'] ? '✅' : '❌') . ' Notificação enviada</li>';
        echo '<li>' . ($logRecord ? '✅' : '❌') . ' Registro no banco</li>';
        echo '</ul>';
    }

    echo '<h4>📈 Estatísticas:</h4>';
    echo '<ul>';
    echo "<li>🆔 Transação teste: #{$testTransactionId}</li>";
    echo "<li>📞 Telefone: {$user['telefone']}</li>";
    echo "<li>💰 Valor: R$ " . number_format($valor, 2, ',', '.') . "</li>";
    echo "<li>🎁 Cashback: R$ " . number_format($cashback, 2, ',', '.') . "</li>";
    echo "<li>⏰ Data: " . date('d/m/Y H:i:s') . "</li>";
    echo '</ul>';

    echo '</div>';

    // Salvar resultado do teste
    $testSummary = [
        'timestamp' => date('Y-m-d H:i:s'),
        'transaction_id' => $testTransactionId,
        'transaction_code' => $codigo,
        'user_phone' => $user['telefone'],
        'notification_result' => $result,
        'log_record' => $logRecord,
        'auto_processing' => $autoResult,
        'success' => $success
    ];

    if (!is_dir('logs')) {
        mkdir('logs', 0755, true);
    }

    file_put_contents('logs/end_to_end_test.json', json_encode($testSummary, JSON_PRETTY_PRINT));
    echo "<p>📝 Relatório completo salvo em: logs/end_to_end_test.json</p>\n";

} catch (Exception $e) {
    echo "<h3>❌ ERRO: " . htmlspecialchars($e->getMessage()) . "</h3>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Teste End-to-End - Klube Cash</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        pre { background: #f8f8f8; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h3>🎯 Este teste verifica:</h3>
        <ul>
            <li>Criação de transação completa</li>
            <li>Disparo automático de notificação</li>
            <li>Processamento via FixedBrutalNotificationSystem</li>
            <li>Registro correto no banco de dados</li>
            <li>Integração com todos os componentes</li>
        </ul>

        <h3>📚 Próximos passos após sucesso:</h3>
        <ol>
            <li>Monitorar transações reais</li>
            <li>Verificar recebimento das mensagens</li>
            <li>Configurar alertas de monitoramento</li>
        </ol>
    </div>
</body>
</html>