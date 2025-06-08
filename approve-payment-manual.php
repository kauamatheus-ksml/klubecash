<?php
// approve-payment-manual.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'config/database.php';
    $db = Database::getConnection();
    
    // Buscar dados da transação
    $stmt = $db->prepare("SELECT usuario_id, loja_id, valor_total FROM transacoes_cashback WHERE id = 149");
    $stmt->execute();
    $trans = $stmt->fetch();
    
    if ($trans) {
        $cashback = $trans['valor_total'] * 0.05; // 5% de cashback
        
        // Liberar cashback
        $cashStmt = $db->prepare("
            INSERT INTO saldos_cashback (usuario_id, loja_id, valor, tipo, origem_transacao_id)
            VALUES (?, ?, ?, 'cashback', 149)
            ON DUPLICATE KEY UPDATE valor = valor + VALUES(valor)
        ");
        $result = $cashStmt->execute([$trans['usuario_id'], $trans['loja_id'], $cashback]);
        
        echo $result ? "✅ Cashback de R$ " . number_format($cashback, 2) . " liberado!" : "❌ Erro no cashback";
    } else {
        echo "❌ Transação não encontrada";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>