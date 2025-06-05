<?php
/**
 * Sidebar Responsiva Moderna - Área Administrativa
 * Design mobile-first com navegação intuitiva
 */

// Iniciar sessão apenas se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . LOGIN_URL);
    exit;
}

// Verificar se $activeMenu está definido
$activeMenu = $activeMenu ?? 'painel';

// Menu items com melhor organização
$menuItems = [
    [
        'id' => 'painel',
        'label' => 'Painel',
        'url' => ADMIN_DASHBOARD_URL,
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>'
    ],
    [
        'id' => 'usuarios',
        'label' => 'Usuários',
        'url' => ADMIN_USERS_URL,
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>'
    ],
    [
        'id' => 'saldo',
        'label' => 'Saldo',
        'url' => ADMIN_BALANCE_URL,
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>'
    ],
    [
        'id' => 'lojas',
        'label' => 'Lojas',
        'url' => ADMIN_STORES_URL,
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h18l-2 13H5L3 3z"></path><path d="M16 16a4 4 0 0 1-8 0"></path></svg>'
    ],
    [
        'id' => 'pagamentos',
        'label' => 'Pagamentos',
        'url' => ADMIN_PAYMENTS_URL,
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>'
    ],
    [
        'id' => 'compras',
        'label' => 'Compras',
        'url' => ADMIN_TRANSACTIONS_URL,
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>'
    ],
    [
        'id' => 'relatorios',
        'label' => 'Relatórios',
        'url' => SITE_URL . '/admin/relatorios',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>'
    ],
    [
        'id' => 'configuracoes',
        'label' => 'Configurações',
        'url' => ADMIN_SETTINGS_URL,
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>'
    ]
];
?>

<!-- CSS da sidebar -->
<link rel="stylesheet" href="../../assets/css/sidebar-responsive.css">

<!-- Botão Toggle (Mobile) -->
<button class="sidebar-toggle" id="sidebarToggle" aria-label="Abrir menu de navegação" aria-expanded="false">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
    </svg>
    <span class="sr-only">Menu</span>
</button>

