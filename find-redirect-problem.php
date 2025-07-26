<?php
session_start();

echo "<h2>🔍 CAÇADOR DE REDIRECIONAMENTO INCORRETO</h2>";

// Verificar todos os arquivos que podem estar fazendo redirecionamento
$filesToCheck = [
    'index.php',
    'views/auth/login.php', 
    'controllers/AuthController.php',
    '.htaccess',
    'assets/js/main.js',
    'assets/js/auth.js'
];

echo "<h3>📁 VERIFICANDO ARQUIVOS:</h3>";

foreach ($filesToCheck as $file) {
    if (file_exists($file)) {
        echo "<h4>🔍 Analisando: {$file}</h4>";
        
        $content = file_get_contents($file);
        
        // Procurar por redirecionamentos suspeitos
        $patterns = [
            '/header\s*\(\s*[\'"]Location:\s*.*client.*dashboard/i',
            '/window\.location.*client.*dashboard/i',
            '/redirect.*client.*dashboard/i',
            '/user_type.*cliente/i',
            '/funcionario.*cliente/i'
        ];
        
        $found = false;
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                $found = true;
                echo "<div style='background: #f8d7da; padding: 10px; margin: 5px 0; color: #721c24;'>";
                echo "<strong>❌ PROBLEMA ENCONTRADO em {$file}:</strong><br>";
                foreach ($matches[0] as $match) {
                    $line = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                    echo "Linha {$line}: <code>" . htmlspecialchars($match[0]) . "</code><br>";
                }
                echo "</div>";
            }
        }
        
        if (!$found) {
            echo "<p style='color: #28a745;'>✅ Nenhum redirecionamento suspeito encontrado</p>";
        }
        
    } else {
        echo "<p style='color: #6c757d;'>⚠️ Arquivo {$file} não encontrado</p>";
    }
    echo "<hr>";
}

echo "<h3>🔧 INTERCEPTADOR DE REDIRECIONAMENTO</h3>";
echo "<p>Vou criar um interceptador para capturar o redirecionamento incorreto...</p>";
?>