<?php
// views/stores/payment-history.php
// Definir o menu ativo na sidebar
$activeMenu = 'payment-history';

// Incluir arquivos necessários
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/TransactionController.php';
require_once '../../models/CashbackBalance.php';

// Iniciar sessão
session_start();

// Verificar se o usuário está logado e é uma loja
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'loja') {
    // Redirecionar para a página de login com mensagem de erro
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

// Verificar se o usuário tem uma loja associada
if ($storeQuery->rowCount() == 0) {
    header('Location: ' . LOGIN_URL . '?error=' . urlencode('Sua conta não está associada a nenhuma loja. Entre em contato com o suporte.'));
    exit;
}

// Obter os dados da loja
$store = $storeQuery->fetch(PDO::FETCH_ASSOC);
$storeId = $store['id'];
$storeName = $store['nome_fantasia'];

// Definir parâmetros de paginação e filtros
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$filters = [];

// Aplicar filtros se fornecidos
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}
if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
    $filters['data_inicio'] = $_GET['data_inicio'];
}
if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
    $filters['data_fim'] = $_GET['data_fim'];
}
if (isset($_GET['metodo_pagamento']) && !empty($_GET['metodo_pagamento'])) {
    $filters['metodo_pagamento'] = $_GET['metodo_pagamento'];
}

// Obter histórico de pagamentos com informações de saldo
$result = TransactionController::getPaymentHistoryWithBalance($storeId, $filters, $page);

// Calcular estatísticas
$totalPagamentos = 0;
$totalAprovados = 0;
$totalPendentes = 0;
$totalRejeitados = 0;
$valorTotalPagamentos = 0;
$valorTotalVendasOriginais = 0;
$totalSaldoUsado = 0;

if ($result['status'] && isset($result['data']['pagamentos'])) {
    foreach ($result['data']['pagamentos'] as $payment) {
        $totalPagamentos++;
        $valorTotalPagamentos += $payment['valor_total'];
        $valorTotalVendasOriginais += $payment['valor_vendas_originais'];
        $totalSaldoUsado += $payment['total_saldo_usado'];
        
        if ($payment['status'] === 'aprovado') {
            $totalAprovados++;
        } elseif ($payment['status'] === 'pendente') {
            $totalPendentes++;
        } elseif ($payment['status'] === 'rejeitado') {
            $totalRejeitados++;
        }
    }
}

