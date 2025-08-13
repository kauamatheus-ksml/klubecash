<?php
// debug-visitor.php - Arquivo para debuggar problemas

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'config/constants.php';

echo "<h1>🔍 DEBUG - Cliente Visitante</h1>";

echo "<h2>1. Verificando Constantes:</h2>";

// Verificar constantes necessárias
$constantes = [
    'CLIENT_TYPE_VISITOR',
    'CLIENT_TYPE_COMPLETE', 
    'USER_TYPE_CLIENT',
    'USER_ACTIVE',
    'VISITOR_PHONE_MIN_LENGTH',
    'MSG_VISITOR_CREATED'
];

foreach ($constantes as $const) {
    if (defined($const)) {
        echo "✅ {$const} = " . constant($const) . "<br>";
    } else {
        echo "❌ {$const} NÃO DEFINIDA<br>";
    }
}

echo "<h2>2. Verificando Estrutura do Banco:</h2>";

try {
    $db = Database::getConnection();
    echo "✅ Conexão com banco OK<br>";
    
    // Verificar se colunas existem
    $colunas = ['tipo_cliente', 'loja_criadora_id'];
    
    foreach ($colunas as $coluna) {
        $stmt = $db->query("SHOW COLUMNS FROM usuarios LIKE '{$coluna}'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Coluna '{$coluna}' existe<br>";
        } else {
            echo "❌ Coluna '{$coluna}' NÃO existe<br>";
        }
    }
    
    // Verificar se senha pode ser NULL
    $stmt = $db->query("SHOW COLUMNS FROM usuarios LIKE 'senha_hash'");
    $coluna = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($coluna && strpos($coluna['Null'], 'YES') !== false) {
        echo "✅ Coluna 'senha_hash' permite NULL<br>";
    } else {
        echo "❌ Coluna 'senha_hash' NÃO permite NULL<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erro no banco: " . $e->getMessage() . "<br>";
}

echo "<h2>3. Testando API:</h2>";

// Simular dados de teste
$testData = [
    'action' => 'create_visitor_client',
    'nome' => 'Teste Debug',
    'telefone' => '11999887766',
    'store_id' => 1
];

echo "📤 Dados de teste: " . json_encode($testData) . "<br>";

// Simular sessão de loja
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_type'] = 'loja';
$_SESSION['store_id'] = 1;

echo "🔑 Sessão simulada criada<br>";

echo "<h2>4. Verificando Arquivos:</h2>";

$arquivos = [
    'api/store-client-search.php',
    'config/constants.php',
    'config/database.php'
];

foreach ($arquivos as $arquivo) {
    if (file_exists($arquivo)) {
        echo "✅ {$arquivo} existe<br>";
    } else {
        echo "❌ {$arquivo} NÃO existe<br>";
    }
}

echo "<hr>";
echo "<p><strong>Para corrigir os problemas, execute as instruções abaixo!</strong></p>";

?>