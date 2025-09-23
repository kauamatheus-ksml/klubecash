<?php
/**
 * Utilitário para Disparar Notificações de Cashback
 * 
 * Esta classe fornece uma interface simples para integrar o sistema de
 * notificações com o código existente. Pode ser chamada de qualquer lugar
 * onde transações são criadas, sem afetar o funcionamento normal.
 * 
 * Uso: NotificationTrigger::send($transactionId);
 * 
 * Localização: utils/NotificationTrigger.php
 * Autor: Sistema Klube Cash
 * Versão: 2.0 - Atualizado para N8N + Evolution API
 */

require_once __DIR__ . '/../config/constants.php';

class NotificationTrigger {
    
    /**
     * Dispara notificação para uma transação específica
     * 
     * Este método é thread-safe e não bloqueia o processo principal.
     * Se a notificação falhar, a transação continua funcionando normalmente.
     * 
     * ATUALIZAÇÃO V2.0: Agora usa N8N como prioridade e Evolution API como backup
     * 
     * @param int $transactionId ID da transação recém-criada
     * @param array $options Opções adicionais (debug, async, etc.)
     * @return array Resultado da operação
     */
    public static function send($transactionId, $options = []) {
        try {
            // Validar entrada
            if (!is_numeric($transactionId) || $transactionId <= 0) {
                throw new Exception('ID da transação inválido');
            }
            
            // ALTERAÇÃO: Usar WHATSAPP_ENABLED em vez de CASHBACK_NOTIFICATIONS_ENABLED
            if (!defined('WHATSAPP_ENABLED') || !WHATSAPP_ENABLED) {
                return [
                    'success' => false,
                    'message' => 'Notificações WhatsApp desabilitadas via configuração',
                    'skipped' => true
                ];
            }
            
            // Definir se deve ser assíncrono (padrão: sim)
            $async = $options['async'] ?? true;
            $debug = $options['debug'] ?? false;
            
            if ($async) {
                // Envio assíncrono (não bloqueia o processo principal)
                return self::sendAsync($transactionId, $debug);
            } else {
                // Envio síncrono (para debug ou casos especiais)
                return self::sendSync($transactionId, $debug);
            }
            
        } catch (Exception $e) {
            error_log('Erro no NotificationTrigger: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error' => true
            ];
        }
    }
    
    /**
     * Envio assíncrono (recomendado para produção)
     * 
     * ALTERAÇÃO MAJOR: Agora usa N8N Webhook como prioridade, Evolution API como backup
     * 
     * @param int $transactionId ID da transação
     * @param bool $debug Se deve incluir detalhes de debug
     * @return array Resultado da operação
     */
    private static function sendAsync($transactionId, $debug = false) {
        $integrationResults = [];
        
        try {
            // === PRIORIDADE 1: N8N WEBHOOK ===
            $n8nResult = false;
            if (defined('N8N_ENABLED') && N8N_ENABLED) {
                try {
                    require_once __DIR__ . '/../api/n8n-webhook.php';
                    $n8nResult = N8nWebhook::sendTransactionData($transactionId, 'nova_transacao');
                    $integrationResults['n8n'] = $n8nResult;
                    
                    if ($debug) {
                        error_log("NotificationTrigger: N8N result: " . ($n8nResult ? 'success' : 'failed'));
                    }
                } catch (Exception $e) {
                    $integrationResults['n8n'] = false;
                    $integrationResults['n8n_error'] = $e->getMessage();
                    if ($debug) {
                        error_log("NotificationTrigger: N8N error: " . $e->getMessage());
                    }
                }
            }
            
            // === BACKUP: EVOLUTION API DIRETA ===
            $evolutionResult = false;
            if (!$n8nResult && defined('EVOLUTION_API_ENABLED') && EVOLUTION_API_ENABLED) {
                try {
                    $evolutionResult = self::sendDirectEvolution($transactionId, $debug);
                    $integrationResults['evolution'] = $evolutionResult['success'] ?? false;
                    
                    if ($debug) {
                        error_log("NotificationTrigger: Evolution direct result: " . ($evolutionResult['success'] ? 'success' : 'failed'));
                    }
                } catch (Exception $e) {
                    $integrationResults['evolution'] = false;
                    $integrationResults['evolution_error'] = $e->getMessage();
                    if ($debug) {
                        error_log("NotificationTrigger: Evolution direct error: " . $e->getMessage());
                    }
                }
            }
            
            // Determinar sucesso geral
            $overallSuccess = $n8nResult || ($evolutionResult && $evolutionResult['success']);
            
            return [
                'success' => $overallSuccess,
                'message' => $overallSuccess ? 'Notificação enviada com sucesso' : 'Falha em todos os métodos',
                'transaction_id' => $transactionId,
                'async' => true,
                'methods_tried' => $integrationResults,
                'primary_method' => $n8nResult ? 'n8n' : ($evolutionResult['success'] ?? false ? 'evolution' : 'none')
            ];
            
        } catch (Exception $e) {
            error_log("NotificationTrigger async falhou para transação {$transactionId}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'transaction_id' => $transactionId,
                'async' => true,
                'error_logged' => true,
                'methods_tried' => $integrationResults
            ];
        }
    }
    
