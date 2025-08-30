<?php
// views/admin/purchases.php - Gestão Moderna de Compras
$activeMenu = 'compras';

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/AdminController.php';
require_once '../../models/CashbackBalance.php';

session_start();

// Verificar autenticação
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== USER_TYPE_ADMIN) {
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'get_kpis':
            // Calcular KPIs das compras
            try {
                $db = Database::getConnection();
                
                // Total de compras
                $stmt = $db->query("SELECT COUNT(*) as total FROM transacoes_cashback WHERE 1=1");
                $totalPurchases = $stmt->fetch()['total'];
                
                // Volume total
                $stmt = $db->query("SELECT SUM(valor_total) as total FROM transacoes_cashback WHERE 1=1");
                $totalVolume = $stmt->fetch()['total'] ?? 0;
                
                // Cashback total
                $stmt = $db->query("SELECT SUM(valor_cliente + valor_admin + valor_loja) as total FROM transacoes_cashback WHERE 1=1");
                $totalCashback = $stmt->fetch()['total'] ?? 0;
                
                // Compras pendentes
                $stmt = $db->query("SELECT COUNT(*) as total FROM transacoes_cashback WHERE status = 'pendente'");
                $pendingPurchases = $stmt->fetch()['total'];
                
                // Ticket médio
                $avgTicket = $totalPurchases > 0 ? $totalVolume / $totalPurchases : 0;
                
                // Taxa de aprovação
                $stmt = $db->query("SELECT COUNT(*) as approved FROM transacoes_cashback WHERE status = 'aprovado'");
                $approved = $stmt->fetch()['approved'];
                $approvalRate = $totalPurchases > 0 ? ($approved / $totalPurchases) * 100 : 0;
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'total_purchases' => (int)$totalPurchases,
                        'total_volume' => (float)$totalVolume,
                        'total_cashback' => (float)$totalCashback,
                        'pending_count' => (int)$pendingPurchases,
                        'avg_ticket' => (float)$avgTicket,
                        'approval_rate' => (float)$approvalRate,
                        'trends' => [
                            'totalPurchases' => ['direction' => 'positive', 'percentage' => 12.5],
                            'totalVolume' => ['direction' => 'positive', 'percentage' => 8.3],
                            'totalCashback' => ['direction' => 'positive', 'percentage' => 15.2],
                            'pendingCount' => ['direction' => 'negative', 'percentage' => -5.8],
                            'avgTicket' => ['direction' => 'positive', 'percentage' => 3.7],
                            'approvalRate' => ['direction' => 'positive', 'percentage' => 2.1]
                        ]
                    ]
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'list':
            // Listar compras
            try {
                $result = AdminController::manageTransactionsWithBalance($_GET, $_GET['page'] ?? 1);
                
                if ($result['status']) {
                    $purchases = array_map(function($transaction) {
                        return [
                            'id' => $transaction['id'],
                            'cliente_nome' => $transaction['cliente_nome'],
                            'cliente_email' => $transaction['cliente_email'] ?? '',
                            'loja_nome' => $transaction['loja_nome'],
                            'valor' => $transaction['valor_total'],
                            'cashback_valor' => $transaction['valor_cliente'] + $transaction['valor_admin'] + $transaction['valor_loja'],
                            'porcentagem_cashback' => (($transaction['valor_cliente'] + $transaction['valor_admin'] + $transaction['valor_loja']) / $transaction['valor_total']) * 100,
                            'status' => $transaction['status'],
                            'data_transacao' => $transaction['data_transacao'],
                            'saldo_usado' => $transaction['saldo_usado'] ?? 0
                        ];
                    }, $result['data']['transacoes']);
                    
                    echo json_encode([
                        'success' => true,
                        'data' => $purchases,
                        'pagination' => [
                            'currentPage' => (int)$result['data']['paginacao']['pagina_atual'],
                            'totalPages' => (int)$result['data']['paginacao']['total_paginas'],
                            'hasNext' => $result['data']['paginacao']['pagina_atual'] < $result['data']['paginacao']['total_paginas'],
                            'hasPrev' => $result['data']['paginacao']['pagina_atual'] > 1
                        ]
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => $result['message']]);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'get_purchase':
            // Obter detalhes de uma compra
            try {
                $purchaseId = (int)$input['id'];
                $db = Database::getConnection();
                
                $stmt = $db->prepare("
                    SELECT tc.*, 
                           u.nome as cliente_nome, u.email as cliente_email,
                           l.nome as loja_nome, l.categoria as loja_categoria
                    FROM transacoes_cashback tc
                    JOIN usuarios u ON tc.cliente_id = u.id
                    JOIN lojas l ON tc.loja_id = l.id
                    WHERE tc.id = ?
                ");
                $stmt->execute([$purchaseId]);
                $purchase = $stmt->fetch();
                
                if ($purchase) {
                    echo json_encode(['success' => true, 'data' => $purchase]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Compra não encontrada']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'approve':
        case 'cancel':
        case 'bulk_approve':
        case 'bulk_cancel':
            // Ações de aprovação/cancelamento
            try {
                $db = Database::getConnection();
                
                if (strpos($action, 'bulk_') === 0) {
                    $purchaseIds = $input['purchases'];
                    $status = ($action === 'bulk_approve') ? 'aprovado' : 'cancelado';
                    $field = ($action === 'bulk_approve') ? 'data_aprovacao' : 'data_cancelamento';
                    
                    $placeholders = str_repeat('?,', count($purchaseIds) - 1) . '?';
                    $stmt = $db->prepare("UPDATE transacoes_cashback SET status = ?, {$field} = NOW() WHERE id IN ($placeholders)");
                    $params = array_merge([$status], $purchaseIds);
                    $stmt->execute($params);
                } else {
                    $purchaseId = (int)$input['purchase_id'];
                    $status = ($action === 'approve') ? 'aprovado' : 'cancelado';
                    $field = ($action === 'approve') ? 'data_aprovacao' : 'data_cancelamento';
                    
                    $stmt = $db->prepare("UPDATE transacoes_cashback SET status = ?, {$field} = NOW() WHERE id = ?");
                    $stmt->execute([$status, $purchaseId]);
                }
                
                echo json_encode(['success' => true, 'message' => 'Ação executada com sucesso']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Ação não reconhecida']);
            exit;
    }
}

// Obter dados iniciais
try {
    $result = AdminController::manageTransactionsWithBalance([], 1);
    $hasError = !$result['status'];
    $stores = $hasError ? [] : $result['data']['lojas'];
} catch (Exception $e) {
    $hasError = true;
    $stores = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Compras - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/views/admin/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/views/admin/purchases.css">
</head>
<body>
    <?php include_once '../components/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Executive Header -->
        <div class="executive-header">
            <div class="header-content">
                <div class="breadcrumb">
                    <a href="dashboard.php">Dashboard</a>
                    <span class="separator">/</span>
                    <span>Compras</span>
                </div>
                
                <div class="header-title">
                    <div class="title-section">
                        <h1>Gestão de Compras</h1>
                        <p>Sistema avançado de gestão de transações e análise de comportamento de compra</p>
                    </div>
                    
                    <div class="header-actions">
                        <a href="#" class="btn-header" onclick="exportData()">
                            <i class="fas fa-download"></i>
                            Exportar Dados
                        </a>
                        <a href="#" class="btn-header primary" onclick="refreshData()">
                            <i class="fas fa-sync"></i>
                            Atualizar
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
            <!-- KPI Dashboard -->
            <div class="kpi-dashboard">
                <div class="kpi-card">
                    <div class="kpi-header">
                        <div class="kpi-title">Total de Compras</div>
                        <div class="kpi-icon blue">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                    <div class="kpi-value" id="totalPurchases">0</div>
                    <div class="kpi-change positive" id="totalPurchasesTrend">
                        <i class="fas fa-arrow-up"></i>
                        0%
                    </div>
                    <div class="kpi-period">vs. período anterior</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-header">
                        <div class="kpi-title">Volume Total</div>
                        <div class="kpi-icon green">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="kpi-value" id="totalVolume">R$ 0,00</div>
                    <div class="kpi-change positive" id="totalVolumeTrend">
                        <i class="fas fa-arrow-up"></i>
                        0%
                    </div>
                    <div class="kpi-period">volume de vendas</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-header">
                        <div class="kpi-title">Cashback Distribuído</div>
                        <div class="kpi-icon orange">
                            <i class="fas fa-gift"></i>
                        </div>
                    </div>
                    <div class="kpi-value" id="totalCashback">R$ 0,00</div>
                    <div class="kpi-change positive" id="totalCashbackTrend">
                        <i class="fas fa-arrow-up"></i>
                        0%
                    </div>
                    <div class="kpi-period">para clientes</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-header">
                        <div class="kpi-title">Compras Pendentes</div>
                        <div class="kpi-icon red">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="kpi-value" id="pendingPurchases">0</div>
                    <div class="kpi-change negative" id="pendingCountTrend">
                        <i class="fas fa-arrow-down"></i>
                        0%
                    </div>
                    <div class="kpi-period">aguardando aprovação</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-header">
                        <div class="kpi-title">Ticket Médio</div>
                        <div class="kpi-icon purple">
                            <i class="fas fa-calculator"></i>
                        </div>
                    </div>
                    <div class="kpi-value" id="avgTicket">R$ 0,00</div>
                    <div class="kpi-change positive" id="avgTicketTrend">
                        <i class="fas fa-arrow-up"></i>
                        0%
                    </div>
                    <div class="kpi-period">por transação</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-header">
                        <div class="kpi-title">Taxa de Aprovação</div>
                        <div class="kpi-icon indigo">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="kpi-value" id="approvalRate">0%</div>
                    <div class="kpi-change positive" id="approvalRateTrend">
                        <i class="fas fa-arrow-up"></i>
                        0%
                    </div>
                    <div class="kpi-period">taxa de aprovação</div>
                </div>
            </div>

            <!-- Management Controls -->
            <div class="management-controls">
                <div class="controls-header">
                    <h3 class="controls-title">Controles de Gestão</h3>
                    
                    <div class="bulk-actions">
                        <button class="btn btn-success" id="bulkApprove" disabled>
                            <i class="fas fa-check"></i>
                            Aprovar Selecionadas <span class="count">(0)</span>
                        </button>
                        <button class="btn btn-warning" id="bulkCancel" disabled>
                            <i class="fas fa-times"></i>
                            Cancelar Selecionadas <span class="count">(0)</span>
                        </button>
                        <button class="btn btn-outline" id="bulkExport" disabled>
                            <i class="fas fa-download"></i>
                            Exportar Selecionadas <span class="count">(0)</span>
                        </button>
                    </div>
                </div>

                <!-- Advanced Filters -->
                <div class="advanced-filters">
                    <div class="filter-group">
                        <label class="filter-label">Data Início</label>
                        <input type="date" class="form-input" id="dateFrom">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Data Fim</label>
                        <input type="date" class="form-input" id="dateTo">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Loja</label>
                        <select class="form-select" id="storeFilter">
                            <option value="">Todas as lojas</option>
                            <?php foreach ($stores as $store): ?>
                                <option value="<?php echo $store['id']; ?>">
                                    <?php echo htmlspecialchars($store['nome_fantasia']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Status</label>
                        <select class="form-select" id="statusFilter">
                            <option value="">Todos os status</option>
                            <option value="pendente">Pendente</option>
                            <option value="aprovado">Aprovado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Tipo de Pagamento</label>
                        <select class="form-select" id="paymentFilter">
                            <option value="">Todos os tipos</option>
                            <option value="pix">PIX</option>
                            <option value="cartao">Cartão</option>
                            <option value="boleto">Boleto</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Valor Mínimo</label>
                        <input type="number" class="form-input" id="amountMin" placeholder="R$ 0,00" step="0.01">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Valor Máximo</label>
                        <input type="number" class="form-input" id="amountMax" placeholder="R$ 1000,00" step="0.01">
                    </div>

                    <div class="search-container">
                        <label class="filter-label">Buscar</label>
                        <div style="position: relative;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" class="search-input" id="searchInput" placeholder="Cliente, loja, ID...">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <div class="table-container">
                <div class="table-header">
                    <h3 class="table-title">Lista de Compras</h3>
                    <div class="table-actions">
                        <button class="btn btn-outline" onclick="clearFilters()">
                            <i class="fas fa-eraser"></i>
                            Limpar Filtros
                        </button>
                        <button class="btn btn-primary" onclick="refreshData()">
                            <i class="fas fa-sync"></i>
                            Atualizar
                        </button>
                    </div>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>
                                <div class="custom-checkbox">
                                    <input type="checkbox" id="selectAll">
                                    <span class="checkmark"></span>
                                </div>
                            </th>
                            <th data-sort="id">ID <i class="fas fa-sort"></i></th>
                            <th data-sort="cliente_nome">Cliente <i class="fas fa-sort"></i></th>
                            <th data-sort="loja_nome">Loja <i class="fas fa-sort"></i></th>
                            <th data-sort="valor">Valor <i class="fas fa-sort"></i></th>
                            <th data-sort="cashback_valor">Cashback <i class="fas fa-sort"></i></th>
                            <th data-sort="status">Status <i class="fas fa-sort"></i></th>
                            <th data-sort="data_transacao">Data <i class="fas fa-sort"></i></th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="purchasesTableBody">
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 2rem;">
                                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #ccc;"></i>
                                <p style="margin-top: 1rem; color: #666;">Carregando compras...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination" id="pagination"></div>

            <!-- Insights Section -->
            <div class="insights-section">
                <div class="insights-header">
                    <i class="fas fa-lightbulb"></i>
                    <h3 class="insights-title">Insights de Negócio</h3>
                </div>

                <div class="insights-grid">
                    <div class="insight-card">
                        <div class="insight-header">
                            <div class="insight-icon blue">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="insight-title">Comportamento do Cliente</div>
                        </div>
                        <div class="insight-content">
                            Analise padrões de compra e identifique oportunidades de cross-sell e upsell baseadas no histórico de transações.
                        </div>
                    </div>

                    <div class="insight-card">
                        <div class="insight-header">
                            <div class="insight-icon green">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <div class="insight-title">Performance das Lojas</div>
                        </div>
                        <div class="insight-content">
                            Compare o desempenho entre lojas parceiras e identifique as que geram mais engagement e conversão.
                        </div>
                    </div>

                    <div class="insight-card">
                        <div class="insight-header">
                            <div class="insight-icon orange">
                                <i class="fas fa-coins"></i>
                            </div>
                            <div class="insight-title">Otimização de Cashback</div>
                        </div>
                        <div class="insight-content">
                            Ajuste estratégico dos percentuais de cashback para maximizar a retenção de clientes e o ROI das campanhas.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Indicator -->
    <div id="loadingIndicator" style="display: none;">
        <div class="loading-spinner"></div>
    </div>

    <!-- Modal de Detalhes da Compra -->
    <div id="purchaseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Detalhes da Compra</h2>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body" id="modalContent">
                <div class="loading-content">
                    <i class="fas fa-spinner fa-spin"></i>
                    Carregando detalhes...
                </div>
            </div>
        </div>
    </div>

    <!-- Include the modern JavaScript -->
    <script src="../../assets/js/views/admin/purchases.js"></script>
</body>
</html>