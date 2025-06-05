<?php
/**
 * Componente Navbar Unificado - Klube Cash
 * Sistema híbrido que funciona como navbar horizontal no desktop 
 * e navbar compacta no mobile
 */

// Verificar se o usuário está logado
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . LOGIN_URL);
    exit;
}

// Obter informações do usuário
$userName = $_SESSION['user_name'] ?? 'Usuário';
$userType = $_SESSION['user_type'] ?? 'cliente';
$userEmail = $_SESSION['user_email'] ?? '';

// Função para gerar iniciais do nome
function getInitials($name) {
    $words = explode(' ', trim($name));
    $initials = '';
    foreach (array_slice($words, 0, 2) as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }
    return $initials;
}

// Determinar contexto baseado no tipo de usuário
$contextTitle = '';
switch($userType) {
    case 'admin':
        $contextTitle = 'Painel Administrativo';
        break;
    case 'loja':
        $contextTitle = 'Painel da Loja';
        break;
    default:
        $contextTitle = 'Painel do Cliente';
}
?>

<nav class="main-navbar" id="mainNavbar">
    <!-- Logo e Brand -->
    <div class="navbar-brand">
        <img src="../../assets/images/logo.png" alt="Klube Cash" class="navbar-logo">
    </div>
    
    <!-- Informações centrais (apenas desktop) -->
    <div class="navbar-center">
        <div class="navbar-user-info">
            <div class="user-avatar">
                <?php echo getInitials($userName); ?>
            </div>
            <div class="user-details">
                <h4><?php echo htmlspecialchars($userName); ?></h4>
                <p><?php echo htmlspecialchars($contextTitle); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Ações da navbar -->
    <div class="navbar-actions">
        <!-- Notificações (apenas desktop) -->
        <button class="navbar-notification" title="Notificações">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
            <!-- Badge de notificação (opcional) -->
            <!-- <span class="notification-badge"></span> -->
        </button>
        
        <!-- Toggle do menu mobile -->
        <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Abrir menu">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
        
        <!-- Botão Sair SEMPRE VISÍVEL -->
        <a href="<?php echo SITE_URL; ?>/controllers/AuthController.php?action=logout" 
           class="navbar-logout" 
           onclick="return confirm('Tem certeza que deseja sair?')"
           title="Sair do sistema">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span>Sair</span>
        </a>
    </div>
</nav>

<!-- Overlay para mobile -->
<div class="mobile-overlay" id="mobileOverlay"></div>