/**
 * JavaScript para integração OpenPix - Klube Cash
 */

class OpenPixIntegration {
    constructor() {
        this.config = window.pixConfig || {};
        this.isPolling = false;
        this.pollInterval = null;
        this.maxPollAttempts = 180; // 15 minutos (5s * 180)
        this.currentPollAttempts = 0;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.startExpirationCountdown();
        
        if (this.config.hasQrCode && this.config.chargeId) {
            this.startPaymentPolling();
        }
    }
    
    bindEvents() {
        // Botão gerar PIX
        const generateBtn = document.getElementById('generatePixBtn');
        if (generateBtn) {
            generateBtn.addEventListener('click', () => this.generatePix());
        }
        
        // Botão gerar novo PIX
        const generateNewBtn = document.getElementById('generateNewPix');
        if (generateNewBtn) {
            generateNewBtn.addEventListener('click', () => this.generatePix());
        }
        
        // Botão copiar código PIX
        const copyBtn = document.getElementById('copyPixCode');
        if (copyBtn) {
            copyBtn.addEventListener('click', () => this.copyPixCode());
        }
        
        // Detectar quando a página ganha foco (usuário voltou do app do banco)
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && this.config.hasQrCode) {
                this.checkPaymentStatus();
            }
        });
        
        // Detectar quando a janela ganha foco
        window.addEventListener('focus', () => {
            if (this.config.hasQrCode) {
                setTimeout(() => this.checkPaymentStatus(), 1000);
            }
        });
    }
    
    async generatePix() {
        try {
            this.showLoading('Gerando PIX...');
            
            const response = await fetch(`${this.config.apiUrl}?action=create_charge`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    payment_id: this.config.paymentId
                })
            });
            
            const result = await response.json();
            
            if (result.status) {
                this.displayPixCode(result.data);
                this.startPaymentPolling();
                this.startExpirationCountdown(result.data.expires_at);
                
                // Atualizar configuração
                this.config.hasQrCode = true;
                this.config.chargeId = result.data.charge_id;
                this.config.expiresAt = result.data.expires_at;
                
                this.showSuccess('PIX gerado com sucesso!');
            } else {
                this.showError(result.message || 'Erro ao gerar PIX');
            }
            
        } catch (error) {
            console.error('Erro ao gerar PIX:', error);
            this.showError('Erro de conexão. Tente novamente.');
        } finally {
            this.hideLoading();
        }
    }
    
    displayPixCode(pixData) {
        // Remover seção de geração
        const generateSection = document.querySelector('.generate-pix-section');
        if (generateSection) {
            generateSection.style.display = 'none';
        }
        
        // Remover seção de expirado
        const expiredSection = document.querySelector('.payment-expired');
        if (expiredSection) {
            expiredSection.style.display = 'none';
        }
        
        // Criar/mostrar seção de display do PIX
        let displaySection = document.querySelector('.pix-display-section');
        if (!displaySection) {
            displaySection = this.createPixDisplaySection(pixData);
            document.querySelector('.pix-interface').appendChild(displaySection);
        } else {
            this.updatePixDisplaySection(displaySection, pixData);
            displaySection.style.display = 'block';
        }
    }
    
    createPixDisplaySection(pixData) {
        const section = document.createElement('div');
        section.className = 'pix-display-section';
        
        section.innerHTML = `
            <div class="pix-qr-container">
                <div class="qr-code-wrapper">
                    <img id="pixQrImage" 
                         src="${pixData.qr_code_image}" 
                         alt="QR Code PIX"
                         class="qr-code">
                </div>
                
                <div class="pix-actions">
                    <button id="copyPixCode" class="pix-action-btn primary">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                        Copiar Código PIX
                    </button>
                    
                    ${pixData.payment_link ? `
                    <a href="${pixData.payment_link}" 
                       target="_blank" 
                       class="pix-action-btn secondary">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <polyline points="15,3 21,3 21,9"></polyline>
                            <line x1="10" y1="14" x2="21" y2="3"></line>
                        </svg>
                        Abrir no App
                    </a>
                    ` : ''}
                </div>
            </div>
            
            <div class="pix-info-display">
                <div class="info-item">
                    <span class="info-label">Valor:</span>
                    <span class="info-value">${pixData.value_formatted}</span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Expira em:</span>
                    <span class="info-value" id="pixExpiration" 
                          data-expires="${pixData.expires_at}">
                        ${this.formatDate(pixData.expires_at)}
                    </span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Status:</span>
                    <span class="info-value" id="pixStatus">Aguardando pagamento</span>
                </div>
            </div>
            
            <textarea id="pixCodeHidden" style="position: absolute; left: -9999px;" readonly>${pixData.qr_code}</textarea>
        `;
        
        // Re-bind events
        setTimeout(() => {
            const copyBtn = section.querySelector('#copyPixCode');
            if (copyBtn) {
                copyBtn.addEventListener('click', () => this.copyPixCode());
            }
        }, 100);
        
        return section;
    }
    
    updatePixDisplaySection(section, pixData) {
        const qrImage = section.querySelector('#pixQrImage');
        const pixCodeHidden = section.querySelector('#pixCodeHidden');
        const valueElement = section.querySelector('.info-value');
        const expirationElement = section.querySelector('#pixExpiration');
        
        if (qrImage) qrImage.src = pixData.qr_code_image;
        if (pixCodeHidden) pixCodeHidden.value = pixData.qr_code;
        if (valueElement) valueElement.textContent = pixData.value_formatted;
        if (expirationElement) {
            expirationElement.textContent = this.formatDate(pixData.expires_at);
            expirationElement.dataset.expires = pixData.expires_at;
        }
    }
    
    async copyPixCode() {
        try {
            const pixCodeElement = document.getElementById('pixCodeHidden');
            if (!pixCodeElement) return;
            
            const pixCode = pixCodeElement.value;
            
            // Tentar usar a API moderna de clipboard
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(pixCode);
            } else {
                // Fallback para browsers mais antigos
                pixCodeElement.select();
                pixCodeElement.setSelectionRange(0, 99999);
                document.execCommand('copy');
            }
            
            this.showSuccess('Código PIX copiado!');
            
            // Mudar texto do botão temporariamente
            const copyBtn = document.getElementById('copyPixCode');
            if (copyBtn) {
                const originalText = copyBtn.innerHTML;
                copyBtn.innerHTML = `
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20,6 9,17 4,12"></polyline>
                    </svg>
                    Copiado!
                `;
                
                setTimeout(() => {
                    copyBtn.innerHTML = originalText;
                }, 2000);
            }
            
        } catch (error) {
            console.error('Erro ao copiar código PIX:', error);
            this.showError('Erro ao copiar código');
        }
    }
    
    startPaymentPolling() {
        if (this.isPolling) return;
        
        this.isPolling = true;
        this.currentPollAttempts = 0;
        
        this.pollInterval = setInterval(() => {
            this.checkPaymentStatus();
        }, 5000); // Verificar a cada 5 segundos
    }
    
    stopPaymentPolling() {
        this.isPolling = false;
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    }
    
    async checkPaymentStatus() {
        if (!this.config.chargeId || this.currentPollAttempts >= this.maxPollAttempts) {
            this.stopPaymentPolling();
            return;
        }
        
        this.currentPollAttempts++;
        
        try {
            const response = await fetch(
                `${this.config.apiUrl}?action=status&charge_id=${this.config.chargeId}`,
                { method: 'GET' }
            );
            
            const result = await response.json();
            
            if (result.status && result.data) {
                const status = result.data.charge_status;
                
                this.updatePixStatus(status);
                
                if (status === 'COMPLETED' || status === 'CONFIRMED') {
                    this.handlePaymentConfirmed();
                }
            }
            
        } catch (error) {
            console.error('Erro ao verificar status do pagamento:', error);
        }
    }
    
    updatePixStatus(status) {
        const statusElement = document.getElementById('pixStatus');
        if (!statusElement) return;
        
        const statusMap = {
            'ACTIVE': 'Aguardando pagamento',
            'COMPLETED': 'Pagamento confirmado',
            'CONFIRMED': 'Pagamento confirmado',
            'EXPIRED': 'PIX expirado',
            'CANCELED': 'PIX cancelado'
        };
        
        statusElement.textContent = statusMap[status] || status;
        statusElement.className = `info-value status-${status.toLowerCase()}`;
    }
    
    handlePaymentConfirmed() {
        this.stopPaymentPolling();
        
        this.showSuccess('Pagamento confirmado! Redirecionando...');
        
        // Aguardar um pouco para mostrar a mensagem e então recarregar
        setTimeout(() => {
            window.location.reload();
        }, 2000);
    }
    
    startExpirationCountdown(expiresAt = null) {
        const expirationElement = document.getElementById('pixExpiration');
        if (!expirationElement) return;
        
        const expires = expiresAt || expirationElement.dataset.expires;
        if (!expires) return;
        
        const expirationTime = new Date(expires).getTime();
        
        const updateCountdown = () => {
            const now = new Date().getTime();
            const distance = expirationTime - now;
            
            if (distance <= 0) {
                expirationElement.textContent = 'Expirado';
                expirationElement.className = 'info-value expired';
                this.stopPaymentPolling();
                this.showPixExpired();
                return;
            }
            
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            expirationElement.textContent = `${minutes}m ${seconds}s`;
            
            // Avisar quando restam poucos minutos
            if (minutes <= 2 && !expirationElement.classList.contains('warning')) {
                expirationElement.classList.add('warning');
                this.showWarning('PIX expira em breve!');
            }
        };
        
        updateCountdown();
        setInterval(updateCountdown, 1000);
    }
    
    showPixExpired() {
        const displaySection = document.querySelector('.pix-display-section');
        if (displaySection) {
            displaySection.style.display = 'none';
        }
        
        const expiredSection = document.querySelector('.payment-expired');
        if (expiredSection) {
            expiredSection.style.display = 'block';
        } else {
            // Criar seção de expirado se não existir
            const pixInterface = document.querySelector('.pix-interface');
            if (pixInterface) {
                const expiredDiv = document.createElement('div');
                expiredDiv.className = 'payment-expired';
                expiredDiv.innerHTML = `
                    <div class="warning-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                    <h2>PIX Expirado</h2>
                    <p>Este PIX expirou. Gere um novo código para continuar com o pagamento.</p>
                    <button id="generateNewPix" class="pix-action-btn primary">
                        Gerar Novo PIX
                    </button>
                `;
                
                pixInterface.appendChild(expiredDiv);
                
                // Bind event do novo botão
                const newBtn = expiredDiv.querySelector('#generateNewPix');
                if (newBtn) {
                    newBtn.addEventListener('click', () => this.generatePix());
                }
            }
        }
    }
    
    // Métodos de utilitários
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR') + ' ' + 
               date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    }
    
    showLoading(message = 'Carregando...') {
        const modal = document.getElementById('loadingModal');
        if (modal) {
            const messageElement = modal.querySelector('p');
            if (messageElement) messageElement.textContent = message;
            modal.style.display = 'flex';
        }
    }
    
    hideLoading() {
        const modal = document.getElementById('loadingModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }
    
    showSuccess(message) {
        this.showNotification(message, 'success');
    }
    
    showError(message) {
        this.showNotification(message, 'error');
    }
    
    showWarning(message) {
        this.showNotification(message, 'warning');
    }
    
    showNotification(message, type = 'info') {
        // Criar notificação se não existir um sistema
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
    new OpenPixIntegration();
});

// Exportar para uso global se necessário
window.OpenPixIntegration = OpenPixIntegration;