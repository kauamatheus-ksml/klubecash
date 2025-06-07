/**
 * JavaScript para área da loja - Versão definitiva
 */

// Configuração global
window.storeConfig = {
    baseUrl: window.location.origin,
    apiUrl: '/api/store-payment.php'
};

document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando área da loja...');
    initializeStoreArea();
});

function initializeStoreArea() {
    // Interceptar TODOS os formulários e botões que possam chamar payment_form
    interceptAllPaymentActions();
    setupCheckboxes();
    updateSelectedCount();
}

function interceptAllPaymentActions() {
    // Interceptar todos os formulários
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const actionInput = form.querySelector('input[name="action"]');
            if (actionInput && actionInput.value === 'payment_form') {
                e.preventDefault();
                handlePaymentFormSubmission(form);
            }
        });
    });
    
    // Interceptar todos os botões com onclick
    const buttons = document.querySelectorAll('button[onclick], .btn[onclick], a[onclick]');
    buttons.forEach(btn => {
        const onclick = btn.getAttribute('onclick');
        if (onclick && onclick.includes('payment_form')) {
            btn.removeAttribute('onclick');
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                handlePaymentButtonClick();
            });
        }
    });
    
    // Procurar por botões específicos pelo texto
    const allButtons = document.querySelectorAll('button, .btn, input[type="submit"]');
    allButtons.forEach(btn => {
        const text = btn.textContent || btn.value || '';
        if (text.toLowerCase().includes('pagar selecionadas') || 
            text.toLowerCase().includes('pagar via pix') ||
            btn.classList.contains('pagar-selecionadas') ||
            btn.classList.contains('pagar-pix')) {
            
            btn.removeAttribute('onclick');
            btn.onclick = null;
            
            // Remover listeners anteriores
            btn.replaceWith(btn.cloneNode(true));
            const newBtn = document.querySelector(`[class="${btn.className}"]`) || btn;
            
            newBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                handlePaymentButtonClick();
            });
        }
    });
    
    // Interceptar AJAX calls que possam estar fazendo a chamada
    const originalFetch = window.fetch;
    window.fetch = function(url, options) {
        if (options && options.body instanceof FormData) {
            const action = options.body.get('action');
            if (action === 'payment_form') {
                console.log('Interceptando chamada fetch com payment_form');
                return handlePaymentFormAjax(url, options);
            }
        }
        return originalFetch.apply(this, arguments);
    };
    
    // Interceptar jQuery AJAX se estiver disponível
    if (window.jQuery) {
        const originalAjax = jQuery.ajax;
        jQuery.ajax = function(options) {
            if (options.data && options.data.action === 'payment_form') {
                console.log('Interceptando chamada jQuery com payment_form');
                return handlePaymentFormAjax(options.url, {
                    method: options.type || 'POST',
                    body: new URLSearchParams(options.data)
                });
            }
            return originalAjax.apply(this, arguments);
        };
    }
}

function handlePaymentFormSubmission(form) {
    const formData = new FormData(form);
    const transactionIds = [];
    
    // Capturar IDs das transações de diferentes formas
    if (formData.has('transaction_ids[]')) {
        formData.getAll('transaction_ids[]').forEach(id => {
            if (id) transactionIds.push(id);
        });
    } else if (formData.has('selected_transactions[]')) {
        formData.getAll('selected_transactions[]').forEach(id => {
            if (id) transactionIds.push(id);
        });
    } else {
        // Pegar dos checkboxes marcados
        const checkboxes = document.querySelectorAll('input[name="selected_transactions[]"]:checked');
        checkboxes.forEach(cb => transactionIds.push(cb.value));
    }
    
    if (transactionIds.length === 0) {
        showNotification('Selecione pelo menos uma transação', 'warning');
        return;
    }
    
    redirectToPaymentForm(transactionIds);
}

function handlePaymentButtonClick() {
    const selectedIds = getSelectedTransactionIds();
    
    if (selectedIds.length === 0) {
        showNotification('Selecione pelo menos uma transação', 'warning');
        return;
    }
    
    redirectToPaymentForm(selectedIds);
}

async function handlePaymentFormAjax(url, options) {
    const selectedIds = getSelectedTransactionIds();
    
    if (selectedIds.length === 0) {
        showNotification('Selecione pelo menos uma transação', 'warning');
        return Promise.resolve(new Response(JSON.stringify({
            status: false,
            message: 'Nenhuma transação selecionada'
        })));
    }
    
    return redirectToPaymentFormAjax(selectedIds);
}

