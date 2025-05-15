<?php
// views/stores/dashboard.php
// Incluir arquivos de configuração
require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/StoreController.php';
require_once '../../controllers/TransactionController.php';

// Iniciar sessão e verificar autenticação
session_start();

// Verificar se o usuário está logado
if (!AuthController::isAuthenticated()) {
    header('Location: ' . LOGIN_URL . '?error=' . urlencode('Você precisa fazer login para acessar esta página.'));
    exit;
}

// Verificar se o usuário é do tipo loja
if (!AuthController::isStore()) {
    header('Location: ' . CLIENT_DASHBOARD_URL . '?error=' . urlencode('Acesso restrito a lojas parceiras.'));
    exit;
}

// Obter ID do usuário logado
$userId = AuthController::getCurrentUserId();

// Obter dados da loja associada ao usuário
$db = Database::getConnection();
$storeQuery = $db->prepare("SELECT * FROM lojas WHERE usuario_id = :usuario_id");
$storeQuery->bindParam(':usuario_id', $userId);
$storeQuery->execute();

// Verificar se o usuário tem uma loja associada
if ($storeQuery->rowCount() == 0) {
    // Usuário é do tipo loja mas não tem loja associada - situação de erro
    header('Location: ' . LOGIN_URL . '?error=' . urlencode('Sua conta não está associada a nenhuma loja. Entre em contato com o suporte.'));
    exit;
}

// Obter os dados da loja
$store = $storeQuery->fetch(PDO::FETCH_ASSOC);
$storeId = $store['id'];

