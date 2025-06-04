<?php
// index.php - Versão Otimizada para Carregamento Rápido
require_once './config/constants.php';
require_once './config/database.php';

// Funcionalidades do logo mantidas
function renderStoreLogo($store) {
    static $logoCache = [];
    
    $nomeFantasia = htmlspecialchars($store['nome_fantasia']);
    $primeiraLetra = strtoupper(substr($nomeFantasia, 0, 1));
    
    if (!empty($store['logo'])) {
        $logoFilename = $store['logo'];
        
        if (!isset($logoCache[$logoFilename])) {
            if (preg_match('/^[a-zA-Z0-9_.-]+\.(jpg|jpeg|png|gif)$/i', $logoFilename)) {
                $fullPath = __DIR__ . '/uploads/store_logos/' . $logoFilename;
                $logoCache[$logoFilename] = file_exists($fullPath);
            } else {
                $logoCache[$logoFilename] = false;
            }
        }
        
        if ($logoCache[$logoFilename]) {
            $logoPath = '/uploads/store_logos/' . htmlspecialchars($logoFilename);
            return '<img src="' . $logoPath . '" alt="Logo ' . $nomeFantasia . '" class="store-logo-image">';
        }
    }
    
    $corDeFundo = generateColorFromName($nomeFantasia);
    return '<div class="store-logo-fallback" style="background: linear-gradient(135deg, ' . $corDeFundo . ', ' . adjustBrightness($corDeFundo, -20) . ')" title="' . $nomeFantasia . '">' . $primeiraLetra . '</div>';
}

function generateColorFromName($name) {
    $colors = [
        '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FECA57',
        '#FF9FF3', '#54A0FF', '#5F27CD', '#FF3838', '#00D2D3',
        '#FF6348', '#7bed9f', '#70a1ff', '#dda0dd', '#ffb142'
    ];
    
    $hash = crc32($name);
    $index = abs($hash) % count($colors);
    return $colors[$index];
}

