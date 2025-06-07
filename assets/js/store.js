/**
 * JavaScript para área da loja - Atualizado com OpenPix
 */

// Configuração global
window.storeConfig = window.storeConfig || {
    baseUrl: '',
    paymentController: '/controllers/PaymentController.php'
};

// Função para processar pagamento de transações selecionadas
function processSelectedPayments() {
    const checkboxes = document.querySelectorAll('input[name="selected_transactions[]"]:checked');
    
    if (checkboxes.length === 0) {
        showNotification('Selecione pelo menos uma transação', 'warning');
        return;
    }
    
    const transactionIds = Array.from(checkboxes).map(cb => cb.value);
    
    // Redirecionar para formulário de pagamento
    const url = new URL(window.location.origin + '/loja/formulario-pagamento');
    url.searchParams.set('transactions', transactionIds.join(','));
    
    window.location.href = url.toString();
}

// Função para pagamento via PIX direto
function processPixPayment() {
    const checkboxes = document.querySelectorAll('input[name="selected_transactions[]"]:checked');
    
    if (checkboxes.length === 0) {
        showNotification('Selecione pelo menos uma transação', 'warning');
        return;
    }
    
    const transactionIds = Array.from(checkboxes).map(cb => cb.value);
    
    // Criar pagamento e redirecionar para PIX
    createCommissionPayment(transactionIds, 'pix_openpix');
}

// Função para criar pagamento de comissão
async function createCommissionPayment(transactionIds, paymentMethod = 'pix_openpix') {
    try {
        showLoading('Criando pagamento...');
        
        const formData = new FormData();
        formData.append('action', 'create_commission_payment');
        formData.append('payment_method', paymentMethod);
        
        transactionIds.forEach(id => {
            formData.append('transaction_ids[]', id);
        });
        
        const response = await fetch(window.storeConfig.baseUrl + window.storeConfig.paymentController, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.status) {
            showNotification(result.message, 'success');
            
            if (result.data && result.data.redirect_url) {
                setTimeout(() => {
                    window.location.href = result.data.redirect_url;
                }, 1500);
            }
        } else {
            showNotification(result.message || 'Erro ao criar pagamento', 'error');
        }
        
    } catch (error) {
        console.error('Erro ao criar pagamento:', error);
        showNotification('Erro de conexão. Tente novamente.', 'error');
    } finally {
        hideLoading();
    }
}

// Atualizar eventos dos botões
document.addEventListener('DOMContentLoaded', function() {
    // Botão "Pagar Selecionadas"
    const pagarSelecionadasBtn = document.querySelector('[onclick*="payment_form"]');
    if (pagarSelecionadasBtn) {
        pagarSelecionadasBtn.removeAttribute('onclick');
        pagarSelecionadasBtn.addEventListener('click', processSelectedPayments);
    }
    
    // Botão "Pagar via PIX"
    const pagarPixBtn = document.querySelector('button[onclick*="pix"]');
    if (pagarPixBtn) {
        pagarPixBtn.removeAttribute('onclick');
        pagarPixBtn.addEventListener('click', processPixPayment);
    }
    
    // Checkbox "Selecionar todos"
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="selected_transactions[]"]');
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateSelectedCount();
        });
    }
    
    // Checkboxes individuais
    const transactionCheckboxes = document.querySelectorAll('input[name="selected_transactions[]"]');
    transactionCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
    });
    
    updateSelectedCount();
});

// Função para atualizar contador de selecionados
function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('input[name="selected_transactions[]"]:checked');
    const count = checkboxes.length;
    
    // Atualizar texto dos botões
    const pagarSelecionadasBtn = document.querySelector('button[class*="pagar-selecionadas"]');
    if (pagarSelecionadasBtn) {
        pagarSelecionadasBtn.textContent = count > 0 
            ? `Pagar Selecionadas (${count})` 
            : 'Pagar Selecionadas';
        pagarSelecionadasBtn.disabled = count === 0;
    }
    
    const pagarPixBtn = document.querySelector('button[class*="pagar-pix"]');
    if (pagarPixBtn) {
        pagarPixBtn.disabled = count === 0;
    }
}

// Funções utilitárias
function showLoading(message = 'Carregando...') {
    // Implementar modal de loading ou usar o existente
    console.log('Loading:', message);
}

function hideLoading() {
    // Esconder modal de loading
    console.log('Loading hidden');
}

function showNotification(message, type = 'info') {
    // Criar notificação toast
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 24px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 10000;
        transform: translateX(400px);
        transition: transform 0.3s ease;
    `;
    
    const colors = {
        success: '#10B981',
        error: '#EF4444', 
        warning: '#F59E0B',
        info: '#3B82F6'
    };
    
    notification.style.backgroundColor = colors[type] || colors.info;
    
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