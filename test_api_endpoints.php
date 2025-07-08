<?php
/**
 * Script de Teste dos Endpoints da API Klube Cash
 * Testa todos os endpoints para verificar disponibilidade e estrutura de resposta
 */

class KlubeCashAPITester {
    private $baseUrl;
    private $authToken;
    private $testResults;
    private $startTime;
    
    public function __construct() {
        $this->baseUrl = 'https://klubecash.com/api';
        $this->authToken = null;
        $this->testResults = [];
        $this->startTime = microtime(true);
    }
    
    /**
     * Registra resultado de um teste
     */
    private function logTest($endpoint, $method, $statusCode, $responseData, $error = null, $success = true) {
        $result = [
            'timestamp' => date('c'),
            'endpoint' => $endpoint,
            'method' => $method,
            'status_code' => $statusCode,
            'success' => $success,
            'response_data' => $responseData,
            'error' => $error,
            'response_size' => strlen(json_encode($responseData))
        ];
        
        $this->testResults[] = $result;
        
        // Log imediato
        $status = $success ? "✅ SUCESSO" : "❌ ERRO";
        echo sprintf("%s | %s %s | %d | %s\n", 
            $status, $method, $endpoint, $statusCode, $error ?: 'OK');
    }
    
    /**
     * Testa um endpoint específico
     */
    private function testEndpoint($endpoint, $method = 'GET', $data = null, $useAuth = false) {
        $url = $this->baseUrl . '/' . $endpoint;
        
        // Configurar cURL
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'KlubeCashAPITester/1.0 PHP',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ]
        ]);
        
        // Adicionar autenticação se necessário
        if ($useAuth && $this->authToken) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(
                curl_getinfo($ch, CURLINFO_HEADER_OUT) ?: [
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                ['Authorization: Bearer ' . $this->authToken]
            ));
        }
        
        // Configurar método HTTP
        switch ($method) {
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
        }
        
        // Executar requisição
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Processar resposta
        if ($error) {
            $this->logTest($endpoint, $method, 0, [], $error, false);
            return [false, [], $error];
        }
        
        $responseData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $responseData = ['raw_response' => substr($response, 0, 500)];
        }
        
        $success = in_array($statusCode, [200, 201]);
        $errorMsg = $success ? null : "HTTP {$statusCode}";
        
        $this->logTest($endpoint, $method, $statusCode, $responseData, $errorMsg, $success);
        
        return [$success, $responseData, $errorMsg ?: 'OK'];
    }
    
    /**
     * Testa endpoint de login e obtém token
     */
    private function testLogin() {
        echo "\n🔐 TESTANDO AUTENTICAÇÃO...\n";
        
        $testCredentials = [
            ['email' => 'admin@klubecash.com', 'senha' => '123456'],
            ['email' => 'teste@klubecash.com', 'senha' => '123456'],
            ['email' => 'user@test.com', 'senha' => 'password'],
        ];
        
        foreach ($testCredentials as $cred) {
            echo "   Tentando login com: {$cred['email']}\n";
            list($success, $response, $error) = $this->testEndpoint('login.php', 'POST', $cred);
            
            if ($success && isset($response['status']) && $response['status'] && isset($response['token'])) {
                $this->authToken = $response['token'];
                echo "   ✅ Token obtido: " . substr($this->authToken, 0, 20) . "...\n";
                return true;
            } elseif ($success) {
                echo "   ⚠️  Login retornou: " . json_encode($response) . "\n";
            } else {
                echo "   ❌ Falha: {$error}\n";
            }
        }
        
        echo "   ⚠️  Nenhum login funcionou, continuando sem token...\n";
        return false;
    }
    
    /**
     * Testa endpoint de registro
     */
    private function testRegistration() {
        echo "\n📝 TESTANDO REGISTRO...\n";
        
        $testData = [
            'nome' => 'Usuário Teste API',
            'email' => 'teste_api_' . time() . '@klubecash.com',
            'telefone' => '(11) 99999-9999',
            'senha' => '123456789',
            'tipo' => 'cliente'
        ];
        
        $this->testEndpoint('register.php', 'POST', $testData);
    }
    
    /**
     * Testa endpoint de saldo do usuário
     */
    private function testUserBalance() {
        echo "\n💰 TESTANDO SALDO DO USUÁRIO...\n";
        $this->testEndpoint('user-balance.php', 'GET', null, true);
    }
    
    /**
     * Testa endpoint de transações
     */
    private function testTransactions() {
        echo "\n📊 TESTANDO TRANSAÇÕES...\n";
        
        // Teste sem parâmetros
        $this->testEndpoint('transactions.php', 'GET', null, true);
        
        // Teste com parâmetros
        $this->testEndpoint('transactions.php?limit=10&offset=0', 'GET', null, true);
    }
    
    /**
     * Testa endpoints de lojas
     */
    private function testStores() {
        echo "\n🏪 TESTANDO LOJAS...\n";
        
        // Teste lojas gerais
        $this->testEndpoint('stores.php', 'GET', null, true);
        
        // Teste com limite
        $this->testEndpoint('stores.php?limit=5', 'GET', null, true);
    }
    
    /**
     * Testa endpoint de saldos por loja
     */
    private function testStoreBalances() {
        echo "\n🏦 TESTANDO SALDOS POR LOJA...\n";
        $this->testEndpoint('store-balances.php', 'GET', null, true);
    }
    
    /**
     * Testa endpoints de perfil
     */
    private function testProfile() {
        echo "\n👤 TESTANDO PERFIL...\n";
        
        // GET perfil
        list($success, $profileData, $error) = $this->testEndpoint('profile.php', 'GET', null, true);
        
        // PUT perfil (atualização)
        if ($success && isset($profileData['data'])) {
            $updateData = $profileData['data'];
            $updateData['nome'] = 'Nome Atualizado ' . time();
            $this->testEndpoint('profile.php', 'PUT', $updateData, true);
        }
    }
    
    /**
     * Testa endpoints de recuperação de senha
     */
    private function testPasswordRecovery() {
        echo "\n🔑 TESTANDO RECUPERAÇÃO DE SENHA...\n";
        
        // Solicitar recuperação
        $recoveryData = ['email' => 'teste@klubecash.com'];
        $this->testEndpoint('recover-password.php', 'POST', $recoveryData);
        
        // Teste reset (com token fictício)
        $resetData = [
            'token' => 'token_ficticio_para_teste',
            'newPassword' => 'nova_senha_123'
        ];
        $this->testEndpoint('reset-password.php', 'POST', $resetData);
    }
    
    /**
     * Testa mudança de senha
     */
    private function testChangePassword() {
        echo "\n🔐 TESTANDO MUDANÇA DE SENHA...\n";
        
        $changeData = [
            'currentPassword' => '123456',
            'newPassword' => 'nova_senha_789'
        ];
        $this->testEndpoint('change-password.php', 'POST', $changeData, true);
    }
    
    /**
     * Testa endpoints adicionais que podem existir
     */
    private function testAdditionalEndpoints() {
        echo "\n🔍 TESTANDO ENDPOINTS ADICIONAIS...\n";
        
        $additionalEndpoints = [
            'users.php',
            'client.php',
            'commissions.php',
            'payments.php',
            'dashboard.php',
            'stores.php?action=popular',
            'users.php?action=login',
            'client.php?action=balance',
            'client.php?action=profile',
            'client.php?action=store_balances',
        ];
        
        foreach ($additionalEndpoints as $endpoint) {
            echo "   Testando: {$endpoint}\n";
            $this->testEndpoint($endpoint, 'GET', null, true);
        }
    }
    
    /**
     * Executa todos os testes
     */
    public function runAllTests() {
        echo "🚀 INICIANDO TESTE COMPLETO DA API KLUBE CASH\n";
        echo str_repeat("=", 60) . "\n";
        
        // 1. Teste de conectividade básica
        echo "\n🌐 TESTANDO CONECTIVIDADE BÁSICA...\n";
        $this->testEndpoint('', 'GET'); // Teste raiz
        
        // 2. Autenticação
        $this->testLogin();
        
        // 3. Registro
        $this->testRegistration();
        
        // 4. Endpoints autenticados
        if ($this->authToken) {
            $this->testUserBalance();
            $this->testTransactions();
            $this->testStores();
            $this->testStoreBalances();
            $this->testProfile();
            $this->testChangePassword();
        } else {
            echo "\n⚠️  Pulando testes autenticados (sem token)\n";
        }
        
        // 5. Recuperação de senha
        $this->testPasswordRecovery();
        
        // 6. Endpoints adicionais
        $this->testAdditionalEndpoints();
        
        // 7. Relatório final
        $this->generateReport();
    }
    
    /**
     * Gera relatório final dos testes
     */
    private function generateReport() {
        $duration = microtime(true) - $this->startTime;
        
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 RELATÓRIO FINAL DOS TESTES\n";
        echo str_repeat("=", 60) . "\n";
        
        $totalTests = count($this->testResults);
        $successfulTests = count(array_filter($this->testResults, function($r) { return $r['success']; }));
        $failedTests = $totalTests - $successfulTests;
        
        printf("⏱️  Duração total: %.2f segundos\n", $duration);
        printf("📈 Total de testes: %d\n", $totalTests);
        printf("✅ Sucessos: %d\n", $successfulTests);
        printf("❌ Falhas: %d\n", $failedTests);
        printf("📊 Taxa de sucesso: %.1f%%\n", $totalTests > 0 ? ($successfulTests/$totalTests)*100 : 0);
        
        printf("\n🔑 Token obtido: %s\n", $this->authToken ? 'Sim' : 'Não');
        
        echo "\n📋 RESUMO POR ENDPOINT:\n";
        echo str_repeat("-", 60) . "\n";
        
        $endpointsSummary = [];
        foreach ($this->testResults as $result) {
            $endpoint = $result['endpoint'];
            if (!isset($endpointsSummary[$endpoint])) {
                $endpointsSummary[$endpoint] = ['success' => 0, 'fail' => 0, 'responses' => []];
            }
            
            if ($result['success']) {
                $endpointsSummary[$endpoint]['success']++;
            } else {
                $endpointsSummary[$endpoint]['fail']++;
            }
            
            $endpointsSummary[$endpoint]['responses'][] = $result;
        }
        
        foreach ($endpointsSummary as $endpoint => $summary) {
            $status = $summary['fail'] == 0 ? "✅" : ($summary['success'] == 0 ? "❌" : "⚠️");
            printf("%s %s | ✅%d ❌%d\n", 
                $status, 
                $endpoint ?: 'ROOT', 
                $summary['success'], 
                $summary['fail']
            );
            
            // Mostrar estrutura de resposta de sucesso
            foreach ($summary['responses'] as $response) {
                if ($response['success'] && $response['response_data']) {
                    echo "     Estrutura: " . $this->analyzeResponseStructure($response['response_data']) . "\n";
                    break;
                }
            }
        }
        
        echo "\n🔧 RECOMENDAÇÕES PARA O FLUTTER:\n";
        echo str_repeat("-", 60) . "\n";
        
        if ($successfulTests > 0) {
            echo "✅ API está respondendo - Flutter pode prosseguir\n";
            
            if ($this->authToken) {
                echo "✅ Autenticação funcionando - usar Bearer token\n";
            } else {
                echo "⚠️  Verificar credenciais de login\n";
            }
            
            // Analisar endpoints funcionais
            $workingEndpoints = array_unique(array_map(function($r) { 
                return $r['endpoint']; 
            }, array_filter($this->testResults, function($r) { 
                return $r['success']; 
            })));
            
            if ($workingEndpoints) {
                echo "✅ Endpoints funcionais encontrados:\n";
                foreach ($workingEndpoints as $endpoint) {
                    if ($endpoint) {
                        echo "   - {$endpoint}\n";
                    }
                }
            }
        } else {
            echo "❌ API não está respondendo adequadamente\n";
            echo "💡 Verificar:\n";
            echo "   - Servidor https://klubecash.com está online?\n";
            echo "   - Pasta /api existe?\n";
            echo "   - Configurações de CORS?\n";
            echo "   - Estrutura dos endpoints?\n";
        }
        
        // Salvar relatório em arquivo
        $this->saveReportToFile();
    }
    
    /**
     * Analisa estrutura da resposta
     */
    private function analyzeResponseStructure($responseData) {
        if (!is_array($responseData)) {
            return gettype($responseData);
        }
        
        $keys = array_keys($responseData);
        if (count($keys) <= 3) {
            return 'Keys: [' . implode(', ', $keys) . ']';
        } else {
            return 'Keys: [' . implode(', ', array_slice($keys, 0, 3)) . '... +' . (count($keys)-3) . ' mais]';
        }
    }
    
    /**
     * Salva relatório detalhado em arquivo JSON
     */
    private function saveReportToFile() {
        $reportData = [
            'timestamp' => date('c'),
            'summary' => [
                'total_tests' => count($this->testResults),
                'successful_tests' => count(array_filter($this->testResults, function($r) { return $r['success']; })),
                'auth_token_obtained' => !empty($this->authToken),
                'base_url' => $this->baseUrl,
                'duration' => microtime(true) - $this->startTime
            ],
            'detailed_results' => $this->testResults
        ];
        
        $filename = 'klube_cash_api_test_report_' . time() . '.json';
        
        if (file_put_contents($filename, json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo "\n💾 Relatório detalhado salvo em: {$filename}\n";
        } else {
            echo "\n❌ Erro ao salvar relatório\n";
        }
    }
}

/**
 * Função principal
 */
function main() {
    echo "🧪 KLUBE CASH API ENDPOINT TESTER\n";
    echo "Testando todos os endpoints da API...\n\n";
    
    $tester = new KlubeCashAPITester();
    $tester->runAllTests();
    
    echo "\n🎉 Teste concluído!\n";
    echo "Verifique o relatório acima para ajustar o Flutter app.\n";
}

// Executar se chamado diretamente
if (php_sapi_name() === 'cli') {
    main();
} else {
    // Se executado via web, definir header e executar
    header('Content-Type: text/plain; charset=utf-8');
    main();
}
?>