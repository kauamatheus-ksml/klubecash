<?php
require_once '../../config/database.php';

echo "🔧 CORRIGINDO API KEY\n\n";

$apiKey = 'kc_live_123456789012345678901234567890123456789012345678901234567890';
$keyHash = hash('sha256', $apiKey);

try {
    $db = Database::getConnection();
    
    echo "1️⃣ Limpando chaves antigas...\n";
    $db->exec("DELETE FROM api_keys WHERE partner_email LIKE '%live%' OR partner_email LIKE '%teste%'");
    
    echo "2️⃣ Inserindo nova chave...\n";
    $stmt = $db->prepare("
        INSERT INTO api_keys (
            key_hash, key_prefix, partner_name, partner_email, 
            permissions, rate_limit_per_minute, rate_limit_per_hour, is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $keyHash,
        'kc_live',
        'API Live Test',
        'live@klubecash.com',
        '["*"]',
        1000,
        10000,
        1
    ]);
    
    echo "3️⃣ Verificando inserção...\n";
    $stmt = $db->prepare("SELECT id, partner_name FROM api_keys WHERE key_hash = ?");
    $stmt->execute([$keyHash]);
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✅ API Key inserida com sucesso!\n";
        echo "ID: " . $result['id'] . "\n";
        echo "Nome: " . $result['partner_name'] . "\n";
        echo "Hash: $keyHash\n";
        echo "Chave: $apiKey\n";
        
        echo "\n🧪 TESTANDO VALIDAÇÃO...\n";
        require_once 'models/SimpleApiKey.php';
        
        $apiKeyModel = new SimpleApiKey();
        $validation = $apiKeyModel->validateApiKey($apiKey);
        
        if ($validation) {
            echo "✅ VALIDAÇÃO OK!\n";
            echo "Parceiro: " . $validation['partner_name'] . "\n";
        } else {
            echo "❌ VALIDAÇÃO FALHOU\n";
        }
        
    } else {
        echo "❌ Erro ao inserir chave\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}

echo "\n🚀 EXECUTE AGORA:\n";
echo "curl -X GET \"https://klubecash.com/api-external/v1/users\" \\\n";
echo "  -H \"X-API-Key: $apiKey\"\n";
?>