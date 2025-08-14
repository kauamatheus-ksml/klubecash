<?php
// test-direct-include.php
echo "<h1>Teste Direto da API</h1>";

// Simular dados POST
$_POST = [
    'action' => 'create_visitor_client',
    'nome' => 'Teste Direto',
    'telefone' => '11888777666',
    'store_id' => 1
];

// Simular sessão
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_type'] = 'loja';
$_SESSION['store_id'] = 1;

// Capturar entrada JSON
$GLOBALS['HTTP_RAW_POST_DATA'] = json_encode($_POST);

// Simular entrada JSON
if (!function_exists('file_get_contents_override')) {
    function file_get_contents_override($filename) {
        if ($filename === 'php://input') {
            return json_encode($GLOBALS['_POST'] ?? []);
        }
        return file_get_contents($filename);
    }
}

echo "🔧 Incluindo API diretamente...<br>";

// Incluir a API diretamente
ob_start();
include 'api/store-client-search.php';
$result = ob_get_clean();

echo "<h2>Resultado:</h2>";
echo "<pre>$result</pre>";
?>