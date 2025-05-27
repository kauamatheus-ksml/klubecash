<?php
// views/stores/transactions.php - VERSÃO CORRIGIDA
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Incluir arquivos necessários
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../controllers/AuthController.php';

// Debug básico
echo "<!-- Debug: Página carregando -->\n";

// Verificar se o usuário está logado
if (!AuthController::isAuthenticated()) {
    echo "<!-- Debug: Usuário não autenticado -->\n";
    header('Location: ' . (defined('LOGIN_URL') ? LOGIN_URL : '/login.php'));
    exit;
}

// Verificar se é uma loja
if (!AuthController::isStore()) {
    echo "<!-- Debug: Usuário não é loja -->\n";
    header('Location: ' . (defined('LOGIN_URL') ? LOGIN_URL : '/login.php') . '?error=' . urlencode('Acesso restrito a lojas.'));
    exit;
}

$currentUserId = AuthController::getCurrentUserId();
echo "<!-- Debug: User ID: $currentUserId -->\n";

// Buscar dados da loja
require_once __DIR__ . '/../../config/database.php';
try {
    $db = Database::getConnection();
    $storeStmt = $db->prepare("SELECT * FROM lojas WHERE usuario_id = ?");
    $storeStmt->execute([$currentUserId]);
    $store = $storeStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$store) {
        echo "<!-- Debug: Loja não encontrada para user ID: $currentUserId -->\n";
        header('Location: ' . (defined('LOGIN_URL') ? LOGIN_URL : '/login.php') . '?error=' . urlencode('Loja não encontrada.'));
        exit;
    }
    
    echo "<!-- Debug: Loja encontrada: " . $store['nome_fantasia'] . " -->\n";
    
} catch (Exception $e) {
    echo "<!-- Debug: Erro BD: " . $e->getMessage() . " -->\n";
    die("Erro na conexão com o banco de dados");
}

// Definir URLs se não estiverem definidas
if (!defined('STORE_DASHBOARD_URL')) {
    define('STORE_DASHBOARD_URL', '/views/stores/dashboard.php');
}
if (!defined('STORE_REGISTER_TRANSACTION_URL')) {
    define('STORE_REGISTER_TRANSACTION_URL', '/views/stores/register-transaction.php');
}
if (!defined('STORE_PENDING_COMMISSIONS_URL')) {
    define('STORE_PENDING_COMMISSIONS_URL', '/views/stores/pending-commissions.php');
}
if (!defined('STORE_PAYMENT_HISTORY_URL')) {
    define('STORE_PAYMENT_HISTORY_URL', '/views/stores/payment-history.php');
}
if (!defined('STORE_PROFILE_URL')) {
    define('STORE_PROFILE_URL', '/views/stores/profile.php');
}
if (!defined('LOGOUT_URL')) {
    define('LOGOUT_URL', '/logout.php');
}
if (!defined('ASSETS_URL')) {
    define('ASSETS_URL', '/assets');
}