<!-- Overlay (Mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar" role="navigation" aria-label="Menu principal">
    <!-- Header -->
    <div class="sidebar-header">
        <img src="../../assets/images/logo.png" alt="KlubeCash" class="sidebar-logo">
    </div>
    
    <!-- Navegação Principal -->
    <nav class="sidebar-nav" role="navigation">
        <?php foreach ($menuItems as $item): ?>
            <a href="<?php echo $item['url']; ?>" 
               class="sidebar-nav-item <?php echo ($activeMenu === $item['id']) ? 'active' : ''; ?>"
               aria-current="<?php echo ($activeMenu === $item['id']) ? 'page' : 'false'; ?>"
               data-item="<?php echo $item['id']; ?>">
                <?php echo $item['icon']; ?>
                <span><?php echo $item['label']; ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    
    <!-- Footer com Logout -->
    <div class="sidebar-footer">
        <a href="<?php echo SITE_URL; ?>/controllers/AuthController.php?action=logout" 
           class="logout-btn" 
           onclick="return confirm('Tem certeza que deseja sair?')"
           aria-label="Sair do sistema">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span>Sair</span>
        </a>
    </div>
</aside>

<!-- JavaScript Responsivo -->
<script>
// Sidebar Responsiva - Klube Cash Admin
class ResponsiveSidebar {
    constructor() {
        this.sidebar = document.getElementById('sidebar');
        this.toggle = document.getElementById('sidebarToggle');
        this.overlay = document.getElementById('sidebarOverlay');
        this.body = document.body;
        this.isOpen = false;
        this.isMobile = false;
        
        this.init();
    }
    
    init() {
        // Event listeners
        this.toggle?.addEventListener('click', () => this.toggleSidebar());
        this.overlay?.addEventListener('click', () => this.closeSidebar());
        
        // Keyboard navigation
        document.addEventListener('keydown', (e) => this.handleKeyboard(e));
        
        // Resize listener
        window.addEventListener('resize', () => this.handleResize());
        
        // Navigation click handlers
        this.setupNavigation();
        
        // Initial setup
        this.handleResize();
        
        console.log('✅ Sidebar responsiva inicializada');
    }
    
    toggleSidebar() {
        if (this.isOpen) {
            this.closeSidebar();
        } else {
            this.openSidebar();
        }
    }
    
    openSidebar() {
        if (!this.isMobile) return;
        
        this.sidebar?.classList.add('open');
        this.overlay?.classList.add('active');
        this.body.classList.add('body-sidebar-open');
        
        // Accessibility
        this.toggle?.setAttribute('aria-expanded', 'true');
        this.overlay?.setAttribute('aria-hidden', 'false');
        
        // Focus management
        const firstNavItem = this.sidebar?.querySelector('.sidebar-nav-item');
        firstNavItem?.focus();
        
        this.isOpen = true;
        
        console.log('📱 Sidebar aberta (mobile)');
    }
    
    closeSidebar() {
        if (!this.isMobile && !this.isOpen) return;
        
        this.sidebar?.classList.remove('open');
        this.overlay?.classList.remove('active');
        this.body.classList.remove('body-sidebar-open');
        
        // Accessibility
        this.toggle?.setAttribute('aria-expanded', 'false');
        this.overlay?.setAttribute('aria-hidden', 'true');
        
        // Return focus to toggle
        this.toggle?.focus();
        
        this.isOpen = false;
        
        console.log('📱 Sidebar fechada');
    }
    
    handleResize() {
        const wasMobile = this.isMobile;
        this.isMobile = window.innerWidth <= 768;
        
        // Se mudou de mobile para desktop
        if (wasMobile && !this.isMobile) {
            this.closeSidebar();
            this.body.classList.remove('body-sidebar-open');
        }
        
        // Se mudou de desktop para mobile
        if (!wasMobile && this.isMobile) {
            this.closeSidebar();
        }
        
        console.log(`📏 Resize: ${this.isMobile ? 'Mobile' : 'Desktop'} (${window.innerWidth}px)`);
    }
    
    handleKeyboard(e) {
        // ESC para fechar
        if (e.key === 'Escape' && this.isOpen && this.isMobile) {
            this.closeSidebar();
        }
        
        // Tab navigation dentro da sidebar
        if (e.key === 'Tab' && this.isOpen && this.isMobile) {
            this.handleTabNavigation(e);
        }
    }
    
    handleTabNavigation(e) {
        const focusableElements = this.sidebar?.querySelectorAll(
            'a, button, [tabindex]:not([tabindex="-1"])'
        );
        
        if (!focusableElements?.length) return;
        
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        // Se shift+tab no primeiro elemento, vai para o último
        if (e.shiftKey && document.activeElement === firstElement) {
            e.preventDefault();
            lastElement.focus();
        }
        
        // Se tab no último elemento, vai para o primeiro
        if (!e.shiftKey && document.activeElement === lastElement) {
            e.preventDefault();
            firstElement.focus();
        }
    }
    
    setupNavigation() {
        const navItems = this.sidebar?.querySelectorAll('.sidebar-nav-item');
        
        navItems?.forEach(item => {
            item.addEventListener('click', (e) => {
                // Feedback visual
                item.classList.add('loading');
                
                // Se mobile, fechar sidebar após click
                if (this.isMobile && this.isOpen) {
                    setTimeout(() => {
                        this.closeSidebar();
                    }, 150);
                }
                
                // Remover loading state
                setTimeout(() => {
                    item.classList.remove('loading');
                }, 2000);
                
                console.log(`🔗 Navegação: ${item.dataset.item}`);
            });
        });
    }
}

// Inicializar quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    new ResponsiveSidebar();
});

// Para debugging
window.KlubeCashSidebar = ResponsiveSidebar;
</script>