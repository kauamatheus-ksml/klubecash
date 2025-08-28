<?php
// Teste de require isolado
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testando require do TransactionController...\n";

try {
    require_once 'controllers/TransactionController.php';
    echo "✅ TransactionController carregado com sucesso\n";
    
    if (class_exists('TransactionController')) {
        echo "✅ Classe TransactionController existe\n";
        
        if (method_exists('TransactionController', 'registerTransaction')) {
            echo "✅ Método registerTransaction existe\n";
        } else {
            echo "❌ Método registerTransaction NÃO existe\n";
        }
    } else {
        echo "❌ Classe TransactionController NÃO existe\n";
    }
    
} catch (Throwable $e) {
    echo "❌ ERRO ao carregar: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . " (linha " . $e->getLine() . ")\n";
}
?>