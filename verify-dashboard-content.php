<?php
echo "<h2>🔍 VERIFICAR CONTEÚDO DO DASHBOARD</h2>";

$dashboardFile = 'views/stores/dashboard.php';

if (file_exists($dashboardFile)) {
    $content = file_get_contents($dashboardFile);
    
    echo "<h3>📁 Arquivo: {$dashboardFile}</h3>";
    echo "<p><strong>Tamanho:</strong> " . strlen($content) . " bytes</p>";
    echo "<p><strong>Linhas:</strong> " . substr_count($content, "\n") . "</p>";
    
    // Verificar se é o dashboard temporário
    if (strpos($content, 'Dashboard Simplificado') !== false) {
        echo "<div style='background: #f8d7da; padding: 15px; color: #721c24;'>";
        echo "<h4>❌ DASHBOARD TEMPORÁRIO DETECTADO</h4>";
        echo "<p>Este é o dashboard temporário que criamos. Você quer o original.</p>";
        echo "</div>";
        
        echo "<h4>📋 Opções para restaurar o original:</h4>";
        echo "<ol>";
        echo "<li><strong>Buscar backup:</strong> Verificar se existe backup em views/stores/dashboard.php.backup.*</li>";
        echo "<li><strong>Código original:</strong> Se você tem o código original, posso aplicar apenas as correções necessárias</li>";
        echo "<li><strong>Repositório:</strong> Se tem controle de versão, reverter para versão anterior</li>";
        echo "</ol>";
        
        // Listar backups disponíveis
        $backups = glob('views/stores/dashboard.php.backup.*');
        if (!empty($backups)) {
            echo "<h4>📋 Backups encontrados:</h4>";
            foreach ($backups as $backup) {
                $date = date('Y-m-d H:i:s', filemtime($backup));
                $size = filesize($backup);
                echo "<p>📁 {$backup} - {$date} - {$size} bytes</p>";
            }
        }
        
    } else {
        echo "<div style='background: #d4edda; padding: 15px; color: #155724;'>";
        echo "<h4>✅ DASHBOARD ORIGINAL DETECTADO</h4>";
        echo "<p>Este parece ser o dashboard original com as funcionalidades completas.</p>";
        echo "</div>";
        
        // Mostrar primeiras linhas para confirmar
        $lines = explode("\n", $content);
        echo "<h4>🔍 Primeiras 20 linhas:</h4>";
        echo "<div style='background: #f8f9fa; padding: 15px; font-family: monospace; font-size: 12px; border: 1px solid #ddd; max-height: 300px; overflow-y: scroll;'>";
        for ($i = 0; $i < min(20, count($lines)); $i++) {
            $lineNum = $i + 1;
            echo "<div>{$lineNum}: " . htmlspecialchars($lines[$i]) . "</div>";
        }
        echo "</div>";
    }
    
} else {
    echo "<div style='background: #f8d7da; padding: 15px; color: #721c24;'>";
    echo "<h4>❌ DASHBOARD NÃO ENCONTRADO</h4>";
    echo "<p>Arquivo {$dashboardFile} não existe</p>";
    echo "</div>";
}

echo "<div style='margin: 20px 0;'>";
echo "<a href='restore-original-dashboard.php' style='background: #dc3545; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px;'>🔧 Restaurar Original</a>";
echo "</div>";
?>