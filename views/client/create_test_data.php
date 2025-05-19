<?php
// create_test_data.php
require_once 'config/database.php';

try {
    $db = Database::getConnection();
    
    // Inserir lojas de teste se não existirem
    $testStores = [
        ['Magazine Luiza', 'Varejo', 3.5],
        ['Americanas', 'Varejo', 2.8],
        ['Submarino', 'Eletrônicos', 4.2],
        ['Netshoes', 'Esportes', 5.0],
        ['Zara', 'Moda', 3.0]
    ];
    
    foreach ($testStores as $store) {
        $checkQuery = "SELECT id FROM lojas WHERE nome_fantasia = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$store[0]]);
        
        if ($checkStmt->rowCount() == 0) {
            $insertQuery = "
                INSERT INTO lojas (nome_fantasia, categoria, porcentagem_cashback, status) 
                VALUES (?, ?, ?, 'aprovado')
            ";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->execute($store);
            echo "Loja '{$store[0]}' criada.<br>";
        } else {
            echo "Loja '{$store[0]}' já existe.<br>";
        }
    }
    
    echo "Dados de teste criados com sucesso!";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>