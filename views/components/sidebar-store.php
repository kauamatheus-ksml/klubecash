<?php
/**
 * Sidebar Responsiva - Área da Loja
 * Otimizada para todas as telas com design intuitivo
 */

require_once '../../config/constants.php';

// Iniciar sessão apenas se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificações de segurança
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . LOGIN_URL);
    exit;
}

if ($_SESSION['user_type'] !== 'loja') {
    header('Location: ' . CLIENT_DASHBOARD_URL);
    exit;
}

$activeMenu = $activeMenu ?? 'dashboard';
?>

<link rel="stylesheet" href="../../assets/css/sidebar-styles.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- Toggle Button (Mobile) -->
<button class="sidebar-toggle" id="sidebarToggle" aria-label="Abrir menu" type="button">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
    </svg>
</button>

<!-- Overlay -->
<div class="overlay" id="overlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar" role="navigation" aria-label="Menu da loja">
    <!-- Header -->
    <header class="sidebar-header">
        <img src="../../assets/images/logo.png" alt="KlubeCash" class="sidebar-logo">
    </header>
    
    <!-- Navegação -->
    <nav class="sidebar-nav">
        <a href="<?php echo STORE_DASHBOARD_URL; ?>" 
           class="sidebar-nav-item <?php echo ($activeMenu == 'dashboard') ? 'active' : ''; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
            Dashboard
        </a>
        
        <a href="<?php echo STORE_REGISTER_TRANSACTION_URL; ?>" 
           class="sidebar-nav-item <?php echo ($activeMenu == 'register-transaction') ? 'active' : ''; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Registrar Venda
        </a>
        
        <a href="<?php echo STORE_TRANSACTIONS_URL; ?>" 
           class="sidebar-nav-item <?php echo ($activeMenu == 'transactions') ? 'active' : ''; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                <line x1="8" y1="21" x2="16" y2="21"></line>
                <line x1="12" y1="17" x2="12" y2="21"></line>
            </svg>
            Transações
        </a>
        
        <a href="<?php echo STORE_PENDING_TRANSACTIONS_URL; ?>" 
           class="sidebar-nav-item <?php echo ($activeMenu == 'pending-commissions') ? 'active' : ''; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            Comissões Pendentes
        </a>
        
        <a href="<?php echo STORE_PAYMENT_HISTORY_URL; ?>" 
           class="sidebar-nav-item <?php echo ($activeMenu == 'payment-history') ? 'active' : ''; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                <line x1="1" y1="10" x2="23" y2="10"></line>
            </svg>
            Histórico Pagamentos
        </a>
        
        <a href="<?php echo STORE_PAYMENT_URL; ?>" 
           class="sidebar-nav-item <?php echo ($activeMenu == 'payment') ? 'active' : ''; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
            Realizar Pagamento
        </a>
        
        <a href="<?php echo STORE_PROFILE_URL; ?>" 
           class="sidebar-nav-item <?php echo ($activeMenu == 'profile') ? 'active' : ''; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            Meu Perfil
        </a>
    </nav>
    
    <!-- Footer -->
    <footer class="sidebar-footer">
        <a href="<?php echo SITE_URL; ?>/controllers/AuthController.php?action=logout" 
           class="logout-btn" 
           onclick="return confirm('Tem certeza que deseja sair?')">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            Sair
        </a>
    </footer>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elementos
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const body = document.body;
    
    let sidebarOpen = false;
    
    // Eventos
    sidebarToggle?.addEventListener('click', toggleSidebar);
    overlay?.addEventListener('click', closeSidebar);
    
    // Fechar com ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebarOpen) closeSidebar();
    });
    
    // Funções
    function toggleSidebar() {
        sidebarOpen ? closeSidebar() : openSidebar();
    }
    
    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('active');
        body.classList.add('sidebar-open');
        sidebarOpen = true;
        
        // Foco no primeiro item para acessibilidade
        const firstItem = sidebar.querySelector('.sidebar-nav-item');
        firstItem?.focus();
    }
    
    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        body.classList.remove('sidebar-open');
        sidebarOpen = false;
    }
    
    // Fechar sidebar em links no mobile
    const navItems = document.querySelectorAll('.sidebar-nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', () => {
            if (window.innerWidth <= 768 && sidebarOpen) {
                setTimeout(closeSidebar, 150);
            }
        });
    });
    
    // Ajustar em resize
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) closeSidebar();
    });
    
    // Orientação mobile
    window.addEventListener('orientationchange', () => {
        setTimeout(() => {
            if (window.innerWidth > 768) closeSidebar();
        }, 100);
    });
});
</script>