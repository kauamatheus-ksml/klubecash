<?php
session_start();
require_once 'utils/StoreHelper.php';
require_once 'controllers/AuthController.php';

echo "<h2>🔍 DEBUG de Sessão</h2>";

echo "<h3>Sessão atual:</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

echo "<h3>Verificações:</h3>";
echo "IsAuthenticated: " . (AuthController::isAuthenticated() ? 'SIM' : 'NÃO') . "<br>";
echo "IsStore: " . (AuthController::isStore() ? 'SIM' : 'NÃO') . "<br>";
echo "IsEmployee: " . (AuthController::isEmployee() ? 'SIM' : 'NÃO') . "<br>";

if (method_exists('AuthController', 'hasStoreAccess')) {
    echo "HasStoreAccess: " . (AuthController::hasStoreAccess() ? 'SIM' : 'NÃO') . "<br>";
}

echo "<h3>IDs:</h3>";
echo "StoreId via Helper: " . StoreHelper::getCurrentStoreId() . "<br>";

if (method_exists('AuthController', 'getStoreId')) {
    echo "StoreId via Auth: " . AuthController::getStoreId() . "<br>";
}

echo "<h3>Store Data:</h3>";
if (method_exists('AuthController', 'getStoreData')) {
    $storeData = AuthController::getStoreData();
    echo "<pre>" . print_r($storeData, true) . "</pre>";
}
?>