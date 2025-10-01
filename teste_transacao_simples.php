<?php
/**
 * TESTE SIMPLES - CRIAÇÃO DE TRANSAÇÃO E NOTIFICAÇÃO
 * Testa criação direta de transação e disparo de notificação
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/ImmediateNotificationSystem.php';

echo "🚀 TESTE SIMPLES - CRIAÇÃO DE TRANSAÇÃO E NOTIFICAÇÃO\n\n";

try {
    $db = Database::getConnection();

    // 1. Verificar usuário de teste
    echo "1️⃣ Verificando usuário de teste...\n";
    $userStmt = $db->prepare("SELECT * FROM usuarios WHERE telefone LIKE '%34991191534%' LIMIT 1");
    $userStmt->execute();
    $testUser = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$testUser) {
        echo "❌ Usuário com telefone 34991191534 não encontrado\n";
        exit(1);
    }

    echo "✅ Usuário: {$testUser['nome']} (ID: {$testUser['id']}, Tel: {$testUser['telefone']})\n";

    // 2. Verificar loja
    $storeStmt = $db->prepare("SELECT * FROM lojas LIMIT 1");
    $storeStmt->execute();
    $testStore = $storeStmt->fetch(PDO::FETCH_ASSOC);

    if (!$testStore) {
        echo "❌ Nenhuma loja encontrada\n";
        exit(1);
    }

    echo "✅ Loja: {$testStore['nome_fantasia']} (ID: {$testStore['id']})\n";

    // 3. Criar transação diretamente no banco
    echo "\n2️⃣ Criando transação diretamente...\n";

    $codigo = 'SIMPLE_TEST_' . time();
    $valor = 89.90;
    $cashback = 8.99;

    $insertStmt = $db->prepare("
        INSERT INTO transacoes_cashback
        (codigo_transacao, usuario_id, loja_id, valor_total, valor_cliente, valor_cashback,
         valor_admin, valor_loja, status, descricao, data_transacao, data_criacao_usuario)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    $success = $insertStmt->execute([
        $codigo,
        $testUser['id'],
        $testStore['id'],
        $valor,
        $cashback, // valor_cliente
        $cashback, // valor_cashback (mesmo valor)
        0.00, // valor_admin
        0.00, // valor_loja
        'pendente',
        'Teste simples de notificação automática'
    ]);

    if (!$success) {
        echo "❌ Erro ao criar transação\n";
        exit(1);
    }

    $transactionId = $db->lastInsertId();
    echo "✅ Transação criada: ID {$transactionId}\n";
    echo "Código: {$codigo}\n";
    echo "Valor: R$ {$valor}\n";
    echo "Cashback: R$ {$cashback}\n";

    // 4. Aguardar um pouco
    echo "\n3️⃣ Aguardando um momento...\n";
    sleep(1);

    // 5. Disparar notificação manual
    echo "\n4️⃣ Disparando notificação manual...\n";

    $start = microtime(true);
    $notificationSystem = new ImmediateNotificationSystem();
    $result = $notificationSystem->sendImmediateNotification($transactionId);
    $end = microtime(true);

    $totalTime = round(($end - $start) * 1000, 2);

    echo "Tempo de execução: {$totalTime}ms\n";

    // 6. Verificar resultado
    echo "\n5️⃣ Resultado da notificação:\n";

    if ($result['success']) {
        echo "✅ SUCESSO!\n";
        echo "Método usado: " . ($result['method_used'] ?? 'N/A') . "\n";

        if (isset($result['all_results'])) {
            echo "\nDetalhes por método:\n";
            foreach ($result['all_results'] as $method => $methodResult) {
                $status = $methodResult['success'] ? "✅" : "❌";
                $time = $methodResult['response_time_ms'] ?? 'N/A';
                $error = $methodResult['success'] ? '' : ' - ' . ($methodResult['error'] ?? 'Erro desconhecido');
                echo "  {$method}: {$status} ({$time}ms){$error}\n";
            }
        }
    } else {
        echo "❌ FALHOU\n";
        echo "Erro: " . ($result['message'] ?? 'Erro desconhecido') . "\n";
    }

    // 7. Verificar registro no banco
    echo "\n6️⃣ Verificando registro no banco...\n";

    $logStmt = $db->prepare("
        SELECT * FROM whatsapp_logs
        WHERE JSON_EXTRACT(additional_data, '$.transaction_id') = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $logStmt->execute([$transactionId]);
    $dbLog = $logStmt->fetch(PDO::FETCH_ASSOC);

    if ($dbLog) {
        echo "✅ Registro encontrado:\n";
        echo "  Log ID: {$dbLog['id']}\n";
        echo "  Sucesso: " . ($dbLog['success'] ? 'Sim' : 'Não') . "\n";
        echo "  Data: {$dbLog['created_at']}\n";

        $metadata = json_decode($dbLog['additional_data'], true);
        if ($metadata && isset($metadata['success_methods'])) {
            echo "  Métodos bem-sucedidos: " . implode(', ', $metadata['success_methods']) . "\n";
        }
    } else {
        echo "⚠️ Nenhum registro encontrado no banco\n";
    }

    // 8. Verificar logs em arquivo
    echo "\n7️⃣ Verificando logs em arquivo...\n";

    $logFile = __DIR__ . '/logs/immediate_notifications.log';
    if (file_exists($logFile)) {
        $logContent = file_get_contents($logFile);
        $lines = explode("\n", $logContent);
        $recentLines = array_slice($lines, -8);

        echo "Últimas entradas do log:\n";
        foreach ($recentLines as $line) {
            if (trim($line) && strpos($line, $transactionId) !== false) {
                echo "  ⭐ " . trim($line) . "\n";
            } elseif (trim($line)) {
                echo "    " . trim($line) . "\n";
            }
        }
    } else {
        echo "⚠️ Arquivo de log não encontrado\n";
    }

    // 9. Resumo final
    echo "\n🎯 RESUMO:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ Transação criada: ID {$transactionId}\n";
    echo "⏱️ Tempo notificação: {$totalTime}ms\n";
    echo "📱 Notificação: " . ($result['success'] ? "✅ ENVIADA" : "❌ FALHOU") . "\n";
    echo "🗄️ Banco atualizado: " . ($dbLog ? "✅ SIM" : "❌ NÃO") . "\n";

    if ($result['success']) {
        echo "\n🎉 TESTE CONCLUÍDO COM SUCESSO!\n";
        echo "O sistema de notificação imediata está funcionando perfeitamente.\n";
        echo "Mensagem foi enviada para: {$testUser['telefone']}\n";
    } else {
        echo "\n⚠️ TESTE FALHOU\n";
        echo "Verifique os logs para mais detalhes.\n";
    }

} catch (Exception $e) {
    echo "❌ ERRO CRÍTICO: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>