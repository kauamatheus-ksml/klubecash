<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getConnection();

    echo "=== ESTRUTURA DA TABELA USUARIOS ===\n";
    $result = $db->query('DESCRIBE usuarios');
    while($row = $result->fetch()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }

    echo "\n=== ESTRUTURA DA TABELA TRANSACOES_CASHBACK ===\n";
    $result = $db->query('DESCRIBE transacoes_cashback');
    while($row = $result->fetch()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }

    echo "\n=== ESTRUTURA DA TABELA LOJAS ===\n";
    $result = $db->query('DESCRIBE lojas');
    while($row = $result->fetch()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>