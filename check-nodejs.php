<?php
echo "<h2>🔍 Verificação de Capacidades do Servidor</h2>";

// Verificar se o servidor suporta exec/shell_exec
echo "<h3>📋 Comandos do Sistema:</h3>";
if (function_exists('shell_exec')) {
    echo "<p style='color: green;'>✅ shell_exec disponível</p>";
    
    // Testar Node.js
    $nodeVersion = shell_exec('node --version 2>&1');
    if ($nodeVersion && !empty(trim($nodeVersion))) {
        echo "<p style='color: green;'>✅ Node.js detectado: " . trim($nodeVersion) . "</p>";
        
        // Testar NPM
        $npmVersion = shell_exec('npm --version 2>&1');
        if ($npmVersion && !empty(trim($npmVersion))) {
            echo "<p style='color: green;'>✅ NPM detectado: " . trim($npmVersion) . "</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ NPM não detectado</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Node.js não detectado</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Comandos do sistema bloqueados</p>";
}

// Verificar diretório atual e permissões
echo "<h3>📁 Informações do Diretório:</h3>";
echo "<p>Diretório atual: " . getcwd() . "</p>";
echo "<p>Permissões de escrita: " . (is_writable('.') ? '✅ Sim' : '❌ Não') . "</p>";

// Verificar portas disponíveis
echo "<h3>🌐 Conectividade de Rede:</h3>";
$socket = @fsockopen('localhost', 3001, $errno, $errstr, 1);
if ($socket) {
    echo "<p style='color: green;'>✅ Porta 3001 acessível</p>";
    fclose($socket);
} else {
    echo "<p style='color: orange;'>⚠️ Porta 3001 não acessível (esperado se bot não estiver rodando)</p>";
}

// Informações do PHP
echo "<h3>🔧 Informações do PHP:</h3>";
echo "<p>Versão PHP: " . phpversion() . "</p>";
echo "<p>Sistema operacional: " . php_uname() . "</p>";
?>