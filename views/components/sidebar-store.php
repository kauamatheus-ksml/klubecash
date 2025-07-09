<?php
/**
 * Sidebar Isolada - Sem Interferências
 * Versão minimalista que não afeta nada na página
 */

// Verificações básicas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'loja') {
    header('Location: ' . LOGIN_URL . '?error=acesso_restrito');
    exit;
}

$activeMenu = $activeMenu ?? 'dashboard';
$userName = $_SESSION['user_name'] ?? 'Lojista';

// Iniciais do usuário
$initials = '';
$nameParts = explode(' ', $userName);
if (count($nameParts) >= 2) {
    $initials = substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1);
} else {
    $initials = substr($userName, 0, 2);
}
$initials = strtoupper($initials);

// Menu items simples
$menuItems = [
    ['id' => 'dashboard', 'title' => 'Dashboard', 'url' => STORE_DASHBOARD_URL],
    ['id' => 'register-transaction', 'title' => 'Nova Venda', 'url' => STORE_REGISTER_TRANSACTION_URL],
    ['id' => 'transactions', 'title' => 'Vendas', 'url' => STORE_TRANSACTIONS_URL],
    ['id' => 'batch-upload', 'title' => 'Upload em Lote', 'url' => STORE_BATCH_UPLOAD_URL],
    ['id' => 'payment-history', 'title' => 'Pagamentos', 'url' => STORE_PAYMENT_HISTORY_URL, 'badge' => 3],
    ['id' => 'saldos', 'title' => 'Saldos', 'url' => STORE_SALDOS_URL],
    ['id' => 'profile', 'title' => 'Perfil', 'url' => STORE_PROFILE_URL]
];
?>

<link rel="stylesheet" href="../../assets/css/sidebar-store-modern.css">

<!-- Mobile Toggle -->
<button class="klube-sidebar-mobile-toggle" id="klubeMobileToggle">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
    </svg>
</button>

<!-- Expand Button -->
<button class="klube-sidebar-expand" id="klubeExpandBtn">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="9,18 15,12 9,6"></polyline>
    </svg>
</button>

<!-- Overlay -->
<div class="klube-sidebar-overlay" id="klubeOverlay"></div>

<!-- Sidebar -->
<div class="klube-sidebar-wrapper">
    <aside class="klube-sidebar" id="klubeSidebar">
        
        <!-- Header -->
        <header class="klube-sidebar-header">
            <div style="display: flex; align-items: center;">
                <img src="../../assets/images/logo.png" alt="Klube Cash" class="klube-sidebar-logo">
                <span class="klube-sidebar-logo-text">Klube Cash</span>
            </div>
            <button class="klube-sidebar-toggle" id="klubeToggle">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15,18 9,12 15,6"></polyline>
                </svg>
            </button>
        </header>

        <!-- Perfil -->
        <div class="klube-sidebar-profile">
            <div class="klube-sidebar-avatar"><?= $initials ?></div>
            <div class="klube-sidebar-user-info">
                <div class="klube-sidebar-user-name"><?= htmlspecialchars($userName) ?></div>
                <div class="klube-sidebar-user-role">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Lojista
                </div>
            </div>
        </div>

        <!-- Navegação -->
        <nav class="klube-sidebar-nav">
            <div class="klube-sidebar-section">
                <h3 class="klube-sidebar-section-title">Menu Principal</h3>
                <ul class="klube-sidebar-menu">
                    <?php foreach ($menuItems as $item): ?>
                        <li class="klube-sidebar-menu-item">
                            <a href="<?= $item['url'] ?>" 
                               class="klube-sidebar-menu-link <?= ($activeMenu === $item['id']) ? 'active' : '' ?>"
                               data-page="<?= $item['id'] ?>">
                                <span class="klube-sidebar-menu-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="9" y1="9" x2="15" y2="15"></line>
                                        <line x1="15" y1="9" x2="9" y2="15"></line>
                                    </svg>
                                </span>
                                <span class="klube-sidebar-menu-text"><?= $item['title'] ?></span>
                                <?php if (isset($item['badge']) && $item['badge'] > 0): ?>
                                    <span class="klube-sidebar-badge"><?= $item['badge'] ?></span>
                                <?php endif; ?>
                                <span class="klube-sidebar-tooltip"><?= $item['title'] ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </nav>

        <!-- Footer -->
        <footer class="klube-sidebar-footer">
            <a href="../../auth/logout.php" class="klube-sidebar-logout" onclick="return confirm('Sair do sistema?')">
                <svg class="klube-sidebar-logout-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4m7 14l5-5-5-5m5 5H9"/>
                </svg>
                <span class="klube-sidebar-logout-text">Sair</span>
            </a>
        </footer>

    </aside>
