<?php
session_start();

echo "<h2>🔧 CORREÇÃO DEFINITIVA - ACESSO AO DASHBOARD</h2>";

$dashboardFile = 'views/stores/dashboard.php';

if (!file_exists($dashboardFile)) {
    echo "<p style='color: #dc3545;'>❌ Dashboard não encontrado</p>";
    exit;
}

// Fazer backup antes da correção
$backupFile = $dashboardFile . '.backup-access-' . date('Y-m-d-H-i-s');
copy($dashboardFile, $backupFile);
echo "<p>📋 Backup criado: {$backupFile}</p>";

// Ler conteúdo atual
$content = file_get_contents($dashboardFile);
$lines = explode("\n", $content);

echo "<h3>🔍 IDENTIFICANDO VERIFICAÇÕES PROBLEMÁTICAS:</h3>";

$modificationsNeeded = [];
foreach ($lines as $lineNum => $line) {
    $lineNumber = $lineNum + 1;
    
    // Identificar padrões que bloqueiam funcionários
    if (preg_match("/if\s*\(.*user_type.*!==?\s*['\"]loja['\"]/i", $line)) {
        $modificationsNeeded[] = [
            'line' => $lineNumber,
            'original' => trim($line),
            'type' => 'user_type_check'
        ];
    }
    
    if (preg_match("/AuthController::isStore\(\)/i", $line)) {
        $modificationsNeeded[] = [
            'line' => $lineNumber,
            'original' => trim($line),
            'type' => 'isStore_check'
        ];
    }
}

