<?php
// test-openpix-flow.php
try {
    $db = new PDO("mysql:host=localhost;dbname=u297617088_klubecash", "u297617088_root", "Aaku_2004@");
    
    echo "✅ Conexão OK<br>";
    
    // Verificar saldo atual
    $stmt = $db->prepare("SELECT saldo_disponivel FROM cashback_saldos WHERE usuario_id = 9 AND loja_id = 34");
    $stmt->execute();
    $saldoAtual = $stmt->fetchColumn() ?: 0;
    echo "Saldo atual: R$ " . number_format($saldoAtual, 2) . "<br>";
    
    // Adicionar R$ 0,25 de teste
    $stmt = $db->prepare("INSERT INTO cashback_saldos (usuario_id, loja_id, saldo_disponivel) VALUES (9, 34, 0.25) ON DUPLICATE KEY UPDATE saldo_disponivel = saldo_disponivel + 0.25");
    $result = $stmt->execute();
    
    echo $result ? "✅ Cashback adicionado" : "❌ Erro";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>