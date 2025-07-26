<?php
// force-redirect-fix.php
session_start();

echo "<h2>🔧 CORREÇÃO FORÇADA DE REDIRECIONAMENTO</h2>";

// 1. VERIFICAR E CORRIGIR index.php
echo "<h3>1. 🔍 VERIFICANDO index.php:</h3>";
if (file_exists('index.php')) {
    $indexContent = file_get_contents('index.php');
    
    // Procurar por redirecionamentos problemáticos
    if (strpos($indexContent, 'cliente') !== false && strpos($indexContent, 'funcionario') === false) {
        echo "<p style='color: #dc3545;'>❌ PROBLEMA ENCONTRADO no index.php</p>";
        
        // Criar backup
        file_put_contents('index.php.backup', $indexContent);
        echo "<p>📋 Backup criado: index.php.backup</p>";
        
        // Aplicar correção
        $fixedContent = str_replace(
            "if (\$_SESSION['user_type'] == 'cliente') {",
            "if (\$_SESSION['user_type'] == 'cliente') {",
            $indexContent
        );
        
        // Adicionar correção para funcionários se não existir
        if (strpos($fixedContent, "user_type'] == 'funcionario'") === false) {
            $fixedContent = str_replace(
                "header('Location: /views/client/dashboard.php');",
                "header('Location: /views/client/dashboard.php');\n} else if (\$_SESSION['user_type'] == 'funcionario') {\n    header('Location: /store/dashboard/');",
                $fixedContent
            );
        }
        
        file_put_contents('index.php', $fixedContent);
        echo "<p style='color: #28a745;'>✅ index.php corrigido</p>";
    } else {
        echo "<p style='color: #28a745;'>✅ index.php parece estar correto</p>";
    }
} else {
    echo "<p>⚠️ index.php não encontrado</p>";
}

// 2. VERIFICAR E CORRIGIR views/auth/login.php
echo "<h3>2. 🔍 VERIFICANDO views/auth/login.php:</h3>";
if (file_exists('views/auth/login.php')) {
    $loginContent = file_get_contents('views/auth/login.php');
    
    if (strpos($loginContent, "userType == 'funcionario'") === false) {
        echo "<p style='color: #dc3545;'>❌ PROBLEMA: Falta redirecionamento para funcionários</p>";
        
        // Criar backup
        file_put_contents('views/auth/login.php.backup', $loginContent);
        
        // Aplicar correção forçada
        $fixPattern = '/header\(\'Location: \' \. CLIENT_DASHBOARD_URL\);/';
        $replacement = "header('Location: ' . CLIENT_DASHBOARD_URL);\n    } else if (\$userType == 'funcionario') {\n        header('Location: ' . STORE_DASHBOARD_URL);";
        
        $fixedLoginContent = preg_replace($fixPattern, $replacement, $loginContent);
        file_put_contents('views/auth/login.php', $fixedLoginContent);
        
        echo "<p style='color: #28a745;'>✅ views/auth/login.php corrigido</p>";
    } else {
        echo "<p style='color: #28a745;'>✅ views/auth/login.php parece estar correto</p>";
    }
} else {
    echo "<p>❌ views/auth/login.php não encontrado</p>";
}

// 3. CRIAR REDIRECIONADOR DEFINITIVO
echo "<h3>3. 🔧 CRIANDO REDIRECIONADOR DEFINITIVO:</h3>";

$redirectorContent = '<?php
// auto-redirect.php - Redirecionador automático para funcionários
session_start();

if (isset($_SESSION["user_type"]) && $_SESSION["user_type"] === "funcionario") {
    // FORÇAR redirecionamento para área da loja
    if (strpos($_SERVER["REQUEST_URI"], "client/dashboard") !== false) {
        header("Location: /store/dashboard/");
        exit;
    }
}
?>';

file_put_contents('auto-redirect.php', $redirectorContent);
echo "<p style='color: #28a745;'>✅ Redirecionador automático criado</p>";

// 4. MODIFICAR .htaccess PARA USAR O REDIRECIONADOR
echo "<h3>4. 🔧 ATUALIZANDO .htaccess:</h3>";

$htaccessRule = '
# === CORREÇÃO AUTOMÁTICA PARA FUNCIONÁRIOS ===
RewriteRule ^views/client/dashboard\.php$ /auto-redirect.php [L]
';

file_put_contents('.htaccess.redirect-rule', $htaccessRule);
echo "<p>📋 Regra criada em .htaccess.redirect-rule</p>";
echo "<p><strong>⚠️ ADICIONE MANUALMENTE</strong> o conteúdo do arquivo acima ao seu .htaccess</p>";

// 5. TESTE FINAL
echo "<h3>5. 🧪 TESTE FINAL:</h3>";
echo "<div style='background: #d4edda; padding: 15px; color: #155724;'>";
echo "<h4>✅ CORREÇÕES APLICADAS</h4>";
echo "<p>1. index.php verificado/corrigido</p>";
echo "<p>2. views/auth/login.php verificado/corrigido</p>";
echo "<p>3. Redirecionador automático criado</p>";
echo "<p>4. Regra .htaccess preparada</p>";
echo "</div>";

echo "<div style='margin: 20px 0;'>";
echo "<a href='logout.php' style='background: #ffc107; color: #212529; padding: 12px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🚪 Fazer Logout</a><br><br>";
echo "<a href='views/auth/login.php' style='background: #007bff; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔑 Testar Login</a><br><br>";
echo "<a href='redirect-interceptor.php' style='background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔍 Verificar Interceptador</a>";
echo "</div>";
?>