<?php
// fix_duplicate_balance.php - Execute este arquivo

require_once 'config/database.php';

echo "<pre>";
echo "=== CORRIGINDO SALDO DUPLICADO ===\n\n";

try {
    $db = Database::getConnection();
    
    // Verificar saldo atual
    $checkStmt = $db->prepare("
        SELECT saldo_disponivel, total_creditado 
        FROM cashback_saldos 
        WHERE usuario_id = 9 AND loja_id = 13
    ");
    $checkStmt->execute();
    $currentBalance = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($currentBalance) {
        echo "Saldo atual: R$ " . number_format($currentBalance['saldo_disponivel'], 2, ',', '.') . "\n";
        echo "Total creditado: R$ " . number_format($currentBalance['total_creditado'], 2, ',', '.') . "\n\n";
        
        if ($currentBalance['saldo_disponivel'] != 125.00) {
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
            } else {
                echo "✗ Erro ao corrigir saldo\n";
            }
        } else {
            echo "✓ Saldo já está correto\n";
        }
    } else {
        echo "Nenhum saldo encontrado para este cliente/loja\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>