<?php
/**
 * SIDEBAR ISOLADA - KLUBE CASH
 * Versão final e completa sem interferências
 * 
 * FILOSOFIA DO CÓDIGO:
 * - Isolamento total de estilos e JavaScript
 * - Não interferência em formulários ou funcionalidades da página
 * - Responsividade completa para todos os dispositivos
 * - Acessibilidade seguindo padrões WCAG
 * - Performance otimizada com lazy loading e throttling
 * 
 * @param string $activeMenu - Menu ativo atual para destacar na navegação
 */

// ====================================
// VERIFICAÇÕES DE SEGURANÇA
// ====================================

// Iniciar sessão apenas se não estiver ativa (evita warnings)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado e tem permissão
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'loja') {
    header('Location: ' . LOGIN_URL . '?error=acesso_restrito');
    exit;
}

// ====================================
// CONFIGURAÇÃO DE DADOS
// ====================================

// Definir menu ativo com fallback seguro
$activeMenu = $activeMenu ?? 'dashboard';

// Dados do usuário com fallbacks
$userName = $_SESSION['user_name'] ?? 'Lojista';
$userEmail = $_SESSION['user_email'] ?? '';
$storeId = $_SESSION['store_id'] ?? null;

// ====================================
// GERAÇÃO DE INICIAIS DO USUÁRIO
// Lógica para criar avatar com iniciais do nome
// ====================================
$initials = '';
$nameParts = explode(' ', trim($userName));

if (count($nameParts) >= 2) {
    // Se tem nome e sobrenome, pega primeira letra de cada
    $initials = substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1);
} elseif (count($nameParts) === 1 && strlen($nameParts[0]) >= 2) {
    // Se tem apenas um nome, pega as duas primeiras letras
    $initials = substr($nameParts[0], 0, 2);
} else {
    // Fallback para casos edge
    $initials = 'US';
}

$initials = strtoupper($initials);

// ====================================
// CONFIGURAÇÃO DOS ITENS DO MENU
// Cada item contém: id, título, URL e ícone SVG específico
// ====================================
$menuItems = [
    [
        'id' => 'dashboard',
        'title' => 'Dashboard',
        'url' => STORE_DASHBOARD_URL,
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"/>',
        'description' => 'Visão geral das suas vendas e métricas'
    ],
    [
        'id' => 'register-transaction',
        'title' => 'Nova Venda',
        'url' => STORE_REGISTER_TRANSACTION_URL,
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>',
        'description' => 'Registrar uma nova transação de venda'
    ],
    [
        'id' => 'transactions',
        'title' => 'Vendas',
        'url' => STORE_TRANSACTIONS_URL,
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>',
        'description' => 'Histórico de todas as suas vendas'
    ],
    [
        'id' => 'batch-upload',
        'title' => 'Upload em Lote',
        'url' => STORE_BATCH_UPLOAD_URL,
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>',
        'description' => 'Importar múltiplas transações via arquivo'
    ],
    [
        'id' => 'payment-history',
        'title' => 'Pagamentos',
        'url' => STORE_PAYMENT_HISTORY_URL,
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>',
        'description' => 'Histórico de pagamentos e comissões',
        'badge' => 3 // Exemplo: 3 pagamentos pendentes
    ],
    [
        'id' => 'saldos',
        'title' => 'Saldos',
        'url' => STORE_SALDOS_URL,
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>',
        'description' => 'Consulte seus saldos e repasses'
    ],
    [
        'id' => 'profile',
        'title' => 'Perfil',
        'url' => STORE_PROFILE_URL,
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
        'description' => 'Configurações do seu perfil e loja'
    ]
];

// ====================================
// VERIFICAÇÃO DE BADGE DINÂMICA
// Em implementação real, essas badges viriam do banco de dados
// ====================================
$pendingPayments = 0; // Aqui viria uma consulta ao banco
$newNotifications = 0; // Aqui viria uma consulta ao banco

