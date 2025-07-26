<?php
session_start();
require_once 'config/database.php';
require_once 'config/constants.php';

echo "<h2>🔍 DEBUG ESPECÍFICO - DASHBOARD DA LOJA</h2>";

if (!isset($_SESSION['user_id'])) {
    echo "<p>❌ Faça login primeiro</p>";
    exit;
}

$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'] ?? '';
$userName = $_SESSION['user_name'] ?? '';

echo "<h3>👤 Usuário: {$userName} (Tipo: {$userType})</h3>";

// Verificar arquivo do dashboard
$dashboardFile = 'views/stores/dashboard.php';

if (file_exists($dashboardFile)) {
    echo "<h3>📁 Analisando: {$dashboardFile}</h3>";
    
    $content = file_get_contents($dashboardFile);
    
    // Mostrar as primeiras 50 linhas para análise
    $lines = explode("\n", $content);
    echo "<h4>🔍 PRIMEIRAS 50 LINHAS DO ARQUIVO:</h4>";
    echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #ddd; font-family: monospace; font-size: 12px; max-height: 400px; overflow-y: scroll;'>";
    
    for ($i = 0; $i < min(50, count($lines)); $i++) {
        $lineNum = $i + 1;
        $line = htmlspecialchars($lines[$i]);
        
        // Destacar linhas suspeitas
        $suspicious = false;
        $suspiciousPatterns = [
            'user_type',
            'acesso_restrito', 
            'header.*Location',
            'exit',
            'die',
            'loja',
            'funcionario'
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match("/{$pattern}/i", $lines[$i])) {
                $suspicious = true;
                break;
            }
        }
        
        $style = $suspicious ? 'background-color: #ffe6e6; font-weight: bold;' : '';
        echo "<div style='{$style}'>{$lineNum}: {$line}</div>";
    }
    echo "</div>";
    
    // Procurar por verificações específicas
    echo "<h4>🚨 VERIFICAÇÕES DE ACESSO ENCONTRADAS:</h4>";
    
    $accessChecks = [
        "user_type'] !== 'loja'" => "Rejeita se não for lojista",
        "user_type'] != 'loja'" => "Rejeita se não for lojista", 
        "acesso_restrito" => "Redirecionamento de acesso restrito",
        "!AuthController::isStore()" => "Verificação AuthController",
        "!StoreHelper::" => "Verificação StoreHelper",
        "SESSION.*loja" => "Verificação de sessão loja"
    ];
    
    $foundChecks = [];
    foreach ($accessChecks as $pattern => $description) {
        if (preg_match("/{$pattern}/i", $content)) {
            $foundChecks[] = $description;
            echo "<p style='color: #dc3545;'>❌ <strong>{$description}</strong> - Padrão: <code>{$pattern}</code></p>";
        }
    }
    
    if (empty($foundChecks)) {
        echo "<p style='color: #28a745;'>✅ Nenhuma verificação de acesso óbvia encontrada</p>";
        echo "<p><strong>⚠️ O problema pode estar em arquivos incluídos ou verificações sutis</strong></p>";
    }
    
} else {
    echo "<p style='color: #dc3545;'>❌ Arquivo {$dashboardFile} não encontrado</p>";
}

// Verificar .htaccess para redirecionamentos
echo "<h3>🔍 VERIFICANDO .htaccess:</h3>";
if (file_exists('.htaccess')) {
    $htaccessContent = file_get_contents('.htaccess');
    
    if (strpos($htaccessContent, 'store/dashboard') !== false || strpos($htaccessContent, 'acesso_restrito') !== false) {
        echo "<div style='background: #f8d7da; padding: 15px; color: #721c24;'>";
        echo "<h4>❌ PROBLEMA NO .htaccess</h4>";
        echo "<p>Encontradas regras que podem estar causando o redirecionamento</p>";
        
        $htaccessLines = explode("\n", $htaccessContent);
        foreach ($htaccessLines as $lineNum => $line) {
            if (strpos($line, 'store') !== false || strpos($line, 'dashboard') !== false || strpos($line, 'acesso') !== false) {
                echo "<p><strong>Linha " . ($lineNum + 1) . ":</strong> <code>" . htmlspecialchars($line) . "</code></p>";
            }
        }
        echo "</div>";
    } else {
        echo "<p style='color: #28a745;'>✅ .htaccess parece OK</p>";
    }
} else {
    echo "<p>⚠️ .htaccess não encontrado</p>";
}

// Testar verificações do AuthController
echo "<h3>🧪 TESTANDO VERIFICAÇÕES AUTHCONTROLLER:</h3>";
require_once 'controllers/AuthController.php';

$tests = [
    'isAuthenticated' => AuthController::isAuthenticated(),
    'isStore' => AuthController::isStore(),
    'isEmployee' => AuthController::isEmployee(),
    'hasStoreAccess' => AuthController::hasStoreAccess(),
    'getStoreId' => AuthController::getStoreId(),
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Método</th><th>Resultado</th><th>Status</th></tr>";
foreach ($tests as $method => $result) {
    $displayResult = is_bool($result) ? ($result ? 'TRUE' : 'FALSE') : ($result ?? 'NULL');
    $status = ($result === true || !empty($result)) ? '✅' : '❌';
    $color = ($result === true || !empty($result)) ? '#d4edda' : '#f8d7da';
    echo "<tr style='background: {$color};'><td><strong>{$method}</strong></td><td>{$displayResult}</td><td>{$status}</td></tr>";
}
echo "</table>";

echo "<h3>🔧 AÇÃO IMEDIATA:</h3>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='fix-dashboard-final.php' style='background: #dc3545; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; font-size: 16px;'>🔧 CORRIGIR DASHBOARD AGORA</a>";
echo "</div>";
?>