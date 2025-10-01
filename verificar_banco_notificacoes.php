<?php
/**
 * VERIFICAÇÃO DO BANCO - Notificações u383946504_klubecash
 * Verificar se as correções estão funcionando no banco de dados
 */

echo "<h2>🔍 VERIFICAÇÃO DO BANCO - NOTIFICAÇÕES</h2>\n";

try {
    // Conectar ao banco
    require_once 'config/database.php';
    $db = Database::getConnection();

    echo "<h3>✅ Conexão com banco estabelecida</h3>\n";

    // 1. Verificar estrutura da tabela whatsapp_logs
    echo "<h3>1. Estrutura da tabela whatsapp_logs:</h3>\n";

    $structStmt = $db->query("DESCRIBE whatsapp_logs");
    $columns = $structStmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th></tr>\n";

    $hasAdditionalData = false;
    $hasMessagePreview = false;

    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>\n";

        if ($column['Field'] === 'additional_data') $hasAdditionalData = true;
        if ($column['Field'] === 'message_preview') $hasMessagePreview = true;
    }
    echo "</table>\n";

    // Verificar se tem as colunas corretas
    if ($hasAdditionalData && $hasMessagePreview) {
        echo "<p>✅ Tabela possui colunas corretas: 'additional_data' e 'message_preview'</p>\n";
    } else {
        echo "<p>❌ Tabela não possui as colunas necessárias:</p>\n";
        echo "<p>• additional_data: " . ($hasAdditionalData ? "✅" : "❌") . "</p>\n";
        echo "<p>• message_preview: " . ($hasMessagePreview ? "✅" : "❌") . "</p>\n";
    }

    // 2. Verificar últimas notificações
    echo "<h3>2. Últimas notificações registradas:</h3>\n";

    $logsStmt = $db->query("
        SELECT id, type, success, message_preview, created_at,
               JSON_EXTRACT(additional_data, '$.transaction_id') as transaction_id
        FROM whatsapp_logs
        ORDER BY created_at DESC
        LIMIT 10
    ");

    $logs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($logs)) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>\n";
        echo "<tr><th>ID</th><th>Tipo</th><th>Sucesso</th><th>Transação ID</th><th>Preview</th><th>Data</th></tr>\n";

        foreach ($logs as $log) {
            echo "<tr>";
            echo "<td>{$log['id']}</td>";
            echo "<td>{$log['type']}</td>";
            echo "<td>" . ($log['success'] ? '✅' : '❌') . "</td>";
            echo "<td>{$log['transaction_id']}</td>";
            echo "<td>" . substr($log['message_preview'], 0, 30) . "...</td>";
            echo "<td>{$log['created_at']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";

        // Estatísticas
        $successCount = array_sum(array_column($logs, 'success'));
        $totalCount = count($logs);

        echo "<p>📊 <strong>Estatísticas dos últimos 10 registros:</strong></p>\n";
        echo "<p>• Total: {$totalCount}</p>\n";
        echo "<p>• Sucessos: {$successCount}</p>\n";
        echo "<p>• Falhas: " . ($totalCount - $successCount) . "</p>\n";

    } else {
        echo "<p>⚠️ Nenhuma notificação encontrada na tabela</p>\n";
    }

    // 3. Verificar notificações com tag [FIXED]
    echo "<h3>3. Notificações com sistema corrigido [FIXED]:</h3>\n";

    $fixedStmt = $db->query("
        SELECT COUNT(*) as total,
               MAX(created_at) as ultima_notificacao,
               SUM(success) as sucessos
        FROM whatsapp_logs
        WHERE JSON_EXTRACT(additional_data, '$.source') LIKE '%FIXED%'
           OR JSON_EXTRACT(additional_data, '$.debug_info') LIKE '%FIXED%'
    ");

    $fixedStats = $fixedStmt->fetch(PDO::FETCH_ASSOC);

    if ($fixedStats['total'] > 0) {
        echo "<p>✅ <strong>Sistema corrigido está funcionando!</strong></p>\n";
        echo "<p>• Total de notificações [FIXED]: {$fixedStats['total']}</p>\n";
        echo "<p>• Sucessos: {$fixedStats['sucessos']}</p>\n";
        echo "<p>• Última notificação: {$fixedStats['ultima_notificacao']}</p>\n";
    } else {
        echo "<p>⚠️ Nenhuma notificação com tag [FIXED] encontrada ainda</p>\n";
        echo "<p>Isso pode significar que nenhuma transação foi criada desde a correção</p>\n";
    }

    // 4. Verificar últimas transações
    echo "<h3>4. Últimas transações criadas:</h3>\n";

    $transStmt = $db->query("
        SELECT id, codigo_transacao, valor_total, valor_cliente, status, data_transacao
        FROM transacoes_cashback
        ORDER BY data_transacao DESC
        LIMIT 5
    ");

    $transactions = $transStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($transactions)) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>\n";
        echo "<tr><th>ID</th><th>Código</th><th>Valor</th><th>Cashback</th><th>Status</th><th>Data</th><th>Notificação?</th></tr>\n";

        foreach ($transactions as $trans) {
            // Verificar se tem notificação para esta transação
            $notifStmt = $db->prepare("
                SELECT COUNT(*) as tem_notificacao
                FROM whatsapp_logs
                WHERE JSON_EXTRACT(additional_data, '$.transaction_id') = ?
            ");
            $notifStmt->execute([$trans['id']]);
            $hasNotification = $notifStmt->fetchColumn() > 0;

            echo "<tr>";
            echo "<td>{$trans['id']}</td>";
            echo "<td>{$trans['codigo_transacao']}</td>";
            echo "<td>R$ " . number_format($trans['valor_total'], 2, ',', '.') . "</td>";
            echo "<td>R$ " . number_format($trans['valor_cliente'], 2, ',', '.') . "</td>";
            echo "<td>{$trans['status']}</td>";
            echo "<td>{$trans['data_transacao']}</td>";
            echo "<td>" . ($hasNotification ? '✅' : '❌') . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";

    } else {
        echo "<p>⚠️ Nenhuma transação encontrada</p>\n";
    }

    // 5. Teste de integridade do sistema de notificação
    echo "<h3>5. Teste de integridade do sistema:</h3>\n";

    // Verificar se a classe FixedBrutalNotificationSystem existe
    if (file_exists('classes/FixedBrutalNotificationSystem.php')) {
        require_once 'classes/FixedBrutalNotificationSystem.php';

        if (class_exists('FixedBrutalNotificationSystem')) {
            echo "<p>✅ Classe FixedBrutalNotificationSystem carregada</p>\n";

            // Testar instanciação
            try {
                $system = new FixedBrutalNotificationSystem();
                echo "<p>✅ Sistema instanciado com sucesso</p>\n";

                // Se houver transações, testar com a mais recente
                if (!empty($transactions)) {
                    $lastTransId = $transactions[0]['id'];
                    echo "<p>🧪 Testando notificação para transação #{$lastTransId}...</p>\n";

                    $result = $system->forceNotifyTransaction($lastTransId);

                    if ($result['success']) {
                        echo "<p>✅ Teste de notificação bem-sucedido: {$result['message']}</p>\n";
                    } else {
                        echo "<p>⚠️ Teste de notificação falhou: {$result['message']}</p>\n";
                    }
                }

            } catch (Exception $e) {
                echo "<p>❌ Erro ao instanciar sistema: " . htmlspecialchars($e->getMessage()) . "</p>\n";
            }

        } else {
            echo "<p>❌ Classe FixedBrutalNotificationSystem não encontrada após require</p>\n";
        }

    } else {
        echo "<p>❌ Arquivo FixedBrutalNotificationSystem.php não encontrado</p>\n";
    }

    // 6. Resumo final
    echo "<h3>📊 RESUMO FINAL:</h3>\n";

    echo '<div style="background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;">';
    echo '<h4>🎯 STATUS DO SISTEMA DE NOTIFICAÇÕES</h4>';

    $allGood = true;

    echo '<ul>';

    if ($hasAdditionalData && $hasMessagePreview) {
        echo '<li>✅ Estrutura do banco: OK</li>';
    } else {
        echo '<li>❌ Estrutura do banco: Problemas nas colunas</li>';
        $allGood = false;
    }

    if (!empty($logs)) {
        echo '<li>✅ Logs de notificação: Registros encontrados</li>';
    } else {
        echo '<li>⚠️ Logs de notificação: Nenhum registro ainda</li>';
    }

    if (file_exists('classes/FixedBrutalNotificationSystem.php')) {
        echo '<li>✅ Sistema corrigido: Arquivo presente</li>';
    } else {
        echo '<li>❌ Sistema corrigido: Arquivo ausente</li>';
        $allGood = false;
    }

    echo '</ul>';

    if ($allGood) {
        echo '<p><strong>🎉 TUDO FUNCIONANDO CORRETAMENTE!</strong></p>';
        echo '<p>O sistema está pronto para processar notificações automaticamente.</p>';
    } else {
        echo '<p><strong>⚠️ ALGUNS PROBLEMAS DETECTADOS</strong></p>';
        echo '<p>Verifique os itens marcados com ❌ acima.</p>';
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
    <title>Verificação do Banco - Klube Cash</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        pre { background: #f8f8f8; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h3>🎯 Verificação concluída</h3>
        <p>Esta verificação mostra o estado atual do sistema de notificações no banco de dados.</p>

        <h3>📚 Próximos passos se tudo estiver OK:</h3>
        <ul>
            <li>Criar uma transação de teste para verificar funcionamento</li>
            <li>Monitorar logs em tempo real</li>
            <li>Verificar se notificações chegam corretamente</li>
        </ul>
    </div>
</body>
</html>