<?php
// fix_duplicate_balance.php - Corrigir saldo duplicado

require_once 'config/database.php';

echo "<pre>";
echo "=== CORRIGINDO SALDO DUPLICADO ===\n\n";

try {
    $db = Database::getConnection();
    
    // Corrigir o saldo do usuário 9 na loja 13
    $stmt = $db->prepare("
        UPDATE cashback_saldos 
        SET saldo_disponivel = 125.00,
            total_creditado = 125.00
        WHERE usuario_id = 9 AND loja_id = 13
    ");
    
    $result = $stmt->execute();
    
    if ($result) {
        echo "✓ Saldo corrigido para R$ 125,00\n";
    }
    
    // Remover movimentação duplicada (manter apenas a mais recente)
    $movStmt = $db->prepare("
        DELETE FROM cashback_movimentacoes 
        WHERE usuario_id = 9 AND loja_id = 13 
        AND id NOT IN (
            SELECT id FROM (
                SELECT MAX(id) as id 
                FROM cashback_movimentacoes 
                WHERE usuario_id = 9 AND loja_id = 13
            ) as temp
        )
    ");
    
    $movResult = $movStmt->execute();
    
    if ($movResult) {
        echo "✓ Movimentação duplicada removida\n";
    }
    
    // Verificar resultado
    $checkStmt = $db->prepare("
        SELECT saldo_disponivel 
        FROM cashback_saldos 
        WHERE usuario_id = 9 AND loja_id = 13
    ");
    $checkStmt->execute();
    $balance = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Saldo final: R$ " . number_format($balance['saldo_disponivel'], 2, ',', '.') . "\n";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>