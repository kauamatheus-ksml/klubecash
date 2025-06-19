<?php
/**
 * Componente de Sidebar para a área da Loja com Sistema de Funcionários
 * 
 * Estrutura: views/stores/ (confirmada)
 * Funcionalidade: Controle de visibilidade baseado no tipo de usuário
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

// Verificar se o usuário tem acesso à área da loja (loja OU funcionário)
if (!in_array($_SESSION['user_type'], ['loja', 'funcionario'])) {
    header('Location: ' . CLIENT_DASHBOARD_URL);
    exit;
}

// Verificar se $activeMenu está definido, caso contrário definir como 'dashboard'
if (!isset($activeMenu)) {
    $activeMenu = 'dashboard';
}

// Variáveis de controle simples e eficazes
$isLoja = ($_SESSION['user_type'] === 'loja');
$isFuncionario = ($_SESSION['user_type'] === 'funcionario');
$userName = $_SESSION['user_name'] ?? 'Usuário';

// Para funcionários, obter subtipo
$subtipoFuncionario = '';
if ($isFuncionario) {
    $subtipoFuncionario = $_SESSION['subtipo_funcionario'] ?? 'funcionario';
}
?>

<link rel="stylesheet" href="../../assets/css/sidebar-styles.css">

<!-- Estilos para identificação de usuários -->
<style>
.user-info {
    text-align: center;
    padding: 12px 10px;
    border-bottom: 1px solid rgba(255,255,255,0.15);
    margin-bottom: 15px;
    background: rgba(0,0,0,0.2);
}

.user-name {
    color: #fff;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 6px;
}

.user-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.user-badge.loja { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.user-badge.gerente { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
.user-badge.financeiro { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }
.user-badge.vendedor { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; }

.funcionario-warning {
    background: rgba(255,193,7,0.2);
    margin: 10px;
    padding: 8px 10px;
    border-radius: 8px;
    text-align: center;
    border-left: 3px solid #ffc107;
}
</style>

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
        
        <!-- Informações do usuário -->
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($userName) ?></div>
            <?php if ($isLoja): ?>
                <span class="user-badge loja">Lojista</span>
            <?php elseif ($isFuncionario): ?>
                <span class="user-badge <?= $subtipoFuncionario ?>"><?= ucfirst($subtipoFuncionario) ?></span>
            <?php endif; ?>
        </div>
    </div>
    
    <ul class="sidebar-nav">
        <!-- Dashboard - Sempre visível -->
        <li>
            <a href="<?php echo STORE_DASHBOARD_URL; ?>" class="sidebar-nav-item <?php echo ($activeMenu == 'dashboard') ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                <span>Início</span> 
            </a>
        </li>

        <!-- Registrar Venda - Ocultar para funcionário financeiro -->
        <?php if ($isLoja || $subtipoFuncionario !== 'financeiro'): ?>
        <li>
            <a href="<?php echo STORE_REGISTER_TRANSACTION_URL; ?>" class="sidebar-nav-item <?php echo ($activeMenu == 'register-transaction') ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Registrar Venda</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- Funcionários - Apenas para lojistas e gerentes -->
        <?php if ($isLoja || $subtipoFuncionario === 'gerente'): ?>
        <li>
            <a href="<?php echo SITE_URL; ?>/views/stores/employees.php" class="sidebar-nav-item <?php echo ($activeMenu == 'funcionarios') ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Funcionários</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- Comissões Pendentes - Visível para todos, exceto vendedores -->
        <?php if ($isLoja || $subtipoFuncionario !== 'vendedor'): ?>
        <li>
            <a href="<?php echo STORE_PENDING_TRANSACTIONS_URL; ?>" class="sidebar-nav-item <?php echo ($activeMenu == 'pending-commissions') ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span>Comissões Pendentes</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- Histórico de Pagamentos - Apenas lojistas e funcionários financeiros -->
        <?php if ($isLoja || $subtipoFuncionario === 'financeiro'): ?>
        <li>
            <a href="<?php echo STORE_PAYMENT_HISTORY_URL; ?>" class="sidebar-nav-item <?php echo ($activeMenu == 'payment-history') ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                <span>Histórico de Pagamentos</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- Meu Perfil - Sempre visível -->
        <li>
            <a href="<?php echo SITE_URL; ?>/store/perfil" class="sidebar-nav-item <?php echo ($activeMenu == 'profile') ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Meu Perfil</span>
            </a>
        </li>
    </ul>
    
    <!-- Aviso para funcionários -->
    <?php if ($isFuncionario): ?>
    <div class="funcionario-warning">
        <div style="color: #ffc107; font-size: 11px; font-weight: 600; margin-bottom: 2px;">
            🔒 ACESSO LIMITADO
        </div>
        <div style="color: rgba(255,255,255,0.8); font-size: 9px;">
            Algumas funções podem estar restritas
        </div>
    </div>
    <?php endif; ?>
    
    <div class="sidebar-footer">
        <a href="<?php echo SITE_URL; ?>/controllers/AuthController.php?action=logout" class="logout-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1-2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            Sair
        </a>
    </div>
</div>

<!-- Script permanece o mesmo -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    
    if (overlay) {
        overlay.addEventListener('click', toggleSidebar);
    }
    
    function toggleSidebar() {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
    }
    
    function checkScreenSize() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        }
    }
    
    window.addEventListener('resize', checkScreenSize);
    checkScreenSize();
});
</script>