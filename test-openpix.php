<?php
/**
 * 🧪 Teste Completo de Integração OpenPix - Klube Cash
 * Versão: 2.0 - Corrigida
 * 
 * Este arquivo testa todos os aspectos da integração OpenPix
 */

// Headers para funcionamento correto
header('Content-Type: text/html; charset=UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir arquivos necessários
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';

// Classe para teste de integração
class OpenPixIntegrationTest {
    private $testResults = [];
    private $testsRun = 0;
    private $testsPassed = 0;
    
    public function __construct() {
        $this->displayHeader();
        $this->runAllTests();
        $this->displaySummary();
        $this->displayQuickActions();
    }
    
    private function displayHeader() {
        echo "<!DOCTYPE html>";
        echo "<html lang='pt-BR'>";
        echo "<head>";
        echo "<meta charset='UTF-8'>";
        echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
        echo "<title>Teste OpenPix - Klube Cash</title>";
        echo "<style>";
        echo "body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 20px; background: #f5f5f5; }";
        echo ".container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
        echo ".header { text-align: center; color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; margin-bottom: 20px; }";
        echo ".test-section { margin: 15px 0; padding: 15px; border-left: 4px solid #ddd; background: #f9f9f9; }";
        echo ".success { border-left-color: #4CAF50; background: #f1f8e9; }";
        echo ".error { border-left-color: #f44336; background: #ffebee; }";
        echo ".warning { border-left-color: #ff9800; background: #fff3e0; }";
        echo ".status-icon { font-size: 16px; margin-right: 8px; }";
        echo ".code-block { background: #263238; color: #fff; padding: 10px; border-radius: 5px; margin: 10px 0; overflow-x: auto; font-family: 'Courier New', monospace; font-size: 12px; }";
        echo ".actions { margin: 20px 0; text-align: center; }";
        echo ".btn { display: inline-block; padding: 10px 20px; margin: 5px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; }";
        echo ".btn:hover { background: #45a049; }";
        echo ".btn-warning { background: #ff9800; }";
        echo ".btn-danger { background: #f44336; }";
        echo ".summary { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0; }";
        echo "</style>";
        echo "</head>";
        echo "<body>";
        echo "<div class='container'>";
        echo "<div class='header'>";
        echo "<h1>🧪 Teste de Integração OpenPix - Klube Cash</h1>";
        echo "<p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>";
        echo "</div>";
    }
    
    private function runAllTests() {
        $this->testConfiguration();
        $this->testConnectivity();
        $this->testChargeCreation();
        $this->testChargeStatus();
        $this->testWebhook();
        $this->testDatabase();
        $this->testAPIs();
    }
    
    private function testConfiguration() {
        $this->testsRun++;
        echo "<div class='test-section'>";
        echo "<h3>🔍 Configurações</h3>";
        
        $requiredConstants = [
            'OPENPIX_API_KEY',
            'OPENPIX_BASE_URL', 
            'OPENPIX_WEBHOOK_URL',
            'SITE_URL'
        ];
        
        $missingConstants = [];
        $configData = [];
        
        foreach ($requiredConstants as $constant) {
            if (defined($constant)) {
                $value = constant($constant);
                if (!empty($value)) {
                    // Mascarar API key para exibição
                    if ($constant === 'OPENPIX_API_KEY') {
                        $configData[$constant] = substr($value, 0, 20) . '...';
                    } else {
                        $configData[$constant] = $value;
                    }
                } else {
                    $missingConstants[] = $constant;
                }
            } else {
                $missingConstants[] = $constant;
            }
        }
        
        if (empty($missingConstants)) {
            echo "<span class='status-icon'>✅</span><strong>PASSOU:</strong> Todas as configurações estão definidas<br>";
            echo "<div class='code-block'>" . json_encode($configData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</div>";
            $this->testsPassed++;
            $this->testResults['configuration'] = true;
        } else {
            echo "<span class='status-icon'>❌</span><strong>FALHOU:</strong> Configurações ausentes: " . implode(', ', $missingConstants);
            $this->testResults['configuration'] = false;
        }
        
        echo "</div>";
    }
    
    private function testConnectivity() {
        $this->testsRun++;
        echo "<div class='test-section'>";
        echo "<h3>🔍 Conectividade</h3>";
        
        try {
            if (!defined('OPENPIX_API_KEY') || !defined('OPENPIX_BASE_URL')) {
                throw new Exception('Configurações OpenPix não definidas');
            }
            
            $response = $this->makeOpenPixRequest('GET', '/account');
            
            if ($response['success']) {
                echo "<span class='status-icon'>✅</span><strong>PASSOU:</strong> Conexão com OpenPix estabelecida com sucesso!";
                $this->testsPassed++;
                $this->testResults['connectivity'] = true;
            } else {
                echo "<span class='status-icon'>❌</span><strong>FALHOU:</strong> " . $response['message'];
                $this->testResults['connectivity'] = false;
            }
        } catch (Exception $e) {
            echo "<span class='status-icon'>❌</span><strong>FALHOU:</strong> " . $e->getMessage();
            $this->testResults['connectivity'] = false;
        }
        
        echo "</div>";
    }
    
    private function testChargeCreation() {
        $this->testsRun++;
        echo "<div class='test-section'>";
        echo "<h3>🔍 Criação de Cobrança</h3>";
        
        try {
            $chargeData = [
                'value' => 100, // R$ 1,00
                'comment' => "Teste Klube Cash - " . date('d/m/Y H:i:s'),
                'correlationID' => "test_" . time()
            ];
            
            $response = $this->makeOpenPixRequest('POST', '/charge', $chargeData);
            
            if ($response['success']) {
                $charge = $response['data']['charge'];
                echo "<span class='status-icon'>✅</span><strong>PASSOU:</strong> Cobrança de teste criada com sucesso<br>";
                
                $chargeInfo = [
                    'charge_id' => $charge['correlationID'],
                    'value' => $charge['value'],
                    'expires_at' => $charge['expiresDate'],
                    'qr_code_length' => strlen($charge['brCode'])
                ];
                
                echo "<div class='code-block'>" . json_encode($chargeInfo, JSON_PRETTY_PRINT) . "</div>";
                
                // Salvar charge_id para próximo teste
                $this->testChargeId = $charge['correlationID'];
                
                $this->testsPassed++;
                $this->testResults['charge_creation'] = true;
            } else {
                echo "<span class='status-icon'>❌</span><strong>FALHOU:</strong> " . $response['message'];
                $this->testResults['charge_creation'] = false;
            }
        } catch (Exception $e) {
            echo "<span class='status-icon'>❌</span><strong>FALHOU:</strong> " . $e->getMessage();
            $this->testResults['charge_creation'] = false;
        }
        
        echo "</div>";
    }
    
    private function testChargeStatus() {
        $this->testsRun++;
        echo "<div class='test-section'>";
        echo "<h3>🔍 Verificação de Status</h3>";
        
        try {
            // Tentar usar o charge_id do teste anterior ou criar um novo
            if (!isset($this->testChargeId)) {
                // Criar uma cobrança específica para teste de status
                $chargeData = [
                    'value' => 100,
                    'comment' => "Teste Status - " . date('d/m/Y H:i:s'),
                    'correlationID' => "status_test_" . time()
                ];
                
                $createResponse = $this->makeOpenPixRequest('POST', '/charge', $chargeData);
                
                if (!$createResponse['success']) {
                    throw new Exception('Não foi possível criar cobrança para teste de status: ' . $createResponse['message']);
                }
                
                $this->testChargeId = $createResponse['data']['charge']['correlationID'];
            }
            
            // Verificar status da cobrança
            $response = $this->makeOpenPixRequest('GET', '/charge/' . $this->testChargeId);
            
            if ($response['success']) {
                echo "<span class='status-icon'>✅</span><strong>PASSOU:</strong> Status verificado com sucesso<br>";
                
                $statusInfo = [
                    'charge_id' => $this->testChargeId,
                    'status' => $response['data']['charge']['status'] ?? 'UNKNOWN',
                    'value' => $response['data']['charge']['value'] ?? 0
                ];
                
                echo "<div class='code-block'>" . json_encode($statusInfo, JSON_PRETTY_PRINT) . "</div>";
                
                $this->testsPassed++;
                $this->testResults['charge_status'] = true;
            } else {
                echo "<span class='status-icon'>❌</span><strong>FALHOU:</strong> " . $response['message'];
                $this->testResults['charge_status'] = false;
            }
        } catch (Exception $e) {
            echo "<span class='status-icon'>❌</span><strong>FALHOU:</strong> " . $e->getMessage();
            $this->testResults['charge_status'] = false;
        }
        
        echo "</div>";
    }
    
    private function testWebhook() {
        $this->testsRun++;
        echo "<div class='test-section'>";
        echo "<h3>🔍 Webhook</h3>";
        
        try {
            if (!defined('OPENPIX_WEBHOOK_URL')) {
                throw new Exception('URL do webhook não configurada');
            }
            
            $webhookUrl = OPENPIX_WEBHOOK_URL;
            
            // Testar se o webhook responde
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $webhookUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'Klube-Cash-Test/1.0'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($httpCode === 200) {
                echo "<span class='status-icon'>✅</span><strong>PASSOU:</strong> Webhook acessível e respondendo<br>";
                
                $webhookInfo = [
                    'url' => $webhookUrl,
                    'http_code' => $httpCode
                ];
                
                echo "<div class='code-block'>" . json_encode($webhookInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</div>";
                
                $this->testsPassed++;
                $this->testResults['webhook'] = true;
            } else {
                echo "<span class='status-icon'>❌</span><strong>FALHOU:</strong> Webhook retornou código $httpCode";
                if ($error) {
                    echo " - Erro: $error";
                }
                $this->testResults['webhook'] = false;
            }
        } catch (Exception $e) {
            echo "<span class='status-icon'>❌</span><strong>FALHOU:</strong> " . $e->getMessage();
            $this->testResults['webhook'] = false;
        }
        
        echo "</div>";
    }
    
    private function testDatabase() {
        $this->testsRun++;
        echo "<div class='test-section'>";
        echo "<h3>🔍 Banco de Dados</h3>";
        
        try {
            $db = Database::getConnection();
            
            // Testar consulta básica com sintaxe corrigida
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM pagamentos_comissao WHERE status = ?");
            $stmt->execute(['pendente']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<span class='status-icon'>✅</span><strong>PASSOU:</strong> Banco de dados funcionando corretamente<br>";
            echo "<div class='code-block'>Registros pendentes encontrados: " . $result['total'] . "</div>";
            
            $this->testsPassed++;
            $this->testResults['database'] = true;
            
        } catch (Exception $e) {
            echo "<span class='status-icon'>❌</span><strong>FALHOU:</strong> Erro no banco de dados: " . $e->getMessage();
            $this->testResults['database'] = false;
        }
        
        echo "</div>";
    }
    
    private function testAPIs() {
        $this->testsRun++;
        echo "<div class='test-section'>";
        echo "<h3>🔍 APIs</h3>";
        
        $endpoints = [
            'OpenPix Principal' => '/api/openpix.php?action=test',
            'Webhook OpenPix' => '/api/openpix-webhook.php'
        ];
        
        $allWorking = true;
        $apiResults = [];
        
        foreach ($endpoints as $name => $endpoint) {
            $url = SITE_URL . $endpoint;
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $working = ($httpCode === 200);
            $allWorking = $allWorking && $working;
            
            $apiResults[$name] = [
                'url' => $url,
                'http_code' => $httpCode,
                'working' => $working
            ];
        }
        
        if ($allWorking) {
            echo "<span class='status-icon'>✅</span><strong>PASSOU:</strong> Todas as APIs estão funcionando<br>";
            $this->testsPassed++;
            $this->testResults['apis'] = true;
        } else {
            echo "<span class='status-icon'>❌</span><strong>FALHOU:</strong> Algumas APIs não estão respondendo";
            $this->testResults['apis'] = false;
        }
        
        echo "<div class='code-block'>" . json_encode($apiResults, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</div>";
        
        echo "</div>";
    }
    
    private function makeOpenPixRequest($method, $endpoint, $data = null) {
        if (!defined('OPENPIX_API_KEY') || !defined('OPENPIX_BASE_URL')) {
            return ['success' => false, 'message' => 'Configurações OpenPix não definidas'];
        }
        
        $url = OPENPIX_BASE_URL . $endpoint;
        
        $headers = [
            'Authorization: ' . OPENPIX_API_KEY,
            'Content-Type: application/json',
            'User-Agent: Klube-Cash/1.0'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['success' => false, 'message' => "Erro cURL: {$error}"];
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'data' => json_decode($response, true)];
        } else {
            return ['success' => false, 'message' => "Erro HTTP {$httpCode}", 'response' => $response];
        }
    }
    
    private function displaySummary() {
        $successRate = ($this->testsRun > 0) ? round(($this->testsPassed / $this->testsRun) * 100, 1) : 0;
        
        echo "<div class='summary'>";
        echo "<h3>📊 Resumo dos Testes</h3>";
        echo "<p><strong>Testes executados:</strong> {$this->testsRun}</p>";
        echo "<p><strong>Testes aprovados:</strong> {$this->testsPassed}</p>";
        echo "<p><strong>Taxa de sucesso:</strong> {$successRate}%</p>";
        
        if ($this->testsPassed === $this->testsRun) {
            echo "<p style='color: #4CAF50; font-weight: bold;'>🎉 Todos os testes passaram! Sistema pronto para produção.</p>";
        } else {
            echo "<p style='color: #f44336; font-weight: bold;'>⚠️ Alguns testes falharam. Verifique as configurações antes de usar em produção.</p>";
        }
        
        echo "</div>";
    }
    
    private function displayQuickActions() {
        echo "<div class='actions'>";
        echo "<h3>🚀 Ações Rápidas</h3>";
        echo "<a href='/api/openpix.php?action=test' class='btn' target='_blank'>Testar API</a>";
        echo "<a href='/api/openpix.php?action=test_connection' class='btn' target='_blank'>Testar Conexão</a>";
        echo "<a href='/api/openpix.php?action=account_info' class='btn btn-warning' target='_blank'>Info da Conta</a>";
        echo "</div>";
        
        echo "</div>"; // Fechar container
        echo "</body></html>";
    }
}

// Executar testes
new OpenPixIntegrationTest();
?>