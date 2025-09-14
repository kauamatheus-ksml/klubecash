<?php
/**
 * Sidebar Responsiva para Lojistas - Klube Cash
 * Sidebar colapsável e adaptável para todos os dispositivos
 */

// Verificações de segurança
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado e é lojista
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'loja') {
    return;
}

// Dados do usuário
$nomeUsuario = $_SESSION['user_name'] ?? 'Lojista';
$emailUsuario = $_SESSION['user_email'] ?? '';

// Gerar iniciais do usuário
$iniciais = '';
$partesNome = explode(' ', $nomeUsuario);
if (count($partesNome) >= 2) {
    $iniciais = strtoupper(substr($partesNome[0], 0, 1) . substr($partesNome[1], 0, 1));
} else {
    $iniciais = strtoupper(substr($nomeUsuario, 0, 2));
}

// Menu ativo (definido na página que incluir este componente)
$menuAtivo = $menuAtivo ?? 'dashboard';

// Definição dos itens do menu
$itensMenu = [
    [
        'id' => 'dashboard',
        'titulo' => 'Dashboard',
        'url' => STORE_DASHBOARD_URL,
        'icone' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"/>'
    ],
    [
        'id' => 'nova-venda',
        'titulo' => 'Nova Venda',
        'url' => STORE_REGISTER_TRANSACTION_URL,
        'icone' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>'
    ],
    [
        'id' => 'funcionarios',
        'titulo' => 'Funcionários',
        'url' => STORE_EMPLOYEES_URL,
        'icone' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>'
    ],
    [
        'id' => 'pagamentos',
        'titulo' => 'Pagamentos',
        'url' => STORE_PAYMENT_HISTORY_URL,
        'icone' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>'
    ],
    [
        'id' => 'pendentes-pagamento',
        'titulo' => 'Pendentes de Pagamento',
        'url' => STORE_PENDING_TRANSACTIONS_URL,
        'icone' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>'
    ],
    [
        'id' => 'perfil',
        'titulo' => 'Perfil',
        'url' => STORE_PROFILE_URL,
        'icone' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>'
    ]
];
?>

<!-- Sidebar Lojista -->
<div class="sidebar-lojista-container" id="sidebarLojistaContainer">
    <!-- Overlay para mobile -->
    <div class="sidebar-lojista-overlay" id="sidebarLojistaOverlay"></div>
    
    <!-- Sidebar Principal -->
    <nav class="sidebar-lojista" id="sidebarLojista">
        <!-- Cabeçalho da Sidebar -->
        <div class="sidebar-lojista-cabecalho">
            <!-- Logo e Toggle -->
            <div class="sidebar-lojista-marca">
                <div class="sidebar-lojista-logo">
                    <img src="<?php echo IMG_URL; ?>/logo-klube-cash.png" alt="Klube Cash" class="logo-lojista-imagem">
                    <span class="logo-lojista-texto">Klube Cash</span>
                </div>
                <button type="button" class="sidebar-lojista-toggle" id="sidebarLojistaToggle" aria-label="Alternar sidebar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="9" y1="12" x2="21" y2="12"></line>
                        <line x1="9" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
            </div>
            
            <!-- Informações do Usuário -->
            <div class="sidebar-lojista-usuario">
                <div class="usuario-lojista-avatar">
                    <?php echo htmlspecialchars($iniciais); ?>
                </div>
                <div class="usuario-lojista-info">
                    <div class="usuario-lojista-nome"><?php echo htmlspecialchars($nomeUsuario); ?></div>
                    <div class="usuario-lojista-tipo">Lojista</div>
                </div>
            </div>
        </div>
        
        <!-- Navegação -->
        <div class="sidebar-lojista-navegacao">
            <ul class="navegacao-lojista-lista">
                <?php foreach ($itensMenu as $item): ?>
                    <li class="navegacao-lojista-item">
                        <a href="<?php echo htmlspecialchars($item['url']); ?>" 
                           class="navegacao-lojista-link <?php echo ($menuAtivo === $item['id']) ? 'ativo' : ''; ?>"
                           data-menu-id="<?php echo htmlspecialchars($item['id']); ?>">
                            <div class="navegacao-lojista-icone">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <?php echo $item['icone']; ?>
                                </svg>
                            </div>
                            <span class="navegacao-lojista-texto"><?php echo htmlspecialchars($item['titulo']); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <!-- Rodapé com Sair -->
        <div class="sidebar-lojista-rodape">
            <a href="<?php echo LOGOUT_URL; ?>" class="botao-lojista-sair" onclick="return confirm('Tem certeza que deseja sair?');">
                <div class="navegacao-lojista-icone">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 21 5-5-5-5"></path>
                        <path d="M20 4v7a4 4 0 0 1-4 4H5"></path>
                    </svg>
                </div>
                <span class="navegacao-lojista-texto">Sair</span>
            </a>
        </div>
    </nav>
    
    <!-- Toggle Mobile -->
    <button type="button" class="sidebar-lojista-mobile-toggle" id="sidebarLojistaMobileToggle" aria-label="Abrir menu">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>
</div>

<!-- Scripts necessários -->
<link rel="stylesheet" href="<?php echo CSS_URL; ?>/sidebar-lojista.css?v=<?php echo ASSETS_VERSION; ?>">
<script src="<?php echo JS_URL; ?>/sidebar-lojista.js?v=<?php echo ASSETS_VERSION; ?>"></script>