<?php
// cleanup_test_data.php
require_once 'config/database.php';

try {
    $db = Database::getConnection();
    
    // Resetar saldo para zero
    $resetStmt = $db->prepare("UPDATE admin_saldo SET valor_total = 0.00, valor_disponivel = 0.00, valor_pendente = 0.00 WHERE id = 1");
    $resetStmt->execute();
    
    // Limpar movimentações de teste
    $clearStmt = $db->prepare("DELETE FROM admin_saldo_movimentacoes WHERE descricao LIKE '%teste%' OR descricao LIKE '%Teste%'");
    $clearStmt->execute();
    
    echo "✓ Dados de teste limpos com sucesso\n";
    echo "✓ Saldo resetado para R$ 0,00\n";
    echo "✓ Movimentações de teste removidas\n";
    
} catch (Exception $e) {
    echo "✗ Erro ao limpar dados: " . $e->getMessage() . "\n";
}
?>