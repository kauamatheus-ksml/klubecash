<?php
// Script de setup completo para funcionalidade MVP
require_once 'config/database.php';

echo "<h2>🚀 Setup da Funcionalidade MVP</h2>";

try {
    $db = Database::getConnection();
    $setupComplete = true;
    
    // 1. Verificar se campo MVP existe
    echo "<h3>1️⃣ Verificando campo MVP...</h3>";
    $checkQuery = "SHOW COLUMNS FROM usuarios LIKE 'mvp'";
    $stmt = $db->prepare($checkQuery);
    $stmt->execute();
    $mvpField = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mvpField) {
        echo "<p>❌ Campo MVP não existe. Criando...</p>";
        try {
            $createQuery = "ALTER TABLE usuarios ADD COLUMN mvp ENUM('sim', 'nao') DEFAULT 'nao'";
            $db->prepare($createQuery)->execute();
            echo "<p>✅ Campo MVP criado com sucesso!</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Erro ao criar campo: " . $e->getMessage() . "</p>";
            $setupComplete = false;
        }
    } else {
        echo "<p>✅ Campo MVP já existe</p>";
    }
    
    // 2. Configurar algumas lojas como MVP para teste
    echo "<h3>2️⃣ Configurando lojas MVP de teste...</h3>";
    $mvpEmails = [
        'kaua@syncholding.com.br',
        'kauamathes123487654@gmail.com'
    ];
    
    foreach ($mvpEmails as $email) {
        $updateQuery = "UPDATE usuarios SET mvp = 'sim' WHERE email = :email AND tipo = 'loja'";
        $stmt = $db->prepare($updateQuery);
        $stmt->bindParam(':email', $email);
        $result = $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo "<p>✅ {$email} configurado como MVP</p>";
        } else {
            echo "<p>⚠️ {$email} não encontrado ou não é loja</p>";
        }
    }
    
    // 3. Testar query do TransactionController
    echo "<h3>3️⃣ Testando query do TransactionController...</h3>";
    $testQuery = "
        SELECT l.id, l.nome_fantasia, 
               COALESCE(u.mvp, 'nao') as store_mvp 
        FROM lojas l 
        JOIN usuarios u ON l.usuario_id = u.id 
        WHERE l.status = 'aprovado'
        ORDER BY u.mvp DESC, l.id ASC
    ";
    
    $stmt = $db->prepare($testQuery);
    $stmt->execute();
    $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($stores) {
        echo "<p>✅ Query executada com sucesso. Encontradas " . count($stores) . " lojas aprovadas</p>";
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Nome</th><th>Status MVP</th></tr>";
        
        foreach ($stores as $store) {
            $mvpStatus = $store['store_mvp'] === 'sim' ? '🏆 MVP' : '📝 Normal';
            $rowColor = $store['store_mvp'] === 'sim' ? 'background: #fff3cd;' : '';
            
            echo "<tr style='{$rowColor}'>";
            echo "<td>{$store['id']}</td>";
            echo "<td>" . htmlspecialchars($store['nome_fantasia']) . "</td>";
            echo "<td>{$mvpStatus}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p>⚠️ Nenhuma loja aprovada encontrada</p>";
    }
    
    // 4. Verificar TransactionController
    echo "<h3>4️⃣ Verificando TransactionController...</h3>";
    if (class_exists('TransactionController')) {
        echo "<p>✅ TransactionController já carregado</p>";
    } else {
        if (file_exists('controllers/TransactionController.php')) {
            echo "<p>✅ Arquivo TransactionController.php encontrado</p>";
        } else {
            echo "<p style='color: red;'>❌ Arquivo TransactionController.php não encontrado</p>";
            $setupComplete = false;
        }
    }
    
    // 5. Verificar página de registro de transação
    echo "<h3>5️⃣ Verificando página de registro...</h3>";
    if (file_exists('views/stores/register-transaction.php')) {
        echo "<p>✅ Página register-transaction.php encontrada</p>";
    } else {
        echo "<p style='color: red;'>❌ Página register-transaction.php não encontrada</p>";
        $setupComplete = false;
    }
    
    // 6. Status final
    echo "<h3>6️⃣ Status Final</h3>";
    if ($setupComplete) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;'>";
        echo "<h4>🎉 Setup Completo!</h4>";
        echo "<p>✅ Funcionalidade MVP está pronta para uso</p>";
        echo "<p>📝 <strong>Como testar:</strong></p>";
        echo "<ol>";
        echo "<li>Acesse uma loja MVP (IDs com 🏆 acima)</li>";
        echo "<li>Faça login na loja</li>";
        echo "<li>Vá para 'Registrar Transação'</li>";
        echo "<li>Registre uma venda</li>";
        echo "<li>Observe o feedback dourado de aprovação instantânea</li>";
        echo "</ol>";
        echo "</div>";
        
        echo "<h4>🔧 Comandos úteis:</h4>";
        echo "<ul>";
        echo "<li><strong>Ver lojas MVP:</strong> <code>SELECT l.nome_fantasia, u.email FROM lojas l JOIN usuarios u ON l.usuario_id = u.id WHERE u.mvp = 'sim'</code></li>";
        echo "<li><strong>Transformar loja em MVP:</strong> <code>UPDATE usuarios SET mvp = 'sim' WHERE email = 'email@loja.com'</code></li>";
        echo "<li><strong>Remover status MVP:</strong> <code>UPDATE usuarios SET mvp = 'nao' WHERE email = 'email@loja.com'</code></li>";
        echo "</ul>";
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545;'>";
        echo "<h4>❌ Setup Incompleto</h4>";
        echo "<p>Algumas verificações falharam. Revise os erros acima.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545;'>";
    echo "<h4>❌ Erro Fatal</h4>";
    echo "<p>Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}
?>

<style>
table { border-collapse: collapse; margin: 1rem 0; }
th, td { padding: 8px 12px; text-align: left; border: 1px solid #ddd; }
th { background: #f5f5f5; }
code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
</style>