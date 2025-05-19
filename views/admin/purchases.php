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
    // Redirecionar para a página de login com mensagem de erro
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
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compras - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <link rel="stylesheet" href="../../assets/css/views/admin/purchases.css">
    
    <style>
        /* Estilos adicionais para informações de saldo */
        .balance-indicator {
            margin-left: 5px;
            font-size: 0.8rem;
        }
        
        .saldo-usado {
            color: #28a745;
            font-weight: 600;
        }
        
        .sem-saldo {
            color: #6c757d;
            font-style: italic;
        }
        
        .valor-original {
            color: #2d3748;
            font-weight: 500;
        }
        
        .valor-pago {
            color: #4a5568;
            font-size: 0.9rem;
        }
        
        .economia-badge {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .stat-card-balance {
            border-left: 4px solid #28a745;
        }
        
        .stat-card-balance .stat-card-value {
            color: #28a745;
        }
        
        .stat-card-subtitle {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 5px;
            font-style: italic;
        }
        
        .impacto-saldo {
            background: #f8fff8;
            border-left: 4px solid #28a745;
            padding: 10px 15px;
            margin: 5px 0;
            border-radius: 8px;
        }
        
        .impacto-item {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }
        
        .impacto-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .impacto-value {
            color: #28a745;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include_once '../components/sidebar.php'; ?>
    
    <div class="main-content" id="mainContent">
        <div class="page-wrapper">
            <h1 class="page-title">Compras</h1>
            
            <?php if ($hasError): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php else: ?>
            
            <!-- Cards de Estatísticas com Informações de Saldo -->
            <?php if (!empty($statistics)): ?>
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-card-title">Total de Transações</div>
                    <div class="stat-card-value"><?php echo number_format($statistics['total_transacoes']); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-title">Valor Total Original</div>
                    <div class="stat-card-value"><?php echo formatCurrency($statistics['valor_vendas_originais']); ?></div>
                    <div class="stat-card-subtitle">Antes de descontos</div>
                </div>
                
                <div class="stat-card stat-card-balance">
                    <div class="stat-card-title">Saldo Usado Clientes</div>
                    <div class="stat-card-value"><?php echo formatCurrency($statistics['total_saldo_usado']); ?></div>
                    <div class="stat-card-subtitle">Economia gerada</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-title">Valor Efetivamente Pago</div>
                    <div class="stat-card-value"><?php echo formatCurrency($statistics['valor_liquido_pago']); ?></div>
                    <div class="stat-card-subtitle">Após uso de saldo</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-title">Cashback Total</div>
                    <div class="stat-card-value"><?php echo formatCurrency($statistics['total_cashback']); ?></div>
                    <div class="stat-card-subtitle">Gerado aos clientes</div>
                </div>
                
                <div class="stat-card stat-card-balance">
                    <div class="stat-card-title">Transações c/ Saldo</div>
                    <div class="stat-card-value"><?php echo number_format($statistics['transacoes_com_saldo']); ?></div>
                    <div class="stat-card-subtitle"><?php echo number_format($statistics['percentual_uso_saldo'], 1); ?>% do total</div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Filtros -->
            <div class="filters-bar">
                <div class="filter-group">
                    <div class="filter-item">
                        <select class="filter-select" id="dataFilter" onchange="applyFilters()">
                            <option value="">Data</option>
                            <option value="today">Hoje</option>
                            <option value="yesterday">Ontem</option>
                            <option value="last_week">Última semana</option>
                            <option value="last_month">Último mês</option>
                            <option value="custom">Personalizado</option>
                        </select>
                    </div>
                    
                    <div class="filter-item">
                        <select class="filter-select" id="storeFilter" onchange="applyFilters()">
                            <option value="">Loja</option>
                            <?php foreach ($stores as $store): ?>
                                <option value="<?php echo $store['id']; ?>" <?php echo (isset($_GET['loja_id']) && $_GET['loja_id'] == $store['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($store['nome_fantasia']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-item">
                        <select class="filter-select" id="statusFilter" onchange="applyFilters()">
                            <option value="">Status</option>
                            <option value="pendente" <?php echo (isset($_GET['status']) && $_GET['status'] === 'pendente') ? 'selected' : ''; ?>>Pendente</option>
                            <option value="aprovado" <?php echo (isset($_GET['status']) && $_GET['status'] === 'aprovado') ? 'selected' : ''; ?>>Aprovado</option>
                            <option value="cancelado" <?php echo (isset($_GET['status']) && $_GET['status'] === 'cancelado') ? 'selected' : ''; ?>>Cancelado</option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-group">
                    <div class="search-bar">
                        <input type="text" id="searchInput" placeholder="Buscar..." value="<?php echo isset($_GET['busca']) ? htmlspecialchars($_GET['busca']) : ''; ?>" onkeyup="if(event.key === 'Enter') applyFilters()">
                        <span class="search-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                        </span>
                    </div>
                    
                    <button class="export-btn" onclick="exportToPDF()">
                        <span>Exportar PDF</span>
                    </button>
                </div>
            </div>
            
            <!-- Tabela de Compras -->
            <div class="table-container">
                <table class="transactions-table">
                    <thead>
                        <tr>
                            <th>
                                <div class="checkbox-wrapper">
                                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                    <span class="checkmark"></span>
                                </div>
                            </th>
                            <th>ID Transação</th>
                            <th>Cliente</th>
                            <th>Loja</th>
                            <th>Valor Original</th>
                            <th>Saldo Usado</th>
                            <th>Valor Pago</th>
                            <th>Cashback Cliente</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="11" style="text-align: center;">Nenhuma transação encontrada</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $index => $transaction): ?>
                                <?php 
                                $saldoUsado = $transaction['saldo_usado'] ?? 0;
                                $valorOriginal = $transaction['valor_total'];
                                $valorPago = $valorOriginal - $saldoUsado;
                                ?>
                                <tr>
                                    <td>
                                        <div class="checkbox-wrapper">
                                            <input type="checkbox" class="transaction-checkbox" value="<?php echo $transaction['id']; ?>">
                                            <span class="checkmark"></span>
                                        </div>
                                    </td>
                                    <td>#<?php echo $transaction['id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($transaction['cliente_nome']); ?>
                                        <?php if ($saldoUsado > 0): ?>
                                            <span class="balance-indicator" title="Cliente usou saldo">💰</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['loja_nome']); ?></td>
                                    <td>
                                        <span class="valor-original"><?php echo formatCurrency($valorOriginal); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($saldoUsado > 0): ?>
                                            <span class="saldo-usado"><?php echo formatCurrency($saldoUsado); ?></span>
                                        <?php else: ?>
                                            <span class="sem-saldo">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="valor-pago"><?php echo formatCurrency($valorPago); ?></span>
                                        <?php if ($saldoUsado > 0): ?>
                                            <br><span class="economia-badge">Economia aplicada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatCurrency($transaction['valor_cliente']); ?></td>
                                    <td><?php echo formatDate($transaction['data_transacao']); ?></td>
                                    <td>
                                        <?php 
                                            $statusClass = '';
                                            $statusText = '';
                                            
                                            switch ($transaction['status']) {
                                                case 'aprovado':
                                                    $statusClass = 'status-approved';
                                                    $statusText = 'Aprovado';
                                                    break;
                                                case 'pendente':
                                                    $statusClass = 'status-pending';
                                                    $statusText = 'Pendente';
                                                    break;
                                                case 'cancelado':
                                                    $statusClass = 'status-canceled';
                                                    $statusText = 'Cancelado';
                                                    break;
                                            }
                                        ?>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="action-btn" onclick="viewTransactionDetails(<?php echo $transaction['id']; ?>)">
                                            Ver Detalhes
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginação -->
            <?php if (!empty($pagination) && $pagination['total_paginas'] > 1): ?>
                <div class="pagination">
                    <a href="?page=<?php echo max(1, $page - 1); ?><?php echo buildQueryString(['page']); ?>" class="arrow">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </a>
                    
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
                    
                    <a href="?page=<?php echo min($pagination['total_paginas'], $page + 1); ?><?php echo buildQueryString(['page']); ?>" class="arrow">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </a>
                </div>
            <?php endif; ?>
            
            <!-- Resumo de Impacto do Saldo -->
            <?php if (!empty($statistics) && $statistics['total_saldo_usado'] > 0): ?>
            <div class="impacto-saldo">
                <h4 style="margin: 0 0 10px 0; color: #28a745;">💰 Impacto do Sistema de Saldo</h4>
                <div class="impacto-item">
                    <span class="impacto-label">Economia gerada aos clientes:</span>
                    <span class="impacto-value"><?php echo formatCurrency($statistics['total_saldo_usado']); ?></span>
                </div>
                <div class="impacto-item">
                    <span class="impacto-label">Redução no faturamento das lojas:</span>
                    <span class="impacto-value"><?php echo formatCurrency($statistics['total_saldo_usado']); ?></span>
                </div>
                <div class="impacto-item">
                    <span class="impacto-label">Redução nas comissões Klube Cash:</span>
                    <span class="impacto-value"><?php echo formatCurrency($statistics['total_saldo_usado'] * 0.1); ?></span>
                </div>
                <div class="impacto-item">
                    <span class="impacto-label">Taxa de adoção do sistema de saldo:</span>
                    <span class="impacto-value"><?php echo number_format($statistics['percentual_uso_saldo'], 1); ?>%</span>
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
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body" id="modalTransactionContent">
                <!-- Conteúdo será carregado via JavaScript -->
            </div>
        </div>
    </div>
    
    <script>
        // Selecionar ou deselecionar todos os checkboxes
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.transaction-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }
        
        // Aplicar filtros
        function applyFilters() {
            const dataFilter = document.getElementById('dataFilter').value;
            const storeFilter = document.getElementById('storeFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const searchInput = document.getElementById('searchInput').value;
            
            let queryParams = [];
            
            // Processar filtro de data
            if (dataFilter) {
                const today = new Date();
                let startDate, endDate;
                
                switch (dataFilter) {
                    case 'today':
                        startDate = formatDateForUrl(today);
                        endDate = formatDateForUrl(today);
                        break;
                    case 'yesterday':
                        const yesterday = new Date(today);
                        yesterday.setDate(yesterday.getDate() - 1);
                        startDate = formatDateForUrl(yesterday);
                        endDate = formatDateForUrl(yesterday);
                        break;
                    case 'last_week':
                        const lastWeekStart = new Date(today);
                        lastWeekStart.setDate(today.getDate() - 7);
                        startDate = formatDateForUrl(lastWeekStart);
                        endDate = formatDateForUrl(today);
                        break;
                    case 'last_month':
                        const lastMonthStart = new Date(today);
                        lastMonthStart.setMonth(today.getMonth() - 1);
                        startDate = formatDateForUrl(lastMonthStart);
                        endDate = formatDateForUrl(today);
                        break;
                    case 'custom':
                        // Aqui você pode adicionar um modal ou outro input para datas personalizadas
                        break;
                }
                
                if (startDate) queryParams.push(`data_inicio=${startDate}`);
                if (endDate) queryParams.push(`data_fim=${endDate}`);
            }
            
            // Filtro de loja
            if (storeFilter) {
                queryParams.push(`loja_id=${storeFilter}`);
            }
            
            // Filtro de status
            if (statusFilter) {
                queryParams.push(`status=${statusFilter}`);
            }
            
            // Busca
            if (searchInput) {
                queryParams.push(`busca=${encodeURIComponent(searchInput)}`);
            }
            
            // Redirecionar com os filtros aplicados
            window.location.href = `?${queryParams.join('&')}`;
        }
        
        // Formatar data para URL (YYYY-MM-DD)
        function formatDateForUrl(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
        
        // Visualizar detalhes da transação
        function viewTransactionDetails(transactionId) {
            const modal = document.getElementById('transactionDetailsModal');
            const content = document.getElementById('modalTransactionContent');
            
            modal.style.display = 'block';
            content.innerHTML = '<p>Carregando detalhes...</p>';
            
            fetch('../../controllers/AdminController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=transaction_details_with_balance&transaction_id=' + transactionId
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    renderTransactionDetailsWithBalance(data.data);
                } else {
                    content.innerHTML = `<p class="error">Erro: ${data.message}</p>`;
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                content.innerHTML = `<p class="error">Erro ao carregar detalhes. Tente novamente.</p>`;
            });
        }
        
        // Renderizar detalhes da transação com informações de saldo
        function renderTransactionDetailsWithBalance(transaction) {
            const saldoUsado = parseFloat(transaction.saldo_usado || 0);
            const valorOriginal = parseFloat(transaction.valor_total);
            const valorPago = valorOriginal - saldoUsado;
            
            const content = document.getElementById('modalTransactionContent');
            document.getElementById('modalTransactionTitle').textContent = `Transação #${transaction.id}`;
            
            let html = `
                <div class="transaction-details">
                    <div class="detail-section">
                        <h4>Informações Gerais</h4>
                        <p><strong>ID:</strong> #${transaction.id}</p>
                        <p><strong>Código:</strong> ${transaction.codigo_transacao || 'N/A'}</p>
                        <p><strong>Cliente:</strong> ${transaction.cliente_nome}${saldoUsado > 0 ? ' 💰' : ''}</p>
                        <p><strong>Loja:</strong> ${transaction.loja_nome}</p>
                        <p><strong>Data:</strong> ${formatDate(transaction.data_transacao)}</p>
                        <p><strong>Status:</strong> <span class="status-badge status-${transaction.status}">${getStatusText(transaction.status)}</span></p>
                        ${transaction.descricao ? `<p><strong>Descrição:</strong> ${transaction.descricao}</p>` : ''}
                    </div>
                    
                    <div class="detail-section">
                        <h4>Informações Financeiras</h4>
                        <div class="financial-breakdown">
                            <div class="breakdown-item">
                                <span>Valor original da venda:</span>
                                <span class="valor-original">${formatCurrency(valorOriginal)}</span>
                            </div>
                            ${saldoUsado > 0 ? `
                            <div class="breakdown-item">
                                <span>Saldo usado pelo cliente:</span>
                                <span class="saldo-usado">- ${formatCurrency(saldoUsado)}</span>
                            </div>
                            <div class="breakdown-item total">
                                <span>Valor efetivamente pago:</span>
                                <span class="valor-pago">${formatCurrency(valorPago)}</span>
                            </div>
                            ` : ''}
                            <div class="breakdown-item">
                                <span>Cashback gerado ao cliente:</span>
                                <span class="cashback-value">${formatCurrency(transaction.valor_cliente)}</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Comissão Klube Cash:</span>
                                <span class="commission-value">${formatCurrency(transaction.valor_admin)}</span>
                            </div>
                        </div>
                    </div>
            `;
            
            if (saldoUsado > 0) {
                html += `
                    <div class="detail-section saldo-impact">
                        <h4 style="color: #28a745;">💰 Impacto do Uso de Saldo</h4>
                        <div class="impact-details">
                            <p><strong>Economia do cliente:</strong> ${formatCurrency(saldoUsado)}</p>
                            <p><strong>Desconto na comissão:</strong> ${formatCurrency(saldoUsado * 0.1)} (sobre valor não pago)</p>
                            <p><strong>Benefício mútuo:</strong> Cliente economiza, loja mantém fidelidade</p>
                        </div>
                    </div>
                `;
            }
            
            html += `</div>`;
            content.innerHTML = html;
        }
        
        // Funções auxiliares
        function getStatusText(status) {
            switch(status) {
                case 'aprovado': return 'Aprovado';
                case 'pendente': return 'Pendente';
                case 'cancelado': return 'Cancelado';
                default: return status;
            }
        }
        
        function formatCurrency(value) {
            return 'R$ ' + parseFloat(value).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
        
        function formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('pt-BR');
        }
        
        // Fechar modal
        function closeTransactionModal() {
            document.getElementById('transactionDetailsModal').style.display = 'none';
        }
        
        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            const modal = document.getElementById('transactionDetailsModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
        
        // Exportar para PDF
        function exportToPDF() {
            alert('Funcionalidade de exportação para PDF será implementada.');
        }
    </script>
    
    <?php 
    // Função auxiliar para construir query string preservando filtros existentes
    function buildQueryString($exclude = []) {
        $params = $_GET;
        foreach ($exclude as $key) {
            unset($params[$key]);
        }
        return $params ? '&' . http_build_query($params) : '';
    }
    ?>
</body>
</html>