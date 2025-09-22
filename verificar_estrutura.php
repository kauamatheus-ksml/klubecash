<?php
/**
 * VERIFICAR ESTRUTURA DA TABELA
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getConnection();

    echo "=== ESTRUTURA DA TABELA transacoes_cashback ===\n";

    $stmt = $db->query("DESCRIBE transacoes_cashback");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) " . ($column['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }

    echo "\n=== ÚLTIMA TRANSAÇÃO ===\n";
    $stmt = $db->query("SELECT * FROM transacoes_cashback ORDER BY id DESC LIMIT 1");
    $lastTransaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($lastTransaction) {
        print_r($lastTransaction);
    } else {
        echo "Nenhuma transação encontrada\n";
    }

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
?>