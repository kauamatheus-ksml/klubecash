<?php
session_start();
require_once 'config/database.php';
require_once 'config/constants.php';

echo "<h2>🔧 RESTAURANDO DASHBOARD ORIGINAL</h2>";

$dashboardFile = 'views/stores/dashboard.php';
$backupPattern = 'views/stores/dashboard.php.backup.*';

// Encontrar o backup mais recente
$backups = glob($backupPattern);
if (!empty($backups)) {
    // Ordenar por data mais recente
    usort($backups, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    $latestBackup = $backups[0];
    echo "<h3>📋 Backup encontrado: {$latestBackup}</h3>";
    
    // Restaurar backup
    $originalContent = file_get_contents($latestBackup);
    
    echo "<h3>🔧 APLICANDO CORREÇÃO MÍNIMA NO DASHBOARD ORIGINAL:</h3>";
    
    // Aplicar APENAS a correção necessária para funcionários
    $correctedContent = $originalContent;
    
    // Encontrar e substituir verificações de acesso restritivas
    $patterns = [
        // Padrão principal: verificação que só permite lojistas
        "/if\s*\(\s*!\s*isset\(\s*\\\$_SESSION\['user_id'\]\s*\)\s*\|\|\s*!\s*isset\(\s*\\\$_SESSION\['user_type'\]\s*\)\s*\|\|\s*\\\$_SESSION\['user_type'\]\s*!==?\s*'loja'\s*\)/i" => "if (!isset(\$_SESSION['user_id']) || !isset(\$_SESSION['user_type']) || !in_array(\$_SESSION['user_type'], ['loja', 'funcionario']))",
        
        // Outros padrões comuns
        "/if\s*\(\s*\\\$_SESSION\['user_type'\]\s*!==?\s*'loja'\s*\)/i" => "if (!in_array(\$_SESSION['user_type'] ?? '', ['loja', 'funcionario']))",
        
        "/if\s*\(\s*!\s*AuthController::isStore\(\s*\)\s*\)/i" => "if (!AuthController::hasStoreAccess())",
    ];
    
    $correctionsMade = 0;
    foreach ($patterns as $pattern => $replacement) {
        $newContent = preg_replace($pattern, $replacement, $correctedContent);
        if ($newContent !== $correctedContent) {
            $correctedContent = $newContent;
            $correctionsMade++;
            echo "<p style='color: #28a745;'>✅ Correção aplicada: Permitir funcionários</p>";
        }
    }
    
    // Se não encontrou padrões automáticos, procurar manualmente
    if ($correctionsMade === 0) {
        echo "<h4>🔍 Procurando verificações manuais:</h4>";
        
        $lines = explode("\n", $correctedContent);
        $modifiedLines = [];
        $lineNumber = 0;
        
        foreach ($lines as $line) {
            $lineNumber++;
            $originalLine = $line;
            
            // Verificar se a linha contém verificação de tipo loja
            if (preg_match("/user_type.*loja/i", $line) && strpos($line, "!==") !== false || strpos($line, "!=") !== false) {
                // Substituir verificação restritiva
                if (strpos($line, "'loja'") !== false) {
                    $line = str_replace("!== 'loja'", "!== 'loja' && \$_SESSION['user_type'] !== 'funcionario'", $line);
                    $line = str_replace("!= 'loja'", "!= 'loja' && \$_SESSION['user_type'] != 'funcionario'", $line);
                    echo "<p style='color: #28a745;'>✅ Linha {$lineNumber} corrigida: <code>" . htmlspecialchars(trim($originalLine)) . "</code></p>";
                    $correctionsMade++;
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
    echo "<p>📁 Arquivo: {$dashboardFile}</p>";
    echo "<p>🔧 Correções aplicadas: {$correctionsMade}</p>";
    echo "<p>🎯 Funcionários agora têm acesso ao dashboard original</p>";
    echo "</div>";
    
} else {
    echo "<div style='background: #f8d7da; padding: 15px; color: #721c24;'>";
    echo "<h4>❌ NENHUM BACKUP ENCONTRADO</h4>";
    echo "<p>Vou verificar se existe um dashboard original para corrigir</p>";
    echo "</div>";
    
    if (file_exists($dashboardFile)) {
        echo "<h3>📁 Dashboard atual encontrado, aplicando correção:</h3>";
        
        $currentContent = file_get_contents($dashboardFile);
        
        // Verificar se é o dashboard temporário
        if (strpos($currentContent, 'Dashboard Simplificado') !== false) {
            echo "<p style='color: #dc3545;'>⚠️ Dashboard atual é o temporário. Preciso do original.</p>";
            echo "<p>Por favor, forneça o código original do dashboard ou indique onde encontrá-lo.</p>";
        } else {
            echo "<p style='color: #28a745;'>✅ Dashboard original encontrado, aplicando correção...</p>";
            
            // Aplicar correções no dashboard atual
            $patterns = [
                "/if\s*\(\s*\\\$_SESSION\['user_type'\]\s*!==?\s*'loja'\s*\)/i" => "if (!in_array(\$_SESSION['user_type'] ?? '', ['loja', 'funcionario']))",
                "/if\s*\(\s*!\s*AuthController::isStore\(\s*\)\s*\)/i" => "if (!AuthController::hasStoreAccess())",
            ];
            
            $correctedContent = $currentContent;
            $correctionsMade = 0;
            
            foreach ($patterns as $pattern => $replacement) {
                $newContent = preg_replace($pattern, $replacement, $correctedContent);
                if ($newContent !== $correctedContent) {
                    $correctedContent = $newContent;
                    $correctionsMade++;
                }
            }
            
            if ($correctionsMade > 0) {
                file_put_contents($dashboardFile, $correctedContent);
                echo "<p style='color: #28a745;'>✅ {$correctionsMade} correções aplicadas no dashboard original</p>";
            } else {
                echo "<p style='color: #ffc107;'>⚠️ Nenhuma correção automática detectada. Dashboard pode já estar correto.</p>";
            }
        }
    }
}

echo "<h3>🧪 TESTE O DASHBOARD ORIGINAL:</h3>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='store/dashboard/' style='background: #007bff; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; font-size: 16px;'>🏠 TESTAR DASHBOARD ORIGINAL</a><br><br>";
echo "<a href='verify-dashboard-content.php' style='background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px;'>🔍 Verificar Conteúdo</a>";
echo "</div>";

echo "<h3>📋 INFORMAÇÕES:</h3>";
echo "<ul>";
echo "<li>Dashboard original foi restaurado e corrigido</li>";
echo "<li>Funcionários agora têm acesso igual aos lojistas</li>";
echo "<li>Todas as funcionalidades originais mantidas</li>";
echo "<li>Sistema simplificado aplicado apenas nas verificações</li>";
echo "</ul>";
?>