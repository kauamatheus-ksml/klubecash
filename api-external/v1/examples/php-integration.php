<?php
/**
 * KlubeCash API - Exemplo de Integração em PHP
 * 
 * Este exemplo demonstra como integrar com a KlubeCash API
 * para listar usuários, lojas e calcular cashback
 */

class KlubeCashAPI {
    private $apiKey;
    private $baseUrl;
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
        $this->baseUrl = 'https://klubecash.com/api-external/v1';
    }
    
    /**
     * Fazer requisição para a API
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $headers = [
            'X-API-Key: ' . $this->apiKey,
            'Content-Type: application/json',
            'User-Agent: KlubeCash-PHP-Client/1.0'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        // Configurar método HTTP
        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Erro cURL: " . $error);
        }
        
        $decodedResponse = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMessage = $decodedResponse['message'] ?? 'Erro na requisição';
            throw new Exception("Erro API (HTTP $httpCode): $errorMessage");
        }
        
        return $decodedResponse;
    }
    
    /**
     * Obter informações da API
     */
    public function getApiInfo() {
        return $this->makeRequest('/auth/info');
    }
    
    /**
     * Verificar saúde da API
     */
    public function checkHealth() {
        return $this->makeRequest('/auth/health');
    }
    
    /**
     * Listar usuários
     */
    public function getUsers() {
        return $this->makeRequest('/users');
    }
    
    /**
     * Listar lojas
     */
    public function getStores() {
        return $this->makeRequest('/stores');
    }
    
    /**
     * Calcular cashback
     */
    public function calculateCashback($storeId, $amount) {
        $data = [
            'store_id' => intval($storeId),
            'amount' => floatval($amount)
        ];
        
        return $this->makeRequest('/cashback/calculate', 'POST', $data);
    }
}

// Exemplo de uso
try {
    echo "=== EXEMPLO DE INTEGRAÇÃO KLUBECASH API ===\n\n";
    
    // Inicializar cliente da API
    // IMPORTANTE: Substitua pela sua API Key real
    $apiKey = 'kc_live_123456789012345678901234567890123456789012345678901234567890';
    $api = new KlubeCashAPI($apiKey);
    
    // 1. Verificar informações da API
    echo "1. Informações da API:\n";
    $info = $api->getApiInfo();
    echo "   Nome: " . $info['data']['api_name'] . "\n";
    echo "   Versão: " . $info['data']['version'] . "\n";
    echo "   Requer API Key: " . ($info['data']['requires_api_key'] ? 'Sim' : 'Não') . "\n\n";
    
    // 2. Verificar saúde da API
    echo "2. Status da API:\n";
    $health = $api->checkHealth();
    echo "   Status: " . $health['data']['status'] . "\n";
    echo "   Database: " . $health['data']['database'] . "\n\n";
    
    // 3. Listar usuários
    echo "3. Últimos usuários:\n";
    $users = $api->getUsers();
    if (!empty($users['data'])) {
        foreach (array_slice($users['data'], 0, 3) as $user) {
            echo "   - ID: {$user['id']} | Nome: {$user['name']} | Email: {$user['email']}\n";
        }
        echo "   Total de usuários retornados: " . count($users['data']) . "\n\n";
    } else {
        echo "   Nenhum usuário encontrado.\n\n";
    }
    
    // 4. Listar lojas
    echo "4. Lojas disponíveis:\n";
    $stores = $api->getStores();
    if (!empty($stores['data'])) {
        foreach ($stores['data'] as $store) {
            echo "   - ID: {$store['id']} | Nome: {$store['trade_name']} | Status: {$store['status']}\n";
        }
        echo "   Total de lojas retornadas: " . count($stores['data']) . "\n\n";
    } else {
        echo "   Nenhuma loja encontrada.\n\n";
    }
    
    // 5. Calcular cashback (usando primeira loja disponível)
    if (!empty($stores['data'])) {
        $firstStore = $stores['data'][0];
        echo "5. Calculando cashback:\n";
        echo "   Loja: {$firstStore['trade_name']} (ID: {$firstStore['id']})\n";
        echo "   Valor da compra: R$ 100,00\n";
        
        $cashback = $api->calculateCashback($firstStore['id'], 100.00);
        
        if (isset($cashback['data'])) {
            $calc = $cashback['data']['cashback_calculation'];
            echo "   Porcentagem da loja: {$cashback['data']['store_cashback_percentage']}%\n";
            echo "   Cashback total: R$ " . number_format($calc['total_cashback'], 2, ',', '.') . "\n";
            echo "   Cliente recebe: R$ " . number_format($calc['client_receives'], 2, ',', '.') . "\n";
            echo "   Admin recebe: R$ " . number_format($calc['admin_receives'], 2, ',', '.') . "\n";
            echo "   Loja recebe: R$ " . number_format($calc['store_receives'], 2, ',', '.') . "\n";
        }
    }
    
    echo "\n=== INTEGRAÇÃO CONCLUÍDA COM SUCESSO ===\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    
    // Dicas para resolução de problemas
    echo "\nDicas para resolução:\n";
    echo "- Verifique se sua API Key está correta\n";
    echo "- Confirme se você tem acesso à internet\n";
    echo "- Verifique se a API está funcionando em: https://klubecash.com/api-external/v1/auth/info\n";
    echo "- Entre em contato com o suporte se o problema persistir\n";
}