// Método de pagamento para exibição
$metodosPagamento = [
    'pix' => 'PIX',
    'pix_mercadopago' => 'PIX Mercado Pago',
    'transferencia' => 'Transferência Bancária',
    'boleto' => 'Boleto',
    'cartao' => 'Cartão de Crédito',
    'outro' => 'Outro'
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <title>Histórico de Pagamentos - Klube Cash</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/views/stores/payment-history.css">
</head>
<body>
    <?php include_once '../components/sidebar-store.php'; ?>
    
    <!-- Conteúdo Principal -->
    <div class="main-content" id="mainContent">
        <div class="dashboard-wrapper">
            <!-- Cabeçalho -->
            <div class="dashboard-header">
                <h1>Histórico de Pagamentos</h1>
                <p class="subtitle">Acompanhe todos os pagamentos de comissões realizados para <?php echo htmlspecialchars($storeName); ?></p>
            </div>
            
            <!-- Cards de estatísticas -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-card-title">Total de Pagamentos</div>
                    <div class="stat-card-value"><?php echo number_format($totalPagamentos); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-title">Pagamentos Aprovados</div>
                    <div class="stat-card-value"><?php echo number_format($totalAprovados); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-title">Pagamentos Pendentes</div>
                    <div class="stat-card-value"><?php echo number_format($totalPendentes); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-title">Valor Total Pago</div>
                    <div class="stat-card-value">R$ <?php echo number_format($valorTotalPagamentos, 2, ',', '.'); ?></div>
                    <div class="stat-card-subtitle">Comissões pagas ao Klube Cash</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-title">Valor Total de Vendas</div>
                    <div class="stat-card-value">R$ <?php echo number_format($valorTotalVendasOriginais, 2, ',', '.'); ?></div>
                    <div class="stat-card-subtitle">Valor original das vendas</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-title">Total Saldo Usado</div>
                    <div class="stat-card-value">R$ <?php echo number_format($totalSaldoUsado, 2, ',', '.'); ?></div>
                    <div class="stat-card-subtitle">Desconto dado aos clientes</div>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="card filter-container">
                <div class="card-header">
                    <div class="card-title">Filtros</div>
                </div>
                <div class="filter-form">
                    <form method="GET" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <option value="">Todos</option>
                                    <option value="pendente" <?php echo (isset($_GET['status']) && $_GET['status'] === 'pendente') ? 'selected' : ''; ?>>Pendente</option>
                                    <option value="aprovado" <?php echo (isset($_GET['status']) && $_GET['status'] === 'aprovado') ? 'selected' : ''; ?>>Aprovado</option>
                                    <option value="rejeitado" <?php echo (isset($_GET['status']) && $_GET['status'] === 'rejeitado') ? 'selected' : ''; ?>>Rejeitado</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="metodo_pagamento">Método de Pagamento</label>
                                <select id="metodo_pagamento" name="metodo_pagamento">
                                    <option value="">Todos</option>
                                    <?php foreach ($metodosPagamento as $key => $value): ?>
                                        <option value="<?php echo $key; ?>" <?php echo (isset($_GET['metodo_pagamento']) && $_GET['metodo_pagamento'] === $key) ? 'selected' : ''; ?>><?php echo $value; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="data_inicio">Data Início</label>
                                <input type="date" id="data_inicio" name="data_inicio" value="<?php echo isset($_GET['data_inicio']) ? htmlspecialchars($_GET['data_inicio']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="data_fim">Data Fim</label>
                                <input type="date" id="data_fim" name="data_fim" value="<?php echo isset($_GET['data_fim']) ? htmlspecialchars($_GET['data_fim']) : ''; ?>">
                            </div>
                            
                            <div class="form-buttons">
                                <button type="submit" class="btn btn-primary">Filtrar</button>
                                <a href="<?php echo STORE_PAYMENT_HISTORY_URL; ?>" class="btn btn-secondary">Limpar</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Listagem de Pagamentos -->
            <div class="card payments-container">
                <div class="card-header">
                    <div class="card-title">Histórico de Pagamentos</div>
                </div>
                
                <?php if ($result['status'] && count($result['data']['pagamentos']) > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#ID</th>
                                    <th>Data</th>
                                    <th>Valor Vendas</th>
                                    <th>Saldo Usado</th>
                                    <th>Comissão Paga</th>
                                    <th>Método</th>
                                    <th>Status</th>
                                    <th>Transações</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($result['data']['pagamentos'] as $payment): ?>
                                    <tr>
                                        <td><?php echo $payment['id']; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($payment['data_registro'])); ?></td>
                                        <td>
                                            <div class="valor-detalhado">
                                                <strong>R$ <?php echo number_format($payment['valor_vendas_originais'], 2, ',', '.'); ?></strong>
                                                <small class="valor-original">Total vendas</small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($payment['total_saldo_usado'] > 0): ?>
                                                <span class="saldo-usado">
                                                    💰 R$ <?php echo number_format($payment['total_saldo_usado'], 2, ',', '.'); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="sem-saldo">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="valor-detalhado">
                                                <strong>R$ <?php echo number_format($payment['valor_total'], 2, ',', '.'); ?></strong>
                                                <?php if ($payment['total_saldo_usado'] > 0): ?>
                                                    <small class="valor-liquido">Valor líquido</small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo isset($metodosPagamento[$payment['metodo_pagamento']]) ? $metodosPagamento[$payment['metodo_pagamento']] : $payment['metodo_pagamento']; ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $payment['status']; ?>">
                                                <?php 
                                                    switch($payment['status']) {
                                                        case 'aprovado':
                                                            echo 'Aprovado';
                                                            break;
                                                        case 'pendente':
                                                            echo 'Pendente';
                                                            break;
                                                        case 'rejeitado':
                                                            echo 'Rejeitado';
                                                            break;
                                                        default:
                                                            echo ucfirst($payment['status']);
                                                    }
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="transacoes-info">
                                                <?php echo $payment['qtd_transacoes']; ?> vendas
                                                <?php if ($payment['qtd_com_saldo'] > 0): ?>
                                                    <small>(<?php echo $payment['qtd_com_saldo']; ?> c/ saldo)</small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-action" onclick="viewPaymentDetails(<?php echo $payment['id']; ?>)">Detalhes</button>
                                                <?php if (!empty($payment['comprovante'])): ?>
                                                    <button class="btn btn-action" onclick="viewReceipt('<?php echo htmlspecialchars($payment['comprovante']); ?>')">Comprovante</button>
                                                <?php endif; ?>
                                                <?php if ($payment['status'] === 'aprovado' && !empty($payment['mp_payment_id'])): ?>
                                                    <button class="btn btn-action btn-warning" onclick="requestRefund(<?php echo $payment['id']; ?>, '<?php echo $payment['valor_total']; ?>', '<?php echo $payment['mp_payment_id']; ?>')">
                                                        Solicitar Devolução
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginação -->
                    <?php if ($result['data']['paginacao']['total_paginas'] > 1): ?>
                        <div class="pagination">
                            <div class="pagination-info">
                                Página <?php echo $result['data']['paginacao']['pagina_atual']; ?> de <?php echo $result['data']['paginacao']['total_paginas']; ?>
                            </div>
                            <div class="pagination-links">
                                <?php if ($result['data']['paginacao']['pagina_atual'] > 1): ?>
                                    <a href="?page=1<?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['metodo_pagamento']) ? '&metodo_pagamento=' . urlencode($_GET['metodo_pagamento']) : ''; ?><?php echo isset($_GET['data_inicio']) ? '&data_inicio=' . urlencode($_GET['data_inicio']) : ''; ?><?php echo isset($_GET['data_fim']) ? '&data_fim=' . urlencode($_GET['data_fim']) : ''; ?>" class="page-link">Primeira</a>
                                    <a href="?page=<?php echo $result['data']['paginacao']['pagina_atual'] - 1; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['metodo_pagamento']) ? '&metodo_pagamento=' . urlencode($_GET['metodo_pagamento']) : ''; ?><?php echo isset($_GET['data_inicio']) ? '&data_inicio=' . urlencode($_GET['data_inicio']) : ''; ?><?php echo isset($_GET['data_fim']) ? '&data_fim=' . urlencode($_GET['data_fim']) : ''; ?>" class="page-link">Anterior</a>
                                <?php endif; ?>
                                
                                <?php if ($result['data']['paginacao']['pagina_atual'] < $result['data']['paginacao']['total_paginas']): ?>
                                    <a href="?page=<?php echo $result['data']['paginacao']['pagina_atual'] + 1; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['metodo_pagamento']) ? '&metodo_pagamento=' . urlencode($_GET['metodo_pagamento']) : ''; ?><?php echo isset($_GET['data_inicio']) ? '&data_inicio=' . urlencode($_GET['data_inicio']) : ''; ?><?php echo isset($_GET['data_fim']) ? '&data_fim=' . urlencode($_GET['data_fim']) : ''; ?>" class="page-link">Próxima</a>
                                    <a href="?page=<?php echo $result['data']['paginacao']['total_paginas']; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['metodo_pagamento']) ? '&metodo_pagamento=' . urlencode($_GET['metodo_pagamento']) : ''; ?><?php echo isset($_GET['data_inicio']) ? '&data_inicio=' . urlencode($_GET['data_inicio']) : ''; ?><?php echo isset($_GET['data_fim']) ? '&data_fim=' . urlencode($_GET['data_fim']) : ''; ?>" class="page-link">Última</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="16" rx="2"></rect>
                                <line x1="2" y1="9" x2="22" y2="9"></line>
                            </svg>
                        </div>
                        <h3>Nenhum pagamento encontrado</h3>
                        <p>Não foram encontrados pagamentos com os filtros selecionados.</p>
                        <a href="<?php echo STORE_PENDING_TRANSACTIONS_URL; ?>" class="btn btn-primary">Ver Comissões Pendentes</a>
                    </div>
                <?php endif; ?>
            </div>
            
           <!-- Informações sobre Status e Saldo (Dropdown Colapsável) -->
            <div class="card info-card collapsible-card">
                <div class="card-header collapsible-header" onclick="toggleInfoSection()">
                    <div class="card-title">
                        <span>📋 Informações sobre Pagamentos e Saldo</span>
                        <span class="dropdown-icon" id="infoDropdownIcon">▼</span>
                    </div>
                </div>
                <div class="collapsible-content" id="infoSectionContent" style="display: none;">
                    <div class="status-info">
                        <div class="info-section">
                            <h4>📊 Status dos Pagamentos:</h4>
                            <div class="status-item">
                                <span class="status-badge status-pendente">Pendente</span>
                                <p>O pagamento foi registrado e está aguardando a análise do administrador.</p>
                            </div>
                            <div class="status-item">
                                <span class="status-badge status-aprovado">Aprovado</span>
                                <p>O pagamento foi confirmado e o cashback já foi liberado para os clientes.</p>
                            </div>
                            <div class="status-item">
                                <span class="status-badge status-rejeitado">Rejeitado</span>
                                <p>O pagamento foi rejeitado pelo administrador. Verifique o motivo nos detalhes e faça um novo pagamento.</p>
                            </div>
                        </div>
                        
                        <div class="info-section">
                            <h4>💰 Sobre o Uso de Saldo:</h4>
                            <ul>
                                <li><strong>Valor Vendas:</strong> Valor original total das vendas incluídas no pagamento</li>
                                <li><strong>Saldo Usado:</strong> Total de saldo de cashback usado pelos clientes nas vendas</li>
                                <li><strong>Comissão Paga:</strong> Valor líquido pago ao Klube Cash (sobre valor efetivamente cobrado)</li>
                                <li><strong>Transações c/ saldo:</strong> Quantidade de vendas onde clientes usaram saldo</li>
                            </ul>
                        </div>
                        
                        <div class="info-section">
                            <h4>🔄 Processo de Pagamento:</h4>
                            <ol>
                                <li>Você seleciona transações pendentes e realiza o pagamento</li>
                                <li>A comissão é calculada sobre o valor efetivamente cobrado (descontando saldo usado)</li>
                                <li>O administrador analisa e aprova/rejeita o pagamento</li>
                                <li>Após aprovação, o cashback é liberado para os clientes</li>
                            </ol>
                        </div>
                        
                        <div class="info-section">
                            <h4>↩️ Solicitação de Devolução:</h4>
                            <ul>
                                <li>Você pode solicitar devolução de pagamentos aprovados via PIX Mercado Pago</li>
                                <li>As devoluções podem ser totais ou parciais</li>
                                <li>O administrador precisa aprovar a solicitação de devolução</li>
                                <li>O cashback dos clientes será revertido após devolução aprovada</li>
                            </ul>
                        </div>
                        
                        <div class="info-section">
                            <h4>ℹ️ Dicas Importantes:</h4>
                            <ul>
                                <li>Mantenha seus comprovantes de pagamento organizados</li>
                                <li>Realize pagamentos regularmente para liberar o cashback dos clientes</li>
                                <li>Em caso de rejeição, verifique o motivo e faça um novo pagamento</li>
                                <li>O valor da comissão é sempre calculado sobre o valor efetivamente pago pelo cliente</li>
                                <li>Solicite devoluções apenas quando necessário, pois afeta o cashback dos clientes</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Detalhes de Pagamento -->
    <div id="paymentDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Detalhes do Pagamento</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body" id="paymentDetailsContent">
                <p>Carregando detalhes...</p>
            </div>
        </div>
    </div>
    
    <!-- Modal de Comprovante -->
    <div id="receiptModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Comprovante de Pagamento</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body receipt-container" id="receiptContent">
                <div class="receipt-image-container">
                    <img id="receiptImage" src="" alt="Comprovante de Pagamento">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Solicitação de Devolução -->
    <div id="refundModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Solicitar Devolução PIX</h2>
                <span class="close" onclick="closeRefundModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="refund-info">
                    <div class="info-alert">
                        <strong>⚠️ Importante:</strong> Ao solicitar uma devolução, o cashback dos clientes relacionado a este pagamento será revertido após aprovação.
                    </div>
                </div>
                
                <form id="refundForm">
                    <input type="hidden" id="refundPaymentId" value="">
                    <input type="hidden" id="refundMpPaymentId" value="">
                    
                    <div class="form-group">
                        <label>Valor do Pagamento:</label>
                        <div class="payment-info">
                            <span id="refundPaymentAmount" class="payment-value"></span>
                            <small>Valor da comissão paga</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="refundType">Tipo de Devolução:</label>
                        <select id="refundType" onchange="toggleRefundAmount()">
                            <option value="total">Devolução Total</option>
                            <option value="parcial">Devolução Parcial</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="amountGroup" style="display: none;">
                        <label for="refundAmount">Valor a Devolver:</label>
                        <input type="number" id="refundAmount" step="0.01" min="0.01" placeholder="0,00">
                        <small class="form-help">Informe o valor em reais (ex: 150.50)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="refundReason">Motivo da Devolução: *</label>
                        <textarea id="refundReason" rows="4" placeholder="Descreva detalhadamente o motivo da solicitação de devolução..." required></textarea>
                        <small class="form-help">Este motivo será analisado pelo administrador</small>
                    </div>
                    
                    <div class="refund-consequences">
                        <h4>Consequências da Devolução:</h4>
                        <ul>
                            <li>O cashback dos clientes relacionado será removido</li>
                            <li>As transações voltarão ao status original</li>
                            <li>O valor será estornado via PIX após aprovação</li>
                            <li>O processo pode levar até 3 dias úteis</li>
                        </ul>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" onclick="submitRefundRequest()">Solicitar Devolução</button>
                <button class="btn btn-secondary" onclick="closeRefundModal()">Cancelar</button>
            </div>
        </div>
    </div>
    
    <script>
        let currentRefundData = null;
        
        function toggleInfoSection() {
            const content = document.getElementById('infoSectionContent');
            const icon = document.getElementById('infoDropdownIcon');
            const card = content.closest('.collapsible-card');
            
            if (content.style.display === 'none' || content.style.display === '') {
                // Abrir
                content.style.display = 'block';
                content.classList.add('opening');
                content.classList.remove('closing');
                icon.classList.add('open');
                card.classList.add('expanded');
                
                // Remover classe de animação após completar
                setTimeout(() => {
                    content.classList.remove('opening');
                }, 400);
                
                // Salvar estado no localStorage
                localStorage.setItem('infoSectionOpen', 'true');
                
            } else {
                // Fechar
                content.classList.add('closing');
                content.classList.remove('opening');
                icon.classList.remove('open');
                card.classList.remove('expanded');
                
                // Ocultar após animação
                setTimeout(() => {
                    content.style.display = 'none';
                    content.classList.remove('closing');
                }, 400);
                
                // Salvar estado no localStorage
                localStorage.setItem('infoSectionOpen', 'false');
            }
        }

        // Restaurar estado do dropdown ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            const savedState = localStorage.getItem('infoSectionOpen');
            const content = document.getElementById('infoSectionContent');
            const icon = document.getElementById('infoDropdownIcon');
            const card = content.closest('.collapsible-card');
            
            if (savedState === 'true') {
                content.style.display = 'block';
                icon.classList.add('open');
                card.classList.add('expanded');
            }
            
            // Adicionar indicador visual ao passar o mouse
            const header = document.querySelector('.collapsible-header');
            if (header) {
                header.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = '#f8f9fa';
                });
                
                header.addEventListener('mouseleave', function() {
                    if (!card.classList.contains('expanded')) {
                        this.style.backgroundColor = '';
                    }
                });
            }
        });
        
        // Função para solicitar devolução
        function requestRefund(paymentId, amount, mpPaymentId) {
            currentRefundData = {
                id: paymentId,
                amount: amount,
                mp_payment_id: mpPaymentId
            };
            
            document.getElementById('refundPaymentId').value = paymentId;
            document.getElementById('refundMpPaymentId').value = mpPaymentId;
            document.getElementById('refundPaymentAmount').textContent = `R$ ${formatMoney(amount)}`;
            document.getElementById('refundModal').style.display = 'block';
        }
        
        async function submitRefundRequest() {
            const paymentId = document.getElementById('refundPaymentId').value;
            const refundType = document.getElementById('refundType').value;
            const refundAmount = document.getElementById('refundAmount').value;
            const reason = document.getElementById('refundReason').value;
            
            if (!reason.trim()) {
                showNotification('Motivo da devolução é obrigatório', 'error');
                return;
            }
            
            if (refundType === 'parcial' && (!refundAmount || parseFloat(refundAmount) <= 0)) {
                showNotification('Para devolução parcial, informe um valor válido', 'error');
                return;
            }
            
            const payload = {
                payment_id: parseInt(paymentId),
                reason: reason.trim()
            };
            
            // Se for devolução parcial, incluir o valor
            if (refundType === 'parcial' && refundAmount) {
                const amount = parseFloat(refundAmount);
                const maxAmount = parseFloat(currentRefundData.amount);
                
                if (amount > maxAmount) {
                    showNotification(`Valor da devolução não pode ser maior que R$ ${formatMoney(maxAmount)}`, 'error');
                    return;
                }
                
                payload.amount = amount;
            }
            
            try {
                const response = await fetch('../../api/refunds.php?action=request', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                
                const result = await response.json();
                
                if (result.status) {
                    showNotification('Solicitação de devolução enviada com sucesso! Aguarde a análise do administrador.', 'success');
                    closeRefundModal();
                    // Opcional: recarregar a página para atualizar status
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showNotification('Erro ao solicitar devolução: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                showNotification('Erro de conexão. Tente novamente.', 'error');
            }
        }
        
        function toggleRefundAmount() {
            const type = document.getElementById('refundType').value;
            const amountGroup = document.getElementById('amountGroup');
            
            if (type === 'parcial') {
                amountGroup.style.display = 'block';
                document.getElementById('refundAmount').max = currentRefundData.amount;
                document.getElementById('refundAmount').placeholder = `Máximo: R$ ${formatMoney(currentRefundData.amount)}`;
            } else {
                amountGroup.style.display = 'none';
                document.getElementById('refundAmount').value = '';
            }
        }
        
        function closeRefundModal() {
            document.getElementById('refundModal').style.display = 'none';
            document.getElementById('refundForm').reset();
            document.getElementById('amountGroup').style.display = 'none';
            currentRefundData = null;
        }
        
        function formatMoney(value) {
            return parseFloat(value).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
        
        function showNotification(message, type) {
            // Sistema simples de notificação - você pode melhorar isso
            const alertClass = type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info';
            
            // Criar elemento de notificação
            const notification = document.createElement('div');
            notification.className = `alert alert-${alertClass} notification-toast`;
            notification.innerHTML = `
                <div class="notification-content">
                    <span>${message}</span>
                    <button class="notification-close" onclick="this.parentElement.parentElement.remove()">&times;</button>
                </div>
            `;
            
            // Adicionar ao body
            document.body.appendChild(notification);
            
            // Remover após 5 segundos
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }

document.addEventListener('DOMContentLoaded', function() {
    // Elementos dos modais - obtendo referências dos elementos DOM
    const paymentDetailsModal = document.getElementById('paymentDetailsModal');
    const receiptModal = document.getElementById('receiptModal');
    const refundModal = document.getElementById('refundModal');
    const paymentDetailsContent = document.getElementById('paymentDetailsContent');
    const receiptImage = document.getElementById('receiptImage');
    
    // Configuração dos botões de fechar modais
    const closeButtons = document.getElementsByClassName('close');
    for (let i = 0; i < closeButtons.length; i++) {
        closeButtons[i].addEventListener('click', function() {
            paymentDetailsModal.style.display = 'none';
            receiptModal.style.display = 'none';
            refundModal.style.display = 'none';
        });
    }
    
    // Fechar modal quando clicar fora dela (no backdrop)
    window.addEventListener('click', function(event) {
        if (event.target === paymentDetailsModal) {
            paymentDetailsModal.style.display = 'none';
        }
        if (event.target === receiptModal) {
            receiptModal.style.display = 'none';
        }
        if (event.target === refundModal) {
            refundModal.style.display = 'none';
        }
    });
    
    // Função principal para visualizar detalhes do pagamento
    window.viewPaymentDetails = function(paymentId) {
        // Validação básica do ID do pagamento
        if (!paymentId || paymentId <= 0) {
            alert('ID do pagamento inválido');
            return;
        }
        
        // Abrir modal e mostrar loading
        paymentDetailsModal.style.display = 'block';
        paymentDetailsContent.innerHTML = '<div class="loading-state"><div class="spinner"></div><p>Carregando detalhes...</p></div>';
        
        // Usar TransactionController para buscar detalhes com informações de saldo
        fetch('../../controllers/TransactionController.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=payment_details_with_balance&payment_id=' + encodeURIComponent(paymentId)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data && data.status) {
                renderPaymentDetailsWithBalance(data.data);
            } else {
                const errorMessage = data && data.message ? data.message : 'Erro desconhecido ao carregar detalhes';
                paymentDetailsContent.innerHTML = `<div class="error-state"><p class="error">Erro: ${errorMessage}</p></div>`;
            }
        })
        .catch(error => {
            console.error('Erro na requisição:', error);
            paymentDetailsContent.innerHTML = `
                <div class="error-state">
                    <p class="error">
                        Erro de conexão. Verifique sua internet e tente novamente.
                        <br><small>Detalhes técnicos: ${error.message}</small>
                    </p>
                </div>
            `;
        });
    };
    
    // Função para visualizar comprovante de pagamento
    window.viewReceipt = function(receiptUrl) {
        if (!receiptUrl) {
            alert('Comprovante não disponível');
            return;
        }
        
        receiptImage.src = '../../uploads/comprovantes/' + encodeURIComponent(receiptUrl);
        receiptModal.style.display = 'block';
        
        receiptImage.onload = function() {
            if (receiptImage.height > 600) {
                receiptImage.style.height = '600px';
                receiptImage.style.width = 'auto';
            }
        };
        
        receiptImage.onerror = function() {
            alert('Erro ao carregar o comprovante. Arquivo pode estar corrompido ou não encontrado.');
            receiptModal.style.display = 'none';
        };
    };
    
    // Função para renderizar os detalhes do pagamento com informações de saldo
    function renderPaymentDetailsWithBalance(data) {
        if (!data || !data.pagamento) {
            paymentDetailsContent.innerHTML = '<div class="error-state"><p class="error">Dados do pagamento não encontrados.</p></div>';
            return;
        }
        
        const payment = data.pagamento;
        const transactions = data.transacoes || [];
        
        // Construção do HTML com informações de saldo
        let html = `
            <div class="payment-details-container">
                <!-- Resumo do Pagamento -->
                <div class="payment-summary">
                    <h3>💳 Resumo do Pagamento</h3>
                    <div class="summary-grid">
                        <div class="summary-item">
                            <span class="summary-label">ID do Pagamento:</span>
                            <span class="summary-value">#${escapeHtml(payment.id || 'N/A')}</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Data do Registro:</span>
                            <span class="summary-value">${formatDate(payment.data_registro)}</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Status:</span>
                            <span class="summary-value">
                                <span class="status-badge status-${payment.status}">${getStatusName(payment.status)}</span>
                            </span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Método de Pagamento:</span>
                            <span class="summary-value">${getPaymentMethodName(payment.metodo_pagamento)}</span>
                        </div>
                        ${payment.numero_referencia ? `
                        <div class="summary-item">
                            <span class="summary-label">Número de Referência:</span>
                            <span class="summary-value">${escapeHtml(payment.numero_referencia)}</span>
                        </div>
                        ` : ''}
                    </div>
                </div>

                <!-- Valores Financeiros -->
                <div class="financial-summary">
                    <h3>💰 Resumo Financeiro</h3>
                    <div class="financial-grid">
                        <div class="financial-item primary">
                            <div class="financial-label">Valor Total das Vendas</div>
                            <div class="financial-value">R$ ${formatCurrency(payment.valor_vendas_originais || payment.valor_total)}</div>
                        </div>
                        <div class="financial-item warning">
                            <div class="financial-label">Total Saldo Usado pelos Clientes</div>
                            <div class="financial-value">R$ ${formatCurrency(payment.total_saldo_usado || 0)}</div>
                        </div>
                        <div class="financial-item success">
                            <div class="financial-label">Comissão Paga ao Klube Cash</div>
                            <div class="financial-value">R$ ${formatCurrency(payment.valor_total)}</div>
                        </div>
                        <div class="financial-item info">
                            <div class="financial-label">Valor Líquido Cobrado</div>
                            <div class="financial-value">R$ ${formatCurrency((payment.valor_vendas_originais || payment.valor_total) - (payment.total_saldo_usado || 0))}</div>
                        </div>
                    </div>
                </div>
        `;
        
        // Seção de informações de aprovação/rejeição
        if (payment.status && payment.status !== 'pendente') {
            html += `
                <div class="approval-info">
                    <h3>${payment.status === 'aprovado' ? '✅ Informações de Aprovação' : '❌ Motivo da Rejeição'}</h3>
                    <div class="approval-details">
                        ${payment.data_aprovacao ? `
                        <div class="approval-item">
                            <span class="approval-label">Data:</span>
                            <span class="approval-value">${formatDate(payment.data_aprovacao)}</span>
                        </div>
                        ` : ''}
                        ${payment.observacao_admin ? `
                        <div class="approval-item">
                            <span class="approval-label">Observação do Administrador:</span>
                            <span class="approval-value">${escapeHtml(payment.observacao_admin)}</span>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }
        
        // Lista de transações incluídas no pagamento com informações de saldo
        html += `
            <div class="transactions-section">
                <h3>📋 Transações Incluídas (${transactions.length})</h3>
                ${transactions.length > 0 ? `
                <div class="transactions-table-container">
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Cliente</th>
                                <th>Data</th>
                                <th>Valor Venda</th>
                                <th>Saldo Usado</th>
                                <th>Valor Efetivo</th>
                                <th>Cashback</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${transactions.map(transaction => {
                                const saldoUsado = parseFloat(transaction.saldo_usado) || 0;
                                const valorEfetivo = parseFloat(transaction.valor_total) - saldoUsado;
                                return `
                                <tr>
                                    <td>
                                        <code>${escapeHtml(transaction.codigo_transacao || 'N/A')}</code>
                                    </td>
                                    <td>
                                        <div class="cliente-info">
                                            <strong>${escapeHtml(transaction.cliente_nome || 'N/A')}</strong>
                                            <small>${escapeHtml(transaction.cliente_email || '')}</small>
                                            ${saldoUsado > 0 ? '<span class="balance-indicator">💰</span>' : ''}
                                        </div>
                                    </td>
                                    <td>${formatDate(transaction.data_transacao)}</td>
                                    <td class="valor-original">R$ ${formatCurrency(transaction.valor_total)}</td>
                                    <td class="saldo-usado ${saldoUsado > 0 ? 'has-balance' : 'no-balance'}">
                                        ${saldoUsado > 0 ? 'R$ ' + formatCurrency(saldoUsado) : '-'}
                                    </td>
                                    <td class="valor-efetivo">R$ ${formatCurrency(valorEfetivo)}</td>
                                    <td class="cashback">R$ ${formatCurrency(transaction.valor_cliente)}</td>
                                </tr>
                            `}).join('')}
                        </tbody>
                    </table>
                </div>
                ` : '<div class="no-transactions"><p>Nenhuma transação associada a este pagamento.</p></div>'}
            </div>
        `;
        
        // Observações da loja sobre o pagamento
        if (payment.observacao) {
            html += `
                <div class="payment-notes">
                    <h3>📝 Suas Observações</h3>
                    <div class="notes-content">
                        <p>${escapeHtml(payment.observacao)}</p>
                    </div>
                </div>
            `;
        }
        
        // Ações disponíveis para pagamentos rejeitados
        if (payment.status === 'rejeitado') {
            html += `
                <div class="payment-actions">
                    <div class="action-info">
                        <p><strong>Seu pagamento foi rejeitado.</strong> Você pode realizar um novo pagamento com as transações pendentes.</p>
                    </div>
                    <a href="../../store/transacoes-pendentes" class="btn btn-primary">
                        <i class="icon">💳</i>
                        Realizar Novo Pagamento
                    </a>
                </div>
            `;
        }
        
        html += '</div>'; // Fechar payment-details-container
        
        paymentDetailsContent.innerHTML = html;
    }
    
    // Funções auxiliares para formatação e segurança
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return 'Data inválida';
            
            return date.toLocaleDateString('pt-BR') + ' às ' + 
                   date.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});
        } catch (error) {
            console.error('Erro ao formatar data:', error);
            return 'Erro na data';
        }
    }
    
    function formatCurrency(value) {
        const numValue = parseFloat(value) || 0;
        return numValue.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    
    function getPaymentMethodName(method) {
        const methods = {
            'pix': 'PIX',
            'pix_mercadopago': 'PIX Mercado Pago',
            'transferencia': 'Transferência Bancária',
            'ted': 'TED',
            'boleto': 'Boleto',
            'cartao': 'Cartão de Crédito',
            'outro': 'Outro'
        };
        return methods[method] || 'Método não especificado';
    }
    
    function getStatusName(status) {
        switch(status) {
            case 'aprovado': return 'Aprovado';
            case 'pendente': return 'Pendente';
            case 'rejeitado': return 'Rejeitado';
            default: return status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Status desconhecido';
        }
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});
</script>

