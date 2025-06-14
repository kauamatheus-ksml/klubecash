<?php
// views/stores/financial.php
require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/StoreController.php';
require_once '../../controllers/TransactionController.php';

session_start();

// Verificações de autenticação
if (!AuthController::isAuthenticated()) {
    header('Location: ' . LOGIN_URL . '?error=' . urlencode('Você precisa fazer login para acessar esta página.'));
    exit;
}

if (!AuthController::isStore()) {
    header('Location: ' . LOGIN_URL . '?error=' . urlencode('Acesso restrito a lojas parceiras.'));
    exit;
}

$userId = AuthController::getCurrentUserId();
$db = Database::getConnection();
$storeQuery = $db->prepare("SELECT * FROM lojas WHERE usuario_id = :usuario_id");
$storeQuery->bindParam(':usuario_id', $userId);
$storeQuery->execute();

if ($storeQuery->rowCount() == 0) {
    header('Location: ' . LOGIN_URL . '?error=' . urlencode('Sua conta não está associada a nenhuma loja.'));
    exit;
}

$store = $storeQuery->fetch(PDO::FETCH_ASSOC);
$storeId = $store['id'];
$storeName = $store['nome_fantasia'];

// Definir menu ativo
$activeMenu = 'financial';

// Obter aba ativa (padrão: resumo)
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'resumo';
$validTabs = ['resumo', 'transacoes', 'pendentes', 'historico'];
if (!in_array($activeTab, $validTabs)) {
    $activeTab = 'resumo';
}

// Parâmetros de paginação e filtros
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$filters = [];

// Aplicar filtros se fornecidos
if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
    $filters['data_inicio'] = $_GET['data_inicio'];
}
if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
    $filters['data_fim'] = $_GET['data_fim'];
}
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}
if (isset($_GET['metodo_pagamento']) && !empty($_GET['metodo_pagamento'])) {
    $filters['metodo_pagamento'] = $_GET['metodo_pagamento'];
}

