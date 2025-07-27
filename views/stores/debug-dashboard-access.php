<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<style>
body { font-family: Arial; margin: 20px; }
.success { color: green; }
.error { color: red; }
.warning { color: orange; }
.section { border: 1px solid #ccc; padding: 15px; margin: 10px 0; }
</style>";

echo "<h1>🔍 DEBUG DASHBOARD - ACESSO FUNCIONÁRIO</h1>";

try {
    // 1. INCLUDES
    echo "<div class='section'><h2>1. Verificando Includes</h2>";
    require_once '../../config/constants.php';
    echo "<p class='success'>✓ constants.php</p>";
    
    require_once '../../config/database.php';
    echo "<p class='success'>✓ database.php</p>";
    
    require_once '../../controllers/AuthController.php';
    echo "<p class='success'>✓ AuthController.php</p>";
    
    require_once '../../utils/StoreHelper.php';
    echo "<p class='success'>✓ StoreHelper.php</p>";
    
    // 2. SESSÃO
    echo "</div><div class='section'><h2>2. Verificando Sessão</h2>";
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    echo "<h3>📋 DADOS DA SESSÃO:</h3>";
    $sessionVars = ['user_id', 'user_type', 'user_name', 'store_id', 'loja_vinculada_id', 'employee_subtype', 'subtipo_funcionario'];
    foreach ($sessionVars as $var) {
        $value = $_SESSION[$var] ?? 'NULL';
        echo "<p><strong>{$var}:</strong> {$value}</p>";
    }
    
    // 3. TESTE STOREHELPER
    echo "</div><div class='section'><h2>3. Testando StoreHelper::requireStoreAccess()</h2>";
    
    $userType = $_SESSION['user_type'] ?? 'NULL';
    $storeId = $_SESSION['store_id'] ?? 'NULL';
    
    echo "<p><strong>User Type:</strong> {$userType}</p>";
    echo "<p><strong>Store ID:</strong> {$storeId}</p>";
    
    // Verificar tipos permitidos
    $allowedTypes = [
        defined('USER_TYPE_STORE') ? USER_TYPE_STORE : 'loja',
        defined('USER_TYPE_EMPLOYEE') ? USER_TYPE_EMPLOYEE : 'funcionario'
    ];
    echo "<p><strong>Tipos Permitidos:</strong> " . implode(', ', $allowedTypes) . "</p>";
    
    $isTypeAllowed = in_array($userType, $allowedTypes);
    echo "<p class='" . ($isTypeAllowed ? 'success' : 'error') . "'>";
    echo ($isTypeAllowed ? '✓' : '❌') . " Tipo de usuário permitido: " . ($isTypeAllowed ? 'SIM' : 'NÃO');
    echo "</p>";
    
    $hasStoreId = !empty($storeId) && $storeId !== 'NULL';
    echo "<p class='" . ($hasStoreId ? 'success' : 'error') . "'>";
    echo ($hasStoreId ? '✓' : '❌') . " Store ID válido: " . ($hasStoreId ? 'SIM' : 'NÃO');
    echo "</p>";
    
    // 4. TESTE MANUAL DA VERIFICAÇÃO
    echo "</div><div class='section'><h2>4. Teste Manual da Verificação</h2>";
    
    try {
        StoreHelper::requireStoreAccess();
        echo "<p class='success'>✅ StoreHelper::requireStoreAccess() - PASSOU!</p>";
        echo "<p>✅ Funcionário deveria ter acesso ao dashboard!</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ StoreHelper::requireStoreAccess() - FALHOU!</p>";
        echo "<p class='error'>Erro: " . $e->getMessage() . "</p>";
    }
    
    // 5. TESTE OUTROS MÉTODOS
    echo "</div><div class='section'><h2>5. Testando Outros Métodos</h2>";
    
    $currentStoreId = StoreHelper::getCurrentStoreId();
    echo "<p><strong>StoreHelper::getCurrentStoreId():</strong> " . ($currentStoreId ?? 'NULL') . "</p>";
    
    if (method_exists('AuthController', 'getStoreData')) {
        $storeData = AuthController::getStoreData();
        echo "<p><strong>AuthController::getStoreData():</strong> " . ($storeData ? 'DADOS OK' : 'NULL') . "</p>";
    }
    
    // 6. VERIFICAR CONSTANTES
    echo "</div><div class='section'><h2>6. Verificando Constantes</h2>";
    $constants = ['USER_TYPE_STORE', 'USER_TYPE_EMPLOYEE'];
    foreach ($constants as $const) {
        if (defined($const)) {
            echo "<p class='success'>✓ {$const}: " . constant($const) . "</p>";
        } else {
            echo "<p class='error'>❌ {$const} não definida</p>";
        }
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<h2 class='error'>💥 ERRO CRÍTICO</h2>";
    echo "<p class='error'>Erro: " . $e->getMessage() . "</p>";
    echo "<p>Arquivo: " . $e->getFile() . "</p>";
    echo "<p>Linha: " . $e->getLine() . "</p>";
    echo "</div>";
}

echo "<div class='section'>";
echo "<h2>🔧 PRÓXIMOS PASSOS</h2>";
echo "<p>1. Execute este debug como funcionário logado</p>";
echo "<p>2. Verifique se store_id está definido na sessão</p>";
echo "<p>3. Confirme se USER_TYPE_EMPLOYEE está definido em constants.php</p>";
echo "<p>4. Se tudo estiver OK mas ainda falhar, há problema no AuthController</p>";
echo "</div>";
?>