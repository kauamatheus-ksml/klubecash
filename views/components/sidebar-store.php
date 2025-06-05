<?php
/**
 * Componente de Sidebar para a Área da Loja
 * Versão 2.0 - Interface amigável para lojistas
 */
require_once '../../config/constants.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . LOGIN_URL);
    exit;
}

if ($_SESSION['user_type'] !== 'loja') {
    header('Location: ' . CLIENT_DASHBOARD_URL);
    exit;
}

if (!isset($activeMenu)) {
    $activeMenu = 'dashboard';
}

// Buscar dados da loja para personalização
try {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT nome_fantasia, logo FROM lojas WHERE usuario_id = :user_id");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $storeData = $stmt->fetch(PDO::FETCH_ASSOC);
    $storeName = $storeData['nome_fantasia'] ?? 'Minha Loja';
    $storeLogo = $storeData['logo'] ?? null;
} catch (Exception $e) {
    $storeName = 'Minha Loja';
    $storeLogo = null;
}
?>

<link rel="stylesheet" href="../../assets/css/sidebar-modern.css">

<!-- Toggle Button para Mobile -->
<button class="sidebar-toggle store-theme" id="sidebarToggle" aria-label="Abrir menu da loja" title="Menu da Loja">
    <div class="hamburger">
        <span></span>
        <span></span>
        <span></span>
    </div>
</button>

