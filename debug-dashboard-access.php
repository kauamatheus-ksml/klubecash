<?php
session_start();
require_once 'config/database.php';
require_once 'config/constants.php';

echo "<h2>🔍 DEBUG CRÍTICO - ACESSO AO DASHBOARD</h2>";

$userId = $_SESSION['user_id'] ?? null;
$userType = $_SESSION['user_type'] ?? null;

echo "<h3>👤 Informações da Sessão:</h3>";
echo "<ul>";
echo "<li><strong>User ID:</strong> " . ($userId ?? 'NÃO LOGADO') . "</li>";
echo "<li><strong>User Type:</strong> " . ($userType ?? 'NÃO DEFINIDO') . "</li>";
echo "<li><strong>Store ID:</strong> " . ($_SESSION['store_id'] ?? 'NÃO DEFINIDO') . "</li>";
echo "<li><strong>Store Name:</strong> " . ($_SESSION['store_name'] ?? 'NÃO DEFINIDO') . "</li>";
if ($userType === 'funcionario') {
    echo "<li><strong>Employee Subtype:</strong> " . ($_SESSION['employee_subtype'] ?? 'NÃO DEFINIDO') . "</li>";
}
echo "</ul>";

if ($userType !== 'funcionario') {
    echo "<div style='background: #d1ecf1; padding: 15px; color: #0c5460;'>";
    echo "<h4>ℹ️ USUÁRIO NÃO É FUNCIONÁRIO</h4>";
    echo "<p>Este debug é específico para funcionários. Tipo atual: {$userType}</p>";
    echo "</div>";
} else {
    echo "<div style='background: #fff3cd; padding: 15px; color: #856404;'>";
    echo "<h4>⚠️ FUNCIONÁRIO DETECTADO - ANALISANDO ACESSO</h4>";
    echo "</div>";
}

// Analisar o dashboard atual linha por linha
$dashboardFile = 'views/stores/dashboard.php';

if (file_exists($dashboardFile)) {
    echo "<h3>📁 ANALISANDO: {$dashboardFile}</h3>";
    
    $content = file_get_contents($dashboardFile);
    $lines = explode("\n", $content);
    
    echo "<p><strong>Tamanho:</strong> " . strlen($content) . " bytes</p>";
    echo "<p><strong>Total de linhas:</strong> " . count($lines) . "</p>";
    
    // Procurar por verificações problemáticas
    echo "<h4>🚨 VERIFICAÇÕES DE ACESSO ENCONTRADAS:</h4>";
    
    $problematicLines = [];
    foreach ($lines as $lineNum => $line) {
        $lineNumber = $lineNum + 1;
        
        // Padrões que podem estar bloqueando funcionários
        $patterns = [
            "/user_type.*!==.*loja/i" => "Verifica se NÃO é loja (bloqueia funcionários)",
            "/user_type.*!=.*loja/i" => "Verifica se NÃO é loja (bloqueia funcionários)",
            "/isStore\(\)/i" => "Método AuthController::isStore()",
            "/acesso_restrito/i" => "Redirecionamento de acesso restrito",
            "/header.*Location/i" => "Redirecionamento HTTP",
            "/exit|die/i" => "Interrupção de execução"
        ];
        
        foreach ($patterns as $pattern => $description) {
            if (preg_match($pattern, $line)) {
                $problematicLines[] = [
                    'line' => $lineNumber,
                    'content' => trim($line),
                    'description' => $description
                ];
            }
        }
    }
    
    if (!empty($problematicLines)) {
        echo "<div style='background: #f8d7da; padding: 15px; color: #721c24;'>";
        echo "<h4>❌ LINHAS PROBLEMÁTICAS ENCONTRADAS:</h4>";
        foreach ($problematicLines as $problem) {
            echo "<div style='margin: 10px 0; padding: 10px; border-left: 4px solid #dc3545; background: #fff;'>";
            echo "<p><strong>Linha {$problem['line']}:</strong> {$problem['description']}</p>";
            echo "<p><code style='background: #f8f9fa; padding: 5px;'>" . htmlspecialchars($problem['content']) . "</code></p>";
            echo "</div>";
        }
        echo "</div>";
    } else {
        echo "<div style='background: #d4edda; padding: 15px; color: #155724;'>";
        echo "<h4>✅ NENHUMA VERIFICAÇÃO ÓBVIA ENCONTRADA</h4>";
        echo "<p>O problema pode estar em verificações sutis ou includes.</p>";
        echo "</div>";
    }
    
    // Mostrar as primeiras 50 linhas com destaque
    echo "<h4>🔍 CÓDIGO COMPLETO (primeiras 50 linhas):</h4>";
    echo "<div style='background: #f8f9fa; padding: 15px; font-family: monospace; font-size: 11px; border: 1px solid #ddd; max-height: 500px; overflow-y: scroll;'>";
    
    for ($i = 0; $i < min(50, count($lines)); $i++) {
        $lineNum = $i + 1;
        $line = htmlspecialchars($lines[$i]);
        
        // Destacar linhas suspeitas
        $suspicious = false;
        $suspiciousPatterns = [
            'user_type', 'loja', 'funcionario', 'acesso_restrito', 
            'header', 'Location', 'exit', 'die', 'isStore', 'AUTH'
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (stripos($lines[$i], $pattern) !== false) {
                $suspicious = true;
                break;
            }
        }
        
        $style = $suspicious ? 'background-color: #ffe6e6; font-weight: bold; border-left: 3px solid #dc3545; padding-left: 5px;' : '';
        echo "<div style='{$style}'>{$lineNum}: {$line}</div>";
    }
    echo "</div>";
    
} else {
    echo "<div style='background: #f8d7da; padding: 15px; color: #721c24;'>";
    echo "<h4>❌ DASHBOARD NÃO ENCONTRADO</h4>";
    echo "<p>Arquivo {$dashboardFile} não existe</p>";
    echo "</div>";
}

echo "<h3>🔧 CORREÇÃO IMEDIATA:</h3>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='fix-dashboard-access-final.php' style='background: #dc3545; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; font-size: 16px;'>🔧 CORRIGIR ACESSO AGORA</a>";
echo "</div>";
?>