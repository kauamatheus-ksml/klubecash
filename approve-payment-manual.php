<?php
// approve-payment-manual.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'config/database.php';
    $db = Database::getConnection();
    
    // Aprovar transação
    $stmt = $db->prepare("UPDATE transacoes_cashback SET status = 'aprovado' WHERE id = 149");
    $result = $stmt->execute();
    
    echo $result ? "✅ Sucesso" : "❌ Falhou";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>