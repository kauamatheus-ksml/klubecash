<?php
// views/stores/financial.php
// Definir o menu ativo na sidebar
$activeMenu = 'financial';

// Incluir arquivos necessários
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/TransactionController.php';
require_once '../../controllers/StoreController.php';
require_once '../../models/CashbackBalance.php';

// Iniciar sessão
session_start();

// Verificar se o usuário está logado e é uma loja
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'loja') {
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

// Obter ID do usuário logado
$userId = $_SESSION['user_id'];

// Obter dados da loja associada ao usuário
$db = Database::getConnection();
$storeQuery = $db->prepare("SELECT id, nome_fantasia FROM lojas WHERE usuario_id = :usuario_id");
$storeQuery->bindParam(':usuario_id', $userId);
$storeQuery->execute();

if ($storeQuery->rowCount() == 0) {
    header('Location: ' . LOGIN_URL . '?error=' . urlencode('Sua conta não está associada a nenhuma loja.'));
    exit;
}

$store = $storeQuery->fetch(PDO::FETCH_ASSOC);
$storeId = $store['id'];
$storeName = $store['nome_fantasia'];

// Determinar aba ativa
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';

// Obter dados de acordo com a aba ativa
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$filters = [];

// Aplicar filtros comuns
if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
    $filters['data_inicio'] = $_GET['data_inicio'];
}
if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
    $filters['data_fim'] = $_GET['data_fim'];
}

// Buscar dados específicos de cada aba
$overviewData = null;
$pendingData = null;
$historyData = null;

// Sempre buscar dados do overview para exibir no cabeçalho
$overviewStats = TransactionController::getStoreFinancialOverview($storeId);

