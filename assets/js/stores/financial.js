// assets/js/stores/financial.js
document.addEventListener('DOMContentLoaded', function() {
    initializeFinancialPage();
});

// Variáveis globais
let selectedTransactions = new Set();
let currentTab = 'resumo';
let currentPage = 1;
let currentFilters = {};

/**
 * Inicializar a página financeiro
 */
function initializeFinancialPage() {
    // Detectar aba ativa na URL
    const urlParams = new URLSearchParams(window.location.search);
    const tabFromUrl = urlParams.get('tab');
    if (tabFromUrl && ['resumo', 'transacoes', 'pendentes', 'historico'].includes(tabFromUrl)) {
        currentTab = tabFromUrl;
    }
    
    // Ativar aba inicial
    switchTab(currentTab);
    
    // Carregar dados iniciais
    loadFinancialData();
    
    // Inicializar gráfico se estiver na aba resumo
    if (currentTab === 'resumo') {
        initializeChart();
    }
    
    // Event listeners
    setupEventListeners();
}

/**
 * Configurar event listeners
 */
function setupEventListeners() {
    // Modal overlay click
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeAllModals();
            }
        });
    });
    
    // ESC key para fechar modais
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });
}

/**
 * Alternar entre abas
 */
function switchTab(tabName) {
    // Atualizar URL sem recarregar página
    const url = new URL(window.location);
    url.searchParams.set('tab', tabName);
    window.history.pushState({}, '', url);
    
    // Remover classe active de todas as abas
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    
    document.querySelectorAll('.tab-pane').forEach(pane => {
        pane.classList.remove('active');
    });
    
    // Ativar aba selecionada
    document.querySelector(`[onclick="switchTab('${tabName}')"]`).classList.add('active');
    document.getElementById(`tab-${tabName}`).classList.add('active');
    
    currentTab = tabName;
    
    // Carregar dados específicos da aba
    loadTabData(tabName);
}

/**
 * Carregar dados financeiros gerais
 */
async function loadFinancialData() {
    try {
        showLoading();
        
        const response = await fetch('/api/stores/financial-summary', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            }
        });
        
        const data = await response.json();
        
        if (data.status) {
            updateFinancialSummary(data.data);
        } else {
            showError('Erro ao carregar dados financeiros: ' + data.message);
        }
    } catch (error) {
        console.error('Erro:', error);
        showError('Erro de conexão ao carregar dados financeiros');
    } finally {
        hideLoading();
    }
}

/**
 * Carregar dados específicos de uma aba
 */
async function loadTabData(tabName) {
    switch (tabName) {
        case 'resumo':
            await loadResumoData();
            break;
        case 'transacoes':
            await loadTransacoesData();
            break;
        case 'pendentes':
            await loadPendentesData();
            break;
        case 'historico':
            await loadHistoricoData();
            break;
    }
}

/**
 * Carregar dados da aba resumo
 */
async function loadResumoData() {
    if (!document.getElementById('evolutionChart')) return;
    
    const period = document.getElementById('chartPeriod')?.value || '30';
    
    try {
        const response = await fetch(`/api/stores/evolution-chart?period=${period}`);
        const data = await response.json();
        
        if (data.status) {
            updateEvolutionChart(data.data);
        }
        
        // Carregar atividades recentes
        const activitiesResponse = await fetch('/api/stores/recent-activities');
        const activitiesData = await activitiesResponse.json();
        
        if (activitiesData.status) {
            updateRecentActivities(activitiesData.data);
        }
        
    } catch (error) {
        console.error('Erro ao carregar dados do resumo:', error);
    }
}

/**
 * Carregar dados da aba transações
 */
async function loadTransacoesData() {
    try {
        const params = new URLSearchParams({
            page: currentPage,
            ...currentFilters
        });
        
        const response = await fetch(`/api/stores/transactions?${params}`);
        const data = await response.json();
        
        if (data.status) {
            updateTransactionsTable(data.data.transactions);
            updatePagination(data.data.pagination, 'transacoes');
        }
    } catch (error) {
        console.error('Erro ao carregar transações:', error);
    }
}