// Obter estatísticas da loja
// 1. Total de vendas registradas
$salesQuery = $db->prepare("
    SELECT COUNT(*) as total_vendas, 
           SUM(valor_total) as valor_total_vendas,
           SUM(valor_cashback) as valor_total_cashback
    FROM transacoes_cashback 
    WHERE loja_id = :loja_id
");
$salesQuery->bindParam(':loja_id', $storeId);
$salesQuery->execute();
$salesStats = $salesQuery->fetch(PDO::FETCH_ASSOC);

// 2. Comissões pendentes
$pendingQuery = $db->prepare("
    SELECT COUNT(*) as total_pendentes, 
           SUM(valor_cashback) as valor_pendente
    FROM transacoes_cashback 
    WHERE loja_id = :loja_id AND status = :status
");
$pendingQuery->bindParam(':loja_id', $storeId);
$status = TRANSACTION_PENDING;
$pendingQuery->bindParam(':status', $status);
$pendingQuery->execute();
$pendingStats = $pendingQuery->fetch(PDO::FETCH_ASSOC);

// 3. Últimas transações
$recentQuery = $db->prepare("
    SELECT t.*, u.nome as cliente_nome
    FROM transacoes_cashback t
    JOIN usuarios u ON t.usuario_id = u.id
    WHERE t.loja_id = :loja_id
    ORDER BY t.data_transacao DESC
    LIMIT 5
");
$recentQuery->bindParam(':loja_id', $storeId);
$recentQuery->execute();
$recentTransactions = $recentQuery->fetchAll(PDO::FETCH_ASSOC);

// 4. Estatísticas de vendas por mês (últimos 6 meses)
$monthlyQuery = $db->prepare("
    SELECT 
        DATE_FORMAT(data_transacao, '%Y-%m') as mes,
        COUNT(*) as total_vendas,
        SUM(valor_total) as valor_total,
        SUM(valor_cashback) as valor_cashback
    FROM transacoes_cashback
    WHERE loja_id = :loja_id
    AND data_transacao >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(data_transacao, '%Y-%m')
    ORDER BY mes ASC
");
$monthlyQuery->bindParam(':loja_id', $storeId);
$monthlyQuery->execute();
$monthlyStats = $monthlyQuery->fetchAll(PDO::FETCH_ASSOC);

// Converter estatísticas mensais para formato adequado para gráficos
$chartLabels = [];
$chartData = [];
$monthNames = [
    '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', 
    '04' => 'Abril', '05' => 'Maio', '06' => 'Junho',
    '07' => 'Julho', '08' => 'Agosto', '09' => 'Setembro', 
    '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
];

foreach ($monthlyStats as $stat) {
    $yearMonth = explode('-', $stat['mes']);
    $monthName = $monthNames[$yearMonth[1]] . '/' . substr($yearMonth[0], 2, 2);
    $chartLabels[] = $monthName;
    $chartData[] = floatval($stat['valor_total']);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard da Loja - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <!-- CSS e JS comuns -->
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/store.css">
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #FF7A00;
            --primary-dark: #E06E00;
            --primary-light: #FFF0E6;
            --secondary-color: #4F46E5;
            --success-color: #10B981;
            --warning-color: #F59E0B;
            --danger-color: #EF4444;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-300: #D1D5DB;
            --gray-400: #9CA3AF;
            --gray-500: #6B7280;
            --gray-600: #4B5563;
            --gray-700: #374151;
            --gray-800: #1F2937;
            --gray-900: #111827;
            --white: #FFFFFF;
            --font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: var(--font-family);
        }
        
        body {
            background-color: #F9FAFB;
            color: var(--gray-800);
            line-height: 1.5;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        .main-content {
            flex: 1;
            padding: 2rem;
            margin-left: 250px; /* Ajustar conforme largura da sidebar */
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .dashboard-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--gray-900);
        }
        
        .welcome-user {
            font-size: 1rem;
            color: var(--gray-600);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background-color: var(--white);
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card h3 {
            font-size: 0.875rem;
            color: var(--gray-500);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
        
        .stat-card .stat-value {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }
        
        .stat-card .stat-description {
            font-size: 0.875rem;
            color: var(--gray-500);
        }
        
        .stat-card.primary {
            border-left: 4px solid var(--primary-color);
        }
        
        .stat-card.success {
            border-left: 4px solid var(--success-color);
        }
        
        .stat-card.warning {
            border-left: 4px solid var(--warning-color);
        }
        
        .stat-card.danger {
            border-left: 4px solid var(--danger-color);
        }
        
        .charts-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .chart-card {
            background-color: var(--white);
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }
        
        .chart-card h3 {
            font-size: 1.125rem;
            color: var(--gray-700);
            margin-bottom: 1rem;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .transactions-container {
            background-color: var(--white);
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .transactions-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .transactions-title {
            font-size: 1.125rem;
            color: var(--gray-700);
        }
        
        .view-all-link {
            font-size: 0.875rem;
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        .view-all-link:hover {
            text-decoration: underline;
        }
        
        .view-all-link svg {
            margin-left: 0.25rem;
            width: 16px;
            height: 16px;
        }
        
        .transactions-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .transactions-table th {
            text-align: left;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--gray-500);
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .transactions-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-200);
            font-size: 0.875rem;
            color: var(--gray-700);
        }
        
        .transactions-table tr:last-child td {
            border-bottom: none;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-badge.pending {
            background-color: var(--warning-color);
            color: white;
        }
        
        .status-badge.approved {
            background-color: var(--success-color);
            color: white;
        }
        
        .status-badge.canceled {
            background-color: var(--danger-color);
            color: white;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .action-card {
            background-color: var(--white);
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            text-align: center;
            transition: all 0.2s ease;
            text-decoration: none;
            color: var(--gray-700);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            color: var(--primary-color);
        }
        
        .action-icon {
            width: 48px;
            height: 48px;
            background-color: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
        
        .action-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .action-description {
            font-size: 0.875rem;
            color: var(--gray-500);
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .charts-container {
                grid-template-columns: 1fr;
            }
            
            .stat-card .stat-value {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Incluir sidebar/menu lateral -->
        <?php include_once '../components/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="dashboard-header">
                <div>
                    <h1 class="dashboard-title">Dashboard da Loja</h1>
                    <p class="welcome-user">Bem-vindo(a), <?php echo htmlspecialchars($store['nome_fantasia']); ?></p>
                </div>
                <div class="header-actions">
                    <!-- Ações do cabeçalho, se necessário -->
                </div>
            </div>
            
            <!-- Cards de estatísticas -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <h3>Total de Vendas</h3>
                    <div class="stat-value"><?php echo number_format($salesStats['total_vendas'], 0, ',', '.'); ?></div>
                    <p class="stat-description">Transações registradas</p>
                </div>
                
                <div class="stat-card success">
                    <h3>Valor Total</h3>
                    <div class="stat-value">R$ <?php echo number_format($salesStats['valor_total_vendas'], 2, ',', '.'); ?></div>
                    <p class="stat-description">Em vendas processadas</p>
                </div>
                
                <div class="stat-card warning">
                    <h3>Comissões Pendentes</h3>
                    <div class="stat-value">R$ <?php echo number_format($pendingStats['valor_pendente'], 2, ',', '.'); ?></div>
                    <p class="stat-description"><?php echo number_format($pendingStats['total_pendentes'], 0, ',', '.'); ?> transações aguardando pagamento</p>
                </div>
                
                <div class="stat-card danger">
                    <h3>Total de Cashback</h3>
                    <div class="stat-value">R$ <?php echo number_format($salesStats['valor_total_cashback'], 2, ',', '.'); ?></div>
                    <p class="stat-description">Gerado para clientes</p>
                </div>
            </div>
            
            <!-- Links rápidos para ações -->
            <div class="quick-actions">
                <a href="<?php echo STORE_REGISTER_TRANSACTION_URL; ?>" class="action-card">
                    <div class="action-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </div>
                    <h3 class="action-title">Nova Transação</h3>
                    <p class="action-description">Registrar uma nova venda</p>
                </a>
                
                <a href="<?php echo STORE_BATCH_UPLOAD_URL; ?>" class="action-card">
                    <div class="action-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                    </div>
                    <h3 class="action-title">Upload em Lote</h3>
                    <p class="action-description">Importar múltiplas transações</p>
                </a>
                
                <a href="<?php echo STORE_PENDING_TRANSACTIONS_URL; ?>" class="action-card">
                    <div class="action-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <h3 class="action-title">Comissões Pendentes</h3>
                    <p class="action-description">Gerenciar pagamentos</p>
                </a>
                
                <a href="<?php echo STORE_PAYMENT_HISTORY_URL; ?>" class="action-card">
                    <div class="action-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                    </div>
                    <h3 class="action-title">Histórico de Pagamentos</h3>
                    <p class="action-description">Visualizar pagamentos realizados</p>
                </a>
            </div>
            
            <!-- Gráficos -->
            <div class="charts-container">
                <div class="chart-card">
                    <h3>Vendas nos Últimos 6 Meses</h3>
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <h3>Distribuição de Status</h3>
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Últimas Transações -->
            <div class="transactions-container">
                <div class="transactions-header">
                    <h3 class="transactions-title">Últimas Transações</h3>
                    <a href="<?php echo STORE_TRANSACTIONS_URL; ?>" class="view-all-link">
                        Ver Todas 
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </a>
                </div>
                
                <table class="transactions-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Cliente</th>
                            <th>Código</th>
                            <th>Valor</th>
                            <th>Cashback</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recentTransactions) > 0): ?>
                            <?php foreach ($recentTransactions as $transaction): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($transaction['data_transacao'])); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['cliente_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['codigo_transacao']); ?></td>
                                    <td>R$ <?php echo number_format($transaction['valor_total'], 2, ',', '.'); ?></td>
                                    <td>R$ <?php echo number_format($transaction['valor_cashback'], 2, ',', '.'); ?></td>
                                    <td>
                                        <?php 
                                            $statusClass = '';
                                            $statusText = '';
                                            
                                            switch ($transaction['status']) {
                                                case TRANSACTION_PENDING:
                                                    $statusClass = 'pending';
                                                    $statusText = 'Pendente';
                                                    break;
                                                case TRANSACTION_APPROVED:
                                                    $statusClass = 'approved';
                                                    $statusText = 'Aprovado';
                                                    break;
                                                case TRANSACTION_CANCELED:
                                                    $statusClass = 'canceled';
                                                    $statusText = 'Cancelado';
                                                    break;
                                                default:
                                                    $statusClass = 'pending';
                                                    $statusText = 'Pendente';
                                            }
                                        ?>
                                        <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">Nenhuma transação registrada ainda.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        // Configuração do gráfico de vendas mensais
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [{
                    label: 'Valor Total (R$)',
                    data: <?php echo json_encode($chartData); ?>,
                    backgroundColor: 'rgba(255, 122, 0, 0.7)',
                    borderColor: 'rgba(255, 122, 0, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR');
                            }
                        }
                    }
                }
            }
        });
        
        // Configuração do gráfico de status de transações
        // Obter contagem de transações por status
        <?php
        // Consulta para obter contagem por status
        $statusQuery = $db->prepare("
            SELECT status, COUNT(*) as total
            FROM transacoes_cashback
            WHERE loja_id = :loja_id
            GROUP BY status
        ");
        $statusQuery->bindParam(':loja_id', $storeId);
        $statusQuery->execute();
        $statusStats = $statusQuery->fetchAll(PDO::FETCH_ASSOC);
        
        // Preparar dados para o gráfico
        $statusLabels = [];
        $statusData = [];
        $statusColors = [];
        
        $colorMap = [
            TRANSACTION_PENDING => 'rgba(245, 158, 11, 0.8)',
            TRANSACTION_APPROVED => 'rgba(16, 185, 129, 0.8)',
            TRANSACTION_CANCELED => 'rgba(239, 68, 68, 0.8)'
        ];
        
        $labelMap = [
            TRANSACTION_PENDING => 'Pendentes',
            TRANSACTION_APPROVED => 'Aprovadas',
            TRANSACTION_CANCELED => 'Canceladas'
        ];
        
        foreach ($statusStats as $stat) {
            $statusLabels[] = $labelMap[$stat['status']] ?? $stat['status'];
            $statusData[] = intval($stat['total']);
            $statusColors[] = $colorMap[$stat['status']] ?? 'rgba(107, 114, 128, 0.8)';
        }
        ?>
        
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($statusLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($statusData); ?>,
                    backgroundColor: <?php echo json_encode($statusColors); ?>,
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>