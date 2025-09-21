<?php
/**
 * TESTE SISTEMA DE NOTIFICAÇÃO IMEDIATA
 * Testa o novo sistema integrado de notificações
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/ImmediateNotificationSystem.php';

echo "🧪 TESTE SISTEMA DE NOTIFICAÇÃO IMEDIATA\n\n";

try {
    // 1. Verificar se sistema existe
    echo "1️⃣ Verificando sistema...\n";
    if (class_exists('ImmediateNotificationSystem')) {
        echo "✅ Classe ImmediateNotificationSystem encontrada\n";
    } else {
        echo "❌ Classe não encontrada\n";
        exit(1);
    }

    // 2. Buscar transação recente para teste
    echo "\n2️⃣ Buscando transação recente...\n";
    $db = Database::getConnection();

    $stmt = $db->prepare("
        SELECT t.*, u.nome as cliente_nome, u.telefone, l.nome_fantasia
        FROM transacoes_cashback t
        LEFT JOIN usuarios u ON t.usuario_id = u.id
        LEFT JOIN lojas l ON t.loja_id = l.id
        WHERE t.data_criacao_usuario >= DATE_SUB(NOW(), INTERVAL 30 DAY)
          AND u.telefone IS NOT NULL
          AND u.telefone != ''
        ORDER BY t.id DESC
        LIMIT 5
    ");
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($transactions)) {
        echo "❌ Nenhuma transação encontrada nos últimos 30 dias\n";

        // Criar transação de teste
        echo "\n3️⃣ Criando transação de teste...\n";

        // Verificar se existe usuário de teste
        $testUserStmt = $db->prepare("SELECT id FROM usuarios WHERE telefone = '34991191534' LIMIT 1");
        $testUserStmt->execute();
        $testUser = $testUserStmt->fetch(PDO::FETCH_ASSOC);

        if (!$testUser) {
            echo "❌ Usuário de teste não encontrado. Crie um usuário com telefone 34991191534\n";
            exit(1);
        }

        // Buscar uma loja qualquer
        $storeStmt = $db->prepare("SELECT id FROM lojas LIMIT 1");
        $storeStmt->execute();
        $store = $storeStmt->fetch(PDO::FETCH_ASSOC);

        if (!$store) {
            echo "❌ Nenhuma loja encontrada\n";
            exit(1);
        }

        // Criar transação de teste
        $createStmt = $db->prepare("
            INSERT INTO transacoes_cashback
            (codigo_transacao, usuario_id, loja_id, valor_total, valor_cliente, status, data_transacao, data_criacao_usuario)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        $codigo = 'TEST_' . time();
        $valor = 50.00;
        $cashback = 5.00;

        if ($createStmt->execute([$codigo, $testUser['id'], $store['id'], $valor, $cashback, 'pendente'])) {
            $testTransactionId = $db->lastInsertId();
            echo "✅ Transação de teste criada: ID {$testTransactionId}\n";

            // Buscar novamente
            $stmt = $db->prepare("
                SELECT t.*, u.nome as cliente_nome, u.telefone, l.nome_fantasia
                FROM transacoes_cashback t
                LEFT JOIN usuarios u ON t.usuario_id = u.id
                LEFT JOIN lojas l ON t.loja_id = l.id
                WHERE t.id = ?
            ");
            $stmt->execute([$testTransactionId]);
            $transactions = [$stmt->fetch(PDO::FETCH_ASSOC)];
        } else {
            echo "❌ Erro ao criar transação de teste\n";
            exit(1);
        }
    }

    echo "✅ Encontradas " . count($transactions) . " transações\n";

    // 3. Mostrar transações disponíveis
    echo "\n📋 Transações disponíveis:\n";
    foreach ($transactions as $i => $t) {
        $phone = $t['telefone'] ? 'Tel: ' . substr($t['telefone'], -4) : 'Sem tel';
        echo "  [{$i}] ID: {$t['id']}, Status: {$t['status']}, Valor: R$ {$t['valor_total']}, Cliente: {$t['cliente_nome']}, {$phone}\n";
    }

    // 4. Testar notificação imediata
    $selectedTransaction = $transactions[0];
    echo "\n4️⃣ Testando notificação para transação ID: {$selectedTransaction['id']}\n";
    echo "Cliente: {$selectedTransaction['cliente_nome']}\n";
    echo "Telefone: {$selectedTransaction['telefone']}\n";
    echo "Status: {$selectedTransaction['status']}\n";
    echo "Valor: R$ {$selectedTransaction['valor_total']}\n\n";

    echo "🚀 Enviando notificação imediata...\n";

    $start = microtime(true);
    $notificationSystem = new ImmediateNotificationSystem();
    $result = $notificationSystem->sendImmediateNotification($selectedTransaction['id']);
    $totalTime = round((microtime(true) - $start) * 1000, 2);

    echo "\n📊 RESULTADO:\n";
    echo "Tempo total: {$totalTime}ms\n";
    echo "Sucesso: " . ($result['success'] ? "✅ SIM" : "❌ NÃO") . "\n";

    if ($result['success']) {
        echo "Método usado: " . ($result['method_used'] ?? 'N/A') . "\n";

        if (isset($result['all_results'])) {
            echo "\n📈 Detalhes por método:\n";
            foreach ($result['all_results'] as $method => $methodResult) {
                $status = $methodResult['success'] ? "✅" : "❌";
                $time = $methodResult['response_time_ms'] ?? 'N/A';
                $error = $methodResult['success'] ? '' : ' - ' . ($methodResult['error'] ?? 'Erro desconhecido');
                echo "  {$method}: {$status} ({$time}ms){$error}\n";
            }
        }

        echo "\n🎉 NOTIFICAÇÃO ENVIADA COM SUCESSO!\n";
    } else {
        echo "Erro: " . ($result['message'] ?? 'Erro desconhecido') . "\n";
        echo "\n❌ NOTIFICAÇÃO FALHOU\n";
    }

    // 5. Verificar logs
    echo "\n5️⃣ Verificando logs...\n";
    $logFile = __DIR__ . '/logs/immediate_notifications.log';
    if (file_exists($logFile)) {
        echo "✅ Log encontrado: {$logFile}\n";
        $logContent = file_get_contents($logFile);
        $lines = explode("\n", $logContent);
        $recentLines = array_slice($lines, -10); // Últimas 10 linhas

        echo "📄 Últimas entradas do log:\n";
        foreach ($recentLines as $line) {
            if (trim($line)) {
                echo "  " . trim($line) . "\n";
            }
        }
    } else {
        echo "⚠️ Log não encontrado\n";
    }

    // 6. Verificar banco de dados
    echo "\n6️⃣ Verificando registro no banco...\n";
    $logStmt = $db->prepare("
        SELECT * FROM whatsapp_logs
        WHERE JSON_EXTRACT(additional_data, '$.transaction_id') = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $logStmt->execute([$selectedTransaction['id']]);
    $dbLog = $logStmt->fetch(PDO::FETCH_ASSOC);

    if ($dbLog) {
        echo "✅ Registro encontrado no banco:\n";
        echo "  ID: {$dbLog['id']}\n";
        echo "  Tipo: {$dbLog['type']}\n";
        echo "  Sucesso: " . ($dbLog['success'] ? 'Sim' : 'Não') . "\n";
        echo "  Data: {$dbLog['created_at']}\n";

        $metadata = json_decode($dbLog['additional_data'], true);
        if ($metadata) {
            echo "  Sistema: " . ($metadata['system'] ?? 'N/A') . "\n";
            if (isset($metadata['success_methods'])) {
                echo "  Métodos bem-sucedidos: " . implode(', ', $metadata['success_methods']) . "\n";
            }
        }
    } else {
        echo "⚠️ Nenhum registro encontrado no banco\n";
    }

    echo "\n🎯 TESTE CONCLUÍDO!\n";
    echo "Sistema de notificação imediata " . ($result['success'] ? "✅ FUNCIONANDO" : "❌ COM PROBLEMAS") . "\n";

} catch (Exception $e) {
    echo "❌ ERRO CRÍTICO: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>