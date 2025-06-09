<?php
// test-openpix-flow.php
require_once 'config/constants.php';

try {
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    
    echo "✅ Conexão OK<br>";
    
    // Verificar saldo atual
    $stmt = $db->prepare("SELECT saldo_disponivel FROM cashback_saldos WHERE usuario_id = 9 AND loja_id = 34");
    $stmt->execute();
    $saldoAtual = $stmt->fetchColumn() ?: 0;
    echo "Saldo atual: R$ " . number_format($saldoAtual, 2) . "<br>";
    
    // Simular cashback de R$ 0,25
    $stmt = $db->prepare("INSERT INTO cashback_saldos (usuario_id, loja_id, saldo_disponivel) VALUES (9, 34, 0.25) ON DUPLICATE KEY UPDATE saldo_disponivel = saldo_disponivel + 0.25");
    $result = $stmt->execute();
    
    echo $result ? "✅ Teste OK - Sistema funcionando!" : "❌ Erro";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>