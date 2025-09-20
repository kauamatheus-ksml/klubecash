<?php
// redirect-interceptor.php - Colocar na raiz
session_start();

echo "<h2>🔧 INTERCEPTADOR DE REDIRECIONAMENTO</h2>";

if (!isset($_SESSION['user_id'])) {
    echo "<p>❌ Faça login primeiro</p>";
    echo "<p><a href='views/auth/login.php'>Login</a></p>";
    exit;
}

$userType = $_SESSION['user_type'] ?? '';
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? '';

echo "<h3>👤 Usuário: {$userName} (Tipo: {$userType})</h3>";

if ($userType === 'funcionario') {
    echo "<div style='background: #fff3cd; padding: 15px; color: #856404;'>";
    echo "<h4>⚠️ FUNCIONÁRIO DETECTADO</h4>";
    echo "<p>Este usuário DEVE ir para <strong>/store/dashboard/</strong></p>";
    echo "<p>Se for redirecionado para /views/client/dashboard.php é um ERRO</p>";
    echo "</div>";
    
    // Simular o que deveria acontecer
    echo "<h4>🔄 REDIRECIONAMENTO CORRETO:</h4>";
    echo "<p>Clique para testar o redirecionamento correto:</p>";
    echo "<p><a href='/store/dashboard/' style='background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px;'>🏠 Ir para Store Dashboard (CORRETO)</a></p>";
    
    echo "<h4>❌ REDIRECIONAMENTO INCORRETO (NÃO CLIQUE):</h4>";
    echo "<p><a href='/views/client/dashboard.php' style='background: #dc3545; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px;'>❌ Client Dashboard (INCORRETO)</a></p>";
    
    // JavaScript para interceptar redirecionamentos
    echo "<script>
    // Interceptar todos os redirecionamentos
    const originalLocation = window.location;
    
    Object.defineProperty(window, 'location', {
        get: function() {
            return originalLocation;
        },
        set: function(url) {
            console.log('REDIRECIONAMENTO INTERCEPTADO:', url);
            
            // Se for funcionário indo para client/dashboard, corrigir
            if (url.includes('client/dashboard') && '{$userType}' === 'funcionario') {
                console.log('CORRIGINDO REDIRECIONAMENTO DE FUNCIONÁRIO');
                alert('ERRO INTERCEPTADO: Funcionário sendo redirecionado para client/dashboard. Corrigindo para store/dashboard.');
                originalLocation.href = '/store/dashboard/';
                return;
            }
            
            originalLocation.href = url;
        }
    });
    
    // Interceptar header redirects via fetch
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        console.log('FETCH INTERCEPTADO:', args);
        return originalFetch.apply(this, args);
    };
    
    console.log('INTERCEPTADOR ATIVO - Tipo de usuário: {$userType}');
    </script>";
    
} else {
    echo "<div style='background: #d1ecf1; padding: 15px; color: #0c5460;'>";
    echo "<h4>ℹ️ USUÁRIO NÃO É FUNCIONÁRIO</h4>";
    echo "<p>Tipo: {$userType}</p>";
    echo "<p>Este usuário pode usar o redirecionamento normal</p>";
    echo "</div>";
}

echo "<h3>🧪 TESTES DE REDIRECIONAMENTO:</h3>";

// Simular diferentes cenários de login
echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0;'>";
echo "<h4>📋 Teste Manual de Redirecionamento:</h4>";

$redirectTests = [
    'admin' => '/views/admin/dashboard.php',
    'loja' => '/store/dashboard/',
    'funcionario' => '/store/dashboard/', // DEVE ser este!
    'cliente' => '/views/client/dashboard.php'
];

echo "<ul>";
foreach ($redirectTests as $tipo => $url) {
    $style = ($tipo === 'funcionario') ? 'color: #dc3545; font-weight: bold;' : '';
    echo "<li style='{$style}'><strong>{$tipo}:</strong> {$url}</li>";
}
echo "</ul>";

echo "<p><strong>🚨 ATENÇÃO:</strong> Se funcionário for para /views/client/dashboard.php é ERRO!</p>";
echo "</div>";

echo "<h3>🔧 AÇÕES DE CORREÇÃO:</h3>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='force-redirect-fix.php' style='background: #dc3545; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔧 FORÇAR CORREÇÃO</a><br><br>";
echo "<a href='debug-all-redirects.php' style='background: #007bff; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔍 Debug Todos Redirects</a><br><br>";
echo "<a href='find-redirect-problem.php' style='background: #6c757d; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🕵️ Encontrar Problema</a>";
echo "</div>";
?>