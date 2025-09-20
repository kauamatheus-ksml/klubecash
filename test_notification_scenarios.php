<?php
/**
 * Teste Abrangente de Cenários de Notificação de Cashback
 *
 * Este script testa diferentes tipos de clientes e situações para
 * garantir que as mensagens personalizadas estão sendo enviadas
 * corretamente para cada perfil.
 *
 * Cenários testados:
 * - Cliente novo (primeira compra)
 * - Cliente regular (múltiplas compras)
 * - Cliente VIP (alto volume/valor)
 * - Compra grande (acima do threshold)
 * - Diferentes valores de cashback
 *
 * Para usar no VPS: php test_notification_scenarios.php
 */

// Incluir dependências
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/classes/CashbackNotifier.php';
require_once __DIR__ . '/utils/NotificationTrigger.php';

// === CONFIGURAÇÕES DOS TESTES ===
$PHONE_BASE = '5538991045'; // Base do telefone (será incrementado)
$SECRET_KEY = 'klube-cash-2024';
$WHATSAPP_BOT_URL = 'http://148.230.73.190:3002';

echo "=== TESTE DE CENÁRIOS DE NOTIFICAÇÃO CASHBACK ===\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n";
echo "Bot WhatsApp: $WHATSAPP_BOT_URL\n\n";

