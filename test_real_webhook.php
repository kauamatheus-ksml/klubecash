<?php
/**
 * SIMULAR WEBHOOK REAL DE TRANSAÇÃO
 * Testar exatamente como seria uma transação criada via web
 */

// Simular ambiente web
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_HOST'] = 'klubecash.com';
$_SERVER['HTTPS'] = 'on';

echo "=== SIMULANDO WEBHOOK REAL DE TRANSAÇÃO ===\n";

// Dados que viriam de um POST real
$_POST = [
    'loja_id' => 59,
    'usuario_id' => 162,
    'valor_total' => 300.00,
    'codigo_transacao' => 'WEB_' . time(),
    'descricao' => 'Compra via webhook real'
];

echo "📋 Dados POST simulados:\n";
print_r($_POST);

// Simular sessão de loja
session_start();
$_SESSION['user_id'] = 59;
$_SESSION['user_type'] = 'loja';
$_SESSION['store_id'] = 59;

echo "\n🔗 Chamando TransactionController via POST (como sistema real)...\n";

// Incluir dependências
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/controllers/TransactionController.php';

try {
    // Simular exatamente o que acontece quando uma transação é criada via web
    $controller = new TransactionController();

    echo "📤 Executando registerTransaction com dados POST...\n";

    $result = $controller::registerTransaction($_POST);

    echo "\n📋 Resultado:\n";
    print_r($result);

    if ($result['success'] ?? false) {
        echo "\n✅ SUCESSO! Transação criada.\n";
        if (isset($result['transaction_id'])) {
            echo "🆔 ID: {$result['transaction_id']}\n";

            // Verificar se notificação foi enviada
            $ultraLog = __DIR__ . '/logs/ultra_direct.log';
            if (file_exists($ultraLog)) {
                $content = file_get_contents($ultraLog);
                $lines = explode("\n", $content);
                $lastLine = end($lines);
                $secondLastLine = $lines[count($lines) - 2] ?? '';

                echo "\n📝 Últimas entradas do log:\n";
                echo "   " . $secondLastLine . "\n";
                echo "   " . $lastLine . "\n";
            }
        }
    } else {
        echo "\n❌ FALHA na criação da transação\n";
        echo "🚫 Erro: " . ($result['message'] ?? 'Erro desconhecido') . "\n";
    }

} catch (Exception $e) {
    echo "\n❌ EXCEÇÃO: " . $e->getMessage() . "\n";
    echo "📁 Arquivo: " . $e->getFile() . "\n";
    echo "📍 Linha: " . $e->getLine() . "\n";
}

echo "\n=== FIM DO TESTE WEBHOOK REAL ===\n";
?>