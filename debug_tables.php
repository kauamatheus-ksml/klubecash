<?php
// debug_tables.php - CRIAR TEMPORARIAMENTE
require_once 'config/database.php';

try {
    $db = Database::getConnection();
    
    // Verificar tabelas
    echo "<h3>Verificando tabelas:</h3>";
    
    $tables = ['pagamentos_comissao', 'pagamentos_transacoes', 'transacoes_cashback'];
    
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW CREATE TABLE $table");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<h4>$table:</h4>";
        echo "<pre>" . $result['Create Table'] . "</pre><br>";
    }
    
    
    // Verificar dados
    echo "<h3>Dados atuais:</h3>";
    
    $payments = $db->query("SELECT * FROM pagamentos_comissao ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h4>Últimos pagamentos:</h4>";
    echo "<pre>" . print_r($payments, true) . "</pre>";
    
    $associations = $db->query("SELECT * FROM pagamentos_transacoes ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h4>Associações pagamento-transação:</h4>";
    echo "<pre>" . print_r($associations, true) . "</pre>";
    
    $transactions = $db->query("SELECT * FROM transacoes_cashback WHERE status = 'pagamento_pendente' ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h4>Transações com pagamento pendente:</h4>";
    echo "<pre>" . print_r($transactions, true) . "</pre>";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>