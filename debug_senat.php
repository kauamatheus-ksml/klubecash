<?php
/**
 * Debug do sistema de CSS baseado no campo senat
 */

session_start();

echo "<h2>Debug - Sistema CSS Senat</h2>";

// Simular diferentes valores de senat
$testValues = ['Sim', 'Não', 'sim', 'nao', null];

foreach ($testValues as $testValue) {
    echo "<h3>Teste com senat = '" . ($testValue ?? 'null') . "':</h3>";

    // Simular a sessão
    $_SESSION['user_senat'] = $testValue;

    // Reproduzir a lógica do dashboard.php
    $cssFile = 'dashboard.css'; // CSS padrão
    if (isset($_SESSION['user_senat']) && ($_SESSION['user_senat'] === 'sim' || $_SESSION['user_senat'] === 'Sim')) {
        $cssFile = 'dashboard_sest.css'; // CSS para usuários senat=sim
    }

    $sidebarCssFile = 'sidebar-lojista.css'; // CSS da sidebar padrão
    if (isset($_SESSION['user_senat']) && ($_SESSION['user_senat'] === 'sim' || $_SESSION['user_senat'] === 'Sim')) {
        $sidebarCssFile = 'sidebar-lojista_sest.css'; // CSS da sidebar para usuários senat=sim
    }

    echo "<p><strong>Dashboard CSS:</strong> $cssFile</p>";
    echo "<p><strong>Sidebar CSS:</strong> $sidebarCssFile</p>";
    echo "<p><strong>Resultado:</strong> " . (($cssFile === 'dashboard_sest.css') ? '✅ CSS Azul (SENAT)' : '❌ CSS Laranja (Normal)') . "</p>";
    echo "<hr>";
}

// Status atual da sessão
echo "<h3>Status Atual da Sessão:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>