try {
    $db = Database::getConnection();

    // === CENÁRIOS DE TESTE ===
    $scenarios = [
        [
            'name' => 'Cliente Novo - Primeira Compra',
            'description' => 'Cliente fazendo primeira compra (mensagem educativa)',
            'user_data' => [
                'nome' => 'João Primeiro',
                'telefone' => $PHONE_BASE . '201',
                'existing_transactions' => 0
            ],
            'transaction_data' => [
                'valor_total' => 50.00,
                'valor_cashback' => 5.00,
                'valor_cliente' => 2.50
            ],
            'expected_type' => 'first_purchase'
        ],
        [
            'name' => 'Cliente Regular - Compra Normal',
            'description' => 'Cliente com histórico fazendo compra normal',
            'user_data' => [
                'nome' => 'Maria Regular',
                'telefone' => $PHONE_BASE . '202',
                'existing_transactions' => 5
            ],
            'transaction_data' => [
                'valor_total' => 80.00,
                'valor_cashback' => 8.00,
                'valor_cliente' => 4.00
            ],
            'expected_type' => 'regular_client'
        ],
        [
            'name' => 'Cliente VIP - Compra Normal',
            'description' => 'Cliente VIP fazendo compra (mensagem concisa)',
            'user_data' => [
                'nome' => 'Carlos VIP Silva',
                'telefone' => $PHONE_BASE . '203',
                'existing_transactions' => 25
            ],
            'transaction_data' => [
                'valor_total' => 120.00,
                'valor_cashback' => 12.00,
                'valor_cliente' => 6.00
            ],
            'expected_type' => 'vip_client'
        ],
        [
            'name' => 'Compra Grande - Cliente Regular',
            'description' => 'Compra acima de R$ 200 (mensagem celebrativa)',
            'user_data' => [
                'nome' => 'Ana Compradora',
                'telefone' => $PHONE_BASE . '204',
                'existing_transactions' => 3
            ],
            'transaction_data' => [
                'valor_total' => 350.00,
                'valor_cashback' => 35.00,
                'valor_cliente' => 17.50
            ],
            'expected_type' => 'big_purchase'
        ],
        [
            'name' => 'Cliente VIP - Compra Grande',
            'description' => 'Cliente VIP fazendo compra grande',
            'user_data' => [
                'nome' => 'Roberto VIP Premium',
                'telefone' => $PHONE_BASE . '205',
                'existing_transactions' => 30
            ],
            'transaction_data' => [
                'valor_total' => 500.00,
                'valor_cashback' => 50.00,
                'valor_cliente' => 25.00
            ],
            'expected_type' => 'big_purchase' // Compra grande tem prioridade sobre VIP
        ]
    ];

    // === BUSCAR LOJA DE TESTE ===
    $stmt = $db->prepare("SELECT id, nome_fantasia FROM lojas WHERE status = 'aprovado' LIMIT 1");
    $stmt->execute();
    $loja = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$loja) {
        throw new Exception("Nenhuma loja aprovada encontrada");
    }

    $lojaId = $loja['id'];
    echo "✅ Loja de teste: {$loja['nome_fantasia']} (ID: $lojaId)\n\n";

    // === EXECUTAR CENÁRIOS ===
    $results = [];

    foreach ($scenarios as $index => $scenario) {
        echo "--- CENÁRIO " . ($index + 1) . ": {$scenario['name']} ---\n";
        echo "Descrição: {$scenario['description']}\n";

        try {
            // Criar ou buscar usuário
            $userId = createTestUser($db, $scenario['user_data']);
            echo "✅ Usuário: {$scenario['user_data']['nome']} (ID: $userId)\n";

            // Criar histórico se necessário
            if ($scenario['user_data']['existing_transactions'] > 0) {
                createUserHistory($db, $userId, $lojaId, $scenario['user_data']['existing_transactions']);
                echo "✅ Histórico criado: {$scenario['user_data']['existing_transactions']} transações\n";
            }

            // Criar transação de teste
            $transactionId = createTestTransaction($db, $userId, $lojaId, $scenario['transaction_data']);
            echo "✅ Transação criada: ID $transactionId\n";

            // Testar notificação
            echo "📤 Enviando notificação...\n";

            $notifier = new CashbackNotifier();
            $result = $notifier->notifyNewTransaction($transactionId);

            if ($result['success']) {
                echo "✅ Notificação enviada com sucesso!\n";
                echo "📱 Tipo de mensagem: {$result['message_type']}\n";
                echo "📞 Telefone: {$result['phone']}\n";

                // Verificar se o tipo está correto
                if ($result['message_type'] === $scenario['expected_type']) {
                    echo "✅ Tipo de mensagem CORRETO (esperado: {$scenario['expected_type']})\n";
                } else {
                    echo "⚠️  Tipo de mensagem DIFERENTE (esperado: {$scenario['expected_type']}, recebido: {$result['message_type']})\n";
                }

                $results[$scenario['name']] = [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'message_type' => $result['message_type'],
                    'expected_type' => $scenario['expected_type'],
                    'type_correct' => $result['message_type'] === $scenario['expected_type'],
                    'phone' => $result['phone']
                ];

            } else {
                echo "❌ Falha na notificação: {$result['message']}\n";
                $results[$scenario['name']] = [
                    'success' => false,
                    'error' => $result['message'],
                    'transaction_id' => $transactionId
                ];
            }

        } catch (Exception $e) {
            echo "❌ Erro no cenário: {$e->getMessage()}\n";
            $results[$scenario['name']] = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }

        echo "\n";
    }

    // === RESULTADOS CONSOLIDADOS ===
    echo "=== RESULTADOS CONSOLIDADOS ===\n";

    $totalTests = count($scenarios);
    $successfulTests = 0;
    $correctTypes = 0;

    foreach ($results as $scenarioName => $result) {
        echo "• $scenarioName: ";

        if ($result['success']) {
            $successfulTests++;
            echo "✅ SUCESSO";

            if (isset($result['type_correct']) && $result['type_correct']) {
                $correctTypes++;
                echo " (Tipo CORRETO)";
            } else {
                echo " (Tipo DIFERENTE)";
            }

            echo " - Tel: {$result['phone']}\n";
        } else {
            echo "❌ FALHA - {$result['error']}\n";
        }
    }

    echo "\n=== ESTATÍSTICAS FINAIS ===\n";
    echo "Total de testes: $totalTests\n";
    echo "Sucessos: $successfulTests\n";
    echo "Falhas: " . ($totalTests - $successfulTests) . "\n";
    echo "Tipos corretos: $correctTypes\n";
    echo "Taxa de sucesso: " . round(($successfulTests / $totalTests) * 100, 2) . "%\n";
    echo "Taxa de tipos corretos: " . round(($correctTypes / $totalTests) * 100, 2) . "%\n";

    // === TESTE DO SISTEMA DE RETRY ===
    echo "\n=== TESTANDO SISTEMA DE RETRY ===\n";

    // Simular uma falha e verificar se o retry foi agendado
    require_once __DIR__ . '/utils/CashbackRetrySystem.php';
    $retrySystem = new CashbackRetrySystem();

    // Registrar uma falha fictícia
    $fakeTransactionId = 999999;
    $retryResult = $retrySystem->registerFailure($fakeTransactionId, "Teste de falha simulada", 1);

    if ($retryResult) {
        echo "✅ Sistema de retry funcionando - Falha registrada\n";

        // Obter estatísticas
        $stats = $retrySystem->getStats();
        echo "📊 Estatísticas do retry:\n";
        echo "   - Pendentes: {$stats['total_pending']}\n";
        echo "   - Sucessos: {$stats['total_success']}\n";
        echo "   - Falhados: {$stats['total_failed']}\n";
        echo "   - Taxa de sucesso: {$stats['success_rate']}\n";
    } else {
        echo "❌ Erro no sistema de retry\n";
    }

    echo "\n=== TESTE CONCLUÍDO ===\n";
    echo "Verifique os WhatsApp dos números testados para confirmar o recebimento das mensagens.\n";
    echo "Números testados:\n";

    foreach ($scenarios as $index => $scenario) {
        if (isset($results[$scenario['name']]['phone'])) {
            echo "- {$scenario['user_data']['nome']}: {$results[$scenario['name']]['phone']}\n";
        }
    }

} catch (Exception $e) {
    echo "❌ ERRO CRÍTICO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// === FUNÇÕES AUXILIARES ===

function createTestUser($db, $userData) {
    // Verificar se usuário já existe
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE telefone = :telefone");
    $stmt->bindParam(':telefone', $userData['telefone']);
    $stmt->execute();
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        return $existing['id'];
    }

    // Criar novo usuário
    $stmt = $db->prepare("
        INSERT INTO usuarios (nome, telefone, email, status, tipo, senha_hash, data_criacao)
        VALUES (:nome, :telefone, :email, 'ativo', 'cliente', :senha_hash, NOW())
    ");

    $stmt->execute([
        ':nome' => $userData['nome'],
        ':telefone' => $userData['telefone'],
        ':email' => strtolower(str_replace(' ', '.', $userData['nome'])) . '@teste.com',
        ':senha_hash' => password_hash('123456', PASSWORD_DEFAULT)
    ]);

    return $db->lastInsertId();
}

function createUserHistory($db, $userId, $lojaId, $transactionCount) {
    // Criar transações históricas para simular perfil do cliente
    for ($i = 0; $i < $transactionCount; $i++) {
        $valorTotal = rand(30, 150);
        $valorCashback = $valorTotal * 0.10;
        $valorCliente = $valorCashback * 0.50;

        $stmt = $db->prepare("
            INSERT INTO transacoes_cashback
            (usuario_id, loja_id, valor_total, valor_cashback, valor_cliente, status, data_transacao)
            VALUES (:usuario_id, :loja_id, :valor_total, :valor_cashback, :valor_cliente, 'aprovado',
                    DATE_SUB(NOW(), INTERVAL :days_ago DAY))
        ");

        $daysAgo = rand(1, 90); // Transações dos últimos 90 dias

        $stmt->execute([
            ':usuario_id' => $userId,
            ':loja_id' => $lojaId,
            ':valor_total' => $valorTotal,
            ':valor_cashback' => $valorCashback,
            ':valor_cliente' => $valorCliente,
            ':days_ago' => $daysAgo
        ]);
    }
}

function createTestTransaction($db, $userId, $lojaId, $transactionData) {
    $stmt = $db->prepare("
        INSERT INTO transacoes_cashback
        (usuario_id, loja_id, valor_total, valor_cashback, valor_cliente, status, data_transacao)
        VALUES (:usuario_id, :loja_id, :valor_total, :valor_cashback, :valor_cliente, 'aprovado', NOW())
    ");

    $stmt->execute([
        ':usuario_id' => $userId,
        ':loja_id' => $lojaId,
        ':valor_total' => $transactionData['valor_total'],
        ':valor_cashback' => $transactionData['valor_cashback'],
        ':valor_cliente' => $transactionData['valor_cliente']
    ]);

    return $db->lastInsertId();
}
?>