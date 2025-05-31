<?php
// views/admin/purchases.php
// Definir o menu ativo na sidebar
$activeMenu = 'compras';

// Incluir conexão com o banco de dados e arquivos necessários
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/AdminController.php';
require_once '../../models/CashbackBalance.php';

// Iniciar sessão
session_start();

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== USER_TYPE_ADMIN) {
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

// Inicializar variáveis de paginação e filtros
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$filters = [];

// Processar filtros se enviados
if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
    $filters['data_inicio'] = $_GET['data_inicio'];
}

if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
    $filters['data_fim'] = $_GET['data_fim'];
}

if (isset($_GET['loja_id']) && !empty($_GET['loja_id'])) {
    $filters['loja_id'] = $_GET['loja_id'];
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}

if (isset($_GET['busca']) && !empty($_GET['busca'])) {
    $filters['busca'] = $_GET['busca'];
}

try {
    // Obter dados das transações com informações de saldo
    $result = AdminController::manageTransactionsWithBalance($filters, $page);

    // Verificar se houve erro
    $hasError = !$result['status'];
    $errorMessage = $hasError ? $result['message'] : '';

    // Dados para exibição na página
    $transactions = $hasError ? [] : $result['data']['transacoes'];
    $stores = $hasError ? [] : $result['data']['lojas'];
    $statistics = $hasError ? [] : $result['data']['estatisticas'];
    $pagination = $hasError ? [] : $result['data']['paginacao'];
} catch (Exception $e) {
    $hasError = true;
    $errorMessage = "Erro ao processar a requisição: " . $e->getMessage();
    $transactions = [];
    $stores = [];
    $statistics = [];
    $pagination = [];
}

// Função para formatar data
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

