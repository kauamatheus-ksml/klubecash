/**
 * JAVASCRIPT DA PÁGINA FINANCEIRO - LOJISTA
 * =========================================
 * 
 * Gerencia todas as funcionalidades da página consolidada:
 * - Sistema de abas
 * - Carregamento de dados via AJAX
 * - Modais
 * - Filtros
 * - Seleção de transações
 * - Processamento de pagamentos
 */

// ===== VARIÁVEIS GLOBAIS =====
let currentTab = 'resumo';
let selectedTransactions = [];
let currentFilters = {};
let salesChart = null;

// ===== INICIALIZAÇÃO =====
document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando página Financeiro...');
    
    // Inicializar gráfico
    initializeSalesChart();
    
    // Carregar dados da primeira aba
    loadTabData('resumo');
    
    // Configurar eventos
    setupEventListeners();
});

// ===== CONFIGURAÇÃO DE EVENTOS =====
function setupEventListeners() {
    // Eventos para fechar modais ao clicar fora
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModal(e.target.id);
        }
    });
    
    // Eventos para tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });
}

// ===== SISTEMA DE ABAS =====
function showTab(tabName) {
    console.log('Mostrando aba:', tabName);
    
    // Atualizar botões das abas
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
    
    // Atualizar conteúdo das abas
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById(`tab-${tabName}`).classList.add('active');
    
    // Atualizar aba atual
    currentTab = tabName;
    
    // Carregar dados da aba
    loadTabData(tabName);
}

// ===== CARREGAMENTO DE DADOS =====
function loadTabData(tabName) {
    console.log('Carregando dados da aba:', tabName);
    
    switch(tabName) {
        case 'resumo':
            loadResumoData();
            break;
        case 'transacoes':
            loadTransacoesData();
            break;
        case 'comissoes':
            loadComissoesData();
            break;
        case 'historico':
            loadHistoricoData();
            break;
    }
}

// Carregar dados do resumo
function loadResumoData() {
    // Atualizar gráfico com dados dos últimos 6 meses
    fetch(`/api/store/financeiro/dashboard?store_id=${STORE_ID}`)
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                updateSalesChart(data.chart_data);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar dados do resumo:', error);
        });
}

// Carregar dados das transações
function loadTransacoesData() {
    const container = document.getElementById('transacoes-list');
    showLoadingState(container);
    
    const params = new URLSearchParams({
        store_id: STORE_ID,
        ...currentFilters
    });
    
    fetch(`/api/store/financeiro/transacoes?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                renderTransacoesList(data.data, container);
            } else {
                showErrorState(container, data.message);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar transações:', error);
            showErrorState(container, 'Erro ao carregar transações');
        });
}

// Carregar dados das comissões pendentes
function loadComissoesData() {
    const container = document.getElementById('comissoes-list');
    showLoadingState(container);
    
    const params = new URLSearchParams({
        store_id: STORE_ID,
        ...currentFilters
    });
    
    fetch(`/api/store/financeiro/comissoes?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                renderComissoesList(data.data, container);
            } else {
                showErrorState(container, data.message);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar comissões:', error);
            showErrorState(container, 'Erro ao carregar comissões');
        });
}

