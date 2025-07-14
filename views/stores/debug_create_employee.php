<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/StoreController.php';

session_start();

echo "<h1>DEBUG - Criar/Editar Funcionário</h1>";

// Testar criação de funcionário
$testData = [
    'nome' => 'Teste Debug',
    'email' => 'teste.debug@test.com',
    'telefone' => '11999999999',
    'senha' => '12345678',
    'subtipo_funcionario' => EMPLOYEE_TYPE_SALESPERSON
];

echo "<h2>1. Testando criação de funcionário:</h2>";
echo "<p>Dados teste: " . json_encode($testData) . "</p>";

try {
    $result = StoreController::createEmployee($testData);
    echo "<p>Resultado: " . json_encode($result) . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>ERRO na criação: " . $e->getMessage() . "</p>";
    echo "<p>Arquivo: " . $e->getFile() . "</p>";
    echo "<p>Linha: " . $e->getLine() . "</p>";
}

echo "<h2>2. Testando banco de dados:</h2>";
try {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'funcionario'");
    $stmt->execute();
    $count = $stmt->fetch();
    echo "<p>Funcionários no banco: " . $count['total'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>ERRO no banco: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Verificando StoreController métodos:</h2>";
$methods = ['createEmployee', 'updateEmployee', 'deleteEmployee', 'getEmployees'];
foreach ($methods as $method) {
    if (method_exists('StoreController', $method)) {
        echo "<p>✓ {$method} existe</p>";
    } else {
        echo "<p>❌ {$method} NÃO existe</p>";
    }
}
?>