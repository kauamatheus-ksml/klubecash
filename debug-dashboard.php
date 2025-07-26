<?php
session_start();

echo "<h2>🔍 DEBUG Dashboard</h2>";

echo "<h3>Sessão:</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

echo "<h3>Verificações básicas:</h3>";
echo "Logado: " . (isset($_SESSION['user_id']) ? 'SIM' : 'NÃO') . "<br>";
echo "Tipo: " . ($_SESSION['user_type'] ?? 'indefinido') . "<br>";

require_once 'controllers/AuthController.php';
echo "IsAuthenticated: " . (AuthController::isAuthenticated() ? 'SIM' : 'NÃO') . "<br>";

if (file_exists('utils/StoreHelper.php')) {
    require_once 'utils/StoreHelper.php';
    echo "StoreHelper existe: SIM<br>";
    
    try {
        $hasAccess = in_array($_SESSION['user_type'] ?? '', ['loja', 'funcionario']);
        echo "Tem acesso (manual): " . ($hasAccess ? 'SIM' : 'NÃO') . "<br>";
    } catch (Exception $e) {
        echo "ERRO: " . $e->getMessage() . "<br>";
    }
} else {
    echo "StoreHelper existe: NÃO<br>";
}
?>