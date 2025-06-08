<?php
// debug.php - Coloque na raiz do site
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Klube Cash</h1>";
echo "<pre>";

// Verificar versão PHP
echo "PHP Version: " . phpversion() . "\n\n";

// Verificar arquivos essenciais
$files_to_check = [
    'config/database.php',
    'config/constants.php', 
    'index.php',
    '.htaccess'
];

echo "=== VERIFICANDO ARQUIVOS ===\n";
foreach ($files_to_check as $file) {
    echo $file . ": " . (file_exists($file) ? "✓ Existe" : "✗ NÃO EXISTE") . "\n";
}

// Verificar conexão com banco
echo "\n=== TESTANDO BANCO DE DADOS ===\n";
if (file_exists('config/database.php')) {
    try {
        require_once 'config/database.php';
        echo "Conexão com banco: ✓ OK\n";
    } catch (Exception $e) {
        echo "Erro no banco: " . $e->getMessage() . "\n";
    }
} else {
    echo "Arquivo database.php não encontrado!\n";
}

// Verificar permissões
echo "\n=== PERMISSÕES DE DIRETÓRIOS ===\n";
$dirs = ['uploads', 'logs', 'assets'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        echo $dir . ": " . (is_writable($dir) ? "✓ Gravável" : "✗ Sem permissão") . "\n";
    } else {
        echo $dir . ": ✗ Diretório não existe\n";
    }
}

echo "</pre>";
?>