<?php
/**
 * Sidebar Moderna para Lojas - Klube Cash
 * 
 * Sidebar responsiva, elegante e funcional para o painel das lojas
 * 
 * @param string $activeMenu - Menu ativo atual
 */

// Verificações de segurança
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'loja') {
    header('Location: ' . LOGIN_URL . '?error=acesso_restrito');
    exit;
}

// Definir menu ativo padrão se não definido
$activeMenu = $activeMenu ?? 'dashboard';

// Dados do usuário
$userName = $_SESSION['user_name'] ?? 'Lojista';
$userEmail = $_SESSION['user_email'] ?? '';
$storeId = $_SESSION['store_id'] ?? null;

// Iniciais do usuário para avatar
$initials = '';
$nameParts = explode(' ', $userName);
if (count($nameParts) >= 2) {
    $initials = substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1);
} else {
    $initials = substr($userName, 0, 2);
}
$initials = strtoupper($initials);

// Menu items com ícones SVG
$menuItems = [
    [
        'id' => 'dashboard',
        'title' => 'Dashboard',
        'url' => STORE_DASHBOARD_URL,
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"/></svg>',
        'section' => 'main'
    ],
    [
        'id' => 'register-transaction',
        'title' => 'Nova Venda',
        'url' => STORE_REGISTER_TRANSACTION_URL,
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>',
        'section' => 'transactions'
    ],
    [
        'id' => 'transactions',
        'title' => 'Vendas',
        'url' => STORE_TRANSACTIONS_URL,
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>',
        'section' => 'transactions'
    ],
    [
        'id' => 'batch-upload',
        'title' => 'Upload em Lote',
        'url' => STORE_BATCH_UPLOAD_URL,
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>',
        'section' => 'transactions'
    ],
    [
        'id' => 'payment-history',
        'title' => 'Pagamentos',
        'url' => STORE_PAYMENT_HISTORY_URL,
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
        'section' => 'financial',
        'badge' => 3 // Exemplo de badge para pendências
    ],
    [
        'id' => 'saldos',
        'title' => 'Saldos',
        'url' => STORE_SALDOS_URL,
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>',
        'section' => 'financial'
    ],
    [
        'id' => 'profile',
        'title' => 'Meu Perfil',
        'url' => STORE_PROFILE_URL,
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
        'section' => 'settings'
    ]
];

// Agrupar itens por seção
$sections = [
    'main' => [
        'title' => 'Principal',
        'items' => []
    ],
    'transactions' => [
        'title' => 'Transações',
        'items' => []
    ],
    'financial' => [
        'title' => 'Financeiro',
        'items' => []
    ],
    'settings' => [
        'title' => 'Configurações',
        'items' => []
    ]
];

foreach ($menuItems as $item) {
    $sections[$item['section']]['items'][] = $item;
}
?>

<link rel="stylesheet" href="../../assets/css/sidebar-store-modern.css">

<!-- Toggle Mobile -->
<button class="mobile-toggle" id="mobileToggle" aria-label="Abrir menu">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
    </svg>
</button>

<!-- Botão Expandir (Desktop quando colapsada) -->
<button class="expand-btn" id="expandBtn" aria-label="Expandir menu" title="Expandir menu (Ctrl+B)">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
</button>

<!-- Overlay Mobile -->
<div class="mobile-overlay" id="mobileOverlay"></div>