echo "<!-- Debug: Todas as verificações passaram, renderizando página -->\n";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transações - Klube Cash</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* CSS inline para garantir que funcione */
        .wrapper {
            display: flex;
            width: 100%;
        }
        
        .sidebar {
            min-width: 250px;
            max-width: 250px;
            background: #343a40;
            color: white;
            transition: all 0.3s;
        }
        
        .sidebar.active {
            margin-left: -250px;
        }
        
        .sidebar-header {
            padding: 20px;
            background: #495057;
            text-align: center;
        }
        
        .sidebar-header .logo {
            max-width: 50px;
            margin-bottom: 10px;
        }
        
        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-nav li {
            padding: 0;
        }
        
        .sidebar-nav a {
            display: block;
            padding: 15px 20px;
            color: #adb5bd;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-nav a:hover,
        .sidebar-nav li.active a {
            color: white;
            background: #495057;
        }
        
        .sidebar-footer {
            position: absolute;
            bottom: 20px;
            width: 100%;
            padding: 0 20px;
        }
        
        #content {
            width: 100%;
            padding: 0;
            min-height: 100vh;
            transition: all 0.3s;
        }
        
        .navbar {
            padding: 15px;
            background: white !important;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }
            .sidebar.active {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar" class="sidebar">
            <div class="sidebar-header">
                <h4>Loja Parceira</h4>
            </div>
            
            <ul class="sidebar-nav">
                <li>
                    <a href="<?php echo STORE_DASHBOARD_URL; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo STORE_REGISTER_TRANSACTION_URL; ?>">
                        <i class="fas fa-plus-circle"></i>
                        <span>Registrar Venda</span>
                    </a>
                </li>
                <li class="active">
                    <a href="#">
                        <i class="fas fa-list"></i>
                        <span>Transações</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo STORE_PENDING_COMMISSIONS_URL; ?>">
                        <i class="fas fa-clock"></i>
                        <span>Comissões Pendentes</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo STORE_PAYMENT_HISTORY_URL; ?>">
                        <i class="fas fa-history"></i>
                        <span>Histórico de Pagamentos</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo STORE_PROFILE_URL; ?>">
                        <i class="fas fa-user-cog"></i>
                        <span>Perfil</span>
                    </a>
                </li>
            </ul>
            
            <div class="sidebar-footer">
                <a href="<?php echo LOGOUT_URL; ?>" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i>
                    Sair
                </a>
            </div>
        </nav>

        <!-- Content -->
        <div id="content">
            <!-- Top Navigation -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-info">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <div class="ms-auto">
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i>
                                <?php echo htmlspecialchars($store['nome_fantasia']); ?>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo STORE_PROFILE_URL; ?>">Perfil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo LOGOUT_URL; ?>">Sair</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <div class="container-fluid mt-4">
                <div class="row">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2><i class="fas fa-list me-2"></i>Minhas Transações</h2>
                            <div>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                                    <i class="fas fa-filter"></i> Filtros
                                </button>
                                <a href="<?php echo STORE_REGISTER_TRANSACTION_URL; ?>" class="btn btn-success">
                                    <i class="fas fa-plus"></i> Nova Venda
                                </a>
                            </div>
                        </div>

                        <!-- Cards de Resumo -->
                        <div class="row mb-4" id="summaryCards">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h6 class="card-title">Total de Transações</h6>
                                                <h4 id="totalTransactions">-</h4>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-shopping-cart fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h6 class="card-title">Valor Total em Vendas</h6>
                                                <h4 id="totalSales">-</h4>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-dollar-sign fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h6 class="card-title">Pendentes</h6>
                                                <h4 id="pendingTransactions">-</h4>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-clock fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h6 class="card-title">Total Comissões</h6>
                                                <h4 id="totalCommissions">-</h4>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-percentage fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabela de Transações -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Lista de Transações</h5>
                            </div>
                            <div class="card-body">
                                <div id="loadingMessage">
                                    <div class="text-center p-4">
                                        <div class="spinner-border" role="status">
                                            <span class="visually-hidden">Carregando...</span>
                                        </div>
                                        <p class="mt-2">Carregando transações...</p>
                                    </div>
                                </div>
                                
                                <div class="table-responsive" id="transactionsContainer" style="display: none;">
                                    <table class="table table-striped" id="transactionsTable">
                                        <thead>
                                            <tr>
                                                <th>Data</th>
                                                <th>Cliente</th>
                                                <th>Código</th>
                                                <th>Valor da Venda</th>
                                                <th>Comissão (10%)</th>
                                                <th>Status</th>
                                                <th>Status Pagamento</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody id="transactionsTableBody">
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Paginação -->
                                <nav aria-label="Paginação das transações">
                                    <ul class="pagination justify-content-center" id="pagination">
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <!-- Modal de Filtros -->
    <div class="modal fade" id="filterModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filtros de Busca</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="filterForm">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="filterDataInicio" class="form-label">Data Início</label>
                                <input type="date" class="form-control" id="filterDataInicio" name="data_inicio">
                            </div>
                            <div class="col-md-6">
                                <label for="filterDataFim" class="form-label">Data Fim</label>
                                <input type="date" class="form-control" id="filterDataFim" name="data_fim">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="filterStatus" class="form-label">Status</label>
                                <select class="form-select" id="filterStatus" name="status">
                                    <option value="">Todos</option>
                                    <option value="pendente">Pendente</option>
                                    <option value="aprovado">Aprovado</option>
                                    <option value="cancelado">Cancelado</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="filterCliente" class="form-label">Cliente</label>
                                <input type="text" class="form-control" id="filterCliente" name="cliente" placeholder="Nome ou email">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="filterValorMin" class="form-label">Valor Mínimo</label>
                                <input type="number" class="form-control" id="filterValorMin" name="valor_min" step="0.01" min="0">
                            </div>
                            <div class="col-md-6">
                                <label for="filterValorMax" class="form-label">Valor Máximo</label>
                                <input type="number" class="form-control" id="filterValorMax" name="valor_max" step="0.01" min="0">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="clearFilters()">Limpar</button>
                    <button type="button" class="btn btn-primary" onclick="applyFilters()">Aplicar Filtros</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        console.log('Página carregada, iniciando JavaScript');
        
        // Variáveis globais
        const storeId = <?php echo $store['id']; ?>;
        let currentPage = 1;
        let currentFilters = {};

        console.log('Store ID:', storeId);

        // Carregar transações ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM carregado, chamando loadTransactions');
            loadTransactions();
        });

        // Função para carregar transações
        function loadTransactions(page = 1) {
            console.log('loadTransactions chamada, page:', page);
            currentPage = page;
            
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

            console.log('Fazendo requisição para TransactionController.php');

            fetch('../../controllers/TransactionController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Resposta recebida:', response);
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(text => {
                console.log('Resposta texto:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Dados JSON:', data);
                    
                    if (data.status) {
                        displayTransactions(data.data);
                        updateSummaryCards(data.data.totais);
                        updatePagination(data.data.paginacao);
                    } else {
                        console.error('Erro na resposta:', data.message);
                        showError('Erro ao carregar transações: ' + data.message);
                    }
                } catch (e) {
                    console.error('Erro ao parse JSON:', e);
                    console.error('Texto da resposta:', text);
                    showError('Erro na resposta do servidor');
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                showError('Erro ao carregar transações');
            });
        }

        // Função para exibir erro
        function showError(message) {
            document.getElementById('loadingMessage').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    ${message}
                    <button class="btn btn-outline-danger btn-sm ms-2" onclick="loadTransactions()">Tentar Novamente</button>
                </div>
            `;
        }

        // Função para exibir transações na tabela
        function displayTransactions(data) {
            console.log('displayTransactions chamada:', data);
            
            document.getElementById('loadingMessage').style.display = 'none';
            document.getElementById('transactionsContainer').style.display = 'block';
            
            const tbody = document.getElementById('transactionsTableBody');
            tbody.innerHTML = '';

            if (!data.transacoes || data.transacoes.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center">Nenhuma transação encontrada</td></tr>';
                return;
            }

            data.transacoes.forEach(transaction => {
                const row = document.createElement('tr');
                
                // Status badge
                let statusBadge = '';
                switch (transaction.status) {
                    case 'pendente':
                        statusBadge = '<span class="badge bg-warning">Pendente</span>';
                        break;
                    case 'aprovado':
                        statusBadge = '<span class="badge bg-success">Aprovado</span>';
                        break;
                    case 'cancelado':
                        statusBadge = '<span class="badge bg-danger">Cancelado</span>';
                        break;
                    default:
                        statusBadge = '<span class="badge bg-secondary">' + transaction.status + '</span>';
                }

                // Status do pagamento
                let paymentStatusBadge = '';
                if (transaction.status_pagamento) {
                    switch (transaction.status_pagamento) {
                        case 'pendente':
                            paymentStatusBadge = '<span class="badge bg-warning">Pagamento Pendente</span>';
                            break;
                        case 'aprovado':
                            paymentStatusBadge = '<span class="badge bg-success">Pago</span>';
                            break;
                        case 'rejeitado':
                            paymentStatusBadge = '<span class="badge bg-danger">Rejeitado</span>';
                            break;
                    }
                } else {
                    paymentStatusBadge = '<span class="badge bg-secondary">Não Processado</span>';
                }

                row.innerHTML = `
                    <td>${formatDateTime(transaction.data_transacao)}</td>
                    <td>
                        <div><strong>${transaction.cliente_nome}</strong></div>
                        <small class="text-muted">${transaction.cliente_email}</small>
                    </td>
                    <td><code>${transaction.codigo_transacao}</code></td>
                    <td><strong>R$ ${formatMoney(transaction.valor_total)}</strong></td>
                    <td><strong>R$ ${formatMoney(transaction.valor_cashback)}</strong></td>
                    <td>${statusBadge}</td>
                    <td>${paymentStatusBadge}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="alert('Detalhes: ID ${transaction.id}')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                `;
                
                tbody.appendChild(row);
            });
        }

        // Função para atualizar cards de resumo
        function updateSummaryCards(totais) {
            console.log('updateSummaryCards:', totais);
            if (!totais) return;
            
            document.getElementById('totalTransactions').textContent = totais.total_transacoes || 0;
            document.getElementById('totalSales').textContent = 'R$ ' + formatMoney(totais.valor_total_vendas || 0);
            document.getElementById('pendingTransactions').textContent = totais.total_pendentes || 0;
            document.getElementById('totalCommissions').textContent = 'R$ ' + formatMoney(totais.total_comissoes || 0);
        }

        // Função para atualizar paginação
        function updatePagination(paginacao) {
            console.log('updatePagination:', paginacao);
            const pagination = document.getElementById('pagination');
            pagination.innerHTML = '';

            if (!paginacao || paginacao.total_paginas <= 1) return;

            // Botão Anterior
            if (paginacao.pagina_atual > 1) {
                pagination.innerHTML += `
                    <li class="page-item">
                        <a class="page-link" href="#" onclick="loadTransactions(${paginacao.pagina_atual - 1}); return false;">Anterior</a>
                    </li>
                `;
            }

            // Páginas numeradas
            for (let i = 1; i <= paginacao.total_paginas; i++) {
                const activeClass = i === paginacao.pagina_atual ? 'active' : '';
                pagination.innerHTML += `
                    <li class="page-item ${activeClass}">
                        <a class="page-link" href="#" onclick="loadTransactions(${i}); return false;">${i}</a>
                    </li>
                `;
            }

            // Botão Próximo
            if (paginacao.pagina_atual < paginacao.total_paginas) {
                pagination.innerHTML += `
                    <li class="page-item">
                        <a class="page-link" href="#" onclick="loadTransactions(${paginacao.pagina_atual + 1}); return false;">Próximo</a>
                    </li>
                `;
            }
        }

        // Função para aplicar filtros
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
            
            // Fechar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('filterModal'));
            if (modal) modal.hide();
        }

        // Função para limpar filtros
        function clearFilters() {
            document.getElementById('filterForm').reset();
            currentFilters = {};
            currentPage = 1;
            loadTransactions(1);
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

        // Sidebar toggle
        document.getElementById('sidebarCollapse').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>