<?php
/**
 * API Evolution Test - Klube Cash
 *
 * Este endpoint testa a conectividade com a Evolution API para diagnosticar
 * problemas de autenticação e configuração. Aceita apenas requisições GET
 * e retorna status detalhado da conexão em formato JSON.
 *
 * Funcionalidades:
 * - Teste de conectividade básica
 * - Validação de instância
 * - Verificação de autenticação
 * - Diagnóstico de configuração
 *
 * Versão: 2.0 - Atualizado com diagnóstico completo
 * Autor: Sistema Klube Cash
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
require_once __DIR__ . '/../config/constants.php';

class EvolutionTest {

    /**
     * Testa conectividade completa com Evolution API
     *
     * @return array Resultado detalhado do teste
     */
    public static function runFullTest() {
        $results = [
            'timestamp' => date('c'),
            'evolution_enabled' => false,
            'configuration' => [],
            'connection_test' => [],
            'instance_test' => [],
            'auth_test' => [],
            'overall_status' => 'failed'
        ];

        try {
            // Verificar se Evolution API está habilitada
            $results['evolution_enabled'] = defined('EVOLUTION_API_ENABLED') && EVOLUTION_API_ENABLED;

            if (!$results['evolution_enabled']) {
                $results['error'] = 'Evolution API está desabilitada na configuração';
                return $results;
            }

            // Verificar configurações
            $results['configuration'] = self::checkConfiguration();

            if (!$results['configuration']['valid']) {
                $results['error'] = 'Configuração inválida da Evolution API';
                return $results;
            }

            // Teste de conectividade básica
            $results['connection_test'] = self::testConnection();

            // Teste de instância (se conexão OK)
            if ($results['connection_test']['success']) {
                $results['instance_test'] = self::testInstance();

                // Teste de autenticação (se instância OK)
                if ($results['instance_test']['success']) {
                    $results['auth_test'] = self::testAuthentication();
                }
            }

            // Determinar status geral
            $results['overall_status'] = (
                $results['connection_test']['success'] &&
                $results['instance_test']['success'] &&
                $results['auth_test']['success']
            ) ? 'success' : 'failed';

            return $results;

        } catch (Exception $e) {
            $results['error'] = $e->getMessage();
            $results['overall_status'] = 'error';
            return $results;
        }
    }

    /**
     * Verifica configurações da Evolution API
     *
     * @return array Status da configuração
     */
    private static function checkConfiguration() {
        $config = [
            'valid' => true,
            'url' => defined('EVOLUTION_API_URL') ? EVOLUTION_API_URL : null,
            'key' => defined('EVOLUTION_API_KEY') ? (EVOLUTION_API_KEY ? 'definida' : 'vazia') : 'não definida',
            'instance' => defined('EVOLUTION_INSTANCE') ? EVOLUTION_INSTANCE : null,
            'timeout' => defined('EVOLUTION_TIMEOUT') ? EVOLUTION_TIMEOUT : 30,
            'issues' => []
        ];

        // Validar URL
        if (empty($config['url'])) {
            $config['valid'] = false;
            $config['issues'][] = 'EVOLUTION_API_URL não está definida';
        } elseif (!filter_var($config['url'], FILTER_VALIDATE_URL)) {
            $config['valid'] = false;
            $config['issues'][] = 'EVOLUTION_API_URL não é uma URL válida';
        }

        // Validar chave API
        if (empty(EVOLUTION_API_KEY)) {
            $config['valid'] = false;
            $config['issues'][] = 'EVOLUTION_API_KEY não está definida ou está vazia';
        }

        // Validar instância
        if (empty($config['instance'])) {
            $config['valid'] = false;
            $config['issues'][] = 'EVOLUTION_INSTANCE não está definida';
        }

        return $config;
    }

    /**
     * Testa conectividade básica com a API
     *
     * @return array Resultado do teste de conexão
     */
    private static function testConnection() {
        try {
            $url = EVOLUTION_API_URL . '/manager/status';

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HTTPHEADER => [
                    'apikey: ' . EVOLUTION_API_KEY,
                    'Content-Type: application/json',
                    'User-Agent: KlubeCash-Test/1.0'
                ]
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);

            return [
                'success' => !$error && $httpCode > 0,
                'http_code' => $httpCode,
                'response' => $response,
                'error' => $error,
                'connection_time' => $info['connect_time'] ?? 0,
                'total_time' => $info['total_time'] ?? 0
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'http_code' => 0
            ];
        }
    }

    /**
     * Testa se a instância existe e está ativa
     *
     * @return array Resultado do teste de instância
     */
    private static function testInstance() {
        try {
            $url = EVOLUTION_API_URL . '/instance/fetchInstances';

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => EVOLUTION_TIMEOUT,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTPHEADER => [
                    'apikey: ' . EVOLUTION_API_KEY,
                    'Content-Type: application/json'
                ]
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return [
                    'success' => false,
                    'error' => 'Erro cURL: ' . $error,
                    'http_code' => 0
                ];
            }

            $data = json_decode($response, true);
            $instanceFound = false;
            $instanceStatus = null;

            // Procurar pela instância específica
            if (is_array($data)) {
                foreach ($data as $instance) {
                    if (isset($instance['instance']['instanceName']) &&
                        $instance['instance']['instanceName'] === EVOLUTION_INSTANCE) {
                        $instanceFound = true;
                        $instanceStatus = $instance['instance']['status'] ?? 'unknown';
                        break;
                    }
                }
            }

            return [
                'success' => $instanceFound,
                'http_code' => $httpCode,
                'instance_found' => $instanceFound,
                'instance_status' => $instanceStatus,
                'instance_name' => EVOLUTION_INSTANCE,
                'total_instances' => is_array($data) ? count($data) : 0,
                'response_sample' => is_array($data) && !empty($data) ?
                    array_keys($data[0]) : 'resposta vazia ou inválida'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'http_code' => 0
            ];
        }
    }

    /**
     * Testa autenticação enviando uma mensagem de teste
     *
     * @return array Resultado do teste de autenticação
     */
    private static function testAuthentication() {
        try {
            // Usar número de teste padrão do WhatsApp
            $testNumber = '5511999999999'; // Número de teste

            $url = EVOLUTION_API_URL . '/message/sendText/' . EVOLUTION_INSTANCE;

            $payload = [
                'number' => $testNumber,
                'textMessage' => [
                    'text' => '🤖 Teste de conectividade KlubeCash - ' . date('H:i:s')
                ]
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => EVOLUTION_TIMEOUT,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTPHEADER => [
                    'apikey: ' . EVOLUTION_API_KEY,
                    'Content-Type: application/json'
                ]
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return [
                    'success' => false,
                    'error' => 'Erro cURL: ' . $error,
                    'http_code' => 0
                ];
            }

            $responseData = json_decode($response, true);
            $success = $httpCode >= 200 && $httpCode < 300;

            return [
                'success' => $success,
                'http_code' => $httpCode,
                'response' => $responseData,
                'test_number' => $testNumber,
                'payload_sent' => $payload,
                'auth_valid' => $success && $httpCode !== 401,
                'error_details' => !$success ? $response : null
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'http_code' => 0
            ];
        }
    }

    /**
     * Diagnóstica problemas específicos baseado nos resultados
     *
     * @param array $results Resultados dos testes
     * @return array Diagnóstico e sugestões
     */
    public static function diagnose($results) {
        $diagnosis = [
            'status' => $results['overall_status'],
            'issues' => [],
            'suggestions' => []
        ];

        // Diagnóstico baseado nos resultados
        if (!$results['evolution_enabled']) {
            $diagnosis['issues'][] = 'Evolution API está desabilitada';
            $diagnosis['suggestions'][] = 'Habilitar EVOLUTION_API_ENABLED=true em constants.php';
        }

        if (!$results['configuration']['valid']) {
            $diagnosis['issues'] = array_merge($diagnosis['issues'], $results['configuration']['issues']);
            $diagnosis['suggestions'][] = 'Verificar configurações em constants.php';
        }

        if (!$results['connection_test']['success']) {
            $diagnosis['issues'][] = 'Falha na conectividade básica';
            $diagnosis['suggestions'][] = 'Verificar se a URL da Evolution API está acessível';
            $diagnosis['suggestions'][] = 'Verificar configuração de SSL/firewall';
        }

        if (!$results['instance_test']['success']) {
            $diagnosis['issues'][] = 'Instância não encontrada ou inativa';
            $diagnosis['suggestions'][] = 'Verificar se a instância "' . EVOLUTION_INSTANCE . '" existe';
            $diagnosis['suggestions'][] = 'Verificar se a instância está conectada ao WhatsApp';
        }

        if (!$results['auth_test']['success']) {
            if ($results['auth_test']['http_code'] === 401) {
                $diagnosis['issues'][] = 'Erro de autenticação HTTP 401';
                $diagnosis['suggestions'][] = 'Verificar se a chave API está correta';
                $diagnosis['suggestions'][] = 'Verificar permissões da chave API';
            } else {
                $diagnosis['issues'][] = 'Falha no teste de envio de mensagem';
                $diagnosis['suggestions'][] = 'Verificar logs da Evolution API';
            }
        }

        return $diagnosis;
    }
}

// === ENDPOINT PRINCIPAL ===

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $action = $_GET['action'] ?? 'test';

        switch ($action) {
            case 'test':
            case 'full':
                $results = EvolutionTest::runFullTest();
                $diagnosis = EvolutionTest::diagnose($results);

                echo json_encode([
                    'test_type' => 'full_evolution_test',
                    'timestamp' => date('c'),
                    'results' => $results,
                    'diagnosis' => $diagnosis
                ]);
                break;

            case 'quick':
                $connectionTest = EvolutionTest::testConnection();
                echo json_encode([
                    'test_type' => 'quick_connection_test',
                    'timestamp' => date('c'),
                    'success' => $connectionTest['success'],
                    'details' => $connectionTest
                ]);
                break;

            case 'config':
                $config = EvolutionTest::checkConfiguration();
                echo json_encode([
                    'test_type' => 'configuration_check',
                    'timestamp' => date('c'),
                    'configuration' => $config
                ]);
                break;

            default:
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Ação não reconhecida',
                    'available_actions' => ['test', 'full', 'quick', 'config']
                ]);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'timestamp' => date('c')
        ]);
    }

} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método não permitido. Use GET.',
        'allowed_methods' => ['GET']
    ]);
}
?>