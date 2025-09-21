<?php
/**
 * TESTE TRANSAÇÃO COMPLETA END-TO-END
 * Simula uma transação sendo criada e verifica se notificação é enviada imediatamente
 */

require_once __DIR__ . '/controllers/TransactionController.php';
require_once __DIR__ . '/config/database.php';

echo "🚀 TESTE TRANSAÇÃO COMPLETA END-TO-END\n\n";

try {
    // 1. Verificar dependências
    echo "1️⃣ Verificando dependências...\n";

    $db = Database::getConnection();

    // Verificar usuário de teste
    $userStmt = $db->prepare("SELECT * FROM usuarios WHERE telefone = '34991191534' LIMIT 1");
    $userStmt->execute();
    $testUser = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$testUser) {
        echo "❌ Usuário de teste não encontrado\n";
        echo "💡 Crie um usuário com telefone: 34991191534\n";
        exit(1);
    }

    echo "✅ Usuário de teste: {$testUser['nome']} (ID: {$testUser['id']})\n";

    // Verificar loja de teste
    $storeStmt = $db->prepare("SELECT * FROM lojas LIMIT 1");
    $storeStmt->execute();
    $testStore = $storeStmt->fetch(PDO::FETCH_ASSOC);

    if (!$testStore) {
        echo "❌ Nenhuma loja encontrada\n";
        exit(1);
    }

    echo "✅ Loja de teste: {$testStore['nome_fantasia']} (ID: {$testStore['id']})\n";

    // 2. Preparar dados da transação
    echo "\n2️⃣ Preparando transação de teste...\n";

    $transactionData = [
        'codigo_transacao' => 'E2E_TEST_' . time(),
        'usuario_id' => $testUser['id'],
        'loja_id' => $testStore['id'],
        'valor_total' => 75.50,
        'valor_cliente' => 7.55, // 10% de cashback
        'metodo_pagamento' => 'Dinheiro',
        'observacao' => 'Teste end-to-end do sistema de notificação imediata',
        'usar_saldo' => false,
        'valor_saldo_usado' => 0
    ];

    echo "Código: {$transactionData['codigo_transacao']}\n";
    echo "Valor: R$ {$transactionData['valor_total']}\n";
    echo "Cashback: R$ {$transactionData['valor_cliente']}\n";

    // 3. Configurar captura de logs
    echo "\n3️⃣ Configurando monitoramento...\n";

    $logFile = __DIR__ . '/logs/immediate_notifications.log';
    $logSizeBefore = file_exists($logFile) ? filesize($logFile) : 0;

    echo "Log file: {$logFile}\n";
    echo "Tamanho inicial: {$logSizeBefore} bytes\n";

    // Contar registros no banco antes
    $countStmt = $db->prepare("SELECT COUNT(*) FROM whatsapp_logs WHERE type = 'immediate_notification'");
    $countStmt->execute();
    $logCountBefore = $countStmt->fetchColumn();
    echo "Registros no banco antes: {$logCountBefore}\n";

    // 4. Executar transação
    echo "\n4️⃣ 🚀 CRIANDO TRANSAÇÃO COM NOTIFICAÇÃO AUTOMÁTICA...\n";
    echo "Timestamp início: " . date('Y-m-d H:i:s') . "\n";

    $start = microtime(true);

    // Simular requisição POST (como viria do formulário)
    $_POST = array_merge($transactionData, ['action' => 'register']);

    // Capturar output
    ob_start();
    $result = TransactionController::registerTransaction($transactionData);
    $output = ob_get_clean();

    $end = microtime(true);
    $totalTime = round(($end - $start) * 1000, 2);

    echo "Timestamp fim: " . date('Y-m-d H:i:s') . "\n";
    echo "Tempo total: {$totalTime}ms\n";

    // 5. Verificar resultado da transação
    echo "\n5️⃣ Verificando resultado da transação...\n";

    if ($result && $result['status']) {
        echo "✅ Transação criada com sucesso!\n";
        echo "ID da transação: {$result['transaction_id']}\n";
        echo "Mensagem: {$result['message']}\n";

        $newTransactionId = $result['transaction_id'];
    } else {
        echo "❌ Erro ao criar transação\n";
        echo "Resultado: " . print_r($result, true) . "\n";
        echo "Output: {$output}\n";
        exit(1);
    }

    // 6. Verificar se notificação foi processada
    echo "\n6️⃣ Verificando notificação automática...\n";

    // Aguardar um pouco para processos assíncronos
    sleep(2);

    // Verificar logs
    $logSizeAfter = file_exists($logFile) ? filesize($logFile) : 0;
    $logGrowth = $logSizeAfter - $logSizeBefore;

    echo "Tamanho do log após: {$logSizeAfter} bytes\n";
    echo "Crescimento: {$logGrowth} bytes\n";

    if ($logGrowth > 0) {
        echo "✅ Log foi atualizado (notificação processada)\n";

        // Mostrar últimas linhas do log
        if (file_exists($logFile)) {
            $logContent = file_get_contents($logFile);
            $lines = explode("\n", $logContent);
            $recentLines = array_slice($lines, -15); // Últimas 15 linhas

            echo "\n📄 Últimas entradas do log:\n";
            foreach ($recentLines as $line) {
                if (trim($line) && strpos($line, $newTransactionId) !== false) {
                    echo "  ⭐ " . trim($line) . "\n";
                } elseif (trim($line)) {
                    echo "    " . trim($line) . "\n";
                }
            }
        }
    } else {
        echo "⚠️ Log não foi atualizado\n";
    }

    // Verificar banco de dados
    $countStmt = $db->prepare("SELECT COUNT(*) FROM whatsapp_logs WHERE type = 'immediate_notification'");
    $countStmt->execute();
    $logCountAfter = $countStmt->fetchColumn();
    $newLogs = $logCountAfter - $logCountBefore;

    echo "Registros no banco após: {$logCountAfter}\n";
    echo "Novos registros: {$newLogs}\n";

    if ($newLogs > 0) {
        echo "✅ Novos registros criados no banco\n";

        // Buscar registro específico da nossa transação
        $logStmt = $db->prepare("
            SELECT * FROM whatsapp_logs
            WHERE JSON_EXTRACT(additional_data, '$.transaction_id') = ?
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $logStmt->execute([$newTransactionId]);
        $dbLog = $logStmt->fetch(PDO::FETCH_ASSOC);

        if ($dbLog) {
            echo "\n🎯 Registro da nossa transação:\n";
            echo "  ID do log: {$dbLog['id']}\n";
            echo "  Sucesso: " . ($dbLog['success'] ? '✅ SIM' : '❌ NÃO') . "\n";
            echo "  Data/hora: {$dbLog['created_at']}\n";

            $metadata = json_decode($dbLog['additional_data'], true);
            if ($metadata) {
                if (isset($metadata['success_methods'])) {
                    echo "  Métodos bem-sucedidos: " . implode(', ', $metadata['success_methods']) . "\n";
                }
                if (isset($metadata['failed_methods'])) {
                    echo "  Métodos que falharam: " . implode(', ', $metadata['failed_methods']) . "\n";
                }
                if (isset($metadata['total_methods_tried'])) {
                    echo "  Total de métodos testados: {$metadata['total_methods_tried']}\n";
                }
            }
        }
    }

    // 7. Verificar logs do sistema
    echo "\n7️⃣ Verificando logs do sistema PHP...\n";

    // Verificar logs de erro do PHP (onde nossos error_log() vão)
    $phpLogFile = ini_get('error_log');
    if ($phpLogFile && file_exists($phpLogFile)) {
        echo "Log PHP: {$phpLogFile}\n";

        $phpLogContent = file_get_contents($phpLogFile);
        $phpLines = explode("\n", $phpLogContent);
        $recentPhpLines = array_slice($phpLines, -20);

        $found = false;
        foreach ($recentPhpLines as $line) {
            if (strpos($line, $newTransactionId) !== false || strpos($line, 'IMMEDIATE') !== false) {
                if (!$found) {
                    echo "\n📄 Logs PHP relevantes:\n";
                    $found = true;
                }
                echo "  " . trim($line) . "\n";
            }
        }

        if (!$found) {
            echo "⚠️ Nenhum log PHP específico da nossa transação encontrado\n";
        }
    } else {
        echo "⚠️ Log PHP não encontrado ou não configurado\n";
    }

    // 8. Resumo final
    echo "\n🎯 RESUMO DO TESTE END-TO-END:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ Transação criada: ID {$newTransactionId}\n";
    echo "⏱️ Tempo total: {$totalTime}ms\n";
    echo "📝 Log atualizado: " . ($logGrowth > 0 ? "✅ SIM ({$logGrowth} bytes)" : "❌ NÃO") . "\n";
    echo "🗄️ Banco atualizado: " . ($newLogs > 0 ? "✅ SIM ({$newLogs} registros)" : "❌ NÃO") . "\n";

    $success = $result['status'] && ($logGrowth > 0 || $newLogs > 0);
    echo "\n🚀 SISTEMA DE NOTIFICAÇÃO IMEDIATA: " . ($success ? "✅ FUNCIONANDO" : "❌ COM PROBLEMAS") . "\n";

    if ($success) {
        echo "\n🎉 PARABÉNS! O sistema está funcionando perfeitamente!\n";
        echo "Agora toda nova transação dispara notificação automática via WhatsApp.\n";
    } else {
        echo "\n⚠️ Verifique os logs para identificar problemas.\n";
    }

} catch (Exception $e) {
    echo "❌ ERRO CRÍTICO: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>