/**
 * Carregar dados da aba pendentes
 */
async function loadPendentesData() {
    try {
        const params = new URLSearchParams({
            page: currentPage,
            ...currentFilters
        });
        
        const response = await fetch(`/api/stores/pending-transactions?${params}`);
        const data = await response.json();
        
        if (data.status) {
            updatePendingTable(data.data.transactions);
            updatePagination(data.data.pagination, 'pendentes');
        }
    } catch (error) {
        console.error('Erro ao carregar pendentes:', error);
    }
}

/**
 * Carregar dados da aba histórico
 */
async function loadHistoricoData() {
    try {
        const params = new URLSearchParams({
            page: currentPage,
            ...currentFilters
        });
        
        const response = await fetch(`/api/stores/payment-history?${params}`);
        const data = await response.json();
        
        if (data.status) {
            updateHistoryTable(data.data.payments);
            updatePagination(data.data.pagination, 'historico');
        }
    } catch (error) {
        console.error('Erro ao carregar histórico:', error);
    }
}

/**
 * Atualizar resumo financeiro
 */
function updateFinancialSummary(data) {
    const elements = {
        totalSales: document.getElementById('totalSales'),
        totalCommissions: document.getElementById('totalCommissions'),
        totalPending: document.getElementById('totalPending'),
        totalBalance: document.getElementById('totalBalance')
    };
    
    if (elements.totalSales && data.total_vendas !== undefined) {
        elements.totalSales.textContent = formatCurrency(data.total_vendas);
    }
    
    if (elements.totalCommissions && data.comissoes_pagas !== undefined) {
        elements.totalCommissions.textContent = formatCurrency(data.comissoes_pagas);
    }
    
    if (elements.totalPending && data.comissoes_pendentes !== undefined) {
        elements.totalPending.textContent = formatCurrency(data.comissoes_pendentes);
    }
    
    if (elements.totalBalance && data.saldo_usado !== undefined) {
        elements.totalBalance.textContent = formatCurrency(data.saldo_usado);
    }
}

/**
 * Inicializar gráfico de evolução
 */
function initializeChart() {
    const ctx = document.getElementById('evolutionChart');
    if (!ctx) return;
    
    // Configuração inicial do gráfico
    window.evolutionChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Vendas',
                data: [],
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Comissões',
                data: [],
                borderColor: '#27ae60',
                backgroundColor: 'rgba(39, 174, 96, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return formatCurrency(value);
                        }
                    }
                }
            }
        }
    });
}

/**
 * Atualizar gráfico de evolução
 */
function updateEvolutionChart(data) {
    if (!window.evolutionChart) return;
    
    window.evolutionChart.data.labels = data.labels || [];
    window.evolutionChart.data.datasets[0].data = data.vendas || [];
    window.evolutionChart.data.datasets[1].data = data.comissoes || [];
    window.evolutionChart.update();
}

/**
 * Atualizar atividades recentes
 */
function updateRecentActivities(activities) {
    const container = document.querySelector('.activities-list');
    if (!container) return;
    
    container.innerHTML = '';
    
    activities.forEach(activity => {
        const item = document.createElement('div');
        item.className = 'activity-item';
        item.innerHTML = `
            <div class="activity-content">
                <strong>${activity.titulo}</strong>
                <p>${activity.descricao}</p>
                <small>${activity.data_formatada}</small>
            </div>
        `;
        container.appendChild(item);
    });
}

/**
 * Atualizar tabela de transações
 */
