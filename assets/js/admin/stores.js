// assets/js/admin/stores.js

// Variáveis globais
let currentStoreId = null;
let selectedStores = new Set();

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    loadInitialData();
});

// Event Listeners
function initializeEventListeners() {
    // Modal events
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            hideAllModals();
        }
    });
    
    // Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideAllModals();
        }
    });
    
    // Form validation
    const storeForm = document.getElementById('storeForm');
    if (storeForm) {
        storeForm.addEventListener('submit', handleStoreFormSubmit);
    }
}

// Carregar dados iniciais
function loadInitialData() {
    // Pode ser usado para carregar dados adicionais se necessário
    console.log('Página de lojas carregada');
}

// ========== FUNÇÕES DE MODAL ==========

function showStoreModal(storeId = null) {
    currentStoreId = storeId;
    const modal = document.getElementById('storeModal');
    const title = document.getElementById('storeModalTitle');
    const form = document.getElementById('storeForm');
    
    // Reset form
    form.reset();
    document.getElementById('storeId').value = '';
    
    if (storeId) {
        title.textContent = 'Editar Loja';
        loadStoreData(storeId);
    } else {
        title.textContent = 'Nova Loja';
        // Set default values
        document.getElementById('porcentagemCashback').value = '10.00';
        document.getElementById('categoria').value = 'Outros';
        document.getElementById('status').value = 'pendente';
    }
    
    showModal(modal);
}

function hideStoreModal() {
    hideModal(document.getElementById('storeModal'));
    currentStoreId = null;
}

function showStoreDetailsModal(storeId) {
    currentStoreId = storeId;
    const modal = document.getElementById('storeDetailsModal');
    const content = document.getElementById('storeDetailsContent');
    
    content.innerHTML = '<div class="loading">Carregando detalhes...</div>';
    showModal(modal);
    
    loadStoreDetails(storeId);
}

function hideStoreDetailsModal() {
    hideModal(document.getElementById('storeDetailsModal'));
    currentStoreId = null;
}

function showModal(modal) {
    modal.style.display = 'flex';
    // Force reflow
    modal.offsetHeight;
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function hideModal(modal) {
    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }, 300);
}

function hideAllModals() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => hideModal(modal));
}

// ========== FUNÇÕES DE DADOS ==========

