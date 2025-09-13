<?php
require_once '../config/database.php';
require_once '../models/ApiKey.php';

try {
    $apiKey = new ApiKey();
    
    // Buscar a chave de teste existente
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT key_prefix FROM api_keys WHERE partner_email = 'teste@example.com' LIMIT 1");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✅ API Key de teste encontrada!\n";
        echo "🔑 Use esta chave para testes: " . $result['key_prefix'] . "_[restante_da_chave]\n\n";
        echo "⚠️  Para obter a chave completa, execute este comando no banco:\n";
        echo "SELECT CONCAT(key_prefix, '_test_key_12345678901234567890123456789012345678901234567890123456') as full_key FROM api_keys WHERE partner_email = 'teste@example.com';\n\n";
        
        // Ou gerar nova chave para testes
        echo "🆕 Ou use esta nova chave de teste:\n";
        $newKey = $apiKey->generateApiKey(
            'Teste API', 
            'novo_teste@example.com',
            ['*'], // Todas as permissões
            ['rate_limit_per_minute' => 1000, 'rate_limit_per_hour' => 10000]
        );
        
        echo "🔑 Nova API Key: " . $newKey['api_key'] . "\n";
        echo "🎯 ID da Key: " . $newKey['api_key_id'] . "\n";
        
    } else {
        // Criar nova chave de teste
        $newKey = $apiKey->generateApiKey(
            'Teste API', 
            'teste@example.com',
            ['*'], // Todas as permissões
            ['rate_limit_per_minute' => 1000, 'rate_limit_per_hour' => 10000]
        );
        
        echo "✅ Nova API Key de teste criada!\n";
        echo "🔑 API Key: " . $newKey['api_key'] . "\n";
        echo "🎯 ID da Key: " . $newKey['api_key_id'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>