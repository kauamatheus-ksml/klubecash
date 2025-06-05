<?php
/**
 * Componente Sidebar Unificado - Klube Cash
 * Funciona como sidebar vertical no desktop e sidebar deslizante no mobile
 */

// Verificar tipo de usuário e definir navegação
$userType = $_SESSION['user_type'] ?? 'cliente';

// Definir menu ativo se não estiver definido
$activeMenu = $activeMenu ?? 'dashboard';

// Configurar navegação baseada no tipo de usuário
$navigationItems = [];

if ($userType === 'admin') {
    $navigationItems = [
        'painel' => [
            'url' => ADMIN_DASHBOARD_URL,
            'label' => 'Painel',
            'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline>'
        ],
        'usuarios' => [
            'url' => ADMIN_USERS_URL,
            'label' => 'Usuários',
            'icon' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>'
        ],
        'saldo' => [
            'url' => ADMIN_BALANCE_URL,
            'label' => 'Saldo',
            'icon' => '<line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>'
        ],
        'lojas' => [
            'url' => ADMIN_STORES_URL,
            'label' => 'Lojas',
            'icon' => '<path d="M3 3h18l-2 13H5L3 3z"></path><path d="M16 16a4 4 0 0 1-8 0"></path>'
        ],
        'pagamentos' => [
            'url' => ADMIN_PAYMENTS_URL,
            'label' => 'Pagamentos',
            'icon' => '<rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line>'
        ],
        'compras' => [
            'url' => ADMIN_TRANSACTIONS_URL,
            'label' => 'Compras',
            'icon' => '<circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>'
        ],
        'relatorios' => [
            'url' => SITE_URL . '/admin/relatorios',
            'label' => 'Relatórios',
            'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline>'
        ],
        'configuracoes' => [
            'url' => ADMIN_SETTINGS_URL,
            'label' => 'Configurações',
            'icon' => '<circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>'
        ]
    ];
} elseif ($userType === 'loja') {
    $navigationItems = [
        'dashboard' => [
            'url' => STORE_DASHBOARD_URL,
            'label' => 'Dashboard',
            'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline>'
        ],
        'register-transaction' => [
            'url' => STORE_REGISTER_TRANSACTION_URL,
            'label' => 'Registrar Venda',
            'icon' => '<line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line>'
        ],
        'transactions' => [
            'url' => STORE_TRANSACTIONS_URL,
            'label' => 'Transações',
            'icon' => '<rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line>'
        ],
        'pending-commissions' => [
            'url' => STORE_PENDING_TRANSACTIONS_URL,
            'label' => 'Comissões Pendentes',
            'icon' => '<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>'
        ],
        'payment-history' => [
            'url' => STORE_PAYMENT_HISTORY_URL,
            'label' => 'Histórico Pagamentos',
            'icon' => '<rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line>'
        ],
        'payment' => [
            'url' => STORE_PAYMENT_URL,
            'label' => 'Realizar Pagamento',
            'icon' => '<line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>'
        ],
        'profile' => [
            'url' => SITE_URL . '/store/perfil',
            'label' => 'Meu Perfil',
            'icon' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>'
        ]
    ];
}
?>

<aside class="main-sidebar" id="mainSidebar">
    <!-- Header da sidebar mobile -->
    <div class="sidebar-mobile-header">
        <img src="../../assets/images/logo.png" alt="Klube Cash" class="navbar-logo">
        <button class="sidebar-mobile-close" id="sidebarMobileClose" aria-label="Fechar menu">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>
    
    <!-- Navegação principal -->
    <nav class="sidebar-nav" role="navigation">
        <?php foreach ($navigationItems as $key => $item): ?>
            <a href="<?php echo $item['url']; ?>" 
               class="sidebar-nav-item <?php echo ($activeMenu == $key) ? 'active' : ''; ?>"
               aria-current="<?php echo ($activeMenu == $key) ? 'page' : 'false'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <?php echo $item['icon']; ?>
                </svg>
                <?php echo $item['label']; ?>
                
                <?php if (isset($item['badge'])): ?>
                    <span class="notification-badge"><?php echo $item['badge']; ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
    
    <!-- Footer da sidebar mobile -->
    <div class="sidebar-mobile-footer">
        <a href="<?php echo SITE_URL; ?>/controllers/AuthController.php?action=logout" 
           class="navbar-logout" 
           onclick="return confirm('Tem certeza que deseja sair?')"
           style="width: 100%; justify-content: center;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            Sair
        </a>
    </div>
</aside>