<?php
require_once '../../config/database.php';
require_once 'models/ApiKey.php';

// Gerar chave real para testes
$apiKey = new ApiKey();

$result = $apiKey->generateApiKey(
    'Teste Produção',
    'producao@klubecash.com',
    ['*'], // Todas as permissões
    [
        'rate_limit_per_minute' => 1000,
        'rate_limit_per_hour' => 10000,
        'notes' => 'Chave de teste para produção - ' . date('Y-m-d H:i:s')
    ]
);

echo "🔑 Nova API Key gerada:\n";
echo "Key: " . $result['api_key'] . "\n";
echo "ID: " . $result['api_key_id'] . "\n";
echo "\n✅ Use esta chave para testar a API!\n";
?>