if (!empty($modificationsNeeded)) {
    echo "<div style='background: #f8d7da; padding: 15px; color: #721c24;'>";
    echo "<h4>❌ VERIFICAÇÕES PROBLEMÁTICAS ENCONTRADAS:</h4>";
    foreach ($modificationsNeeded as $mod) {
        echo "<p><strong>Linha {$mod['line']} ({$mod['type']}):</strong></p>";
        echo "<p><code>" . htmlspecialchars($mod['original']) . "</code></p>";
    }
    echo "</div>";
    
    echo "<h3>🔧 APLICANDO CORREÇÕES:</h3>";
    
    // Aplicar correções específicas
    $correctedContent = $content;
    
    // Correção 1: user_type !== 'loja' para aceitar funcionários
    $patterns = [
        // Padrão 1: verificação básica de tipo
        "/if\s*\(\s*\\\$_SESSION\['user_type'\]\s*!==\s*'loja'\s*\)/i" => 
        "if (!in_array(\$_SESSION['user_type'] ?? '', ['loja', 'funcionario']))",
        
        // Padrão 2: verificação com isset
        "/if\s*\(\s*!\s*isset\(\s*\\\$_SESSION\['user_id'\]\s*\)\s*\|\|\s*!\s*isset\(\s*\\\$_SESSION\['user_type'\]\s*\)\s*\|\|\s*\\\$_SESSION\['user_type'\]\s*!==\s*'loja'\s*\)/i" => 
        "if (!isset(\$_SESSION['user_id']) || !isset(\$_SESSION['user_type']) || !in_array(\$_SESSION['user_type'], ['loja', 'funcionario']))",
        
        // Padrão 3: AuthController::isStore()
        "/if\s*\(\s*!\s*AuthController::isStore\(\)\s*\)/i" => 
        "if (!AuthController::hasStoreAccess())",
        
        // Padrão 4: verificação simples !=
        "/if\s*\(\s*\\\$_SESSION\['user_type'\]\s*!=\s*'loja'\s*\)/i" => 
        "if (!in_array(\$_SESSION['user_type'] ?? '', ['loja', 'funcionario']))"
    ];
    
    $appliedCorrections = 0;
    foreach ($patterns as $pattern => $replacement) {
        $newContent = preg_replace($pattern, $replacement, $correctedContent);
        if ($newContent !== $correctedContent) {
            $correctedContent = $newContent;
            $appliedCorrections++;
            echo "<p style='color: #28a745;'>✅ Correção {$appliedCorrections} aplicada</p>";
        }
    }
    
    // Se não conseguiu corrigir automaticamente, fazer correção manual
    if ($appliedCorrections === 0) {
        echo "<h4>🔧 CORREÇÃO MANUAL LINHA POR LINHA:</h4>";
        
        $lines = explode("\n", $correctedContent);
        $modifiedLines = [];
        
        foreach ($lines as $lineNum => $line) {
            $originalLine = $line;
            
            // Substituir verificações específicas
            if (strpos($line, "user_type'] !== 'loja'") !== false) {
                $line = str_replace(
                    "\$_SESSION['user_type'] !== 'loja'",
                    "!in_array(\$_SESSION['user_type'] ?? '', ['loja', 'funcionario'])",
                    $line
                );
                $appliedCorrections++;
                echo "<p style='color: #28a745;'>✅ Linha " . ($lineNum + 1) . " corrigida manualmente</p>";
            }
            
            if (strpos($line, "user_type'] != 'loja'") !== false) {
                $line = str_replace(
                    "\$_SESSION['user_type'] != 'loja'",
                    "!in_array(\$_SESSION['user_type'] ?? '', ['loja', 'funcionario'])",
                    $line
                );
                $appliedCorrections++;
                echo "<p style='color: #28a745;'>✅ Linha " . ($lineNum + 1) . " corrigida manualmente</p>";
            }
            
            if (strpos($line, "AuthController::isStore()") !== false && strpos($line, "!") !== false) {
                $line = str_replace(
                    "!AuthController::isStore()",
                    "!AuthController::hasStoreAccess()",
                    $line
                );
                $appliedCorrections++;
                echo "<p style='color: #28a745;'>✅ Linha " . ($lineNum + 1) . " - isStore() corrigido</p>";
            }
            
            $modifiedLines[] = $line;
        }
        
        if ($appliedCorrections > 0) {
            $correctedContent = implode("\n", $modifiedLines);
        }
    }
    
    // Salvar arquivo corrigido
    if ($appliedCorrections > 0) {
        file_put_contents($dashboardFile, $correctedContent);
        
        echo "<div style='background: #d4edda; padding: 15px; color: #155724; margin: 15px 0;'>";
        echo "<h4>✅ DASHBOARD CORRIGIDO COM SUCESSO</h4>";
        echo "<p>📁 Arquivo: {$dashboardFile}</p>";
        echo "<p>🔧 Correções aplicadas: {$appliedCorrections}</p>";
        echo "<p>🎯 Funcionários agora têm acesso igual aos lojistas</p>";
        echo "</div>";
        
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; color: #856404;'>";
        echo "<h4>⚠️ NENHUMA CORREÇÃO AUTOMÁTICA POSSÍVEL</h4>";
        echo "<p>Verificações não seguem padrões conhecidos</p>";
        echo "</div>";
    }
    
} else {
    echo "<div style='background: #d1ecf1; padding: 15px; color: #0c5460;'>";
    echo "<h4>ℹ️ NENHUMA VERIFICAÇÃO PROBLEMÁTICA ÓBVIA</h4>";
    echo "<p>O problema pode estar em outros arquivos incluídos</p>";
    echo "</div>";
    
    // Verificar includes problemáticos
    echo "<h4>🔍 VERIFICANDO INCLUDES:</h4>";
    $includes = [];
    if (preg_match_all("/require_once\s+['\"]([^'\"]+)['\"]/", $content, $matches)) {
        $includes = $matches[1];
    }
    
    foreach ($includes as $include) {
        if (strpos($include, 'Auth') !== false || strpos($include, 'Store') !== false) {
            echo "<p>⚠️ Include suspeito: <code>{$include}</code></p>";
        }
    }
}

echo "<h3>🧪 TESTE IMEDIATO:</h3>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='store/dashboard/' style='background: #007bff; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; font-size: 16px;'>🏠 TESTAR DASHBOARD AGORA</a><br><br>";
echo "<a href='debug-dashboard-access.php' style='background: #6c757d; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px;'>🔍 Debug Novamente</a>";
echo "</div>";

echo "<h3>📋 PRÓXIMOS PASSOS:</h3>";
echo "<ul>";
echo "<li>Teste o dashboard com funcionário logado</li>";
echo "<li>Se ainda der erro, verifique logs do servidor</li>";
echo "<li>Confirme que não há outros arquivos interferindo</li>";
echo "</ul>";
?>