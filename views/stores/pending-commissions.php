<?php
// views/stores/pending-commissions.php
$activeMenu = 'pending-commissions';

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/TransactionController.php';
require_once '../../models/CashbackBalance.php';

session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'loja') {
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

$userId = $_SESSION['user_id'];

$db = Database::getConnection();
$storeQuery = $db->prepare("SELECT id, nome_fantasia FROM lojas WHERE usuario_id = :usuario_id");
$storeQuery->bindParam(':usuario_id', $userId);
$storeQuery->execute();

if ($storeQuery->rowCount() == 0) {
    header('Location: ' . LOGIN_URL . '?error=' . urlencode('Sua conta não está associada a nenhuma loja. Entre em contato com o suporte.'));
    exit;
}

$store = $storeQuery->fetch(PDO::FETCH_ASSOC);
$storeId = $store['id'];
$storeName = $store['nome_fantasia'];

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$filters = [];

if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
    $filters['data_inicio'] = $_GET['data_inicio'];
}
if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
    $filters['data_fim'] = $_GET['data_fim'];
}
if (isset($_GET['valor_min']) && !empty($_GET['valor_min'])) {
    $filters['valor_min'] = floatval($_GET['valor_min']);
}
if (isset($_GET['valor_max']) && !empty($_GET['valor_max'])) {
    $filters['valor_max'] = floatval($_GET['valor_max']);
}

$result = TransactionController::getPendingTransactionsWithBalance($storeId, $filters, $page);

