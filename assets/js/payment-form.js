/**
 * JavaScript para formulário de pagamento de comissões
 */

class PaymentForm {
    constructor() {
        this.config = window.paymentConfig || {};
        this.form = document.getElementById('paymentForm');
        this.submitButton = document.getElementById('submitPayment');
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.setupPaymentMethods();
    }
    
    bindEvents() {
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        }
        
        // Bind payment method changes
        const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
        paymentMethods.forEach(method => {
            method.addEventListener('change', () => this.handleMethodChange());
        });
    }
    
    setupPaymentMethods() {
        // Adicionar interatividade aos métodos de pagamento
        const methodLabels = document.querySelectorAll('.payment-method');
        
        methodLabels.forEach(label => {
            label.addEventListener('click', () => {
                // Remover active de todos
                methodLabels.forEach(l => l.classList.remove('active'));
                // Adicionar active ao clicado
                label.classList.add('active');
            });
        });
    }
    
    async handleSubmit(e) {
        e.preventDefault();
        
        try {
            this.setLoading(true);
            
            const formData = new FormData(this.form);
            formData.append('action', 'create_commission_payment');
            
            // Converter transaction_ids para array
            const transactionIds = formData.get('transaction_ids').split(',');
            formData.delete('transaction_ids');
            
            transactionIds.forEach(id => {
                formData.append('transaction_ids[]', id);
            });
            
            const response = await fetch(this.config.paymentController, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.status) {
                this.showSuccess(result.message);
                
                // Redirecionar para página de PIX se fornecida
                if (result.data && result.data.redirect_url) {
                    setTimeout(() => {
                        window.location.href = result.data.redirect_url;
                    }, 1500);
                } else {
                    setTimeout(() => {
                        window.location.href = this.config.baseUrl + '/loja/historico-pagamentos';
                    }, 2000);
                }
            } else {
                this.showError(result.message || 'Erro ao processar pagamento');
            }
            
        } catch (error) {
            console.error('Erro ao enviar formulário:', error);
            this.showError('Erro de conexão. Tente novamente.');
        } finally {
            this.setLoading(false);
        }
    }
    
    handleMethodChange() {
        const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
        
        if (selectedMethod) {
            // Atualizar texto do botão baseado no método
            const methodText = {
                'pix_openpix': 'Gerar PIX',
                'transferencia': 'Solicitar Transferência',
                'boleto': 'Gerar Boleto'
            };
            
            const buttonText = methodText[selectedMethod.value] || 'Confirmar Pagamento';
            
            if (this.submitButton) {
                const buttonSpan = this.submitButton.querySelector('span') || this.submitButton;
                buttonSpan.textContent = buttonText;
            }
        }
    }
    
    setLoading(loading) {
        if (this.submitButton) {
            this.submitButton.disabled = loading;
            
            if (loading) {
                this.submitButton.innerHTML = `
                    <div class="loading-spinner-small"></div>
                    Processando...
                `;
            } else {
                this.submitButton.innerHTML = `
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9,11 12,14 22,4"></polyline>
                        <path d="M21,12v7a2,2 0,0 1,-2,2H5a2,2 0,0 1,-2,-2V5a2,2 0,0 1,2,-2h11"></path>
                    </svg>
                    Confirmar Pagamento
                `;
            }
        }
        
        const modal = document.getElementById('loadingModal');
        if (modal) {
            modal.style.display = loading ? 'flex' : 'none';
        }
    }
    
    showSuccess(message) {
        this.showNotification(message, 'success');
    }
    
    showError(message) {
        this.showNotification(message, 'error');
    }
    
    showNotification(message, type = 'info') {
        // Criar notificação
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 10000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            max-width: 400px;
        `;
        
        // Cores baseadas no tipo
        const colors = {
            success: '#10B981',
            error: '#EF4444',
            warning: '#F59E0B',
            info: '#3B82F6'
        };
        
        notification.style.backgroundColor = colors[type] || colors.info;
        
        document.body.appendChild(notification);
        
        // Animar entrada
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 10);
        
        // Remover após 5 segundos
        setTimeout(() => {
            notification.style.transform = 'translateX(400px)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 5000);
    }
}

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    new PaymentForm();
});