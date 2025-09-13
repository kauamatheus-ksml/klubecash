<?php
require_once '../sdk/KlubeCashSDK.php';

/**
 * Script de teste completo da API KlubeCash
 */

// Configurações
$API_KEY = 'kc_sua_chave_aqui'; // Substitua pela chave real
$BASE_URL = 'http://localhost'; // Ajuste conforme seu ambiente

echo "🧪 TESTANDO API KLUBECASH\n";
echo "========================\n\n";

try {
    $sdk = new KlubeCashSDK($API_KEY, $BASE_URL);
    
    // 1. Testar status da API
    echo "1️⃣ Testando status da API...\n";
    $health = $sdk->health();
    echo "✅ Status: " . ($health['data']['status'] ?? 'unknown') . "\n\n";
    
    $info = $sdk->info();
    echo "ℹ️ Versão da API: " . ($info['data']['version'] ?? 'unknown') . "\n";
    echo "🔑 Requer API Key: " . ($info['data']['requires_api_key'] ? 'Sim' : 'Não') . "\n\n";
    
    // 2. Testar usuários
    echo "2️⃣ Testando usuários...\n";
    
    // Listar usuários existentes
    $users = $sdk->users()->list(['page_size' => 5]);
    echo "👥 Usuários existentes: " . count($users['data']) . "\n";
    
    // Criar novo usuário
    $newUser = $sdk->users()->create([
        'name' => 'Teste API ' . date('H:i:s'),
        'email' => 'teste_' . time() . '@api.com',
        'password' => 'senha123',
        'type' => 'cliente'
    ]);
    echo "✅ Usuário criado: ID " . $newUser['data']['id'] . " - " . $newUser['data']['name'] . "\n\n";
    
    // 3. Testar lojas
    echo "3️⃣ Testando lojas...\n";
    
    $stores = $sdk->stores()->list(['status' => 'aprovado', 'page_size' => 5]);
    echo "🏪 Lojas aprovadas: " . count($stores['data']) . "\n";
    
    if (empty($stores['data'])) {
        // Criar loja de teste se não existir
        $newStore = $sdk->stores()->create([
            'trade_name' => 'Loja Teste API',
            'legal_name' => 'Loja Teste API LTDA',
            'cnpj' => '12345678000199',
            'email' => 'loja@teste.com',
            'phone' => '11999999999',
            'cashback_percentage' => 5.0,
            'status' => 'aprovado'
        ]);
        echo "✅ Loja criada: ID " . $newStore['data']['id'] . "\n";
        $storeId = $newStore['data']['id'];
    } else {
        $storeId = $stores['data'][0]['id'];
        echo "🏪 Usando loja existente: ID " . $storeId . "\n";
    }
    echo "\n";
    
    // 4. Testar cashback
    echo "4️⃣ Testando cálculo de cashback...\n";
    
    $cashback = $sdk->cashback()->calculate([
        'store_id' => $storeId,
        'amount' => 100.00
    ]);
    
    echo "💰 Compra de R$ 100,00 na loja ID " . $storeId . ":\n";
    echo "   - Cashback total: R$ " . number_format($cashback['data']['cashback_calculation']['total_cashback'], 2) . "\n";
    echo "   - Cliente recebe: R$ " . number_format($cashback['data']['cashback_calculation']['client_receives'], 2) . "\n";
    echo "   - Admin recebe: R$ " . number_format($cashback['data']['cashback_calculation']['admin_receives'], 2) . "\n";
    echo "   - Loja recebe: R$ " . number_format($cashback['data']['cashback_calculation']['store_receives'], 2) . "\n\n";
    
    // 5. Testar transações
    echo "5️⃣ Testando transações...\n";
    
    $transaction = $sdk->transactions()->create([
        'user_id' => $newUser['data']['id'],
        'store_id' => $storeId,
        'total_amount' => 150.00,
        'status' => 'pendente'
    ]);
    
    echo "✅ Transação criada: ID " . $transaction['data']['id'] . "\n";
    echo "   - Valor total: R$ " . number_format($transaction['data']['total_amount'], 2) . "\n";
    echo "   - Cashback: R$ " . number_format($transaction['data']['cashback_amount'], 2) . "\n";
    echo "   - Status: " . $transaction['data']['status'] . "\n\n";
    
    // Aprovar transação
    $approvedTransaction = $sdk->transactions()->updateStatus($transaction['data']['id'], 'aprovado', 'Aprovado via teste da API');
    echo "✅ Transação aprovada\n\n";
    
    // 6. Testar saldo do usuário
    echo "6️⃣ Testando saldo do usuário...\n";
    
    $balance = $sdk->users()->getBalance($newUser['data']['id']);
    echo "💳 Saldo do usuário " . $newUser['data']['name'] . ":\n";
    echo "   - Disponível: R$ " . number_format($balance['data']['available_balance'], 2) . "\n";
    echo "   - Pendente: R$ " . number_format($balance['data']['pending_balance'], 2) . "\n";
    echo "   - Total: R$ " . number_format($balance['data']['total_balance'], 2) . "\n\n";
    
    // 7. Testar estatísticas
    echo "7️⃣ Testando estatísticas...\n";
    
    $transactionStats = $sdk->transactions()->getStats(['days' => 30]);
    echo "📊 Estatísticas dos últimos 30 dias:\n";
    echo "   - Total de transações: " . $transactionStats['data']['summary']['total_transactions'] . "\n";
    echo "   - Total de vendas: R$ " . number_format($transactionStats['data']['summary']['total_sales'], 2) . "\n";
    echo "   - Total de cashback: R$ " . number_format($transactionStats['data']['summary']['total_cashback'], 2) . "\n\n";
    
    echo "🎉 TODOS OS TESTES CONCLUÍDOS COM SUCESSO!\n";
    
} catch (KlubeCashAPIException $e) {
    echo "❌ Erro da API: " . $e->getMessage() . " (HTTP " . $e->getHttpCode() . ")\n";
    
    if ($e->getValidationErrors()) {
        echo "🔍 Erros de validação:\n";
        foreach ($e->getValidationErrors() as $field => $error) {
            echo "   - {$field}: {$error}\n";
        }
    }
    
    if ($e->getResponseData()) {
        echo "📝 Dados da resposta: " . json_encode($e->getResponseData(), JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro geral: " . $e->getMessage() . "\n";
}
?>