// Obter dados consolidados para todas as seções
$resumoGeral = TransactionController::getStoreFinancialSummary($storeId);
$transacoes = TransactionController::getStoreTransactions($storeId, $filters, $page);
$pendentes = TransactionController::getPendingTransactionsWithBalance($storeId, $filters, $page);
$historico = TransactionController::getPaymentHistoryWithBalance($storeId, $filters, $page);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <title>Financeiro - Klube Cash</title>
    
    <link rel="stylesheet" href="../../assets/css/views/stores/financial.css">
    <link rel="stylesheet" href="../../assets/css/openpix-styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include_once '../components/sidebar-store.php'; ?>
    
    <div class="main-content" id="mainContent">
        <div class="financial-wrapper">
            <!-- Header da página -->
            <div class="financial-header">
                <div class="header-content">
                    <h1>Financeiro</h1>
                    <p class="subtitle">Gestão financeira completa para <?php echo htmlspecialchars($storeName); ?></p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openFilterModal()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M3 17v2h6v-2H3zM3 5v2h10V5H3zm10 16v-2h8v-2h-8v-2h-2v6h2zM7 9v2H3v2h4v2h2V9H7zm14 4v-2H11v2h10zm-6-4h2V7h4V5h-4V3h-2v6z"/>
                        </svg>
                        Filtros
                    </button>
                </div>
            </div>

            <!-- Resumo Financeiro -->
            <div class="financial-summary">
                <div class="summary-card">
                    <div class="card-content">
                        <h3>Total de Vendas</h3>
                        <div class="card-value" id="totalSales">R$ <?php echo number_format($resumoGeral['total_vendas'] ?? 0, 2, ',', '.'); ?></div>
                        <div class="card-period">Valor total processado</div>
                    </div>
                    <div class="card-icon sales">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12L8.1 13h7.45c.75 0 1.41-.41 1.75-1.03L21.7 4H5.21l-.94-2H1zm16 16c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                        </svg>
                    </div>
                </div>
                
                <div class="summary-card">
                    <div class="card-content">
                        <h3>Comissões Pagas</h3>
                        <div class="card-value" id="totalCommissions">R$ <?php echo number_format($resumoGeral['comissoes_pagas'] ?? 0, 2, ',', '.'); ?></div>
                        <div class="card-period">Total já pago</div>
                    </div>
                    <div class="card-icon paid">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                        </svg>
                    </div>
                </div>
                
                <div class="summary-card">
                    <div class="card-content">
                        <h3>Pendente de Pagamento</h3>
                        <div class="card-value warning" id="totalPending">R$ <?php echo number_format($resumoGeral['comissoes_pendentes'] ?? 0, 2, ',', '.'); ?></div>
                        <div class="card-period">Aguardando pagamento</div>
                    </div>
                    <div class="card-icon pending">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                    </div>
                </div>
                
                <div class="summary-card">
                    <div class="card-content">
                        <h3>Saldo Usado</h3>
                        <div class="card-value" id="totalBalance">R$ <?php echo number_format($resumoGeral['saldo_usado'] ?? 0, 2, ',', '.'); ?></div>
                        <div class="card-period">Cashback utilizado</div>
                    </div>
                    <div class="card-icon balance">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Navegação por Abas -->
            <div class="tabs-navigation">
                <button class="tab-button <?php echo $activeTab === 'resumo' ? 'active' : ''; ?>" 
                        onclick="switchTab('resumo')">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
                    </svg>
                    Resumo
                </button>
                <button class="tab-button <?php echo $activeTab === 'transacoes' ? 'active' : ''; ?>" 
                        onclick="switchTab('transacoes')">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 9H9V9h10v2zm-4 4H9v-2h6v2zm4-8H9V5h10v2z"/>
                    </svg>
                    Todas as Transações
                </button>
                <button class="tab-button <?php echo $activeTab === 'pendentes' ? 'active' : ''; ?>" 
                        onclick="switchTab('pendentes')">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                    Pendentes
                    <?php if (($resumoGeral['transacoes_pendentes'] ?? 0) > 0): ?>
                        <span class="badge warning"><?php echo $resumoGeral['transacoes_pendentes']; ?></span>
                    <?php endif; ?>
                </button>
                <button class="tab-button <?php echo $activeTab === 'historico' ? 'active' : ''; ?>" 
                        onclick="switchTab('historico')">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/>
                    </svg>
                    Histórico
                </button>
            </div>

            <!-- Conteúdo das Abas -->
            <div class="tab-content">
                <!-- Aba Resumo -->
                <div id="tab-resumo" class="tab-pane <?php echo $activeTab === 'resumo' ? 'active' : ''; ?>">
                    <div class="resume-content">
                        <!-- Gráfico de Evolução -->
                        <div class="chart-container">
                            <div class="chart-header">
                                <h3>Evolução Financeira</h3>
                                <div class="chart-controls">
                                    <select id="chartPeriod" onchange="updateChart()">
                                        <option value="7">Últimos 7 dias</option>
                                        <option value="30" selected>Últimos 30 dias</option>
                                        <option value="90">Últimos 90 dias</option>
                                    </select>
                                </div>
                            </div>
                            <canvas id="evolutionChart"></canvas>
                        </div>
                        
                        <!-- Últimas Atividades -->
                        <div class="recent-activities">
                            <h3>Últimas Atividades</h3>
                            <div class="activities-list">
                                <!-- Será preenchido via JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Aba Transações -->
                <div id="tab-transacoes" class="tab-pane <?php echo $activeTab === 'transacoes' ? 'active' : ''; ?>">
                    <div class="transactions-content">
                        <div class="content-header">
                            <h3>Todas as Transações</h3>
                            <div class="header-actions">
                                <button class="btn btn-secondary" onclick="exportTransactions()">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 2 2h8c1.1 0 2-.9 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/>
                                    </svg>
                                    Exportar
                                </button>
                            </div>
                        </div>
                        
                        <div class="table-container">
                            <table class="transactions-table">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Cliente</th>
                                        <th>Valor Venda</th>
                                        <th>Saldo Usado</th>
                                        <th>Comissão</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="transactionsTableBody">
                                    <!-- Será preenchido via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="pagination-container">
                            <!-- Paginação será inserida via JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- Aba Pendentes -->
                <div id="tab-pendentes" class="tab-pane <?php echo $activeTab === 'pendentes' ? 'active' : ''; ?>">
                    <div class="pending-content">
                        <div class="content-header">
                            <h3>Comissões Pendentes</h3>
                            <div class="header-actions">
                                <button class="btn btn-primary" onclick="selectAllPending()" id="selectAllBtn">
                                    Selecionar Todas
                                </button>
                                <button class="btn btn-success" onclick="paySelectedCommissions()" id="paySelectedBtn" disabled>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                    </svg>
                                    Pagar Selecionadas
                                </button>
                            </div>
                        </div>
                        
                        <div class="pending-summary">
                            <div class="summary-item">
                                <span class="label">Total Selecionado:</span>
                                <span class="value" id="selectedTotal">R$ 0,00</span>
                            </div>
                            <div class="summary-item">
                                <span class="label">Transações Selecionadas:</span>
                                <span class="value" id="selectedCount">0</span>
                            </div>
                        </div>
                        
                        <div class="table-container">
                            <table class="pending-table">
                                <thead>
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()">
                                        </th>
                                        <th>Data</th>
                                        <th>Cliente</th>
                                        <th>Valor Venda</th>
                                        <th>Saldo Usado</th>
                                        <th>Comissão</th>
                                        <th>Dias Pendente</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="pendingTableBody">
                                    <!-- Será preenchido via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Aba Histórico -->
                <div id="tab-historico" class="tab-pane <?php echo $activeTab === 'historico' ? 'active' : ''; ?>">
                    <div class="history-content">
                        <div class="content-header">
                            <h3>Histórico de Pagamentos</h3>
                            <div class="header-actions">
                                <button class="btn btn-secondary" onclick="exportPayments()">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 2 2h8c1.1 0 2-.9 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/>
                                    </svg>
                                    Exportar
                                </button>
                            </div>
                        </div>
                        
                        <div class="table-container">
                            <table class="history-table">
                                <thead>
                                    <tr>
                                        <th>#ID</th>
                                        <th>Data Pagamento</th>
                                        <th>Valor Vendas</th>
                                        <th>Saldo Usado</th>
                                        <th>Comissão Paga</th>
                                        <th>Método</th>
                                        <th>Status</th>
                                        <th>Transações</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="historyTableBody">
                                    <!-- Será preenchido via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Filtros -->
    <div id="filterModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Filtros Avançados</h3>
                <button class="modal-close" onclick="closeFilterModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="filterForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="filterDataInicio">Data Início</label>
                            <input type="date" id="filterDataInicio" name="data_inicio" 
                                   value="<?php echo $_GET['data_inicio'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="filterDataFim">Data Fim</label>
                            <input type="date" id="filterDataFim" name="data_fim" 
                                   value="<?php echo $_GET['data_fim'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="filterStatus">Status</label>
                            <select id="filterStatus" name="status">
                                <option value="">Todos</option>
                                <option value="pendente" <?php echo ($_GET['status'] ?? '') === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                <option value="aprovado" <?php echo ($_GET['status'] ?? '') === 'aprovado' ? 'selected' : ''; ?>>Aprovado</option>
                                <option value="rejeitado" <?php echo ($_GET['status'] ?? '') === 'rejeitado' ? 'selected' : ''; ?>>Rejeitado</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="filterMetodo">Método de Pagamento</label>
                            <select id="filterMetodo" name="metodo_pagamento">
                                <option value="">Todos</option>
                                <option value="pix_openpix" <?php echo ($_GET['metodo_pagamento'] ?? '') === 'pix_openpix' ? 'selected' : ''; ?>>PIX (OpenPix)</option>
                                <option value="mercadopago" <?php echo ($_GET['metodo_pagamento'] ?? '') === 'mercadopago' ? 'selected' : ''; ?>>Mercado Pago</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="clearFilters()">Limpar</button>
                <button class="btn btn-primary" onclick="applyFilters()">Aplicar</button>
            </div>
        </div>
    </div>

    <!-- Modal de Detalhes da Transação -->
    <div id="transactionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detalhes da Transação</h3>
                <button class="modal-close" onclick="closeTransactionModal()">&times;</button>
            </div>
            <div class="modal-body" id="transactionModalBody">
                <!-- Conteúdo será inserido via JavaScript -->
            </div>
        </div>
    </div>

    <!-- Modal de Pagamento -->
    <div id="paymentModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h3>Realizar Pagamento de Comissões</h3>
                <button class="modal-close" onclick="closePaymentModal()">&times;</button>
            </div>
            <div class="modal-body" id="paymentModalBody">
                <!-- Conteúdo será inserido via JavaScript -->
            </div>
        </div>
    </div>

    <script src="../../assets/js/stores/financial.js"></script>
</body>
</html>