// Carregar dados do histórico
function loadHistoricoData() {
    const container = document.getElementById('historico-list');
    showLoadingState(container);
    
    const params = new URLSearchParams({
        store_id: STORE_ID,
        ...currentFilters
    });
    
    fetch(`/api/store/financeiro/historico?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                renderHistoricoList(data.data, container);
            } else {
                showErrorState(container, data.message);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar histórico:', error);
            showErrorState(container, 'Erro ao carregar histórico');
        });
}

// ===== RENDERIZAÇÃO DE LISTAS =====
function renderTransacoesList(data, container) {
    if (!data.transacoes || data.transacoes.length === 0) {
        showEmptyState(container, 'Nenhuma transação encontrada');
        return;
    }
    
    const html = `
        <div class="list-header">
            <div class="list-title">Transações (${data.transacoes.length})</div>
        </div>
        <div class="list-items">
            ${data.transacoes.map(transacao => `
                <div class="list-item" data-id="${transacao.id}">
                    <div class="item-content">
                        <div class="item-main">
                            <div class="item-title">Transação #${transacao.id}</div>
                            <div class="item-subtitle">Cliente: ${transacao.cliente_nome || transacao.cliente_email}</div>
                        </div>
                        <div class="item-details">
                            <div class="detail-item">
                                <span class="label">Valor:</span>
                                <span class="value">R$ ${formatCurrency(transacao.valor_total)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Cashback:</span>
                                <span class="value">R$ ${formatCurrency(transacao.valor_cashback)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Data:</span>
                                <span class="value">${formatDate(transacao.data_transacao)}</span>
                            </div>
                        </div>
                        <div class="item-status">
                            <span class="status-badge ${transacao.status}">${getStatusLabel(transacao.status)}</span>
                        </div>
                    </div>
                    <div class="item-actions">
                        <button class="btn-action secondary small" onclick="showTransactionDetails(${transacao.id})">
                            Detalhes
                        </button>
                    </div>
                </div>
            `).join('')}
        </div>
    `;
    
    container.innerHTML = html;
}

function renderComissoesList(data, container) {
    if (!data.transacoes || data.transacoes.length === 0) {
        showEmptyState(container, 'Nenhuma comissão pendente');
        return;
    }
    
    const html = `
        <div class="list-header">
            <div class="list-title">Comissões Pendentes (${data.transacoes.length})</div>
            <div class="select-all-container">
                <label class="checkbox-label">
                    <input type="checkbox" id="selectAllCommissions" onchange="toggleSelectAll()">
                    Selecionar Todas
                </label>
            </div>
        </div>
        <div class="list-items">
            ${data.transacoes.map(transacao => `
                <div class="list-item selectable" data-id="${transacao.id}">
                    <div class="item-selector">
                        <input type="checkbox" class="transaction-checkbox" 
                               value="${transacao.id}" 
                               data-amount="${transacao.valor_cashback}"
                               onchange="updateSelection()">
                    </div>
                    <div class="item-content">
                        <div class="item-main">
                            <div class="item-title">Transação #${transacao.id}</div>
                            <div class="item-subtitle">Cliente: ${transacao.cliente_nome || transacao.cliente_email}</div>
                        </div>
                        <div class="item-details">
                            <div class="detail-item">
                                <span class="label">Valor Venda:</span>
                                <span class="value">R$ ${formatCurrency(transacao.valor_total)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Saldo Usado:</span>
                                <span class="value">R$ ${formatCurrency(transacao.saldo_usado || 0)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Comissão (10%):</span>
                                <span class="value highlight">R$ ${formatCurrency(transacao.valor_cashback)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Data:</span>
                                <span class="value">${formatDate(transacao.data_transacao)}</span>
                            </div>
                        </div>
                    </div>
                    <div class="item-actions">
                        <button class="btn-action secondary small" onclick="showTransactionDetails(${transacao.id})">
                            Detalhes
                        </button>
                    </div>
                </div>
            `).join('')}
        </div>
    `;
    
    container.innerHTML = html;
}

function renderHistoricoList(data, container) {
    if (!data.pagamentos || data.pagamentos.length === 0) {
        showEmptyState(container, 'Nenhum pagamento encontrado');
        return;
    }
    
    const html = `
        <div class="list-header">
            <div class="list-title">Histórico de Pagamentos (${data.pagamentos.length})</div>
        </div>
        <div class="list-items">
            ${data.pagamentos.map(pagamento => `
                <div class="list-item" data-id="${pagamento.id}">
                    <div class="item-content">
                        <div class="item-main">
                            <div class="item-title">Pagamento #${pagamento.id}</div>
                            <div class="item-subtitle">${pagamento.metodo_pagamento_label} - ${pagamento.qtd_transacoes} transações</div>
                        </div>
                        <div class="item-details">
                            <div class="detail-item">
                                <span class="label">Valor Pago:</span>
                                <span class="value">R$ ${formatCurrency(pagamento.valor_total)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Data:</span>
                                <span class="value">${formatDate(pagamento.data_registro)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Referência:</span>
                                <span class="value">${pagamento.numero_referencia || 'N/A'}</span>
                            </div>
                        </div>
                        <div class="item-status">
                            <span class="status-badge ${pagamento.status}">${getStatusLabel(pagamento.status)}</span>
                        </div>
                    </div>
                    <div class="item-actions">
                        <button class="btn-action secondary small" onclick="showPaymentDetails(${pagamento.id})">
                            Detalhes
                        </button>
                    </div>
                </div>
            `).join('')}
        </div>
    `;
    
    container.innerHTML = html;
}

// ===== GRÁFICO DE VENDAS =====
function initializeSalesChart() {
    const ctx = document.getElementById('salesChart');
    if (!ctx) return;
    
    salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Vendas (R$)',
                data: [],
                backgroundColor: 'rgba(255, 122, 0, 0.1)',
                borderColor: 'rgba(255, 122, 0, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'R$ ' + value.toLocaleString('pt-BR');
                        }
                    }
                }
            }
        }
    });
}

function updateSalesChart(data) {
    if (!salesChart || !data) return;
    
    salesChart.data.labels = data.labels;
    salesChart.data.datasets[0].data = data.values;
    salesChart.update();
}

// ===== SELEÇÃO DE TRANSAÇÕES =====
function updateSelection() {
    const checkboxes = document.querySelectorAll('.transaction-checkbox:checked');
    selectedTransactions = Array.from(checkboxes).map(cb => ({
        id: parseInt(cb.value),
        amount: parseFloat(cb.dataset.amount)
    }));
    
    // Atualizar interface
    updateSelectionInfo();
    updatePayButton();
}

function updateSelectionInfo() {
    const info = document.getElementById('selectionInfo');
    const count = document.getElementById('selectedCount');
    const total = document.getElementById('selectedTotal');
    
    if (selectedTransactions.length > 0) {
        const totalAmount = selectedTransactions.reduce((sum, t) => sum + t.amount, 0);
        count.textContent = selectedTransactions.length;
        total.textContent = formatCurrency(totalAmount);
        info.style.display = 'flex';
    } else {
        info.style.display = 'none';
    }
}

function updatePayButton() {
    const btn = document.getElementById('paySelectedBtn');
    btn.disabled = selectedTransactions.length === 0;
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAllCommissions');
    const checkboxes = document.querySelectorAll('.transaction-checkbox');
    
    checkboxes.forEach(cb => {
        cb.checked = selectAll.checked;
    });
    
    updateSelection();
}

function clearSelection() {
    document.querySelectorAll('.transaction-checkbox').forEach(cb => {
        cb.checked = false;
    });
    
    const selectAll = document.getElementById('selectAllCommissions');
    if (selectAll) selectAll.checked = false;
    
    updateSelection();
}

// ===== MODAIS =====
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function closeAllModals() {
    document.querySelectorAll('.modal.active').forEach(modal => {
        modal.classList.remove('active');
    });
    document.body.style.overflow = '';
}

// Modal de filtros
function showFilterModal(tabType) {
    // Configurar formulário baseado no tipo de aba
    const form = document.getElementById('filterForm');
    form.dataset.tabType = tabType;
    
    // Preencher valores atuais
    Object.keys(currentFilters).forEach(key => {
        const input = form.querySelector(`[name="${key}"]`);
        if (input) input.value = currentFilters[key];
    });
    
    showModal('filterModal');
}

// Modal de pagamento
function openPaymentModal() {
    if (selectedTransactions.length === 0) {
        alert('Selecione pelo menos uma transação para pagar.');
        return;
    }
    
    // Calcular totais
    const totalCommission = selectedTransactions.reduce((sum, t) => sum + t.amount, 0);
    
    // Atualizar informações no modal
    document.getElementById('paymentTransactionCount').textContent = selectedTransactions.length;
    document.getElementById('paymentCommission').textContent = 'R$ ' + formatCurrency(totalCommission);
    
    // Preparar campo hidden
    document.getElementById('selectedTransactions').value = JSON.stringify(selectedTransactions.map(t => t.id));
    
    showModal('paymentModal');
}

// ===== PROCESSAMENTO DE PAGAMENTO =====
function processPayment() {
    const form = document.getElementById('paymentForm');
    const formData = new FormData(form);
    
    // Adicionar informações extras
    formData.append('store_id', STORE_ID);
    formData.append('transacoes', JSON.stringify(selectedTransactions.map(t => t.id)));
    
    // Mostrar loading
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<div class="spinner small"></div> Processando...';
    btn.disabled = true;
    
    fetch('/api/store/financeiro/processar-pagamento', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status) {
            // Sucesso
            alert('Pagamento registrado com sucesso! Aguarde a aprovação do administrador.');
            closeModal('paymentModal');
            clearSelection();
            loadTabData('comissoes'); // Recarregar comissões
            loadTabData('historico'); // Recarregar histórico
        } else {
            alert('Erro ao processar pagamento: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro ao processar pagamento:', error);
        alert('Erro ao processar pagamento. Tente novamente.');
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

// ===== FILTROS =====
function applyFilters() {
    const form = document.getElementById('filterForm');
    const formData = new FormData(form);
    
    // Converter para objeto
    currentFilters = {};
    for (let [key, value] of formData.entries()) {
        if (value.trim()) {
            currentFilters[key] = value.trim();
        }
    }
    
    // Recarregar dados da aba atual
    loadTabData(currentTab);
    
    closeModal('filterModal');
}

function clearFilters() {
    currentFilters = {};
    document.getElementById('filterForm').reset();
    loadTabData(currentTab);
    closeModal('filterModal');
}

// ===== DETALHES =====
function showTransactionDetails(transactionId) {
    fetch(`/api/store/transacao-detalhes?id=${transactionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                const content = document.getElementById('detailsContent');
                content.innerHTML = renderTransactionDetails(data.data);
                showModal('detailsModal');
            } else {
                alert('Erro ao carregar detalhes: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar detalhes:', error);
            alert('Erro ao carregar detalhes');
        });
}

