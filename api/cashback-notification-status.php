<?php
/**
 * API para Monitoramento do Sistema de Notificações de Cashback
 *
 * Esta API fornece endpoints para monitorar o status do sistema
 * de notificações, incluindo estatísticas, logs e controles.
 *
 * Endpoints:
 * - GET /api/cashback-notification-status.php?action=stats
 * - GET /api/cashback-notification-status.php?action=health
 * - POST /api/cashback-notification-status.php?action=retry (com IDs)
 * - POST /api/cashback-notification-status.php?action=test
 *
 * Localização: api/cashback-notification-status.php
 * Autor: Sistema Klube Cash
 * Versão: 1.0
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir dependências
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../utils/CashbackRetrySystem.php';
require_once __DIR__ . '/../utils/NotificationTrigger.php';

try {
    // Verificar método
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? $_POST['action'] ?? 'stats';

    // Autenticação básica (usando mesma chave do WhatsApp)
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $providedSecret = '';

    if ($method === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        $providedSecret = $data['secret'] ?? '';
    } else {
        $providedSecret = $_GET['secret'] ?? '';
    }

    if ($providedSecret !== WHATSAPP_BOT_SECRET) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Chave de autenticação inválida',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    // Instanciar sistema de retry
    $retrySystem = new CashbackRetrySystem();

    switch ($action) {

        // === ESTATÍSTICAS COMPLETAS ===
        case 'stats':
            $stats = $retrySystem->getStats();

            // Adicionar informações do WhatsApp Bot
            $botStatus = checkBotStatus();
            $stats['whatsapp_bot'] = $botStatus;

            // Adicionar estatísticas de transações recentes
            $recentStats = getRecentTransactionStats();
            $stats['recent_transactions'] = $recentStats;

            echo json_encode([
                'success' => true,
                'action' => 'stats',
                'data' => $stats,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;

        // === VERIFICAÇÃO DE SAÚDE DO SISTEMA ===
        case 'health':
            $health = performHealthCheck();

            echo json_encode([
                'success' => true,
                'action' => 'health',
                'data' => $health,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;

        // === PROCESSAR RETRIES MANUALMENTE ===
        case 'retry':
            if ($method !== 'POST') {
                throw new Exception('Ação retry requer método POST');
            }

            $batchSize = $data['batch_size'] ?? 20;
            $result = $retrySystem->processRetries($batchSize);

            echo json_encode([
                'success' => $result['success'],
                'action' => 'retry',
                'data' => $result,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;

        // === TESTE DE NOTIFICAÇÃO ===
        case 'test':
            if ($method !== 'POST') {
                throw new Exception('Ação test requer método POST');
            }

            $testTransactionId = $data['transaction_id'] ?? null;
            $testResult = NotificationTrigger::test($testTransactionId);

            echo json_encode([
                'success' => $testResult['success'],
                'action' => 'test',
                'data' => $testResult,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;

        // === LOGS RECENTES ===
        case 'logs':
            $limit = $_GET['limit'] ?? 50;
            $logs = getRecentLogs($limit);

            echo json_encode([
                'success' => true,
                'action' => 'logs',
                'data' => $logs,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;

        // === LIMPEZA DE REGISTROS ANTIGOS ===
        case 'cleanup':
            if ($method !== 'POST') {
                throw new Exception('Ação cleanup requer método POST');
            }

            $days = $data['days'] ?? 30;
            $cleaned = $retrySystem->cleanupOldRecords($days);

            echo json_encode([
                'success' => true,
                'action' => 'cleanup',
                'data' => [
                    'records_cleaned' => $cleaned,
                    'days_threshold' => $days
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;

        default:
            throw new Exception('Ação não reconhecida: ' . $action);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

// === FUNÇÕES AUXILIARES ===

/**
 * Verifica status do bot WhatsApp
 */
function checkBotStatus() {
    try {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => WHATSAPP_BOT_URL . '/status',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json']
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($curlError) {
            return [
                'status' => 'error',
                'message' => 'Erro de conexão: ' . $curlError,
                'connected' => false
            ];
        }

        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            return [
                'status' => 'connected',
                'connected' => true,
                'bot_ready' => $data['bot_ready'] ?? false,
                'uptime' => $data['uptime'] ?? 0,
                'version' => $data['version'] ?? 'unknown'
            ];
        }

        return [
            'status' => 'http_error',
            'message' => 'HTTP ' . $httpCode,
            'connected' => false
        ];

    } catch (Exception $e) {
        return [
            'status' => 'exception',
            'message' => $e->getMessage(),
            'connected' => false
        ];
    }
}

/**
 * Obter estatísticas de transações recentes
 */
