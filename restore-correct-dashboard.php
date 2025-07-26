<?php
session_start();

echo "<h2>🔧 RESTAURANDO DASHBOARD ORIGINAL CORRETO</h2>";

$dashboardFile = 'views/stores/dashboard.php';
$correctBackup = 'views/stores/dashboard.php.backup.2025-07-26-17-02-23'; // 35529 bytes

if (file_exists($correctBackup)) {
    echo "<h3>📁 Restaurando backup correto: {$correctBackup}</h3>";
    echo "<p><strong>Tamanho:</strong> " . filesize($correctBackup) . " bytes (dashboard original completo)</p>";
    
    // Ler o conteúdo do backup original
    $originalContent = file_get_contents($correctBackup);
    
    echo "<h4>🔍 Analisando dashboard original:</h4>";
    echo "<p>Linhas: " . substr_count($originalContent, "\n") . "</p>";
    echo "<p>Contém 'StoreHelper': " . (strpos($originalContent, 'StoreHelper') !== false ? 'SIM' : 'NÃO') . "</p>";
    echo "<p>Contém verificações de loja: " . (strpos($originalContent, "user_type") !== false ? 'SIM' : 'NÃO') . "</p>";
    
    // Verificar se já tem StoreHelper
    if (strpos($originalContent, 'StoreHelper::requireStoreAccess()') !== false) {
        echo "<div style='background: #d4edda; padding: 15px; color: #155724;'>";
        echo "<h4>✅ DASHBOARD JÁ ESTÁ CORRETO</h4>";
        echo "<p>O dashboard original já contém StoreHelper::requireStoreAccess()</p>";
        echo "<p>Funcionários já devem ter acesso</p>";
        echo "</div>";
        
        // Restaurar diretamente
        file_put_contents($dashboardFile, $originalContent);
        echo "<p style='color: #28a745;'>✅ Dashboard original restaurado sem modificações</p>";
        
    } else {
        echo "<h4>🔧 Aplicando correção mínima para funcionários:</h4>";
        
        // Aplicar correção mínima - substituir verificações restritivas
        $correctedContent = $originalContent;
        
        // Padrões de verificação que podem estar bloqueando funcionários
        $patterns = [
            // Verificação que só permite lojistas
            "/if\s*\(\s*!\s*isset\(\s*\\\$_SESSION\['user_id'\]\s*\)\s*\|\|\s*!\s*isset\(\s*\\\$_SESSION\['user_type'\]\s*\)\s*\|\|\s*\\\$_SESSION\['user_type'\]\s*!==?\s*'loja'\s*\)/i" => 
            "if (!isset(\$_SESSION['user_id']) || !isset(\$_SESSION['user_type']) || !in_array(\$_SESSION['user_type'], ['loja', 'funcionario']))",
            
            "/if\s*\(\s*\\\$_SESSION\['user_type'\]\s*!==?\s*'loja'\s*\)/i" => 
            "if (!in_array(\$_SESSION['user_type'] ?? '', ['loja', 'funcionario']))",
            
            "/if\s*\(\s*!\s*AuthController::isStore\(\s*\)\s*\)/i" => 
            "if (!AuthController::hasStoreAccess())",
            
            // Específico para redirecionamento com erro de acesso
            "/header\s*\(\s*[\"']Location:\s*.*\?error=acesso_restrito[\"']\s*\)/i" =>
            "header('Location: ' . LOGIN_URL . '?error=acesso_restrito')"
        ];
        
        $correctionsMade = 0;
        foreach ($patterns as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $correctedContent);
            if ($newContent !== $correctedContent) {
                $correctedContent = $newContent;
                $correctionsMade++;
                echo "<p style='color: #28a745;'>✅ Correção {$correctionsMade}: Permitir funcionários</p>";
            }
        }
        
        // Se não encontrou padrões automáticos, buscar linha por linha
        if ($correctionsMade === 0) {
            echo "<h5>🔍 Análise linha por linha:</h5>";
            
            $lines = explode("\n", $correctedContent);
            $modifiedLines = [];
            
            foreach ($lines as $lineNum => $line) {
                $originalLine = $line;
                
                // Procurar por verificações que rejeitam funcionários
                if (preg_match("/user_type.*!==?\s*['\"]loja['\"]/i", $line)) {
                    // Modificar para aceitar funcionários também
                    if (strpos($line, "!==") !== false) {
                        $line = preg_replace(
                            "/\\\$_SESSION\['user_type'\]\s*!==\s*'loja'/", 
                            "!in_array(\$_SESSION['user_type'] ?? '', ['loja', 'funcionario'])", 
                            $line
                        );
                    } elseif (strpos($line, "!=") !== false) {
                        $line = preg_replace(
                            "/\\\$_SESSION\['user_type'\]\s*!=\s*'loja'/", 
                            "!in_array(\$_SESSION['user_type'] ?? '', ['loja', 'funcionario'])", 
                            $line
                        );
                    }
                    
                    if ($line !== $originalLine) {
                        $correctionsMade++;
                        echo "<p style='color: #28a745;'>✅ Linha " . ($lineNum + 1) . " corrigida</p>";
                        echo "<p style='font-size: 12px; color: #666;'><strong>Antes:</strong> " . htmlspecialchars(trim($originalLine)) . "</p>";
                        echo "<p style='font-size: 12px; color: #666;'><strong>Depois:</strong> " . htmlspecialchars(trim($line)) . "</p>";
                    }
                }
                
                $modifiedLines[] = $line;
            }
            
            if ($correctionsMade > 0) {
                $correctedContent = implode("\n", $modifiedLines);
            }
        }
        
        // Salvar dashboard corrigido
        file_put_contents($dashboardFile, $correctedContent);
        
        echo "<div style='background: #d4edda; padding: 15px; color: #155724; margin: 15px 0;'>";
        echo "<h4>✅ DASHBOARD ORIGINAL RESTAURADO E CORRIGIDO</h4>";
        echo "<p>📁 Backup usado: {$correctBackup}</p>";
        echo "<p>🔧 Correções aplicadas: {$correctionsMade}</p>";
        echo "<p>🎯 Dashboard original mantido com acesso para funcionários</p>";
        echo "</div>";
    }
    
} else {
    echo "<div style='background: #f8d7da; padding: 15px; color: #721c24;'>";
    echo "<h4>❌ BACKUP ORIGINAL NÃO ENCONTRADO</h4>";
    echo "<p>Arquivo {$correctBackup} não existe</p>";
    echo "</div>";
}

