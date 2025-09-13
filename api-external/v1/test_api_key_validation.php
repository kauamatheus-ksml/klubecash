<?php
require_once '../../config/database.php';
require_once 'models/ApiKey.php';

echo "🔍 TESTE: Validação de API Key em tempo real\n\n";

// Chave que estamos usando
$testKey = 'kc_live_123456789012345678901234567890123456789012345678901234567890';

try {
    echo "1️⃣ Conectando ao banco...\n";
    $db = Database::getConnection();
    echo "✅ Conexão OK\n\n";
    
    echo "2️⃣ Gerando hash da chave...\n";
    $keyHash = hash('sha256', $testKey);
    echo "Hash: $keyHash\n\n";
    
    echo "3️⃣ Buscando diretamente no banco...\n";
    $stmt = $db->prepare("
        SELECT id, partner_name, is_active, expires_at,
               (expires_at IS NULL OR expires_at > NOW()) as not_expired
        FROM api_keys 
        WHERE key_hash = ?
    ");
    $stmt->execute([$keyHash]);
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✅ Chave encontrada!\n";
        echo "ID: " . $result['id'] . "\n";
        echo "Parceiro: " . $result['partner_name'] . "\n";
        echo "Ativa: " . ($result['is_active'] ? 'Sim' : 'Não') . "\n";
        echo "Não expirada: " . ($result['not_expired'] ? 'Sim' : 'Não') . "\n";
        
        if ($result['is_active'] && $result['not_expired']) {
            echo "✅ Chave deveria ser válida!\n";
        } else {
            echo "❌ Chave não deveria ser válida\n";
        }
    } else {
        echo "❌ Chave não encontrada no banco\n";
    }
    
    echo "\n4️⃣ Testando com modelo ApiKey...\n";
    $apiKeyModel = new ApiKey();
    $validation = $apiKeyModel->validateApiKey($testKey);
    
    if ($validation) {
        echo "✅ Modelo validou a chave!\n";
        echo "Dados: " . json_encode($validation, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ Modelo rejeitou a chave - vamos debugar...\n";
        
        // Debug manual da validação
        echo "\n🔍 Debug manual da validação:\n";
        
        // 1. Verificar prefixo
        if (!str_starts_with($testKey, 'kc_')) {
            echo "❌ Problema no prefixo\n";
        } else {
            echo "✅ Prefixo OK\n";
        }
        
        // 2. Testar query manual
        $stmt = $db->prepare("
            SELECT id, partner_name, partner_email, permissions, 
                   rate_limit_per_minute, rate_limit_per_hour, 
                   is_active, expires_at
            FROM api_keys 
            WHERE key_hash = ? AND is_active = 1
        ");
        $stmt->execute([$keyHash]);
        $manualResult = $stmt->fetch();
        
        if ($manualResult) {
            echo "✅ Query manual funcionou\n";
            echo "Dados: " . json_encode($manualResult, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "❌ Query manual falhou\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
?>