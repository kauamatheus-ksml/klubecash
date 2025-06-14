// Gestão de abas e funcionalidades da página financeira

// Variáveis globais
let selectedTransactions = [];
let evolutionChart = null;

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    // Configurar checkboxes
    setupCheckboxes();
    
    // Configurar gráfico se estiver na aba overview
    if (getActiveTab() === 'overview') {
        initializeChart();
    }
    
    // Configurar formulários
    setupForms();
});

// Função para obter aba ativa
function getActiveTab() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('tab') || 'overview';
}

// Configurar checkboxes para seleção múltipla
function setupCheckboxes() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.transaction-checkbox');
    
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                cb.checked = this.checked;
                updateSelectedTransactions();
            });
        });
    }
    
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateSelectedTransactions);
    });
}

// Atualizar transações selecionadas
function updateSelectedTransactions() {
    selectedTransactions = [];
    let total = 0;
    
    document.querySelectorAll('.transaction-checkbox:checked').forEach(cb => {
        selectedTransactions.push(cb.value);
        total += parseFloat(cb.dataset.valor);
    });
    
    // Atualizar contador e total se existirem
    const countElement = document.getElementById('selectedCount');
    const totalElement = document.getElementById('selectedTotal');
    
    if (countElement) countElement.textContent = selectedTransactions.length;
    if (totalElement) totalElement.textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
}

// Toggle de filtros
function toggleFilters() {
    const filtersBody = document.getElementById('filtersBody');
    const icon = document.querySelector('.toggle-icon');
    
    filtersBody.classList.toggle('show');
    icon.textContent = filtersBody.classList.contains('show') ? '▲' : '▼';
}

// Toggle de informações
function toggleInfoSection() {
    const content = document.getElementById('infoSectionContent');
    const icon = document.getElementById('infoDropdownIcon');
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.textContent = '▲';
    } else {
        content.style.display = 'none';
        icon.textContent = '▼';
    }
}

// Modais
function openTransactionModal(transactionId) {
    const modal = document.getElementById('transactionModal');
    const detailsDiv = document.getElementById('transactionDetails');
    
    // Carregar detalhes via AJAX
    detailsDiv.innerHTML = '<p>Carregando...</p>';
    modal.style.display = 'block';
    
    fetch(`/api/transactions.php?action=details&id=${transactionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                detailsDiv.innerHTML = formatTransactionDetails(data.data);
            } else {
                detailsDiv.innerHTML = '<p>Erro ao carregar detalhes.</p>';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            detailsDiv.innerHTML = '<p>Erro ao carregar detalhes.</p>';
        });
}

function closeTransactionModal() {
    document.getElementById('transactionModal').style.display = 'none';
}

function openPaymentModal(paymentId) {
    const modal = document.getElementById('paymentModal');
    const detailsDiv = document.getElementById('paymentDetails');
    
    detailsDiv.innerHTML = '<p>Carregando...</p>';
    modal.style.display = 'block';
    
    fetch(`/api/payments.php?action=details&id=${paymentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                detailsDiv.innerHTML = formatPaymentDetails(data.data);
            } else {
                detailsDiv.innerHTML = '<p>Erro ao carregar detalhes.</p>';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            detailsDiv.innerHTML = '<p>Erro ao carregar detalhes.</p>';
        });
}

function closePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
}

function openBatchPaymentModal() {
    if (selectedTransactions.length === 0) {
        alert('Selecione pelo menos uma transação para pagar.');
        return;
    }
    
    document.getElementById('batchPaymentModal').style.display = 'block';
}

function closeBatchPaymentModal() {
    document.getElementById('batchPaymentModal').style.display = 'none';
}

// Formatar detalhes da transação
function formatTransactionDetails(transaction) {
    return `
        <div class="transaction-details">
            <div class="detail-group">
                <h4>Informações da Venda</h4>
                <div class="detail-row">
                    <span>Data:</span>
                    <strong>${formatDate(transaction.data_transacao)}</strong>
                </div>
                <div class="detail-row">
                    <span>Cliente:</span>
                    <strong>${transaction.cliente_nome}</strong>
                </div>
                <div class="detail-row">
                    <span>CPF:</span>
                    <strong>${formatCPF(transaction.cliente_cpf)}</strong>
                </div>
            </div>
            
            <div class="detail-group">
                <h4>Valores</h4>
                <div class="detail-row">
                    <span>Valor da Venda:</span>
                    <strong>R$ ${formatMoney(transaction.valor_total)}</strong>
                </div>
                <div class="detail-row">
                    <span>Saldo Usado:</span>
                    <strong>R$ ${formatMoney(transaction.valor_saldo_usado || 0)}</strong>
                </div>
                <div class="detail-row">
                    <span>Valor Efetivo:</span>
                    <strong>R$ ${formatMoney(transaction.valor_total - (transaction.valor_saldo_usado || 0))}</strong>
                </div>
                <div class="detail-row highlight">
                    <span>Comissão Total (10%):</span>
                    <strong>R$ ${formatMoney(transaction.valor_cashback)}</strong>
                </div>
            </div>
            
            <div class="detail-group">
                <h4>Distribuição</h4>
                <div class="detail-row">
                    <span>Cashback Cliente (5%):</span>
                    <strong>R$ ${formatMoney(transaction.valor_cliente)}</strong>
                </div>
                <div class="detail-row">
                    <span>Klube Cash (5%):</span>
                    <strong>R$ ${formatMoney(transaction.valor_admin)}</strong>
                </div>
            </div>
            
            <div class="detail-group">
                <h4>Status</h4>
                <div class="status-info">
                    <span class="status-badge status-${transaction.status}">
                        ${transaction.status.toUpperCase()}
                    </span>
                    ${transaction.observacoes ? `<p class="observations">${transaction.observacoes}</p>` : ''}
                </div>
            </div>
        </div>
    `;
}