function showPaymentDetails(paymentId) {
    fetch(`/api/store/pagamento-detalhes?id=${paymentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                const content = document.getElementById('detailsContent');
                content.innerHTML = renderPaymentDetails(data.data);
                showModal('detailsModal');
            } else {
                alert('Erro ao carregar detalhes: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar detalhes:', error);
            alert('Erro ao carregar detalhes');
        });
}

// ===== FUNÇÕES DE RENDERIZAÇÃO DE DETALHES =====
function renderTransactionDetails(transaction) {
    return `
        <div class="details-grid">
            <div class="detail-section">
                <h4>Informações da Transação</h4>
                <div class="detail-items">
                    <div class="detail-row">
                        <span class="label">ID:</span>
                        <span class="value">#${transaction.id}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Data:</span>
                        <span class="value">${formatDateTime(transaction.data_transacao)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Status:</span>
                        <span class="value">
                            <span class="status-badge ${transaction.status}">${getStatusLabel(transaction.status)}</span>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="detail-section">
                <h4>Valores</h4>
                <div class="detail-items">
                    <div class="detail-row">
                        <span class="label">Valor Total da Venda:</span>
                        <span class="value">R$ ${formatCurrency(transaction.valor_total)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Saldo Usado pelo Cliente:</span>
                        <span class="value">R$ ${formatCurrency(transaction.saldo_usado || 0)}</span>
                    </div>
                    <div class="detail-row highlight">
                        <span class="label">Comissão Klube Cash (10%):</span>
                        <span class="value">R$ ${formatCurrency(transaction.valor_cashback)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Cashback para Cliente (5%):</span>
                        <span class="value">R$ ${formatCurrency(transaction.valor_cliente)}</span>
                    </div>
                </div>
            </div>
            
            <div class="detail-section">
                <h4>Cliente</h4>
                <div class="detail-items">
                    <div class="detail-row">
                        <span class="label">Nome:</span>
                        <span class="value">${transaction.cliente_nome || 'N/A'}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Email:</span>
                        <span class="value">${transaction.cliente_email}</span>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function renderPaymentDetails(payment) {
    return `
        <div class="details-grid">
            <div class="detail-section">
                <h4>Informações do Pagamento</h4>
                <div class="detail-items">
                    <div class="detail-row">
                        <span class="label">ID:</span>
                        <span class="value">#${payment.id}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Data do Pagamento:</span>
                        <span class="value">${formatDateTime(payment.data_registro)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Status:</span>
                        <span class="value">
                            <span class="status-badge ${payment.status}">${getStatusLabel(payment.status)}</span>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Método:</span>
                        <span class="value">${payment.metodo_pagamento_label}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Referência:</span>
                        <span class="value">${payment.numero_referencia || 'N/A'}</span>
                    </div>
                </div>
            </div>
            
            <div class="detail-section">
                <h4>Valores</h4>
                <div class="detail-items">
                    <div class="detail-row">
                        <span class="label">Transações Incluídas:</span>
                        <span class="value">${payment.qtd_transacoes}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Valor Total Pago:</span>
                        <span class="value">R$ ${formatCurrency(payment.valor_total)}</span>
                    </div>
                </div>
            </div>
            
            ${payment.observacao ? `
            <div class="detail-section">
                <h4>Observações</h4>
                <div class="detail-items">
                    <div class="detail-row full-width">
                        <span class="value">${payment.observacao}</span>
                    </div>
                </div>
            </div>
            ` : ''}
        </div>
    `;
}

// ===== ESTADOS DE LOADING E ERRO =====
function showLoadingState(container) {
    container.innerHTML = `
        <div class="loading-state">
            <div class="spinner"></div>
            <p>Carregando dados...</p>
        </div>
    `;
}

function showErrorState(container, message) {
    container.innerHTML = `
        <div class="error-state">
            <div class="error-icon">⚠️</div>
            <p>${message}</p>
            <button class="btn-action primary" onclick="loadTabData('${currentTab}')">
                Tentar Novamente
            </button>
        </div>
    `;
}

function showEmptyState(container, message) {
    container.innerHTML = `
        <div class="empty-state">
            <div class="empty-icon">📋</div>
            <p>${message}</p>
        </div>
    `;
}

// ===== FUNÇÕES UTILITÁRIAS =====
function formatCurrency(value) {
    return parseFloat(value).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('pt-BR');
}

function formatDateTime(dateString) {
    return new Date(dateString).toLocaleString('pt-BR');
}

function getStatusLabel(status) {
    const labels = {
        'pendente': 'Pendente',
        'aprovado': 'Aprovado',
        'rejeitado': 'Rejeitado',
        'em_processamento': 'Em Processamento'
    };
    return labels[status] || status;
}

// ===== EXPORTAR FUNÇÕES GLOBAIS =====
window.showTab = showTab;
window.showFilterModal = showFilterModal;
window.openPaymentModal = openPaymentModal;
window.processPayment = processPayment;
window.applyFilters = applyFilters;
window.clearFilters = clearFilters;
window.showTransactionDetails = showTransactionDetails;
window.showPaymentDetails = showPaymentDetails;
window.closeModal = closeModal;
window.updateSelection = updateSelection;
window.toggleSelectAll = toggleSelectAll;
window.clearSelection = clearSelection;