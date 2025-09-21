<?php
/**
 * Teste da correção no TransactionController
 * Criar uma transação real usando o TransactionController corrigido
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/controllers/TransactionController.php';
require_once __DIR__ . '/controllers/AuthController.php';

echo "=== TESTE TRANSACTIONCONTROLLER CORRIGIDO ===\n\n";

// Simular sessão PHP
if (!session_id()) {
    session_start();
}

// Simular autenticação como loja
$_SESSION['authenticated'] = true;
$_SESSION['user_type'] = 'store';
$_SESSION['user_id'] = 59;
$_SESSION['store_id'] = 59;

try {
    echo "1. Criando transação usando TransactionController::registerTransaction()\n";

    $transactionData = [
        'loja_id' => 59,
        'usuario_id' => 9,
        'valor_total' => 35.00,
        'codigo_transacao' => 'TEST_FIX_' . time(),
        'descricao' => 'Teste da correção no TransactionController'
    ];

    echo "Dados da transação:\n";
    print_r($transactionData);
    echo "\n";

    echo "2. Registrando transação...\n";
    $resultado = TransactionController::registerTransaction($transactionData);

    echo "Resultado:\n";
    print_r($resultado);
    echo "\n";

    if ($resultado['status']) {
        echo "✅ Transação criada com sucesso!\n";

        // Aguardar para ver se notificação é processada
        echo "3. Aguardando processamento da notificação...\n";
        sleep(3);

        // Verificar logs
        echo "4. Verificando últimas linhas do trace:\n";
        $traceLines = file('integration_trace.log');
        $lastLines = array_slice($traceLines, -10);

        foreach ($lastLines as $line) {
            if (strpos($line, 'TransactionController') !== false) {
                echo $line;
            }
        }

    } else {
        echo "❌ Erro ao criar transação: " . $resultado['message'] . "\n";
    }

} catch (Exception $e) {
    echo "❌ Erro no teste: " . $e->getMessage() . "\n";
}

echo "\n=== CONCLUSÃO ===\n";
echo "Se você ver '[TRACE] TransactionController::registerTransaction() - SUCESSO' no log,\n";
echo "então a correção funcionou e as transações reais agora disparam notificações!\n";
?>