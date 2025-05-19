<?php
/**
 * Componente de Navbar para o Sistema Klube Cash
 * 
 * Este componente cria uma barra de navegação responsiva para todas as páginas do sistema.
 */
// Iniciar sessão se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar login e tipo de usuário
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['user_name'] ?? 'Usuário' : '';
$userType = $isLoggedIn ? $_SESSION['user_type'] ?? '' : '';

// Identificar tipo de usuário
$isAdmin = $userType === 'admin';
$isClient = $userType === 'cliente';
$isStore = $userType === 'loja';
?>

<style>
    .navbar {
        background-color: #FFFFFF;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 100;
        
    }
    
    .navbar-brand {
        display: flex;
        align-items: center;
        text-decoration: none;
    }
    
    .navbar-logo {
        height: 40px;
        margin-right: 10px;
    }
    
    .navbar-title {
        font-size: 20px;
        font-weight: 600;
        color: #FF7A00;
        margin: 0;
    }
    
    .navbar-menu {
        display: flex;
        align-items: center;
    }
    
    .navbar-links {
        display: flex;
        list-style: none;
        margin: 0;
        padding: 0;
    }
    
    .navbar-item {
        margin: 0 12px;
    }
    
    .navbar-link {
        text-decoration: none;
        color: #333333;
        font-weight: 500;
        font-size: 14px;
        transition: color 0.3s;
        display: flex;
        align-items: center;
    }
    
    .navbar-link:hover {
        color: #FF7A00;
    }
    
    .navbar-link svg {
        margin-right: 5px;
    }
    
    .navbar-user {
        display: flex;
        align-items: center;
        margin-left: 15px;
        position: relative;
    }
    
    .user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background-color: #FFD9B3;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #FF7A00;
        font-weight: 600;
        font-size: 16px;
        margin-right: 8px;
    }
    
    .user-name {
        font-size: 14px;
        font-weight: 600;
        color: #333333;
        cursor: pointer;
        display: flex;
        align-items: center;
    }
    
    .user-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        background-color: #FFFFFF;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        padding: 10px 0;
        min-width: 180px;
        display: none;
        z-index: 101;
    }
    
    .user-dropdown.open {
        display: block;
    }
    
    .dropdown-item {
        padding: 8px 15px;
        display: flex;
        align-items: center;
        color: #333333;
        text-decoration: none;
        font-size: 14px;
        transition: background-color 0.3s;
    }
    
    .dropdown-item:hover {
        background-color: #FFF0E6;
    }
    
    .dropdown-item svg {
        margin-right: 10px;
        color: #666666;
    }
    
    .navbar-toggle {
        display: none;
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
    }
    
    /* Responsividade */
    @media (max-width: 768px) {
        .navbar-links {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background-color: #FFFFFF;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            flex-direction: column;
            padding: 10px 0;
        }
        
        .navbar-links.open {
            display: flex;
        }
        
        .navbar-item {
            margin: 5px 0;
            width: 100%;
        }
        
        .navbar-link {
            padding: 10px 20px;
            width: 100%;
        }
        
        .navbar-toggle {
            display: block;
            margin-right: 15px;
        }
        
        .user-name span {
            display: none;
        }
    }
</style>