<!-- Sidebar Container -->
<aside class="sidebar-container" id="sidebarContainer">
    
    <!-- Header -->
    <header class="sidebar-header">
        <div class="logo-section">
            <img src="../../assets/images/logo-icon.png" alt="Klube Cash" class="logo">
            <span class="logo-text">Klube Cash</span>
        </div>
        <button class="toggle-btn" id="toggleBtn" aria-label="Minimizar menu" title="Minimizar/Expandir menu (Ctrl+B)">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>
    </header>

    <!-- Perfil do Usuário -->
    <div class="user-profile">
        <div class="user-avatar" title="<?= htmlspecialchars($userName) ?>">
            <?= $initials ?>
        </div>
        <div class="user-info">
            <div class="user-name" title="<?= htmlspecialchars($userName) ?>">
                <?= htmlspecialchars($userName) ?>
            </div>
            <div class="user-role">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                Lojista
            </div>
        </div>
    </div>

    <!-- Navegação -->
    <nav class="sidebar-nav" role="navigation">
        <?php foreach ($sections as $sectionKey => $section): ?>
            <?php if (!empty($section['items'])): ?>
                <div class="nav-section">
                    <h3 class="nav-section-title"><?= $section['title'] ?></h3>
                    <ul class="nav-list" role="menubar">
                        <?php foreach ($section['items'] as $item): ?>
                            <li class="nav-item" role="none">
                                <a href="<?= $item['url'] ?>" 
                                   class="nav-link <?= ($activeMenu === $item['id']) ? 'active' : '' ?>"
                                   role="menuitem"
                                   aria-current="<?= ($activeMenu === $item['id']) ? 'page' : 'false' ?>"
                                   data-page="<?= $item['id'] ?>">
                                    <span class="nav-icon">
                                        <?= $item['icon'] ?>
                                    </span>
                                    <span class="nav-text"><?= $item['title'] ?></span>
                                    <?php if (isset($item['badge']) && $item['badge'] > 0): ?>
                                        <span class="nav-badge" aria-label="<?= $item['badge'] ?> pendências"><?= $item['badge'] ?></span>
                                    <?php endif; ?>
                                    
                                    <!-- Tooltip para modo colapsado -->
                                    <span class="nav-tooltip"><?= $item['title'] ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <!-- Footer com Logout -->
    <footer class="sidebar-footer">
        <a href="/controllers/AuthController.php?action=logout" class="logout-btn" onclick="return confirmarLogout()" title="Sair do sistema">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            <span>Sair</span>
        </a>
    </footer>

</aside>

