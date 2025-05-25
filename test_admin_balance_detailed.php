<?php
// test_admin_balance_detailed.php
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'controllers/AdminController.php';

// Simular uma sessão de admin
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_type'] = 'admin';

echo "=== TESTE DETALHADO DO SALDO ADMINISTRATIVO ===\n\n";

// Teste 1: Verificar conexão com banco
echo "1. Testando conexão com banco de dados...\n";
try {
    $db = Database::getConnection();
    if ($db) {
        echo "✓ Conexão com banco estabelecida\n\n";
    } else {
        echo "✗ Falha na conexão com banco\n\n";
        exit;
    }
} catch (Exception $e) {
    echo "✗ Erro na conexão: " . $e->getMessage() . "\n\n";
    exit;
}

// Teste 2: Verificar estrutura da tabela
echo "2. Verificando estrutura da tabela admin_saldo...\n";
try {
    $stmt = $db->query("DESCRIBE admin_saldo");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Colunas encontradas:\n";
    foreach ($columns as $column) {
        echo "  - {$column['Field']} ({$column['Type']})\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "✗ Erro ao verificar estrutura: " . $e->getMessage() . "\n\n";
}

// Teste 3: Verificar registro existente
echo "3. Verificando registro existente...\n";
try {
    $stmt = $db->query("SELECT * FROM admin_saldo WHERE id = 1");
    $saldo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($saldo) {
        echo "✓ Registro encontrado:\n";
        echo "  - ID: {$saldo['id']}\n";
        echo "  - Valor Total: {$saldo['valor_total']}\n";
        echo "  - Valor Disponível: {$saldo['valor_disponivel']}\n";
        echo "  - Valor Pendente: {$saldo['valor_pendente']}\n";
        echo "  - Última Atualização: {$saldo['ultima_atualizacao']}\n\n";
    } else {
        echo "✗ Nenhum registro encontrado\n\n";
    }
} catch (Exception $e) {
    echo "✗ Erro ao buscar registro: " . $e->getMessage() . "\n\n";
}

// Teste 4: Testar atualização manual do banco
echo "4. Testando atualização manual no banco...\n";
try {
    $stmt = $db->prepare("UPDATE admin_saldo SET valor_total = valor_total + 10.00 WHERE id = 1");
    $result = $stmt->execute();
    
    if ($result) {
        echo "✓ Atualização manual bem-sucedida\n";
        
        // Verificar se realmente atualizou
        $checkStmt = $db->query("SELECT valor_total FROM admin_saldo WHERE id = 1");
        $newTotal = $checkStmt->fetch(PDO::FETCH_ASSOC)['valor_total'];
        echo "✓ Novo valor total: $newTotal\n\n";
        
        // Reverter a mudança
        $revertStmt = $db->prepare("UPDATE admin_saldo SET valor_total = valor_total - 10.00 WHERE id = 1");
        $revertStmt->execute();
        echo "✓ Mudança revertida\n\n";
    } else {
        echo "✗ Falha na atualização manual\n\n";
    }
} catch (Exception $e) {
    echo "✗ Erro na atualização manual: " . $e->getMessage() . "\n\n";
}

// Teste 5: Testar método AdminController
echo "5. Testando método AdminController::updateAdminBalance...\n";
$result = AdminController::updateAdminBalance(25.00, null, "Teste detalhado de saldo");

if ($result) {
    echo "✓ Método executado com sucesso\n";
} else {
    echo "✗ Método falhou\n";
}

// Verificar resultado final
echo "\n6. Verificando resultado final...\n";
try {
    $stmt = $db->query("SELECT * FROM admin_saldo WHERE id = 1");
    $finalSaldo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Saldo final: R$ " . number_format($finalSaldo['valor_disponivel'], 2, ',', '.') . "\n";
    
    // Verificar movimentações
    $movStmt = $db->query("SELECT COUNT(*) as total FROM admin_saldo_movimentacoes");
    $totalMov = $movStmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Total de movimentações: $totalMov\n";
    
} catch (Exception $e) {
    echo "✗ Erro ao verificar resultado: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DO TESTE DETALHADO ===\n";
?>