<?php
// views/admin/purchases.php
// Definir o menu ativo na sidebar
$activeMenu = 'compras';

// Incluir conexão com o banco de dados e arquivos necessários
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/AdminController.php';

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

if (isset($_GET['busca']) && !empty($_GET['busca'])) {
    $filters['busca'] = $_GET['busca'];
}

try {
    // Obter dados das transações
    $result = AdminController::manageTransactions($filters, $page);

    // Verificar se houve erro
    $hasError = !$result['status'];
    $errorMessage = $hasError ? $result['message'] : '';

    // Dados para exibição na página
    $transactions = $hasError ? [] : $result['data']['transacoes'];
    $stores = $hasError ? [] : $result['data']['lojas'];
    $pagination = $hasError ? [] : $result['data']['paginacao'];
} catch (Exception $e) {
    $hasError = true;
    $errorMessage = "Erro ao processar a requisição: " . $e->getMessage();
    $transactions = [];
    $stores = [];
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
                                <option value="<?php echo $store['id']; ?>">
                                    <?php echo htmlspecialchars($store['nome_fantasia']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="filter-group">
                    <div class="search-bar">
                        <input type="text" id="searchInput" placeholder="Buscar..." onkeyup="if(event.key === 'Enter') applyFilters()">
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
                            <th>ID Pedido</th>
                            <th>Nome do Cliente</th>
                            <th>Loja</th>
                            <th>Comissão Plataforma</th>
                            <th>Valor Total</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center;">Nenhuma transação encontrada</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $index => $transaction): ?>
                                <tr>
                                    <td>
                                        <div class="checkbox-wrapper">
                                            <input type="checkbox" class="transaction-checkbox" value="<?php echo $transaction['id']; ?>">
                                            <span class="checkmark"></span>
                                        </div>
                                    </td>
                                    <td><?php echo $transaction['id']; ?></td>
                                    <td><?php echo htmlspecialchars($transaction['cliente_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['loja_nome']); ?></td>
                                    <td>
                                        <?php 
                                            // Comissão da plataforma (valor_admin na tabela transacoes_comissao)
                                            // Aqui você precisaria buscar esse valor ou adicionar ao resultado do controller
                                            echo isset($transaction['valor_admin']) ? formatCurrency($transaction['valor_admin']) : '-';
                                        ?>
                                    </td>
                                    <td><?php echo formatCurrency($transaction['valor_total']); ?></td>
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
                                        <button class="action-btn" onclick="viewDetails(<?php echo $transaction['id']; ?>)">
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
                    <a href="?page=<?php echo max(1, $page - 1); ?>" class="arrow">
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
                        <a href="?page=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <a href="?page=<?php echo min($pagination['total_paginas'], $page + 1); ?>" class="arrow">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </a>
                </div>
            <?php endif; ?>
            <?php endif; ?>
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
        function viewDetails(transactionId) {
            window.location.href = "<?php echo SITE_URL; ?>/admin/transacao/" + transactionId;
        }
        
        // Exportar para PDF
        function exportToPDF() {
            alert('Funcionalidade de exportação para PDF será implementada.');
            // Aqui você pode implementar a lógica de exportação para PDF
            // Pode usar bibliotecas como jsPDF ou fazer uma requisição para um endpoint do backend
        }
    </script>
</body>
</html>