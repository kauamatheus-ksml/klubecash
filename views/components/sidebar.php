<?php
/**
 * Componente de Sidebar Modernizada para o Painel Administrativo
 * 
 * Este componente cria uma sidebar responsiva moderna que pode ser utilizada em todas as páginas
 * do painel administrativo.
 * 
 * @param string $activeMenu - O ID do menu ativo atual (ex: 'painel', 'usuarios', etc.)
 * @param string $userName - Nome do usuário logado para exibição
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

// Verificar se $activeMenu está definido, caso contrário definir como 'painel'
$activeMenu = $activeMenu ?? 'painel';
?>

<link rel="stylesheet" href="../../assets/css/sidebar-styles.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- Sidebar Toggle Button (visível apenas em dispositivos móveis) -->
<div class="sidebar-toggle" id="sidebarToggle" aria-label="Abrir menu">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
    </svg>
</div>

<!-- Overlay para dispositivos móveis -->
<div class="overlay" id="overlay"></div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="../../assets/images/logo.png" alt="KlubeCash" class="sidebar-logo">
    </div>
    
    <ul class="sidebar-nav" role="navigation">
        <li>
            <a href="<?php echo ADMIN_DASHBOARD_URL; ?>" 
               class="sidebar-nav-item <?php echo ($activeMenu == 'painel') ? 'active' : ''; ?>"
               aria-current="<?php echo ($activeMenu == 'painel') ? 'page' : 'false'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                Painel
            </a>
        </li>
        <li>
            <a href="<?php echo ADMIN_USERS_URL; ?>" 
               class="sidebar-nav-item <?php echo ($activeMenu == 'usuarios') ? 'active' : ''; ?>"
               aria-current="<?php echo ($activeMenu == 'usuarios') ? 'page' : 'false'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                Usuários
            </a>
        </li>
        <li>
            <a href="<?php echo ADMIN_BALANCE_URL; ?>" 
               class="sidebar-nav-item <?php echo ($activeMenu == 'saldo') ? 'active' : ''; ?>"
               aria-current="<?php echo ($activeMenu == 'saldo') ? 'page' : 'false'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                Saldo
            </a>
        </li>
        <li>
            <a href="<?php echo ADMIN_STORES_URL; ?>" 
               class="sidebar-nav-item <?php echo ($activeMenu == 'lojas') ? 'active' : ''; ?>"
               aria-current="<?php echo ($activeMenu == 'lojas') ? 'page' : 'false'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 3h18l-2 13H5L3 3z"></path>
                    <path d="M16 16a4 4 0 0 1-8 0"></path>
                </svg>
                Lojas
            </a>
        </li>
        <li>
            <a href="<?php echo ADMIN_PAYMENTS_URL; ?>" 
               class="sidebar-nav-item <?php echo ($activeMenu == 'pagamentos') ? 'active' : ''; ?>"
               aria-current="<?php echo ($activeMenu == 'pagamentos') ? 'page' : 'false'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                Pagamentos
            </a>
        </li>
        <li>
            <a href="<?php echo ADMIN_TRANSACTIONS_URL; ?>" 
               class="sidebar-nav-item <?php echo ($activeMenu == 'compras') ? 'active' : ''; ?>"
               aria-current="<?php echo ($activeMenu == 'compras') ? 'page' : 'false'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                Compras
            </a>
        </li>
        <li>
            <a href="<?php echo SITE_URL; ?>/admin/relatorios" 
               class="sidebar-nav-item <?php echo ($activeMenu == 'relatorios') ? 'active' : ''; ?>"
               aria-current="<?php echo ($activeMenu == 'relatorios') ? 'page' : 'false'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                Relatórios
            </a>
        </li>
        <li>
            <a href="<?php echo ADMIN_SETTINGS_URL; ?>" 
               class="sidebar-nav-item <?php echo ($activeMenu == 'configuracoes') ? 'active' : ''; ?>"
               aria-current="<?php echo ($activeMenu == 'configuracoes') ? 'page' : 'false'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
                Configurações
            </a>
        </li>
    </ul>
    
    <div class="sidebar-footer">
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
    </div>
</div>

<!-- Script da Sidebar Atualizado -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elementos da DOM
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const body = document.body;
    
    // Variável para controlar o estado da sidebar
    let sidebarOpen = false;
    
    // Evento para mostrar/ocultar a sidebar em dispositivos móveis
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }
    
    // Fechar sidebar com ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebarOpen) {
            closeSidebar();
        }
    });
    
    /**
     * Alterna a visibilidade da sidebar em dispositivos móveis
     */
    function toggleSidebar() {
        if (sidebarOpen) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }
    
    /**
     * Abre a sidebar
     */
    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('active');
        body.style.overflow = 'hidden'; // Previne scroll do body
        sidebarOpen = true;
        
        // Foco no primeiro item do menu para acessibilidade
        const firstMenuItem = sidebar.querySelector('.sidebar-nav-item');
        if (firstMenuItem) {
            firstMenuItem.focus();
        }
    }
    
    /**
     * Fecha a sidebar
     */
    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        body.style.overflow = ''; // Restaura o scroll do body
        sidebarOpen = false;
    }
    
    /**
     * Verifica o tamanho da tela e ajusta a sidebar conforme necessário
     */
    function checkScreenSize() {
        if (window.innerWidth > 768) {
            closeSidebar();
            body.style.overflow = ''; // Garantir que o scroll funcione em desktop
        }
    }
    
    // Verificar o tamanho da tela ao carregar e redimensionar
    window.addEventListener('resize', checkScreenSize);
    
    // Adicionar indicadores visuais de carregamento nos links
    const navItems = document.querySelectorAll('.sidebar-nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', function() {
            // Adicionar classe de loading
            this.classList.add('loading');
            
            // Remover classe após um tempo (será removida quando a página carregar)
            setTimeout(() => {
                this.classList.remove('loading');
            }, 2000);
        });
    });
    
    // Animação suave para os itens do menu
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationDelay = `${Array.from(navItems).indexOf(entry.target) * 0.1}s`;
            }
        });
    });
    
    navItems.forEach(item => observer.observe(item));
    
    // Inicializar
    checkScreenSize();
});
</script>