<!-- Overlay para Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar da Loja -->
<aside class="sidebar store-sidebar" id="sidebar" role="navigation" aria-label="Menu da loja">
    <!-- Header Personalizado da Loja -->
    <div class="sidebar-header store-header">
        <div class="store-brand">
            <?php if ($storeLogo): ?>
                <img src="../../uploads/store_logos/<?php echo htmlspecialchars($storeLogo); ?>" 
                     alt="<?php echo htmlspecialchars($storeName); ?>" 
                     class="store-logo">
            <?php else: ?>
                <div class="store-logo-placeholder">
                    <?php echo strtoupper(substr($storeName, 0, 2)); ?>
                </div>
            <?php endif; ?>
            <div class="store-info">
                <span class="store-name"><?php echo htmlspecialchars($storeName); ?></span>
                <span class="store-badge">Loja Parceira</span>
            </div>
        </div>
        <div class="klube-brand">
            <img src="../../assets/images/logo.png" alt="KlubeCash" class="klube-logo">
        </div>
    </div>

    <!-- Navegação Principal -->
    <nav class="sidebar-nav store-nav">
        <!-- Seção: Início -->
        <div class="nav-section">
            <h3 class="nav-section-title">
                <i class="icon-home"></i>
                Visão Geral
            </h3>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="<?php echo STORE_DASHBOARD_URL; ?>" 
                       class="nav-link <?php echo ($activeMenu == 'dashboard') ? 'active' : ''; ?>"
                       data-tooltip="Resumo das suas vendas e performance">
                        <div class="nav-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                        </div>
                        <span class="nav-text">Painel Principal</span>
                        <span class="nav-description">Resumo da loja</span>
                        <?php if($activeMenu == 'dashboard'): ?>
                            <div class="nav-indicator"></div>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Seção: Vendas -->
        <div class="nav-section highlight">
            <h3 class="nav-section-title">
                <i class="icon-sales"></i>
                Registrar Vendas
            </h3>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="<?php echo STORE_REGISTER_TRANSACTION_URL; ?>" 
                       class="nav-link action-primary <?php echo ($activeMenu == 'register-transaction') ? 'active' : ''; ?>"
                       data-tooltip="Cadastre uma nova venda e gere cashback">
                        <div class="nav-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="16"></line>
                                <line x1="8" y1="12" x2="16" y2="12"></line>
                            </svg>
                        </div>
                        <span class="nav-text">Nova Venda</span>
                        <span class="nav-description">Registre uma venda</span>
                        <div class="action-badge">Ação Principal</div>
                        <?php if($activeMenu == 'register-transaction'): ?>
                            <div class="nav-indicator"></div>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo STORE_TRANSACTIONS_URL; ?>" 
                       class="nav-link <?php echo ($activeMenu == 'transactions') ? 'active' : ''; ?>"
                       data-tooltip="Veja todas as vendas registradas">
                        <div class="nav-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                        </div>
                        <span class="nav-text">Minhas Vendas</span>
                        <span class="nav-description">Histórico completo</span>
                        <?php if($activeMenu == 'transactions'): ?>
                            <div class="nav-indicator"></div>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Seção: Financeiro -->
        <div class="nav-section">
            <h3 class="nav-section-title">
                <i class="icon-money"></i>
                Área Financeira
            </h3>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="<?php echo STORE_PENDING_TRANSACTIONS_URL; ?>" 
                       class="nav-link <?php echo ($activeMenu == 'pending-commissions') ? 'active' : ''; ?>"
                       data-tooltip="Comissões que você precisa pagar">
                        <div class="nav-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                        <span class="nav-text">Pendente de Pagamento</span>
                        <span class="nav-description">Comissões a pagar</span>
                        <div class="status-badge warning">Atenção</div>
                        <?php if($activeMenu == 'pending-commissions'): ?>
                            <div class="nav-indicator"></div>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo STORE_PAYMENT_URL; ?>" 
                       class="nav-link <?php echo ($activeMenu == 'payment') ? 'active' : ''; ?>"
                       data-tooltip="Efetue o pagamento das comissões">
                        <div class="nav-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                        </div>
                        <span class="nav-text">Pagar Comissões</span>
                        <span class="nav-description">PIX ou transferência</span>
                        <?php if($activeMenu == 'payment'): ?>
                            <div class="nav-indicator"></div>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo STORE_PAYMENT_HISTORY_URL; ?>" 
                       class="nav-link <?php echo ($activeMenu == 'payment-history') ? 'active' : ''; ?>"
                       data-tooltip="Histórico de todos os seus pagamentos">
                        <div class="nav-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 3v5h5"></path>
                                <path d="M21 21v-5h-5"></path>
                                <path d="M20.2 15.2A9 9 0 1 1 15.2 3.8"></path>
                            </svg>
                        </div>
                        <span class="nav-text">Histórico de Pagamentos</span>
                        <span class="nav-description">Consulte pagamentos</span>
                        <?php if($activeMenu == 'payment-history'): ?>
                            <div class="nav-indicator"></div>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Footer da Sidebar -->
    <div class="sidebar-footer store-footer">
        <a href="<?php echo SITE_URL; ?>/store/perfil" 
           class="footer-link <?php echo ($activeMenu == 'profile') ? 'active' : ''; ?>"
           data-tooltip="Configurações da sua loja">
            <div class="footer-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
            <span class="footer-text">Meu Perfil</span>
        </a>
        
        <div class="help-section">
            <div class="help-info">
                <h4>Como funciona?</h4>
                <p>1. Registre suas vendas<br>
                   2. Pague 10% de comissão<br>
                   3. Cliente recebe 5% de cashback</p>
            </div>
        </div>
        
        <button class="logout-btn" onclick="confirmLogout()" data-tooltip="Sair do painel da loja">
            <div class="logout-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </div>
            <span class="logout-text">Sair</span>
        </button>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const navLinks = document.querySelectorAll('.nav-link');
    
    // Toggle sidebar
    function toggleSidebar() {
        sidebar.classList.toggle('open');
        sidebarOverlay.classList.toggle('active');
        document.body.classList.toggle('sidebar-open');
    }
    
    // Event listeners
    sidebarToggle?.addEventListener('click', toggleSidebar);
    sidebarOverlay?.addEventListener('click', toggleSidebar);
    
    // Fechar sidebar ao clicar em um link (mobile)
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                setTimeout(toggleSidebar, 100);
            }
        });
    });
    
    // Responsive behavior
    function handleResize() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('active');
            document.body.classList.remove('sidebar-open');
        }
    }
    
    window.addEventListener('resize', handleResize);
    
    // Tooltips para ajudar usuários leigos
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
        
        // Touch devices
        element.addEventListener('touchstart', showTooltip);
        element.addEventListener('touchend', () => {
            setTimeout(hideTooltip, 2000);
        });
    });
    
    function showTooltip(e) {
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip store-tooltip';
        tooltip.innerHTML = `<div class="tooltip-content">${e.target.dataset.tooltip}</div>`;
        document.body.appendChild(tooltip);
        
        const rect = e.target.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        
        let left = rect.right + 10;
        let top = rect.top + (rect.height / 2) - (tooltipRect.height / 2);
        
        // Ajustar se sair da tela
        if (left + tooltipRect.width > window.innerWidth) {
            left = rect.left - tooltipRect.width - 10;
        }
        
        if (top < 10) top = 10;
        if (top + tooltipRect.height > window.innerHeight - 10) {
            top = window.innerHeight - tooltipRect.height - 10;
        }
        
        tooltip.style.left = left + 'px';
        tooltip.style.top = top + 'px';
        
        setTimeout(() => tooltip.classList.add('show'), 10);
    }
    
    function hideTooltip() {
        const tooltips = document.querySelectorAll('.tooltip');
        tooltips.forEach(tooltip => tooltip.remove());
    }
    
    // Adicionar feedback visual aos clicks
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            this.classList.add('clicked');
            setTimeout(() => {
                this.classList.remove('clicked');
            }, 200);
        });
    });
});

function confirmLogout() {
    if (confirm('Tem certeza que deseja sair do painel da loja?')) {
        window.location.href = '<?php echo SITE_URL; ?>/controllers/AuthController.php?action=logout';
    }
}
</script>