<?php
session_start();
require_once 'utils/StoreHelper.php';

// Simular algumas ações
$_SESSION['user_id'] = 999;

echo "<h2>🧪 TESTE de Logs de Auditoria</h2>";

StoreHelper::logUserAction(999, 'teste_login', ['ip' => '127.0.0.1']);
StoreHelper::logUserAction(999, 'teste_transacao', ['valor' => 100.50, 'loja_id' => 1]);
StoreHelper::logUserAction(999, 'teste_logout', []);

echo "✅ Logs gerados com sucesso!<br>";
echo "Verifique o error_log do servidor para ver os logs.<br>";

// Mostrar como encontrar os logs
echo "<br><h3>📍 Como encontrar os logs:</h3>";
echo "1. cPanel → Arquivos → Gerenciador de Arquivos<br>";
echo "2. Procure por: <code>error_log</code> ou <code>logs/</code><br>";
echo "3. Busque por: <code>TESTE_AUDIT</code><br>";
?>