    /**
     * Envio síncrono (para debug ou casos especiais)
     * 
     * ALTERAÇÃO: Usa a nova arquitetura em vez da classe CashbackNotifier inexistente
     * 
     * @param int $transactionId ID da transação
     * @param bool $debug Se deve incluir detalhes de debug
     * @return array Resultado da operação
     */
    private static function sendSync($transactionId, $debug = false) {
        $integrationResults = [];
        
        try {
            // === PRIORIDADE 1: N8N WEBHOOK ===
            $n8nResult = false;
            if (defined('N8N_ENABLED') && N8N_ENABLED) {
                try {
                    require_once __DIR__ . '/../api/n8n-webhook.php';
                    $n8nResult = N8nWebhook::sendTransactionData($transactionId, 'nova_transacao');
                    $integrationResults['n8n'] = $n8nResult;
                } catch (Exception $e) {
                    $integrationResults['n8n'] = false;
                    $integrationResults['n8n_error'] = $e->getMessage();
                }
            }
            
            // === BACKUP: EVOLUTION API DIRETA ===
            $evolutionResult = false;
            if (!$n8nResult) {
                try {
                    $evolutionResult = self::sendDirectEvolution($transactionId, $debug);
                    $integrationResults['evolution'] = $evolutionResult['success'] ?? false;
                } catch (Exception $e) {
                    $integrationResults['evolution'] = false;
                    $integrationResults['evolution_error'] = $e->getMessage();
                }
            }
            
            $overallSuccess = $n8nResult || ($evolutionResult && $evolutionResult['success']);
            
            if ($debug) {
                error_log("NotificationTrigger sync: " . json_encode($integrationResults));
            }
            
            return [
                'success' => $overallSuccess,
                'message' => $overallSuccess ? 'Notificação enviada com sucesso' : 'Falha em todos os métodos',
                'transaction_id' => $transactionId,
                'async' => false,
                'methods_tried' => $integrationResults,
                'primary_method' => $n8nResult ? 'n8n' : ($evolutionResult['success'] ?? false ? 'evolution' : 'none')
            ];
            
        } catch (Exception $e) {
            error_log("NotificationTrigger sync falhou para transação {$transactionId}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'transaction_id' => $transactionId,
                'async' => false,
                'methods_tried' => $integrationResults
            ];
        }
    }
    
