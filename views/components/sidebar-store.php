<?php
/**
 * Componente de Sidebar para a área da Loja com Sistema de Permissões
 * 
 * Este componente cria uma sidebar responsiva para todas as páginas
 * do painel da loja parceira, incluindo controle granular de permissões
 * para funcionários.
 * 
 * IMPORTANTE: Lojas não recebem cashback - elas pagam 10% de comissão
 * (5% para cliente + 5% para admin)
 * 
 * NOVO: Funcionários podem acessar com permissões personalizáveis
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

// Verificar se $activeMenu está definido, caso contrário definir como 'dashboard'
if (!isset($activeMenu)) {
    $activeMenu = 'dashboard';
}

// Definir variáveis de controle
$isEmployee = AuthController::isEmployee();
$isStoreOwner = AuthController::isStoreOwner();
$userName = $_SESSION['user_name'];
$userType = $_SESSION['user_type'];

// Função helper para verificar se deve mostrar item do menu
function canShowMenuItem($modulo, $acao) {
    global $isEmployee;
    
    if (!$isEmployee) {
        return true; // Lojista tem acesso total
    }
    
    return PermissionManager::checkAccess($modulo, $acao);
}

// Função para obter badge do usuário
function getUserBadge() {
    global $isEmployee, $isStoreOwner;
    
    if ($isStoreOwner) {
        return '<span class="user-badge owner">Lojista</span>';
    } elseif ($isEmployee) {
        $subtipo = $_SESSION['subtipo_funcionario'] ?? 'funcionario';
        $badgeClass = match($subtipo) {
            'gerente' => 'manager',
            'financeiro' => 'financial', 
            'vendedor' => 'seller',
            default => 'employee'
        };
        return '<span class="user-badge ' . $badgeClass . '">' . ucfirst($subtipo) . '</span>';
    }
    
    return '';
}
?>

<link rel="stylesheet" href="../../assets/css/sidebar-styles.css">

<style>
/* Estilos adicionais para o sistema de permissões */
.sidebar-header {
    position: relative;
    padding-bottom: 15px;
}

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

.user-badge.employee {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    color: white;
}

.restricted-item {
    opacity: 0.6;
    position: relative;
}

.restricted-item::after {
    content: '🔒';
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 12px;
}

.sidebar-nav-item.disabled {
    pointer-events: none;
    opacity: 0.5;
}

.permission-indicator {
    font-size: 10px;
    opacity: 0.7;
    margin-left: 5px;
}

.submenu {
    background: rgba(0,0,0,0.2);
    margin: 5px 0;
    border-radius: 8px;
    overflow: hidden;
}

.submenu-item {
    padding: 8px 20px 8px 45px;
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    display: block;
    font-size: 13px;
    transition: all 0.3s ease;
}

.submenu-item:hover {
    background: rgba(255,255,255,0.1);
    color: white;
    text-decoration: none;
}

