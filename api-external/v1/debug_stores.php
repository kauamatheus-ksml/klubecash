<?php
require_once '../../config/database.php';

echo "🔍 DEBUG: Status das lojas\n\n";

try {
    $db = Database::getConnection();
    
    echo "1️⃣ Verificando lojas por status...\n";
    $stmt = $db->prepare("
        SELECT status, COUNT(*) as count 
        FROM lojas 
        GROUP BY status
    ");
    $stmt->execute();
    $statusCount = $stmt->fetchAll();
    
    foreach ($statusCount as $row) {
        echo "Status '{$row['status']}': {$row['count']} lojas\n";
    }
    
    echo "\n2️⃣ Lojas com status 'aprovado'...\n";
    $stmt = $db->prepare("
        SELECT id, nome_fantasia, status, porcentagem_cashback
        FROM lojas 
        WHERE status = 'aprovado'
        LIMIT 5
    ");
    $stmt->execute();
    $approvedStores = $stmt->fetchAll();
    
    if ($approvedStores) {
        foreach ($approvedStores as $store) {
            echo "ID: {$store['id']} - {$store['nome_fantasia']} - {$store['status']} - Cashback: {$store['porcentagem_cashback']}%\n";
        }
    } else {
        echo "❌ Nenhuma loja com status 'aprovado' encontrada!\n";
    }
    
    echo "\n3️⃣ Todas as lojas (primeiras 5)...\n";
    $stmt = $db->prepare("
        SELECT id, nome_fantasia, status, porcentagem_cashback
        FROM lojas 
        ORDER BY id DESC
        LIMIT 5
    ");
    $stmt->execute();
    $allStores = $stmt->fetchAll();
    
    foreach ($allStores as $store) {
        echo "ID: {$store['id']} - {$store['nome_fantasia']} - Status: '{$store['status']}' - Cashback: {$store['porcentagem_cashback']}%\n";
    }
    
    echo "\n4️⃣ Testando query do cashback...\n";
    $testStoreId = $allStores[0]['id'] ?? 59;
    
    $stmt = $db->prepare("
        SELECT id, nome_fantasia, porcentagem_cashback, status 
        FROM lojas 
        WHERE id = ? AND status = 'aprovado'
    ");
    $stmt->execute([$testStoreId]);
    $testStore = $stmt->fetch();
    
    if ($testStore) {
        echo "✅ Loja ID $testStoreId encontrada e aprovada!\n";
        echo "Nome: {$testStore['nome_fantasia']}\n";
        echo "Cashback: {$testStore['porcentagem_cashback']}%\n";
    } else {
        echo "❌ Loja ID $testStoreId não encontrada ou não aprovada\n";
        
        // Tentar sem filtro de status
        $stmt = $db->prepare("SELECT id, nome_fantasia, status FROM lojas WHERE id = ?");
        $stmt->execute([$testStoreId]);
        $storeAnyStatus = $stmt->fetch();
        
        if ($storeAnyStatus) {
            echo "ℹ️ Loja existe mas status é: '{$storeAnyStatus['status']}'\n";
            
            if ($storeAnyStatus['status'] !== 'aprovado') {
                echo "🔧 Vou aprovar a loja para teste...\n";
                $stmt = $db->prepare("UPDATE lojas SET status = 'aprovado' WHERE id = ?");
                $stmt->execute([$testStoreId]);
                echo "✅ Loja aprovada!\n";
            }
        } else {
            echo "❌ Loja ID $testStoreId não existe no banco\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>