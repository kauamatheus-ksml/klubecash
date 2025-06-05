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
    header('Location: ' . LOGIN_URL . '?error=' . urlencode('Acesso restrito a lojas parceiras.'));
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
           SUM(valor_cashback) as valor_total_cashback,
           SUM(valor_cliente) as valor_total_cliente,
           SUM(valor_admin) as valor_total_admin
    FROM transacoes_cashback 
    WHERE loja_id = :loja_id
");
$salesQuery->bindParam(':loja_id', $storeId);
$salesQuery->execute();
$salesStats = $salesQuery->fetch(PDO::FETCH_ASSOC);

// 2. Comissões pendentes
$pendingQuery = $db->prepare("
    SELECT COUNT(*) as total_pendentes, 
           SUM(valor_cashback) as valor_pendente,
           SUM(valor_cliente) as valor_cliente_pendente,
           COUNT(DISTINCT usuario_id) as clientes_afetados
    FROM transacoes_cashback 
    WHERE loja_id = :loja_id AND status = :status
");

$paidQuery = $db->prepare("
    SELECT COUNT(*) as total_pagas, 
           SUM(valor_cashback) as valor_pago
    FROM transacoes_cashback 
    WHERE loja_id = :loja_id AND status = :status
");
$paidQuery->bindParam(':loja_id', $storeId);
$status = 'aprovado';
$paidQuery->bindParam(':status', $status);
$paidQuery->execute();
$paidStats = $paidQuery->fetch(PDO::FETCH_ASSOC);

$pendingQuery->bindParam(':loja_id', $storeId);
$status = 'pendente';
$pendingQuery->bindParam(':status', $status);
$pendingQuery->execute();
$pendingStats = $pendingQuery->fetch(PDO::FETCH_ASSOC);

// 4. Últimas transações
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

// 5. Estatísticas de vendas por mês (últimos 6 meses)
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

// Definir menu ativo
$activeMenu = 'dashboard';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard da Loja - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    /* Variáveis e configurações globais */
    :root {
        --primary-color: #FF7A00;
        --primary-dark: #E06E00;
        --primary-light: #FFF0E6;
        --secondary-color: #2A3F54;
        --success-color: #28A745;
        --warning-color: #FFC107; 
        --danger-color: #DC3545;
        --info-color: #17A2B8;
        --light-gray: #F8F9FA;
        --medium-gray: #6C757D;
        --dark-gray: #343A40;
        --white: #FFFFFF;
        --shadow-sm: 0 2px 8px rgba(0,0,0,0.04);
        --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
        --shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
        --border-radius: 12px;
        --transition: all 0.3s ease;
    }
    
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #F5F7FA;
        color: var(--dark-gray);
        line-height: 1.5;
        margin: 0;
        padding: 0;
    }
    
    /* Layout do dashboard */
    .dashboard-container {
        display: flex;
        min-height: 100vh;
    }
    
    .main-content {
        flex: 1;
        padding: 1.5rem;
        margin-left: 250px; /* Largura da sidebar */
        transition: margin-left 0.3s ease;
    }
    
    /* Cabeçalho */
    .dashboard-header {
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    
    .dashboard-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--secondary-color);
        margin-bottom: 0.5rem;
    }
    
    .welcome-user {
        color: var(--medium-gray);
        font-size: 1rem;
    }
    
    /* Cards estatísticos */
    .summary-cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .card {
        background-color: var(--white);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        box-shadow: var(--shadow-md);
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: var(--transition);
        border: none;
        overflow: hidden;
        position: relative;
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }
    
    .card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 4px;
        background-color: var(--primary-color);
        opacity: 0;
        transition: var(--transition);
    }
    
    .card:hover::before {
        opacity: 1;
    }
    
    .card-content {
        flex: 1;
    }
    
    .card-content h3 {
        font-size: 0.85rem;
        color: var(--medium-gray);
        margin-bottom: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .card-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--secondary-color);
        margin-bottom: 0.5rem;
        line-height: 1.2;
    }
    
    .card-period {
        font-size: 0.85rem;
        color: var(--medium-gray);
    }
    
    .info-card {
        background-color: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 25px;
        border: 1px solid #e9ecef;
    }
    
    .info-card h3 {
        margin-bottom: 15px;
        color: #333;
        font-size: 18px;
    }
    
    .info-number {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background-color: #FF7A00;
        color: white;
        font-weight: bold;
    }
    
    .info-content {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .info-item {
        display: flex;
        align-items: flex-start;
        gap: 15px;
    }
    
    .info-item h4 {
        margin: 0 0 5px 0;
        font-size: 16px;
        color: #444;
    }
    
    .info-item p {
        margin: 0;
        color: #666;
        font-size: 14px;
    }
    
    .card-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: var(--primary-light);
        color: var(--primary-color);
        transition: var(--transition);
    }
    
    .card:hover .card-icon {
        transform: scale(1.1);
    }
    
    .card-icon.success {
        background-color: rgba(40, 167, 69, 0.1);
        color: var(--success-color);
    }
    
    .card-icon.warning {
        background-color: rgba(255, 193, 7, 0.1);
        color: var(--warning-color);
    }
    
    .card-icon.info {
        background-color: rgba(23, 162, 184, 0.1);
        color: var(--info-color);
    }
    
    /* Alerta */
    .alert {
        background-color: var(--white);
        border-radius: var(--border-radius);
        padding: 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: var(--shadow-md);
        margin-bottom: 2rem;
        border-left: 4px solid;
    }
    
    .alert.warning {
        border-color: var(--warning-color);
    }
    
    .alert.warning svg {
        color: var(--warning-color);
    }
    
    .alert h4 {
        margin: 0 0 0.35rem 0;
        font-size: 1.1rem;
        color: var(--dark-gray);
    }
    
    .alert p {
        margin: 0;
        color: var(--medium-gray);
        font-size: 0.9rem;
    }
    
    .btn-warning {
        background-color: #ffc107;
        color: #333;
        font-weight: 600;
        padding: 8px 15px;
        border-radius: 5px;
        text-decoration: none;
        transition: background-color 0.3s;
        white-space: nowrap;
    }
    
    .btn-warning:hover {
        background-color: #e0a800;
        transform: translateY(-2px);
    }
    
    /* Seções */
    .quick-actions, .chart-container, .recent-transactions {
        background-color: var(--white);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        box-shadow: var(--shadow-md);
        margin-bottom: 2rem;
    }
    
    .quick-actions h2, .chart-container h2, .section-header h2 {
        font-size: 1.25rem;
        color: var(--secondary-color);
        margin-top: 0;
        margin-bottom: 1.25rem;
        font-weight: 600;
        display: flex;
        align-items: center;
    }
    
    .quick-actions h2::after, .chart-container h2::after, .section-header h2::after {
        content: '';
        height: 3px;
        width: 2rem;
        background-color: var(--primary-color);
        margin-left: 0.75rem;
        border-radius: 3px;
    }
    
    /* Ações rápidas */
    .actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 1.25rem;
    }
    
    .action-card {
        background-color: var(--light-gray);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        text-decoration: none;
        color: var(--dark-gray);
        transition: var(--transition);
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        overflow: hidden;
    }
    
    .action-card::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        height: 3px;
        width: 0;
        background-color: var(--primary-color);
        transition: var(--transition);
    }
    
    .action-card:hover {
        transform: translateY(-5px);
        background-color: var(--white);
        box-shadow: var(--shadow-md);
    }
    
    .action-card:hover::after {
        width: 100%;
    }
    
    .action-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background-color: var(--primary-light);
        color: var(--primary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
        transition: var(--transition);
    }
    
    .action-card:hover .action-icon {
        transform: scale(1.1);
        background-color: var(--primary-color);
        color: var(--white);
    }
    
    .action-card h3 {
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
        color: var(--secondary-color);
    }
    
    .action-card p {
        font-size: 0.9rem;
        color: var(--medium-gray);
        margin: 0;
    }
    
    /* Gráfico */
    .chart-wrapper {
        height: 300px;
        position: relative;
    }
    
    /* Tabela */
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.25rem;
    }
    
    .link-more {
        color: var(--primary-color);
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        transition: var(--transition);
    }
    
    .link-more:hover {
        color: var(--primary-dark);
    }
    
    .link-more::after {
        content: '→';
        margin-left: 0.4rem;
        transition: var(--transition);
    }
    
    .link-more:hover::after {
        transform: translateX(3px);
    }
    
    .table-responsive {
        overflow-x: auto;
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .data-table th {
        padding: 0.75rem 1rem;
        text-align: left;
        font-size: 0.85rem;
        color: var(--medium-gray);
        border-bottom: 2px solid var(--light-gray);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .data-table td {
        padding: 1rem;
        border-bottom: 1px solid var(--light-gray);
        font-size: 0.95rem;
        color: var(--dark-gray);
        vertical-align: middle;
    }
    
    .data-table tr:last-child td {
        border-bottom: none;
    }
    
    .data-table tr:hover td {
        background-color: rgba(245, 247, 250, 0.5);
    }
    
    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .status-badge.pendente {
        background-color: rgba(255, 193, 7, 0.1);
        color: var(--warning-color);
    }
    
    .status-badge.aprovado {
        background-color: rgba(40, 167, 69, 0.1);
        color: var(--success-color);
    }
    
    .status-badge.cancelado {
        background-color: rgba(220, 53, 69, 0.1);
        color: var(--danger-color);
    }
    
    /* Estado vazio */
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
    }
    
    .empty-state svg {
        color: #D1D5DB;
        margin-bottom: 1rem;
    }
    
    .empty-state h3 {
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
        color: var(--secondary-color);
    }
    
    .empty-state p {
        color: var(--medium-gray);
        margin-bottom: 1.5rem;
        font-size: 0.95rem;
    }
    
    .btn-primary {
        background-color: var(--primary-color);
        color: var(--white);
        font-weight: 600;
        padding: 0.6rem 1.2rem;
        border-radius: var(--border-radius);
        text-decoration: none;
        display: inline-block;
        transition: var(--transition);
        border: none;
        cursor: pointer;
    }
    
    .btn-primary:hover {
        background-color: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 122, 0, 0.25);
    }
    
    /* Responsividade */
    @media (max-width: 1199.98px) {
        .actions-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        }
    }
    
    @media (max-width: 991.98px) {
        .main-content {
            margin-left: 0; /* Remove a margem quando a sidebar é ocultada */
        }
        
        .dashboard-header {
            margin-top: 60px;
        }
        
        .summary-cards {
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        }
    }
    
    @media (max-width: 767.98px) {
        .summary-cards {
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .actions-grid {
            grid-template-columns: 1fr 1fr;
        }
        
        .alert {
            flex-direction: column;
            text-align: center;
            align-items: center;
        }
        
        .alert .btn {
            margin-left: 0;
            margin-top: 1rem;
            width: 100%;
        }
        
        .card-value {
            font-size: 1.5rem;
        }
    }
    
    @media (max-width: 575.98px) {
        .summary-cards {
            grid-template-columns: 1fr;
        }
        
        .actions-grid {
            grid-template-columns: 1fr;
        }
        
        .main-content {
            padding: 1rem;
        }
        
        .dashboard-title {
            font-size: 1.5rem;
        }
        
        .card {
            padding: 1.25rem;
        }
        
        /* Melhorar visibilidade da tabela em celulares */
        .data-table {
            display: block;
            width: 100%;
        }
        
        .data-table thead {
            display: none;
        }
        
        .data-table tbody {
            display: block;
            width: 100%;
        }
        
        .data-table tr {
            display: block;
            margin-bottom: 1rem;
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius);
            padding: 0.75rem;
        }
        
        .data-table td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--light-gray);
            padding: 0.75rem 0;
        }
        
        .data-table td:last-child {
            border-bottom: none;
        }
        
        .data-table td::before {
            content: attr(data-label);
            font-weight: 600;
            margin-right: 1rem;
            width: 40%;
            color: var(--secondary-color);
        }
    }
    </style>
    <link rel="stylesheet" href="../../assets/css/navigation-system.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Incluir o componente sidebar -->
        
    
        <!-- Sidebar unificada -->
        <?php include_once '../components/sidebar-unified.php'; ?>
        
        <div class="main-content" id="mainContent">
            <div class="dashboard-header">
                <div>
                    <h1 class="dashboard-title">Dashboard da Loja</h1>
                    <p class="welcome-user">Bem-vindo(a), <?php echo htmlspecialchars($store['nome_fantasia']); ?></p>
                </div>
            </div>
            
            <!-- Cards de estatísticas -->
            <div class="summary-cards">
                <div class="card">
                    <div class="card-content">
                        <h3>Total de Vendas</h3>
                        <div class="card-value"><?php echo number_format($salesStats['total_vendas'], 0, ',', '.'); ?></div>
                        <div class="card-period">Transações registradas</div>
                    </div>
                    <div class="card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-content">
                        <h3>Valor Total</h3>
                        <div class="card-value">R$ <?php echo number_format($salesStats['valor_total_vendas'], 2, ',', '.'); ?></div>
                        <div class="card-period">Em vendas processadas</div>
                    </div>
                    <div class="card-icon success">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-content">
                        <h3>Comissões Pendentes (10%)</h3>
                        <div class="card-value">R$ <?php echo number_format($pendingStats['valor_pendente'], 2, ',', '.'); ?></div>
                        <div class="card-period"><?php echo number_format($pendingStats['total_pendentes'], 0, ',', '.'); ?> transações aguardando pagamento</div>
                    </div>
                    <div class="card-icon warning">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-content">
                        <h3>Cashback Gerado (5%)</h3>
                        <div class="card-value">R$ <?php echo number_format($salesStats['valor_total_cliente'], 2, ',', '.'); ?></div>
                        <div class="card-period">Destinado aos clientes</div>
                    </div>
                    <div class="card-icon info">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    </div>
                </div>
            </div>
            
            <?php if ($pendingStats['total_pendentes'] > 0): ?>
            <div class="alert warning">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <div>
                    <h4>Comissões Pendentes</h4>
                    <p>Você tem <?php echo $pendingStats['total_pendentes']; ?> transações com pagamento pendente, totalizando R$ <?php echo number_format($pendingStats['valor_pendente'], 2, ',', '.'); ?>. 
                    Esta pendência afeta <?php echo $pendingStats['clientes_afetados']; ?> clientes que aguardam a liberação de R$ <?php echo number_format($pendingStats['valor_cliente_pendente'], 2, ',', '.'); ?> em cashback.</p>
                </div>
                <a href="<?php echo STORE_PENDING_TRANSACTIONS_URL; ?>" class="btn btn-warning">Pagar Comissões</a>
            </div>
            <?php endif; ?>

            
            <!-- Links rápidos para ações -->
            <div class="quick-actions">
                <h2>Ações Rápidas</h2>
                <div class="actions-grid">
                    <a href="<?php echo STORE_REGISTER_TRANSACTION_URL; ?>" class="action-card">
                        <div class="action-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                        </div>
                        <h3>Nova Transação</h3>
                        <p>Registrar uma nova venda</p>
                    </a>
                    
                    <a href="<?php echo STORE_BATCH_UPLOAD_URL; ?>" class="action-card">
                        <div class="action-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                        </div>
                        <h3>Upload em Lote</h3>
                        <p>Importar múltiplas transações</p>
                    </a>
                    
                    <a href="<?php echo STORE_PENDING_TRANSACTIONS_URL; ?>" class="action-card">
                        <div class="action-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                        <h3>Comissões Pendentes</h3>
                        <p>Gerenciar pagamentos</p>
                    </a>
                    
                    <a href="<?php echo STORE_PAYMENT_HISTORY_URL; ?>" class="action-card">
                        <div class="action-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                        </div>
                        <h3>Histórico de Pagamentos</h3>
                        <p>Visualizar pagamentos realizados</p>
                    </a>
                </div>
            </div>
            
            <!-- Gráficos -->
            <div class="chart-container">
                <h2>Vendas nos Últimos 6 Meses</h2>
                <div class="chart-wrapper">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
            
            <!-- Últimas Transações -->
            <div class="recent-transactions">
                <div class="section-header">
                    <h2>Últimas Transações</h2>
                    <a href="<?php echo STORE_TRANSACTIONS_URL; ?>" class="link-more">Ver Todas</a>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
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
                                        <td data-label="Data"><?php echo date('d/m/Y H:i', strtotime($transaction['data_transacao'])); ?></td>
                                        <td data-label="Cliente"><?php echo htmlspecialchars($transaction['cliente_nome']); ?></td>
                                        <td data-label="Código"><?php echo htmlspecialchars($transaction['codigo_transacao'] ?? 'N/A'); ?></td>
                                        <td data-label="Valor">R$ <?php echo number_format($transaction['valor_total'], 2, ',', '.'); ?></td>
                                        <td data-label="Cashback">R$ <?php echo number_format($transaction['valor_cashback'], 2, ',', '.'); ?></td>
                                        <td data-label="Status">
                                            <span class="status-badge <?php echo $transaction['status']; ?>">
                                                <?php 
                                                    switch ($transaction['status']) {
                                                        case 'pendente': echo 'Pendente'; break;
                                                        case 'aprovado': echo 'Aprovado'; break;
                                                        case 'cancelado': echo 'Cancelado'; break;
                                                        default: echo 'Pendente';
                                                    }
                                                ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="empty-state">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                                            <polyline points="13 2 13 9 20 9"></polyline>
                                        </svg>
                                        <h3>Nenhuma transação registrada</h3>
                                        <p>Comece registrando sua primeira venda com cashback</p>
                                        <a href="<?php echo STORE_REGISTER_TRANSACTION_URL; ?>" class="btn btn-primary">Registrar Venda</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Adicionar novo card informativo sobre o fluxo de cashback -->
            <div class="info-card">
                <h3>Como Funciona o Sistema de Comissão no Klube Cash</h3>
                <div class="info-content">
                    <div class="info-item">
                        <span class="info-number">1</span>
                        <div>
                            <h4>Registro da Venda</h4>
                            <p>Você registra suas vendas no sistema com o valor total e identificação do cliente</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <span class="info-number">2</span>
                        <div>
                            <h4>Pagamento da Comissão</h4>
                            <p>Você paga 10% de comissão sobre o valor efetivamente cobrado (descontando saldo usado)</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <span class="info-number">3</span>
                        <div>
                            <h4>Distribuição dos 10%</h4>
                            <p>5% vira cashback para o cliente e 5% fica como receita do Klube Cash</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <span class="info-number">4</span>
                        <div>
                            <h4>Liberação do Cashback</h4>
                            <p>Após aprovação do seu pagamento, o cashback é liberado para o cliente usar na sua loja</p>
                        </div>
                    </div>
                </div>
                
                <!-- ADICIONADO: Informação importante -->
                <div class="info-highlight">
                    <strong>💡 Importante:</strong> Sua loja não recebe cashback. O saldo do cliente só pode ser usado na sua própria loja, gerando uma nova comissão sobre o valor efetivamente pago.
                </div>
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
    </script>

    <style>
    /* Adicione no final do CSS existente */
    .info-highlight {
        margin-top: 20px;
        padding: 15px;
        background-color: #fff3e0;
        border-left: 4px solid #FF7A00;
        border-radius: 8px;
        color: #e65100;
        font-size: 0.9rem;
    }
    </style>
    <script src="../../assets/js/navigation-system.js"></script>
</body>
</html>