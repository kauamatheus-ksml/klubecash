<?php
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'controllers/AdminController.php';

// Simular uma sessão de admin
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_type'] = 'admin';

echo "=== TESTE DO SALDO ADMINISTRATIVO ===\n\n";

// Teste 1: Verificar se a tabela existe
try {
    $db = Database::getConnection();
    $stmt = $db->query("SELECT * FROM admin_saldo WHERE id = 1");
    $saldo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($saldo) {
        echo "✓ Tabela admin_saldo existe\n";
        echo "Saldo atual: R$ " . number_format($saldo['valor_disponivel'], 2, ',', '.') . "\n\n";
    } else {
        echo "✗ Registro não encontrado na tabela admin_saldo\n\n";
    }
    
} catch (Exception $e) {
    echo "✗ Erro ao acessar tabela: " . $e->getMessage() . "\n\n";
}

// Teste 2: Tentar adicionar um valor ao saldo
echo "Teste: Adicionando R$ 25,00 ao saldo...\n";
$result = AdminController::updateAdminBalance(25.00, null, "Teste de saldo administrativo");

if ($result) {
    echo "✓ Saldo atualizado com sucesso\n";
} else {
    echo "✗ Erro ao atualizar saldo\n";
}

// Teste 3: Verificar saldo após atualização
try {
    $stmt = $db->query("SELECT * FROM admin_saldo WHERE id = 1");
    $saldo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($saldo) {
        echo "✓ Novo saldo: R$ " . number_format($saldo['valor_disponivel'], 2, ',', '.') . "\n\n";
    }
    
} catch (Exception $e) {
    echo "✗ Erro ao verificar saldo: " . $e->getMessage() . "\n\n";
}

// Teste 4: Obter dados completos
echo "Teste: Obtendo dados completos do saldo...\n";
$balanceData = AdminController::getAdminBalance();

if ($balanceData['status']) {
    echo "✓ Dados obtidos com sucesso\n";
    echo "Saldo Admin: R$ " . number_format($balanceData['data']['saldo_admin']['valor_disponivel'], 2, ',', '.') . "\n";
    echo "Movimentações: " . count($balanceData['data']['movimentacoes']) . "\n";
} else {
    echo "✗ Erro ao obter dados: " . $balanceData['message'] . "\n";
}

echo "\n=== FIM DO TESTE ===\n";
?>