// Função para formatar valor
function formatCurrency($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

// Função auxiliar para construir query string preservando filtros existentes
function buildQueryString($exclude = []) {
    $params = $_GET;
    foreach ($exclude as $key) {
        unset($params[$key]);
    }
    return $params ? '&' . http_build_query($params) : '';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compras - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <link rel="stylesheet" href="../../assets/css/views/admin/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/views/admin/purchases.css">
    <link rel="stylesheet" href="../../assets/css/layout-fix.css">
    
</head>
<body>
    <?php include_once '../components/sidebar.php'; ?>
    
    <div class="main-content" id="mainContent">
        <div class="page-wrapper">
            <!-- Cabeçalho da Página -->
            <div class="page-header">
                <h1 class="page-title">💳 Compras & Transações</h1>
                <p class="page-subtitle">Gerencie todas as transações e analise o uso de saldo dos clientes</p>
            </div>
            
            <?php if ($hasError): ?>
                <div class="alert alert-danger">
                    <strong>Ops!</strong> <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php else: ?>
            
            <!-- Cards de Estatísticas -->
            <?php if (!empty($statistics)): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Total de Transações</span>
                        <div class="stat-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                                <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($statistics['total_transacoes']); ?></div>
                    <div class="stat-subtitle">Registradas no período</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Valor Original Total</span>
                        <div class="stat-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M16 8l-4 4-2-2"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo formatCurrency($statistics['valor_vendas_originais']); ?></div>
                    <div class="stat-subtitle">Antes de descontos</div>
                </div>
                
                <div class="stat-card balance-card">
                    <div class="stat-header">
                        <span class="stat-title">Saldo Usado</span>
                        <div class="stat-icon success">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="1" x2="12" y2="23"/>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo formatCurrency($statistics['total_saldo_usado']); ?></div>
                    <div class="stat-subtitle">Economia dos clientes</div>
                    <div class="stat-change">
                        <?php echo number_format($statistics['percentual_uso_saldo'], 1); ?>% das transações
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Valor Efetivo Pago</span>
                        <div class="stat-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                                <line x1="1" y1="10" x2="23" y2="10"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo formatCurrency($statistics['valor_liquido_pago']); ?></div>
                    <div class="stat-subtitle">Após uso de saldo</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Cashback Total</span>
                        <div class="stat-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2L2 7v10c0 5.55 3.84 10 9 10s9-4.45 9-10V7l-10-5z"/>
                                <path d="M9 12l2 2 4-4"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo formatCurrency($statistics['total_cashback']); ?></div>
                    <div class="stat-subtitle">Gerado para clientes</div>
                </div>
                
                <div class="stat-card balance-card">
                    <div class="stat-header">
                        <span class="stat-title">Transações c/ Saldo</span>
                        <div class="stat-icon success">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22,4 12,14.01 9,11.01"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($statistics['transacoes_com_saldo']); ?></div>
                    <div class="stat-subtitle"><?php echo number_format($statistics['percentual_uso_saldo'], 1); ?>% do total</div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Seção de Filtros -->
            <div class="filters-section">
                <div class="filters-header">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                    </svg>
                    <h3>Filtros Avançados</h3>
                </div>
                
                <form method="GET" action="" id="filtersForm">
                    <div class="filters-grid">
                        <!-- Filtro de Data -->
                        <div class="filter-group">
                            <label class="filter-label">Período</label>
                            <select class="filter-input" id="dataFilter" name="data_periodo" onchange="handleDateFilter()">
                                <option value="">Todas as datas</option>
                                <option value="today">Hoje</option>
                                <option value="yesterday">Ontem</option>
                                <option value="last_week">Última semana</option>
                                <option value="last_month">Último mês</option>
                                <option value="custom">Personalizado</option>
                            </select>
                        </div>
                        
                        <!-- Datas Personalizadas -->
                        <div class="filter-group" id="customDatesGroup" style="display: none;">
                            <label class="filter-label">Data Início</label>
                            <input type="date" class="filter-input" name="data_inicio" value="<?php echo $_GET['data_inicio'] ?? ''; ?>">
                        </div>
                        
                        <div class="filter-group" id="customDatesGroup2" style="display: none;">
                            <label class="filter-label">Data Fim</label>
                            <input type="date" class="filter-input" name="data_fim" value="<?php echo $_GET['data_fim'] ?? ''; ?>">
                        </div>
                        
                        <!-- Filtro de Loja -->
                        <div class="filter-group">
                            <label class="filter-label">Loja</label>
                            <select class="filter-input" name="loja_id">
                                <option value="">Todas as lojas</option>
                                <?php foreach ($stores as $store): ?>
                                    <option value="<?php echo $store['id']; ?>" <?php echo (isset($_GET['loja_id']) && $_GET['loja_id'] == $store['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($store['nome_fantasia']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Filtro de Status -->
                        <div class="filter-group">
                            <label class="filter-label">Status</label>
                            <select class="filter-input" name="status">
                                <option value="">Todos os status</option>
                                <option value="pendente" <?php echo (isset($_GET['status']) && $_GET['status'] === 'pendente') ? 'selected' : ''; ?>>Pendente</option>
                                <option value="aprovado" <?php echo (isset($_GET['status']) && $_GET['status'] === 'aprovado') ? 'selected' : ''; ?>>Aprovado</option>
                                <option value="cancelado" <?php echo (isset($_GET['status']) && $_GET['status'] === 'cancelado') ? 'selected' : ''; ?>>Cancelado</option>
                            </select>
                        </div>
                        
                        <!-- Busca -->
                        <div class="search-container">
                            <label class="filter-label">Buscar</label>
                            <div style="position: relative;">
                                <input type="text" class="filter-input search-input" name="busca" placeholder="ID, cliente, loja..." value="<?php echo htmlspecialchars($_GET['busca'] ?? ''); ?>">
                                <div class="search-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="11" cy="11" r="8"/>
                                        <path d="m21 21-4.35-4.35"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                            Limpar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                            </svg>
                            Aplicar Filtros
                        </button>
                        <button type="button" class="btn btn-outline" onclick="exportData()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            Exportar
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Tabela de Transações -->
            <div class="table-container">
                <div class="table-header">
                    <h3>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14,2 14,8 20,8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10,9 9,9 8,9"/>
                        </svg>
                        Lista de Transações
                    </h3>
                </div>
                
                <div class="table-wrapper">
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th>
                                    <div class="checkbox-container">
                                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                        <span class="checkbox-mark"></span>
                                    </div>
                                </th>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Loja</th>
                                <th>Valor Original</th>
                                <th>Saldo Usado</th>
                                <th>Valor Pago</th>
                                <th>Cashback</th>
                                <th>Data</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="11">
                                        <div class="empty-state">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.68 0C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1v7z"/>
                                                <polyline points="9 12 11 14 15 10"/>
                                            </svg>
                                            <h3>Nenhuma transação encontrada</h3>
                                            <p>Não há transações que correspondam aos filtros aplicados.</p>
                                            <button class="btn btn-primary" onclick="clearFilters()">
                                                Limpar Filtros
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $transaction): ?>
                                    <?php 
                                    $saldoUsado = floatval($transaction['saldo_usado'] ?? 0);
                                    $valorOriginal = floatval($transaction['valor_total']);
                                    $valorPago = $valorOriginal - $saldoUsado;
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="checkbox-container">
                                                <input type="checkbox" class="transaction-checkbox" value="<?php echo $transaction['id']; ?>">
                                                <span class="checkbox-mark"></span>
                                            </div>
                                        </td>
                                        <td>
                                            <strong>#<?php echo $transaction['id']; ?></strong>
                                        </td>
                                        <td>
                                            <div>
                                                <?php echo htmlspecialchars($transaction['cliente_nome']); ?>
                                                <?php if ($saldoUsado > 0): ?>
                                                    <div class="balance-indicator">
                                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <circle cx="12" cy="12" r="10"/>
                                                            <line x1="12" y1="6" x2="12" y2="18"/>
                                                            <line x1="6" y1="12" x2="18" y2="12"/>
                                                        </svg>
                                                        Usou Saldo
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($transaction['loja_nome']); ?></td>
                                        <td>
                                            <span class="value-display value-original"><?php echo formatCurrency($valorOriginal); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($saldoUsado > 0): ?>
                                                <span class="value-display value-used">-<?php echo formatCurrency($saldoUsado); ?></span>
                                            <?php else: ?>
                                                <span style="color: #999; font-style: italic;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div>
                                                <span class="value-display value-paid"><?php echo formatCurrency($valorPago); ?></span>
                                                <?php if ($saldoUsado > 0): ?>
                                                    <span class="economy-badge">Economizou</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="value-display"><?php echo formatCurrency($transaction['valor_cliente']); ?></span>
                                            <?php if ($transaction['valor_admin'] > 0 || $transaction['valor_loja'] > 0): ?>
                                                <br>
                                                <small style="color: #666; font-size: 11px;">
                                                    Admin: <?php echo formatCurrency($transaction['valor_admin']); ?>
                                                    <?php if ($transaction['valor_loja'] > 0): ?>
                                                        | Loja: <?php echo formatCurrency($transaction['valor_loja']); ?>
                                                    <?php endif; ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatDate($transaction['data_transacao']); ?></td>
                                        <td>
                                            <?php 
                                                $statusMap = [
                                                    'aprovado' => ['class' => 'status-approved', 'text' => 'Aprovado'],
                                                    'pendente' => ['class' => 'status-pending', 'text' => 'Pendente'],
                                                    'cancelado' => ['class' => 'status-canceled', 'text' => 'Cancelado']
                                                ];
                                                $status = $statusMap[$transaction['status']] ?? ['class' => 'status-pending', 'text' => ucfirst($transaction['status'])];
                                            ?>
                                            <span class="status-badge <?php echo $status['class']; ?>">
                                                <?php echo $status['text']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm action-btn" onclick="viewTransactionDetails(<?php echo $transaction['id']; ?>)">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                    <circle cx="12" cy="12" r="3"/>
                                                </svg>
                                                Detalhes
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Paginação -->
            <?php if (!empty($pagination) && $pagination['total_paginas'] > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=1<?php echo buildQueryString(['page']); ?>" class="arrow" title="Primeira página">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="11 17 6 12 11 7"/>
                                <polyline points="18 17 13 12 18 7"/>
                            </svg>
                        </a>
                        <a href="?page=<?php echo $page - 1; ?><?php echo buildQueryString(['page']); ?>" class="arrow" title="Página anterior">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="15 18 9 12 15 6"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                    
                    <?php 
                        $startPage = max(1, $page - 2);
                        $endPage = min($pagination['total_paginas'], $startPage + 4);
                        if ($endPage - $startPage < 4) {
                            $startPage = max(1, $endPage - 4);
                        }
                        
                        for ($i = $startPage; $i <= $endPage; $i++): 
                    ?>
                        <a href="?page=<?php echo $i; ?><?php echo buildQueryString(['page']); ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $pagination['total_paginas']): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo buildQueryString(['page']); ?>" class="arrow" title="Próxima página">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                        </a>
                        <a href="?page=<?php echo $pagination['total_paginas']; ?><?php echo buildQueryString(['page']); ?>" class="arrow" title="Última página">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 17 11 12 6 7"/>
                                <polyline points="13 17 18 12 13 7"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- Seção de Impacto do Saldo -->
            <?php if (!empty($statistics) && $statistics['total_saldo_usado'] > 0): ?>
            <div class="impact-section">
                <div class="impact-header">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M8 14s1.5 2 4 2 4-2 4-2"/>
                        <line x1="9" y1="9" x2="9.01" y2="9"/>
                        <line x1="15" y1="9" x2="15.01" y2="9"/>
                    </svg>
                    <h4>💰 Análise do Impacto do Sistema de Saldo</h4>
                </div>
                
                <div class="impact-grid">
                    <div class="impact-item">
                        <div class="impact-label">Economia dos Clientes</div>
                        <div class="impact-value"><?php echo formatCurrency($statistics['total_saldo_usado']); ?></div>
                    </div>
                    
                    <div class="impact-item">
                        <div class="impact-label">Redução na Receita das Lojas</div>
                        <div class="impact-value"><?php echo formatCurrency($statistics['total_saldo_usado']); ?></div>
                    </div>
                    
                    <div class="impact-item">
                        <div class="impact-label">Impacto na Comissão Klube Cash</div>
                        <div class="impact-value"><?php echo formatCurrency($statistics['total_saldo_usado'] * 0.1); ?></div>
                    </div>
                    
                    <div class="impact-item">
                        <div class="impact-label">Taxa de Adoção do Saldo</div>
                        <div class="impact-value"><?php echo number_format($statistics['percentual_uso_saldo'], 1); ?>%</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal de Detalhes da Transação -->
    <div id="transactionDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTransactionTitle">Detalhes da Transação</h3>
                <button class="modal-close" onclick="closeTransactionModal()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body" id="modalTransactionContent">
                <div class="loading" style="height: 200px;"></div>
            </div>
        </div>
    </div>
    
    <script>
        // Variáveis globais
        let selectedTransactions = [];
        
        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            initializeFilters();
            initializeTooltips();
        });
        
        // Funções de Filtro
        function initializeFilters() {
            // Aplicar filtros preservados da URL
            const urlParams = new URLSearchParams(window.location.search);
            
            // Verificar se há filtro personalizado ativo
            if (urlParams.get('data_inicio') || urlParams.get('data_fim')) {
                document.getElementById('dataFilter').value = 'custom';
                handleDateFilter();
            }
        }
        
        function handleDateFilter() {
            const filter = document.getElementById('dataFilter').value;
            const customGroups = document.querySelectorAll('#customDatesGroup, #customDatesGroup2');
            
            if (filter === 'custom') {
                customGroups.forEach(group => {
                    group.style.display = 'block';
                    group.style.animation = 'fadeIn 0.3s ease';
                });
            } else {
                customGroups.forEach(group => {
                    group.style.display = 'none';
                });
                
                // Aplicar filtros predefinidos automaticamente
                if (filter) {
                    applyPredefinedDateFilter(filter);
                }
            }
        }
        
        function applyPredefinedDateFilter(filter) {
            const today = new Date();
            let startDate, endDate;
            
            switch (filter) {
                case 'today':
                    startDate = formatDateForInput(today);
                    endDate = formatDateForInput(today);
                    break;
                case 'yesterday':
                    const yesterday = new Date(today);
                    yesterday.setDate(yesterday.getDate() - 1);
                    startDate = formatDateForInput(yesterday);
                    endDate = formatDateForInput(yesterday);
                    break;
                case 'last_week':
                    const weekStart = new Date(today);
                    weekStart.setDate(today.getDate() - 7);
                    startDate = formatDateForInput(weekStart);
                    endDate = formatDateForInput(today);
                    break;
                case 'last_month':
                    const monthStart = new Date(today);
                    monthStart.setMonth(today.getMonth() - 1);
                    startDate = formatDateForInput(monthStart);
                    endDate = formatDateForInput(today);
                    break;
            }
            
            if (startDate && endDate) {
                const queryParams = new URLSearchParams(window.location.search);
                queryParams.set('data_inicio', startDate);
                queryParams.set('data_fim', endDate);
                queryParams.delete('page'); // Reset pagination
                
                window.location.href = `${window.location.pathname}?${queryParams.toString()}`;
            }
        }
        
        function formatDateForInput(date) {
            return date.toISOString().split('T')[0];
        }
        
        function clearFilters() {
            window.location.href = window.location.pathname;
        }
        
        // Funções de Seleção
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.transaction-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
                updateSelection(checkbox);
            });
            
            updateBulkActions();
        }
        
        function updateSelection(checkbox) {
            const transactionId = parseInt(checkbox.value);
            
            if (checkbox.checked) {
                if (!selectedTransactions.includes(transactionId)) {
                    selectedTransactions.push(transactionId);
                }
            } else {
                const index = selectedTransactions.indexOf(transactionId);
                if (index > -1) {
                    selectedTransactions.splice(index, 1);
                }
            }
        }
        
        function updateBulkActions() {
            const bulkActions = document.getElementById('bulkActions');
            if (bulkActions) {
                bulkActions.style.display = selectedTransactions.length > 0 ? 'flex' : 'none';
            }
        }
        
        // Função para visualizar detalhes
        function viewTransactionDetails(transactionId) {
            const modal = document.getElementById('transactionDetailsModal');
            const content = document.getElementById('modalTransactionContent');
            
            modal.style.display = 'block';
            content.innerHTML = '<div class="loading">Carregando...</div>';
            
            console.log('Buscando transação:', transactionId);
            
            fetch('../../controllers/AdminController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=transaction_details_with_balance&transaction_id=${transactionId}`
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.text();
            })
            .then(text => {
                console.log('Response text:', text);
                
                // Tentar fazer parse JSON
                try {
                    const data = JSON.parse(text);
                    if (data.status) {
                        renderTransactionDetailsWithBalance(data.data);
                    } else {
                        content.innerHTML = `<div class="alert alert-danger">Erro: ${data.message}</div>`;
                    }
                } catch (e) {
                    console.error('Erro JSON:', e);
                    // Mostrar resposta raw para debug
                    content.innerHTML = `
                        <div class="alert alert-danger">
                            <strong>Debug - Resposta do servidor:</strong><br>
                            <pre style="white-space: pre-wrap; font-size: 12px;">${text}</pre>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Erro fetch:', error);
                content.innerHTML = `<div class="alert alert-danger">Erro de conexão: ${error.message}</div>`;
            });
        }


        function renderTransactionDetailsWithBalance(transaction) {
    // Garantir que os valores sejam números
    const valorOriginal = parseFloat(transaction.valor_total) || 0;
    const valorCliente = parseFloat(transaction.valor_cliente) || 0;
    const valorAdmin = parseFloat(transaction.valor_admin) || 0;
    const valorLoja = parseFloat(transaction.valor_loja) || 0;
    const saldoUsado = parseFloat(transaction.saldo_usado) || 0;
    const valorPago = valorOriginal - saldoUsado;
    
    const content = document.getElementById('modalTransactionContent');
    
    let html = `
        <div class="transaction-details" style="max-width: none;">
            <div class="detail-grid" style="grid-template-columns: 1fr; gap: 20px;">
                <!-- Informações Básicas -->
                <div class="detail-card">
                    <h4 style="color: var(--primary-color); margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14,2 14,8 20,8"/>
                        </svg>
                        Informações da Transação
                    </h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div class="detail-item" style="flex-direction: column; align-items: flex-start; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                            <span class="detail-label" style="margin-bottom: 5px;">Código da Transação</span>
                            <span class="detail-value" style="font-weight: 600; color: var(--dark-gray);">${transaction.codigo_transacao || 'Não informado'}</span>
                        </div>
                        <div class="detail-item" style="flex-direction: column; align-items: flex-start; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                            <span class="detail-label" style="margin-bottom: 5px;">Data da Transação</span>
                            <span class="detail-value" style="font-weight: 600; color: var(--dark-gray);">${formatDate(transaction.data_transacao)}</span>
                        </div>
                        <div class="detail-item" style="flex-direction: column; align-items: flex-start; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                            <span class="detail-label" style="margin-bottom: 5px;">Cliente</span>
                            <span class="detail-value" style="font-weight: 600; color: var(--dark-gray);">
                                ${transaction.cliente_nome || 'Não identificado'}
                                <br><small style="color: #666;">${transaction.cliente_email || ''}</small>
                                ${saldoUsado > 0 ? '<br><span class="balance-indicator" style="margin-top: 5px; display: inline-block;">💰 Usou Saldo</span>' : ''}
                            </span>
                        </div>
                        <div class="detail-item" style="flex-direction: column; align-items: flex-start; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                            <span class="detail-label" style="margin-bottom: 5px;">Loja</span>
                            <span class="detail-value" style="font-weight: 600; color: var(--dark-gray);">
                                ${transaction.loja_nome || 'Não identificada'}
                                ${transaction.loja_categoria ? `<br><small style="color: #666;">${transaction.loja_categoria}</small>` : ''}
                            </span>
                        </div>
                        <div class="detail-item" style="flex-direction: column; align-items: flex-start; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                            <span class="detail-label" style="margin-bottom: 5px;">Status da Transação</span>
                            <span class="detail-value">${getStatusBadge(transaction.status)}</span>
                        </div>
                        ${transaction.status_pagamento ? `
                            <div class="detail-item" style="flex-direction: column; align-items: flex-start; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                                <span class="detail-label" style="margin-bottom: 5px;">Status do Pagamento</span>
                                <span class="detail-value">${getPaymentStatusBadge(transaction.status_pagamento)}</span>
                            </div>
                        ` : ''}
                    </div>
                    ${transaction.descricao ? `
                        <div style="margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                            <span class="detail-label" style="display: block; margin-bottom: 5px;">Descrição</span>
                            <span class="detail-value">${transaction.descricao}</span>
                        </div>
                    ` : ''}
                </div>
                
                <!-- Informações Financeiras -->
                <div class="detail-card">
                    <h4 style="color: var(--primary-color); margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                        Valores Financeiros
                    </h4>
                    
                    <div class="financial-breakdown" style="margin: 0;">
                        <div class="breakdown-item">
                            <span>Valor original da venda:</span>
                            <span class="value" style="color: var(--dark-gray); font-size: 16px;">${formatCurrency(valorOriginal)}</span>
                        </div>
                        ${saldoUsado > 0 ? `
                            <div class="breakdown-item" style="color: #e74c3c;">
                                <span>Saldo usado pelo cliente:</span>
                                <span class="value">-${formatCurrency(saldoUsado)}</span>
                            </div>
                            <div class="breakdown-item total">
                                <span>Valor efetivamente pago:</span>
                                <span class="value" style="color: #28a745; font-size: 18px;">${formatCurrency(valorPago)}</span>
                            </div>
                        ` : ''}
                        
                        <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #e9ecef;">
                            <h5 style="margin-bottom: 10px; color: #666;">Distribuição do Cashback (10% do valor pago)</h5>
                            <div class="breakdown-item">
                                <span>Cashback do cliente (5%):</span>
                                <span class="value" style="color: var(--primary-color);">${formatCurrency(valorCliente)}</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Comissão Klube Cash (5%):</span>
                                <span class="value" style="color: var(--primary-color);">${formatCurrency(valorAdmin)}</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Comissão da Loja:</span>
                                <span class="value" style="color: #666;">${formatCurrency(valorLoja)}</span>
                            </div>
                            <div class="breakdown-item total">
                                <span>Total de Comissões:</span>
                                <span class="value" style="color: #28a745;">${formatCurrency(valorCliente + valorAdmin + valorLoja)}</span>
                            </div>
                        </div>
                    </div>
                </div>
    `;
    
    // Se houver informações de pagamento
    if (transaction.pagamento_id) {
        html += `
            <div class="detail-card">
                <h4 style="color: var(--primary-color); margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="5" width="20" height="14" rx="2"/>
                        <line x1="2" y1="10" x2="22" y2="10"/>
                    </svg>
                    Informações de Pagamento
                </h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div class="detail-item" style="flex-direction: column; align-items: flex-start; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <span class="detail-label" style="margin-bottom: 5px;">ID do Pagamento</span>
                        <span class="detail-value" style="font-weight: 600;">#${transaction.pagamento_id}</span>
                    </div>
                    <div class="detail-item" style="flex-direction: column; align-items: flex-start; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <span class="detail-label" style="margin-bottom: 5px;">Método</span>
                        <span class="detail-value" style="font-weight: 600;">${transaction.metodo_pagamento || 'Não informado'}</span>
                    </div>
                    ${transaction.numero_referencia ? `
                        <div class="detail-item" style="flex-direction: column; align-items: flex-start; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                            <span class="detail-label" style="margin-bottom: 5px;">Referência</span>
                            <span class="detail-value" style="font-weight: 600;">${transaction.numero_referencia}</span>
                        </div>
                    ` : ''}
                    ${transaction.data_pagamento ? `
                        <div class="detail-item" style="flex-direction: column; align-items: flex-start; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                            <span class="detail-label" style="margin-bottom: 5px;">Data de Aprovação</span>
                            <span class="detail-value" style="font-weight: 600;">${formatDate(transaction.data_pagamento)}</span>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }
    
    // Se há movimentações
    if (transaction.movimentacoes && transaction.movimentacoes.length > 0) {
        html += `
            <div class="detail-card">
                <h4 style="color: var(--primary-color); margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 3v18h18"/>
                        <path d="m19 9-5 5-4-4-3 3"/>
                    </svg>
                    Histórico de Movimentações
                </h4>
                <div style="max-height: 300px; overflow-y: auto;">
                    <table style="width: 100%; font-size: 14px;">
                        <thead>
                            <tr style="background: #f8f9fa;">
                                <th style="padding: 10px; text-align: left;">Data</th>
                                <th style="padding: 10px; text-align: left;">Tipo</th>
                                <th style="padding: 10px; text-align: left;">Descrição</th>
                                <th style="padding: 10px; text-align: right;">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${transaction.movimentacoes.map(mov => `
                                <tr style="border-bottom: 1px solid #e9ecef;">
                                    <td style="padding: 10px;">${formatDate(mov.data_operacao)}</td>
                                    <td style="padding: 10px;">
                                        <span class="badge ${mov.tipo_operacao === 'credito' ? 'badge-success' : 'badge-danger'}">
                                            ${mov.tipo_operacao.toUpperCase()}
                                        </span>
                                    </td>
                                    <td style="padding: 10px;">${mov.descricao || 'Sem descrição'}</td>
                                    <td style="padding: 10px; text-align: right; font-weight: 600; color: ${mov.tipo_operacao === 'credito' ? '#28a745' : '#e74c3c'};">
                                        ${mov.tipo_operacao === 'credito' ? '+' : '-'}${formatCurrency(mov.valor)}
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }
    
    if (saldoUsado > 0) {
        html += `
            <!-- Análise do Impacto -->
            <div class="detail-card" style="background: linear-gradient(135deg, #f8fff8 0%, #e8f5e9 100%); border-left: 4px solid #28a745;">
                <h4 style="color: #2e7d32; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M8 14s1.5 2 4 2 4-2 4-2"/>
                        <line x1="9" y1="9" x2="9.01" y2="9"/>
                        <line x1="15" y1="9" x2="15.01" y2="9"/>
                    </svg>
                    💰 Análise do Uso de Saldo
                </h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        <div style="font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600; text-transform: uppercase;">Economia do Cliente</div>
                        <div style="font-size: 16px; font-weight: 700; color: #2e7d32;">${formatCurrency(saldoUsado)}</div>
                    </div>
                    <div style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        <div style="font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600; text-transform: uppercase;">Redução na Comissão</div>
                        <div style="font-size: 16px; font-weight: 700; color: #dc3545;">-${formatCurrency(saldoUsado * 0.1)}</div>
                        <small style="color: #666;">10% do valor usado</small>
                    </div>
                    <div style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        <div style="font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600; text-transform: uppercase;">Percentual Usado</div>
                        <div style="font-size: 16px; font-weight: 700; color: #2e7d32;">${((saldoUsado / valorOriginal) * 100).toFixed(1)}%</div>
                        <small style="color: #666;">do valor total</small>
                    </div>
                </div>
            </div>
        `;
    }
    
    html += `</div></div>`;
    content.innerHTML = html;
}

        // Função auxiliar para formatar badge de status de pagamento
        function getPaymentStatusBadge(status) {
            const badges = {
                'pendente': '<span class="status-badge status-pending">Pendente</span>',
                'em_processamento': '<span class="status-badge status-payment">Em Processamento</span>',
                'aprovado': '<span class="status-badge status-approved">Aprovado</span>',
                'rejeitado': '<span class="status-badge status-canceled">Rejeitado</span>'
            };
            return badges[status] || `<span class="status-badge status-pending">${status || 'Sem pagamento'}</span>`;
        }
        
        
        function closeTransactionModal() {
            const modal = document.getElementById('transactionDetailsModal');
            modal.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => {
                modal.style.display = 'none';
                modal.style.animation = '';
            }, 300);
        }
        
        // Funções auxiliares
        function getStatusBadge(status) {
            const badges = {
                'aprovado': '<span class="status-badge status-approved">Aprovado</span>',
                'pendente': '<span class="status-badge status-pending">Pendente</span>',
                'cancelado': '<span class="status-badge status-canceled">Cancelado</span>'
            };
            return badges[status] || `<span class="status-badge status-pending">${status}</span>`;
        }
        
        function formatCurrency(value) {
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }).format(value);
        }
        
        function formatDate(dateString) {
            return new Date(dateString).toLocaleString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        // Função de exportação
        function exportData() {
            const exportBtn = event.target;
            const originalText = exportBtn.innerHTML;
            
            exportBtn.innerHTML = '<div style="width: 16px; height: 16px; border: 2px solid transparent; border-top: 2px solid white; border-radius: 50%; animation: spin 1s linear infinite;"></div> Exportando...';
            exportBtn.disabled = true;
            
            // Construir URL com filtros atuais
            const queryParams = new URLSearchParams(window.location.search);
            queryParams.set('action', 'export_transactions');
            
            fetch('../../controllers/AdminController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: queryParams.toString()
            })
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `transacoes_${new Date().toISOString().split('T')[0]}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            })
            .catch(error => {
                console.error('Erro na exportação:', error);
                alert('Erro ao exportar dados. Tente novamente.');
            })
            .finally(() => {
                exportBtn.innerHTML = originalText;
                exportBtn.disabled = false;
            });
        }
        
        // Tooltips simples
        function initializeTooltips() {
            const elements = document.querySelectorAll('[title]');
            elements.forEach(element => {
                element.addEventListener('mouseenter', showTooltip);
                element.addEventListener('mouseleave', hideTooltip);
            });
        }
        
        function showTooltip(event) {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = event.target.title;
            tooltip.style.cssText = `
                position: absolute;
                background: #333;
                color: white;
                padding: 8px 12px;
                border-radius: 6px;
                font-size: 12px;
                z-index: 1001;
                pointer-events: none;
                white-space: nowrap;
                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            `;
            
            document.body.appendChild(tooltip);
            
            const rect = event.target.getBoundingClientRect();
            tooltip.style.left = `${rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2)}px`;
            tooltip.style.top = `${rect.top - tooltip.offsetHeight - 8}px`;
            
            event.target.removeAttribute('title');
            event.target.tooltipText = tooltip.textContent;
        }
        
        function hideTooltip(event) {
            const tooltip = document.querySelector('.tooltip');
            if (tooltip) {
                tooltip.remove();
            }
            if (event.target.tooltipText) {
                event.target.title = event.target.tooltipText;
                delete event.target.tooltipText;
            }
        }
        
        // Fechar modal ao clicar fora
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('transactionDetailsModal');
            if (event.target === modal) {
                closeTransactionModal();
            }
        });
        
        // Adicionar animação de fadeOut ao CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeOut {
                from {
                    opacity: 1;
                    transform: scale(1);
                }
                to {
                    opacity: 0;
                    transform: scale(0.95);
                }
            }
        `;
        document.head.appendChild(style);
        
        // Adicionar listeners aos checkboxes
        document.addEventListener('change', function(event) {
            if (event.target.classList.contains('transaction-checkbox')) {
                updateSelection(event.target);
                updateBulkActions();
            }
        });
        
        // Auto-submit do formulário quando filtro rápido é alterado
        document.getElementById('dataFilter').addEventListener('change', function() {
            if (this.value !== 'custom') {
                // Delay para permitir que a função handleDateFilter execute primeiro
                setTimeout(() => {
                    if (this.value !== 'custom') {
                        document.getElementById('filtersForm').submit();
                    }
                }, 100);
            }
        });
    </script>
</body>
</html>''