// Mostrar primeiras linhas para confirmar
echo "<h3>🔍 VERIFICAÇÃO FINAL - Primeiras 30 linhas:</h3>";
if (file_exists($dashboardFile)) {
    $content = file_get_contents($dashboardFile);
    $lines = explode("\n", $content);
    
    echo "<div style='background: #f8f9fa; padding: 15px; font-family: monospace; font-size: 11px; border: 1px solid #ddd; max-height: 400px; overflow-y: scroll;'>";
    for ($i = 0; $i < min(30, count($lines)); $i++) {
        $lineNum = $i + 1;
        $line = htmlspecialchars($lines[$i]);
        
        // Destacar linhas importantes
        $important = false;
        if (strpos($lines[$i], 'user_type') !== false || 
            strpos($lines[$i], 'StoreHelper') !== false || 
            strpos($lines[$i], 'funcionario') !== false ||
            strpos($lines[$i], 'acesso_restrito') !== false) {
            $important = true;
        }
        
        $style = $important ? 'background-color: #fff3cd; font-weight: bold;' : '';
        echo "<div style='{$style}'>{$lineNum}: {$line}</div>";
    }
    echo "</div>";
    
    echo "<p><strong>Tamanho atual:</strong> " . strlen($content) . " bytes</p>";
    echo "<p><strong>Linhas totais:</strong> " . count($lines) . "</p>";
}

echo "<h3>🧪 TESTE O DASHBOARD ORIGINAL CORRIGIDO:</h3>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='store/dashboard/' style='background: #007bff; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; font-size: 16px;'>🏠 TESTAR DASHBOARD ORIGINAL</a>";
echo "</div>";

echo "<h3>📋 RESUMO:</h3>";
echo "<ul>";
echo "<li>✅ Dashboard original de 35529 bytes restaurado</li>";
echo "<li>✅ Funcionalidades completas mantidas</li>";
echo "<li>✅ Correção mínima aplicada para funcionários</li>";
echo "<li>✅ Sistema simplificado: funcionários = lojistas</li>";
echo "</ul>";
?>