<!-- Navbar -->
<nav class="navbar">
    <!-- Botão Toggle para mobile -->
    <button class="navbar-toggle" id="navbarToggle">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>
    
    <!-- Logo e Título -->
    <a href="<?php echo $isLoggedIn ? ($isAdmin ? ADMIN_DASHBOARD_URL : CLIENT_DASHBOARD_URL) : SITE_URL; ?>" class="navbar-brand">
        <img src="../../assets/images/logolaranja.png" alt="Klube Cash" class="navbar-logo">
        
    </a>
    
    <!-- Menu de Navegação -->
    <div class="navbar-menu">
        <ul class="navbar-links" id="navbarLinks">
            <?php if (!$isLoggedIn): ?>
                <!-- Menu para visitantes -->
                <li class="navbar-item">
                    <a href="<?php echo SITE_URL; ?>" class="navbar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        Início
                    </a>
                </li>
                <li class="navbar-item">
                    <a href="<?php echo STORE_REGISTER_URL; ?>" class="navbar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 3h18v18H3zM21 9H3M21 15H3M12 3v18"></path>
                        </svg>
                        Seja um Parceiro
                    </a>
                </li>
                <li class="navbar-item">
                    <a href="<?php echo LOGIN_URL; ?>" class="navbar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                            <polyline points="10 17 15 12 10 7"></polyline>
                            <line x1="15" y1="12" x2="3" y2="12"></line>
                        </svg>
                        Entrar
                    </a>
                </li>
                <li class="navbar-item">
                    <a href="<?php echo REGISTER_URL; ?>" class="navbar-link"></a>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <line x1="20" y1="8" x2="20" y2="14"></line>
                            <line x1="23" y1="11" x2="17" y2="11"></line>
                        </svg>
                        Cadastrar
                    </a>
                </li>
            <?php elseif ($isClient): ?>
                <!-- Menu para clientes -->
                <li class="navbar-item">
                <a href="<?php echo CLIENT_DASHBOARD_URL; ?>" class="navbar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        Painel
                    </a>
                </li>
                <li class="navbar-item">
                    <a href="<?php echo CLIENT_BALANCE_URL; ?>" class="navbar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M12 6v6l4 2"></path>
                        </svg>
                        Saldo
                    </a>
                </li>
                <li class="navbar-item">
                <a href="<?php echo CLIENT_STATEMENT_URL; ?>" class="navbar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        Extrato
                    </a>
                </li>
                <li class="navbar-item">x
                    <a href="<?php echo CLIENT_STORES_URL; ?>" class="navbar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 3h18v18H3zM21 9H3M21 15H3M12 3v18"></path>
                        </svg>
                        Lojas Parceiras
                    </a>
                </li>
            <?php elseif ($isAdmin): ?>
                <!-- Menu reduzido para admin (já tem sidebar) -->
                <li class="navbar-item">
                    <a href="<?php echo ADMIN_DASHBOARD_URL; ?>" class="navbar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        Dashboard
                    </a>
                </li>
            <?php endif; ?>
        </ul>
        
        <?php if ($isLoggedIn): ?>
            <!-- Menu do Usuário -->
            <div class="navbar-user">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($userName, 0, 1)); ?>
                </div>
                <div class="user-name" id="userDropdownToggle">
                    <span><?php echo htmlspecialchars($userName); ?></span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </div>
                
                <!-- Dropdown do Usuário -->
                <div class="user-dropdown" id="userDropdown">
                    <?php if ($isClient): ?>
                        <a href="<?php echo CLIENT_PROFILE_URL; ?>" class="dropdown-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            Meu Perfil
                        </a>
                    <?php elseif ($isAdmin): ?>
                        <a href="<?php echo ADMIN_SETTINGS_URL; ?>" class="dropdown-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                            </svg>
                            Configurações
                        </a>
                    <?php endif; ?>
                    
                    <a href="../../controllers/AuthController.php?action=logout" class="dropdown-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        Sair
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle do menu mobile
    const navbarToggle = document.getElementById('navbarToggle');
    const navbarLinks = document.getElementById('navbarLinks');
    
    if (navbarToggle) {
        navbarToggle.addEventListener('click', function() {
            navbarLinks.classList.toggle('open');
        });
    }
    
    // Toggle do dropdown do usuário
    const userDropdownToggle = document.getElementById('userDropdownToggle');
    const userDropdown = document.getElementById('userDropdown');
    
    if (userDropdownToggle) {
        userDropdownToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('open');
        });
    }
    
    // Fechar dropdown ao clicar fora
    document.addEventListener('click', function(e) {
        if (userDropdown && userDropdownToggle) {
            if (!userDropdown.contains(e.target) && !userDropdownToggle.contains(e.target)) {
                userDropdown.classList.remove('open');
            }
        }
    });
});
</script>