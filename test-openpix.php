<?php
/**
 * Teste completo da integração OpenPix
 * Execute este arquivo para verificar se tudo está funcionando
 */

require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'utils/OpenPixClient.php';

// Configurar relatório de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; }
.test-section { background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #007bff; }
.success { border-left-color: #28a745; background: #d4edda; }
.error { border-left-color: #dc3545; background: #f8d7da; }
.warning { border-left-color: #ffc107; background: #fff3cd; }
pre { background: #212529; color: #f8f9fa; padding: 15px; border-radius: 6px; overflow-x: auto; }
.btn { display: inline-block; padding: 10px 20px; margin: 5px; text-decoration: none; border-radius: 5px; }
.btn-primary { background: #007bff; color: white; }
.btn-success { background: #28a745; color: white; }
.btn-danger { background: #dc3545; color: white; }
</style>";

echo "<h1>🧪 Teste de Integração OpenPix - Klube Cash</h1>";
echo "<p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>";

$tests = [
    'Configurações' => 'testConfigurations',
    'Conectividade' => 'testConnectivity', 
    'Criação de Cobrança' => 'testCreateCharge',
    'Verificação de Status' => 'testChargeStatus',
    'Webhook' => 'testWebhook',
    'Banco de Dados' => 'testDatabase',
    'APIs' => 'testAPIs'
];

$results = [];
$totalTests = count($tests);
$passedTests = 0;

foreach ($tests as $testName => $testFunction) {
    echo "<div class='test-section'>";
    echo "<h3>🔍 {$testName}</h3>";
    
    try {
        $result = $testFunction();
        if ($result['status']) {
            $passedTests++;
            echo "<div class='success'>";
            echo "<strong>✅ PASSOU:</strong> " . $result['message'];
        } else {
            echo "<div class='error'>";
            echo "<strong>❌ FALHOU:</strong> " . $result['message'];
        }
        
        if (isset($result['data'])) {
            echo "<pre>" . json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        }
        
        echo "</div>";
        $results[$testName] = $result;
        
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<strong>❌ ERRO:</strong> " . $e->getMessage();
        echo "</div>";
        $results[$testName] = ['status' => false, 'message' => $e->getMessage()];
    }
    
    echo "</div>";
}

// Resumo final
echo "<div class='test-section " . ($passedTests === $totalTests ? 'success' : 'warning') . "'>";
echo "<h2>📊 Resumo dos Testes</h2>";
echo "<p><strong>Testes executados:</strong> {$totalTests}</p>";
echo "<p><strong>Testes aprovados:</strong> {$passedTests}</p>";
echo "<p><strong>Taxa de sucesso:</strong> " . round(($passedTests / $totalTests) * 100, 1) . "%</p>";

if ($passedTests === $totalTests) {
    echo "<p style='color: #28a745; font-weight: bold;'>🎉 Todos os testes passaram! A integração OpenPix está funcionando perfeitamente.</p>";
} else {
    echo "<p style='color: #dc3545; font-weight: bold;'>⚠️ Alguns testes falharam. Verifique as configurações antes de usar em produção.</p>";
}
echo "</div>";

// Ações rápidas
echo "<div class='test-section'>";
echo "<h3>🚀 Ações Rápidas</h3>";
echo "<a href='api/openpix.php?action=test' class='btn btn-primary'>Testar API</a>";
echo "<a href='api/openpix.php?action=test_connection' class='btn btn-success'>Testar Conexão</a>";
echo "<a href='api/openpix.php?action=account_info' class='btn btn-primary'>Info da Conta</a>";
echo "</div>";

/**
 * Teste de configurações
 */
function testConfigurations() {
    $configs = [
        'Q2xpZW50X0lkXzIzOTVjYmMzLWYyOGItNGJmYi04MWE3LWNkZWIzYzJkYTI4ZTpDbGllbnRfU2VjcmV0X3JYOFRxM016ZWdoNUY5YnVnempJeHl1VlBsRkg2QkNubm0yRFFzUWxQU1E9' => OPENPIX_API_KEY,
        'OPENPIX_BASE_URL' => OPENPIX_BASE_URL,
        'OPENPIX_WEBHOOK_URL' => OPENPIX_WEBHOOK_URL,
        'SITE_URL' => SITE_URL
    ];
    
    $missing = [];
    foreach ($configs as $key => $value) {
        if (empty($value)) {
            $missing[] = $key;
        }
    }
    
    if (!empty($missing)) {
        return [
            'status' => false,
            'message' => 'Configurações faltando: ' . implode(', ', $missing),
            'data' => $configs
        ];
    }
    
    return [
        'status' => true,
        'message' => 'Todas as configurações estão definidas',
        'data' => array_map(function($v) { 
            return strlen($v) > 20 ? substr($v, 0, 20) . '...' : $v; 
        }, $configs)
    ];
}

/**
 * Teste de conectividade
 */
function testConnectivity() {
    try {
        $openPix = new OpenPixClient();
        $result = $openPix->testConnection();
        return $result;
    } catch (Exception $e) {
        return [
            'status' => false,
            'message' => 'Erro na conectividade: ' . $e->getMessage()
        ];
    }
}

/**
 * Teste de criação de cobrança
 */
function testCreateCharge() {
    try {
        $openPix = new OpenPixClient();
        
        $testData = [
            'amount' => 1.00,
            'correlation_id' => 'test_' . time(),
            'comment' => 'Teste automatizado Klube Cash',
            'customer' => [
                'name' => 'Teste Automatizado',
                'email' => 'teste@klubecash.com'
            ]
        ];
        
        $result = $openPix->createCharge($testData);
        
        if ($result['status']) {
            return [
                'status' => true,
                'message' => 'Cobrança de teste criada com sucesso',
                'data' => [
                    'charge_id' => $result['charge_id'],
                    'value' => $result['value'],
                    'expires_at' => $result['expires_at'],
                    'qr_code_length' => strlen($result['qr_code'])
                ]
            ];
        } else {
            return [
                'status' => false,
                'message' => 'Erro ao criar cobrança: ' . $result['message']
            ];
        }
    } catch (Exception $e) {
        return [
            'status' => false,
            'message' => 'Erro no teste de cobrança: ' . $e->getMessage()
        ];
    }
}

/**
 * Teste de verificação de status
 */
function testChargeStatus() {
    try {
        // Primeiro criar uma cobrança
        $openPix = new OpenPixClient();
        
        $chargeResult = $openPix->createCharge([
            'amount' => 0.50,
            'correlation_id' => 'status_test_' . time(),
            'comment' => 'Teste de status'
        ]);
        
        if (!$chargeResult['status']) {
            return [
                'status' => false,
                'message' => 'Não foi possível criar cobrança para teste de status'
            ];
        }
        
        // Verificar status
        $statusResult = $openPix->getChargeStatus($chargeResult['charge_id']);
        
        if ($statusResult['status']) {
            return [
                'status' => true,
                'message' => 'Verificação de status funcionando',
                'data' => [
                    'charge_id' => $chargeResult['charge_id'],
                    'charge_status' => $statusResult['charge_status']
                ]
            ];
        } else {
            return [
                'status' => false,
                'message' => 'Erro na verificação de status: ' . $statusResult['message']
            ];
        }
    } catch (Exception $e) {
        return [
            'status' => false,
            'message' => 'Erro no teste de status: ' . $e->getMessage()
        ];
    }
}

/**
 * Teste de webhook
 */
function testWebhook() {
    $webhookUrl = OPENPIX_WEBHOOK_URL;
    
    if (empty($webhookUrl)) {
        return [
            'status' => false,
            'message' => 'URL do webhook não configurada'
        ];
    }
    
    // Verificar se a URL do webhook é acessível
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $webhookUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        return [
            'status' => true,
            'message' => 'Webhook acessível e respondendo',
            'data' => [
                'url' => $webhookUrl,
                'http_code' => $httpCode
            ]
        ];
    } else {
        return [
            'status' => false,
            'message' => "Webhook não acessível (HTTP {$httpCode})",
            'data' => [
                'url' => $webhookUrl,
                'http_code' => $httpCode
            ]
        ];
    }
}

/**
 * Teste de banco de dados
 */
function testDatabase() {
    try {
        $db = Database::getConnection();
        
        // Verificar se as tabelas necessárias existem
        $tables = [
            'pagamentos_comissao',
            'transacoes_cashback',
            'lojas',
            'usuarios'
        ];
        
        $existingTables = [];
        $missingTables = [];
        
        foreach ($tables as $table) {
            $stmt = $db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            
            if ($stmt->fetch()) {
                $existingTables[] = $table;
            } else {
                $missingTables[] = $table;
            }
        }
        
        // Verificar colunas específicas do OpenPix
        $pixColumns = [
            'pagamentos_comissao' => [
                'pix_charge_id',
                'pix_correlation_id', 
                'pix_transaction_id',
                'pix_qr_code',
                'pix_qr_code_image',
                'pix_payment_link',
                'pix_expires_at',
                'pix_paid_at'
            ]
        ];
        
        $missingColumns = [];
        foreach ($pixColumns as $table => $columns) {
            if (in_array($table, $existingTables)) {
                $stmt = $db->prepare("DESCRIBE {$table}");
                $stmt->execute();
                $existingColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
                
                foreach ($columns as $column) {
                    if (!in_array($column, $existingColumns)) {
                        $missingColumns[] = "{$table}.{$column}";
                    }
                }
            }
        }
        
        if (empty($missingTables) && empty($missingColumns)) {
            return [
                'status' => true,
                'message' => 'Banco de dados configurado corretamente',
                'data' => [
                    'existing_tables' => $existingTables,
                    'pix_columns_status' => 'OK'
                ]
            ];
        } else {
            return [
                'status' => false,
                'message' => 'Estrutura do banco incompleta',
                'data' => [
                    'missing_tables' => $missingTables,
                    'missing_columns' => $missingColumns
                ]
            ];
        }
        
    } catch (Exception $e) {
        return [
            'status' => false,
            'message' => 'Erro no banco de dados: ' . $e->getMessage()
        ];
    }
}

/**
 * Teste de APIs
 */
function testAPIs() {
    $apiEndpoints = [
        'OpenPix Principal' => SITE_URL . '/api/openpix.php?action=test',
        'Webhook OpenPix' => SITE_URL . '/api/openpix-webhook.php'
    ];
    
    $results = [];
    $allWorking = true;
    
    foreach ($apiEndpoints as $name => $url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $working = $httpCode === 200;
        $results[$name] = [
            'url' => $url,
            'http_code' => $httpCode,
            'working' => $working
        ];
        
        if (!$working) {
            $allWorking = false;
        }
    }
    
    return [
        'status' => $allWorking,
        'message' => $allWorking ? 'Todas as APIs estão funcionando' : 'Algumas APIs não estão respondendo',
        'data' => $results
    ];
}
?>