switch ($activeTab) {
    case 'overview':
        // Dados já carregados em $overviewStats
        break;
        
    case 'pending':
        // Filtros específicos para transações pendentes
        if (isset($_GET['valor_min']) && !empty($_GET['valor_min'])) {
            $filters['valor_min'] = floatval($_GET['valor_min']);
        }
        if (isset($_GET['valor_max']) && !empty($_GET['valor_max'])) {
            $filters['valor_max'] = floatval($_GET['valor_max']);
        }
        $pendingData = TransactionController::getPendingTransactionsWithBalance($storeId, $filters, $page);
        break;
        
    case 'history':
        // Filtros específicos para histórico
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        if (isset($_GET['metodo_pagamento']) && !empty($_GET['metodo_pagamento'])) {
            $filters['metodo_pagamento'] = $_GET['metodo_pagamento'];
        }
        $historyData = TransactionController::getPaymentHistoryWithBalance($storeId, $filters, $page);
        break;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <title>Financeiro - <?php echo htmlspecialchars($storeName); ?> - Klube Cash</title>
    
    <link rel="stylesheet" href="../../assets/css/views/stores/financial.css">
    <link rel="stylesheet" href="../../assets/css/openpix-styles.css">
</head>
<body>
    <?php include_once '../components/sidebar-store.php'; ?>
    
    <div class="main-content" id="mainContent">
        <div class="financial-wrapper">
            <!-- Cabeçalho com resumo financeiro sempre visível -->
            <div class="financial-header">
                <h1>Financeiro</h1>
                <p class="subtitle">Gestão financeira completa de <?php echo htmlspecialchars($storeName); ?></p>
                
                <!-- Cards de resumo sempre visíveis -->
                <div class="summary-cards">
                    <div class="summary-card">
                        <div class="summary-icon">💰</div>
                        <div class="summary-content">
                            <span class="summary-label">Total de Vendas</span>
                            <span class="summary-value">R$ <?php echo number_format($overviewStats['total_vendas'] ?? 0, 2, ',', '.'); ?></span>
                        </div>
                    </div>
                    
                    <div class="summary-card">
                        <div class="summary-icon">⏳</div>
                        <div class="summary-content">
                            <span class="summary-label">Comissões Pendentes</span>
                            <span class="summary-value text-warning">R$ <?php echo number_format($overviewStats['comissoes_pendentes'] ?? 0, 2, ',', '.'); ?></span>
                        </div>
                    </div>
                    
                    <div class="summary-card">
                        <div class="summary-icon">✅</div>
                        <div class="summary-content">
                            <span class="summary-label">Comissões Pagas</span>
                            <span class="summary-value text-success">R$ <?php echo number_format($overviewStats['comissoes_pagas'] ?? 0, 2, ',', '.'); ?></span>
                        </div>
                    </div>
                    
                    <div class="summary-card">
                        <div class="summary-icon">💳</div>
                        <div class="summary-content">
                            <span class="summary-label">Saldo Usado</span>
                            <span class="summary-value">R$ <?php echo number_format($overviewStats['total_saldo_usado'] ?? 0, 2, ',', '.'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Navegação em abas -->
            <div class="tabs-navigation">
                <a href="?tab=overview" class="tab-link <?php echo $activeTab === 'overview' ? 'active' : ''; ?>">
                    <span class="tab-icon">📊</span>
                    Visão Geral
                </a>
                <a href="?tab=pending" class="tab-link <?php echo $activeTab === 'pending' ? 'active' : ''; ?>">
                    <span class="tab-icon">⏳</span>
                    Transações Pendentes
                    <?php if (($overviewStats['transacoes_pendentes'] ?? 0) > 0): ?>
                        <span class="tab-badge"><?php echo $overviewStats['transacoes_pendentes']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="?tab=history" class="tab-link <?php echo $activeTab === 'history' ? 'active' : ''; ?>">
                    <span class="tab-icon">📋</span>
                    Histórico de Pagamentos
                </a>
            </div>
            
            <!-- Conteúdo das abas -->
            <div class="tab-content">
                <?php if ($activeTab === 'overview'): ?>
                    <!-- Aba Visão Geral -->
                    <div class="overview-content">
                        <!-- Gráfico de evolução -->
                        <div class="card chart-card">
                            <div class="card-header">
                                <h3>Evolução Mensal</h3>
                                <select id="chartPeriod" class="form-control" style="width: auto;">
                                    <option value="6">Últimos 6 meses</option>
                                    <option value="12">Último ano</option>
                                </select>
                            </div>
                            <div class="card-body">
                                <canvas id="evolutionChart"></canvas>
                            </div>
                        </div>
                        
                        <!-- Últimas transações -->
                        <div class="card recent-transactions">
                            <div class="card-header">
                                <h3>Últimas Transações</h3>
                                <a href="?tab=pending" class="view-all-link">Ver todas →</a>
                            </div>
                            <div class="card-body">
                                <div class="transaction-list">
                                    <?php foreach ($overviewStats['ultimas_transacoes'] ?? [] as $transaction): ?>
                                        <div class="transaction-item">
                                            <div class="transaction-info">
                                                <span class="transaction-client"><?php echo htmlspecialchars($transaction['cliente_nome']); ?></span>
                                                <span class="transaction-date"><?php echo date('d/m/Y H:i', strtotime($transaction['data_transacao'])); ?></span>
                                            </div>
                                            <div class="transaction-values">
                                                <span class="transaction-total">R$ <?php echo number_format($transaction['valor_total'], 2, ',', '.'); ?></span>
                                                <span class="transaction-commission">Comissão: R$ <?php echo number_format($transaction['valor_cashback'], 2, ',', '.'); ?></span>
                                            </div>
                                            <span class="status-badge status-<?php echo $transaction['status']; ?>">
                                                <?php echo ucfirst($transaction['status']); ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php elseif ($activeTab === 'pending'): ?>
                    <!-- Aba Transações Pendentes -->
                    <div class="pending-content">
                        <!-- Filtros -->
                        <div class="card filter-card">
                            <div class="card-header collapsible" onclick="toggleFilters()">
                                <h3>Filtros</h3>
                                <span class="toggle-icon">▼</span>
                            </div>
                            <div class="card-body filters-body" id="filtersBody">
                                <form method="GET" action="" class="filters-form">
                                    <input type="hidden" name="tab" value="pending">
                                    
                                    <div class="filter-row">
                                        <div class="filter-group">
                                            <label>Data Início</label>
                                            <input type="date" name="data_inicio" value="<?php echo $_GET['data_inicio'] ?? ''; ?>" class="form-control">
                                        </div>
                                        
                                        <div class="filter-group">
                                            <label>Data Fim</label>
                                            <input type="date" name="data_fim" value="<?php echo $_GET['data_fim'] ?? ''; ?>" class="form-control">
                                        </div>
                                        
                                        <div class="filter-group">
                                            <label>Valor Mínimo</label>
                                            <input type="number" name="valor_min" value="<?php echo $_GET['valor_min'] ?? ''; ?>" class="form-control" step="0.01">
                                        </div>
                                        
                                        <div class="filter-group">
                                            <label>Valor Máximo</label>
                                            <input type="number" name="valor_max" value="<?php echo $_GET['valor_max'] ?? ''; ?>" class="form-control" step="0.01">
                                        </div>
                                    </div>
                                    
                                    <div class="filter-actions">
                                        <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                                        <a href="?tab=pending" class="btn btn-secondary">Limpar</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Lista de transações pendentes -->
                        <?php if ($pendingData && $pendingData['status'] && !empty($pendingData['data']['transacoes'])): ?>
                            <div class="card">
                                <div class="card-header">
                                    <h3>Transações Pendentes de Pagamento</h3>
                                    <button onclick="openBatchPaymentModal()" class="btn btn-primary">
                                        Pagar Selecionadas
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="data-table">
                                            <thead>
                                                <tr>
                                                    <th><input type="checkbox" id="selectAll"></th>
                                                    <th>Data</th>
                                                    <th>Cliente</th>
                                                    <th>Valor Venda</th>
                                                    <th>Saldo Usado</th>
                                                    <th>Comissão</th>
                                                    <th>Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($pendingData['data']['transacoes'] as $transaction): ?>
                                                    <tr>
                                                        <td>
                                                            <input type="checkbox" class="transaction-checkbox" 
                                                                   value="<?php echo $transaction['id']; ?>"
                                                                   data-valor="<?php echo $transaction['valor_cashback']; ?>">
                                                        </td>
                                                        <td><?php echo date('d/m/Y', strtotime($transaction['data_transacao'])); ?></td>
                                                        <td><?php echo htmlspecialchars($transaction['cliente_nome']); ?></td>
                                                        <td>R$ <?php echo number_format($transaction['valor_total'], 2, ',', '.'); ?></td>
                                                        <td>R$ <?php echo number_format($transaction['valor_saldo_usado'] ?? 0, 2, ',', '.'); ?></td>
                                                        <td>R$ <?php echo number_format($transaction['valor_cashback'], 2, ',', '.'); ?></td>
                                                        <td>
                                                            <button onclick="openTransactionModal(<?php echo $transaction['id']; ?>)" 
                                                                    class="btn btn-sm btn-info">
                                                                Detalhes
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- Paginação -->
                                    <?php if ($pendingData['data']['paginacao']['total_paginas'] > 1): ?>
                                        <div class="pagination">
                                            <!-- código de paginação aqui -->
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon">📋</div>
                                <h3>Nenhuma transação pendente</h3>
                                <p>Todas as suas transações estão em dia!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                <?php elseif ($activeTab === 'history'): ?>
                    <!-- Aba Histórico de Pagamentos -->
                    <div class="history-content">
                        <!-- Filtros -->
                        <div class="card filter-card">
                            <div class="card-header collapsible" onclick="toggleFilters()">
                                <h3>Filtros</h3>
                                <span class="toggle-icon">▼</span>
                            </div>
                            <div class="card-body filters-body" id="filtersBody">
                                <form method="GET" action="" class="filters-form">
                                    <input type="hidden" name="tab" value="history">
                                    
                                    <div class="filter-row">
                                        <div class="filter-group">
                                            <label>Status</label>
                                            <select name="status" class="form-control">
                                                <option value="">Todos</option>
                                                <option value="pendente" <?php echo ($_GET['status'] ?? '') === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                                <option value="aprovado" <?php echo ($_GET['status'] ?? '') === 'aprovado' ? 'selected' : ''; ?>>Aprovado</option>
                                                <option value="rejeitado" <?php echo ($_GET['status'] ?? '') === 'rejeitado' ? 'selected' : ''; ?>>Rejeitado</option>
                                            </select>
                                        </div>
                                        
                                        <div class="filter-group">
                                            <label>Método</label>
                                            <select name="metodo_pagamento" class="form-control">
                                                <option value="">Todos</option>
                                                <option value="pix_mercadopago" <?php echo ($_GET['metodo_pagamento'] ?? '') === 'pix_mercadopago' ? 'selected' : ''; ?>>PIX Mercado Pago</option>
                                                <!-- Outros métodos comentados
                                                <option value="pix_openpix">PIX OpenPix</option>
                                                <option value="transferencia">Transferência</option>
                                                -->
                                            </select>
                                        </div>
                                        
                                        <div class="filter-group">
                                            <label>Data Início</label>
                                            <input type="date" name="data_inicio" value="<?php echo $_GET['data_inicio'] ?? ''; ?>" class="form-control">
                                        </div>
                                        
                                        <div class="filter-group">
                                            <label>Data Fim</label>
                                            <input type="date" name="data_fim" value="<?php echo $_GET['data_fim'] ?? ''; ?>" class="form-control">
                                        </div>
                                    </div>
                                    
                                    <div class="filter-actions">
                                        <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                                        <a href="?tab=history" class="btn btn-secondary">Limpar</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Lista de pagamentos -->
                        <?php if ($historyData && $historyData['status'] && !empty($historyData['data']['pagamentos'])): ?>
                            <div class="card">
                                <div class="card-header">
                                    <h3>Histórico de Pagamentos</h3>
                                </div>
                                <div class="card-body">
                                    <div class="payment-list">
                                        <?php foreach ($historyData['data']['pagamentos'] as $payment): ?>
                                            <div class="payment-item">
                                                <div class="payment-header">
                                                    <div class="payment-info">
                                                        <span class="payment-id">#<?php echo $payment['id']; ?></span>
                                                        <span class="payment-date"><?php echo date('d/m/Y H:i', strtotime($payment['data_pagamento'])); ?></span>
                                                    </div>
                                                    <span class="status-badge status-<?php echo $payment['status']; ?>">
                                                        <?php echo ucfirst($payment['status']); ?>
                                                    </span>
                                                </div>
                                                
                                                <div class="payment-details">
                                                    <div class="detail-row">
                                                        <span class="detail-label">Valor Total:</span>
                                                        <span class="detail-value">R$ <?php echo number_format($payment['valor_total'], 2, ',', '.'); ?></span>
                                                    </div>
                                                    <div class="detail-row">
                                                        <span class="detail-label">Transações:</span>
                                                        <span class="detail-value"><?php echo $payment['quantidade_transacoes']; ?></span>
                                                    </div>
                                                    <div class="detail-row">
                                                        <span class="detail-label">Método:</span>
                                                        <span class="detail-value"><?php echo $payment['metodo_pagamento']; ?></span>
                                                    </div>
                                                </div>
                                                
                                                <div class="payment-actions">
                                                    <button onclick="openPaymentModal(<?php echo $payment['id']; ?>)" 
                                                            class="btn btn-sm btn-info">
                                                        Ver Detalhes
                                                    </button>
                                                    <?php if (!empty($payment['comprovante'])): ?>
                                                        <a href="<?php echo $payment['comprovante']; ?>" 
                                                           target="_blank" 
                                                           class="btn btn-sm btn-secondary">
                                                            Ver Comprovante
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <!-- Paginação -->
                                    <?php if ($historyData['data']['paginacao']['total_paginas'] > 1): ?>
                                        <div class="pagination">
                                            <!-- código de paginação aqui -->
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon">💰</div>
                                <h3>Nenhum pagamento encontrado</h3>
                                <p>Não foram encontrados pagamentos com os filtros selecionados.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Card de informações sempre visível no final -->
            <div class="card info-card collapsible-card">
                <div class="card-header collapsible-header" onclick="toggleInfoSection()">
                    <div class="card-title">
                        <span>📋 Informações sobre o Sistema Financeiro</span>
                        <span class="dropdown-icon" id="infoDropdownIcon">▼</span>
                    </div>
                </div>
                <div class="collapsible-content" id="infoSectionContent" style="display: none;">
                    <div class="info-grid">
                        <div class="info-section">
                            <h4>💰 Como funciona o cashback:</h4>
                            <ul>
                                <li>Você registra a venda do cliente</li>
                                <li>A comissão de 10% é calculada automaticamente</li>
                                <li>5% vai para o cliente como cashback</li>
                                <li>5% fica para o Klube Cash</li>
                                <li>O cliente só recebe após você pagar a comissão</li>
                            </ul>
                        </div>
                        
                        <div class="info-section">
                            <h4>💳 Sobre o uso de saldo:</h4>
                            <ul>
                                <li>Clientes podem usar o cashback acumulado em novas compras</li>
                                <li>A comissão é calculada sobre o valor efetivamente pago</li>
                                <li>Exemplo: Venda de R$ 100 com R$ 20 de saldo = comissão sobre R$ 80</li>
                            </ul>
                        </div>
                        
                        <div class="info-section">
                            <h4>📊 Status dos pagamentos:</h4>
                            <ul>
                                <li><span class="status-badge status-pendente">Pendente</span> - Aguardando análise</li>
                                <li><span class="status-badge status-aprovado">Aprovado</span> - Cashback liberado</li>
                                <li><span class="status-badge status-rejeitado">Rejeitado</span> - Necessita correção</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modais -->
    <!-- Modal de Detalhes da Transação -->
    <div id="transactionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detalhes da Transação</h3>
                <span class="close" onclick="closeTransactionModal()">&times;</span>
            </div>
            <div class="modal-body" id="transactionDetails">
                <!-- Conteúdo carregado via AJAX -->
            </div>
        </div>
    </div>
    
    <!-- Modal de Detalhes do Pagamento -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detalhes do Pagamento</h3>
                <span class="close" onclick="closePaymentModal()">&times;</span>
            </div>
            <div class="modal-body" id="paymentDetails">
                <!-- Conteúdo carregado via AJAX -->
            </div>
        </div>
    </div>
    
    <!-- Modal de Pagamento em Lote -->
    <div id="batchPaymentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Realizar Pagamento</h3>
                <span class="close" onclick="closeBatchPaymentModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="batchPaymentForm">
                    <div class="payment-summary">
                        <h4>Resumo do Pagamento</h4>
                        <div class="summary-row">
                            <span>Transações selecionadas:</span>
                            <span id="selectedCount">0</span>
                        </div>
                        <div class="summary-row">
                            <span>Valor total:</span>
                            <span id="selectedTotal">R$ 0,00</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Método de Pagamento</label>
                        <select name="metodo_pagamento" class="form-control" required>
                            <option value="pix_mercadopago">PIX Mercado Pago</option>
                            <!-- Outros métodos comentados
                            <option value="pix_openpix">PIX OpenPix</option>
                            <option value="transferencia">Transferência Bancária</option>
                            -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Comprovante</label>
                        <input type="file" name="comprovante" class="form-control" accept="image/*,.pdf" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Observações (opcional)</label>
                        <textarea name="observacoes" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" onclick="closeBatchPaymentModal()" class="btn btn-secondary">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Confirmar Pagamento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../../assets/js/stores/financial.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>
</html>