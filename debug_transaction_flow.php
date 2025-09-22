<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/TransactionController.php';

echo "=== DEBUG FLUXO DE TRANSAÇÃO ===\n";

// 1. Verificar se TransactionController tem o método registerTransaction
echo "1. Métodos disponíveis no TransactionController:\n";
$methods = get_class_methods('TransactionController');
foreach ($methods as $method) {
    if (strpos($method, 'register') !== false || strpos($method, 'Transaction') !== false) {
        echo "   - $method\n";
    }
}

// 2. Verificar se UltraDirectNotifier existe e está acessível
echo "\n2. Verificando UltraDirectNotifier:\n";
$ultraPath = __DIR__ . '/classes/UltraDirectNotifier.php';
if (file_exists($ultraPath)) {
    echo "   ✅ Arquivo existe: $ultraPath\n";
    require_once $ultraPath;
    if (class_exists('UltraDirectNotifier')) {
        echo "   ✅ Classe UltraDirectNotifier carregada\n";
    } else {
        echo "   ❌ Classe UltraDirectNotifier não encontrada\n";
    }
} else {
    echo "   ❌ Arquivo não existe: $ultraPath\n";
}

// 3. Verificar última transação no banco para simular
echo "\n3. Última transação no banco:\n";
try {
    $db = Database::getConnection();
    $stmt = $db->query("SELECT id, cliente_id, valor_total, status, created_at FROM transacoes_cashback ORDER BY id DESC LIMIT 1");
    $lastTransaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($lastTransaction) {
        echo "   ID: {$lastTransaction['id']}\n";
        echo "   Cliente: {$lastTransaction['cliente_id']}\n";
        echo "   Valor: R$ {$lastTransaction['valor_total']}\n";
        echo "   Status: {$lastTransaction['status']}\n";
        echo "   Data: {$lastTransaction['created_at']}\n";
    } else {
        echo "   ❌ Nenhuma transação encontrada\n";
    }
} catch (Exception $e) {
    echo "   ❌ Erro: " . $e->getMessage() . "\n";
}

echo "\n=== COMO TESTAR ===\n";
echo "1. Faça uma transação real no sistema\n";
echo "2. Verifique logs: tail -f logs/ultra_direct.log\n";
echo "3. Se não aparecer nada, o método registerTransaction não está sendo chamado\n";
?>