function adjustBrightness($hex, $percent) {
    $hex = ltrim($hex, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $r = max(0, min(255, $r + ($r * $percent / 100)));
    $g = max(0, min(255, $g + ($g * $percent / 100)));
    $b = max(0, min(255, $b + ($b * $percent / 100)));
    
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}

// Backend funcionando perfeitamente (mantido igual)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
$userType = $isLoggedIn ? ($_SESSION['user_type'] ?? '') : '';
$userName = $isLoggedIn ? ($_SESSION['user_name'] ?? '') : '';

$dashboardURL = '';
if ($isLoggedIn) {
    switch ($userType) {
        case 'admin':
            $dashboardURL = ADMIN_DASHBOARD_URL;
            break;
        case 'cliente':
            $dashboardURL = CLIENT_DASHBOARD_URL;
            break;
        case 'loja':
            $dashboardURL = STORE_DASHBOARD_URL;
            break;
    }
}

// Buscar lojas parceiras
$partnerStores = [];
try {
    $db = Database::getConnection();
    $stmt = $db->query("SELECT nome_fantasia, logo, categoria, descricao, porcentagem_cashback FROM lojas WHERE status = 'aprovado' ORDER BY RAND() LIMIT 8");
    $partnerStores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar lojas parceiras: " . $e->getMessage());
    $partnerStores = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isLoggedIn ? "Bem-vindo ao Klube Cash, " . htmlspecialchars($userName) : "Klube Cash - Transforme suas Compras em Dinheiro de Volta"; ?></title>
    
    <meta name="description" content="Klube Cash - O programa de cashback mais inteligente do Brasil. Receba dinheiro de volta em todas as suas compras. Cadastre-se grátis!">
    <meta name="keywords" content="cashback, dinheiro de volta, economia, programa de fidelidade">
    
    <link rel="icon" type="image/x-icon" href="assets/images/icons/KlubeCashLOGO.ico">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* CSS INLINE OTIMIZADO - Carregamento Instantâneo */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary-color: #FF7A00;
            --primary-dark: #E06600;
            --primary-light: #FFB366;
            --secondary-color: #1A1A1A;
            --white: #FFFFFF;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-600: #4B5563;
            --gray-800: #1F2937;
            --gray-900: #111827;
            --success: #10B981;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: 'Inter', system-ui, sans-serif;
            line-height: 1.6;
            color: var(--gray-800);
            background: var(--white);
        }
        
        /* HEADER */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 80px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            z-index: 1000;
            display: flex;
            align-items: center;
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .logo img {
            height: 40px;
            width: auto;
        }
        
        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }
        
        .nav-link {
            text-decoration: none;
            color: var(--gray-600);
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .nav-link:hover {
            color: var(--primary-color);
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        /* BOTÕES */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            border: 2px solid transparent;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
            min-height: 44px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: var(--white);
            box-shadow: 0 10px 20px rgba(255, 122, 0, 0.2);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(255, 122, 0, 0.3);
        }
        
        .btn-ghost {
            background: transparent;
            color: var(--gray-600);
            border-color: var(--gray-600);
        }
        
        .btn-ghost:hover {
            background: var(--gray-50);
            color: var(--gray-800);
        }
        
        /* USER MENU */
        .user-menu {
            position: relative;
        }
        
        .user-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.75rem;
            transition: background-color 0.3s ease;
        }
        
        .user-button:hover {
            background-color: var(--gray-100);
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .user-name {
            font-weight: 500;
            color: var(--gray-600);
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            width: 200px;
            background: var(--white);
            border: 1px solid var(--gray-100);
            border-radius: 0.75rem;
            box-shadow: var(--shadow);
            padding: 0.5rem;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1100;
        }
        
        .user-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            color: var(--gray-600);
            text-decoration: none;
            border-radius: 0.5rem;
            transition: background-color 0.3s ease;
        }
        
        .dropdown-item:hover {
            background-color: var(--gray-50);
            color: var(--primary-color);
        }
        
        /* MOBILE MENU */
        .mobile-toggle {
            display: none;
            flex-direction: column;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
        }
        
        .hamburger-line {
            width: 24px;
            height: 2px;
            background: var(--gray-600);
            margin: 3px 0;
            transition: 0.3s;
        }
        
        /* MAIN CONTENT */
        .main-content {
            padding-top: 80px;
        }
        
        /* HERO SECTION */
        .hero {
            background: linear-gradient(135deg, #FF7A00 0%, #FF9A40 50%, #FFB366 100%);
            color: var(--white);
            padding: 6rem 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 2rem;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
        }
        
        .hero-title {
            font-size: 3rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
        }
        
        .hero-subtitle {
            font-size: 1.25rem;
            line-height: 1.6;
            margin-bottom: 2rem;
            opacity: 0.95;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .hero-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 3rem;
        }
        
        .btn-large {
            padding: 1rem 2rem;
            font-size: 1.125rem;
            min-height: 56px;
        }
        
        /* STATS */
        .hero-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            max-width: 600px;
            margin: 0 auto;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: #FFD700;
            margin-bottom: 0.25rem;
            display: block;
        }
        
        .stat-label {
            font-size: 0.875rem;
            opacity: 0.8;
        }
        
        /* SECTIONS */
        .section {
            padding: 6rem 0;
        }
        
        .section:nth-child(even) {
            background: var(--gray-50);
        }
        
        .section-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }
        
        .section-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: rgba(255, 122, 0, 0.1);
            color: var(--primary-color);
            border: 1px solid rgba(255, 122, 0, 0.2);
            border-radius: 2rem;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1rem;
        }
        
        .section-title {
            font-size: 2.25rem;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 1rem;
        }
        
        .section-description {
            font-size: 1.125rem;
            color: var(--gray-600);
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* GRID LAYOUTS */
        .grid {
            display: grid;
            gap: 2rem;
        }
        
        .grid-3 {
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }
        
        .grid-4 {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }
        
        /* CARDS */
        .card {
            background: var(--white);
            border: 1px solid var(--gray-100);
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow);
            border-color: var(--primary-color);
        }
        
        .card-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 122, 0, 0.1);
            color: var(--primary-color);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 1rem;
        }
        
        .card-description {
            color: var(--gray-600);
            line-height: 1.6;
        }
        
        /* STORE LOGOS */
        .store-logo-image {
            max-width: 70px;
            max-height: 70px;
            border-radius: 0.75rem;
            object-fit: contain;
        }
        
        .store-logo-fallback {
            width: 70px;
            height: 70px;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--white);
            margin: 0 auto 1rem;
        }
        
        /* FOOTER */
        .footer {
            background: var(--gray-900);
            color: var(--white);
            padding: 3rem 0 1rem;
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .footer-brand {
            grid-column: span 2;
        }
        
        .footer-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 0.75rem;
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer-links a:hover {
            color: var(--primary-color);
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 1rem;
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
        }
        
        /* RESPONSIVIDADE */
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
            
            .mobile-toggle {
                display: flex;
            }
            
            .hero-title {
                font-size: 2.25rem;
            }
            
            .hero-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .hero-stats {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .section-title {
                font-size: 1.875rem;
            }
            
            .footer-brand {
                grid-column: span 1;
            }
            
            .user-name {
                display: none;
            }
        }
        
        @media (max-width: 480px) {
            .header-container,
            .hero-container,
            .section-container,
            .footer-container {
                padding: 0 1rem;
            }
            
            .grid {
                gap: 1rem;
            }
            
            .card {
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <header class="header">
        <div class="header-container">
            <a href="<?php echo SITE_URL; ?>" class="logo">
                <img src="assets/images/logolaranja.png" alt="Klube Cash">
            </a>
            
            <nav>
                <ul class="nav-menu">
                    <li><a href="#como-funciona" class="nav-link">Como Funciona</a></li>
                    <li><a href="#vantagens" class="nav-link">Vantagens</a></li>
                    <li><a href="#parceiros" class="nav-link">Parceiros</a></li>
                    <li><a href="#faq" class="nav-link">FAQ</a></li>
                </ul>
            </nav>
            
            <div class="header-actions">
                <?php if ($isLoggedIn): ?>
                    <div class="user-menu">
                        <button class="user-button" onclick="toggleUserMenu()">
                            <div class="user-avatar"><?php echo strtoupper(substr($userName, 0, 1)); ?></div>
                            <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M7 10l5 5 5-5z"/>
                            </svg>
                        </button>
                        
                        <div class="user-dropdown" id="userDropdown">
                            <a href="<?php echo htmlspecialchars($dashboardURL); ?>" class="dropdown-item">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                </svg>
                                Meu Painel
                            </a>
                            <a href="<?php echo SITE_URL; ?>/controllers/AuthController.php?action=logout" class="dropdown-item">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>
                                </svg>
                                Sair
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?php echo LOGIN_URL; ?>" class="btn btn-ghost">Entrar</a>
                    <a href="<?php echo REGISTER_URL; ?>" class="btn btn-primary">Cadastrar Grátis</a>
                <?php endif; ?>
                
                <button class="mobile-toggle" onclick="toggleMobileMenu()">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </button>
            </div>
        </div>
    </header>
    
    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- HERO SECTION -->
        <section class="hero">
            <div class="hero-container">
                <?php if ($isLoggedIn): ?>
                    <div class="hero-badge">
                        <span>👋</span>
                        <span>Bem-vindo de volta!</span>
                    </div>
                    <h1 class="hero-title">
                        Olá, <?php echo htmlspecialchars($userName); ?>!<br>
                        Continue economizando com <em>inteligência</em>
                    </h1>
                    <p class="hero-subtitle">
                        Você já faz parte da revolução do cashback. Explore suas oportunidades de economia.
                    </p>
                    <div class="hero-actions">
                        <a href="<?php echo htmlspecialchars($dashboardURL); ?>" class="btn btn-primary btn-large">
                            Acessar Minha Conta
                        </a>
                        <a href="#parceiros" class="btn btn-ghost btn-large">Ver Lojas Parceiras</a>
                    </div>
                <?php else: ?>
                    <div class="hero-badge">
                        <span>💰</span>
                        <span>Dinheiro de volta garantido</span>
                    </div>
                    <h1 class="hero-title">
                        Transforme suas <em>compras</em> em<br>
                        <em>dinheiro de volta</em>
                    </h1>
                    <p class="hero-subtitle">
                        O Klube Cash é o programa de cashback mais inteligente do Brasil. 
                        Cadastre-se gratuitamente e comece a receber dinheiro de volta em todas as suas compras.
                    </p>
                    <div class="hero-actions">
                        <a href="<?php echo REGISTER_URL; ?>" class="btn btn-primary btn-large">
                            Começar Agora - É Grátis
                        </a>
                        <a href="#como-funciona" class="btn btn-ghost btn-large">Como Funciona?</a>
                    </div>
                <?php endif; ?>
                
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number">50K+</span>
                        <span class="stat-label">Usuários Ativos</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">500+</span>
                        <span class="stat-label">Lojas Parceiras</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">R$ 2.5M</span>
                        <span class="stat-label">Em Cashback Pago</span>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- COMO FUNCIONA -->
        <section class="section" id="como-funciona">
            <div class="section-container">
                <div class="section-header">
                    <span class="section-badge">Processo Simples</span>
                    <h2 class="section-title">Como o Klube Cash Funciona?</h2>
                    <p class="section-description">
                        Três passos simples para começar a receber dinheiro de volta em todas as suas compras.
                    </p>
                </div>
                
                <div class="grid grid-3">
                    <div class="card">
                        <div class="card-icon">1️⃣</div>
                        <h3 class="card-title">Cadastre-se Gratuitamente</h3>
                        <p class="card-description">
                            Crie sua conta em menos de 2 minutos. É 100% gratuito e você não paga nada para participar.
                        </p>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">2️⃣</div>
                        <h3 class="card-title">Compre e Se Identifique</h3>
                        <p class="card-description">
                            Faça suas compras normalmente nas lojas parceiras e se identifique como membro Klube Cash.
                        </p>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">3️⃣</div>
                        <h3 class="card-title">Receba Seu Cashback</h3>
                        <p class="card-description">
                            Uma porcentagem do valor volta para sua conta. É dinheiro real que você pode usar!
                        </p>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- VANTAGENS -->
        <section class="section" id="vantagens">
            <div class="section-container">
                <div class="section-header">
                    <span class="section-badge">Por Que Escolher?</span>
                    <h2 class="section-title">Vantagens Exclusivas do Klube Cash</h2>
                    <p class="section-description">
                        Descubra por que somos a escolha número 1 de quem quer economizar de verdade
                    </p>
                </div>
                
                <div class="grid grid-3">
                    <div class="card">
                        <div class="card-icon">💰</div>
                        <h3 class="card-title">Cashback Real</h3>
                        <p class="card-description">
                            Dinheiro de verdade na sua conta, não pontos que expiram ou vales complicados.
                        </p>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">🔒</div>
                        <h3 class="card-title">100% Seguro</h3>
                        <p class="card-description">
                            Plataforma criptografada e dados protegidos. Sua segurança é nossa prioridade.
                        </p>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">⚡</div>
                        <h3 class="card-title">Instantâneo</h3>
                        <p class="card-description">
                            Cashback processado rapidamente. Você vê o retorno em tempo real.
                        </p>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">🏪</div>
                        <h3 class="card-title">Milhares de Lojas</h3>
                        <p class="card-description">
                            Rede gigante de parceiros em todas as categorias que você imaginar.
                        </p>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">💝</div>
                        <h3 class="card-title">Sem Fidelidade</h3>
                        <p class="card-description">
                            Use quando quiser, como quiser. Sem contratos longos ou obrigações.
                        </p>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">🔧</div>
                        <h3 class="card-title">Suporte 24/7</h3>
                        <p class="card-description">
                            Equipe especializada sempre pronta para ajudar com qualquer dúvida.
                        </p>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- PARCEIROS -->
        <section class="section" id="parceiros">
            <div class="section-container">
                <div class="section-header">
                    <span class="section-badge">Nossos Parceiros</span>
                    <h2 class="section-title">Onde Você Pode Usar o Klube Cash</h2>
                    <p class="section-description">
                        Algumas das incríveis lojas parceiras onde você pode ganhar cashback
                    </p>
                </div>
                
                <?php if (!empty($partnerStores)): ?>
                    <div class="grid grid-4">
                        <?php foreach ($partnerStores as $store): ?>
                            <div class="card">
                                <?php echo renderStoreLogo($store); ?>
                                <h4 class="card-title"><?php echo htmlspecialchars($store['nome_fantasia']); ?></h4>
                                <?php if (!empty($store['categoria'])): ?>
                                    <p style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 1rem;">
                                        <?php echo htmlspecialchars($store['categoria']); ?>
                                    </p>
                                <?php endif; ?>
                                <div style="font-weight: 600; color: var(--primary-color);">
                                    Cashback: <?php echo number_format($store['porcentagem_cashback'] ?? 5, 1); ?>%
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 4rem 0; background: rgba(255, 122, 0, 0.05); border-radius: 1rem;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">🏪</div>
                        <h3>Em Breve: Lojas Incríveis!</h3>
                        <p>Estamos fechando parcerias com as melhores lojas para você.</p>
                        <a href="<?php echo STORE_REGISTER_URL; ?>" class="btn btn-primary" style="margin-top: 1rem;">Seja o Primeiro Parceiro</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- FAQ -->
        <section class="section" id="faq">
            <div class="section-container">
                <div class="section-header">
                    <span class="section-badge">Tire Suas Dúvidas</span>
                    <h2 class="section-title">Perguntas Frequentes</h2>
                </div>
                
                <div style="max-width: 800px; margin: 0 auto;">
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div style="background: var(--white); border: 1px solid var(--gray-100); border-radius: 1rem; margin-bottom: 1rem; overflow: hidden;">
                            <button style="width: 100%; padding: 1.5rem; text-align: left; background: none; border: none; font-size: 1.125rem; font-weight: 600; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                                <span>O cadastro no Klube Cash é realmente gratuito?</span>
                                <span class="faq-icon">▼</span>
                            </button>
                            <div class="faq-answer" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease;">
                                <div style="padding: 0 1.5rem 1.5rem; color: var(--gray-600);">
                                    Sim! O cadastro no Klube Cash é 100% gratuito e sempre será. Não cobramos nenhuma taxa de adesão, mensalidade ou anuidade.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div style="background: var(--white); border: 1px solid var(--gray-100); border-radius: 1rem; margin-bottom: 1rem; overflow: hidden;">
                            <button style="width: 100%; padding: 1.5rem; text-align: left; background: none; border: none; font-size: 1.125rem; font-weight: 600; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                                <span>Como funciona o cashback? É dinheiro real?</span>
                                <span class="faq-icon">▼</span>
                            </button>
                            <div class="faq-answer" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease;">
                                <div style="padding: 0 1.5rem 1.5rem; color: var(--gray-600);">
                                    Sim, é dinheiro real! Quando você compra em lojas parceiras, uma porcentagem do valor volta como cashback para usar em novas compras na mesma loja.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div style="background: var(--white); border: 1px solid var(--gray-100); border-radius: 1rem; margin-bottom: 1rem; overflow: hidden;">
                            <button style="width: 100%; padding: 1.5rem; text-align: left; background: none; border: none; font-size: 1.125rem; font-weight: 600; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                                <span>O Klube Cash é seguro para usar?</span>
                                <span class="faq-icon">▼</span>
                            </button>
                            <div class="faq-answer" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease;">
                                <div style="padding: 0 1.5rem 1.5rem; color: var(--gray-600);">
                                    Absolutamente! Utilizamos tecnologias avançadas de criptografia para proteger seus dados e seguimos todas as normas de segurança.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <!-- FOOTER -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
                        <img src="assets/images/logobranco.png" alt="Klube Cash" style="height: 40px;">
                    </div>
                    <p style="margin-bottom: 1.5rem; color: rgba(255, 255, 255, 0.8);">
                        Transformando suas compras em oportunidades de economia. 
                        O programa de cashback mais inteligente e confiável do Brasil.
                    </p>
                </div>
                
                <div>
                    <h4 class="footer-title">Links Rápidos</h4>
                    <ul class="footer-links">
                        <li><a href="#como-funciona">Como Funciona</a></li>
                        <li><a href="#vantagens">Vantagens</a></li>
                        <li><a href="#parceiros">Lojas Parceiras</a></li>
                        <li><a href="<?php echo STORE_REGISTER_URL; ?>">Seja Parceiro</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="footer-title">Contato</h4>
                    <ul class="footer-links">
                        <li><a href="mailto:contato@klubecash.com">contato@klubecash.com</a></li>
                        <li><span style="color: rgba(255, 255, 255, 0.8);">(34) 9999-9999</span></li>
                        <li><span style="color: rgba(255, 255, 255, 0.8);">Patos de Minas, MG</span></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Klube Cash. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>
    
    <script>
        // JavaScript Essencial e Funcional
        
        // Toggle User Menu
        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('show');
        }
        
        // Toggle FAQ
        function toggleFAQ(element) {
            const answer = element.querySelector('.faq-answer');
            const icon = element.querySelector('.faq-icon');
            const isOpen = answer.style.maxHeight && answer.style.maxHeight !== '0px';
            
            // Fechar todos os outros FAQs
            document.querySelectorAll('.faq-item').forEach(item => {
                if (item !== element) {
                    const otherAnswer = item.querySelector('.faq-answer');
                    const otherIcon = item.querySelector('.faq-icon');
                    otherAnswer.style.maxHeight = '0';
                    otherIcon.textContent = '▼';
                }
            });
            
            // Toggle atual
            if (isOpen) {
                answer.style.maxHeight = '0';
                icon.textContent = '▼';
            } else {
                answer.style.maxHeight = answer.scrollHeight + 'px';
                icon.textContent = '▲';
            }
        }
        
        // Smooth Scroll
        document.querySelectorAll('a[href^="#"]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.user-menu')) {
                document.getElementById('userDropdown')?.classList.remove('show');
            }
        });
        
        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.header');
            if (window.scrollY > 100) {
                header.style.background = 'rgba(255, 255, 255, 0.98)';
                header.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1)';
            } else {
                header.style.background = 'rgba(255, 255, 255, 0.95)';
                header.style.boxShadow = 'none';
            }
        });
        
        console.log('✅ Klube Cash carregado com sucesso!');
    </script>
</body>
</html>