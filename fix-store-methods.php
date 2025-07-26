<?php
session_start();

echo "<h2>🔧 CORREÇÃO DOS MÉTODOS DA LOJA</h2>";

// 1. CORRIGIR StoreHelper::getCurrentStoreId()
$storeHelperFile = 'utils/StoreHelper.php';

if (file_exists($storeHelperFile)) {
    echo "<h3>🔧 CORRIGINDO StoreHelper::getCurrentStoreId()</h3>";
    
    $content = file_get_contents($storeHelperFile);
    
    // Verificar se método precisa de correção
    if (strpos($content, 'getCurrentStoreId') !== false) {
        // Fazer backup
        copy($storeHelperFile, $storeHelperFile . '.backup-methods-' . date('Y-m-d-H-i-s'));
        
        // Substituir método getCurrentStoreId
        $newMethod = '    public static function getCurrentStoreId() {
        if (!isset($_SESSION[\'user_type\'])) {
            return null;
        }
        
        $userType = $_SESSION[\'user_type\'];
        
        // Para lojistas, usar store_id
        if ($userType === \'loja\' || (defined(\'USER_TYPE_STORE\') && $userType === USER_TYPE_STORE)) {
            return $_SESSION[\'store_id\'] ?? null;
        }
        
        // Para funcionários, usar store_id OU loja_vinculada_id (ambos devem ter o mesmo valor)
        if ($userType === \'funcionario\' || (defined(\'USER_TYPE_EMPLOYEE\') && $userType === USER_TYPE_EMPLOYEE)) {
            // Priorizar store_id, mas fallback para loja_vinculada_id
            return $_SESSION[\'store_id\'] ?? $_SESSION[\'loja_vinculada_id\'] ?? null;
        }
        
        return null;
    }';
        
        // Substituir método
        $pattern = '/public\s+static\s+function\s+getCurrentStoreId\s*\(\s*\)\s*\{[^}]*\}/s';
        if (preg_match($pattern, $content)) {
            $correctedContent = preg_replace($pattern, $newMethod, $content);
            file_put_contents($storeHelperFile, $correctedContent);
            echo "<p style='color: #28a745;'>✅ StoreHelper::getCurrentStoreId() corrigido</p>";
        } else {
            echo "<p style='color: #dc3545;'>❌ Método getCurrentStoreId() não encontrado para correção</p>";
        }
    }
} else {
    echo "<p style='color: #dc3545;'>❌ StoreHelper.php não encontrado</p>";
}

// 2. CORRIGIR AuthController::getStoreId()
$authControllerFile = 'controllers/AuthController.php';

if (file_exists($authControllerFile)) {
    echo "<h3>🔧 CORRIGINDO AuthController::getStoreId()</h3>";
    
    $content = file_get_contents($authControllerFile);
    
    if (strpos($content, 'getStoreId') !== false) {
        // Fazer backup
        copy($authControllerFile, $authControllerFile . '.backup-methods-' . date('Y-m-d-H-i-s'));
        
        // Substituir método getStoreId
        $newMethod = '    public static function getStoreId() {
        if (!isset($_SESSION[\'user_type\'])) {
            return null;
        }
        
        $userType = $_SESSION[\'user_type\'];
        
        // Para lojistas
        if ($userType === \'loja\' || (defined(\'USER_TYPE_STORE\') && $userType === USER_TYPE_STORE)) {
            return $_SESSION[\'store_id\'] ?? null;
        }
        
        // Para funcionários
        if ($userType === \'funcionario\' || (defined(\'USER_TYPE_EMPLOYEE\') && $userType === USER_TYPE_EMPLOYEE)) {
            // Sistema simplificado: funcionários usam store_id igual aos lojistas
            return $_SESSION[\'store_id\'] ?? $_SESSION[\'loja_vinculada_id\'] ?? null;
        }
        
        return null;
    }';
        
        // Substituir método
        $pattern = '/public\s+static\s+function\s+getStoreId\s*\(\s*\)\s*\{[^}]*\}/s';
        if (preg_match($pattern, $content)) {
            $correctedContent = preg_replace($pattern, $newMethod, $content);
            file_put_contents($authControllerFile, $correctedContent);
            echo "<p style='color: #28a745;'>✅ AuthController::getStoreId() corrigido</p>";
        } else {
            echo "<p style='color: #dc3545;'>❌ Método getStoreId() não encontrado para correção</p>";
        }
    }
} else {
    echo "<p style='color: #dc3545;'>❌ AuthController.php não encontrado</p>";
}

// 3. VERIFICAR SE A SESSÃO TEM OS DADOS CORRETOS
echo "<h3>🔍 VERIFICANDO SESSÃO ATUAL:</h3>";
$userId = $_SESSION['user_id'] ?? null;
$userType = $_SESSION['user_type'] ?? null;

if ($userType === 'funcionario') {
    echo "<h4>👤 Funcionário Logado:</h4>";
    echo "<ul>";
    echo "<li><strong>User ID:</strong> {$userId}</li>";
    echo "<li><strong>Store ID:</strong> " . ($_SESSION['store_id'] ?? 'NULL') . "</li>";
    echo "<li><strong>Loja Vinculada ID:</strong> " . ($_SESSION['loja_vinculada_id'] ?? 'NULL') . "</li>";
    echo "</ul>";
    
    // Se store_id está vazio, corrigir agora
    if (empty($_SESSION['store_id']) && !empty($_SESSION['loja_vinculada_id'])) {
        $_SESSION['store_id'] = $_SESSION['loja_vinculada_id'];
        echo "<p style='color: #28a745;'>✅ store_id corrigido na sessão: {$_SESSION['store_id']}</p>";
    }
}

echo "<div style='background: #d4edda; padding: 15px; color: #155724; margin: 15px 0;'>";
echo "<h4>✅ CORREÇÕES APLICADAS</h4>";
echo "<p>Métodos corrigidos para funcionarem com funcionários</p>";
echo "<p>Sistema simplificado: funcionários = lojistas</p>";
echo "</div>";

echo "<h3>🧪 TESTE IMEDIATO:</h3>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='debug-store-methods.php' style='background: #007bff; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔍 Testar Métodos</a><br><br>";
echo "<a href='store/dashboard/' style='background: #28a745; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; font-size: 16px;'>🏠 TESTAR DASHBOARD</a>";
echo "</div>";
?>