.submenu-item.active {
    background: rgba(255,255,255,0.2);
    color: white;
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
            <?= getUserBadge() ?>
        </div>
    </div>
    
    <ul class="sidebar-nav">
        <!-- Dashboard - Sempre visível se tem permissão -->
        <?php if (canShowMenuItem(MODULO_DASHBOARD, ACAO_VER)): ?>
            <li>
                <a href="<?php echo STORE_DASHBOARD_URL; ?>" class="sidebar-nav-item <?php echo ($activeMenu == 'dashboard') ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    <span>Início</span> 
                </a>
            </li>
        <?php endif; ?>

        <!-- Registro de Vendas - Verificar permissão -->
        <?php if (canShowMenuItem(MODULO_TRANSACOES, ACAO_CRIAR)): ?>
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

        <!-- Transações - Menu expandível se tem pelo menos visualização -->
        <?php if (canShowMenuItem(MODULO_TRANSACOES, ACAO_VER)): ?>
            <li>
                <a href="#" class="sidebar-nav-item <?php echo (in_array($activeMenu, ['transactions', 'batch-upload']) ? 'active' : ''); ?>" onclick="toggleSubmenu('transacoes')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                    <span>Transações</span>
                    <svg class="submenu-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </a>
                <div class="submenu" id="submenu-transacoes" style="display: none;">
                    <a href="<?php echo STORE_TRANSACTIONS_URL; ?>" class="submenu-item <?php echo ($activeMenu == 'transactions') ? 'active' : ''; ?>">
                        📋 Ver Todas
                    </a>
                    <?php if (canShowMenuItem(MODULO_TRANSACOES, ACAO_UPLOAD_LOTE)): ?>
                        <a href="<?php echo STORE_BATCH_UPLOAD_URL; ?>" class="submenu-item <?php echo ($activeMenu == 'batch-upload') ? 'active' : ''; ?>">
                            📤 Upload em Lote
                        </a>
                    <?php endif; ?>
                </div>
            </li>
        <?php endif; ?>

        <!-- Funcionários - Apenas para lojistas ou gerentes -->
        <?php if (canShowMenuItem(MODULO_FUNCIONARIOS, ACAO_VER)): ?>
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
        <?php endif; ?>

        <!-- Comissões - Menu expandível -->
        <?php if (canShowMenuItem(MODULO_COMISSOES, ACAO_VER)): ?>
            <li>
                <a href="#" class="sidebar-nav-item <?php echo (in_array($activeMenu, ['pending-commissions', 'payment-history']) ? 'active' : ''); ?>" onclick="toggleSubmenu('comissoes')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span>Comissões</span>
                    <svg class="submenu-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </a>
                <div class="submenu" id="submenu-comissoes" style="display: none;">
                    <a href="<?php echo STORE_PENDING_TRANSACTIONS_URL; ?>" class="submenu-item <?php echo ($activeMenu == 'pending-commissions') ? 'active' : ''; ?>">
                        ⏰ Pendentes
                    </a>
                    <?php if (canShowMenuItem(MODULO_COMISSOES, ACAO_PAGAR)): ?>
                        <a href="<?php echo STORE_PAYMENT_HISTORY_URL; ?>" class="submenu-item <?php echo ($activeMenu == 'payment-history') ? 'active' : ''; ?>">
                            💳 Histórico de Pagamentos
                        </a>
                    <?php else: ?>
                        <a href="#" class="submenu-item disabled" title="Sem permissão para acessar">
                            💳 Histórico de Pagamentos 🔒
                        </a>
                    <?php endif; ?>
                </div>
            </li>
        <?php endif; ?>

        <!-- Relatórios - Se tem permissão -->
        <?php if (canShowMenuItem(MODULO_RELATORIOS, ACAO_VER)): ?>
            <li>
                <a href="<?php echo SITE_URL; ?>/views/stores/reports.php" class="sidebar-nav-item <?php echo ($activeMenu == 'reports') ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    <span>Relatórios</span>
                </a>
            </li>
        <?php endif; ?>

        <!-- Perfil da Loja/Configurações -->
        <?php if (canShowMenuItem(MODULO_CONFIGURACOES, ACAO_VER)): ?>
            <li>
                <a href="<?php echo SITE_URL; ?>/store/perfil" class="sidebar-nav-item <?php echo ($activeMenu == 'profile') ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>Configurações</span>
                    <?php if ($isEmployee && !canShowMenuItem(MODULO_CONFIGURACOES, ACAO_EDITAR)): ?>
                        <span class="permission-indicator">👁️</span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endif; ?>

        <!-- Separador visual -->
        <li style="margin: 15px 0; border-top: 1px solid rgba(255,255,255,0.1);"></li>

        <!-- Meu Perfil - Sempre acessível -->
        <li>
            <a href="<?php echo SITE_URL; ?>/views/stores/meu-perfil.php" class="sidebar-nav-item <?php echo ($activeMenu == 'meu-perfil') ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Meu Perfil</span>
            </a>
        </li>

        <!-- Ajuda/Suporte -->
        <li>
            <a href="<?php echo SITE_URL; ?>/views/stores/help.php" class="sidebar-nav-item <?php echo ($activeMenu == 'help') ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                <span>Ajuda</span>
            </a>
        </li>
    </ul>
    
    <div class="sidebar-footer">
        <!-- Indicador de acesso limitado para funcionários -->
        <?php if ($isEmployee): ?>
            <div style="padding: 10px; background: rgba(255,193,7,0.2); margin-bottom: 10px; border-radius: 8px; text-align: center;">
                <div style="color: #ffc107; font-size: 12px; font-weight: 600;">
                    🔐 ACESSO LIMITADO
                </div>
                <div style="color: rgba(255,255,255,0.8); font-size: 10px; margin-top: 2px;">
                    Permissões controladas pelo lojista
                </div>
            </div>
        <?php endif; ?>

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
    
    /**
     * Alterna a visibilidade de submenus
     */
    window.toggleSubmenu = function(menuId) {
        const submenu = document.getElementById('submenu-' + menuId);
        const arrow = event.currentTarget.querySelector('.submenu-arrow');
        
        if (submenu.style.display === 'none' || submenu.style.display === '') {
            submenu.style.display = 'block';
            arrow.style.transform = 'rotate(180deg)';
        } else {
            submenu.style.display = 'none';
            arrow.style.transform = 'rotate(0deg)';
        }
        
        // Prevenir navegação do link
        event.preventDefault();
        return false;
    }
    
    // Verificar o tamanho da tela ao carregar e redimensionar
    window.addEventListener('resize', checkScreenSize);
    
    // Inicializar
    checkScreenSize();
    
    // Auto-expandir submenu se uma página filha estiver ativa
    const activeSubmenus = document.querySelectorAll('.submenu-item.active');
    activeSubmenus.forEach(item => {
        const submenu = item.closest('.submenu');
        if (submenu) {
            submenu.style.display = 'block';
            const arrow = submenu.previousElementSibling.querySelector('.submenu-arrow');
            if (arrow) {
                arrow.style.transform = 'rotate(180deg)';
            }
        }
    });
});

// Função para mostrar tooltip de permissões (opcional)
function showPermissionTooltip(element, message) {
    // Implementar tooltip se necessário
    console.log('Permissão:', message);
}
</script>

<style>
/* Estilos adicionais para submenus */
.submenu-arrow {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    transition: transform 0.3s ease;
}

.sidebar-nav-item {
    position: relative;
}

.sidebar-nav-item:hover .submenu-arrow {
    color: #fff;
}

/* Animações suaves para submenus */
.submenu {
    transition: all 0.3s ease;
    max-height: 0;
    overflow: hidden;
}

.submenu[style*="block"] {
    max-height: 200px;
}

/* Indicadores visuais aprimorados */
.permission-indicator {
    position: absolute;
    right: 25px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 10px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Responsividade aprimorada */
@media (max-width: 768px) {
    .user-info {
        padding: 8px;
    }
    
    .user-name {
        font-size: 13px;
    }
    
    .user-badge {
        font-size: 10px;
        padding: 1px 6px;
    }
    
    .submenu-item {
        padding: 6px 15px 6px 35px;
        font-size: 12px;
    }
}
</style>