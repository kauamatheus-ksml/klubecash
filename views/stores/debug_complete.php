<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<style>
body { font-family: Arial; margin: 20px; }
.success { color: green; }
.error { color: red; }
.warning { color: orange; }
.section { border: 1px solid #ccc; padding: 15px; margin: 10px 0; }
.code { background: #f5f5f5; padding: 10px; margin: 5px 0; }
</style>";

echo "<h1>🔍 DEBUG COMPLETO - Klube Cash</h1>";

try {
    // 1. VERIFICAR INCLUDES
    echo "<div class='section'><h2>1. Verificando Includes</h2>";
    
    $files = [
        '../../config/database.php',
        '../../config/constants.php', 
        '../../controllers/AuthController.php',
        '../../controllers/StoreController.php'
    ];
    
    foreach ($files as $file) {
        if (file_exists($file)) {
            require_once $file;
            echo "<p class='success'>✓ {$file}</p>";
        } else {
            echo "<p class='error'>❌ {$file} - NÃO ENCONTRADO</p>";
        }
    }
    
    // 2. VERIFICAR SESSÃO
    echo "</div><div class='section'><h2>2. Verificando Sessão</h2>";
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $sessionVars = ['user_id', 'user_type', 'user_name', 'employee_subtype', 'store_id'];
    foreach ($sessionVars as $var) {
        $value = $_SESSION[$var] ?? 'NULL';
        echo "<p><strong>{$var}:</strong> {$value}</p>";
    }
    
    // 3. VERIFICAR CONSTANTES
    echo "</div><div class='section'><h2>3. Verificando Constantes</h2>";
    
    $constants = [
        'USER_TYPE_EMPLOYEE', 'USER_TYPE_STORE', 'EMPLOYEE_TYPE_MANAGER',
        'EMPLOYEE_TYPE_FINANCIAL', 'EMPLOYEE_TYPE_SALESPERSON', 'PASSWORD_MIN_LENGTH'
    ];
    
    foreach ($constants as $const) {
        if (defined($const)) {
            echo "<p class='success'>✓ {$const}: " . constant($const) . "</p>";
        } else {
            echo "<p class='error'>❌ {$const} não definida</p>";
        }
    }
    
    // 4. VERIFICAR MÉTODOS AUTHCONTROLLER
    echo "</div><div class='section'><h2>4. Verificando AuthController</h2>";
    
    $authMethods = [
        'requireStoreAccess', 'hasStoreAccess', 'canManageEmployees',
        'getStoreId', 'getStoreData', 'isStore', 'isEmployee'
    ];
    
    foreach ($authMethods as $method) {
        if (method_exists('AuthController', $method)) {
            echo "<p class='success'>✓ AuthController::{$method}</p>";
        } else {
            echo "<p class='error'>❌ AuthController::{$method} NÃO EXISTE</p>";
        }
    }
    
    // 5. TESTAR MÉTODOS AUTHCONTROLLER
    echo "</div><div class='section'><h2>5. Testando Métodos AuthController</h2>";
    
    try {
        $hasStoreAccess = AuthController::hasStoreAccess();
        echo "<p>hasStoreAccess(): " . ($hasStoreAccess ? 'TRUE' : 'FALSE') . "</p>";
        
        $canManage = AuthController::canManageEmployees();
        echo "<p>canManageEmployees(): " . ($canManage ? 'TRUE' : 'FALSE') . "</p>";
        
        $storeId = AuthController::getStoreId();
        echo "<p>getStoreId(): " . ($storeId ?? 'NULL') . "</p>";
        
    } catch (Exception $e) {
        echo "<p class='error'>ERRO nos métodos: " . $e->getMessage() . "</p>";
    }
    
    // 6. VERIFICAR MÉTODOS STORECONTROLLER
    echo "</div><div class='section'><h2>6. Verificando StoreController</h2>";
    
    $storeMethods = ['getEmployees', 'createEmployee', 'updateEmployee', 'deleteEmployee'];
    
    foreach ($storeMethods as $method) {
        if (method_exists('StoreController', $method)) {
            echo "<p class='success'>✓ StoreController::{$method}</p>";
        } else {
            echo "<p class='error'>❌ StoreController::{$method} NÃO EXISTE</p>";
        }
    }
    
    // 7. TESTAR CONEXÃO DATABASE
    echo "</div><div class='section'><h2>7. Testando Database</h2>";
    
    try {
        $db = Database::getConnection();
        echo "<p class='success'>✓ Conexão estabelecida</p>";
        
        // Verificar se tabelas existem
        $tables = ['usuarios', 'lojas'];
        foreach ($tables as $table) {
            $stmt = $db->query("SHOW TABLES LIKE '{$table}'");
            if ($stmt->rowCount() > 0) {
                echo "<p class='success'>✓ Tabela {$table} existe</p>";
            } else {
                echo "<p class='error'>❌ Tabela {$table} não existe</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Database: " . $e->getMessage() . "</p>";
    }
    
    // 8. TESTAR STORECONTROLLER::GETEMPLOYEES
    echo "</div><div class='section'><h2>8. Testando getEmployees()</h2>";
    
    try {
        $result = StoreController::getEmployees([], 1);
        echo "<p class='success'>✓ getEmployees executado</p>";
        echo "<div class='code'>" . json_encode($result, JSON_PRETTY_PRINT) . "</div>";
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ getEmployees: " . $e->getMessage() . "</p>";
        echo "<p>Arquivo: " . $e->getFile() . " - Linha: " . $e->getLine() . "</p>";
    }
    
    // 9. VERIFICAR ESTRUTURA BANCO
    echo "</div><div class='section'><h2>9. Estrutura do Banco</h2>";
    
    try {
        $storeId = AuthController::getStoreId();
        if ($storeId) {
            // Verificar loja na tabela usuarios
            $userStmt = $db->prepare("SELECT * FROM usuarios WHERE id = ? AND tipo = 'loja'");
            $userStmt->execute([$storeId]);
            $userExists = $userStmt->rowCount() > 0;
            echo "<p>Lojista na tabela usuarios: " . ($userExists ? 'SIM' : 'NÃO') . "</p>";
            
            // Verificar loja na tabela lojas
            $lojaStmt = $db->prepare("SELECT * FROM lojas WHERE id = ?");
            $lojaStmt->execute([$storeId]);
            $lojaExists = $lojaStmt->rowCount() > 0;
            echo "<p>Loja na tabela lojas: " . ($lojaExists ? 'SIM' : 'NÃO') . "</p>";
            
            // Contar funcionários
            $funcStmt = $db->prepare("SELECT COUNT(*) as total FROM usuarios WHERE loja_vinculada_id = ? AND tipo = 'funcionario'");
            $funcStmt->execute([$storeId]);
            $funcCount = $funcStmt->fetch()['total'];
            echo "<p>Total funcionários: {$funcCount}</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Estrutura: " . $e->getMessage() . "</p>";
    }
    
    // 10. TESTAR CRIAÇÃO DE FUNCIONÁRIO
    echo "</div><div class='section'><h2>10. Teste de Criação</h2>";
    
    $testData = [
        'nome' => 'Teste Debug ' . time(),
        'email' => 'debug' . time() . '@test.com',
        'telefone' => '11999999999',
        'senha' => '12345678',
        'subtipo_funcionario' => 'vendedor'
    ];
    
    try {
        $createResult = StoreController::createEmployee($testData);
        echo "<p>Resultado criação:</p>";
        echo "<div class='code'>" . json_encode($createResult, JSON_PRETTY_PRINT) . "</div>";
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Criação: " . $e->getMessage() . "</p>";
        echo "<p>Arquivo: " . $e->getFile() . " - Linha: " . $e->getLine() . "</p>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'><h2>ERRO GERAL</h2>";
    echo "<p>Mensagem: " . $e->getMessage() . "</p>";
    echo "<p>Arquivo: " . $e->getFile() . "</p>";
    echo "<p>Linha: " . $e->getLine() . "</p>";
    echo "<p>Stack trace:</p><pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "<hr><p><strong>Debug concluído.</strong> Acesse a página normal em: <a href='/store/funcionarios'>store/funcionarios</a></p>";
?>