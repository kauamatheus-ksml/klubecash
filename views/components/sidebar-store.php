<?php
/**
 * Sidebar da Loja Reformulada - Super Simples para Lojistas
 * Versão 3.0 - Foco em facilidade de uso e responsividade
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

$activeMenu = $activeMenu ?? 'dashboard';

// Buscar dados da loja
try {
    require_once '../../config/database.php';
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

<link rel="stylesheet" href="../../assets/css/sidebar-styles.css">

<!-- Toggle Button Personalizado para Loja -->
<button class="sidebar-toggle store-theme" id="sidebarToggle" aria-label="Menu da Loja" title="Abrir Menu da Loja">
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
    <!-- Header da Loja -->
    <div class="sidebar-header">
        <div class="store-brand">
            <?php if ($storeLogo): ?>
                <img src="../../uploads/store_logos/<?php echo htmlspecialchars($storeLogo); ?>" 
                     alt="<?php echo htmlspecialchars($storeName); ?>" 
                     class="store-logo">
            <?php else: ?>
                <div class="store-logo-placeholder">
                    🏪
                </div>
            <?php endif; ?>
            <div class="store-info">
                <span class="store-name"><?php echo htmlspecialchars($storeName); ?></span>
                <span class="store-badge">✅ Loja Parceira</span>
            </div>
        </div>
        <div class="klube-brand">
            <img src="../../assets/images/logo.png" alt="KlubeCash" class="klube-logo">
        </div>
    </div>

    <!-- Navegação Super Simples -->
    <nav class="sidebar-nav">
        <!-- Seção: Visão Geral -->
        <div class="nav-section">
            <h3 class="nav-section-title">
                📊 Minha Loja
            </h3>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="<?php echo STORE_DASHBOARD_URL; ?>" 
                       class="nav-link <?php echo ($activeMenu == 'dashboard') ? 'active' : ''; ?>"
                       data-tooltip="Resumo das suas vendas e ganhos">
                        <div class="nav-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                        </div>
                        <div>
                            <span class="nav-text">Meu Painel</span>
                            <span class="nav-description">Resumo da loja</span>
                        </div>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Seção: Ação Principal -->
        <div class="nav-section highlight">
            <h3 class="nav-section-title">
                💰 Registrar Vendas
            </h3>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="<?php echo STORE_REGISTER_TRANSACTION_URL; ?>" 
                       class="nav-link action-primary <?php echo ($activeMenu == 'register-transaction') ? 'active' : ''; ?>"
                       data-tooltip="Cadastre uma venda e gere cashback para o cliente">
                        <div class="nav-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="16"></line>
                                <line x1="8" y1="12" x2="16" y2="12"></line>
                            </svg>
                        </div>
                        <div>
                            <span class="nav-text">➕ Nova Venda</span>
                            <span class="nav-description">Cadastrar agora</span>
                        </div>
                        <div class="action-badge">Principal</div>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo STORE_TRANSACTIONS_URL; ?>" 
                       class="nav-link <?php echo ($activeMenu == 'transactions') ? 'active' : ''; ?>"
                       data-tooltip="Lista de todas as suas vendas registradas">
                        <div class="nav-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                            </svg>
                        </div>
                        <div>
                            <span class="nav-text">📋 Minhas Vendas</span>
                            <span class="nav-description">Ver histórico</span>
                        </div>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Seção: Pagamentos -->
        <div class="nav-section">
            <h3 class="nav-section-title">
                💳 Pagamentos
            </h3>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="<?php echo STORE_PENDING_TRANSACTIONS_URL; ?>" 
                       class="nav-link <?php echo ($activeMenu == 'pending-commissions') ? 'active' : ''; ?>"
                       data-tooltip="Comissões que você precisa pagar para liberar o cashback">
                        <div class="nav-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                        <div>
                            <span class="nav-text">⏰ Pendente</span>
                            <span class="nav-description">Comissões a pagar</span>
                        </div>
                        <div class="status-badge warning">Atenção</div>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo STORE_PAYMENT_URL; ?>" 
                       class="nav-link <?php echo ($activeMenu == 'payment') ? 'active' : ''; ?>"
                       data-tooltip="Pague as comissões via PIX">
                        <div class="nav-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                        </div>
                        <div>
                            <span class="nav-text">💳 Pagar PIX</span>
                            <span class="nav-description">Quitar comissões</span>
                        </div>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo STORE_PAYMENT_HISTORY_URL; ?>" 
                       class="nav-link <?php echo ($activeMenu == 'payment-history') ? 'active' : ''; ?>"
                       data-tooltip="Histórico de pagamentos realizados">
                        <div class="nav-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 3v5h5"></path>
                                <path d="M21 21v-5h-5"></path>
                                <path d="M20.2 15.2A9 9 0 1 1 15.2 3.8"></path>
                            </svg>
                        </div>
                        <div>
                            <span class="nav-text">📜 Histórico</span>
                            <span class="nav-description">Pagamentos feitos</span>
                        </div>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Footer Sempre Visível -->
    <div class="sidebar-footer store-footer">
        <!-- Ajuda Rápida -->
        <div class="help-section">
            <div class="help-info">
                <h4>💡 Como funciona?</h4>
                <p><strong>1.</strong> Registre suas vendas<br>
                   <strong>2.</strong> Pague 10% de comissão<br>
                   <strong>3.</strong> Cliente recebe 5% de cashback</p>
            </div>
        </div>
        
        <a href="<?php echo STORE_PROFILE_URL; ?>" 
           class="footer-link <?php echo ($activeMenu == 'profile') ? 'active' : ''; ?>"
           data-tooltip="Editar informações da sua loja">
            <div class="footer-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
            <span class="footer-text">Meus Dados</span>
        </a>
        
        <button class="logout-btn" onclick="confirmLogout()" data-tooltip="Sair do painel da loja">
            <div class="logout-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </div>
            <span class="logout-text">Sair da Loja</span>
        </button>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeStoreSidebar();
});

function initializeStoreSidebar() {
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
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', toggleSidebar);
    }
    
    // Fechar sidebar no mobile ao clicar em link
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Feedback visual para lojistas leigos
            this.classList.add('clicked');
            setTimeout(() => {
                this.classList.remove('clicked');
            }, 200);
            
            // Fechar no mobile
            if (window.innerWidth <= 768) {
                setTimeout(toggleSidebar, 150);
            }
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
    
    // Tooltips especiais para lojistas
    initializeStoreTooltips();
    
    // Destaque para ação principal (Nova Venda)
    highlightMainAction();
}

function initializeStoreTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        // Desktop: hover
        element.addEventListener('mouseenter', showStoreTooltip);
        element.addEventListener('mouseleave', hideStoreTooltip);
        
        // Mobile: touch
        element.addEventListener('touchstart', function(e) {
            showStoreTooltip(e);
            setTimeout(hideStoreTooltip, 3000); // 3 segundos no mobile
        });
    });
    
    function showStoreTooltip(e) {
        const tooltipText = e.target.closest('[data-tooltip]').dataset.tooltip;
        
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip store-tooltip';
        tooltip.innerHTML = `
            <div class="tooltip-content">
                <strong>💡 Dica:</strong><br>
                ${tooltipText}
            </div>
        `;
        document.body.appendChild(tooltip);
        
        const rect = e.target.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        
        let left = rect.right + 15;
        let top = rect.top + (rect.height / 2) - (tooltipRect.height / 2);
        
        // Ajustar se sair da tela
        if (left + tooltipRect.width > window.innerWidth) {
            left = rect.left - tooltipRect.width - 15;
        }
        
        if (top < 10) top = 10;
        if (top + tooltipRect.height > window.innerHeight - 10) {
            top = window.innerHeight - tooltipRect.height - 10;
        }
        
        tooltip.style.left = left + 'px';
        tooltip.style.top = top + 'px';
        
        setTimeout(() => tooltip.classList.add('show'), 10);
    }
    
    function hideStoreTooltip() {
        const tooltips = document.querySelectorAll('.tooltip');
        tooltips.forEach(tooltip => tooltip.remove());
    }
}

function highlightMainAction() {
    const mainActionLink = document.querySelector('.action-primary');
    if (mainActionLink) {
        // Pulsar suavemente para chamar atenção
        setInterval(() => {
            mainActionLink.style.transform = 'scale(1.02)';
            setTimeout(() => {
                mainActionLink.style.transform = 'scale(1)';
            }, 500);
        }, 5000); // A cada 5 segundos
    }
}

function confirmLogout() {
    if (confirm('🤔 Tem certeza que deseja sair?\n\n✅ Suas vendas estão salvas!\n❌ Você precisará fazer login novamente.')) {
        // Feedback visual
        const logoutBtn = document.querySelector('.logout-btn');
        logoutBtn.innerHTML = '⏳ Saindo...';
        logoutBtn.style.opacity = '0.7';
        
        setTimeout(() => {
            window.location.href = '<?php echo SITE_URL; ?>/controllers/AuthController.php?action=logout';
        }, 500);
    }
}

// Dicas contextuais para lojistas leigos
window.addEventListener('load', function() {
    setTimeout(() => {
        if (window.innerWidth > 768 && !localStorage.getItem('store_tip_shown')) {
            showWelcomeTip();
            localStorage.setItem('store_tip_shown', 'true');
        }
    }, 2000);
});

function showWelcomeTip() {
    const welcomeTooltip = document.createElement('div');
    welcomeTooltip.className = 'tooltip store-tooltip show';
    welcomeTooltip.innerHTML = `
        <div class="tooltip-content">
            <strong>🎉 Bem-vindo!</strong><br>
            Clique em "➕ Nova Venda" para começar a registrar suas vendas e gerar cashback para seus clientes!
            <br><br>
            <small>💡 Esta dica aparece apenas uma vez</small>
        </div>
    `;
    document.body.appendChild(welcomeTooltip);
    
    const mainAction = document.querySelector('.action-primary');
    if (mainAction) {
        const rect = mainAction.getBoundingClientRect();
        welcomeTooltip.style.left = (rect.right + 15) + 'px';
        welcomeTooltip.style.top = rect.top + 'px';
        
        setTimeout(() => {
            welcomeTooltip.remove();
        }, 8000);
    }
}
</script>