/**
 * Sistema de Navegação Unificado - Klube Cash
 * Gerencia navbar e sidebar responsivos
 */

class NavigationSystem {
    constructor() {
        this.mobileBreakpoint = 992;
        this.isMenuOpen = false;
        this.elements = {
            navbar: document.getElementById('mainNavbar'),
            sidebar: document.getElementById('mainSidebar'),
            mobileToggle: document.getElementById('mobileMenuToggle'),
            mobileClose: document.getElementById('sidebarMobileClose'),
            overlay: document.getElementById('mobileOverlay'),
            mainContent: document.querySelector('.main-content'),
            body: document.body
        };
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.checkScreenSize();
        this.handleNavItemClicks();
        this.initAccessibility();
        
        // Verificar orientação em dispositivos móveis
        window.addEventListener('orientationchange', () => {
            setTimeout(() => this.checkScreenSize(), 100);
        });
    }

    bindEvents() {
        // Toggle do menu mobile
        if (this.elements.mobileToggle) {
            this.elements.mobileToggle.addEventListener('click', () => this.toggleMobileMenu());
        }

        // Fechar menu mobile
        if (this.elements.mobileClose) {
            this.elements.mobileClose.addEventListener('click', () => this.closeMobileMenu());
        }

        // Overlay
        if (this.elements.overlay) {
            this.elements.overlay.addEventListener('click', () => this.closeMobileMenu());
        }

        // Redimensionamento da janela
        window.addEventListener('resize', () => this.handleResize());

        // Tecla ESC para fechar menu
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isMenuOpen) {
                this.closeMobileMenu();
            }
        });

        // Prevenir scroll do body quando menu estiver aberto
        document.addEventListener('touchmove', (e) => {
            if (this.elements.body.classList.contains('mobile-menu-open')) {
                if (!e.target.closest('.main-sidebar')) {
                    e.preventDefault();
                }
            }
        }, { passive: false });
    }

    toggleMobileMenu() {
        if (this.isMenuOpen) {
            this.closeMobileMenu();
        } else {
            this.openMobileMenu();
        }
    }

    openMobileMenu() {
        this.isMenuOpen = true;
        this.elements.sidebar?.classList.add('open');
        this.elements.overlay?.classList.add('active');
        this.elements.body.classList.add('mobile-menu-open');
        
        // Foco no primeiro item do menu para acessibilidade
        const firstMenuItem = this.elements.sidebar?.querySelector('.sidebar-nav-item');
        if (firstMenuItem) {
            setTimeout(() => firstMenuItem.focus(), 300);
        }

        // Adicionar atributos ARIA
        this.elements.sidebar?.setAttribute('aria-hidden', 'false');
        this.elements.mobileToggle?.setAttribute('aria-expanded', 'true');
    }

    closeMobileMenu() {
        this.isMenuOpen = false;
        this.elements.sidebar?.classList.remove('open');
        this.elements.overlay?.classList.remove('active');
        this.elements.body.classList.remove('mobile-menu-open');

        // Adicionar atributos ARIA
        this.elements.sidebar?.setAttribute('aria-hidden', 'true');
        this.elements.mobileToggle?.setAttribute('aria-expanded', 'false');
    }

    handleResize() {
        // Debounce para evitar múltiplas chamadas
        clearTimeout(this.resizeTimeout);
        this.resizeTimeout = setTimeout(() => {
            this.checkScreenSize();
        }, 150);
    }

    checkScreenSize() {
        const isDesktop = window.innerWidth >= this.mobileBreakpoint;
        
        if (isDesktop && this.isMenuOpen) {
            // Fechar menu mobile quando mudou para desktop
            this.closeMobileMenu();
        }
        
        // Garantir que o body não tenha overflow hidden no desktop
        if (isDesktop) {
            this.elements.body.classList.remove('mobile-menu-open');
        }
    }

    handleNavItemClicks() {
        const navItems = document.querySelectorAll('.sidebar-nav-item');
        
        navItems.forEach(item => {
            item.addEventListener('click', (e) => {
                // Adicionar classe de loading
                item.classList.add('loading');
                
                // Fechar menu mobile se estiver aberto
                if (window.innerWidth < this.mobileBreakpoint && this.isMenuOpen) {
                    setTimeout(() => {
                        this.closeMobileMenu();
                    }, 150);
                }
                
                // Remover classe de loading após um tempo
                setTimeout(() => {
                    item.classList.remove('loading');
                }, 2000);
            });
        });
    }

    initAccessibility() {
        // Configurar atributos ARIA iniciais
        if (this.elements.sidebar) {
            this.elements.sidebar.setAttribute('aria-hidden', 'true');
            this.elements.sidebar.setAttribute('role', 'navigation');
            this.elements.sidebar.setAttribute('aria-label', 'Menu principal');
        }

        if (this.elements.mobileToggle) {
            this.elements.mobileToggle.setAttribute('aria-expanded', 'false');
            this.elements.mobileToggle.setAttribute('aria-controls', 'mainSidebar');
        }

        // Adicionar suporte a navegação por teclado
        const navItems = document.querySelectorAll('.sidebar-nav-item');
        navItems.forEach((item, index) => {
            item.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    const nextItem = navItems[index + 1];
                    if (nextItem) nextItem.focus();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    const prevItem = navItems[index - 1];
                    if (prevItem) prevItem.focus();
                }
            });
        });
    }

    // Método público para programaticamente abrir/fechar menu
    setMenuState(isOpen) {
        if (isOpen) {
            this.openMobileMenu();
        } else {
            this.closeMobileMenu();
        }
    }

    // Método para adicionar badges de notificação dinamicamente
    addNotificationBadge(menuKey, count) {
        const menuItem = document.querySelector(`[data-menu="${menuKey}"]`);
        if (menuItem && count > 0) {
            let badge = menuItem.querySelector('.notification-badge');
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'notification-badge';
                menuItem.appendChild(badge);
            }
            badge.textContent = count > 99 ? '99+' : count;
        }
    }

    // Método para remover badges
    removeNotificationBadge(menuKey) {
        const menuItem = document.querySelector(`[data-menu="${menuKey}"]`);
        if (menuItem) {
            const badge = menuItem.querySelector('.notification-badge');
            if (badge) {
                badge.remove();
            }
        }
    }
}

// Inicializar quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', () => {
    window.navigationSystem = new NavigationSystem();
    
    // Animação suave para os itens do menu
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const navItems = document.querySelectorAll('.sidebar-nav-item');
                entry.target.style.animationDelay = `${Array.from(navItems).indexOf(entry.target) * 0.1}s`;
            }
        });
    });
    
    document.querySelectorAll('.sidebar-nav-item').forEach(item => {
        observer.observe(item);
    });
});

// Exportar para uso global se necessário
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NavigationSystem;
}