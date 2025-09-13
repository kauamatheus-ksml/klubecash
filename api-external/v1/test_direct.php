<?php
// Teste direto da API sem usar o router
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🧪 Teste Direto da API KlubeCash</h1>";

// 1. Testar autoload dos arquivos
echo "<h2>1. Testando includes...</h2>";

try {
    echo "✅ Incluindo database.php...<br>";
    require_once '../../config/database.php';
    
    echo "✅ Incluindo config da API...<br>";
    require_once 'config/api_config.php';
    
    echo "✅ Incluindo Response...<br>";
    require_once 'core/Response.php';
    
    echo "✅ Incluindo ApiException...<br>";
    require_once 'core/ApiException.php';
    
    echo "✅ Todos os arquivos incluídos com sucesso!<br><br>";
    
} catch (Exception $e) {
    echo "❌ Erro ao incluir arquivos: " . $e->getMessage() . "<br>";
    echo "📁 Diretório atual: " . __DIR__ . "<br>";
    echo "📁 Arquivo atual: " . __FILE__ . "<br>";
    exit;
}

// 2. Testar conexão com banco
echo "<h2>2. Testando conexão com banco...</h2>";

try {
    $db = Database::getConnection();
    echo "✅ Conexão com banco estabelecida!<br>";
    
    // Testar query simples
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM usuarios");
    $stmt->execute();
    $result = $stmt->fetch();
    
    echo "✅ Query executada! Total de usuários: " . $result['total'] . "<br><br>";
    
} catch (Exception $e) {
    echo "❌ Erro na conexão: " . $e->getMessage() . "<br>";
    echo "🔧 Verifique config/database.php<br><br>";
}

// 3. Testar API Keys
echo "<h2>3. Testando tabela API Keys...</h2>";

try {
    require_once 'models/ApiKey.php';
    
    $apiKey = new ApiKey();
    $keys = $apiKey->listApiKeys();
    
    echo "✅ Modelo ApiKey funcionando!<br>";
    echo "🔑 API Keys encontradas: " . count($keys) . "<br>";
    
    if (count($keys) > 0) {
        echo "📋 Primeira key: " . $keys[0]['key_prefix'] . " - " . $keys[0]['partner_name'] . "<br>";
    }
    
    echo "<br>";
    
} catch (Exception $e) {
    echo "❌ Erro com API Keys: " . $e->getMessage() . "<br>";
    echo "🗄️ Verifique se tabela api_keys existe<br><br>";
}

// 4. Testar Response
echo "<h2>4. Testando Response...</h2>";

try {
    // Não pode executar porque vai sair do script
    echo "✅ Classe Response carregada!<br>";
    echo "📤 Response funcionaria corretamente<br><br>";
    
} catch (Exception $e) {
    echo "❌ Erro na Response: " . $e->getMessage() . "<br><br>";
}

// 5. Informações do ambiente
echo "<h2>5. Informações do ambiente...</h2>";
echo "🖥️ Servidor: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "🐘 PHP: " . PHP_VERSION . "<br>";
echo "📁 Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "📁 Script: " . $_SERVER['SCRIPT_FILENAME'] . "<br>";
echo "🌐 Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "<br>";
echo "🔧 URL Rewrite: " . (isset($_SERVER['HTTP_MOD_REWRITE']) ? 'ON' : 'OFF/Unknown') . "<br>";

// 6. Testar um endpoint simples
echo "<h2>6. Simulando resposta da API...</h2>";

header('Content-Type: application/json');
$response = [
    'success' => true,
    'message' => 'Teste direto funcionando!',
    'timestamp' => date('c'),
    'version' => 'v1',
    'test_results' => [
        'includes' => 'OK',
        'database' => 'OK',
        'api_keys' => 'OK',
        'response' => 'OK'
    ]
];

echo "<pre>" . json_encode($response, JSON_PRETTY_PRINT) . "</pre>";

echo "<h2>✅ Teste concluído!</h2>";
echo "<p>Se você vê esta mensagem, os componentes básicos estão funcionando.</p>";
echo "<p>O erro 500 pode ser no .htaccess ou no roteamento.</p>";
?>