// NOVO: Buscar pagamentos PIX pendentes (ficam visíveis até aprovação)
$paymentsQuery = $db->prepare("
    SELECT p.*, COUNT(DISTINCT t.id) as transactions_count
    FROM pagamentos_comissao p
    LEFT JOIN transacoes_cashback t ON FIND_IN_SET(t.id, REPLACE(REPLACE(JSON_EXTRACT(p.transacoes_incluidas, '$.transaction_ids'), '[', ''), ']', ''))
    WHERE p.loja_id = ? 
    AND p.status IN ('pendente', 'pix_aguardando', 'pix_expirado')
    GROUP BY p.id
    ORDER BY p.data_registro DESC
");
$paymentsQuery->execute([$storeId]);
$pendingPayments = $paymentsQuery->fetchAll(PDO::FETCH_ASSOC);

$totalTransacoes = 0;
$totalValorVendas = 0;
$totalValorComissoes = 0;
$totalSaldoUsado = 0;

if ($result['status'] && isset($result['data']['totais'])) {
    $totalTransacoes = $result['data']['totais']['total_transacoes'];
    $totalValorVendas = $result['data']['totais']['total_valor_vendas_originais'];
    $totalSaldoUsado = $result['data']['totais']['total_saldo_usado'];
    
    $valorEfetivo = $totalValorVendas - $totalSaldoUsado;
    $totalValorComissoes = $valorEfetivo * 0.10;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <title>Comissões Pendentes - Klube Cash</title>
    
    <link rel="stylesheet" href="../../assets/css/views/stores/pending-commissions.css">
    <link rel="stylesheet" href="../../assets/css/openpix-styles.css">
</head>
<body>
    <?php include_once '../components/sidebar-store.php'; ?>
    
    <div class="main-content" id="mainContent">
        <div class="dashboard-wrapper">
            <div class="dashboard-header">
                <h1>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 6v6l4 2"/>
                    </svg>
                    Comissões Pendentes
                </h1>
                <p class="subtitle">Gerenciar comissões pendentes de pagamento para <?php echo htmlspecialchars($storeName); ?> - Transações ficam visíveis até aprovação</p>
            </div>
            
            <!-- NOVO: Alerta sobre transações pendentes -->
            <?php if (!empty($pendingPayments)): ?>
            <div class="alert-section">
                <div class="alert alert-warning">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                    <div>
                        <strong>💡 Atenção:</strong> Você tem <?php echo count($pendingPayments); ?> pagamento(s) PIX aguardando confirmação. 
                        <strong>Transações permanecem visíveis até aprovação.</strong> Clique em "Pagar PIX" para gerar um novo código.
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- NOVO: Seção de Pagamentos PIX Pendentes -->
            <?php if (!empty($pendingPayments)): ?>
            <div class="card payments-pending-section">
                <div class="card-header">
                    <div class="card-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                            <line x1="1" y1="10" x2="23" y2="10"/>
                        </svg>
                        Pagamentos PIX Aguardando Confirmação
                    </div>
                    <span class="payments-count"><?php echo count($pendingPayments); ?> pagamento(s)</span>
                </div>
                
                <div class="payments-grid">
                    <?php foreach ($pendingPayments as $payment): ?>
                    <div class="payment-card status-<?php echo $payment['status']; ?>">
                        <div class="payment-header">
                            <div class="payment-info">
                                <h3>Pagamento #<?php echo $payment['id']; ?></h3>
                                <span class="payment-status <?php echo $payment['status']; ?>">
                                    <?php
                                    switch($payment['status']) {
                                        case 'pix_aguardando': echo '⏳ PIX Aguardando Pagamento'; break;
                                        case 'pix_expirado': echo '⏰ PIX Expirado'; break;
                                        case 'pendente': echo '📋 Aguardando Geração PIX'; break;
                                        default: echo ucfirst($payment['status']);
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="payment-amount">
                                <span class="amount-label">Valor</span>
                                <span class="amount-value">R$ <?php echo number_format($payment['valor_total'], 2, ',', '.'); ?></span>
                            </div>
                        </div>
                        
                        <div class="payment-details">
                            <div class="detail-row">
                                <span class="detail-label">Transações incluídas:</span>
                                <span class="detail-value"><?php echo $payment['transactions_count']; ?> transação(ões)</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Criado em:</span>
                                <span class="detail-value"><?php echo date('d/m/Y H:i', strtotime($payment['data_registro'])); ?></span>
                            </div>
                            <?php if (!empty($payment['mp_payment_id'])): ?>
                            <div class="detail-row">
                                <span class="detail-label">ID Mercado Pago:</span>
                                <span class="detail-value mp-id"><?php echo $payment['mp_payment_id']; ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="payment-actions">
                            <!-- BOTÃO PRINCIPAL: Sempre disponível para gerar novo PIX -->
                            <a href="<?php echo STORE_PAYMENT_PIX_URL; ?>?payment_id=<?php echo $payment['id']; ?>" 
                               class="btn btn-primary btn-pix">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                                    <line x1="1" y1="10" x2="23" y2="10"/>
                                </svg>
                                <?php 
                                if ($payment['status'] === 'pix_expirado') {
                                    echo 'Gerar Novo PIX';
                                } elseif ($payment['status'] === 'pix_aguardando') {
                                    echo 'Pagar PIX / Gerar Novo';
                                } else {
                                    echo 'Gerar PIX';
                                }
                                ?>
                            </a>
                            
                            <!-- Botão de verificar status (se já tem PIX) -->
                            <?php if (!empty($payment['mp_payment_id']) && $payment['status'] === 'pix_aguardando'): ?>
                            <button onclick="checkPaymentStatus(<?php echo $payment['id']; ?>, '<?php echo $payment['mp_payment_id']; ?>')" 
                                    class="btn btn-outline btn-check">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="23 4 23 10 17 10"/>
                                    <polyline points="1 20 1 14 7 14"/>
                                    <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"/>
                                </svg>
                                Verificar Status
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="payments-info">
                    <div class="info-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 16v-4"/>
                            <path d="M12 8h.01"/>
                        </svg>
                        <span><strong>Importante:</strong> Cada clique em "Pagar PIX" gera um novo código de pagamento. Pagamentos ficam visíveis até confirmação.</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-card-title">Transações Pendentes</div>
                    <div class="stat-card-value"><?php echo number_format($totalTransacoes); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-title">Valor Total de Vendas</div>
                    <div class="stat-card-value">R$ <?php echo number_format($totalValorVendas, 2, ',', '.'); ?></div>
                    <div class="stat-card-subtitle">Valor original das vendas</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-title">Total Saldo Usado</div>
                    <div class="stat-card-value">R$ <?php echo number_format($totalSaldoUsado, 2, ',', '.'); ?></div>
                    <div class="stat-card-subtitle">Desconto aplicado pelos clientes</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-title">Valor Total de Comissões</div>
                    <div class="stat-card-value">R$ <?php echo number_format($totalValorComissoes, 2, ',', '.'); ?></div>
                    <div class="stat-card-subtitle">Valor a pagar ao Klube Cash (10%)</div>
                </div>
            </div>
            
            <div class="card filter-container">
                <div class="card-header">
                    <div class="card-title">Filtros</div>
                </div>
                <div class="filter-form">
                    <form method="GET" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="data_inicio">Data Início</label>
                                <input type="date" id="data_inicio" name="data_inicio" value="<?php echo isset($_GET['data_inicio']) ? htmlspecialchars($_GET['data_inicio']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="data_fim">Data Fim</label>
                                <input type="date" id="data_fim" name="data_fim" value="<?php echo isset($_GET['data_fim']) ? htmlspecialchars($_GET['data_fim']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="valor_min">Valor Mínimo</label>
                                <input type="number" id="valor_min" name="valor_min" step="0.01" min="0" value="<?php echo isset($_GET['valor_min']) ? htmlspecialchars($_GET['valor_min']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="valor_max">Valor Máximo</label>
                                <input type="number" id="valor_max" name="valor_max" step="0.01" min="0" value="<?php echo isset($_GET['valor_max']) ? htmlspecialchars($_GET['valor_max']) : ''; ?>">
                            </div>
                            
                            <div class="form-buttons">
                                <button type="submit" class="btn btn-primary">Filtrar</button>
                                <a href="<?php echo STORE_PENDING_TRANSACTIONS_URL; ?>" class="btn btn-secondary">Limpar</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card transactions-container">
                <div class="card-header">
                    <div class="card-title">Transações Aguardando Pagamento de Comissão</div>
                    <?php if ($totalTransacoes > 0): ?>
                    <div style="display: flex; gap: 1rem;">
                        <button id="payPixBtn" class="btn btn-success" disabled>Pagar via PIX</button>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($result['status'] && count($result['data']['transacoes']) > 0): ?>
                    <form id="paymentForm" method="POST" action="<?php echo STORE_PAYMENT_URL; ?>">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th width="40">
                                            <input type="checkbox" id="selectAll">
                                        </th>
                                        <th>Código</th>
                                        <th>Cliente</th>
                                        <th>Data</th>
                                        <th>Valor Original</th>
                                        <th>Saldo Usado</th>
                                        <th>Valor Cobrado</th>
                                        <th>Comissão Total</th>
                                        <th>Cashback Cliente</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($result['data']['transacoes'])): ?>
                                        <tr>
                                            <td colspan="9" style="text-align: center;">Nenhuma transação encontrada</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($result['data']['transacoes'] as $transaction): ?>
                                            <?php 
                                            $valorOriginal = floatval($transaction['valor_total']);
                                            $saldoUsado = floatval($transaction['saldo_usado'] ?? 0);
                                            $valorCobrado = $valorOriginal - $saldoUsado;
                                            
                                            $comissaoTotal = $valorCobrado * 0.10;
                                            $cashbackCliente = $valorCobrado * 0.05;
                                            ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="transacoes[]" value="<?php echo $transaction['id']; ?>" 
                                                        class="transaction-checkbox" 
                                                        data-value="<?php echo number_format($comissaoTotal, 2, '.', ''); ?>">
                                                </td>
                                                <td><?php echo htmlspecialchars($transaction['codigo_transacao'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($transaction['cliente_nome']); ?>
                                                    <?php if ($saldoUsado > 0): ?>
                                                        <span class="balance-used-badge" title="Cliente usou saldo">💰</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($transaction['data_transacao'])); ?></td>
                                                <td>R$ <?php echo number_format($valorOriginal, 2, ',', '.'); ?></td>
                                                <td>
                                                    <?php if ($saldoUsado > 0): ?>
                                                        <span class="saldo-usado">R$ <?php echo number_format($saldoUsado, 2, ',', '.'); ?></span>
                                                    <?php else: ?>
                                                        <span class="sem-saldo">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong>R$ <?php echo number_format($valorCobrado, 2, ',', '.'); ?></strong>
                                                    <?php if ($valorCobrado < $valorOriginal): ?>
                                                        <small class="desconto">(com desconto)</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><strong>R$ <?php echo number_format($comissaoTotal, 2, ',', '.'); ?></strong></td>
                                                <td>R$ <?php echo number_format($cashbackCliente, 2, ',', '.'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <input type="hidden" name="loja_id" value="<?php echo $storeId; ?>">
                        <input type="hidden" name="action" value="payment_form">
                        
                        <div class="payment-summary" id="paymentSummary">
                            <div class="summary-content">
                                <div class="summary-item">
                                    <span class="label">Transações selecionadas:</span>
                                    <span class="value" id="selectedCount">0</span>
                                </div>
                                <div class="summary-item">
                                    <span class="label">Valor total das vendas:</span>
                                    <span class="value" id="totalSalesValue">R$ 0,00</span>
                                </div>
                                <div class="summary-item">
                                    <span class="label">Total saldo usado:</span>
                                    <span class="value" id="totalBalanceUsed">R$ 0,00</span>
                                </div>
                                <div class="summary-item">
                                    <span class="label">Valor total a pagar:</span>
                                    <span class="value" id="totalCommissionValue">R$ 0,00</span>
                                </div>
                            </div>
                        </div>
                    </form>
                    
                    <?php if ($result['data']['paginacao']['total_paginas'] > 1): ?>
                        <div class="pagination">
                            <div class="pagination-info">
                                Página <?php echo $result['data']['paginacao']['pagina_atual']; ?> de <?php echo $result['data']['paginacao']['total_paginas']; ?>
                            </div>
                            <div class="pagination-links">
                                <?php if ($result['data']['paginacao']['pagina_atual'] > 1): ?>
                                    <a href="?page=1<?php echo isset($_GET['data_inicio']) ? '&data_inicio=' . urlencode($_GET['data_inicio']) : ''; ?><?php echo isset($_GET['data_fim']) ? '&data_fim=' . urlencode($_GET['data_fim']) : ''; ?><?php echo isset($_GET['valor_min']) ? '&valor_min=' . urlencode($_GET['valor_min']) : ''; ?><?php echo isset($_GET['valor_max']) ? '&valor_max=' . urlencode($_GET['valor_max']) : ''; ?>" class="page-link">Primeira</a>
                                    <a href="?page=<?php echo $result['data']['paginacao']['pagina_atual'] - 1; ?><?php echo isset($_GET['data_inicio']) ? '&data_inicio=' . urlencode($_GET['data_inicio']) : ''; ?><?php echo isset($_GET['data_fim']) ? '&data_fim=' . urlencode($_GET['data_fim']) : ''; ?><?php echo isset($_GET['valor_min']) ? '&valor_min=' . urlencode($_GET['valor_min']) : ''; ?><?php echo isset($_GET['valor_max']) ? '&valor_max=' . urlencode($_GET['valor_max']) : ''; ?>" class="page-link">Anterior</a>
                                <?php endif; ?>
                                
                                <?php if ($result['data']['paginacao']['pagina_atual'] < $result['data']['paginacao']['total_paginas']): ?>
                                    <a href="?page=<?php echo $result['data']['paginacao']['pagina_atual'] + 1; ?><?php echo isset($_GET['data_inicio']) ? '&data_inicio=' . urlencode($_GET['data_inicio']) : ''; ?><?php echo isset($_GET['data_fim']) ? '&data_fim=' . urlencode($_GET['data_fim']) : ''; ?><?php echo isset($_GET['valor_min']) ? '&valor_min=' . urlencode($_GET['valor_min']) : ''; ?><?php echo isset($_GET['valor_max']) ? '&valor_max=' . urlencode($_GET['valor_max']) : ''; ?>" class="page-link">Próxima</a>
                                    <a href="?page=<?php echo $result['data']['paginacao']['total_paginas']; ?><?php echo isset($_GET['data_inicio']) ? '&data_inicio=' . urlencode($_GET['data_inicio']) : ''; ?><?php echo isset($_GET['data_fim']) ? '&data_fim=' . urlencode($_GET['data_fim']) : ''; ?><?php echo isset($_GET['valor_min']) ? '&valor_min=' . urlencode($_GET['valor_min']) : ''; ?><?php echo isset($_GET['valor_max']) ? '&valor_max=' . urlencode($_GET['valor_max']) : ''; ?>" class="page-link">Última</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                        </div>
                        <h3>Nenhuma comissão pendente</h3>
                        <p>Não existem transações pendentes de pagamento no momento.</p>
                        <a href="<?php echo STORE_REGISTER_TRANSACTION_URL; ?>" class="btn btn-primary">Registrar Nova Venda</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card info-card collapsible-card">
                <div class="card-header collapsible-header" onclick="toggleInfoSection()">
                    <div class="card-title">
                        <span>📋 Informações sobre Saldo e Comissões</span>
                        <span class="dropdown-icon" id="infoDropdownIcon">▼</span>
                    </div>
                </div>
                <div class="collapsible-content" id="infoSectionContent" style="display: none;">
                    <div class="info-content">
                        <div class="info-section">
                            <h4>🔄 Como funciona o novo sistema de pagamento PIX:</h4>
                            <ul>
                                <li><strong>Transações ficam visíveis:</strong> Suas transações pendentes não somem até serem aprovadas</li>
                                <li><strong>Novo PIX a cada clique:</strong> Cada vez que você clica em "Pagar PIX", um novo código é gerado</li>
                                <li><strong>Múltiplas tentativas:</strong> Você pode tentar pagar quantas vezes quiser</li>
                                <li><strong>Aprovação automática:</strong> Assim que o PIX for confirmado, o cashback é liberado instantaneamente</li>
                            </ul>
                        </div>
                        
                        <div class="info-section">
                            <h4>📊 Como são calculadas as comissões:</h4>
                            <ul>
                                <li>A comissão é de <strong>10%</strong> calculada apenas sobre o valor efetivamente cobrado do cliente</li>
                                <li>Se o cliente usou saldo, o valor é descontado antes do cálculo da comissão</li>
                                <li>Exemplo: Venda de R$ 100,00 - Saldo usado R$ 20,00 = Comissão sobre R$ 80,00 (R$ 8,00)</li>
                                <li><strong>Sua loja não recebe cashback</strong> - você apenas paga a comissão</li>
                            </ul>
                        </div>
                        
                        <div class="info-section">
                            <h4>💰 Sobre o uso de saldo pelo cliente:</h4>
                            <ul>
                                <li>Clientes podem usar o cashback recebido para desconto em novas compras <strong>na sua loja</strong></li>
                                <li>O saldo usado é identificado pelo ícone 💰 ao lado do nome do cliente</li>
                                <li>O cliente ainda recebe cashback normal sobre o valor que ele efetivamente pagou</li>
                                <li>Você paga comissão apenas sobre o valor que efetivamente recebeu</li>
                            </ul>
                        </div>
                        
                        <div class="info-section">
                            <h4>🔔 Distribuição dos 10% de comissão:</h4>
                            <ul>
                                <li><strong>5% para o cliente:</strong> Vira cashback disponível para usar na sua loja</li>
                                <li><strong>5% para o Klube Cash:</strong> Nossa receita pela plataforma</li>
                                <li><strong>0% para sua loja:</strong> Você não recebe cashback, apenas oferece o benefício</li>
                            </ul>
                        </div>
                        
                        <div class="info-section">
                            <h4>🔄 Processo de pagamento melhorado:</h4>
                            <ul>
                                <li>Selecione as transações que deseja quitar</li>
                                <li>Clique em "Pagar via PIX" para gerar um código de pagamento</li>
                                <li><strong>Pode gerar novos PIX quantas vezes precisar</strong></li>
                                <li>Após o pagamento ser confirmado, o cashback é liberado automaticamente</li>
                            </ul>
                        </div>
                        
                        <div class="info-section">
                            <h4>ℹ️ Dicas Importantes:</h4>
                            <ul>
                                <li>Realize pagamentos regularmente para manter o fluxo de cashback dos clientes</li>
                                <li>Monitore vendas com uso de saldo - indicam clientes fidelizados</li>
                                <li>O valor da economia gerada aos clientes também beneficia sua loja com mais vendas</li>
                                <li>Se um PIX expirar, simplesmente clique novamente para gerar um novo</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../../assets/js/openpix-integration.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAllCheckbox = document.getElementById('selectAll');
        const transactionCheckboxes = document.querySelectorAll('.transaction-checkbox');
        const payPixBtn = document.getElementById('payPixBtn');
        const paymentForm = document.getElementById('paymentForm');
        const paymentSummary = document.getElementById('paymentSummary');
        
        function formatCurrency(value) {
            return value.toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL',
                minimumFractionDigits: 2
            });
        }
        
        function updatePaymentSummary() {
            const selectedCheckboxes = document.querySelectorAll('.transaction-checkbox:checked');
            const selectedCount = selectedCheckboxes.length;
            let totalCommission = 0;
            let totalSalesValue = 0;
            let totalBalanceUsed = 0;
            
            selectedCheckboxes.forEach(checkbox => {
                const commission = parseFloat(checkbox.getAttribute('data-value'));
                totalCommission += commission;
                
                const row = checkbox.closest('tr');
                const cells = row.querySelectorAll('td');
                
                const originalValueText = cells[4].textContent.replace('R$ ', '').replace(/\./g, '').replace(',', '.');
                const originalValue = parseFloat(originalValueText);
                
                const balanceUsedElement = cells[5].querySelector('.saldo-usado');
                const balanceUsed = balanceUsedElement ? 
                    parseFloat(balanceUsedElement.textContent.replace('R$ ', '').replace(/\./g, '').replace(',', '.')) : 0;
                
                totalSalesValue += originalValue;
                totalBalanceUsed += balanceUsed;
            });
            
            document.getElementById('selectedCount').textContent = selectedCount;
            document.getElementById('totalSalesValue').textContent = formatCurrency(totalSalesValue);
            document.getElementById('totalBalanceUsed').textContent = formatCurrency(totalBalanceUsed);
            document.getElementById('totalCommissionValue').textContent = formatCurrency(totalCommission);
            
            if (payPixBtn) payPixBtn.disabled = selectedCount === 0;
            
            if (selectedCount > 0) {
                paymentSummary.style.display = 'block';
            } else {
                paymentSummary.style.display = 'none';
            }
        }
        
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                transactionCheckboxes.forEach(checkbox => {
                    checkbox.checked = selectAllCheckbox.checked;
                });
                updatePaymentSummary();
            });
        }
        
        transactionCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allChecked = Array.from(transactionCheckboxes).every(cb => cb.checked);
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = allChecked;
                }
                updatePaymentSummary();
            });
        });
        
        if (payPixBtn) {
            payPixBtn.addEventListener('click', function() {
                const selected = document.querySelectorAll('.transaction-checkbox:checked');
                if (selected.length > 0) {
                    createPixPayment();
                }
            });
        }

        // NOVO: Função para criar pagamento PIX (gera novo a cada clique)
        async function createPixPayment() {
            const selectedCheckboxes = document.querySelectorAll('.transaction-checkbox:checked');
            let totalCommission = 0;
            
            selectedCheckboxes.forEach(checkbox => {
                totalCommission += parseFloat(checkbox.getAttribute('data-value'));
            });

            if (totalCommission <= 0) {
                alert('Selecione pelo menos uma transação');
                return;
            }

            // Mostrar loading
            payPixBtn.disabled = true;
            payPixBtn.innerHTML = '<span class="loading-spinner"></span> Criando PIX...';

            const formData = new FormData(paymentForm);
            formData.append('metodo_pagamento', 'pix_mercadopago');
            formData.append('valor_total', totalCommission.toFixed(2));
            formData.append('force_new_payment', 'true'); // NOVO: Força criar novo pagamento

            try {
                const response = await fetch('../../api/store-payment.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin' 
                });

                const result = await response.json();
                
                if (result.status) {
                    // Redirecionar para página de PIX
                    window.location.href = `../../store/pagamento-pix?payment_id=${result.data.payment_id}`;
                } else {
                    alert('Erro: ' + result.message);
                    // Restaurar botão
                    payPixBtn.disabled = false;
                    payPixBtn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                        <line x1="1" y1="10" x2="23" y2="10"/>
                    </svg> Pagar via PIX`;
                }
            } catch (error) {
                console.error('Erro na requisição:', error);
                alert('Erro de conexão: ' + error.message);
                // Restaurar botão
                payPixBtn.disabled = false;
                payPixBtn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                    <line x1="1" y1="10" x2="23" y2="10"/>
                </svg> Pagar via PIX`;
            }
        }

        // NOVO: Função para verificar status de pagamento PIX
        async function checkPaymentStatus(paymentId, mpPaymentId) {
            try {
                const response = await fetch(`../../api/payment-status.php?payment_id=${paymentId}&mp_payment_id=${mpPaymentId}`);
                const result = await response.json();
                
                if (result.status && result.data.status === 'approved') {
                    alert('✅ Pagamento PIX confirmado! A página será recarregada para mostrar o status atualizado.');
                    window.location.reload();
                } else if (result.data && result.data.status === 'pending') {
                    alert('⏳ Pagamento PIX ainda está pendente. Continue aguardando ou tente gerar um novo PIX.');
                } else if (result.data && result.data.status === 'rejected') {
                    alert('❌ Pagamento PIX foi rejeitado. Gere um novo PIX para tentar novamente.');
                } else {
                    alert('ℹ️ Status atual: ' + (result.data ? result.data.status : 'Desconhecido'));
                }
            } catch (error) {
                console.error('Erro ao verificar status:', error);
                alert('Erro ao verificar status do pagamento. Tente novamente.');
            }
        }

        // Tornar função global para uso nos botões
        window.checkPaymentStatus = checkPaymentStatus;
        
        updatePaymentSummary();
        
        const savedState = localStorage.getItem('pendingCommissionsInfoOpen');
        const content = document.getElementById('infoSectionContent');
        const icon = document.getElementById('infoDropdownIcon');
        const card = content ? content.closest('.collapsible-card') : null;
        
        if (savedState === 'true' && content && icon && card) {
            content.style.display = 'block';
            icon.classList.add('open');
            card.classList.add('expanded');
        }

        // NOVO: Auto-refresh para verificar status dos pagamentos PIX
        <?php if (!empty($pendingPayments)): ?>
        setInterval(() => {
            // Verificar silenciosamente se algum pagamento foi aprovado
            fetch(`../../api/payment-status.php?check_all=1&store_id=<?php echo $storeId; ?>`)
                .then(response => response.json())
                .then(data => {
                    if (data.has_updates) {
                        // Mostrar notificação e recarregar página
                        alert('✅ Novo pagamento aprovado! A página será recarregada.');
                        window.location.reload();
                    }
                })
                .catch(error => console.log('Status check error:', error));
        }, <?php echo defined('PIX_AUTO_REFRESH_SECONDS') ? PIX_AUTO_REFRESH_SECONDS * 1000 : 15000; ?>);
        <?php endif; ?>
    });

    function toggleInfoSection() {
        const content = document.getElementById('infoSectionContent');
        const icon = document.getElementById('infoDropdownIcon');
        const card = content.closest('.collapsible-card');
        
        if (content.style.display === 'none' || content.style.display === '') {
            content.style.display = 'block';
            content.classList.add('opening');
            content.classList.remove('closing');
            icon.classList.add('open');
            card.classList.add('expanded');
            
            setTimeout(() => {
                content.classList.remove('opening');
            }, 400);
            
            localStorage.setItem('pendingCommissionsInfoOpen', 'true');
        } else {
            content.classList.add('closing');
            content.classList.remove('opening');
            icon.classList.remove('open');
            card.classList.remove('expanded');
            
            setTimeout(() => {
                content.style.display = 'none';
                content.classList.remove('closing');
            }, 400);
            
            localStorage.setItem('pendingCommissionsInfoOpen', 'false');
        }
    }
    </script>
    
    <style>
        /* NOVOS ESTILOS PARA SEÇÃO DE PAGAMENTOS PIX */
        .alert-section {
            margin-bottom: 1.5rem;
        }

        .alert {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 1rem 1.25rem;
            border-radius: 8px;
            border-left: 4px solid;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .alert-warning {
            background-color: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }

        .alert svg {
            color: #ffc107;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .payments-pending-section {
            margin-bottom: 2rem;
        }

        .payments-pending-section .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .payments-count {
            background: var(--warning-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .payments-grid {
            display: grid;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .payment-card {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1.5rem;
            background: white;
            transition: all 0.3s ease;
        }

        .payment-card.status-pix_aguardando {
            border-color: #ffc107;
            background: linear-gradient(135deg, #fff9e6 0%, #ffffff 100%);
        }

        .payment-card.status-pix_expirado {
            border-color: #dc3545;
            background: linear-gradient(135deg, #ffeaea 0%, #ffffff 100%);
        }

        .payment-card.status-pendente {
            border-color: #6c757d;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        }

        .payment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .payment-info h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
        }

        .payment-status {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .payment-status.pix_aguardando {
            background: #fff3cd;
            color: #856404;
        }

        .payment-status.pix_expirado {
            background: #f8d7da;
            color: #721c24;
        }

        .payment-status.pendente {
            background: #e2e3e5;
            color: #383d41;
        }

        .payment-amount {
            text-align: right;
        }

        .amount-label {
            display: block;
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }

        .amount-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .payment-details {
            margin-bottom: 1.5rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .detail-label {
            color: #6c757d;
        }

        .detail-value {
            font-weight: 500;
            color: #333;
        }

        .mp-id {
            font-family: monospace;
            font-size: 0.8rem;
        }

        .payment-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .btn-pix {
            flex: 1;
            min-width: 150px;
        }

        .btn-check {
            flex: 0 0 auto;
        }

        .payments-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid var(--info-color);
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .info-item svg {
            color: var(--info-color);
            flex-shrink: 0;
            margin-top: 2px;
        }

        /* Loading spinner */
        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Existing styles continuam aqui... */
        .balance-used-badge {
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
        
        .desconto {
            color: #28a745;
            font-size: 0.8rem;
            display: block;
        }
        
        .info-section {
            margin-bottom: 20px;
        }
        
        .info-section h4 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .info-section ul {
            list-style-type: none;
            padding-left: 0;
        }
        
        .info-section li {
            margin-bottom: 8px;
            padding-left: 20px;
            position: relative;
        }
        
        .info-section li::before {
            content: "•";
            color: #FF7A00;
            font-weight: bold;
            position: absolute;
            left: 0;
        }
        
        .stat-card-subtitle {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 5px;
        }

        .collapsible-card {
            transition: all 0.3s ease;
        }

        .collapsible-header {
            cursor: pointer;
            user-select: none;
            transition: background-color 0.3s ease;
            position: relative;
            padding: 1.25rem 1.5rem;
            border-bottom: none;
        }

        .collapsible-header:hover {
            background-color: #f8f9fa;
        }

        .collapsible-header .card-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--secondary-color);
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

        .collapsible-card.expanded {
            border-left: 4px solid var(--primary-color);
        }

        .collapsible-card.expanded .collapsible-header {
            background-color: var(--primary-light);
        }

        .info-content {
            padding: 1.5rem;
            color: var(--medium-gray);
        }

        .info-section {
            margin-bottom: 25px;
        }

        .info-section:last-child {
            margin-bottom: 0;
        }

        .info-section h4 {
            color: #333;
            margin-bottom: 12px;
            font-size: 1rem;
            font-weight: 600;
        }

        .info-section ul {
            list-style-type: none;
            padding-left: 0;
            margin: 0;
        }

        .info-section li {
            margin-bottom: 10px;
            padding-left: 20px;
            position: relative;
            line-height: 1.5;
        }

        .info-section li::before {
            content: "•";
            color: var(--primary-color);
            font-weight: bold;
            position: absolute;
            left: 0;
            top: 0;
        }

        @media (max-width: 768px) {
            .payment-actions {
                flex-direction: column;
            }
            
            .btn-pix, .btn-check {
                flex: 1;
                min-width: auto;
            }
            
            .payment-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .payment-amount {
                text-align: left;
            }
            
            .collapsible-header {
                padding: 1rem;
            }
            
            .collapsible-header .card-title {
                font-size: 1rem;
            }
            
            .dropdown-icon {
                font-size: 12px;
            }
            
            .info-content {
                padding: 1rem;
            }
            
            .info-section h4 {
                font-size: 0.9rem;
            }
            
            .info-section li {
                font-size: 0.9rem;
                margin-bottom: 8px;
            }
        }

        .info-section li strong {
            color: var(--primary-color);
            font-weight: 600;
        }
    </style>
</body>
</html>