function redirectToPaymentForm(transactionIds) {
    const url = `${window.storeConfig.baseUrl}/loja/formulario-pagamento?transactions=${transactionIds.join(',')}`;
    console.log('Redirecionando para:', url);
    window.location.href = url;
}

async function redirectToPaymentFormAjax(transactionIds) {
    try {
        showLoading('Preparando pagamento...');
        
        const formData = new FormData();
        formData.append('action', 'payment_form');
        transactionIds.forEach(id => {
            formData.append('transaction_ids[]', id);
        });
        
        const response = await fetch(window.storeConfig.apiUrl, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.status && result.redirect_url) {
            showNotification('Redirecionando...', 'success');
            setTimeout(() => {
                window.location.href = result.redirect_url;
            }, 1000);
        } else {
            showNotification(result.message || 'Erro ao processar', 'error');
        }
        
        return new Response(JSON.stringify(result));
        
    } catch (error) {
        console.error('Erro:', error);
        showNotification('Erro de conexão', 'error');
        return Promise.resolve(new Response(JSON.stringify({
            status: false,
            message: 'Erro de conexão'
        })));
    } finally {
        hideLoading();
    }
}

function setupCheckboxes() {
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="selected_transactions[]"]');
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateSelectedCount();
        });
    }
    
    const transactionCheckboxes = document.querySelectorAll('input[name="selected_transactions[]"]');
    transactionCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
    });
}

function getSelectedTransactionIds() {
    const checkboxes = document.querySelectorAll('input[name="selected_transactions[]"]:checked');
    return Array.from(checkboxes).map(cb => cb.value).filter(id => id);
}

function updateSelectedCount() {
    const selectedCount = getSelectedTransactionIds().length;
    
    // Atualizar todos os botões relacionados a pagamento
    const paymentButtons = document.querySelectorAll('button, .btn, input[type="submit"]');
    paymentButtons.forEach(btn => {
        const text = btn.textContent || btn.value || '';
        if (text.toLowerCase().includes('pagar')) {
            btn.disabled = selectedCount === 0;
            
            if (text.toLowerCase().includes('selecionadas')) {
                const baseText = text.replace(/\(\d+\)/, '').trim();
                btn.textContent = selectedCount > 0 ? `${baseText} (${selectedCount})` : baseText;
            }
        }
    });
}

function showLoading(message = 'Carregando...') {
    let modal = document.getElementById('loadingModal');
    
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'loadingModal';
        modal.innerHTML = `
            <div style="
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
            ">
                <div style="
                    background: white;
                    padding: 30px;
                    border-radius: 8px;
                    text-align: center;
                ">
                    <div style="
                        width: 40px;
                        height: 40px;
                        border: 4px solid #f3f3f3;
                        border-top: 4px solid #FF7A00;
                        border-radius: 50%;
                        animation: spin 1s linear infinite;
                        margin: 0 auto 16px;
                    "></div>
                    <p>${message}</p>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Adicionar CSS da animação se não existir
        if (!document.getElementById('spinnerCSS')) {
            const style = document.createElement('style');
            style.id = 'spinnerCSS';
            style.textContent = `
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    modal.style.display = 'block';
}

function hideLoading() {
    const modal = document.getElementById('loadingModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 24px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 10001;
        transform: translateX(400px);
        transition: transform 0.3s ease;
        max-width: 400px;
    `;
    
    const colors = {
        success: '#10B981',
        error: '#EF4444',
        warning: '#F59E0B',
        info: '#3B82F6'
    };
    
    notification.style.backgroundColor = colors[type] || colors.info;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 10);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(400px)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 5000);
}

console.log('Store.js carregado com interceptação completa');
// Função para processar pagamentos selecionados com OpenPix
function processSelectedPayments() {
    const checkboxes = document.querySelectorAll('input[name="transaction_ids[]"]:checked');
    const selectedIds = Array.from(checkboxes).map(cb => cb.value);
    
    if (selectedIds.length === 0) {
        showAlert('warning', 'Por favor, selecione pelo menos uma transação para pagar.');
        return;
    }
    
    // Mostrar loading
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="spinner-border spinner-border-sm me-2"></i>Processando...';
    
    // Enviar requisição
    fetch(`${SITE_URL}/api/store-payment?action=payment_form`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `transaction_ids=${selectedIds.join(',')}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status) {
            // Redirecionar para página de pagamento
            window.location.href = data.redirect_url;
        } else {
            showAlert('danger', data.message || 'Erro ao processar pagamento');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showAlert('danger', 'Erro ao processar requisição');
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

// Função auxiliar para mostrar alertas
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Inserir alerta no topo da página
    const container = document.querySelector('.container-fluid main');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-remover após 5 segundos
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}