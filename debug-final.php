<?php
session_start();
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';

echo "<h2>🔍 DEBUG FINAL</h2>";

echo "<h3>1. Sessão Atual:</h3>";
foreach ($_SESSION as $key => $value) {
    echo "<strong>{$key}:</strong> " . htmlspecialchars($value) . "<br>";
}

echo "<h3>2. Métodos AuthController:</h3>";
echo "<strong>isAuthenticated:</strong> " . (AuthController::isAuthenticated() ? 'SIM' : 'NÃO') . "<br>";
echo "<strong>hasStoreAccess:</strong> " . (AuthController::hasStoreAccess() ? 'SIM' : 'NÃO') . "<br>";
echo "<strong>getStoreId:</strong> " . (AuthController::getStoreId() ?? 'NULL') . "<br>";
echo "<strong>canManageEmployees:</strong> " . (AuthController::canManageEmployees() ? 'SIM' : 'NÃO') . "<br>";

echo "<h3>3. Dados da Loja:</h3>";
$storeData = AuthController::getStoreData();
if ($storeData) {
    echo "<strong>Nome:</strong> {$storeData['nome_fantasia']}<br>";
    echo "<strong>Status:</strong> {$storeData['status']}<br>";
    echo "<strong>ID:</strong> {$storeData['id']}<br>";
} else {
    echo "<strong style='color:red;'>❌ NENHUM DADO DA LOJA ENCONTRADO</strong>";
}

echo "<h3>4. Teste de Acesso:</h3>";
echo "<a href='store/dashboard/' style='background: #4CAF50; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>🏠 Dashboard Loja</a><br><br>";
echo "<a href='views/stores/funcionarios.php' style='background: #2196F3; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>👥 Funcionários</a>";
?>