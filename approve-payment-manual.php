<?php
require_once 'config/database.php';
$db = Database::getConnection();

try {
    $stmt = $db->prepare("INSERT INTO cashback_saldos (usuario_id, loja_id, valor) VALUES (9, 34, 0.25)");
    $result = $stmt->execute();
    echo $result ? "✅ OK" : "❌ Erro";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>