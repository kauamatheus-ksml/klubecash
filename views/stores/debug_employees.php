<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>DEBUG - Funcionários</h1>";

try {
    echo "<p>1. Verificando includes...</p>";
    require_once '../../config/database.php';
    echo "<p>✓ Database OK</p>";
    
    require_once '../../config/constants.php';
    echo "<p>✓ Constants OK</p>";
    
    require_once '../../controllers/AuthController.php';
    echo "<p>✓ AuthController OK</p>";
    
    require_once '../../controllers/StoreController.php';
    echo "<p>✓ StoreController OK</p>";
    
    session_start();
    echo "<p>✓ Session OK</p>";
    
    echo "<p>2. Verificando sessão...</p>";
    echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'NULL') . "</p>";
    echo "<p>User Type: " . ($_SESSION['user_type'] ?? 'NULL') . "</p>";
    echo "<p>User Name: " . ($_SESSION['user_name'] ?? 'NULL') . "</p>";
    
    echo "<p>3. Verificando métodos AuthController...</p>";
    if (method_exists('AuthController', 'requireStoreAccess')) {
        echo "<p>✓ requireStoreAccess existe</p>";
    } else {
        echo "<p>❌ requireStoreAccess NÃO existe</p>";
    }
    
    if (method_exists('AuthController', 'canManageEmployees')) {
        echo "<p>✓ canManageEmployees existe</p>";
    } else {
        echo "<p>❌ canManageEmployees NÃO existe</p>";
    }
    
    if (method_exists('AuthController', 'getStoreId')) {
        echo "<p>✓ getStoreId existe</p>";
    } else {
        echo "<p>❌ getStoreId NÃO existe</p>";
    }
    
    echo "<p>4. Testando métodos...</p>";
    
    if (method_exists('AuthController', 'hasStoreAccess')) {
        $hasAccess = AuthController::hasStoreAccess();
        echo "<p>hasStoreAccess: " . ($hasAccess ? 'TRUE' : 'FALSE') . "</p>";
    }
    
    if (method_exists('StoreController', 'getEmployees')) {
        echo "<p>✓ StoreController::getEmployees existe</p>";
    } else {
        echo "<p>❌ StoreController::getEmployees NÃO existe</p>";
    }
    
    echo "<p>5. Verificando constantes...</p>";
    echo "<p>USER_TYPE_EMPLOYEE: " . (defined('USER_TYPE_EMPLOYEE') ? USER_TYPE_EMPLOYEE : 'NÃO DEFINIDA') . "</p>";
    echo "<p>EMPLOYEE_TYPE_MANAGER: " . (defined('EMPLOYEE_TYPE_MANAGER') ? EMPLOYEE_TYPE_MANAGER : 'NÃO DEFINIDA') . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>ERRO: " . $e->getMessage() . "</p>";
    echo "<p>Arquivo: " . $e->getFile() . "</p>";
    echo "<p>Linha: " . $e->getLine() . "</p>";
}
?>