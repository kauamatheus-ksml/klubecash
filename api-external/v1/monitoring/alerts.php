<?php
/**
 * KlubeCash API - Sistema de Alertas
 * 
 * Sistema automatizado de alertas para monitoramento da API
 */

require_once '../../../config/database.php';

class AlertManager {
    private $db;
    private $alertRules;
    private $notifications;
    
    public function __construct() {
        $this->db = Database::getConnection();
        $this->initializeAlertRules();
        $this->notifications = [];
    }
    
    /**
     * Definir regras de alerta
     */
    private function initializeAlertRules() {
        $this->alertRules = [
            'high_error_rate' => [
                'name' => 'Taxa de Erro Alta',
                'threshold' => 5.0, // 5% de erro
                'check_interval' => 300, // 5 minutos
                'severity' => 'critical'
            ],
            'slow_response_time' => [
                'name' => 'Tempo de Resposta Lento',
                'threshold' => 1000, // 1 segundo
                'check_interval' => 300,
                'severity' => 'warning'
            ],
            'high_request_volume' => [
                'name' => 'Volume Alto de Requests',
                'threshold' => 1000, // requests por hora
                'check_interval' => 3600, // 1 hora
                'severity' => 'info'
            ],
            'api_key_expired' => [
                'name' => 'API Key Expirando',
                'threshold' => 7, // dias
                'check_interval' => 86400, // 24 horas
                'severity' => 'warning'
            ],
            'database_connection' => [
                'name' => 'Problemas de Conexão com Database',
                'threshold' => 1,
                'check_interval' => 60, // 1 minuto
                'severity' => 'critical'
            ]
        ];
    }
    
    /**
     * Verificar todos os alertas
     */
    public function checkAllAlerts() {
        $alerts = [];
        
        foreach ($this->alertRules as $rule => $config) {
            try {
                $result = $this->checkAlert($rule, $config);
                if ($result) {
                    $alerts[] = $result;
                }
            } catch (Exception $e) {
                error_log("Erro ao verificar alerta {$rule}: " . $e->getMessage());
            }
        }
        
        return $alerts;
    }
    
    /**
     * Verificar um alerta específico
     */
    private function checkAlert($ruleId, $config) {
        switch ($ruleId) {
            case 'high_error_rate':
                return $this->checkErrorRate($config);
                
            case 'slow_response_time':
                return $this->checkResponseTime($config);
                
            case 'high_request_volume':
                return $this->checkRequestVolume($config);
                
            case 'api_key_expired':
                return $this->checkApiKeyExpiration($config);
                
            case 'database_connection':
                return $this->checkDatabaseConnection($config);
                
            default:
                return null;
        }
    }
    
