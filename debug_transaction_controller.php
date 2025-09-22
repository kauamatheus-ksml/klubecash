<?php
/**
 * DEBUG DO TRANSACTION CONTROLLER
 * Simular exatamente como o sistema oficial cria transações
 */

echo "=== DEBUG TRANSACTION CONTROLLER ===\n";

// Incluir configurações
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';

try {
    // Incluir o TransactionController
    $controllerPath = __DIR__ . '/controllers/TransactionController.php';

    echo "📁 Verificando TransactionController...\n";
    if (!file_exists($controllerPath)) {
        echo "❌ TransactionController não encontrado!\n";
        exit;
    }

    echo "✅ TransactionController encontrado\n";
    require_once $controllerPath;

    if (!class_exists('TransactionController')) {
        echo "❌ Classe TransactionController não existe!\n";
        exit;
    }

    echo "✅ Classe TransactionController carregada\n";

    // Verificar se método existe
    if (!method_exists('TransactionController', 'registerTransaction')) {
        echo "❌ Método registerTransaction não existe!\n";
        exit;
    }

    echo "✅ Método registerTransaction encontrado\n";

    // Ativar logs de erro para capturar tudo
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);

    echo "\n🚀 Criando instância do TransactionController...\n";

    // Simular dados de uma transação real
    $transactionData = [
        'usuario_id' => 162,  // Cecilia que tem telefone
        'loja_id' => 59,      // Sync Holding
        'valor_total' => 200.00,
        'percentual_cashback' => 5.0,
        'descricao' => 'Teste via TransactionController'
    ];

    echo "📋 Dados da transação:\n";
    print_r($transactionData);

    echo "\n📞 Chamando TransactionController::registerTransaction()...\n";

    // Instanciar controller
    $controller = new TransactionController();

    // Chamar método de registro
    $result = $controller->registerTransaction($transactionData);

    echo "\n📋 Resultado do registerTransaction:\n";
    print_r($result);

    if (isset($result['success']) && $result['success']) {
        echo "\n✅ TRANSAÇÃO REGISTRADA COM SUCESSO!\n";

        if (isset($result['transaction_id'])) {
            $transactionId = $result['transaction_id'];
            echo "🆔 ID da transação: {$transactionId}\n";

            // Verificar se foi criado log do UltraDirectNotifier
            $ultraLogPath = __DIR__ . '/logs/ultra_direct.log';
            if (file_exists($ultraLogPath)) {
                echo "\n📝 Últimas linhas do log UltraDirectNotifier:\n";
                $logContent = file_get_contents($ultraLogPath);
                $lines = explode("\n", trim($logContent));
                $lastLines = array_slice($lines, -5);
                foreach ($lastLines as $line) {
                    echo "   " . $line . "\n";
                }
            } else {
                echo "\n❌ Log do UltraDirectNotifier não encontrado\n";
            }
        }
    } else {
        echo "\n❌ FALHA NO REGISTRO DA TRANSAÇÃO!\n";
        if (isset($result['error'])) {
            echo "🚫 Erro: {$result['error']}\n";
        }
    }

} catch (Exception $e) {
    echo "\n❌ ERRO CRÍTICO: " . $e->getMessage() . "\n";
    echo "📍 Arquivo: " . $e->getFile() . "\n";
    echo "📍 Linha: " . $e->getLine() . "\n";
    echo "📋 Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== FIM DO DEBUG ===\n";
?>