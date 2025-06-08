<?php
// approve-payment-manual.php
require_once 'config/database.php';
$db = Database::getConnection();

// Verificar tabelas existentes
$tables = $db->query("SHOW TABLES")->fetchAll();
echo "Tabelas disponíveis:<br>";
foreach ($tables as $table) {
    $tableName = array_values($table)[0];
    if (strpos($tableName, 'saldo') !== false || strpos($tableName, 'cashback') !== false) {
        echo "- $tableName<br>";
    }
}

// Buscar transação
$stmt = $db->prepare("SELECT usuario_id, loja_id, valor_total FROM transacoes_cashback WHERE id = 149");
$stmt->execute();
$trans = $stmt->fetch();

if ($trans) {
    $cashback = $trans['valor_total'] * 0.05;
    echo "<br>Cashback calculado: R$ " . number_format($cashback, 2);
    echo "<br>Para usuário: " . $trans['usuario_id'];
}
?>