<style>
/* Estilos existentes mantidos... */

/* Estilos adicionais para informações de saldo */
.stat-card-subtitle {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 5px;
}

.valor-detalhado {
    display: flex;
    flex-direction: column;
}

.valor-original, 
.valor-liquido {
    font-size: 0.8rem;
    color: #6c757d;
    font-style: italic;
}

.saldo-usado {
    color: #28a745;
    font-weight: 600;
}

.sem-saldo {
    color: #6c757d;
    font-style: italic;
}

.transacoes-info {
    display: flex;
    flex-direction: column;
}

.balance-used {
    color: #28a745 !important;
    font-weight: 600;
}

.balance-indicator {
    margin-left: 5px;
    font-size: 0.8rem;
}

.info-section {
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.info-section:last-child {
    border-bottom: none;
}

.info-section h4 {
    color: #333;
    margin-bottom: 15px;
    font-size: 1.1rem;
}

.info-section ul {
    list-style-type: none;
    padding-left: 0;
}

.info-section ol {
    padding-left: 0;
}

.info-section li {
    margin-bottom: 10px;
    padding-left: 20px;
    position: relative;
}

.info-section ul li::before {
    content: "•";
    color: #FF7A00;
    font-weight: bold;
    position: absolute;
    left: 0;
}

/* Estilos para seção colapsável */
.collapsible-card {
    transition: all 0.3s ease;
}

.collapsible-header {
    cursor: pointer;
    user-select: none;
    transition: background-color 0.3s ease;
    position: relative;
}

.collapsible-header:hover {
    background-color: #f8f9fa;
}

.collapsible-header .card-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}