<script>
/**
 * Controles da Sidebar Moderna - CORRIGIDO
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // Elementos
    const sidebar = document.getElementById('sidebarContainer');
    const toggleBtn = document.getElementById('toggleBtn');
    const mobileToggle = document.getElementById('mobileToggle');
    const expandBtn = document.getElementById('expandBtn');
    const mobileOverlay = document.getElementById('mobileOverlay');
    const body = document.body;
    const navLinks = document.querySelectorAll('.nav-link');
    
    // Estado da sidebar
    let isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    let isMobileOpen = false;
    let hideTimeout;
    
    // Verificar tamanho da tela
    function isMobile() {
        return window.innerWidth <= 768;
    }
    
    // CORRIGIDO: Controlar visibilidade do expand button
    function updateExpandButtonVisibility() {
        if (!isMobile()) {
            if (isCollapsed) {
                expandBtn.classList.add('show');
            } else {
                expandBtn.classList.remove('show');
            }
        } else {
            expandBtn.classList.remove('show');
        }
    }
    
    // Aplicar estado inicial
    function initSidebar() {
        if (!isMobile() && isCollapsed) {
            sidebar.classList.add('collapsed');
        }
        updateMainContent();
        updateExpandButtonVisibility(); // NOVO
    }
    
    // Toggle sidebar desktop
    function toggleSidebar() {
        if (isMobile()) return;
        
        isCollapsed = !isCollapsed;
        sidebar.classList.toggle('collapsed', isCollapsed);
        localStorage.setItem('sidebarCollapsed', isCollapsed);
        
        updateMainContent();
        updateExpandButtonVisibility(); // NOVO
        
        // Trigger evento para ajustar layout da página
        window.dispatchEvent(new CustomEvent('sidebarToggle', { 
            detail: { collapsed: isCollapsed, width: isCollapsed ? 80 : 280 }
        }));
        
        // Feedback visual
        console.log(isCollapsed ? '📱 Sidebar minimizada' : '📖 Sidebar expandida');
    }
    
    // Toggle sidebar mobile
    function toggleMobileSidebar() {
        if (!isMobile()) return;
        
        isMobileOpen = !isMobileOpen;
        sidebar.classList.toggle('open', isMobileOpen);
        mobileOverlay.classList.toggle('active', isMobileOpen);
        body.classList.toggle('sidebar-open', isMobileOpen);
        
        // Controlar visibilidade do mobile toggle
        if (isMobileOpen) {
            hideMobileToggle();
        } else {
            showMobileToggle();
        }
        
        // Acessibilidade
        sidebar.setAttribute('aria-hidden', !isMobileOpen);
        mobileToggle.setAttribute('aria-expanded', isMobileOpen);
    }
    
    // Fechar sidebar mobile
    function closeMobileSidebar() {
        if (!isMobile()) return;
        
        isMobileOpen = false;
        sidebar.classList.remove('open');
        mobileOverlay.classList.remove('active');
        body.classList.remove('sidebar-open');
        
        showMobileToggle();
        
        sidebar.setAttribute('aria-hidden', 'true');
        mobileToggle.setAttribute('aria-expanded', 'false');
    }
    
    // Esconder mobile toggle
    function hideMobileToggle() {
        mobileToggle.classList.add('hidden');
    }
    
    // Mostrar mobile toggle
    function showMobileToggle() {
        mobileToggle.classList.remove('hidden');
    }
    
    // Esconder mobile toggle com delay
    function hideMobileToggleDelayed() {
        clearTimeout(hideTimeout);
        hideTimeout = setTimeout(() => {
            if (isMobileOpen) {
                hideMobileToggle();
            }
        }, 2000); // 2 segundos de delay
    }
    
    // Atualizar margem do conteúdo principal
    function updateMainContent() {
        const mainContent = document.querySelector('.main-content, .content, .page-content');
        if (!mainContent) return;
        
        if (isMobile()) {
            mainContent.style.marginLeft = '0';
        } else {
            const marginLeft = isCollapsed ? '80px' : '280px';
            mainContent.style.marginLeft = marginLeft;
        }
        
        mainContent.style.transition = 'margin-left 0.3s ease';
    }
    
    // Event listeners principais
    toggleBtn?.addEventListener('click', toggleSidebar);
    expandBtn?.addEventListener('click', toggleSidebar); // CORRIGIDO: Mesmo comportamento
    mobileToggle?.addEventListener('click', toggleMobileSidebar);
    mobileOverlay?.addEventListener('click', closeMobileSidebar);
    
    // Event listener para cliques fora da sidebar no mobile
    document.addEventListener('click', function(e) {
        if (!isMobile() || !isMobileOpen) return;
        
        // Verificar se o clique foi fora da sidebar e não foi no toggle
        const clickedInsideSidebar = sidebar.contains(e.target);
        const clickedOnToggle = mobileToggle.contains(e.target);
        
        if (!clickedInsideSidebar && !clickedOnToggle) {
            closeMobileSidebar();
        }
    });
    
    // Event listener para mostrar mobile toggle quando sidebar aberta
    sidebar.addEventListener('mouseenter', function() {
        if (isMobile() && isMobileOpen) {
            clearTimeout(hideTimeout);
            showMobileToggle();
        }
    });
    
    sidebar.addEventListener('mouseleave', function() {
        if (isMobile() && isMobileOpen) {
            hideMobileToggleDelayed();
        }
    });
    
    // Fechar sidebar mobile ao clicar em link
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Adicionar loading state
            this.classList.add('loading');
            
            // Fechar sidebar mobile se aberta
            if (isMobile() && isMobileOpen) {
                setTimeout(closeMobileSidebar, 100);
            }
            
            // Remover loading após navegação
            setTimeout(() => {
                this.classList.remove('loading');
            }, 2000);
        });
    });
    
    // CORRIGIDO: Ajustar na mudança de tela
    window.addEventListener('resize', function() {
        if (isMobile()) {
            // Em mobile, sempre remover classe collapsed
            sidebar.classList.remove('collapsed');
            if (isMobileOpen) {
                body.classList.add('sidebar-open');
                hideMobileToggleDelayed();
            } else {
                showMobileToggle();
            }
        } else {
            // Desktop: restaurar estado collapsed e fechar mobile
            closeMobileSidebar();
            showMobileToggle();
            if (isCollapsed) {
                sidebar.classList.add('collapsed');
            }
        }
        updateMainContent();
        updateExpandButtonVisibility(); // NOVO
    });
    
    // Navegação por teclado
    document.addEventListener('keydown', function(e) {
        // ESC para fechar sidebar mobile
        if (e.key === 'Escape' && isMobile() && isMobileOpen) {
            closeMobileSidebar();
        }
        
        // Ctrl+B para toggle sidebar desktop
        if (e.ctrlKey && e.key === 'b' && !isMobile()) {
            e.preventDefault();
            toggleSidebar();
        }
        
        // M para mostrar mobile toggle quando oculto
        if (e.key === 'm' && isMobile() && isMobileOpen) {
            showMobileToggle();
            clearTimeout(hideTimeout);
        }
    });
    
    // Inicializar
    initSidebar();
    
    // Acessibilidade inicial
    if (isMobile()) {
        sidebar.setAttribute('aria-hidden', 'true');
        mobileToggle.setAttribute('aria-expanded', 'false');
    }
    
    // Debug: Logs para acompanhar o estado
    console.log('🚀 Sidebar moderna inicializada');
    console.log('📱 Mobile:', isMobile());
    console.log('📏 Colapsada:', isCollapsed);
});

/**
 * Ajustar layout da página principal quando sidebar muda
 */
