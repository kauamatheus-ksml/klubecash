<?php
// views/stores/transactions.php
// Incluir arquivos de configuração
require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
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
    header('Location: ' . LOGIN_URL . '?error=' . urlencode('Sua conta não está associada a nenhuma loja. Entre em contato com o suporte.'));
    exit;
}

// Obter os dados da loja
$store = $storeQuery->fetch(PDO::FETCH_ASSOC);
$storeId = $store['id'];

// Definir menu ativo
$activeMenu = 'transactions';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transações - Klube Cash</title>
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
    
    /* Seções */
    .transactions-section {
        background-color: var(--white);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        box-shadow: var(--shadow-md);
        margin-bottom: 2rem;
    }
    
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.25rem;
    }
    
    .section-header h2 {
        font-size: 1.25rem;
        color: var(--secondary-color);
        margin: 0;
        font-weight: 600;
        display: flex;
        align-items: center;
    }
    
    .section-header h2::after {
        content: '';
        height: 3px;
        width: 2rem;
        background-color: var(--primary-color);
        margin-left: 0.75rem;
        border-radius: 3px;
    }
    
    .btn {
        padding: 0.6rem 1.2rem;
        border-radius: var(--border-radius);
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        transition: var(--transition);
        border: none;
        cursor: pointer;
        font-size: 0.9rem;
    }
    
    .btn-primary {
        background-color: var(--primary-color);
        color: var(--white);
    }
    
    .btn-primary:hover {
        background-color: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 122, 0, 0.25);
    }
    
    .btn-success {
        background-color: var(--success-color);
        color: var(--white);
    }
    
    .btn-success:hover {
        background-color: #218838;
        transform: translateY(-2px);
    }
    
    .btn-outline-primary {
        background-color: transparent;
        color: var(--primary-color);
        border: 2px solid var(--primary-color);
    }
    
    .btn-outline-primary:hover {
        background-color: var(--primary-color);
        color: var(--white);
    }
    
    .btn-sm {
        padding: 0.4rem 0.8rem;
        font-size: 0.8rem;
    }
    
    /* Tabela */
    .table-responsive {
        overflow-x: auto;
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
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
        background-color: var(--light-gray);
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
    
    /* Loading */
    .loading-state {
        text-align: center;
        padding: 3rem 1rem;
    }
    
    .spinner {
        width: 40px;
        height: 40px;
        border: 4px solid var(--light-gray);
        border-top: 4px solid var(--primary-color);
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 1rem;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Modal */
    .modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        display: none;
    }
    
    .modal {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: var(--white);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-lg);
        z-index: 1001;
        display: none;
        min-width: 500px;
        max-width: 90%;
        max-height: 90%;
        overflow-y: auto;
    }
    
    .modal-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--light-gray);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--secondary-color);
        margin: 0;
    }
    
    .modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--medium-gray);
        padding: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: var(--transition);
    }
    
    .modal-close:hover {
        background-color: var(--light-gray);
        color: var(--dark-gray);
    }
    
    .modal-body {
        padding: 1.5rem;
    }
    
    .modal-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid var(--light-gray);
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
    }
    
    .form-group {
        margin-bottom: 1rem;
    }
    
    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--secondary-color);
    }
    
    .form-control {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #E1E5EA;
        border-radius: 8px;
        font-size: 1rem;
        transition: var(--transition);
    }
    
    .form-control:focus {
        border-color: var(--primary-color);
        outline: none;
        box-shadow: 0 0 0 3px rgba(255, 122, 0, 0.1);
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    
    /* Paginação */
    .pagination {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 2rem;
        list-style: none;
        padding: 0;
    }
    
    .pagination a {
        padding: 0.5rem 1rem;
        border: 2px solid var(--light-gray);
        border-radius: 8px;
        text-decoration: none;
        color: var(--medium-gray);
        transition: var(--transition);
    }
    
    .pagination a:hover,
    .pagination a.active {
        background-color: var(--primary-color);
        color: var(--white);
        border-color: var(--primary-color);
    }
    
    /* Responsividade */
    @media (max-width: 991.98px) {
        .main-content {
            margin-left: 0;
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
        
        .main-content {
            padding: 1rem;
        }
        
        .dashboard-title {
            font-size: 1.5rem;
        }
        
        .card-value {
            font-size: 1.5rem;
        }
        
        .section-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .modal {
            min-width: 95%;
        }
        
        .form-row {
            grid-template-columns: 1fr;
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
    
    @media (max-width: 575.98px) {
        .summary-cards {
            grid-template-columns: 1fr;
        }
    }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Incluir o componente sidebar -->
        <?php include_once '../components/sidebar-store.php'; ?>
        
        <div class="main-content" id="mainContent">
            <div class="dashboard-header">
                <div>
                    <h1 class="dashboard-title">Minhas Transações</h1>
                    <p class="welcome-user">Loja: <?php echo htmlspecialchars($store['nome_fantasia']); ?></p>
                </div>
            </div>
            
            <!-- Cards de estatísticas -->
            <div class="summary-cards">
                <div class="card">
                    <div class="card-content">
                        <h3>Total de Transações</h3>
                        <div class="card-value" id="totalTransactions">-</div>
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
                        <div class="card-value" id="totalSales">-</div>
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
                        <h3>Transações Pendentes</h3>
                        <div class="card-value" id="pendingTransactions">-</div>
                        <div class="card-period">Aguardando pagamento</div>
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
                        <h3>Total Comissões</h3>
                        <div class="card-value" id="totalCommissions">-</div>
                        <div class="card-period">Valor total de comissões</div>
                    </div>
                    <div class="card-icon info">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Lista de Transações -->
            <div class="transactions-section">
                <div class="section-header">
                    <h2>Lista de Transações</h2>
                    <div>
                        <button class="btn btn-primary" onclick="openFilterModal()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem;">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                            </svg>
                            Filtros
                        </button>
                        <a href="<?php echo STORE_REGISTER_TRANSACTION_URL; ?>" class="btn btn-success">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem;">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Nova Venda
                        </a>
                    </div>
                </div>
                
                <div id="loadingState" class="loading-state">
                    <div class="spinner"></div>
                    <p>Carregando transações...</p>
                </div>
                
                <div id="transactionsContent" style="display: none;">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Cliente</th>
                                    <th>Código</th>
                                    <th>Valor</th>
                                    <th>Comissão</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="transactionsTableBody">
                            </tbody>
                        </table>
                    </div>
                    
                    <div id="paginationContainer">
                        <!-- Paginação será inserida aqui -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Filtros -->
    <div class="modal-backdrop" id="filterModalBackdrop"></div>
    <div class="modal" id="filterModal">
        <div class="modal-header">
            <h3 class="modal-title">Filtros de Busca</h3>
            <button class="modal-close" onclick="closeFilterModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="filterForm">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Data Início</label>
                        <input type="date" class="form-control" name="data_inicio" id="filterDataInicio">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data Fim</label>
                        <input type="date" class="form-control" name="data_fim" id="filterDataFim">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-control" name="status" id="filterStatus">
                            <option value="">Todos</option>
                            <option value="pendente">Pendente</option>
                            <option value="aprovado">Aprovado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cliente</label>
                        <input type="text" class="form-control" name="cliente" id="filterCliente" placeholder="Nome ou email">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Valor Mínimo</label>
                        <input type="number" class="form-control" name="valor_min" id="filterValorMin" step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Valor Máximo</label>
                        <input type="number" class="form-control" name="valor_max" id="filterValorMax" step="0.01" min="0">
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline-primary" onclick="clearFilters()">Limpar</button>
            <button class="btn btn-primary" onclick="applyFilters()">Aplicar Filtros</button>
        </div>
    </div>

    <script>
        // Variáveis globais
        const storeId = <?php echo $store['id']; ?>;
        let currentPage = 1;
        let currentFilters = {};

        // Carregar transações ao iniciar
        document.addEventListener('DOMContentLoaded', function() {
            loadTransactions();
        });

        // Função para carregar transações
        function loadTransactions(page = 1) {
            currentPage = page;
            
            // Mostrar loading
            document.getElementById('loadingState').style.display = 'block';
            document.getElementById('transactionsContent').style.display = 'none';
            
            const formData = new FormData();
            formData.append('action', 'store_transactions');
            formData.append('loja_id', storeId);
            formData.append('page', page);
            
            // Adicionar filtros
            Object.keys(currentFilters).forEach(key => {
                if (currentFilters[key]) {
                    formData.append(`filters[${key}]`, currentFilters[key]);
                }
            });

            fetch('../../controllers/TransactionController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    displayTransactions(data.data);
                    updateSummaryCards(data.data.totais);
                    updatePagination(data.data.paginacao);
                } else {
                    showError('Erro ao carregar transações: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showError('Erro ao carregar transações');
            });
        }

        // Função para exibir transações
        function displayTransactions(data) {
            document.getElementById('loadingState').style.display = 'none';
            document.getElementById('transactionsContent').style.display = 'block';
            
            const tbody = document.getElementById('transactionsTableBody');
            tbody.innerHTML = '';

            if (!data.transacoes || data.transacoes.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="empty-state">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                                <polyline points="13 2 13 9 20 9"></polyline>
                            </svg>
                            <h3>Nenhuma transação encontrada</h3>
                            <p>Comece registrando sua primeira venda com cashback</p>
                            <a href="${'<?php echo STORE_REGISTER_TRANSACTION_URL; ?>'}" class="btn btn-primary">Registrar Venda</a>
                        </td>
                    </tr>
                `;
                return;
            }

            data.transacoes.forEach(transaction => {
                const row = document.createElement('tr');
                
                let statusBadge = '';
                switch (transaction.status) {
                    case 'pendente':
                        statusBadge = '<span class="status-badge pendente">Pendente</span>';
                        break;
                    case 'aprovado':
                        statusBadge = '<span class="status-badge aprovado">Aprovado</span>';
                        break;
                    case 'cancelado':
                        statusBadge = '<span class="status-badge cancelado">Cancelado</span>';
                        break;
                    default:
                        statusBadge = '<span class="status-badge">' + transaction.status + '</span>';
                }

                row.innerHTML = `
                    <td data-label="Data">${formatDateTime(transaction.data_transacao)}</td>
                    <td data-label="Cliente">
                        <div><strong>${transaction.cliente_nome}</strong></div>
                        <small style="color: var(--medium-gray);">${transaction.cliente_email}</small>
                    </td>
                    <td data-label="Código"><code>${transaction.codigo_transacao}</code></td>
                    <td data-label="Valor">R$ ${formatMoney(transaction.valor_total)}</td>
                    <td data-label="Comissão">R$ ${formatMoney(transaction.valor_cashback)}</td>
                    <td data-label="Status">${statusBadge}</td>
                    <td data-label="Ações">
                        <button class="btn btn-outline-primary btn-sm" onclick="viewTransactionDetails(${transaction.id})">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </td>
                `;
                
                tbody.appendChild(row);
            });
        }

        // Função para atualizar cards de resumo
        function updateSummaryCards(totais) {
            if (!totais) return;
            
            document.getElementById('totalTransactions').textContent = totais.total_transacoes || 0;
            document.getElementById('totalSales').textContent = 'R$ ' + formatMoney(totais.valor_total_vendas || 0);
            document.getElementById('pendingTransactions').textContent = totais.total_pendentes || 0;
            document.getElementById('totalCommissions').textContent = 'R$ ' + formatMoney(totais.total_comissoes || 0);
        }

        // Função para atualizar paginação
        function updatePagination(paginacao) {
            const container = document.getElementById('paginationContainer');
            
            if (!paginacao || paginacao.total_paginas <= 1) {
                container.innerHTML = '';
                return;
            }

            let paginationHTML = '<ul class="pagination">';

            // Botão Anterior
            if (paginacao.pagina_atual > 1) {
                paginationHTML += `<li><a href="#" onclick="loadTransactions(${paginacao.pagina_atual - 1}); return false;">« Anterior</a></li>`;
            }

            // Páginas numeradas
            for (let i = 1; i <= paginacao.total_paginas; i++) {
                const activeClass = i === paginacao.pagina_atual ? 'active' : '';
                paginationHTML += `<li><a href="#" class="${activeClass}" onclick="loadTransactions(${i}); return false;">${i}</a></li>`;
            }

            // Botão Próximo
            if (paginacao.pagina_atual < paginacao.total_paginas) {
                paginationHTML += `<li><a href="#" onclick="loadTransactions(${paginacao.pagina_atual + 1}); return false;">Próximo »</a></li>`;
            }

            paginationHTML += '</ul>';
            container.innerHTML = paginationHTML;
        }

        // Funções do modal
        function openFilterModal() {
            document.getElementById('filterModal').style.display = 'block';
            document.getElementById('filterModalBackdrop').style.display = 'block';
        }

        function closeFilterModal() {
            document.getElementById('filterModal').style.display = 'none';
            document.getElementById('filterModalBackdrop').style.display = 'none';
        }

        function applyFilters() {
            const form = document.getElementById('filterForm');
            const formData = new FormData(form);
            
            currentFilters = {};
            for (let [key, value] of formData.entries()) {
                if (value.trim() !== '') {
                    currentFilters[key] = value;
                }
            }
            
            currentPage = 1;
            loadTransactions(1);
            closeFilterModal();
        }

        function clearFilters() {
            document.getElementById('filterForm').reset();
            currentFilters = {};
            currentPage = 1;
            loadTransactions(1);
        }

        function viewTransactionDetails(transactionId) {
            alert('Ver detalhes da transação ID: ' + transactionId);
            // Implementar modal de detalhes
        }

        function showError(message) {
            document.getElementById('loadingState').innerHTML = `
                <div style="text-align: center; padding: 2rem; color: var(--danger-color);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 1rem;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    <h3>Erro ao carregar</h3>
                    <p>${message}</p>
                    <button class="btn btn-primary" onclick="loadTransactions()">Tentar Novamente</button>
                </div>
            `;
        }

        // Funções utilitárias
        function formatMoney(value) {
            return parseFloat(value || 0).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function formatDateTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('pt-BR');
        }

        // Fechar modal clicando no backdrop
        document.getElementById('filterModalBackdrop').addEventListener('click', closeFilterModal);
    </script>
</body>
</html>