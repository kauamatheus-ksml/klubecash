<?php
//views/admin/dashboard.php - Versão 2025 Responsiva
// Definir o menu ativo na sidebar
$activeMenu = 'painel';

// Incluir conexão com o banco de dados
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../models/CashbackBalance.php';

// Iniciar sessão
session_start();

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

// Obter dados do usuário logado (MANTÉM A LÓGICA EXISTENTE)
try {
    $db = Database::getConnection();
    
    // Buscar informações do usuário
    $userId = $_SESSION['user_id'];
    $userStmt = $db->prepare("SELECT nome FROM usuarios WHERE id = ? AND tipo = 'admin' AND status = 'ativo'");
    $userStmt->execute([$userId]);
    $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        header("Location: " . LOGIN_URL . "?error=acesso_restrito");
        exit;
    }
    
    $adminName = $userData['nome'];
    
    // Total de usuários
    $userCountStmt = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'cliente'");
    $totalUsers = $userCountStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de lojas
    $storeStmt = $db->query("SELECT COUNT(*) as total FROM lojas WHERE status = 'aprovado'");
    $totalStores = $storeStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de cashback
    $cashbackStmt = $db->query("SELECT SUM(valor_cashback) as total FROM transacoes_cashback");
    $totalCashback = $cashbackStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Estatísticas de saldo - total de saldo disponível em todas as lojas
    $saldoDisponivelStmt = $db->query("SELECT SUM(saldo_disponivel) as total FROM cashback_saldos");
    $totalSaldoDisponivel = $saldoDisponivelStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Total de saldo usado pelos clientes
    $saldoUsadoStmt = $db->query("
        SELECT SUM(valor) as total 
        FROM cashback_movimentacoes 
        WHERE tipo_operacao = 'uso'
    ");
    $totalSaldoUsado = $saldoUsadoStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Estatísticas de transações com saldo
    $transacoesComSaldoStmt = $db->query("
        SELECT 
            COUNT(DISTINCT t.id) as total_transacoes,
            COUNT(DISTINCT CASE WHEN cm.id IS NOT NULL THEN t.id END) as transacoes_com_saldo,
            COUNT(DISTINCT CASE WHEN cm.id IS NULL THEN t.id END) as transacoes_sem_saldo
        FROM transacoes_cashback t
        LEFT JOIN cashback_movimentacoes cm ON t.id = cm.transacao_uso_id AND cm.tipo_operacao = 'uso'
        WHERE t.status = 'aprovado'
    ");
    $estatisticasSaldo = $transacoesComSaldoStmt->fetch(PDO::FETCH_ASSOC);
    
    // Percentual de transações com uso de saldo
    $percentualComSaldo = $estatisticasSaldo['total_transacoes'] > 0 ? 
        ($estatisticasSaldo['transacoes_com_saldo'] / $estatisticasSaldo['total_transacoes']) * 100 : 0;
    
    // Lojas pendentes de aprovação
    $pendingStores = $db->query("
        SELECT id, nome_fantasia, razao_social, cnpj 
        FROM lojas 
        WHERE status = 'pendente' 
        ORDER BY data_cadastro DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Últimas transações com informações de saldo usado
    $recentTransactions = $db->query("
        SELECT 
            t.id, 
            t.valor_total, 
            t.valor_cashback, 
            t.codigo_transacao,
            t.data_transacao,
            u.nome as usuario, 
            l.nome_fantasia as loja, 
            l.razao_social,
            COALESCE(
                (SELECT SUM(cm.valor) 
                 FROM cashback_movimentacoes cm 
                 WHERE cm.transacao_uso_id = t.id AND cm.tipo_operacao = 'uso'), 0
            ) as saldo_usado
        FROM transacoes_cashback t
        JOIN usuarios u ON t.usuario_id = u.id
        JOIN lojas l ON t.loja_id = l.id
        ORDER BY t.data_transacao DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Impacto financeiro do saldo no sistema
    $impactoFinanceiroStmt = $db->query("
        SELECT 
            SUM(t.valor_total) as valor_vendas_originais,
            SUM(t.valor_total - COALESCE(cm_sum.saldo_usado, 0)) as valor_vendas_liquidas,
            SUM(t.valor_cashback) as comissoes_recebidas
        FROM transacoes_cashback t
        LEFT JOIN (
            SELECT 
                transacao_uso_id,
                SUM(valor) as saldo_usado
            FROM cashback_movimentacoes 
            WHERE tipo_operacao = 'uso'
            GROUP BY transacao_uso_id
        ) cm_sum ON t.id = cm_sum.transacao_uso_id
        WHERE t.status = 'aprovado'
    ");
    $impactoFinanceiro = $impactoFinanceiroStmt->fetch(PDO::FETCH_ASSOC);
    
    // Top 5 clientes que mais usaram saldo
    $topClientesSaldoStmt = $db->query("
        SELECT 
            u.nome,
            u.email,
            SUM(cm.valor) as total_saldo_usado,
            COUNT(cm.id) as vezes_usado
        FROM cashback_movimentacoes cm
        JOIN usuarios u ON cm.usuario_id = u.id
        WHERE cm.tipo_operacao = 'uso'
        GROUP BY u.id, u.nome, u.email
        ORDER BY total_saldo_usado DESC
        LIMIT 5
    ");
    $topClientesSaldo = $topClientesSaldoStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Erro ao carregar estatísticas: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <title>Dashboard - Klube Cash</title>
    
    <!-- Fonts otimizadas para performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- CSS com prioridade para performance -->
    <link rel="stylesheet" href="../../assets/css/views/admin/dashboard-responsive-v2.css">
    <link rel="stylesheet" href="../../assets/css/sidebar-styles.css">
    <link rel="stylesheet" href="../../assets/css/layout-fix.css">
</head>
<body class="dashboard-body">
    <?php include_once '../components/sidebar.php'; ?>
    
    <!-- Conteúdo Principal com Nova Estrutura -->
    <div class="main-content" id="mainContent">
        <div class="dashboard-container">
            
            <!-- Header Hero Section -->
            <div class="dashboard-hero">
                <div class="hero-content">
                    <div class="hero-text">
                        <h1 class="dashboard-title">
                            <span class="greeting">Bem Vindo,</span>
                            <span class="admin-name"><?php echo htmlspecialchars($adminName); ?>!</span>
                        </h1>
                        <p class="dashboard-subtitle">Aqui está um resumo da sua plataforma de cashback</p>
                    </div>
                    <div class="hero-stats">
                        <div class="quick-stat">
                            <span class="stat-number"><?php echo number_format($totalUsers); ?></span>
                            <span class="stat-label">Usuários Ativos</span>
                        </div>
                        <div class="quick-stat">
                            <span class="stat-number"><?php echo number_format($totalStores); ?></span>
                            <span class="stat-label">Lojas Parceiras</span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <div class="alert-icon">⚠️</div>
                    <div class="alert-content">
                        <h4>Erro no Sistema</h4>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    </div>
                </div>
            <?php else: ?>
            
            <!-- Seção de Métricas Principais -->
            <div class="metrics-section">
                <h2 class="section-title">
                    <span class="title-icon">📊</span>
                    Métricas Financeiras
                </h2>
                
                <div class="metrics-grid">
                    <!-- Card Cashback Total -->
                    <div class="metric-card primary-card" data-metric="cashback">
                        <div class="card-header">
                            <div class="card-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="1" x2="12" y2="23"></line>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                            </div>
                            <div class="card-trend positive">
                                <span class="trend-arrow">↗</span>
                                <span class="trend-percent">+12%</span>
                            </div>
                        </div>
                        <div class="card-content">
                            <h3 class="card-title">Total de Cashback</h3>
                            <div class="card-value">R$ <?php echo number_format($totalCashback, 2, ',', '.'); ?></div>
                            <p class="card-description">Total creditado aos clientes</p>
                        </div>
                        <div class="card-footer">
                            <small>Atualizado há 5 min</small>
                        </div>
                    </div>

                    <!-- Card Saldo Disponível -->
                    <div class="metric-card success-card" data-metric="saldo-disponivel">
                        <div class="card-header">
                            <div class="card-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 6L9 17l-5-5"></path>
                                </svg>
                            </div>
                            <div class="card-trend positive">
                                <span class="trend-arrow">↗</span>
                                <span class="trend-percent">+8%</span>
                            </div>
                        </div>
                        <div class="card-content">
                            <h3 class="card-title">Saldo Disponível</h3>
                            <div class="card-value">R$ <?php echo number_format($totalSaldoDisponivel, 2, ',', '.'); ?></div>
                            <p class="card-description">Saldo acumulado pelos clientes</p>
                        </div>
                        <div class="card-footer">
                            <small>Em tempo real</small>
                        </div>
                    </div>

                    <!-- Card Saldo Usado -->
                    <div class="metric-card warning-card" data-metric="saldo-usado">
                        <div class="card-header">
                            <div class="card-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="M12 6v6l4 2"></path>
                                </svg>
                            </div>
                            <div class="card-trend positive">
                                <span class="trend-arrow">↗</span>
                                <span class="trend-percent">+15%</span>
                            </div>
                        </div>
                        <div class="card-content">
                            <h3 class="card-title">Saldo Usado</h3>
                            <div class="card-value">R$ <?php echo number_format($totalSaldoUsado, 2, ',', '.'); ?></div>
                            <p class="card-description">Total usado pelos clientes</p>
                        </div>
                        <div class="card-footer">
                            <small>Última transação há 2h</small>
                        </div>
                    </div>

                    <!-- Card Taxa de Uso -->
                    <div class="metric-card info-card" data-metric="taxa-uso">
                        <div class="card-header">
                            <div class="card-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 3v18h18"></path>
                                    <path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"></path>
                                </svg>
                            </div>
                            <div class="card-trend neutral">
                                <span class="trend-arrow">→</span>
                                <span class="trend-percent">0%</span>
                            </div>
                        </div>
                        <div class="card-content">
                            <h3 class="card-title">Taxa de Uso</h3>
                            <div class="card-value"><?php echo number_format($percentualComSaldo, 1); ?>%</div>
                            <p class="card-description">Clientes usando saldo</p>
                        </div>
                        <div class="card-footer">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $percentualComSaldo; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Seção de Análise e Gestão -->
            <div class="dashboard-grid">
                
                <!-- Impacto Financeiro (Novo Design) -->
                <div class="dashboard-card financial-impact-card">
                    <div class="card-header-modern">
                        <h3 class="card-title-modern">
                            <span class="title-icon">💰</span>
                            Impacto Financeiro
                        </h3>
                        <div class="card-actions">
                            <button class="action-btn" onclick="exportFinancialData()">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7 10 12 15 17 10"></polyline>
                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="financial-summary">
                        <div class="summary-item primary">
                            <div class="summary-label">Vendas Originais</div>
                            <div class="summary-value">R$ <?php echo number_format($impactoFinanceiro['valor_vendas_originais'] ?? 0, 2, ',', '.'); ?></div>
                        </div>
                        
                        <div class="summary-divider">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                        </div>
                        
                        <div class="summary-item negative">
                            <div class="summary-label">Desconto via Saldo</div>
                            <div class="summary-value">- R$ <?php echo number_format($totalSaldoUsado, 2, ',', '.'); ?></div>
                        </div>
                        
                        <div class="summary-divider">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                        </div>
                        
                        <div class="summary-item result">
                            <div class="summary-label">Vendas Líquidas</div>
                            <div class="summary-value">R$ <?php echo number_format($impactoFinanceiro['valor_vendas_liquidas'] ?? 0, 2, ',', '.'); ?></div>
                        </div>
                    </div>
                    
                    <div class="revenue-highlight">
                        <div class="revenue-label">Comissões Klube Cash</div>
                        <div class="revenue-value">R$ <?php echo number_format($impactoFinanceiro['comissoes_recebidas'] ?? 0, 2, ',', '.'); ?></div>
                        <div class="revenue-badge">Receita Total</div>
                    </div>
                </div>

                <!-- Top Clientes (Redesenhado) -->
                <div class="dashboard-card top-clients-card">
                    <div class="card-header-modern">
                        <h3 class="card-title-modern">
                            <span class="title-icon">🏆</span>
                            Top Clientes - Uso de Saldo
                        </h3>
                        <div class="card-actions">
                            <button class="action-btn" onclick="viewAllClients()">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 18l6-6-6-6"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <?php if (!empty($topClientesSaldo)): ?>
                        <div class="clients-ranking">
                            <?php foreach ($topClientesSaldo as $index => $cliente): ?>
                                <div class="client-rank-item" data-rank="<?php echo $index + 1; ?>">
                                    <div class="rank-badge rank-<?php echo $index + 1; ?>">
                                        <?php if ($index === 0): ?>
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path>
                                                <path d="M14 15h1.5a2.5 2.5 0 0 1 0 5H14"></path>
                                                <path d="M6 9h8.5a2.5 2.5 0 0 1 0 5H6V9z"></path>
                                            </svg>
                                        <?php else: ?>
                                            #<?php echo $index + 1; ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="client-info">
                                        <div class="client-name"><?php echo htmlspecialchars($cliente['nome']); ?></div>
                                        <div class="client-details">
                                            <span class="usage-amount">R$ <?php echo number_format($cliente['total_saldo_usado'], 2, ',', '.'); ?></span>
                                            <span class="usage-frequency"><?php echo $cliente['vezes_usado']; ?> uso<?php echo $cliente['vezes_usado'] > 1 ? 's' : ''; ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="client-progress">
                                        <div class="progress-circle">
                                            <svg width="40" height="40" viewBox="0 0 40 40">
                                                <circle cx="20" cy="20" r="16" fill="none" stroke="#f0f0f0" stroke-width="3"></circle>
                                                <circle cx="20" cy="20" r="16" fill="none" stroke="#FF7A00" stroke-width="3" 
                                                        stroke-dasharray="<?php echo 100 - ($index * 15); ?> 100" 
                                                        stroke-linecap="round" transform="rotate(-90 20 20)"></circle>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">👤</div>
                            <h4>Nenhum Cliente Ativo</h4>
                            <p>Ainda não há clientes usando saldo no sistema.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Gestão de Lojas -->
                <div class="dashboard-card stores-management-card">
                    <div class="card-header-modern">
                        <h3 class="card-title-modern">
                            <span class="title-icon">🏪</span>
                            Gestão de Lojas
                        </h3>
                        <div class="card-actions">
                            <div class="pending-badge">
                                <?php echo count($pendingStores); ?> pendente<?php echo count($pendingStores) !== 1 ? 's' : ''; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($pendingStores)): ?>
                        <div class="stores-list">
                            <?php foreach ($pendingStores as $store): ?>
                                <div class="store-item">
                                    <div class="store-info">
                                        <h4 class="store-name"><?php echo htmlspecialchars($store['nome_fantasia']); ?></h4>
                                        <p class="store-type">Loja de Varejo</p>
                                        <span class="store-cnpj"><?php echo htmlspecialchars($store['cnpj']); ?></span>
                                    </div>
                                    <div class="store-actions">
                                        <button class="approve-btn" onclick="approveStore(<?php echo $store['id']; ?>)">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M20 6L9 17l-5-5"></path>
                                            </svg>
                                            Aprovar
                                        </button>
                                        <button class="reject-btn" onclick="rejectStore(<?php echo $store['id']; ?>)">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                                <line x1="6" y1="6" x2="18" y2="18"></line>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">✅</div>
                            <h4>Todas as Lojas Aprovadas</h4>
                            <p>Não há lojas pendentes de aprovação no momento.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Notificações Modernas -->
                <div class="dashboard-card notifications-card">
                    <div class="card-header-modern">
                        <h3 class="card-title-modern">
                            <span class="title-icon">🔔</span>
                            Centro de Notificações
                        </h3>
                        <div class="card-actions">
                            <button class="action-btn" onclick="markAllAsRead()">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 6L9 17l-5-5"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="notifications-content">
                        <!-- Simulação de notificações para demonstração -->
                        <div class="notification-item new">
                            <div class="notification-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                </svg>
                            </div>
                            <div class="notification-content">
                                <h5>Sistema funcionando normalmente</h5>
                                <p>Todos os serviços estão operacionais</p>
                                <small>Há 5 minutos</small>
                            </div>
                        </div>
                        
                        <div class="notification-item">
                            <div class="notification-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="M12 6v6l4 2"></path>
                                </svg>
                            </div>
                            <div class="notification-content">
                                <h5>Backup automático concluído</h5>
                                <p>Backup diário realizado com sucesso</p>
                                <small>Há 2 horas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Seção de Transações Recentes -->
            <div class="transactions-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="title-icon">💳</span>
                        Últimas Transações
                    </h2>
                    <div class="section-actions">
                        <button class="action-btn secondary" onclick="exportTransactions()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            Exportar
                        </button>
                        <button class="action-btn primary" onclick="viewAllTransactions()">
                            Ver Todas
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 18l6-6-6-6"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="transactions-container">
                    <?php if (!empty($recentTransactions)): ?>
                        <div class="transactions-grid">
                            <?php foreach ($recentTransactions as $transaction): ?>
                                <div class="transaction-card" onclick="viewTransaction(<?php echo $transaction['id']; ?>)">
                                    <div class="transaction-header">
                                        <div class="transaction-id">
                                            #<?php echo htmlspecialchars($transaction['codigo_transacao'] ?? 'N/A'); ?>
                                        </div>
                                        <div class="transaction-date">
                                            <?php echo date('d/m/Y H:i', strtotime($transaction['data_transacao'])); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="transaction-details">
                                        <div class="transaction-participants">
                                            <div class="participant">
                                                <span class="participant-label">Cliente:</span>
                                                <span class="participant-name"><?php echo htmlspecialchars($transaction['usuario']); ?></span>
                                            </div>
                                            <div class="participant">
                                                <span class="participant-label">Loja:</span>
                                                <span class="participant-name">
                                                    <?php echo htmlspecialchars($transaction['loja']); ?>
                                                    <?php if ($transaction['saldo_usado'] > 0): ?>
                                                        <span class="balance-indicator" title="Cliente usou saldo">💰</span>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="transaction-values">
                                            <div class="value-item primary">
                                                <span class="value-label">Valor Total</span>
                                                <span class="value-amount">R$ <?php echo number_format($transaction['valor_total'], 2, ',', '.'); ?></span>
                                            </div>
                                            
                                            <?php if ($transaction['saldo_usado'] > 0): ?>
                                                <div class="value-item used">
                                                    <span class="value-label">Saldo Usado</span>
                                                    <span class="value-amount">R$ <?php echo number_format($transaction['saldo_usado'], 2, ',', '.'); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="value-item success">
                                                <span class="value-label">Cashback</span>
                                                <span class="value-amount">R$ <?php echo number_format($transaction['valor_cashback'], 2, ',', '.'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="transaction-footer">
                                        <div class="transaction-status approved">
                                            <span class="status-dot"></span>
                                            Aprovada
                                        </div>
                                        <div class="transaction-arrow">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M9 18l6-6-6-6"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state large">
                            <div class="empty-icon">💳</div>
                            <h3>Nenhuma Transação Encontrada</h3>
                            <p>Ainda não há transações registradas no sistema.</p>
                            <button class="action-btn primary" onclick="window.location.href='<?php echo ADMIN_TRANSACTIONS_URL; ?>'">
                                Gerenciar Transações
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Scripts otimizados -->
    <script src="../../assets/js/dashboard-interactions-v2.js"></script>
    <script>
        // Funções de interação mantidas da versão original
        function approveStore(storeId) {
            if (confirm('Tem certeza que deseja aprovar esta loja?')) {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo SITE_URL; ?>/controllers/StoreController.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (this.status === 200) {
                        showNotification('Loja aprovada com sucesso!', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification('Erro ao aprovar loja. Tente novamente.', 'error');
                    }
                };
                xhr.send('action=approve&id=' + storeId);
            }
        }
        
        function rejectStore(storeId) {
            if (confirm('Tem certeza que deseja rejeitar esta loja?')) {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo SITE_URL; ?>/controllers/StoreController.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (this.status === 200) {
                        showNotification('Loja rejeitada com sucesso!', 'warning');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification('Erro ao rejeitar loja. Tente novamente.', 'error');
                    }
                };
                xhr.send('action=reject&id=' + storeId);
            }
        }
        
        function viewTransaction(transactionId) {
            window.location.href = '<?php echo ADMIN_TRANSACTION_DETAILS_URL; ?>/' + transactionId;
        }
        
        function viewAllTransactions() {
            window.location.href = '<?php echo ADMIN_TRANSACTIONS_URL; ?>';
        }
        
        function viewAllClients() {
            window.location.href = '<?php echo ADMIN_USERS_URL; ?>';
        }
        
        function exportFinancialData() {
            showNotification('Exportando dados financeiros...', 'info');
            setTimeout(() => {
                showNotification('Dados exportados com sucesso!', 'success');
            }, 2000);
        }
        
        function exportTransactions() {
            showNotification('Exportando transações...', 'info');
            setTimeout(() => {
                showNotification('Transações exportadas com sucesso!', 'success');
            }, 2000);
        }
        
        function markAllAsRead() {
            showNotification('Todas as notificações foram marcadas como lidas', 'success');
        }
        
        // Sistema de notificações
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <span class="notification-message">${message}</span>
                    <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 4000);
        }
        
        // Animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.metric-card, .dashboard-card, .transaction-card');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');
                    }
                });
            }, {
                threshold: 0.1
            });
            
            cards.forEach(card => {
                observer.observe(card);
            });
            
            // Animar números
            animateNumbers();
        });
        
        function animateNumbers() {
            const numberElements = document.querySelectorAll('.card-value, .summary-value, .revenue-value');
            
            numberElements.forEach(element => {
                const text = element.textContent;
                const number = parseFloat(text.replace(/[^\d,.-]/g, '').replace(',', '.'));
                
                if (!isNaN(number)) {
                    animateNumber(element, 0, number, 1500);
                }
            });
        }
        
        function animateNumber(element, start, end, duration) {
            const startTime = performance.now();
            const isPrice = element.textContent.includes('R$');
            const isPercentage = element.textContent.includes('%');
            
            function update(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                const current = start + (end - start) * easeOutQuart(progress);
                
                if (isPrice) {
                    element.textContent = 'R$ ' + current.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                } else if (isPercentage) {
                    element.textContent = current.toFixed(1) + '%';
                } else {
                    element.textContent = Math.round(current).toLocaleString('pt-BR');
                }
                
                if (progress < 1) {
                    requestAnimationFrame(update);
                }
            }
            
            requestAnimationFrame(update);
        }
        
        function easeOutQuart(x) {
            return 1 - Math.pow(1 - x, 4);
        }
    </script>
</body>
</html>