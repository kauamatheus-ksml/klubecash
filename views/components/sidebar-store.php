<?php
/**
 * Sidebar Responsiva Moderna - Área da Loja
 * Design mobile-first com navegação intuitiva
 */

require_once '../../config/constants.php';

// Iniciar sessão apenas se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . LOGIN_URL);
    exit;
}

// Verificar se o usuário é loja
if ($_SESSION['user_type'] !== 'loja') {
    header('Location: ' . CLIENT_DASHBOARD_URL);
    exit;
}

// Verificar se $activeMenu está definido
$activeMenu = $activeMenu ?? 'dashboard';

// Menu items para loja com melhor organização
$storeMenuItems = [
    [
        'id' => 'dashboard',
        'label' => 'Dashboard',
        'url' => STORE_DASHBOARD_URL,
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>'
    ],
    [
        'id' => 'register-transaction',
        'label' => 'Nova Venda',
        'url' => STORE_REGISTER_TRANSACTION_URL,
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>'
    ],
    [
        'id' => 'transactions',
        'label' => 'Transações',
        'url' => STORE_TRANSACTIONS_URL,
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>'
    ],
    [
        'id' => 'pending-commissions',
        'label' => 'Comissões',
        'url' => STORE_PENDING_TRANSACTIONS_URL,
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>'
    ],
    [
        'id' => 'payment-history',
        'label' => 'Pagamentos',
        'url' => STORE_PAYMENT_HISTORY_URL,
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>'
    ],
    [
        'id' => 'payment',
        'label' => 'Pagar Agora',
        'url' => STORE_PAYMENT_URL,
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>',
        'highlight' => true // Item especial
    ],
    [
        'id' => 'profile',
        'label' => 'Perfil',
        'url' => SITE_URL . '/store/perfil',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>'
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
<aside class="sidebar" id="sidebar" role="navigation" aria-label="Menu principal da loja">
    <!-- Header -->
    <div class="sidebar-header">
        <img src="../../assets/images/logo.png" alt="KlubeCash" class="sidebar-logo">
    </div>
    
    <!-- Navegação Principal -->
    <nav class="sidebar-nav" role="navigation">
        <?php foreach ($storeMenuItems as $item): ?>
            <a href="<?php echo $item['url']; ?>" 
               class="sidebar-nav-item <?php echo ($activeMenu === $item['id']) ? 'active' : ''; ?> <?php echo isset($item['highlight']) ? 'highlight' : ''; ?>"
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

<!-- CSS adicional para loja -->
<style>
/* Destacar item especial (Pagar Agora) */
.sidebar-nav-item.highlight {
    background: linear-gradient(135deg, rgba(255, 122, 0, 0.1) 0%, rgba(255, 122, 0, 0.05) 100%);
    border: 1px solid rgba(255, 122, 0, 0.2);
    font-weight: 600;
}

.sidebar-nav-item.highlight:hover {
    background: linear-gradient(135deg, rgba(255, 122, 0, 0.2) 0%, rgba(255, 122, 0, 0.1) 100%);
    border-color: rgba(255, 122, 0, 0.3);
}

.sidebar-nav-item.highlight.active {
    background: linear-gradient(135deg, var(--primary-color) 0%, #FF8A1A 100%);
    color: var(--white);
    border-color: var(--primary-dark);
}
</style>

<!-- JavaScript Responsivo (mesmo código da sidebar admin) -->
<script>
// Sidebar Responsiva - Klube Cash Loja
class ResponsiveSidebarStore {
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
        
        console.log('✅ Sidebar loja responsiva inicializada');
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
        
        console.log('🏪 Sidebar loja aberta (mobile)');
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
        
        console.log('🏪 Sidebar loja fechada');
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
        
        console.log(`🏪 Resize: ${this.isMobile ? 'Mobile' : 'Desktop'} (${window.innerWidth}px)`);
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
                
                console.log(`🏪 Navegação: ${item.dataset.item}`);
            });
        });
    }
}

// Inicializar quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    new ResponsiveSidebarStore();
});

// Para debugging
window.KlubeCashSidebarStore = ResponsiveSidebarStore;
</script>