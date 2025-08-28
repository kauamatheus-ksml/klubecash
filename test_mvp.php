<?php
// Script de teste para funcionalidade MVP
require_once 'config/database.php';

echo "<h2>🧪 Teste da Funcionalidade MVP</h2>";

try {
    $db = Database::getConnection();
    
    // Listar todas as lojas e seus status MVP
    echo "<h3>📊 Status MVP das Lojas:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>ID Loja</th><th>Nome da Loja</th><th>Email</th><th>Status MVP</th><th>Status Loja</th></tr>";
    
    $query = "
        SELECT l.id, l.nome_fantasia, u.email, u.mvp, l.status
        FROM lojas l 
        JOIN usuarios u ON l.usuario_id = u.id 
        ORDER BY l.id ASC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($stores as $store) {
        $mvpStatus = $store['mvp'] === 'sim' ? '🏆 SIM' : '❌ NÃO';
        $storeStatus = $store['status'] === 'aprovado' ? '✅ Aprovado' : '⏳ ' . ucfirst($store['status']);
        $rowColor = $store['mvp'] === 'sim' ? 'background: #fff3cd;' : '';
        
        echo "<tr style='{$rowColor}'>";
        echo "<td>{$store['id']}</td>";
        echo "<td>{$store['nome_fantasia']}</td>";
        echo "<td>{$store['email']}</td>";
        echo "<td>{$mvpStatus}</td>";
        echo "<td>{$storeStatus}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Contar lojas MVP
    $mvpCount = array_filter($stores, function($store) {
        return $store['mvp'] === 'sim';
    });
    
    echo "<br><p><strong>Total de lojas MVP: " . count($mvpCount) . " de " . count($stores) . "</strong></p>";
    
    // Testar query de verificação MVP usado no TransactionController
    echo "<h3>🔍 Teste da Query MVP (como usado no TransactionController):</h3>";
    
    if (!empty($stores)) {
        $firstStoreId = $stores[0]['id'];
        
        $testQuery = "
            SELECT l.*, u.mvp as store_mvp 
            FROM lojas l 
            JOIN usuarios u ON l.usuario_id = u.id 
            WHERE l.id = :loja_id AND l.status = :status
        ";
        
        $testStmt = $db->prepare($testQuery);
        $testStmt->bindParam(':loja_id', $firstStoreId);
        $status = 'aprovado';
        $testStmt->bindParam(':status', $status);
        $testStmt->execute();
        $testResult = $testStmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Testando com Loja ID {$firstStoreId}:</strong></p>";
        if ($testResult) {
            $isMvp = ($testResult['store_mvp'] === 'sim');
            echo "<p>✅ Query executada com sucesso</p>";
            echo "<p>🏷️ Nome: {$testResult['nome_fantasia']}</p>";
            echo "<p>🏆 MVP: " . ($isMvp ? 'SIM - Transações serão aprovadas automaticamente' : 'NÃO - Transações ficarão pendentes') . "</p>";
        } else {
            echo "<p>❌ Nenhum resultado encontrado (loja pode não estar aprovada)</p>";
        }
    }
    
    echo "<h3>📝 Instruções de Teste:</h3>";
    echo "<ol>";
    echo "<li>Para testar a funcionalidade, acesse uma loja MVP e registre uma transação</li>";
    echo "<li>Verifique se a transação aparece como 'aprovado' imediatamente</li>";
    echo "<li>Confirme se o cashback foi creditado automaticamente</li>";
    echo "<li>Compare com uma loja não-MVP (deve ficar pendente)</li>";
    echo "</ol>";
    
    echo "<h3>🔧 Para transformar uma loja em MVP:</h3>";
    echo "<code>UPDATE usuarios SET mvp = 'sim' WHERE email = 'email_da_loja@exemplo.com';</code>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>

<style>
table { margin: 1rem 0; }
th, td { padding: 8px 12px; text-align: left; }
code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
</style>