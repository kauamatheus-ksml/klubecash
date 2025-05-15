<?php
// index.php
// Incluir configurações do sistema
require_once './config/constants.php';

// Iniciar sessão
session_start();

// Verificar se o usuário está logado
$isLoggedIn = isset($_SESSION['user_id']);
$userType = $isLoggedIn ? $_SESSION['user_type'] : '';
$userName = $isLoggedIn ? $_SESSION['user_name'] : '';

// Determinar URL do dashboard
$dashboardURL = '';
if ($isLoggedIn) {
    if ($userType === 'admin') {
        $dashboardURL = ADMIN_DASHBOARD_URL;
    } elseif ($userType === 'cliente') {
        $dashboardURL = CLIENT_DASHBOARD_URL;
    }   elseif ($userType === 'loja') {
        $dashboardURL = STORE_DASHBOARD_URL;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Klube Cash - Programa de Cashback</title>
    <link rel="shortcut icon" type="image/jpg" href="assets/images/icons/KlubeCashLOGO.ico"/>
    <meta name="description" content="Klube Cash - O melhor programa de cashback para suas compras. Ganhe de volta parte do valor em todas as suas compras nas lojas parceiras.">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/index.css">
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</head>
<body>
    <!-- Header -->
    <header class="header" id="header">
        <div class="container">
            <nav class="nav">
                <a href="#" class="logo">
                    <img src="assets/images/logolaranja.png" alt="Klube Cash Logo" class="logo-img">
                </a>
                
                <button class="hamburger" id="hamburger" aria-label="Menu">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </button>
                
                <ul class="nav-menu" id="nav-menu">
                    <li class="nav-item"><a href="#como-funciona" class="nav-link">Como Funciona</a></li>
                    <li class="nav-item"><a href="#beneficios" class="nav-link">Benefícios</a></li>
                    <li class="nav-item"><a href="#parceiros" class="nav-link">Lojas Parceiras</a></li>
                    <li class="nav-item"><a href="#faq" class="nav-link">FAQ</a></li>
                    
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-item"><a href="<?php echo $dashboardURL; ?>" class="nav-link">Meu Painel</a></li>
                        <li class="nav-item"><a href="<?php echo SITE_URL; ?>/controllers/AuthController.php?action=logout" class="nav-link btn">Sair</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a href="<?php echo LOGIN_URL; ?>" class="nav-link">Entrar</a></li>
                        <li class="nav-item"><a href="<?php echo REGISTER_URL; ?>" class="nav-link btn">Cadastre-se</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-bg-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
        <div class="container">
            <div class="hero-content" data-aos="fade-right" data-aos-duration="1000">
                <?php if ($isLoggedIn): ?>
                    <h1 class="hero-title">Bem-vindo de volta, <span class="highlight"><?php echo htmlspecialchars($userName); ?></span>!</h1>
                    <p class="hero-subtitle">Continue aproveitando o cashback em suas compras nas lojas parceiras.</p>
                    <div class="hero-buttons">
                        <a href="<?php echo $dashboardURL; ?>" class="btn btn-white btn-with-icon">
                            <span>Acessar Meu Painel</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5l7 7-7 7"></path></svg>
                        </a>
                        <a href="#como-funciona" class="btn btn-outline-white">Saiba Mais</a>
                    </div>
                <?php else: ?>
                    <h1 class="hero-title">Ganhe <span class="highlight">dinheiro de volta</span> em cada compra</h1>
                    <p class="hero-subtitle">Junte-se a milhares de pessoas que economizam com o Klube Cash. Receba cashback em todas as compras nas lojas parceiras.</p>
                    <div class="hero-buttons">
                        <a href="<?php echo REGISTER_URL; ?>" class="btn btn-white btn-with-icon">
                            <span>Começar Agora</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5l7 7-7 7"></path></svg>
                        </a>
                        <a href="#como-funciona" class="btn btn-outline-white">Saiba Mais</a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="hero-image-container" data-aos="fade-left" data-aos-duration="1000">
                <div class="floating-animation">
                    <img src="assets/images/hero-image.png" alt="Pessoas economizando com cashback" class="hero-image">
                </div>
                <div class="hero-decoration">
                    <div class="circle-decoration"></div>
                    <div class="circle-decoration"></div>
                    <div class="circle-decoration"></div>
                </div>
            </div>
        </div>
        <div class="hero-wave">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
                <path fill="#ffffff" fill-opacity="1" d="M0,128L48,144C96,160,192,192,288,197.3C384,203,480,181,576,181.3C672,181,768,203,864,197.3C960,192,1056,160,1152,138.7C1248,117,1344,107,1392,101.3L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
            </svg>
        </div>
    </section>
    
    <!-- Como Funciona -->
    <section class="section" id="como-funciona">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <span class="section-subtitle">Processo simples</span>
                <h2>Como Funciona</h2>
                <p class="section-description">Ganhar cashback nunca foi tão fácil</p>
            </div>
            
            <div class="steps-container">
                <div class="step-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="step-icon">
                        <span>1</span>
                    </div>
                    <h3 class="step-title">Cadastre-se</h3>
                    <p>Crie sua conta gratuitamente no Klube Cash em menos de 2 minutos.</p>
                    <div class="step-indicator"></div>
                </div>
                
                <div class="step-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="step-icon">
                        <span>2</span>
                    </div>
                    <h3 class="step-title">Compre nas Lojas Parceiras</h3>
                    <p>Faça suas compras normalmente nas lojas parceiras online ou físicas.</p>
                    <div class="step-indicator"></div>
                </div>
                
                <div class="step-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="step-icon">
                        <span>3</span>
                    </div>
                    <h3 class="step-title">Receba Cashback</h3>
                    <p>O valor do cashback será creditado automaticamente em sua conta Klube Cash.</p>
                    <div class="step-indicator"></div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Benefícios -->
    <section class="section benefits" id="beneficios">
        <div class="benefits-bg"></div>
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <span class="section-subtitle">Por que nos escolher</span>
                <h2>Benefícios</h2>
                <p class="section-description">Por que escolher o Klube Cash?</p>
            </div>
            
            <div class="benefits-container">
                <div class="benefit-card" data-aos="zoom-in" data-aos-delay="100">
                    <div class="benefit-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M16 12l-4 4-4-4"></path>
                            <path d="M12 8v8"></path>
                        </svg>
                    </div>
                    <h3 class="benefit-title">Sem Valor Mínimo</h3>
                    <p>Não há valor mínimo para receber o cashback. Ganhe de volta em cada compra.</p>
                </div>
                
                <div class="benefit-card" data-aos="zoom-in" data-aos-delay="200">
                    <div class="benefit-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <h3 class="benefit-title">Rápido e Fácil</h3>
                    <p>O cashback é processado rapidamente e você pode acompanhar tudo pelo seu painel.</p>
                </div>
                
                <div class="benefit-card" data-aos="zoom-in" data-aos-delay="300">
                    <div class="benefit-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                        </svg>
                    </div>
                    <h3 class="benefit-title">Milhares de Lojas</h3>
                    <p>Compre nas melhores lojas e marcas parceiras em todo o Brasil.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="cta">
        <div class="cta-bg-shapes">
            <div class="cta-shape cta-shape-1"></div>
            <div class="cta-shape cta-shape-2"></div>
        </div>
        <div class="container">
            <div class="cta-content" data-aos="fade-up">
                <h2>Comece a economizar hoje mesmo!</h2>
                <p>Junte-se a milhares de pessoas que já economizaram com o Klube Cash. Cadastre-se gratuitamente e comece a ganhar cashback em suas compras.</p>
                <a href="<?php echo REGISTER_URL; ?>" class="btn btn-white btn-lg btn-with-icon">
                    <span>Criar Conta Grátis</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5l7 7-7 7"></path></svg>
                </a>
                <div class="cta-decoration">
                    <div class="cta-circle"></div>
                    <div class="cta-circle"></div>
                    <div class="cta-circle"></div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Lojas Parceiras -->
    <section class="section" id="parceiros">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <span class="section-subtitle">Nossa rede</span>
                <h2>Lojas Parceiras</h2>
                <p class="section-description">Algumas das principais lojas que fazem parte do nosso programa</p>
            </div>
            
            <div class="partners-grid" data-aos="fade-up">
                <!-- As logos seriam carregadas dinamicamente do banco de dados -->
                <div class="partner-card">
                    <div class="partner-logo-container">
                        <div class="partner-logo">LOGO</div>
                    </div>
                    <h4>Loja 1</h4>
                </div>
                <div class="partner-card">
                    <div class="partner-logo-container">
                        <div class="partner-logo">LOGO</div>
                    </div>
                    <h4>Loja 2</h4>
                </div>
                <div class="partner-card">
                    <div class="partner-logo-container">
                        <div class="partner-logo">LOGO</div>
                    </div>
                    <h4>Loja 3</h4>
                </div>
                <div class="partner-card">
                    <div class="partner-logo-container">
                        <div class="partner-logo">LOGO</div>
                    </div>
                    <h4>Loja 4</h4>
                </div>
                <div class="partner-card">
                    <div class="partner-logo-container">
                        <div class="partner-logo">LOGO</div>
                    </div>
                    <h4>Loja 5</h4>
                </div>
                <div class="partner-card">
                    <div class="partner-logo-container">
                        <div class="partner-logo">LOGO</div>
                    </div>
                    <h4>Loja 6</h4>
                </div>
            </div>
            
            <div class="partners-cta" data-aos="fade-up">
                <a href="<?php echo STORE_REGISTER_URL; ?>" class="btn btn-primary btn-with-icon">
                    <span>Seja um Parceiro</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5l7 7-7 7"></path></svg>
                </a>
            </div>
        </div>
    </section>
    
    <!-- FAQ -->
        <section class="section faq" id="faq">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <span class="section-subtitle">Dúvidas frequentes</span>
                <h2>Perguntas Frequentes</h2>
                <p class="section-description">Tire suas dúvidas sobre o Klube Cash</p>
            </div>
            
            <div class="accordion" data-aos="fade-up">
                <div class="accordion-item">
                    <button class="accordion-header">
                        <span>O que é cashback?</span>
                        <span class="accordion-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </span>
                    </button>
                    <div class="accordion-content">
                        <div class="accordion-content-inner">
                            <p>Cashback é um sistema onde você recebe de volta uma porcentagem do valor gasto em suas compras. É como um desconto, mas que você recebe depois da compra realizada.</p>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <button class="accordion-header">
                        <span>Como faço para receber meu cashback?</span>
                        <span class="accordion-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </span>
                    </button>
                    <div class="accordion-content">
                        <div class="accordion-content-inner">
                            <p>Após realizar uma compra em uma loja parceira, o valor do cashback é automaticamente creditado em sua conta Klube Cash em até 48 horas, dependendo da loja. Você pode acompanhar todas as suas transações e saldo pelo seu painel.</p>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <button class="accordion-header">
                        <span>O cadastro no Klube Cash é gratuito?</span>
                        <span class="accordion-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </span>
                    </button>
                    <div class="accordion-content">
                        <div class="accordion-content-inner">
                            <p>Sim, o cadastro no Klube Cash é totalmente gratuito. Não cobramos nenhuma taxa de adesão ou mensalidade.</p>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <button class="accordion-header">
                        <span>Como posso usar meu saldo de cashback?</span>
                        <span class="accordion-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </span>
                    </button>
                    <div class="accordion-content">
                        <div class="accordion-content-inner">
                            <p>Você pode transferir o saldo para sua conta bancária, converter em produtos ou usar como desconto em novas compras nas lojas parceiras. O valor mínimo para saque é de R$ 20,00.</p>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <button class="accordion-header">
                        <span>Como posso me tornar um lojista parceiro?</span>
                        <span class="accordion-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </span>
                    </button>
                    <div class="accordion-content">
                        <div class="accordion-content-inner">
                            <p>É muito simples! Basta clicar em "Seja um Parceiro" e preencher o formulário com os dados da sua loja. Nossa equipe entrará em contato para finalizar o processo.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-wave">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
                <path fill="#333333" fill-opacity="1" d="M0,256L48,261.3C96,267,192,277,288,245.3C384,213,480,139,576,122.7C672,107,768,149,864,170.7C960,192,1056,192,1152,170.7C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
            </svg>
        </div>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-column">
                    <h3>Klube Cash</h3>
                    <p>O melhor programa de cashback para suas compras. Ganhe dinheiro de volta em cada compra nas lojas parceiras.</p>
                    <div class="social-icons">
                        <a href="#" class="social-icon" aria-label="Facebook">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                            </svg>
                        </a>
                        <a href="https://www.instagram.com/klubecash/" class="social-icon" aria-label="Instagram">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                                <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                            </svg>
                        </a>
                        <a href="#" class="social-icon" aria-label="Twitter">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path>
                            </svg>
                        </a>
                    </div>
                </div>
                
                <div class="footer-column">
                    <h3>Links Úteis</h3>
                    <ul class="footer-links">
                        <li class="footer-link"><a href="#como-funciona">Como Funciona</a></li>
                        <li class="footer-link"><a href="#beneficios">Benefícios</a></li>
                        <li class="footer-link"><a href="#parceiros">Lojas Parceiras</a></li>
                        <li class="footer-link"><a href="#faq">FAQ</a></li>
                        <li class="footer-link"><a href="<?php echo STORE_REGISTER_URL; ?>">Seja um Parceiro</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Contato</h3>
                    <div class="contact-info">
                        <span class="contact-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                        </span>
                        <span>contato@klubecash.com</span>
                    </div>
                    <div class="contact-info">
                        <span class="contact-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                        </span>
                        <span>(34) 9999-9999</span>
                    </div>
                    <div class="contact-info">
                        <span class="contact-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                        </span>
                        <span>Patos de Minas, MG - Brasil</span>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Klube Cash. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>
    
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Inicializa as animações AOS
        AOS.init({
            once: true, // Animações só acontecem uma vez
            offset: 100,
            duration: 800
        });
        
        // Mobile Menu Toggle
        const hamburger = document.getElementById('hamburger');
        const navMenu = document.getElementById('nav-menu');
        
        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            navMenu.classList.toggle('active');
        });
        
        // Accordion Function for FAQ
        const accordionItems = document.querySelectorAll('.accordion-item');
        
        accordionItems.forEach(item => {
            const header = item.querySelector('.accordion-header');
            const content = item.querySelector('.accordion-content');

            // Garantir que o conteúdo comece fechado
            content.style.maxHeight = "0px";
            
             header.addEventListener('click', () => {
                // Verificar se este item está ativo
                const isActive = item.classList.contains('active');
                
                // Fechar todos os itens primeiro
                accordionItems.forEach(accordionItem => {
                    accordionItem.classList.remove('active');
                    accordionItem.querySelector('.accordion-content').style.maxHeight = "0px";
                });
                
                // Se o item clicado não estava ativo, abri-lo
                if (!isActive) {
                    item.classList.add('active');
                    content.style.maxHeight = content.scrollHeight + "px";
                }
            });
        });
        
        // Smooth Scroll para Navigation
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                
                // Fechar menu mobile se estiver aberto
                hamburger.classList.remove('active');
                navMenu.classList.remove('active');
                
                const target = document.querySelector(this.getAttribute('href'));
                
                if (target) {
                    window.scrollTo({
                        top: target.offsetTop - 70, // Ajuste para altura do header
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Adiciona classe para animação no scroll
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.header');
            const scrollPosition = window.scrollY;
            
            if (scrollPosition > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>