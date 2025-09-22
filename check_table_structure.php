<?php
require_once __DIR__ . '/config/database.php';

echo "📋 ESTRUTURA DA TABELA transacoes_cashback:\n\n";

try {
    $db = Database::getConnection();
    $stmt = $db->query('DESCRIBE transacoes_cashback');

    echo "Campo | Tipo | Null | Key | Default\n";
    echo "------+------+------+-----+--------\n";

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} | {$row['Type']} | {$row['Null']} | {$row['Key']} | {$row['Default']}\n";
    }

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>