window.addEventListener('sidebarToggle', function(e) {
    const mainContent = document.querySelector('.main-content, .content, .page-content');
    if (mainContent) {
        const marginLeft = e.detail.collapsed ? '80px' : '280px';
        mainContent.style.marginLeft = marginLeft;
        mainContent.style.transition = 'margin-left 0.3s ease';
    }
});

/**
 * Notificações/badges dinâmicas (exemplo)
 */
function updateNavBadge(menuId, count) {
    const navLink = document.querySelector(`[data-page="${menuId}"]`);
    if (!navLink) return;
    
    let badge = navLink.querySelector('.nav-badge');
    
    if (count > 0) {
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'nav-badge';
            navLink.appendChild(badge);
        }
        badge.textContent = count > 99 ? '99+' : count;
        badge.setAttribute('aria-label', `${count} pendências`);
    } else if (badge) {
        badge.remove();
    }
}

/**
 * Controles globais da sidebar
 */
window.sidebarControls = {
    toggle: function() {
        const toggleBtn = document.getElementById('toggleBtn');
        const expandBtn = document.getElementById('expandBtn');
        if (toggleBtn) toggleBtn.click();
        else if (expandBtn) expandBtn.click();
    },
    
    expand: function() {
        const sidebar = document.getElementById('sidebarContainer');
        if (sidebar && sidebar.classList.contains('collapsed')) {
            this.toggle();
        }
    },
    
    collapse: function() {
        const sidebar = document.getElementById('sidebarContainer');
        if (sidebar && !sidebar.classList.contains('collapsed') && window.innerWidth > 768) {
            this.toggle();
        }
    },
    
    showMobileToggle: function() {
        const toggle = document.getElementById('mobileToggle');
        if (toggle) toggle.classList.remove('hidden');
    },
    
    hideMobileToggle: function() {
        const toggle = document.getElementById('mobileToggle');
        if (toggle) toggle.classList.add('hidden');
    },
    
    closeMobile: function() {
        const sidebar = document.getElementById('sidebarContainer');
        const overlay = document.getElementById('mobileOverlay');
        const body = document.body;
        
        if (sidebar) sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
        body.classList.remove('sidebar-open');
        
        this.showMobileToggle();
    }
};

// Exemplo de uso:
// updateNavBadge('payment-history', 5); // 5 pagamentos pendentes
// window.sidebarControls.toggle(); // Toggle sidebar
// window.sidebarControls.expand(); // Forçar expansão
</script>

<style>
.sidebar-footer {
    margin-top: auto;
    padding: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.logout-btn {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: #dc3545;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
    width: 100%;
    font-weight: 500;
}

.logout-btn:hover {
    background-color: rgba(220, 53, 69, 0.1);
    color: #b02a37;
    transform: translateX(3px);
}
</style>

<script>
function confirmarLogout() {
    return confirm('Tem certeza que deseja sair?');
}
</script>