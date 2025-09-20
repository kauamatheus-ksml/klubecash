<?php
/**
 * Sistema de Retry Automático para Notificações de Cashback
 *
 * Este sistema monitora notificações falhadas e tenta reenviá-las
 * automaticamente em intervalos definidos, garantindo que nenhuma
 * notificação importante seja perdida.
 *
 * Funcionalidades:
 * - Retry automático com backoff exponencial
 * - Monitoramento de notificações falhadas
 * - Logs detalhados para auditoria
 * - Limite máximo de tentativas
 *
 * Localização: utils/CashbackRetrySystem.php
 * Autor: Sistema Klube Cash
 * Versão: 1.0
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/NotificationTrigger.php';

class CashbackRetrySystem {

    private $db;
    private $maxRetries;
    private $baseDelay;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->maxRetries = defined('CASHBACK_NOTIFICATION_MAX_RETRIES') ? CASHBACK_NOTIFICATION_MAX_RETRIES : 3;
        $this->baseDelay = defined('CASHBACK_NOTIFICATION_RETRY_INTERVAL') ? CASHBACK_NOTIFICATION_RETRY_INTERVAL : 3600; // 1 hora
    }

    /**
     * Processa todas as notificações pendentes de retry
     *
     * Este método deve ser chamado periodicamente (via cron job ou similar)
     * para processar notificações que falharam anteriormente.
     *
     * @param int $batchSize Quantas notificações processar por vez
     * @return array Resultado do processamento
     */
    public function processRetries($batchSize = 50) {
        try {
            // Criar tabela de logs se não existir
            $this->createLogTableIfNotExists();

            // Buscar notificações que precisam de retry
            $pendingRetries = $this->getPendingRetries($batchSize);

            if (empty($pendingRetries)) {
                return [
                    'success' => true,
                    'message' => 'Nenhuma notificação pendente de retry',
                    'processed' => 0,
                    'successes' => 0,
                    'failures' => 0
                ];
            }

            $processed = 0;
            $successes = 0;
            $failures = 0;

            foreach ($pendingRetries as $retry) {
                $processed++;

                $result = $this->processRetry($retry);

                if ($result['success']) {
                    $successes++;
                } else {
                    $failures++;
                }
            }

            return [
                'success' => true,
                'message' => "Processadas {$processed} notificações",
                'processed' => $processed,
                'successes' => $successes,
                'failures' => $failures,
                'details' => $pendingRetries
            ];

        } catch (Exception $e) {
            error_log('Erro no CashbackRetrySystem::processRetries: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro no processamento: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Registra uma falha de notificação para retry posterior
     *
     * @param int $transactionId ID da transação
     * @param string $error Mensagem de erro
     * @param int $attempt Número da tentativa atual
     * @return bool True se registrado com sucesso
     */
    public function registerFailure($transactionId, $error, $attempt = 1) {
        try {
            $this->createLogTableIfNotExists();

            // Verificar se já existe um registro para esta transação
            $stmt = $this->db->prepare("
                SELECT id, attempts FROM cashback_notification_retries
                WHERE transaction_id = :transaction_id AND status = 'pending'
            ");
            $stmt->bindParam(':transaction_id', $transactionId);
            $stmt->execute();
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Atualizar registro existente
                $newAttempts = $existing['attempts'] + 1;
                $nextRetry = $this->calculateNextRetry($newAttempts);

                if ($newAttempts >= $this->maxRetries) {
                    // Marcar como falhado definitivamente
                    $stmt = $this->db->prepare("
                        UPDATE cashback_notification_retries
                        SET status = 'failed', attempts = :attempts, last_error = :error, updated_at = NOW()
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':attempts' => $newAttempts,
                        ':error' => $error,
                        ':id' => $existing['id']
                    ]);

                    error_log("Notificação para transação {$transactionId} falhada definitivamente após {$newAttempts} tentativas");
                    return false;
                } else {
                    // Agendar próximo retry
                    $stmt = $this->db->prepare("
                        UPDATE cashback_notification_retries
                        SET attempts = :attempts, last_error = :error, next_retry = :next_retry, updated_at = NOW()
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':attempts' => $newAttempts,
                        ':error' => $error,
                        ':next_retry' => $nextRetry,
                        ':id' => $existing['id']
                    ]);
                }
            } else {
                // Criar novo registro
                $nextRetry = $this->calculateNextRetry($attempt);

                $stmt = $this->db->prepare("
                    INSERT INTO cashback_notification_retries
                    (transaction_id, attempts, last_error, next_retry, status, created_at)
                    VALUES (:transaction_id, :attempts, :error, :next_retry, 'pending', NOW())
                ");
                $stmt->execute([
                    ':transaction_id' => $transactionId,
                    ':attempts' => $attempt,
                    ':error' => $error,
                    ':next_retry' => $nextRetry
                ]);
            }

            return true;

        } catch (Exception $e) {
            error_log('Erro ao registrar falha de notificação: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Marca uma notificação como enviada com sucesso
     *
     * @param int $transactionId ID da transação
     * @return bool True se marcado com sucesso
     */
    public function markAsSuccess($transactionId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE cashback_notification_retries
                SET status = 'success', updated_at = NOW()
                WHERE transaction_id = :transaction_id AND status = 'pending'
            ");
            $stmt->bindParam(':transaction_id', $transactionId);
            return $stmt->execute();

        } catch (Exception $e) {
            error_log('Erro ao marcar notificação como sucesso: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca notificações pendentes de retry
     *
     * @param int $limit Limite de registros
     * @return array Array de notificações pendentes
     */
    private function getPendingRetries($limit = 50) {
        try {
            $stmt = $this->db->prepare("
                SELECT r.*, t.usuario_id, t.valor_total, t.valor_cliente, t.data_transacao,
                       u.nome, u.telefone, l.nome_fantasia as loja_nome
                FROM cashback_notification_retries r
                INNER JOIN transacoes_cashback t ON r.transaction_id = t.id
                INNER JOIN usuarios u ON t.usuario_id = u.id
                INNER JOIN lojas l ON t.loja_id = l.id
                WHERE r.status = 'pending'
                AND r.next_retry <= NOW()
                AND r.attempts < :max_retries
                ORDER BY r.next_retry ASC
                LIMIT :limit
            ");

            $stmt->bindParam(':max_retries', $this->maxRetries, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Erro ao buscar retries pendentes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Processa um retry específico
     *
     * @param array $retry Dados do retry
     * @return array Resultado do processamento
     */
    private function processRetry($retry) {
        try {
            $transactionId = $retry['transaction_id'];

            // Tentar enviar notificação
            $result = NotificationTrigger::send($transactionId, ['async' => false]);

            if ($result['success']) {
                // Marcar como sucesso
                $this->markAsSuccess($transactionId);

                error_log("Retry bem-sucedido para transação {$transactionId} após {$retry['attempts']} tentativas");

                return [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'message' => 'Notificação enviada com sucesso no retry'
                ];
            } else {
                // Registrar nova falha
                $this->registerFailure($transactionId, $result['message'], $retry['attempts']);

                return [
                    'success' => false,
                    'transaction_id' => $transactionId,
                    'message' => 'Retry falhou: ' . $result['message']
                ];
            }

        } catch (Exception $e) {
            error_log('Erro ao processar retry para transação ' . $retry['transaction_id'] . ': ' . $e->getMessage());

            return [
                'success' => false,
                'transaction_id' => $retry['transaction_id'],
                'message' => 'Erro no processamento: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calcula próximo horário de retry com backoff exponencial
     *
     * @param int $attempt Número da tentativa
     * @return string Data/hora do próximo retry (Y-m-d H:i:s)
     */
    private function calculateNextRetry($attempt) {
        // Backoff exponencial: 1h, 2h, 4h, 8h, etc.
        $delaySeconds = $this->baseDelay * pow(2, $attempt - 1);

        // Máximo de 24 horas
        $delaySeconds = min($delaySeconds, 86400);

        return date('Y-m-d H:i:s', time() + $delaySeconds);
    }

    /**
     * Cria tabela de logs se não existir
     */
    private function createLogTableIfNotExists() {
        try {
            $sql = "
                CREATE TABLE IF NOT EXISTS cashback_notification_retries (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    transaction_id INT NOT NULL,
                    attempts INT DEFAULT 1,
                    last_error TEXT,
                    next_retry DATETIME,
                    status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_transaction_id (transaction_id),
                    INDEX idx_status_next_retry (status, next_retry),
                    INDEX idx_created_at (created_at)
                )
            ";

            $this->db->exec($sql);

        } catch (Exception $e) {
            error_log('Erro ao criar tabela de retry: ' . $e->getMessage());
        }
    }

    /**
     * Obtém estatísticas do sistema de retry
     *
     * @return array Estatísticas detalhadas
     */
    public function getStats() {
        try {
            $this->createLogTableIfNotExists();

            $stats = [];

            // Total de registros por status
            $stmt = $this->db->prepare("
                SELECT status, COUNT(*) as count
                FROM cashback_notification_retries
                GROUP BY status
            ");
            $stmt->execute();
            $statusCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            // Próximos retries agendados
            $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM cashback_notification_retries
                WHERE status = 'pending' AND next_retry > NOW()
            ");
            $stmt->execute();
            $pendingRetries = $stmt->fetchColumn();

            // Retries atrasados
            $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM cashback_notification_retries
                WHERE status = 'pending' AND next_retry <= NOW()
            ");
            $stmt->execute();
            $overdueRetries = $stmt->fetchColumn();

            // Taxa de sucesso
            $totalProcessed = ($statusCounts['success'] ?? 0) + ($statusCounts['failed'] ?? 0);
            $successRate = $totalProcessed > 0 ? round(($statusCounts['success'] ?? 0) / $totalProcessed * 100, 2) : 0;

            return [
                'total_pending' => $statusCounts['pending'] ?? 0,
                'total_success' => $statusCounts['success'] ?? 0,
                'total_failed' => $statusCounts['failed'] ?? 0,
                'pending_retries' => $pendingRetries,
                'overdue_retries' => $overdueRetries,
                'success_rate' => $successRate . '%',
                'max_retries_configured' => $this->maxRetries,
                'base_delay_seconds' => $this->baseDelay
            ];

        } catch (Exception $e) {
            error_log('Erro ao obter estatísticas de retry: ' . $e->getMessage());
            return [
                'error' => 'Erro ao obter estatísticas: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Limpa registros antigos
     *
     * @param int $daysOld Quantos dias de registros manter
     * @return int Número de registros removidos
     */
    public function cleanupOldRecords($daysOld = 30) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM cashback_notification_retries
                WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
                AND status IN ('success', 'failed')
            ");
            $stmt->bindParam(':days', $daysOld, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount();

        } catch (Exception $e) {
            error_log('Erro na limpeza de registros antigos: ' . $e->getMessage());
            return 0;
        }
    }
}
?>