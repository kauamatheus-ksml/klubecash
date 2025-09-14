/**
 * Sidebar Lojista - Funcionalidades JavaScript
 * Sistema completo de navegação responsiva
 */

class SidebarLojista {
    constructor() {
        this.sidebar = document.getElementById('sidebarLojista');
        this.container = document.getElementById('sidebarLojistaContainer');
        this.overlay = document.getElementById('sidebarLojistaOverlay');
        this.toggleBtn = document.getElementById('sidebarLojistaToggle');
        this.mobileToggleBtn = document.getElementById('sidebarLojistaMobileToggle');
        
        this.isCollapsed = this.getStoredState();
        this.isMobile = window.innerWidth <= 768;
        this.isTablet = window.innerWidth > 768 && window.innerWidth <= 1024;
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.applyInitialState();
        this.adjustMainContent();
        this.setupKeyboardShortcuts();
        this.addTooltips();
        
        // Log de inicialização
        console.log('Sidebar Lojista inicializada');
    }
    
    setupEventListeners() {
        // Toggle desktop
        if (this.toggleBtn) {
            this.toggleBtn.addEventListener('click', () => this.toggleSidebar());
        }
        
        // Toggle mobile
        if (this.mobileToggleBtn) {
            this.mobileToggleBtn.addEventListener('click', () => this.toggleMobileSidebar());
        }
        
        // Overlay mobile
        if (this.overlay) {
            this.overlay.addEventListener('click', () => this.closeMobileSidebar());
        }
        
        // Redimensionamento da tela
        window.addEventListener('resize', () => this.handleResize());
        
        // Links de navegação
        this.setupNavigationLinks();
        
        // Escape key para fechar mobile
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isMobileOpen()) {
                this.closeMobileSidebar();
            }
        });
    }
    
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl + B para toggle da sidebar
            if (e.ctrlKey && e.key === 'b') {
                e.preventDefault();
                if (this.isMobile) {
                    this.toggleMobileSidebar();
                } else {
                    this.toggleSidebar();
                }
            }
        });
    }
    
    setupNavigationLinks() {
        const links = this.sidebar?.querySelectorAll('.navegacao-lojista-link');
        if (!links) return;
        
        links.forEach(link => {
            link.addEventListener('click', (e) => {
                // Adicionar estado de loading
                link.classList.add('carregando');
                
                // Remover loading após um tempo (caso não haja redirecionamento)
                setTimeout(() => {
                    link.classList.remove('carregando');
                }, 2000);
                
                // Fechar sidebar mobile após click
                if (this.isMobile && this.isMobileOpen()) {
                    setTimeout(() => {
                        this.closeMobileSidebar();
                    }, 150);
                }
            });
        });
    }
    
    addTooltips() {
        if (this.isMobile) return;
        
        const links = this.sidebar?.querySelectorAll('.navegacao-lojista-link');
        const logoutBtn = this.sidebar?.querySelector('.botao-lojista-sair');
        
        if (links) {
            links.forEach(link => {
                const text = link.querySelector('.navegacao-lojista-texto')?.textContent;
                if (text) {
                    link.setAttribute('data-tooltip', text);
                }
            });
        }
        
        if (logoutBtn) {
            const text = logoutBtn.querySelector('.navegacao-lojista-texto')?.textContent;
            if (text) {
                logoutBtn.setAttribute('data-tooltip', text);
            }
        }
    }
    
    toggleSidebar() {
        if (this.isMobile) return;
        
        this.isCollapsed = !this.isCollapsed;
        this.applySidebarState();
        this.storeState();
        this.adjustMainContent();
        
        // Trigger evento personalizado
        window.dispatchEvent(new CustomEvent('sidebarLojistaToggle', {
            detail: { collapsed: this.isCollapsed }
        }));
    }
    
    toggleMobileSidebar() {
        if (!this.isMobile) return;
        
        if (this.isMobileOpen()) {
            this.closeMobileSidebar();
        } else {
            this.openMobileSidebar();
        }
    }
    
    openMobileSidebar() {
        if (!this.sidebar || !this.overlay) return;
        
        this.sidebar.classList.add('aberta');
        this.overlay.classList.add('ativa');
        document.body.classList.add('sidebar-lojista-mobile-aberta');
        
        // Aria attributes
        this.sidebar.setAttribute('aria-hidden', 'false');
        this.mobileToggleBtn?.setAttribute('aria-expanded', 'true');
    }
    
    closeMobileSidebar() {
        if (!this.sidebar || !this.overlay) return;
        
        this.sidebar.classList.remove('aberta');
        this.overlay.classList.remove('ativa');
        document.body.classList.remove('sidebar-lojista-mobile-aberta');
        
        // Aria attributes
        this.sidebar.setAttribute('aria-hidden', 'true');
        this.mobileToggleBtn?.setAttribute('aria-expanded', 'false');
    }
    
    isMobileOpen() {
        return this.sidebar?.classList.contains('aberta') || false;
    }
    
    applySidebarState() {
        if (!this.sidebar) return;
        
        if (this.isCollapsed && !this.isMobile) {
            this.sidebar.classList.add('colapsada');
        } else {
            this.sidebar.classList.remove('colapsada');
        }
    }
    
    applyInitialState() {
        // Detectar dispositivo
        this.updateDeviceType();
        
        // Aplicar estado inicial
        if (!this.isMobile) {
            this.applySidebarState();
        }
        
        // Configurar aria attributes
        if (this.isMobile) {
            this.sidebar?.setAttribute('aria-hidden', 'true');
            this.mobileToggleBtn?.setAttribute('aria-expanded', 'false');
        }
    }
    
    handleResize() {
        const wasMobile = this.isMobile;
        this.updateDeviceType();
        
        // Se mudou de mobile para desktop
        if (wasMobile && !this.isMobile) {
            this.closeMobileSidebar();
            this.applySidebarState();
        }
        
        // Se mudou de desktop para mobile
        if (!wasMobile && this.isMobile) {
            this.sidebar?.classList.remove('colapsada');
            this.closeMobileSidebar();
        }
        
        this.adjustMainContent();
        
        // Reconfigurar tooltips
        if (!this.isMobile) {
            this.addTooltips();
        }
    }
    
    updateDeviceType() {
        this.isMobile = window.innerWidth <= 768;
        this.isTablet = window.innerWidth > 768 && window.innerWidth <= 1024;
    }
    
    adjustMainContent() {
        // Procurar elemento de conteúdo principal
        const mainElements = [
            '.main-content',
            '.content',
            '.page-content',
            'main',
            '.conteudo-principal',
            '.dashboard-container > div:not(.sidebar-lojista-container)'
        ];
        
        let mainContent = null;
        for (const selector of mainElements) {
            mainContent = document.querySelector(selector);
            if (mainContent) break;
        }
        
        if (!mainContent) return;
        
        // Aplicar classe para transições
        if (!mainContent.classList.contains('conteudo-com-sidebar-lojista')) {
            mainContent.classList.add('conteudo-com-sidebar-lojista');
        }
        
        // Ajustar baseado no estado atual
        if (this.isMobile) {
            mainContent.classList.remove('sidebar-colapsada');
        } else {
            if (this.isCollapsed) {
                mainContent.classList.add('sidebar-colapsada');
            } else {
                mainContent.classList.remove('sidebar-colapsada');
            }
        }
    }
    
    getStoredState() {
        const stored = localStorage.getItem('sidebarLojistaColapsada');
        return stored === 'true';
    }
    
    storeState() {
        localStorage.setItem('sidebarLojistaColapsada', this.isCollapsed.toString());
    }
    
    // Métodos públicos para controle externo
    collapse() {
        if (!this.isMobile && !this.isCollapsed) {
            this.toggleSidebar();
        }
    }
    
    expand() {
        if (!this.isMobile && this.isCollapsed) {
            this.toggleSidebar();
        }
    }
    
    getCurrentState() {
        return {
            collapsed: this.isCollapsed,
            mobile: this.isMobile,
            mobileOpen: this.isMobileOpen()
        };
    }
    
    // Método para atualizar item ativo
    setActiveItem(itemId) {
        const links = this.sidebar?.querySelectorAll('.navegacao-lojista-link');
        if (!links) return;
        
        links.forEach(link => {
            link.classList.remove('ativo');
            if (link.getAttribute('data-menu-id') === itemId) {
                link.classList.add('ativo');
            }
        });
    }
    
    // Método para adicionar badge de notificação
    addBadge(itemId, count) {
        const link = this.sidebar?.querySelector(`[data-menu-id="${itemId}"]`);
        if (!link) return;
        
        // Remove badge existente
        const existingBadge = link.querySelector('.navegacao-badge');
        if (existingBadge) {
            existingBadge.remove();
        }
        
        // Adiciona novo badge se count > 0
        if (count > 0) {
            const badge = document.createElement('span');
            badge.className = 'navegacao-badge';
            badge.textContent = count > 99 ? '99+' : count.toString();
            badge.style.cssText = `
                position: absolute;
                top: 8px;
                right: 8px;
                background: #dc3545;
                color: white;
                font-size: 10px;
                font-weight: 600;
                padding: 2px 6px;
                border-radius: 10px;
                min-width: 18px;
                text-align: center;
            `;
            link.appendChild(badge);
        }
    }
}

// Inicializar quando DOM estiver carregado
document.addEventListener('DOMContentLoaded', () => {
    window.sidebarLojista = new SidebarLojista();
});

// Fallback para carregamento dinâmico
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.sidebarLojista) {
            window.sidebarLojista = new SidebarLojista();
        }
    });
} else {
    if (!window.sidebarLojista) {
        window.sidebarLojista = new SidebarLojista();
    }
}