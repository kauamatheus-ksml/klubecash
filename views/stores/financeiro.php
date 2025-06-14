<?php
/**
 * PÁGINA FINANCEIRO - LOJISTA (NOVA)
 * ===================================
 * 
 * Esta página consolida todas as funcionalidades financeiras do lojista:
 * - Resumo geral (dashboard)
 * - Gestão de transações  
 * - Comissões pendentes
 * - Histórico de pagamentos
 * 
 * IMPORTANTE: Mantém toda a lógica existente, apenas reorganiza a interface
 */

// Incluir arquivos de configuração
require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/StoreController.php';
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
    header('Location: ' . LOGIN_URL . '?error=' . urlencode('Acesso restrito a lojas parceiras.'));
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

$store = $storeQuery->fetch(PDO::FETCH_ASSOC);
$storeId = $store['id'];
$storeName = $store['nome_fantasia'];

// Definir menu ativo para sidebar
$activeMenu = 'financeiro';

// ===== CARREGAR DADOS PARA O RESUMO GERAL =====
// 1. Estatísticas principais
$salesQuery = $db->prepare("
    SELECT COUNT(*) as total_vendas, 
           SUM(valor_total) as valor_total_vendas,
           SUM(valor_cashback) as valor_total_cashback,
           SUM(valor_cliente) as valor_total_cliente,
           SUM(valor_admin) as valor_total_admin
    FROM transacoes_cashback 
    WHERE loja_id = :loja_id
");
$salesQuery->bindParam(':loja_id', $storeId);
$salesQuery->execute();
$salesStats = $salesQuery->fetch(PDO::FETCH_ASSOC);

// 2. Comissões pendentes
$pendingQuery = $db->prepare("
    SELECT COUNT(*) as total_pendentes, 
           SUM(valor_cashback) as valor_pendente,
           SUM(valor_cliente) as valor_cliente_pendente
    FROM transacoes_cashback 
    WHERE loja_id = :loja_id AND status = 'pendente'
");
$pendingQuery->bindParam(':loja_id', $storeId);
$pendingQuery->execute();
$pendingStats = $pendingQuery->fetch(PDO::FETCH_ASSOC);

// 3. Pagamentos aprovados
$paidQuery = $db->prepare("
    SELECT COUNT(*) as total_pagas, 
           SUM(valor_cashback) as valor_pago
    FROM transacoes_cashback 
    WHERE loja_id = :loja_id AND status = 'aprovado'
");
$paidQuery->bindParam(':loja_id', $storeId);
$paidQuery->execute();
$paidStats = $paidQuery->fetch(PDO::FETCH_ASSOC);

// ===== CÁLCULOS PARA EXIBIÇÃO =====
$totalVendas = $salesStats['total_vendas'] ?? 0;
$valorTotalVendas = $salesStats['valor_total_vendas'] ?? 0;
$comissoesPendentes = $pendingStats['valor_pendente'] ?? 0;
$comissoesPagas = $paidStats['valor_pago'] ?? 0;
$transacoesPendentes = $pendingStats['total_pendentes'] ?? 0;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <title>Financeiro - Klube Cash</title>
    
    <!-- CSS da nova página financeiro -->
    <link rel="stylesheet" href="../../assets/css/views/stores/financeiro.css">
    <link rel="stylesheet" href="../../assets/css/openpix-styles.css">
    
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Incluir sidebar da loja -->
    <?php include_once '../components/sidebar-store.php'; ?>
    
    <div class="main-content" id="mainContent">
        <div class="financeiro-container">
            
            <!-- ===== HEADER COM RESUMO PRINCIPAL ===== -->
            <div class="financeiro-header">
                <div class="header-content">
                    <h1>Centro Financeiro</h1>
                    <p class="subtitle">Gerencie todas as suas informações financeiras em um só lugar - <?php echo htmlspecialchars($storeName); ?></p>
                </div>
                
                <!-- Cards de resumo principal -->
                <div class="summary-grid">
                    <div class="summary-card vendas">
                        <div class="card-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <div class="card-content">
                            <h3>Total de Vendas</h3>
                            <div class="value">R$ <?php echo number_format($valorTotalVendas, 2, ',', '.'); ?></div>
                            <div class="label"><?php echo number_format($totalVendas); ?> transações registradas</div>
                        </div>
                    </div>
                    
                    <div class="summary-card pendentes">
                        <div class="card-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                        <div class="card-content">
                            <h3>Comissões Pendentes</h3>
                            <div class="value">R$ <?php echo number_format($comissoesPendentes, 2, ',', '.'); ?></div>
                            <div class="label"><?php echo number_format($transacoesPendentes); ?> transações aguardando pagamento</div>
                        </div>
                    </div>
                    
                    <div class="summary-card pagas">
                        <div class="card-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 12l2 2 4-4"></path>
                                <path d="M21 12c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/>
                                <path d="M3 12c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/>
                                <path d="M12 3c0 1.1-.9 2-2 2s-2-.9-2-2 .9-2 2-2 2 .9 2 2z"/>
                                <path d="M12 21c0-1.1-.9-2-2-2s-2 .9-2 2 .9 2 2 2 2-.9 2-2z"/>
                            </svg>
                        </div>
                        <div class="card-content">
                            <h3>Comissões Pagas</h3>
                            <div class="value">R$ <?php echo number_format($comissoesPagas, 2, ',', '.'); ?></div>
                            <div class="label">Pagamentos já processados</div>
                        </div>
                    </div>
                    
                    <div class="summary-card acoes">
                        <div class="card-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                            </svg>
                        </div>
                        <div class="card-content">
                            <h3>Ações Rápidas</h3>
                            <div class="quick-actions">
                                <button class="quick-btn" onclick="showTab('transacoes')">Ver Transações</button>
                                <button class="quick-btn" onclick="showTab('comissoes')">Pagar Comissões</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ===== SISTEMA DE ABAS/TABS ===== -->
            <div class="tabs-container">
                <div class="tabs-header">
                    <button class="tab-btn active" data-tab="resumo" onclick="showTab('resumo')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        Resumo
                    </button>
                    
                    <button class="tab-btn" data-tab="transacoes" onclick="showTab('transacoes')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        Transações (<?php echo number_format($totalVendas); ?>)
                    </button>
                    
                    <button class="tab-btn" data-tab="comissoes" onclick="showTab('comissoes')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        Comissões Pendentes (<?php echo number_format($transacoesPendentes); ?>)
                    </button>
                    
                    <button class="tab-btn" data-tab="historico" onclick="showTab('historico')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                        Histórico de Pagamentos
                    </button>
                </div>
                
                <!-- ===== CONTEÚDO DAS ABAS ===== -->
                
                <!-- ABA 1: RESUMO -->
                <div class="tab-content active" id="tab-resumo">
                    <div class="resumo-content">
                        <div class="resumo-grid">
                            <!-- Gráfico de vendas mensais -->
                            <div class="chart-container">
                                <h3>Vendas dos Últimos 6 Meses</h3>
                                <canvas id="salesChart"></canvas>
                            </div>
                            
                            <!-- Informações úteis -->
                            <div class="info-container">
                                <h3>Como Funciona o Sistema</h3>
                                <div class="info-items">
                                    <div class="info-item">
                                        <div class="info-icon">📊</div>
                                        <div class="info-text">
                                            <h4>Registre suas vendas</h4>
                                            <p>Cadastre as vendas realizadas para seus clientes para gerar cashback</p>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-icon">💰</div>
                                        <div class="info-text">
                                            <h4>Pague a comissão de 10%</h4>
                                            <p>5% vai como cashback para o cliente e 5% fica para o Klube Cash</p>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-icon">✅</div>
                                        <div class="info-text">
                                            <h4>Cashback liberado</h4>
                                            <p>Após aprovação do pagamento, o cashback é liberado para o cliente</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- ABA 2: TRANSAÇÕES -->
                <div class="tab-content" id="tab-transacoes">
                    <div class="section-header">
                        <h3>Todas as Transações</h3>
                        <div class="section-actions">
                            <button class="btn-action primary" onclick="window.location.href='<?php echo STORE_REGISTER_TRANSACTION_URL; ?>'">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                Nova Transação
                            </button>
                            <button class="btn-action secondary" onclick="showFilterModal('transacoes')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="22,3 2,3 10,12.46 10,19 14,21 14,12.46"></polygon>
                                </svg>
                                Filtros
                            </button>
                        </div>
                    </div>
                    
                    <!-- Lista de transações será carregada via AJAX -->
                    <div id="transacoes-list" class="data-list">
                        <div class="loading-state">
                            <div class="spinner"></div>
                            <p>Carregando transações...</p>
                        </div>
                    </div>
                </div>
                
                <!-- ABA 3: COMISSÕES PENDENTES -->
                <div class="tab-content" id="tab-comissoes">
                    <div class="section-header">
                        <h3>Comissões Pendentes de Pagamento</h3>
                        <div class="section-actions">
                            <button class="btn-action success" onclick="openPaymentModal()" id="paySelectedBtn" disabled>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 12l2 2 4-4"></path>
                                    <path d="M21 12c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/>
                                </svg>
                                Pagar Selecionadas
                            </button>
                            <button class="btn-action secondary" onclick="showFilterModal('comissoes')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="22,3 2,3 10,12.46 10,19 14,21 14,12.46"></polygon>
                                </svg>
                                Filtros
                            </button>
                        </div>
                    </div>
                    
                    <!-- Informação sobre seleção -->
                    <div class="selection-info" id="selectionInfo" style="display: none;">
                        <div class="selection-summary">
                            <span id="selectedCount">0</span> transações selecionadas - 
                            Total: R$ <span id="selectedTotal">0,00</span>
                        </div>
                        <button class="btn-clear" onclick="clearSelection()">Limpar Seleção</button>
                    </div>
                    
                    <!-- Lista de comissões será carregada via AJAX -->
                    <div id="comissoes-list" class="data-list">
                        <div class="loading-state">
                            <div class="spinner"></div>
                            <p>Carregando comissões pendentes...</p>
                        </div>
                    </div>
                </div>
                
                <!-- ABA 4: HISTÓRICO -->
                <div class="tab-content" id="tab-historico">
                    <div class="section-header">
                        <h3>Histórico de Pagamentos</h3>
                        <div class="section-actions">
                            <button class="btn-action secondary" onclick="showFilterModal('historico')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="22,3 2,3 10,12.46 10,19 14,21 14,12.46"></polygon>
                                </svg>
                                Filtros
                            </button>
                        </div>
                    </div>
                    
                    <!-- Lista de histórico será carregada via AJAX -->
                    <div id="historico-list" class="data-list">
                        <div class="loading-state">
                            <div class="spinner"></div>
                            <p>Carregando histórico...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ===== MODAIS ===== -->
    
    <!-- Modal de Filtros -->
    <div class="modal" id="filterModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Filtros</h3>
                <button class="modal-close" onclick="closeModal('filterModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="filterForm">
                    <div class="form-group">
                        <label>Período:</label>
                        <div class="date-range">
                            <input type="date" name="data_inicio" placeholder="Data Início">
                            <input type="date" name="data_fim" placeholder="Data Fim">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Status:</label>
                        <select name="status">
                            <option value="">Todos os status</option>
                            <option value="pendente">Pendente</option>
                            <option value="aprovado">Aprovado</option>
                            <option value="rejeitado">Rejeitado</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Valor Mínimo:</label>
                        <input type="number" name="valor_min" step="0.01" placeholder="R$ 0,00">
                    </div>
                    
                    <div class="form-group">
                        <label>Valor Máximo:</label>
                        <input type="number" name="valor_max" step="0.01" placeholder="R$ 0,00">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-action secondary" onclick="clearFilters()">Limpar</button>
                <button class="btn-action primary" onclick="applyFilters()">Aplicar Filtros</button>
            </div>
        </div>
    </div>
    
    <!-- Modal de Pagamento -->
    <div class="modal" id="paymentModal">
        <div class="modal-content large">
            <div class="modal-header">
                <h3>Pagar Comissões Selecionadas</h3>
                <button class="modal-close" onclick="closeModal('paymentModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="payment-summary">
                    <div class="summary-item">
                        <label>Transações Selecionadas:</label>
                        <span id="paymentTransactionCount">0</span>
                    </div>
                    <div class="summary-item">
                        <label>Valor Total das Vendas:</label>
                        <span id="paymentTotalSales">R$ 0,00</span>
                    </div>
                    <div class="summary-item">
                        <label>Saldo Usado pelos Clientes:</label>
                        <span id="paymentUsedBalance">R$ 0,00</span>
                    </div>
                    <div class="summary-item highlight">
                        <label>Comissão a Pagar (10%):</label>
                        <span id="paymentCommission">R$ 0,00</span>
                    </div>
                </div>
                
                <form id="paymentForm">
                    <div class="form-group">
                        <label>Método de Pagamento:</label>
                        <select name="metodo_pagamento" required>
                            <option value="pix_openpix">PIX (OpenPix/Mercado Pago)</option>
                            <!-- Outros métodos comentados conforme solicitado -->
                            <!-- <option value="transferencia">Transferência Bancária</option> -->
                            <!-- <option value="boleto">Boleto</option> -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Número de Referência/Transação:</label>
                        <input type="text" name="numero_referencia" placeholder="Digite o código da transação PIX" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Observações (opcional):</label>
                        <textarea name="observacao" rows="3" placeholder="Adicione observações sobre este pagamento..."></textarea>
                    </div>
                    
                    <input type="hidden" name="transacoes" id="selectedTransactions">
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-action secondary" onclick="closeModal('paymentModal')">Cancelar</button>
                <button class="btn-action success" onclick="processPayment()">Confirmar Pagamento</button>
            </div>
        </div>
    </div>
    
    <!-- Modal de Detalhes -->
    <div class="modal" id="detailsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detalhes da Transação</h3>
                <button class="modal-close" onclick="closeModal('detailsModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="detailsContent">
                    <!-- Conteúdo carregado dinamicamente -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-action primary" onclick="closeModal('detailsModal')">Fechar</button>
            </div>
        </div>
    </div>
    
    <!-- JavaScript da página -->
    <script src="../../assets/js/stores/financeiro.js"></script>
    
    <script>
        // Configuração inicial
        const STORE_ID = <?php echo $storeId; ?>;
        const STORE_NAME = '<?php echo addslashes($storeName); ?>';
        
        // Dados para o gráfico
        const chartData = {
            labels: [], // Será preenchido via AJAX
            datasets: [{
                label: 'Vendas (R$)',
                data: [], // Será preenchido via AJAX
                backgroundColor: 'rgba(255, 122, 0, 0.7)',
                borderColor: 'rgba(255, 122, 0, 1)',
                borderWidth: 2
            }]
        };
        
        // Inicializar a página quando carregada
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar gráfico
            initializeSalesChart();
            
            // Carregar dados da primeira aba ativa
            loadTabData('resumo');
        });
    </script>
</body>
</html>