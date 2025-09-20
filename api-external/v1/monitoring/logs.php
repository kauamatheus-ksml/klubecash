<?php
/**
 * KlubeCash API - Sistema de Logs Avançado
 * 
 * Visualizador e analisador de logs da API
 */

require_once '../../../config/database.php';

class LogsManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getConnection();
        $this->createLogsTable();
    }
    
    /**
     * Criar tabela de logs se não existir
     */
    private function createLogsTable() {
        $sql = "
            CREATE TABLE IF NOT EXISTS api_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                endpoint VARCHAR(255) NOT NULL,
                method ENUM('GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS') NOT NULL,
                status_code INT NOT NULL,
                response_time INT NOT NULL COMMENT 'em milissegundos',
                api_key_id INT NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT NULL,
                request_size INT DEFAULT 0,
                response_size INT DEFAULT 0,
                error_message TEXT NULL,
                request_data JSON NULL,
                response_data JSON NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_endpoint (endpoint),
                INDEX idx_status (status_code),
                INDEX idx_created_at (created_at),
                INDEX idx_api_key (api_key_id),
                FOREIGN KEY (api_key_id) REFERENCES api_keys(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        try {
            $this->db->exec($sql);
        } catch (Exception $e) {
            // Tabela pode já existir, ignorar erro
        }
    }
    
    /**
     * Inserir log de requisição
     */
    public function logRequest($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO api_logs (
                    endpoint, method, status_code, response_time, api_key_id,
                    ip_address, user_agent, request_size, response_size,
                    error_message, request_data, response_data
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['endpoint'] ?? '',
                $data['method'] ?? 'GET',
                $data['status_code'] ?? 200,
                $data['response_time'] ?? 0,
                $data['api_key_id'] ?? null,
                $data['ip_address'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $data['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '',
                $data['request_size'] ?? 0,
                $data['response_size'] ?? 0,
                $data['error_message'] ?? null,
                $data['request_data'] ? json_encode($data['request_data']) : null,
                $data['response_data'] ? json_encode($data['response_data']) : null
            ]);
            
            return $this->db->lastInsertId();
            
        } catch (Exception $e) {
            error_log("Erro ao inserir log: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter logs com filtros
     */
    public function getLogs($filters = [], $limit = 100, $offset = 0) {
        try {
            $where = ['1=1'];
            $params = [];
            
            // Filtros
            if (!empty($filters['endpoint'])) {
                $where[] = 'endpoint LIKE ?';
                $params[] = '%' . $filters['endpoint'] . '%';
            }
            
            if (!empty($filters['method'])) {
                $where[] = 'method = ?';
                $params[] = $filters['method'];
            }
            
            if (!empty($filters['status_code'])) {
                $where[] = 'status_code = ?';
                $params[] = $filters['status_code'];
            }
            
            if (!empty($filters['api_key_id'])) {
                $where[] = 'api_key_id = ?';
                $params[] = $filters['api_key_id'];
            }
            
            if (!empty($filters['date_from'])) {
                $where[] = 'created_at >= ?';
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $where[] = 'created_at <= ?';
                $params[] = $filters['date_to'];
            }
            
            if (!empty($filters['ip_address'])) {
                $where[] = 'ip_address = ?';
                $params[] = $filters['ip_address'];
            }
            
            // Query principal
            $sql = "
                SELECT l.*, ak.partner_name, ak.partner_email
                FROM api_logs l
                LEFT JOIN api_keys ak ON l.api_key_id = ak.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY l.created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Erro ao obter logs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter estatísticas de logs
     */
    public function getLogStats($period = '24h') {
        try {
            $whereClause = $this->getPeriodWhereClause($period);
            
            $stats = [];
            
            // Total de requests
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM api_logs WHERE $whereClause");
            $stmt->execute();
            $stats['total_requests'] = $stmt->fetch()['total'] ?? 0;
            
            // Requests por status
            $stmt = $this->db->prepare("
                SELECT status_code, COUNT(*) as count 
                FROM api_logs 
                WHERE $whereClause
                GROUP BY status_code
                ORDER BY count DESC
            ");
            $stmt->execute();
            $stats['by_status'] = $stmt->fetchAll();
            
            // Requests por endpoint
            $stmt = $this->db->prepare("
                SELECT endpoint, COUNT(*) as count 
                FROM api_logs 
                WHERE $whereClause
                GROUP BY endpoint
                ORDER BY count DESC
                LIMIT 10
            ");
            $stmt->execute();
            $stats['by_endpoint'] = $stmt->fetchAll();
            
            // Tempo médio de resposta
            $stmt = $this->db->prepare("
                SELECT AVG(response_time) as avg_time,
                       MIN(response_time) as min_time,
                       MAX(response_time) as max_time
                FROM api_logs 
                WHERE $whereClause
            ");
            $stmt->execute();
            $stats['response_times'] = $stmt->fetch();
            
            // Top IPs
            $stmt = $this->db->prepare("
                SELECT ip_address, COUNT(*) as count 
                FROM api_logs 
                WHERE $whereClause
                GROUP BY ip_address
                ORDER BY count DESC
                LIMIT 10
            ");
            $stmt->execute();
            $stats['top_ips'] = $stmt->fetchAll();
            
            // Requests por hora (últimas 24h)
            $stmt = $this->db->prepare("
                SELECT HOUR(created_at) as hour, COUNT(*) as count
                FROM api_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY HOUR(created_at)
                ORDER BY hour
            ");
            $stmt->execute();
            $stats['hourly_requests'] = $stmt->fetchAll();
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Erro ao obter estatísticas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter cláusula WHERE para período
     */
    private function getPeriodWhereClause($period) {
        switch ($period) {
            case '1h':
                return "created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
            case '24h':
                return "created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            case '7d':
                return "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            case '30d':
                return "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            default:
                return "created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        }
    }
    
    /**
     * Gerar logs de exemplo para demonstração
     */
    public function generateSampleLogs($count = 100) {
        $endpoints = [
            '/users', '/stores', '/cashback/calculate', 
            '/auth/info', '/auth/health'
        ];
        
        $methods = ['GET', 'POST'];
        $statuses = [200, 200, 200, 200, 200, 400, 401, 404, 500];
        $ips = [
            '192.168.1.10', '192.168.1.20', '192.168.1.30',
            '10.0.0.5', '10.0.0.15', '172.16.0.10'
        ];
        
        for ($i = 0; $i < $count; $i++) {
            $endpoint = $endpoints[array_rand($endpoints)];
            $method = $methods[array_rand($methods)];
            $status = $statuses[array_rand($statuses)];
            $ip = $ips[array_rand($ips)];
            $responseTime = rand(50, 1000);
            
            // Timestamp aleatório nas últimas 24 horas
            $timestamp = date('Y-m-d H:i:s', strtotime('-' . rand(0, 1440) . ' minutes'));
            
            $this->db->prepare("
                INSERT INTO api_logs (
                    endpoint, method, status_code, response_time, api_key_id,
                    ip_address, user_agent, request_size, response_size, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $endpoint,
                $method,
                $status,
                $responseTime,
                rand(1, 3),
                $ip,
                'KlubeCash-Test-Agent/1.0',
                rand(100, 5000),
                rand(500, 10000),
                $timestamp
            ]);
        }
    }
}

// Handle AJAX requests
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    
    header('Content-Type: application/json');
    
    $logsManager = new LogsManager();
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'logs':
            $filters = [
                'endpoint' => $_GET['endpoint'] ?? '',
                'method' => $_GET['method'] ?? '',
                'status_code' => $_GET['status_code'] ?? '',
                'date_from' => $_GET['date_from'] ?? '',
                'date_to' => $_GET['date_to'] ?? '',
                'ip_address' => $_GET['ip_address'] ?? ''
            ];
            
            $limit = min(intval($_GET['limit'] ?? 100), 1000);
            $offset = intval($_GET['offset'] ?? 0);
            
            $logs = $logsManager->getLogs($filters, $limit, $offset);
            echo json_encode(['success' => true, 'logs' => $logs]);
            break;
            
        case 'stats':
            $period = $_GET['period'] ?? '24h';
            $stats = $logsManager->getLogStats($period);
            echo json_encode(['success' => true, 'stats' => $stats]);
            break;
            
        case 'generate':
            $count = min(intval($_GET['count'] ?? 100), 1000);
            $logsManager->generateSampleLogs($count);
            echo json_encode(['success' => true, 'message' => "$count logs gerados"]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida']);
    }
    
    exit;
}

$logsManager = new LogsManager();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KlubeCash API - Logs</title>
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
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 2rem 0;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .container {
            max-width: 1400px;
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

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 0.3rem;
            color: #555;
        }

        .form-group input,
        .form-group select {
            padding: 0.5rem;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            font-size: 0.9rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #28a745;
        }

        .btn {
            background: #28a745;
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
            background: #218838;
            transform: translateY(-1px);
        }

        .btn.secondary {
            background: #6c757d;
        }

        .btn.secondary:hover {
            background: #5a6268;
        }

        .btn.danger {
            background: #dc3545;
        }

        .btn.danger:hover {
            background: #c82333;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

        .stat-card h4 {
            color: #28a745;
            margin-bottom: 1rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .logs-table-container {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        .logs-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .logs-table th,
        .logs-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
            font-size: 0.9rem;
        }

        .logs-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
            position: sticky;
            top: 0;
        }

        .logs-table tr:hover {
            background: #f8f9fa;
        }

        .status-code {
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-200 { background: #d4edda; color: #155724; }
        .status-400 { background: #fff3cd; color: #856404; }
        .status-401 { background: #f8d7da; color: #721c24; }
        .status-404 { background: #f8d7da; color: #721c24; }
        .status-500 { background: #f8d7da; color: #721c24; }

        .method-badge {
            padding: 0.2rem 0.5rem;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .method-GET { background: #007bff; color: white; }
        .method-POST { background: #28a745; color: white; }
        .method-PUT { background: #ffc107; color: black; }
        .method-DELETE { background: #dc3545; color: white; }

        .response-time {
            font-family: 'Monaco', 'Consolas', monospace;
        }

        .response-time.fast { color: #28a745; }
        .response-time.medium { color: #ffc107; }
        .response-time.slow { color: #dc3545; }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin-top: 2rem;
        }

        .pagination button {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 5px;
        }

        .pagination button:hover {
            background: #f8f9fa;
        }

        .pagination button:disabled {
            cursor: not-allowed;
            opacity: 0.5;
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
            border-top: 3px solid #28a745;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 0.5rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .log-detail {
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 0.8rem;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            cursor: pointer;
        }

        .log-detail:hover {
            text-decoration: underline;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 10px;
            width: 80%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .close {
            font-size: 2rem;
            cursor: pointer;
            color: #999;
        }

        .close:hover {
            color: #333;
        }

        .json-viewer {
            background: #2d3748;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 5px;
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 0.9rem;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📄 Sistema de Logs</h1>
        <p>Análise detalhada de requisições da API</p>
    </div>

    <div class="container">
        <!-- Controls -->
        <div class="controls">
            <h3>🎛️ Controles</h3>
            
            <!-- Period Selection -->
            <div style="margin-bottom: 1rem;">
                <button class="btn" onclick="loadStats('1h')">Última Hora</button>
                <button class="btn" onclick="loadStats('24h')">Últimas 24h</button>
                <button class="btn" onclick="loadStats('7d')">Últimos 7 dias</button>
                <button class="btn" onclick="loadStats('30d')">Últimos 30 dias</button>
            </div>

            <!-- Filters -->
            <div class="filters">
                <div class="form-group">
                    <label>Endpoint:</label>
                    <input type="text" id="filter-endpoint" placeholder="/users, /stores...">
                </div>
                <div class="form-group">
                    <label>Método:</label>
                    <select id="filter-method">
                        <option value="">Todos</option>
                        <option value="GET">GET</option>
                        <option value="POST">POST</option>
                        <option value="PUT">PUT</option>
                        <option value="DELETE">DELETE</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status Code:</label>
                    <select id="filter-status">
                        <option value="">Todos</option>
                        <option value="200">200 (OK)</option>
                        <option value="400">400 (Bad Request)</option>
                        <option value="401">401 (Unauthorized)</option>
                        <option value="404">404 (Not Found)</option>
                        <option value="500">500 (Server Error)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>IP Address:</label>
                    <input type="text" id="filter-ip" placeholder="192.168.1.1">
                </div>
                <div class="form-group">
                    <label>Data de/até:</label>
                    <input type="datetime-local" id="filter-date-from">
                    <input type="datetime-local" id="filter-date-to" style="margin-top: 0.5rem;">
                </div>
            </div>

            <button class="btn" onclick="loadLogs()">🔍 Aplicar Filtros</button>
            <button class="btn secondary" onclick="clearFilters()">🧹 Limpar</button>
            <button class="btn secondary" onclick="exportLogs()">📥 Exportar</button>
            <button class="btn danger" onclick="generateSampleLogs()">🎲 Gerar Dados de Teste</button>
        </div>

        <!-- Statistics -->
        <div class="stats-grid" id="stats-container">
            <div class="stat-card">
                <h4>Total de Requests</h4>
                <div class="stat-value" id="stat-total">-</div>
                <div class="stat-label">Período selecionado</div>
            </div>
            <div class="stat-card">
                <h4>Tempo Médio</h4>
                <div class="stat-value" id="stat-avg-time">-</div>
                <div class="stat-label">Milissegundos</div>
            </div>
            <div class="stat-card">
                <h4>Taxa de Sucesso</h4>
                <div class="stat-value" id="stat-success-rate">-</div>
                <div class="stat-label">Requisições 2xx</div>
            </div>
            <div class="stat-card">
                <h4>Top Endpoint</h4>
                <div class="stat-value" id="stat-top-endpoint">-</div>
                <div class="stat-label">Mais requisitado</div>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="logs-table-container">
            <h3>📋 Logs de Requisições</h3>
            <div id="logs-content">
                <div class="loading">Carregando logs</div>
            </div>
        </div>
    </div>

    <!-- Modal for log details -->
    <div id="log-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>📄 Detalhes do Log</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div id="modal-body"></div>
        </div>
    </div>

    <script>
        let currentPage = 0;
        let currentPeriod = '24h';
        const logsPerPage = 50;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadStats('24h');
            loadLogs();
        });

        // Load statistics
        async function loadStats(period) {
            currentPeriod = period;
            
            try {
                const response = await fetch(`?action=stats&period=${period}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                const data = await response.json();
                
                if (data.success && data.stats) {
                    displayStats(data.stats);
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        // Display statistics
        function displayStats(stats) {
            document.getElementById('stat-total').textContent = stats.total_requests.toLocaleString();
            
            const avgTime = Math.round(stats.response_times.avg_time || 0);
            document.getElementById('stat-avg-time').textContent = avgTime + 'ms';
            
            // Success rate calculation
            let successRequests = 0;
            stats.by_status.forEach(status => {
                if (status.status_code >= 200 && status.status_code < 300) {
                    successRequests += parseInt(status.count);
                }
            });
            
            const successRate = stats.total_requests > 0 
                ? ((successRequests / stats.total_requests) * 100).toFixed(1) 
                : 0;
            document.getElementById('stat-success-rate').textContent = successRate + '%';
            
            // Top endpoint
            const topEndpoint = stats.by_endpoint[0]?.endpoint || '-';
            document.getElementById('stat-top-endpoint').textContent = topEndpoint;
        }

        // Load logs
        async function loadLogs(page = 0) {
            const logsContent = document.getElementById('logs-content');
            logsContent.innerHTML = '<div class="loading">Carregando logs</div>';
            
            try {
                const filters = getFilters();
                const params = new URLSearchParams({
                    action: 'logs',
                    limit: logsPerPage,
                    offset: page * logsPerPage,
                    ...filters
                });
                
                const response = await fetch(`?${params}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    displayLogs(data.logs);
                    currentPage = page;
                } else {
                    logsContent.innerHTML = '<div class="loading">Erro ao carregar logs</div>';
                }
            } catch (error) {
                console.error('Error loading logs:', error);
                logsContent.innerHTML = '<div class="loading">Erro de conexão</div>';
            }
        }

        // Get current filters
        function getFilters() {
            return {
                endpoint: document.getElementById('filter-endpoint').value,
                method: document.getElementById('filter-method').value,
                status_code: document.getElementById('filter-status').value,
                ip_address: document.getElementById('filter-ip').value,
                date_from: document.getElementById('filter-date-from').value,
                date_to: document.getElementById('filter-date-to').value
            };
        }

        // Display logs
        function displayLogs(logs) {
            const logsContent = document.getElementById('logs-content');
            
            if (logs.length === 0) {
                logsContent.innerHTML = '<div class="loading">Nenhum log encontrado</div>';
                return;
            }

            let html = `
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Método</th>
                            <th>Endpoint</th>
                            <th>Status</th>
                            <th>Tempo</th>
                            <th>IP</th>
                            <th>Parceiro</th>
                            <th>Tamanhos</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            logs.forEach(log => {
                const responseTimeClass = log.response_time < 100 ? 'fast' : 
                                        log.response_time < 500 ? 'medium' : 'slow';
                
                const timestamp = new Date(log.created_at).toLocaleString();
                const partner = log.partner_name || 'N/A';
                const requestSize = formatBytes(log.request_size);
                const responseSize = formatBytes(log.response_size);

                html += `
                    <tr>
                        <td style="font-family: Monaco, monospace; font-size: 0.8rem;">${timestamp}</td>
                        <td><span class="method-badge method-${log.method}">${log.method}</span></td>
                        <td><code>${log.endpoint}</code></td>
                        <td><span class="status-code status-${log.status_code}">${log.status_code}</span></td>
                        <td><span class="response-time ${responseTimeClass}">${log.response_time}ms</span></td>
                        <td><code>${log.ip_address}</code></td>
                        <td>${partner}</td>
                        <td>
                            <small>Req: ${requestSize}<br>Res: ${responseSize}</small>
                        </td>
                        <td>
                            <button class="btn" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;" 
                                    onclick="showLogDetails(${log.id})">
                                Detalhes
                            </button>
                        </td>
                    </tr>
                `;
            });

            html += `
                    </tbody>
                </table>
                <div class="pagination">
                    <button onclick="loadLogs(${currentPage - 1})" ${currentPage === 0 ? 'disabled' : ''}>
                        ← Anterior
                    </button>
                    <span>Página ${currentPage + 1}</span>
                    <button onclick="loadLogs(${currentPage + 1})" ${logs.length < logsPerPage ? 'disabled' : ''}>
                        Próxima →
                    </button>
                </div>
            `;

            logsContent.innerHTML = html;
        }

        // Show log details
        function showLogDetails(logId) {
            // For demo purposes, show a mock detail
            const modal = document.getElementById('log-modal');
            const modalBody = document.getElementById('modal-body');
            
            modalBody.innerHTML = `
                <div style="margin-bottom: 1rem;">
                    <h4>📊 Informações Básicas</h4>
                    <p><strong>ID:</strong> ${logId}</p>
                    <p><strong>Endpoint:</strong> /cashback/calculate</p>
                    <p><strong>Método:</strong> POST</p>
                    <p><strong>Status:</strong> 200</p>
                    <p><strong>Tempo de Resposta:</strong> 245ms</p>
                    <p><strong>IP:</strong> 192.168.1.10</p>
                    <p><strong>User Agent:</strong> KlubeCash-SDK/1.0</p>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <h4>📤 Request Data</h4>
                    <div class="json-viewer">{
  "store_id": 59,
  "amount": 100.00
}</div>
                </div>
                
                <div>
                    <h4>📥 Response Data</h4>
                    <div class="json-viewer">{
  "success": true,
  "data": {
    "store_id": 59,
    "purchase_amount": 100,
    "store_cashback_percentage": 10,
    "cashback_calculation": {
      "total_cashback": 10,
      "client_receives": 0.5,
      "admin_receives": 0.5,
      "store_receives": 0
    }
  }
}</div>
                </div>
            `;
            
            modal.style.display = 'block';
        }

        // Close modal
        function closeModal() {
            document.getElementById('log-modal').style.display = 'none';
        }

        // Clear filters
        function clearFilters() {
            document.getElementById('filter-endpoint').value = '';
            document.getElementById('filter-method').value = '';
            document.getElementById('filter-status').value = '';
            document.getElementById('filter-ip').value = '';
            document.getElementById('filter-date-from').value = '';
            document.getElementById('filter-date-to').value = '';
            loadLogs(0);
        }

        // Export logs
        function exportLogs() {
            alert('Funcionalidade de exportação será implementada em breve!');
        }

        // Generate sample logs
        async function generateSampleLogs() {
            if (!confirm('Isso irá gerar 200 logs de exemplo. Continuar?')) {
                return;
            }

            try {
                const response = await fetch('?action=generate&count=200', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Logs de exemplo gerados com sucesso!');
                    loadStats(currentPeriod);
                    loadLogs(0);
                } else {
                    alert('Erro ao gerar logs: ' + data.message);
                }
            } catch (error) {
                console.error('Error generating logs:', error);
                alert('Erro de conexão');
            }
        }

        // Format bytes
        function formatBytes(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('log-modal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Auto-refresh every 30 seconds
        setInterval(() => {
            if (document.visibilityState === 'visible') {
                loadStats(currentPeriod);
                loadLogs(currentPage);
            }
        }, 30000);
    </script>
</body>
</html>