// Atualizar badges baseado em dados reais
foreach ($menuItems as &$item) {
    if ($item['id'] === 'payment-history' && $pendingPayments > 0) {
        $item['badge'] = $pendingPayments;
    }
}
unset($item); // Limpar referência
?>

<!-- CARREGAMENTO DO CSS ISOLADO -->
<link rel="stylesheet" href="../../assets/css/sidebar-store-modern.css">

<!-- ====================================
     CONTROLES MOBILE E DESKTOP
     Botões que aparecem em diferentes situações
     ==================================== -->

<!-- Toggle Mobile: Aparece apenas no mobile -->
<button class="klube-sidebar-mobile-toggle" 
        id="klubeMobileToggle" 
        aria-label="Abrir menu de navegação"
        title="Abrir menu (Pressione Alt+M)">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
    </svg>
</button>

<!-- Expand Button: Aparece quando sidebar está colapsada no desktop -->
<button class="klube-sidebar-expand" 
        id="klubeExpandBtn" 
        aria-label="Expandir menu de navegação"
        title="Expandir menu (Pressione Ctrl+B)">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="9,18 15,12 9,6"></polyline>
    </svg>
</button>

<!-- Overlay: Fundo escuro que aparece no mobile quando sidebar está aberta -->
<div class="klube-sidebar-overlay" 
     id="klubeOverlay" 
     aria-hidden="true"></div>

<!-- ====================================
     CONTAINER PRINCIPAL DA SIDEBAR
     ==================================== -->
<div class="klube-sidebar-wrapper">
    <aside class="klube-sidebar" 
           id="klubeSidebar" 
           role="navigation" 
           aria-label="Menu principal de navegação"
           aria-hidden="false">
        
        <!-- ====================================
             HEADER DA SIDEBAR
             Contém logo e botão de collapse
             ==================================== -->
        <header class="klube-sidebar-header">
            <div class="klube-sidebar-logo-section">
                <img src="../../assets/images/logo.png" 
                     alt="Klube Cash - Sistema de Cashback" 
                     class="klube-sidebar-logo"
                     loading="lazy">
                <span class="klube-sidebar-logo-text">Klube Cash</span>
            </div>
            
            <button class="klube-sidebar-toggle" 
                    id="klubeToggle" 
                    aria-label="Recolher menu de navegação"
                    title="Recolher/Expandir menu (Ctrl+B)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15,18 9,12 15,6"></polyline>
                </svg>
            </button>
        </header>

        <!-- ====================================
             PERFIL DO USUÁRIO
             Exibe avatar, nome e tipo de usuário
             ==================================== -->
        <div class="klube-sidebar-profile">
            <div class="klube-sidebar-avatar" 
                 title="<?= htmlspecialchars($userName) ?> - Clique para ver perfil"
                 role="button"
                 tabindex="0">
                <?= $initials ?>
            </div>
            
            <div class="klube-sidebar-user-info">
                <div class="klube-sidebar-user-name" 
                     title="<?= htmlspecialchars($userName) ?>">
                    <?= htmlspecialchars($userName) ?>
                </div>
                
                <div class="klube-sidebar-user-role">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Lojista
                </div>
            </div>
        </div>

        <!-- ====================================
             NAVEGAÇÃO PRINCIPAL
             Menu com todos os itens de navegação
             ==================================== -->
        <nav class="klube-sidebar-nav" role="navigation">
            <div class="klube-sidebar-section">
                <h3 class="klube-sidebar-section-title">Menu Principal</h3>
                
                <ul class="klube-sidebar-menu" role="menubar">
                    <?php foreach ($menuItems as $index => $item): ?>
                        <li class="klube-sidebar-menu-item" role="none">
                            <a href="<?= htmlspecialchars($item['url']) ?>" 
                               class="klube-sidebar-menu-link <?= ($activeMenu === $item['id']) ? 'active' : '' ?>"
                               role="menuitem"
                               aria-current="<?= ($activeMenu === $item['id']) ? 'page' : 'false' ?>"
                               data-page="<?= htmlspecialchars($item['id']) ?>"
                               title="<?= htmlspecialchars($item['description'] ?? $item['title']) ?>"
                               <?php if ($activeMenu === $item['id']): ?>
                                   aria-describedby="current-page-description"
                               <?php endif; ?>>
                                
                                <!-- Ícone do menu -->
                                <span class="klube-sidebar-menu-icon" 
                                      aria-hidden="true">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <?= $item['icon'] ?>
                                    </svg>
                                </span>
                                
                                <!-- Texto do menu -->
                                <span class="klube-sidebar-menu-text">
                                    <?= htmlspecialchars($item['title']) ?>
                                </span>
                                
                                <!-- Badge de notificação (se houver) -->
                                <?php if (isset($item['badge']) && $item['badge'] > 0): ?>
                                    <span class="klube-sidebar-badge" 
                                          aria-label="<?= $item['badge'] ?> itens pendentes"
                                          title="<?= $item['badge'] ?> <?= $item['badge'] === 1 ? 'item pendente' : 'itens pendentes' ?>">
                                        <?= $item['badge'] ?>
                                    </span>
                                <?php endif; ?>
                                
                                <!-- Tooltip para modo colapsado -->
                                <span class="klube-sidebar-tooltip" 
                                      role="tooltip" 
                                      aria-hidden="true">
                                    <?= htmlspecialchars($item['title']) ?>
                                </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </nav>

        <!-- ====================================
             FOOTER COM LOGOUT
             Botão de logout sempre acessível
             ==================================== -->
        <footer class="klube-sidebar-footer">
            <a href="../../auth/logout.php" 
               class="klube-sidebar-logout"
               onclick="return confirm('Tem certeza que deseja sair do sistema?')"
               title="Sair do sistema"
               role="button">
                <svg class="klube-sidebar-logout-icon" 
                     viewBox="0 0 24 24" 
                     fill="none" 
                     stroke="currentColor" 
                     stroke-width="2"
                     aria-hidden="true">
                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4m7 14l5-5-5-5m5 5H9"/>
                </svg>
                <span class="klube-sidebar-logout-text">Sair</span>
            </a>
        </footer>

    </aside>
