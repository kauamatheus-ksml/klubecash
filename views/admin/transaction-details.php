<?php
// views/admin/transaction-details.php
// Verificar se o usuário está autenticado
session_start();
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';

// Verificar autenticação e tipo de usuário
if (!AuthController::isAuthenticated() || !AuthController::isAdmin()) {
    header('Location: ' . LOGIN_URL);
    exit;
}

// Obter ID da transação da URL
$transactionId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($transactionId <= 0) {
    header('Location: ' . ADMIN_TRANSACTIONS_URL);
    exit;
}

$activeMenu = 'transacoes';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Transação - Klube Cash Admin</title>
    <link rel="stylesheet" href="../../assets/css/views/admin/dashboard.css">
</head>
<body>
    <?php include '../components/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="dashboard-wrapper">
            <!-- Cabeçalho -->
            <div class="dashboard-header">
                <h1>Detalhes da Transação #<span id="transaction-number"><?php echo $transactionId; ?></span></h1>
                <div class="action-buttons">
                    <button class="btn btn-secondary" onclick="history.back()">
                        <i class="icon-arrow-left"></i> Voltar
                    </button>
                    <button class="btn btn-primary" id="btn-edit-status" onclick="editTransactionStatus()">
                        <i class="icon-edit"></i> Alterar Status
                    </button>
                </div>
            </div>

            <!-- Loading -->
            <div id="loading" class="loading-container" style="display: none;">
                <div class="loading-spinner"></div>
            </div>

            <!-- Conteúdo principal -->
            <div id="transaction-content" style="display: none;">
                
                <!-- Cards de informações básicas -->
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-card-title">Valor Total</div>
                        <div class="stat-card-value" id="valor-total">--</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-title">Cashback Cliente</div>
                        <div class="stat-card-value" id="cashback-cliente">--</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-title">Comissão Admin</div>
                        <div class="stat-card-value" id="comissao-admin">--</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-title">Status</div>
                        <div class="stat-card-value">
                            <span id="status-badge" class="status-badge">--</span>
                        </div>
                    </div>
                </div>

                <!-- Duas colunas de informações -->
                <div class="two-column-layout">
                    <!-- Informações da Transação -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Informações da Transação</h3>
                        </div>
                        <div class="transaction-details">
                            <div class="detail-row">
                                <span class="detail-label">ID da Transação:</span>
                                <span class="detail-value" id="detail-id">--</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Código da Transação:</span>
                                <span class="detail-value" id="detail-codigo">--</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Data da Transação:</span>
                                <span class="detail-value" id="detail-data">--</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Descrição:</span>
                                <span class="detail-value" id="detail-descricao">--</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Status Atual:</span>
                                <span class="detail-value">
                                    <span id="detail-status" class="status-badge">--</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Informações de Valores -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Distribuição de Valores</h3>
                        </div>
                        <div class="value-distribution">
                            <div class="detail-row">
                                <span class="detail-label">Valor Original da Venda:</span>
                                <span class="detail-value" id="valor-original">--</span>
                            </div>
                            <div class="detail-row has-balance" id="saldo-row" style="display: none;">
                                <span class="detail-label">Saldo Usado pelo Cliente:</span>
                                <span class="detail-value text-warning" id="saldo-usado">--</span>
                            </div>
                            <div class="detail-row has-balance" id="valor-liquido-row" style="display: none;">
                                <span class="detail-label">Valor Efetivamente Pago:</span>
                                <span class="detail-value" id="valor-liquido">--</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Total de Cashback (10%):</span>
                                <span class="detail-value" id="total-cashback">--</span>
                            </div>
                            <div class="detail-row indent">
                                <span class="detail-label">• Cashback para Cliente (5%):</span>
                                <span class="detail-value text-success" id="cashback-detail">--</span>
                            </div>
                            <div class="detail-row indent">
                                <span class="detail-label">• Comissão da Klube Cash (5%):</span>
                                <span class="detail-value text-primary" id="comissao-detail">--</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informações do Cliente e Loja -->
                <div class="two-column-layout">
                    <!-- Dados do Cliente -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Dados do Cliente</h3>
                        </div>
                        <div class="client-info">
                            <div class="detail-row">
                                <span class="detail-label">Nome:</span>
                                <span class="detail-value" id="cliente-nome">--</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Email:</span>
                                <span class="detail-value" id="cliente-email">--</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">ID do Cliente:</span>
                                <span class="detail-value" id="cliente-id">--</span>
                            </div>
                        </div>
                    </div>

                    <!-- Dados da Loja -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Dados da Loja</h3>
                        </div>
                        <div class="store-info">
                            <div class="detail-row">
                                <span class="detail-label">Nome Fantasia:</span>
                                <span class="detail-value" id="loja-nome">--</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Razão Social:</span>
                                <span class="detail-value" id="loja-razao">--</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">ID da Loja:</span>
                                <span class="detail-value" id="loja-id">--</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Histórico de Status -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Histórico de Status</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table" id="status-history-table">
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Status Anterior</th>
                                    <th>Status Novo</th>
                                    <th>Observação</th>
                                    <th>Alterado por</th>
                                </tr>
                            </thead>
                            <tbody id="status-history-content">
                                <!-- Conteúdo carregado dinamicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Mensagem de erro -->
            <div id="error-message" class="card" style="display: none;">
                <div class="notification-empty text-center">
                    <h3>Transação não encontrada</h3>
                    <p>A transação solicitada não foi encontrada ou você não tem permissão para acessá-la.</p>
                    <button class="btn btn-primary" onclick="history.back()">Voltar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para alterar status -->
    <div id="edit-status-modal" class="modal" style="display: none;">
        <div class="modal-overlay" onclick="closeModal()"></div>
        <div class="modal-container">
            <div class="modal-header">
                <h3>Alterar Status da Transação</h3>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>
            <div class="modal-body">
                <form id="status-form">
                    <div class="form-group">
                        <label for="new-status">Novo Status:</label>
                        <select id="new-status" name="status" class="form-control" required>
                            <option value="">Selecione...</option>
                            <option value="pendente">Pendente</option>
                            <option value="aprovado">Aprovado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status-observacao">Observação:</label>
                        <textarea id="status-observacao" name="observacao" 
                                  class="form-control" rows="3" 
                                  placeholder="Motivo da alteração (opcional)"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                <button class="btn btn-primary" onclick="updateStatus()">Confirmar</button>
            </div>
        </div>
    </div>

    <script>
        let transactionData = null;

        // Carregar dados da transação
        document.addEventListener('DOMContentLoaded', function() {
            loadTransactionDetails();
        });

        async function loadTransactionDetails() {
            const loadingElement = document.getElementById('loading');
            const contentElement = document.getElementById('transaction-content');
            const errorElement = document.getElementById('error-message');

            try {
                loadingElement.style.display = 'block';
                contentElement.style.display = 'none';
                errorElement.style.display = 'none';

                const response = await fetch('../../controllers/AdminController.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=transaction_details_with_balance&transaction_id=' + <?php echo $transactionId; ?>
                });

                const data = await response.json();

                if (data.status) {
                    transactionData = data.data;
                    populateTransactionDetails(transactionData);
                    contentElement.style.display = 'block';
                } else {
                    throw new Error(data.message || 'Erro ao carregar transação');
                }
            } catch (error) {
                console.error('Erro:', error);
                errorElement.style.display = 'block';
            } finally {
                loadingElement.style.display = 'none';
            }
        }

        function populateTransactionDetails(data) {
            // Função para formatar valores monetários
            const formatMoney = (value) => {
                return 'R$ ' + parseFloat(value || 0).toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            };

            // Função para formatar data
            const formatDate = (dateString) => {
                const date = new Date(dateString);
                return date.toLocaleString('pt-BR');
            };

            // Função para obter classe do status
            const getStatusClass = (status) => {
                switch (status) {
                    case 'aprovado': return 'status-approved';
                    case 'pendente': return 'status-pending';
                    case 'cancelado': return 'status-cancelled';
                    case 'pagamento_pendente': return 'status-payment-pending';
                    default: return 'status-pending';
                }
            };

            // Função para obter label do status
            const getStatusLabel = (status) => {
                switch (status) {
                    case 'aprovado': return 'Aprovado';
                    case 'pendente': return 'Pendente';
                    case 'cancelado': return 'Cancelado';
                    case 'pagamento_pendente': return 'Pagamento Pendente';
                    default: return status;
                }
            };

            // Popular cards superiores
            document.getElementById('valor-total').textContent = formatMoney(data.valor_total);
            document.getElementById('cashback-cliente').textContent = formatMoney(data.valor_cliente);
            document.getElementById('comissao-admin').textContent = formatMoney(data.valor_admin);
            
            const statusBadges = document.querySelectorAll('#status-badge, #detail-status');
            statusBadges.forEach(badge => {
                badge.textContent = getStatusLabel(data.status);
                badge.className = 'status-badge ' + getStatusClass(data.status);
            });

            // Popular informações da transação
            document.getElementById('detail-id').textContent = data.id;
            document.getElementById('detail-codigo').textContent = data.codigo_transacao || 'N/A';
            document.getElementById('detail-data').textContent = formatDate(data.data_transacao);
            document.getElementById('detail-descricao').textContent = data.descricao || 'N/A';

            // Popular distribuição de valores
            document.getElementById('valor-original').textContent = formatMoney(data.valor_total);
            document.getElementById('total-cashback').textContent = formatMoney(data.valor_cashback);
            document.getElementById('cashback-detail').textContent = formatMoney(data.valor_cliente);
            document.getElementById('comissao-detail').textContent = formatMoney(data.valor_admin);

            // Exibir informações de saldo se houver
            if (data.saldo_usado && parseFloat(data.saldo_usado) > 0) {
                const valorLiquido = parseFloat(data.valor_total) - parseFloat(data.saldo_usado);
                document.getElementById('saldo-usado').textContent = formatMoney(data.saldo_usado);
                document.getElementById('valor-liquido').textContent = formatMoney(valorLiquido);
                document.getElementById('saldo-row').style.display = 'flex';
                document.getElementById('valor-liquido-row').style.display = 'flex';
            }

            // Popular dados do cliente
            document.getElementById('cliente-nome').textContent = data.cliente_nome || 'N/A';
            document.getElementById('cliente-email').textContent = data.cliente_email || 'N/A';
            document.getElementById('cliente-id').textContent = data.usuario_id;

            // Popular dados da loja
            document.getElementById('loja-nome').textContent = data.loja_nome || 'N/A';
            document.getElementById('loja-razao').textContent = data.loja_razao_social || 'N/A';
            document.getElementById('loja-id').textContent = data.loja_id;

            // Popular histórico de status
            if (data.historico_status && data.historico_status.length > 0) {
                const tbody = document.getElementById('status-history-content');
                tbody.innerHTML = data.historico_status.map(item => `
                    <tr>
                        <td>${formatDate(item.data_alteracao)}</td>
                        <td><span class="status-badge ${getStatusClass(item.status_anterior)}">${getStatusLabel(item.status_anterior)}</span></td>
                        <td><span class="status-badge ${getStatusClass(item.status_novo)}">${getStatusLabel(item.status_novo)}</span></td>
                        <td>${item.observacao || 'N/A'}</td>
                        <td>${item.usuario_nome || 'Sistema'}</td>
                    </tr>
                `).join('');
            } else {
                document.getElementById('status-history-content').innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center">Nenhum histórico disponível</td>
                    </tr>
                `;
            }
        }

        function editTransactionStatus() {
            if (!transactionData) return;
            
            const modal = document.getElementById('edit-status-modal');
            const statusSelect = document.getElementById('new-status');
            
            // Definir status atual como selecionado
            statusSelect.value = transactionData.status;
            
            modal.style.display = 'block';
        }

        function closeModal() {
            document.getElementById('edit-status-modal').style.display = 'none';
            document.getElementById('status-form').reset();
        }

        async function updateStatus() {
            const newStatus = document.getElementById('new-status').value;
            const observacao = document.getElementById('status-observacao').value;

            if (!newStatus) {
                alert('Selecione o novo status');
                return;
            }

            if (newStatus === transactionData.status) {
                alert('O status selecionado é o mesmo status atual');
                return;
            }

            try {
                const response = await fetch('../../controllers/AdminController.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=update_transaction_status&transaction_id=${transactionData.id}&status=${newStatus}&observacao=${encodeURIComponent(observacao)}`
                });

                const data = await response.json();

                if (data.status) {
                    alert('Status atualizado com sucesso!');
                    closeModal();
                    // Recarregar os dados
                    loadTransactionDetails();
                } else {
                    throw new Error(data.message || 'Erro ao atualizar status');
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao atualizar status: ' + error.message);
            }
        }

        // Fechar modal clicando fora dele
        window.onclick = function(event) {
            const modal = document.getElementById('edit-status-modal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>

    <style>
        /* Estilos específicos para esta página */
        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: var(--medium-gray);
            flex: 1;
        }

        .detail-value {
            flex: 1;
            text-align: right;
            font-weight: 500;
        }

        .detail-row.indent .detail-label {
            padding-left: 20px;
            color: var(--medium-gray);
            font-size: 0.9em;
        }

        .detail-row.has-balance {
            background-color: #fff9e6;
            padding: 12px;
            margin: 5px -10px;
            border-radius: 8px;
            border: 1px solid #ffd700;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-approved { background-color: #d4edda; color: #155724; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
        .status-payment-pending { background-color: #d1ecf1; color: #0c5460; }

        .text-success { color: var(--success-color); }
        .text-warning { color: var(--warning-color); }
        .text-primary { color: var(--primary-color); }

        .loading-container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 50px;
        }

        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Modal */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-container {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
            z-index: 1001;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid #eee;
        }

        .modal-header h3 {
            margin: 0;
            color: var(--dark-gray);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--medium-gray);
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            color: var(--dark-gray);
        }

        .modal-body {
            padding: 24px;
        }

        .modal-footer {
            padding: 20px 24px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--dark-gray);
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 122, 0, 0.1);
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
                align-items: stretch;
            }

            .two-column-layout {
                grid-template-columns: 1fr;
            }

            .detail-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
            }

            .detail-value {
                text-align: left;
            }

            .modal-container {
                width: 95%;
                margin: 20px;
            }
        }
    </style>
</body>
</html>