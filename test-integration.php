<?php
/**
 * Teste de Integração Completo - Klube Cash
 *
 * Este script executa uma bateria completa de testes para validar
 * a integração entre N8N, Evolution API e sistema legado.
 *
 * Funcionalidades testadas:
 * - Conectividade N8N
 * - Conectividade Evolution API
 * - Envio de mensagens de teste
 * - Webhook com transação real
 * - Sistema de fallback
 *
 * Versão: 2.0
 * Autor: Sistema Klube Cash
 */

// Configuração inicial
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/api/n8n-webhook.php';
require_once __DIR__ . '/utils/EvolutionWhatsApp.php';
require_once __DIR__ . '/utils/NotificationTrigger.php';

// Determinar se é CLI ou WEB
$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Teste de Integração - Klube Cash</title>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 20px; background: #f8f9fa; }
            .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .test-section { margin: 15px 0; padding: 15px; border-radius: 8px; border-left: 4px solid #ddd; }
            .success { background: #d4edda; border-left-color: #28a745; }
            .error { background: #f8d7da; border-left-color: #dc3545; }
            .warning { background: #fff3cd; border-left-color: #ffc107; }
            .info { background: #d1ecf1; border-left-color: #17a2b8; }
            pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
            h1, h2 { color: #333; }
            .test-title { font-weight: 600; margin-bottom: 8px; }
            .progress { background: #e9ecef; border-radius: 4px; overflow: hidden; margin: 10px 0; height: 25px; }
            .progress-bar { background: linear-gradient(45deg, #007bff, #0056b3); color: white; text-align: center; line-height: 25px; transition: width 0.3s; }
            .icon { font-size: 18px; margin-right: 8px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>🧪 Teste de Integração Completo - Klube Cash</h1>
            <p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>
            <hr>
    ";
}

// Funções helper
function output($message, $newline = true) {
    global $isCli;
    if ($isCli) {
        echo $message . ($newline ? "\n" : "");
    } else {
        echo $message . ($newline ? "<br>" : "");
    }
}

function displayTestResult($title, $result, $details = null) {
    global $isCli;

    $statusIcon = $result ? '✅' : '❌';
    $statusText = $result ? 'SUCESSO' : 'FALHA';

    if ($isCli) {
        echo "{$statusIcon} {$title}: {$statusText}\n";
        if ($details) {
            echo "   Detalhes: " . print_r($details, true) . "\n";
        }
    } else {
        $statusClass = $result ? 'success' : 'error';
        echo "<div class='test-section {$statusClass}'>
                <div class='test-title'><span class='icon'>{$statusIcon}</span>{$title}</div>
                <div><strong>Status:</strong> {$statusText}</div>";

        if ($details) {
            echo "<pre>" . htmlspecialchars(print_r($details, true)) . "</pre>";
        }

        echo "</div>";
    }
}

function displayProgress($current, $total, $description) {
    global $isCli;

    $percentage = ($current / $total) * 100;

    if ($isCli) {
        echo "[{$current}/{$total}] {$description}...\n";
    } else {
        echo "<div class='progress'>
                <div class='progress-bar' style='width: {$percentage}%'>
                    {$current}/{$total} - {$description}
                </div>
              </div>";

        if (ob_get_level()) ob_flush();
        flush();
    }
}

// Iniciar testes
$totalTests = 8;
$currentTest = 0;
$testResults = [];

if (!$isCli) output("<h2>📋 Iniciando Bateria de Testes</h2>");

// === TESTE 1: VERIFICAR CONFIGURAÇÕES ===
$currentTest++;
displayProgress($currentTest, $totalTests, "Verificando configurações");

$configTests = [
    'N8N_ENABLED' => defined('N8N_ENABLED') && N8N_ENABLED,
    'N8N_WEBHOOK_URL' => defined('N8N_WEBHOOK_URL') && !empty(N8N_WEBHOOK_URL),
    'N8N_WEBHOOK_SECRET' => defined('N8N_WEBHOOK_SECRET') && !empty(N8N_WEBHOOK_SECRET),
    'EVOLUTION_API_ENABLED' => defined('EVOLUTION_API_ENABLED') && EVOLUTION_API_ENABLED,
    'EVOLUTION_API_URL' => defined('EVOLUTION_API_URL') && !empty(EVOLUTION_API_URL),
    'EVOLUTION_API_KEY' => defined('EVOLUTION_API_KEY') && !empty(EVOLUTION_API_KEY),
    'EVOLUTION_INSTANCE' => defined('EVOLUTION_INSTANCE') && !empty(EVOLUTION_INSTANCE)
];

$configSuccess = array_sum($configTests) === count($configTests);
$testResults['config'] = $configSuccess;

displayTestResult("1. Verificação de Configurações", $configSuccess, $configTests);

// === TESTE 2: CONECTIVIDADE N8N ===
$currentTest++;
displayProgress($currentTest, $totalTests, "Testando conectividade N8N");

try {
    $n8nTest = N8nWebhook::testConnection();
    $testResults['n8n_connection'] = $n8nTest;
    displayTestResult("2. Conectividade N8N", $n8nTest, $n8nTest ? "N8N respondeu corretamente" : "Falha na conexão com N8N");
} catch (Exception $e) {
    $testResults['n8n_connection'] = false;
    displayTestResult("2. Conectividade N8N", false, "Erro: " . $e->getMessage());
}

// === TESTE 3: CONECTIVIDADE EVOLUTION API ===
$currentTest++;
displayProgress($currentTest, $totalTests, "Testando conectividade Evolution API");

try {
    $evolutionTest = EvolutionWhatsApp::testConnection();
    $testResults['evolution_connection'] = $evolutionTest['success'];
    displayTestResult("3. Conectividade Evolution API", $evolutionTest['success'], $evolutionTest);
} catch (Exception $e) {
    $testResults['evolution_connection'] = false;
    displayTestResult("3. Conectividade Evolution API", false, "Erro: " . $e->getMessage());
}

// === TESTE 4: BUSCAR TRANSAÇÃO DE TESTE ===
$currentTest++;
displayProgress($currentTest, $totalTests, "Buscando transação de teste");

try {
    $db = Database::getConnection();
    $stmt = $db->prepare("
        SELECT t.id, u.nome, u.telefone, l.nome_fantasia, t.valor_cashback
        FROM transacoes_cashback t
        JOIN usuarios u ON t.usuario_id = u.id
        JOIN lojas l ON t.loja_id = l.id
        WHERE u.telefone IS NOT NULL AND u.telefone != ''
        ORDER BY t.data_criacao DESC
        LIMIT 1
    ");
    $stmt->execute();
    $testTransaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($testTransaction) {
        $testResults['transaction_found'] = true;
        $transactionId = $testTransaction['id'];
        displayTestResult("4. Transação de Teste Encontrada", true, [
            'ID' => $testTransaction['id'],
            'Cliente' => $testTransaction['nome'],
            'Telefone' => substr($testTransaction['telefone'], 0, 5) . '***',
            'Loja' => $testTransaction['nome_fantasia'],
            'Cashback' => 'R$ ' . number_format($testTransaction['valor_cashback'], 2, ',', '.')
        ]);
    } else {
        $testResults['transaction_found'] = false;
        $transactionId = null;
        displayTestResult("4. Transação de Teste Encontrada", false, "Nenhuma transação com telefone válido encontrada");
    }

} catch (Exception $e) {
    $testResults['transaction_found'] = false;
    $transactionId = null;
    displayTestResult("4. Transação de Teste Encontrada", false, "Erro: " . $e->getMessage());
}

// === TESTE 5: TESTE DE ENVIO N8N ===
$currentTest++;
displayProgress($currentTest, $totalTests, "Testando envio N8N");

if ($transactionId && $testResults['n8n_connection']) {
    try {
        $n8nSendResult = N8nWebhook::sendTransactionData($transactionId, 'test_transaction');
        $testResults['n8n_send'] = $n8nSendResult;
        displayTestResult("5. Envio via N8N", $n8nSendResult, $n8nSendResult ? "Webhook enviado com sucesso" : "Falha no envio do webhook");
    } catch (Exception $e) {
        $testResults['n8n_send'] = false;
        displayTestResult("5. Envio via N8N", false, "Erro: " . $e->getMessage());
    }
} else {
    $testResults['n8n_send'] = false;
    displayTestResult("5. Envio via N8N", false, "Pré-requisitos não atendidos (transação ou conectividade N8N)");
}

// === TESTE 6: TESTE DE ENVIO EVOLUTION API ===
$currentTest++;
displayProgress($currentTest, $totalTests, "Testando envio Evolution API");

if ($transactionId && $testResults['evolution_connection']) {
    try {
        $testData = [
            'transaction_id' => $transactionId,
            'valor_cashback' => $testTransaction['valor_cashback'],
            'nome_cliente' => $testTransaction['nome'],
            'nome_loja' => $testTransaction['nome_fantasia']
        ];

        $evolutionSendResult = EvolutionWhatsApp::sendNewTransactionNotification($testTransaction['telefone'], $testData);
        $testResults['evolution_send'] = $evolutionSendResult['success'];
        displayTestResult("6. Envio via Evolution API", $evolutionSendResult['success'], $evolutionSendResult);
    } catch (Exception $e) {
        $testResults['evolution_send'] = false;
        displayTestResult("6. Envio via Evolution API", false, "Erro: " . $e->getMessage());
    }
} else {
    $testResults['evolution_send'] = false;
    displayTestResult("6. Envio via Evolution API", false, "Pré-requisitos não atendidos (transação ou conectividade Evolution)");
}

// === TESTE 7: TESTE DO SISTEMA DE FALLBACK ===
$currentTest++;
displayProgress($currentTest, $totalTests, "Testando sistema de fallback");

if ($transactionId) {
    try {
        $fallbackResult = NotificationTrigger::send($transactionId, ['async' => false, 'debug' => true]);
        $testResults['fallback'] = $fallbackResult['success'];
        displayTestResult("7. Sistema de Fallback", $fallbackResult['success'], $fallbackResult);
    } catch (Exception $e) {
        $testResults['fallback'] = false;
        displayTestResult("7. Sistema de Fallback", false, "Erro: " . $e->getMessage());
    }
} else {
    $testResults['fallback'] = false;
    displayTestResult("7. Sistema de Fallback", false, "Transação de teste não disponível");
}

// === TESTE 8: ESTATÍSTICAS E LOGS ===
$currentTest++;
displayProgress($currentTest, $totalTests, "Verificando estatísticas");

try {
    $n8nStats = N8nWebhook::getStats('24h');
    $evolutionStats = EvolutionWhatsApp::getStats('24h');

    $statsSuccess = $n8nStats['success'] && $evolutionStats['success'];
    $testResults['stats'] = $statsSuccess;

    displayTestResult("8. Estatísticas e Logs", $statsSuccess, [
        'N8N Stats (24h)' => $n8nStats['stats'] ?? 'N/A',
        'Evolution Stats (24h)' => $evolutionStats['stats'] ?? 'N/A'
    ]);
} catch (Exception $e) {
    $testResults['stats'] = false;
    displayTestResult("8. Estatísticas e Logs", false, "Erro: " . $e->getMessage());
}

// === RESUMO FINAL ===
$successCount = array_sum($testResults);
$totalCount = count($testResults);
$successRate = round(($successCount / $totalCount) * 100, 1);

if (!$isCli) {
    echo "<h2>📊 Resumo Final</h2>";

    $overallStatus = $successRate >= 70 ? 'success' : ($successRate >= 50 ? 'warning' : 'error');
    $statusIcon = $successRate >= 70 ? '✅' : ($successRate >= 50 ? '⚠️' : '❌');

    echo "<div class='test-section {$overallStatus}'>
            <div class='test-title'><span class='icon'>{$statusIcon}</span>Resultado Geral</div>
            <div><strong>Taxa de Sucesso:</strong> {$successCount}/{$totalCount} ({$successRate}%)</div>
            <div class='progress'>
                <div class='progress-bar' style='width: {$successRate}%'>
                    {$successRate}% dos testes passaram
                </div>
            </div>
          </div>";

    echo "<h3>Detalhamento:</h3>";
    $testNames = [
        'config' => 'Configurações',
        'n8n_connection' => 'N8N Conectividade',
        'evolution_connection' => 'Evolution Conectividade',
        'transaction_found' => 'Transação Teste',
        'n8n_send' => 'Envio N8N',
        'evolution_send' => 'Envio Evolution',
        'fallback' => 'Sistema Fallback',
        'stats' => 'Estatísticas/Logs'
    ];

    foreach ($testNames as $key => $name) {
        $result = $testResults[$key] ?? false;
        $icon = $result ? '✅' : '❌';
        $status = $result ? 'Passou' : 'Falhou';
        echo "<div>{$icon} <strong>{$name}:</strong> {$status}</div>";
    }

    // Recomendações
    echo "<h2>💡 Recomendações</h2>";
    echo "<div class='test-section info'>";
    echo "<div class='test-title'>📋 Próximos Passos</div>";

    if (!$testResults['evolution_connection']) {
        echo "<div>• Resolver problema HTTP 401 da Evolution API</div>";
        echo "<div>• Verificar chave API e instância</div>";
    }

    if (!$testResults['n8n_connection']) {
        echo "<div>• Verificar conectividade com N8N</div>";
    }

    if ($successRate < 100) {
        echo "<div>• Analisar logs de erro detalhados</div>";
        echo "<div>• Executar testes individuais dos componentes com falha</div>";
    }

    echo "<div>• Monitorar logs de integração continuamente</div>";
    echo "</div>";

    echo "<div style='margin-top: 30px; text-align: center; color: #666;'>
            <hr>
            <p>Teste executado em: " . date('d/m/Y H:i:s') . "</p>
            <p>Sistema: Klube Cash v2.1 - Integração N8N + Evolution API</p>
          </div>";

    echo "</div></body></html>";

} else {
    echo "\n=== RESUMO FINAL ===\n";
    echo "Taxa de Sucesso: {$successCount}/{$totalCount} ({$successRate}%)\n\n";

    foreach ($testResults as $test => $result) {
        $icon = $result ? '✅' : '❌';
        echo "{$icon} {$test}\n";
    }

    echo "\n=== FIM DOS TESTES ===\n";
}
?>