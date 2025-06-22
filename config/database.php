<?php
/**
 * Configuração Otimizada de Banco de Dados - Klube Cash PWA
 * Versão 2.1.0 com otimizações para mobile e pool de conexões
 * Autor: Sistema Klube Cash
 * Data: 22/06/2025
 */

date_default_timezone_set('America/Sao_Paulo');

// Parâmetros de conexão com o banco de dados
define('DB_HOST', 'srv406.hstgr.io');
define('DB_NAME', 'u383946504_klubecash');
define('DB_USER', 'u383946504_klubecash');
define('DB_PASS', 'Aaku_2004@');

// === CONFIGURAÇÕES OTIMIZADAS PARA MOBILE ===
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATION', 'utf8mb4_unicode_ci');

// Timeouts reduzidos para mobile (em segundos)
define('DB_CONNECT_TIMEOUT', 3); // 3 segundos
define('DB_READ_TIMEOUT', 5);    // 5 segundos  
define('DB_WRITE_TIMEOUT', 8);   // 8 segundos

// Pool de conexões otimizado
define('DB_MAX_CONNECTIONS', 10);
define('DB_MIN_CONNECTIONS', 2);
define('DB_CONNECTION_LIFETIME', 300); // 5 minutos
define('DB_IDLE_TIMEOUT', 60); // 1 minuto

// Cache de conexões
define('DB_CACHE_ENABLED', true);
define('DB_CACHE_TTL', 1800); // 30 minutos

// Configurações específicas para PWA
define('DB_MOBILE_BATCH_SIZE', 20);
define('DB_MOBILE_MAX_RETRY', 2);
define('DB_OFFLINE_SYNC_INTERVAL', 30); // segundos

/**
 * Classe Database Otimizada para PWA Mobile
 * Implementa pool de conexões e otimizações específicas para dispositivos móveis
 */
class Database {
    private static $connectionPool = [];
    private static $activeConnections = 0;
    private static $connectionStats = [
        'created' => 0,
        'reused' => 0,
        'failed' => 0,
        'timeout' => 0
    ];
    
    /**
     * Obtém uma conexão otimizada do pool
     * 
     * @param bool $forceNew Força uma nova conexão
     * @return PDO Objeto de conexão PDO
     */
    public static function getConnection($forceNew = false) {
        try {
            // Limpa conexões expiradas do pool
            self::cleanExpiredConnections();
            
            // Se não forçar nova conexão, tenta reutilizar do pool
            if (!$forceNew && !empty(self::$connectionPool)) {
                $connection = array_pop(self::$connectionPool);
                if (self::isConnectionValid($connection['pdo'])) {
                    self::$connectionStats['reused']++;
                    return $connection['pdo'];
                }
            }
            
            // Verifica limite de conexões
            if (self::$activeConnections >= DB_MAX_CONNECTIONS) {
                self::waitForAvailableConnection();
            }
            
            // Cria nova conexão otimizada para mobile
            $connection = self::createOptimizedConnection();
            self::$activeConnections++;
            self::$connectionStats['created']++;
            
            return $connection;
            
        } catch (Exception $e) {
            self::$connectionStats['failed']++;
            self::logError("Erro ao obter conexão: " . $e->getMessage());
            throw new Exception("Falha na conexão com o banco de dados. Tente novamente.");
        }
    }
    
