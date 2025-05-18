<?php
// debug_payment.php (CRIAR NA RAIZ DO PROJETO - TEMPORÁRIO)
require_once 'config/database.php';
require_once 'config/constants.php';

// Testar conexão
try {
    $db = Database::getConnection();
    echo "Conexão OK<br>";
    
    // Testar inserção simples
    $stmt = $db->prepare("INSERT INTO pagamentos_comissao (loja_id, valor_total, metodo_pagamento, status) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([9, 100.00, 'teste', 'pendente']);
    
    if ($result) {
        echo "Inserção OK - ID: " . $db->lastInsertId() . "<br>";
        
        // Verificar se foi inserido
        $check = $db->query("SELECT * FROM pagamentos_comissao ORDER BY id DESC LIMIT 1");
        $payment = $check->fetch(PDO::FETCH_ASSOC);
        echo "Último pagamento: " . print_r($payment, true);
    } else {
        echo "Erro na inserção: " . print_r($stmt->errorInfo(), true);
    }
    
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>