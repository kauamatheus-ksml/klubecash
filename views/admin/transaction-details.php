<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../controllers/AuthController.php';

// Verificar se o usuário está logado e é admin
if (!AuthController::isAuthenticated() || !AuthController::isAdmin()) {
    header('Location: ' . LOGIN_URL);
    exit;
}

// Obter ID da transação
$transactionId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($transactionId <= 0) {
    header('Location: ' . ADMIN_DASHBOARD_URL . '/transactions');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Transação - Klube Cash Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/admin.css" rel="stylesheet">
    <style>
        .transaction-details {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .detail-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
        }
        
        .detail-section {
            padding: 1.5rem;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .detail-section:last-child {
            border-bottom: none;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .detail-label {
            font-size: 0.875rem;
            color: #666;
            font-weight: 600;
        }
        
        .detail-value {
            font-size: 1rem;
            color: #333;
            font-weight: 500;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        
        .status-pendente {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .status-aprovado {
            background: #d4edda;
            color: #155724;
            border: 1px solid #00b894;
        }
        
        .status-cancelado {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #d63031;
        }
        
        .status-pagamento-pendente {
            background: #cce5ff;
            color: #0066cc;
            border: 1px solid #74b9ff;
        }
        
        .commission-breakdown {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 6px;
            margin-top: 1rem;
        }
        
        .breakdown-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .breakdown-item:last-child {
            border-bottom: none;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .history-timeline {
            position: relative;
            padding-left: 2rem;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -2rem;
            top: 0.5rem;
            width: 10px;
            height: 10px;
            background: #667eea;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 0 0 2px #667eea;
        }
        
        .timeline-item::after {
            content: '';
            position: absolute;
            left: -1.75rem;
            top: 1.25rem;
            width: 2px;
            height: calc(100% - 1rem);
            background: #e0e0e0;
        }
        
        .timeline-item:last-child::after {
            display: none;
        }
        
        .timeline-content {
            background: white;
            padding: 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .timeline-date {
            font-size: 0.75rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-approve {
            background: #2ecc71;
            color: white;
        }
        
        .btn-approve:hover {
            background: #27ae60;
        }
        
        .btn-cancel {
            background: #e74c3c;
            color: white;
        }
        
        .btn-cancel:hover {
            background: #c0392b;
        }
        
        .btn-back {
            background: #6c757d;
            color: white;
        }
        
        .btn-back:hover {
            background: #5a6268;
        }
        
        .loading {
            text-align: center;
            padding: 2rem;
        }
        
        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        
        .balance-info {
            background: #e8f4fd;
            border: 1px solid #bee5eb;
            padding: 1rem;
            border-radius: 6px;
            margin-top: 1rem;
        }
        
        .balance-info h4 {
            color: #0c5460;
            margin-bottom: 0.5rem;
        }
        
        .balance-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        @media (max-width: 768px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                justify-content: center;
            }
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
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 1;
        }
        
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            position: relative;
            transform: scale(0.7);
            transition: transform 0.3s ease;
        }
        
        .modal.show .modal-content {
            transform: scale(1);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            min-height: 100px;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/../components/admin-sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/../components/admin-header.php'; ?>
            
            <div class="content-wrapper">
                <div class="page-header">
                    <h1><i class="fas fa-receipt"></i> Detalhes da Transação</h1>
                    <p>Informações completas da transação selecionada</p>
                </div>
                
                <div id="transaction-content" class="loading">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p>Carregando detalhes da transação...</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Atualização de Status -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Atualizar Status da Transação</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="statusForm">
                <input type="hidden" id="transactionId" value="<?php echo $transactionId; ?>">
                <input type="hidden" id="newStatus" value="">
                
                <div class="form-group">
                    <label for="observacao">Observação:</label>
                    <textarea id="observacao" name="observacao" placeholder="Digite uma observação sobre esta atualização..."></textarea>
                </div>
                
                <div class="action-buttons">
                    <button type="button" class="btn btn-back" onclick="closeModal()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn" id="confirmBtn">
                        <i class="fas fa-check"></i> Confirmar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadTransactionDetails();
        });
        
        function loadTransactionDetails() {
            const transactionId = <?php echo $transactionId; ?>;
            
            fetch('<?php echo SITE_URL; ?>/controllers/AdminController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=transaction_details&transaction_id=${transactionId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    renderTransactionDetails(data.data);
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showError('Erro ao carregar detalhes da transação');
            });
        }
        
        function renderTransactionDetails(data) {
            const transaction = data.transacao;
            const comissions = data.comissoes || [];
            const history = data.historico_status || [];
            
            const content = `
                <div class="transaction-details">
                    <div class="detail-header">
                        <h2><i class="fas fa-receipt"></i> Transação #${transaction.id}</h2>
                        <div class="status-badge status-${transaction.status}">
                            <i class="fas fa-${getStatusIcon(transaction.status)}"></i>
                            ${getStatusText(transaction.status)}
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h3><i class="fas fa-info-circle"></i> Informações Gerais</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">ID da Transação</span>
                                <span class="detail-value">#${transaction.id}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Código da Transação</span>
                                <span class="detail-value">${transaction.codigo_transacao || 'N/A'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Data da Transação</span>
                                <span class="detail-value">${formatDate(transaction.data_transacao)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Status</span>
                                <span class="detail-value">
                                    <span class="status-badge status-${transaction.status}">
                                        <i class="fas fa-${getStatusIcon(transaction.status)}"></i>
                                        ${getStatusText(transaction.status)}
                                    </span>
                                </span>
                            </div>
                        </div>
                        
                        ${transaction.descricao ? `
                        <div class="detail-item">
                            <span class="detail-label">Descrição</span>
                            <span class="detail-value">${transaction.descricao}</span>
                        </div>
                        ` : ''}
                    </div>
                    
                    <div class="detail-section">
                        <h3><i class="fas fa-user"></i> Cliente</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Nome</span>
                                <span class="detail-value">${transaction.cliente_nome}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Email</span>
                                <span class="detail-value">${transaction.cliente_email}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h3><i class="fas fa-store"></i> Loja</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Nome Fantasia</span>
                                <span class="detail-value">${transaction.loja_nome}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Razão Social</span>
                                <span class="detail-value">${transaction.loja_razao_social || 'N/A'}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h3><i class="fas fa-calculator"></i> Valores e Comissões</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Valor Total</span>
                                <span class="detail-value">R$ ${formatMoney(transaction.valor_total)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Cashback Total</span>
                                <span class="detail-value">R$ ${formatMoney(transaction.valor_cashback)}</span>
                            </div>
                        </div>
                        
                        <div class="commission-breakdown">
                            <h4>Distribuição do Cashback</h4>
                            <div class="breakdown-item">
                                <span>Cliente (${formatPercent(transaction.valor_cliente, transaction.valor_total)})</span>
                                <span>R$ ${formatMoney(transaction.valor_cliente || 0)}</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Administração (${formatPercent(transaction.valor_admin, transaction.valor_total)})</span>
                                <span>R$ ${formatMoney(transaction.valor_admin || 0)}</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Loja (${formatPercent(transaction.valor_loja || 0, transaction.valor_total)})</span>
                                <span>R$ ${formatMoney(transaction.valor_loja || 0)}</span>
                            </div>
                            <div class="breakdown-item">
                                <span><strong>Total</strong></span>
                                <span><strong>R$ ${formatMoney(transaction.valor_cashback)}</strong></span>
                            </div>
                        </div>
                        
                        ${transaction.saldo_usado && parseFloat(transaction.saldo_usado) > 0 ? `
                        <div class="balance-info">
                            <h4><i class="fas fa-piggy-bank"></i> Informações de Saldo</h4>
                            <div class="balance-details">
                                <div class="detail-item">
                                    <span class="detail-label">Saldo Usado na Compra</span>
                                    <span class="detail-value">R$ ${formatMoney(transaction.saldo_usado)}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Valor Efetivamente Pago</span>
                                    <span class="detail-value">R$ ${formatMoney(parseFloat(transaction.valor_total) - parseFloat(transaction.saldo_usado))}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Descrição do Uso</span>
                                    <span class="detail-value">${transaction.descricao_uso_saldo || 'N/A'}</span>
                                </div>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                    
                    ${comissions.length > 0 ? `
                    <div class="detail-section">
                        <h3><i class="fas fa-percentage"></i> Comissões Relacionadas</h3>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Usuário</th>
                                        <th>Valor</th>
                                        <th>Status</th>
                                        <th>Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${comissions.map(comm => `
                                        <tr>
                                            <td>${comm.tipo_usuario}</td>
                                            <td>#${comm.usuario_id}</td>
                                            <td>R$ ${formatMoney(comm.valor_comissao)}</td>
                                            <td>
                                                <span class="status-badge status-${comm.status}">
                                                    ${getStatusText(comm.status)}
                                                </span>
                                            </td>
                                            <td>${formatDate(comm.data_transacao)}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    ` : ''}
                    
                    ${history.length > 0 ? `
                    <div class="detail-section">
                        <h3><i class="fas fa-history"></i> Histórico de Alterações</h3>
                        <div class="history-timeline">
                            ${history.map(item => `
                                <div class="timeline-item">
                                    <div class="timeline-content">
                                        <div class="timeline-date">${formatDateTime(item.data_alteracao)}</div>
                                        <p><strong>Status alterado:</strong> ${getStatusText(item.status_anterior)} → ${getStatusText(item.status_novo)}</p>
                                        ${item.observacao ? `<p><strong>Observação:</strong> ${item.observacao}</p>` : ''}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    ` : ''}
                    
                    <div class="detail-section">
                        <div class="action-buttons">
                            <a href="<?php echo SITE_URL; ?>/admin/transactions" class="btn btn-back">
                                <i class="fas fa-arrow-left"></i> Voltar à Lista
                            </a>
                            
                            ${transaction.status === 'pendente' ? `
                                <button class="btn btn-approve" onclick="openStatusModal('aprovado', 'Aprovar Transação')">
                                    <i class="fas fa-check"></i> Aprovar
                                </button>
                                <button class="btn btn-cancel" onclick="openStatusModal('cancelado', 'Cancelar Transação')">
                                    <i class="fas fa-times"></i> Cancelar
                                </button>
                            ` : ''}
                            
                            ${(transaction.status === 'aprovado' || transaction.status === 'cancelado') ? `
                                <button class="btn btn-back" onclick="openStatusModal('pendente', 'Reverter para Pendente')">
                                    <i class="fas fa-undo"></i> Reverter Status
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('transaction-content').innerHTML = content;
        }
        
        function openStatusModal(status, title) {
            document.getElementById('newStatus').value = status;
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('observacao').value = '';
            
            const confirmBtn = document.getElementById('confirmBtn');
            confirmBtn.className = `btn ${status === 'aprovado' ? 'btn-approve' : status === 'cancelado' ? 'btn-cancel' : 'btn-back'}`;
            confirmBtn.innerHTML = `<i class="fas fa-check"></i> ${title}`;
            
            document.getElementById('statusModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('statusModal').classList.remove('show');
        }
        
        document.getElementById('statusForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'update_transaction_status');
            formData.append('transaction_id', document.getElementById('transactionId').value);
            formData.append('status', document.getElementById('newStatus').value);
            formData.append('observacao', document.getElementById('observacao').value);
            
            fetch('<?php echo SITE_URL; ?>/controllers/AdminController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    closeModal();
                    showSuccess(data.message);
                    loadTransactionDetails(); // Recarregar os detalhes
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showError('Erro ao atualizar status da transação');
            });
        });
        
        function getStatusIcon(status) {
            const icons = {
                'pendente': 'clock',
                'aprovado': 'check-circle',
                'cancelado': 'times-circle',
                'pagamento_pendente': 'credit-card'
            };
            return icons[status] || 'question-circle';
        }
        
        function getStatusText(status) {
            const texts = {
                'pendente': 'Pendente',
                'aprovado': 'Aprovado',
                'cancelado': 'Cancelado',
                'pagamento_pendente': 'Pagamento Pendente'
            };
            return texts[status] || status;
        }
        
        function formatMoney(value) {
            return parseFloat(value || 0).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
        
        function formatPercent(value, total) {
            const percent = (parseFloat(value || 0) / parseFloat(total || 1)) * 100;
            return percent.toFixed(1) + '%';
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        }
        
        function formatDateTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        function showError(message) {
            document.getElementById('transaction-content').innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    ${message}
                </div>
                <div class="action-buttons">
                    <a href="<?php echo SITE_URL; ?>/admin/transactions" class="btn btn-back">
                        <i class="fas fa-arrow-left"></i> Voltar à Lista
                    </a>
                </div>
            `;
        }
        
        function showSuccess(message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success';
            alertDiv.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
            alertDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1001;
                padding: 1rem;
                background: #d4edda;
                border: 1px solid #c3e6cb;
                color: #155724;
                border-radius: 6px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            `;
            
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        }
        
        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            const modal = document.getElementById('statusModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>