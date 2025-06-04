<?php
// views/client/refund-status.php - Nova página para cliente ver suas devoluções
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';

session_start();

if (!AuthController::isAuthenticated()) {
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

$activeMenu = 'refunds';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Devoluções - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/client.css">
</head>
<body>
    <?php include_once '../components/sidebar-client.php'; ?>
    
    <div class="main-content" id="mainContent">
        <div class="dashboard-header">
            <h1>Status das Devoluções</h1>
            <p class="subtitle">Acompanhe suas solicitações de devolução</p>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Minhas Devoluções</h2>
                <button class="btn btn-primary" onclick="checkAllRefunds()">Atualizar Status</button>
            </div>
            
            <div class="table-responsive">
                <table class="table" id="refundsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data</th>
                            <th>Valor</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Carregado via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal de Detalhes -->
    <div id="detailsModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detalhes da Devolução</h3>
                <span class="close" onclick="closeDetailsModal()">&times;</span>
            </div>
            <div class="modal-body" id="refundDetails">
                <!-- Carregado via JS -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeDetailsModal()">Fechar</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadMyRefunds();
        });
        
        async function loadMyRefunds() {
            try {
                const response = await fetch('../../api/refunds.php?action=list');
                const result = await response.json();
                
                if (result.status) {
                    displayRefunds(result.data);
                } else {
                    showNotification('Erro ao carregar devoluções: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                showNotification('Erro de conexão', 'error');
            }
        }
        
        function displayRefunds(refunds) {
            const tbody = document.querySelector('#refundsTable tbody');
            tbody.innerHTML = '';
            
            if (refunds.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">Nenhuma devolução encontrada</td></tr>';
                return;
            }
            
            refunds.forEach(refund => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>#${refund.id}</td>
                    <td>${formatDate(refund.data_solicitacao)}</td>
                    <td>R$ ${formatMoney(refund.valor_devolucao)}</td>
                    <td><span class="badge ${refund.tipo}">${refund.tipo}</span></td>
                    <td><span class="status ${refund.status}">${getStatusText(refund.status)}</span></td>
                    <td>
                        <button class="btn btn-small" onclick="viewRefundDetails(${refund.id})">Detalhes</button>
                        <button class="btn btn-small btn-info" onclick="checkRefundStatus(${refund.id})">Verificar Status</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
        
        // AQUI ESTÁ O CÓDIGO 3 - VERIFICAR STATUS
        async function checkRefundStatus(refundId) {
            try {
                // ESTE É O CÓDIGO DE EXEMPLO 3 EM USO REAL
                const response = await fetch(`../../api/refunds.php?action=status&refund_id=${refundId}`);
                const result = await response.json();
                
                if (result.status) {
                    const refund = result.data;
                    
                    // Mostrar status atualizado
                    showNotification(
                        `Status da devolução #${refund.id}: ${getStatusText(refund.status)}`, 
                        refund.status === 'aprovado' ? 'success' : 'info'
                    );
                    
                    // Recarregar lista para mostrar status atualizado
                    loadMyRefunds();
                } else {
                    showNotification('Erro ao verificar status: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                showNotification('Erro de conexão', 'error');
            }
        }
        
        async function checkAllRefunds() {
            showNotification('Verificando status de todas as devoluções...', 'info');
            
            // Recarregar lista completa
            await loadMyRefunds();
            
            showNotification('Status atualizado!', 'success');
        }
        
        async function viewRefundDetails(refundId) {
            try {
                const response = await fetch(`../../api/refunds.php?action=status&refund_id=${refundId}`);
                const result = await response.json();
                
                if (result.status) {
                    const refund = result.data;
                    
                    document.getElementById('refundDetails').innerHTML = `
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>ID da Devolução:</label>
                                <span>#${refund.id}</span>
                            </div>
                            <div class="detail-item">
                                <label>Status Atual:</label>
                                <span class="status ${refund.status}">${getStatusText(refund.status)}</span>
                            </div>
                            <div class="detail-item">
                                <label>Valor Solicitado:</label>
                                <span>R$ ${formatMoney(refund.valor_devolucao)}</span>
                            </div>
                            <div class="detail-item">
                                <label>Tipo:</label>
                                <span>${refund.tipo}</span>
                            </div>
                            <div class="detail-item">
                                <label>Data da Solicitação:</label>
                                <span>${formatDate(refund.data_solicitacao)}</span>
                            </div>
                            ${refund.data_processamento ? `
                                <div class="detail-item">
                                    <label>Data do Processamento:</label>
                                    <span>${formatDate(refund.data_processamento)}</span>
                                </div>
                            ` : ''}
                            <div class="detail-item full-width">
                                <label>Motivo:</label>
                                <span>${refund.motivo}</span>
                            </div>
                            ${refund.observacao_admin ? `
                                <div class="detail-item full-width">
                                    <label>Observação do Administrador:</label>
                                    <span>${refund.observacao_admin}</span>
                                </div>
                            ` : ''}
                        </div>
                    `;
                    
                    document.getElementById('detailsModal').style.display = 'block';
                } else {
                    showNotification('Erro ao carregar detalhes: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                showNotification('Erro de conexão', 'error');
            }
        }
        
        function closeDetailsModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }
        
        function formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('pt-BR');
        }
        
        function formatMoney(value) {
            return parseFloat(value).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
        
        function getStatusText(status) {
            const statusMap = {
                'solicitado': 'Solicitado',
                'processando': 'Processando',
                'aprovado': 'Aprovado',
                'rejeitado': 'Rejeitado',
                'erro': 'Erro'
            };
            return statusMap[status] || status;
        }
        
        function showNotification(message, type) {
            // Implementar sistema de notificações ou usar alert temporariamente
            alert(message);
        }
    </script>
</body>
</html>