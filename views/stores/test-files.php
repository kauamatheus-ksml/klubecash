<?php
// views/stores/test-files.php
echo "<!DOCTYPE html>";
echo "<html><head><title>Teste de Arquivos</title></head>";
echo "<body>";
echo "<h1>Verificação de Arquivos</h1>";

// Definir caminhos possíveis
$possible_paths = [
    'controllers/StoreController.php' => '../../controllers/StoreController.php',
    'utils/Validator.php' => '../../utils/Validator.php',
    'config/database.php' => '../../config/database.php',
    'config/constants.php' => '../../config/constants.php'
];

foreach ($possible_paths as $description => $path) {
    $full_path = realpath($path);
    if (file_exists($path)) {
        echo "<p style='color: green;'>✓ $description: Encontrado em $full_path</p>";
    } else {
        echo "<p style='color: red;'>✗ $description: NÃO encontrado no caminho $path</p>";
        
        // Tentar caminhos alternativos
        $alternative_paths = [
            dirname(dirname(__DIR__)) . '/' . str_replace('../../', '', $path),
            __DIR__ . '/' . $path,
            $_SERVER['DOCUMENT_ROOT'] . '/' . $path
        ];
        
        foreach ($alternative_paths as $alt_path) {
            if (file_exists($alt_path)) {
                echo "<p style='color: orange;'>&nbsp;&nbsp;→ Encontrado em: $alt_path</p>";
                break;
            }
        }
    }
}

echo "<h2>Informações do Sistema</h2>";
echo "<p><strong>Diretório atual:</strong> " . __DIR__ . "</p>";
echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p><strong>Caminho real:</strong> " . realpath('.') . "</p>";

echo "</body></html>";
?>