    /**
     * NOVO MÉTODO: Envio direto via Evolution API
     * 
     * Método helper para enviar notificação diretamente via Evolution API
     * quando N8N não está disponível ou falha
     * 
     * @param int $transactionId ID da transação
     * @param bool $debug Se deve incluir logs de debug
     * @return array Resultado da operação
     */
    private static function sendDirectEvolution($transactionId, $debug = false) {
        try {
            require_once __DIR__ . '/EvolutionWhatsApp.php';
            require_once __DIR__ . '/../config/database.php';
            
            $db = Database::getConnection();
            
            // Buscar dados da transação
            $stmt = $db->prepare("
                SELECT 
                    t.valor_cashback,
                    t.valor_saldo_usado,
                    u.nome,
                    u.telefone,
                    l.nome_fantasia as loja_nome
                FROM transacoes_cashback t
                JOIN usuarios u ON t.usuario_id = u.id
                JOIN lojas l ON t.loja_id = l.id
                WHERE t.id = ?
            ");
            $stmt->execute([$transactionId]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$data || empty($data['telefone'])) {
                return [
                    'success' => false, 
                    'error' => 'Dados da transação ou telefone não encontrados'
                ];
            }
            
            $notificationData = [
                'transaction_id' => $transactionId,
                'valor_cashback' => $data['valor_cashback'],
                'valor_usado' => $data['valor_saldo_usado'],
                'nome_loja' => $data['loja_nome'],
                'nome_cliente' => $data['nome']
            ];
            
            return EvolutionWhatsApp::sendNewTransactionNotification(
                $data['telefone'], 
                $notificationData
            );
            
        } catch (Exception $e) {
            return [
                'success' => false, 
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Método para testar o sistema de notificações
     * 
     * ATUALIZAÇÃO: Agora testa tanto N8N quanto Evolution API
     * 
     * @param int $testTransactionId ID de uma transação existente para teste
     * @return array Resultado do teste
     */
    public static function test($testTransactionId = null) {
        try {
            if (!$testTransactionId) {
                // Buscar uma transação recente para teste
                require_once __DIR__ . '/../config/database.php';
                $db = Database::getConnection();
                
                $stmt = $db->prepare("
                    SELECT id FROM transacoes_cashback 
                    ORDER BY data_criacao DESC 
                    LIMIT 1
                ");
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$result) {
                    return [
                        'success' => false,
                        'message' => 'Nenhuma transação encontrada para teste',
                        'test' => true
                    ];
                }
                
                $testTransactionId = $result['id'];
            }
            
            // Testar conectividade dos sistemas
            $connectivityTests = [
                'n8n' => false,
                'evolution' => false
            ];
            
            // Teste N8N
            if (defined('N8N_ENABLED') && N8N_ENABLED) {
                try {
                    require_once __DIR__ . '/../api/n8n-webhook.php';
                    $connectivityTests['n8n'] = N8nWebhook::testConnection();
                } catch (Exception $e) {
                    $connectivityTests['n8n_error'] = $e->getMessage();
                }
            }
            
            // Teste Evolution API
            if (defined('EVOLUTION_API_ENABLED') && EVOLUTION_API_ENABLED) {
                try {
                    require_once __DIR__ . '/EvolutionWhatsApp.php';
                    $evolutionTest = EvolutionWhatsApp::testConnection();
                    $connectivityTests['evolution'] = $evolutionTest['success'];
                    if (!$evolutionTest['success']) {
                        $connectivityTests['evolution_error'] = $evolutionTest['error'];
                    }
                } catch (Exception $e) {
                    $connectivityTests['evolution_error'] = $e->getMessage();
                }
            }
            
            // Enviar notificação de teste de forma síncrona
            $result = self::send($testTransactionId, [
                'async' => false,
                'debug' => true
            ]);
            
            $result['test'] = true;
            $result['test_transaction_id'] = $testTransactionId;
            $result['connectivity_tests'] = $connectivityTests;
            
            return $result;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro no teste: ' . $e->getMessage(),
                'test' => true
            ];
        }
    }
    
    /**
     * Método para reenviar notificações falhadas
     * 
     * MANTIDO: Mesma funcionalidade, mas usando nova arquitetura
     * 
     * @param array $transactionIds Array de IDs de transações
     * @param int $maxRetries Máximo de tentativas
     * @return array Resultado consolidado
     */
    public static function retry($transactionIds, $maxRetries = 3) {
        $results = [];
        $successCount = 0;
        $failCount = 0;
        
        foreach ($transactionIds as $transactionId) {
            $attempts = 0;
            $success = false;
            $lastResult = null;
            
            while ($attempts < $maxRetries && !$success) {
                $attempts++;
                $result = self::send($transactionId, ['async' => false]);
                $lastResult = $result;
                
                if ($result['success']) {
                    $success = true;
                    $successCount++;
                } else {
                    sleep(2); // Aguardar 2 segundos entre tentativas
                }
            }
            
            if (!$success) {
                $failCount++;
            }
            
            $results[$transactionId] = [
                'success' => $success,
                'attempts' => $attempts,
                'last_result' => $lastResult
            ];
        }
        
        return [
            'success' => $successCount > 0,
            'total_processed' => count($transactionIds),
            'success_count' => $successCount,
            'fail_count' => $failCount,
            'results' => $results
        ];
    }
    
    /**
     * NOVO MÉTODO: Obter estatísticas de notificações
     * 
     * Método para obter estatísticas das notificações enviadas
     * 
     * @param string $period Período para as estatísticas (24h, 7d, 30d)
     * @return array Estatísticas das notificações
     */
    public static function getStats($period = '24h') {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getConnection();
            
            // Definir filtro de período
            $periodFilter = '';
            switch ($period) {
                case '1h':
                    $periodFilter = 'created_at >= NOW() - INTERVAL 1 HOUR';
                    break;
                case '24h':
                    $periodFilter = 'created_at >= NOW() - INTERVAL 24 HOUR';
                    break;
                case '7d':
                    $periodFilter = 'created_at >= NOW() - INTERVAL 7 DAY';
                    break;
                case '30d':
                    $periodFilter = 'created_at >= NOW() - INTERVAL 30 DAY';
                    break;
                default:
                    $periodFilter = 'created_at >= NOW() - INTERVAL 24 HOUR';
            }
            
            $stats = [];
            
            // Estatísticas N8N
            try {
                $n8nStmt = $db->prepare("
                    SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as success_count
                    FROM n8n_webhook_logs 
                    WHERE {$periodFilter}
                ");
                $n8nStmt->execute();
                $stats['n8n'] = $n8nStmt->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $stats['n8n'] = ['total' => 0, 'success_count' => 0, 'error' => $e->getMessage()];
            }
            
            // Estatísticas Evolution
            try {
                $evolutionStmt = $db->prepare("
                    SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as success_count
                    FROM whatsapp_evolution_logs 
                    WHERE {$periodFilter}
                ");
                $evolutionStmt->execute();
                $stats['evolution'] = $evolutionStmt->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $stats['evolution'] = ['total' => 0, 'success_count' => 0, 'error' => $e->getMessage()];
            }
            
            return [
                'success' => true,
                'period' => $period,
                'stats' => $stats
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao obter estatísticas: ' . $e->getMessage()
            ];
        }
    }
}
?>