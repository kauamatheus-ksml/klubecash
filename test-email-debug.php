<?php
// verificar-conflito.php

echo "<h1>🔍 Verificar Conflito de Classes</h1>";

$files = [
    '/config/email.php',
    '/utils/Email.php',
    '/libs/Email.php',
    '/classes/Email.php'
];

foreach ($files as $file) {
    $fullPath = __DIR__ . $file;
    
    if (file_exists($fullPath)) {
        echo "<p>✅ <strong>ENCONTRADO:</strong> $file</p>";
        
        // Ler as primeiras linhas para ver se declara classe Email
        $content = file_get_contents($fullPath);
        if (strpos($content, 'class Email') !== false) {
            echo "<p>⚠️ <strong>DECLARA CLASSE EMAIL:</strong> $file</p>";
        }
        
        // Mostrar primeiras linhas
        $lines = explode("\n", $content);
        $preview = array_slice($lines, 0, 10);
        echo "<pre style='background:#f5f5f5;padding:10px;'>";
        echo htmlspecialchars(implode("\n", $preview));
        echo "</pre>";
        echo "<hr>";
    } else {
        echo "<p>❌ <strong>NÃO EXISTE:</strong> $file</p>";
    }
}

// Verificar se recover-password.php está incluindo config/email.php
$recoverFile = __DIR__ . '/views/auth/recover-password.php';
if (file_exists($recoverFile)) {
    $recoverContent = file_get_contents($recoverFile);
    
    echo "<h2>📋 Verificar recover-password.php</h2>";
    
    if (strpos($recoverContent, 'config/email.php') !== false) {
        echo "<p>⚠️ <strong>PROBLEMA:</strong> recover-password.php inclui config/email.php</p>";
    } else {
        echo "<p>✅ recover-password.php NÃO inclui config/email.php</p>";
    }
    
    // Mostrar includes
    preg_match_all('/require_once.*?;/', $recoverContent, $matches);
    echo "<p><strong>Includes encontrados:</strong></p>";
    foreach ($matches[0] as $include) {
        echo "<p>• " . htmlspecialchars($include) . "</p>";
    }
}
?>