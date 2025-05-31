// assets/js/client/dashboard-new.js
// JavaScript para o novo dashboard do cliente

class ClientDashboard {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadDynamicContent();
        this.initializeAnimations();
        
        // Verificar se é primeira visita
        if (this.isFirstVisit()) {
            this.showWelcomeTour();
        }
    }

    setupEventListeners() {
        // Event listeners para modais
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeAllModals();
            }
        });

        // Event listeners para teclado
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllModals();
            }
        });

        // Event listeners para cards de loja
        document.querySelectorAll('.store-balance-card').forEach(card => {
            card.addEventListener('click', (e) => {
                const storeId = e.currentTarget.dataset.storeId;
                if (storeId) {
                    this.openStoreDetails(storeId);
                }
            });
        });

        // Event listener para refresh de dados
        this.setupRefreshButton();
    }

    loadDynamicContent() {
        // Carregar notificações em tempo real
        this.loadNotifications();
        
        // Carregar saldo atualizado
        this.updateBalance();
        
        // Setup auto-refresh a cada 5 minutos
        setInterval(() => {
            this.updateBalance();
            this.loadNotifications();
        }, 300000); // 5 minutos
    }

    initializeAnimations() {
        // Animar números ao carregar
        this.animateCounters();
        
        // Animar cards ao entrar na viewport
        this.setupScrollAnimations();
        
        // Adicionar micro-interações
        this.setupMicroInteractions();
    }

    // === ANIMAÇÕES ===
    animateCounters() {
        const counters = document.querySelectorAll('.amount-value, .stat-number');
        
        counters.forEach(counter => {
            const target = this.parseNumberFromText(counter.textContent);
            if (isNaN(target)) return;

            const duration = 1500;
            const start = performance.now();
            const isCurrency = counter.classList.contains('amount-value');

            const animate = (currentTime) => {
                const elapsed = currentTime - start;
                const progress = Math.min(elapsed / duration, 1);
                
                const current = target * this.easeOutCubic(progress);
                
                if (isCurrency) {
                    counter.textContent = this.formatCurrency(current);
                } else {
                    counter.textContent = Math.floor(current).toString();
                }
                
                if (progress < 1) {
                    requestAnimationFrame(animate);
                }
            };
            
            counter.textContent = isCurrency ? 'R$ 0,00' : '0';
            requestAnimationFrame(animate);
        });
    }

    setupScrollAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Aplicar a elementos que devem animar
        document.querySelectorAll('.info-card, .store-balance-card, .nav-item').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
    }

    setupMicroInteractions() {
        // Efeito de ripple nos botões
        document.querySelectorAll('.btn, .action-btn, .card-action-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.createRippleEffect(e);
            });
        });

        // Hover effect nos cards
        document.querySelectorAll('.info-card, .store-balance-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-4px)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
            });
        });
    }

    createRippleEffect(e) {
        const button = e.currentTarget;
        const rect = button.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        
        const ripple = document.createElement('span');
        ripple.style.cssText = `
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            transform: scale(0);
            animation: ripple 0.6s linear;
            left: ${x}px;
            top: ${y}px;
            width: ${size}px;
            height: ${size}px;
            pointer-events: none;
        `;
        
        button.style.position = 'relative';
        button.style.overflow = 'hidden';
        button.appendChild(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    }

    // === MODAIS ===
    openBalanceModal() {
        const modal = document.getElementById('balanceModal');
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Animar entrada
            const content = modal.querySelector('.modal-content');
            content.style.transform = 'scale(0.9)';
            content.style.opacity = '0';
            
            setTimeout(() => {
                content.style.transition = 'all 0.3s ease';
                content.style.transform = 'scale(1)';
                content.style.opacity = '1';
            }, 10);
        }
    }

    closeBalanceModal() {
        const modal = document.getElementById('balanceModal');
        if (modal) {
            const content = modal.querySelector('.modal-content');
            content.style.transform = 'scale(0.9)';
            content.style.opacity = '0';
            
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }, 300);
        }
    }

    closeAllModals() {
        document.querySelectorAll('.modal').forEach(modal => {
            if (modal.style.display === 'flex') {
                modal.style.display = 'none';
            }
        });
        document.body.style.overflow = 'auto';
    }

    // === DADOS DINÂMICOS ===
    async updateBalance() {
        try {
            const response = await fetch('../../controllers/ClientController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=balance'
            });
            
            const data = await response.json();
            
            if (data.status) {
                this.updateBalanceDisplay(data.data);
            }
        } catch (error) {
            console.error('Erro ao atualizar saldo:', error);
        }
    }

    updateBalanceDisplay(balanceData) {
        const amountElement = document.querySelector('.amount-value');
        if (amountElement && balanceData.saldo_total !== undefined) {
            const currentValue = this.parseNumberFromText(amountElement.textContent);
            const newValue = balanceData.saldo_total;
            
            if (currentValue !== newValue) {
                this.animateValueChange(amountElement, currentValue, newValue, true);
            }
        }

        // Atualizar outros elementos se necessário
        this.updateStoreBalances(balanceData.saldos_por_loja || []);
    }

    updateStoreBalances(storeBalances) {
        storeBalances.forEach(store => {
            const storeCard = document.querySelector(`[data-store-id="${store.loja_id}"]`);
            if (storeCard) {
                const balanceElement = storeCard.querySelector('.store-balance-amount');
                if (balanceElement) {
                    balanceElement.textContent = this.formatCurrency(store.saldo_disponivel);
                }
            }
        });
    }

    async loadNotifications() {
        try {
            const response = await fetch('../../controllers/ClientController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_notifications'
            });
            
            const data = await response.json();
            
            if (data.status && data.notifications) {
                this.updateNotificationsDisplay(data.notifications);
            }
        } catch (error) {
            console.error('Erro ao carregar notificações:', error);
        }
    }

    updateNotificationsDisplay(notifications) {
        const container = document.querySelector('.notifications-list');
        if (!container) return;

        if (notifications.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <p>Tudo em dia!</p>
                    <small>Você será notificado sobre novos cashbacks</small>
                </div>
            `;
            return;
        }

        container.innerHTML = notifications.map(notification => `
            <div class="notification-item">
                <div class="notification-icon notification-${notification.tipo}">
                    <i class="fas fa-${this.getNotificationIcon(notification.tipo)}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${this.escapeHtml(notification.titulo)}</div>
                    <div class="notification-message">${this.escapeHtml(notification.mensagem)}</div>
                    <div class="notification-date">${this.formatDate(notification.data_criacao)}</div>
                </div>
            </div>
        `).join('');
    }

    // === DETALHES DA LOJA ===
    async openStoreDetails(storeId) {
        try {
            const response = await fetch('../../controllers/client_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=store_balance_details&loja_id=${storeId}`
            });
            
            const data = await response.json();
            
            if (data.status) {
                this.showStoreDetailsModal(data.data);
            } else {
                this.showAlert('Erro ao carregar detalhes da loja', 'error');
            }
        } catch (error) {
            console.error('Erro ao carregar detalhes da loja:', error);
            this.showAlert('Erro ao carregar detalhes da loja', 'error');
        }
    }

    showStoreDetailsModal(storeData) {
        // Implementar modal de detalhes da loja
        console.log('Detalhes da loja:', storeData);
        // TODO: Criar modal com histórico de movimentações da loja
    }

    // === TOUR DE BOAS-VINDAS ===
    isFirstVisit() {
        return !localStorage.getItem('dashboard_visited');
    }

    showWelcomeTour() {
        localStorage.setItem('dashboard_visited', 'true');
        
        // Implementar tour interativo
        const tour = [
            {
                element: '.balance-hero-card',
                title: 'Seu Saldo de Cashback',
                content: 'Aqui você vê todo o dinheiro que ganhou de volta em suas compras!'
            },
            {
                element: '.stores-balance-section',
                title: 'Saldo por Loja',
                content: 'Cada loja tem um saldo separado que só pode ser usado nela mesma.'
            },
            {
                element: '.quick-navigation',
                title: 'Navegação Rápida',
                content: 'Use esses atalhos para acessar rapidamente as principais funcionalidades.'
            }
        ];

        this.startTour(tour);
    }

    startTour(steps) {
        let currentStep = 0;
        
        const showStep = () => {
            if (currentStep >= steps.length) {
                this.endTour();
                return;
            }
            
            const step = steps[currentStep];
            const element = document.querySelector(step.element);
            
            if (element) {
                this.highlightElement(element);
                this.showTourTooltip(element, step.title, step.content, currentStep + 1, steps.length);
            }
        };

        // Criar overlay do tour
        this.createTourOverlay();
        showStep();

        // Event listener para próximo passo
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('tour-next')) {
                currentStep++;
                showStep();
            } else if (e.target.classList.contains('tour-skip')) {
                this.endTour();
            }
        });
    }

    createTourOverlay() {
        const overlay = document.createElement('div');
        overlay.className = 'tour-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9998;
            pointer-events: none;
        `;
        document.body.appendChild(overlay);
    }

    highlightElement(element) {
        // Remove highlight anterior
        document.querySelectorAll('.tour-highlight').forEach(el => {
            el.classList.remove('tour-highlight');
        });

        // Adiciona highlight ao elemento atual
        element.classList.add('tour-highlight');
        element.style.position = 'relative';
        element.style.zIndex = '9999';
        
        // Scroll para o elemento
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    showTourTooltip(element, title, content, step, total) {
        // Remove tooltip anterior
        const existingTooltip = document.querySelector('.tour-tooltip');
        if (existingTooltip) {
            existingTooltip.remove();
        }

        const tooltip = document.createElement('div');
        tooltip.className = 'tour-tooltip';
        tooltip.innerHTML = `
            <div class="tour-content">
                <h3>${title}</h3>
                <p>${content}</p>
                <div class="tour-controls">
                    <span class="tour-progress">${step} de ${total}</span>
                    <div class="tour-buttons">
                        <button class="tour-skip">Pular Tour</button>
                        <button class="tour-next">${step === total ? 'Finalizar' : 'Próximo'}</button>
                    </div>
                </div>
            </div>
            <div class="tour-arrow"></div>
        `;
        
        tooltip.style.cssText = `
            position: absolute;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            z-index: 10000;
            max-width: 300px;
            pointer-events: auto;
        `;

        document.body.appendChild(tooltip);
        this.positionTooltip(tooltip, element);
    }

    positionTooltip(tooltip, element) {
        const elementRect = element.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        
        let top = elementRect.bottom + 10;
        let left = elementRect.left + (elementRect.width / 2) - (tooltipRect.width / 2);
        
        // Ajustar se sair da tela
        if (left < 10) left = 10;
        if (left + tooltipRect.width > window.innerWidth - 10) {
            left = window.innerWidth - tooltipRect.width - 10;
        }
        if (top + tooltipRect.height > window.innerHeight - 10) {
            top = elementRect.top - tooltipRect.height - 10;
        }
        
        tooltip.style.top = `${top}px`;
        tooltip.style.left = `${left}px`;
    }

    endTour() {
        // Remove overlay e tooltips
        document.querySelectorAll('.tour-overlay, .tour-tooltip').forEach(el => {
            el.remove();
        });
        
        // Remove highlights
        document.querySelectorAll('.tour-highlight').forEach(el => {
            el.classList.remove('tour-highlight');
            el.style.position = '';
            el.style.zIndex = '';
        });
    }

    // === UTILIDADES ===
    parseNumberFromText(text) {
        const cleanText = text.replace(/[^\d,-]/g, '').replace(',', '.');
        return parseFloat(cleanText) || 0;
    }

    formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value);
    }

    formatDate(dateString) {
        return new Date(dateString).toLocaleString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    getNotificationIcon(type) {
        const icons = {
            'success': 'check-circle',
            'warning': 'exclamation-triangle',
            'error': 'times-circle',
            'info': 'info-circle'
        };
        return icons[type] || 'info-circle';
    }

    easeOutCubic(t) {
        return 1 - Math.pow(1 - t, 3);
    }

    animateValueChange(element, fromValue, toValue, isCurrency = false) {
        const duration = 800;
        const start = performance.now();
        
        const animate = (currentTime) => {
            const elapsed = currentTime - start;
            const progress = Math.min(elapsed / duration, 1);
            
            const current = fromValue + (toValue - fromValue) * this.easeOutCubic(progress);
            
            if (isCurrency) {
                element.textContent = this.formatCurrency(current);
            } else {
                element.textContent = Math.floor(current).toString();
            }
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };
        
        requestAnimationFrame(animate);
    }

    setupRefreshButton() {
        // Adicionar botão de refresh se não existir
        if (!document.querySelector('.refresh-btn')) {
            const refreshBtn = document.createElement('button');
            refreshBtn.className = 'refresh-btn';
            refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i>';
            refreshBtn.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                width: 50px;
                height: 50px;
                border-radius: 50%;
                background: var(--primary-color);
                color: white;
                border: none;
                box-shadow: var(--shadow-lg);
                cursor: pointer;
                z-index: 1000;
                transition: all var(--transition-normal);
            `;
            
            refreshBtn.addEventListener('click', () => {
                refreshBtn.classList.add('spinning');
                this.updateBalance();
                this.loadNotifications();
                
                setTimeout(() => {
                    refreshBtn.classList.remove('spinning');
                }, 1000);
            });
            
            document.body.appendChild(refreshBtn);
        }
    }

    showAlert(message, type = 'info') {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            z-index: 10000;
            animation: slideInRight 0.3s ease;
        `;
        
        const colors = {
            'success': '#10B981',
            'warning': '#F59E0B',
            'error': '#EF4444',
            'info': '#3B82F6'
        };
        
        alert.style.background = colors[type] || colors['info'];
        alert.textContent = message;
        
        document.body.appendChild(alert);
        
        setTimeout(() => {
            alert.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => alert.remove(), 300);
        }, 4000);
    }
}

// Adicionar estilos CSS necessários para as animações
const styles = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .spinning {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .tour-highlight {
        box-shadow: 0 0 0 4px rgba(255, 122, 0, 0.5) !important;
        border-radius: 8px !important;
    }
    
    .tour-tooltip .tour-content h3 {
        margin: 0 0 10px 0;
        color: #1F2937;
        font-size: 18px;
        font-weight: 600;
    }
    
    .tour-tooltip .tour-content p {
        margin: 0 0 15px 0;
        color: #4B5563;
        line-height: 1.5;
    }
    
    .tour-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .tour-progress {
        font-size: 12px;
        color: #6B7280;
    }
    
    .tour-buttons {
        display: flex;
        gap: 10px;
    }
    
    .tour-skip, .tour-next {
        padding: 6px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
    }
    
    .tour-skip {
        background: #F3F4F6;
        color: #4B5563;
    }
    
    .tour-next {
        background: #FF7A00;
        color: white;
    }
    
    .tour-skip:hover {
        background: #E5E7EB;
    }
    
    .tour-next:hover {
        background: #E66C00;
    }
`;

// Adicionar estilos ao head
const styleSheet = document.createElement('style');
styleSheet.textContent = styles;
document.head.appendChild(styleSheet);

// Inicializar dashboard quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    new ClientDashboard();
});

// Expor funções globais necessárias
window.openBalanceModal = () => {
    if (window.clientDashboard) {
        window.clientDashboard.openBalanceModal();
    }
};

window.closeBalanceModal = () => {
    if (window.clientDashboard) {
        window.clientDashboard.closeBalanceModal();
    }
};

window.openStoreDetails = (storeId) => {
    if (window.clientDashboard) {
        window.clientDashboard.openStoreDetails(storeId);
    }
};

// Armazenar instância global
window.clientDashboard = new ClientDashboard();