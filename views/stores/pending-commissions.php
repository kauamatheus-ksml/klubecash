<?php
// views/stores/pending-commissions.php
// Definir o menu ativo na sidebar
$activeMenu = 'pending-commissions';

// Incluir arquivos necessários
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/TransactionController.php';

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

// Obter transações pendentes
$result = TransactionController::getPendingTransactions($storeId, $filters, $page);

// Calcular totais
$totalTransacoes = 0;
$totalValorVendas = 0;
$totalValorComissoes = 0;

if ($result['status'] && isset($result['data']['totais'])) {
    $totalTransacoes = $result['data']['totais']['total_transacoes'];
    $totalValorVendas = $result['data']['totais']['total_valor_compras'];
    $totalValorComissoes = $result['data']['totais']['total_valor_comissoes'];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <title>Comissões Pendentes - Klube Cash</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/views/stores/pending-commissions.css">
</head>
<body>
    <?php include_once '../components/sidebar-store.php'; ?>
    
    <!-- Conteúdo Principal -->
    <div class="main-content" id="mainContent">
        <div class="dashboard-wrapper">
            <!-- Cabeçalho -->
            <div class="dashboard-header">
                <h1>Comissões Pendentes</h1>
                <p class="subtitle">Gerenciar comissões pendentes de pagamento para <?php echo htmlspecialchars($storeName); ?></p>
            </div>
            
            <!-- Cards de estatísticas -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-card-title">Transações Pendentes</div>
                    <div class="stat-card-value"><?php echo number_format($totalTransacoes); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-title">Valor Total de Vendas</div>
                    <div class="stat-card-value">R$ <?php echo number_format($totalValorVendas, 2, ',', '.'); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-title">Valor Total de Comissões</div>
                    <div class="stat-card-value">R$ <?php echo number_format($totalValorComissoes, 2, ',', '.'); ?></div>
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
            
            <!-- Listagem de Transações Pendentes -->
            <div class="card transactions-container">
                <div class="card-header">
                    <div class="card-title">Transações Pendentes de Pagamento</div>
                    <?php if ($totalTransacoes > 0): ?>
                    <button id="paySelectedBtn" class="btn btn-primary" disabled>Pagar Selecionadas</button>
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
                                        <th>Valor Venda</th>
                                        <th>Comissão</th>
                                        <th>Cashback Cliente</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($result['data']['transacoes'] as $transaction): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="transacoes[]" value="<?php echo $transaction['id']; ?>" 
                                                       class="transaction-checkbox" 
                                                       data-value="<?php echo $transaction['valor_cashback']; ?>">
                                            </td>
                                            <td><?php echo htmlspecialchars($transaction['codigo_transacao'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['cliente_nome']); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($transaction['data_transacao'])); ?></td>
                                            <td>R$ <?php echo number_format($transaction['valor_total'], 2, ',', '.'); ?></td>
                                            <td>R$ <?php echo number_format($transaction['valor_cashback'], 2, ',', '.'); ?></td>
                                            <td>R$ <?php echo number_format($transaction['valor_cliente'], 2, ',', '.'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
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
                                    <span class="label">Valor total a pagar:</span>
                                    <span class="value" id="totalValue">R$ 0,00</span>
                                </div>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Paginação -->
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
            
            <!-- Informações Adicionais -->
            <div class="card info-card">
                <div class="card-header">
                    <div class="card-title">Informações Importantes</div>
                </div>
                <div class="info-content">
                    <p>As comissões pendentes representam os valores a serem pagos pela sua loja ao Klube Cash. Após o pagamento e aprovação, o cashback será liberado para os clientes.</p>
                    <p>Para realizar o pagamento, selecione as transações desejadas e clique no botão "Pagar Selecionadas". Você será direcionado para a página de pagamento, onde poderá escolher o método e enviar o comprovante.</p>
                    <p>Pagamentos são processados em até 24 horas úteis após o envio do comprovante.</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAllCheckbox = document.getElementById('selectAll');
            const transactionCheckboxes = document.querySelectorAll('.transaction-checkbox');
            const paySelectedBtn = document.getElementById('paySelectedBtn');
            const selectedCountElement = document.getElementById('selectedCount');
            const totalValueElement = document.getElementById('totalValue');
            const paymentForm = document.getElementById('paymentForm');
            const paymentSummary = document.getElementById('paymentSummary');
            
            // Função para formatar valores como moeda
            function formatCurrency(value) {
                return value.toLocaleString('pt-BR', {
                    style: 'currency',
                    currency: 'BRL',
                    minimumFractionDigits: 2
                });
            }
            
            // Função para atualizar resumo de pagamento
            function updatePaymentSummary() {
                const selectedCheckboxes = document.querySelectorAll('.transaction-checkbox:checked');
                const selectedCount = selectedCheckboxes.length;
                let totalValue = 0;
                
                selectedCheckboxes.forEach(checkbox => {
                    totalValue += parseFloat(checkbox.getAttribute('data-value'));
                });
                
                selectedCountElement.textContent = selectedCount;
                totalValueElement.textContent = formatCurrency(totalValue);
                
                // Habilitar/desabilitar botão de pagamento
                paySelectedBtn.disabled = selectedCount === 0;
                
                // Mostrar/esconder resumo de pagamento
                if (selectedCount > 0) {
                    paymentSummary.style.display = 'block';
                } else {
                    paymentSummary.style.display = 'none';
                }
            }
            
            // Evento para selecionar/deselecionar todos
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    transactionCheckboxes.forEach(checkbox => {
                        checkbox.checked = selectAllCheckbox.checked;
                    });
                    updatePaymentSummary();
                });
            }
            
            // Eventos para checkboxes individuais
            transactionCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    // Verificar se todos estão selecionados
                    const allChecked = Array.from(transactionCheckboxes).every(cb => cb.checked);
                    if (selectAllCheckbox) {
                        selectAllCheckbox.checked = allChecked;
                    }
                    
                    updatePaymentSummary();
                });
            });
            
            // Evento para botão de pagamento
            if (paySelectedBtn) {
                paySelectedBtn.addEventListener('click', function() {
                    if (document.querySelectorAll('.transaction-checkbox:checked').length > 0) {
                        paymentForm.submit();
                    }
                });
            }
            
            // Inicializar resumo
            updatePaymentSummary();
        });
    </script>
</body>
</html>