function loadStoreData(storeId) {
    makeRequest('store_details', { store_id: storeId })
        .then(data => {
            if (data.status && data.data && data.data.loja) {
                populateStoreForm(data.data.loja);
            } else {
                showNotification('Erro ao carregar dados da loja', 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showNotification('Erro ao carregar dados da loja', 'error');
        });
}

function loadStoreDetails(storeId) {
    // Primeiro testar a conexão
    console.log('Testando conexão antes de carregar detalhes...');
    
    makeRequest('test_store_connection')
        .then(testData => {
            console.log('Teste de conexão:', testData);
            
            // Se o teste passou, carregar os detalhes
            return makeRequest('store_details', { store_id: storeId });
        })
        .then(data => {
            console.log('Dados da loja recebidos:', data);
            
            if (data.status && data.data && data.data.loja) {
                renderStoreDetailsSimple(data.data);
            } else {
                document.getElementById('storeDetailsContent').innerHTML = 
                    `<div class="alert alert-danger">Erro: ${data.message || 'Dados não encontrados'}</div>`;
            }
        })
        .catch(error => {
            console.error('Erro completo:', error);
            document.getElementById('storeDetailsContent').innerHTML = 
                `<div class="alert alert-danger">Erro ao carregar detalhes: ${error.message}</div>`;
        });
}

function populateStoreForm(store) {
    const fields = [
        'nome_fantasia', 'razao_social', 'cnpj', 'email', 'telefone',
        'categoria', 'porcentagem_cashback', 'status'
    ];
    
    document.getElementById('storeId').value = store.id;
    
    fields.forEach(field => {
        const element = document.getElementById(field.replace('_', ''));
        if (element && store[field] !== undefined) {
            element.value = store[field];
        }
    });
}

// Função simplificada para renderizar detalhes
function renderStoreDetailsSimple(data) {
    const store = data.loja;
    const stats = data.estatisticas || {};
    
    const statusInfo = getStatusInfo(store.status);
    
    const html = `
        <div class="store-details-content">
            <div class="details-section">
                <h4>Informações Gerais</h4>
                <div class="details-grid">
                    <div class="detail-item">
                        <label>Nome Fantasia:</label>
                        <span>${escapeHtml(store.nome_fantasia)}</span>
                    </div>
                    <div class="detail-item">
                        <label>Razão Social:</label>
                        <span>${escapeHtml(store.razao_social)}</span>
                    </div>
                    <div class="detail-item">
                        <label>CNPJ:</label>
                        <span>${formatCNPJ(store.cnpj)}</span>
                    </div>
                    <div class="detail-item">
                        <label>Email:</label>
                        <span>${escapeHtml(store.email)}</span>
                    </div>
                    <div class="detail-item">
                        <label>Telefone:</label>
                        <span>${formatPhone(store.telefone)}</span>
                    </div>
                    <div class="detail-item">
                        <label>Categoria:</label>
                        <span>${escapeHtml(store.categoria || 'Não definida')}</span>
                    </div>
                    <div class="detail-item">
                        <label>Status:</label>
                        <span class="status-badge ${statusInfo.class}">${statusInfo.text}</span>
                    </div>
                    <div class="detail-item">
                        <label>Cashback:</label>
                        <span>${store.porcentagem_cashback}%</span>
                    </div>
                </div>
            </div>
            
            <div class="details-section">
                <h4>Estatísticas</h4>
                <div class="stats-mini-grid">
                    <div class="stat-mini">
                        <div class="stat-mini-value">${stats.total_transacoes || 0}</div>
                        <div class="stat-mini-label">Total de Transações</div>
                    </div>
                    <div class="stat-mini">
                        <div class="stat-mini-value">R$ ${formatMoney(stats.total_vendas || 0)}</div>
                        <div class="stat-mini-label">Total de Vendas</div>
                    </div>
                    <div class="stat-mini">
                        <div class="stat-mini-value">R$ ${formatMoney(stats.total_cashback || 0)}</div>
                        <div class="stat-mini-label">Total de Cashback</div>
                    </div>
                </div>
            </div>
            
            <div class="details-section">
                <h4>Datas</h4>
                <div class="details-grid">
                    <div class="detail-item">
                        <label>Data de Cadastro:</label>
                        <span>${formatDateTime(store.data_cadastro)}</span>
                    </div>
                    ${store.data_aprovacao ? `
                        <div class="detail-item">
                            <label>Data de Aprovação:</label>
                            <span>${formatDateTime(store.data_aprovacao)}</span>
                        </div>
                    ` : ''}
                </div>
            </div>
            
            ${store.observacao ? `
                <div class="details-section">
                    <h4>Observações</h4>
                    <p class="observation-text">${escapeHtml(store.observacao)}</p>
                </div>
            ` : ''}
            
            ${store.status === 'pendente' ? `
                <div class="details-section">
                    <h4>Ações</h4>
                    <div class="action-group">
                        <button class="btn btn-success" onclick="approveStore(${store.id})">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Aprovar Loja
                        </button>
                        <button class="btn btn-danger" onclick="rejectStore(${store.id})">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                            Rejeitar Loja
                        </button>
                    </div>
                </div>
            ` : ''}
        </div>
    `;
    
    document.getElementById('storeDetailsContent').innerHTML = html;
    document.getElementById('storeDetailsTitle').textContent = store.nome_fantasia;
}

// ========== FUNÇÕES DE FORMULÁRIO ==========

function handleStoreFormSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const isEditing = document.getElementById('storeId').value !== '';
    
    // Validate form
    if (!validateStoreForm(formData)) {
        return;
    }
    
    // Add action
    formData.append('action', isEditing ? 'update_store' : 'create_store');
    if (isEditing) {
        formData.append('store_id', document.getElementById('storeId').value);
    }
    
    // Submit
    submitStoreForm(formData, isEditing);
}

function validateStoreForm(formData) {
    const requiredFields = ['nome_fantasia', 'razao_social', 'cnpj', 'email', 'telefone'];
    
    for (let field of requiredFields) {
        if (!formData.get(field)?.trim()) {
            showNotification(`Campo "${getFieldLabel(field)}" é obrigatório`, 'error');
            return false;
        }
    }
    
    // Validate CNPJ
    const cnpj = formData.get('cnpj').replace(/\D/g, '');
    if (cnpj.length !== 14) {
        showNotification('CNPJ deve ter 14 dígitos', 'error');
        return false;
    }
    
    // Validate email
    const email = formData.get('email');
    if (!isValidEmail(email)) {
        showNotification('Email inválido', 'error');
        return false;
    }
    
    return true;
}
function debugStoreDetails(storeId) {
    console.log('Debug: Testando conexão...');
    
    makeRequest('test_connection')
        .then(data => {
            console.log('Conexão OK:', data);
            
            // Agora testar detalhes da loja
            return makeRequest('store_details', { store_id: storeId });
        })
        .then(data => {
            console.log('Detalhes da loja:', data);
        })
        .catch(error => {
            console.error('Erro de debug:', error);
        });
}
function submitStoreForm(formData, isEditing) {
    const submitButton = document.querySelector('#storeForm button[type="submit"]');
    const originalText = submitButton.textContent;
    
    // Loading state
    submitButton.disabled = true;
    submitButton.textContent = 'Salvando...';
    
    makeRequest(formData.get('action'), formData, 'POST')
        .then(data => {
            if (data.status) {
                showNotification(
                    data.message || (isEditing ? 'Loja atualizada com sucesso!' : 'Loja criada com sucesso!'),
                    'success'
                );
                hideStoreModal();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showNotification(data.message || 'Erro ao salvar loja', 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showNotification('Erro ao salvar loja', 'error');
        })
        .finally(() => {
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        });
}

// ========== FUNÇÕES DE AÇÃO ==========

function viewStoreDetails(storeId) {
    showStoreDetailsModal(storeId);
}

function editStore() {
    if (currentStoreId) {
        hideStoreDetailsModal();
        showStoreModal(currentStoreId);
    }
}

function approveStore(storeId) {
    if (confirm('Tem certeza que deseja aprovar esta loja?')) {
        updateStoreStatus(storeId, 'aprovado');
    }
}

function rejectStore(storeId) {
    const observacao = prompt('Digite o motivo da rejeição (opcional):') || '';
    updateStoreStatus(storeId, 'rejeitado', observacao);
}

function updateStoreStatus(storeId, status, observacao = '') {
    makeRequest('update_store_status', {
        store_id: storeId,
        status: status,
        observacao: observacao
    })
    .then(data => {
        if (data.status) {
            showNotification(data.message || 'Status atualizado com sucesso!', 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showNotification(data.message || 'Erro ao atualizar status', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showNotification('Erro ao atualizar status', 'error');
    });
}

// ========== FUNÇÕES DE SELEÇÃO ==========

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.store-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
        if (selectAll.checked) {
            selectedStores.add(parseInt(checkbox.value));
        } else {
            selectedStores.delete(parseInt(checkbox.value));
        }
    });
    
    updateBulkActions();
}

function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.store-checkbox');
    const checkedBoxes = document.querySelectorAll('.store-checkbox:checked');
    const selectAll = document.getElementById('selectAll');
    const bulkApproveBtn = document.getElementById('bulkApproveBtn');
    
    // Update selectedStores set
    selectedStores.clear();
    checkedBoxes.forEach(checkbox => {
        selectedStores.add(parseInt(checkbox.value));
    });
    
    // Update select all checkbox
    if (checkedBoxes.length === 0) {
        selectAll.indeterminate = false;
        selectAll.checked = false;
    } else if (checkedBoxes.length === checkboxes.length) {
        selectAll.indeterminate = false;
        selectAll.checked = true;
    } else {
        selectAll.indeterminate = true;
    }
    
    // Show/hide bulk actions
    if (bulkApproveBtn) {
        bulkApproveBtn.style.display = selectedStores.size > 0 ? 'inline-flex' : 'none';
    }
}

function bulkApprove() {
    if (selectedStores.size === 0) {
        showNotification('Selecione pelo menos uma loja', 'warning');
        return;
    }
    
    if (confirm(`Aprovar ${selectedStores.size} loja(s) selecionada(s)?`)) {
        const promises = Array.from(selectedStores).map(storeId => 
            makeRequest('update_store_status', {
                store_id: storeId,
                status: 'aprovado'
            })
        );
        
        Promise.all(promises)
            .then(results => {
                const successful = results.filter(r => r.status).length;
                showNotification(`${successful} loja(s) aprovada(s) com sucesso!`, 'success');
                setTimeout(() => window.location.reload(), 1000);
            })
            .catch(error => {
                console.error('Erro:', error);
                showNotification('Erro ao aprovar lojas', 'error');
            });
    }
}

// ========== FUNÇÕES UTILITÁRIAS ==========

function makeRequest(action, data = {}, method = 'POST') {
    // Usar a rota AJAX específica
     const url = '/admin/ajax/stores-direct';
    
    let body;
    let headers = {};
    
    if (data instanceof FormData) {
        data.append('action', action);
        body = data;
    } else {
        headers['Content-Type'] = 'application/x-www-form-urlencoded';
        const params = new URLSearchParams();
        params.append('action', action);
        
        Object.keys(data).forEach(key => {
            params.append(key, data[key]);
        });
        
        body = params.toString();
    }
    
    console.log('Making request to:', url, 'Action:', action);
    
    return fetch(url, {
        method: method,
        headers: headers,
        body: body,
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Response status:', response.status, response.statusText);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.text();
    })
    .then(text => {
        console.log('Response received, length:', text.length);
        console.log('First 200 chars:', text.substring(0, 200));
        
        // Verificar se a resposta começa com HTML
        if (text.trim().startsWith('<!DOCTYPE') || text.trim().startsWith('<html')) {
            console.error('Received HTML instead of JSON:', text.substring(0, 500));
            throw new Error('Servidor retornou HTML em vez de JSON - verifique autenticação');
        }
        
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Response is not JSON:', text);
            throw new Error('Resposta inválida do servidor');
        }
    });
}

function showNotification(message, type = 'info') {
    // Criar notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-message">${escapeHtml(message)}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto remove
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatMoney(value) {
    return new Intl.NumberFormat('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value);
}

function formatCNPJ(cnpj) {
    return cnpj.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
}

function formatPhone(phone) {
    const cleaned = phone.replace(/\D/g, '');
    if (cleaned.length === 11) {
        return cleaned.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    } else if (cleaned.length === 10) {
        return cleaned.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
    }
    return phone;
}

function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return dateString;
    }
}

function getStatusInfo(status) {
    const statusMap = {
        'aprovado': { class: 'status-approved', text: 'Aprovado' },
        'pendente': { class: 'status-pending', text: 'Pendente' },
        'rejeitado': { class: 'status-rejected', text: 'Rejeitado' }
    };
    
    return statusMap[status] || { class: 'status-unknown', text: status };
}

function getFieldLabel(field) {
    const labels = {
        'nome_fantasia': 'Nome Fantasia',
        'razao_social': 'Razão Social',
        'cnpj': 'CNPJ',
        'email': 'Email',
        'telefone': 'Telefone'
    };
    
    return labels[field] || field;
}

function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// CSS adicional para notificações (adicionar ao CSS)
const notificationStyles = `
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    z-index: 1001;
    min-width: 300px;
    animation: slideInRight 0.3s ease;
}

.notification-success {
    border-left: 4px solid var(--success-color);
}

.notification-error {
    border-left: 4px solid var(--danger-color);
}

.notification-warning {
    border-left: 4px solid var(--warning-color);
}

.notification-info {
    border-left: 4px solid var(--info-color);
}

.notification-content {
    padding: 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
}

.notification-message {
    flex: 1;
    font-size: 14px;
    color: var(--text-color);
}

.notification-close {
    background: none;
    border: none;
    padding: 4px;
    cursor: pointer;
    border-radius: 4px;
    transition: var(--transition);
}

.notification-close:hover {
    background-color: var(--border-color);
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(100%);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Estilos adicionais para detalhes */
.store-details-content {
    max-height: 60vh;
    overflow-y: auto;
}

.details-section {
    margin-bottom: 24px;
}

.details-section h4 {
    color: var(--primary-color);
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 2px solid var(--primary-light);
    font-size: 16px;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.detail-item label {
    font-weight: 600;
    color: var(--text-muted);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-item span {
    color: var(--text-color);
    font-size: 14px;
}

.stats-mini-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
}

.stat-mini {
    background: var(--light-gray);
    padding: 16px;
    border-radius: var(--border-radius);
    text-align: center;
}

.stat-mini-value {
    font-size: 18px;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 4px;
}

.stat-mini-label {
    font-size: 12px;
    color: var(--text-muted);
}

.observation-text {
    background: var(--light-gray);
    padding: 16px;
    border-radius: var(--border-radius);
    font-style: italic;
    color: var(--text-color);
    line-height: 1.6;
}

.action-group {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}
`;

// Inject notification styles
if (!document.getElementById('notification-styles')) {
    const style = document.createElement('style');
    style.id = 'notification-styles';
    style.textContent = notificationStyles;
    document.head.appendChild(style);
}


// Função para testar AJAX - adicionar no final do arquivo
function testAjaxConnection() {
    console.log('Testando conexão AJAX...');
    
    makeRequest('test_ajax')
        .then(data => {
            console.log('✅ AJAX funcionando:', data);
            showNotification('Conexão AJAX funcionando!', 'success');
        })
        .catch(error => {
            console.error('❌ Erro AJAX:', error);
            showNotification('Erro na conexão AJAX: ' + error.message, 'error');
        });
}

// Executar teste ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(testAjaxConnection, 1000); // Testar após 1 segundo
});







// Função para testar conexão - adicionar no final do arquivo
function debugConnection() {
    console.log('🔍 Testando conexão com servidor...');
    
    makeRequest('test_connection')
        .then(data => {
            console.log('✅ Conexão OK:', data);
            showNotification('Conexão funcionando!', 'success');
        })
        .catch(error => {
            console.error('❌ Erro de conexão:', error);
            showNotification('Erro de conexão: ' + error.message, 'error');
        });
}

// Executar teste automaticamente se houver problemas
document.addEventListener('DOMContentLoaded', function() {
    // Verificar se a página carregou com erro
    const hasError = document.querySelector('.alert-danger');
    if (hasError) {
        console.log('⚠️ Erro detectado na página, testando conexão...');
        setTimeout(debugConnection, 2000);
    }
});