    /**
     * Cria uma conexão otimizada para dispositivos móveis
     * 
     * @return PDO
     */
    private static function createOptimizedConnection() {
        $dsn = sprintf(
            "mysql:host=%s;dbname=%s;charset=%s;port=3306",
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );
        
        // Opções otimizadas para mobile
        $options = [
            // Configurações básicas
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            
            // Timeouts otimizados para mobile
            PDO::ATTR_TIMEOUT => DB_CONNECT_TIMEOUT,
            
            // Configurações de performance
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE " . DB_COLLATION,
            
            // Otimizações específicas para mobile
            PDO::MYSQL_ATTR_READ_DEFAULT_FILE => null,
            PDO::MYSQL_ATTR_READ_DEFAULT_GROUP => null,
            
            // SSL otimizado (se necessário)
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            
            // Configurações de conexão persistente controlada
            PDO::ATTR_PERSISTENT => false, // Desabilitado para melhor controle do pool
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        // Configurações adicionais após conexão
        $pdo->exec("SET SESSION wait_timeout = " . DB_IDLE_TIMEOUT);
        $pdo->exec("SET SESSION interactive_timeout = " . DB_CONNECTION_LIFETIME);
        $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE'");
        
        // Otimizações específicas para PWA/Mobile
        $pdo->exec("SET SESSION sort_buffer_size = 2097152"); // 2MB
        $pdo->exec("SET SESSION read_buffer_size = 131072");  // 128KB
        $pdo->exec("SET SESSION max_heap_table_size = 16777216"); // 16MB
        
        return $pdo;
    }
    
    /**
     * Retorna uma conexão para o pool (reutilização)
     * 
     * @param PDO $connection
     */
    public static function returnConnection($connection) {
        if (count(self::$connectionPool) < DB_MAX_CONNECTIONS && self::isConnectionValid($connection)) {
            self::$connectionPool[] = [
                'pdo' => $connection,
                'created_at' => time(),
                'last_used' => time()
            ];
        } else {
            $connection = null;
            self::$activeConnections--;
        }
    }
    
    /**
     * Verifica se a conexão ainda é válida
     * 
     * @param PDO $connection
     * @return bool
     */
    private static function isConnectionValid($connection) {
        try {
            $connection->query('SELECT 1');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Remove conexões expiradas do pool
     */
    private static function cleanExpiredConnections() {
        $now = time();
        self::$connectionPool = array_filter(self::$connectionPool, function($conn) use ($now) {
            $expired = ($now - $conn['created_at']) > DB_CONNECTION_LIFETIME;
            if ($expired) {
                self::$activeConnections--;
            }
            return !$expired;
        });
    }
    
    /**
     * Aguarda uma conexão ficar disponível
     */
    private static function waitForAvailableConnection() {
        $maxWait = 3; // segundos
        $waited = 0;
        
        while (self::$activeConnections >= DB_MAX_CONNECTIONS && $waited < $maxWait) {
            usleep(100000); // 0.1 segundo
            $waited += 0.1;
            self::cleanExpiredConnections();
        }
        
        if (self::$activeConnections >= DB_MAX_CONNECTIONS) {
            throw new Exception("Pool de conexões esgotado. Tente novamente em alguns segundos.");
        }
    }
    
    /**
     * Executa uma query com retry automático para mobile
     * 
     * @param string $query
     * @param array $params
     * @param int $maxRetries
     * @return mixed
     */
    public static function executeWithRetry($query, $params = [], $maxRetries = DB_MOBILE_MAX_RETRY) {
        $attempt = 0;
        
        while ($attempt <= $maxRetries) {
            try {
                $connection = self::getConnection();
                $stmt = $connection->prepare($query);
                
                if ($params) {
                    $stmt->execute($params);
                } else {
                    $stmt->execute();
                }
                
                self::returnConnection($connection);
                return $stmt;
                
            } catch (PDOException $e) {
                $attempt++;
                self::$connectionStats['timeout']++;
                
                if ($attempt > $maxRetries) {
                    self::logError("Query falhou após {$maxRetries} tentativas: " . $e->getMessage());
                    throw $e;
                }
                
                // Aguarda antes de tentar novamente
                usleep(500000 * $attempt); // 0.5s * tentativa
            }
        }
    }
    
    /**
     * Obtém estatísticas do pool de conexões
     * 
     * @return array
     */
    public static function getPoolStats() {
        return [
            'pool_size' => count(self::$connectionPool),
            'active_connections' => self::$activeConnections,
            'max_connections' => DB_MAX_CONNECTIONS,
            'stats' => self::$connectionStats,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];
    }
    
    /**
     * Fecha todas as conexões do pool
     */
    public static function closeAllConnections() {
        self::$connectionPool = [];
        self::$activeConnections = 0;
        
        // Força garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }
    
    /**
     * Configuração específica para transações mobile
     * 
     * @return PDO
     */
    public static function getTransactionConnection() {
        $connection = self::getConnection(true);
        
        // Configurações otimizadas para transações mobile
        $connection->exec("SET SESSION autocommit = 0");
        $connection->exec("SET SESSION transaction_isolation = 'READ_COMMITTED'");
        
        return $connection;
    }
    
    /**
     * Método para queries de leitura otimizadas (relatórios, consultas)
     * 
     * @return PDO
     */
    public static function getReadOnlyConnection() {
        $connection = self::getConnection();
        
        // Otimizações para leitura
        $connection->exec("SET SESSION transaction_read_only = 1");
        $connection->exec("SET SESSION query_cache_type = ON");
        
        return $connection;
    }
    
    /**
     * Log de erros otimizado
     * 
     * @param string $message
     */
    private static function logError($message) {
        $logFile = LOGS_DIR . '/database_' . date('Y-m-d') . '.log';
        $logMessage = sprintf(
            "[%s] %s | Pool: %d/%d | Memory: %s\n",
            date('Y-m-d H:i:s'),
            $message,
            self::$activeConnections,
            DB_MAX_CONNECTIONS,
            self::formatBytes(memory_get_usage(true))
        );
        
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Formatar bytes para leitura humana
     * 
     * @param int $bytes
     * @return string
     */
    private static function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }
    
    /**
     * Health check da conexão para monitoramento
     * 
     * @return array
     */
    public static function healthCheck() {
        $start = microtime(true);
        
        try {
            $connection = self::getConnection();
            $stmt = $connection->query("SELECT 1 as health_check");
            $result = $stmt->fetch();
            
            $responseTime = (microtime(true) - $start) * 1000; // em ms
            
            self::returnConnection($connection);
            
            return [
                'status' => 'healthy',
                'response_time_ms' => round($responseTime, 2),
                'pool_stats' => self::getPoolStats(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'response_time_ms' => round((microtime(true) - $start) * 1000, 2),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }
}

// === FUNÇÕES AUXILIARES PARA PWA ===

/**
 * Função para operações batch otimizadas para mobile
 * 
 * @param array $operations Lista de operações
 * @param int $batchSize Tamanho do lote
 * @return array Resultados
 */
function executeMobileBatch($operations, $batchSize = DB_MOBILE_BATCH_SIZE) {
    $results = [];
    $batches = array_chunk($operations, $batchSize);
    
    foreach ($batches as $batch) {
        $connection = Database::getConnection();
        
        try {
            $connection->beginTransaction();
            
            foreach ($batch as $operation) {
                $stmt = $connection->prepare($operation['query']);
                $stmt->execute($operation['params'] ?? []);
                $results[] = $stmt;
            }
            
            $connection->commit();
        } catch (Exception $e) {
            $connection->rollback();
            throw $e;
        } finally {
            Database::returnConnection($connection);
        }
    }
    
    return $results;
}

/**
 * Cache de queries para melhor performance mobile
 */
class MobileQueryCache {
    private static $cache = [];
    private static $maxSize = 100;
    
    public static function get($key) {
        if (isset(self::$cache[$key]) && 
            (time() - self::$cache[$key]['time']) < DB_CACHE_TTL) {
            return self::$cache[$key]['data'];
        }
        return null;
    }
    
    public static function set($key, $data) {
        if (count(self::$cache) >= self::$maxSize) {
            array_shift(self::$cache);
        }
        
        self::$cache[$key] = [
            'data' => $data,
            'time' => time()
        ];
    }
    
    public static function clear() {
        self::$cache = [];
    }
}

// Inicialização automática em ambiente de produção
if (!defined('ENVIRONMENT') || ENVIRONMENT === 'production') {
    register_shutdown_function([Database::class, 'closeAllConnections']);
}

// Log de inicialização
if (defined('LOGS_DIR')) {
    $initLog = sprintf(
        "[%s] Database config loaded | Pool: %d-%d connections | Timeouts: %ds connect, %ds read/write\n",
        date('Y-m-d H:i:s'),
        DB_MIN_CONNECTIONS,
        DB_MAX_CONNECTIONS,
        DB_CONNECT_TIMEOUT,
        DB_READ_TIMEOUT
    );
    
    file_put_contents(
        LOGS_DIR . '/database_init_' . date('Y-m-d') . '.log',
        $initLog,
        FILE_APPEND | LOCK_EX
    );
}
?>