<?php
// Teste básico para verificar funcionamento
echo "<!DOCTYPE html>";
echo "<html><head><title>Teste</title></head>";
echo "<body>";
echo "<h1>Teste de Funcionamento</h1>";
echo "<p>Se você está vendo esta mensagem, o PHP está funcionando.</p>";
echo "<p>Data/Hora: " . date('Y-m-d H:i:s') . "</p>";

// Testar conexão com banco
try {
    require_once '../../config/database.php';
    $db = Database::getConnection();
    echo "<p style='color: green;'>Conexão com banco: OK</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro no banco: " . $e->getMessage() . "</p>";
}

// Testar se as classes existem
$classes = ['StoreController', 'Validator'];
foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "<p style='color: green;'>Classe $class: OK</p>";
    } else {
        echo "<p style='color: red;'>Classe $class: Não encontrada</p>";
    }
}

echo "</body></html>";
?>