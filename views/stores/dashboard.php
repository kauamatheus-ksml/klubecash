<?php
// views/stores/dashboard.php - VERSÃO RENOVADA E RESPONSIVA
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

// === MANTER TODA A LÓGICA EXISTENTE DE CONSULTAS ===
// [Todo o código PHP de consultas permanece exatamente igual]

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
    <title>Dashboard - <?php echo htmlspecialchars($store['nome_fantasia']); ?> | Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    
    <!-- Fonts otimizadas -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- CSS Modernizado -->
    <style>
        /* === RESET E CONFIGURAÇÕES GLOBAIS === */
        :root {
            --primary-color: #FF7A00;
            --primary-dark: #E06E00;
            --primary-light: #FFF0E6;
            --primary-gradient: linear-gradient(135deg, #FF7A00 0%, #FF8A1A 100%);
            
            --secondary-color: #2A3F54;
            --success-color: #10B981;
            --warning-color: #F59E0B; 
            --danger-color: #EF4444;
            --info-color: #3B82F6;
            
            --gray-50: #F9FAFB;
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
            --background: #F8FAFC;
            
            /* Sombras modernas */
            --shadow-xs: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            
            /* Radiuses modernos */
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
            --radius-2xl: 24px;
            
            /* Transições suaves */
            --transition-fast: all 0.15s ease;
            --transition-normal: all 0.3s ease;
            --transition-slow: all 0.5s ease;
            
            /* Layout */
            --sidebar-width: 250px;
            --header-height: 70px;
            --content-padding: 24px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--background);
            color: var(--gray-900);
            line-height: 1.6;
            font-size: 14px;
            overflow-x: hidden;
        }

        /* === LAYOUT PRINCIPAL === */
        .dashboard-layout {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: var(--content-padding);
            transition: margin-left 0.3s ease;
            max-width: calc(100vw - var(--sidebar-width));
        }

        /* === HEADER MODERNO === */
        .dashboard-header {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: 24px 28px;
            margin-bottom: 32px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
            border-radius: var(--radius-xl) var(--radius-xl) 0 0;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .header-text h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 4px;
            letter-spacing: -0.025em;
        }

        .header-text p {
            color: var(--gray-600);
            font-size: 16px;
            margin: 0;
        }

        .header-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .quick-action-btn {
            background: var(--primary-gradient);
            color: var(--white);
            border: none;
            padding: 12px 20px;
            border-radius: var(--radius-lg);
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition-fast);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: var(--shadow-sm);
        }

        .quick-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .quick-action-btn:active {
            transform: translateY(0);
        }

        /* === GRID DE CARDS RESPONSIVO === */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            transition: var(--transition-normal);
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary-gradient);
            transform: scaleX(0);
            transition: var(--transition-normal);
        }

        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .card-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 0;
        }

        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary-light);
            color: var(--primary-color);
            flex-shrink: 0;
        }

        .card-icon.success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .card-icon.warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .card-icon.info {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info-color);
        }

        .card-value {
            font-size: 32px;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 8px;
            line-height: 1;
            letter-spacing: -0.025em;
        }

        .card-subtitle {
            font-size: 14px;
            color: var(--gray-500);
            margin: 0;
            font-weight: 500;
        }

        .card-trend {
            display: flex;
            align-items: center;
            gap: 4px;
            margin-top: 8px;
            font-size: 12px;
            font-weight: 600;
        }

        .trend-up {
            color: var(--success-color);
        }

        .trend-down {
            color: var(--danger-color);
        }

        /* === ALERTA MODERNO === */
        .alert-modern {
            background: linear-gradient(135deg, #FEF3E2 0%, #FDF2E9 100%);
            border: 1px solid #F59E0B;
            border-radius: var(--radius-xl);
            padding: 20px 24px;
            margin-bottom: 32px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            box-shadow: var(--shadow-sm);
        }

        .alert-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-lg);
            background: var(--warning-color);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .alert-content h4 {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 8px;
        }

        .alert-content p {
            color: var(--gray-700);
            margin-bottom: 16px;
            line-height: 1.5;
        }

        .alert-action {
            background: var(--warning-color);
            color: var(--white);
            border: none;
            padding: 10px 16px;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition-fast);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .alert-action:hover {
            background: #D97706;
            transform: translateY(-1px);
        }

        /* === SEÇÕES PRINCIPAIS === */
        .content-section {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: 28px;
            margin-bottom: 32px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--gray-100);
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition-fast);
        }

        .section-link:hover {
            color: var(--primary-dark);
            transform: translateX(2px);
        }

        /* === AÇÕES RÁPIDAS === */
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }

        .action-card {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 24px 20px;
            text-decoration: none;
            color: var(--gray-900);
            transition: var(--transition-normal);
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            position: relative;
            overflow: hidden;
        }

        .action-card::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary-gradient);
            transform: scaleX(0);
            transition: var(--transition-normal);
        }

        .action-card:hover {
            transform: translateY(-4px);
            background: var(--white);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-color);
        }

        .action-card:hover::before {
            transform: scaleX(1);
        }

        .action-icon {
            width: 56px;
            height: 56px;
            border-radius: var(--radius-lg);
            background: var(--primary-light);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition-normal);
        }

        .action-card:hover .action-icon {
            background: var(--primary-gradient);
            color: var(--white);
            transform: scale(1.1);
        }

        .action-card h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0;
        }

        .action-card p {
            font-size: 14px;
            color: var(--gray-600);
            margin: 0;
            line-height: 1.4;
        }

        /* === GRÁFICO === */
        .chart-container {
            position: relative;
            height: 320px;
            width: 100%;
        }

        /* === TABELA MODERNA === */
        .table-container {
            overflow-x: auto;
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-200);
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .modern-table th {
            background: var(--gray-50);
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: var(--gray-700);
            border-bottom: 1px solid var(--gray-200);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .modern-table td {
            padding: 16px;
            border-bottom: 1px solid var(--gray-100);
            color: var(--gray-900);
            vertical-align: middle;
        }

        .modern-table tr:last-child td {
            border-bottom: none;
        }

        .modern-table tbody tr:hover {
            background: var(--gray-50);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-badge.pendente {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .status-badge.aprovado {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .status-badge.cancelado {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        /* === ESTADO VAZIO === */
        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: var(--gray-500);
        }

        .empty-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 8px;
        }

        .empty-state p {
            margin-bottom: 24px;
            line-height: 1.5;
        }

        /* === CARD INFORMATIVO === */
        .info-card {
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--white) 100%);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-xl);
            padding: 28px;
            margin-bottom: 32px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 20px;
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-200);
            transition: var(--transition-fast);
        }

        .info-item:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .info-number {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary-gradient);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }

        .info-content h4 {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 6px;
        }

        .info-content p {
            font-size: 14px;
            color: var(--gray-600);
            line-height: 1.5;
            margin: 0;
        }

        .info-highlight {
            margin-top: 24px;
            padding: 20px;
            background: linear-gradient(135deg, var(--primary-light) 0%, #FFF5ED 100%);
            border: 1px solid var(--primary-color);
            border-radius: var(--radius-lg);
            font-size: 14px;
            line-height: 1.5;
        }

        /* === RESPONSIVIDADE === */
        @media (max-width: 1024px) {
            .main-content {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
            
            .dashboard-header {
                padding: 20px;
                margin-bottom: 24px;
            }
            
            .header-content {
                flex-direction: column;
                align-items: flex-start;
                text-align: left;
            }
            
            .header-text h1 {
                font-size: 24px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .actions-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
            }
            
            .content-section {
                padding: 20px;
                margin-bottom: 24px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .modern-table {
                font-size: 12px;
            }
            
            .modern-table th,
            .modern-table td {
                padding: 12px 8px;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 12px;
            }
            
            .actions-grid {
                grid-template-columns: 1fr;
            }
            
            .card-value {
                font-size: 28px;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            /* Tabela responsiva no mobile */
            .table-container {
                border: none;
            }
            
            .modern-table,
            .modern-table thead,
            .modern-table tbody,
            .modern-table th,
            .modern-table td,
            .modern-table tr {
                display: block;
            }
            
            .modern-table thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            
            .modern-table tr {
                background: var(--white);
                border: 1px solid var(--gray-200);
                border-radius: var(--radius-lg);
                margin-bottom: 12px;
                padding: 16px;
            }
            
            .modern-table td {
                border: none;
                position: relative;
                padding: 8px 0 8px 40%;
                text-align: right;
            }
            
            .modern-table td:before {
                content: attr(data-label);
                position: absolute;
                left: 0;
                width: 35%;
                text-align: left;
                font-weight: 600;
                color: var(--gray-700);
            }
        }

        /* === ANIMAÇÕES === */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeInUp 0.6s ease-out;
        }

        /* === LOADING STATES === */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: .5;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Incluir sidebar da loja -->
        <?php include_once '../components/sidebar-store.php'; ?>
        
        <main class="main-content" id="mainContent">
            <!-- === HEADER PRINCIPAL === -->
            <header class="dashboard-header animate-fade-in">
                <div class="header-content">
                    <div class="header-text">
                        <h1>Olá, <?php echo htmlspecialchars($store['nome_fantasia']); ?>! 👋</h1>
                        <p>Acompanhe suas vendas e gerencie suas comissões de forma simples</p>
                    </div>
                    <div class="header-actions">
                        <a href="<?php echo STORE_REGISTER_TRANSACTION_URL; ?>" class="quick-action-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Nova Venda
                        </a>
                    </div>
                </div>
            </header>

            <!-- === CARDS DE ESTATÍSTICAS === -->
            <section class="stats-grid">
                <!-- Card: Total de Vendas -->
                <div class="stat-card animate-fade-in">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Total de Vendas</h3>
                            <div class="card-value"><?php echo number_format($salesStats['total_vendas'], 0, ',', '.'); ?></div>
                            <p class="card-subtitle">Transações registradas</p>
                        </div>
                        <div class="card-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 3h18l-2 13H5L3 3z"></path>
                                <path d="M16 16a4 4 0 0 1-8 0"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Card: Valor Total -->
                <div class="stat-card animate-fade-in">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Valor Total</h3>
                            <div class="card-value">R$ <?php echo number_format($salesStats['valor_total_vendas'], 2, ',', '.'); ?></div>
                            <p class="card-subtitle">Em vendas processadas</p>
                        </div>
                        <div class="card-icon success">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Card: Comissões Pendentes -->
                <div class="stat-card animate-fade-in">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Comissões Pendentes</h3>
                            <div class="card-value">R$ <?php echo number_format($pendingStats['valor_pendente'], 2, ',', '.'); ?></div>
                            <p class="card-subtitle"><?php echo number_format($pendingStats['total_pendentes'], 0, ',', '.'); ?> transações aguardando</p>
                        </div>
                        <div class="card-icon warning">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Card: Cashback Gerado -->
                <div class="stat-card animate-fade-in">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Cashback Gerado</h3>
                            <div class="card-value">R$ <?php echo number_format($salesStats['valor_total_cliente'], 2, ',', '.'); ?></div>
                            <p class="card-subtitle">Destinado aos clientes</p>
                        </div>
                        <div class="card-icon info">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                    </div>
                </div>
            </section>

            <!-- === ALERTA DE COMISSÕES PENDENTES === -->
            <?php if ($pendingStats['total_pendentes'] > 0): ?>
            <div class="alert-modern animate-fade-in">
                <div class="alert-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <div class="alert-content">
                    <h4>⚠️ Atenção: Comissões Pendentes</h4>
                    <p>Você tem <strong><?php echo $pendingStats['total_pendentes']; ?> transações</strong> com pagamento pendente, totalizando <strong>R$ <?php echo number_format($pendingStats['valor_pendente'], 2, ',', '.'); ?></strong>. Esta pendência afeta <strong><?php echo $pendingStats['clientes_afetados']; ?> clientes</strong> que aguardam a liberação de R$ <?php echo number_format($pendingStats['valor_cliente_pendente'], 2, ',', '.'); ?> em cashback.</p>
                    <a href="<?php echo STORE_PENDING_TRANSACTIONS_URL; ?>" class="alert-action">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        Pagar Comissões
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- === AÇÕES RÁPIDAS === -->
            <section class="content-section animate-fade-in">
                <div class="section-header">
                    <h2 class="section-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                        </svg>
                        Ações Rápidas
                    </h2>
                </div>
                
                <div class="actions-grid">
                    <a href="<?php echo STORE_REGISTER_TRANSACTION_URL; ?>" class="action-card">
                        <div class="action-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                        </div>
                        <h3>Registrar Venda</h3>
                        <p>Cadastre uma nova transação</p>
                    </a>
                    
                    <a href="<?php echo STORE_PENDING_TRANSACTIONS_URL; ?>" class="action-card">
                        <div class="action-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                        <h3>Comissões Pendentes</h3>
                        <p>Gerencie seus pagamentos</p>
                    </a>
                    
                    <a href="<?php echo STORE_PAYMENT_HISTORY_URL; ?>" class="action-card">
                        <div class="action-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                        </div>
                        <h3>Histórico</h3>
                        <p>Visualize pagamentos realizados</p>
                    </a>
                    
                    <a href="<?php echo STORE_TRANSACTIONS_URL; ?>" class="action-card">
                        <div class="action-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                                <line x1="8" y1="21" x2="16" y2="21"></line>
                                <line x1="12" y1="17" x2="12" y2="21"></line>
                            </svg>
                        </div>
                        <h3>Todas as Transações</h3>
                        <p>Visualize seu histórico completo</p>
                    </a>
                </div>
            </section>

            <!-- === GRÁFICO DE VENDAS === -->
            <section class="content-section animate-fade-in">
                <div class="section-header">
                    <h2 class="section-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                        </svg>
                        Vendas dos Últimos 6 Meses
                    </h2>
                </div>
                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
            </section>

            <!-- === ÚLTIMAS TRANSAÇÕES === -->
            <section class="content-section animate-fade-in">
                <div class="section-header">
                    <h2 class="section-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        Últimas Transações
                    </h2>
                    <a href="<?php echo STORE_TRANSACTIONS_URL; ?>" class="section-link">
                        Ver Todas
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </a>
                </div>
                
                <div class="table-container">
                    <table class="modern-table">
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
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                <polyline points="13 2 13 9 20 9"></polyline>
                                            </svg>
                                            <h3>Nenhuma transação registrada</h3>
                                            <p>Comece registrando sua primeira venda com cashback</p>
                                            <a href="<?php echo STORE_REGISTER_TRANSACTION_URL; ?>" class="quick-action-btn">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                                </svg>
                                                Registrar Primeira Venda
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- === CARD INFORMATIVO === -->
            <section class="info-card animate-fade-in">
                <h3>💡 Como Funciona o Sistema de Comissão no Klube Cash</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-number">1</span>
                        <div class="info-content">
                            <h4>Registro da Venda</h4>
                            <p>Você registra suas vendas no sistema com o valor total e identificação do cliente</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <span class="info-number">2</span>
                        <div class="info-content">
                            <h4>Pagamento da Comissão</h4>
                            <p>Você paga 10% de comissão sobre o valor efetivamente cobrado (descontando saldo usado)</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <span class="info-number">3</span>
                        <div class="info-content">
                            <h4>Distribuição dos 10%</h4>
                            <p>5% vira cashback para o cliente e 5% fica como receita do Klube Cash</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <span class="info-number">4</span>
                        <div class="info-content">
                            <h4>Liberação do Cashback</h4>
                            <p>Após aprovação do seu pagamento, o cashback é liberado para o cliente usar na sua loja</p>
                        </div>
                    </div>
                </div>
                
                <div class="info-highlight">
                    <strong>💰 Importante:</strong> Sua loja não recebe cashback. O saldo do cliente só pode ser usado na sua própria loja, gerando uma nova comissão sobre o valor efetivamente pago.
                </div>
            </section>
        </main>
    </div>

    <!-- === SCRIPTS === -->
    <script>
        // ✅ CONFIGURAÇÃO DO GRÁFICO MODERNO
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('salesChart').getContext('2d');
            
            // Gradient para o gráfico
            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(255, 122, 0, 0.8)');
            gradient.addColorStop(1, 'rgba(255, 122, 0, 0.1)');
            
            const chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($chartLabels); ?>,
                    datasets: [{
                        label: 'Valor Total (R$)',
                        data: <?php echo json_encode($chartData); ?>,
                        backgroundColor: gradient,
                        borderColor: '#FF7A00',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: '#FF7A00',
                            borderWidth: 1,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    return 'R$ ' + context.parsed.y.toLocaleString('pt-BR', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    });
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false
                            },
                            ticks: {
                                color: '#6B7280',
                                font: {
                                    size: 12
                                },
                                callback: function(value) {
                                    return 'R$ ' + value.toLocaleString('pt-BR');
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#6B7280',
                                font: {
                                    size: 12
                                }
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });

            // ✅ ANIMAÇÕES NOS CARDS
            const animateValue = (element, start, end, duration) => {
                let startTimestamp = null;
                const step = (timestamp) => {
                    if (!startTimestamp) startTimestamp = timestamp;
                    const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                    const currentValue = Math.floor(progress * (end - start) + start);
                    
                    if (element.textContent.includes('R$')) {
                        element.textContent = 'R$ ' + currentValue.toLocaleString('pt-BR', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    } else {
                        element.textContent = currentValue.toLocaleString('pt-BR');
                    }
                    
                    if (progress < 1) {
                        window.requestAnimationFrame(step);
                    }
                };
                window.requestAnimationFrame(step);
            };

            // Animar valores dos cards
            const cardValues = document.querySelectorAll('.card-value');
            cardValues.forEach((card, index) => {
                const text = card.textContent;
                const numberValue = parseFloat(text.replace(/[^\d,]/g, '').replace(',', '.'));
                if (numberValue > 0) {
                    setTimeout(() => {
                        animateValue(card, 0, numberValue, 1500);
                    }, index * 200);
                }
            });

            // ✅ LAZY LOADING PARA PERFORMANCE
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Aplicar observer nos elementos animáveis
            document.querySelectorAll('.animate-fade-in').forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'all 0.6s ease-out';
                observer.observe(el);
            });

            // ✅ MELHORIA DE UX - Loading states
            document.querySelectorAll('a[href]').forEach(link => {
                link.addEventListener('click', function(e) {
                    // Adicionar loading state se for navegação interna
                    if (this.hostname === window.location.hostname) {
                        this.classList.add('loading');
                        
                        // Remover loading após timeout (fallback)
                        setTimeout(() => {
                            this.classList.remove('loading');
                        }, 3000);
                    }
                });
            });

            // ✅ FEEDBACK VISUAL NOS CARDS
            document.querySelectorAll('.stat-card, .action-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-4px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            console.log('✅ Dashboard Store carregado com sucesso!');
        });
    </script>
</body>
</html>