// Formatar detalhes do pagamento
function formatPaymentDetails(payment) {
    return `
        <div class="payment-details-modal">
            <div class="detail-group">
                <h4>Informações do Pagamento</h4>
                <div class="detail-row">
                    <span>ID:</span>
                    <strong>#${payment.id}</strong>
                </div>
                <div class="detail-row">
                    <span>Data:</span>
                    <strong>${formatDate(payment.data_pagamento)}</strong>
                </div>
                <div class="detail-row">
                    <span>Método:</span>
                    <strong>${payment.metodo_pagamento}</strong>
                </div>
                <div class="detail-row">
                    <span>Status:</span>
                    <span class="status-badge status-${payment.status}">
                        ${payment.status.toUpperCase()}
                    </span>
                </div>
            </div>
            
            <div class="detail-group">
                <h4>Valores</h4>
                <div class="detail-row">
                    <span>Total de Vendas:</span>
                    <strong>R$ ${formatMoney(payment.valor_vendas)}</strong>
                </div>
                <div class="detail-row">
                    <span>Saldo Usado:</span>
                    <strong>R$ ${formatMoney(payment.saldo_usado)}</strong>
                </div>
                <div class="detail-row highlight">
                    <span>Valor Pago:</span>
                    <strong>R$ ${formatMoney(payment.valor_total)}</strong>
                </div>
            </div>
            
            <div class="detail-group">
                <h4>Transações Incluídas</h4>
                <div class="transactions-list">
                    ${payment.transacoes.map(t => `
                        <div class="transaction-item-small">
                            <span>${formatDate(t.data)} - ${t.cliente}</span>
                            <span>R$ ${formatMoney(t.valor)}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
            
            ${payment.observacoes ? `
            <div class="detail-group">
                <h4>Observações</h4>
                <p>${payment.observacoes}</p>
            </div>
            ` : ''}
            
            ${payment.comprovante ? `
            <div class="detail-group">
                <a href="${payment.comprovante}" target="_blank" class="btn btn-primary">
                    Ver Comprovante
                </a>
            </div>
            ` : ''}
        </div>
    `;
}

// Configurar formulários
function setupForms() {
    const batchForm = document.getElementById('batchPaymentForm');
    if (batchForm) {
        batchForm.addEventListener('submit', handleBatchPayment);
    }
}

// Processar pagamento em lote
function handleBatchPayment(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('transactions', JSON.stringify(selectedTransactions));
    
    // Desabilitar botão de submit
    const submitBtn = e.target.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Processando...';
    
    fetch('/api/payments.php?action=create', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status) {
            alert('Pagamento registrado com sucesso!');
            window.location.reload();
        } else {
            alert('Erro: ' + data.message);
            submitBtn.disabled = false;
            submitBtn.textContent = 'Confirmar Pagamento';
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao processar pagamento.');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Confirmar Pagamento';
    });
}

// Inicializar gráfico
function initializeChart() {
    const ctx = document.getElementById('evolutionChart');
    if (!ctx) return;
    
    // Dados de exemplo - substituir por dados reais via AJAX
    const data = {
        labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
        datasets: [{
            label: 'Vendas',
            data: [12000, 19000, 15000, 25000, 22000, 30000],
            borderColor: '#4361ee',
            backgroundColor: 'rgba(67, 97, 238, 0.1)',
            tension: 0.4
        }, {
            label: 'Comissões',
            data: [1200, 1900, 1500, 2500, 2200, 3000],
            borderColor: '#f39c12',
            backgroundColor: 'rgba(243, 156, 18, 0.1)',
            tension: 0.4
        }]
    };
    
    evolutionChart = new Chart(ctx, {
        type: 'line',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
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

// Funções utilitárias
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
}

function formatMoney(value) {
    return parseFloat(value).toFixed(2).replace('.', ',');
}

function formatCPF(cpf) {
    if (!cpf) return '';
    return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
}

// Fechar modais ao clicar fora
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}