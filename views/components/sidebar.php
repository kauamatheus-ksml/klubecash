<?php
/**
 * Sidebar Administrativa Reformulada - Simples e Intuitiva
 * Versão 3.0 - Focada em Usuários Leigos e Responsividade Total
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . LOGIN_URL);
    exit;
}

$activeMenu = $activeMenu ?? 'painel';

// Garantir que as constantes existam
if (!defined('SITE_URL')) define('SITE_URL', '../../');
if (!defined('ADMIN_DASHBOARD_URL')) define('ADMIN_DASHBOARD_URL', SITE_URL . '/admin/dashboard');
if (!defined('ADMIN_USERS_URL')) define('ADMIN_USERS_URL', SITE_URL . '/admin/usuarios');
if (!defined('ADMIN_STORES_URL')) define('ADMIN_STORES_URL', SITE_URL . '/admin/lojas');
if (!defined('ADMIN_TRANSACTIONS_URL')) define('ADMIN_TRANSACTIONS_URL', SITE_URL . '/admin/transacoes');
if (!defined('ADMIN_PAYMENTS_URL')) define('ADMIN_PAYMENTS_URL', SITE_URL . '/admin/pagamentos');
if (!defined('ADMIN_REPORTS_URL')) define('ADMIN_REPORTS_URL', SITE_URL . '/admin/relatorios');
if (!defined('ADMIN_SETTINGS_URL')) define('ADMIN_SETTINGS_URL', SITE_URL . '/admin/configuracoes');
?>

<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/sidebar-styles.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<!-- Toggle Button para Mobile -->
<button class="sidebar-toggle" id="sidebarToggle" aria-label="Menu do Administrador" title="Abrir Menu">
    <div class="hamburger">
        <span></span>
        <span></span>
        <span></span>
    </div>
</button>

<!-- Overlay para Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar Principal -->
<aside class="sidebar" id="sidebar" role="navigation" aria-label="Menu administrativo">
    <!-- Header Simplificado -->
    <div class="sidebar-header">
        <div class="logo-container">
            <img src="<?php echo SITE_URL; ?>/assets/images/logo.png" alt="KlubeCash" class="sidebar-logo">
            <span class="logo-text">KlubeCash</span>
        </div>
        <div class="user-info">
            <div class="user-avatar">
                👨‍💼
            </div>
            <div class="user-details">
                <span class="user-name">Administrador</span>
                <span class="user-role">Painel de Controle</span>
            </div>
        </div>
    </div>

    <!-- Navegação Simplificada -->
    <nav class="sidebar-nav">
        <!-- Seção: Principal -->
        <div class="nav-section">
            <h3 class="nav-section-title">
                📊 Principal
            </h3>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="<?php echo ADMIN_DASHBOARD_URL; ?>" 
                       class="nav-link <?php echo ($activeMenu == 'painel') ? 'active' : ''; ?>"
                       data-tooltip="Visão geral do sistema e estatísticas">
                        <div class="nav-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                        </div>
                        <div>
                            <span class="nav-text">Painel Principal</span>
                            <span class="nav-description">Visão geral do sistema</span>
                        </div>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Seção: Pessoas -->
        <div class="nav-section">
            <h3 class="nav-section-title">
                👥 Pessoas
            </h3>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="<?php echo ADMIN_USERS_URL; ?>" 
                       class="nav-link <?php echo ($activeMenu == 'usuarios') ? 'active' : ''; ?>"
                       data-tooltip="Gerenciar clientes do sistema">
                        <div class="nav-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <div>
                            <span class="nav-text">Clientes</span>
                            <span class="nav-description">Usuários cadastrados</span>
                        </div>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo ADMIN_STORES_URL; ?>" 
                       class="nav-link <?php echo ($activeMenu == 'lojas') ? 'active' : ''; ?>"
                       data-tooltip="Administrar lojas parceiras">
                        <div class="nav-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                        </div>
                        <div>
                            <span class="nav-text">Lojas Parceiras</span>
                            <span class="nav-description">Gerenciar parcerias</span>
                        </div>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Seção: Financeiro -->
        <div class="nav-section">
            <h3 class="nav-section-title">
                💰 Financeiro
            </h3>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="<?php echo ADMIN_TRANSACTIONS_URL; ?>" 
                       class="nav-link <?php echo ($activeMenu == 'compras') ? 'active' : ''; ?>"
                       data-tooltip="Acompanhar vendas e cashback">
                        <div class="nav-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="9" cy="21" r="1"></circle>
                                <circle cx="20" cy="21" r="1"></circle>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                        </div>
                        <div>
                            <span class="nav-text">Vendas</span>
                            <span class="nav-description">Transações e cashback</span>
                        </div>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo ADMIN_PAYMENTS_URL; ?>" 
                       class="nav-link <?php echo ($activeMenu == 'pagamentos') ? 'active' : ''; ?>"
                       data-tooltip="Controlar pagamentos das lojas">
                        <div class="nav-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                        </div>
                        <div>
                            <span class="nav-text">Pagamentos</span>
                            <span class="nav-description">Comissões das lojas</span>
                        </div>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Seção: Relatórios -->
        <div class="nav-section">
            <h3 class="nav-section-title">
                📈 Análises
            </h3>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="<?php echo ADMIN_REPORTS_URL; ?>" 
                       class="nav-link <?php echo ($activeMenu == 'relatorios') ? 'active' : ''; ?>"
                       data-tooltip="Relatórios e estatísticas detalhadas">
                        <div class="nav-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="20" x2="18" y2="10"></line>
                                <line x1="12" y1="20" x2="12" y2="4"></line>
                                <line x1="6" y1="20" x2="6" y2="14"></line>
                            </svg>
                        </div>
                        <div>
                            <span class="nav-text">Relatórios</span>
                            <span class="nav-description">Estatísticas do sistema</span>
                        </div>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Footer SEMPRE VISÍVEL -->
    <div class="sidebar-footer">
        <a href="<?php echo ADMIN_SETTINGS_URL; ?>" 
           class="footer-link <?php echo ($activeMenu == 'configuracoes') ? 'active' : ''; ?>"
           data-tooltip="Configurações do sistema">
            <div class="footer-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
            </div>
            <span class="footer-text">Configurações</span>
        </a>
        
        <button class="logout-btn" onclick="confirmLogout()" data-tooltip="Sair do sistema com segurança">
            <div class="logout-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </div>
            <span class="logout-text">Sair do Sistema</span>
        </button>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeSidebar();
});

function initializeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const navLinks = document.querySelectorAll('.nav-link');
    
    // Toggle sidebar function
    function toggleSidebar() {
        sidebar.classList.toggle('open');
        sidebarOverlay.classList.toggle('active');
        document.body.classList.toggle('sidebar-open');
    }
    
    // Event listeners
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', toggleSidebar);
    }
    
    // Fechar sidebar ao clicar em link no mobile
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                setTimeout(toggleSidebar, 150);
            }
            
            // Feedback visual
            this.classList.add('clicked');
            setTimeout(() => {
                this.classList.remove('clicked');
            }, 200);
        });
    });
    
    // Responsividade
    function handleResize() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('active');
            document.body.classList.remove('sidebar-open');
        }
    }
    
    window.addEventListener('resize', handleResize);
    
    // Tooltips
    initializeTooltips();
}

function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
        element.addEventListener('focus', showTooltip);
        element.addEventListener('blur', hideTooltip);
    });
    
    function showTooltip(e) {
        if (window.innerWidth <= 768) return;
        
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = e.target.closest('[data-tooltip]').dataset.tooltip;
        document.body.appendChild(tooltip);
        
        const rect = e.target.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        
        tooltip.style.left = (rect.right + 15) + 'px';
        tooltip.style.top = (rect.top + (rect.height / 2) - (tooltipRect.height / 2)) + 'px';
        
        setTimeout(() => tooltip.classList.add('show'), 10);
    }
    
    function hideTooltip() {
        const tooltips = document.querySelectorAll('.tooltip');
        tooltips.forEach(tooltip => tooltip.remove());
    }
}

function confirmLogout() {
    if (confirm('Tem certeza que deseja sair do sistema?\n\nVocê precisará fazer login novamente.')) {
        window.location.href = '<?php echo SITE_URL; ?>/controllers/AuthController.php?action=logout';
    }
}

// Preload da próxima página para melhor UX
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('mouseenter', function() {
        const href = this.getAttribute('href');
        if (href && !this.dataset.preloaded) {
            const linkElement = document.createElement('link');
            linkElement.rel = 'prefetch';
            linkElement.href = href;
            document.head.appendChild(linkElement);
            this.dataset.preloaded = 'true';
        }
    });
});
</script>