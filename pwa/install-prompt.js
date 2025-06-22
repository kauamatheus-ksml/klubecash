/**
 * Klube Cash PWA - Install Prompt v1.0
 * Sistema de instalação inteligente com analytics e onboarding
 */

class KlubeCashInstaller {
    constructor() {
        this.deferredPrompt = null;
        this.isInstalled = false;
        this.hasShownPrompt = false;
        this.installButton = null;
        this.promptShownCount = 0;
        
        this.init();
    }

    // === INICIALIZAÇÃO ===
    init() {
        this.checkInstallationStatus();
        this.bindEvents();
        this.createInstallElements();
        this.setupAnalytics();
        
        console.log('🎉 Klube Cash Installer inicializado');
    }

    // === VERIFICAR STATUS DE INSTALAÇÃO ===
    checkInstallationStatus() {
        // Verificar se já está instalado
        if (window.matchMedia('(display-mode: standalone)').matches) {
            this.isInstalled = true;
            this.hideInstallPrompts();
            this.trackEvent('app_already_installed');
            return;
        }

        // Verificar localStorage para persistência
        const installData = this.getInstallData();
        this.promptShownCount = installData.promptCount || 0;
        this.hasShownPrompt = installData.hasShown || false;
    }

    // === EVENTOS DE INSTALAÇÃO ===
    bindEvents() {
        // Evento beforeinstallprompt - capturar o prompt nativo
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('📱 Prompt de instalação disponível');
            
            // Prevenir o prompt automático
            e.preventDefault();
            
            // Salvar o evento para uso posterior
            this.deferredPrompt = e;
            
            // Decidir se deve mostrar o prompt customizado
            this.evaluatePromptDisplay();
            
            this.trackEvent('install_prompt_available');
        });

        // Evento appinstalled - app foi instalado
        window.addEventListener('appinstalled', (e) => {
            console.log('✅ App instalado com sucesso!');
            
            this.isInstalled = true;
            this.hideInstallPrompts();
            this.showOnboarding();
            this.trackEvent('app_installed');
            
            // Limpar dados do localStorage
            this.clearInstallData();
        });

        // Detectar mudança para standalone mode
        window.matchMedia('(display-mode: standalone)').addEventListener('change', (e) => {
            if (e.matches) {
                this.isInstalled = true;
                this.hideInstallPrompts();
                this.trackEvent('app_launched_standalone');
            }
        });
    }

    // === AVALIAR SE DEVE MOSTRAR PROMPT ===
    evaluatePromptDisplay() {
        // Não mostrar se já instalado
        if (this.isInstalled) return;
        
        // Não mostrar se usuário rejeitou muitas vezes
        if (this.promptShownCount >= 3) {
            this.trackEvent('install_prompt_max_reached');
            return;
        }

        // Aguardar um tempo para não ser invasivo
        setTimeout(() => {
            this.showCustomPrompt();
        }, 5000); // 5 segundos após página carregar
    }

    // === CRIAR ELEMENTOS DE INSTALAÇÃO ===
    createInstallElements() {
        // Banner de instalação fixo (top)
        this.createInstallBanner();
        
        // Botão flutuante de instalação
        this.createFloatingInstallButton();
        
        // Modal de instalação
        this.createInstallModal();
    }

    // === BANNER DE INSTALAÇÃO ===
    createInstallBanner() {
        const banner = document.createElement('div');
        banner.id = 'install-banner';
        banner.className = 'install-banner hidden';
        banner.innerHTML = `
            <div class="install-banner-content">
                <div class="install-banner-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M12 2L13.09 8.26L22 9L13.09 9.74L12 16L10.91 9.74L2 9L10.91 8.26L12 2Z" fill="currentColor"/>
                    </svg>
                </div>
                <div class="install-banner-text">
                    <strong>Instale o Klube Cash</strong>
                    <span>Acesso rápido ao seu cashback</span>
                </div>
                <button class="install-banner-btn" data-action="install">
                    Instalar
                </button>
                <button class="install-banner-close" data-action="close">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M6 6L18 18M6 18L18 6" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </button>
            </div>
        `;

        document.body.appendChild(banner);

        // Eventos do banner
        banner.querySelector('[data-action="install"]').addEventListener('click', () => {
            this.triggerInstall('banner');
        });

        banner.querySelector('[data-action="close"]').addEventListener('click', () => {
            this.hideBanner();
            this.trackEvent('install_banner_dismissed');
        });

        this.installBanner = banner;
    }

    // === BOTÃO FLUTUANTE ===
    createFloatingInstallButton() {
        const button = document.createElement('button');
        button.id = 'floating-install-btn';
        button.className = 'floating-install-btn hidden';
        button.innerHTML = `
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M12 2L13.09 8.26L22 9L13.09 9.74L12 16L10.91 9.74L2 9L10.91 8.26L12 2Z" fill="white"/>
            </svg>
            <span>Instalar App</span>
        `;

        button.addEventListener('click', () => {
            this.showInstallModal();
            this.trackEvent('floating_button_clicked');
        });

        document.body.appendChild(button);
        this.floatingButton = button;
    }

    // === MODAL DE INSTALAÇÃO ===
    createInstallModal() {
        const modal = document.createElement('div');
        modal.id = 'install-modal';
        modal.className = 'install-modal hidden';
        modal.innerHTML = `
            <div class="install-modal-overlay" data-action="close"></div>
            <div class="install-modal-content">
                <div class="install-modal-header">
                    <h3>Instalar Klube Cash</h3>
                    <button class="install-modal-close" data-action="close">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M6 6L18 18M6 18L18 6" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </button>
                </div>
                
                <div class="install-modal-body">
                    <div class="install-modal-icon">
                        <img src="/assets/images/icon-128x128.png" alt="Klube Cash" width="80" height="80">
                    </div>
                    
                    <div class="install-modal-benefits">
                        <h4>Vantagens do App:</h4>
                        <ul>
                            <li>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                    <path d="M20 6L9 17L4 12" stroke="#10B981" stroke-width="2"/>
                                </svg>
                                Acesso rápido ao seu saldo
                            </li>
                            <li>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                    <path d="M20 6L9 17L4 12" stroke="#10B981" stroke-width="2"/>
                                </svg>
                                Notificações de cashback
                            </li>
                            <li>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                    <path d="M20 6L9 17L4 12" stroke="#10B981" stroke-width="2"/>
                                </svg>
                                Funciona mesmo offline
                            </li>
                            <li>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                    <path d="M20 6L9 17L4 12" stroke="#10B981" stroke-width="2"/>
                                </svg>
                                Carregamento mais rápido
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="install-modal-footer">
                    <button class="btn-secondary" data-action="close">
                        Agora não
                    </button>
                    <button class="btn-primary" data-action="install">
                        Instalar Agora
                    </button>
                </div>
            </div>
        `;

        // Eventos do modal
        modal.querySelectorAll('[data-action="close"]').forEach(element => {
            element.addEventListener('click', () => {
                this.hideInstallModal();
                this.trackEvent('install_modal_dismissed');
            });
        });

        modal.querySelector('[data-action="install"]').addEventListener('click', () => {
            this.triggerInstall('modal');
        });

        document.body.appendChild(modal);
        this.installModal = modal;
    }

    // === MOSTRAR PROMPT CUSTOMIZADO ===
    showCustomPrompt() {
        if (!this.deferredPrompt || this.hasShownPrompt) return;

        // Incrementar contador
        this.promptShownCount++;
        this.hasShownPrompt = true;
        this.saveInstallData();

        // Mostrar banner primeiro
        this.showBanner();

        // Mostrar botão flutuante após alguns segundos
        setTimeout(() => {
            this.showFloatingButton();
        }, 3000);

        this.trackEvent('install_prompt_shown', {
            count: this.promptShownCount
        });
    }

    // === TRIGGER DE INSTALAÇÃO ===
    async triggerInstall(source) {
        if (!this.deferredPrompt) {
            this.trackEvent('install_failed', { reason: 'no_prompt_available', source });
            this.showInstallInstructions();
            return;
        }

        try {
            // Mostrar prompt nativo
            this.deferredPrompt.prompt();

            // Aguardar escolha do usuário
            const { outcome } = await this.deferredPrompt.userChoice;

            this.trackEvent('install_prompt_result', {
                outcome,
                source,
                promptCount: this.promptShownCount
            });

            if (outcome === 'accepted') {
                console.log('✅ Usuário aceitou instalar o app');
                this.hideInstallPrompts();
            } else {
                console.log('❌ Usuário rejeitou a instalação');
                this.handleInstallRejection();
            }

            // Limpar o prompt usado
            this.deferredPrompt = null;

        } catch (error) {
            console.error('❌ Erro na instalação:', error);
            this.trackEvent('install_error', { error: error.message, source });
        }
    }

    // === LIDAR COM REJEIÇÃO ===
    handleInstallRejection() {
        this.hideInstallPrompts();
        
        // Se rejeitou 3 vezes, não mostrar mais
        if (this.promptShownCount >= 3) {
            this.saveInstallData({ neverShow: true });
        }
    }

    // === INSTRUÇÕES MANUAIS DE INSTALAÇÃO ===
    showInstallInstructions() {
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
        const isAndroid = /Android/.test(navigator.userAgent);
        
        let instructions = '';
        
        if (isIOS) {
            instructions = `
                <div class="install-instructions">
                    <h4>Como instalar no iOS:</h4>
                    <ol>
                        <li>Toque no botão de compartilhar <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M8 12V16H16V12" stroke="currentColor" stroke-width="2"/><path d="M12 8L12 16" stroke="currentColor" stroke-width="2"/><path d="M8 8L12 4L16 8" stroke="currentColor" stroke-width="2"/></svg></li>
                        <li>Selecione "Adicionar à Tela de Início"</li>
                        <li>Toque em "Adicionar"</li>
                    </ol>
                </div>
            `;
        } else if (isAndroid) {
            instructions = `
                <div class="install-instructions">
                    <h4>Como instalar no Android:</h4>
                    <ol>
                        <li>Toque no menu do navegador (⋮)</li>
                        <li>Selecione "Instalar app" ou "Adicionar à tela inicial"</li>
                        <li>Confirme a instalação</li>
                    </ol>
                </div>
            `;
        } else {
            instructions = `
                <div class="install-instructions">
                    <h4>Como instalar no Desktop:</h4>
                    <ol>
                        <li>Procure pelo ícone de instalação na barra de endereços</li>
                        <li>Ou use o menu do navegador para "Instalar aplicativo"</li>
                        <li>Confirme a instalação</li>
                    </ol>
                </div>
            `;
        }

        // Mostrar modal com instruções
        this.showCustomModal('Instalar Klube Cash', instructions);
    }

    // === ONBOARDING PÓS-INSTALAÇÃO ===
    showOnboarding() {
        // Aguardar um pouco para garantir que o app foi instalado
        setTimeout(() => {
            const onboardingData = {
                title: 'Bem-vindo ao Klube Cash!',
                content: `
                    <div class="onboarding-success">
                        <div class="onboarding-icon">
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" stroke="#10B981" stroke-width="2"/>
                                <path d="M8 12L11 15L16 9" stroke="#10B981" stroke-width="2"/>
                            </svg>
                        </div>
                        <h3>App instalado com sucesso!</h3>
                        <p>Agora você pode acessar seu cashback diretamente da tela inicial do seu dispositivo.</p>
                        
                        <div class="onboarding-tips">
                            <h4>Dicas para usar o app:</h4>
                            <ul>
                                <li>📱 Abra direto da tela inicial</li>
                                <li>🔔 Ative as notificações para não perder cashback</li>
                                <li>⚡ Funciona mesmo sem internet</li>
                                <li>🎯 Acesse lojas parceiras rapidamente</li>
                            </ul>
                        </div>
                    </div>
                `,
                actions: [
                    {
                        text: 'Ativar Notificações',
                        action: () => this.requestNotificationPermission(),
                        class: 'btn-primary'
                    },
                    {
                        text: 'Continuar',
                        action: () => this.finishOnboarding(),
                        class: 'btn-secondary'
                    }
                ]
            };

            this.showCustomModal(onboardingData.title, onboardingData.content, onboardingData.actions);
            this.trackEvent('onboarding_shown');
        }, 2000);
    }

    // === SOLICITAR PERMISSÃO DE NOTIFICAÇÃO ===
    async requestNotificationPermission() {
        if ('Notification' in window) {
            const permission = await Notification.requestPermission();
            this.trackEvent('notification_permission', { result: permission });
            
            if (permission === 'granted') {
                this.showToast('Notificações ativadas com sucesso!', 'success');
            }
        }
        
        this.finishOnboarding();
    }

    // === FINALIZAR ONBOARDING ===
    finishOnboarding() {
        this.hideCustomModal();
        this.trackEvent('onboarding_completed');
        
        // Salvar que o onboarding foi concluído
        localStorage.setItem('klube_onboarding_completed', 'true');
    }

    // === MÉTODOS DE EXIBIÇÃO ===
    showBanner() {
        if (this.installBanner) {
            this.installBanner.classList.remove('hidden');
            this.installBanner.classList.add('show');
        }
    }

    hideBanner() {
        if (this.installBanner) {
            this.installBanner.classList.remove('show');
            this.installBanner.classList.add('hidden');
        }
    }

    showFloatingButton() {
        if (this.floatingButton) {
            this.floatingButton.classList.remove('hidden');
            this.floatingButton.classList.add('show');
        }
    }

    hideFloatingButton() {
        if (this.floatingButton) {
            this.floatingButton.classList.remove('show');
            this.floatingButton.classList.add('hidden');
        }
    }

    showInstallModal() {
        if (this.installModal) {
            this.installModal.classList.remove('hidden');
            this.installModal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }

    hideInstallModal() {
        if (this.installModal) {
            this.installModal.classList.remove('show');
            this.installModal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    hideInstallPrompts() {
        this.hideBanner();
        this.hideFloatingButton();
        this.hideInstallModal();
    }

    // === MODAL CUSTOMIZADO ===
    showCustomModal(title, content, actions = []) {
        const modal = document.createElement('div');
        modal.className = 'custom-modal show';
        modal.innerHTML = `
            <div class="custom-modal-overlay"></div>
            <div class="custom-modal-content">
                <div class="custom-modal-header">
                    <h3>${title}</h3>
                </div>
                <div class="custom-modal-body">
                    ${content}
                </div>
                <div class="custom-modal-footer">
                    ${actions.map(action => 
                        `<button class="${action.class}" data-action="custom">${action.text}</button>`
                    ).join('')}
                </div>
            </div>
        `;

        // Eventos
        const buttons = modal.querySelectorAll('button[data-action="custom"]');
        buttons.forEach((button, index) => {
            button.addEventListener('click', () => {
                if (actions[index] && actions[index].action) {
                    actions[index].action();
                }
            });
        });

        document.body.appendChild(modal);
        document.body.style.overflow = 'hidden';
        
        this.customModal = modal;
    }

    hideCustomModal() {
        if (this.customModal) {
            this.customModal.remove();
            document.body.style.overflow = '';
            this.customModal = null;
        }
    }

    // === TOAST DE FEEDBACK ===
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type} show`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }

    // === GERENCIAMENTO DE DADOS ===
    getInstallData() {
        try {
            const data = localStorage.getItem('klube_install_data');
            return data ? JSON.parse(data) : {};
        } catch (error) {
            return {};
        }
    }

    saveInstallData(additionalData = {}) {
        const data = {
            ...this.getInstallData(),
            promptCount: this.promptShownCount,
            hasShown: this.hasShownPrompt,
            lastShown: Date.now(),
            ...additionalData
        };

        localStorage.setItem('klube_install_data', JSON.stringify(data));
    }

    clearInstallData() {
        localStorage.removeItem('klube_install_data');
    }

    // === ANALYTICS ===
    setupAnalytics() {
        // Configurar analytics básico
        this.analyticsEvents = [];
        
        // Enviar eventos em batch a cada 30 segundos
        setInterval(() => {
            this.sendAnalyticsBatch();
        }, 30000);
    }

    trackEvent(eventName, eventData = {}) {
        const event = {
            name: eventName,
            data: {
                ...eventData,
                timestamp: Date.now(),
                userAgent: navigator.userAgent,
                url: window.location.href,
                screenSize: `${window.screen.width}x${window.screen.height}`,
                installStatus: this.isInstalled ? 'installed' : 'not_installed'
            }
        };

        console.log(`📊 Analytics: ${eventName}`, event.data);
        
        this.analyticsEvents.push(event);
        
        // Se tiver muitos eventos, enviar imediatamente
        if (this.analyticsEvents.length >= 10) {
            this.sendAnalyticsBatch();
        }
    }

    async sendAnalyticsBatch() {
        if (this.analyticsEvents.length === 0) return;

        try {
            const response = await fetch('/api/pwa/analytics', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    events: this.analyticsEvents
                })
            });

            if (response.ok) {
                console.log(`📊 ${this.analyticsEvents.length} eventos enviados para analytics`);
                this.analyticsEvents = [];
            }
        } catch (error) {
            console.warn('⚠️ Erro ao enviar analytics:', error);
        }
    }
}

// === INICIALIZAÇÃO AUTOMÁTICA ===
// Aguardar DOM estar pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.klubeCashInstaller = new KlubeCashInstaller();
    });
} else {
    window.klubeCashInstaller = new KlubeCashInstaller();
}

// === EXPORT GLOBAL ===
window.KlubeCash = window.KlubeCash || {};
window.KlubeCash.Installer = KlubeCashInstaller;

// === FUNÇÕES DE CONVENIÊNCIA ===
// Função global para mostrar prompt de instalação
window.showInstallPrompt = function() {
    if (window.klubeCashInstaller) {
        window.klubeCashInstaller.showInstallModal();
    }
};

// Função global para verificar se está instalado
window.isAppInstalled = function() {
    return window.klubeCashInstaller ? window.klubeCashInstaller.isInstalled : false;
};

console.log('📱 Klube Cash Install Prompt inicializado - v1.0');