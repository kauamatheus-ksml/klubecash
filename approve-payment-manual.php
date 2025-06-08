<?php
// approve-payment-manual.php
try {
    // Use as mesmas credenciais do constants.php
    $db = new PDO("mysql:host=localhost;dbname=u297617088_klubecash", "u297617088_root", "Aaku_2004@");
    
    // Inserir cashback diretamente
    $stmt = $db->prepare("
        INSERT INTO cashback_saldos (usuario_id, loja_id, valor_disponivel) 
        VALUES (9, 34, 0.25)
        ON DUPLICATE KEY UPDATE valor_disponivel = valor_disponivel + 0.25
    ");
    $result = $stmt->execute();
    
    echo $result ? "✅ Cashback liberado!" : "❌ Erro";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>