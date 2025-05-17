<?php
// ADICIONAR LOGO APÓS session_start() em payment.php

// Habilitar logs de erro
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Log de debug
error_log("payment.php - Início da execução. Method: " . $_SERVER['REQUEST_METHOD']);
if ($_POST) {
    error_log("payment.php - POST data: " . print_r($_POST, true));
}


// views/admin/payments.php
// Definir o menu ativo na sidebar
$activeMenu = 'pagamentos';

// Incluir arquivos necessários
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/TransactionController.php';

// Iniciar sessão
session_start();

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

// Obter pagamentos do banco
$db = Database::getConnection();

// Construir consulta
$query = "
    SELECT p.*, l.nome_fantasia, l.email as loja_email,
           (SELECT COUNT(*) FROM pagamentos_transacoes WHERE pagamento_id = p.id) as total_transacoes
    FROM pagamentos_comissao p
    JOIN lojas l ON p.loja_id = l.id
    WHERE 1=1
";

$params = [];

// Aplicar filtros
if (!empty($filters['status'])) {
    $query .= " AND p.status = :status";
    $params[':status'] = $filters['status'];
}

if (!empty($filters['data_inicio'])) {
    $query .= " AND p.data_registro >= :data_inicio";
    $params[':data_inicio'] = $filters['data_inicio'] . ' 00:00:00';
}

if (!empty($filters['data_fim'])) {
    $query .= " AND p.data_registro <= :data_fim";
    $params[':data_fim'] = $filters['data_fim'] . ' 23:59:59';
}

$query .= " ORDER BY p.data_registro DESC";

// Contagem para paginação
$countQuery = str_replace("p.*, l.nome_fantasia, l.email as loja_email, (SELECT COUNT(*) FROM pagamentos_transacoes WHERE pagamento_id = p.id) as total_transacoes", "COUNT(*) as total", $query);
$countStmt = $db->prepare($countQuery);
foreach ($params as $param => $value) {
    $countStmt->bindValue($param, $value);
}
$countStmt->execute();
$totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Paginação
$perPage = ITEMS_PER_PAGE;
$totalPages = ceil($totalCount / $perPage);
$page = max(1, min($page, $totalPages));
$offset = ($page - 1) * $perPage;

$query .= " LIMIT :offset, :limit";
$params[':offset'] = $offset;
$params[':limit'] = $perPage;

