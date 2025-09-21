<?php
/**
 * AUTO NOTIFICATION TRIGGER - KLUBE CASH
 *
 * Sistema automático para disparar notificações assim que transações são criadas
 */

class AutoNotificationTrigger {

    /**
     * Dispara notificação automaticamente após criação/atualização de transação
     */
    public static function triggerNotification($transactionId, $action = 'created') {
        try {
            // Log do trigger
            self::log("TRIGGER AUTOMÁTICO: Transação {$transactionId} - Ação: {$action}");

            // Executar em background para não atrasar a resposta
            self::executeInBackground($transactionId, $action);

            return true;

        } catch (Exception $e) {
            self::log("ERRO no trigger automático: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Executa notificação em background
     */
    private static function executeInBackground($transactionId, $action) {
        $scriptPath = __DIR__ . '/../run_single_notification.php';

        // Comando para executar em background
        $command = "php {$scriptPath} {$transactionId} {$action} > /dev/null 2>&1 &";

        // Executar em background
        if (function_exists('exec')) {
            exec($command);
            self::log("Comando em background executado: {$command}");
        } else {
            // Fallback: executar diretamente (pode ser mais lento)
            self::executeDirect($transactionId, $action);
        }
    }

    /**
     * Execução direta caso exec() não esteja disponível
     */
    private static function executeDirect($transactionId, $action) {
        try {
            require_once __DIR__ . '/../classes/FixedBrutalNotificationSystem.php';

            $system = new FixedBrutalNotificationSystem();
            $result = $system->forceNotifyTransaction($transactionId);

            self::log("Notificação direta executada: " . json_encode($result));

        } catch (Exception $e) {
            self::log("Erro na execução direta: " . $e->getMessage());
        }
    }

    /**
     * Hook para ser chamado após INSERT de transação
     */
    public static function onTransactionCreated($transactionId) {
        self::triggerNotification($transactionId, 'created');
    }

    /**
     * Hook para ser chamado após UPDATE de transação
     */
    public static function onTransactionUpdated($transactionId) {
        self::triggerNotification($transactionId, 'updated');
    }

    /**
     * Hook para ser chamado após mudança de status
     */
    public static function onStatusChanged($transactionId, $oldStatus, $newStatus) {
        self::log("Status alterado: Transação {$transactionId} de '{$oldStatus}' para '{$newStatus}'");

        // Notificar apenas se mudou para aprovado ou pendente
        if (in_array($newStatus, ['aprovado', 'pendente'])) {
            self::triggerNotification($transactionId, 'status_changed');
        }
    }

    /**
     * Log específico do trigger
     */
    private static function log($message) {
        $logFile = __DIR__ . '/../logs/auto_trigger.log';

        // Criar diretório se não existir
        if (!is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $logLine = "[{$timestamp}] {$message}\n";

        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Verificação automática de todas as transações não notificadas
     */
    public static function checkAllPendingNotifications() {
        try {
            require_once __DIR__ . '/../classes/FixedBrutalNotificationSystem.php';

            $system = new FixedBrutalNotificationSystem();
            return $system->checkAndProcessNewTransactions();

        } catch (Exception $e) {
            self::log("Erro na verificação automática: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

// CHAMADA AUTOMÁTICA SE EXECUTADO DIRETAMENTE
if (basename($_SERVER['PHP_SELF']) === 'AutoNotificationTrigger.php') {
    echo "=== EXECUTANDO VERIFICAÇÃO AUTOMÁTICA ===\n";
    $result = AutoNotificationTrigger::checkAllPendingNotifications();
    echo "Resultado: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
}
?>