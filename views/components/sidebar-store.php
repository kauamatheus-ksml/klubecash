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
require_once '../../controllers/AuthController.php';
require_once '../../utils/PermissionManager.php';
// Iniciar sessão apenas se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . LOGIN_URL);
    exit;
}
// Verificar se o usuário tem acesso à área da loja (lojista OU funcionário)
if (!AuthController::hasStoreAccess()) {
    header('Location: ' . CLIENT_DASHBOARD_URL);
    exit;
}

// Definir variáveis de controle para permissões
$isEmployee = AuthController::isEmployee();
$isStoreOwner = AuthController::isStoreOwner();
$userName = $_SESSION['user_name'];

// Função helper para verificar permissões
function canShowMenuItem($modulo, $acao) {
    global $isEmployee;
    if (!$isEmployee) {
        return true; // Lojista tem acesso total
    }
    return PermissionManager::checkAccess($modulo, $acao);
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
        
        <!-- NOVA SEÇÃO: Informações do usuário -->
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($userName) ?></div>
            <?php if ($isStoreOwner): ?>
                <span class="user-badge owner">Lojista</span>
            <?php elseif ($isEmployee): ?>
                <?php 
                $subtipo = $_SESSION['subtipo_funcionario'] ?? 'funcionario';
                $badgeClass = match($subtipo) {
                    'gerente' => 'manager',
                    'financeiro' => 'financial', 
                    'vendedor' => 'seller',
                    default => 'employee'
                };
                ?>
                <span class="user-badge <?= $badgeClass ?>"><?= ucfirst($subtipo) ?></span>
            <?php endif; ?>
        </div>
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
                <?php if ($isEmployee): ?>
                    <span class="permission-indicator">👑</span>
                <?php endif; ?>
            </a>
        </li>
        <!--
        <li>
            <a href="<?php echo STORE_TRANSACTIONS_URL; ?>" class="sidebar-nav-item <?php echo ($activeMenu == 'transactions') ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                    <line x1="8" y1="21" x2="16" y2="21"></line>
                    <line x1="12" y1="17" x2="12" y2="21"></line>
                </svg>
                Transações
            </a>
        </li>-->
        <!--<li>
            <a href="<?php echo STORE_BATCH_UPLOAD_URL; ?>" class="sidebar-nav-item <?php echo ($activeMenu == 'batch-upload') ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                Upload em Lote
            </a>
        </li>-->
        <li>
            <a href="<?php echo STORE_PENDING_TRANSACTIONS_URL; ?>" class="sidebar-nav-item <?php echo ($activeMenu == 'pending-commissions') ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                Comissões Pendentes
            </a>
        </li>
        <li>
            <a href="<?php echo STORE_PAYMENT_HISTORY_URL; ?>" class="sidebar-nav-item <?php echo ($activeMenu == 'payment-history') ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                Histórico de Pagamentos
            </a>
        </li>
        <!--
        <li>
            <a href="<?php echo STORE_PAYMENT_URL; ?>" class="sidebar-nav-item <?php echo ($activeMenu == 'payment') ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                Realizar Pagamento
            </a>
        </li>
        -->
        <li>
            <a href="<?php echo SITE_URL; ?>/store/perfil" class="sidebar-nav-item <?php echo ($activeMenu == 'profile') ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                Meu Perfil
            </a>
        </li>
    </ul>
    <!-- NOVO: Indicador de acesso limitado -->
    <?php if ($isEmployee): ?>
    <div style="padding: 10px; background: rgba(255,193,7,0.2); margin: 10px; border-radius: 8px; text-align: center;">
        <div style="color: #ffc107; font-size: 12px; font-weight: 600;">
            🔐 ACESSO LIMITADO
        </div>
        <div style="color: rgba(255,255,255,0.8); font-size: 10px; margin-top: 2px;">
            Permissões controladas pelo lojista
        </div>
    </div>
    <?php endif; ?>

    <div class="sidebar-footer">
        <!-- resto do conteúdo permanece igual -->
    <div class="sidebar-footer">
        <a href="<?php echo SITE_URL; ?>/controllers/AuthController.php?action=logout" class="logout-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            Sair
        </a>
    </div>
</div>

<!-- Script para responsividade da Sidebar -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elementos da DOM
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const mainContent = document.getElementById('mainContent');
    
    // Evento para mostrar/ocultar a sidebar em dispositivos móveis
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    
    if (overlay) {
        overlay.addEventListener('click', toggleSidebar);
    }
    
    /**
     * Alterna a visibilidade da sidebar em dispositivos móveis
     */
    function toggleSidebar() {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
    }
    
    /**
     * Verifica o tamanho da tela e ajusta a sidebar conforme necessário
     */
    function checkScreenSize() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        }
    }
    
    // Verificar o tamanho da tela ao carregar e redimensionar
    window.addEventListener('resize', checkScreenSize);
    
    // Inicializar
    checkScreenSize();
});
</script>

<!-- NOVOS ESTILOS PARA PERMISSÕES -->
<style>
.user-info {
    text-align: center;
    padding: 10px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    margin-bottom: 10px;
}

.user-name {
    color: #fff;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 5px;
}

.user-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.user-badge.owner {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.user-badge.manager {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.user-badge.financial {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
}

.user-badge.seller {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
}

.permission-indicator {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 12px;
}

.sidebar-nav-item {
    position: relative;
}
</style>