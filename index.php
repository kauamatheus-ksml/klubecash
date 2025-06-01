<?php
// index.php - Versão 2.0 Completamente Redesenhada
require_once './config/constants.php';
require_once './config/database.php';

/**
 * Como um artista digital, esta função decide se vai mostrar uma foto real da loja
 * ou criar um ícone colorido personalizado - é como ter um designer automático!
 */
function renderStoreLogo($store) {
    static $logoCache = [];
    
    $nomeFantasia = htmlspecialchars($store['nome_fantasia']);
    $primeiraLetra = strtoupper(substr($nomeFantasia, 0, 1));
    
    if (!empty($store['logo'])) {
        $logoFilename = $store['logo'];
        
        // Verificação de segurança - como um porteiro que confere se o arquivo é confiável
        if (!isset($logoCache[$logoFilename])) {
            if (preg_match('/^[a-zA-Z0-9_.-]+\.(jpg|jpeg|png|gif)$/i', $logoFilename)) {
                $fullPath = __DIR__ . '/uploads/store_logos/' . $logoFilename;
                $logoCache[$logoFilename] = file_exists($fullPath);
            } else {
                $logoCache[$logoFilename] = false;
                error_log("Arquivo suspeito detectado: " . $logoFilename);
            }
        }
        
        if ($logoCache[$logoFilename]) {
            $logoPath = '/uploads/store_logos/' . htmlspecialchars($logoFilename);
            return '<img src="' . $logoPath . '" alt="Logo ' . $nomeFantasia . '" class="store-logo-image" loading="lazy">';
        }
    }
    
    // Se não tem logo, criamos um ícone personalizado com cor única
    $corDeFundo = generateColorFromName($nomeFantasia);
    return '<div class="store-logo-fallback" style="background: linear-gradient(135deg, ' . $corDeFundo . ', ' . adjustBrightness($corDeFundo, -20) . ')" title="' . $nomeFantasia . '">' . $primeiraLetra . '</div>';
}

/**
 * Gera uma cor única para cada loja - como uma impressão digital colorida
 * Cada loja sempre terá a mesma cor, criando uma identidade visual consistente
 */
function generateColorFromName($name) {
    $colors = [
        '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FECA57',
        '#FF9FF3', '#54A0FF', '#5F27CD', '#FF3838', '#00D2D3',
        '#FF6348', '#7bed9f', '#70a1ff', '#dda0dd', '#ffb142',
        '#ff7675', '#74b9ff', '#0984e3', '#00b894', '#fdcb6e'
    ];
    
    $hash = crc32($name);
    $index = abs($hash) % count($colors);
    return $colors[$index];
}

/**
 * Ajusta o brilho de uma cor para criar gradientes automáticos
 */
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

// Inicialização da sessão - como abrir a porta de casa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificação do usuário logado - como verificar se alguém já está em casa
$isLoggedIn = isset($_SESSION['user_id']);
$userType = $isLoggedIn ? ($_SESSION['user_type'] ?? '') : '';
$userName = $isLoggedIn ? ($_SESSION['user_name'] ?? '') : '';

// Determinação da URL do dashboard - como decidir para qual sala da casa ir
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

