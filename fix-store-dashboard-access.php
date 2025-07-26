<?php
session_start();
require_once 'config/database.php';
require_once 'config/constants.php';

echo "<h2>🔧 CORRIGINDO VERIFICAÇÃO DE ACESSO - DASHBOARD LOJA</h2>";

// Verificar arquivo do dashboard da loja
$dashboardFiles = [
    'store/dashboard/index.php',
    'views/stores/dashboard.php', 
    'store/index.php'
];

$foundDashboard = null;
foreach ($dashboardFiles as $file) {
    if (file_exists($file)) {
        $foundDashboard = $file;
        break;
    }
}

if ($foundDashboard) {
    echo "<h3>📁 Arquivo encontrado: {$foundDashboard}</h3>";
    
    $content = file_get_contents($foundDashboard);
    
    // Verificar se tem verificação de acesso problemática
    $problematicChecks = [
        "user_type'] !== 'loja'",
        "user_type'] != 'loja'", 
        "!= USER_TYPE_STORE",
        "!== USER_TYPE_STORE",
        "acesso_restrito"
    ];
    
    $hasProblems = false;
    echo "<h4>🔍 Verificando problemas de acesso:</h4>";
    
    foreach ($problematicChecks as $check) {
        if (strpos($content, $check) !== false) {
            $hasProblems = true;
            echo "<p style='color: #dc3545;'>❌ Problema encontrado: <code>{$check}</code></p>";
        }
    }
    
    if ($hasProblems) {
        echo "<div style='background: #fff3cd; padding: 15px; color: #856404; margin: 10px 0;'>";
        echo "<h4>🔧 APLICANDO CORREÇÃO:</h4>";
        
        // Criar backup
        file_put_contents($foundDashboard . '.backup', $content);
        echo "<p>📋 Backup criado: {$foundDashboard}.backup</p>";
        
        // Aplicar correções
        $fixedContent = $content;
        
        // Substituir verificações problemáticas por verificação simplificada
        $oldChecks = [
            "if (\$_SESSION['user_type'] !== 'loja')",
            "if (\$_SESSION['user_type'] != 'loja')",
            "if (!\$_SESSION['user_type'] === 'loja')"
        ];
        
        $newCheck = "if (!in_array(\$_SESSION['user_type'] ?? '', ['loja', 'funcionario']))";
        
        foreach ($oldChecks as $oldCheck) {
            $fixedContent = str_replace($oldCheck, $newCheck, $fixedContent);
        }
        
        // Adicionar include do StoreHelper se não existir
        if (strpos($fixedContent, 'StoreHelper') === false) {
            $includeStoreHelper = "<?php\nrequire_once '../../utils/StoreHelper.php';\nStoreHelper::requireStoreAccess();\n";
            $fixedContent = str_replace('<?php', $includeStoreHelper, $fixedContent);
        }
        
        file_put_contents($foundDashboard, $fixedContent);
        echo "<p style='color: #28a745;'>✅ {$foundDashboard} corrigido</p>";
        echo "</div>";
        
    } else {
        echo "<p style='color: #28a745;'>✅ Nenhum problema de acesso encontrado</p>";
    }
    
} else {
    echo "<p style='color: #dc3545;'>❌ Dashboard da loja não encontrado</p>";
    echo "<h4>📁 Verificar estes locais:</h4>";
    foreach ($dashboardFiles as $file) {
        echo "<p>- {$file}</p>";
    }
}

// Criar dashboard simplificado se não existir
if (!$foundDashboard) {
    echo "<h3>🔧 CRIANDO DASHBOARD SIMPLIFICADO:</h3>";
    
    $simpleDashboard = '<?php
require_once "../../utils/StoreHelper.php";
StoreHelper::requireStoreAccess();

$storeId = StoreHelper::getCurrentStoreId();
$userName = $_SESSION["user_name"] ?? "Usuário";
$userType = $_SESSION["user_type"] ?? "";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Loja</title>
</head>
<body>
    <h1>Dashboard da Loja</h1>
    <p>Bem-vindo, <?php echo htmlspecialchars($userName); ?>!</p>
    <p>Tipo: <?php echo htmlspecialchars($userType); ?></p>
    <p>Store ID: <?php echo htmlspecialchars($storeId); ?></p>
    
    <?php if ($userType === "funcionario"): ?>
        <div style="background: #d4edda; padding: 15px; color: #155724;">
            <h3>✅ FUNCIONÁRIO COM ACESSO À LOJA</h3>
            <p>Sistema simplificado funcionando!</p>
        </div>
    <?php endif; ?>
    
    <ul>
        <li><a href="../funcionarios/">Gerenciar Funcionários</a></li>
        <li><a href="../transacoes/">Transações</a></li>
        <li><a href="../../views/auth/login.php?action=logout">Logout</a></li>
    </ul>
</body>
</html>';
    
    // Criar diretório se não existir
    if (!is_dir('store/dashboard')) {
        mkdir('store/dashboard', 0755, true);
    }
    
    file_put_contents('store/dashboard/index.php', $simpleDashboard);
    echo "<p style='color: #28a745;'>✅ Dashboard criado em store/dashboard/index.php</p>";
}

echo "<h3>🧪 TESTE AGORA:</h3>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='store/dashboard/' style='background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🏠 Testar Dashboard</a><br><br>";
echo "<a href='logout.php' style='background: #ffc107; color: #212529; padding: 12px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🚪 Logout</a><br><br>";
echo "<a href='views/auth/login.php' style='background: #007bff; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔑 Login</a>";
echo "</div>";
?>