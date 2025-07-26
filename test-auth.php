<?php
/**
 * TESTE das modificações no AuthController
 */

require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'controllers/AuthController.php';
require_once 'utils/StoreHelper.php';

session_start();

echo "<h2>🧪 TESTE AuthController + StoreHelper</h2>";

// Simular usuário lojista logado
$_SESSION['user_id'] = 1;
$_SESSION['user_type'] = USER_TYPE_STORE;
$_SESSION['store_id'] = 1;

echo "<h3>Testando usuário LOJISTA:</h3>";
echo "✅ IsAuthenticated: " . (AuthController::isAuthenticated() ? 'SIM' : 'NÃO') . "<br>";
echo "✅ IsStore: " . (AuthController::isStore() ? 'SIM' : 'NÃO') . "<br>";

// Testar funções novas (se já foram adicionadas)
if (method_exists('AuthController', 'hasStoreAccess')) {
    echo "✅ HasStoreAccess: " . (AuthController::hasStoreAccess() ? 'SIM' : 'NÃO') . "<br>";
    echo "✅ StoreId: " . AuthController::getStoreId() . "<br>";
} else {
    echo "⚠️  Funções novas ainda não foram adicionadas ao AuthController<br>";
}

echo "<br><h3>Testando usuário FUNCIONÁRIO:</h3>";
// Simular funcionário
$_SESSION['user_type'] = USER_TYPE_EMPLOYEE;
$_SESSION['loja_vinculada_id'] = 1;
$_SESSION['subtipo_funcionario'] = 'gerente';

echo "✅ User type: " . $_SESSION['user_type'] . "<br>";
echo "✅ Loja vinculada: " . $_SESSION['loja_vinculada_id'] . "<br>";
echo "✅ Subtipo: " . $_SESSION['subtipo_funcionario'] . "<br>";

// Testar StoreHelper
echo "<br><h3>Testando StoreHelper:</h3>";
StoreHelper::requireStoreAccess();
echo "✅ Store ID via Helper: " . StoreHelper::getCurrentStoreId() . "<br>";
StoreHelper::logUserAction($_SESSION['user_id'], 'teste_funcionario', ['subtipo' => $_SESSION['subtipo_funcionario']]);

echo "<br><h3>🔍 Verificar logs:</h3>";
echo "Verifique o error_log do PHP para ver os logs de auditoria.<br>";
?>