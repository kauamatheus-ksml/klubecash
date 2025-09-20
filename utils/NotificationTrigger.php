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
 * Versão: 1.0
 */

require_once __DIR__ . '/../config/constants.php';

class NotificationTrigger {
    
    /**
     * Dispara notificação para uma transação específica
     *
     * Este método é thread-safe e não bloqueia o processo principal.
     * Se a notificação falhar, a transação continua funcionando normalmente.
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

            // Verificar se notificações estão habilitadas
            if (!defined('CASHBACK_NOTIFICATIONS_ENABLED') || !CASHBACK_NOTIFICATIONS_ENABLED) {
                return [
                    'success' => false,
                    'message' => 'Notificações desabilitadas via configuração',
                    'skipped' => true
                ];
            }

            // Definir se deve ser assíncrono (padrão: sim)
            $async = $options['async'] ?? true;
            $debug = $options['debug'] ?? false;
            $useRetrySystem = $options['use_retry'] ?? true;

            $result = null;

            if ($async) {
                // Envio assíncrono (não bloqueia o processo principal)
                $result = self::sendAsync($transactionId, $debug);
            } else {
                // Envio síncrono (para debug ou casos especiais)
                $result = self::sendSync($transactionId, $debug);
            }

            // Integrar com sistema de retry se habilitado
            if ($useRetrySystem && !$result['success'] && !isset($result['skipped'])) {
                try {
                    require_once __DIR__ . '/CashbackRetrySystem.php';
                    $retrySystem = new CashbackRetrySystem();
                    $retrySystem->registerFailure($transactionId, $result['message'], 1);

                    $result['retry_scheduled'] = true;
                } catch (Exception $e) {
                    error_log('Erro ao registrar retry: ' . $e->getMessage());
                    $result['retry_error'] = $e->getMessage();
                }
            } else if ($useRetrySystem && $result['success']) {
                // Marcar como sucesso no sistema de retry se existir um registro pendente
                try {
                    require_once __DIR__ . '/CashbackRetrySystem.php';
                    $retrySystem = new CashbackRetrySystem();
                    $retrySystem->markAsSuccess($transactionId);
                } catch (Exception $e) {
                    // Não é crítico, apenas logar
                    error_log('Erro ao marcar sucesso no retry: ' . $e->getMessage());
                }
            }

            return $result;

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
     * Usa cURL com timeout baixo para não afetar o tempo de resposta
     * da operação principal. Se falhar, apenas loga o erro.
     * 
     * @param int $transactionId ID da transação
     * @param bool $debug Se deve incluir detalhes de debug
     * @return array Resultado da operação
     */
    private static function sendAsync($transactionId, $debug = false) {
        try {
            // Preparar dados para a API
            $postData = [
                'secret' => WHATSAPP_BOT_SECRET,
                'transaction_id' => $transactionId
            ];
            
            // URL da API de notificação
            $apiUrl = CASHBACK_NOTIFICATION_API_URL;
            
            // Configurar cURL para requisição rápida
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5, // Timeout baixo para não afetar performance
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($postData),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'User-Agent: KlubeCash-Trigger/1.0'
                ],
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_FOLLOWLOCATION => false
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);
            
            // Verificar resultado
            if ($curlError) {
                throw new Exception("Erro cURL: " . $curlError);
            }
            
            if ($httpCode !== 200) {
                throw new Exception("HTTP Error: " . $httpCode);
            }
            
            $responseData = json_decode($response, true);
            
            // Log de sucesso se habilitado debug
            if ($debug) {
                error_log("NotificationTrigger: Sucesso para transação {$transactionId}");
            }
            
            return [
                'success' => true,
                'message' => 'Notificação enviada com sucesso',
                'transaction_id' => $transactionId,
                'api_response' => $responseData,
                'async' => true
            ];
            
        } catch (Exception $e) {
            // Em caso de erro, apenas logar - não afetar processo principal
            error_log("NotificationTrigger async falhou para transação {$transactionId}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'transaction_id' => $transactionId,
                'async' => true,
                'error_logged' => true
            ];
        }
    }
    
    /**
     * Envio síncrono (para debug ou casos especiais)
     * 
     * Usa a classe CashbackNotifier diretamente, aguardando o resultado.
     * Útil para debug ou quando se quer garantir que a notificação foi enviada.
     * 
     * @param int $transactionId ID da transação
     * @param bool $debug Se deve incluir detalhes de debug
     * @return array Resultado da operação
     */
    private static function sendSync($transactionId, $debug = false) {
        try {
            require_once __DIR__ . '/../classes/CashbackNotifier.php';
            
            $notifier = new CashbackNotifier();
            $result = $notifier->notifyNewTransaction($transactionId);
            
            if ($debug) {
                error_log("NotificationTrigger sync: " . json_encode($result));
            }
            
            $result['async'] = false;
            return $result;
            
        } catch (Exception $e) {
            error_log("NotificationTrigger sync falhou para transação {$transactionId}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'transaction_id' => $transactionId,
                'async' => false
            ];
        }
    }
    
    /**
     * Método para testar o sistema de notificações
     * 
     * Útil para verificar se tudo está funcionando corretamente
     * sem depender de uma transação real.
     * 
     * @param int $testTransactionId ID de uma transação existente para teste
     * @return array Resultado do teste
     */
    public static function test($testTransactionId = null) {
        try {
            if (!$testTransactionId) {
                // Se não fornecido, buscar uma transação recente para teste
                require_once __DIR__ . '/../config/database.php';
                $db = Database::getConnection();
                
                $stmt = $db->prepare("
                    SELECT id FROM transacoes_cashback 
                    WHERE status = 'pendente' 
                    ORDER BY data_transacao DESC 
                    LIMIT 1
                ");
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$result) {
                    return [
                        'success' => false,
                        'message' => 'Nenhuma transação pendente encontrada para teste',
                        'test' => true
                    ];
                }
                
                $testTransactionId = $result['id'];
            }
            
            // Enviar notificação de teste de forma síncrona
            $result = self::send($testTransactionId, [
                'async' => false,
                'debug' => true
            ]);
            
            $result['test'] = true;
            $result['test_transaction_id'] = $testTransactionId;
            
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
     * Pode ser usado em uma rotina de manutenção para tentar
     * reenviar notificações que falharam anteriormente.
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
            
            while ($attempts < $maxRetries && !$success) {
                $attempts++;
                $result = self::send($transactionId, ['async' => false]);
                
                if ($result['success']) {
                    $success = true;
                    $successCount++;
                } else {
                    sleep(1); // Aguardar 1 segundo entre tentativas
                }
            }
            
            if (!$success) {
                $failCount++;
            }
            
            $results[$transactionId] = [
                'success' => $success,
                'attempts' => $attempts,
                'last_result' => $result ?? null
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
}
?>