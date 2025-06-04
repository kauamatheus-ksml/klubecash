<?php
echo "<h1>Teste PHP funcionando!</h1>";
echo "<p>SITE_URL: " . (defined('SITE_URL') ? SITE_URL : 'NÃO DEFINIDO') . "</p>";

// Testar banco
try {
    require_once './config/database.php';
    $db = Database::getConnection();
    echo "<p>✅ Conexão com banco: OK</p>";
} catch (Exception $e) {
    echo "<p>❌ Erro no banco: " . $e->getMessage() . "</p>";
}

// Testar constantes
try {
    require_once './config/constants.php';
    echo "<p>✅ Constants carregadas: OK</p>";
    echo "<p>SITE_URL: " . SITE_URL . "</p>";
} catch (Exception $e) {
    echo "<p>❌ Erro nas constants: " . $e->getMessage() . "</p>";
}
?>