</div>

<!-- Texto oculto para acessibilidade -->
<span id="current-page-description" class="klube-sidebar-hidden">
    Página atual: você está navegando nesta seção
</span>

<script>
/**
 * SIDEBAR ISOLADA - JAVASCRIPT FINAL
 * 
 * ARQUITETURA:
 * - IIFE (Immediately Invoked Function Expression) para isolamento
 * - Namespace único para evitar conflitos globais
 * - Event delegation para performance
 * - Throttling em eventos de resize
 * - Accessible keyboard navigation
 * - Mobile-first responsive behavior
 */
(function() {
    'use strict';
    
    // ====================================
    // CONSTANTES E CONFIGURAÇÕES
    // ====================================
    
    const CONFIG = {
        STORAGE_KEY: 'klubeSidebarCollapsed',
        MOBILE_BREAKPOINT: 768,
        HIDE_DELAY: 2000,
        RESIZE_THROTTLE: 100,
        ANIMATION_DURATION: 300
    };
    
    const SELECTORS = {
        sidebar: '#klubeSidebar',
        toggle: '#klubeToggle',
        mobileToggle: '#klubeMobileToggle',
        expandBtn: '#klubeExpandBtn',
        overlay: '#klubeOverlay',
        navLinks: '.klube-sidebar-menu-link',
        mainContent: '.main-content, .content, .page-content'
    };
    
    const CSS_CLASSES = {
        collapsed: 'collapsed',
        open: 'open',
        active: 'active',
        hidden: 'hidden',
        loading: 'loading',
        show: 'show',
        sidebarOpen: 'klube-sidebar-open',
        mainContent: 'klube-main-content'
    };
    
    // ====================================
    // ESTADO E ELEMENTOS
    // ====================================
    
    const elements = {};
    const state = {
        isCollapsed: localStorage.getItem(CONFIG.STORAGE_KEY) === 'true',
        isMobileOpen: false,
        hideTimeout: null,
        resizeTimeout: null
    };
    
    // ====================================
    // INICIALIZAÇÃO
    // ====================================
    
    function init() {
        try {
            findElements();
            
            if (!elements.sidebar) {
                console.warn('Klube Sidebar: Elemento sidebar não encontrado');
                return;
            }
            
            bindEvents();
            initState();
            initAccessibility();
            
            console.log('✅ Klube Sidebar inicializada sem interferências');
        } catch (error) {
            console.error('❌ Erro ao inicializar Klube Sidebar:', error);
        }
    }
    
    // ====================================
    // BUSCA DE ELEMENTOS
    // ====================================
    
    function findElements() {
        Object.keys(SELECTORS).forEach(key => {
            elements[key] = document.querySelector(SELECTORS[key]);
        });
        
        // Buscar múltiplos elementos
        elements.navLinksAll = document.querySelectorAll(SELECTORS.navLinks);
    }
    
    // ====================================
    // VINCULAÇÃO DE EVENTOS
    // ====================================
    
    function bindEvents() {
        // Eventos dos botões principais
        bindButtonEvents();
        
        // Eventos de navegação
        bindNavigationEvents();
        
        // Eventos de sistema
        bindSystemEvents();
        
        // Eventos de teclado
        bindKeyboardEvents();
        
        // Eventos de touch (mobile)
        bindTouchEvents();
    }
    
    function bindButtonEvents() {
        // Toggle desktop
        if (elements.toggle) {
            elements.toggle.addEventListener('click', handleDesktopToggle);
        }
        
        // Expand button
        if (elements.expandBtn) {
            elements.expandBtn.addEventListener('click', handleDesktopToggle);
        }
        
        // Mobile toggle
        if (elements.mobileToggle) {
            elements.mobileToggle.addEventListener('click', handleMobileToggle);
        }
        
        // Overlay
        if (elements.overlay) {
            elements.overlay.addEventListener('click', handleOverlayClick);
        }
    }
    
    function bindNavigationEvents() {
        // Links de navegação
        elements.navLinksAll.forEach(link => {
            link.addEventListener('click', handleNavClick);
        });
        
        // Cliques fora da sidebar (mobile)
        document.addEventListener('click', handleOutsideClick);
    }
    
    function bindSystemEvents() {
        // Resize com throttling
        window.addEventListener('resize', throttledResize);
        
        // Visibility change (para pausar animações quando tab não está ativa)
        document.addEventListener('visibilitychange', handleVisibilityChange);
    }
    
    function bindKeyboardEvents() {
        document.addEventListener('keydown', handleKeyboard);
        
        // Navegação por teclado dentro da sidebar
        elements.sidebar.addEventListener('keydown', handleSidebarKeyboard);
    }
    
    function bindTouchEvents() {
        // Implementar swipe gestures no mobile
        let touchStartX = 0;
        let touchCurrentX = 0;
        let isDragging = false;
        
        // Swipe para abrir
        document.addEventListener('touchstart', function(e) {
            if (isMobile() && e.touches[0].clientX < 50) {
                touchStartX = e.touches[0].clientX;
                isDragging = true;
            }
        });
        
        document.addEventListener('touchmove', function(e) {
            if (!isDragging) return;
            touchCurrentX = e.touches[0].clientX;
            
            // Prevenir scroll se movimento horizontal significativo
            if (Math.abs(touchCurrentX - touchStartX) > 10) {
                e.preventDefault();
            }
        }, { passive: false });
        
        document.addEventListener('touchend', function() {
            if (!isDragging) return;
            
            const diff = touchCurrentX - touchStartX;
            if (diff > 50 && !state.isMobileOpen) {
                openMobile();
            }
            
            isDragging = false;
        });
        
        // Swipe para fechar dentro da sidebar
        if (elements.sidebar) {
            elements.sidebar.addEventListener('touchstart', function(e) {
                if (isMobile() && state.isMobileOpen) {
                    touchStartX = e.touches[0].clientX;
                    isDragging = true;
                }
            });
            
            elements.sidebar.addEventListener('touchend', function() {
                if (!isDragging) return;
                
                const diff = touchStartX - touchCurrentX;
                if (diff > 100) {
                    closeMobile();
                }
                
                isDragging = false;
            });
        }
    }
    
    // ====================================
    // HANDLERS DE EVENTOS
    // ====================================
    
    function handleDesktopToggle(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (isMobile()) return;
        
        toggleDesktop();
    }
    
    function handleMobileToggle(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (!isMobile()) return;
        
        toggleMobile();
    }
    
    function handleOverlayClick(e) {
        e.preventDefault();
        e.stopPropagation();
        
        closeMobile();
    }
    
    function handleNavClick(e) {
        const link = e.currentTarget;
        
        // Adicionar estado de loading
        link.classList.add(CSS_CLASSES.loading);
        
        // Fechar sidebar mobile se aberta
        if (isMobile() && state.isMobileOpen) {
            setTimeout(closeMobile, 100);
        }
        
        // Remover loading após timeout
        setTimeout(() => {
            link.classList.remove(CSS_CLASSES.loading);
        }, 2000);
        
        // Analytics/tracking
        trackNavigation(link.dataset.page);
    }
    
    function handleOutsideClick(e) {
        if (!isMobile() || !state.isMobileOpen) return;
        
        const clickedInsideSidebar = elements.sidebar.contains(e.target);
        const clickedOnToggle = elements.mobileToggle.contains(e.target);
        
        if (!clickedInsideSidebar && !clickedOnToggle) {
            closeMobile();
        }
    }
    
    function handleKeyboard(e) {
        // ESC para fechar sidebar mobile
        if (e.key === 'Escape' && isMobile() && state.isMobileOpen) {
            e.preventDefault();
            closeMobile();
        }
        
        // Ctrl+B para toggle sidebar desktop
        if (e.ctrlKey && e.key === 'b' && !isMobile()) {
            e.preventDefault();
            toggleDesktop();
        }
        
        // Alt+M para toggle mobile
        if (e.altKey && e.key === 'm' && isMobile()) {
            e.preventDefault();
            toggleMobile();
        }
    }
    
    function handleSidebarKeyboard(e) {
        const focusableElements = elements.sidebar.querySelectorAll(
            'a, button, [tabindex]:not([tabindex="-1"])'
        );
        
        const currentIndex = Array.from(focusableElements).indexOf(document.activeElement);
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                const nextIndex = (currentIndex + 1) % focusableElements.length;
                focusableElements[nextIndex].focus();
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                const prevIndex = currentIndex === 0 ? focusableElements.length - 1 : currentIndex - 1;
                focusableElements[prevIndex].focus();
                break;
                
            case 'Home':
                e.preventDefault();
                focusableElements[0].focus();
                break;
                
            case 'End':
                e.preventDefault();
                focusableElements[focusableElements.length - 1].focus();
                break;
        }
    }
    
    function handleVisibilityChange() {
        // Pausar animações quando tab não está ativa para economizar recursos
        if (document.hidden) {
            elements.sidebar.style.animationPlayState = 'paused';
        } else {
            elements.sidebar.style.animationPlayState = 'running';
        }
    }
    
    // ====================================
    // FUNÇÕES PRINCIPAIS
    // ====================================
    
    function toggleDesktop() {
        if (isMobile()) return;
        
        state.isCollapsed = !state.isCollapsed;
        elements.sidebar.classList.toggle(CSS_CLASSES.collapsed, state.isCollapsed);
        localStorage.setItem(CONFIG.STORAGE_KEY, state.isCollapsed);
        
        updateMainContent();
        updateExpandButton();
        dispatchToggleEvent();
        
        // Feedback sonoro para acessibilidade (se disponível)
        if ('speechSynthesis' in window && state.isCollapsed) {
            // Feedback mínimo sem interferir
        }
    }
    
    function toggleMobile() {
        if (!isMobile()) return;
        
        if (state.isMobileOpen) {
            closeMobile();
        } else {
            openMobile();
        }
    }
    
    function openMobile() {
        state.isMobileOpen = true;
        elements.sidebar.classList.add(CSS_CLASSES.open);
        elements.overlay.classList.add(CSS_CLASSES.active);
        document.body.classList.add(CSS_CLASSES.sidebarOpen);
        
        hideMobileToggleDelayed();
        updateAriaStates(false);
        
        // Focus management
        const firstFocusable = elements.sidebar.querySelector('a, button');
        if (firstFocusable) {
            setTimeout(() => firstFocusable.focus(), CONFIG.ANIMATION_DURATION);
        }
    }
    
    function closeMobile() {
        state.isMobileOpen = false;
        elements.sidebar.classList.remove(CSS_CLASSES.open);
        elements.overlay.classList.remove(CSS_CLASSES.active);
        document.body.classList.remove(CSS_CLASSES.sidebarOpen);
        
        clearHideTimeout();
        showMobileToggle();
        updateAriaStates(true);
    }
    
    function hideMobileToggleDelayed() {
        clearHideTimeout();
        state.hideTimeout = setTimeout(() => {
            if (state.isMobileOpen && elements.mobileToggle) {
                elements.mobileToggle.classList.add(CSS_CLASSES.hidden);
            }
        }, CONFIG.HIDE_DELAY);
    }
    
    function showMobileToggle() {
        if (elements.mobileToggle) {
            elements.mobileToggle.classList.remove(CSS_CLASSES.hidden);
        }
    }
    
    function clearHideTimeout() {
        if (state.hideTimeout) {
            clearTimeout(state.hideTimeout);
            state.hideTimeout = null;
        }
    }
    
    // ====================================
    // FUNÇÕES DE ATUALIZAÇÃO
    // ====================================
    
    function updateMainContent() {
        const main = document.querySelector(SELECTORS.mainContent);
        if (main) {
            main.classList.add(CSS_CLASSES.mainContent);
            
            if (isMobile()) {
                main.style.marginLeft = '0';
            } else {
                main.style.marginLeft = state.isCollapsed ? '80px' : '280px';
            }
            
            main.style.transition = 'margin-left 0.3s ease';
        }
    }
    
    function updateExpandButton() {
        if (elements.expandBtn) {
            if (!isMobile() && state.isCollapsed) {
                elements.expandBtn.classList.add(CSS_CLASSES.show);
            } else {
                elements.expandBtn.classList.remove(CSS_CLASSES.show);
            }
        }
    }
    
    function updateAriaStates(hidden) {
        elements.sidebar.setAttribute('aria-hidden', hidden);
        if (elements.mobileToggle) {
            elements.mobileToggle.setAttribute('aria-expanded', !hidden);
        }
    }
    
    function dispatchToggleEvent() {
        const event = new CustomEvent('klubeSidebarToggle', {
            detail: {
                collapsed: state.isCollapsed,
                width: state.isCollapsed ? 80 : 280,
                timestamp: Date.now()
            }
        });
        
        window.dispatchEvent(event);
    }
    
    // ====================================
    // FUNÇÕES DE INICIALIZAÇÃO
    // ====================================
    
    function initState() {
        if (!isMobile() && state.isCollapsed) {
            elements.sidebar.classList.add(CSS_CLASSES.collapsed);
        }
        
        updateMainContent();
        updateExpandButton();
    }
    
    function initAccessibility() {
        // ARIA labels
        elements.sidebar.setAttribute('role', 'navigation');
        elements.sidebar.setAttribute('aria-label', 'Menu principal');
        
        if (isMobile()) {
            updateAriaStates(true);
        }
        
        // Skip link para navegação por teclado
        const skipLink = document.createElement('a');
        skipLink.href = '#main-content';
        skipLink.textContent = 'Pular para conteúdo principal';
        skipLink.className = 'klube-sidebar-skip-link';
        skipLink.style.cssText = `
            position: absolute;
            top: -40px;
            left: 6px;
            background: #000;
            color: #fff;
            padding: 8px;
            text-decoration: none;
            z-index: 100000;
            transition: top 0.3s;
        `;
        
        skipLink.addEventListener('focus', () => {
            skipLink.style.top = '6px';
        });
        
        skipLink.addEventListener('blur', () => {
            skipLink.style.top = '-40px';
        });
        
        document.body.insertBefore(skipLink, document.body.firstChild);
    }
    
    // ====================================
    // FUNÇÕES UTILITÁRIAS
    // ====================================
    
    function isMobile() {
        return window.innerWidth <= CONFIG.MOBILE_BREAKPOINT;
    }
    
    function throttledResize() {
        clearTimeout(state.resizeTimeout);
        state.resizeTimeout = setTimeout(handleResize, CONFIG.RESIZE_THROTTLE);
    }
    
    function handleResize() {
        const wasMobile = state.isMobileOpen;
        
        if (isMobile()) {
            elements.sidebar.classList.remove(CSS_CLASSES.collapsed);
            if (wasMobile) {
                document.body.classList.add(CSS_CLASSES.sidebarOpen);
                hideMobileToggleDelayed();
            } else {
                showMobileToggle();
            }
        } else {
            closeMobile();
            showMobileToggle();
            if (state.isCollapsed) {
                elements.sidebar.classList.add(CSS_CLASSES.collapsed);
            }
        }
        
        updateMainContent();
        updateExpandButton();
    }
    
    function trackNavigation(page) {
        // Tracking/analytics simples
        console.log('Navegação para:', page);
        
        // Integração com Google Analytics (se disponível)
        if (typeof gtag !== 'undefined') {
            gtag('event', 'navigation', {
                'page': page,
                'source': 'klube-sidebar'
            });
        }
        
        // Integração com outras ferramentas de analytics
        if (typeof dataLayer !== 'undefined') {
            dataLayer.push({
                'event': 'sidebar_navigation',
                'page': page
            });
        }
    }
    
    // ====================================
    // API PÚBLICA
    // ====================================
    
    const publicAPI = {
        toggle: toggleDesktop,
        expand: () => {
            if (state.isCollapsed && !isMobile()) {
                toggleDesktop();
            }
        },
        collapse: () => {
            if (!state.isCollapsed && !isMobile()) {
                toggleDesktop();
            }
        },
        openMobile: openMobile,
        closeMobile: closeMobile,
        isCollapsed: () => state.isCollapsed,
        isMobileOpen: () => state.isMobileOpen,
        isMobile: isMobile
    };
    
    // ====================================
    // INICIALIZAÇÃO E EXPOSIÇÃO
    // ====================================
    
    // Inicializar quando DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Expor API pública
    window.klubeSidebar = publicAPI;
    
    // Compatibilidade com API anterior
    window.sidebarControls = {
        toggle: publicAPI.toggle,
        expand: publicAPI.expand,
        collapse: publicAPI.collapse,
        closeMobile: publicAPI.closeMobile,
        showToggle: showMobileToggle,
        hideToggle: () => elements.mobileToggle?.classList.add(CSS_CLASSES.hidden)
    };
    
})();
</script>