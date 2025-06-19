<?php
/**
 * Componente de Sidebar para a área da Loja
 * 
 * Este componente cria uma sidebar responsiva para todas as páginas
 * do painel da loja parceira.
 * 
 * IMPORTANTE: Lojas não recebem cashback - elas pagam 10% de comissão
 * (5% para cliente + 5% para admin)
 * 
 * @param string $activeMenu - O ID do menu ativo atual
 */
require_once '../../config/constants.php';
// Iniciar sessão apenas se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . LOGIN_URL);
    exit;
}
// Verificar se o usuário é loja
if ($_SESSION['user_type'] !== 'loja') {
    header('Location: ' . CLIENT_DASHBOARD_URL);
    exit;
}
// Verificar se $activeMenu está definido, caso contrário definir como 'dashboard'
if (!isset($activeMenu)) {
    $activeMenu = 'dashboard';
}
?>
<link rel="stylesheet" href="../../assets/css/sidebar-styles.css">
<!-- Sidebar Toggle Button (visível apenas em dispositivos móveis) -->
<div class="sidebar-toggle" id="sidebarToggle">
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
    
    <ul class="sidebar-nav">
        <li>
            <a href="<?php echo STORE_DASHBOARD_URL; ?>" class="sidebar-nav-item <?php echo ($activeMenu == 'dashboard') ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                <span>Inicio</span>
            </a>
        </li>
        <li>
            <a href="<?php echo STORE_REGISTER_TRANSACTION_URL; ?>" class="sidebar-nav-item <?php echo ($activeMenu == 'register-transaction') ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Registrar Venda</span>
            </a>
        </li>
        <li>
            <a href="<?php echo SITE_URL; ?>/views/stores/employees.php" class="sidebar-nav-item <?php echo ($activeMenu == 'funcionarios') ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Funcionários</span>
            </a>
        </li>
        <li>
            <a href="<?php echo STORE_PENDING_TRANSACTIONS_URL; ?>" class="sidebar-nav-item <?php echo ($activeMenu == 'pending-commissions') ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span>Comissões Pendentes</span>
            </a>
        </li>
        <li>
            <a href="<?php echo STORE_PAYMENT_HISTORY_URL; ?>" class="sidebar-nav-item <?php echo ($activeMenu == 'payment-history') ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                <span>Histórico de Pagamentos</span>
            </a>
        </li>
        <li>
            <a href="<?php echo SITE_URL; ?>/views/stores/profile.php" class="sidebar-nav-item <?php echo ($activeMenu == 'profile') ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Meu Perfil</span>
            </a>
        </li>
    </ul>
    
    <!-- Botão de Logout -->
    <div class="sidebar-footer">
        <a href="<?php echo LOGOUT_URL; ?>" class="logout-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span>Sair</span>
        </a>
    </div>
</div>

<!-- JavaScript da Sidebar -->
<script src="../../assets/js/sidebar.js"></script>