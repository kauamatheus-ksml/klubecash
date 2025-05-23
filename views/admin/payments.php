<?php
// views/admin/payments.php
// Definir o menu ativo na sidebar
$activeMenu = 'pagamentos';

// Incluir arquivos necessários
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/TransactionController.php';
require_once '../../models/CashbackBalance.php';

// Iniciar sessão
session_start();

// Habilitar logs de erro e debug inicial
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

error_log("payments.php - Início da execução. Method: " . $_SERVER['REQUEST_METHOD']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    error_log("payments.php - POST data: " . print_r($_POST, true));
}

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

// Processar ações (aprovar/rejeitar)
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'approve' && isset($_POST['payment_id'])) {
            $paymentId = intval($_POST['payment_id']);
            $observacao = $_POST['observacao'] ?? '';
            $result = TransactionController::approvePayment($paymentId, $observacao);
            
            if ($result['status']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        } elseif ($_POST['action'] === 'reject' && isset($_POST['payment_id'])) {
            $paymentId = intval($_POST['payment_id']);
            $motivo = $_POST['motivo'] ?? '';
            $result = TransactionController::rejectPayment($paymentId, $motivo);
            
            if ($result['status']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Obter lista de pagamentos
$filters = [];
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;

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

$db = Database::getConnection();

// --- Construção da Query com informações de saldo ---
$selectPart = "SELECT 
    p.*, 
    l.nome_fantasia, 
    l.email as loja_email,
    COALESCE((SELECT COUNT(*) FROM pagamentos_transacoes pt WHERE pt.pagamento_id = p.id), 0) as total_transacoes,
    COALESCE(SUM(t_vendas.valor_total), 0) as valor_vendas_originais,
    COALESCE(SUM(
        (SELECT SUM(cm.valor) 
         FROM cashback_movimentacoes cm 
         WHERE cm.transacao_uso_id = t_vendas.id AND cm.tipo_operacao = 'uso')
    ), 0) as total_saldo_usado,
    COUNT(CASE WHEN EXISTS(
        SELECT 1 FROM cashback_movimentacoes cm2 
        WHERE cm2.transacao_uso_id = t_vendas.id AND cm2.tipo_operacao = 'uso'
    ) THEN 1 END) as transacoes_com_saldo";

$fromPart = "FROM pagamentos_comissao p 
JOIN lojas l ON p.loja_id = l.id
LEFT JOIN pagamentos_transacoes pt ON p.id = pt.pagamento_id
LEFT JOIN transacoes_cashback t_vendas ON pt.transacao_id = t_vendas.id";

$wherePart = "WHERE 1=1";
$paramsForWhere = [];

// Aplicar filtros à cláusula WHERE
if (!empty($filters['status'])) {
    $wherePart .= " AND p.status = :status";
    $paramsForWhere[':status'] = $filters['status'];
}
if (!empty($filters['data_inicio'])) {
    $wherePart .= " AND p.data_registro >= :data_inicio";
    $paramsForWhere[':data_inicio'] = $filters['data_inicio'] . ' 00:00:00';
}
if (!empty($filters['data_fim'])) {
    $wherePart .= " AND p.data_registro <= :data_fim";
    $paramsForWhere[':data_fim'] = $filters['data_fim'] . ' 23:59:59';
}

$groupPart = "GROUP BY p.id";

// Contagem para paginação
$countQuery = "SELECT COUNT(DISTINCT p.id) as total 
               FROM pagamentos_comissao p 
               JOIN lojas l ON p.loja_id = l.id " . 
               str_replace("WHERE 1=1", "WHERE 1=1", $wherePart);

$countStmt = $db->prepare($countQuery);
foreach ($paramsForWhere as $paramName => $paramValue) {
    $countStmt->bindValue($paramName, $paramValue);
}
$countStmt->execute();
$resultCount = $countStmt->fetch(PDO::FETCH_ASSOC);
$totalCount = $resultCount ? (int)$resultCount['total'] : 0;

// Paginação
$perPage = defined('ITEMS_PER_PAGE') ? ITEMS_PER_PAGE : 10;
$totalPages = ($totalCount > 0) ? ceil($totalCount / $perPage) : 1;
$page = max(1, min($page, $totalPages));
$offset = ($page - 1) * $perPage;

// Query principal para buscar dados
$mainQuery = $selectPart . " " . $fromPart . " " . $wherePart . " " . $groupPart . " ORDER BY p.data_registro DESC LIMIT :offset, :limit";
$stmt = $db->prepare($mainQuery);

// Bind parâmetros para a query principal (filtros + paginação)
$paramsForMainQuery = $paramsForWhere;
$paramsForMainQuery[':offset'] = $offset;
$paramsForMainQuery[':limit'] = $perPage;

foreach ($paramsForMainQuery as $paramName => $paramValue) {
    if ($paramName == ':offset' || $paramName == ':limit') {
        $stmt->bindValue($paramName, (int)$paramValue, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($paramName, $paramValue);
    }
}
$stmt->execute();
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas globais com informações de saldo
$statsQuery = "
    SELECT 
        COUNT(*) as total_pagamentos,
        SUM(p.valor_total) as valor_total_comissoes,
        SUM(CASE WHEN p.status = 'pendente' THEN p.valor_total ELSE 0 END) as valor_pendente,
        SUM(CASE WHEN p.status = 'aprovado' THEN p.valor_total ELSE 0 END) as valor_aprovado,
        COUNT(CASE WHEN p.status = 'pendente' THEN 1 END) as count_pendente,
        COUNT(CASE WHEN p.status = 'aprovado' THEN 1 END) as count_aprovado,
        COUNT(CASE WHEN p.status = 'rejeitado' THEN 1 END) as count_rejeitado,
        COALESCE(SUM(sub.valor_vendas_originais), 0) as total_vendas_originais,
        COALESCE(SUM(sub.total_saldo_usado), 0) as total_saldo_usado_sistema
    FROM pagamentos_comissao p
    LEFT JOIN (
        SELECT 
            pt.pagamento_id,
            SUM(t.valor_total) as valor_vendas_originais,
            SUM(COALESCE(
                (SELECT SUM(cm.valor) 
                 FROM cashback_movimentacoes cm 
                 WHERE cm.transacao_uso_id = t.id AND cm.tipo_operacao = 'uso'), 0
            )) as total_saldo_usado
        FROM pagamentos_transacoes pt
        JOIN transacoes_cashback t ON pt.transacao_id = t.id
        GROUP BY pt.pagamento_id
    ) sub ON p.id = sub.pagamento_id
";

$statsStmt = $db->query($statsQuery);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <title>Gerenciar Pagamentos - Klube Cash</title>
    <link rel="stylesheet" href="../../assets/css/views/admin/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/views/admin/payments.css">
    <style>
        
    </style>
</head>
<body>
    <?php include_once '../components/sidebar.php'; ?>
    
    <div class="main-content" id="mainContent">
        <div class="dashboard-wrapper">
            <div class="dashboard-header">
                <h1>Gerenciar Pagamentos</h1>
                <p class="subtitle">Aprovar ou rejeitar pagamentos de comissões das lojas</p>
            </div>
            
            <?php if (!empty($success)): ?>
                <div class="alert success">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert error">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-card-title">Total de Pagamentos</div>
                    <div class="stat-card-value"><?php echo number_format($stats['total_pagamentos']); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-title">Pendentes</div>
                    <div class="stat-card-value"><?php echo number_format($stats['count_pendente']); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-title">Valor Pendente</div>
                    <div class="stat-card-value">R$ <?php echo number_format($stats['valor_pendente'], 2, ',', '.'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-title">Valor Total Aprovado</div>
                    <div class="stat-card-value">R$ <?php echo number_format($stats['valor_aprovado'], 2, ',', '.'); ?></div>
                </div>
                <div class="stat-card stat-card-balance">
                    <div class="stat-card-title">Vendas Originais</div>
                    <div class="stat-card-value">R$ <?php echo number_format($stats['total_vendas_originais'], 2, ',', '.'); ?></div>
                    <div class="stat-card-subtitle">Valor total das vendas</div>
                </div>
                <div class="stat-card stat-card-balance">
                    <div class="stat-card-title">Economia Clientes</div>
                    <div class="stat-card-value">R$ <?php echo number_format($stats['total_saldo_usado_sistema'], 2, ',', '.'); ?></div>
                    <div class="stat-card-subtitle">Saldo usado em compras</div>
                </div>
            </div>
            
            <div class="filter-container">
                <form method="GET" action="" class="filter-form">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="">Todos</option>
                            <option value="pendente" <?php echo (isset($filters['status']) && $filters['status'] === 'pendente') ? 'selected' : ''; ?>>Pendente</option>
                            <option value="aprovado" <?php echo (isset($filters['status']) && $filters['status'] === 'aprovado') ? 'selected' : ''; ?>>Aprovado</option>
                            <option value="rejeitado" <?php echo (isset($filters['status']) && $filters['status'] === 'rejeitado') ? 'selected' : ''; ?>>Rejeitado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="data_inicio">Data Início</label>
                        <input type="date" id="data_inicio" name="data_inicio" value="<?php echo isset($filters['data_inicio']) ? htmlspecialchars($filters['data_inicio']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="data_fim">Data Fim</label>
                        <input type="date" id="data_fim" name="data_fim" value="<?php echo isset($filters['data_fim']) ? htmlspecialchars($filters['data_fim']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <a href="<?php echo ADMIN_PAYMENTS_URL; ?>" class="btn btn-secondary">Limpar</a>
                    </div>
                </form>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Lista de Pagamentos</div>
                </div>
                
                <?php if (count($payments) > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#ID</th>
                                    <th>Loja</th>
                                    <th>Valor Original</th>
                                    <th>Saldo Usado</th>
                                    <th>Comissão</th>
                                    <th>Método</th>
                                    <th>Data</th>
                                    <th>Transações</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?php echo $payment['id']; ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($payment['nome_fantasia']); ?>
                                            <?php if ($payment['total_saldo_usado'] > 0): ?>
                                                <span class="balance-indicator" title="Clientes usaram saldo">💰</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div>
                                                <span class="valor-original">R$ <?php echo number_format($payment['valor_vendas_originais'], 2, ',', '.'); ?></span>
                                                <small class="valor-detail">Vendas</small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($payment['total_saldo_usado'] > 0): ?>
                                                <span class="saldo-usado">R$ <?php echo number_format($payment['total_saldo_usado'], 2, ',', '.'); ?></span>
                                                <?php if ($payment['transacoes_com_saldo'] > 0): ?>
                                                    <small class="economia-badge"><?php echo $payment['transacoes_com_saldo']; ?> uso<?php echo $payment['transacoes_com_saldo'] > 1 ? 's' : ''; ?></small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="sem-saldo">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div>
                                                <span class="valor-liquido">R$ <?php echo number_format($payment['valor_total'], 2, ',', '.'); ?></span>
                                                <small class="valor-detail">Paga</small>
                                                <?php if ($payment['total_saldo_usado'] > 0): ?>
                                                    <div class="saldo-info">
                                                        <small>Economia: R$ <?php echo number_format($payment['total_saldo_usado'], 2, ',', '.'); ?></small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo ucfirst($payment['metodo_pagamento']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($payment['data_registro'])); ?></td>
                                        <td>
                                            <?php echo $payment['total_transacoes']; ?> vendas
                                            <?php if ($payment['transacoes_com_saldo'] > 0): ?>
                                                <br><small class="economia-badge"><?php echo $payment['transacoes_com_saldo']; ?> c/ saldo</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $payment['status']; ?>">
                                                <?php echo ucfirst($payment['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn-action btn-view" onclick="viewPaymentDetails(<?php echo $payment['id']; ?>)">
                                                Ver Detalhes
                                            </button>
                                            <?php if ($payment['status'] === 'pendente'): ?>
                                                <button class="btn-action btn-approve" onclick="showApproveModal(<?php echo $payment['id']; ?>)">
                                                    Aprovar
                                                </button>
                                                <button class="btn-action btn-reject" onclick="showRejectModal(<?php echo $payment['id']; ?>)">
                                                    Rejeitar
                                                </button>
                                            <?php endif; ?>
                                            <?php if (!empty($payment['comprovante'])): ?>
                                                <button class="btn-action btn-view" onclick="viewReceipt('<?php echo htmlspecialchars($payment['comprovante']); ?>')">
                                                    Comprovante
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <div class="pagination-info">
                                Página <?php echo $page; ?> de <?php echo $totalPages; ?> (<?php echo $totalCount; ?> itens)
                            </div>
                            <div class="pagination-links">
                                <?php if ($page > 1): ?>
                                    <a href="?page=1<?php echo !empty($filters['status']) ? '&status=' . urlencode($filters['status']) : ''; ?><?php echo !empty($filters['data_inicio']) ? '&data_inicio=' . urlencode($filters['data_inicio']) : ''; ?><?php echo !empty($filters['data_fim']) ? '&data_fim=' . urlencode($filters['data_fim']) : ''; ?>" class="page-link">
                                        Primeira
                                    </a>
                                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($filters['status']) ? '&status=' . urlencode($filters['status']) : ''; ?><?php echo !empty($filters['data_inicio']) ? '&data_inicio=' . urlencode($filters['data_inicio']) : ''; ?><?php echo !empty($filters['data_fim']) ? '&data_fim=' . urlencode($filters['data_fim']) : ''; ?>" class="page-link">
                                        Anterior
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($filters['status']) ? '&status=' . urlencode($filters['status']) : ''; ?><?php echo !empty($filters['data_inicio']) ? '&data_inicio=' . urlencode($filters['data_inicio']) : ''; ?><?php echo !empty($filters['data_fim']) ? '&data_fim=' . urlencode($filters['data_fim']) : ''; ?>" class="page-link">
                                        Próxima
                                    </a>
                                    <a href="?page=<?php echo $totalPages; ?><?php echo !empty($filters['status']) ? '&status=' . urlencode($filters['status']) : ''; ?><?php echo !empty($filters['data_inicio']) ? '&data_inicio=' . urlencode($filters['data_inicio']) : ''; ?><?php echo !empty($filters['data_fim']) ? '&data_fim=' . urlencode($filters['data_fim']) : ''; ?>" class="page-link">
                                        Última
                                    </a>
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
                        <p>Não foram encontrados pagamentos com os filtros aplicados.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Detalhes do Pagamento</h2>
                <span class="close" onclick="closeModal('detailsModal')">&times;</span>
            </div>
            <div id="detailsContent">
                <p>Carregando...</p>
            </div>
        </div>
    </div>
    
    <div id="approveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Aprovar Pagamento</h2>
                <span class="close" onclick="closeModal('approveModal')">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="payment_id" id="approve_payment_id">
                <div class="form-group">
                    <label for="observacao">Observação (opcional)</label>
                    <textarea id="observacao" name="observacao" rows="3" placeholder="Adicione uma observação se necessário..."></textarea>
                </div>
                <div style="margin-top: 20px; text-align: right;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('approveModal')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Confirmar Aprovação</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Rejeitar Pagamento</h2>
                <span class="close" onclick="closeModal('rejectModal')">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="payment_id" id="reject_payment_id">
                <div class="form-group">
                    <label for="motivo">Motivo da rejeição *</label>
                    <textarea id="motivo" name="motivo" rows="3" placeholder="Informe o motivo da rejeição..." required></textarea>
                </div>
                <div style="margin-top: 20px; text-align: right;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('rejectModal')">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Confirmar Rejeição</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="receiptModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Comprovante de Pagamento</h2>
                <span class="close" onclick="closeModal('receiptModal')">&times;</span>
            </div>
            <div id="receiptContent">
                <img id="receiptImage" src="" alt="Comprovante" style="max-width: 100%; height: auto;">
            </div>
        </div>
    </div>
    
    <script>
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function showApproveModal(paymentId) {
            document.getElementById('approve_payment_id').value = paymentId;
            document.getElementById('approveModal').style.display = 'block';
        }
        
        function showRejectModal(paymentId) {
            document.getElementById('reject_payment_id').value = paymentId;
            document.getElementById('rejectModal').style.display = 'block';
        }
        
        function viewPaymentDetails(paymentId) {
            const modal = document.getElementById('detailsModal');
            const content = document.getElementById('detailsContent');
            modal.style.display = 'block';
            content.innerHTML = '<p>Carregando detalhes...</p>';
            
            fetch('../../controllers/TransactionController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=payment_details_with_balance&payment_id=' + paymentId
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    renderPaymentDetailsWithBalance(data.data, content);
                } else {
                    content.innerHTML = '<p class="error">Erro ao carregar detalhes: ' + (data.message || 'Erro desconhecido.') + '</p>';
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                content.innerHTML = '<p class="error">Erro de conexão. Tente novamente.</p>';
            });
        }
        
        function renderPaymentDetailsWithBalance(data, contentElement) {
            const payment = data.pagamento;
            const transactions = data.transacoes;
            
            let html = `
                <div style="margin-bottom: 20px;">
                    <h3>Informações do Pagamento</h3>
                    <p><strong>ID:</strong> ${payment.id}</p>
                    <p><strong>Loja:</strong> ${payment.loja_nome || 'N/A'}</p>
                    <p><strong>Valor Original das Vendas:</strong> R$ ${parseFloat(payment.valor_vendas_originais || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>
                    <p><strong>Total Saldo Usado:</strong> <span style="color: #28a745; font-weight: 600;">R$ ${parseFloat(payment.total_saldo_usado || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span></p>
                    <p><strong>Comissão Paga:</strong> R$ ${parseFloat(payment.valor_total).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>
                    <p><strong>Método:</strong> ${payment.metodo_pagamento || 'N/A'}</p>
                    <p><strong>Data:</strong> ${payment.data_registro ? new Date(payment.data_registro).toLocaleString('pt-BR') : 'N/A'}</p>
                    ${payment.numero_referencia ? `<p><strong>Referência:</strong> ${payment.numero_referencia}</p>` : ''}
                    ${payment.observacao ? `<p><strong>Observação (Loja):</strong> ${payment.observacao}</p>` : ''}
                    ${payment.observacao_admin ? `<p><strong>Observação (Admin):</strong> ${payment.observacao_admin}</p>` : ''}
                </div>
                
                <div>
                    <h3>Transações Incluídas (${transactions.length})</h3>`;
            
            if (transactions.length > 0) {
                html += `<div style="max-height: 300px; overflow-y: auto;">
                            <table class="table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Cliente</th>
                                        <th>Data Trans.</th>
                                        <th>Valor Original</th>
                                        <th>Saldo Usado</th>
                                        <th>Valor Pago</th>
                                        <th>Cashback Cliente</th>
                                    </tr>
                                </thead>
                                <tbody>`;
                transactions.forEach(transaction => {
                    const saldoUsado = parseFloat(transaction.saldo_usado || 0);
                    const valorPago = parseFloat(transaction.valor_total) - saldoUsado;
                    
                    html += `
                        <tr>
                            <td>${transaction.cliente_nome || 'N/A'} ${saldoUsado > 0 ? '💰' : ''}</td>
                            <td>${transaction.data_transacao ? new Date(transaction.data_transacao).toLocaleDateString('pt-BR') : 'N/A'}</td>
                            <td>R$ ${parseFloat(transaction.valor_total).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                            <td>${saldoUsado > 0 ? 'R$ ' + saldoUsado.toLocaleString('pt-BR', {minimumFractionDigits: 2}) : '-'}</td>
                            <td>R$ ${valorPago.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                            <td>R$ ${parseFloat(transaction.valor_cliente).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                        </tr>
                    `;
                });
                html += `       </tbody>
                            </table>
                        </div>`;
            } else {
                html += '<p>Nenhuma transação associada a este pagamento.</p>';
            }
            
            // Resumo do impacto do saldo
            if (payment.total_saldo_usado > 0) {
                html += `
                    <div style="background: #f8fff8; padding: 15px; border-radius: 8px; margin-top: 15px; border-left: 4px solid #28a745;">
                        <h4 style="margin-top: 0; color: #28a745;">💰 Impacto do Saldo</h4>
                        <p><strong>Economia gerada aos clientes:</strong> R$ ${parseFloat(payment.total_saldo_usado).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>
                        <p><strong>Redução na comissão:</strong> R$ ${(parseFloat(payment.total_saldo_usado) * 0.1).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>
                        <p><small>A comissão foi calculada sobre o valor efetivamente pago pelos clientes (original - saldo usado)</small></p>
                    </div>
                `;
            }
            
            html += `</div>`;
            
            contentElement.innerHTML = html;
        }
        
        function viewReceipt(filename) {
            if (!filename) return;
            document.getElementById('receiptImage').src = '../../uploads/comprovantes/' + filename;
            document.getElementById('receiptModal').style.display = 'block';
        }
    </script>
</body>
</html>