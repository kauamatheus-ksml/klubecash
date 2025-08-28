<?php
// Verificar se campo MVP existe
require_once 'config/database.php';

try {
    $db = Database::getConnection();
    
    echo "<h3>🔍 Verificando estrutura da tabela usuarios</h3>";
    
    $query = "SHOW COLUMNS FROM usuarios LIKE 'mvp'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "<p>✅ Campo 'mvp' existe!</p>";
        echo "<pre>" . print_r($result, true) . "</pre>";
        
        // Verificar valores
        echo "<h3>📊 Valores MVP existentes:</h3>";
        $valuesQuery = "SELECT email, mvp FROM usuarios WHERE mvp IS NOT NULL ORDER BY mvp DESC";
        $valuesStmt = $db->prepare($valuesQuery);
        $valuesStmt->execute();
        $values = $valuesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($values) {
            foreach ($values as $user) {
                echo "<p>{$user['email']}: <strong>{$user['mvp']}</strong></p>";
            }
        } else {
            echo "<p>❌ Nenhum valor MVP encontrado</p>";
        }
        
    } else {
        echo "<p>❌ Campo 'mvp' NÃO existe na tabela usuarios!</p>";
        echo "<h3>💡 Execute este SQL para criar:</h3>";
        echo "<code>ALTER TABLE usuarios ADD COLUMN mvp ENUM('sim', 'nao') DEFAULT 'nao';</code>";
        
        // Tentar criar automaticamente
        echo "<h3>🔧 Tentando criar automaticamente...</h3>";
        try {
            $createQuery = "ALTER TABLE usuarios ADD COLUMN mvp ENUM('sim', 'nao') DEFAULT 'nao'";
            $createStmt = $db->prepare($createQuery);
            $createStmt->execute();
            echo "<p>✅ Campo criado com sucesso!</p>";
            
            // Definir algumas lojas como MVP para teste
            $updateQuery = "UPDATE usuarios SET mvp = 'sim' WHERE email IN ('kaua@syncholding.com.br', 'kauamathes123487654@gmail.com')";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute();
            echo "<p>✅ Lojas de teste definidas como MVP</p>";
            
        } catch (Exception $e) {
            echo "<p>❌ Erro ao criar campo: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
}
?>