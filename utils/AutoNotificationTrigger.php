<?php
/**
 * TRIGGER AUTOMÁTICO DE NOTIFICAÇÃO
 *
 * Este arquivo deve ser chamado automaticamente após cada transação
 * Funciona independente de qualquer outro sistema
 */

class AutoNotificationTrigger {

    /**
     * Método estático para ser chamado de qualquer lugar
     */
    public static function notifyNewTransaction($transactionId) {
        try {
            // Log de início
            error_log("AutoNotificationTrigger: Iniciando para transação {$transactionId}");

            // Carregar sistema brutal
            require_once __DIR__ . '/../classes/BrutalNotificationSystem.php';

            $system = new BrutalNotificationSystem();
            $result = $system->forceNotifyTransaction($transactionId);

            // Log de resultado
            if ($result['success']) {
                error_log("AutoNotificationTrigger: SUCESSO para transação {$transactionId} - " . $result['message']);
            } else {
                error_log("AutoNotificationTrigger: FALHA para transação {$transactionId} - " . $result['message']);
            }

            return $result;

        } catch (Exception $e) {
            error_log("AutoNotificationTrigger: ERRO CRÍTICO para transação {$transactionId} - " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Verificação automática de todas as transações não notificadas
     */
    public static function checkAllPendingNotifications() {
        try {
            require_once __DIR__ . '/../classes/BrutalNotificationSystem.php';

            $system = new BrutalNotificationSystem();
            return $system->checkAndProcessNewTransactions();

        } catch (Exception $e) {
            error_log("AutoNotificationTrigger: Erro na verificação automática - " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

// CHAMADA AUTOMÁTICA SE EXECUTADO DIRETAMENTE
if (basename($_SERVER['PHP_SELF']) === 'AutoNotificationTrigger.php') {
    echo "=== EXECUTANDO VERIFICAÇÃO AUTOMÁTICA ===\n";
    $result = AutoNotificationTrigger::checkAllPendingNotifications();
    echo "Resultado: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
}
?>