.dropdown-icon {
    font-size: 14px;
    font-weight: bold;
    color: var(--primary-color);
    transition: transform 0.3s ease;
    margin-left: 10px;
}

.dropdown-icon.open {
    transform: rotate(180deg);
}

.collapsible-content {
    overflow: hidden;
    transition: all 0.4s ease;
    border-top: 1px solid #eee;
    margin-top: 0;
}

.collapsible-content.opening {
    animation: slideDown 0.4s ease-out;
}

.collapsible-content.closing {
    animation: slideUp 0.4s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        max-height: 0;
        padding-top: 0;
        padding-bottom: 0;
    }
    to {
        opacity: 1;
        max-height: 1000px;
        padding-top: 20px;
        padding-bottom: 20px;
    }
}

@keyframes slideUp {
    from {
        opacity: 1;
        max-height: 1000px;
        padding-top: 20px;
        padding-bottom: 20px;
    }
    to {
        opacity: 0;
        max-height: 0;
        padding-top: 0;
        padding-bottom: 0;
    }
}

/* Estilos especiais para quando está expandido */
.collapsible-card.expanded {
    border-left: 4px solid var(--primary-color);
}

.collapsible-card.expanded .collapsible-header {
    background-color: var(--primary-light);
}

/* Estilos para o modal de devolução */
.refund-info {
    margin-bottom: 20px;
}