// Busca das lojas parceiras - como procurar os melhores amigos para apresentar
$partnerStores = [];
try {
    $db = Database::getConnection();
    
    $stmt = $db->query("
        SELECT 
            nome_fantasia, 
            logo, 
            categoria,
            descricao,
            porcentagem_cashback
        FROM lojas 
        WHERE status = 'aprovado' 
        ORDER BY RAND() 
        LIMIT 8
    ");
    $partnerStores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Lojas parceiras carregadas: " . count($partnerStores));
    
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
    
    <!-- Meta tags otimizadas para SEO e compartilhamento -->
    <meta name="description" content="Klube Cash - O programa de cashback mais inteligente do Brasil. Receba dinheiro de volta em todas as suas compras. Cadastre-se grátis e comece a economizar hoje mesmo!">
    <meta name="keywords" content="cashback, dinheiro de volta, economia, programa de fidelidade, compras online, desconto, lojas parceiras">
    <meta name="author" content="Klube Cash">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph para redes sociais -->
    <meta property="og:title" content="Klube Cash - Seu Dinheiro de Volta Garantido">
    <meta property="og:description" content="Receba cashback real em suas compras. Simples, rápido e confiável.">
    <meta property="og:image" content="<?php echo SITE_URL; ?>/assets/images/og-image.jpg">
    <meta property="og:url" content="<?php echo SITE_URL; ?>">
    <meta property="og:type" content="website">
    
    <!-- Favicons modernos -->
    <link rel="icon" type="image/x-icon" href="assets/images/icons/KlubeCashLOGO.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/icons/apple-touch-icon.png">
    
    <!-- Preload de recursos críticos -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" as="style">
    <link rel="preload" href="assets/css/index-v2.css" as="style">
    
    <!-- Styles -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/index-v2.css">
    
    <!-- Bibliotecas de animação -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Schema.org para rich snippets -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "Klube Cash",
        "description": "Programa de cashback inteligente",
        "url": "<?php echo SITE_URL; ?>",
        "applicationCategory": "FinanceApplication",
        "operatingSystem": "Web"
    }
    </script>
</head>

<body>
    <!-- Loading Screen -->
    <div id="loading-screen" class="loading-screen">
        <div class="loading-content">
            <div class="loading-logo">
                <img src="assets/images/logolaranja.png" alt="Klube Cash">
            </div>
            <div class="loading-spinner"></div>
            <p>Carregando sua experiência...</p>
        </div>
    </div>

    <!-- Header Moderno e Responsivo -->
    <header class="modern-header" id="mainHeader">
        <div class="header-container">
            <nav class="main-navigation">
                <!-- Logo Responsivo -->
                <a href="<?php echo SITE_URL; ?>" class="brand-logo" aria-label="Klube Cash - Página Inicial">
                    <img src="assets/images/logolaranja.png" alt="Klube Cash" class="logo-image">
                    <span class="logo-text"></span>
                </a>
                
                <!-- Menu Desktop -->
                <ul class="desktop-menu" role="menubar">
                    <li><a href="#como-funciona" class="nav-link smooth-scroll" role="menuitem">Como Funciona</a></li>
                    <li><a href="#vantagens" class="nav-link smooth-scroll" role="menuitem">Vantagens</a></li>
                    <li><a href="#parceiros" class="nav-link smooth-scroll" role="menuitem">Parceiros</a></li>
                    <li><a href="#testimonials" class="nav-link smooth-scroll" role="menuitem">Depoimentos</a></li>
                    <li><a href="#faq" class="nav-link smooth-scroll" role="menuitem">FAQ</a></li>
                </ul>
                
                <!-- Botões de Ação -->
                <div class="header-actions">
                    <?php if ($isLoggedIn): ?>
                        <div class="user-menu">
                            <button class="user-button" id="userMenuBtn">
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($userName, 0, 1)); ?>
                                </div>
                                <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                                <svg class="dropdown-icon" viewBox="0 0 24 24" width="16" height="16">
                                    <path d="M7 10l5 5 5-5z" fill="currentColor"/>
                                </svg>
                            </button>
                            
                            <div class="user-dropdown" id="userDropdown">
                                <a href="<?php echo htmlspecialchars($dashboardURL); ?>" class="dropdown-item">
                                    <svg viewBox="0 0 24 24" width="20" height="20">
                                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" fill="none" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                    Meu Painel
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="<?php echo SITE_URL; ?>/controllers/AuthController.php?action=logout" class="dropdown-item logout">
                                    <svg viewBox="0 0 24 24" width="20" height="20">
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" fill="none" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                    Sair
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo LOGIN_URL; ?>" class="btn btn-ghost">Entrar</a>
                        <a href="<?php echo REGISTER_URL; ?>" class="btn btn-primary">Cadastrar Grátis</a>
                    <?php endif; ?>
                </div>
                
                <!-- Botão Mobile Menu -->
                <button class="mobile-menu-toggle" id="mobileMenuBtn" aria-label="Abrir menu">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </button>
            </nav>
        </div>
        
        <!-- Menu Mobile -->
        <div class="mobile-menu" id="mobileMenu">
            <div class="mobile-menu-content">
                <ul class="mobile-nav-list">
                    <li><a href="#como-funciona" class="mobile-nav-link">Como Funciona</a></li>
                    <li><a href="#vantagens" class="mobile-nav-link">Vantagens</a></li>
                    <li><a href="#parceiros" class="mobile-nav-link">Parceiros</a></li>
                    <li><a href="#testimonials" class="mobile-nav-link">Depoimentos</a></li>
                    <li><a href="#faq" class="mobile-nav-link">FAQ</a></li>
                </ul>
                
                <div class="mobile-menu-actions">
                    <?php if ($isLoggedIn): ?>
                        <a href="<?php echo htmlspecialchars($dashboardURL); ?>" class="btn btn-primary btn-full">Meu Painel</a>
                        <a href="<?php echo SITE_URL; ?>/controllers/AuthController.php?action=logout" class="btn btn-ghost btn-full">Sair</a>
                    <?php else: ?>
                        <a href="<?php echo LOGIN_URL; ?>" class="btn btn-ghost btn-full">Entrar</a>
                        <a href="<?php echo REGISTER_URL; ?>" class="btn btn-primary btn-full">Cadastrar Grátis</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content">
        <!-- Hero Section Revolucionária -->
        <section class="hero-modern" id="hero">
            <div class="hero-background">
                <div class="hero-shapes">
                    <div class="shape shape-1"></div>
                    <div class="shape shape-2"></div>
                    <div class="shape shape-3"></div>
                </div>
                <div class="hero-gradient"></div>
            </div>
            
            <div class="hero-container">
                <div class="hero-content" data-aos="fade-up" data-aos-duration="800">
                    <?php if ($isLoggedIn): ?>
                        <div class="welcome-badge">
                            <span class="badge-icon">👋</span>
                            <span>Bem-vindo de volta!</span>
                        </div>
                        <h1 class="hero-title">
                            Olá, <span class="highlight-name"><?php echo htmlspecialchars($userName); ?></span>!
                            <br>Continue economizando com <span class="highlight-brand">inteligência</span>
                        </h1>
                        <p class="hero-subtitle">
                            Você já faz parte da revolução do cashback. Explore suas oportunidades de economia e descubra quanto dinheiro pode ganhar de volta com suas próximas compras.
                        </p>
                        <div class="hero-actions">
                            <a href="<?php echo htmlspecialchars($dashboardURL); ?>" class="btn btn-primary btn-large">
                                <span>Acessar Minha Conta</span>
                                <svg viewBox="0 0 24 24" width="20" height="20">
                                    <path d="M5 12h14M12 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </a>
                            <a href="#parceiros" class="btn btn-ghost btn-large smooth-scroll">Ver Lojas Parceiras</a>
                        </div>
                    <?php else: ?>
                        <div class="hero-badge">
                            <span class="badge-icon">💰</span>
                            <span>Dinheiro de volta garantido</span>
                        </div>
                        <h1 class="hero-title">
                            Transforme suas <span class="highlight-primary">compras</span> em
                            <span class="highlight-secondary">dinheiro de volta</span>
                        </h1>
                        <p class="hero-subtitle">
                            O Klube Cash é o programa de cashback mais inteligente do Brasil. Cadastre-se gratuitamente e comece a receber dinheiro de volta em todas as suas compras. Simples, rápido e sem pegadinhas.
                        </p>
                        <div class="hero-actions">
                            <a href="<?php echo REGISTER_URL; ?>" class="btn btn-primary btn-large pulse-animation">
                                <span>Começar Agora - É Grátis</span>
                                <svg viewBox="0 0 24 24" width="20" height="20">
                                    <path d="M5 12h14M12 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </a>
                            <a href="#como-funciona" class="btn btn-ghost btn-large smooth-scroll">Como Funciona?</a>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Estatísticas Impressionantes -->
                    <div class="hero-stats" data-aos="fade-up" data-aos-delay="400">
                        <div class="stat-item">
                            <div class="stat-number" data-count="50000">0</div>
                            <div class="stat-label">Usuários Ativos</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number" data-count="500">0</div>
                            <div class="stat-label">Lojas Parceiras</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">R$ <span data-count="2500000">0</span></div>
                            <div class="stat-label">Em Cashback Pago</div>
                        </div>
                    </div>
                </div>
                
                <!-- Ilustração Moderna -->
                <div class="hero-visual" data-aos="fade-left" data-aos-delay="200">
                    <div class="visual-container">
                        <div class="cashback-card floating">
                            <div class="card-header">
                                <div class="card-logo">💳</div>
                                <span>Klube Cash</span>
                            </div>
                            <div class="card-content">
                                <div class="balance-label">Seu Cashback</div>
                                <div class="balance-amount">R$ 247,85</div>
                            </div>
                            <div class="card-footer">
                                <div class="progress-bar">
                                    <div class="progress-fill"></div>
                                </div>
                                <span class="progress-text">+15% este mês</span>
                            </div>
                        </div>
                        
                        <div class="floating-icons">
                            <div class="icon-item icon-1">💰</div>
                            <div class="icon-item icon-2">🎯</div>
                            <div class="icon-item icon-3">⚡</div>
                            <div class="icon-item icon-4">🚀</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Indicador de Scroll -->
            <div class="scroll-indicator">
                <div class="scroll-text">Role para descobrir mais</div>
                <div class="scroll-arrow"></div>
            </div>
        </section>

        <!-- Como Funciona - Redesenhado -->
        <section class="how-it-works modern-section" id="como-funciona">
            <div class="section-container">
                <div class="section-header" data-aos="fade-up">
                    <span class="section-badge">Processo Simples</span>
                    <h2 class="section-title">Como o Klube Cash Funciona?</h2>
                    <p class="section-description">
                        Três passos simples para começar a receber dinheiro de volta em todas as suas compras. 
                        Não há truques, taxas ocultas ou complicações.
                    </p>
                </div>
                
                <div class="steps-grid">
                    <div class="step-card" data-aos="zoom-in" data-aos-delay="100">
                        <div class="step-visual">
                            <div class="step-number">1</div>
                            <div class="step-icon">
                                <svg viewBox="0 0 24 24" width="32" height="32">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8z" fill="none" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </div>
                        </div>
                        <h3 class="step-title">Cadastre-se Gratuitamente</h3>
                        <p class="step-description">
                            Crie sua conta em menos de 2 minutos. É 100% gratuito e você não paga nada para participar do programa.
                        </p>
                        <div class="step-features">
                            <span class="feature">✓ Sem taxas</span>
                            <span class="feature">✓ Sem anuidade</span>
                            <span class="feature">✓ Cadastro rápido</span>
                        </div>
                    </div>
                    
                    <div class="step-card" data-aos="zoom-in" data-aos-delay="200">
                        <div class="step-visual">
                            <div class="step-number">2</div>
                            <div class="step-icon">
                                <svg viewBox="0 0 24 24" width="32" height="32">
                                    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2M15 2H9a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1z" fill="none" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </div>
                        </div>
                        <h3 class="step-title">Compre e Se Identifique</h3>
                        <p class="step-description">
                            Faça suas compras normalmente nas lojas parceiras e se identifique como membro Klube Cash no momento da compra.
                        </p>
                        <div class="step-features">
                            <span class="feature">✓ Online ou física</span>
                            <span class="feature">✓ Processo simples</span>
                            <span class="feature">✓ Sem mudanças</span>
                        </div>
                    </div>
                    
                    <div class="step-card" data-aos="zoom-in" data-aos-delay="300">
                        <div class="step-visual">
                            <div class="step-number">3</div>
                            <div class="step-icon">
                                <svg viewBox="0 0 24 24" width="32" height="32">
                                    <path d="M12 1v6m0 6v6m8-10l-6 6-2-2-4 4" fill="none" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </div>
                        </div>
                        <h3 class="step-title">Receba Seu Cashback</h3>
                        <p class="step-description">
                            Uma porcentagem do valor das suas compras volta para sua conta Klube Cash. É dinheiro real que você pode usar!
                        </p>
                        <div class="step-features">
                            <span class="feature">✓ Dinheiro real</span>
                            <span class="feature">✓ Automático</span>
                            <span class="feature">✓ Sem prazo para usar</span>
                        </div>
                    </div>
                </div>
                
                <!-- Timeline Visual -->
                <div class="process-timeline" data-aos="fade-up" data-aos-delay="400">
                    <div class="timeline-line"></div>
                    <div class="timeline-points">
                        <div class="timeline-point active">
                            <span class="point-number">1</span>
                        </div>
                        <div class="timeline-point">
                            <span class="point-number">2</span>
                        </div>
                        <div class="timeline-point">
                            <span class="point-number">3</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Vantagens - Seção Completamente Nova -->
        <section class="advantages-section modern-section" id="vantagens">
            <div class="section-container">
                <div class="section-header" data-aos="fade-up">
                    <span class="section-badge">Por Que Escolher?</span>
                    <h2 class="section-title">Vantagens Exclusivas do Klube Cash</h2>
                    <p class="section-description">
                        Descobri porque somos a escolha número 1 de quem quer economizar de verdade
                    </p>
                </div>
                
                <div class="advantages-grid">
                    <div class="advantage-card featured" data-aos="fade-up" data-aos-delay="100">
                        <div class="advantage-icon">
                            <svg viewBox="0 0 24 24" width="40" height="40">
                                <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" fill="none" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h3 class="advantage-title">Cashback Real</h3>
                        <p class="advantage-description">
                            Dinheiro de verdade na sua conta, não pontos que expiram ou vales que complicam sua vida.
                        </p>
                        <div class="advantage-highlight">
                            <span class="highlight-text">Até 10% de volta</span>
                        </div>
                    </div>
                    
                    <div class="advantage-card" data-aos="fade-up" data-aos-delay="200">
                        <div class="advantage-icon">
                            <svg viewBox="0 0 24 24" width="40" height="40">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" fill="none" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h3 class="advantage-title">100% Seguro</h3>
                        <p class="advantage-description">
                            Plataforma criptografada e dados protegidos. Sua segurança é nossa prioridade máxima.
                        </p>
                    </div>
                    
                    <div class="advantage-card" data-aos="fade-up" data-aos-delay="300">
                        <div class="advantage-icon">
                            <svg viewBox="0 0 24 24" width="40" height="40">
                                <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" fill="none" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h3 class="advantage-title">Instantâneo</h3>
                        <p class="advantage-description">
                            Cashback processado rapidamente. Você vê o retorno do seu dinheiro em tempo real.
                        </p>
                    </div>
                    
                    <div class="advantage-card" data-aos="fade-up" data-aos-delay="400">
                        <div class="advantage-icon">
                            <svg viewBox="0 0 24 24" width="40" height="40">
                                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" fill="none" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h3 class="advantage-title">Suporte 24/7</h3>
                        <p class="advantage-description">
                            Equipe especializada sempre pronta para ajudar você com qualquer dúvida ou problema.
                        </p>
                    </div>
                    
                    <div class="advantage-card" data-aos="fade-up" data-aos-delay="500">
                        <div class="advantage-icon">
                            <svg viewBox="0 0 24 24" width="40" height="40">
                                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l7 7z" fill="none" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h3 class="advantage-title">Sem Fidelidade</h3>
                        <p class="advantage-description">
                            Use quando quiser, como quiser. Sem contratos longos ou obrigações chatas.
                        </p>
                    </div>
                    
                    <div class="advantage-card" data-aos="fade-up" data-aos-delay="600">
                        <div class="advantage-icon">
                            <svg viewBox="0 0 24 24" width="40" height="40">
                                <path d="M3 3h18l-1 13H4L3 3zM3 3L2 1M7 13v6a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-6" fill="none" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h3 class="advantage-title">Milhares de Lojas</h3>
                        <p class="advantage-description">
                            Rede gigante de parceiros em todas as categorias que você imaginar.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Revolucionário -->
        <section class="cta-modern">
            <div class="cta-background">
                <div class="cta-shapes">
                    <div class="cta-shape cta-shape-1"></div>
                    <div class="cta-shape cta-shape-2"></div>
                </div>
            </div>
            
            <div class="cta-container" data-aos="zoom-in">
                <div class="cta-content">
                    <h2 class="cta-title">Pronto para Começar a Ganhar Dinheiro?</h2>
                    <p class="cta-description">
                        Junte-se a milhares de brasileiros que já descobriram o segredo de transformar gastos em ganhos. 
                        Seu primeiro cashback está a apenas um clique de distância.
                    </p>
                    
                    <div class="cta-stats">
                        <div class="cta-stat">
                            <div class="stat-icon">⏱️</div>
                            <div class="stat-info">
                                <div class="stat-number">2 min</div>
                                <div class="stat-text">para cadastrar</div>
                            </div>
                        </div>
                        <div class="cta-stat">
                            <div class="stat-icon">💰</div>
                            <div class="stat-info">
                                <div class="stat-number">R$ 0</div>
                                <div class="stat-text">de taxa</div>
                            </div>
                        </div>
                        <div class="cta-stat">
                            <div class="stat-icon">🚀</div>
                            <div class="stat-info">
                                <div class="stat-number">Hoje</div>
                                <div class="stat-text">comece a economizar</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="cta-actions">
                        <a href="<?php echo REGISTER_URL; ?>" class="btn btn-cta btn-large">
                            <span>Quero Meu Cashback Agora!</span>
                            <svg viewBox="0 0 24 24" width="24" height="24">
                                <path d="M5 12h14M12 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </a>
                        
                        <div class="cta-guarantee">
                            <svg viewBox="0 0 24 24" width="20" height="20">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" fill="none" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <span>100% Gratuito • Sem Pegadinhas</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Lojas Parceiras - Redesenhado -->
        <section class="partners-modern modern-section" id="parceiros">
            <div class="section-container">
                <div class="section-header" data-aos="fade-up">
                    <span class="section-badge">Nossos Parceiros</span>
                    <h2 class="section-title">Onde Você Pode Usar o Klube Cash</h2>
                    <p class="section-description">
                        Descubra algumas das incríveis lojas parceiras onde você pode ganhar cashback em suas compras
                    </p>
                </div>
                
                <?php if (!empty($partnerStores)): ?>
                    <div class="partners-showcase" data-aos="fade-up" data-aos-delay="200">
                        <?php foreach ($partnerStores as $index => $store): ?>
                            <div class="partner-item" data-aos="zoom-in" data-aos-delay="<?php echo 100 + ($index * 50); ?>">
                                <div class="partner-logo">
                                    <?php echo renderStoreLogo($store); ?>
                                </div>
                                <div class="partner-info">
                                    <h4 class="partner-name"><?php echo htmlspecialchars($store['nome_fantasia']); ?></h4>
                                    <?php if (!empty($store['categoria'])): ?>
                                        <span class="partner-category"><?php echo htmlspecialchars($store['categoria']); ?></span>
                                    <?php endif; ?>
                                    <div class="partner-cashback">
                                        <span class="cashback-label">Cashback:</span>
                                        <span class="cashback-value"><?php echo number_format($store['porcentagem_cashback'] ?? 5, 1); ?>%</span>
                                    </div>
                                </div>
                                <div class="partner-hover-effect"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="partners-footer" data-aos="fade-up" data-aos-delay="400">
                        <div class="partners-count">
                            <span class="count-number">+500</span>
                            <span class="count-text">lojas parceiras em todo Brasil</span>
                        </div>
                        <a href="<?php echo STORE_REGISTER_URL; ?>" class="btn btn-outline">
                            <span>Quero Ser Parceiro</span>
                            <svg viewBox="0 0 24 24" width="20" height="20">
                                <path d="M5 12h14M12 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="partners-empty" data-aos="fade-up">
                        <div class="empty-icon">🏪</div>
                        <h3>Em Breve: Lojas Incríveis!</h3>
                        <p>Estamos fechando parcerias com as melhores lojas para você. Em breve você terá acesso a centenas de opções para ganhar cashback!</p>
                        <a href="<?php echo STORE_REGISTER_URL; ?>" class="btn btn-primary">Seja o Primeiro Parceiro</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Depoimentos - Seção Nova -->
        <section class="testimonials-section modern-section" id="testimonials">
            <div class="section-container">
                <div class="section-header" data-aos="fade-up">
                    <span class="section-badge">Depoimentos</span>
                    <h2 class="section-title">O Que Nossos Usuários Dizem</h2>
                    <p class="section-description">
                        Veja como o Klube Cash está transformando a vida financeira de milhares de brasileiros
                    </p>
                </div>
                
                <div class="testimonials-grid">
                    <div class="testimonial-card" data-aos="fade-up" data-aos-delay="100">
                        <div class="testimonial-content">
                            <div class="testimonial-rating">
                                <span class="star">⭐</span>
                                <span class="star">⭐</span>
                                <span class="star">⭐</span>
                                <span class="star">⭐</span>
                                <span class="star">⭐</span>
                            </div>
                            <blockquote class="testimonial-text">
                                "Em 6 meses já recebi mais de R$ 500 em cashback! É dinheiro real que eu uso para outras compras. Simplesmente incrível!"
                            </blockquote>
                            <div class="testimonial-author">
                                <div class="author-avatar">M</div>
                                <div class="author-info">
                                    <div class="author-name">Maria Silva</div>
                                    <div class="author-location">São Paulo, SP</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="testimonial-card featured" data-aos="fade-up" data-aos-delay="200">
                        <div class="testimonial-content">
                            <div class="testimonial-rating">
                                <span class="star">⭐</span>
                                <span class="star">⭐</span>
                                <span class="star">⭐</span>
                                <span class="star">⭐</span>
                                <span class="star">⭐</span>
                            </div>
                            <blockquote class="testimonial-text">
                                "O processo é super simples e transparente. Não tem pegadinha, o dinheiro realmente volta para minha conta. Recomendo para todos!"
                            </blockquote>
                            <div class="testimonial-author">
                                <div class="author-avatar">J</div>
                                <div class="author-info">
                                    <div class="author-name">João Santos</div>
                                    <div class="author-location">Rio de Janeiro, RJ</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="testimonial-card" data-aos="fade-up" data-aos-delay="300">
                        <div class="testimonial-content">
                            <div class="testimonial-rating">
                                <span class="star">⭐</span>
                                <span class="star">⭐</span>
                                <span class="star">⭐</span>
                                <span class="star">⭐</span>
                                <span class="star">⭐</span>
                            </div>
                            <blockquote class="testimonial-text">
                                "Como empresária, preciso de soluções práticas. O Klube Cash me ajuda a economizar nas compras para minha empresa."
                            </blockquote>
                            <div class="testimonial-author">
                                <div class="author-avatar">A</div>
                                <div class="author-info">
                                    <div class="author-name">Ana Costa</div>
                                    <div class="author-location">Belo Horizonte, MG</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Modernizado -->
        <section class="faq-modern modern-section" id="faq">
            <div class="section-container">
                <div class="section-header" data-aos="fade-up">
                    <span class="section-badge">Tire Suas Dúvidas</span>
                    <h2 class="section-title">Perguntas Frequentes</h2>
                    <p class="section-description">
                        Encontre respostas para as principais dúvidas sobre o Klube Cash
                    </p>
                </div>
                
                <div class="faq-container" data-aos="fade-up" data-aos-delay="200">
                    <div class="faq-item">
                        <button class="faq-question" aria-expanded="false">
                            <span class="question-text">O cadastro no Klube Cash é realmente gratuito?</span>
                            <span class="question-icon">
                                <svg viewBox="0 0 24 24" width="20" height="20">
                                    <path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </span>
                        </button>
                        <div class="faq-answer">
                            <div class="answer-content">
                                <p>Sim! O cadastro no Klube Cash é 100% gratuito e sempre será. Não cobramos nenhuma taxa de adesão, mensalidade ou anuidade. Você só ganha dinheiro, nunca paga nada para participar do programa.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <button class="faq-question" aria-expanded="false">
                            <span class="question-text">Como funciona o cashback? É dinheiro real?</span>
                            <span class="question-icon">
                                <svg viewBox="0 0 24 24" width="20" height="20">
                                    <path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </span>
                        </button>
                        <div class="faq-answer">
                            <div class="answer-content">
                                <p>Sim, é dinheiro real! Quando você compra em lojas parceiras, uma porcentagem do valor volta como cashback para sua conta Klube Cash. Esse dinheiro pode ser usado em novas compras ou transferido para sua conta bancária.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <button class="faq-question" aria-expanded="false">
                            <span class="question-text">Quanto tempo demora para receber o cashback?</span>
                            <span class="question-icon">
                                <svg viewBox="0 0 24 24" width="20" height="20">
                                    <path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </span>
                        </button>
                        <div class="faq-answer">
                            <div class="answer-content">
                                <p>O cashback aparece em sua conta imediatamente após a confirmação da compra pela loja parceira. Geralmente isso acontece em até 48 horas, mas pode variar conforme cada loja.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <button class="faq-question" aria-expanded="false">
                            <span class="question-text">Posso usar o cashback em qualquer loja?</span>
                            <span class="question-icon">
                                <svg viewBox="0 0 24 24" width="20" height="20">
                                    <path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </span>
                        </button>
                        <div class="faq-answer">
                            <div class="answer-content">
                                <p>O cashback gerado em uma loja só pode ser usado na mesma loja onde foi gerado. Isso garante um relacionamento mais forte entre você e as lojas parceiras que realmente oferecem vantagens.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <button class="faq-question" aria-expanded="false">
                            <span class="question-text">Como minha loja pode participar do programa?</span>
                            <span class="question-icon">
                                <svg viewBox="0 0 24 24" width="20" height="20">
                                    <path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </span>
                        </button>
                        <div class="faq-answer">
                            <div class="answer-content">
                                <p>É muito simples! Clique em "Quero Ser Parceiro", preencha o formulário de cadastro da sua loja e nossa equipe entrará em contato para explicar todo o processo e benefícios.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <button class="faq-question" aria-expanded="false">
                            <span class="question-text">O Klube Cash é seguro para usar?</span>
                            <span class="question-icon">
                                <svg viewBox="0 0 24 24" width="20" height="20">
                                    <path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </span>
                        </button>
                        <div class="faq-answer">
                            <div class="answer-content">
                                <p>Absolutamente! Utilizamos as mais avançadas tecnologias de criptografia para proteger seus dados. Além disso, não armazenamos informações de cartão de crédito e seguimos todas as normas de segurança do mercado financeiro.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="faq-footer" data-aos="fade-up" data-aos-delay="400">
                    <div class="faq-contact">
                        <h3>Ainda tem dúvidas?</h3>
                        <p>Nossa equipe está sempre pronta para ajudar você!</p>
                        <a href="mailto:contato@klubecash.com" class="btn btn-outline">
                            <svg viewBox="0 0 24 24" width="20" height="20">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" fill="none" stroke="currentColor" stroke-width="2"/>
                                <polyline points="22,6 12,13 2,6" fill="none" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            Falar com Suporte
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer Moderno -->
    <footer class="modern-footer">
        <div class="footer-background">
            <div class="footer-pattern"></div>
        </div>
        
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="brand-logo-footer">
                        <img src="assets/images/logolaranja.png" alt="Klube Cash">
                        <span class="brand-name">Klube Cash</span>
                    </div>
                    <p class="brand-description">
                        Transformando suas compras em oportunidades de economia. 
                        O programa de cashback mais inteligente e confiável do Brasil.
                    </p>
                    <div class="social-links">
                        <a href="https://www.instagram.com/klubecash/" class="social-link" target="_blank" rel="noopener">
                            <svg viewBox="0 0 24 24" width="20" height="20">
                                <rect x="2" y="2" width="20" height="20" rx="5" ry="5" fill="none" stroke="currentColor" stroke-width="2"/>
                                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" fill="none" stroke="currentColor" stroke-width="2"/>
                                <line x1="17.5" y1="6.5" x2="17.51" y2="6.5" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </a>
                        <a href="#" class="social-link">
                            <svg viewBox="0 0 24 24" width="20" height="20">
                                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" fill="none" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </a>
                        <a href="#" class="social-link">
                            <svg viewBox="0 0 24 24" width="20" height="20">
                                <path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z" fill="none" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </a>
                    </div>
                </div>
                
                <div class="footer-links">
                    <h4 class="footer-title">Links Rápidos</h4>
                    <ul class="link-list">
                        <li><a href="#como-funciona" class="smooth-scroll">Como Funciona</a></li>
                        <li><a href="#vantagens" class="smooth-scroll">Vantagens</a></li>
                        <li><a href="#parceiros" class="smooth-scroll">Lojas Parceiras</a></li>
                        <li><a href="#faq" class="smooth-scroll">FAQ</a></li>
                        <li><a href="<?php echo STORE_REGISTER_URL; ?>">Seja Parceiro</a></li>
                    </ul>
                </div>
                
                <div class="footer-legal">
                    <h4 class="footer-title">Legal</h4>
                    <ul class="link-list">
                        <li><a href="#">Termos de Uso</a></li>
                        <li><a href="#">Política de Privacidade</a></li>
                        <li><a href="#">Política de Cookies</a></li>
                        <li><a href="#">Código de Conduta</a></li>
                        <li><a href="#">Regulamentação</a></li>
                    </ul>
                </div>
                
                <div class="footer-contact">
                    <h4 class="footer-title">Contato</h4>
                    <div class="contact-list">
                        <div class="contact-item">
                            <svg viewBox="0 0 24 24" width="18" height="18">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" fill="none" stroke="currentColor" stroke-width="2"/>
                                <polyline points="22,6 12,13 2,6" fill="none" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <a href="mailto:contato@klubecash.com">contato@klubecash.com</a>
                        </div>
                        <div class="contact-item">
                            <svg viewBox="0 0 24 24" width="18" height="18">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" fill="none" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <span>(34) 9999-9999</span>
                        </div>
                        <div class="contact-item">
                            <svg viewBox="0 0 24 24" width="18" height="18">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" fill="none" stroke="currentColor" stroke-width="2"/>
                                <circle cx="12" cy="10" r="3" fill="none" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <span>Patos de Minas, MG</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="footer-copyright">
                    <p>&copy; <?php echo date('Y'); ?> Klube Cash. Todos os direitos reservados.</p>
                </div>
                <div class="footer-badges">
                    <div class="security-badge">
                        <svg viewBox="0 0 24 24" width="16" height="16">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" fill="none" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <span>Site Seguro</span>
                    </div>
                    <div class="ssl-badge">
                        <svg viewBox="0 0 24 24" width="16" height="16">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2" fill="none" stroke="currentColor" stroke-width="2"/>
                            <circle cx="12" cy="16" r="1" fill="currentColor"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" fill="none" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <span>SSL Certificado</span>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="assets/js/index-v2.js"></script>
</body>
</html>