function getRecentTransactionStats() {
    try {
        $db = Database::getConnection();

        // Transações das últimas 24 horas
        $stmt = $db->prepare("
            SELECT
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'aprovado' THEN 1 END) as approved,
                COUNT(CASE WHEN status = 'pendente' THEN 1 END) as pending,
                SUM(valor_cliente) as total_cashback
            FROM transacoes_cashback
            WHERE data_transacao >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute();
        $last24h = $stmt->fetch(PDO::FETCH_ASSOC);

        // Transações da última semana
        $stmt = $db->prepare("
            SELECT
                COUNT(*) as total,
                SUM(valor_cliente) as total_cashback
            FROM transacoes_cashback
            WHERE data_transacao >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stmt->execute();
        $last7days = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'last_24_hours' => [
                'total_transactions' => intval($last24h['total']),
                'approved_transactions' => intval($last24h['approved']),
                'pending_transactions' => intval($last24h['pending']),
                'total_cashback' => floatval($last24h['total_cashback'])
            ],
            'last_7_days' => [
                'total_transactions' => intval($last7days['total']),
                'total_cashback' => floatval($last7days['total_cashback'])
            ]
        ];

    } catch (Exception $e) {
        return [
            'error' => 'Erro ao obter estatísticas: ' . $e->getMessage()
        ];
    }
}

/**
 * Verificação completa de saúde do sistema
 */
function performHealthCheck() {
    $health = [
        'overall_status' => 'healthy',
        'checks' => []
    ];

    // 1. Verificar banco de dados
    try {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT 1");
        $health['checks']['database'] = [
            'status' => 'healthy',
            'message' => 'Conexão com banco funcionando'
        ];
    } catch (Exception $e) {
        $health['checks']['database'] = [
            'status' => 'unhealthy',
            'message' => 'Erro no banco: ' . $e->getMessage()
        ];
        $health['overall_status'] = 'unhealthy';
    }

    // 2. Verificar bot WhatsApp
    $botStatus = checkBotStatus();
    $health['checks']['whatsapp_bot'] = [
        'status' => $botStatus['connected'] ? 'healthy' : 'unhealthy',
        'message' => $botStatus['message'] ?? 'Bot funcionando',
        'details' => $botStatus
    ];

    if (!$botStatus['connected']) {
        $health['overall_status'] = 'degraded';
    }

    // 3. Verificar configurações
    $requiredConstants = [
        'WHATSAPP_BOT_URL', 'WHATSAPP_BOT_SECRET',
        'CASHBACK_NOTIFICATIONS_ENABLED', 'SITE_URL'
    ];

    $configIssues = [];
    foreach ($requiredConstants as $constant) {
        if (!defined($constant)) {
            $configIssues[] = $constant;
        }
    }

    $health['checks']['configuration'] = [
        'status' => empty($configIssues) ? 'healthy' : 'unhealthy',
        'message' => empty($configIssues) ? 'Configurações OK' : 'Constantes faltando: ' . implode(', ', $configIssues)
    ];

    if (!empty($configIssues)) {
        $health['overall_status'] = 'unhealthy';
    }

    // 4. Verificar retries atrasados
    try {
        $retrySystem = new CashbackRetrySystem();
        $stats = $retrySystem->getStats();

        $overdueRetries = $stats['overdue_retries'] ?? 0;
        $health['checks']['retry_system'] = [
            'status' => $overdueRetries < 10 ? 'healthy' : 'warning',
            'message' => $overdueRetries == 0 ? 'Nenhum retry atrasado' : "{$overdueRetries} retries atrasados",
            'overdue_count' => $overdueRetries
        ];

        if ($overdueRetries >= 50) {
            $health['overall_status'] = 'degraded';
        }

    } catch (Exception $e) {
        $health['checks']['retry_system'] = [
            'status' => 'unhealthy',
            'message' => 'Erro no sistema de retry: ' . $e->getMessage()
        ];
        $health['overall_status'] = 'unhealthy';
    }

    return $health;
}

/**
 * Obter logs recentes do sistema
 */
function getRecentLogs($limit = 50) {
    try {
        $db = Database::getConnection();

        // Verificar se tabela de retry exists
        $stmt = $db->prepare("SHOW TABLES LIKE 'cashback_notification_retries'");
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            return [
                'logs' => [],
                'message' => 'Tabela de logs não encontrada'
            ];
        }

        // Buscar logs recentes
        $stmt = $db->prepare("
            SELECT r.*, t.valor_total, t.valor_cliente, u.nome, l.nome_fantasia as loja_nome
            FROM cashback_notification_retries r
            INNER JOIN transacoes_cashback t ON r.transaction_id = t.id
            INNER JOIN usuarios u ON t.usuario_id = u.id
            INNER JOIN lojas l ON t.loja_id = l.id
            ORDER BY r.updated_at DESC
            LIMIT :limit
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'logs' => $logs,
            'count' => count($logs)
        ];

    } catch (Exception $e) {
        return [
            'logs' => [],
            'error' => 'Erro ao obter logs: ' . $e->getMessage()
        ];
    }
}
?>