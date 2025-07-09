<?php
/**
 * Sidebar da Loja - Versão Corrigida
 * Sistema perfeito de posicionamento sem sobreposições
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

// Menu items - VENDAS E UPLOAD EM LOTE OCULTADOS
$menuItems = [
    [
        'id' => 'dashboard', 
        'title' => 'Dashboard', 
        'url' => STORE_DASHBOARD_URL,
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"/>'
    ],
    [
        'id' => 'register-transaction', 
        'title' => 'Nova Venda', 
        'url' => STORE_REGISTER_TRANSACTION_URL,
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>'
    ],
    /*
    // VENDAS - OCULTADO CONFORME SOLICITADO
    [
        'id' => 'transactions', 
        'title' => 'Vendas', 
        'url' => STORE_TRANSACTIONS_URL,
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>'
    ],
    */
    /*
    // UPLOAD EM LOTE - OCULTADO CONFORME SOLICITADO
    [
        'id' => 'batch-upload', 
        'title' => 'Upload em Lote', 
        'url' => STORE_BATCH_UPLOAD_URL,
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>'
    ],
    */
    [
        'id' => 'payment-history', 
        'title' => 'Pagamentos', 
        'url' => STORE_PAYMENT_HISTORY_URL, 
        'badge' => 3,
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>'
    ],
    [
        'id' => 'saldos', 
        'title' => 'Saldos', 
        'url' => STORE_SALDOS_URL,
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>'
    ],
    [
        'id' => 'profile', 
        'title' => 'Perfil', 
        'url' => STORE_PROFILE_URL,
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>'
    ]
];
?>

<!-- CSS da Sidebar Incorporado -->
<link rel="stylesheet" href="../../assets/css/sidebar-store-perfect.css">

<!-- Mobile Toggle -->
<button class="klube-mobile-toggle" id="klubeMobileToggle" aria-label="Abrir menu">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
    </svg>
</button>

<!-- Overlay -->
<div class="klube-overlay" id="klubeOverlay"></div>

<!-- Sidebar -->
<aside class="klube-sidebar" id="klubeSidebar">
    
    <!-- Header -->
    <header class="klube-sidebar-header">
        <div class="klube-logo-container">
            <img src="../../assets/images/icons/KlubeCashLOGO.png" alt="Klube Cash" class="klube-logo">
            <span class="klube-brand-text">Klube Cash</span>
        </div>
        
        <button class="klube-close-btn" id="klubeCloseBtn" aria-label="Fechar menu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </header>

    <!-- Perfil do Usuário -->
    <div class="klube-user-profile">
        <div class="klube-user-avatar">
            <?php echo $initials; ?>
        </div>
        <div class="klube-user-info">
            <span class="klube-user-name"><?php echo htmlspecialchars($userName); ?></span>
            <span class="klube-user-type">Loja Parceira</span>
        </div>
    </div>

    <!-- Navegação -->
    <nav class="klube-nav">
        <ul class="klube-nav-list">
            <?php foreach ($menuItems as $item): ?>
            <li class="klube-nav-item">
                <a href="<?php echo $item['url']; ?>" 
                   class="klube-nav-link <?php echo ($activeMenu === $item['id']) ? 'active' : ''; ?>">
                    <span class="klube-nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <?php echo $item['icon']; ?>
                        </svg>
                    </span>
                    <span class="klube-nav-text"><?php echo $item['title']; ?></span>
                    
                    <?php if (isset($item['badge']) && $item['badge'] > 0): ?>
                    <span class="klube-nav-badge"><?php echo $item['badge']; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <!-- Versão -->
    <div class="klube-sidebar-footer">
        <span class="klube-version">Klube Cash v<?php echo SYSTEM_VERSION; ?></span>
    </div>
</aside>

<!-- Script JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileToggle = document.getElementById('klubeMobileToggle');
    const sidebar = document.getElementById('klubeSidebar');
    const overlay = document.getElementById('klubeOverlay');
    const closeBtn = document.getElementById('klubeCloseBtn');

    // Função para abrir a sidebar
    function openSidebar() {
        sidebar.classList.add('klube-sidebar-open');
        overlay.classList.add('klube-overlay-active');
        document.body.style.overflow = 'hidden';
    }

    // Função para fechar a sidebar
    function closeSidebar() {
        sidebar.classList.remove('klube-sidebar-open');
        overlay.classList.remove('klube-overlay-active');
        document.body.style.overflow = '';
    }

    // Event listeners
    if (mobileToggle) {
        mobileToggle.addEventListener('click', openSidebar);
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeSidebar);
    }

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    // Fechar sidebar com ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('klube-sidebar-open')) {
            closeSidebar();
        }
    });

    // Fechar sidebar automaticamente em desktop quando a tela for redimensionada
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            closeSidebar();
        }
    });
});
</script>