.info-alert {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 20px;
    color: #856404;
}

.payment-info {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 15px;
    margin-top: 5px;
}

.payment-value {
    font-size: 1.2rem;
    font-weight: 600;
    color: #28a745;
    display: block;
}

.payment-info small {
    color: #6c757d;
    font-size: 0.8rem;
}

.form-help {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 5px;
    display: block;
}

.refund-consequences {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    border-radius: 6px;
    padding: 15px;
    margin-top: 20px;
}

.refund-consequences h4 {
    color: #721c24;
    margin-bottom: 10px;
}

.refund-consequences ul {
    margin: 0;
    padding-left: 20px;
}

.refund-consequences li {
    margin-bottom: 5px;
    color: #721c24;
}

.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.btn-warning {
    background-color: #ffc107;
    border-color: #ffc107;
    color: #212529;
}

.btn-warning:hover {
    background-color: #e0a800;
    border-color: #d39e00;
}

/* Sistema de notificações */
.notification-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    min-width: 300px;
    max-width: 500px;
    padding: 15px;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    animation: slideInRight 0.3s ease-out;
}

.notification-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-close {
    background: none;
    border: none;
    color: inherit;
    font-size: 1.2rem;
    cursor: pointer;
    margin-left: 10px;
    opacity: 0.7;
}

.notification-close:hover {
    opacity: 1;
}

.alert-success {
    color: #155724;
    background-color: #d4edda;
    border-color: #c3e6cb;
}

.alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
}

.alert-info {
    color: #0c5460;
    background-color: #d1ecf1;
    border-color: #bee5eb;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Ajustes para mobile */
@media (max-width: 768px) {
    .action-buttons {
        flex-direction: column;
        gap: 5px;
    }
    
    .btn-action {
        width: 100%;
        margin-bottom: 5px;
    }
    
    .notification-toast {
        left: 10px;
        right: 10px;
        min-width: auto;
    }
    
    .collapsible-header .card-title {
        font-size: 16px;
    }
    
    .dropdown-icon {
        font-size: 12px;
    }
    
    .info-section h4 {
        font-size: 1rem;
    }
}
</style>
</body>
</html>