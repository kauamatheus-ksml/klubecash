<?php
// Debug do sistema MVP
require_once 'config/database.php';

echo "<h2>🔧 Debug Sistema MVP</h2>";

try {
    $db = Database::getConnection();
    
    // Verificar estrutura da tabela usuarios
    echo "<h3>1. Verificar se campo 'mvp' existe na tabela usuarios:</h3>";
    $descQuery = "DESCRIBE usuarios";
    $stmt = $db->prepare($descQuery);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $mvpExists = false;
    echo "<table border='1'><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        if ($col['Field'] === 'mvp') {
            $mvpExists = true;
            echo "<tr style='background: yellow;'>";
        } else {
            echo "<tr>";
        }
        echo "<td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Default']}</td></tr>";
    }
    echo "</table>";
    
    if (!$mvpExists) {
        echo "<p style='color: red;'>❌ PROBLEMA: Campo 'mvp' não existe na tabela usuarios!</p>";
        echo "<h3>💡 Solução: Execute este comando SQL:</h3>";
        echo "<code>ALTER TABLE usuarios ADD COLUMN mvp ENUM('sim', 'nao') DEFAULT 'nao';</code>";
    } else {
        echo "<p style='color: green;'>✅ Campo 'mvp' existe na tabela usuarios</p>";
        
        // Testar a query específica do TransactionController
        echo "<h3>2. Testar query do TransactionController:</h3>";
        
        $testQuery = "
            SELECT l.*, u.mvp as store_mvp 
            FROM lojas l 
            JOIN usuarios u ON l.usuario_id = u.id 
            WHERE l.id = :loja_id AND l.status = :status
        ";
        
        // Pegar uma loja para teste
        $getStoreQuery = "SELECT id FROM lojas WHERE status = 'aprovado' LIMIT 1";
        $storeStmt = $db->prepare($getStoreQuery);
        $storeStmt->execute();
        $testStore = $storeStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($testStore) {
            $testStmt = $db->prepare($testQuery);
            $testStmt->bindParam(':loja_id', $testStore['id']);
            $status = 'aprovado';
            $testStmt->bindParam(':status', $status);
            $testStmt->execute();
            $result = $testStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                echo "<p>✅ Query executada com sucesso para loja ID {$testStore['id']}</p>";
                echo "<p>🏷️ Nome: {$result['nome_fantasia']}</p>";
                echo "<p>🏆 MVP: {$result['store_mvp']}</p>";
                echo "<p>👤 Usuario ID: {$result['usuario_id']}</p>";
            } else {
                echo "<p style='color: red;'>❌ Query falhou - nenhum resultado</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ Nenhuma loja aprovada encontrada para teste</p>";
        }
        
        // Verificar valores MVP existentes
        echo "<h3>3. Valores MVP na base:</h3>";
        $mvpQuery = "SELECT email, mvp, tipo FROM usuarios WHERE tipo IN ('loja', 'admin') ORDER BY mvp DESC";
        $mvpStmt = $db->prepare($mvpQuery);
        $mvpStmt->execute();
        $mvpResults = $mvpStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1'><tr><th>Email</th><th>Tipo</th><th>MVP</th></tr>";
        foreach ($mvpResults as $user) {
            $bgColor = $user['mvp'] === 'sim' ? 'background: #d4edda;' : '';
            echo "<tr style='{$bgColor}'>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['tipo']}</td>";
            echo "<td>" . ($user['mvp'] === 'sim' ? '🏆 SIM' : '❌ NÃO') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Testar TransactionController diretamente
    echo "<h3>4. Teste de Transação Simulada:</h3>";
    
    require_once 'controllers/TransactionController.php';
    
    // Dados de teste
    $testData = [
        'loja_id' => 34, // ID da loja MVP do teste
        'usuario_id' => 1, // Um usuário cliente qualquer
        'valor_total' => 100.00,
        'codigo_transacao' => 'TEST_MVP_' . time(),
        'descricao' => 'Teste MVP - ' . date('Y-m-d H:i:s')
    ];
    
    echo "<p>📝 Simulando transação com dados:</p>";
    echo "<pre>" . print_r($testData, true) . "</pre>";
    
    // Não executar a transação real, apenas mostrar os dados
    echo "<p>ℹ️ Para executar teste real, descomente o código abaixo no arquivo</p>";
    
    /*
    // DESCOMENTE PARA TESTE REAL:
    $result = TransactionController::registerTransaction($testData);
    echo "<h4>Resultado:</h4>";
    echo "<pre>" . print_r($result, true) . "</pre>";
    */
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ERRO: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>

<style>
table { margin: 1rem 0; border-collapse: collapse; }
th, td { padding: 8px 12px; text-align: left; border: 1px solid #ddd; }
th { background: #f5f5f5; }
code { background: #f4f4f4; padding: 4px 8px; border-radius: 3px; display: block; margin: 10px 0; }
pre { background: #f8f8f8; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style>