function updateTransactionsTable(transactions) {
    const tbody = document.getElementById('transactionsTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    transactions.forEach(transaction => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${formatDate(transaction.data_transacao)}</td>
            <td>${transaction.cliente_nome || 'N/A'}</td>
            <td>${formatCurrency(transaction.valor_total)}</td>
            <td>${formatCurrency(transaction.saldo_usado || 0)}</td>
            <td>${formatCurrency(transaction.valor_comissao)}</td>
            <td><span class="status-badge ${transaction.status}">${getStatusText(transaction.status)}</span></td>
            <td>
                <button class="btn btn-sm btn-secondary" onclick="viewTransactionDetails(${transaction.id})">
                    Ver Detalhes
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

/**
 * Atualizar tabela de pendentes
 */
function updatePendingTable(transactions) {
    const tbody = document.getElementById('pendingTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    transactions.forEach(transaction => {
        const row = document.createElement('tr');
        const daysPending = calculateDaysPending(transaction.data_transacao);
        
        row.innerHTML = `
            <td>
                <input type="checkbox" value="${transaction.id}" onchange="toggleTransactionSelection(${transaction.id}, this.checked)">
            </td>
            <td>${formatDate(transaction.data_transacao)}</td>
            <td>${transaction.cliente_nome || 'N/A'}</td>
            <td>${formatCurrency(transaction.valor_total)}</td>
            <td>${formatCurrency(transaction.saldo_usado || 0)}</td>
            <td>${formatCurrency(transaction.valor_comissao)}</td>
            <td>
                <span class="days-pending ${daysPending > 30 ? 'urgent' : ''}">${daysPending} dias</span>
            </td>
            <td>
                <button class="btn btn-sm btn-secondary" onclick="viewTransactionDetails(${transaction.id})">
                    Ver Detalhes
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
    
    updateSelectedSummary();
}

/**
 * Atualizar tabela de histórico
 */
function updateHistoryTable(payments) {
    const tbody = document.getElementById('historyTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    payments.forEach(payment => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>#${payment.id}</td>
            <td>${formatDate(payment.data_registro)}</td>
            <td>${formatCurrency(payment.valor_vendas_originais)}</td>
            <td>${formatCurrency(payment.saldo_usado || 0)}</td>
            <td>${formatCurrency(payment.valor_total)}</td>
            <td>${getPaymentMethodText(payment.metodo_pagamento)}</td>
            <td><span class="status-badge ${payment.status}">${getStatusText(payment.status)}</span></td>
            <td>${payment.total_transacoes || 0}</td>
            <td>
                <button class="btn btn-sm btn-secondary" onclick="viewPaymentDetails(${payment.id})">
                    Ver Detalhes
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

/**
 * Gerenciar seleção de transações pendentes
 */
function toggleTransactionSelection(transactionId, selected) {
    if (selected) {
        selectedTransactions.add(transactionId);
    } else {
        selectedTransactions.delete(transactionId);
    }
    
    updateSelectedSummary();
    updatePaymentButtons();
}

/**
 * Selecionar todas as transações pendentes
 */
function selectAllPending() {
    const checkboxes = document.querySelectorAll('#pendingTableBody input[type="checkbox"]');
    const selectAllBtn = document.getElementById('selectAllBtn');
    
    if (selectedTransactions.size === checkboxes.length) {
        // Desmarcar todas
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
            selectedTransactions.delete(parseInt(checkbox.value));
        });
        selectAllBtn.textContent = 'Selecionar Todas';
    } else {
        // Marcar todas
        checkboxes.forEach(checkbox => {
            checkbox.checked = true;
            selectedTransactions.add(parseInt(checkbox.value));
        });
        selectAllBtn.textContent = 'Desmarcar Todas';
    }
    
    updateSelectedSummary();
    updatePaymentButtons();
}

/**
 * Alternar seleção geral
 */
function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const checkboxes = document.querySelectorAll('#pendingTableBody input[type="checkbox"]');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
        toggleTransactionSelection(parseInt(checkbox.value), checkbox.checked);
    });
}

/**
 * Atualizar resumo de selecionados
 */
function updateSelectedSummary() {
    const selectedTotalEl = document.getElementById('selectedTotal');
    const selectedCountEl = document.getElementById('selectedCount');
    
    if (!selectedTotalEl || !selectedCountEl) return;
    
    let totalValue = 0;
    const checkboxes = document.querySelectorAll('#pendingTableBody input[type="checkbox"]:checked');
    
    checkboxes.forEach(checkbox => {
        const row = checkbox.closest('tr');
        const valueText = row.children[5].textContent; // Coluna de comissão
        const value = parseFloat(valueText.replace('R$', '').replace(/\./g, '').replace(',', '.'));
        totalValue += value || 0;
    });
    
    selectedTotalEl.textContent = formatCurrency(totalValue);
    selectedCountEl.textContent = selectedTransactions.size;
}

/**
 * Atualizar botões de pagamento
 */
function updatePaymentButtons() {
    const paySelectedBtn = document.getElementById('paySelectedBtn');
    if (paySelectedBtn) {
        paySelectedBtn.disabled = selectedTransactions.size === 0;
    }
}

/**
 * Realizar pagamento das comissões selecionadas
 */
function paySelectedCommissions() {
    if (selectedTransactions.size === 0) {
        showError('Selecione pelo menos uma transação para pagar.');
        return;
    }
    
    openPaymentModal(Array.from(selectedTransactions));
}

/**
 * Modais
 */
function openFilterModal() {
    document.getElementById('filterModal').style.display = 'block';
}

function closeFilterModal() {
    document.getElementById('filterModal').style.display = 'none';
}

function openPaymentModal(transactionIds) {
    const modal = document.getElementById('paymentModal');
    const modalBody = document.getElementById('paymentModalBody');
    
    // Carregar conteúdo do modal de pagamento
    loadPaymentModalContent(transactionIds);
    
    modal.style.display = 'block';
}

function closePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
}

function openTransactionModal() {
    document.getElementById('transactionModal').style.display = 'block';
}

function closeTransactionModal() {
    document.getElementById('transactionModal').style.display = 'none';
}

function closeAllModals() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.style.display = 'none';
    });
}

/**
 * Carregar conteúdo do modal de pagamento
 */
async function loadPaymentModalContent(transactionIds) {
    try {
        const response = await fetch('/api/stores/payment-modal', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ transaction_ids: transactionIds })
        });
        
        const data = await response.json();
        
        if (data.status) {
            document.getElementById('paymentModalBody').innerHTML = data.html;
        } else {
            showError('Erro ao carregar modal de pagamento: ' + data.message);
        }
    } catch (error) {
        console.error('Erro:', error);
        showError('Erro de conexão ao carregar modal de pagamento');
    }
}

/**
 * Ver detalhes da transação
 */
async function viewTransactionDetails(transactionId) {
    try {
        const response = await fetch(`/api/stores/transaction-details/${transactionId}`);
        const data = await response.json();
        
        if (data.status) {
            document.getElementById('transactionModalBody').innerHTML = data.html;
            openTransactionModal();
        } else {
            showError('Erro ao carregar detalhes: ' + data.message);
        }
    } catch (error) {
        console.error('Erro:', error);
        showError('Erro de conexão ao carregar detalhes');
    }
}

/**
 * Ver detalhes do pagamento
 */
async function viewPaymentDetails(paymentId) {
    try {
        const response = await fetch(`/api/stores/payment-details/${paymentId}`);
        const data = await response.json();
        
        if (data.status) {
            document.getElementById('transactionModalBody').innerHTML = data.html;
            openTransactionModal();
        } else {
            showError('Erro ao carregar detalhes: ' + data.message);
        }
    } catch (error) {
        console.error('Erro:', error);
        showError('Erro de conexão ao carregar detalhes');
    }
}

/**
 * Filtros
 */
function applyFilters() {
    const formData = new FormData(document.getElementById('filterForm'));
    currentFilters = Object.fromEntries(formData.entries());
    currentPage = 1;
    
    // Atualizar URL
    const url = new URL(window.location);
    Object.keys(currentFilters).forEach(key => {
        if (currentFilters[key]) {
            url.searchParams.set(key, currentFilters[key]);
        } else {
            url.searchParams.delete(key);
        }
    });
    window.history.pushState({}, '', url);
    
    // Recarregar dados da aba atual
    loadTabData(currentTab);
    
    closeFilterModal();
}

function clearFilters() {
    document.getElementById('filterForm').reset();
    currentFilters = {};
    currentPage = 1;
    
    // Limpar URL
    const url = new URL(window.location);
    ['data_inicio', 'data_fim', 'status', 'metodo_pagamento'].forEach(param => {
        url.searchParams.delete(param);
    });
    window.history.pushState({}, '', url);
    
    // Recarregar dados
    loadTabData(currentTab);
    
    closeFilterModal();
}

/**
 * Exportações
 */
async function exportTransactions() {
    try {
        const params = new URLSearchParams({
            export: 'transactions',
            ...currentFilters
        });
        
        window.location.href = `/api/stores/export?${params}`;
    } catch (error) {
        console.error('Erro ao exportar:', error);
        showError('Erro ao exportar transações');
    }
}

async function exportPayments() {
    try {
        const params = new URLSearchParams({
            export: 'payments',
            ...currentFilters
        });
        
        window.location.href = `/api/stores/export?${params}`;
    } catch (error) {
        console.error('Erro ao exportar:', error);
        showError('Erro ao exportar pagamentos');
    }
}

/**
 * Atualizar gráfico quando período mudar
 */
function updateChart() {
    loadResumoData();
}

/**
 * Atualizar paginação
 */
function updatePagination(pagination, context) {
    const container = document.querySelector('.pagination-container');
    if (!container) return;
    
    let html = '<div class="pagination">';
    
    // Primeira página
    if (pagination.pagina_atual > 1) {
        html += `<a href="#" class="page-link" onclick="changePage(1, '${context}')">Primeira</a>`;
        html += `<a href="#" class="page-link" onclick="changePage(${pagination.pagina_atual - 1}, '${context}')">Anterior</a>`;
    }
    
    // Páginas
    const start = Math.max(1, pagination.pagina_atual - 2);
    const end = Math.min(pagination.total_paginas, pagination.pagina_atual + 2);
    
    for (let i = start; i <= end; i++) {
        const activeClass = i === pagination.pagina_atual ? 'active' : '';
        html += `<a href="#" class="page-link ${activeClass}" onclick="changePage(${i}, '${context}')">${i}</a>`;
    }
    
    // Última página
    if (pagination.pagina_atual < pagination.total_paginas) {
        html += `<a href="#" class="page-link" onclick="changePage(${pagination.pagina_atual + 1}, '${context}')">Próxima</a>`;
        html += `<a href="#" class="page-link" onclick="changePage(${pagination.total_paginas}, '${context}')">Última</a>`;
    }
    
    html += '</div>';
    container.innerHTML = html;
}

/**
 * Mudar página
 */
function changePage(page, context) {
    currentPage = page;
    loadTabData(currentTab);
}

/**
 * Funções utilitárias
 */
function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value || 0);
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('pt-BR');
}

function calculateDaysPending(dateString) {
    const transactionDate = new Date(dateString);
    const today = new Date();
    const diffTime = Math.abs(today - transactionDate);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
}

function getStatusText(status) {
    const statusMap = {
        'pendente': 'Pendente',
        'aprovado': 'Aprovado',
        'rejeitado': 'Rejeitado'
    };
    return statusMap[status] || status;
}

function getPaymentMethodText(method) {
    const methodMap = {
        'pix_openpix': 'PIX (OpenPix)',
        'mercadopago': 'Mercado Pago'
    };
    return methodMap[method] || method;
}

function showLoading() {
    // Implementar indicador de carregamento
}

function hideLoading() {
    // Remover indicador de carregamento
}

function showError(message) {
    // Implementar notificação de erro
    console.error(message);
}

function showSuccess(message) {
    // Implementar notificação de sucesso
    console.log(message);
}