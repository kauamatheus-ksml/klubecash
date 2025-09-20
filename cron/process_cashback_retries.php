<?php
/**
 * Script para Processamento Automático de Retries de Notificações
 *
 * Este script deve ser executado via cron job para processar
 * automaticamente notificações de cashback que falharam.
 *
 * Exemplo de cron (executar a cada 30 minutos):
 * */30 * * * * php /path/to/project/cron/process_cashback_retries.php
 *
 * Para executar manualmente:
 * php process_cashback_retries.php
 *
 * Localização: cron/process_cashback_retries.php
 * Autor: Sistema Klube Cash
 * Versão: 1.0
 */

// Configurar ambiente para linha de comando
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain');
    echo "Este script deve ser executado via linha de comando.\n";
    exit(1);
}

// Incluir dependências
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../utils/CashbackRetrySystem.php';

// === CONFIGURAÇÕES ===
$BATCH_SIZE = 50; // Quantas notificações processar por vez
$CLEANUP_DAYS = 30; // Limpar registros com mais de X dias
$VERBOSE = in_array('--verbose', $argv) || in_array('-v', $argv);
$STATS_ONLY = in_array('--stats', $argv) || in_array('-s', $argv);
$CLEANUP_ONLY = in_array('--cleanup', $argv) || in_array('-c', $argv);

// === FUNÇÕES AUXILIARES ===
function log_message($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    echo "[{$timestamp}] [{$level}] {$message}\n";
    error_log("[RETRY-CRON] [{$level}] {$message}");
}

function format_stats($stats) {
    $output = "\n=== ESTATÍSTICAS DO SISTEMA DE RETRY ===\n";
    $output .= "Total pendente: {$stats['total_pending']}\n";
    $output .= "Total sucesso: {$stats['total_success']}\n";
    $output .= "Total falhado: {$stats['total_failed']}\n";
    $output .= "Retries agendados: {$stats['pending_retries']}\n";
    $output .= "Retries atrasados: {$stats['overdue_retries']}\n";
    $output .= "Taxa de sucesso: {$stats['success_rate']}\n";
    $output .= "Máx. tentativas: {$stats['max_retries_configured']}\n";
    $output .= "Intervalo base: {$stats['base_delay_seconds']}s\n";
    $output .= "==========================================\n";
    return $output;
}

// === INÍCIO DO SCRIPT ===
try {
    log_message("Iniciando processamento de retries de notificações cashback", 'INFO');

    // Instanciar sistema de retry
    $retrySystem = new CashbackRetrySystem();

    // Obter estatísticas iniciais
    $initialStats = $retrySystem->getStats();

    if ($VERBOSE) {
        log_message("Estatísticas iniciais:", 'INFO');
        echo format_stats($initialStats);
    }

    // === PROCESSAMENTO DE ESTATÍSTICAS APENAS ===
    if ($STATS_ONLY) {
        log_message("Modo apenas estatísticas ativado", 'INFO');
        echo format_stats($initialStats);
        exit(0);
    }

    // === PROCESSAMENTO DE LIMPEZA APENAS ===
    if ($CLEANUP_ONLY) {
        log_message("Modo apenas limpeza ativado", 'INFO');
        $cleaned = $retrySystem->cleanupOldRecords($CLEANUP_DAYS);
        log_message("Removidos {$cleaned} registros antigos (>{$CLEANUP_DAYS} dias)", 'INFO');
        exit(0);
    }

    // === PROCESSAMENTO PRINCIPAL ===

    // Verificar se há retries pendentes
    if ($initialStats['overdue_retries'] == 0) {
        log_message("Nenhum retry atrasado encontrado", 'INFO');

        if ($initialStats['pending_retries'] > 0) {
            log_message("Existem {$initialStats['pending_retries']} retries agendados para o futuro", 'INFO');
        }

        // Fazer limpeza se há registros para limpar
        if ($initialStats['total_success'] > 100 || $initialStats['total_failed'] > 100) {
            $cleaned = $retrySystem->cleanupOldRecords($CLEANUP_DAYS);
            if ($cleaned > 0) {
                log_message("Limpeza automática: removidos {$cleaned} registros antigos", 'INFO');
            }
        }

        exit(0);
    }

    log_message("Encontrados {$initialStats['overdue_retries']} retries atrasados para processar", 'INFO');

    // Processar retries
    $result = $retrySystem->processRetries($BATCH_SIZE);

    if ($result['success']) {
        log_message("Processamento concluído com sucesso", 'INFO');
        log_message("Processadas: {$result['processed']} | Sucessos: {$result['successes']} | Falhas: {$result['failures']}", 'INFO');

        if ($VERBOSE && $result['processed'] > 0) {
            log_message("Detalhes do processamento:", 'DEBUG');

            foreach ($result['details'] as $detail) {
                $transactionId = $detail['transaction_id'];
                $attempt = $detail['attempts'];
                $clientName = $detail['nome'];

                log_message("  - Transação {$transactionId} (Cliente: {$clientName}) - Tentativa {$attempt}", 'DEBUG');
            }
        }

        // Estatísticas finais
        if ($VERBOSE) {
            $finalStats = $retrySystem->getStats();
            log_message("Estatísticas finais:", 'INFO');
            echo format_stats($finalStats);
        }

    } else {
        log_message("Erro no processamento: " . $result['message'], 'ERROR');
        exit(1);
    }

    // === LIMPEZA AUTOMÁTICA ===
    // Executar limpeza a cada 100 registros processados ou se há muitos registros antigos
    $shouldCleanup = $result['processed'] >= 50 ||
                    ($initialStats['total_success'] + $initialStats['total_failed']) > 500;

    if ($shouldCleanup) {
        log_message("Executando limpeza automática de registros antigos", 'INFO');
        $cleaned = $retrySystem->cleanupOldRecords($CLEANUP_DAYS);

        if ($cleaned > 0) {
            log_message("Limpeza concluída: {$cleaned} registros removidos", 'INFO');
        } else {
            log_message("Limpeza concluída: nenhum registro antigo encontrado", 'INFO');
        }
    }

    log_message("Script finalizado com sucesso", 'INFO');

} catch (Exception $e) {
    log_message("Erro crítico no script: " . $e->getMessage(), 'ERROR');
    log_message("Stack trace: " . $e->getTraceAsString(), 'ERROR');
    exit(1);
}

// === INFORMAÇÕES DE USO ===
if (count($argv) > 1 && ($argv[1] === '--help' || $argv[1] === '-h')) {
    echo "\n=== AJUDA - PROCESSAMENTO DE RETRIES CASHBACK ===\n";
    echo "Uso: php process_cashback_retries.php [opções]\n\n";
    echo "Opções:\n";
    echo "  --verbose, -v    Exibir informações detalhadas\n";
    echo "  --stats, -s      Exibir apenas estatísticas (não processar)\n";
    echo "  --cleanup, -c    Executar apenas limpeza de registros antigos\n";
    echo "  --help, -h       Exibir esta ajuda\n\n";
    echo "Exemplos:\n";
    echo "  php process_cashback_retries.php               # Execução normal\n";
    echo "  php process_cashback_retries.php --verbose     # Com detalhes\n";
    echo "  php process_cashback_retries.php --stats       # Apenas estatísticas\n";
    echo "  php process_cashback_retries.php --cleanup     # Apenas limpeza\n\n";
    echo "Configuração de cron recomendada (a cada 30 minutos):\n";
    echo "*/30 * * * * php " . __FILE__ . " >> /var/log/cashback_retries.log 2>&1\n\n";
}
?>