// Executar consulta
$stmt = $db->prepare($query);
foreach ($params as $param => $value) {
    if ($param == ':offset' || $param == ':limit') {
        $stmt->bindValue($param, $value, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($param, $value);
    }
}
$stmt->execute();
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$statsQuery = "
    SELECT 
        COUNT(*) as total_pagamentos,
        SUM(valor_total) as valor_total,
        SUM(CASE WHEN status = 'pendente' THEN valor_total ELSE 0 END) as valor_pendente,
        SUM(CASE WHEN status = 'aprovado' THEN valor_total ELSE 0 END) as valor_aprovado,
        COUNT(CASE WHEN status = 'pendente' THEN 1 END) as count_pendente,
        COUNT(CASE WHEN status = 'aprovado' THEN 1 END) as count_aprovado,
        COUNT(CASE WHEN status = 'rejeitado' THEN 1 END) as count_rejeitado
    FROM pagamentos_comissao
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
    <style>
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert.success {
            background-color: #E6F7E6;
            color: #4CAF50;
            border: 1px solid #4CAF50;
        }
        .alert.error {
            background-color: #FFEAE6;
            color: #F44336;
            border: 1px solid #F44336;
        }
        .filter-container {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .filter-form {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        .form-group {
            flex: 1;
            min-width: 150px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pendente {
            background-color: #FFF3CD;
            color: #856404;
        }
        .status-aprovado {
            background-color: #D4EDDA;
            color: #155724;
        }
        .status-rejeitado {
            background-color: #F8D7DA;
            color: #721C24;
        }
        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin: 2px;
        }
        .btn-approve {
            background-color: #28a745;
            color: white;
        }
        .btn-reject {
            background-color: #dc3545;
            color: white;
        }
        .btn-view {
            background-color: #007bff;
            color: white;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 12px;
            width: 80%;
            max-width: 600px;
            max-height: 80%;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }
        .close:hover {
            color: #000;
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }
        .pagination-links {
            display: flex;
            gap: 10px;
        }
        .page-link {
            padding: 8px 16px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            text-decoration: none;
            color: #495057;
        }
        .page-link:hover {
            background-color: #e9ecef;
        }
    </style>
</head>
<body>
    <?php include_once '../components/sidebar.php'; ?>
    
    <div class="main-content" id="mainContent">
        <div class="dashboard-wrapper">
            <!-- Cabeçalho -->
            <div class="dashboard-header">
                <h1>Gerenciar Pagamentos</h1>
                <p class="subtitle">Aprovar ou rejeitar pagamentos de comissões das lojas</p>
            </div>
            
            <!-- Alertas -->
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
            
            <!-- Estatísticas -->
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
            </div>
            
            <!-- Filtros -->
            <div class="filter-container">
                <form method="GET" action="" class="filter-form">
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
                        <label for="data_inicio">Data Início</label>
                        <input type="date" id="data_inicio" name="data_inicio" value="<?php echo isset($_GET['data_inicio']) ? htmlspecialchars($_GET['data_inicio']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="data_fim">Data Fim</label>
                        <input type="date" id="data_fim" name="data_fim" value="<?php echo isset($_GET['data_fim']) ? htmlspecialchars($_GET['data_fim']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <a href="<?php echo ADMIN_PAYMENTS_URL; ?>" class="btn btn-secondary">Limpar</a>
                    </div>
                </form>
            </div>
            
            <!-- Lista de Pagamentos -->
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
                                    <th>Valor</th>
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
                                        <td><?php echo htmlspecialchars($payment['nome_fantasia']); ?></td>
                                        <td>R$ <?php echo number_format($payment['valor_total'], 2, ',', '.'); ?></td>
                                        <td><?php echo ucfirst($payment['metodo_pagamento']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($payment['data_registro'])); ?></td>
                                        <td><?php echo $payment['total_transacoes']; ?></td>
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
                    
                    <!-- Paginação -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <div class="pagination-info">
                                Página <?php echo $page; ?> de <?php echo $totalPages; ?> (<?php echo $totalCount; ?> itens)
                            </div>
                            <div class="pagination-links">
                                <?php if ($page > 1): ?>
                                    <a href="?page=1<?php echo !empty($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo !empty($_GET['data_inicio']) ? '&data_inicio=' . urlencode($_GET['data_inicio']) : ''; ?><?php echo !empty($_GET['data_fim']) ? '&data_fim=' . urlencode($_GET['data_fim']) : ''; ?>" class="page-link">
                                        Primeira
                                    </a>
                                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo !empty($_GET['data_inicio']) ? '&data_inicio=' . urlencode($_GET['data_inicio']) : ''; ?><?php echo !empty($_GET['data_fim']) ? '&data_fim=' . urlencode($_GET['data_fim']) : ''; ?>" class="page-link">
                                        Anterior
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo !empty($_GET['data_inicio']) ? '&data_inicio=' . urlencode($_GET['data_inicio']) : ''; ?><?php echo !empty($_GET['data_fim']) ? '&data_fim=' . urlencode($_GET['data_fim']) : ''; ?>" class="page-link">
                                        Próxima
                                    </a>
                                    <a href="?page=<?php echo $totalPages; ?><?php echo !empty($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo !empty($_GET['data_inicio']) ? '&data_inicio=' . urlencode($_GET['data_inicio']) : ''; ?><?php echo !empty($_GET['data_fim']) ? '&data_fim=' . urlencode($_GET['data_fim']) : ''; ?>" class="page-link">
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
    
    <!-- Modal de Detalhes -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Detalhes do Pagamento</h2>
                <span class="close">&times;</span>
            </div>
            <div id="detailsContent">
                <p>Carregando...</p>
            </div>
        </div>
    </div>
    
    <!-- Modal de Aprovação -->
    <div id="approveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Aprovar Pagamento</h2>
                <span class="close">&times;</span>
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
    
    <!-- Modal de Rejeição -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Rejeitar Pagamento</h2>
                <span class="close">&times;</span>
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
    
    <!-- Modal de Comprovante -->
    <div id="receiptModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Comprovante de Pagamento</h2>
                <span class="close">&times;</span>
            </div>
            <div id="receiptContent">
                <img id="receiptImage" src="" alt="Comprovante" style="max-width: 100%; height: auto;">
            </div>
        </div>
    </div>
    
    <script>
        // Fechar modais
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('close') || e.target.classList.contains('modal')) {
                e.target.closest('.modal').style.display = 'none';
            }
        });
        
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
            document.getElementById('detailsModal').style.display = 'block';
            document.getElementById('detailsContent').innerHTML = '<p>Carregando detalhes...</p>';
            
            // Fazer requisição AJAX para obter detalhes
            fetch('../../controllers/TransactionController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=payment_details&payment_id=' + paymentId
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    renderPaymentDetails(data.data);
                } else {
                    document.getElementById('detailsContent').innerHTML = '<p class="error">Erro ao carregar detalhes: ' + data.message + '</p>';
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                document.getElementById('detailsContent').innerHTML = '<p class="error">Erro de conexão. Tente novamente.</p>';
            });
        }
        
        function renderPaymentDetails(data) {
            const payment = data.pagamento;
            const transactions = data.transacoes;
            
            let html = `
                <div style="margin-bottom: 20px;">
                    <h3>Informações do Pagamento</h3>
                    <p><strong>ID:</strong> ${payment.id}</p>
                    <p><strong>Loja:</strong> ${payment.loja_nome}</p>
                    <p><strong>Valor Total:</strong> R$ ${parseFloat(payment.valor_total).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>
                    <p><strong>Método:</strong> ${payment.metodo_pagamento}</p>
                    <p><strong>Data:</strong> ${new Date(payment.data_registro).toLocaleDateString('pt-BR')}</p>
                    ${payment.numero_referencia ? `<p><strong>Referência:</strong> ${payment.numero_referencia}</p>` : ''}
                    ${payment.observacao ? `<p><strong>Observação:</strong> ${payment.observacao}</p>` : ''}
                </div>
                
                <div>
                    <h3>Transações Incluídas (${transactions.length})</h3>
                    <div style="max-height: 300px; overflow-y: auto;">
                        <table class="table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Data</th>
                                    <th>Valor</th>
                                    <th>Cashback</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            
            transactions.forEach(transaction => {
                html += `
                    <tr>
                        <td>${transaction.cliente_nome}</td>
                        <td>${new Date(transaction.data_transacao).toLocaleDateString('pt-BR')}</td>
                        <td>R$ ${parseFloat(transaction.valor_total).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                        <td>R$ ${parseFloat(transaction.valor_cliente).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                    </tr>
                `;
            });
            
            html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
            
            document.getElementById('detailsContent').innerHTML = html;
        }
        
        function viewReceipt(filename) {
            document.getElementById('receiptImage').src = '../../uploads/comprovantes/' + filename;
            document.getElementById('receiptModal').style.display = 'block';
        }
    </script>
</body>
</html>