/**
 * EXEMPLO AVANÇADO: Classe para gerenciar transações de cashback
 */
class KlubeCashTransactionManager extends KlubeCashAPI {
    
    /**
     * Processar uma compra completa com cashback
     */
    public function processTransaction($storeId, $amount, $customerEmail) {
        try {
            // 1. Verificar se a loja existe e está ativa
            $stores = $this->getStores();
            $targetStore = null;
            
            foreach ($stores['data'] as $store) {
                if ($store['id'] == $storeId && $store['status'] === 'aprovado') {
                    $targetStore = $store;
                    break;
                }
            }
            
            if (!$targetStore) {
                throw new Exception("Loja não encontrada ou não aprovada (ID: $storeId)");
            }
            
            // 2. Calcular cashback
            $cashbackData = $this->calculateCashback($storeId, $amount);
            
            // 3. Simular salvamento da transação (você implementaria a lógica real aqui)
            $transaction = [
                'id' => uniqid(),
                'store_id' => $storeId,
                'store_name' => $targetStore['trade_name'],
                'customer_email' => $customerEmail,
                'purchase_amount' => $amount,
                'cashback_data' => $cashbackData['data'],
                'created_at' => date('Y-m-d H:i:s'),
                'status' => 'processed'
            ];
            
            return [
                'success' => true,
                'message' => 'Transação processada com sucesso',
                'transaction' => $transaction
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao processar transação: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obter resumo mensal de cashback
     */
    public function getMonthlyCashbackSummary() {
        // Este é um exemplo de como você poderia estruturar
        // um relatório mensal usando os dados da API
        
        try {
            $stores = $this->getStores();
            $summary = [
                'month' => date('Y-m'),
                'total_stores' => count($stores['data']),
                'active_stores' => 0,
                'estimated_cashback' => 0
            ];
            
            foreach ($stores['data'] as $store) {
                if ($store['status'] === 'aprovado') {
                    $summary['active_stores']++;
                    
                    // Simular cálculo de cashback médio
                    $avgPurchase = 100; // R$ 100 valor médio simulado
                    $cashback = $this->calculateCashback($store['id'], $avgPurchase);
                    if (isset($cashback['data']['cashback_calculation']['total_cashback'])) {
                        $summary['estimated_cashback'] += $cashback['data']['cashback_calculation']['total_cashback'];
                    }
                }
            }
            
            return $summary;
            
        } catch (Exception $e) {
            throw new Exception("Erro ao gerar resumo mensal: " . $e->getMessage());
        }
    }
}

// Exemplo de uso avançado
echo "\n\n=== EXEMPLO AVANÇADO: GERENCIADOR DE TRANSAÇÕES ===\n";

try {
    $transactionManager = new KlubeCashTransactionManager($apiKey);
    
    // Processar uma transação
    $result = $transactionManager->processTransaction(59, 250.00, 'cliente@email.com');
    
    if ($result['success']) {
        echo "Transação processada:\n";
        $tx = $result['transaction'];
        echo "  ID: {$tx['id']}\n";
        echo "  Loja: {$tx['store_name']}\n";
        echo "  Cliente: {$tx['customer_email']}\n";
        echo "  Valor: R$ " . number_format($tx['purchase_amount'], 2, ',', '.') . "\n";
        echo "  Cashback Cliente: R$ " . number_format($tx['cashback_data']['cashback_calculation']['client_receives'], 2, ',', '.') . "\n";
    } else {
        echo "Erro: " . $result['message'] . "\n";
    }
    
    // Obter resumo mensal
    echo "\nResumo mensal:\n";
    $summary = $transactionManager->getMonthlyCashbackSummary();
    echo "  Mês: {$summary['month']}\n";
    echo "  Total de lojas: {$summary['total_stores']}\n";
    echo "  Lojas ativas: {$summary['active_stores']}\n";
    echo "  Cashback estimado: R$ " . number_format($summary['estimated_cashback'], 2, ',', '.') . "\n";
    
} catch (Exception $e) {
    echo "ERRO AVANÇADO: " . $e->getMessage() . "\n";
}
?>