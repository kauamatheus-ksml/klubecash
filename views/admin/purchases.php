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
    <style>
        :root {
            --primary-color: #FF7A00;
            --primary-light: #FFF0E6;
            --white: #FFFFFF;
            --light-gray: #F5F5F5;
            --dark-gray: #333333;
            --medium-gray: #666666;
            --success-color: #4CAF50;
            --border-radius: 8px;
            --shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            --font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Reset e estilos gerais */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: var(--font-family);
        }
        
        body {
            background-color: #FFF9F2;
            overflow-x: hidden;
        }
        
        /* Container principal */
        .main-content {
            padding-left: 250px;
            transition: padding-left 0.3s ease;
        }
        
        /* Wrapper da página */
        .page-wrapper {
            padding: 20px;
            min-height: 100vh;
        }
        
        /* Título da página */
        .page-title {
            font-size: 28px;
            font-weight: 600;
            color: var(--dark-gray);
            margin-bottom: 25px;
        }
        
        /* Barra de filtros */
        .filters-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
            justify-content: space-between;
        }
        
        .filter-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .filter-select {
            min-width: 120px;
            padding: 10px 15px;
            border: 1px solid #FFD9B3;
            border-radius: var(--border-radius);
            background-color: var(--white);
            cursor: pointer;
            font-size: 14px;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23FF7A00' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 16px;
            transition: all 0.3s;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 122, 0, 0.2);
        }
        
        .search-bar {
            position: relative;
            flex-grow: 1;
            max-width: 400px;
        }
        
        .search-bar input {
            width: 100%;
            padding: 10px 40px 10px 15px;
            border: 1px solid #FFD9B3;
            border-radius: var(--border-radius);
            background-color: var(--white);
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .search-bar input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 122, 0, 0.2);
        }
        
        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
        }
        
        .export-btn {
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: var(--border-radius);
            padding: 10px 20px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.3s;
        }
        
        .export-btn:hover {
            background-color: #E06E00;
        }
        
        /* Tabela de compras */
        .table-container {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .transactions-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .transactions-table th,
        .transactions-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #F0F0F0;
        }
        
        .transactions-table th {
            background-color: #FFFAF3;
            font-weight: 600;
            color: var(--dark-gray);
        }
        
        .transactions-table tr:last-child td {
            border-bottom: none;
        }
        
        .transactions-table tr:hover {
            background-color: #FFFAF3;
        }
        
        .checkbox-wrapper {
            display: inline-block;
            position: relative;
            width: 18px;
            height: 18px;
        }
        
        .checkbox-wrapper input[type="checkbox"] {
            opacity: 0;
            position: absolute;
        }
        
        .checkbox-wrapper .checkmark {
            position: absolute;
            top: 0;
            left: 0;
            height: 18px;
            width: 18px;
            background-color: #fff;
            border: 2px solid #FFD9B3;
            border-radius: 3px;
        }
        
        .checkbox-wrapper input[type="checkbox"]:checked ~ .checkmark {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .checkbox-wrapper .checkmark:after {
            content: "";
            position: absolute;
            display: none;
        }
        
        .checkbox-wrapper input[type="checkbox"]:checked ~ .checkmark:after {
            display: block;
            left: 5px;
            top: 1px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-approved {
            background-color: #E8F5E9;
            color: #4CAF50;
        }
        
        .status-pending {
            background-color: #FFF8E0;
            color: #FFC107;
        }
        
        .status-canceled {
            background-color: #FFEBEE;
            color: #F44336;
        }
        
        .action-btn {
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: var(--border-radius);
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .action-btn:hover {
            background-color: #E06E00;
        }
        
        /* Paginação */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
            margin-top: 20px;
        }
        
        .pagination a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            text-decoration: none;
            color: var(--dark-gray);
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .pagination a:hover {
            background-color: #FFFAF3;
            color: var(--primary-color);
        }
        
        .pagination a.active {
            background-color: var(--primary-color);
            color: var(--white);
        }
        
        .pagination .arrow {
            color: var(--primary-color);
        }
        
        /* Responsividade */
        @media (max-width: 1024px) {
            .page-wrapper {
                padding: 75px 20px;
            }
            .main-content {
                padding-left: 0;
            }
            
            .filters-bar {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-group {
                width: 100%;
                justify-content: space-between;
            }
            
            .search-bar {
                max-width: 100%;
                width: 100%;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            .transactions-table {
                min-width: 900px;
            }
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