<?php
require_once '../../config/database.php';
require_once 'models/ApiKey.php';

echo "🔍 DEBUG: Validação de API Key\n\n";

// API Key para testar
$testKey = 'kc_live_123456789012345678901234567890123456789012345678901234567890';

echo "🔑 Testando chave: $testKey\n\n";

try {
    // 1. Verificar se chave começa com prefixo correto
    echo "1️⃣ Verificando prefixo...\n";
    if (str_starts_with($testKey, 'kc_')) {
        echo "✅ Prefixo correto\n";
    } else {
        echo "❌ Prefixo incorreto\n";
        exit;
    }
    
    // 2. Gerar hash da chave
    echo "\n2️⃣ Gerando hash...\n";
    $keyHash = hash('sha256', $testKey);
    echo "Hash gerado: $keyHash\n";
    
    // 3. Buscar no banco
    echo "\n3️⃣ Buscando no banco...\n";
    $db = Database::getConnection();
    
    $stmt = $db->prepare("
        SELECT id, partner_name, partner_email, permissions, 
               rate_limit_per_minute, rate_limit_per_hour, 
               is_active, expires_at
        FROM api_keys 
        WHERE key_hash = ? AND is_active = 1
    ");
    
    $stmt->execute([$keyHash]);
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✅ Chave encontrada no banco!\n";
        echo "ID: " . $result['id'] . "\n";
        echo "Parceiro: " . $result['partner_name'] . "\n";
        echo "Email: " . $result['partner_email'] . "\n";
        echo "Ativo: " . ($result['is_active'] ? 'Sim' : 'Não') . "\n";
        echo "Expira em: " . ($result['expires_at'] ?: 'Nunca') . "\n";
        echo "Permissões: " . $result['permissions'] . "\n";
        
        // 4. Testar validação com modelo
        echo "\n4️⃣ Testando com modelo ApiKey...\n";
        $apiKeyModel = new ApiKey();
        $validation = $apiKeyModel->validateApiKey($testKey);
        
        if ($validation) {
            echo "✅ Modelo validou a chave!\n";
            echo "Dados retornados: " . json_encode($validation, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "❌ Modelo rejeitou a chave\n";
        }
        
    } else {
        echo "❌ Chave NÃO encontrada no banco\n";
        
        // Verificar o que existe no banco
        echo "\n🔍 Chaves existentes no banco:\n";
        $stmt = $db->prepare("SELECT key_prefix, partner_name, is_active FROM api_keys ORDER BY created_at DESC LIMIT 5");
        $stmt->execute();
        $keys = $stmt->fetchAll();
        
        foreach ($keys as $key) {
            echo "- " . $key['key_prefix'] . " (" . $key['partner_name'] . ") - " . ($key['is_active'] ? 'Ativo' : 'Inativo') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
?>