</div>

<script>
/**
 * Sidebar Isolada - JavaScript Minimalista
 * NÃO interfere em nada na página
 */
(function() {
    'use strict';
    
    // Elementos específicos da sidebar
    const sidebar = document.getElementById('klubeSidebar');
    const toggle = document.getElementById('klubeToggle');
    const mobileToggle = document.getElementById('klubeMobileToggle');
    const expandBtn = document.getElementById('klubeExpandBtn');
    const overlay = document.getElementById('klubeOverlay');
    
    if (!sidebar) return; // Se não existe, não faz nada
    
    // Estado
    let isCollapsed = localStorage.getItem('klubeSidebarCollapsed') === 'true';
    let isMobileOpen = false;
    let hideTimeout;
    
    // Funções utilitárias
    function isMobile() { return window.innerWidth <= 768; }
    
    function updateMainContent() {
        const main = document.querySelector('.main-content, .content, .page-content');
        if (main) {
            main.classList.add('klube-main-content');
            if (isMobile()) {
                main.style.marginLeft = '0';
            } else {
                main.style.marginLeft = isCollapsed ? '80px' : '280px';
            }
        }
    }
    
    function updateExpandButton() {
        if (expandBtn) {
            if (!isMobile() && isCollapsed) {
                expandBtn.classList.add('show');
            } else {
                expandBtn.classList.remove('show');
            }
        }
    }
    
    // Toggle desktop
    function toggleDesktop() {
        if (isMobile()) return;
        isCollapsed = !isCollapsed;
        sidebar.classList.toggle('collapsed', isCollapsed);
        localStorage.setItem('klubeSidebarCollapsed', isCollapsed);
        updateMainContent();
        updateExpandButton();
    }
    
    // Toggle mobile
    function toggleMobile() {
        if (!isMobile()) return;
        isMobileOpen = !isMobileOpen;
        sidebar.classList.toggle('open', isMobileOpen);
        overlay.classList.toggle('active', isMobileOpen);
        document.body.classList.toggle('klube-sidebar-open', isMobileOpen);
        
        if (isMobileOpen) {
            hideMobileToggleDelayed();
        } else {
            showMobileToggle();
        }
    }
    
    function closeMobile() {
        if (!isMobile()) return;
        isMobileOpen = false;
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        document.body.classList.remove('klube-sidebar-open');
        showMobileToggle();
    }
    
    function hideMobileToggleDelayed() {
        clearTimeout(hideTimeout);
        hideTimeout = setTimeout(() => {
            if (mobileToggle) mobileToggle.classList.add('hidden');
        }, 2000);
    }
    
    function showMobileToggle() {
        if (mobileToggle) mobileToggle.classList.remove('hidden');
    }
    
    // Event listeners com proteção
    if (toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleDesktop();
        });
    }
    
    if (expandBtn) {
        expandBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleDesktop();
        });
    }
    
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleMobile();
        });
    }
    
    if (overlay) {
        overlay.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeMobile();
        });
    }
    
    // Outside clicks (apenas para mobile)
    document.addEventListener('click', function(e) {
        if (!isMobile() || !isMobileOpen) return;
        if (!sidebar.contains(e.target) && !mobileToggle.contains(e.target)) {
            closeMobile();
        }
    });
    
    // Resize com throttle
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            if (isMobile()) {
                sidebar.classList.remove('collapsed');
                closeMobile();
            } else {
                if (isCollapsed) sidebar.classList.add('collapsed');
            }
            updateMainContent();
            updateExpandButton();
        }, 100);
    });
    
    // Keyboard (apenas Ctrl+B)
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'b' && !isMobile()) {
            e.preventDefault();
            toggleDesktop();
        }
        if (e.key === 'Escape' && isMobile() && isMobileOpen) {
            closeMobile();
        }
    });
    
    // Inicialização
    if (!isMobile() && isCollapsed) {
        sidebar.classList.add('collapsed');
    }
    updateMainContent();
    updateExpandButton();
    
    console.log('✅ Sidebar isolada carregada sem interferências');
    
})();
</script>