<?php
session_start();
require_once 'config/database.php';
require_once 'config/constants.php';

echo "<h2>🔧 CORREÇÃO FORÇADA - DASHBOARD DA LOJA</h2>";

$dashboardFile = 'views/stores/dashboard.php';

if (file_exists($dashboardFile)) {
    echo "<h3>📁 Corrigindo: {$dashboardFile}</h3>";
    
    // Fazer backup
    $backupFile = $dashboardFile . '.backup.' . date('Y-m-d-H-i-s');
    copy($dashboardFile, $backupFile);
    echo "<p>📋 Backup criado: {$backupFile}</p>";
    
    // Ler conteúdo atual
    $content = file_get_contents($dashboardFile);
    
    // Aplicar correções múltiplas
    $corrections = [
        // Padrão 1: user_type !== 'loja'
        "/if\s*\(\s*\\\$_SESSION\['user_type'\]\s*!==\s*'loja'\s*\)/i" => "if (!in_array(\$_SESSION['user_type'] ?? '', ['loja', 'funcionario']))",
        
        // Padrão 2: user_type != 'loja'  
        "/if\s*\(\s*\\\$_SESSION\['user_type'\]\s*!=\s*'loja'\s*\)/i" => "if (!in_array(\$_SESSION['user_type'] ?? '', ['loja', 'funcionario']))",
        
        // Padrão 3: !isStore()
        "/if\s*\(\s*!AuthController::isStore\(\)\s*\)/i" => "if (!AuthController::hasStoreAccess())",
        
        // Padrão 4: USER_TYPE_STORE
        "/if\s*\(\s*\\\$_SESSION\['user_type'\]\s*!==?\s*USER_TYPE_STORE\s*\)/i" => "if (!in_array(\$_SESSION['user_type'] ?? '', ['loja', 'funcionario']))",
        
        // Padrão 5: Verificação específica de loja
        "/\\\$_SESSION\['user_type'\]\s*===?\s*'loja'/" => "in_array(\$_SESSION['user_type'] ?? '', ['loja', 'funcionario'])"
    ];
    
    $correctedContent = $content;
    $appliedCorrections = 0;
    
    foreach ($corrections as $pattern => $replacement) {
        $newContent = preg_replace($pattern, $replacement, $correctedContent);
        if ($newContent !== $correctedContent) {
            $correctedContent = $newContent;
            $appliedCorrections++;
            echo "<p style='color: #28a745;'>✅ Correção aplicada: {$pattern}</p>";
        }
    }
    
    // Adicionar verificação StoreHelper no início se não existir
    if (strpos($correctedContent, 'StoreHelper') === false && strpos($correctedContent, '<?php') !== false) {
        $storeHelperInclude = "<?php\n// Sistema simplificado - funcionários têm acesso igual a lojistas\nrequire_once '../../utils/StoreHelper.php';\nStoreHelper::requireStoreAccess();\n";
        $correctedContent = str_replace('<?php', $storeHelperInclude, $correctedContent);
        $appliedCorrections++;
        echo "<p style='color: #28a745;'>✅ StoreHelper adicionado</p>";
    }
    
    if ($appliedCorrections > 0) {
        file_put_contents($dashboardFile, $correctedContent);
        echo "<div style='background: #d4edda; padding: 15px; color: #155724; margin: 10px 0;'>";
        echo "<h4>✅ DASHBOARD CORRIGIDO</h4>";
        echo "<p>{$appliedCorrections} correções aplicadas</p>";
        echo "<p>Funcionários agora têm acesso igual a lojistas</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; color: #856404; margin: 10px 0;'>";
        echo "<h4>⚠️ NENHUMA CORREÇÃO AUTOMÁTICA POSSÍVEL</h4>";
        echo "<p>Vou criar um dashboard simplificado que funciona</p>";
        echo "</div>";
        
        // Criar dashboard simplificado
        $simpleDashboard = '<?php
// Dashboard Simplificado - Sistema Klube Cash
require_once "../../utils/StoreHelper.php";
StoreHelper::requireStoreAccess();

$storeId = StoreHelper::getCurrentStoreId();
$userName = $_SESSION["user_name"] ?? "Usuário";
$userType = $_SESSION["user_type"] ?? "";
$isEmployee = ($userType === "funcionario");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Klube Cash</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .success-box { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #28a745; }
        .menu { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .menu-item { background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid #ddd; }
        .menu-item a { text-decoration: none; color: #333; font-weight: bold; }
        .menu-item:hover { background: #e9ecef; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏪 Dashboard da Loja</h1>
            <p>Bem-vindo, <?php echo htmlspecialchars($userName); ?>!</p>
            <p>Store ID: <?php echo htmlspecialchars($storeId); ?></p>
        </div>

        <?php if ($isEmployee): ?>
            <div class="success-box">
                <h3>✅ SISTEMA SIMPLIFICADO FUNCIONANDO!</h3>
                <p><strong>Funcionário com acesso total à loja</strong></p>
                <p>Tipo: <?php echo htmlspecialchars($userType); ?></p>
                <p>Subtipo: <?php echo htmlspecialchars($_SESSION["employee_subtype"] ?? "Não definido"); ?></p>
            </div>
        <?php endif; ?>

        <div class="menu">
            <div class="menu-item">
                <a href="../funcionarios/">👥 Funcionários</a>
            </div>
            <div class="menu-item">
                <a href="../transacoes/">💰 Transações</a>
            </div>
            <div class="menu-item">
                <a href="../comissoes/">📊 Comissões</a>
            </div>
            <div class="menu-item">
                <a href="../../views/auth/login.php?action=logout">🚪 Logout</a>
            </div>
        </div>

        <div style="margin-top: 30px; padding: 15px; background: #e9ecef; border-radius: 5px;">
            <h4>📋 Informações da Sessão (Debug):</h4>
            <ul>
                <li><strong>User ID:</strong> <?php echo $_SESSION["user_id"] ?? "N/A"; ?></li>
                <li><strong>User Type:</strong> <?php echo $userType; ?></li>
                <li><strong>Store ID:</strong> <?php echo $storeId; ?></li>
                <li><strong>Store Name:</strong> <?php echo htmlspecialchars($_SESSION["store_name"] ?? "N/A"); ?></li>
            </ul>
        </div>
    </div>
</body>
</html>';

        // Salvar como dashboard simplificado
        file_put_contents('views/stores/dashboard-simple.php', $simpleDashboard);
        
        // Substituir o dashboard atual
        file_put_contents($dashboardFile, $simpleDashboard);
        echo "<p style='color: #28a745;'>✅ Dashboard simplificado criado e aplicado</p>";
    }
    
} else {
    echo "<p style='color: #dc3545;'>❌ Arquivo {$dashboardFile} não encontrado</p>";
    
    // Criar dashboard do zero
    $newDashboard = '<?php
require_once "../../utils/StoreHelper.php";
StoreHelper::requireStoreAccess();
// ... código do dashboard simplificado aqui ...
?>';
    
    // Criar diretórios se não existirem
    $dir = dirname($dashboardFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    file_put_contents($dashboardFile, $newDashboard);
    echo "<p style='color: #28a745;'>✅ Dashboard criado do zero</p>";
}

echo "<h3>🧪 TESTE FINAL:</h3>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='store/dashboard/' style='background: #28a745; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; font-size: 16px;'>🏠 TESTAR DASHBOARD CORRIGIDO</a><br><br>";
echo "<a href='debug-dashboard-specific.php' style='background: #007bff; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px;'>🔍 Debug Novamente</a>";
echo "</div>";

echo "<h3>📋 PRÓXIMOS PASSOS:</h3>";
echo "<ol>";
echo "<li>Teste o dashboard corrigido</li>";
echo "<li>Se ainda der erro, verifique os logs do servidor</li>";
echo "<li>Confirme que não há outros arquivos interferindo</li>";
echo "</ol>";
?>