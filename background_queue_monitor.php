<?php
/**
 * 🔄 MONITOR DE FILA EM BACKGROUND
 *
 * Script que roda continuamente verificando e processando fila
 * REDUNDÂNCIA para garantia total de entrega
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/EmergencyQueueNotifier.php';
require_once __DIR__ . '/process_queue.php';

class BackgroundQueueMonitor {

    private $logFile;
    private $pidFile;
    private $emergencyNotifier;
    private $processor;

    public function __construct() {
        $this->logFile = __DIR__ . '/logs/background_monitor.log';
        $this->pidFile = __DIR__ . '/logs/monitor.pid';
        $this->emergencyNotifier = new EmergencyQueueNotifier();
        $this->processor = new QueueProcessor();

        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }

    /**
     * 🚀 INICIAR MONITORAMENTO EM BACKGROUND
     */
    public function start() {
        // Verificar se já está rodando
        if ($this->isRunning()) {
            $this->log("⚠️ Monitor já está rodando (PID: " . file_get_contents($this->pidFile) . ")");
            return;
        }

        // Salvar PID
        file_put_contents($this->pidFile, getmypid());

        $this->log("🚀 INICIANDO MONITOR DE FILA EM BACKGROUND");
        $this->log("📋 PID: " . getmypid());

        // Loop principal
        $this->monitorLoop();
    }

    /**
     * 🔄 LOOP PRINCIPAL DE MONITORAMENTO
     */
    private function monitorLoop() {
        $cycles = 0;

        while (true) {
            $cycles++;

            try {
                // Verificar fila a cada 30 segundos
                $queueCount = $this->emergencyNotifier->getQueueCount();

                if ($queueCount > 0) {
                    $this->log("📋 Encontradas {$queueCount} mensagens pendentes na fila");

                    // Processar fila
                    ob_start();
                    $this->processor->processQueue();
                    $output = ob_get_clean();

                    $newQueueCount = $this->emergencyNotifier->getQueueCount();
                    $processed = $queueCount - $newQueueCount;

                    if ($processed > 0) {
                        $this->log("✅ Processadas {$processed} mensagens automaticamente");
                    }
                } else {
                    // Log reduzido - apenas a cada 10 ciclos (5 minutos)
                    if ($cycles % 10 === 0) {
                        $this->log("✅ Fila vazia - sistema funcionando normalmente (ciclo {$cycles})");
                    }
                }

                // Dormir 30 segundos
                sleep(30);

            } catch (Exception $e) {
                $this->log("❌ ERRO no monitor: " . $e->getMessage());
                sleep(30); // Continuar mesmo com erro
            }
        }
    }

    /**
     * 🛑 PARAR MONITOR
     */
    public function stop() {
        if ($this->isRunning()) {
            $pid = file_get_contents($this->pidFile);
            $this->log("🛑 Parando monitor (PID: {$pid})");

            // Remover PID file
            unlink($this->pidFile);

            // No Windows, usar taskkill
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                exec("taskkill /F /PID {$pid}");
            } else {
                exec("kill {$pid}");
            }
        } else {
            $this->log("⚠️ Monitor não está rodando");
        }
    }

    /**
     * ❓ VERIFICAR SE ESTÁ RODANDO
     */
    private function isRunning() {
        if (!file_exists($this->pidFile)) {
            return false;
        }

        $pid = file_get_contents($this->pidFile);

        // Verificar se processo existe
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $output = shell_exec("tasklist /FI \"PID eq {$pid}\" 2>NUL");
            return strpos($output, $pid) !== false;
        } else {
            return file_exists("/proc/{$pid}");
        }
    }

    /**
     * 📊 STATUS DO MONITOR
     */
    public function status() {
        if ($this->isRunning()) {
            $pid = file_get_contents($this->pidFile);
            $queueCount = $this->emergencyNotifier->getQueueCount();

            echo "✅ Monitor ativo (PID: {$pid})\n";
            echo "📋 Mensagens na fila: {$queueCount}\n";
            echo "📝 Log: tail -f {$this->logFile}\n";
        } else {
            echo "❌ Monitor não está rodando\n";
            echo "🚀 Para iniciar: php background_queue_monitor.php start\n";
        }
    }

    /**
     * 📝 LOG
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logLine = "[{$timestamp}] {$message}\n";
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
}

// ===== EXECUÇÃO =====
$monitor = new BackgroundQueueMonitor();

if (php_sapi_name() === 'cli') {
    $action = $argv[1] ?? 'status';

    switch ($action) {
        case 'start':
            $monitor->start();
            break;
        case 'stop':
            $monitor->stop();
            break;
        case 'status':
            $monitor->status();
            break;
        default:
            echo "Uso: php background_queue_monitor.php [start|stop|status]\n";
    }
} else {
    // Execução via web
    $action = $_GET['action'] ?? 'status';

    echo "<h2>🔄 Monitor de Fila em Background</h2>";

    switch ($action) {
        case 'start':
            echo "<p>🚀 Iniciando monitor...</p>";
            $monitor->start();
            break;
        case 'stop':
            echo "<p>🛑 Parando monitor...</p>";
            $monitor->stop();
            break;
        default:
            $monitor->status();
            echo "<p><a href='?action=start'>🚀 Iniciar</a> | <a href='?action=stop'>🛑 Parar</a></p>";
    }
}
?>