    /**
     * Verificar taxa de erro
     */
    private function checkErrorRate($config) {
        // Simular verificação (em produção, buscaria dados reais)
        $errorRate = rand(1, 10); // 1-10%
        
        if ($errorRate > $config['threshold']) {
            return [
                'rule_id' => 'high_error_rate',
                'name' => $config['name'],
                'severity' => $config['severity'],
                'message' => "Taxa de erro atual: {$errorRate}% (limite: {$config['threshold']}%)",
                'value' => $errorRate,
                'threshold' => $config['threshold'],
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        return null;
    }
    
    /**
     * Verificar tempo de resposta
     */
    private function checkResponseTime($config) {
        // Simular verificação
        $avgResponseTime = rand(200, 1500);
        
        if ($avgResponseTime > $config['threshold']) {
            return [
                'rule_id' => 'slow_response_time',
                'name' => $config['name'],
                'severity' => $config['severity'],
                'message' => "Tempo médio de resposta: {$avgResponseTime}ms (limite: {$config['threshold']}ms)",
                'value' => $avgResponseTime,
                'threshold' => $config['threshold'],
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        return null;
    }
    
    /**
     * Verificar volume de requests
     */
    private function checkRequestVolume($config) {
        // Simular verificação
        $requestsLastHour = rand(500, 1500);
        
        if ($requestsLastHour > $config['threshold']) {
            return [
                'rule_id' => 'high_request_volume',
                'name' => $config['name'],
                'severity' => $config['severity'],
                'message' => "Volume de requests na última hora: {$requestsLastHour} (limite: {$config['threshold']})",
                'value' => $requestsLastHour,
                'threshold' => $config['threshold'],
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        return null;
    }
    
    /**
     * Verificar expiração de API Keys
     */
    private function checkApiKeyExpiration($config) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM api_keys 
                WHERE expires_at IS NOT NULL 
                AND expires_at <= DATE_ADD(NOW(), INTERVAL ? DAY)
                AND is_active = 1
            ");
            
            $stmt->execute([$config['threshold']]);
            $result = $stmt->fetch();
            $expiringKeys = $result['count'] ?? 0;
            
            if ($expiringKeys > 0) {
                return [
                    'rule_id' => 'api_key_expired',
                    'name' => $config['name'],
                    'severity' => $config['severity'],
                    'message' => "{$expiringKeys} API Key(s) expirando nos próximos {$config['threshold']} dias",
                    'value' => $expiringKeys,
                    'threshold' => $config['threshold'],
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }
            
        } catch (Exception $e) {
            // Se não conseguir verificar, criar alerta
            return [
                'rule_id' => 'api_key_expired',
                'name' => $config['name'],
                'severity' => 'error',
                'message' => "Erro ao verificar expiração de API Keys: " . $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        return null;
    }
    
    /**
     * Verificar conexão com database
     */
    private function checkDatabaseConnection($config) {
        try {
            $startTime = microtime(true);
            $stmt = $this->db->prepare("SELECT 1");
            $stmt->execute();
            $responseTime = (microtime(true) - $startTime) * 1000;
            
            // Se demorou mais de 1 segundo, criar alerta
            if ($responseTime > 1000) {
                return [
                    'rule_id' => 'database_connection',
                    'name' => $config['name'],
                    'severity' => 'warning',
                    'message' => "Conexão com database lenta: {$responseTime}ms",
                    'value' => $responseTime,
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }
            
        } catch (Exception $e) {
            return [
                'rule_id' => 'database_connection',
                'name' => $config['name'],
                'severity' => 'critical',
                'message' => "Falha na conexão com database: " . $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        return null;
    }
    
    /**
     * Enviar alertas por email (simulado)
     */
    public function sendAlert($alert) {
        // Em produção, enviaria email real
        $emailBody = $this->formatAlertEmail($alert);
        
        // Simular envio de email
        error_log("ALERT EMAIL SENT: " . $alert['name'] . " - " . $alert['message']);
        
        return [
            'sent' => true,
            'method' => 'email',
            'recipient' => 'admin@klubecash.com',
            'subject' => "ALERTA: " . $alert['name'],
            'body' => $emailBody
        ];
    }
    
    /**
     * Formatar email de alerta
     */
    private function formatAlertEmail($alert) {
        $severityEmoji = [
            'critical' => '🔴',
            'warning' => '⚠️',
            'info' => 'ℹ️',
            'error' => '❌'
        ];
        
        $emoji = $severityEmoji[$alert['severity']] ?? '📊';
        
        return "
        {$emoji} ALERTA KLUBECASH API {$emoji}
        
        Regra: {$alert['name']}
        Severidade: " . strtoupper($alert['severity']) . "
        Mensagem: {$alert['message']}
        Timestamp: {$alert['timestamp']}
        
        Para mais detalhes, acesse:
        https://klubecash.com/api-external/v1/monitoring/
        
        ---
        Sistema de Monitoramento KlubeCash API
        ";
    }
    
    /**
     * Salvar alerta no banco
     */
    public function saveAlert($alert) {
        try {
            // Criar tabela se não existir
            $this->createAlertsTable();
            
            $stmt = $this->db->prepare("
                INSERT INTO api_alerts (
                    rule_id, name, severity, message, alert_value, 
                    threshold_value, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $alert['rule_id'],
                $alert['name'],
                $alert['severity'],
                $alert['message'],
                $alert['value'] ?? null,
                $alert['threshold'] ?? null,
                $alert['timestamp']
            ]);
            
            return $this->db->lastInsertId();
            
        } catch (Exception $e) {
            error_log("Erro ao salvar alerta: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Criar tabela de alertas
     */
    private function createAlertsTable() {
        $sql = "
            CREATE TABLE IF NOT EXISTS api_alerts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                rule_id VARCHAR(100) NOT NULL,
                name VARCHAR(255) NOT NULL,
                severity ENUM('critical', 'warning', 'info', 'error') NOT NULL,
                message TEXT NOT NULL,
                alert_value DECIMAL(10,2) NULL,
                threshold_value DECIMAL(10,2) NULL,
                acknowledged BOOLEAN DEFAULT FALSE,
                acknowledged_at DATETIME NULL,
                acknowledged_by VARCHAR(100) NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_rule_id (rule_id),
                INDEX idx_severity (severity),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $this->db->exec($sql);
    }
    
    /**
     * Obter alertas recentes
     */
    public function getRecentAlerts($limit = 50) {
        try {
            $this->createAlertsTable();
            
            $stmt = $this->db->prepare("
                SELECT * FROM api_alerts 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Erro ao obter alertas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Reconhecer alerta
     */
    public function acknowledgeAlert($alertId, $acknowledgedBy = 'admin') {
        try {
            $stmt = $this->db->prepare("
                UPDATE api_alerts 
                SET acknowledged = TRUE, 
                    acknowledged_at = NOW(),
                    acknowledged_by = ?
                WHERE id = ?
            ");
            
            return $stmt->execute([$acknowledgedBy, $alertId]);
            
        } catch (Exception $e) {
            error_log("Erro ao reconhecer alerta: " . $e->getMessage());
            return false;
        }
    }
}

// Se for executado diretamente (cron job)
if (php_sapi_name() === 'cli') {
    echo "=== KLUBECASH API ALERT CHECKER ===\n";
    echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";
    
    $alertManager = new AlertManager();
    $alerts = $alertManager->checkAllAlerts();
    
    if (empty($alerts)) {
        echo "✅ Nenhum alerta detectado.\n";
    } else {
        echo "⚠️ " . count($alerts) . " alerta(s) detectado(s):\n\n";
        
        foreach ($alerts as $alert) {
            echo "- [{$alert['severity']}] {$alert['name']}: {$alert['message']}\n";
            
            // Salvar no banco
            $alertId = $alertManager->saveAlert($alert);
            
            // Enviar por email se for crítico
            if ($alert['severity'] === 'critical') {
                $emailResult = $alertManager->sendAlert($alert);
                echo "  Email enviado: " . ($emailResult['sent'] ? 'Sim' : 'Não') . "\n";
            }
        }
    }
    
    echo "\n=== FIM DA VERIFICAÇÃO ===\n";
    exit;
}

// Se for requisição web
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    $alertManager = new AlertManager();
    $action = $_GET['action'];
    
    switch ($action) {
        case 'check':
            $alerts = $alertManager->checkAllAlerts();
            echo json_encode([
                'success' => true,
                'alerts' => $alerts,
                'count' => count($alerts)
            ]);
            break;
            
        case 'recent':
            $alerts = $alertManager->getRecentAlerts();
            echo json_encode([
                'success' => true,
                'alerts' => $alerts
            ]);
            break;
            
        case 'acknowledge':
            $alertId = $_POST['alert_id'] ?? null;
            $acknowledgedBy = $_POST['acknowledged_by'] ?? 'web_user';
            
            if ($alertId) {
                $result = $alertManager->acknowledgeAlert($alertId, $acknowledgedBy);
                echo json_encode([
                    'success' => $result,
                    'message' => $result ? 'Alerta reconhecido' : 'Erro ao reconhecer alerta'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID do alerta não fornecido'
                ]);
            }
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Ação inválida'
            ]);
    }
    
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KlubeCash API - Alertas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 2rem 0;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .controls {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            margin-right: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        .btn.secondary {
            background: #6c757d;
        }

        .btn.secondary:hover {
            background: #5a6268;
        }

        .alert-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border-left: 5px solid;
        }

        .alert-card.critical {
            border-left-color: #dc3545;
        }

        .alert-card.warning {
            border-left-color: #ffc107;
        }

        .alert-card.info {
            border-left-color: #17a2b8;
        }

        .alert-card.error {
            border-left-color: #fd7e14;
        }

        .alert-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .alert-title {
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .severity-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .severity-critical {
            background: #dc3545;
            color: white;
        }

        .severity-warning {
            background: #ffc107;
            color: #212529;
        }

        .severity-info {
            background: #17a2b8;
            color: white;
        }

        .severity-error {
            background: #fd7e14;
            color: white;
        }

        .alert-message {
            font-size: 1rem;
            margin-bottom: 1rem;
            color: #555;
        }

        .alert-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: #666;
        }

        .alert-timestamp {
            font-family: 'Monaco', 'Consolas', monospace;
        }

        .acknowledge-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 0.4rem 0.8rem;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.8rem;
        }

        .acknowledge-btn:hover {
            background: #218838;
        }

        .acknowledge-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        .acknowledged {
            opacity: 0.6;
        }

        .acknowledged .acknowledge-btn {
            display: none;
        }

        .no-alerts {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .no-alerts .emoji {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .loading::after {
            content: '';
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #dc3545;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 0.5rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #dc3545;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚨 Sistema de Alertas</h1>
        <p>Monitoramento e notificações da API</p>
    </div>

    <div class="container">
        <div class="controls">
            <button class="btn" onclick="checkAlerts()">🔍 Verificar Alertas</button>
            <button class="btn secondary" onclick="loadRecentAlerts()">📋 Alertas Recentes</button>
            <button class="btn secondary" onclick="acknowledgeAllAlerts()">✅ Reconhecer Todos</button>
        </div>

        <div class="stats" id="alert-stats" style="display: none;">
            <div class="stat-card">
                <div class="stat-value" id="total-alerts">0</div>
                <div class="stat-label">Total de Alertas</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="critical-alerts">0</div>
                <div class="stat-label">Críticos</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="warning-alerts">0</div>
                <div class="stat-label">Avisos</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="unacknowledged-alerts">0</div>
                <div class="stat-label">Não Reconhecidos</div>
            </div>
        </div>

        <div id="alerts-container">
            <div class="no-alerts">
                <span class="emoji">🕐</span>
                Clique em "Verificar Alertas" para começar o monitoramento
            </div>
        </div>
    </div>

    <script>
        let currentAlerts = [];

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadRecentAlerts();
        });

        // Check for new alerts
        async function checkAlerts() {
            const container = document.getElementById('alerts-container');
            container.innerHTML = '<div class="loading">Verificando alertas</div>';

            try {
                const response = await fetch('?action=check');
                const data = await response.json();

                if (data.success) {
                    currentAlerts = data.alerts;
                    displayAlerts(currentAlerts);
                    updateStats(currentAlerts);
                } else {
                    container.innerHTML = '<div class="no-alerts"><span class="emoji">❌</span>Erro ao verificar alertas</div>';
                }
            } catch (error) {
                console.error('Error checking alerts:', error);
                container.innerHTML = '<div class="no-alerts"><span class="emoji">❌</span>Erro de conexão</div>';
            }
        }

        // Load recent alerts from database
        async function loadRecentAlerts() {
            const container = document.getElementById('alerts-container');
            container.innerHTML = '<div class="loading">Carregando alertas recentes</div>';

            try {
                const response = await fetch('?action=recent');
                const data = await response.json();

                if (data.success) {
                    displayRecentAlerts(data.alerts);
                    updateStatsFromRecent(data.alerts);
                } else {
                    container.innerHTML = '<div class="no-alerts"><span class="emoji">❌</span>Erro ao carregar alertas</div>';
                }
            } catch (error) {
                console.error('Error loading recent alerts:', error);
                container.innerHTML = '<div class="no-alerts"><span class="emoji">❌</span>Erro de conexão</div>';
            }
        }

        // Display alerts
        function displayAlerts(alerts) {
            const container = document.getElementById('alerts-container');

            if (alerts.length === 0) {
                container.innerHTML = `
                    <div class="no-alerts">
                        <span class="emoji">✅</span>
                        Nenhum alerta ativo no momento
                    </div>
                `;
                return;
            }

            let html = '';
            alerts.forEach(alert => {
                const severityEmoji = {
                    critical: '🔴',
                    warning: '⚠️',
                    info: 'ℹ️',
                    error: '❌'
                };

                html += `
                    <div class="alert-card ${alert.severity}">
                        <div class="alert-header">
                            <div class="alert-title">
                                ${severityEmoji[alert.severity] || '📊'}
                                ${alert.name}
                            </div>
                            <span class="severity-badge severity-${alert.severity}">
                                ${alert.severity}
                            </span>
                        </div>
                        <div class="alert-message">${alert.message}</div>
                        <div class="alert-meta">
                            <span class="alert-timestamp">${alert.timestamp}</span>
                            <button class="acknowledge-btn" onclick="acknowledgeAlert('${alert.rule_id}')">
                                Reconhecer
                            </button>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        // Display recent alerts from database
        function displayRecentAlerts(alerts) {
            const container = document.getElementById('alerts-container');

            if (alerts.length === 0) {
                container.innerHTML = `
                    <div class="no-alerts">
                        <span class="emoji">📭</span>
                        Nenhum alerta encontrado no histórico
                    </div>
                `;
                return;
            }

            let html = '';
            alerts.forEach(alert => {
                const severityEmoji = {
                    critical: '🔴',
                    warning: '⚠️',
                    info: 'ℹ️',
                    error: '❌'
                };

                const isAcknowledged = alert.acknowledged == 1;
                const acknowledgedClass = isAcknowledged ? 'acknowledged' : '';

                html += `
                    <div class="alert-card ${alert.severity} ${acknowledgedClass}">
                        <div class="alert-header">
                            <div class="alert-title">
                                ${severityEmoji[alert.severity] || '📊'}
                                ${alert.name}
                                ${isAcknowledged ? '✅' : ''}
                            </div>
                            <span class="severity-badge severity-${alert.severity}">
                                ${alert.severity}
                            </span>
                        </div>
                        <div class="alert-message">${alert.message}</div>
                        <div class="alert-meta">
                            <span class="alert-timestamp">${new Date(alert.created_at).toLocaleString()}</span>
                            <button class="acknowledge-btn" 
                                    onclick="acknowledgeAlertById(${alert.id})"
                                    ${isAcknowledged ? 'disabled' : ''}>
                                ${isAcknowledged ? 'Reconhecido' : 'Reconhecer'}
                            </button>
                        </div>
                        ${isAcknowledged ? `
                            <div style="margin-top: 1rem; font-size: 0.8rem; color: #666;">
                                Reconhecido por ${alert.acknowledged_by} em ${new Date(alert.acknowledged_at).toLocaleString()}
                            </div>
                        ` : ''}
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        // Update statistics
        function updateStats(alerts) {
            const statsContainer = document.getElementById('alert-stats');
            statsContainer.style.display = 'grid';

            const totalAlerts = alerts.length;
            const criticalAlerts = alerts.filter(a => a.severity === 'critical').length;
            const warningAlerts = alerts.filter(a => a.severity === 'warning').length;
            const unacknowledgedAlerts = totalAlerts; // New alerts are always unacknowledged

            document.getElementById('total-alerts').textContent = totalAlerts;
            document.getElementById('critical-alerts').textContent = criticalAlerts;
            document.getElementById('warning-alerts').textContent = warningAlerts;
            document.getElementById('unacknowledged-alerts').textContent = unacknowledgedAlerts;
        }

        // Update statistics from recent alerts
        function updateStatsFromRecent(alerts) {
            const statsContainer = document.getElementById('alert-stats');
            statsContainer.style.display = 'grid';

            const totalAlerts = alerts.length;
            const criticalAlerts = alerts.filter(a => a.severity === 'critical').length;
            const warningAlerts = alerts.filter(a => a.severity === 'warning').length;
            const unacknowledgedAlerts = alerts.filter(a => a.acknowledged == 0).length;

            document.getElementById('total-alerts').textContent = totalAlerts;
            document.getElementById('critical-alerts').textContent = criticalAlerts;
            document.getElementById('warning-alerts').textContent = warningAlerts;
            document.getElementById('unacknowledged-alerts').textContent = unacknowledgedAlerts;
        }

        // Acknowledge alert by ID
        async function acknowledgeAlertById(alertId) {
            try {
                const formData = new FormData();
                formData.append('alert_id', alertId);
                formData.append('acknowledged_by', 'web_admin');

                const response = await fetch('?action=acknowledge', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // Reload recent alerts to show updated status
                    loadRecentAlerts();
                } else {
                    alert('Erro ao reconhecer alerta: ' + data.message);
                }
            } catch (error) {
                console.error('Error acknowledging alert:', error);
                alert('Erro de conexão');
            }
        }

        // Acknowledge alert by rule (for new alerts)
        function acknowledgeAlert(ruleId) {
            // For new alerts, just hide the alert visually
            event.target.disabled = true;
            event.target.textContent = 'Reconhecido';
            event.target.closest('.alert-card').classList.add('acknowledged');
        }

        // Acknowledge all alerts
        function acknowledgeAllAlerts() {
            const acknowledgeButtons = document.querySelectorAll('.acknowledge-btn:not(:disabled)');
            acknowledgeButtons.forEach(button => {
                button.disabled = true;
                button.textContent = 'Reconhecido';
                button.closest('.alert-card').classList.add('acknowledged');
            });
        }

        // Auto-refresh every 2 minutes
        setInterval(() => {
            if (document.visibilityState === 'visible') {
                checkAlerts();
            }
        }, 120000);
    </script>
</body>
</html>